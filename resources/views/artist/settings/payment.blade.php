@extends('layouts.dashboard_layout')

@section('title', 'Payment Settings')

@section('content')
@php
  $currentPaymentType = $userDetail->payment_type ?? '';
  $artistStripeId = ($currentPaymentType === 'artist_account') ? ($userDetail->stripe_account_id ?? '') : '';
  $studioLocked = ($currentPaymentType === 'studio_account' && !empty($userDetail->studio_email));
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
                        value="{{ old('studio_email', $userDetail->studio_email ?? '') }}"
                        placeholder="Enter studio email address"
                        {{ $studioLocked ? 'readonly' : '' }}
                      >
                      @if($studioLocked)
                        <small class="text-muted">Studio email is locked because your payments are already linked to this studio.</small>
                      @else
                        <small class="text-muted">We’ll send an email to connect Stripe</small>
                      @endif
                      @error('studio_email')
                        <div class="invalid-feedback">{{ $message }}</div>
                      @enderror
                    </div>

                    @if(($userDetail->payment_type ?? '') === 'studio_account')
                      <div class="d-flex flex-wrap gap-2 align-items-center">
                        <span class="badge bg-{{ ($userDetail->studio_payment_status ?? 'pending') === 'approved' ? 'success' : (($userDetail->studio_payment_status ?? 'pending') === 'declined' ? 'danger' : 'warning') }}">
                          Status: {{ ucfirst($userDetail->studio_payment_status ?? 'pending') }}
                        </span>
                        @if(!empty($userDetail->studio_email))
                          <button type="button" class="btn btn-outline-secondary btn-sm" id="resendStudioInviteBtn">
                            <i class="ti ti-mail-forward me-1"></i> Resend invite
                          </button>
                        @endif
                        @if(($userDetail->studio_payment_status ?? 'pending') !== 'approved')
                          <a class="btn btn-outline-primary btn-sm" href="{{ route('studio.waiting') }}">
                            <i class="ti ti-clock me-1"></i> View waiting page
                          </a>
                        @endif
                      </div>
                    @endif
                  </div>
                </div>
              </div>

              <!-- Inkjin Account - Info -->
              <div class="col-12" id="inkjin_section" style="display: none;">
                <div class="card">
                  <div class="card-body">
                    <div class="alert alert-info mb-0">
                      <i class="ti ti-info-circle me-2"></i>
                      Payments will be processed by Inkjin and paid out to you off-platform / via manual process.
                    </div>
                  </div>
                </div>
              </div>
            </div>

            <div class="d-flex justify-content-end mt-4">
              <button type="submit" class="btn btn-primary">
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
</div>

@push('scripts')
<script>
  const ARTIST_STRIPE_ID = @json($artistStripeId);

  function handlePaymentTypeChange() {
    const selected = document.querySelector('input[name=\"payment_type\"]:checked');
    const type = selected ? selected.value : '';

    const artistSection = document.getElementById('artist_stripe_section');
    const studioSection = document.getElementById('studio_section');
    const inkjinSection = document.getElementById('inkjin_section');
    const stripeAccountIdEl = document.getElementById('stripe_account_id');
    const studioEmailEl = document.getElementById('studio_email');

    if (artistSection) artistSection.style.display = (type === 'artist_account') ? 'block' : 'none';
    if (studioSection) studioSection.style.display = (type === 'studio_account') ? 'block' : 'none';
    if (inkjinSection) inkjinSection.style.display = (type === 'inkjin_account') ? 'block' : 'none';

    // IMPORTANT:
    // `user_details.stripe_account_id` may contain a studio's Stripe ID when payment_type is studio_account.
    // To avoid showing "connected" for Artist when the artist hasn't connected their own Stripe,
    // we only submit an artist Stripe ID when the saved payment type is artist_account.
    if (stripeAccountIdEl) {
      stripeAccountIdEl.value = (type === 'artist_account') ? (ARTIST_STRIPE_ID || '') : '';
    }

    // If studio is locked, keep it read-only even if user selects studio again
    if (studioEmailEl && studioEmailEl.hasAttribute('readonly')) {
      studioEmailEl.readOnly = true;
    }
  }

  // init on load
  document.addEventListener('DOMContentLoaded', () => {
    handlePaymentTypeChange();
  });

  // Stripe connect
  document.getElementById('connectStripeBtn')?.addEventListener('click', () => {
    window.location.href = '{{ route("connect.stripe") }}';
  });

  // Stripe disconnect (modal)
  const disconnectStripeBtn = document.getElementById('disconnectStripeBtn');
  const disconnectStripeModal = document.getElementById('disconnectStripeModal');
  const confirmStripeDisconnectBtn = document.getElementById('confirmStripeDisconnectBtn');

  if (disconnectStripeBtn) {
    disconnectStripeBtn.addEventListener('click', () => {
      const modal = new bootstrap.Modal(disconnectStripeModal);
      modal.show();
    });
  }

  if (confirmStripeDisconnectBtn) {
    confirmStripeDisconnectBtn.addEventListener('click', async () => {
      const modal = bootstrap.Modal.getInstance(disconnectStripeModal);
      modal.hide();

      try {
        const resp = await fetch('{{ route("connect.stripe.disconnect") }}', {
          method: 'POST',
          headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || document.querySelector('input[name="_token"]').value,
            'Content-Type': 'application/json',
          }
        });
        const data = await resp.json();
        if (data.success) {
          window.location.reload();
        } else {
          alert(data.message || 'Failed to disconnect Stripe');
        }
      } catch (e) {
        alert('An error occurred while disconnecting Stripe.');
      }
    });
  }

  // Resend studio invite
  document.getElementById('resendStudioInviteBtn')?.addEventListener('click', async () => {
    const btn = document.getElementById('resendStudioInviteBtn');
    const original = btn.innerHTML;
    btn.disabled = true;
    btn.innerHTML = '<span class=\"spinner-border spinner-border-sm me-2\"></span>Sending...';

    try {
      const resp = await fetch('{{ route("studio.resend-invite") }}', {
        method: 'POST',
        headers: {
          'X-CSRF-TOKEN': document.querySelector('meta[name=\"csrf-token\"]').content,
          'Content-Type': 'application/json',
          'Accept': 'application/json'
        }
      });
      const data = await resp.json();
      if (data.success) {
        alert(data.message || 'Invite resent successfully');
      } else {
        alert(data.message || 'Failed to resend invite');
      }
    } catch (e) {
      alert('Failed to resend invite');
    } finally {
      btn.disabled = false;
      btn.innerHTML = original;
    }
  });
</script>
@endpush

@endsection

