@extends('layouts.onboarding_bookpay')

@section('title', 'Payouts')

@php
  $ud = $userDetail;
  $studioEmail = old('studio_email', $ud->studio->email ?? '');
  $pt = in_array($ud->payment_type ?? '', ['artist_account', 'studio_account'], true) ? $ud->payment_type : 'artist_account';
  $payoutKey = match ($pt) {
    'studio_account' => 'studio',
    default => 'artist',
  };
  $stripeComplete = (bool) ($stripeStatus['complete'] ?? false);
  $artistStripeConnected = (bool) ($artistStripeConnected ?? false);
  $studioPayoutConnected = (bool) ($studioPayoutConnected ?? false);
  $studioPayoutCommitted = (bool) ($studioPayoutCommitted ?? false);
  $studioPayoutStatus = match (true) {
    $studioPayoutConnected => 'connected',
    $studioPayoutCommitted => 'not_connected',
    default => 'email_not_sent',
  };
  $payoutOptionLocked = (bool) ($payoutOptionLocked ?? ($artistStripeConnected || $studioPayoutCommitted));
  $stripeConnectLocale = $stripeConnectLocale ?? config('services.stripe.connect.locale', 'en-US');
  $payoutBankCountry = $payoutBankCountry ?? $ud->payout_bank_country ?? null;
  $payoutWaitingListCountry = $payoutWaitingListCountry ?? $ud->payout_waiting_list_country ?? null;
  $payoutRegistrationCountries = $payoutRegistrationCountries ?? [];
  $payoutBankCountryName = $payoutBankCountryName ?? ($payoutBankCountry ? \App\Support\StripeConnectCountries::nameFor($payoutBankCountry) : null);
  $hasSignupPayoutCountry = $payoutBankCountry
    && \App\Support\StripeConnectCountries::isSupported($payoutBankCountry);
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

    <div id="payoutOptionLockBanner" class="rounded-xl border border-outline-variant/30 bg-surface-container-low px-4 py-3 text-sm text-on-surface-variant mb-6 max-w-2xl {{ $payoutOptionLocked ? '' : 'hidden' }}">
      Your payout option is locked after setup is saved. Disconnect your current setup in Payout Settings before switching between Artist and Studio.
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">
      <div class="payout-card {{ $payoutKey === 'artist' ? 'selected' : '' }} {{ $payoutOptionLocked && $payoutKey !== 'artist' ? 'opacity-55 cursor-not-allowed' : '' }}" onclick="selectPayout('artist', this)" id="card-artist" role="button" tabindex="0">
        <div class="radio-indicator"></div>
        <div class="w-12 h-12 rounded-xl bg-secondary-fixed flex items-center justify-center mb-5">
          <span class="material-symbols-outlined text-primary text-2xl">person</span>
        </div>
        <h3 class="text-xl font-bold text-on-surface mb-2">Artist</h3>
        <p class="text-on-surface-variant text-sm leading-relaxed mb-6">Funds are paid directly to you. Ideal for independent freelancers.</p>
        <span class="inline-flex items-center gap-1 text-sm font-bold text-primary">You get paid directly <span class="material-symbols-outlined text-base">arrow_forward</span></span>
      </div>

      <div class="payout-card {{ $payoutKey === 'studio' ? 'selected' : '' }} {{ $payoutOptionLocked && $payoutKey !== 'studio' ? 'opacity-55 cursor-not-allowed' : '' }}"
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
      <div class="bg-surface-container-low rounded-2xl p-6 bg-white space-y-6">
        @if ($artistStripeConnected)
          <div class="max-w-2xl rounded-xl border border-green-200 bg-green-50 text-green-900 px-4 py-4 text-sm">
            <p class="font-semibold flex items-center gap-2 mb-2">
              <span class="material-symbols-outlined text-base">check_circle</span>
              Stripe account connected
            </p>
            <p class="text-green-800">Your artist payouts are already set up through Stripe.</p>
            @if ($payoutBankCountry && ($payoutBankCountryName ?? null))
              <p class="text-green-800 mt-2">Bank account country: <strong>{{ $payoutBankCountryName }}</strong></p>
            @endif
          </div>
        @else
          @if ($payoutWaitingListCountry)
            <div class="rounded-xl border border-green-200 bg-green-50 text-green-900 px-4 py-4 max-w-md">
              <p class="text-sm font-semibold mb-1">Your country isn't supported yet</p>
              <p class="text-sm">We'll notify you at <strong>{{ auth()->user()->email }}</strong> when payouts become available.</p>
            </div>
          @else
            @if (! $hasSignupPayoutCountry)
              <div class="rounded-xl border border-amber-200 bg-amber-50 text-amber-900 px-4 py-4 max-w-md">
                <p class="text-sm font-semibold mb-1">Payout country missing</p>
                <p class="text-sm">We need the country you chose at signup to set up Stripe payouts. Please contact support if this looks wrong.</p>
              </div>
            @else
            <div id="payoutConnectIntroStep" class="max-w-2xl">
              <p class="text-on-surface-variant text-sm mb-5">Connect your bank account through Stripe to receive payouts directly.</p>
              @if ($payoutBankCountryName)
                <p class="text-sm text-on-surface mb-4">Bank account country from signup: <strong>{{ $payoutBankCountryName }}</strong></p>
              @endif
              <button
                type="button"
                id="connectBankAccountBtn"
                class="inline-flex items-center gap-2 bg-gradient-to-br from-primary to-primary-container text-white font-bold py-3.5 px-8 rounded-xl shadow-lg shadow-primary/20 hover:opacity-90 transition-all active:scale-[0.98]"
              >
                <span class="material-symbols-outlined text-xl">account_balance</span>
                Connect Bank Account
              </button>
            </div>

            <div id="payoutStripeStep" class="hidden">
              <div class="mb-4 pb-4 border-b border-outline-variant/20 max-w-2xl">
                <p id="payoutStripeStepTitle" class="text-base font-semibold text-on-surface">Complete your payout details</p>
                <p id="payoutStripeStepDescription" class="text-on-surface-variant text-sm mt-2">
                  Add your personal details, upload an identity document (passport or ID), and enter your bank account below{{ $payoutBankCountryName ? ' for payouts to '.$payoutBankCountryName : '' }}. Onboarding will finish automatically when you're done.
                </p>
              </div>

              @if (!($stripeConnectConfigured ?? false))
                <div class="rounded-xl border border-red-200 bg-red-50 text-red-800 px-4 py-3 text-sm">
                  Stripe is not configured. You can skip this step for now and finish payout setup later in settings.
                </div>
              @else
                <div id="stripeConnectMount" class="min-h-[420px] rounded-xl p-1 bg-white overflow-hidden"></div>
                <p id="stripe_connect_error" class="text-error text-xs mt-2 hidden"></p>
                <p id="stripeConnectHint" class="text-on-surface-variant text-xs mt-3 {{ $stripeComplete ? 'hidden' : '' }}">
                  You'll be guided through personal details, identity document upload, and bank account setup. Onboarding will finish automatically once all steps are complete.
                </p>
              @endif
            </div>
            @endif
          @endif
        @endif
      </div>
    </div>

    <div id="payout-studio" class="{{ $payoutKey !== 'studio' ? 'hidden' : '' }}">
      <div class="bg-surface-container-low rounded-2xl p-6 space-y-4">
        <div id="studioPayoutEmailNotSent" class="max-w-2xl space-y-4 {{ $studioPayoutStatus !== 'email_not_sent' ? 'hidden' : '' }}">
          <span class="inline-flex items-center gap-1.5 rounded-full bg-surface-container-high text-on-surface-variant border border-outline-variant/40 px-3 py-1 text-xs font-bold uppercase tracking-wide">Email not sent</span>
          <p class="text-on-surface-variant text-sm">You need to send an email to your studio and ask them to connect their bank account. Payouts are on hold until they do.</p>
          <div>
            <label for="studio_email" class="block text-sm font-semibold text-on-surface mb-2">Studio Email Address</label>
            <input type="email" id="studio_email" name="studio_email" value="{{ $studioEmail }}" placeholder="studio@example.com" class="form-input" autocomplete="email">
          </div>
          <button type="button" id="payStudioSend" class="inline-flex items-center gap-2 bg-gradient-to-br from-primary to-primary-container text-white font-bold py-3 px-8 rounded-xl shadow-lg shadow-primary/20 hover:opacity-90 transition-all active:scale-[0.98]">
            Send
          </button>
        </div>

        <div id="studioPayoutNotConnected" class="max-w-2xl space-y-4 {{ $studioPayoutStatus !== 'not_connected' ? 'hidden' : '' }}">
          <span class="inline-flex items-center gap-1.5 rounded-full bg-amber-50 text-amber-900 border border-amber-200/80 px-3 py-1 text-xs font-bold uppercase tracking-wide">Not connected</span>
          <p class="text-on-surface-variant text-sm">Your studio hasn't connected a bank account yet. Payouts are on hold until they do.</p>
          <div>
            <label for="studio_email_not_connected" class="block text-sm font-semibold text-on-surface mb-2">Studio Email Address</label>
            <div class="flex flex-col sm:flex-row sm:items-start gap-2">
              <input type="email" id="studio_email_not_connected" value="{{ $studioEmail }}" placeholder="studio@example.com" class="form-input flex-1" autocomplete="email" readonly>
              <button type="button" id="editStudioEmailBtn" class="inline-flex items-center justify-center gap-1.5 text-sm font-semibold text-primary border border-primary/20 px-4 py-2.5 rounded-xl hover:bg-primary/5 transition-colors whitespace-nowrap">
                <span class="material-symbols-outlined text-[18px]">edit</span> Edit
              </button>
              <button type="button" id="cancelStudioEmailEditBtn" class="hidden inline-flex items-center justify-center gap-1.5 text-sm font-semibold text-on-surface-variant border border-outline-variant/40 px-4 py-2.5 rounded-xl hover:bg-surface-container-high transition-colors whitespace-nowrap">
                Cancel
              </button>
            </div>
          </div>
          <button type="button" id="payStudioReminder" class="inline-flex items-center gap-2 bg-gradient-to-br from-primary to-primary-container text-white font-bold py-3 px-8 rounded-xl shadow-lg shadow-primary/20 hover:opacity-90 transition-all active:scale-[0.98]">
            Send a reminder to your studio
          </button>
        </div>

        <div id="studioPayoutConnected" class="max-w-2xl space-y-4 {{ $studioPayoutStatus !== 'connected' ? 'hidden' : '' }}">
          <span class="inline-flex items-center gap-1.5 rounded-full bg-green-50 text-green-800 border border-green-200/80 px-3 py-1 text-xs font-bold uppercase tracking-wide">Connected</span>
          <p class="text-on-surface-variant text-sm">Your studio's bank account is connected. You're ready to receive payments.</p>
          @if ($studioEmail)
            <div>
              <p class="text-xs uppercase tracking-wider text-on-surface-variant font-medium">Studio email</p>
              <p class="text-sm font-semibold text-on-surface mt-1">{{ $studioEmail }}</p>
            </div>
          @endif
        </div>

        @if ($studioPayoutCommitted)
          <div id="studioPayoutDisconnectWrap" class="max-w-2xl pt-4 mt-2 border-t border-outline-variant/20">
            <button type="button" id="disconnectStudioBtn" class="text-sm font-semibold text-error hover:text-on-error-container border border-error/20 px-4 py-2 rounded-xl hover:bg-error-container/30 transition-colors">
              Disconnect studio payout
            </button>
            <p class="text-xs text-on-surface-variant mt-2">Cancel this studio request and switch to artist payout or invite a different studio.</p>
          </div>
        @endif

        <p id="studio_email_error" class="text-error text-sm mt-3 hidden"></p>
      </div>
    </div>

    <p id="payment_type_error" class="text-error text-sm mt-4 hidden"></p>
    <div id="payAlert" class="hidden rounded-xl px-4 py-3 text-sm mt-4"></div>
    <p id="paySkipHint" class="text-on-surface-variant text-sm mt-4 max-w-xl">You can finish this step now, or come back to it later — just know that payouts must be set up before you can collect deposits and confirm bookings.</p>
  </div>

  <div class="sticky bottom-0 bg-surface border-t border-outline-variant/10 px-8 md:px-12 py-5 flex flex-wrap items-center justify-between gap-4 mt-auto">
    <a href="{{ route('onboarding.calendar') }}" class="inline-flex items-center gap-1 text-on-surface font-semibold hover:text-primary transition-colors">
      <span class="material-symbols-outlined text-lg">arrow_back</span> Back
    </a>
    <div class="flex flex-wrap items-center gap-3 sm:ml-auto">
      <button type="button" id="paySkip" class="inline-flex items-center gap-2 font-semibold py-3 px-6 rounded-xl border border-outline-variant/40 text-on-surface-variant hover:bg-surface-container-high transition-colors">
        Set up later
      </button>
      <button type="button" id="payFinish" class="hidden inline-flex items-center gap-2 font-bold py-3 px-6 rounded-xl bg-gradient-to-br from-primary to-primary-container text-white shadow-lg shadow-primary/20 hover:opacity-90 transition-all">
        Go to dashboard
      </button>
    </div>
  </div>
