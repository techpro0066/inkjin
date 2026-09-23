@extends('layouts.onboarding_bookpay', ['hideSidebar' => true])

@section('title', 'Studio invitation')

@php
  $isApproved = ($paymentStatus ?? '') === 'approved';
  $isRejected = ($paymentStatus ?? '') === 'rejected';
  $isPending = ! $isApproved && ! $isRejected;
  $artistPercent = (int) ($studioRevenueArtistPercent ?? 50);
  $studioPercent = (int) ($studioRevenueStudioPercent ?? (100 - $artistPercent));
@endphp

@section('content')
<div class="flex-1 p-6 md:p-10 max-w-xl w-full mx-auto">
  @if (request()->query('completed') || $isApproved)
    <div class="rounded-2xl border border-green-200 bg-green-50 text-green-900 px-5 py-4 text-sm mb-6">
      {{ request()->query('completed') ? 'Stripe payout setup is complete. This artist can now receive payouts through your studio.' : 'You have already approved this artist to receive payouts through your studio.' }}
    </div>
  @endif

  @if ($isRejected)
    <div class="rounded-2xl border border-outline-variant/30 bg-surface-container-high text-on-surface-variant px-5 py-4 text-sm mb-6">
      You have already declined this payout request.
    </div>
  @endif

  <div id="studioDecisionPanel" class="bg-white rounded-2xl border border-outline-variant/25 shadow-sm p-6 md:p-8 space-y-6 {{ (request()->query('completed') && ! $isPending) ? 'hidden' : '' }}">
    <div>
      <h2 class="text-2xl md:text-3xl font-extrabold text-on-surface tracking-tight">Studio invitation</h2>
      <p class="text-on-surface-variant text-sm md:text-[15px] leading-relaxed mt-3">
        You’ve been invited for payouts by <span class="font-semibold text-on-surface">{{ $artistName }}</span>. Review the artist details and studio information, then accept to complete the payment setup, so you can get paid automatically for each booking.
      </p>
    </div>

    <div class="rounded-2xl border border-outline-variant/25 bg-surface-container-low/40 p-4 md:p-5">
      <div class="flex items-start gap-4">
        <div class="w-16 h-16 rounded-full bg-surface-container-high border border-outline-variant/30 overflow-hidden shrink-0 flex items-center justify-center text-on-surface-variant font-bold text-lg">
          @if (!empty($artistAvatarUrl))
            <img src="{{ $artistAvatarUrl }}" alt="{{ $artistName }}" class="w-full h-full object-cover">
          @else
            {{ $artistInitials ?? 'AR' }}
          @endif
        </div>
        <div class="min-w-0 space-y-1.5">
          <p class="text-base font-bold text-on-surface">{{ $artistName }}</p>
          @if (!empty($artistLocation))
            <p class="text-sm text-on-surface-variant flex items-start gap-1.5">
              <span aria-hidden="true">📍</span>
              <span>{{ $artistLocation }}</span>
            </p>
          @endif
          @if (!empty($artistTattooingSince))
            <p class="text-sm text-on-surface-variant flex items-start gap-1.5">
              <span aria-hidden="true">🗓️</span>
              <span>Tattooing since {{ $artistTattooingSince }}</span>
            </p>
          @endif
          @if (!empty($artistPrimaryStyle))
            <p class="text-sm text-on-surface-variant flex items-start gap-1.5">
              <span aria-hidden="true">🎨</span>
              <span>{{ $artistPrimaryStyle }}</span>
            </p>
          @endif
        </div>
      </div>
    </div>

    <div class="rounded-2xl border border-outline-variant/25 px-4 py-4 md:px-5 md:py-5">
      <p class="text-[11px] font-semibold uppercase tracking-wider text-on-surface-variant">Revenue split</p>
      <div class="mt-3 grid grid-cols-2 gap-4">
        <div>
          <p class="text-sm text-on-surface-variant">Artist keeps</p>
          <p class="text-2xl font-extrabold text-on-surface mt-1 tabular-nums">{{ $artistPercent }}%</p>
        </div>
        <div>
          <p class="text-sm text-on-surface-variant">Studio keeps</p>
          <p class="text-2xl font-extrabold text-on-surface mt-1 tabular-nums">{{ $studioPercent }}%</p>
        </div>
      </div>
    </div>

    @if (!empty($studioRelationshipLabel))
      <div class="rounded-2xl border border-outline-variant/25 px-4 py-4 md:px-5 md:py-5">
        <p class="text-[11px] font-semibold uppercase tracking-wider text-on-surface-variant">Relationship type</p>
        <p class="text-base font-semibold text-on-surface mt-2">{{ $studioRelationshipLabel }}</p>
      </div>
    @endif

    @if (!empty($studioNameValue))
      <div class="rounded-2xl border border-outline-variant/25 px-4 py-4 md:px-5 md:py-5">
        <p class="text-[11px] font-semibold uppercase tracking-wider text-on-surface-variant">Studio name</p>
        <p class="text-base font-semibold text-on-surface mt-2">{{ $studioNameValue }}</p>
      </div>
    @endif

    @if ($isPending)
      <div class="flex flex-col sm:flex-row gap-3 pt-1">
        @if ($studioAlreadyConnected)
          <a href="{{ $approveUrl }}" class="flex-1 inline-flex items-center justify-center bg-on-surface text-white font-bold py-3.5 px-6 rounded-xl hover:opacity-90 transition-opacity text-sm">
            Accept invitation
          </a>
        @else
          <button type="button" id="studioApproveStartBtn" class="flex-1 inline-flex items-center justify-center bg-on-surface text-white font-bold py-3.5 px-6 rounded-xl hover:opacity-90 transition-opacity text-sm">
            Accept invitation
          </button>
        @endif
        <a href="{{ $declineUrl }}" class="flex-1 inline-flex items-center justify-center font-semibold py-3.5 px-6 rounded-xl border border-outline-variant/50 text-on-surface bg-white hover:bg-surface-container-high transition-colors text-sm">
          Decline
        </a>
      </div>
    @endif
  </div>

  @if (! $studioAlreadyConnected && ($stripeConnectConfigured ?? false) && $isPending)
    <div id="studioConnectPanel" class="hidden mt-6 bg-white rounded-2xl border border-outline-variant/25 shadow-sm p-6 md:p-8 space-y-6">
      <div id="studioSetupStep" class="space-y-5">
        <div>
          <p class="text-base font-semibold text-on-surface">Connect your studio bank account</p>
          <p class="text-on-surface-variant text-sm mt-1">Answer a few questions so we can set up the right payout account. Completing Stripe will accept this invitation.</p>
        </div>

        <div class="space-y-3">
          <label for="studio_business_type" class="block text-sm font-semibold text-on-surface">Account type</label>
          <div class="rounded-xl border border-outline-variant/20 bg-surface-container-low/50 px-4 py-3 text-sm text-on-surface-variant space-y-3">
            <p class="leading-relaxed">
              <span class="font-semibold text-on-surface">Individual</span> — Choose this if you work under your own name, with no registered company.
            </p>
            <p class="leading-relaxed">
              <span class="font-semibold text-on-surface">Business</span> — Choose this if your studio is a registered business or legal entity.
            </p>
          </div>
          <select id="studio_business_type" name="business_type" class="select w-full text-sm border border-outline-variant/40 rounded-xl px-4 py-3 bg-white text-on-surface">
            <option value="" disabled selected>Select type</option>
            <option value="individual">Individual</option>
            <option value="company">Business</option>
          </select>
          <p id="studio_business_type_error" class="text-error text-xs hidden"></p>
        </div>

        <div class="space-y-2">
          <label for="studio_country" class="block text-sm font-semibold text-on-surface">Country</label>
          <select id="studio_country" name="country" class="select w-full text-sm border border-outline-variant/40 rounded-xl px-4 py-3 bg-white text-on-surface">
            <option value="" disabled selected>Select country</option>
            @foreach ($stripeSupportedCountries as $country)
              <option value="{{ $country['code'] }}">{{ $country['name'] }}</option>
            @endforeach
          </select>
          <p id="studio_country_error" class="text-error text-xs hidden"></p>
        </div>

        <div class="space-y-2">
          <label for="studio_industry" class="block text-sm font-semibold text-on-surface">What best describes you?</label>
          <select id="studio_industry" name="industry" class="select w-full text-sm border border-outline-variant/40 rounded-xl px-4 py-3 bg-white text-on-surface">
            <option value="" disabled selected>Select option</option>
            <option value="tattoo_studio">Tattoo studio — We do tattoos and body art</option>
            <option value="tattoo_beauty">Tattoo &amp; beauty studio — We also offer beauty, piercing, or barber services</option>
            <option value="other">Other — Something else</option>
          </select>
          <p id="studio_industry_error" class="text-error text-xs hidden"></p>
        </div>

        <button type="button" id="studioSetupContinue" class="inline-flex items-center justify-center gap-2 bg-on-surface text-white font-bold py-3 px-8 rounded-xl hover:opacity-90 transition-opacity text-sm">
          Continue
          <span class="material-symbols-outlined text-lg">arrow_forward</span>
        </button>
      </div>

      <div id="studioStripeStep" class="hidden space-y-4">
        <div class="pb-4 border-b border-outline-variant/20">
          <p class="text-base font-semibold text-on-surface">Complete your Stripe payout setup</p>
          <p id="studioStripeStepDescription" class="text-on-surface-variant text-sm mt-2">
            Add your details, verify your identity, and connect your bank account below.
          </p>
        </div>
        <div id="studioStripeConnectMount" class="min-h-[420px] bg-white overflow-hidden p-2"></div>
        <p id="studio_stripe_connect_error" class="text-error text-xs hidden"></p>
        <p id="studioStripeConnectHint" class="text-on-surface-variant text-xs">
          Setup finishes automatically when all required steps are complete.
        </p>
      </div>
    </div>
  @elseif (! $studioAlreadyConnected && !($stripeConnectConfigured ?? false) && $isPending)
    <div class="rounded-xl border border-red-200 bg-red-50 text-red-800 px-4 py-3 text-sm mt-6">
      Stripe is not configured. Please contact support.
    </div>
  @endif
