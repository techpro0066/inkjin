@extends('layouts.onboarding_bookpay')

@section('title', 'Preferences')

@php
  $ud = $userDetail;
  $tz = $ud->timezone ?? '';
  $zones = \DateTimeZone::listIdentifiers();
@endphp

@section('content')
<form id="prefForm" class="contents">
  @csrf
  <div class="flex-1 p-8 md:p-12 max-w-4xl">
    <div class="mb-10">
      <h2 class="text-3xl font-extrabold text-on-surface tracking-tight">Configure your booking preferences</h2>
      <p class="text-on-surface-variant mt-2 max-w-xl">Fine-tune how your booking system works. These settings control scheduling, payments, and client interactions.</p>
    </div>

    <div class="space-y-10">
      <section>
        <h3 class="text-lg font-bold text-on-surface mb-1">General Settings</h3>
        <div class="h-px bg-outline-variant/30 mb-5"></div>
        <div class="bg-surface-container-low rounded-2xl p-6">
          <div class="grid grid-cols-1 sm:grid-cols-3 gap-6">
            <div>
              <label for="timezone" class="block text-[11px] uppercase tracking-wider text-on-surface-variant font-medium mb-2">Timezone <span class="text-red-600">*</span></label>
              <select id="timezone" name="timezone" class="select w-full px-4 py-3 rounded-xl border border-outline-variant/30 bg-white focus:ring-2 focus:ring-primary/40 text-on-surface text-sm">
                <option value="" disabled {{ !$tz ? 'selected' : '' }}>Select timezone</option>
                @foreach ($zones as $z)
                  <option value="{{ $z }}" {{ $tz === $z ? 'selected' : '' }}>{{ str_replace('_', ' ', $z) }}</option>
                @endforeach
              </select>
              <p id="timezone_error" class="text-error text-xs mt-1 hidden"></p>
            </div>
            <div>
              <label for="date_time_format" class="block text-[11px] uppercase tracking-wider text-on-surface-variant font-medium mb-2">Date Format <span class="text-red-600">*</span></label>
              <select id="date_time_format" name="date_time_format" class="select w-full px-4 py-3 rounded-xl border border-outline-variant/30 bg-white focus:ring-2 focus:ring-primary/40 text-sm">
                <option value="DD/MM/YYYY" {{ ($ud->date_time_format ?? '') == 'DD/MM/YYYY' ? 'selected' : '' }}>DD/MM/YYYY</option>
                <option value="MM/DD/YYYY" {{ ($ud->date_time_format ?? '') == 'MM/DD/YYYY' ? 'selected' : '' }}>MM/DD/YYYY</option>
                <option value="YYYY-MM-DD" {{ ($ud->date_time_format ?? '') == 'YYYY-MM-DD' ? 'selected' : '' }}>YYYY-MM-DD</option>
              </select>
              <p id="date_time_format_error" class="text-error text-xs mt-1 hidden"></p>
            </div>
            <div>
              <label class="block text-[11px] uppercase tracking-wider text-on-surface-variant font-medium mb-2">Design Size Unit <span class="text-red-600">*</span></label>
              <div class="inline-flex bg-surface-container-highest toggle-div p-1">
                <button type="button" class="toggle-segment toggle-segment-left {{ ($ud->size_unit ?? 'cm') === 'cm' ? 'active' : '' }}" id="unit_cm" onclick="setSizeUnit('cm')">Centimeters (cm)</button>
                <button type="button" class="toggle-segment toggle-segment-right {{ ($ud->size_unit ?? 'cm') === 'in' ? 'active' : '' }}" id="unit_in" onclick="setSizeUnit('in')">Inches (in)</button>
              </div>
              <input type="hidden" id="size_unit" name="size_unit" value="{{ $ud->size_unit ?? 'cm' }}">
            </div>
          </div>
        </div>
      </section>

      <section>
        <h3 class="text-lg font-bold text-on-surface mb-1">Payment Logic</h3>
        <div class="h-px bg-outline-variant/30 mb-5"></div>
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-6 mb-8">
          <div>
            <label for="currency" class="block text-[11px] uppercase tracking-wider text-on-surface-variant font-medium mb-2">Currency <span class="text-red-600">*</span></label>
            <select id="currency" name="currency" class="select w-full px-4 py-3 rounded-xl border border-outline-variant/30 bg-white text-sm" data-selected="{{ $ud->currency ?? '' }}"></select>
            <p id="currency_error" class="text-error text-xs mt-1 hidden"></p>
          </div>
          <div>
            <label class="block text-[11px] uppercase tracking-wider text-on-surface-variant font-medium mb-2">Deposit Type <span class="text-red-600">*</span></label>
            <div class="inline-flex bg-surface-container-highest toggle-div p-1">
              <button type="button" class="toggle-segment toggle-segment-left {{ ($ud->minimum_deposit_type ?? 'amount') === 'amount' ? 'active' : '' }}" id="deposit_fixed" onclick="setDepositType('amount')">Fixed Amount</button>
              <button type="button" class="toggle-segment toggle-segment-right {{ ($ud->minimum_deposit_type ?? '') === 'percentage' ? 'active' : '' }}" id="deposit_percent" onclick="setDepositType('percentage')">Percentage %</button>
            </div>
            <input type="hidden" name="minimum_deposit_type" id="minimum_deposit_type" value="{{ ($ud->minimum_deposit_type ?? 'amount') === 'percentage' ? 'percentage' : 'amount' }}">
          </div>
          <div>
            <label for="minimum_deposit_amount" class="block text-[11px] uppercase tracking-wider text-on-surface-variant font-medium mb-2">Min. Deposit <span class="deposit-type-selected">Amount</span> <span class="text-red-600">*</span></label>
            <input type="text" id="minimum_deposit_amount" name="minimum_deposit_amount" value="{{ $ud->minimum_deposit_amount ?? '' }}" class="w-full px-4 py-3 rounded-xl border border-outline-variant/30 bg-white text-sm">
            <p id="minimum_deposit_amount_error" class="text-error text-xs mt-1 hidden"></p>
          </div>
        </div>

        <div>
          <h4 class="text-sm font-bold text-on-surface mb-1">Service Booking Fee <span class="text-red-600">*</span></h4>
          <p class="text-on-surface-variant text-xs mb-4">How would you like to handle the platform service fee?</p>
          <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
            @foreach ([['client','Client pays','Fee added to client total'],['artist','Artist pays','Fee deducted from payout'],['split','Split','Shared between client and artist']] as $i => $row)
              <div class="fee-card radio-card {{ ($ud->booking_fee_type ?? 'client') === $row[0] ? 'selected' : '' }}" onclick="selectFee(this, '{{ $row[0] }}')">
                <div class="flex items-start justify-between mb-2">
                  <span class="text-sm font-bold text-on-surface">{{ $row[1] }}</span>
                  <div class="radio-dot"></div>
                </div>
                <p class="text-xs text-on-surface-variant">{{ $row[2] }}</p>
              </div>
            @endforeach
          </div>
          <input type="hidden" name="booking_fee_type" id="booking_fee_type" value="{{ $ud->booking_fee_type ?? 'client' }}">
          <p id="booking_fee_type_error" class="text-error text-xs mt-2 hidden"></p>
        </div>
      </section>

      <section>
        <h3 class="text-lg font-bold text-on-surface mb-1">Schedule Rules</h3>
        <div class="h-px bg-outline-variant/30 mb-5"></div>
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-6">
          <div>
            <label for="cancellation_window" class="block text-[11px] uppercase tracking-wider text-on-surface-variant font-medium mb-2">Cancellation Window <span class="text-red-600">*</span></label>
            <p class="text-on-surface-variant text-xs mb-3">How long before the session can clients cancel for a full refund?</p>
            <select id="cancellation_window" name="cancellation_window" class="select w-full px-4 py-3 rounded-xl border border-outline-variant/30 bg-white text-sm">
              @foreach (['12h'=>'12 Hours','24h'=>'24 Hours','48h'=>'48 Hours','72h'=>'72 Hours','1w'=>'1 Week','2w'=>'2 Weeks'] as $k => $lab)
                <option value="{{ $k }}" {{ ($ud->cancellation_window ?? '24h') === $k ? 'selected' : '' }}>{{ $lab }}</option>
              @endforeach
            </select>
            <p id="cancellation_window_error" class="text-error text-xs mt-1 hidden"></p>
          </div>
          <div>
            <label class="block text-[11px] uppercase tracking-wider text-on-surface-variant font-medium mb-2">Buffer Time <span class="text-red-600">*</span></label>
            <p class="text-on-surface-variant text-xs mb-3">Time blocked between sessions.</p>
            <div class="grid grid-cols-2 gap-2">
              @foreach ([15,30,45,60] as $m)
                <button type="button" class="buffer-btn {{ (int)($ud->session_buffer_period ?? 30) === $m ? 'active' : '' }}" onclick="setBuffer(this, {{ $m }})">{{ $m }}m</button>
              @endforeach
            </div>
            <input type="hidden" name="session_buffer_period" id="session_buffer_period" value="{{ $ud->session_buffer_period ?? 30 }}">
            <p id="session_buffer_period_error" class="text-error text-xs mt-1 hidden"></p>
          </div>
          <div>
            <label class="block text-[11px] uppercase tracking-wider text-on-surface-variant font-medium mb-2">Reschedule Policy <span class="text-red-600">*</span></label>
            <div class="space-y-3 mt-3">
              <label class="flex items-center gap-3 cursor-pointer">
                <input type="radio" name="reschedule_times" value="once" {{ ($ud->reschedule_times ?? 'once') === 'once' ? 'checked' : '' }} class="w-[18px] h-[18px] text-primary border-outline-variant">
                <span class="text-sm text-on-surface">Allow once</span>
              </label>
              <label class="flex items-center gap-3 cursor-pointer">
                <input type="radio" name="reschedule_times" value="twice" {{ ($ud->reschedule_times ?? '') === 'twice' ? 'checked' : '' }} class="w-[18px] h-[18px]">
                <span class="text-sm text-on-surface">Allow Twice</span>
              </label>
              <label class="flex items-center gap-3 cursor-pointer">
                <input type="radio" name="reschedule_times" value="unlimited" {{ ($ud->reschedule_times ?? '') === 'unlimited' ? 'checked' : '' }} class="w-[18px] h-[18px]">
                <span class="text-sm text-on-surface">Unlimited</span>
              </label>
              <label class="flex items-center gap-3 cursor-pointer">
                <input type="radio" name="reschedule_times" value="never" {{ ($ud->reschedule_times ?? '') === 'never' ? 'checked' : '' }} class="w-[18px] h-[18px]">
                <span class="text-sm text-on-surface">Strict (no rescheduling)</span>
              </label>
            </div>
            <p id="reschedule_times_error" class="text-error text-xs mt-1 hidden"></p>
          </div>
        </div>
      </section>

      <section>
        <h3 class="text-lg font-bold text-on-surface mb-1">Consultation Settings</h3>
        <div class="h-px bg-outline-variant/30 mb-5"></div>
        @php
          $reqCons = $ud->require_consultation ?? false;
          $st = $ud->session_type ?? '';
          $ct = $ud->consultation_timing ?? '';
        @endphp
        <div class="bg-surface-container-low rounded-2xl p-6 space-y-6">
          <div class="flex items-center justify-between gap-6 flex-wrap">
            <div>
              <h4 class="text-sm font-bold text-on-surface">Require Consultation Session</h4>
              <p class="text-on-surface-variant text-xs mt-1">When enabled, clients must book a consultation before booking a tattoo session.</p>
            </div>
            <div class="toggle-switch {{ $reqCons ? 'active' : '' }}" id="consultation_toggle" onclick="toggleConsultation()" role="switch" aria-checked="{{ $reqCons ? 'true' : 'false' }}"></div>
            <input type="hidden" name="require_consultation" id="require_consultation" value="{{ $reqCons ? '1' : '0' }}">
          </div>
          <p id="require_consultation_error" class="text-error text-xs hidden"></p>

          <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
            <div id="session_type_container" style="display: {{ $reqCons ? 'block' : 'none' }};">
              <label for="session_type" class="block text-[11px] uppercase tracking-wider text-on-surface-variant font-medium mb-2">Session type <span class="text-red-500">*</span></label>
              <select id="session_type" name="session_type" class="select w-full px-4 py-3 rounded-xl border border-outline-variant/30 bg-white text-sm">
                <option value="" disabled {{ $st === '' ? 'selected' : '' }}>Select session type</option>
                <option value="online" {{ $st === 'online' ? 'selected' : '' }}>Online session</option>
                <option value="physical" {{ $st === 'physical' ? 'selected' : '' }}>Physical session</option>
                <option value="both" {{ $st === 'both' ? 'selected' : '' }}>Both (online &amp; physical)</option>
              </select>
              <p class="text-on-surface-variant text-xs mt-1">Online, in person, or both.</p>
              <p id="session_type_error" class="text-error text-xs mt-1 hidden"></p>
            </div>
            <div id="session_duration_container" style="display: {{ $reqCons ? 'block' : 'none' }};">
              <label for="session_duration_minutes" class="block text-[11px] uppercase tracking-wider text-on-surface-variant font-medium mb-2">Session duration (minutes) <span class="text-red-500">*</span></label>
              <input type="number" id="session_duration_minutes" name="session_duration_minutes" value="{{ $ud->session_duration_minutes ?? '' }}" placeholder="e.g. 30, 60" min="15" max="480" step="15" class="w-full px-4 py-3 rounded-xl border border-outline-variant/30 bg-white text-sm">
              <p class="text-on-surface-variant text-xs mt-1">Minimum 15 minutes, maximum 8 hours.</p>
              <p id="session_duration_minutes_error" class="text-error text-xs mt-1 hidden"></p>
            </div>
          </div>

          <div id="consultation_timing_container" style="display: {{ $reqCons ? 'block' : 'none' }};">
            <label for="consultation_timing" class="block text-[11px] uppercase tracking-wider text-on-surface-variant font-medium mb-2">Consultation timing <span class="text-red-500">*</span></label>
            <select id="consultation_timing" name="consultation_timing" class="select w-full px-4 py-3 rounded-xl border border-outline-variant/30 bg-white text-sm" onchange="toggleGapFields()">
              <option value="" disabled {{ $ct === '' ? 'selected' : '' }}>Select timing</option>
              <option value="combined" {{ $ct === 'combined' ? 'selected' : '' }}>Add with tattoo session</option>
              <option value="separate" {{ $ct === 'separate' ? 'selected' : '' }}>Separate from tattoo session</option>
            </select>
            <p class="text-on-surface-variant text-xs mt-2"><strong>Combined:</strong> Consultation time is added to the tattoo session duration. <strong>Separate:</strong> Consultation is a standalone session.</p>
            <p id="consultation_timing_error" class="text-error text-xs mt-1 hidden"></p>
          </div>

          <div id="gap_fields_container" class="space-y-4 pt-2 border-t border-outline-variant/20" style="display: {{ ($reqCons && $ct === 'separate') ? 'block' : 'none' }};">
            <input type="hidden" id="require_gap_between_consultation_tattoo" name="require_gap_between_consultation_tattoo" value="{{ ($reqCons && $ct === 'separate') ? '1' : '0' }}">
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
              <div id="gap_duration_container" style="display: {{ ($reqCons && $ct === 'separate') ? 'block' : 'none' }};">
                <label for="consultation_tattoo_gap_value" class="block text-[11px] uppercase tracking-wider text-on-surface-variant font-medium mb-2">Minimum gap (in days) <span class="text-red-500">*</span></label>
                <input type="number" id="consultation_tattoo_gap_value" name="consultation_tattoo_gap_value" value="{{ $ud->consultation_tattoo_gap_value ?? '' }}" placeholder="e.g. 1, 2, 7" min="1" class="w-full px-4 py-3 rounded-xl border border-outline-variant/30 bg-white text-sm">
                <p id="consultation_tattoo_gap_value_error" class="text-error text-xs mt-1 hidden"></p>
              </div>
            </div>
            <p class="text-on-surface-variant text-xs">Set the minimum time between the consultation and the tattoo session</p>
          </div>
        </div>
      </section>
    </div>
  </div>

  <div class="sticky bottom-0 bg-surface border-t border-outline-variant/10 px-8 md:px-12 py-5 flex items-center justify-between mt-auto">
    <a href="{{ route('onboarding.studio') }}" class="inline-flex items-center gap-1 text-on-surface font-semibold hover:text-primary transition-colors">
      <span class="material-symbols-outlined text-lg">arrow_back</span> Back
    </a>
    <button type="submit" class="inline-flex items-center gap-2 bg-gradient-to-br from-primary to-primary-container text-white font-bold py-3 px-8 rounded-xl shadow-lg shadow-primary/20 hover:opacity-90 transition-all active:scale-[0.98]">
      Next Step <span class="material-symbols-outlined text-lg">arrow_forward</span>
    </button>
  </div>
