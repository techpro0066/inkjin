@extends('layouts.artist-onboarding-layout')

@section('title', 'Payouts — Artist onboarding')

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
  $studioDraftWithStripe = $artistStripeConnected
    && ($ud->payment_type ?? null) === 'studio_account'
    && ! $studioPayoutCommitted;
  $stripeConnectLocale = $stripeConnectLocale ?? config('services.stripe.connect.locale', 'en-US');
  $payoutBankCountry = $payoutBankCountry ?? $ud->payout_bank_country ?? null;
  $payoutWaitingListCountry = $payoutWaitingListCountry ?? $ud->payout_waiting_list_country ?? null;
  $payoutRegistrationCountries = $payoutRegistrationCountries ?? [];
  $payoutBankCountryName = $payoutBankCountryName ?? ($payoutBankCountry ? \App\Support\StripeConnectCountries::nameFor($payoutBankCountry) : null);
  $hasSignupPayoutCountry = $payoutBankCountry
    && \App\Support\StripeConnectCountries::isSupported($payoutBankCountry);
  $studioRevenueArtistPercent = (int) old('studio_revenue_artist_percent', $ud->studio_revenue_artist_percent ?? 50);
  $studioRevenueArtistPercent = max(0, min(100, $studioRevenueArtistPercent));
  $studioDisplayName = $ud->studio->name ?? $ud->studio_name ?? null;
@endphp

