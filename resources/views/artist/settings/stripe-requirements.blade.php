@extends('layouts.new-artist-dashboard-layout')

@section('title', 'Money · Stripe requirements')

@php
  $activeNav = 'money';
  $stripeConnectLocale = $stripeConnectLocale ?? config('services.stripe.connect.locale', 'en-US');
@endphp

@section('styles')
<style>
  .req-page .back{
    display:inline-flex;align-items:center;gap:6px;font-size:13.5px;font-weight:600;
    color:var(--pri);margin-bottom:18px
  }
  .req-page .back .ms{font-size:18px}
  .req-page .back:hover{opacity:.8}
  .req-page .card{
    background:var(--card);border:1px solid var(--line);border-radius:16px;padding:20px 22px
  }
  .req-page .pill{
    display:inline-flex;align-items:center;gap:6px;border-radius:999px;padding:5px 12px;
    font-size:11px;font-weight:800;letter-spacing:.04em;text-transform:uppercase;
    background:var(--amberl);color:var(--amber);border:1px solid #F0D9A8;margin-bottom:12px
  }
  .req-page .pill .ms{font-size:16px;font-variation-settings:'FILL' 1}
  .req-page .help{font-size:13px;color:var(--muted);line-height:1.55;margin:0}
  .req-page .err{color:var(--red);font-size:13px;margin-top:10px}
  .req-page .err.hidden{display:none!important}
  .req-page .hint{font-size:12.5px;color:var(--faint);margin-top:12px;line-height:1.5}
  .req-page #stripeRequirementsMount{
    min-height:420px;width:100%;max-width:100%;margin-top:16px;
    background:#fff;border-radius:12px;overflow:hidden
  }
  .req-page #stripeRequirementsMount > *{
    display:block;width:100%!important;max-width:100%!important;min-width:0!important;box-sizing:border-box
  }
  .req-page #stripeRequirementsMount iframe{width:100%!important;max-width:100%!important}
  .req-page .note{
    display:flex;gap:10px;align-items:flex-start;background:var(--amberl);border:1px solid #F0D9A8;
    border-radius:14px;padding:12px 14px;color:#7A4E00;font-size:13.5px;line-height:1.5;margin-bottom:14px
  }
  .req-page .note .ms{color:var(--amber);font-size:22px;font-variation-settings:'FILL' 1;flex-shrink:0}
</style>
@endsection

@section('content')
<div class="req-page">
  <div class="head">
    <div>
      <h1>
        Money
        <a class="help-q" href="https://help.inkjin.com/en/articles/17201212-dashboard-money-payouts" target="_blank" rel="noopener" title="Help with this page" aria-label="Help with this page">
          <span class="ms">help</span>
        </a>
      </h1>
      <div class="sub">What you've earned, how clients pay you, and where your payouts go.</div>
    </div>
  </div>

  @include('artist.partials.money-tabs', ['moneyTab' => 'payouts'])

  <a href="{{ route('settings.payment') }}" class="back">
    <span class="ms">arrow_back</span>
    Back to payouts
  </a>

  <div class="note">
    <span class="ms">priority_high</span>
    <div>
      <b style="display:block;margin-bottom:2px">Stripe needs more information</b>
      Submit the details below for your connected payout account. When you’re done, you’ll return to payouts.
      @if (!empty($payoutBankCountryName))
        <div style="margin-top:4px;font-size:12.5px;opacity:.9">Bank country: {{ $payoutBankCountryName }}</div>
      @endif
    </div>
  </div>

  <div class="card">
    <span class="pill"><span class="ms">assignment</span>Action needed</span>
    <p class="help">Complete every required Stripe step. This page redirects automatically when nothing else is due.</p>

    @if (!($stripeConnectConfigured ?? false))
      <p class="err" style="display:block">Stripe is not configured. Please contact support.</p>
    @else
      <div id="stripeRequirementsMount"></div>
      <p id="stripe_requirements_error" class="err hidden"></p>
      <p id="stripeRequirementsHint" class="hint">
        Finish every required Stripe step. You’ll be redirected when nothing else is due.
      </p>
    @endif
  </div>
</div>
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

  container.innerHTML = '<p class="help" style="padding:24px 8px">Loading Stripe form…</p>';

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
