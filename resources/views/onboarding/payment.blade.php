@extends('layouts.onboarding_bookpay')

@section('title', 'Payments')

@php
  $ud = $userDetail;
  $bank = auth()->user()?->bankDetail;
  $studioEmail = old('studio_email', $ud->studio->email ?? '');
  $pt = in_array($ud->payment_type ?? '', ['artist_account', 'studio_account'], true) ? $ud->payment_type : 'artist_account';
  $payoutKey = match ($pt) {
    'studio_account' => 'studio',
    default => 'artist',
  };
@endphp

@section('content')
<form id="paymentForm" class="contents">
  @csrf
  <input type="hidden" name="payment_type" id="payment_type" value="{{ $pt }}" />

  <div class="flex-1 p-8 md:p-12 max-w-5xl w-full mx-auto">
    <div class="mb-10">
      <h2 class="text-3xl font-extrabold text-on-surface tracking-tight">Payout Configuration</h2>
      <p class="text-on-surface-variant mt-2 max-w-lg">Select how you want your earnings to be handled. You can update these settings later in your financial dashboard.</p>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">
      <div class="payout-card {{ $payoutKey === 'artist' ? 'selected' : '' }}" onclick="selectPayout('artist', this)" id="card-artist" role="button" tabindex="0">
        <div class="radio-indicator"></div>
        <div class="w-12 h-12 rounded-xl bg-secondary-fixed flex items-center justify-center mb-5">
          <span class="material-symbols-outlined text-primary text-2xl">person</span>
        </div>
        <h3 class="text-xl font-bold text-on-surface mb-2">Artist</h3>
        <p class="text-on-surface-variant text-sm leading-relaxed mb-6">Funds are paid directly to you. Ideal for independent freelancers.</p>
        <span class="inline-flex items-center gap-1 text-sm font-bold text-primary">You get paid directly <span class="material-symbols-outlined text-base">arrow_forward</span></span>
      </div>

      <div class="payout-card {{ $payoutKey === 'studio' ? 'selected' : '' }}"
        onclick="selectPayout('studio', this)" id="card-studio" role="button" tabindex="0">
        <div class="radio-indicator"></div>
        <div class="w-12 h-12 rounded-xl bg-secondary-fixed flex items-center justify-center mb-5">
          <span class="material-symbols-outlined text-primary text-2xl">storefront</span>
        </div>
        <h3 class="text-xl font-bold text-on-surface mb-2">Studio</h3>
        <p class="text-on-surface-variant text-sm leading-relaxed mb-6">Payments go to your studio, and your studio handles payouts to you. Best for resident artists.</p>
        <span class="inline-flex items-center gap-1 text-sm font-bold text-primary">Your studio gets paid <span class="material-symbols-outlined text-base">arrow_forward</span></span>
      </div>

    </div>

    <div id="payout-artist" class="{{ $payoutKey !== 'artist' ? 'hidden' : '' }}">
      <div class="bg-surface-container-low rounded-2xl p-6">
        <p class="text-on-surface-variant text-sm mb-6">Provide your bank details. Your payouts will be sent directly to this account.</p>
        <h3 class="text-lg font-bold text-on-surface mb-6">Bank Account Details</h3>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
          <div>
            <label for="holder_name" class="block text-sm font-semibold text-on-surface mb-2">Account Holder Full Name</label>
            <input type="text" id="holder_name" name="account_holder_name" value="{{ old('account_holder_name', $bank->account_holder_name ?? '') }}" class="w-full px-4 py-3 rounded-xl border border-outline-variant/30 bg-white text-sm" placeholder="Enter your full name" autocomplete="name">
            <p id="account_holder_name_error" class="text-error text-xs mt-1 hidden"></p>
          </div>
          <div>
            <label for="bank_name" class="block text-sm font-semibold text-on-surface mb-2">Bank Name</label>
            <input type="text" id="bank_name" name="bank_name" value="{{ old('bank_name', $bank->bank_name ?? '') }}" class="w-full px-4 py-3 rounded-xl border border-outline-variant/30 bg-white text-sm" placeholder="Enter your bank's name">
            <p id="bank_name_error" class="text-error text-xs mt-1 hidden"></p>
          </div>
        </div>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
          <div>
            <label for="iban" class="block text-sm font-semibold text-on-surface mb-2">Account Number / IBAN</label>
            <input type="text" id="iban" name="account_number" value="{{ old('account_number', $bank->account_number ?? '') }}" class="w-full px-4 py-3 rounded-xl border border-outline-variant/30 bg-white text-sm" placeholder="Enter your account number">
            <p id="account_number_error" class="text-error text-xs mt-1 hidden"></p>
          </div>
          <div>
            <label for="swift" class="block text-sm font-semibold text-on-surface mb-2">SWIFT / BIC</label>
            <input type="text" id="swift" name="swift_bic" value="{{ old('swift_bic', $bank->swift_bic ?? '') }}" class="w-full px-4 py-3 rounded-xl border border-outline-variant/30 bg-white text-sm" placeholder="Enter your bank's SWIFT/BIC">
            <p id="swift_bic_error" class="text-error text-xs mt-1 hidden"></p>
          </div>
        </div>
        <div class="mb-2">
          <label for="artist_currency" class="block text-[11px] uppercase tracking-wider text-on-surface-variant font-medium mb-2">Currency <span class="text-red-600">*</span></label>
          <select id="artist_currency" name="currency" class="select w-full px-4 py-3 rounded-xl border border-outline-variant/30 bg-white text-sm" data-selected="{{ old('currency', $bank->bank_currency ?? $ud->currency ?? 'USD') }}"></select>
          <p id="currency_error" class="text-error text-xs mt-1 hidden"></p>
        </div>
      </div>
    </div>

    <div id="payout-studio" class="{{ $payoutKey !== 'studio' ? 'hidden' : '' }}">
      <div class="bg-surface-container-low rounded-2xl p-6">
        <div class="space-y-4">
          <div>
            <label for="studio_email" class="block text-sm font-semibold text-on-surface mb-2">Studio Email Address</label>
            <input type="text" id="studio_email" name="studio_email" value="{{ $studioEmail }}" placeholder="studio@example.com" class="form-input" autocomplete="email">
            <p class="text-on-surface-variant text-xs mt-2">When you click Complete Onboarding, we send an approval email to this studio.</p>
          </div>
        </div>
        <p id="studio_email_error" class="text-error text-sm mt-3 hidden"></p>
      </div>
    </div>

    <p id="payment_type_error" class="text-error text-sm mt-4 hidden"></p>
    <div id="payAlert" class="hidden rounded-xl px-4 py-3 text-sm mt-4"></div>
    <p class="text-on-surface-variant text-sm mt-4 max-w-xl">You can complete payout details later in your financial settings if you prefer.</p>
  </div>

  <div class="sticky bottom-0 bg-surface border-t border-outline-variant/10 px-8 md:px-12 py-5 flex flex-wrap items-center justify-between gap-4 mt-auto">
    <a href="{{ route('onboarding.calendar') }}" class="inline-flex items-center gap-1 text-on-surface font-semibold hover:text-primary transition-colors">
      <span class="material-symbols-outlined text-lg">arrow_back</span> Back
    </a>
    <div class="flex flex-wrap items-center gap-3 sm:ml-auto">
      <button type="button" id="paySkip" class="inline-flex items-center gap-2 font-semibold py-3 px-6 rounded-xl border border-outline-variant/40 text-on-surface-variant hover:bg-surface-container-high transition-colors">
        Skip for now
      </button>
      <button type="submit" id="paySubmit" class="inline-flex items-center gap-2 bg-gradient-to-br from-primary to-primary-container text-white font-bold py-3 px-8 rounded-xl shadow-lg shadow-primary/20 hover:opacity-90 transition-all active:scale-[0.98]">
        Complete Onboarding
      </button>
    </div>
  </div>
