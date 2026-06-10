@extends('layouts.onboarding_bookpay')

@section('title', 'Payments')

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
  $payoutOptionLocked = (bool) ($payoutOptionLocked ?? ($artistStripeConnected || $studioPayoutCommitted));
  $stripeConnectLocale = $stripeConnectLocale ?? config('services.stripe.connect.locale', 'en-US');
  $payoutBankCountry = $payoutBankCountry ?? $ud->payout_bank_country ?? null;
  $payoutWaitingListCountry = $payoutWaitingListCountry ?? $ud->payout_waiting_list_country ?? null;
  $stripeSupportedCountries = $stripeSupportedCountries ?? [];
  $stripeUnsupportedCountries = $stripeUnsupportedCountries ?? [];
  $bankCountryRoute = route('onboarding.payment.bank-country');
  $waitingListRoute = route('onboarding.payment.waiting-list');
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
      Your payout option is locked after setup is saved. Disconnect your current setup in Payment Settings before switching between Artist and Studio.
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
        {{-- Bank account country (InkJin-controlled) --}}
        <div id="payoutCountryStep" class="{{ ($payoutBankCountry || $payoutWaitingListCountry) && ! $payoutWaitingListCountry ? 'hidden' : '' }}">
          @if ($payoutWaitingListCountry)
            <div class="rounded-xl border border-green-200 bg-green-50 text-green-900 px-4 py-4 max-w-md">
              <p class="text-sm font-semibold mb-1">Your country isn't supported yet</p>
              <p class="text-sm">We'll notify you at <strong>{{ auth()->user()->email }}</strong> when payouts become available.</p>
            </div>
          @else
            <div class="max-w-2xl space-y-4">
              <div id="payoutCountryFields">
                <p id="payoutCountryTitle" class="text-base font-semibold text-on-surface mb-2">Where is your bank account based?</p>
                <p id="payoutCountryDescription" class="text-on-surface-variant text-sm mb-4">
                  This is the country of your bank account where you'll receive your payouts.
                  Are you using Revolut? Check your IBAN in the app — the first two letters show the country.
                  GR = Greece, LT = Lithuania, GB = United Kingdom.
                </p>
                <select id="payout_bank_country" name="payout_bank_country" class="select w-full max-w-md px-4 py-3 rounded-xl border border-outline-variant/30 bg-white text-sm" aria-label="Bank account country">
                  <option value="" disabled selected>Select country</option>
                  @foreach ($stripeSupportedCountries as $country)
                    <option value="{{ $country['code'] }}">{{ $country['name'] }}</option>
                  @endforeach
                  <option disabled>──────────</option>
                  <option value="__not_listed__">My country is not listed</option>
                </select>
                <p id="payout_bank_country_error" class="text-error text-xs mt-2 hidden"></p>
                <button type="button" id="payoutCountryContinue" class="hidden mt-4 inline-flex items-center gap-2 bg-gradient-to-br from-primary to-primary-container text-white font-bold py-3 px-6 rounded-xl shadow-lg shadow-primary/20 hover:opacity-90 transition-all disabled:opacity-70">
                  <span id="payoutCountryContinueLabel">Continue</span>
                  <span id="payoutCountryContinueIcon" class="material-symbols-outlined text-lg">arrow_forward</span>
                </button>
              </div>

              <div id="payoutWaitingListInline" class="hidden">
                <p class="text-on-surface-variant text-sm mb-3">Your country isn't supported yet. Tell us where you are and we'll notify you when payouts become available.</p>
                <label for="payout_waiting_list_country" class="block text-[11px] uppercase tracking-wider text-on-surface-variant font-medium mb-2">Select your country <span class="text-red-600">*</span></label>
                <select id="payout_waiting_list_country" name="payout_waiting_list_country" class="select w-full px-4 py-3 rounded-xl border border-outline-variant/30 bg-white text-sm">
                  <option value="" disabled selected>Select country</option>
                  @foreach ($stripeUnsupportedCountries as $countryName)
                    <option value="{{ $countryName }}">{{ $countryName }}</option>
                  @endforeach
                </select>
                <p id="payout_waiting_list_country_error" class="text-error text-xs mt-2 hidden"></p>
                <div id="payoutWaitingListSuccess" class="hidden rounded-xl border border-green-200 bg-green-50 text-green-900 px-4 py-3 mt-3 text-sm"></div>
              </div>
            </div>
          @endif
        </div>

        {{-- Stripe personal details + bank (after country selected) --}}
        <div id="payoutStripeStep" class="{{ $payoutBankCountry && ! $payoutWaitingListCountry ? '' : 'hidden' }}">
          <div class="mb-4 pb-4 border-b border-outline-variant/20 max-w-2xl">
            <p id="payoutStripeStepTitle" class="text-base font-semibold text-on-surface">Complete your payout details</p>
            <p id="payoutStripeStepDescription" class="text-on-surface-variant text-sm mt-2">
              @if ($payoutBankCountry && ($payoutBankCountryName ?? null))
                Add your personal details, upload an identity document (passport or ID), and enter your bank account below for payouts to {{ $payoutBankCountryName }}. Onboarding will finish automatically when you're done.
              @else
                Add your personal details, upload an identity document (passport or ID), and enter your bank account below. Onboarding will finish automatically when you're done.
              @endif
            </p>
            <div id="payoutStripeCountrySummary" class="mt-4 {{ $payoutBankCountry ? '' : 'hidden' }}">
              <p class="text-xs uppercase tracking-wider text-on-surface-variant font-medium">Bank account country</p>
              <p id="payoutBankCountryName" class="text-sm font-semibold text-on-surface">{{ $payoutBankCountryName ?? $payoutBankCountry ?? '' }}</p>
            </div>
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
      </div>
    </div>

    <div id="payout-studio" class="{{ $payoutKey !== 'studio' ? 'hidden' : '' }}">
      <div class="bg-surface-container-low rounded-2xl p-6">
        <div class="space-y-4">
          <div>
            <label for="studio_email" class="block text-sm font-semibold text-on-surface mb-2">Studio Email Address</label>
            <input type="text" id="studio_email" name="studio_email" value="{{ $studioEmail }}" placeholder="studio@example.com" class="form-input" autocomplete="email">
            <p class="text-on-surface-variant text-xs mt-2">When you click Complete Onboarding, we email your studio a secure link to connect Stripe for payouts.</p>
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
      <button type="submit" id="paySubmit" data-default-label="Complete Onboarding" data-waitlist-label="Notify me and complete onboarding" class="inline-flex items-center gap-2 bg-gradient-to-br from-primary to-primary-container text-white font-bold py-3 px-8 rounded-xl shadow-lg shadow-primary/20 hover:opacity-90 transition-all active:scale-[0.98]">
        Complete Onboarding
      </button>
    </div>
  </div>
