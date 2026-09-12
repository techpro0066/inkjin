@extends('layouts.artist_dashboard_layout')

@section('title', 'Regional settings')

@section('styles')
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet">
<style>
  .select2-container { width: 100% !important; z-index: 1; }
  .select2-container--open { z-index: 10060 !important; }
  .select2-container--default .select2-selection--single {
    min-height: 48px;
    padding: 6px 12px;
    border-radius: 0.75rem;
    border: 1px solid rgba(202,196,211,0.5) !important;
    background: #fff !important;
  }
  .select2-container--default .select2-selection--single .select2-selection__rendered {
    line-height: 2.25rem;
    padding-left: 4px;
    color: #1c1b21;
  }
  .select2-container--default .select2-selection--single .select2-selection__arrow { height: 46px; }
  .select2-container--default.select2-container--focus .select2-selection--single,
  .select2-container--default.select2-container--open .select2-selection--single {
    border-color: #310f7a !important;
    box-shadow: 0 0 0 2px rgba(49,15,122,0.25);
  }
  .select2-dropdown { border-radius: 0.75rem; border-color: rgba(202,196,211,0.5); overflow: hidden; }
  .select2-container--default .select2-results__option--highlighted[aria-selected] { background-color: #310f7a !important; }
  .select2-container--default .select2-search--dropdown .select2-search__field {
    border-radius: 0.5rem;
    border-color: rgba(202,196,211,0.5);
  }

  @media (max-width: 1023px) {
    .main-content { overflow-x: hidden; padding: 16px; padding-top: 70px; }
    body { overflow-x: hidden; }
  }
</style>
@endsection

@section('content')
<main class="main-content flex-1 min-h-screen flex flex-col">
  <form id="otherSettingsForm" class="contents">
    @csrf
    <div class="flex-1 p-6 md:p-10 lg:p-12 max-w-4xl">
      @include('artist.partials.settings-tabs', ['activeTab' => 'regional'])

      <div class="mb-8">
        <h2 class="text-3xl font-extrabold text-on-surface tracking-tight">Regional settings</h2>
        <p class="text-on-surface-variant mt-1">Override the regional preferences selected automatically during setup.</p>
      </div>

      <div id="otherSuccessAlert" class="hidden mb-6 rounded-xl border border-emerald-200 bg-emerald-50 text-emerald-800 px-4 py-3 text-sm"></div>
      <div id="otherErrorAlert" class="hidden mb-6 rounded-xl border border-error/30 bg-error/10 text-error px-4 py-3 text-sm"></div>

      <div class="bg-surface-container-low rounded-2xl p-6 space-y-6 max-w-2xl">
        <div>
          <label for="country" class="block text-sm font-semibold text-on-surface mb-2">Country <span class="text-error">*</span></label>
          <select id="country" name="country" class="js-select2 w-full text-sm border border-outline-variant/30 rounded-xl px-4 py-3 bg-white text-on-surface cursor-pointer focus:outline-none focus:ring-2 focus:ring-primary/30">
            @foreach($registrationCountries as $country)
              <option value="{{ $country['code'] }}" {{ strtoupper((string) ($currentCountry ?? '')) === strtoupper((string) $country['code']) ? 'selected' : '' }}>{{ $country['name'] }}</option>
            @endforeach
          </select>
          <p class="text-xs text-on-surface-variant mt-1.5">Changing your country updates the timezone, date format, and units below.</p>
          <p id="country_error" class="hidden text-error text-xs mt-1.5"></p>
        </div>

        <div>
          <label for="timezone" class="block text-sm font-semibold text-on-surface mb-2">Timezone <span class="text-error">*</span></label>
          <select id="timezone" name="timezone" class="js-select2 w-full text-sm border border-outline-variant/30 rounded-xl px-4 py-3 bg-white text-on-surface cursor-pointer focus:outline-none focus:ring-2 focus:ring-primary/30">
            @foreach($timezones as $timezone)
              <option value="{{ $timezone }}" {{ ($userDetail->timezone ?? 'UTC') === $timezone ? 'selected' : '' }}>{{ str_replace('_', ' ', $timezone) }}</option>
            @endforeach
          </select>
          <p class="text-xs text-on-surface-variant mt-1.5">Booking times and availability will be displayed in this timezone.</p>
          <p id="timezone_error" class="hidden text-error text-xs mt-1.5"></p>
        </div>

        <div>
          <label for="date_time_format" class="block text-sm font-semibold text-on-surface mb-2">Date Format <span class="text-error">*</span></label>
          <select id="date_time_format" name="date_time_format" class="js-select2 w-full text-sm border border-outline-variant/30 rounded-xl px-4 py-3 bg-white text-on-surface cursor-pointer focus:outline-none focus:ring-2 focus:ring-primary/30">
            <option value="DD/MM/YYYY" {{ ($userDetail->date_time_format ?? 'DD/MM/YYYY') === 'DD/MM/YYYY' ? 'selected' : '' }}>DD/MM/YYYY — 31/12/2026</option>
            <option value="MM/DD/YYYY" {{ ($userDetail->date_time_format ?? '') === 'MM/DD/YYYY' ? 'selected' : '' }}>MM/DD/YYYY — 12/31/2026</option>
            <option value="YYYY-MM-DD" {{ ($userDetail->date_time_format ?? '') === 'YYYY-MM-DD' ? 'selected' : '' }}>YYYY-MM-DD — 2026-12-31</option>
          </select>
          <p id="date_time_format_error" class="hidden text-error text-xs mt-1.5"></p>
        </div>

        <div>
          <label class="block text-sm font-semibold text-on-surface mb-2">Units <span class="text-error">*</span></label>
          <div class="inline-flex bg-surface-container-highest rounded-xl p-1">
            <button type="button" data-unit="cm" class="unit-option px-4 py-2 rounded-lg text-sm font-semibold transition-colors">Centimeters (cm)</button>
            <button type="button" data-unit="in" class="unit-option px-4 py-2 rounded-lg text-sm font-semibold transition-colors">Inches (in)</button>
          </div>
          <input type="hidden" id="size_unit" name="size_unit" value="{{ $userDetail->size_unit ?? 'cm' }}">
          <p class="text-xs text-on-surface-variant mt-1.5">Used for design sizes throughout your public page and booking forms.</p>
          <p id="size_unit_error" class="hidden text-error text-xs mt-1.5"></p>
        </div>
      </div>
    </div>

    <div class="sticky bottom-0 bg-surface border-t border-outline-variant/10 px-6 md:px-10 lg:px-12 py-5 flex justify-end">
      <button type="submit" id="saveOtherBtn" class="inline-flex items-center gap-2 bg-gradient-to-br from-primary to-primary-container text-white font-bold py-3 px-8 rounded-xl shadow-lg shadow-primary/20 hover:opacity-90 disabled:opacity-60">
        <span class="material-symbols-outlined text-lg">save</span> Save Changes
      </button>
    </div>
  </form>
</main>
@endsection

@section('scripts')
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script>
  (function () {
    const form = document.getElementById('otherSettingsForm');
    const sizeInput = document.getElementById('size_unit');
    const saveButton = document.getElementById('saveOtherBtn');
    const successAlert = document.getElementById('otherSuccessAlert');
    const errorAlert = document.getElementById('otherErrorAlert');
    const countryDefaults = @json($countryDefaults ?? []);
    const $ = window.jQuery;

    function select2SelectionFor(id) {
      if (!$ || !$('#' + id).hasClass('select2-hidden-accessible')) return null;
      return $('#' + id).next('.select2-container').find('.select2-selection');
    }

    function renderUnit() {
      document.querySelectorAll('.unit-option').forEach(function (button) {
        const active = button.dataset.unit === sizeInput.value;
        button.classList.toggle('bg-primary', active);
        button.classList.toggle('text-white', active);
        button.classList.toggle('text-on-surface-variant', !active);
      });
    }

    function clearFieldError(fieldId) {
      const field = document.getElementById(fieldId);
      if (field) {
        field.classList.remove('border-error');
        const selection = select2SelectionFor(fieldId);
        if (selection) selection.removeClass('ring-2 ring-error/40');
      }
      const error = document.getElementById(fieldId + '_error');
      if (error) {
        error.textContent = '';
        error.classList.add('hidden');
      }
    }

    function clearErrors() {
      errorAlert.classList.add('hidden');
      form.querySelectorAll('[id$="_error"]').forEach(function (element) {
        element.textContent = '';
        element.classList.add('hidden');
      });
      form.querySelectorAll('select').forEach(function (element) {
        element.classList.remove('border-error');
        const selection = select2SelectionFor(element.id);
        if (selection) selection.removeClass('ring-2 ring-error/40');
      });
    }

    function applyCountryDefaults(countryCode) {
      const defaults = countryDefaults[String(countryCode || '').toUpperCase()];
      if (!defaults) return;

      if ($ && $('#timezone').length) {
        $('#timezone').val(defaults.timezone).trigger('change');
      }
      if ($ && $('#date_time_format').length) {
        $('#date_time_format').val(defaults.date_time_format).trigger('change');
      }
      if (defaults.size_unit) {
        sizeInput.value = defaults.size_unit;
        renderUnit();
      }
    }

    document.querySelectorAll('.unit-option').forEach(function (button) {
      button.addEventListener('click', function () {
        sizeInput.value = button.dataset.unit;
        document.getElementById('size_unit_error').classList.add('hidden');
        renderUnit();
      });
    });

    if ($ && $.fn && $.fn.select2) {
      $('.js-select2').select2({
        width: '100%',
        dropdownParent: $('body'),
        minimumResultsForSearch: 8,
      });
      $('#timezone, #date_time_format, #country').on('change', function () {
        clearFieldError(this.id);
      });

      $('#country').on('change', function () {
        applyCountryDefaults(this.value);
      });
    }

    form.addEventListener('submit', async function (event) {
      event.preventDefault();
      clearErrors();
      successAlert.classList.add('hidden');
      saveButton.disabled = true;

      try {
        const response = await fetch(@json(route('settings.regional.update')), {
          method: 'POST',
          headers: {
            'X-CSRF-TOKEN': @json(csrf_token()),
            'X-Requested-With': 'XMLHttpRequest',
            'Accept': 'application/json',
          },
          body: new FormData(form),
        });
        const data = await response.json();

        if (!response.ok) {
          if (response.status === 422 && data.errors) {
            Object.entries(data.errors).forEach(function ([key, messages]) {
              const error = document.getElementById(key + '_error');
              if (error) {
                error.textContent = messages[0];
                error.classList.remove('hidden');
              }
              const field = document.getElementById(key);
              if (field) {
                field.classList.add('border-error');
                const selection = select2SelectionFor(key);
                if (selection) selection.addClass('ring-2 ring-error/40');
              }
            });
            return;
          }
          throw new Error(data.message || 'Could not save settings.');
        }

        successAlert.textContent = data.message;
        successAlert.classList.remove('hidden');
        if (typeof showSaveToast === 'function') showSaveToast();
      } catch (error) {
        errorAlert.textContent = error.message || 'Network error. Please try again.';
        errorAlert.classList.remove('hidden');
      } finally {
        saveButton.disabled = false;
      }
    });

    renderUnit();
  })();
</script>
@endsection
