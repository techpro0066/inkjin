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
                      Payments will go to your studio's Stripe account. We will email the studio to connect Stripe.
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
  $bank = auth()->user()?->bankDetail;
  $studioEmail = old('studio_email', $ud->studio->email ?? '');
  $pt = $ud->payment_type ?? 'artist_account';
  $payoutKey = match ($pt) {
    'studio_account' => 'studio',
    'inkjin_account' => 'inkjin',
    default => 'artist',
  };
  $artistStripeConnected = ($pt === 'artist_account' && !empty($ud->stripe_account_id));
  $artistStripeId = $artistStripeConnected ? ($ud->stripe_account_id ?? '') : '';
  $stripeLocksOtherPayouts = ($pt === 'artist_account' && !empty($ud->stripe_account_id));
  $studioLocked = ($pt === 'studio_account' && !empty($ud->studio_id));
  $studioStripeConnected = $studioLocked && !empty(optional($ud->studio)->stripe_account_id);
  $studioConnectUrl = $studioLocked
    ? \Illuminate\Support\Facades\URL::temporarySignedRoute('studio.stripe.connect', now()->addDays(30), ['userDetail' => $ud->id])
    : null;
@endphp
<main class="main-content flex-1 min-h-screen flex flex-col">
  <form id="paymentForm" class="contents">
    @csrf
    <input type="hidden" name="payment_type" id="payment_type" value="{{ $pt }}" />
    <input type="hidden" name="stripe_account_id" id="stripe_account_id" value="{{ $artistStripeId }}" />
  <div class="flex-1 p-6 md:p-10 lg:p-12 max-w-5xl">

    <!-- Settings Tabs -->
    <div class="flex items-center gap-1 mb-6 border-b border-outline-variant/20 pb-0 overflow-x-auto">
      <a href="{{ route('profile.edit') }}" class="px-4 py-3 text-sm font-semibold whitespace-nowrap border-b-2 border-transparent text-on-surface-variant hover:text-on-surface hover:border-outline-variant transition-all">Profile</a>
      <a href="{{ route('settings.styles') }}" class="px-4 py-3 text-sm font-semibold whitespace-nowrap border-b-2 border-transparent text-on-surface-variant hover:text-on-surface hover:border-outline-variant transition-all">Styles &amp; Social</a>
      <a href="{{ route('settings.studio') }}" class="px-4 py-3 text-sm font-semibold whitespace-nowrap border-b-2 border-transparent text-on-surface-variant hover:text-on-surface hover:border-outline-variant transition-all">Studio</a>
      <a href="{{ route('settings.preferences') }}" class="px-4 py-3 text-sm font-semibold whitespace-nowrap border-b-2 border-transparent text-on-surface-variant hover:text-on-surface hover:border-outline-variant transition-all">Preferences</a>
      <a href="{{ route('settings.calendar') }}" class="px-4 py-3 text-sm font-semibold whitespace-nowrap border-b-2 border-transparent text-on-surface-variant hover:text-on-surface hover:border-outline-variant transition-all">Calendar</a>
      <a href="javascript:void(0)" class="px-4 py-3 text-sm font-semibold whitespace-nowrap border-b-2 border-primary text-primary hover:text-on-surface hover:border-outline-variant transition-all">Payments</a>
      <a href="{{route('settings.notifications')}}" class="px-4 py-3 text-sm font-semibold whitespace-nowrap border-b-2 border-transparent text-on-surface-variant hover:text-on-surface hover:border-outline-variant transition-all">Notifications</a>
    </div>


    <!-- Page Header -->
    <div class="mb-8">
      <h2 class="text-3xl font-extrabold text-on-surface tracking-tight">Payment Settings</h2>
      <p class="text-on-surface-variant mt-1">Manage how your earnings are handled and update payout methods.</p>
    </div>
    @if($stripeLocksOtherPayouts)
      <p class="text-sm text-on-surface-variant mb-4 max-w-2xl">
        <span class="material-symbols-outlined text-base align-middle mr-1 text-primary">lock</span>
        Studio and Inkjin payout options are unavailable while your Stripe account is connected. Disconnect Stripe below to change payout type.
      </p>
    @endif
    <p id="payment_type_error" class="text-error text-sm mt-1 mb-4 hidden"></p>
    <div id="payAlert" class="hidden rounded-xl px-4 py-3 text-sm mb-6"></div>

    <!-- Payout Cards -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
      <!-- Artist -->
      <div class="payout-card {{ $payoutKey === 'artist' ? 'selected' : '' }}" onclick="selectPayout('artist', this)" id="card-artist">
        <div class="radio-indicator"></div>
        <div class="w-12 h-12 rounded-xl bg-secondary-fixed flex items-center justify-center mb-5">
          <span class="material-symbols-outlined text-primary text-2xl">person</span>
        </div>
        <h3 class="text-xl font-bold text-on-surface mb-2">Artist</h3>
        <p class="text-on-surface-variant text-sm leading-relaxed mb-6">Funds are paid directly to you. Ideal for independent freelancers.</p>
        <span class="inline-flex items-center gap-1 text-sm font-bold text-primary">You get paid directly <span class="material-symbols-outlined text-base">arrow_forward</span></span>
      </div>

      <!-- Studio -->
      <div class="payout-card {{ $payoutKey === 'studio' ? 'selected' : '' }} {{ $stripeLocksOtherPayouts ? 'opacity-50 pointer-events-none cursor-not-allowed select-none' : '' }}" onclick="selectPayout('studio', this)" id="card-studio">
        <div class="radio-indicator"></div>
        <div class="w-12 h-12 rounded-xl bg-secondary-fixed flex items-center justify-center mb-5">
          <span class="material-symbols-outlined text-primary text-2xl">storefront</span>
        </div>
        <h3 class="text-xl font-bold text-on-surface mb-2">Studio</h3>
        <p class="text-on-surface-variant text-sm leading-relaxed mb-6">Payments go to your studio, and your studio handles payouts to you. Best for resident artists.</p>
        <span class="inline-flex items-center gap-1 text-sm font-bold text-primary">Your studio gets paid <span class="material-symbols-outlined text-base">arrow_forward</span></span>
      </div>

      <!-- Inkjin -->
      <div class="payout-card {{ $payoutKey === 'inkjin' ? 'selected' : '' }} {{ $stripeLocksOtherPayouts ? 'opacity-50 pointer-events-none cursor-not-allowed select-none' : '' }}" onclick="selectPayout('inkjin', this)" id="card-inkjin">
        <div class="radio-indicator"></div>
        <div class="w-12 h-12 rounded-xl bg-secondary-fixed flex items-center justify-center mb-5">
          <span class="material-symbols-outlined text-primary text-2xl">account_balance</span>
        </div>
        <h3 class="text-xl font-bold text-on-surface mb-2">Inkjin</h3>
        <p class="text-on-surface-variant text-sm leading-relaxed mb-6">Inkjin collects payments and we pay you after the booking is completed.</p>
        <span class="inline-flex items-center gap-1 text-sm font-bold text-primary">Inkjin collects for you <span class="material-symbols-outlined text-base">arrow_forward</span></span>
      </div>
    </div>

    <!-- Artist: Stripe Connected Status -->
    <div id="payout-artist" class="{{ $payoutKey !== 'artist' ? 'hidden' : '' }}">
      <div class="bg-surface-container-low rounded-2xl p-6">
        <div class="flex items-center justify-between">
          <div class="flex items-center gap-4">
            <div class="w-12 h-12 rounded-xl bg-white flex items-center justify-center shadow-sm border border-outline-variant/15">
              <svg class="w-7 h-5" viewBox="0 0 60 25" fill="none"><path fill-rule="evenodd" clip-rule="evenodd" d="M60 12.8C60 8.5 57.9 5.1 54 5.1c-3.9 0-6.4 3.4-6.4 7.6 0 5 2.8 7.6 6.9 7.6 2 0 3.5-.4 4.6-1.1v-3.5c-1.1.6-2.4.9-4 .9-1.6 0-3-.6-3.2-2.5H60c0-.2 0-1.1 0-1.3zm-6.3-1.6c0-1.9 1.2-2.7 2.2-2.7 1 0 2.1.8 2.1 2.7h-4.3zM41.2 5.1c-1.6 0-2.6.7-3.2 1.3l-.2-1h-3.8v19.3l4.3-.9v-4.7c.6.4 1.4 1 2.8 1 2.8 0 5.4-2.3 5.4-7.3-.1-4.6-2.7-7.7-5.3-7.7zm-.9 11.8c-.9 0-1.5-.3-1.9-.8v-6.2c.4-.5 1-.8 1.9-.8 1.5 0 2.5 1.7 2.5 3.9 0 2.2-1 3.9-2.5 3.9zM28.1 4.2l4.3-.9V0l-4.3.9v3.3zM28.1 5.4h4.3v14.3h-4.3V5.4zM23.7 6.5l-.3-1.1h-3.7v14.3h4.3V9.9c1-.3 2.7-.5 3.6-.2V5.6c-1-.4-2.9-.1-3.9.9zM15.3 1.7l-4.2.9-.1 13.1c0 2.4 1.8 4.2 4.2 4.2 1.3 0 2.3-.2 2.9-.5v-3.5c-.5.2-3 .9-3-1.4V8.9h3V5.4h-3l.2-3.7zM4.7 9.5c0-.7.6-1 1.5-1 1.3 0 3 .4 4.3 1.1V5.8C9 5.2 7.5 4.9 6.2 4.9 2.5 4.9 0 6.9 0 10c0 4.7 6.5 4 6.5 6 0 .8-.7 1.1-1.7 1.1-1.5 0-3.4-.6-4.8-1.4v3.9c1.6.7 3.3 1 4.8 1 3.8 0 6.4-1.9 6.4-5 .1-5.1-6.5-4.2-6.5-6.1z" fill="#635BFF"/></svg>
            </div>
            <div>
              <div class="flex items-center gap-2">
                <h4 class="text-sm font-bold text-on-surface">Stripe</h4>
                @if($artistStripeConnected)
                  <span class="inline-flex items-center gap-1 text-xs font-semibold text-green-700 bg-green-50 px-2 py-0.5 rounded-full">
                    <span class="w-1.5 h-1.5 rounded-full bg-green-600"></span> Connected
                  </span>
                @else
                  <span class="inline-flex items-center gap-1 text-xs font-semibold text-amber-700 bg-amber-50 px-2 py-0.5 rounded-full">
                    <span class="w-1.5 h-1.5 rounded-full bg-amber-600"></span> Not connected
                  </span>
                @endif
              </div>
              <p class="text-on-surface-variant text-xs mt-1">{{ $artistStripeConnected ? 'Connected to ' . $artistStripeId : 'Connect your Stripe account to receive payouts.' }}</p>
            </div>
          </div>
          @if($artistStripeConnected)
            <button type="button" id="disconnectStripeBtn" class="text-sm font-semibold text-error hover:text-on-error-container border border-error/20 px-4 py-2 rounded-xl hover:bg-error-container/30 transition-colors">Disconnect</button>
          @else
            <button type="button" id="connectStripeBtn" class="text-sm font-semibold text-primary border border-primary/20 px-4 py-2 rounded-xl hover:bg-primary/5 transition-colors">Connect</button>
          @endif
        </div>
      </div>
      <p id="stripe_account_id_error" class="text-error text-sm mt-3 hidden"></p>
    </div>

    <!-- Studio: Invitation -->
    <div id="payout-studio" class="{{ $payoutKey !== 'studio' ? 'hidden' : '' }}">
      <div class="bg-surface-container-low rounded-2xl p-6">
        <div class="space-y-4">
          <div>
            <label for="studio_email" class="block text-sm font-semibold text-on-surface mb-2">Studio Email Address</label>
            <input type="email" id="studio_email" name="studio_email" value="{{ $studioEmail }}" {{ $studioLocked ? 'readonly' : '' }} class="w-full text-sm border border-outline-variant/30 rounded-xl px-4 py-3 bg-white text-on-surface focus:outline-none focus:ring-2 focus:ring-primary/30 {{ $studioLocked ? 'opacity-80 cursor-not-allowed' : '' }}">
          </div>
          @if($studioLocked)
            <p class="text-on-surface-variant text-xs mt-2">This email is locked because your payouts are already linked to a studio.</p>
          @else
            <p class="text-on-surface-variant text-xs mt-2">Studio email used for your studio payout profile.</p>
          @endif

          @if($studioLocked)
            <div class="rounded-xl border border-outline-variant/20 bg-white p-4">
              <div class="flex flex-wrap items-center justify-between gap-3">
                <div>
                  <p class="text-sm font-semibold text-on-surface">Studio Stripe Account</p>
                  @if($studioStripeConnected)
                    <p class="text-xs text-green-700 mt-1">Connected to studio Stripe account.</p>
                  @else
                    <p class="text-xs text-amber-700 mt-1">Not connected yet. Connect the studio Stripe account.</p>
                  @endif
                </div>
                @if(!$studioStripeConnected && $studioConnectUrl)
                  <a href="{{ $studioConnectUrl }}" class="inline-flex items-center gap-2 text-sm font-semibold text-primary border border-primary/20 px-4 py-2 rounded-xl hover:bg-primary/5 transition-colors">
                    <span class="material-symbols-outlined text-base">link</span> Connect Studio Stripe
                  </a>
                @endif
              </div>
            </div>

            <div class="rounded-xl border border-error/25 bg-error/5 p-4">
              <p class="text-sm text-error">If you unlink this studio, no payout method will be integrated until you connect another payout option.</p>
              <button type="button" id="disconnectStudioBtn" class="mt-3 text-sm font-semibold text-error hover:text-on-error-container border border-error/20 px-4 py-2 rounded-xl hover:bg-error-container/30 transition-colors">
                Disconnect Studio
              </button>
            </div>
          @endif
        </div>
        <p id="studio_email_error" class="text-error text-sm mt-3 hidden"></p>
      </div>
    </div>

    <!-- Inkjin: Bank Details -->
    <div id="payout-inkjin" class="{{ $payoutKey !== 'inkjin' ? 'hidden' : '' }}">
      <div class="bg-surface-container-low rounded-2xl p-6">
        <h3 class="text-lg font-bold text-on-surface mb-6">Bank Account Details</h3>

        <!-- Holder Name + Bank Name -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
          <div>
            <label for="holder_name" class="block text-sm font-semibold text-on-surface mb-2">Account Holder Full Name</label>
            <input type="text" id="holder_name" name="account_holder_name" value="{{ old('account_holder_name', $bank->account_holder_name ?? '') }}" class="w-full text-sm border border-outline-variant/30 rounded-xl px-4 py-3 bg-white text-on-surface focus:outline-none focus:ring-2 focus:ring-primary/30">
            <p class="text-[11px] uppercase tracking-wider font-semibold text-on-surface-variant mt-1.5">Must match legal identification</p>
            <p id="account_holder_name_error" class="text-error text-xs mt-1 hidden"></p>
          </div>
          <div>
            <label for="bank_name" class="block text-sm font-semibold text-on-surface mb-2">Bank Name</label>
            <input type="text" id="bank_name" name="bank_name" value="{{ old('bank_name', $bank->bank_name ?? '') }}" class="w-full text-sm border border-outline-variant/30 rounded-xl px-4 py-3 bg-white text-on-surface focus:outline-none focus:ring-2 focus:ring-primary/30">
            <p id="bank_name_error" class="text-error text-xs mt-1 hidden"></p>
          </div>
        </div>

        <!-- IBAN + SWIFT -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
          <div>
            <label for="iban" class="block text-sm font-semibold text-on-surface mb-2">Account Number / IBAN</label>
            <input type="text" id="iban" name="account_number" value="{{ old('account_number', $bank->account_number ?? '') }}" class="w-full text-sm border border-outline-variant/30 rounded-xl px-4 py-3 bg-white text-on-surface focus:outline-none focus:ring-2 focus:ring-primary/30">
            <p id="account_number_error" class="text-error text-xs mt-1 hidden"></p>
          </div>
          <div>
            <label for="swift" class="block text-sm font-semibold text-on-surface mb-2">SWIFT / BIC</label>
            <input type="text" id="swift" name="swift_bic" value="{{ old('swift_bic', $bank->swift_bic ?? '') }}" class="w-full text-sm border border-outline-variant/30 rounded-xl px-4 py-3 bg-white text-on-surface focus:outline-none focus:ring-2 focus:ring-primary/30">
            <p id="swift_bic_error" class="text-error text-xs mt-1 hidden"></p>
          </div>
        </div>

        <!-- Currency -->
        <div>
          <label for="bank_currency" class="block text-sm font-semibold text-on-surface mb-2">Currency</label>
          <select id="inkjin_currency" name="currency" class="js-select2 w-full text-sm border border-outline-variant/30 rounded-xl px-4 py-3 bg-white text-on-surface cursor-pointer focus:outline-none focus:ring-2 focus:ring-primary/30" data-selected="{{ old('currency', $bank->bank_currency ?? $ud->currency ?? 'USD') }}"></select>
          <p id="currency_error" class="text-error text-xs mt-1 hidden"></p>
        </div>
      </div>
    </div>
  </div>

  <!-- Footer: Save Changes -->
  <div class="sticky bottom-0 bg-surface border-t border-outline-variant/10 px-6 md:px-10 lg:px-12 py-5 flex items-center justify-end">
    <button type="submit" id="savePaymentBtn" class="inline-flex items-center gap-2 bg-gradient-to-br from-primary to-primary-container text-white font-bold py-3 px-8 rounded-xl shadow-lg shadow-primary/20 hover:opacity-90 transition-all active:scale-[0.98]">
      <span class="material-symbols-outlined text-lg">save</span> Save Changes
    </button>
  </div>
  </form>