</form>
@endsection

@push('scripts')
<script>
$(function () {
  var sel = document.getElementById('currency');
  if (sel && typeof fillCurrencySelect === 'function') {
    fillCurrencySelect(sel, sel.getAttribute('data-selected') || 'USD');
  }
});
function setSizeUnit(unit) {
  $('#size_unit').val(unit);
  $('#unit_cm').toggleClass('active', unit === 'cm');
  $('#unit_in').toggleClass('active', unit === 'in');
}
function setDepositType(type) {
  $('#minimum_deposit_type').val(type);
  $('#deposit_fixed').toggleClass('active', type === 'amount');
  $('#deposit_percent').toggleClass('active', type === 'percentage');
  $('.deposit-type-selected').text(type === 'amount' ? 'Amount' : 'Percentage');
}
function selectFee(el, value) {
  $('#prefForm .fee-card').removeClass('selected');
  $(el).addClass('selected');
  $('#booking_fee_type').val(value);
  if (typeof window.clearOnboardingFieldError === 'function') window.clearOnboardingFieldError('booking_fee_type');
}
function setBuffer(btn, value) {
  $(btn).closest('.grid').find('.buffer-btn').removeClass('active');
  $(btn).addClass('active');
  $('#session_buffer_period').val(value);
  if (typeof window.clearOnboardingFieldError === 'function') window.clearOnboardingFieldError('session_buffer_period');
}
function requireConsultationEnabled() {
  return $('#require_consultation').val() === '1';
}
function isElementVisible(el) {
  var $el = $(el);
  if (!$el.length) return false;
  return $el.is(':visible');
}
function clearFieldError(id) {
  if (typeof window.clearOnboardingFieldError === 'function') {
    window.clearOnboardingFieldError(id);
  }
}
function initVisibleConsultationSelect2() {
  if (typeof window.initOnboardingSelect2 !== 'function' || !window.jQuery) return;
  window.initOnboardingSelect2(window.jQuery('#session_type_container, #consultation_timing_container, #gap_unit_container'));
}
function toggleSessionFields() {
  var show = requireConsultationEnabled();
  $('#session_type_container').css('display', show ? 'block' : 'none');
  $('#session_duration_container').css('display', show ? 'block' : 'none');
  $('#consultation_timing_container').css('display', show ? 'block' : 'none');
  if (show) {
    initVisibleConsultationSelect2();
  } else {
    $('#session_type').val('');
    $('#session_duration_minutes').val('');
    $('#consultation_timing').val('');
    clearFieldError('session_type');
    clearFieldError('session_duration_minutes');
    clearFieldError('consultation_timing');
    toggleGapFields();
  }
}
function toggleGapFields() {
  var ct = $('#consultation_timing').val();
  var $gap = $('#gap_fields_container');
  if (!$gap.length) return;
  if (requireConsultationEnabled() && ct === 'separate') {
    $gap.css('display', 'block');
    $('#require_gap_between_consultation_tattoo').val('1');
    $('#gap_duration_container').css('display', 'block');
    $('#gap_unit_container').css('display', 'block');
    initVisibleConsultationSelect2();
  } else {
    $gap.css('display', 'none');
    $('#require_gap_between_consultation_tattoo').val('0');
    $('#gap_duration_container').css('display', 'none');
    $('#consultation_tattoo_gap_value').val('');
    clearFieldError('consultation_tattoo_gap_value');
  }
}
function toggleConsultation() {
  var $toggle = $('#consultation_toggle');
  $toggle.toggleClass('active');
  var isActive = $toggle.hasClass('active');
  $('#require_consultation').val(isActive ? '1' : '0');
  $toggle.attr('aria-checked', isActive ? 'true' : 'false');
  if (typeof window.clearOnboardingFieldError === 'function') window.clearOnboardingFieldError('require_consultation');
  toggleSessionFields();
  toggleGapFields();
}
$(function () {
  toggleSessionFields();
  toggleGapFields();

  $.each(['timezone', 'date_time_format', 'currency', 'cancellation_window', 'minimum_deposit_amount', 'session_type', 'consultation_timing', 'session_duration_minutes', 'consultation_tattoo_gap_value'], function (_, id) {
    $('#' + id).on('change input', function () {
      if (typeof window.clearOnboardingFieldError === 'function') window.clearOnboardingFieldError(id);
    });
  });
  $('#prefForm input[name="reschedule_times"]').on('change', function () {
    if (typeof window.clearOnboardingFieldError === 'function') window.clearOnboardingFieldError('reschedule_times');
  });

  function showPrefErrors(errors) {
    $.each(errors, function (k, messages) {
      var $err = $('#' + k + '_error');
      if (!$err.length) return;
      var $field = $('#' + k);
      if ($field.length && !isElementVisible($field[0])) {
        $err.text('').addClass('hidden');
        return;
      }
      $err.text(messages[0]).removeClass('hidden');
    });
    if (typeof window.scrollToFirstOnboardingError === 'function') {
      window.scrollToFirstOnboardingError(document.getElementById('prefForm'));
    }
  }

  $('#prefForm').on('submit', function (e) {
    e.preventDefault();
    var fd = new FormData(this);
    var rc = $('#require_consultation').val() === '1';
    if (!rc) {
      fd.delete('session_type');
      fd.delete('session_duration_minutes');
      fd.delete('consultation_timing');
      fd.delete('require_gap_between_consultation_tattoo');
      fd.delete('consultation_tattoo_gap_value');
    } else if ($('#consultation_timing').val() !== 'separate') {
      fd.set('require_gap_between_consultation_tattoo', '0');
      fd.delete('consultation_tattoo_gap_value');
    }
    $.ajax({
      url: @json(route('onboarding.preferences.save')),
      type: 'POST',
      data: fd,
      processData: false,
      contentType: false,
      headers: {
        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content'),
        Accept: 'application/json',
      },
    })
      .done(function (data) {
        if (data.success && data.redirect) {
          window.location.href = data.redirect;
          return;
        }
        if (data.errors) {
          showPrefErrors(data.errors);
        } else {
          alert(data.message || 'Error');
        }
      })
      .fail(function (xhr) {
        if (xhr.status === 422 && xhr.responseJSON && xhr.responseJSON.errors) {
          showPrefErrors(xhr.responseJSON.errors);
        } else {
          alert((xhr.responseJSON && xhr.responseJSON.message) || 'Error');
        }
      });
  });
});
</script>
@endpush
