@extends('layouts.onboarding_bookpay')

@section('title', 'Payments')

@php
  $ud = $userDetail;
  $bank = auth()->user()?->bankDetail;
  $studioEmail = old('studio_email', $ud->studio->email ?? '');
  $pt = $ud->payment_type ?? 'artist_account';
  $payoutKey = match ($pt) {
    'studio_account' => 'studio',
    'inkjin_account' => 'inkjin',
    default => 'artist',
  };
  /** Studio + Inkjin cards are not selectable while Artist + Stripe is connected. */
  $stripeLocksOtherPayouts = ($pt === 'artist_account' && !empty($ud->stripe_account_id));
@endphp

@section('content')
<form id="paymentForm" class="contents">
  @csrf
  <input type="hidden" name="payment_type" id="payment_type" value="{{ $pt }}" />
  <input type="hidden" name="stripe_account_id" id="stripe_account_id" value="{{ $ud->stripe_account_id ?? '' }}" />

  <div class="flex-1 p-8 md:p-12 max-w-5xl w-full mx-auto">
    <div class="mb-10">
      <h2 class="text-3xl font-extrabold text-on-surface tracking-tight">Payout Configuration</h2>
      <p class="text-on-surface-variant mt-2 max-w-lg">Select how you want your earnings to be handled. You can update these settings later in your financial dashboard.</p>
    </div>

    @if($stripeLocksOtherPayouts)
      <p class="text-sm text-on-surface-variant mb-4 max-w-2xl">
        <span class="material-symbols-outlined text-base align-middle mr-1 text-primary">lock</span>
        Studio and Inkjin payout options are unavailable while your Stripe account is connected. Disconnect Stripe below to change payout type.
      </p>
    @endif

    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
      <div class="payout-card {{ $payoutKey === 'artist' ? 'selected' : '' }}" onclick="selectPayout('artist', this)" id="card-artist" role="button" tabindex="0">
        <div class="radio-indicator"></div>
        <div class="w-12 h-12 rounded-xl bg-secondary-fixed flex items-center justify-center mb-5">
          <span class="material-symbols-outlined text-primary text-2xl">person</span>
        </div>
        <h3 class="text-xl font-bold text-on-surface mb-2">Artist</h3>
        <p class="text-on-surface-variant text-sm leading-relaxed mb-6">Funds are paid directly to you. Ideal for independent freelancers.</p>
        <span class="inline-flex items-center gap-1 text-sm font-bold text-primary">You get paid directly <span class="material-symbols-outlined text-base">arrow_forward</span></span>
      </div>

      <div class="payout-card {{ $payoutKey === 'studio' ? 'selected' : '' }} {{ $stripeLocksOtherPayouts ? 'opacity-50 pointer-events-none cursor-not-allowed select-none' : '' }}"
        onclick="selectPayout('studio', this)" id="card-studio" role="button" tabindex="{{ $stripeLocksOtherPayouts ? '-1' : '0' }}"
        @if($stripeLocksOtherPayouts) aria-disabled="true" title="Disconnect Stripe to choose Studio payouts" @endif>
        <div class="radio-indicator"></div>
        <div class="w-12 h-12 rounded-xl bg-secondary-fixed flex items-center justify-center mb-5">
          <span class="material-symbols-outlined text-primary text-2xl">storefront</span>
        </div>
        <h3 class="text-xl font-bold text-on-surface mb-2">Studio</h3>
        <p class="text-on-surface-variant text-sm leading-relaxed mb-6">Payments go to your studio, and your studio handles payouts to you. Best for resident artists.</p>
        <span class="inline-flex items-center gap-1 text-sm font-bold text-primary">Your studio gets paid <span class="material-symbols-outlined text-base">arrow_forward</span></span>
      </div>

      <div class="payout-card {{ $payoutKey === 'inkjin' ? 'selected' : '' }} {{ $stripeLocksOtherPayouts ? 'opacity-50 pointer-events-none cursor-not-allowed select-none' : '' }}"
        onclick="selectPayout('inkjin', this)" id="card-inkjin" role="button" tabindex="{{ $stripeLocksOtherPayouts ? '-1' : '0' }}"
        @if($stripeLocksOtherPayouts) aria-disabled="true" title="Disconnect Stripe to choose Inkjin payouts" @endif>
        <div class="radio-indicator"></div>
        <div class="w-12 h-12 rounded-xl bg-secondary-fixed flex items-center justify-center mb-5">
          <span class="material-symbols-outlined text-primary text-2xl">account_balance</span>
        </div>
        <h3 class="text-xl font-bold text-on-surface mb-2">Inkjin</h3>
        <p class="text-on-surface-variant text-sm leading-relaxed mb-6">Inkjin collects payments and we pay you after the booking is completed.</p>
        <span class="inline-flex items-center gap-1 text-sm font-bold text-primary">Inkjin collects for you <span class="material-symbols-outlined text-base">arrow_forward</span></span>
      </div>
    </div>

    <div id="payout-artist" class="{{ $payoutKey !== 'artist' ? 'hidden' : '' }}">
      @if($ud->stripe_account_id ?? null)
        <p class="text-green-700 text-sm mb-3 flex items-center gap-2"><span class="material-symbols-outlined text-lg">check_circle</span> Stripe connected</p>
        <button type="button" id="disconnectStripeBtn" class="inline-flex items-center gap-2 rounded-xl border border-error/40 text-error font-semibold py-2.5 px-5 text-sm hover:bg-error/5">Disconnect Stripe</button>
      @else
        <button type="button" id="connectStripeBtn" class="inline-flex items-center gap-2 bg-gradient-to-br from-primary to-primary-container text-white font-bold py-3 px-8 rounded-xl shadow-lg shadow-primary/20 hover:opacity-90 transition-all active:scale-[0.98]">
          <span class="material-symbols-outlined text-lg">link</span> Connect your Stripe account
        </button>
      @endif
      <p id="stripe_account_id_error" class="text-error text-sm mt-3 hidden"></p>
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

    <div id="payout-inkjin" class="{{ $payoutKey !== 'inkjin' ? 'hidden' : '' }}">
      <div class="bg-surface-container-low rounded-2xl p-6">
        <p class="text-on-surface-variant text-sm mb-6">Inkjin will process payouts according to your agreement. Optional: add bank details here for your records (not stored until enabled in your dashboard).</p>

        <h3 class="text-lg font-bold text-on-surface mb-6">Bank Account Details</h3>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
          <div>
            <label for="holder_name" class="block text-sm font-semibold text-on-surface mb-2">Account Holder Full Name</label>
            <input type="text" id="holder_name" name="account_holder_name" value="{{ old('account_holder_name', $bank->account_holder_name ?? '') }}" class="w-full px-4 py-3 rounded-xl border border-outline-variant/30 bg-white text-sm" placeholder="Enter your full name (first & last name)" autocomplete="name">
            <p id="account_holder_name_error" class="text-error text-xs mt-1 hidden"></p>
            <p class="text-[11px] uppercase tracking-wider font-semibold text-on-surface-variant mt-1.5">Must match legal identification</p>
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
        <div class="mb-8">
          <label for="inkjin_currency" class="block text-[11px] uppercase tracking-wider text-on-surface-variant font-medium mb-2">Currency <span class="text-red-600">*</span></label>
          <select id="inkjin_currency" name="currency" class="select w-full px-4 py-3 rounded-xl border border-outline-variant/30 bg-white text-sm" data-selected="{{ $ud->currency ?? '' }}"></select>
          <p id="currency_error" class="text-error text-xs mt-1 hidden"></p>
        </div>
      </div>
    </div>

    <p id="payment_type_error" class="text-error text-sm mt-4 hidden"></p>
    <div id="payAlert" class="hidden rounded-xl px-4 py-3 text-sm mt-4"></div>
  </div>

  <div class="sticky bottom-0 bg-surface border-t border-outline-variant/10 px-8 md:px-12 py-5 flex items-center justify-between mt-auto">
    <a href="{{ route('onboarding.calendar') }}" class="inline-flex items-center gap-1 text-on-surface font-semibold hover:text-primary transition-colors">
      <span class="material-symbols-outlined text-lg">arrow_back</span> Back
    </a>
    <button type="submit" id="paySubmit" class="inline-flex items-center gap-2 bg-gradient-to-br from-primary to-primary-container text-white font-bold py-3 px-8 rounded-xl shadow-lg shadow-primary/20 hover:opacity-90 transition-all active:scale-[0.98]">
      Complete Onboarding
    </button>
  </div>
