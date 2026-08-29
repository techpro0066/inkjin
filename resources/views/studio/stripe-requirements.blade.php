@extends('layouts.onboarding_bookpay', ['hideSidebar' => true])

@section('title', 'Studio Stripe requirements')

@section('content')
@php
  $stripeConnectLocale = $stripeConnectLocale ?? config('services.stripe.connect.locale', 'en-US');
@endphp
<div class="flex-1 p-8 md:p-12 max-w-3xl w-full mx-auto">
  <div class="mb-8">
    <h2 class="text-3xl font-extrabold text-on-surface tracking-tight">Complete required Stripe information</h2>
    <p class="text-on-surface-variant mt-2 text-sm md:text-base">
      <strong>{{ $studio->name }}</strong> — additional details are needed so <strong>{{ $artistName }}</strong> can continue receiving payouts through your studio on {{ config('app.name', 'Inkjin') }}.
    </p>
  </div>

  <div class="bg-surface-container-low rounded-2xl border border-outline-variant/20 p-6 md:p-8 space-y-6">
    <div>
      <span class="inline-flex items-center gap-1.5 rounded-full bg-amber-50 text-amber-900 border border-amber-200/80 px-3 py-1 text-xs font-bold uppercase tracking-wide">Action needed</span>
      <p class="text-on-surface-variant text-sm mt-3">
        Complete the form below. When Stripe has everything it needs, you’ll see a confirmation screen.
      </p>
    </div>

    @if (!($stripeConnectConfigured ?? false))
      <div class="rounded-xl border border-red-200 bg-red-50 text-red-800 px-4 py-3 text-sm">
        Stripe is not configured. Please contact support.
      </div>
    @else
      <div id="stripeRequirementsMount" class="min-h-[420px] rounded-xl p-1 bg-white overflow-hidden"></div>
      <p id="stripe_requirements_error" class="text-error text-xs mt-2 hidden"></p>
      <p class="text-on-surface-variant text-xs mt-3">
        Finish every required Stripe step. This page updates automatically when you are done.
      </p>
    @endif
  </div>
</div>
@endsection

@push('scripts')
@if ($stripeConnectConfigured ?? false)
<script type="module">
import { loadConnectAndInitialize } from 'https://esm.sh/@stripe/connect-js@3.3.34/pure';

const stripeConfigured = @json($stripeConnectConfigured ?? false);
const publishableKey = @json($stripePublishableKey ?? '');
const sessionUrl = @json($stripeSessionUrl);
const statusUrl = @json($stripeStatusUrl);
const requirementsUrl = @json($requirementsUrl);
const stripeConnectLocale = @json($stripeConnectLocale);
const stripeConnectAppearance = @json(config('services.stripe.connect.appearance', []));
const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';

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
      Accept: 'application/json',
      ...(csrfToken ? { 'X-CSRF-TOKEN': csrfToken } : {}),
    },
    body: JSON.stringify({}),
  });
  const data = await res.json().catch(() => ({}));
  if (!res.ok || !data.client_secret) {
    throw new Error(data.message || 'Could not start Stripe form.');
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

function reloadRequirementsPage() {
  if (redirectInProgress) return;
  redirectInProgress = true;
  window.location.href = requirementsUrl;
}

async function finishOrReloadRequirements() {
  await new Promise((resolve) => setTimeout(resolve, 1200));
  reloadRequirementsPage();
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
            reloadRequirementsPage();
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
@endpush