</div>
@endsection

@if ($isPending && ! $studioAlreadyConnected)
@push('scripts')
<script>
document.getElementById('studioApproveStartBtn')?.addEventListener('click', function () {
  document.getElementById('studioDecisionPanel')?.classList.add('hidden');
  document.getElementById('studioConnectPanel')?.classList.remove('hidden');
  document.getElementById('studioConnectPanel')?.scrollIntoView({ behavior: 'smooth', block: 'start' });
});
</script>
@endpush
@endif

@if (! $studioAlreadyConnected && ($stripeConnectConfigured ?? false) && $isPending)
@push('scripts')
<script type="module">
import { loadConnectAndInitialize } from 'https://esm.sh/@stripe/connect-js@3.3.34/pure';

const publishableKey = @json($stripePublishableKey ?? '');
const sessionUrl = @json($stripeSessionUrl);
const completeUrl = @json($stripeCompleteUrl);
const stripeConnectLocale = @json($stripeConnectLocale ?? 'en-US');
const stripeConnectAppearance = @json(config('services.stripe.connect.appearance', []));
const studioName = @json($studioNameValue ?? '');

let connectInstance = null;
let stripeSessionData = null;
let onboardingMounted = false;
let completeTriggered = false;

function clearStudioSetupErrors() {
  ['studio_business_type_error', 'studio_country_error', 'studio_industry_error', 'studio_stripe_connect_error'].forEach((id) => {
    const el = document.getElementById(id);
    if (el) {
      el.classList.add('hidden');
      el.textContent = '';
    }
  });
}

