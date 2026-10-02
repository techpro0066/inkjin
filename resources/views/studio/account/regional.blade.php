@extends('layouts.studio-dashboard-layout')

@section('title', 'Account · Regional')

@push('styles')
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet">
<style>
  .select2-container { width: 100% !important; z-index: 1; }
  .select2-container--open { z-index: 10060 !important; }
  .select2-container--default .select2-selection--single {
    min-height: 42px;
    padding: 4px 10px;
    border-radius: 10px;
    border: 1px solid var(--line) !important;
    background: #fff !important;
  }
  .select2-container--default .select2-selection--single .select2-selection__rendered {
    line-height: 2rem;
    padding-left: 4px;
    color: var(--ink);
    font-size: 13.5px;
  }
  .select2-container--default .select2-selection--single .select2-selection__arrow { height: 40px; }
  .select2-container--default.select2-container--focus .select2-selection--single,
  .select2-container--default.select2-container--open .select2-selection--single {
    border-color: var(--pri) !important;
    box-shadow: 0 0 0 3px #F3E8FF;
  }
  .select2-dropdown {
    border-radius: 10px;
    border-color: var(--line);
    overflow: hidden;
    font-family: inherit;
    font-size: 13.5px;
  }
  .select2-container--default .select2-results__option--highlighted[aria-selected] {
    background-color: var(--pri) !important;
  }
  .select2-container--default .select2-search--dropdown .select2-search__field {
    border-radius: 8px;
    border-color: var(--line);
  }
  .select2-container--default .select2-selection--single.s2-err {
    border-color: #C62828 !important;
    box-shadow: 0 0 0 3px #FDECEC;
  }
  .field-err { display: none; color: #C62828; font-size: 12px; margin-top: 6px; }
</style>
@endpush

@section('content')
  <div class="head">
    <div>
      <h1>Account</h1>
      <div class="sub">Your login and your studio's details.</div>
    </div>
  </div>

  @include('studio.account._tabs')

  <form id="studioRegionalForm" method="POST" action="{{ route('studio.account.regional.update') }}" novalidate style="max-width:620px">
    @csrf

    <div class="card" style="margin-bottom:14px">
      <div class="ch">
        <div>
          <h3>Regional settings</h3>
          <div class="faint" style="font-size:12.5px;margin-top:2px">Picked automatically at sign-up. Change them here.</div>
        </div>
      </div>
      <div style="padding:18px 22px">
        <label class="fl" for="country" style="margin-top:0">Country</label>
        <select id="country" name="country" class="js-select2">
          @foreach($registrationCountries as $country)
            <option value="{{ $country['code'] }}" {{ strtoupper((string) ($currentCountry ?? '')) === strtoupper((string) $country['code']) ? 'selected' : '' }}>{{ $country['name'] }}</option>
          @endforeach
        </select>
        <div id="country_error" class="field-err"></div>
        <div class="help">Changing your country updates the timezone, date format and units below. Your currency is set in Money &gt; Payouts.</div>

        <label class="fl" for="timezone" style="margin-top:14px">Timezone</label>
        <select id="timezone" name="timezone" class="js-select2">
          @foreach($timezones as $timezone)
            <option value="{{ $timezone }}" {{ ($currentTimezone ?? 'UTC') === $timezone ? 'selected' : '' }}>{{ str_replace('_', ' ', $timezone) }}</option>
          @endforeach
        </select>
        <div id="timezone_error" class="field-err"></div>
        <div class="help">Booking times and availability show in this timezone</div>

        <label class="fl" for="date_time_format" style="margin-top:14px">Date format</label>
        <select id="date_time_format" name="date_time_format" class="js-select2">
          <option value="DD/MM/YYYY" {{ ($currentDateFormat ?? 'DD/MM/YYYY') === 'DD/MM/YYYY' ? 'selected' : '' }}>DD/MM/YYYY — 31/12/2026</option>
          <option value="MM/DD/YYYY" {{ ($currentDateFormat ?? '') === 'MM/DD/YYYY' ? 'selected' : '' }}>MM/DD/YYYY — 12/31/2026</option>
          <option value="YYYY-MM-DD" {{ ($currentDateFormat ?? '') === 'YYYY-MM-DD' ? 'selected' : '' }}>YYYY-MM-DD — 2026-12-31</option>
        </select>
        <div id="date_time_format_error" class="field-err"></div>

        <label class="fl" for="size_unit" style="margin-top:14px">Units</label>
        <select id="size_unit" name="size_unit" class="js-select2">
          <option value="cm" {{ ($currentSizeUnit ?? 'cm') === 'cm' ? 'selected' : '' }}>Metric (cm)</option>
          <option value="in" {{ ($currentSizeUnit ?? '') === 'in' ? 'selected' : '' }}>Imperial (in)</option>
        </select>
        <div id="size_unit_error" class="field-err"></div>
        <div class="help">Used for tattoo sizes</div>
      </div>
    </div>

    <div class="pgsaverow">
      <button class="btn pgsave" type="submit" id="saveRegionalBtn"><span class="ms">save</span>Save changes</button>
    </div>
  </form>
@endsection

@push('scripts')
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script>
(function () {
  var form = document.getElementById('studioRegionalForm');
  var btn = document.getElementById('saveRegionalBtn');
  var defaultBtnHtml = btn ? btn.innerHTML : '';
  var countryDefaults = @json($countryDefaults ?? []);
  var fields = ['country', 'timezone', 'date_time_format', 'size_unit'];
  var $ = window.jQuery;

  function select2Selection(id) {
    if (!$ || !$('#' + id).hasClass('select2-hidden-accessible')) return null;
    return $('#' + id).next('.select2-container').find('.select2-selection');
  }

  function clearFieldError(name) {
    var el = document.getElementById(name + '_error');
    if (el) {
      el.textContent = '';
      el.style.display = 'none';
    }
    var selection = select2Selection(name);
    if (selection) selection.removeClass('s2-err');
  }

  function setFieldError(name, message) {
    var el = document.getElementById(name + '_error');
    if (el) {
      el.textContent = message || '';
      el.style.display = message ? 'block' : 'none';
    }
    var selection = select2Selection(name);
    if (selection) {
      if (message) selection.addClass('s2-err');
      else selection.removeClass('s2-err');
    }
  }

  function clearAllErrors() {
    fields.forEach(clearFieldError);
  }

  function fieldErrorMessage(errors, name) {
    if (!errors) return '';
    if (errors[name] && errors[name][0]) return errors[name][0];
    return '';
  }

  function applyCountryDefaults(countryCode) {
    var defaults = countryDefaults[String(countryCode || '').toUpperCase()];
    if (!defaults) return;

    if ($ && $('#timezone').length) {
      $('#timezone').val(defaults.timezone).trigger('change');
    }
    if ($ && $('#date_time_format').length) {
      $('#date_time_format').val(defaults.date_time_format).trigger('change');
    }
    if ($ && $('#size_unit').length && defaults.size_unit) {
      $('#size_unit').val(defaults.size_unit).trigger('change');
    }
  }

  if ($ && $.fn && $.fn.select2) {
    $('.js-select2').select2({
      width: '100%',
      dropdownParent: $('body'),
      minimumResultsForSearch: 8,
    });

    fields.forEach(function (id) {
      $('#' + id).on('change', function () {
        clearFieldError(id);
      });
    });

    $('#country').on('change', function () {
      applyCountryDefaults(this.value);
    });
  }

  if (!form) return;

  form.addEventListener('submit', function (e) {
    e.preventDefault();
    clearAllErrors();

    if (btn) {
      btn.disabled = true;
      btn.innerHTML = '<span class="ms">hourglass_top</span>Saving…';
    }

    fetch(form.action, {
      method: 'POST',
      headers: {
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || form.querySelector('input[name="_token"]').value,
        'X-Requested-With': 'XMLHttpRequest',
        'Accept': 'application/json'
      },
      body: new FormData(form)
    })
      .then(function (response) {
        return response.json().then(function (data) {
          return { ok: response.ok, status: response.status, data: data };
        }).catch(function () {
          return { ok: false, status: response.status, data: {} };
        });
      })
      .then(function (result) {
        if (result.ok && result.data && result.data.success) {
          if (window.bpToast) window.bpToast('Changes saved');
          return;
        }

        var errors = (result.data && result.data.errors) || {};
        var shown = false;
        fields.forEach(function (name) {
          var msg = fieldErrorMessage(errors, name);
          if (msg) {
            setFieldError(name, msg);
            shown = true;
          }
        });

        if (!shown && window.bpToast) {
          window.bpToast((result.data && result.data.message) || 'Could not save settings.', true);
        }
      })
      .catch(function () {
        if (window.bpToast) window.bpToast('Something went wrong. Please try again.', true);
      })
      .finally(function () {
        if (btn) {
          btn.disabled = false;
          btn.innerHTML = defaultBtnHtml;
        }
      });
  });
})();
</script>
@endpush