</form>

<div id="disconnectStudioModal" class="hidden fixed inset-0 z-[200] flex items-center justify-center p-4 bg-black/50" role="dialog" aria-modal="true">
  <div class="bg-white rounded-2xl max-w-md w-full p-6 shadow-xl">
    <h5 class="text-lg font-bold text-on-surface mb-2">Disconnect studio payout?</h5>
    <p class="text-on-surface-variant text-sm mb-6">This cancels the request to your studio. You can switch to artist payout or send an invite to a different studio anytime.</p>
    <div class="flex justify-end gap-3">
      <button type="button" id="cancelDisconnectStudio" class="rounded-xl px-5 py-2.5 text-sm font-semibold text-on-surface hover:bg-surface-container-low">Cancel</button>
      <button type="button" id="confirmDisconnectStudioBtn" class="rounded-xl px-5 py-2.5 text-sm font-semibold bg-error text-white hover:opacity-90">Disconnect</button>
    </div>
  </div>
</div>

@endsection

@push('scripts')
@include('partials.reddit-pixel', ['event' => 'Step_6'])
<script type="module">
import { loadConnectAndInitialize } from 'https://esm.sh/@stripe/connect-js@3.3.34/pure';

const stripeConfigured = @json($stripeConnectConfigured ?? false);
const publishableKey = @json($stripePublishableKey ?? '');
const sessionUrl = @json(route('onboarding.payment.stripe.session'));
const statusUrl = @json(route('onboarding.payment.stripe.status'));
const stripeConnectLocale = @json($stripeConnectLocale);
const stripeConnectAppearance = @json(config('services.stripe.connect.appearance', []));
const initialWaitingListCountry = @json($payoutWaitingListCountry);
const initialPayoutBankCountry = @json($payoutBankCountry);
const initialPayoutBankCountryName = @json($payoutBankCountryName ?? null);
window.stripeOnboardingComplete = @json($stripeComplete);
window.stripeAutoFinishTriggered = false;
window.payoutBankCountrySelected = @json((bool) $hasSignupPayoutCountry);