</form>

@endsection

@push('scripts')
<script type="module">
import { loadConnectAndInitialize } from 'https://esm.sh/@stripe/connect-js@3.3.34/pure';

const stripeConfigured = @json($stripeConnectConfigured ?? false);
const publishableKey = @json($stripePublishableKey ?? '');
const sessionUrl = @json(route('onboarding.payment.stripe.session'));
const statusUrl = @json(route('onboarding.payment.stripe.status'));
const bankCountryUrl = @json($bankCountryRoute);
const waitingListUrl = @json($waitingListRoute);
const stripeConnectLocale = @json($stripeConnectLocale);
const initialPayoutBankCountry = @json($payoutBankCountry);
const initialWaitingListCountry = @json($payoutWaitingListCountry);
window.stripeOnboardingComplete = @json($stripeComplete);
window.stripeAutoFinishTriggered = false;
window.payoutBankCountrySelected = !!initialPayoutBankCountry && !initialWaitingListCountry;
window.payoutWaitingListMode = false;
window.payoutWaitingListSaved = !!initialWaitingListCountry;
window.payoutSupportedCountryPending = null;
window.savedPayoutBankCountry = initialPayoutBankCountry || null;

let connectInstance = null;
let onboardingMounted = false;
let autoFinishInProgress = false;

window.setStripeOnboardingComplete = function (complete) {
  window.stripeOnboardingComplete = !!complete;
  const hint = document.getElementById('stripeConnectHint');
  if (complete && hint) {
    hint.classList.add('hidden');
  } else if (hint) {
    hint.classList.remove('hidden');
  }
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
    window.setStripeOnboardingComplete(!!data.complete);
  }
  return data;
}

