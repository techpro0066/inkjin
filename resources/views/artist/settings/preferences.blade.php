@extends('layouts.dashboard_layout')

@section('title', 'Preferences')

@push('styles')
<!-- Dropify CSS -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/dropify@0.2.2/dist/css/dropify.min.css">

<!-- Select2 CSS -->
<link rel="stylesheet" href="{{ asset('assets/vendor/libs/select2/select2.css') }}" />

<style>
  .dropify-wrapper .dropify-message p {
    font-size: 18px !important;
  }
</style>
@endpush

@section('content')
<div class="container-xxl flex-grow-1 container-p-y">
  <h4 class="fw-bold py-3 mb-4">
    <span class="text-muted fw-light">Settings /</span> Preferences
  </h4>

  @if (session('status') === 'preferences-updated')
    <div class="alert alert-success alert-dismissible fade show" role="alert">
      <i class="ti ti-check-circle me-2"></i>
      Preferences updated successfully!
      <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
  @endif

  <!-- Success message container for AJAX preferences update -->
  <div id="preferences-success-alert" class="alert alert-success alert-dismissible fade" role="alert" style="display: none;">
    <i class="ti ti-check-circle me-2"></i>
    <span>Preferences updated successfully!</span>
    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
  </div>

  <div class="row">
    <div class="col-12">
      <div class="card">
        <div class="card-header">
          <h5 class="card-title mb-0">Preferences</h5>
          <p class="text-muted mb-0">Update your preferences and settings</p>
        </div>
        <div class="card-body">
          <form method="POST" action="{{ route('settings.preferences.update') }}" id="preferencesForm" enctype="multipart/form-data">
            @csrf
            <div class="row g-3">
              
              <div class="col-md-6">
                <label for="currency" class="form-label">Currency</label>
                <select class="form-select select2" id="currency" name="currency" data-placeholder="Search and select currency">
                  <option value=""></option>
                </select>
                <p class="text-danger mt-1 mb-0" id="currency_error" style="display: none; font-size: 0.875rem;"></p>
              </div>
              
              <div class="col-md-6">
                <label for="timezone" class="form-label">Timezone</label>
                <select class="form-select select2" id="timezone" name="timezone" data-placeholder="Search and select timezone">
                  <option value=""></option>
                </select>
                <p class="text-danger mt-1 mb-0" id="timezone_error" style="display: none; font-size: 0.875rem;"></p>
              </div>
              
              <div class="col-md-6">
                <label for="date_time_format" class="form-label">Date & Time Format</label>
                <select class="form-select" id="date_time_format" name="date_time_format">
                  <option value="">Select Format</option>
                  <option value="MM/DD/YYYY" {{ ($userDetail && ($userDetail->date_time_format ?? '') == 'MM/DD/YYYY') ? 'selected' : '' }}>MM/DD/YYYY</option>
                  <option value="DD/MM/YYYY" {{ ($userDetail && ($userDetail->date_time_format ?? '') == 'DD/MM/YYYY') ? 'selected' : '' }}>DD/MM/YYYY</option>
                  <option value="YYYY-MM-DD" {{ ($userDetail && ($userDetail->date_time_format ?? '') == 'YYYY-MM-DD') ? 'selected' : '' }}>YYYY-MM-DD</option>
                </select>
                <p class="text-danger mt-1 mb-0" id="date_time_format_error" style="display: none; font-size: 0.875rem;"></p>
              </div>
              
              <div class="col-md-6">
                <label for="minimum_deposit_amount" class="form-label">Minimum Deposit Amount</label>
                <input type="text" class="form-control" id="minimum_deposit_amount" name="minimum_deposit_amount" value="{{ $userDetail->minimum_deposit_amount ?? '' }}" placeholder="Enter amount">
                <p class="text-danger mt-1 mb-0" id="minimum_deposit_amount_error" style="display: none; font-size: 0.875rem;"></p>
              </div>
              
              <div class="col-md-6">
                <label for="minimum_deposit_type" class="form-label">Deposit Type</label>
                <select class="form-select" id="minimum_deposit_type" name="minimum_deposit_type">
                  <option value="">Select Type</option>
                  <option value="fixed" {{ ($userDetail && ($userDetail->minimum_deposit_type ?? '') == 'fixed') ? 'selected' : '' }}>Fixed Amount</option>
                  <option value="percentage" {{ ($userDetail && ($userDetail->minimum_deposit_type ?? '') == 'percentage') ? 'selected' : '' }}>Percentage</option>
                </select>
                <p class="text-danger mt-1 mb-0" id="minimum_deposit_type_error" style="display: none; font-size: 0.875rem;"></p>
              </div>
              
              <div class="col-md-6">
                <label for="cancellation_window" class="form-label">Cancellation Window</label>
                <select class="form-select" id="cancellation_window" name="cancellation_window">
                  <option value="">Select Window</option>
                  <option value="24h" {{ ($userDetail && ($userDetail->cancellation_window ?? '') == '24h') ? 'selected' : '' }}>24 Hours</option>
                  <option value="48h" {{ ($userDetail && ($userDetail->cancellation_window ?? '') == '48h') ? 'selected' : '' }}>48 Hours</option>
                  <option value="72h" {{ ($userDetail && ($userDetail->cancellation_window ?? '') == '72h') ? 'selected' : '' }}>72 Hours</option>
                  <option value="1w" {{ ($userDetail && ($userDetail->cancellation_window ?? '') == '1w') ? 'selected' : '' }}>1 Week</option>
                </select>
                <p class="text-danger mt-1 mb-0" id="cancellation_window_error" style="display: none; font-size: 0.875rem;"></p>
              </div>
              
              <div class="col-md-6">
                <label for="reschedule_times" class="form-label">Reschedule Times</label>
                <select class="form-select" id="reschedule_times" name="reschedule_times">
                  <option value="">Select Option</option>
                  <option value="never" {{ ($userDetail && ($userDetail->reschedule_times ?? '') == 'never') ? 'selected' : '' }}>Never</option>
                  <option value="once" {{ ($userDetail && ($userDetail->reschedule_times ?? '') == 'once') ? 'selected' : '' }}>Once</option>
                  <option value="twice" {{ ($userDetail && ($userDetail->reschedule_times ?? '') == 'twice') ? 'selected' : '' }}>Twice</option>
                  <option value="unlimited" {{ ($userDetail && ($userDetail->reschedule_times ?? '') == 'unlimited') ? 'selected' : '' }}>Unlimited</option>
                </select>
                <p class="text-danger mt-1 mb-0" id="reschedule_times_error" style="display: none; font-size: 0.875rem;"></p>
              </div>
              
              <div class="col-md-6">
                <label for="session_buffer_period" class="form-label">Session Buffer Period (minutes)</label>
                <input type="number" class="form-control" id="session_buffer_period" name="session_buffer_period" value="{{ $userDetail->session_buffer_period ?? '' }}" placeholder="e.g., 15, 30, 60" min="0" step="1">
                <small class="text-muted">Time between sessions for rest, clean up, or preparation</small>
                <p class="text-danger mt-1 mb-0" id="session_buffer_period_error" style="display: none; font-size: 0.875rem;"></p>
              </div>
              
              <div class="col-md-6">
                <label class="form-label">Require Consultation Session</label>
                <div class="form-check form-switch">
                  <input class="form-check-input" type="checkbox" id="require_consultation" name="require_consultation" value="1" {{ ($userDetail && ($userDetail->require_consultation ?? false)) ? 'checked' : '' }}>
                  <label class="form-check-label" for="require_consultation">
                    Require consultation session when booking a tattoo
                  </label>
                </div>
                <small class="text-muted d-block mt-1">When enabled, clients must book a consultation before booking a tattoo session</small>
                <p class="text-danger mt-1 mb-0" id="require_consultation_error" style="display: none; font-size: 0.875rem;"></p>
              </div>
            </div>
            
            <div class="d-flex justify-content-end mt-4">
              <button type="submit" class="btn btn-primary">
                <i class="ti ti-device-floppy me-2"></i>
                Save Preferences
              </button>
            </div>
          </form>
        </div>
      </div>
    </div>
  </div>
