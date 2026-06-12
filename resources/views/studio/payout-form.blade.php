@extends('layouts.onboarding_bookpay', ['hideSidebar' => true])

@section('title', $studioAlreadyConnected ? 'Review payout request' : 'Studio Stripe payout setup')

@section('content')
<div class="flex-1 p-8 md:p-12 max-w-3xl w-full mx-auto">
  <div class="mb-8">
    <h2 class="text-3xl font-extrabold text-on-surface tracking-tight">
      {{ $studioAlreadyConnected ? 'Review payout request' : 'Connect for payouts' }}
    </h2>
    <p class="text-on-surface-variant mt-2 text-sm md:text-base">
      @if ($studioAlreadyConnected)
        <strong>{{ $artistName }}</strong> selected your studio to receive their booking payouts on {{ config('app.name', 'Inkjin') }}.
      @else
        <strong>{{ $studio->name }}</strong> — {{ $artistName }} selected your studio to receive their booking payouts on {{ config('app.name', 'Inkjin') }}.
      @endif
    </p>
  </div>

  @if (request()->query('completed'))
    <div class="rounded-xl border border-green-200 bg-green-50 text-green-900 px-4 py-4 text-sm mb-6">
      Stripe payout setup is complete. This artist can now receive payouts through your studio.
    </div>
  @endif

  @if ($studioAlreadyConnected)
    @php
      $profile = $studioProfile ?? ['name' => $studio->name, 'email' => $studio->email];
      $isApproved = ($paymentStatus ?? '') === 'approved';
      $isRejected = ($paymentStatus ?? '') === 'rejected';
    @endphp
    <div class="bg-surface-container-low rounded-2xl border border-outline-variant/20 p-6 md:p-8 space-y-6">
      <div>
        <p class="text-base font-semibold text-on-surface">Your studio information</p>
        <p class="text-on-surface-variant text-sm mt-1">
          Your studio already has a bank account connected for payouts. Review the details below, then approve or decline this artist.
        </p>
      </div>

      <dl class="grid gap-4 sm:grid-cols-2 text-sm">
        <div class="sm:col-span-2">
          <dt class="text-xs uppercase tracking-wider text-on-surface-variant font-medium">Studio name</dt>
          <dd class="text-on-surface font-semibold mt-1">{{ $profile['name'] ?? $studio->name }}</dd>
        </div>
        @if (!empty($profile['email']))
          <div>
            <dt class="text-xs uppercase tracking-wider text-on-surface-variant font-medium">Email</dt>
            <dd class="text-on-surface mt-1 break-all">{{ $profile['email'] }}</dd>
          </div>
        @endif
        @if (!empty($profile['business_type_label']))
          <div>
            <dt class="text-xs uppercase tracking-wider text-on-surface-variant font-medium">Account type</dt>
            <dd class="text-on-surface mt-1">{{ $profile['business_type_label'] }}</dd>
          </div>
        @endif
        @if (!empty($profile['country_name']) || !empty($profile['country']))
          <div>
            <dt class="text-xs uppercase tracking-wider text-on-surface-variant font-medium">Country</dt>
            <dd class="text-on-surface mt-1">{{ $profile['country_name'] ?? $profile['country'] }}</dd>
          </div>
        @endif
        @if (!empty($profile['address']))
          <div class="sm:col-span-2">
            <dt class="text-xs uppercase tracking-wider text-on-surface-variant font-medium">Address</dt>
            <dd class="text-on-surface mt-1">{{ $profile['address'] }}</dd>
          </div>
        @endif
      </dl>

      @if ($isApproved)
        <div class="rounded-xl border border-green-200 bg-green-50 text-green-900 px-4 py-4 text-sm">
          You have already approved this artist to receive payouts through your studio.
        </div>
      @elseif ($isRejected)
        <div class="rounded-xl border border-outline-variant/30 bg-surface-container-high text-on-surface-variant px-4 py-4 text-sm">
          You have already declined this payout request.
        </div>
      @else
        <div class="pt-2 border-t border-outline-variant/20 flex flex-wrap gap-3">
          <a href="{{ $approveUrl }}" class="inline-flex items-center justify-center gap-2 bg-gradient-to-br from-primary to-primary-container text-white font-bold py-3 px-6 rounded-xl shadow-lg shadow-primary/20 hover:opacity-90 transition-all text-sm">
            Approve artist
          </a>
          <a href="{{ $declineUrl }}" class="inline-flex items-center justify-center gap-2 font-semibold py-3 px-6 rounded-xl border border-error/40 text-error hover:bg-error-container/20 transition-colors text-sm">
            Decline
          </a>
        </div>
      @endif
    </div>
  @elseif (!($stripeConnectConfigured ?? false))
    <div class="rounded-xl border border-red-200 bg-red-50 text-red-800 px-4 py-3 text-sm">
      Stripe is not configured. Please contact support.
    </div>
  @else
    <div class="bg-surface-container-low rounded-2xl border border-outline-variant/20 p-6 md:p-8 space-y-6">
      <div id="studioSetupStep" class="space-y-5">
        <div>
          <p class="text-base font-semibold text-on-surface">Before we connect Stripe</p>
          <p class="text-on-surface-variant text-sm mt-1">Answer a few questions so we can set up the right payout account for you.</p>
        </div>

        <div class="space-y-3">
          <label for="studio_business_type" class="block text-sm font-semibold text-on-surface">Account type</label>
          <div class="rounded-xl border border-outline-variant/20 bg-white/60 px-4 py-3 text-sm text-on-surface-variant space-y-3">
            <p class="leading-relaxed">
              <span class="font-semibold text-on-surface">Individual</span> — Choose this option if you work under your own name, with no registered company. Payouts go to your personal bank account.
            </p>
            <p class="leading-relaxed">
              <span class="font-semibold text-on-surface">Business</span> — Choose this option if your studio is a registered business or legal entity. Payouts go to your business bank account.
            </p>
          </div>
          <select id="studio_business_type" name="business_type" class="select w-full text-sm border border-outline-variant/30 rounded-xl px-4 py-3 bg-white text-on-surface">
            <option value="" disabled selected>Select type</option>
            <option value="individual">Individual</option>
            <option value="company">Business</option>
          </select>
          <p id="studio_business_type_error" class="text-error text-xs hidden"></p>
        </div>

        <div class="space-y-2">
          <label for="studio_country" class="block text-sm font-semibold text-on-surface">Country</label>
          <select id="studio_country" name="country" class="select w-full text-sm border border-outline-variant/30 rounded-xl px-4 py-3 bg-white text-on-surface">
            <option value="" disabled selected>Select country</option>
            @foreach ($stripeSupportedCountries as $country)
              <option value="{{ $country['code'] }}">{{ $country['name'] }}</option>
            @endforeach
          </select>
          <p id="studio_country_error" class="text-error text-xs hidden"></p>
        </div>

        <div class="space-y-2">
          <label for="studio_industry" class="block text-sm font-semibold text-on-surface">What best describes you?</label>
          <select id="studio_industry" name="industry" class="select w-full text-sm border border-outline-variant/30 rounded-xl px-4 py-3 bg-white text-on-surface">
            <option value="" disabled selected>Select option</option>
            <option value="tattoo_studio">Tattoo studio — We do tattoos and body art</option>
            <option value="tattoo_beauty">Tattoo &amp; beauty studio — We also offer beauty, piercing, or barber services</option>
            <option value="other">Other — Something else</option>
          </select>
          <p id="studio_industry_error" class="text-error text-xs hidden"></p>
        </div>

        <button type="button" id="studioSetupContinue" class="inline-flex items-center justify-center gap-2 bg-gradient-to-br from-primary to-primary-container text-white font-bold py-3 px-8 rounded-xl shadow-lg shadow-primary/20 hover:opacity-90 transition-all text-sm">
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
          Onboarding will finish automatically when all required steps are complete.
        </p>
      </div>
    </div>
  @endif
</div>
@endsection

@if (! $studioAlreadyConnected && ($stripeConnectConfigured ?? false))
@push('scripts')
<script type="module">
import { loadConnectAndInitialize } from 'https://esm.sh/@stripe/connect-js@3.3.34/pure';

const publishableKey = @json($stripePublishableKey ?? '');
const sessionUrl = @json($stripeSessionUrl);
const completeUrl = @json($stripeCompleteUrl);
const stripeConnectLocale = @json($stripeConnectLocale ?? 'en-US');
const stripeConnectAppearance = @json(config('services.stripe.connect.appearance', []));

let connectInstance = null;
let stripeSessionData = null;
let onboardingMounted = false;
let completeTriggered = false;
let studioSetup = null;

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

  studioSetup = setup;
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