let connectInstance = null;
let onboardingMounted = false;
let autoFinishInProgress = false;
let stripeStatusPollTimer = null;
let currentPayoutStep = 'intro';
let autoFinishAttempts = 0;
const MAX_AUTO_FINISH_ATTEMPTS = 3;

window.setStripeOnboardingComplete = function (complete) {
  window.stripeOnboardingComplete = !!complete;
  const hint = document.getElementById('stripeConnectHint');
  if (complete && hint) {
    hint.classList.add('hidden');
  } else if (hint) {
    hint.classList.remove('hidden');
  }
  window.updatePaymentSkipUi(currentPayoutStep);
};

window.lockPayoutOptions = function (activeKey) {
  window.payoutOptionLocked = true;
  window.activePayoutKey = activeKey;

  const banner = document.getElementById('payoutOptionLockBanner');
  if (banner) banner.classList.remove('hidden');

  const artistCard = document.getElementById('card-artist');
  const studioCard = document.getElementById('card-studio');
  if (artistCard) {
    artistCard.classList.toggle('opacity-55', activeKey !== 'artist');
    artistCard.classList.toggle('cursor-not-allowed', activeKey !== 'artist');
  }
  if (studioCard) {
    studioCard.classList.toggle('opacity-55', activeKey !== 'studio');
    studioCard.classList.toggle('cursor-not-allowed', activeKey !== 'studio');
  }
};