window.refreshStripeOnboardingStatus = refreshStripeStatus;

async function tryAutoFinishOnboarding() {
  if (autoFinishInProgress || window.stripeAutoFinishTriggered) {
    return;
  }
  if (window.payoutWaitingListMode || window.payoutWaitingListSaved) {
    return;
  }

  autoFinishInProgress = true;
  try {
    if (window.payoutSupportedCountryPending && !window.payoutBankCountrySelected) {
      await savePayoutBankCountry(window.payoutSupportedCountryPending);
    }
    if (typeof window.submitPaymentForm === 'function') {
      window.stripeAutoFinishTriggered = true;
      await window.submitPaymentForm({ auto: true, stripeExit: true });
    }
  } catch (err) {
    window.stripeAutoFinishTriggered = false;
    console.warn('Auto-finish onboarding failed', err);
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
      await new Promise((resolve) => setTimeout(resolve, 1500));
      const status = await refreshStripeStatus();
      if (status?.complete) {
        window.setStripeOnboardingComplete(true);
        window.lockPayoutOptions('artist');
        await tryAutoFinishOnboarding();
      }
    });
    accountOnboarding.setOnStepChange(() => {
      window.clearOnboardingFieldError && window.clearOnboardingFieldError('stripe_connect');
    });

    container.innerHTML = '';
    container.appendChild(accountOnboarding);
    onboardingMounted = true;
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
  const country = document.getElementById('payoutCountryStep');
  const stripe = document.getElementById('payoutStripeStep');
  if (country) country.classList.toggle('hidden', step !== 'country');
  if (stripe) stripe.classList.toggle('hidden', step !== 'stripe');
};

function setPayoutCountrySaving(saving) {
  const bankSelect = document.getElementById('payout_bank_country');
  const waitSelect = document.getElementById('payout_waiting_list_country');
  if (bankSelect) bankSelect.disabled = saving;
  if (waitSelect) waitSelect.disabled = saving;
}

function setCountryContinueLoading(loading) {
  const btn = document.getElementById('payoutCountryContinue');
  const label = document.getElementById('payoutCountryContinueLabel');
  const icon = document.getElementById('payoutCountryContinueIcon');
  const desc = document.getElementById('payoutCountryDescription');
  if (btn) btn.disabled = loading;
  if (label) label.textContent = loading ? 'Setting up payout verification…' : 'Continue';
  if (icon) {
    icon.textContent = loading ? 'progress_activity' : 'arrow_forward';
    icon.classList.toggle('animate-spin', loading);
  }
  if (desc && loading) {
    desc.textContent = 'Saving your bank country and loading secure identity and bank verification…';
  }
}

function resetCountryStepCopy() {
  const desc = document.getElementById('payoutCountryDescription');
  if (desc) {
    desc.textContent = "This is the country of your bank account where you'll receive your payouts. Are you using Revolut? Check your IBAN in the app — the first two letters show the country. GR = Greece, LT = Lithuania, GB = United Kingdom.";
  }
  setCountryContinueLoading(false);
}

function updateStripeStepCopy(countryName) {
  const title = document.getElementById('payoutStripeStepTitle');
  const desc = document.getElementById('payoutStripeStepDescription');
  const summary = document.getElementById('payoutStripeCountrySummary');
  const nameEl = document.getElementById('payoutBankCountryName');
  if (title) title.textContent = 'Complete your payout details';
  if (desc && countryName) {
    desc.textContent = `Add your personal details, upload an identity document (passport or ID), and enter your bank account below for payouts to ${countryName}. Onboarding will finish automatically when you're done.`;
  }
  if (nameEl && countryName) nameEl.textContent = countryName;
  if (summary) summary.classList.remove('hidden');
}

function togglePayoutCountryContinue(show) {
  const btn = document.getElementById('payoutCountryContinue');
  if (btn) btn.classList.toggle('hidden', !show);
  if (show) {
    setCountryContinueLoading(false);
  }
}

function toggleWaitingListInline(show) {
  const inline = document.getElementById('payoutWaitingListInline');
  if (!inline) return;
  inline.classList.toggle('hidden', !show);
  window.payoutWaitingListMode = !!show;
  if (typeof window.updatePaySubmitLabel === 'function') {
    window.updatePaySubmitLabel();
  }
  if (show && typeof window.initOnboardingSelect2 === 'function' && window.jQuery) {
    window.initOnboardingSelect2(window.jQuery(inline));
  }
}

