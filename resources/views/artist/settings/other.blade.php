@extends('layouts.new-artist-dashboard-layout')

@section('title', 'Account')

@section('styles')
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet">
<style>
  .rg-page{max-width:620px}
  .rg-page .fl{font-size:13px;font-weight:700;margin-bottom:6px;display:block}
  .rg-page .help{font-size:12px;color:var(--faint);margin-top:6px;line-height:1.4}
  .rg-page .ch h3{font-size:16px;font-weight:700;margin:0}
  .rg-page .ch .faint{font-size:12.5px;font-weight:400;margin-top:2px;color:var(--faint)}
  .rg-page .btn{
    display:inline-flex;align-items:center;justify-content:center;gap:8px;
    background:var(--ink);color:#fff;border:0;border-radius:10px;padding:10px 16px;
    font-family:inherit;font-weight:600;font-size:13.5px;line-height:normal;
    white-space:nowrap;cursor:pointer;-webkit-appearance:none;appearance:none
  }
  .rg-page .btn .ms{font-size:18px}
  .rg-page .btn:disabled{opacity:.55;cursor:not-allowed}
  .rg-err{color:var(--red);font-size:12.5px;margin-top:6px}
  .rg-err[hidden]{display:none!important}
  .rg-toast{
    position:fixed;top:22px;right:24px;background:#1A1A1A;color:#fff;padding:12px 18px;border-radius:10px;
    font-weight:600;font-size:14px;z-index:400;box-shadow:0 10px 30px rgba(0,0,0,.22);
    display:flex;gap:10px;align-items:center;opacity:0;transform:translateX(calc(100% + 40px));
    transition:opacity .25s,transform .35s cubic-bezier(.2,.8,.2,1);pointer-events:none
  }
  .rg-toast.on{opacity:1;transform:none}
  .rg-toast .ms{color:#3DD68C;font-size:20px}

  .rg-page .select2-container{width:100%!important;z-index:1}
  .rg-page .select2-container--open{z-index:10060!important}
  .select2-container--open{z-index:10060!important}
  .rg-page .select2-container--default .select2-selection--single{
    min-height:42px;padding:4px 10px;border-radius:10px;
    border:1px solid var(--line)!important;background:#fff!important
  }
  .rg-page .select2-container--default .select2-selection--single .select2-selection__rendered{
    line-height:2rem;padding-left:4px;color:var(--ink);font-size:13.5px;font-family:inherit
  }
  .rg-page .select2-container--default .select2-selection--single .select2-selection__arrow{height:40px}
  .rg-page .select2-container--default.select2-container--focus .select2-selection--single,
  .rg-page .select2-container--default.select2-container--open .select2-selection--single{
    border-color:var(--pri)!important;box-shadow:0 0 0 3px #F3E8FF
  }
  .select2-dropdown{
    border-radius:10px;border-color:var(--line);overflow:hidden;
    font-family:inherit;font-size:13.5px
  }
  .select2-container--default .select2-results__option--highlighted[aria-selected]{
    background-color:var(--pri)!important
  }
  .select2-container--default .select2-search--dropdown .select2-search__field{
    border-radius:8px;border-color:var(--line);font-family:inherit
  }
  .rg-page .select2-container--default .select2-selection--single.s2-err{
    border-color:var(--red)!important;box-shadow:0 0 0 3px #FDECEC
  }
  .rg-page .select2-container--default .select2-selection--single.s2-flash{
    box-shadow:0 0 0 3px #E9DDF7
  }
  @media (max-width:900px){
    .rg-toast{left:16px;right:16px;top:74px}
  }
</style>
@endsection

@section('content')
@php
  $currentDateFormat = $userDetail->date_time_format ?? 'DD/MM/YYYY';
  $currentSizeUnit = $userDetail->size_unit ?? 'cm';
  $currentTimezone = $userDetail->timezone ?? 'UTC';
  $countryNameByCode = collect($registrationCountries)->mapWithKeys(fn ($c) => [strtoupper($c['code']) => $c['name']])->all();
@endphp

  <div class="head">
    <div>
      <h1>
        Account
        <a class="help-q"
           href="https://help.inkjin.com/en/articles/17201441-dashboard-account-regional"
           target="_blank"
           rel="noopener"
           data-help-article="09d-account-regional"
           title="Help with this page"
           aria-label="Help with this page">
          <span class="ms">help</span>
        </a>
      </h1>
      <div class="sub">Your login, contact details, studio and region. Set once, rarely changed.</div>
    </div>
  </div>

  @include('artist.partials.profile-settings-tabs', ['activeProfileTab' => 'regional'])

  <form id="regionalForm" method="post" action="{{ route('settings.regional.update') }}" class="rg-page" novalidate>
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
        <select class="js-select2" id="country" name="country" aria-label="Country">
          @foreach($registrationCountries as $country)
            <option value="{{ $country['code'] }}" {{ strtoupper((string) ($currentCountry ?? '')) === strtoupper((string) $country['code']) ? 'selected' : '' }}>
              {{ $country['name'] }}
            </option>
          @endforeach
        </select>
        <div class="help">Changing your country updates the timezone, date format and units below</div>
        <div class="rg-err" id="country_error" hidden></div>

        <label class="fl" for="timezone" style="margin-top:14px">Timezone</label>
        <select class="js-select2" id="timezone" name="timezone" aria-label="Timezone">
          @foreach($timezones as $timezone)
            <option value="{{ $timezone }}" {{ $currentTimezone === $timezone ? 'selected' : '' }}>
              {{ $timezone }}
            </option>
          @endforeach
        </select>
        <div class="help" id="rg-th">Booking times and availability show in this timezone</div>
        <div class="rg-err" id="timezone_error" hidden></div>

        <label class="fl" for="date_time_format" style="margin-top:14px">Date format</label>
        <select class="js-select2" id="date_time_format" name="date_time_format" aria-label="Date format">
          <option value="DD/MM/YYYY" {{ $currentDateFormat === 'DD/MM/YYYY' ? 'selected' : '' }}>DD/MM/YYYY · 31/12/2026</option>
          <option value="MM/DD/YYYY" {{ $currentDateFormat === 'MM/DD/YYYY' ? 'selected' : '' }}>MM/DD/YYYY · 12/31/2026</option>
          <option value="YYYY-MM-DD" {{ $currentDateFormat === 'YYYY-MM-DD' ? 'selected' : '' }}>YYYY-MM-DD · 2026-12-31</option>
        </select>
        <div class="help" id="rg-dh">Uses this format by default</div>
        <div class="rg-err" id="date_time_format_error" hidden></div>

        <label class="fl" for="size_unit" style="margin-top:14px">Units</label>
        <select class="js-select2" id="size_unit" name="size_unit" aria-label="Units">
          <option value="cm" {{ $currentSizeUnit === 'cm' ? 'selected' : '' }}>Metric · cm</option>
          <option value="in" {{ $currentSizeUnit === 'in' ? 'selected' : '' }}>Imperial · inches</option>
        </select>
        <div class="help" id="rg-uh">Used for tattoo sizes</div>
        <div class="rg-err" id="size_unit_error" hidden></div>
      </div>
    </div>

    <div class="row" style="justify-content:flex-start;margin-top:4px">
      <button type="submit" class="btn" id="saveRegionalBtn">
        <span class="ms">save</span><span id="saveRegionalLabel">Save changes</span>
      </button>
    </div>
  </form>

  <div class="rg-toast" id="regionalToast" role="status">
    <span class="ms">check_circle</span>
    <span id="regionalToastMsg">Regional settings updated</span>
  </div>
@endsection

@section('scripts')
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script>
(function () {
  var form = document.getElementById('regionalForm');
  var btn = document.getElementById('saveRegionalBtn');
  var label = document.getElementById('saveRegionalLabel');
  var toast = document.getElementById('regionalToast');
  var toastMsg = document.getElementById('regionalToastMsg');
  var toastTimer = null;
  var countryDefaults = @json($countryDefaults ?? []);
  var countryNames = @json($countryNameByCode ?? []);
  var fields = ['country', 'timezone', 'date_time_format', 'size_unit'];
  var $ = window.jQuery;

  function showToast(msg) {
    if (!toast || !toastMsg) return;
    toastMsg.textContent = msg || 'Regional settings updated';
    toast.classList.add('on');
    clearTimeout(toastTimer);
    toastTimer = setTimeout(function () { toast.classList.remove('on'); }, 2800);
  }

  function selectionFor(id) {
    if (!$ || !$('#' + id).hasClass('select2-hidden-accessible')) return null;
    return $('#' + id).next('.select2-container').find('.select2-selection');
  }

  function clearError(name) {
    var err = document.getElementById(name + '_error');
    if (err) { err.hidden = true; err.textContent = ''; }
    var selection = selectionFor(name);
    if (selection) selection.removeClass('s2-err');
  }

  function setError(name, message) {
    var err = document.getElementById(name + '_error');
    if (err) { err.textContent = message || ''; err.hidden = !message; }
    var selection = selectionFor(name);
    if (selection) {
      if (message) selection.addClass('s2-err');
      else selection.removeClass('s2-err');
    }
  }

  function clearAll() {
    fields.forEach(clearError);
  }

  function countryLabel(code) {
    return countryNames[String(code || '').toUpperCase()] || code || 'This country';
  }

  function defaultsFor(code) {
    return countryDefaults[String(code || '').toUpperCase()] || null;
  }

  function val(id) {
    return $ ? $('#' + id).val() : (document.getElementById(id)?.value || '');
  }

  function setVal(id, value) {
    if ($ && $('#' + id).length) {
      $('#' + id).val(value).trigger('change.select2');
      return;
    }
    var el = document.getElementById(id);
    if (el) el.value = value;
  }

  function hints() {
    var code = val('country');
    var d = defaultsFor(code);
    var name = countryLabel(code);
    var df = val('date_time_format');
    var un = val('size_unit');
    if (!d) {
      document.getElementById('rg-th').textContent = 'Booking times and availability show in this timezone';
      document.getElementById('rg-dh').textContent = name + ' uses this format by default';
      document.getElementById('rg-uh').textContent = 'Used for tattoo sizes';
      return;
    }
    document.getElementById('rg-th').textContent = 'Booking times and availability show in this timezone';
    document.getElementById('rg-dh').textContent = df === d.date_time_format
      ? name + ' uses this format by default'
      : 'Default for ' + name + ' is ' + d.date_time_format;
    document.getElementById('rg-uh').textContent = 'Used for tattoo sizes' + (
      un === d.size_unit
        ? ''
        : ' · default for ' + name + ' is ' + (d.size_unit === 'cm' ? 'metric' : 'imperial')
    );
  }

  function flash(id) {
    var selection = selectionFor(id);
    if (!selection) return;
    selection.addClass('s2-flash');
    setTimeout(function () { selection.removeClass('s2-flash'); }, 900);
  }

  function applyCountry(code, silent) {
    var d = defaultsFor(code);
    if (!d) { hints(); return; }
    if (d.timezone) setVal('timezone', d.timezone);
    if (d.date_time_format) setVal('date_time_format', d.date_time_format);
    if (d.size_unit) setVal('size_unit', d.size_unit);
    hints();
    if (!silent) ['timezone', 'date_time_format', 'size_unit'].forEach(flash);
  }

  if ($ && $.fn && $.fn.select2) {
    $('.js-select2').select2({
      width: '100%',
      dropdownParent: $('body'),
      minimumResultsForSearch: 8,
    });

    fields.forEach(function (id) {
      $('#' + id).on('change', function () {
        clearError(id);
        if (id === 'country') applyCountry(this.value, false);
        else hints();
      });
    });
  } else {
    fields.forEach(function (name) {
      document.getElementById(name)?.addEventListener('change', function () {
        clearError(name);
        if (name === 'country') applyCountry(this.value, false);
        else hints();
      });
    });
  }

  hints();

  if (!form) return;

  form.addEventListener('submit', function (e) {
    e.preventDefault();
    clearAll();

    btn.disabled = true;
    if (label) label.textContent = 'Saving…';

    fetch(form.action, {
      method: 'POST',
      headers: {
        'Accept': 'application/json',
        'X-Requested-With': 'XMLHttpRequest',
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
      },
      body: new FormData(form),
    })
      .then(function (res) {
        return res.json().then(function (data) {
          return { ok: res.ok, status: res.status, data: data };
        }).catch(function () {
          return { ok: res.ok, status: res.status, data: {} };
        });
      })
      .then(function (result) {
        if (result.ok && result.data && result.data.success) {
          showToast(result.data.message || 'Regional settings updated');
          return;
        }
        if (result.status === 422 && result.data && result.data.errors) {
          Object.keys(result.data.errors).forEach(function (field) {
            setError(field, result.data.errors[field][0]);
          });
          var firstErr = form.querySelector('.rg-err:not([hidden])');
          if (firstErr) firstErr.scrollIntoView({ behavior: 'smooth', block: 'center' });
          return;
        }
        showToast((result.data && result.data.message) || 'Could not save. Try again.');
      })
      .catch(function () {
        showToast('Network error. Try again.');
      })
      .finally(function () {
        btn.disabled = false;
        if (label) label.textContent = 'Save changes';
      });
  });
})();
</script>
@endsection
