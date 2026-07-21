@extends('layouts.onboarding_bookpay')

@section('title', 'Payments')

@php
  $ud = $userDetail;
@endphp

@section('content')
<form id="prefForm" class="contents">
  @csrf
  <div class="flex-1 p-8 md:p-12 max-w-4xl">
    <div class="mb-10">
      <h2 class="text-3xl font-extrabold text-on-surface tracking-tight">Configure your payments</h2>
      <p class="text-on-surface-variant mt-2 max-w-xl">Fine-tune how your booking system works. These settings control scheduling, payments, and client interactions.</p>
    </div>

    <div class="space-y-10">
      <input type="hidden" id="timezone" name="timezone" value="{{ $ud->timezone ?: 'UTC' }}">
      <input type="hidden" id="date_time_format" name="date_time_format" value="{{ $ud->date_time_format ?: 'DD/MM/YYYY' }}">
      <input type="hidden" id="size_unit" name="size_unit" value="{{ $ud->size_unit ?: 'cm' }}">

      <section>
        <h3 class="text-lg font-bold text-on-surface mb-1">Payment Logic</h3>
        <div class="h-px bg-outline-variant/30 mb-5"></div>
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-6 mb-8">
          <div>
            <label for="currency" class="block text-[11px] uppercase tracking-wider text-on-surface-variant font-medium mb-2">Currency <span class="text-red-600">*</span></label>
            <select id="currency" name="currency" class="select w-full px-4 py-3 rounded-xl border border-outline-variant/30 bg-white text-sm" data-selected="{{ $ud->currency ?? '' }}"></select>
            <p class="text-on-surface-variant text-xs mt-2">Your payout currency. We'll confirm this matches your bank account when you set up payouts.</p>
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

        <div class="mb-8">
          <h4 class="text-sm font-bold text-on-surface mb-1">Rates</h4>
          <p class="text-on-surface-variant text-xs mb-4">These are reference rates. Final pricing for custom work is always confirmed by you with a quote.</p>
          <div class="grid grid-cols-1 sm:grid-cols-3 gap-6">
            <div>
              <label for="hourly_rate" class="block text-[11px] uppercase tracking-wider text-on-surface-variant font-medium mb-2">Hourly rate <span class="text-on-surface-variant font-normal normal-case tracking-normal">(optional)</span></label>
              <input type="number" step="0.01" min="0" id="hourly_rate" name="hourly_rate" value="{{ $ud->hourly_rate !== null ? rtrim(rtrim(number_format((float) $ud->hourly_rate, 2, '.', ''), '0'), '.') : '' }}" placeholder="e.g. 120" class="w-full px-4 py-3 rounded-xl border border-outline-variant/30 bg-white text-sm">
              <p class="text-on-surface-variant text-xs mt-2">Your typical rate per hour, before any custom quote</p>
              <p id="hourly_rate_error" class="text-error text-xs mt-1 hidden"></p>
            </div>
            <div>
              <label for="half_day_rate" class="block text-[11px] uppercase tracking-wider text-on-surface-variant font-medium mb-2">Half-day rate <span class="text-on-surface-variant font-normal normal-case tracking-normal">(optional)</span></label>
              <input type="number" step="0.01" min="0" id="half_day_rate" name="half_day_rate" value="{{ $ud->half_day_rate !== null ? rtrim(rtrim(number_format((float) $ud->half_day_rate, 2, '.', ''), '0'), '.') : '' }}" placeholder="e.g. 400" class="w-full px-4 py-3 rounded-xl border border-outline-variant/30 bg-white text-sm">
              <p class="text-on-surface-variant text-xs mt-2">For sessions typically booked in half-day blocks</p>
              <p id="half_day_rate_error" class="text-error text-xs mt-1 hidden"></p>
            </div>
            <div>
              <label for="full_day_rate" class="block text-[11px] uppercase tracking-wider text-on-surface-variant font-medium mb-2">Full-day rate <span class="text-on-surface-variant font-normal normal-case tracking-normal">(optional)</span></label>
              <input type="number" step="0.01" min="0" id="full_day_rate" name="full_day_rate" value="{{ $ud->full_day_rate !== null ? rtrim(rtrim(number_format((float) $ud->full_day_rate, 2, '.', ''), '0'), '.') : '' }}" placeholder="e.g. 700" class="w-full px-4 py-3 rounded-xl border border-outline-variant/30 bg-white text-sm">
              <p class="text-on-surface-variant text-xs mt-2">For sessions typically booked as a full day</p>
              <p id="full_day_rate_error" class="text-error text-xs mt-1 hidden"></p>
            </div>
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
    </div>
  </div>

  <div class="sticky bottom-0 bg-surface border-t border-outline-variant/10 px-8 md:px-12 py-5 flex items-center justify-between mt-auto">
    <a href="{{ route('onboarding.studio') }}" class="inline-flex items-center gap-1 text-on-surface font-semibold hover:text-primary transition-colors">
      <span class="material-symbols-outlined text-lg">arrow_back</span> Back
    </a>
    <button type="submit" id="prefSubmit" class="inline-flex items-center gap-2 bg-gradient-to-br from-primary to-primary-container text-white font-bold py-3 px-8 rounded-xl shadow-lg shadow-primary/20 hover:opacity-90 transition-all active:scale-[0.98]">
      Next Step <span class="material-symbols-outlined text-lg">arrow_forward</span>
    </button>
  </div>
</form>
@endsection

@push('scripts')
<script>
$(function () {
  var hasSavedTimezone = @json(!empty($ud->timezone));
  var hasSavedDateFormat = @json(!empty($ud->date_time_format));
  var hasSavedSizeUnit = @json(!empty($ud->size_unit));
  var locale = navigator.language || 'en-GB';
  var region = (locale.split('-')[1] || '').toUpperCase();

  if (!hasSavedTimezone) {
    var detectedTimezone = Intl.DateTimeFormat().resolvedOptions().timeZone;
    if (detectedTimezone) $('#timezone').val(detectedTimezone);
  }
  if (!hasSavedDateFormat) {
    if (region === 'US') $('#date_time_format').val('MM/DD/YYYY');
    else if (/^(ja|zh|ko)/i.test(locale)) $('#date_time_format').val('YYYY-MM-DD');
    else $('#date_time_format').val('DD/MM/YYYY');
  }
  if (!hasSavedSizeUnit) {
    $('#size_unit').val(['US', 'LR', 'MM'].includes(region) ? 'in' : 'cm');
  }

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
$(function () {
  $.each(['timezone', 'date_time_format', 'currency', 'minimum_deposit_amount', 'hourly_rate', 'half_day_rate', 'full_day_rate'], function (_, id) {
    $('#' + id).on('change input', function () {
      if (typeof window.clearOnboardingFieldError === 'function') window.clearOnboardingFieldError(id);
    });
  });

  function showPrefErrors(errors) {
    $.each(errors, function (k, messages) {
      var $err = $('#' + k + '_error');
      if (!$err.length) return;
      $err.text(messages[0]).removeClass('hidden');
    });
    if (typeof window.scrollToFirstOnboardingError === 'function') {
      window.scrollToFirstOnboardingError(document.getElementById('prefForm'));
    }
  }

  $('#prefForm').on('submit', function (e) {
    e.preventDefault();
    var $btn = $('#prefSubmit');
    var originalBtnHtml = $btn.html();
    $btn.prop('disabled', true);
    $btn.text('Saving...');
    var fd = new FormData(this);
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
      })
      .always(function () {
        $btn.prop('disabled', false);
        $btn.html(originalBtnHtml);
      });
  });
});
</script>
@endpush