async function submitPayoutWaitingList(countryName) {
  const errEl = document.getElementById('payout_waiting_list_country_error');
  if (errEl) {
    errEl.classList.add('hidden');
    errEl.textContent = '';
  }

  setPayoutCountrySaving(true);
  try {
    const res = await fetch(waitingListUrl, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
        Accept: 'application/json',
      },
      body: JSON.stringify({ payout_waiting_list_country: countryName }),
    });
    const data = await res.json();
    if (!res.ok || !data.success) {
      throw new Error(data.message || 'Could not join the waiting list.');
    }
    window.payoutBankCountrySelected = false;
    window.stripeOnboardingComplete = false;
    window.payoutWaitingListSaved = true;
    return data;
  } catch (err) {
    if (errEl) {
      errEl.textContent = err.message || 'Could not join the waiting list.';
      errEl.classList.remove('hidden');
    }
    throw err;
  } finally {
    setPayoutCountrySaving(false);
  }
}

async function savePayoutBankCountry(countryCode) {
  const errEl = document.getElementById('payout_bank_country_error');
  if (errEl) {
    errEl.classList.add('hidden');
    errEl.textContent = '';
  }

  setPayoutCountrySaving(true);
  setCountryContinueLoading(true);
  try {
    const res = await fetch(bankCountryUrl, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
        Accept: 'application/json',
      },
      body: JSON.stringify({ payout_bank_country: countryCode }),
    });
    const data = await res.json();
    if (!res.ok || !data.success) {
      throw new Error(data.message || (data.errors && data.errors.payout_bank_country && data.errors.payout_bank_country[0]) || 'Could not save bank country.');
    }
    window.payoutBankCountrySelected = true;
    window.savedPayoutBankCountry = countryCode;
    onboardingMounted = false;
    connectInstance = null;
    stripeSessionData = null;
    const nameEl = document.getElementById('payoutBankCountryName');
    const countryLabel = data.payout_bank_country_name || countryCode;
    if (nameEl && countryLabel) {
      nameEl.textContent = countryLabel;
    }
    updateStripeStepCopy(countryLabel);
    window.showPayoutStep('stripe');
    togglePayoutCountryContinue(false);
    window.payoutSupportedCountryPending = null;
    await mountStripeOnboarding();
    return data;
  } finally {
    setPayoutCountrySaving(false);
    setCountryContinueLoading(false);
  }
}

window.handlePayoutBankCountryChange = async function (value) {
  const select = document.getElementById('payout_bank_country');
  const errEl = document.getElementById('payout_bank_country_error');

  if (errEl) {
    errEl.classList.add('hidden');
    errEl.textContent = '';
  }

  if (!value) {
    toggleWaitingListInline(false);
    togglePayoutCountryContinue(false);
    window.payoutSupportedCountryPending = null;
    window.payoutWaitingListMode = false;
    if (typeof window.updatePaySubmitLabel === 'function') window.updatePaySubmitLabel();
    return;
  }

  if (value === '__not_listed__') {
    togglePayoutCountryContinue(false);
    window.payoutSupportedCountryPending = null;
    toggleWaitingListInline(true);
    return;
  }

  toggleWaitingListInline(false);
  window.payoutWaitingListMode = false;
  window.payoutSupportedCountryPending = value;
  togglePayoutCountryContinue(true);
  if (typeof window.updatePaySubmitLabel === 'function') window.updatePaySubmitLabel();
};

document.getElementById('payoutCountryContinue')?.addEventListener('click', async () => {
  const select = document.getElementById('payout_bank_country');
  const errEl = document.getElementById('payout_bank_country_error');
  const btn = document.getElementById('payoutCountryContinue');
  const countryCode = window.payoutSupportedCountryPending || select?.value;

  if (!countryCode || countryCode === '__not_listed__') {
    if (errEl) {
      errEl.textContent = 'Please select where your bank account is based.';
      errEl.classList.remove('hidden');
    }
    return;
  }

  if (btn) btn.disabled = true;
  setCountryContinueLoading(true);
  try {
    await savePayoutBankCountry(countryCode);
  } catch (err) {
    if (errEl) {
      errEl.textContent = err.message || 'Could not save bank country.';
      errEl.classList.remove('hidden');
    }
    if (select) select.value = '';
    togglePayoutCountryContinue(false);
    window.payoutSupportedCountryPending = null;
    resetCountryStepCopy();
  } finally {
    if (btn) btn.disabled = false;
    setCountryContinueLoading(false);
  }
});