</form>

@endsection

@push('scripts')
<script>
function selectPayout(type, el) {
  $('.payout-card').removeClass('selected');
  $(el).addClass('selected');
  var map = { artist: 'artist_account', studio: 'studio_account' };
  $('#payment_type').val(map[type]);
  $('#payout-artist').toggleClass('hidden', type !== 'artist');
  $('#payout-studio').toggleClass('hidden', type !== 'studio');
  if (typeof window.clearOnboardingFieldError === 'function') window.clearOnboardingFieldError('payment_type');
  if (typeof window.clearOnboardingAlert === 'function') window.clearOnboardingAlert('payAlert');
}
$(function () {
  var cur = document.getElementById('artist_currency');
  if (cur && typeof fillCurrencySelect === 'function') {
    fillCurrencySelect(cur, cur.getAttribute('data-selected') || 'USD');
  }
  $('#artist_currency').on('change', function () {
    if (typeof window.clearOnboardingFieldError === 'function') window.clearOnboardingFieldError('currency');
  });
  $('#holder_name').on('input', function () {
    if (typeof window.clearOnboardingFieldError === 'function') window.clearOnboardingFieldError('account_holder_name');
  });
  $('#bank_name').on('input', function () {
    if (typeof window.clearOnboardingFieldError === 'function') window.clearOnboardingFieldError('bank_name');
  });
  $('#iban').on('input', function () {
    if (typeof window.clearOnboardingFieldError === 'function') window.clearOnboardingFieldError('account_number');
  });
  $('#swift').on('input', function () {
    if (typeof window.clearOnboardingFieldError === 'function') window.clearOnboardingFieldError('swift_bic');
  });

  $('#studio_email').on('input', function () {
    if (typeof window.clearOnboardingFieldError === 'function') window.clearOnboardingFieldError('studio_email');
  });

  function showPaymentErrors(errors) {
    $.each(errors, function (k, messages) {
      var $el = $('#' + k + '_error');
      if ($el.length) $el.text(messages[0]).removeClass('hidden');
    });
    if (typeof window.scrollToFirstOnboardingError === 'function') {
      window.scrollToFirstOnboardingError(document.getElementById('paymentForm'));
    }
  }

  $('#paymentForm').on('submit', function (e) {
    e.preventDefault();
    var $alertEl = $('#payAlert');
    var $btn = $('#paySubmit');
    var $skip = $('#paySkip');
    var originalBtnHtml = $btn.html();
    $('#paymentForm').find('[id$="_error"]').addClass('hidden').text('');
    $btn.prop('disabled', true);
    $skip.prop('disabled', true);
    $btn.text('Submitting...');
    var fd = new FormData(this);
    $.ajax({
      url: @json(route('onboarding.payment.save')),
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
          showPaymentErrors(data.errors);
          $alertEl.addClass('hidden');
        } else {
          $alertEl.attr('class', 'rounded-xl px-4 py-3 text-sm mt-4 bg-red-50 text-red-800 border border-red-200');
          $alertEl.text(data.message || 'Could not complete').removeClass('hidden');
          if (typeof window.scrollToFirstOnboardingError === 'function') {
            window.scrollToFirstOnboardingError(document.getElementById('paymentForm'));
          }
        }
      })
      .fail(function (xhr) {
        if (xhr.status === 422 && xhr.responseJSON && xhr.responseJSON.errors) {
          showPaymentErrors(xhr.responseJSON.errors);
          $alertEl.addClass('hidden');
        } else {
          $alertEl.attr('class', 'rounded-xl px-4 py-3 text-sm mt-4 bg-red-50 text-red-800 border border-red-200');
          $alertEl.text('Network error').removeClass('hidden');
          if (typeof window.scrollToFirstOnboardingError === 'function') {
            window.scrollToFirstOnboardingError(document.getElementById('paymentForm'));
          }
        }
      })
      .always(function () {
        $btn.prop('disabled', false);
        $skip.prop('disabled', false);
        $btn.html(originalBtnHtml);
      });
  });

  $('#paySkip').on('click', function () {
    var $skip = $(this);
    var $submit = $('#paySubmit');
    var $alertEl = $('#payAlert');
    var originalSkipHtml = $skip.html();
    $skip.prop('disabled', true);
    $submit.prop('disabled', true);
    $skip.text('Skipping...');
    $alertEl.addClass('hidden');
    $.ajax({
      url: @json(route('onboarding.payment.skip')),
      type: 'POST',
      data: { _token: @json(csrf_token()) },
      headers: {
        Accept: 'application/json',
      },
    })
      .done(function (data) {
        if (data.success && data.redirect) {
          window.location.href = data.redirect;
          return;
        }
        $alertEl.attr('class', 'rounded-xl px-4 py-3 text-sm mt-4 bg-red-50 text-red-800 border border-red-200');
        $alertEl.text(data.message || 'Could not skip').removeClass('hidden');
      })
      .fail(function (xhr) {
        var msg = (xhr.responseJSON && xhr.responseJSON.message) || 'Network error';
        $alertEl.attr('class', 'rounded-xl px-4 py-3 text-sm mt-4 bg-red-50 text-red-800 border border-red-200');
        $alertEl.text(msg).removeClass('hidden');
      })
      .always(function () {
        $skip.prop('disabled', false);
        $submit.prop('disabled', false);
        $skip.html(originalSkipHtml);
      });
  });
});
</script>
@endpush