</main>

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

<div id="disconnectStudioModal" class="hidden fixed inset-0 z-[200] flex items-center justify-center p-4 bg-black/50" role="dialog" aria-modal="true">
  <div class="bg-white rounded-2xl max-w-md w-full p-6 shadow-xl">
    <h5 class="text-lg font-bold text-on-surface mb-2">Disconnect studio payouts?</h5>
    <p class="text-on-surface-variant text-sm mb-6">If you unlink this, no payment method will be integrated until you configure another payout option.</p>
    <div class="flex justify-end gap-3">
      <button type="button" id="cancelDisconnectStudio" class="rounded-xl px-5 py-2.5 text-sm font-semibold text-on-surface hover:bg-surface-container-low">Cancel</button>
      <button type="button" id="confirmDisconnectStudioBtn" class="rounded-xl px-5 py-2.5 text-sm font-semibold bg-error text-white hover:opacity-90">Disconnect</button>
    </div>
  </div>
</div>
@endsection

@section('scripts')
<script src="{{ asset('design/js/currencies.js') }}"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script>
  function otherPayoutOptionsLocked() {
    return $('#payment_type').val() === 'artist_account' && !!String($('#stripe_account_id').val() || '').trim();
  }
  function selectPayout(type, el) {
    if ((type === 'studio' || type === 'inkjin') && otherPayoutOptionsLocked()) return;
    document.querySelectorAll('.payout-card').forEach(c => c.classList.remove('selected'));
    el.classList.add('selected');
    var map = { artist: 'artist_account', studio: 'studio_account', inkjin: 'inkjin_account' };
    $('#payment_type').val(map[type]);
    if (type !== 'artist') {
      // Never submit studio-linked Stripe ID as artist Stripe ID.
      $('#stripe_account_id').val('');
    }
    $('#payout-artist').toggleClass('hidden', type !== 'artist');
    $('#payout-studio').toggleClass('hidden', type !== 'studio');
    $('#payout-inkjin').toggleClass('hidden', type !== 'inkjin');
    $('#payment_type_error').text('').addClass('hidden');
    $('#payAlert').addClass('hidden').text('');
  }
  $(function () {
    var cur = document.getElementById('inkjin_currency');
    if (cur && typeof fillCurrencySelect === 'function') fillCurrencySelect(cur, cur.getAttribute('data-selected') || 'USD');
    if (window.jQuery && $.fn.select2) {
      $('#inkjin_currency.js-select2').select2({ width: '100%', dropdownParent: $('body') });
    }

    $('#connectStripeBtn').on('click', function () {
      window.location.href = @json(route('connect.stripe'));
    });
    function openStripeModal() { $('#disconnectStripeModal').removeClass('hidden'); }
    function closeStripeModal() { $('#disconnectStripeModal').addClass('hidden'); }
    $('#disconnectStripeBtn').on('click', openStripeModal);
    $('#cancelDisconnectStripe').on('click', closeStripeModal);
    $('#disconnectStripeModal').on('click', function (e) { if (e.target === this) closeStripeModal(); });
    $('#confirmDisconnectStripeBtn').on('click', function () {
      closeStripeModal();
      $.ajax({
        url: @json(route('connect.stripe.disconnect')),
        type: 'POST',
        data: { _token: @json(csrf_token()) },
        headers: { 'X-CSRF-TOKEN': @json(csrf_token()), Accept: 'application/json' },
      }).done(function (data) {
        if (data.success) window.location.reload();
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

    $('#paymentForm').on('submit', function (e) {
      e.preventDefault();
      $('#paymentForm').find('[id$="_error"]').addClass('hidden').text('');
      $('#payAlert').addClass('hidden').text('');
      var $btn = $('#savePaymentBtn');
      var original = $btn.html();
      $btn.prop('disabled', true).html('<span class="material-symbols-outlined text-lg">hourglass_top</span> Saving...');
      var fd = new FormData(this);
      $.ajax({
        url: @json(route('settings.payment.update')),
        type: 'POST',
        data: fd,
        processData: false,
        contentType: false,
        headers: { 'X-CSRF-TOKEN': @json(csrf_token()), Accept: 'application/json' },
      }).done(function (data) {
        if (data.success) {
          $('#payAlert').attr('class', 'rounded-xl px-4 py-3 text-sm mb-6 bg-green-50 text-green-800 border border-green-200').text(data.message || 'Payment settings updated successfully.').removeClass('hidden');
          return;
        }
        $('#payAlert').attr('class', 'rounded-xl px-4 py-3 text-sm mb-6 bg-red-50 text-red-800 border border-red-200').text(data.message || 'Could not save payment settings.').removeClass('hidden');
      }).fail(function (xhr) {
        if (xhr.status === 422 && xhr.responseJSON && xhr.responseJSON.errors) {
          $.each(xhr.responseJSON.errors, function (k, msgs) {
            $('#' + k + '_error').text(msgs[0]).removeClass('hidden');
          });
        } else {
          $('#payAlert').attr('class', 'rounded-xl px-4 py-3 text-sm mb-6 bg-red-50 text-red-800 border border-red-200').text((xhr.responseJSON && xhr.responseJSON.message) || 'Network error.').removeClass('hidden');
        }
      }).always(function () {
        $btn.prop('disabled', false).html(original);
      });
    });
  });
</script>
@endsection