function showStudioSetupError(field, message) {
  const el = document.getElementById(`studio_${field}_error`);
  if (el) {
    el.textContent = message;
    el.classList.remove('hidden');
  }
}

function readStudioSetup() {
  return {
    business_type: document.getElementById('studio_business_type')?.value || '',
    country: document.getElementById('studio_country')?.value || '',
    industry: document.getElementById('studio_industry')?.value || '',
    studio_name: studioName || '',
  };
}

function validateStudioSetup() {
  clearStudioSetupErrors();
  const setup = readStudioSetup();
  let valid = true;

  if (!setup.business_type) {
    showStudioSetupError('business_type', 'Please select an account type.');
    valid = false;
  }
  if (!setup.country) {
    showStudioSetupError('country', 'Please select your country.');
    valid = false;
  }
  if (!setup.industry) {
    showStudioSetupError('industry', 'Please select what best describes you.');
    valid = false;
  }

  return valid ? setup : null;
}

function updateStripeStepDescription(setup) {
  const desc = document.getElementById('studioStripeStepDescription');
  if (!desc || !setup) return;

  const typeLabel = setup.business_type === 'individual' ? 'individual' : 'business';
  const countrySelect = document.getElementById('studio_country');
  const countryName = countrySelect?.selectedOptions?.[0]?.text || setup.country;
  desc.textContent = `Complete Stripe onboarding for your ${typeLabel} account in ${countryName}. You will verify your identity and connect your bank account.`;
}