window.handlePayoutWaitingListChange = function (value) {
  const errEl = document.getElementById('payout_waiting_list_country_error');
  if (errEl) {
    errEl.classList.add('hidden');
    errEl.textContent = '';
  }
  if (typeof window.clearOnboardingFieldError === 'function') {
    window.clearOnboardingFieldError('payout_waiting_list_country');
  }
};

window.artistStripeConnected = @json($artistStripeConnected);

if (!window.artistStripeConnected) {
  if (window.payoutBankCountrySelected) {
    window.showPayoutStep('stripe');
  } else {
    window.showPayoutStep('country');
  }
  window.mountStripeOnboardingIfNeeded();
}

window.submitPayoutWaitingList = submitPayoutWaitingList;
</script>
<script>
window.payoutOptionLocked = @json($payoutOptionLocked);
window.activePayoutKey = @json($payoutKey);
const artistStripeConnected = @json($artistStripeConnected);

function selectPayout(type, el) {
  if (window.payoutOptionLocked && type !== window.activePayoutKey) {
    if (typeof window.showOnboardingAlert === 'function') {
      window.showOnboardingAlert('payAlert', 'Disconnect your current payout setup in Payment Settings before switching between Artist and Studio.', 'warning');
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
  if (type === 'artist' && !artistStripeConnected && typeof window.mountStripeOnboardingIfNeeded === 'function') {
    window.mountStripeOnboardingIfNeeded();
  }
  if (typeof window.clearOnboardingFieldError === 'function') window.clearOnboardingFieldError('payment_type');
  if (typeof window.clearOnboardingFieldError === 'function') window.clearOnboardingFieldError('stripe_connect');
  if (typeof window.clearOnboardingAlert === 'function') window.clearOnboardingAlert('payAlert');
  if (typeof window.updatePaySubmitLabel === 'function') window.updatePaySubmitLabel();
}
$(function () {
  window.updatePaySubmitLabel = function () {
    var $btn = $('#paySubmit');
    if (!$btn.length) return;
    var isArtist = $('#payment_type').val() === 'artist_account';
    var waitlist = isArtist && (window.payoutWaitingListMode || window.payoutWaitingListSaved);
    $btn.text(waitlist ? ($btn.data('waitlist-label') || 'Notify me and complete onboarding') : ($btn.data('default-label') || 'Complete Onboarding'));
  };
  window.updatePaySubmitLabel();

  $('#payout_bank_country').on('change select2:select', function () {
    if (typeof window.handlePayoutBankCountryChange === 'function') {
      window.handlePayoutBankCountryChange($(this).val());
    }
  });
  $('#payout_waiting_list_country').on('change select2:select', function () {
    if (typeof window.handlePayoutWaitingListChange === 'function') {
      window.handlePayoutWaitingListChange($(this).val());
    }
  });

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

  function validateArtistStripeSetup() {
    return validateArtistStripeSetupAsync();
  }

  async function validateArtistStripeSetupAsync() {
    var paymentType = $('#payment_type').val();
    if (paymentType !== 'artist_account') return true;
    if (artistStripeConnected) return true;
    if (window.payoutWaitingListMode || window.payoutWaitingListSaved) {
      if (window.payoutWaitingListSaved) return true;
      var waitCountry = $('#payout_waiting_list_country').val();
      if (!waitCountry) {
        $('#payout_waiting_list_country_error').text('Please select your country.').removeClass('hidden');
        if (typeof window.scrollToFirstOnboardingError === 'function') {
          window.scrollToFirstOnboardingError(document.getElementById('paymentForm'));
        }
        return false;
      }
      return true;
    }
    if (window.payoutSupportedCountryPending && !window.payoutBankCountrySelected) {
      $('#payout_bank_country_error').text('Please click Continue to proceed with payout setup.').removeClass('hidden');
      if (typeof window.scrollToFirstOnboardingError === 'function') {
        window.scrollToFirstOnboardingError(document.getElementById('paymentForm'));
      }
      return false;
    }
    if (!@json($stripeConnectConfigured ?? false)) return true;
    if (!window.payoutBankCountrySelected) {
      $('#stripe_connect_error').text('Please select where your bank account is based before continuing.').removeClass('hidden');
      window.showPayoutStep('country');
      if (typeof window.scrollToFirstOnboardingError === 'function') {
        window.scrollToFirstOnboardingError(document.getElementById('paymentForm'));
      }
      return false;
    }
    if (typeof window.refreshStripeOnboardingStatus === 'function') {
      await window.refreshStripeOnboardingStatus();
    }
    if (!window.stripeOnboardingComplete) {
      $('#stripe_connect_error').text('Please complete Stripe payout setup before continuing.').removeClass('hidden');
      if (typeof window.scrollToFirstOnboardingError === 'function') {
        window.scrollToFirstOnboardingError(document.getElementById('paymentForm'));
      }
      return false;
    }
    return true;
  }

  async function submitPaymentForm(options) {
    options = options || {};
    var $alertEl = $('#payAlert');
    var $btn = $('#paySubmit');
    var $skip = $('#paySkip');
    var originalBtnHtml = $btn.html();
    $('#paymentForm').find('[id$="_error"]').addClass('hidden').text('');

    if (!options.auto) {
      if (!await validateArtistStripeSetupAsync()) return;
    }

    if ($('#payment_type').val() === 'artist_account' && window.payoutWaitingListMode && !window.payoutWaitingListSaved) {
      var waitCountry = $('#payout_waiting_list_country').val();
      if (!waitCountry) {
        $('#payout_waiting_list_country_error').text('Please select your country.').removeClass('hidden');
        return;
      }
      $btn.prop('disabled', true);
      $skip.prop('disabled', true);
      $btn.text('Submitting...');
      try {
        if (typeof window.submitPayoutWaitingList === 'function') {
          await window.submitPayoutWaitingList(waitCountry);
        }
      } catch (err) {
        $btn.prop('disabled', false);
        $skip.prop('disabled', false);
        window.updatePaySubmitLabel();
        return;
      }
    }

    $btn.prop('disabled', true);
    $skip.prop('disabled', true);
    $btn.text(options.auto ? 'Finishing onboarding…' : 'Submitting...');
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
          window.location.href = data.redirect;
          return;
        }
        if (data.errors) {
          showPaymentErrors(data.errors);
          $alertEl.addClass('hidden');
          if (options.auto && options.stripeExit && !options._retried) {
            setTimeout(function () {
              submitPaymentForm({ auto: true, stripeExit: true, _retried: true });
            }, 2000);
            return;
          }
          if (options.auto) {
            window.stripeAutoFinishTriggered = false;
          }
        } else {
          $alertEl.attr('class', 'rounded-xl px-4 py-3 text-sm mt-4 bg-red-50 text-red-800 border border-red-200');
          $alertEl.text(data.message || 'Could not complete').removeClass('hidden');
        }
      })
      .fail(function (xhr) {
        if (xhr.status === 422 && xhr.responseJSON && xhr.responseJSON.errors) {
          showPaymentErrors(xhr.responseJSON.errors);
          $alertEl.addClass('hidden');
          if (options.auto && options.stripeExit && !options._retried) {
            setTimeout(function () {
              submitPaymentForm({ auto: true, stripeExit: true, _retried: true });
            }, 2000);
            return;
          }
        } else {
          $alertEl.attr('class', 'rounded-xl px-4 py-3 text-sm mt-4 bg-red-50 text-red-800 border border-red-200');
          $alertEl.text('Network error').removeClass('hidden');
        }
        if (options.auto) {
          window.stripeAutoFinishTriggered = false;
        }
      })
      .always(function () {
        $btn.prop('disabled', false);
        $skip.prop('disabled', false);
        window.updatePaySubmitLabel();
        if (!options.auto) {
          window.stripeAutoFinishTriggered = false;
        }
      });
  }

  window.submitPaymentForm = submitPaymentForm;

  $('#paymentForm').on('submit', function (e) {
    e.preventDefault();
    submitPaymentForm();
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
        $submit.prop('disabled', false);
        $skip.html(originalSkipHtml);
      });
  });
});
</script>
@endpush