</form>

<div id="disconnectStripeModal" class="hidden fixed inset-0 z-[200] flex items-center justify-center p-4 bg-black/50" role="dialog" aria-modal="true">
  <div class="bg-white rounded-2xl max-w-md w-full p-6 shadow-xl">
    <h5 class="text-lg font-bold text-on-surface mb-2">Disconnect Stripe?</h5>
    <p class="text-on-surface-variant text-sm mb-6">You can reconnect later.</p>
    <div class="flex justify-end gap-3">
      <button type="button" id="cancelDisconnectStripe" class="rounded-xl px-5 py-2.5 text-sm font-semibold text-on-surface hover:bg-surface-container-low">Cancel</button>
      <button type="button" id="confirmDisconnectStripeBtn" class="rounded-xl px-5 py-2.5 text-sm font-semibold bg-error text-white hover:opacity-90">Disconnect</button>
    </div>
  </div>
</div>
@endsection

@push('scripts')
<script>
function otherPayoutOptionsLocked() {
  return $('#payment_type').val() === 'artist_account' && !!String($('#stripe_account_id').val() || '').trim();
}

function selectPayout(type, el) {
  if ((type === 'studio' || type === 'inkjin') && otherPayoutOptionsLocked()) {
    return;
  }
  $('.payout-card').removeClass('selected');
  $(el).addClass('selected');
  var map = { artist: 'artist_account', studio: 'studio_account', inkjin: 'inkjin_account' };
  $('#payment_type').val(map[type]);
  $('#payout-artist').toggleClass('hidden', type !== 'artist');
  $('#payout-studio').toggleClass('hidden', type !== 'studio');
  $('#payout-inkjin').toggleClass('hidden', type !== 'inkjin');
  if (typeof window.clearOnboardingFieldError === 'function') window.clearOnboardingFieldError('payment_type');
  if (typeof window.clearOnboardingAlert === 'function') window.clearOnboardingAlert('payAlert');
  if (typeof window.initOnboardingSelect2 === 'function' && window.jQuery) {
    var $inkjin = window.jQuery('#payout-inkjin');
    // Defer so :visible checks pass when switching from Artist/Studio to Inkjin
    setTimeout(function () {
      window.initOnboardingSelect2($inkjin);
    }, 0);
  }
}
$(function () {
  var cur = document.getElementById('inkjin_currency');
  if (cur && typeof fillCurrencySelect === 'function') {
    fillCurrencySelect(cur, cur.getAttribute('data-selected') || 'USD');
  }
  $('#inkjin_currency').on('change', function () {
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

  $('#connectStripeBtn').on('click', function () {
    if (typeof window.clearOnboardingFieldError === 'function') window.clearOnboardingFieldError('stripe_account_id');
    if (typeof window.clearOnboardingAlert === 'function') window.clearOnboardingAlert('payAlert');
    window.location.href = @json(route('connect.stripe'));
  });

  function openStripeModal() {
    $('#disconnectStripeModal').removeClass('hidden');
  }
  function closeStripeModal() {
    $('#disconnectStripeModal').addClass('hidden');
  }
  $('#disconnectStripeBtn').on('click', openStripeModal);
  $('#cancelDisconnectStripe').on('click', closeStripeModal);
  $('#disconnectStripeModal').on('click', function (e) {
    if (e.target === this) closeStripeModal();
  });
  $('#confirmDisconnectStripeBtn').on('click', function () {
    closeStripeModal();
    $.ajax({
      url: @json(route('connect.stripe.disconnect')),
      type: 'POST',
      headers: {
        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content'),
        Accept: 'application/json',
      },
    }).done(function (data) {
      if (data.success) window.location.reload();
    });
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
    var originalBtnHtml = $btn.html();
    $('#paymentForm').find('[id$="_error"]').addClass('hidden').text('');
    $btn.prop('disabled', true);
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
        $btn.html(originalBtnHtml);
      });
  });
});
</script>
@endpush