async function createStudioStripeSession(setup) {
  const res = await fetch(sessionUrl, {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json',
      'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
      Accept: 'application/json',
    },
    body: JSON.stringify(setup),
  });
  const data = await res.json();

  if (res.status === 422 && data.errors) {
    const errors = data.errors;
    if (errors.business_type?.[0]) showStudioSetupError('business_type', errors.business_type[0]);
    if (errors.country?.[0]) showStudioSetupError('country', errors.country[0]);
    if (errors.industry?.[0]) showStudioSetupError('industry', errors.industry[0]);
    throw new Error(data.message || 'Please check your answers and try again.');
  }

  if (!res.ok || !data.client_secret) {
    throw new Error(data.message || 'Could not start Stripe onboarding.');
  }

  stripeSessionData = data;
  return data;
}

async function finalizeStudioOnboarding() {
  if (completeTriggered || !stripeSessionData?.account_id) {
    return;
  }
  completeTriggered = true;

  const res = await fetch(completeUrl, {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json',
      'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
      Accept: 'application/json',
    },
    body: JSON.stringify({ account_id: stripeSessionData.account_id }),
  });
  const data = await res.json();
  if (!res.ok || !data.success) {
    completeTriggered = false;
    throw new Error(data.message || 'Could not save Stripe payout setup.');
  }

  window.location.href = data.redirect || window.location.href;
}

async function mountStudioStripeOnboarding(setup) {
  const container = document.getElementById('studioStripeConnectMount');
  if (!publishableKey || !container) {
    return;
  }

  if (onboardingMounted) {
    container.innerHTML = '';
    onboardingMounted = false;
    connectInstance = null;
    stripeSessionData = null;
    completeTriggered = false;
  }

  container.innerHTML = '<p class="text-sm text-on-surface-variant p-6">Loading Stripe onboarding…</p>';

  try {
    await createStudioStripeSession(setup);

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
      document.getElementById('studioStripeConnectHint')?.classList.add('hidden');
      await new Promise((resolve) => setTimeout(resolve, 1500));
      try {
        await finalizeStudioOnboarding();
      } catch (err) {
        completeTriggered = false;
        const errEl = document.getElementById('studio_stripe_connect_error');
        if (errEl) {
          errEl.textContent = err.message || 'Could not complete payout setup.';
          errEl.classList.remove('hidden');
        }
      }
    });

    container.innerHTML = '';
    container.appendChild(accountOnboarding);
    onboardingMounted = true;
  } catch (err) {
    container.innerHTML = '';
    const errEl = document.getElementById('studio_stripe_connect_error');
    if (errEl) {
      errEl.textContent = err.message || 'Could not load Stripe onboarding.';
      errEl.classList.remove('hidden');
    }
    document.getElementById('studioSetupStep')?.classList.remove('hidden');
    document.getElementById('studioStripeStep')?.classList.add('hidden');
  }
}

document.getElementById('studioSetupContinue')?.addEventListener('click', async () => {
  const setup = validateStudioSetup();
  if (!setup) return;

  updateStripeStepDescription(setup);

  const btn = document.getElementById('studioSetupContinue');
  const originalHtml = btn?.innerHTML;
  if (btn) {
    btn.disabled = true;
    btn.innerHTML = 'Loading Stripe…';
  }

  document.getElementById('studioSetupStep')?.classList.add('hidden');
  document.getElementById('studioStripeStep')?.classList.remove('hidden');

  try {
    await mountStudioStripeOnboarding(setup);
  } finally {
    if (btn) {
      btn.disabled = false;
      btn.innerHTML = originalHtml;
    }
  }
});
</script>
@endpush
@endif