let stripeSessionData = null;

function isStripeDetailsSubmitted(status) {
  return !!(status?.success && (status.details_submitted || status.submitted || status.complete));
}

function stopStripeStatusPolling() {
  if (stripeStatusPollTimer) {
    clearInterval(stripeStatusPollTimer);
    stripeStatusPollTimer = null;
  }
}

function startStripeStatusPolling() {
  stopStripeStatusPolling();
  stripeStatusPollTimer = setInterval(() => {
    maybeFinalizeOnboardingStripe().catch(() => {});
  }, 2000);
}

function resetAutoFinishFlags() {
  autoFinishInProgress = false;
  window.stripeAutoFinishTriggered = false;
}

async function maybeFinalizeOnboardingStripe() {
  if (autoFinishInProgress) {
    return;
  }

  const status = await refreshStripeStatus();
  if (!isStripeDetailsSubmitted(status)) {
    return;
  }

  window.setStripeOnboardingComplete(true);
  window.lockPayoutOptions('artist');
  await tryAutoFinishOnboarding();
}

async function createStripeSession() {
  const res = await fetch(sessionUrl, {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json',
      'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
      Accept: 'application/json',
    },
    body: JSON.stringify({}),
  });
  const data = await res.json();
  if (!res.ok || !data.client_secret) {
    throw new Error(data.message || 'Could not start Stripe onboarding.');
  }
  stripeSessionData = data;
  return data;
}

function resetStripeMount() {
  stopStripeStatusPolling();
  onboardingMounted = false;
  connectInstance = null;
  stripeSessionData = null;
  const mount = document.getElementById('stripeConnectMount');
  if (mount) mount.innerHTML = '';
}

async function refreshStripeStatus() {
  const res = await fetch(statusUrl, { headers: { Accept: 'application/json' } });
  const data = await res.json();
  if (res.ok && data.success) {
    window.setStripeOnboardingComplete(!!(data.complete || data.details_submitted || data.submitted));
  }
  return data;
}

window.refreshStripeOnboardingStatus = refreshStripeStatus;

async function tryAutoFinishOnboarding(options = {}) {
  if (autoFinishInProgress) {
    return false;
  }

  if (!options.force && autoFinishAttempts >= MAX_AUTO_FINISH_ATTEMPTS) {
    window.updatePaymentSkipUi(currentPayoutStep);
    stopStripeStatusPolling();
    return false;
  }

  if (typeof window.submitPaymentForm !== 'function') {
    setTimeout(() => {
      tryAutoFinishOnboarding(options).catch(() => {});
    }, 300);
    return false;
  }

  autoFinishInProgress = true;
  window.stripeAutoFinishTriggered = true;
  if (!options.force) {
    autoFinishAttempts += 1;
  }

  try {
    const data = await window.submitPaymentForm({
      auto: true,
      stripeExit: true,
      _retried: !!options._retried,
    });

    if (data?.success && data?.redirect) {
      stopStripeStatusPolling();
      window.location.href = data.redirect;
      return true;
    }

    throw new Error(data?.message || 'Could not complete onboarding.');
  } catch (err) {
    resetAutoFinishFlags();
    window.updatePaymentSkipUi(currentPayoutStep);

    if (autoFinishAttempts < MAX_AUTO_FINISH_ATTEMPTS && onboardingMounted && !stripeStatusPollTimer) {
      startStripeStatusPolling();
    } else {
      stopStripeStatusPolling();
    }

    console.warn('Auto-finish onboarding failed', err);
    return false;
  } finally {
    autoFinishInProgress = false;
  }
}

