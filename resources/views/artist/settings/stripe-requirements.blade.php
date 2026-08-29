@extends('layouts.artist_dashboard_layout')

@section('title', 'Stripe Requirements')

@section('content')
@php
  $stripeConnectLocale = $stripeConnectLocale ?? config('services.stripe.connect.locale', 'en-US');
@endphp
<main class="main-content flex-1 min-h-screen flex flex-col">
  <div class="flex-1 p-6 md:p-10 lg:p-12 max-w-5xl">
    @include('artist.partials.settings-tabs', ['activeTab' => 'payouts'])

    <div class="mb-6">
      <a href="{{ route('settings.payment') }}" class="inline-flex items-center gap-1.5 text-sm font-semibold text-primary hover:opacity-80">
        <span class="material-symbols-outlined text-base">arrow_back</span>
        Back to payout settings
      </a>
    </div>

    <div class="mb-8">
      <h2 class="text-3xl font-extrabold text-on-surface tracking-tight">Stripe required information</h2>
      <p class="text-on-surface-variant mt-1">Submit any details Stripe still needs for your connected payout account.</p>
    </div>

    <div class="bg-surface-container-low rounded-2xl p-6 bg-white space-y-6">
      <div class="max-w-2xl space-y-4 mb-2">
        <span class="inline-flex items-center gap-1.5 rounded-full bg-amber-50 text-amber-900 border border-amber-200/80 px-3 py-1 text-xs font-bold uppercase tracking-wide">Action needed</span>
        <p class="text-on-surface-variant text-sm">
          Complete the form below. When you’re done, you’ll return to payout settings.
        </p>
        @if ($payoutBankCountryName ?? null)
          <div>
            <p class="text-xs uppercase tracking-wider text-on-surface-variant font-medium">Bank account country</p>
            <p class="text-sm font-semibold text-on-surface mt-1">{{ $payoutBankCountryName }}</p>
          </div>
        @endif
      </div>

      @if (!($stripeConnectConfigured ?? false))
        <div class="rounded-xl border border-red-200 bg-red-50 text-red-800 px-4 py-3 text-sm">
          Stripe is not configured. Please contact support.
        </div>
      @else
        <div id="stripeRequirementsMount" class="min-h-[420px] rounded-xl p-1 bg-white overflow-hidden"></div>
        <p id="stripe_requirements_error" class="text-error text-xs mt-2 hidden"></p>
        <p id="stripeRequirementsHint" class="text-on-surface-variant text-xs mt-3">
          Finish every required Stripe step. You’ll be redirected when nothing else is due.
        </p>
      @endif
    </div>
  </div>
</main>
@endsection

@section('scripts')
@if ($stripeConnectConfigured ?? false)
<script type="module">
import { loadConnectAndInitialize } from 'https://esm.sh/@stripe/connect-js@3.3.34/pure';

const stripeConfigured = @json($stripeConnectConfigured ?? false);
const publishableKey = @json($stripePublishableKey ?? '');
const sessionUrl = @json(route('settings.payment.stripe.session'));
const statusUrl = @json(route('settings.payment.stripe.status'));
const requirementsUrl = @json(route('settings.payment.stripe.requirements'));
const payoutUrl = @json(route('settings.payment'));
const stripeConnectLocale = @json($stripeConnectLocale);
const stripeConnectAppearance = @json(config('services.stripe.connect.appearance', []));

let connectInstance = null;
let stripeSessionData = null;
let redirectInProgress = false;

function stillNeedsUserSubmission(status) {
  if (!status?.success) return true;
  if (status.disabled_reason) return true;
  return Array.isArray(status.currently_due) && status.currently_due.length > 0;
}

async function createStripeSession() {
  const res = await fetch(sessionUrl, {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json',
      'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
      Accept: 'application/json',
    },
    body: JSON.stringify({ requirements_only: true }),
  });
  const data = await res.json();
  if (!res.ok || !data.client_secret) {
    throw new Error(data.message || 'Could not start Stripe onboarding.');
  }
  stripeSessionData = data;
  return data;
}

async function refreshStripeStatus() {
  const params = new URLSearchParams();
  if (stripeSessionData?.account_id) params.set('account_id', stripeSessionData.account_id);
  const url = params.toString() ? `${statusUrl}?${params}` : statusUrl;
  const res = await fetch(url, { headers: { Accept: 'application/json' } });
  return res.json();
}

function goToPayoutSettings() {
  if (redirectInProgress) return;
  redirectInProgress = true;
  window.location.href = payoutUrl;
}

function reloadRequirementsPage() {
  if (redirectInProgress) return;
  redirectInProgress = true;
  window.location.href = requirementsUrl;
}

async function finishOrReloadRequirements() {
  await new Promise((resolve) => setTimeout(resolve, 1200));
  try {
    const status = await refreshStripeStatus();
    if (stillNeedsUserSubmission(status)) {
      redirectInProgress = false;
      reloadRequirementsPage();
      return;
    }
    goToPayoutSettings();
  } catch (err) {
    goToPayoutSettings();
  }
}

async function mountRequirementsOnboarding() {
  const container = document.getElementById('stripeRequirementsMount');
  if (!stripeConfigured || !publishableKey || !container) return;

  container.innerHTML = '<p class="text-sm text-on-surface-variant p-6">Loading Stripe form…</p>';

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
      fields: collectionOptions.fields || 'currently_due',
      futureRequirements: collectionOptions.futureRequirements || 'omit',
      ...(collectionOptions.requirements ? { requirements: collectionOptions.requirements } : {}),
    });
    accountOnboarding.setOnExit(finishOrReloadRequirements);
    accountOnboarding.setOnStepChange(() => {
      setTimeout(async () => {
        if (redirectInProgress) return;
        try {
          const status = await refreshStripeStatus();
          if (!stillNeedsUserSubmission(status)) {
            goToPayoutSettings();
          }
        } catch (err) {
          // Ignore transient status errors while typing.
        }
      }, 800);
    });

    container.innerHTML = '';
    container.appendChild(accountOnboarding);
  } catch (err) {
    container.innerHTML = '';
    const errEl = document.getElementById('stripe_requirements_error');
    if (errEl) {
      errEl.textContent = err.message || 'Could not load Stripe form.';
      errEl.classList.remove('hidden');
    }
  }
}

mountRequirementsOnboarding();
</script>
@endif
@endsection