@push('styles')
<style>
  .hidden{display:none!important}
  .field-err{color:#C62828;font-size:12px;margin-top:6px}
  .field-err.hidden{display:none!important}
  input.in.is-err,.in.is-err{border-color:#C62828}
  button.btn,a.btn{cursor:pointer;font:inherit;text-decoration:none}
  button.btn:not(.ghost),a.btn:not(.ghost){border:0}
  button.btn:disabled{opacity:.6;cursor:not-allowed}
  .po.locked-out{opacity:.55;cursor:not-allowed}
  .pay-alert{margin-top:12px;padding:12px 14px;border-radius:12px;font-size:13.5px;font-weight:500}
  .pay-alert.err{background:#FDECEC;color:#9B1C1C;border:1px solid #F5C2C2}
  .pay-alert.warn{background:#FFF3DE;color:#8A5A00;border:1px solid #F3DDB0}
  .pay-alert.ok{background:#E6F8EE;color:#0B6B3A;border:1px solid #B6E6C8}
  .pay-alert.hidden{display:none!important}
  .status-pill{display:inline-flex;align-items:center;gap:6px;border-radius:20px;font-size:11.5px;font-weight:700;padding:4px 10px;text-transform:uppercase;letter-spacing:.4px}
  .status-pill.k{background:var(--chip);color:var(--muted)}
  .status-pill.a{background:var(--amberl);color:var(--amber)}
  .status-pill.g{background:var(--greenl);color:var(--green)}
  .ok-box{border:1px solid #B6E6C8;background:#E6F8EE;color:#0B6B3A;border-radius:12px;padding:14px 16px;font-size:13.5px}
  .warn-box{border:1px solid #F3DDB0;background:#FFF3DE;color:#8A5A00;border-radius:12px;padding:14px 16px;font-size:13.5px;max-width:480px;margin-bottom:14px}
  .err-box{border:1px solid #F5C2C2;background:#FDECEC;color:#9B1C1C;border-radius:12px;padding:14px 16px;font-size:13.5px}
  .modal-ov{display:none;position:fixed;inset:0;z-index:200;background:rgba(0,0,0,.5);align-items:center;justify-content:center;padding:16px}
  .modal-ov.open{display:flex}
  .modal-box{background:#fff;border-radius:16px;max-width:420px;width:100%;padding:22px;border:1px solid var(--line)}
  #payoutArtistBankSection .card{
    overflow:visible;
  }
  #payoutArtistBankSection .card > div[style*="padding"]{
    min-width:0;
  }
  .stripe-connect-shell{
    width:100%;
    max-width:100%;
    min-width:0;
    margin:0 -10px;
    padding:4px 10px 8px;
    overflow-x:auto;
    overflow-y:visible;
    -webkit-overflow-scrolling:touch;
  }
  #stripeConnectMount{
    min-height:420px;
    width:100%;
    max-width:100%;
    min-width:0;
    background:#fff;
    overflow:visible;
    box-sizing:border-box;
  }
  #stripeConnectMount > *{
    display:block;
    width:100%!important;
    max-width:100%!important;
    min-width:0!important;
    box-sizing:border-box;
  }
  #payoutStripeStep{
    width:100%;
    max-width:100%;
    min-width:0;
  }
  @media (max-width:700px){
    .studio-invite-grid{grid-template-columns:1fr!important}
  }
</style>
@endpush

@section('content')
<form id="paymentForm">
  @csrf
  <input type="hidden" name="payment_type" id="payment_type" value="{{ $pt }}" />

  <div class="wrap">
    <h1 style="margin-top:6px">How you get paid<a class="help-q" href="https://help.inkjin.com/en/articles/17200746-setup-step-6-payouts" target="_blank" rel="noopener" data-help-article="O6-payouts" title="Help with this page" aria-label="Help with this page"><span class="ms">help</span></a></h1>
    <div class="sub" style="max-width:640px;margin-bottom:24px">Connect Stripe to receive payments. Payouts are automatic.</div>

    <div id="payoutOptionLockBanner" class="lockbar" style="margin-bottom:14px;{{ $payoutOptionLocked ? '' : 'display:none' }}">
      <span class="ms" style="font-size:17px">info</span>
      Your payout option is locked after setup is saved. Disconnect your current setup below before switching between Artist and Studio.
    </div>

    <div class="card" style="margin-bottom:14px">
      <div class="ch">
        <div>
          <h3>How you get paid</h3>
          <div class="faint" style="font-size:12.5px;margin-top:2px">
            @if ($studioDisplayName)
              Choose how bookings at {{ $studioDisplayName }} are paid.
            @else
              Choose how bookings are paid.
            @endif
        </div>
      </div>
        </div>
      <div style="padding:18px 22px">
        <label class="po {{ $payoutKey === 'artist' ? 'sel' : '' }}{{ $payoutOptionLocked && $payoutKey !== 'artist' ? ' locked-out' : '' }}" id="card-artist">
          <input type="radio" name="po" value="artist" {{ $payoutKey === 'artist' ? 'checked' : '' }} {{ $payoutOptionLocked && $payoutKey !== 'artist' ? 'disabled' : '' }}>
          <div style="flex:1">
            <b>Artist - Direct payment</b>
            <div class="d">The full payment goes to your own Stripe account. Nothing is sent to the studio. Choose this option if you're renting a workstation. You settle rent or fees with them outside Bookpay.</div>
      </div>
        </label>

        <label class="po {{ $payoutKey === 'studio' ? 'sel' : '' }}{{ $payoutOptionLocked && $payoutKey !== 'studio' ? ' locked-out' : '' }}" id="card-studio" style="margin-bottom:0">
          <input type="radio" name="po" value="studio" {{ $payoutKey === 'studio' ? 'checked' : '' }} {{ $payoutOptionLocked && $payoutKey !== 'studio' ? 'disabled' : '' }}>
          <div style="flex:1">
            <b>Invite the studio and set a revenue split</b>
            <div class="d">Your studio will get an invite to join Bookpay. They will need to set up their Stripe account. Once they join and confirm, each payment is split between you.</div>
    </div>
        </label>

        <div class="splitbox" id="splitbox" @if($payoutKey !== 'studio') hidden @endif>
          <div id="payout-studio">
            <div id="studioPayoutEmailNotSent" class="{{ $studioPayoutStatus !== 'email_not_sent' ? 'hidden' : '' }}">
              <div class="row" style="gap:12px;align-items:flex-start">
                <div class="stepn">1</div>
                <div style="flex:1">
                  <b style="font-size:14px">Invite the studio</b>
                  <div class="grid studio-invite-grid" style="grid-template-columns:1fr 1fr;gap:10px;margin-top:8px">
                    <div>
                      <span class="fl">Studio name</span>
                      <input class="in" type="text" value="{{ $studioDisplayName }}" placeholder="Studio name" readonly tabindex="-1" style="background:var(--tint);color:var(--muted)">
          </div>
                    <div>
                      <label class="fl" for="studio_email">Email</label>
                      <input class="in" type="email" id="studio_email" name="studio_email" value="{{ $studioEmail }}" placeholder="studio@example.com" autocomplete="email">
            </div>
                  </div>
                  <div class="help">They get an email to join Bookpay and set up their Stripe account. The invite is sent when you finish setup.</div>
                </div>
            </div>

              <div style="margin-top:16px">
                @include('onboarding.partials.studio-split-relationship-new', [
                  'fieldSuffix' => '',
                  'percentId' => 'studio_revenue_you_get',
                  'showSaveButton' => false,
                  'showStepNumbers' => true,
                  'splitStep' => 2,
                  'hintId' => 'studio_revenue_you_get_hint',
                  'hintText' => $studioDisplayName
                    ? "You're paid directly until {$studioDisplayName} joins Bookpay and confirms this split. Then it starts, for new bookings."
                    : "You're paid directly until your studio joins Bookpay and confirms this split. Then it starts, for new bookings.",
                  'studioRevenueArtistPercent' => $studioRevenueArtistPercent,
                ])
              </div>

              <div style="margin-top:16px">
                <button type="button" id="payStudioSend" class="btn pri">Save &amp; Send</button>
              </div>
            </div>

            <div id="studioPayoutNotConnected" class="{{ $studioPayoutStatus !== 'not_connected' ? 'hidden' : '' }}">
              <span class="status-pill a" style="margin-bottom:12px">Not connected</span>
              <p class="muted" style="font-size:13.5px;line-height:1.5;margin:0 0 14px">Your studio hasn't connected a bank account yet. Payouts are on hold until they do.</p>
              <div class="row" style="gap:12px;align-items:flex-start">
                <div class="stepn">1</div>
                <div style="flex:1">
                  <b style="font-size:14px">Studio email</b>
                  <div class="row" style="gap:8px;flex-wrap:wrap;align-items:stretch;margin-top:8px">
                    <input class="in" style="flex:1;min-width:200px" type="email" id="studio_email_not_connected" value="{{ $studioEmail }}" placeholder="studio@example.com" autocomplete="email" readonly>
                    <button type="button" id="editStudioEmailBtn" class="btn ghost sm"><span class="ms">edit</span> Edit</button>
                    <button type="button" id="cancelStudioEmailEditBtn" class="btn ghost sm hidden">Cancel</button>
                  </div>
                </div>
              </div>
              <div style="margin-top:14px">
                <button type="button" id="payStudioReminder" class="btn pri">Send a reminder to your studio</button>
              </div>
              <div style="margin-top:16px">
                @include('onboarding.partials.studio-split-relationship-new', [
                  'fieldSuffix' => '_nc',
                  'percentId' => 'studio_revenue_you_get_nc',
                  'saveBtnId' => 'saveStudioSplitReminder',
                  'showSaveButton' => true,
                  'showStepNumbers' => true,
                  'splitStep' => 2,
                  'hintId' => null,
                  'hintText' => 'Enter 0 if the studio collects the entire payment for your bookings.',
                  'studioRevenueArtistPercent' => $studioRevenueArtistPercent,
                ])
                </div>
              </div>

            <div id="studioPayoutConnected" class="{{ $studioPayoutStatus !== 'connected' ? 'hidden' : '' }}">
              <span class="status-pill g" style="margin-bottom:12px">Connected</span>
              <p class="muted" style="font-size:13.5px;line-height:1.5;margin:0">Your studio's bank account is connected{{ $artistStripeConnected ? ' and your Stripe account is linked' : '' }}. You're ready to receive payments.</p>
              @if ($studioEmail)
                <div style="margin-top:12px">
                  <div class="lbl">Studio email</div>
                  <div style="font-weight:600;margin-top:4px">{{ $studioEmail }}</div>
                </div>
              @endif
            </div>

            @if ($studioPayoutCommitted)
              <div id="studioPayoutDisconnectWrap" style="padding-top:16px;margin-top:14px;border-top:1px solid #E4D6F5">
                <button type="button" id="disconnectStudioBtn" class="btn ghost" style="color:var(--red);border-color:#F5C2C2">Disconnect studio payout</button>
                <p class="help">Cancel this studio request. You stay on Studio and can invite again or switch to Artist anytime.</p>
            </div>
          @endif

            <p id="studio_email_error" class="field-err hidden" role="alert"></p>
            <p id="studio_revenue_artist_percent_error" class="field-err hidden" role="alert"></p>
      </div>
    </div>
          </div>
        </div>

    <div id="payoutArtistBankSection">
      <div class="card" style="margin-bottom:14px">
        <div class="ch">
          <div>
            <h3>Payment account</h3>
            <div class="faint" style="font-size:12.5px;margin-top:2px" id="payoutArtistBankSubtitle">Every artist connects their own Stripe account</div>
            </div>
          </div>
        <div style="padding:18px 22px">
          @if ($artistStripeConnected)
            <div style="display:flex;flex-direction:column;gap:14px">
              <div class="ok-box">
                <p style="font-weight:700;display:flex;align-items:center;gap:8px;margin-bottom:6px">
                  <span class="ms" style="font-variation-settings:'FILL' 1">check_circle</span>
                  Stripe account connected
                </p>
                <p style="margin:0">Your bank account is connected through Stripe.</p>
                @if ($payoutBankCountry && ($payoutBankCountryName ?? null))
                  <p style="margin:8px 0 0">Bank account country: <strong>{{ $payoutBankCountryName }}</strong></p>
                @endif
        </div>
            <div>
                <button type="button" id="disconnectStripeBtn" class="btn ghost" style="color:var(--red);border-color:#F5C2C2">Disconnect Stripe</button>
                <p class="help">Disconnect to reconnect a different account or switch payout options.</p>
              </div>
            </div>
          @elseif ($payoutWaitingListCountry)
            <div class="ok-box" style="max-width:480px">
              <p style="font-weight:700;margin-bottom:4px">Your country isn't supported yet</p>
              <p style="margin:0">We'll notify you at <strong>{{ auth()->user()->email }}</strong> when payouts become available.</p>
            </div>
          @else
            @if (! $hasSignupPayoutCountry)
              <div class="warn-box">
                <p style="font-weight:700;margin-bottom:4px">Payout country missing</p>
                <p style="margin:0">We need the country you chose at signup to set up Stripe payouts. Please contact support if this looks wrong.</p>
            </div>
          @endif
            <div id="payoutConnectIntroStep">
              <div class="row" style="gap:14px;align-items:center;flex-wrap:wrap">
                <div class="ic"><span class="ms">credit_card</span></div>
                <div style="flex:1;min-width:200px">
                  <b>Connect with Stripe</b>
                  <div class="faint" style="font-size:12.5px;margin-top:2px">Required to get paid. Payments go through Stripe straight to your bank account.</div>
                </div>
                <button type="button" id="connectBankAccountBtn" class="btn"><span class="ms">link</span>Connect</button>
              </div>
        </div>

            <div id="payoutStripeStep" class="hidden">
              <div style="margin-bottom:14px;padding-bottom:14px;border-bottom:1px solid var(--line)">
                <p id="payoutStripeStepTitle" style="font-size:15px;font-weight:700;margin:0">Complete your payout details</p>
                <p id="payoutStripeStepDescription" class="muted" style="font-size:13.5px;margin-top:6px">
                  Add your personal details, upload an identity document (passport or ID).
                </p>
          </div>

              @if (!($stripeConnectConfigured ?? false))
                <div class="err-box">
                  Stripe is not configured. You can skip this step for now and finish payout setup later in settings.
      </div>
              @else
                <div class="stripe-connect-shell">
                  <div id="stripeConnectMount"></div>
    </div>
                <p id="stripe_connect_error" class="field-err hidden" role="alert"></p>
                <p id="stripeConnectHint" class="help {{ $stripeComplete ? 'hidden' : '' }}">
                  You'll be guided through personal details, identity document upload, and bank account setup.
                </p>
              @endif
            </div>
          @endif
        </div>
      </div>
  </div>

    <p id="payment_type_error" class="field-err hidden" role="alert"></p>
    <div id="payAlert" class="pay-alert err hidden" role="alert"></div>
    <p id="paySkipHint" class="help" style="max-width:640px;margin-top:8px">You can finish this step now, or come back later — payouts must be set up before you can collect deposits and confirm bookings.</p>

    <div class="obfoot">
      <a href="{{ route('onboarding.calendar') }}" class="btn ghost"><span class="ms">arrow_back</span>Back</a>
      <div class="btns">
        <button type="button" id="payGoDashboard" class="btn pri hidden">Go to dashboard</button>
        <button type="button" id="paySkip" class="btn ghost">Set up later</button>
      </div>
    </div>
  </div>
</form>

<div id="disconnectStripeModal" class="modal-ov" role="dialog" aria-modal="true">
  <div class="modal-box">
    <h5 style="font-size:17px;font-weight:800;margin-bottom:8px">Disconnect Stripe payouts?</h5>
    <p class="muted" style="font-size:13.5px;margin-bottom:18px">You will need to complete Stripe setup again if you want direct artist payouts later.</p>
    <div class="row" style="justify-content:flex-end;gap:10px">
      <button type="button" id="cancelDisconnectStripe" class="btn ghost">Cancel</button>
      <button type="button" id="confirmDisconnectStripeBtn" class="btn" style="background:var(--red)">Disconnect</button>
    </div>
  </div>
</div>

<div id="disconnectStudioModal" class="modal-ov" role="dialog" aria-modal="true">
  <div class="modal-box">
    <h5 style="font-size:17px;font-weight:800;margin-bottom:8px">Disconnect studio payout?</h5>
    <p class="muted" style="font-size:13.5px;margin-bottom:18px">This cancels the request to your studio. You stay on Studio payout and can invite again or switch to Artist anytime.</p>
    <div class="row" style="justify-content:flex-end;gap:10px">
      <button type="button" id="cancelDisconnectStudio" class="btn ghost">Cancel</button>
      <button type="button" id="confirmDisconnectStudioBtn" class="btn" style="background:var(--red)">Disconnect</button>
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
const MAX_AUTO_FINISH_ATTEMPTS = 8;
window.PAYMENT_SAVE_MAX_RETRIES = 4;

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
  if (banner) banner.style.display = '';

  const artistCard = document.getElementById('card-artist');
  const studioCard = document.getElementById('card-studio');
  [artistCard, studioCard].forEach(function (card) {
    if (!card) return;
    const key = card.id === 'card-artist' ? 'artist' : 'studio';
    const lockedOut = key !== activeKey;
    card.classList.toggle('locked-out', lockedOut);
    const input = card.querySelector('input[type="radio"]');
    if (input) input.disabled = lockedOut;
  });
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
  autoFinishAttempts = 0;
}

window.resetAutoFinishFlags = resetAutoFinishFlags;

async function maybeFinalizeOnboardingStripe() {
  if (autoFinishInProgress) {
    return;
  }

  const status = await refreshStripeStatus();
  if (!isStripeDetailsSubmitted(status)) {
    return;
  }

  window.setStripeOnboardingComplete(true);
  window.artistStripeConnected = true;

  const paymentType = document.getElementById('payment_type')?.value;
  if (paymentType === 'studio_account') {
    window.studioDraftWithStripe = true;
    stopStripeStatusPolling();
    window.updatePaymentSkipUi(currentPayoutStep);
    const bankSection = document.getElementById('payoutArtistBankSection');
    if (bankSection && !document.getElementById('studioArtistStripeConnectedBanner')) {
      window.location.reload();
    }
    return;
  }

  window.lockPayoutOptions('artist');
  await tryAutoFinishOnboarding();
}

async function createStripeSession() {
  const paymentType = document.getElementById('payment_type')?.value || 'artist_account';
  const res = await fetch(sessionUrl, {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json',
      'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
      Accept: 'application/json',
    },
    body: JSON.stringify({ payment_type: paymentType }),
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

    if (onboardingMounted && !stripeStatusPollTimer) {
      startStripeStatusPolling();
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
  container.innerHTML = '<p class="muted" style="padding:24px;font-size:13.5px">Loading Stripe onboarding…</p>';

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
    requestAnimationFrame(function () {
      window.dispatchEvent(new Event('resize'));
    });
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
  const bank = document.getElementById('payoutArtistBankSection');
  const bankVisible = !!(bank && bank.offsetParent !== null);
  if (bankVisible && stripeConfigured && window.payoutBankCountrySelected) {
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
  const inConnectFlow = !window.artistStripeConnected
    && !window.stripeOnboardingComplete
    && currentPayoutStep === 'stripe';
  const skipHint = document.getElementById('paySkipHint');
  const skipBtn = document.getElementById('paySkip');
  const goDashboardBtn = document.getElementById('payGoDashboard');

  const unfinishedStudio = paymentType === 'studio_account' && !window.studioPayoutCommitted;
  const showGoDashboard = paymentType === 'artist_account'
    && stripeReady
    && !!window.studioDraftWithStripe;

  if (goDashboardBtn) goDashboardBtn.classList.toggle('hidden', !showGoDashboard);

  const hideSkip = showGoDashboard
    || (!unfinishedStudio && (inConnectFlow || (paymentType === 'artist_account' && stripeReady)));

  if (skipHint) skipHint.classList.toggle('hidden', hideSkip);
  if (skipBtn) skipBtn.classList.toggle('hidden', hideSkip);
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
window.studioDraftWithStripe = @json($studioDraftWithStripe);
window.studioPayoutCommitted = @json($studioPayoutCommitted);

window.placePayoutBankSection = function (type) {
  const subtitle = document.getElementById('payoutArtistBankSubtitle');
  if (subtitle) {
    subtitle.textContent = type === 'studio'
      ? 'Connect your Stripe account so you can receive payouts alongside your studio.'
      : 'Every artist connects their own Stripe account';
  }
};

if (!window.artistStripeConnected && !initialWaitingListCountry) {
  window.showPayoutStep('intro');
} else {
  window.updatePaymentSkipUi('intro');
}
window.placePayoutBankSection(@json($payoutKey));
</script>
<script>
window.payoutOptionLocked = @json($payoutOptionLocked);
window.activePayoutKey = @json($payoutKey);
const artistStripeConnected = @json($artistStripeConnected);

function alertClass(type) {
  if (type === 'ok' || type === 'success') return 'pay-alert ok';
  if (type === 'warning' || type === 'warn') return 'pay-alert warn';
  return 'pay-alert err';
}

function selectPayout(type) {
  if (window.payoutOptionLocked && type !== window.activePayoutKey) {
    showPaymentAlert('Disconnect your current payout setup below before switching between Artist and Studio.', 'warning');
    $('input[name="po"][value="' + window.activePayoutKey + '"]').prop('checked', true);
    return;
  }

  $('#card-artist, #card-studio').removeClass('sel');
  $('#card-' + type).addClass('sel');
  $('input[name="po"][value="' + type + '"]').prop('checked', true);

  var map = { artist: 'artist_account', studio: 'studio_account' };
  $('#payment_type').val(map[type]);
  window.activePayoutKey = type;

  var splitbox = document.getElementById('splitbox');
  if (splitbox) splitbox.hidden = type !== 'studio';

  if (typeof window.placePayoutBankSection === 'function') {
    window.placePayoutBankSection(type);
  }
  if (!artistStripeConnected && typeof window.showPayoutStep === 'function') {
    window.showPayoutStep('intro');
  } else if (typeof window.updatePaymentSkipUi === 'function') {
    window.updatePaymentSkipUi('intro');
  }
  if (typeof window.clearOnboardingFieldError === 'function') window.clearOnboardingFieldError('payment_type');
  if (typeof window.clearOnboardingFieldError === 'function') window.clearOnboardingFieldError('stripe_connect');
  $('#payAlert').addClass('hidden').text('');
}

$(function () {
  $('#card-artist, #card-studio').on('click', function (e) {
    if ($(this).hasClass('locked-out')) {
      e.preventDefault();
      selectPayout(window.activePayoutKey);
      return;
    }
    var type = this.id === 'card-artist' ? 'artist' : 'studio';
    selectPayout(type);
  });

  $('#studio_email').on('input', function () {
    if (typeof window.clearOnboardingFieldError === 'function') window.clearOnboardingFieldError('studio_email');
  });

  function syncStudioRevenueSplit($input) {
    var digits = String($input.val() || '').replace(/\D/g, '').slice(0, 3);
    if (digits !== String($input.val() || '')) {
      $input.val(digits);
    }
    if (digits === '') {
      $input.closest('.studio-revenue-split').find('.js-studio-revenue-studio-gets').text('100');
      return;
    }
    var you = parseInt(digits, 10);
    if (!Number.isFinite(you)) {
      you = 0;
    }
    if (you > 100) {
      you = 100;
      $input.val('100');
    }
    var studio = 100 - you;
    $input.closest('.studio-revenue-split').find('.js-studio-revenue-studio-gets').text(String(studio));
  }

  $(document).on('input', '.js-studio-revenue-you-get', function () {
    syncStudioRevenueSplit($(this));
  });
  $(document).on('blur', '.js-studio-revenue-you-get', function () {
    var $input = $(this);
    if (String($input.val() || '').trim() === '') {
      $input.val('0');
    }
    syncStudioRevenueSplit($input);
  });
  $('.js-studio-revenue-you-get').each(function () {
    syncStudioRevenueSplit($(this));
  });

  function syncStudioPayoutSectionInputs() {
    var $notSent = $('#studioPayoutEmailNotSent');
    var $notConnected = $('#studioPayoutNotConnected');
    if (!$notSent.length && !$notConnected.length) return;
    var notSentActive = $notSent.length && !$notSent.hasClass('hidden');
    $notSent.find('input, select, textarea').prop('disabled', !notSentActive);
    $notConnected.find('input, select, textarea').prop('disabled', notSentActive);
  }
  syncStudioPayoutSectionInputs();

  function collectStudioSplitPayload($panel) {
    var $splitInput = $panel.find('.js-studio-revenue-you-get').first();
    if ($splitInput.length && String($splitInput.val() || '').trim() === '') {
      $splitInput.val('0');
      syncStudioRevenueSplit($splitInput);
    }
    return {
      _token: @json(csrf_token()),
      save_studio_split: 1,
      studio_revenue_artist_percent: ($splitInput.val() || '').trim(),
    };
  }

  $(document).on('click', '.js-save-studio-split', function () {
    var $btn = $(this);
    var $panel = $btn.closest('.studio-split-relationship-panel');
    var $alertEl = $('#payAlert');
    $('#studio_email_error, #studio_revenue_artist_percent_error').addClass('hidden').text('');
    $alertEl.addClass('hidden').text('');
    var originalHtml = $btn.html();
    $btn.prop('disabled', true).text('Saving...');
    $.ajax({
      url: @json(route('onboarding.payment.save')),
      type: 'POST',
      data: collectStudioSplitPayload($panel),
      headers: { 'X-CSRF-TOKEN': @json(csrf_token()), Accept: 'application/json' },
    })
      .done(function (data) {
        if (data.success) {
          $alertEl.attr('class', alertClass('ok')).text(data.message || 'Saved.').removeClass('hidden');
          return;
        }
        $alertEl.attr('class', alertClass('error')).text(data.message || 'Could not save.').removeClass('hidden');
      })
      .fail(function (xhr) {
        if (xhr.status === 422 && xhr.responseJSON && xhr.responseJSON.errors) {
          showPaymentErrors(xhr.responseJSON.errors);
          return;
        }
        $alertEl.attr('class', alertClass('error')).text((xhr.responseJSON && xhr.responseJSON.message) || 'Could not save.').removeClass('hidden');
      })
      .always(function () {
        $btn.prop('disabled', false).html(originalHtml);
      });
  });

  function showPaymentAlert(message, type) {
    type = type || 'error';
    var $alertEl = $('#payAlert');
    $alertEl.attr('class', alertClass(type)).text(message).removeClass('hidden');
    if ($alertEl[0] && typeof $alertEl[0].scrollIntoView === 'function') {
      $alertEl[0].scrollIntoView({ behavior: 'smooth', block: 'nearest' });
    }
  }

  function showPaymentErrors(errors) {
    var firstMessage = null;
    $.each(errors, function (k, messages) {
      if (!firstMessage && messages && messages[0]) {
        firstMessage = messages[0];
      }
      var errId = k === 'stripe_connect' ? 'stripe_connect_error' : (k + '_error');
      var $el = $('#' + errId);
      if ($el.length) $el.text(messages[0]).removeClass('hidden');
    });
    if (firstMessage) {
      showPaymentAlert(firstMessage, 'error');
    }
    if (typeof window.scrollToFirstOnboardingError === 'function') {
      window.scrollToFirstOnboardingError(document.getElementById('paymentForm'));
    }
  }

  function shouldRetryPaymentSave(attemptOptions, errors) {
    var retryCount = attemptOptions._retryCount || 0;
    var maxRetries = window.PAYMENT_SAVE_MAX_RETRIES || 4;
    if (retryCount >= maxRetries) {
      return false;
    }
    if (!errors || !errors.stripe_connect) {
      return false;
    }
    return !!attemptOptions.auto;
  }

  function schedulePaymentSaveRetry(attemptOptions, resolve, reject, runAjax) {
    var nextOptions = $.extend({}, attemptOptions, {
      _retryCount: (attemptOptions._retryCount || 0) + 1,
    });
    var delay = 1500 * nextOptions._retryCount;

    setTimeout(function () {
      var refresh = (typeof window.refreshStripeOnboardingStatus === 'function')
        ? window.refreshStripeOnboardingStatus()
        : Promise.resolve();

      refresh.finally(function () {
        runAjax(nextOptions).then(resolve).catch(reject);
      });
    }, delay);
  }

  async function validateArtistStripeSetupAsync() {
    var paymentType = $('#payment_type').val();
    if (paymentType !== 'artist_account' && paymentType !== 'studio_account') return true;
    if (artistStripeConnected || window.stripeOnboardingComplete) return true;
    if (!@json($stripeConnectConfigured ?? false)) return true;
    if (!window.payoutBankCountrySelected) {
      var countryMessage = 'Please connect your bank account and complete payout setup before continuing.';
      $('#stripe_connect_error').text(countryMessage).removeClass('hidden');
      showPaymentAlert(countryMessage, 'error');
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
      var stripeMessage = paymentType === 'studio_account'
        ? 'Please connect your bank account before inviting your studio.'
        : 'Please complete Stripe payout setup before continuing.';
      $('#stripe_connect_error').text(stripeMessage).removeClass('hidden');
      showPaymentAlert(stripeMessage, 'error');
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
    var $goDashboard = $('#payGoDashboard');
    var $studioSend = $('#payStudioSend');
    var originalStudioSendHtml = $studioSend.length ? $studioSend.html() : '';
    var originalGoDashboardHtml = $goDashboard.length ? $goDashboard.html() : '';
    $('#paymentForm').find('[id$="_error"]').addClass('hidden').text('');

    return (async function () {
    if (!options.auto) {
        if (typeof window.refreshStripeOnboardingStatus === 'function') {
          await window.refreshStripeOnboardingStatus();
        }
        if (!await validateArtistStripeSetupAsync()) {
          throw new Error('Please complete Stripe payout setup before continuing.');
        }
      }

      $alertEl.addClass('hidden').text('');

    $skip.prop('disabled', true);
      if ($goDashboard.length) {
        $goDashboard.prop('disabled', true);
        if (!$goDashboard.hasClass('hidden')) {
          $goDashboard.text(options.auto ? 'Finishing onboarding…' : 'Saving...');
        }
      }
    if ($studioSend.length) {
      $studioSend.prop('disabled', true);
        $studioSend.text(options.auto ? 'Finishing onboarding…' : 'Saving...');
    }

      const runAjax = function (attemptOptions) {
        return new Promise(function (resolve, reject) {
    var fd = new FormData(document.getElementById('paymentForm'));
          if (($('#payment_type').val() || '') === 'studio_account') {
            var $sendPanel = $('#studioPayoutEmailNotSent').find('.studio-split-relationship-panel').first();
            if ($sendPanel.length) {
              var splitPayload = collectStudioSplitPayload($sendPanel);
              fd.set('studio_revenue_artist_percent', splitPayload.studio_revenue_artist_percent);
            }
          }
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
                if (shouldRetryPaymentSave(attemptOptions, data.errors)) {
                  schedulePaymentSaveRetry(attemptOptions, resolve, reject, runAjax);
            return;
          }
                reject(new Error((data.errors.stripe_connect && data.errors.stripe_connect[0]) || data.message || 'Could not complete'));
                return;
              }

              $alertEl.attr('class', alertClass('error')).text(data.message || 'Could not complete').removeClass('hidden');
              reject(new Error(data.message || 'Could not complete'));
      })
      .fail(function (xhr) {
        if (xhr.status === 422 && xhr.responseJSON && xhr.responseJSON.errors) {
          showPaymentErrors(xhr.responseJSON.errors);
                if (shouldRetryPaymentSave(attemptOptions, xhr.responseJSON.errors)) {
                  schedulePaymentSaveRetry(attemptOptions, resolve, reject, runAjax);
            return;
          }
                const firstError = Object.values(xhr.responseJSON.errors || {})[0];
                reject(new Error((firstError && firstError[0]) || xhr.responseJSON.message || 'Could not complete'));
                return;
              }

              const msg = (xhr.responseJSON && xhr.responseJSON.message) || 'Network error';
              $alertEl.attr('class', alertClass('error')).text(msg).removeClass('hidden');
              reject(new Error(msg));
      })
      .always(function () {
        $skip.prop('disabled', false);
              if ($goDashboard.length) {
                $goDashboard.prop('disabled', false).html(originalGoDashboardHtml);
              }
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

  var paymentTypeOnLoad = ($('#payment_type').val() || '');
  var canAutoFinishArtist = (artistStripeConnected || window.stripeOnboardingComplete)
    && paymentTypeOnLoad === 'artist_account'
    && !window.studioDraftWithStripe;

  if (artistStripeConnected || window.stripeOnboardingComplete) {
    if (typeof window.updatePaymentSkipUi === 'function') {
      window.updatePaymentSkipUi('intro');
    }
  }

  if (canAutoFinishArtist) {
    setTimeout(function () {
      if (typeof window.tryAutoFinishOnboarding === 'function') {
        window.tryAutoFinishOnboarding().catch(function () {});
      }
    }, 400);
  }

  $('#payGoDashboard').on('click', function () {
    $('#payment_type').val('artist_account');
    submitPaymentForm().catch(function () {});
  });

  $('#payStudioSend').on('click', function () {
    $('#payment_type').val('studio_account');
    var email = ($('#studio_email').val() || '').trim();
    if (!email) {
      $('#studio_email_error').text('Studio email is required.').removeClass('hidden');
      return;
    }
    $('#studio_email_error, #studio_revenue_artist_percent_error').addClass('hidden').text('');
    var $splitInput = $('#studio_revenue_you_get');
    if ($splitInput.length && String($splitInput.val() || '').trim() === '') {
      $splitInput.val('0');
      syncStudioRevenueSplit($splitInput);
    }
    if (typeof syncStudioPayoutSectionInputs === 'function') {
      syncStudioPayoutSectionInputs();
    }
    submitPaymentForm().catch(function () {});
  });

  function openStripeDisconnectModal() { $('#disconnectStripeModal').addClass('open'); }
  function closeStripeDisconnectModal() { $('#disconnectStripeModal').removeClass('open'); }
  $('#disconnectStripeBtn').on('click', openStripeDisconnectModal);
  $('#cancelDisconnectStripe').on('click', closeStripeDisconnectModal);
  $('#disconnectStripeModal').on('click', function (e) { if (e.target === this) closeStripeDisconnectModal(); });
  $('#confirmDisconnectStripeBtn').on('click', function () {
    var $alertEl = $('#payAlert');
    closeStripeDisconnectModal();
    $alertEl.addClass('hidden').text('');
    $.ajax({
      url: @json(route('onboarding.payment.save')),
      type: 'POST',
      data: { _token: @json(csrf_token()), disconnect_stripe: 1 },
      headers: { 'X-CSRF-TOKEN': @json(csrf_token()), Accept: 'application/json' },
    })
      .done(function (data) {
        if (data.success) {
          window.location.reload();
          return;
        }
        $alertEl.attr('class', alertClass('error')).text(data.message || 'Could not disconnect Stripe.').removeClass('hidden');
      })
      .fail(function (xhr) {
        $alertEl.attr('class', alertClass('error')).text((xhr.responseJSON && xhr.responseJSON.message) || 'Could not disconnect Stripe.').removeClass('hidden');
      });
  });

  function openStudioDisconnectModal() { $('#disconnectStudioModal').addClass('open'); }
  function closeStudioDisconnectModal() { $('#disconnectStudioModal').removeClass('open'); }
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
        $alertEl.attr('class', alertClass('error')).text(data.message || 'Could not disconnect studio payout.').removeClass('hidden');
      })
      .fail(function (xhr) {
        $alertEl.attr('class', alertClass('error')).text((xhr.responseJSON && xhr.responseJSON.message) || 'Could not disconnect studio payout.').removeClass('hidden');
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
    $('#studio_email_error, #studio_revenue_artist_percent_error').addClass('hidden').text('');
    $alertEl.addClass('hidden').text('');
    var originalLabel = $btn.html();
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
          $alertEl.attr('class', alertClass('ok')).text(data.message || 'Reminder sent to your studio.').removeClass('hidden');
          return;
        }
        $alertEl.attr('class', alertClass('error')).text(data.message || 'Could not send reminder.').removeClass('hidden');
      })
      .fail(function (xhr) {
        if (xhr.status === 422 && xhr.responseJSON && xhr.responseJSON.errors) {
          if (typeof showPaymentErrors === 'function') {
            showPaymentErrors(xhr.responseJSON.errors);
          } else {
            $.each(xhr.responseJSON.errors, function (k, msgs) {
              $('#' + k + '_error').text(msgs[0]).removeClass('hidden');
            });
          }
          return;
        }
        $alertEl.attr('class', alertClass('error')).text((xhr.responseJSON && xhr.responseJSON.message) || 'Could not send reminder.').removeClass('hidden');
      })
      .always(function () {
        $btn.prop('disabled', false).html(originalLabel);
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
      $btn.html('<span class="ms">send</span> Update & send email');
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
        $alertEl.attr('class', alertClass('error')).text(data.message || 'Could not skip').removeClass('hidden');
      })
      .fail(function (xhr) {
        var msg = (xhr.responseJSON && xhr.responseJSON.message) || 'Network error';
        $alertEl.attr('class', alertClass('error')).text(msg).removeClass('hidden');
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