window.tryAutoFinishOnboarding = tryAutoFinishOnboarding;

async function mountStripeOnboarding() {
  const container = document.getElementById('stripeConnectMount');
  if (!stripeConfigured || !publishableKey || !container) {
    return;
  }
  if (!window.payoutBankCountrySelected) {
    return;
  }
  if (onboardingMounted) {
    return;
  }

  resetStripeMount();
  container.innerHTML = '<p class="text-sm text-on-surface-variant p-6">Loading Stripe onboarding…</p>';

  try {
    await createStripeSession();

    connectInstance = loadConnectAndInitialize({
      publishableKey,
      fetchClientSecret: async () => stripeSessionData.client_secret,
      locale: stripeConnectLocale || 'en-US',
      appearance: stripeConnectAppearance,
    });
    connectInstance.update({ locale: stripeConnectLocale || 'en-US' });

    const accountOnboarding = connectInstance.create('account-onboarding');
    const collectionOptions = stripeSessionData.collection_options || {};
    accountOnboarding.setCollectionOptions({
      fields: collectionOptions.fields || 'eventually_due',
      futureRequirements: collectionOptions.futureRequirements || 'include',
      ...(collectionOptions.requirements ? { requirements: collectionOptions.requirements } : {}),
    });
    accountOnboarding.setOnExit(async () => {
      await new Promise((resolve) => setTimeout(resolve, 800));
      await maybeFinalizeOnboardingStripe();
    });
    accountOnboarding.setOnStepChange(() => {
      window.clearOnboardingFieldError && window.clearOnboardingFieldError('stripe_connect');
      setTimeout(() => {
        maybeFinalizeOnboardingStripe().catch(() => {});
      }, 800);
    });

    container.innerHTML = '';
    container.appendChild(accountOnboarding);
    onboardingMounted = true;
    startStripeStatusPolling();
  } catch (err) {
    container.innerHTML = '';
    const errEl = document.getElementById('stripe_connect_error');
    if (errEl) {
      errEl.textContent = err.message || 'Could not load Stripe onboarding.';
      errEl.classList.remove('hidden');
    }
  }
}

window.mountStripeOnboardingIfNeeded = async function () {
  const artistVisible = !document.getElementById('payout-artist').classList.contains('hidden');
  if (artistVisible && stripeConfigured && window.payoutBankCountrySelected) {
    await mountStripeOnboarding();
  }
};

window.showPayoutStep = function (step) {
  currentPayoutStep = step || 'intro';
  const intro = document.getElementById('payoutConnectIntroStep');
  const stripe = document.getElementById('payoutStripeStep');
  if (intro) intro.classList.toggle('hidden', currentPayoutStep !== 'intro');
  if (stripe) stripe.classList.toggle('hidden', currentPayoutStep !== 'stripe');
  window.updatePaymentSkipUi(currentPayoutStep);
};

window.updatePaymentSkipUi = function (payoutStep) {
  currentPayoutStep = payoutStep || currentPayoutStep || 'intro';
  const paymentType = document.getElementById('payment_type')?.value;
  const stripeReady = !!(window.stripeOnboardingComplete || window.artistStripeConnected);
  const inArtistConnectFlow = paymentType === 'artist_account'
    && !window.artistStripeConnected
    && !window.stripeOnboardingComplete
    && currentPayoutStep === 'stripe';
  const skipHint = document.getElementById('paySkipHint');
  const skipBtn = document.getElementById('paySkip');
  const finishBtn = document.getElementById('payFinish');

  if (skipHint) skipHint.classList.toggle('hidden', inArtistConnectFlow || (paymentType === 'artist_account' && stripeReady));
  if (skipBtn) skipBtn.classList.toggle('hidden', inArtistConnectFlow || (paymentType === 'artist_account' && stripeReady));
  if (finishBtn) {
    const showFinish = paymentType === 'artist_account' && stripeReady;
    finishBtn.classList.toggle('hidden', !showFinish);
  }
};

document.getElementById('connectBankAccountBtn')?.addEventListener('click', async () => {
  if (!window.payoutBankCountrySelected) {
    const errEl = document.getElementById('stripe_connect_error');
    if (errEl) {
      errEl.textContent = 'Your signup country is required before connecting Stripe.';
      errEl.classList.remove('hidden');
    }
    return;
  }
  window.showPayoutStep('stripe');
  await mountStripeOnboarding();
});

window.artistStripeConnected = @json($artistStripeConnected);