</div>
@endsection

@push('scripts')
<!-- Dropify JS -->
<script src="https://cdn.jsdelivr.net/npm/dropify@0.2.2/dist/js/dropify.min.js"></script>

<!-- Select2 JS -->
<script src="{{ asset('assets/vendor/libs/select2/select2.js') }}"></script>

<script>
  // All world currencies with their symbols and names
  const allCurrencies = [
    { code: 'USD', name: 'United States Dollar', symbol: '$' },
    { code: 'EUR', name: 'Euro', symbol: '€' },
    { code: 'GBP', name: 'British Pound Sterling', symbol: '£' },
    { code: 'JPY', name: 'Japanese Yen', symbol: '¥' },
    { code: 'AUD', name: 'Australian Dollar', symbol: 'A$' },
    { code: 'CAD', name: 'Canadian Dollar', symbol: 'C$' },
    { code: 'CHF', name: 'Swiss Franc', symbol: 'Fr' },
    { code: 'CNY', name: 'Chinese Yuan', symbol: '¥' },
    { code: 'INR', name: 'Indian Rupee', symbol: '₹' },
    { code: 'BRL', name: 'Brazilian Real', symbol: 'R$' },
    { code: 'ZAR', name: 'South African Rand', symbol: 'R' },
    { code: 'SGD', name: 'Singapore Dollar', symbol: 'S$' },
    { code: 'HKD', name: 'Hong Kong Dollar', symbol: 'HK$' },
    { code: 'NZD', name: 'New Zealand Dollar', symbol: 'NZ$' },
    { code: 'MXN', name: 'Mexican Peso', symbol: '$' },
    { code: 'KRW', name: 'South Korean Won', symbol: '₩' },
    { code: 'RUB', name: 'Russian Ruble', symbol: '₽' },
    { code: 'TRY', name: 'Turkish Lira', symbol: '₺' },
    { code: 'SEK', name: 'Swedish Krona', symbol: 'kr' },
    { code: 'NOK', name: 'Norwegian Krone', symbol: 'kr' },
    { code: 'DKK', name: 'Danish Krone', symbol: 'kr' },
    { code: 'PLN', name: 'Polish Zloty', symbol: 'zł' },
    { code: 'THB', name: 'Thai Baht', symbol: '฿' },
    { code: 'IDR', name: 'Indonesian Rupiah', symbol: 'Rp' },
    { code: 'MYR', name: 'Malaysian Ringgit', symbol: 'RM' },
    { code: 'PHP', name: 'Philippine Peso', symbol: '₱' },
    { code: 'VND', name: 'Vietnamese Dong', symbol: '₫' },
    { code: 'AED', name: 'United Arab Emirates Dirham', symbol: 'د.إ' },
    { code: 'SAR', name: 'Saudi Riyal', symbol: '﷼' },
    { code: 'ILS', name: 'Israeli New Shekel', symbol: '₪' },
    { code: 'EGP', name: 'Egyptian Pound', symbol: 'E£' },
    { code: 'ARS', name: 'Argentine Peso', symbol: '$' },
    { code: 'CLP', name: 'Chilean Peso', symbol: '$' },
    { code: 'COP', name: 'Colombian Peso', symbol: '$' },
    { code: 'PEN', name: 'Peruvian Sol', symbol: 'S/' },
    { code: 'CZK', name: 'Czech Koruna', symbol: 'Kč' },
    { code: 'HUF', name: 'Hungarian Forint', symbol: 'Ft' },
    { code: 'RON', name: 'Romanian Leu', symbol: 'lei' },
    { code: 'BGN', name: 'Bulgarian Lev', symbol: 'лв' },
    { code: 'HRK', name: 'Croatian Kuna', symbol: 'kn' },
    { code: 'UAH', name: 'Ukrainian Hryvnia', symbol: '₴' },
    { code: 'PKR', name: 'Pakistani Rupee', symbol: '₨' },
    { code: 'BDT', name: 'Bangladeshi Taka', symbol: '৳' },
    { code: 'LKR', name: 'Sri Lankan Rupee', symbol: '₨' },
    { code: 'NPR', name: 'Nepalese Rupee', symbol: '₨' },
    { code: 'MMK', name: 'Myanmar Kyat', symbol: 'K' },
    { code: 'KZT', name: 'Kazakhstani Tenge', symbol: '₸' },
    { code: 'UZS', name: 'Uzbekistani Som', symbol: 'лв' },
    { code: 'KWD', name: 'Kuwaiti Dinar', symbol: 'د.ك' },
    { code: 'QAR', name: 'Qatari Riyal', symbol: '﷼' },
    { code: 'OMR', name: 'Omani Rial', symbol: '﷼' },
    { code: 'BHD', name: 'Bahraini Dinar', symbol: '.د.ب' },
    { code: 'JOD', name: 'Jordanian Dinar', symbol: 'د.ا' },
    { code: 'LBP', name: 'Lebanese Pound', symbol: '£' },
    { code: 'NGN', name: 'Nigerian Naira', symbol: '₦' },
    { code: 'KES', name: 'Kenyan Shilling', symbol: 'KSh' },
    { code: 'UGX', name: 'Ugandan Shilling', symbol: 'USh' },
    { code: 'TZS', name: 'Tanzanian Shilling', symbol: 'TSh' },
    { code: 'ETB', name: 'Ethiopian Birr', symbol: 'Br' },
    { code: 'GHS', name: 'Ghanaian Cedi', symbol: '₵' },
    { code: 'XAF', name: 'Central African CFA Franc', symbol: 'Fr' },
    { code: 'XOF', name: 'West African CFA Franc', symbol: 'Fr' },
    { code: 'MAD', name: 'Moroccan Dirham', symbol: 'د.م.' },
    { code: 'TND', name: 'Tunisian Dinar', symbol: 'د.ت' },
    { code: 'DZD', name: 'Algerian Dinar', symbol: 'د.ج' },
    { code: 'ZMW', name: 'Zambian Kwacha', symbol: 'ZK' },
    { code: 'BWP', name: 'Botswana Pula', symbol: 'P' },
    { code: 'MZN', name: 'Mozambican Metical', symbol: 'MT' },
    { code: 'AOA', name: 'Angolan Kwanza', symbol: 'Kz' },
    { code: 'MUR', name: 'Mauritian Rupee', symbol: '₨' },
    { code: 'SCR', name: 'Seychellois Rupee', symbol: '₨' },
    { code: 'MGA', name: 'Malagasy Ariary', symbol: 'Ar' },
    { code: 'XPF', name: 'CFP Franc', symbol: 'Fr' },
    { code: 'FJD', name: 'Fijian Dollar', symbol: 'FJ$' },
    { code: 'PGK', name: 'Papua New Guinean Kina', symbol: 'K' },
    { code: 'SBD', name: 'Solomon Islands Dollar', symbol: 'SI$' },
    { code: 'VUV', name: 'Vanuatu Vatu', symbol: 'Vt' },
    { code: 'WST', name: 'Samoan Tala', symbol: 'T' },
    { code: 'TOP', name: 'Tongan Paʻanga', symbol: 'T$' },
    { code: 'XCD', name: 'East Caribbean Dollar', symbol: '$' },
    { code: 'BBD', name: 'Barbadian Dollar', symbol: '$' },
    { code: 'BZD', name: 'Belize Dollar', symbol: '$' },
    { code: 'BMD', name: 'Bermudan Dollar', symbol: '$' },
    { code: 'BND', name: 'Brunei Dollar', symbol: 'B$' },
    { code: 'KYD', name: 'Cayman Islands Dollar', symbol: '$' },
    { code: 'GYD', name: 'Guyanese Dollar', symbol: '$' },
    { code: 'JMD', name: 'Jamaican Dollar', symbol: 'J$' },
    { code: 'TTD', name: 'Trinidad and Tobago Dollar', symbol: 'TT$' },
    { code: 'BSD', name: 'Bahamian Dollar', symbol: '$' },
    { code: 'IQD', name: 'Iraqi Dinar', symbol: 'ع.د' },
    { code: 'IRR', name: 'Iranian Rial', symbol: '﷼' },
    { code: 'AFN', name: 'Afghan Afghani', symbol: '؋' },
    { code: 'CRC', name: 'Costa Rican Colón', symbol: '₡' },
    { code: 'GTQ', name: 'Guatemalan Quetzal', symbol: 'Q' },
    { code: 'HNL', name: 'Honduran Lempira', symbol: 'L' },
    { code: 'NIO', name: 'Nicaraguan Córdoba', symbol: 'C$' },
    { code: 'PAB', name: 'Panamanian Balboa', symbol: 'B/.' },
    { code: 'PYG', name: 'Paraguayan Guaraní', symbol: 'Gs' },
    { code: 'UYU', name: 'Uruguayan Peso', symbol: '$U' },
    { code: 'VES', name: 'Venezuelan Bolívar', symbol: 'Bs' },
    { code: 'BOB', name: 'Bolivian Boliviano', symbol: 'Bs.' },
    { code: 'DOP', name: 'Dominican Peso', symbol: 'RD$' },
    { code: 'HTG', name: 'Haitian Gourde', symbol: 'G' },
    { code: 'CUP', name: 'Cuban Peso', symbol: '₱' },
    { code: 'ANG', name: 'Netherlands Antillean Guilder', symbol: 'ƒ' },
    { code: 'AWG', name: 'Aruban Florin', symbol: 'ƒ' },
    { code: 'SRD', name: 'Surinamese Dollar', symbol: '$' },
  ];

  // Get all IANA timezones
  function getAllTimezones() {
    const timezones = [];
    const timezoneNames = Intl.supportedValuesOf('timeZone');
    
    timezoneNames.forEach(tz => {
      const date = new Date();
      const formatter = new Intl.DateTimeFormat('en', {
        timeZone: tz,
        timeZoneName: 'short'
      });
      const parts = formatter.formatToParts(date);
      const timeZoneName = parts.find(part => part.type === 'timeZoneName')?.value || '';
      
      // Format timezone name for display
      const displayName = tz.replace(/_/g, ' ').split('/').map(part => 
        part.charAt(0).toUpperCase() + part.slice(1).toLowerCase()
      ).join(' / ');
      
      timezones.push({
        value: tz,
        name: displayName,
        offset: timeZoneName
      });
    });
    
    // Sort by timezone name
    return timezones.sort((a, b) => a.name.localeCompare(b.name));
  }

  // Initialize Dropify
  function initDropify() {
    if ($('.dropify').length) {
      $('.dropify').dropify('destroy');
      $('.dropify').dropify({
        messages: {
          'default': 'Drag and drop an image here or click',
          'replace': 'Drag and drop or click to replace',
          'remove': 'Remove',
          'error': 'Ooops, something wrong happened.'
        },
        error: {
          'fileSize': 'The file size is too big (2MB max).',
          'fileExtension': 'The file extension is not allowed (jpg, jpeg, png, heif, heic only).'
        }
      });
    }
  }

  // Initialize Select2 for currency and timezone
  function initializeSelect2() {
    // Initialize Currency Select2
    const currencySelect = $('#currency');
    if (currencySelect.length && !currencySelect.hasClass('select2-hidden-accessible')) {
      // Populate currency options
      allCurrencies.forEach(currency => {
        const isSelected = currency.code === '{{ $userDetail && $userDetail->currency ? $userDetail->currency : "" }}';
        currencySelect.append(
          new Option(`${currency.code} (${currency.symbol}) - ${currency.name}`, currency.code, isSelected, isSelected)
        );
      });

      currencySelect.select2({
        placeholder: 'Search and select currency',
        allowClear: true,
        width: '100%'
      });
    }

    // Initialize Timezone Select2
    const timezoneSelect = $('#timezone');
    if (timezoneSelect.length && !timezoneSelect.hasClass('select2-hidden-accessible')) {
      // Populate timezone options
      const timezones = getAllTimezones();
      const selectedTimezone = '{{ $userDetail && $userDetail->timezone ? $userDetail->timezone : "" }}';
      
      timezones.forEach(tz => {
        const isSelected = tz.value === selectedTimezone;
        timezoneSelect.append(
          new Option(`${tz.name} (${tz.offset})`, tz.value, isSelected, isSelected)
        );
      });

      timezoneSelect.select2({
        placeholder: 'Search and select timezone',
        allowClear: true,
        width: '100%'
      });
    }
  }

  // Function to scroll to first error on page
  function scrollToFirstError() {
    // Check for Laravel validation errors (server-side)
    const invalidFields = document.querySelectorAll('.is-invalid, .form-control.is-invalid, .form-select.is-invalid');
    if (invalidFields.length > 0) {
      setTimeout(() => {
        const firstField = invalidFields[0];
        // Check if it's a Select2 field
        const isSelect2 = $(firstField).hasClass('select2-hidden-accessible') || $(firstField).data('select2');
        let targetElement = firstField;
        
        if (isSelect2) {
          // Find the Select2 container
          const select2Container = $(firstField).next('.select2-container').length 
            ? $(firstField).next('.select2-container')[0]
            : $(firstField).parent().find('.select2-container').first()[0];
          if (select2Container) {
            targetElement = select2Container;
          }
        }
        
        targetElement.scrollIntoView({ behavior: 'smooth', block: 'center' });
        
        // For Select2, open the dropdown; for regular inputs, focus
        if (isSelect2 && $(firstField).data('select2')) {
          $(firstField).select2('open');
        } else if (firstField.focus) {
          firstField.focus();
        }
      }, 100);
    }
    
    // Check for custom error messages (client-side)
    const errorMessages = document.querySelectorAll('[id$="_error"]');
    if (errorMessages.length > 0) {
      const firstError = Array.from(errorMessages).find(el => {
        const style = window.getComputedStyle(el);
        return style.display !== 'none' && el.textContent.trim() !== '';
      });
      if (firstError) {
        const inputId = firstError.id.replace('_error', '');
        const input = document.getElementById(inputId);
        if (input) {
          setTimeout(() => {
            // Check if it's a Select2 field
            const isSelect2 = $(input).hasClass('select2-hidden-accessible') || $(input).data('select2');
            let targetElement = input;
            
            if (isSelect2) {
              // Find the Select2 container
              const select2Container = $(input).next('.select2-container').length 
                ? $(input).next('.select2-container')[0]
                : $(input).parent().find('.select2-container').first()[0];
              if (select2Container) {
                targetElement = select2Container;
              }
            }
            
            targetElement.scrollIntoView({ behavior: 'smooth', block: 'center' });
            
            // For Select2, open the dropdown; for regular inputs, focus
            if (isSelect2 && $(input).data('select2')) {
              $(input).select2('open');
            } else if (input.focus) {
              input.focus();
            }
          }, 100);
        }
      }
    }
  }

  // Initialize on page load
  $(document).ready(function() {
    initDropify();
    initializeSelect2();
    // Scroll to errors if page has validation errors
    scrollToFirstError();
  });

  // Handle preferences form submission
  document.getElementById('preferencesForm').addEventListener('submit', async function(e) {
    e.preventDefault();
    
    const formData = new FormData(this);
    const submitBtn = this.querySelector('button[type="submit"]');
    const originalText = submitBtn.innerHTML;
    submitBtn.disabled = true;
    submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Saving...';
    
    // Clear previous errors
    document.querySelectorAll('[id$="_error"]').forEach(el => {
      el.style.display = 'none';
      el.textContent = '';
    });
    document.querySelectorAll('.form-control, .form-select').forEach(el => {
      el.classList.remove('is-invalid');
    });
    
    try {
      const response = await fetch('{{ route("settings.preferences.update") }}', {
        method: 'POST',
        body: formData,
        headers: {
          'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || document.querySelector('input[name="_token"]').value
        }
      });
      
      const data = await response.json();
      
      if (data.success) {
        // Show success message
        const successAlert = document.getElementById('preferences-success-alert');
        if (successAlert) {
          successAlert.style.display = 'block';
          successAlert.classList.add('show');
          
          // Scroll to top to show the success message
          window.scrollTo({ top: 0, behavior: 'smooth' });
          
          // Reload after 1.5 seconds to refresh the form with updated values
          setTimeout(() => {
            window.location.reload();
          }, 1500);
        } else {
          // Fallback: reload immediately if alert element not found
          window.location.reload();
        }
      } else {
        if (data.errors) {
          let firstErrorField = null;
          
          // Display validation errors
          Object.keys(data.errors).forEach(field => {
            const input = document.getElementById(field);
            const errorDiv = document.getElementById(field + '_error');
            
            if (input) {
              input.classList.add('is-invalid');
              // Track first error field for scrolling
              if (!firstErrorField) {
                firstErrorField = input;
              }
            }
            
            if (errorDiv) {
              errorDiv.style.display = 'block';
              errorDiv.textContent = Array.isArray(data.errors[field]) ? data.errors[field][0] : data.errors[field];
            }
          });
          
          // Scroll to first error field
          if (firstErrorField) {
            setTimeout(() => {
              // Check if it's a Select2 field by checking if jQuery select2 is initialized on it
              const isSelect2 = $(firstErrorField).hasClass('select2-hidden-accessible') || $(firstErrorField).data('select2');
              let targetElement = firstErrorField;
              
              if (isSelect2) {
                // Find the Select2 container (it's usually a sibling or parent)
                const select2Container = $(firstErrorField).next('.select2-container').length 
                  ? $(firstErrorField).next('.select2-container')[0]
                  : $(firstErrorField).parent().find('.select2-container').first()[0];
                if (select2Container) {
                  targetElement = select2Container;
                }
              }
              
              targetElement.scrollIntoView({ behavior: 'smooth', block: 'center' });
              
              // For Select2, open the dropdown; for regular inputs, focus
              if (isSelect2 && $(firstErrorField).data('select2')) {
                $(firstErrorField).select2('open');
              } else if (firstErrorField.focus) {
                firstErrorField.focus();
              }
            }, 100);
          }
        }
      }
    } catch (error) {
      alert('An error occurred. Please try again.');
    } finally {
      submitBtn.disabled = false;
      submitBtn.innerHTML = originalText;
    }
  });
</script>
@endpush

