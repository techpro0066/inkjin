@extends('layouts.artist_dashboard_layout')

@section('title', 'Payment Settings')

@section('styles')
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet">
<style>
  .payout-card { border: 1.5px solid #cac4d3; border-radius: 16px; padding: 32px; cursor: pointer; transition: all 0.2s; background: white; position: relative; }
  .payout-card.selected { border-color: #310f7a; border-width: 2px; }
  .payout-card .radio-indicator { width: 20px; height: 20px; border-radius: 50%; border: 2px solid #cac4d3; position: absolute; top: 20px; right: 20px; transition: all 0.2s; display: flex; align-items: center; justify-content: center; }
  .payout-card.selected .radio-indicator { border-color: #310f7a; background: #310f7a; }
  .payout-card.selected .radio-indicator::after { content: ''; width: 6px; height: 6px; background: white; border-radius: 50%; }
  .payout-card.locked-other { opacity: 0.55; cursor: not-allowed; }
  .payout-card.locked-other:hover { border-color: #cac4d3; }
  .select2-container { width: 100% !important; z-index: 1; }
  .select2-container--open { z-index: 10060 !important; }
  .select2-container--default .select2-selection--single {
    min-height: 48px;
    padding: 6px 12px;
    border-radius: 0.75rem;
    border: 1px solid rgba(202,196,211,0.5) !important;
    background: #fff !important;
  }
  .select2-container--default .select2-selection--single .select2-selection__rendered {
    line-height: 2.25rem;
    padding-left: 4px;
    color: #1c1b21;
  }
  .select2-container--default .select2-selection--single .select2-selection__arrow { height: 46px; }
  .select2-container--default.select2-container--focus .select2-selection--single,
  .select2-container--default.select2-container--open .select2-selection--single {
    border-color: #310f7a !important;
    box-shadow: 0 0 0 2px rgba(49,15,122,0.25);
  }
  .select2-dropdown { border-radius: 0.75rem; border-color: rgba(202,196,211,0.5); overflow: hidden; }
  .select2-container--default .select2-results__option--highlighted[aria-selected] { background-color: #310f7a !important; }
  @media (max-width: 1023px) {
    .main-content { overflow-x: hidden; padding: 16px; padding-top: 70px; }
    body { overflow-x: hidden; }
  }
</style>
@endsection

@section('content')
{{-- @php
  $currentPaymentType = $userDetail->payment_type ?? '';
  $artistStripeId = ($currentPaymentType === 'artist_account') ? ($userDetail->stripe_account_id ?? '') : '';
  $studioEmail = old('studio_email', $userDetail->studio->email ?? '');
  $studioLocked = ($currentPaymentType === 'studio_account' && !empty($userDetail->studio_id));
  $bank = auth()->user()?->bankDetail;
@endphp
<div class="container-xxl flex-grow-1 container-p-y">
  <h4 class="fw-bold py-3 mb-4">
    <span class="text-muted fw-light">Settings /</span> Payment
  </h4>

  @if (session('success'))
    <div class="alert alert-success alert-dismissible fade show" role="alert">
      <i class="ti ti-check-circle me-2"></i>
      {{ session('success') }}
      <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
  @endif

  @if (session('error'))
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
      <i class="ti ti-alert-circle me-2"></i>
      {{ session('error') }}
      <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
  @endif

  <div class="row">
    <div class="col-12">
      <div class="card">
        <div class="card-header">
          <h5 class="card-title mb-0">Payment Setup</h5>
          <p class="text-muted mb-0">Choose who receives payments and connect Stripe if needed</p>
        </div>
        <div class="card-body">
          <form method="POST" action="{{ route('settings.payment.update') }}" id="paymentForm">
            @csrf
            <div class="row g-3">
              <div class="col-12">
                <label class="form-label">Who will receive payments? <span class="text-danger">*</span></label>
                <div class="card">
                  <div class="card-body">
                    <div class="form-check mb-3">
                      <input class="form-check-input" type="radio" name="payment_type" id="payment_type_artist" value="artist_account" {{ ($userDetail->payment_type ?? '') == 'artist_account' ? 'checked' : '' }} onchange="handlePaymentTypeChange()">
                      <label class="form-check-label" for="payment_type_artist">
                        <strong>Artist</strong> — Payments go directly to you
                      </label>
                    </div>
                    <div class="form-check mb-3">
                      <input class="form-check-input" type="radio" name="payment_type" id="payment_type_studio" value="studio_account" {{ ($userDetail->payment_type ?? '') == 'studio_account' ? 'checked' : '' }} onchange="handlePaymentTypeChange()">
                      <label class="form-check-label" for="payment_type_studio">
                        <strong>Studio</strong> — Payments go to your studio
                      </label>
                    </div>
                    <div class="form-check">
                      <input class="form-check-input" type="radio" name="payment_type" id="payment_type_inkjin" value="inkjin_account" {{ ($userDetail->payment_type ?? '') == 'inkjin_account' ? 'checked' : '' }} onchange="handlePaymentTypeChange()">
                      <label class="form-check-label" for="payment_type_inkjin">
                        <strong>Inkjin</strong> — Payments go to Inkjin and we pay you
                      </label>
                    </div>
                  </div>
                </div>
                @error('payment_type')
                  <p class="text-danger mt-1 mb-0">{{ $message }}</p>
                @enderror
              </div>

              <!-- Artist Account - Stripe Connect -->
              <div class="col-12" id="artist_stripe_section" style="display: none;">
                <div class="card border-2 {{ !empty($artistStripeId) ? 'border-success' : 'border-dashed' }}">
                  <div class="card-body text-center py-5">
                    <i class="ti ti-credit-card ti-3x {{ !empty($artistStripeId) ? 'text-success' : 'text-muted' }} mb-3"></i>
                    <h6 class="mb-2">Connect Your Stripe Account <span class="text-danger">*</span></h6>
                    <p class="text-muted mb-4">Connect Stripe so you can receive payments directly.</p>
                    
                    @if(!empty($artistStripeId))
                      <div class="mb-3">
                        <span class="badge bg-success mb-3">
                          <i class="ti ti-check me-1"></i> Stripe Account Connected
                        </span>
                      </div>
                      <button type="button" class="btn btn-label-danger" id="disconnectStripeBtn">
                        <i class="ti ti-unlink me-2"></i>
                        Disconnect Stripe
                      </button>
                    @else
                      <button type="button" class="btn btn-outline-primary" id="connectStripeBtn">
                        <i class="ti ti-brand-stripe me-2"></i>
                        Connect Stripe Account
                      </button>
                    @endif
                    
                    <input type="hidden" name="stripe_account_id" id="stripe_account_id" value="{{ $artistStripeId }}">
                    @error('stripe_account_id')
                      <p class="text-danger mt-2 mb-0">{{ $message }}</p>
                    @enderror
                  </div>
                </div>
              </div>

              <!-- Studio Account - Studio Info -->
              <div class="col-12" id="studio_section" style="display: none;">
                <div class="card">
                  <div class="card-body">
                    <div class="alert alert-info mb-4">
                      <i class="ti ti-info-circle me-2"></i>
                      Payouts go to your studio’s Stripe account. We email your studio a secure link to connect Stripe or approve new artists whenever you save these settings.
                    </div>
                    
                    <div class="mb-3">
                      <label for="studio_name_display" class="form-label">Studio Name</label>
                      <input type="text" class="form-control" id="studio_name_display" value="{{ $userDetail->studio_name ?? '' }}" readonly>
                      <small class="text-muted">This comes from your Studio Information settings</small>
                    </div>
                    
                    <div class="mb-3">
                      <label for="studio_email" class="form-label">Studio Email <span class="text-danger">*</span></label>
                      <input
                        type="email"
                        class="form-control {{ $studioLocked ? 'bg-light' : '' }} @error('studio_email') is-invalid @enderror"
                        id="studio_email"
                        name="studio_email"
                        value="{{ $studioEmail }}"
                        placeholder="Enter studio email address"
                        {{ $studioLocked ? 'readonly' : '' }}
                      >
                      @if($studioLocked)
                        <small class="text-muted">Studio email is locked because your payments are already linked to this studio.</small>
                      @else
                        <small class="text-muted">Studio email used for your studio payout profile.</small>
                      @endif
                      @error('studio_email')
                        <div class="invalid-feedback">{{ $message }}</div>
                      @enderror
                    </div>

                    @if(($userDetail->payment_type ?? '') === 'studio_account')
                      <div class="d-flex flex-wrap gap-2 align-items-center">
                        <span class="badge bg-{{ ($userDetail->payment_status ?? 'pending') === 'approved' ? 'success' : (($userDetail->payment_status ?? 'pending') === 'rejected' ? 'danger' : 'warning') }}">
                          Status: {{ ucfirst($userDetail->payment_status ?? 'pending') }}
                        </span>
                      </div>
                    @endif
                  </div>
                </div>
              </div>

              <!-- Inkjin Account - Info -->
              <div class="col-12" id="inkjin_section" style="display: none;">
                <div class="card">
                  <div class="card-body">
                    <div class="alert alert-info mb-4">
                      <i class="ti ti-info-circle me-2"></i>
                      Payments will be processed by Inkjin and paid out to you off-platform / via manual process.
                    </div>
                    <div class="row g-3">
                      <div class="col-md-6">
                        <label for="account_holder_name" class="form-label">Account Holder Name <span class="text-danger">*</span></label>
                        <input type="text" class="form-control @error('account_holder_name') is-invalid @enderror" id="account_holder_name" name="account_holder_name" value="{{ old('account_holder_name', $bank->account_holder_name ?? '') }}">
                        @error('account_holder_name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                      </div>
                      <div class="col-md-6">
                        <label for="bank_name" class="form-label">Bank Name <span class="text-danger">*</span></label>
                        <input type="text" class="form-control @error('bank_name') is-invalid @enderror" id="bank_name" name="bank_name" value="{{ old('bank_name', $bank->bank_name ?? '') }}">
                        @error('bank_name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                      </div>
                      <div class="col-md-6">
                        <label for="account_number" class="form-label">Account Number / IBAN <span class="text-danger">*</span></label>
                        <input type="text" class="form-control @error('account_number') is-invalid @enderror" id="account_number" name="account_number" value="{{ old('account_number', $bank->account_number ?? '') }}">
                        @error('account_number')<div class="invalid-feedback">{{ $message }}</div>@enderror
                      </div>
                      <div class="col-md-6">
                        <label for="swift_bic" class="form-label">SWIFT / BIC <span class="text-danger">*</span></label>
                        <input type="text" class="form-control @error('swift_bic') is-invalid @enderror" id="swift_bic" name="swift_bic" value="{{ old('swift_bic', $bank->swift_bic ?? '') }}">
                        @error('swift_bic')<div class="invalid-feedback">{{ $message }}</div>@enderror
                      </div>
                      <div class="col-md-6">
                        <label for="currency" class="form-label">Bank Currency <span class="text-danger">*</span></label>
                        <select id="currency" name="currency" class="form-select @error('currency') is-invalid @enderror" data-selected="{{ old('currency', $bank->bank_currency ?? $userDetail->currency ?? 'USD') }}"></select>
                        @error('currency')<div class="invalid-feedback">{{ $message }}</div>@enderror
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            </div>

            <div class="d-flex justify-content-end mt-4">
              <button type="submit" class="btn btn-primary" id="savePaymentBtn">
                <i class="ti ti-device-floppy me-2"></i>
                Save Changes
              </button>
            </div>
          </form>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- Disconnect Stripe Confirmation Modal -->
<div class="modal fade" id="disconnectStripeModal" tabindex="-1" aria-labelledby="disconnectStripeModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="disconnectStripeModalLabel">
          <i class="ti ti-alert-triangle text-warning me-2"></i>
          Disconnect Stripe
        </h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <p class="mb-0">Are you sure you want to disconnect Stripe?</p>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-label-secondary" data-bs-dismiss="modal">Cancel</button>
        <button type="button" class="btn btn-danger" id="confirmStripeDisconnectBtn">
          <i class="ti ti-unlink me-2"></i> Disconnect
        </button>
      </div>
    </div>
  </div>
</div> --}}

@php
  $ud = $userDetail;
  $studioEmail = old('studio_email', $ud->studio->email ?? '');
  $pt = in_array($ud->payment_type ?? '', ['artist_account', 'studio_account'], true) ? $ud->payment_type : 'artist_account';
  $payoutKey = match ($pt) {
    'studio_account' => 'studio',
    default => 'artist',
  };
  $studioLocked = ($pt === 'studio_account' && !empty($ud->studio_id));
  $studioPayoutLinked = $pt === 'studio_account' && (($ud->payment_status ?? '') === 'approved');
  $artistStripeConnected = (bool) ($artistStripeConnected ?? false);
  $studioPayoutConnected = (bool) ($studioPayoutConnected ?? $studioPayoutLinked);
  $studioPayoutCommitted = (bool) ($studioPayoutCommitted ?? $studioLocked);
  $studioPayoutStatus = match (true) {
    $studioPayoutConnected => 'connected',
    $studioPayoutCommitted => 'not_connected',
    default => 'email_not_sent',
  };
  $payoutOptionLocked = (bool) ($payoutOptionLocked ?? ($artistStripeConnected || $studioPayoutCommitted));
  $showStudioDisconnect = $studioPayoutCommitted;
  $showArtistStripeDisconnect = $artistStripeConnected;
  $stripeComplete = (bool) ($stripeStatus['complete'] ?? false);
  $stripeConnectLocale = $stripeConnectLocale ?? config('services.stripe.connect.locale', 'en-US');
  $payoutBankCountry = $payoutBankCountry ?? $ud->payout_bank_country ?? null;
  $payoutWaitingListCountry = $payoutWaitingListCountry ?? $ud->payout_waiting_list_country ?? null;
@endphp
<main class="main-content flex-1 min-h-screen flex flex-col">
  <form id="paymentForm" class="contents">
    @csrf
    <input type="hidden" name="payment_type" id="payment_type" value="{{ $pt }}" />
  <div class="flex-1 p-6 md:p-10 lg:p-12 max-w-5xl">

    <!-- Settings Tabs -->
    <div class="flex items-center gap-1 mb-6 border-b border-outline-variant/20 pb-0 overflow-x-auto">
      <a href="{{ route('profile.edit') }}" class="px-4 py-3 text-sm font-semibold whitespace-nowrap border-b-2 border-transparent text-on-surface-variant hover:text-on-surface hover:border-outline-variant transition-all">Profile</a>
      <a href="{{ route('settings.styles') }}" class="px-4 py-3 text-sm font-semibold whitespace-nowrap border-b-2 border-transparent text-on-surface-variant hover:text-on-surface hover:border-outline-variant transition-all">Styles &amp; Social</a>
      <a href="{{ route('settings.studio') }}" class="px-4 py-3 text-sm font-semibold whitespace-nowrap border-b-2 border-transparent text-on-surface-variant hover:text-on-surface hover:border-outline-variant transition-all">Studio</a>
      <a href="{{ route('settings.preferences') }}" class="px-4 py-3 text-sm font-semibold whitespace-nowrap border-b-2 border-transparent text-on-surface-variant hover:text-on-surface hover:border-outline-variant transition-all">Preferences</a>
      <a href="{{ route('settings.calendar') }}" class="px-4 py-3 text-sm font-semibold whitespace-nowrap border-b-2 border-transparent text-on-surface-variant hover:text-on-surface hover:border-outline-variant transition-all">Calendar</a>
      <a href="javascript:void(0)" class="px-4 py-3 text-sm font-semibold whitespace-nowrap border-b-2 border-primary text-primary hover:text-on-surface hover:border-outline-variant transition-all">Payments</a>
      {{-- <a href="{{route('settings.notifications')}}" class="px-4 py-3 text-sm font-semibold whitespace-nowrap border-b-2 border-transparent text-on-surface-variant hover:text-on-surface hover:border-outline-variant transition-all">Notifications</a> --}}
    </div>


    <!-- Page Header -->
    <div class="mb-8">
      <h2 class="text-3xl font-extrabold text-on-surface tracking-tight">Payment Settings</h2>
      <p class="text-on-surface-variant mt-1">Manage how your earnings are handled and update payout methods.</p>
    </div>
    <p id="payment_type_error" class="text-error text-sm mt-1 mb-4 hidden"></p>
    <div id="payAlert" class="hidden rounded-xl px-4 py-3 text-sm mb-6"></div>

    @if($pt === 'studio_account' && ($ud->payment_status ?? '') === 'rejected' && empty($ud->studio_id))
      <div class="rounded-xl border border-red-200 bg-red-50 text-red-900 px-4 py-3 text-sm mb-6 max-w-2xl">
        Your studio declined your payout request. You can connect your own bank account as an Artist or send an invite to a different studio.
      </div>
    @endif

    <div id="payoutOptionLockBanner" class="rounded-xl border border-outline-variant/30 bg-surface-container-low px-4 py-3 text-sm text-on-surface-variant mb-6 {{ $payoutOptionLocked ? '' : 'hidden' }}">
      Your payout option is locked after setup is saved. Disconnect your current setup below before switching between Artist and Studio.
    </div>

    <!-- Payout Cards -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">
      <!-- Artist -->
      <div class="payout-card {{ $payoutKey === 'artist' ? 'selected' : '' }} {{ $payoutOptionLocked && $payoutKey !== 'artist' ? 'locked-other' : '' }}" onclick="selectPayout('artist', this)" id="card-artist" data-payout-type="artist">
        <div class="radio-indicator"></div>
        <div class="w-12 h-12 rounded-xl bg-secondary-fixed flex items-center justify-center mb-5">
          <span class="material-symbols-outlined text-primary text-2xl">person</span>
        </div>
        <h3 class="text-xl font-bold text-on-surface mb-2">Artist</h3>
        <p class="text-on-surface-variant text-sm leading-relaxed mb-6">Funds are paid directly to you. Ideal for independent freelancers.</p>
        <span class="inline-flex items-center gap-1 text-sm font-bold text-primary">You get paid directly <span class="material-symbols-outlined text-base">arrow_forward</span></span>
      </div>

      <!-- Studio -->
      <div class="payout-card {{ $payoutKey === 'studio' ? 'selected' : '' }} {{ $payoutOptionLocked && $payoutKey !== 'studio' ? 'locked-other' : '' }}" onclick="selectPayout('studio', this)" id="card-studio" data-payout-type="studio">
        <div class="radio-indicator"></div>
        <div class="w-12 h-12 rounded-xl bg-secondary-fixed flex items-center justify-center mb-5">
          <span class="material-symbols-outlined text-primary text-2xl">storefront</span>
        </div>
        <h3 class="text-xl font-bold text-on-surface mb-2">Studio</h3>
        <p class="text-on-surface-variant text-sm leading-relaxed mb-6">Payments go to your studio, and your studio handles payouts to you. Best for resident artists.</p>
        <span class="inline-flex items-center gap-1 text-sm font-bold text-primary">Your studio gets paid <span class="material-symbols-outlined text-base">arrow_forward</span></span>
      </div>

    </div>

    <!-- Artist: Stripe payout setup -->
    <div id="payout-artist" class="{{ $payoutKey !== 'artist' ? 'hidden' : '' }}">
      <div class="bg-surface-container-low rounded-2xl p-6 bg-white space-y-6">
        @if ($payoutWaitingListCountry)
          <div class="rounded-xl border border-green-200 bg-green-50 text-green-900 px-4 py-4 max-w-md">
            <p class="text-sm font-semibold mb-1">Your country isn't supported yet</p>
            <p class="text-sm">We'll notify you at <strong>{{ auth()->user()->email }}</strong> when payouts become available.</p>
          </div>
        @else
          <div id="artistPayoutNotConnected" class="max-w-2xl space-y-4 {{ $artistStripeConnected ? 'hidden' : '' }}">
            <span class="inline-flex items-center gap-1.5 rounded-full bg-surface-container-high text-on-surface-variant border border-outline-variant/40 px-3 py-1 text-xs font-bold uppercase tracking-wide">Not connected</span>
            <p class="text-on-surface-variant text-sm">Your account is not connected. You won't receive payments until you connect your bank account.</p>
            <button
              type="button"
              id="connectBankAccountBtn"
              class="inline-flex items-center gap-2 bg-gradient-to-br from-primary to-primary-container text-white font-bold py-3.5 px-8 rounded-xl shadow-lg shadow-primary/20 hover:opacity-90 transition-all active:scale-[0.98]"
            >
              <span class="material-symbols-outlined text-xl">account_balance</span>
              Connect your bank account
            </button>
          </div>

          <div id="artistPayoutConnected" class="max-w-2xl space-y-4 {{ $artistStripeConnected ? '' : 'hidden' }}">
            <span class="inline-flex items-center gap-1.5 rounded-full bg-green-50 text-green-800 border border-green-200/80 px-3 py-1 text-xs font-bold uppercase tracking-wide">Connected</span>
            <p class="text-on-surface-variant text-sm">Your account is connected. You're ready to receive payments.</p>
            @if ($payoutBankCountry && ($payoutBankCountryName ?? null))
              <div>
                <p class="text-xs uppercase tracking-wider text-on-surface-variant font-medium">Bank account country</p>
                <p id="artistConnectedCountryName" class="text-sm font-semibold text-on-surface mt-1">{{ $payoutBankCountryName }}</p>
              </div>
            @else
              <div id="artistConnectedCountryWrap" class="hidden">
                <p class="text-xs uppercase tracking-wider text-on-surface-variant font-medium">Bank account country</p>
                <p id="artistConnectedCountryName" class="text-sm font-semibold text-on-surface mt-1"></p>
              </div>
            @endif
            <button type="button" id="disconnectStripeBtn" class="text-sm font-semibold text-error hover:text-on-error-container border border-error/20 px-4 py-2 rounded-xl hover:bg-error-container/30 transition-colors">
              Disconnect
            </button>
            <p id="artistStripeSavingMessage" class="hidden text-sm text-on-surface-variant">Saving your payout connection…</p>
          </div>

          <div id="artistStripeSetupSection" class="hidden space-y-6">
            <div id="payoutCountryStep" class="hidden max-w-2xl space-y-4">
              <div id="payoutCountryFields">
                <label for="payout_bank_country" class="block text-base font-semibold text-on-surface mb-2">Where is your bank account based?</label>
                <p id="payoutCountryDescription" class="text-on-surface-variant text-sm mb-4">
                  This is the country of your bank account where you'll receive your payouts.
                  Are you using Revolut? Check your IBAN in the app — the first two letters show the country.
                  GR = Greece, LT = Lithuania, GB = United Kingdom.
                </p>
                <select id="payout_bank_country" name="payout_bank_country" class="js-payout-country-select w-full max-w-md text-sm border border-outline-variant/30 rounded-xl px-4 py-3 bg-white text-on-surface focus:outline-none focus:ring-2 focus:ring-primary/30" aria-label="Bank account country">
                  <option value="">Select country</option>
                  @foreach ($payoutRegistrationCountries as $country)
                    <option value="{{ $country['code'] }}">{{ $country['name'] }}</option>
                  @endforeach
                </select>
                <p id="payout_bank_country_error" class="text-error text-xs mt-2 hidden"></p>
                <button type="button" id="payoutCountryContinue" class="hidden mt-4 inline-flex items-center gap-2 bg-gradient-to-br from-primary to-primary-container text-white font-bold py-3 px-6 rounded-xl shadow-lg shadow-primary/20 hover:opacity-90 transition-all disabled:opacity-70">
                  <span id="payoutCountryContinueLabel">Continue</span>
                  <span id="payoutCountryContinueIcon" class="material-symbols-outlined text-lg">arrow_forward</span>
                </button>
              </div>
            </div>

            <div id="payoutStripeStep" class="hidden">
              <div class="mb-4 pb-4 border-b border-outline-variant/20 max-w-2xl">
                <p id="payoutStripeStepTitle" class="text-base font-semibold text-on-surface">Complete your payout details</p>
                <p id="payoutStripeStepDescription" class="text-on-surface-variant text-sm mt-2">
                  Add your personal details, upload an identity document (passport or ID), and enter your bank account below.
                </p>
                <div id="payoutStripeCountrySummary" class="mt-4 hidden">
                  <p class="text-xs uppercase tracking-wider text-on-surface-variant font-medium">Bank account country</p>
                  <p id="payoutBankCountryName" class="text-sm font-semibold text-on-surface"></p>
                </div>
              </div>

              @if (!($stripeConnectConfigured ?? false))
                <div class="rounded-xl border border-red-200 bg-red-50 text-red-800 px-4 py-3 text-sm">
                  Stripe is not configured. Please contact support.
                </div>
              @else
                <div id="stripeConnectMount" class="min-h-[420px] rounded-xl p-1 bg-white overflow-hidden"></div>
                <p id="stripe_connect_error" class="text-error text-xs mt-2 hidden"></p>
                <p id="stripeConnectHint" class="text-on-surface-variant text-xs mt-3">
                  Complete all Stripe steps. Your connection status will update automatically when you are done.
                </p>
              @endif
            </div>
          </div>
        @endif
      </div>
    </div>

    <!-- Studio: payout status -->
    <div id="payout-studio" class="{{ $payoutKey !== 'studio' ? 'hidden' : '' }}">
      <div class="bg-surface-container-low rounded-2xl p-6 space-y-4">
        <div id="studioPayoutEmailNotSent" class="max-w-2xl space-y-4 {{ $studioPayoutStatus !== 'email_not_sent' ? 'hidden' : '' }}">
          <span class="inline-flex items-center gap-1.5 rounded-full bg-surface-container-high text-on-surface-variant border border-outline-variant/40 px-3 py-1 text-xs font-bold uppercase tracking-wide">Email not sent</span>
          <p class="text-on-surface-variant text-sm">You need to send an email to your studio and ask them to connect their bank account. Payouts are on hold until they do.</p>
          <div>
            <label for="studio_email" class="block text-sm font-semibold text-on-surface mb-2">Studio Email Address</label>
            <input type="email" id="studio_email" name="studio_email" value="{{ $studioEmail }}" placeholder="studio@example.com" class="w-full text-sm border border-outline-variant/30 rounded-xl px-4 py-3 bg-white text-on-surface focus:outline-none focus:ring-2 focus:ring-primary/30" autocomplete="email">
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
              <input type="email" id="studio_email_not_connected" value="{{ $studioEmail }}" placeholder="studio@example.com" class="w-full flex-1 text-sm border border-outline-variant/30 rounded-xl px-4 py-3 bg-white text-on-surface focus:outline-none focus:ring-2 focus:ring-primary/30" autocomplete="email" readonly>
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

        @if ($showStudioDisconnect)
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

  </div>

  </form>
</main>

<div id="disconnectStripeModal" class="hidden fixed inset-0 z-[200] flex items-center justify-center p-4 bg-black/50" role="dialog" aria-modal="true">
  <div class="bg-white rounded-2xl max-w-md w-full p-6 shadow-xl">
    <h5 class="text-lg font-bold text-on-surface mb-2">Disconnect Stripe payouts?</h5>
    <p class="text-on-surface-variant text-sm mb-6">You will need to complete Stripe setup again if you want direct artist payouts later.</p>
    <div class="flex justify-end gap-3">
      <button type="button" id="cancelDisconnectStripe" class="rounded-xl px-5 py-2.5 text-sm font-semibold text-on-surface hover:bg-surface-container-low">Cancel</button>
      <button type="button" id="confirmDisconnectStripeBtn" class="rounded-xl px-5 py-2.5 text-sm font-semibold bg-error text-white hover:opacity-90">Disconnect</button>
    </div>
  </div>
</div>

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

@section('scripts')
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script type="module">
import { loadConnectAndInitialize } from 'https://esm.sh/@stripe/connect-js@3.3.34/pure';

const stripeConfigured = @json($stripeConnectConfigured ?? false);
const publishableKey = @json($stripePublishableKey ?? '');
const sessionUrl = @json(route('settings.payment.stripe.session'));
const statusUrl = @json(route('settings.payment.stripe.status'));
const bankCountryUrl = @json(route('settings.payment.bank-country'));
const savePaymentUrl = @json(route('settings.payment.update'));
const initialPayoutBankCountryName = @json($payoutBankCountryName ?? null);
const stripeConnectLocale = @json($stripeConnectLocale);
const stripeConnectAppearance = @json(config('services.stripe.connect.appearance', []));
const initialPayoutBankCountry = @json($payoutBankCountry);
const initialWaitingListCountry = @json($payoutWaitingListCountry);
window.stripeOnboardingComplete = @json($stripeComplete);
window.payoutBankCountrySelected = !!initialPayoutBankCountry;
window.payoutSupportedCountryPending = null;

let connectInstance = null;
let onboardingMounted = false;
let stripeSessionData = null;

window.setStripeOnboardingComplete = function (complete) {
  window.stripeOnboardingComplete = !!complete;
  const hint = document.getElementById('stripeConnectHint');
  if (complete && hint) hint.classList.add('hidden');
  else if (hint) hint.classList.remove('hidden');
};

function showArtistStripeConnectedUi(countryName) {
  const setup = document.getElementById('artistStripeSetupSection');
  const notConnected = document.getElementById('artistPayoutNotConnected');
  const connected = document.getElementById('artistPayoutConnected');
  const countryWrap = document.getElementById('artistConnectedCountryWrap');
  const countryNameEl = document.getElementById('artistConnectedCountryName');

  if (setup) setup.classList.add('hidden');
  if (notConnected) notConnected.classList.add('hidden');
  if (connected) connected.classList.remove('hidden');

  const resolvedCountry = countryName || initialPayoutBankCountryName || document.getElementById('payoutBankCountryName')?.textContent?.trim();
  if (resolvedCountry && countryNameEl) {
    countryNameEl.textContent = resolvedCountry;
    if (countryWrap) countryWrap.classList.remove('hidden');
  }

  window.artistStripeConnected = true;
  window.stripeOnboardingComplete = true;
  window.setStripeOnboardingComplete(true);
  if (typeof window.lockPayoutOptions === 'function') {
    window.lockPayoutOptions('artist');
  }
}

window.lockPayoutOptions = function (activeKey) {
  window.payoutOptionLocked = true;
  window.activePayoutKey = activeKey;

  const banner = document.getElementById('payoutOptionLockBanner');
  if (banner) banner.classList.remove('hidden');

  const artistCard = document.getElementById('card-artist');
  const studioCard = document.getElementById('card-studio');
  if (artistCard) {
    artistCard.classList.toggle('locked-other', activeKey !== 'artist');
  }
  if (studioCard) {
    studioCard.classList.toggle('locked-other', activeKey !== 'studio');
  }
};

let autoSaveArtistStripeInProgress = false;

async function tryAutoSaveArtistStripe() {
  if (autoSaveArtistStripeInProgress || window.artistStripeConnected) return;

  const accountId = stripeSessionData?.account_id;
  if (!accountId) return;

  autoSaveArtistStripeInProgress = true;
  const savingMsg = document.getElementById('artistStripeSavingMessage');
  if (savingMsg) savingMsg.classList.remove('hidden');

  const fd = new FormData();
  fd.append('_token', document.querySelector('meta[name="csrf-token"]').getAttribute('content'));
  fd.append('payment_type', 'artist_account');
  fd.append('stripe_account_id', accountId);

  try {
    const res = await fetch(savePaymentUrl, {
      method: 'POST',
      body: fd,
      headers: { Accept: 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content') },
    });
    const data = await res.json();

    if (!res.ok || !data.success) {
      throw new Error(data.message || (data.errors?.stripe_connect?.[0]) || 'Could not save Stripe payout connection.');
    }

    window.location.reload();
  } catch (err) {
    autoSaveArtistStripeInProgress = false;
    if (savingMsg) savingMsg.classList.add('hidden');
    const errEl = document.getElementById('stripe_connect_error');
    if (errEl) {
      errEl.textContent = err.message || 'Stripe is complete, but saving failed. Please refresh the page to try again.';
      errEl.classList.remove('hidden');
    }
  }
}

async function handleStripeOnboardingExit() {
  await new Promise((resolve) => setTimeout(resolve, 1500));
  const status = await refreshStripeStatus();
  if (status?.complete) {
    const countryName = document.getElementById('payoutBankCountryName')?.textContent?.trim() || initialPayoutBankCountryName;
    showArtistStripeConnectedUi(countryName);
    await tryAutoSaveArtistStripe();
  }
}

window.tryAutoSaveArtistStripe = tryAutoSaveArtistStripe;

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
  if (!res.ok || !data.client_secret) throw new Error(data.message || 'Could not start Stripe onboarding.');
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
  const params = new URLSearchParams();
  if (stripeSessionData?.account_id) params.set('account_id', stripeSessionData.account_id);
  const url = params.toString() ? `${statusUrl}?${params}` : statusUrl;
  const res = await fetch(url, { headers: { Accept: 'application/json' } });
  const data = await res.json();
  if (res.ok && data.success) window.setStripeOnboardingComplete(!!data.complete);
  return data;
}

window.refreshStripeOnboardingStatus = refreshStripeStatus;

async function mountStripeOnboarding() {
  const container = document.getElementById('stripeConnectMount');
  const stripeStep = document.getElementById('payoutStripeStep');
  if (!stripeConfigured || !publishableKey || !container || !window.payoutBankCountrySelected || onboardingMounted) return;
  if (!stripeStep || stripeStep.classList.contains('hidden')) return;

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
    accountOnboarding.setOnExit(handleStripeOnboardingExit);
    accountOnboarding.setOnStepChange(() => {
      setTimeout(async () => {
        const status = await refreshStripeStatus();
        if (status?.complete) {
          const countryName = document.getElementById('payoutBankCountryName')?.textContent?.trim() || initialPayoutBankCountryName;
          showArtistStripeConnectedUi(countryName);
          await tryAutoSaveArtistStripe();
        }
      }, 800);
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
  const setupVisible = document.getElementById('artistStripeSetupSection') && !document.getElementById('artistStripeSetupSection').classList.contains('hidden');
  const stripeStepVisible = document.getElementById('payoutStripeStep') && !document.getElementById('payoutStripeStep').classList.contains('hidden');
  if (artistVisible && setupVisible && stripeStepVisible && stripeConfigured && window.payoutBankCountrySelected) {
    await mountStripeOnboarding();
  }
};

window.showPayoutStep = function (step) {
  const country = document.getElementById('payoutCountryStep');
  const stripe = document.getElementById('payoutStripeStep');
  if (country) country.classList.toggle('hidden', step !== 'country');
  if (stripe) stripe.classList.toggle('hidden', step !== 'stripe');
};

function initPayoutCountrySelect2() {
  if (!window.jQuery || !window.jQuery.fn.select2) return;
  const $select = window.jQuery('#payout_bank_country');
  if (!$select.length) return;
  if ($select.hasClass('select2-hidden-accessible')) {
    $select.select2('destroy');
  }
  $select.select2({
    width: '100%',
    dropdownParent: window.jQuery('body'),
    placeholder: 'Select country',
  });
}

function resetPayoutCountrySelect() {
  const select = document.getElementById('payout_bank_country');
  if (window.jQuery && select) {
    const $select = window.jQuery(select);
    if ($select.hasClass('select2-hidden-accessible')) {
      $select.val('').trigger('change');
    } else {
      select.value = '';
    }
  } else if (select) {
    select.value = '';
  }
  togglePayoutCountryContinue(false);
  window.payoutSupportedCountryPending = null;
  const errEl = document.getElementById('payout_bank_country_error');
  if (errEl) {
    errEl.classList.add('hidden');
    errEl.textContent = '';
  }
}

document.getElementById('connectBankAccountBtn')?.addEventListener('click', async () => {
  const setup = document.getElementById('artistStripeSetupSection');
  if (setup) setup.classList.remove('hidden');

  if (initialPayoutBankCountry || window.payoutBankCountrySelected) {
    window.payoutBankCountrySelected = true;
    const nameEl = document.getElementById('payoutBankCountryName');
    const summary = document.getElementById('payoutStripeCountrySummary');
    if (nameEl && initialPayoutBankCountryName) nameEl.textContent = initialPayoutBankCountryName;
    if (summary && initialPayoutBankCountryName) summary.classList.remove('hidden');
    window.showPayoutStep('stripe');
    await mountStripeOnboarding();
    return;
  }

  window.showPayoutStep('country');
  window.requestAnimationFrame(() => {
    initPayoutCountrySelect2();
    resetPayoutCountrySelect();
  });
});

function setCountryContinueLoading(loading) {
  const btn = document.getElementById('payoutCountryContinue');
  const label = document.getElementById('payoutCountryContinueLabel');
  const icon = document.getElementById('payoutCountryContinueIcon');
  if (btn) btn.disabled = loading;
  if (label) label.textContent = loading ? 'Setting up payout verification…' : 'Continue';
  if (icon) {
    icon.textContent = loading ? 'progress_activity' : 'arrow_forward';
    icon.classList.toggle('animate-spin', loading);
  }
}

function togglePayoutCountryContinue(show) {
  const btn = document.getElementById('payoutCountryContinue');
  if (btn) btn.classList.toggle('hidden', !show);
}

async function savePayoutBankCountry(countryCode) {
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
    throw new Error(data.message || 'Could not save bank country.');
  }
  window.payoutBankCountrySelected = true;
  onboardingMounted = false;
  connectInstance = null;
  stripeSessionData = null;
  const nameEl = document.getElementById('payoutBankCountryName');
  const summary = document.getElementById('payoutStripeCountrySummary');
  const countryLabel = data.payout_bank_country_name || countryCode;
  if (nameEl) nameEl.textContent = countryLabel;
  if (summary) summary.classList.remove('hidden');
  const stripeDesc = document.getElementById('payoutStripeStepDescription');
  if (stripeDesc && countryLabel) {
    stripeDesc.textContent = `Add your personal details, upload an identity document (passport or ID), and enter your bank account below for payouts to ${countryLabel}.`;
  }
  window.showPayoutStep('stripe');
  togglePayoutCountryContinue(false);
  window.payoutSupportedCountryPending = null;
  await mountStripeOnboarding();
  return data;
}

window.handlePayoutBankCountryChange = function (value) {
  const errEl = document.getElementById('payout_bank_country_error');
  if (errEl) { errEl.classList.add('hidden'); errEl.textContent = ''; }
  if (!value) {
    togglePayoutCountryContinue(false);
    window.payoutSupportedCountryPending = null;
    return;
  }
  window.payoutSupportedCountryPending = value;
  togglePayoutCountryContinue(true);
};

document.getElementById('payoutCountryContinue')?.addEventListener('click', async () => {
  const countryCode = window.payoutSupportedCountryPending || document.getElementById('payout_bank_country')?.value;
  const errEl = document.getElementById('payout_bank_country_error');
  if (!countryCode) {
    if (errEl) { errEl.textContent = 'Please select where your bank account is based.'; errEl.classList.remove('hidden'); }
    return;
  }
  setCountryContinueLoading(true);
  try {
    await savePayoutBankCountry(countryCode);
  } catch (err) {
    if (errEl) { errEl.textContent = err.message || 'Could not save bank country.'; errEl.classList.remove('hidden'); }
  } finally {
    setCountryContinueLoading(false);
  }
});

window.artistStripeConnected = @json($artistStripeConnected);

window.getStripeSessionAccountId = function () { return stripeSessionData?.account_id || null; };
</script>
<script>
  window.payoutOptionLocked = @json($payoutOptionLocked);
  window.activePayoutKey = @json($payoutKey);
  const settingsPaymentSaveUrl = @json(route('settings.payment.update'));

  function selectPayout(type, el) {
    if (window.payoutOptionLocked && type !== window.activePayoutKey) {
      $('#payAlert')
        .attr('class', 'rounded-xl px-4 py-3 text-sm mb-6 bg-amber-50 text-amber-900 border border-amber-200')
        .text('Disconnect your current payout setup before switching between Artist and Studio.')
        .removeClass('hidden');
      return;
    }

    document.querySelectorAll('.payout-card').forEach(c => c.classList.remove('selected'));
    el.classList.add('selected');
    var map = { artist: 'artist_account', studio: 'studio_account' };
    $('#payment_type').val(map[type]);
    $('#payout-artist').toggleClass('hidden', type !== 'artist');
    $('#payout-studio').toggleClass('hidden', type !== 'studio');
    $('#payment_type_error').text('').addClass('hidden');
    $('#payAlert').addClass('hidden').text('');
  }
  $(function () {
    $('#payout_bank_country').on('change select2:select', function () {
      if (typeof window.handlePayoutBankCountryChange === 'function') {
        window.handlePayoutBankCountryChange($(this).val());
      }
    });

    function openStripeModal() { $('#disconnectStripeModal').removeClass('hidden'); }
    function closeStripeModal() { $('#disconnectStripeModal').addClass('hidden'); }
    $('#disconnectStripeBtn').on('click', openStripeModal);
    $('#cancelDisconnectStripe').on('click', closeStripeModal);
    $('#disconnectStripeModal').on('click', function (e) { if (e.target === this) closeStripeModal(); });
    $('#confirmDisconnectStripeBtn').on('click', function () {
      closeStripeModal();
      $.ajax({
        url: @json(route('settings.payment.update')),
        type: 'POST',
        data: { _token: @json(csrf_token()), disconnect_stripe: 1 },
        headers: { 'X-CSRF-TOKEN': @json(csrf_token()), Accept: 'application/json' },
      }).done(function (data) {
        if (data.success) {
          window.location.reload();
          return;
        }
        $('#payAlert').attr('class', 'rounded-xl px-4 py-3 text-sm mb-6 bg-red-50 text-red-800 border border-red-200').text(data.message || 'Could not disconnect Stripe.').removeClass('hidden');
      }).fail(function (xhr) {
        $('#payAlert').attr('class', 'rounded-xl px-4 py-3 text-sm mb-6 bg-red-50 text-red-800 border border-red-200').text((xhr.responseJSON && xhr.responseJSON.message) || 'Could not disconnect Stripe.').removeClass('hidden');
      });
    });

    function openStudioModal() { $('#disconnectStudioModal').removeClass('hidden'); }
    function closeStudioModal() { $('#disconnectStudioModal').addClass('hidden'); }
    $('#disconnectStudioBtn').on('click', openStudioModal);
    $('#cancelDisconnectStudio').on('click', closeStudioModal);
    $('#disconnectStudioModal').on('click', function (e) { if (e.target === this) closeStudioModal(); });
    $('#confirmDisconnectStudioBtn').on('click', function () {
      closeStudioModal();
      $.ajax({
        url: @json(route('settings.payment.update')),
        type: 'POST',
        data: { _token: @json(csrf_token()), disconnect_studio: 1 },
        headers: { 'X-CSRF-TOKEN': @json(csrf_token()), Accept: 'application/json' },
      }).done(function (data) {
        if (data.success) {
          window.location.reload();
          return;
        }
        $('#payAlert').attr('class', 'rounded-xl px-4 py-3 text-sm mb-6 bg-red-50 text-red-800 border border-red-200').text(data.message || 'Could not disconnect studio payouts.').removeClass('hidden');
      }).fail(function (xhr) {
        $('#payAlert').attr('class', 'rounded-xl px-4 py-3 text-sm mb-6 bg-red-50 text-red-800 border border-red-200').text((xhr.responseJSON && xhr.responseJSON.message) || 'Could not disconnect studio payouts.').removeClass('hidden');
      });
    });

    function setStudioActionLoading($btn, loading, loadingText) {
      if (!$btn.length) return;
      if (loading) {
        $btn.data('original-html', $btn.html());
        $btn.prop('disabled', true).text(loadingText);
      } else {
        $btn.prop('disabled', false).html($btn.data('original-html') || $btn.text());
      }
    }

    $('#payStudioSend').on('click', function () {
      var $btn = $(this);
      var $alertEl = $('#payAlert');
      $('#studio_email_error').addClass('hidden').text('');
      $alertEl.addClass('hidden').text('');
      $('#payment_type').val('studio_account');
      var email = ($('#studio_email').val() || '').trim();
      if (!email) {
        $('#studio_email_error').text('Studio email is required.').removeClass('hidden');
        return;
      }
      setStudioActionLoading($btn, true, 'Sending...');
      var fd = new FormData(document.getElementById('paymentForm'));
      fd.set('payment_type', 'studio_account');
      fd.set('studio_email', email);
      $.ajax({
        url: settingsPaymentSaveUrl,
        type: 'POST',
        data: fd,
        processData: false,
        contentType: false,
        headers: { 'X-CSRF-TOKEN': @json(csrf_token()), Accept: 'application/json' },
      }).done(function (data) {
        if (data.success) {
          if (typeof window.lockPayoutOptions === 'function') window.lockPayoutOptions('studio');
          window.location.reload();
          return;
        }
        $alertEl.attr('class', 'rounded-xl px-4 py-3 text-sm mb-6 bg-red-50 text-red-800 border border-red-200').text(data.message || 'Could not send studio email.').removeClass('hidden');
      }).fail(function (xhr) {
        if (xhr.status === 422 && xhr.responseJSON && xhr.responseJSON.errors) {
          $.each(xhr.responseJSON.errors, function (k, msgs) {
            $('#' + (k === 'stripe_connect' ? 'stripe_connect_error' : k + '_error')).text(msgs[0]).removeClass('hidden');
          });
        } else {
          $alertEl.attr('class', 'rounded-xl px-4 py-3 text-sm mb-6 bg-red-50 text-red-800 border border-red-200').text((xhr.responseJSON && xhr.responseJSON.message) || 'Could not send studio email.').removeClass('hidden');
        }
      }).always(function () {
        setStudioActionLoading($btn, false);
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
        url: settingsPaymentSaveUrl,
        type: 'POST',
        data: payload,
        headers: { 'X-CSRF-TOKEN': @json(csrf_token()), Accept: 'application/json' },
      }).done(function (data) {
        if (data.success) {
          if (data.studio_email && $emailInput.length) {
            $emailInput.val(data.studio_email);
            studioEmailOriginal = data.studio_email;
            setStudioEmailEditing(false);
          }
          $alertEl.attr('class', 'rounded-xl px-4 py-3 text-sm mb-6 bg-green-50 text-green-800 border border-green-200').text(data.message || 'Reminder sent to your studio.').removeClass('hidden');
          return;
        }
        $alertEl.attr('class', 'rounded-xl px-4 py-3 text-sm mb-6 bg-red-50 text-red-800 border border-red-200').text(data.message || 'Could not send reminder.').removeClass('hidden');
      }).fail(function (xhr) {
        if (xhr.status === 422 && xhr.responseJSON && xhr.responseJSON.errors && xhr.responseJSON.errors.studio_email) {
          $('#studio_email_error').text(xhr.responseJSON.errors.studio_email[0]).removeClass('hidden');
        }
        $alertEl.attr('class', 'rounded-xl px-4 py-3 text-sm mb-6 bg-red-50 text-red-800 border border-red-200').text((xhr.responseJSON && xhr.responseJSON.message) || 'Could not send reminder.').removeClass('hidden');
      }).always(function () {
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
  });
</script>
@endsection