if (!window.artistStripeConnected && !initialWaitingListCountry && window.payoutBankCountrySelected) {
  window.showPayoutStep('intro');
} else {
  window.updatePaymentSkipUi(window.artistStripeConnected ? 'intro' : 'intro');
}
</script>
<script>
window.payoutOptionLocked = @json($payoutOptionLocked);
window.activePayoutKey = @json($payoutKey);
const artistStripeConnected = @json($artistStripeConnected);

function selectPayout(type, el) {
  if (window.payoutOptionLocked && type !== window.activePayoutKey) {
    if (typeof window.showOnboardingAlert === 'function') {
      window.showOnboardingAlert('payAlert', 'Disconnect your current payout setup in Payout Settings before switching between Artist and Studio.', 'warning');
    } else {
      $('#payAlert').attr('class', 'rounded-xl px-4 py-3 text-sm mt-4 bg-amber-50 text-amber-900 border border-amber-200').text('Disconnect your current payout setup before switching between Artist and Studio.').removeClass('hidden');
    }
    return;
  }

  $('.payout-card').removeClass('selected');
  $(el).addClass('selected');
  var map = { artist: 'artist_account', studio: 'studio_account' };
  $('#payment_type').val(map[type]);
  $('#payout-artist').toggleClass('hidden', type !== 'artist');
  $('#payout-studio').toggleClass('hidden', type !== 'studio');
  if (type === 'artist' && !artistStripeConnected && typeof window.showPayoutStep === 'function') {
    window.showPayoutStep('intro');
  } else if (typeof window.updatePaymentSkipUi === 'function') {
    window.updatePaymentSkipUi('intro');
  }
  if (typeof window.clearOnboardingFieldError === 'function') window.clearOnboardingFieldError('payment_type');
  if (typeof window.clearOnboardingFieldError === 'function') window.clearOnboardingFieldError('stripe_connect');
  if (typeof window.clearOnboardingAlert === 'function') window.clearOnboardingAlert('payAlert');
}
$(function () {
  $('#studio_email').on('input', function () {
    if (typeof window.clearOnboardingFieldError === 'function') window.clearOnboardingFieldError('studio_email');
  });

  function showPaymentErrors(errors) {
    $.each(errors, function (k, messages) {
      var errId = k === 'stripe_connect' ? 'stripe_connect_error' : (k + '_error');
      var $el = $('#' + errId);
      if ($el.length) $el.text(messages[0]).removeClass('hidden');
    });
    if (typeof window.scrollToFirstOnboardingError === 'function') {
      window.scrollToFirstOnboardingError(document.getElementById('paymentForm'));
    }
  }

  async function validateArtistStripeSetupAsync() {
    var paymentType = $('#payment_type').val();
    if (paymentType !== 'artist_account') return true;
    if (artistStripeConnected || window.stripeOnboardingComplete) return true;
    if (!@json($stripeConnectConfigured ?? false)) return true;
    if (!window.payoutBankCountrySelected) {
      $('#stripe_connect_error').text('Please connect your bank account and complete payout setup before continuing.').removeClass('hidden');
      window.showPayoutStep('intro');
      if (typeof window.scrollToFirstOnboardingError === 'function') {
        window.scrollToFirstOnboardingError(document.getElementById('paymentForm'));
      }
      return false;
    }
    if (typeof window.refreshStripeOnboardingStatus === 'function') {
      await window.refreshStripeOnboardingStatus();
    }
    if (!window.stripeOnboardingComplete && !artistStripeConnected) {
      $('#stripe_connect_error').text('Please complete Stripe payout setup before continuing.').removeClass('hidden');
      if (typeof window.scrollToFirstOnboardingError === 'function') {
        window.scrollToFirstOnboardingError(document.getElementById('paymentForm'));
      }
      return false;
    }
    return true;
  }

  function submitPaymentForm(options) {
    options = options || {};
    var $alertEl = $('#payAlert');
    var $skip = $('#paySkip');
    var $finish = $('#payFinish');
    var $studioSend = $('#payStudioSend');
    var originalStudioSendHtml = $studioSend.length ? $studioSend.html() : '';
    var originalFinishHtml = $finish.length ? $finish.html() : '';
    $('#paymentForm').find('[id$="_error"]').addClass('hidden').text('');

    return (async function () {
      if (!options.auto) {
        if (!await validateArtistStripeSetupAsync()) {
          throw new Error('Please complete Stripe payout setup before continuing.');
        }
      }

      $skip.prop('disabled', true);
      $finish.prop('disabled', true);
      if ($finish.length && !options.auto) {
        $finish.text('Finishing…');
      }
      if ($studioSend.length) {
        $studioSend.prop('disabled', true);
        $studioSend.text(options.auto ? 'Finishing onboarding…' : 'Sending...');
      }

      const runAjax = function (attemptOptions) {
        return new Promise(function (resolve, reject) {
          var fd = new FormData(document.getElementById('paymentForm'));
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
                const paymentType = $('#payment_type').val();
                if (typeof window.lockPayoutOptions === 'function') {
                  if (paymentType === 'artist_account') {
                    window.lockPayoutOptions('artist');
                  } else if (paymentType === 'studio_account') {
                    window.lockPayoutOptions('studio');
                  }
                }
                resolve(data);
                window.location.href = data.redirect;
                return;
              }

              if (data.errors) {
                showPaymentErrors(data.errors);
                $alertEl.addClass('hidden');
                if (attemptOptions.auto && attemptOptions.stripeExit && !attemptOptions._retried) {
                  setTimeout(function () {
                    runAjax({ auto: true, stripeExit: true, _retried: true }).then(resolve).catch(reject);
                  }, 2000);
                  return;
                }
                reject(new Error((data.errors.stripe_connect && data.errors.stripe_connect[0]) || data.message || 'Could not complete'));
                return;
              }

              $alertEl.attr('class', 'rounded-xl px-4 py-3 text-sm mt-4 bg-red-50 text-red-800 border border-red-200');
              $alertEl.text(data.message || 'Could not complete').removeClass('hidden');
              reject(new Error(data.message || 'Could not complete'));
            })
            .fail(function (xhr) {
              if (xhr.status === 422 && xhr.responseJSON && xhr.responseJSON.errors) {
                showPaymentErrors(xhr.responseJSON.errors);
                $alertEl.addClass('hidden');
                if (attemptOptions.auto && attemptOptions.stripeExit && !attemptOptions._retried) {
                  setTimeout(function () {
                    runAjax({ auto: true, stripeExit: true, _retried: true }).then(resolve).catch(reject);
                  }, 2000);
                  return;
                }
                const firstError = Object.values(xhr.responseJSON.errors || {})[0];
                reject(new Error((firstError && firstError[0]) || xhr.responseJSON.message || 'Could not complete'));
                return;
              }

              const msg = (xhr.responseJSON && xhr.responseJSON.message) || 'Network error';
              $alertEl.attr('class', 'rounded-xl px-4 py-3 text-sm mt-4 bg-red-50 text-red-800 border border-red-200');
              $alertEl.text(msg).removeClass('hidden');
              reject(new Error(msg));
            })
            .always(function () {
              $skip.prop('disabled', false);
              $finish.prop('disabled', false);
              if ($finish.length) $finish.html(originalFinishHtml);
              if ($studioSend.length) {
                $studioSend.prop('disabled', false);
                $studioSend.html(originalStudioSendHtml);
              }
            });
        });
      };

      return runAjax(options);
    })();
  }

  window.submitPaymentForm = submitPaymentForm;

  $('#payFinish').on('click', function () {
    autoFinishAttempts = 0;
    submitPaymentForm({ auto: false }).catch(function () {});
  });

  if (artistStripeConnected || window.stripeOnboardingComplete) {
    if (typeof window.updatePaymentSkipUi === 'function') {
      window.updatePaymentSkipUi('intro');
    }
    setTimeout(function () {
      if (typeof window.tryAutoFinishOnboarding === 'function') {
        window.tryAutoFinishOnboarding().catch(function () {});
      }
    }, 400);
  }

  $('#payStudioSend').on('click', function () {
    $('#payment_type').val('studio_account');
    var email = ($('#studio_email').val() || '').trim();
    if (!email) {
      $('#studio_email_error').text('Studio email is required.').removeClass('hidden');
      return;
    }
    $('#studio_email_error').addClass('hidden').text('');
    submitPaymentForm().catch(function () {});
  });

  function openStudioDisconnectModal() { $('#disconnectStudioModal').removeClass('hidden'); }
  function closeStudioDisconnectModal() { $('#disconnectStudioModal').addClass('hidden'); }
  $('#disconnectStudioBtn').on('click', openStudioDisconnectModal);
  $('#cancelDisconnectStudio').on('click', closeStudioDisconnectModal);
  $('#disconnectStudioModal').on('click', function (e) { if (e.target === this) closeStudioDisconnectModal(); });
  $('#confirmDisconnectStudioBtn').on('click', function () {
    var $alertEl = $('#payAlert');
    closeStudioDisconnectModal();
    $alertEl.addClass('hidden').text('');
    $.ajax({
      url: @json(route('onboarding.payment.save')),
      type: 'POST',
      data: { _token: @json(csrf_token()), disconnect_studio: 1 },
      headers: { 'X-CSRF-TOKEN': @json(csrf_token()), Accept: 'application/json' },
    })
      .done(function (data) {
        if (data.success) {
          window.location.reload();
          return;
        }
        $alertEl.attr('class', 'rounded-xl px-4 py-3 text-sm mt-4 bg-red-50 text-red-800 border border-red-200').text(data.message || 'Could not disconnect studio payout.').removeClass('hidden');
      })
      .fail(function (xhr) {
        $alertEl.attr('class', 'rounded-xl px-4 py-3 text-sm mt-4 bg-red-50 text-red-800 border border-red-200').text((xhr.responseJSON && xhr.responseJSON.message) || 'Could not disconnect studio payout.').removeClass('hidden');
      });
  });

  $('#payStudioReminder').on('click', function () {
    var $btn = $(this);
    var $alertEl = $('#payAlert');
    var $emailInput = $('#studio_email_not_connected');
    var email = ($emailInput.val() || '').trim();
    if ($emailInput.length && !email) {
      $('#studio_email_error').text('Studio email is required.').removeClass('hidden');
      return;
    }
    $('#studio_email_error').addClass('hidden').text('');
    $alertEl.addClass('hidden').text('');
    $btn.prop('disabled', true).text('Sending...');
    var payload = { _token: @json(csrf_token()), resend_studio_email: 1 };
    if ($emailInput.length && email) {
      payload.studio_email = email;
    }
    $.ajax({
      url: @json(route('onboarding.payment.save')),
      type: 'POST',
      data: payload,
      headers: { 'X-CSRF-TOKEN': @json(csrf_token()), Accept: 'application/json' },
    })
      .done(function (data) {
        if (data.success) {
          if (data.studio_email && $emailInput.length) {
            $emailInput.val(data.studio_email);
            studioEmailOriginal = data.studio_email;
            setStudioEmailEditing(false);
          }
          $alertEl.attr('class', 'rounded-xl px-4 py-3 text-sm mt-4 bg-green-50 text-green-800 border border-green-200').text(data.message || 'Reminder sent to your studio.').removeClass('hidden');
          return;
        }
        $alertEl.attr('class', 'rounded-xl px-4 py-3 text-sm mt-4 bg-red-50 text-red-800 border border-red-200').text(data.message || 'Could not send reminder.').removeClass('hidden');
      })
      .fail(function (xhr) {
        if (xhr.status === 422 && xhr.responseJSON && xhr.responseJSON.errors && xhr.responseJSON.errors.studio_email) {
          $('#studio_email_error').text(xhr.responseJSON.errors.studio_email[0]).removeClass('hidden');
        }
        $alertEl.attr('class', 'rounded-xl px-4 py-3 text-sm mt-4 bg-red-50 text-red-800 border border-red-200').text((xhr.responseJSON && xhr.responseJSON.message) || 'Could not send reminder.').removeClass('hidden');
      })
      .always(function () {
        $btn.prop('disabled', false);
        updateStudioReminderButtonLabel();
      });
  });

  var studioEmailOriginal = $('#studio_email_not_connected').val() || '';
  function isStudioEmailEditing() {
    var $input = $('#studio_email_not_connected');
    return $input.length && !$input.prop('readonly');
  }
  function updateStudioReminderButtonLabel() {
    var $btn = $('#payStudioReminder');
    if (!$btn.length) return;
    if (!$btn.data('default-html')) {
      $btn.data('default-html', $btn.html());
    }
    var current = ($('#studio_email_not_connected').val() || '').trim().toLowerCase();
    var original = (studioEmailOriginal || '').trim().toLowerCase();
    if (isStudioEmailEditing() && current && current !== original) {
      $btn.html('<span class="material-symbols-outlined text-[18px]">send</span> Update & send email');
    } else {
      $btn.html($btn.data('default-html'));
    }
  }
  function setStudioEmailEditing(editing) {
    var $input = $('#studio_email_not_connected');
    if (!$input.length) return;
    if (editing) {
      studioEmailOriginal = $input.val() || '';
      $input.prop('readonly', false).focus();
      $('#editStudioEmailBtn').addClass('hidden');
      $('#cancelStudioEmailEditBtn').removeClass('hidden');
    } else {
      $input.val(studioEmailOriginal).prop('readonly', true);
      $('#editStudioEmailBtn').removeClass('hidden');
      $('#cancelStudioEmailEditBtn').addClass('hidden');
      $('#studio_email_error').addClass('hidden').text('');
    }
    updateStudioReminderButtonLabel();
  }
  $('#editStudioEmailBtn').on('click', function () { setStudioEmailEditing(true); });
  $('#cancelStudioEmailEditBtn').on('click', function () { setStudioEmailEditing(false); });
  $('#studio_email_not_connected').on('input', updateStudioReminderButtonLabel);

  $('#paySkip').on('click', function () {
    var $skip = $(this);
    var $studioSend = $('#payStudioSend');
    var $studioReminder = $('#payStudioReminder');
    var $alertEl = $('#payAlert');
    var originalSkipHtml = $skip.html();
    $skip.prop('disabled', true);
    if ($studioSend.length) $studioSend.prop('disabled', true);
    if ($studioReminder.length) $studioReminder.prop('disabled', true);
    $skip.text('Skipping...');
    $alertEl.addClass('hidden');
    $.ajax({
      url: @json(route('onboarding.payment.skip')),
      type: 'POST',
      data: { _token: @json(csrf_token()) },
      headers: { Accept: 'application/json' },
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
        if ($studioSend.length) $studioSend.prop('disabled', false);
        if ($studioReminder.length) $studioReminder.prop('disabled', false);
        $skip.html(originalSkipHtml);
      });
  });
});
</script>
@endpush
