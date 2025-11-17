@extends('layouts.onboarding_layout')

@section('title', 'Complete Your Onboarding')

@push('styles')
<style>
  .step-item {
    position: relative;
    flex: 1;
  }
  
  .step-item:not(:last-child)::after {
    content: '';
    position: absolute;
    top: 20px;
    left: 50%;
    width: 100%;
    height: 2px;
    background-color: #e0e0e0;
    z-index: 0;
  }
  
  .step-item.active:not(:last-child)::after {
    background-color: var(--bs-primary);
  }
  
  .step-item.completed:not(:last-child)::after {
    background-color: var(--bs-success);
  }
  
  .step-number {
    position: relative;
    z-index: 1;
    width: 40px;
    height: 40px;
    border-radius: 50%;
    background-color: #e0e0e0;
    color: #666;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 600;
    margin: 0 auto 8px;
    transition: all 0.3s;
  }
  
  .step-item.active .step-number {
    background-color: var(--bs-primary);
    color: #fff;
  }
  
  .step-item.completed .step-number {
    background-color: var(--bs-success);
    color: #fff;
  }
  
  .step-item.disabled .step-number {
    opacity: 0.5;
    cursor: not-allowed;
  }
  
  .step-content {
    display: none;
  }
  
  .step-content.active {
    display: block;
  }
  
  .avatar-preview {
    width: 120px;
    height: 120px;
    border-radius: 50%;
    object-fit: cover;
    border: 3px solid #e0e0e0;
  }

  .dropify-wrapper .dropify-message p {
      font-size: 18px !important;
    }
</style>

<!-- Dropify CSS -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/dropify@0.2.2/dist/css/dropify.min.css">

<!-- Select2 CSS -->
<link rel="stylesheet" href="{{ asset('assets/vendor/libs/select2/select2.css') }}" />
@endpush

@section('content')
<div class="row justify-content-center">
  <div class="col-lg-10 col-xl-9">
    <div class="card">
        <div class="card-header">
          <h4 class="card-title mb-0">Complete Your Onboarding</h4>
          <p class="text-muted mb-0">Let's set up your profile step by step</p>
        </div>
        <div class="card-body">
          <!-- Progress Stepper -->
          <div class="d-flex justify-content-between mb-5" id="stepper">
            <div class="step-item {{ $currentStep >= 1 ? ($completedSteps && in_array(1, $completedSteps) ? 'completed' : 'active') : 'disabled' }}" data-step="1">
              <div class="step-number">
                @if($completedSteps && in_array(1, $completedSteps))
                  <i class="ti ti-check"></i>
                @else
                  1
                @endif
              </div>
              <div class="text-center">
                <small class="d-block fw-semibold">Complete Profile</small>
              </div>
            </div>
            
            <div class="step-item {{ $currentStep >= 2 ? ($completedSteps && in_array(2, $completedSteps) ? 'completed' : ($currentStep == 2 ? 'active' : '')) : 'disabled' }}" data-step="2">
              <div class="step-number">
                @if($completedSteps && in_array(2, $completedSteps))
                  <i class="ti ti-check"></i>
                @else
                  2
                @endif
              </div>
              <div class="text-center">
                <small class="d-block fw-semibold">Studio Info</small>
              </div>
            </div>
            
            <div class="step-item {{ $currentStep >= 3 ? ($completedSteps && in_array(3, $completedSteps) ? 'completed' : ($currentStep == 3 ? 'active' : '')) : 'disabled' }}" data-step="3">
              <div class="step-number">
                @if($completedSteps && in_array(3, $completedSteps))
                  <i class="ti ti-check"></i>
                @else
                  3
                @endif
              </div>
              <div class="text-center">
                <small class="d-block fw-semibold">Calendar</small>
                <small class="text-muted">(Optional)</small>
              </div>
            </div>
            
            <div class="step-item {{ $currentStep >= 4 ? ($completedSteps && in_array(4, $completedSteps) ? 'completed' : ($currentStep == 4 ? 'active' : '')) : 'disabled' }}" data-step="4">
              <div class="step-number">
                @if($completedSteps && in_array(4, $completedSteps))
                  <i class="ti ti-check"></i>
                @else
                  4
                @endif
              </div>
              <div class="text-center">
                <small class="d-block fw-semibold">Preferences</small>
              </div>
            </div>
            
            <div class="step-item {{ $currentStep >= 5 ? ($completedSteps && in_array(5, $completedSteps) ? 'completed' : ($currentStep == 5 ? 'active' : '')) : 'disabled' }}" data-step="5">
              <div class="step-number">
                @if($completedSteps && in_array(5, $completedSteps))
                  <i class="ti ti-check"></i>
                @else
                  5
                @endif
              </div>
              <div class="text-center">
                <small class="d-block fw-semibold">Payments</small>
              </div>
            </div>
          </div>

          <!-- Alert Messages -->
          <div id="alertContainer">
            @if(session('success'))
              <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="ti ti-check-circle me-2"></i>
                {{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
              </div>
            @endif
            @if(session('error'))
              <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="ti ti-alert-circle me-2"></i>
                {{ session('error') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
              </div>
            @endif
          </div>

          <!-- Step 1: Complete Profile -->
          <div class="step-content {{ $currentStep == 1 ? 'active' : '' }}" id="step1">
            <h5 class="mb-4">Complete Profile</h5>
            <form id="step1Form" enctype="multipart/form-data">
              @csrf
              <div class="row g-3">
                <div class="col-md-6 mb-3">
                  <label class="form-label">Profile Avatar <span class="text-danger">*</span></label>
                  <input type="file" class="dropify" id="avatar" name="avatar" data-allowed-file-extensions="jpg jpeg png heif heic" data-max-file-size="2M" data-show-errors="true" data-height="200" data-default-file="{{ $userDetail->avatar ? asset($userDetail->avatar) : '' }}">
                  <small class="text-muted d-block mt-2">Recommended: 400x400, Max 2MB (JPG, PNG, HEIF, HEIC)</small>
                  <p class="text-danger mt-1 mb-0" id="avatar_error" style="display: none; font-size: 0.875rem;"></p>
                </div>
                <div class="col-12"></div>
                
                <div class="col-md-6">
                  <label for="user_name" class="form-label">User Name <span class="text-danger">*</span></label>
                  <input type="text" class="form-control" id="user_name" name="user_name" value="{{ $userDetail->user_name ?? '' }}" placeholder="Enter your name">
                  <p class="text-danger mt-1 mb-0" id="user_name_error" style="display: none; font-size: 0.875rem;"></p>
                </div>
                
                <div class="col-md-6">
                  <label for="mobile_number" class="form-label">Mobile Number <span class="text-danger">*</span></label>
                  <input type="text" class="form-control" id="mobile_number" name="mobile_number" value="{{ $userDetail->mobile_number ?? '' }}" placeholder="Enter mobile number">
                  <p class="text-danger mt-1 mb-0" id="mobile_number_error" style="display: none; font-size: 0.875rem;"></p>
                </div>
                
                <div class="col-md-6">
                  <label for="country" class="form-label">Country <span class="text-danger">*</span></label>
                  <select class="form-select select2" id="country" name="country" data-placeholder="Search and select country">
                    <option value=""></option>
                  </select>
                  <p class="text-danger mt-1 mb-0" id="country_error" style="display: none; font-size: 0.875rem;"></p>
                </div>
                
                <div class="col-md-6">
                  <label for="city" class="form-label">City <span class="text-danger">*</span></label>
                  <select class="form-select select2" id="city" name="city" data-placeholder="Search and select city" disabled>
                    <option value=""></option>
                  </select>
                  <p class="text-danger mt-1 mb-0" id="city_error" style="display: none; font-size: 0.875rem;"></p>
                </div>
              </div>
              
              <div class="d-flex justify-content-end mt-4">
                <button type="submit" class="btn btn-primary">
                  Next Step <i class="ti ti-arrow-right ms-2"></i>
                </button>
              </div>
            </form>
          </div>

          <!-- Step 2: Studio Information -->
          <div class="step-content {{ $currentStep == 2 ? 'active' : '' }}" id="step2">
            <h5 class="mb-4">Studio Information</h5>
            <form id="step2Form">
              @csrf
              <div class="row g-3">
                 <div class="col-12">
                   <label for="studio_name" class="form-label">Studio Name <span class="text-danger">*</span></label>
                   <input type="text" class="form-control" id="studio_name" name="studio_name" value="{{ $userDetail->studio_name ?? '' }}">
                   <p class="text-danger mt-1 mb-0" id="studio_name_error" style="display: none; font-size: 0.875rem;"></p>
                 </div>
                 
                 <div class="col-12">
                   <label for="studio_address" class="form-label">Studio Address <span class="text-danger">*</span></label>
                   <textarea class="form-control" id="studio_address" name="studio_address" rows="3">{{ $userDetail->studio_address ?? '' }}</textarea>
                   <p class="text-danger mt-1 mb-0" id="studio_address_error" style="display: none; font-size: 0.875rem;"></p>
                 </div>
                 
                 <div class="col-12">
                   <label for="google_maps_link" class="form-label">Google Maps Link</label>
                   <input type="text" class="form-control" id="google_maps_link" name="google_maps_link" value="{{ $userDetail->google_maps_link ?? '' }}" placeholder="https://maps.google.com/...">
                   <small class="text-muted">Optional: Add your studio's Google Maps link</small>
                   <p class="text-danger mt-1 mb-0" id="google_maps_link_error" style="display: none; font-size: 0.875rem;"></p>
                 </div>
              </div>
              
              <div class="d-flex justify-content-between mt-4">
                <button type="button" class="btn btn-label-secondary" onclick="goToStep(1)">
                  <i class="ti ti-arrow-left me-2"></i> Previous
                </button>
                <button type="submit" class="btn btn-primary">
                  Next Step <i class="ti ti-arrow-right ms-2"></i>
                </button>
              </div>
            </form>
          </div>

          <!-- Step 3: Calendar Connection -->
          <div class="step-content {{ $currentStep == 3 ? 'active' : '' }}" id="step3">
            <h5 class="mb-4">Calendar Connection</h5>
            <form id="step3Form">
              @csrf
              <div class="row g-3">
                <div class="col-12">
                  <div class="card border-2 {{ ($userDetail->google_calendar_token ?? null) ? 'border-success' : 'border-dashed' }}">
                    <div class="card-body text-center py-5">
                      <i class="ti ti-calendar ti-3x {{ ($userDetail->google_calendar_token ?? null) ? 'text-success' : 'text-muted' }} mb-3"></i>
                      <h6 class="mb-2">Connect Your Google Calendar</h6>
                      <p class="text-muted mb-4">This step is optional. You can connect your calendar later.</p>
                      
                      @if($userDetail->google_calendar_token ?? null)
                        <div class="mb-3">
                          <span class="badge bg-success mb-3">
                            <i class="ti ti-check me-1"></i> Google Calendar Connected
                          </span>
                        </div>
                        <button type="button" class="btn btn-label-danger" id="disconnectCalendarBtn">
                          <i class="ti ti-unlink me-2"></i>
                          Disconnect Calendar
                        </button>
                      @else
                        <button type="button" class="btn btn-outline-primary" id="connectCalendarBtn">
                          <i class="ti ti-brand-google me-2"></i>
                          Connect Google Calendar
                        </button>
                      @endif
                      
                      <input type="hidden" name="google_calendar_connected" id="google_calendar_connected" value="{{ ($userDetail->google_calendar_token ?? null) ? '1' : '0' }}">
                    </div>
                  </div>
                </div>
              </div>
              
              <div class="d-flex justify-content-between mt-4">
                <button type="button" class="btn btn-label-secondary" onclick="goToStep(2)">
                  <i class="ti ti-arrow-left me-2"></i> Previous
                </button>
                <div>
                  <button type="button" class="btn btn-outline-secondary me-2" onclick="saveStep3(true)">
                    Skip for Now
                  </button>
                  <button type="submit" class="btn btn-primary">
                    Next Step <i class="ti ti-arrow-right ms-2"></i>
                  </button>
                </div>
              </div>
            </form>
          </div>

          <!-- Step 4: Preferences -->
          <div class="step-content {{ $currentStep == 4 ? 'active' : '' }}" id="step4">
            <h5 class="mb-4">Preferences</h5>
            <form id="step4Form">
              @csrf
              <div class="row g-3">
                <div class="col-md-6">
                  <label for="currency" class="form-label">Currency <span class="text-danger">*</span></label>
                   <select class="form-select select2" id="currency" name="currency" data-placeholder="Search and select currency">
                     <option value=""></option>
                   </select>
                   <p class="text-danger mt-1 mb-0" id="currency_error" style="display: none; font-size: 0.875rem;"></p>
                </div>
                
                <div class="col-md-6">
                  <label for="timezone" class="form-label">Timezone <span class="text-danger">*</span></label>
                   <select class="form-select select2" id="timezone" name="timezone" data-placeholder="Search and select timezone">
                     <option value=""></option>
                   </select>
                   <p class="text-danger mt-1 mb-0" id="timezone_error" style="display: none; font-size: 0.875rem;"></p>
                </div>
                
                <div class="col-md-6">
                  <label for="date_time_format" class="form-label">Date & Time Format <span class="text-danger">*</span></label>
                   <select class="form-select" id="date_time_format" name="date_time_format">
                     <option value="">Select Format</option>
                     <option value="MM/DD/YYYY" {{ ($userDetail->date_time_format ?? '') == 'MM/DD/YYYY' ? 'selected' : '' }}>MM/DD/YYYY</option>
                     <option value="DD/MM/YYYY" {{ ($userDetail->date_time_format ?? '') == 'DD/MM/YYYY' ? 'selected' : '' }}>DD/MM/YYYY</option>
                     <option value="YYYY-MM-DD" {{ ($userDetail->date_time_format ?? '') == 'YYYY-MM-DD' ? 'selected' : '' }}>YYYY-MM-DD</option>
                   </select>
                   <p class="text-danger mt-1 mb-0" id="date_time_format_error" style="display: none; font-size: 0.875rem;"></p>
                </div>
                
                <div class="col-md-6">
                   <label for="minimum_deposit_amount" class="form-label">Minimum Deposit Amount <span class="text-danger">*</span></label>
                   <input type="text" class="form-control" id="minimum_deposit_amount" name="minimum_deposit_amount" value="{{ $userDetail->minimum_deposit_amount ?? '' }}" placeholder="Enter amount">
                   <p class="text-danger mt-1 mb-0" id="minimum_deposit_amount_error" style="display: none; font-size: 0.875rem;"></p>
                </div>
                
                <div class="col-md-6">
                  <label for="minimum_deposit_type" class="form-label">Deposit Type <span class="text-danger">*</span></label>
                   <select class="form-select" id="minimum_deposit_type" name="minimum_deposit_type">
                     <option value="">Select Type</option>
                     <option value="fixed" {{ ($userDetail->minimum_deposit_type ?? '') == 'fixed' ? 'selected' : '' }}>Fixed Amount</option>
                     <option value="percentage" {{ ($userDetail->minimum_deposit_type ?? '') == 'percentage' ? 'selected' : '' }}>Percentage</option>
                   </select>
                   <p class="text-danger mt-1 mb-0" id="minimum_deposit_type_error" style="display: none; font-size: 0.875rem;"></p>
                </div>
                
                <div class="col-md-6">
                  <label for="cancellation_window" class="form-label">Cancellation Window <span class="text-danger">*</span></label>
                   <select class="form-select" id="cancellation_window" name="cancellation_window">
                     <option value="">Select Window</option>
                     <option value="24h" {{ ($userDetail->cancellation_window ?? '') == '24h' ? 'selected' : '' }}>24 Hours</option>
                     <option value="48h" {{ ($userDetail->cancellation_window ?? '') == '48h' ? 'selected' : '' }}>48 Hours</option>
                     <option value="72h" {{ ($userDetail->cancellation_window ?? '') == '72h' ? 'selected' : '' }}>72 Hours</option>
                     <option value="1w" {{ ($userDetail->cancellation_window ?? '') == '1w' ? 'selected' : '' }}>1 Week</option>
                   </select>
                   <p class="text-danger mt-1 mb-0" id="cancellation_window_error" style="display: none; font-size: 0.875rem;"></p>
                </div>
                
                <div class="col-md-6">
                  <label for="reschedule_times" class="form-label">Reschedule Times <span class="text-danger">*</span></label>
                   <select class="form-select" id="reschedule_times" name="reschedule_times">
                     <option value="">Select Option</option>
                     <option value="never" {{ ($userDetail->reschedule_times ?? '') == 'never' ? 'selected' : '' }}>Never</option>
                     <option value="once" {{ ($userDetail->reschedule_times ?? '') == 'once' ? 'selected' : '' }}>Once</option>
                     <option value="twice" {{ ($userDetail->reschedule_times ?? '') == 'twice' ? 'selected' : '' }}>Twice</option>
                     <option value="unlimited" {{ ($userDetail->reschedule_times ?? '') == 'unlimited' ? 'selected' : '' }}>Unlimited</option>
                   </select>
                   <p class="text-danger mt-1 mb-0" id="reschedule_times_error" style="display: none; font-size: 0.875rem;"></p>
                </div>
                
                <div class="col-md-6">
                  <label for="session_buffer_period" class="form-label">Session Buffer Period (minutes) <span class="text-danger">*</span></label>
                  <input type="number" class="form-control" id="session_buffer_period" name="session_buffer_period" value="{{ $userDetail->session_buffer_period ?? '' }}" placeholder="e.g., 15, 30, 60" min="0" step="1">
                  <small class="text-muted">Time between sessions for rest, clean up, or preparation</small>
                  <p class="text-danger mt-1 mb-0" id="session_buffer_period_error" style="display: none; font-size: 0.875rem;"></p>
                </div>
                
                <div class="col-md-6">
                  <label class="form-label">Require Consultation Session</label>
                  <div class="form-check form-switch">
                    <input class="form-check-input" type="checkbox" id="require_consultation" name="require_consultation" value="1" {{ ($userDetail->require_consultation ?? false) ? 'checked' : '' }}>
                    <label class="form-check-label" for="require_consultation">
                      Require consultation session when booking a tattoo
                    </label>
                  </div>
                  <small class="text-muted d-block mt-1">When enabled, clients must book a consultation before booking a tattoo session</small>
                  <p class="text-danger mt-1 mb-0" id="require_consultation_error" style="display: none; font-size: 0.875rem;"></p>
                </div>
              </div>
              
              <div class="d-flex justify-content-between mt-4">
                <button type="button" class="btn btn-label-secondary" onclick="goToStep(3)">
                  <i class="ti ti-arrow-left me-2"></i> Previous
                </button>
                <button type="submit" class="btn btn-primary">
                  Next Step <i class="ti ti-arrow-right ms-2"></i>
                </button>
              </div>
            </form>
          </div>

          <!-- Step 5: Payments -->
          <div class="step-content {{ $currentStep == 5 ? 'active' : '' }}" id="step5">
            <h5 class="mb-4">Payment Setup</h5>
            <form id="step5Form">
              @csrf
              <div class="row g-3">
                <div class="col-12">
                  <div class="card border-2 {{ ($userDetail->stripe_account_id ?? null) ? 'border-success' : 'border-dashed' }}">
                    <div class="card-body text-center py-5">
                      <i class="ti ti-credit-card ti-3x {{ ($userDetail->stripe_account_id ?? null) ? 'text-success' : 'text-muted' }} mb-3"></i>
                      <h6 class="mb-2">Connect Your Stripe Account</h6>
                      <p class="text-muted mb-4">Connect your Stripe account to receive payments. You can set this up later.</p>
                      
                      @if($userDetail->stripe_account_id ?? null)
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
                      
                      <input type="hidden" name="stripe_account_id" id="stripe_account_id" value="{{ $userDetail->stripe_account_id ?? '' }}">
                      <p class="text-danger mt-1 mb-0" id="stripe_account_id_error" style="display: none; font-size: 0.875rem;"></p>
                    </div>
                  </div>
                </div>
              </div>
              
              <div class="d-flex justify-content-between mt-4">
                <button type="button" class="btn btn-label-secondary" onclick="goToStep(4)">
                  <i class="ti ti-arrow-left me-2"></i> Previous
                </button>
                <div>
                  <button type="button" class="btn btn-outline-secondary me-2" onclick="saveStep5(true)">
                    Skip for Now
                  </button>
                  <button type="submit" class="btn btn-primary">
                    Complete Onboarding <i class="ti ti-check ms-2"></i>
                  </button>
                </div>
              </div>
            </form>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- Disconnect Calendar Confirmation Modal -->
<div class="modal fade" id="disconnectCalendarModal" tabindex="-1" aria-labelledby="disconnectCalendarModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="disconnectCalendarModalLabel">
          <i class="ti ti-alert-triangle text-warning me-2"></i>
          Disconnect Google Calendar
        </h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <p class="mb-0">Are you sure you want to disconnect your Google Calendar? You can reconnect it later if needed.</p>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-label-secondary" data-bs-dismiss="modal">
          Cancel
        </button>
        <button type="button" class="btn btn-danger" id="confirmDisconnectBtn">
          <i class="ti ti-unlink me-2"></i>
          Disconnect
        </button>
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
          Disconnect Stripe Account
        </h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <p class="mb-0">Are you sure you want to disconnect your Stripe account? You can reconnect it later if needed.</p>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-label-secondary" data-bs-dismiss="modal">
          Cancel
        </button>
        <button type="button" class="btn btn-danger" id="confirmDisconnectStripeBtn">
          <i class="ti ti-unlink me-2"></i>
          Disconnect
        </button>
      </div>
    </div>
  </div>
</div>
@endsection

@push('scripts')
<script>
  let currentStep = {{ $currentStep }};
  let completedSteps = @json($completedSteps ?? []);

  // Show alert function
  function showAlert(type, message) {
    const alertHtml = `
      <div class="alert alert-${type} alert-dismissible fade show" role="alert">
        <i class="ti ti-${type === 'success' ? 'check-circle' : 'alert-circle'} me-2"></i>
        ${message}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
      </div>
    `;
    document.getElementById('alertContainer').innerHTML = alertHtml;
    setTimeout(() => {
      const alert = document.querySelector('.alert');
      if (alert) alert.remove();
    }, 5000);
  }

  // Display validation errors
  function displayErrors(errors) {
    // Clear previous errors
    document.querySelectorAll('[id$="_error"]').forEach(el => {
      el.style.display = 'none';
      el.textContent = '';
    });
    document.querySelectorAll('.form-control, .form-select, input[type="file"]').forEach(el => {
      el.classList.remove('is-invalid', 'border-danger');
    });

    // Display new errors
    if (errors) {
      Object.keys(errors).forEach(field => {
        const input = document.getElementById(field);
        const errorDiv = document.getElementById(field + '_error');
        
        if (input) {
          input.classList.add('is-invalid', 'border-danger');
          // For Select2, also add error class to the container
          if ($(input).hasClass('select2-hidden-accessible')) {
            $(input).next('.select2-container').addClass('is-invalid');
          }
        }
        
        if (errorDiv) {
          errorDiv.style.display = 'block';
          errorDiv.textContent = Array.isArray(errors[field]) ? errors[field][0] : errors[field];
        }
      });
    }
  }

  // Clear all errors
  function clearErrors() {
    document.querySelectorAll('[id$="_error"]').forEach(el => {
      el.style.display = 'none';
      el.textContent = '';
    });
    document.querySelectorAll('.form-control, .form-select').forEach(el => {
      el.classList.remove('is-invalid', 'border-danger');
      // Clear Select2 error styling
      if ($(el).hasClass('select2-hidden-accessible')) {
        $(el).next('.select2-container').removeClass('is-invalid');
      }
    });
  }

  // Validate Step 1
  function validateStep1() {
    clearErrors();
    const errors = {};
    let isValid = true;

    // Avatar validation (required)
    const avatarInput = document.getElementById('avatar');
    if (!avatarInput) {
      errors.avatar = 'This field is required.';
      isValid = false;
    } else {
      // Check if file is selected (either new file or existing default file)
      const hasDefaultFile = $(avatarInput).data('default-file') && $(avatarInput).data('default-file') !== '';
      const hasNewFile = avatarInput.files && avatarInput.files.length > 0;
      
      if (!hasDefaultFile && !hasNewFile) {
        errors.avatar = 'This field is required.';
        isValid = false;
      } else if (hasNewFile) {
        // Validate new file if uploaded
        const file = avatarInput.files[0];
        const maxSize = 2 * 1024 * 1024; // 2MB
        const allowedTypes = ['image/jpeg', 'image/png', 'image/jpg', 'image/gif', 'image/heif', 'image/heic'];

        if (!allowedTypes.includes(file.type)) {
          errors.avatar = 'Avatar must be an image file (JPEG, PNG, JPG, HEIF, or HEIC).';
          isValid = false;
        }

        if (file.size > maxSize) {
          errors.avatar = 'Avatar file size must not exceed 2MB.';
          isValid = false;
        }
      }
    }

    // User name validation (required)
    const userName = document.getElementById('user_name').value.trim();
    if (!userName) {
      errors.user_name = 'This field is required.';
      isValid = false;
    }

    // Mobile number validation (required)
    const mobileNumber = document.getElementById('mobile_number').value.trim();
    if (!mobileNumber) {
      errors.mobile_number = 'This field is required.';
      isValid = false;
    }

    // Country validation (required)
    const country = document.getElementById('country');
    const countryValue = country ? (country.value || $(country).val() || '').trim() : '';
    if (!countryValue) {
      errors.country = 'This field is required.';
      isValid = false;
    }

    // City validation (required)
    const city = document.getElementById('city');
    const cityValue = city ? (city.value || $(city).val() || '').trim() : '';
    if (!cityValue) {
      errors.city = 'This field is required.';
      isValid = false;
    }

    if (!isValid) {
      displayErrors(errors);
    }

    return isValid;
  }

  // Validate Step 2
  function validateStep2() {
    clearErrors();
    const errors = {};
    let isValid = true;

    const studioName = document.getElementById('studio_name').value.trim();
    if (!studioName) {
      errors.studio_name = 'This field is required.';
      isValid = false;
    }

    const studioAddress = document.getElementById('studio_address').value.trim();
    if (!studioAddress) {
      errors.studio_address = 'This field is required.';
      isValid = false;
    }

    if (!isValid) {
      displayErrors(errors);
    }

    return isValid;
  }

  // Validate Step 3 (optional, no validation needed)
  function validateStep3() {
    return true;
  }

  // Validate Step 4
  function validateStep4() {
    clearErrors();
    const errors = {};
    let isValid = true;

    // Currency validation (required)
    const currency = document.getElementById('currency').value.trim();
    if (!currency) {
      errors.currency = 'This field is required.';
      isValid = false;
    }

    // Timezone validation (required)
    const timezone = document.getElementById('timezone').value.trim();
    if (!timezone) {
      errors.timezone = 'This field is required.';
      isValid = false;
    }

    // Date & Time Format validation (required)
    const dateTimeFormat = document.getElementById('date_time_format').value.trim();
    if (!dateTimeFormat) {
      errors.date_time_format = 'This field is required.';
      isValid = false;
    }

    // Minimum Deposit Amount validation (required)
    const minDepositAmount = document.getElementById('minimum_deposit_amount').value.trim();
    if (!minDepositAmount) {
      errors.minimum_deposit_amount = 'This field is required.';
      isValid = false;
    } else if (isNaN(minDepositAmount) || parseFloat(minDepositAmount) < 0) {
      errors.minimum_deposit_amount = 'Please enter a valid number (0 or greater).';
      isValid = false;
    }

    // Minimum Deposit Type validation (required)
    const minDepositType = document.getElementById('minimum_deposit_type').value.trim();
    if (!minDepositType) {
      errors.minimum_deposit_type = 'This field is required.';
      isValid = false;
    }

    // Cancellation Window validation (required)
    const cancellationWindow = document.getElementById('cancellation_window').value.trim();
    if (!cancellationWindow) {
      errors.cancellation_window = 'This field is required.';
      isValid = false;
    }

    // Reschedule Times validation (required)
    const rescheduleTimes = document.getElementById('reschedule_times').value.trim();
    if (!rescheduleTimes) {
      errors.reschedule_times = 'This field is required.';
      isValid = false;
    }

    if (!isValid) {
      displayErrors(errors);
    }

    return isValid;
  }

  // Validate Step 5 (optional, no validation needed)
  function validateStep5() {
    return true;
  }

  // Update stepper visual
  function updateStepper(activeStep) {
    document.querySelectorAll('.step-item').forEach((item, index) => {
      const stepNum = index + 1;
      item.classList.remove('active', 'completed', 'disabled');
      
      if (completedSteps.includes(stepNum)) {
        item.classList.add('completed');
        // Allow clicking on completed steps
        item.style.cursor = 'pointer';
      } else if (stepNum === activeStep) {
        item.classList.add('active');
      } else if (stepNum > activeStep) {
        // Check if we can access this step
        const prevStep = stepNum - 1;
        if (completedSteps.includes(prevStep)) {
          item.style.cursor = 'pointer';
        } else {
          item.classList.add('disabled');
          item.style.cursor = 'not-allowed';
        }
      } else {
        // Previous steps - always allow clicking
        item.style.cursor = 'pointer';
      }
    });
  }

  // Initialize Dropify
  function initDropify() {
    if ($('.dropify').length) {
      // Destroy existing instances if any
      $('.dropify').dropify('destroy');
      
      // Initialize Dropify
      $('.dropify').dropify({
        messages: {
          'default': 'Drag and drop an image here or click',
          'replace': 'Drag and drop or click to replace',
          'remove': 'Remove',
          'error': 'Ooops, something wrong happened.'
        },
        error: {
          'fileSize': 'The file size is too big (2MB max).',
          'fileExtension': 'The file extension is not allowed (jpg, jpeg, png, gif only).'
        }
      });
    }
  }

  // Initialize on page load
  $(document).ready(function() {
    initDropify();
  });

  // Re-initialize when navigating to step 3 or 4
  function goToStep(step) {
    if (step < 1 || step > 5) return;
    
    // Allow going to previous steps or already completed steps
    // For future steps, check if previous step is completed
    if (step > currentStep) {
      // Check if previous step is completed
      const prevStep = step - 1;
      if (!completedSteps.includes(prevStep)) {
        showAlert('warning', 'Please complete the previous step first.');
        return;
      }
    }

    // Clear errors when navigating
    clearErrors();

    // Hide all steps
    document.querySelectorAll('.step-content').forEach(el => el.classList.remove('active'));
    
    // Show selected step
    const targetStep = document.getElementById(`step${step}`);
    if (targetStep) {
      targetStep.classList.add('active');
      
      // Re-initialize Dropify and Country/City if navigating to step 1
      if (step === 1) {
        setTimeout(() => {
          initDropify();
          initializeCountryCity();
        }, 100);
      }
      
      // Re-initialize Select2 if navigating to step 4
      if (step === 4) {
        setTimeout(() => {
          if (typeof initializeSelect2 === 'function') {
            initializeSelect2();
          }
        }, 100);
      }
    }
    
    // Update current step variable - allow going back
    currentStep = step;
    
    // Update stepper
    updateStepper(step);
  }

  // Save Step 1
  document.getElementById('step1Form').addEventListener('submit', async (e) => {
    e.preventDefault();
    
    // Validate before submitting
    if (!validateStep1()) {
      return;
    }

    const formData = new FormData(e.target);
    const submitBtn = e.target.querySelector('button[type="submit"]');
    const originalText = submitBtn.innerHTML;
    submitBtn.disabled = true;
    submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Saving...';

    try {
      const response = await fetch('{{ route("onboarding.step1") }}', {
        method: 'POST',
        body: formData,
        headers: {
          'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || document.querySelector('input[name="_token"]').value
        }
      });

      const data = await response.json();
      
      if (data.success) {
        clearErrors();
        showAlert('success', data.message);
        if (data.avatar) {
          // Update Dropify with the new avatar
          const avatarInput = document.getElementById('avatar');
          if (avatarInput && $('.dropify').length) {
            $('.dropify').dropify('destroy');
            $(avatarInput).attr('data-default-file', data.avatar);
            initDropify();
          }
        }
        if (!completedSteps.includes(1)) {
          completedSteps.push(1);
        }
        currentStep = 2;
        setTimeout(() => goToStep(2), 1000);
      } else {
        if (data.errors) {
          displayErrors(data.errors);
        }
        showAlert('danger', data.message || 'Failed to save step 1');
      }
    } catch (error) {
      showAlert('danger', 'An error occurred. Please try again.');
    } finally {
      submitBtn.disabled = false;
      submitBtn.innerHTML = originalText;
    }
  });

  // Save Step 2
  document.getElementById('step2Form').addEventListener('submit', async (e) => {
    e.preventDefault();
    
    // Validate before submitting
    if (!validateStep2()) {
      return;
    }

    const formData = new FormData(e.target);
    const submitBtn = e.target.querySelector('button[type="submit"]');
    const originalText = submitBtn.innerHTML;
    submitBtn.disabled = true;
    submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Saving...';

    try {
      const response = await fetch('{{ route("onboarding.step2") }}', {
        method: 'POST',
        body: formData,
        headers: {
          'X-CSRF-TOKEN': document.querySelector('input[name="_token"]').value
        }
      });

      const data = await response.json();
      
      if (data.success) {
        clearErrors();
        showAlert('success', data.message);
        if (!completedSteps.includes(2)) {
          completedSteps.push(2);
        }
        currentStep = 3;
        setTimeout(() => goToStep(3), 1000);
      } else {
        if (data.errors) {
          displayErrors(data.errors);
        }
        showAlert('danger', data.message || 'Failed to save step 2');
      }
    } catch (error) {
      showAlert('danger', 'An error occurred. Please try again.');
    } finally {
      submitBtn.disabled = false;
      submitBtn.innerHTML = originalText;
    }
  });

  // Save Step 3
  async function saveStep3(skip = false) {
    clearErrors();
    const form = document.getElementById('step3Form');
    const formData = new FormData(form);
    if (skip) {
      formData.set('google_calendar_connected', '0');
    }

    const submitBtn = form.querySelector('button[type="submit"]');
    const originalText = submitBtn.innerHTML;
    submitBtn.disabled = true;
    submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Saving...';

    try {
      const response = await fetch('{{ route("onboarding.step3") }}', {
        method: 'POST',
        body: formData,
        headers: {
          'X-CSRF-TOKEN': document.querySelector('input[name="_token"]').value
        }
      });

      const data = await response.json();
      
      if (data.success) {
        clearErrors();
        showAlert('success', data.message);
        if (!completedSteps.includes(3)) {
          completedSteps.push(3);
        }
        currentStep = 4;
        setTimeout(() => goToStep(4), 1000);
      } else {
        if (data.errors) {
          displayErrors(data.errors);
        }
        showAlert('danger', data.message || 'Failed to save step 3');
      }
    } catch (error) {
      showAlert('danger', 'An error occurred. Please try again.');
    } finally {
      submitBtn.disabled = false;
      submitBtn.innerHTML = originalText;
    }
  }

  document.getElementById('step3Form').addEventListener('submit', (e) => {
    e.preventDefault();
    if (validateStep3()) {
      saveStep3(false);
    }
  });

  // Save Step 4
  document.getElementById('step4Form').addEventListener('submit', async (e) => {
    e.preventDefault();
    
    // Validate before submitting
    if (!validateStep4()) {
      return;
    }

    const formData = new FormData(e.target);
    const submitBtn = e.target.querySelector('button[type="submit"]');
    const originalText = submitBtn.innerHTML;
    submitBtn.disabled = true;
    submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Saving...';

    try {
      const response = await fetch('{{ route("onboarding.step4") }}', {
        method: 'POST',
        body: formData,
        headers: {
          'X-CSRF-TOKEN': document.querySelector('input[name="_token"]').value
        }
      });

      const data = await response.json();
      
      if (data.success) {
        clearErrors();
        showAlert('success', data.message);
        if (!completedSteps.includes(4)) {
          completedSteps.push(4);
        }
        currentStep = 5;
        setTimeout(() => goToStep(5), 1000);
      } else {
        if (data.errors) {
          displayErrors(data.errors);
        }
        showAlert('danger', data.message || 'Failed to save step 4');
      }
    } catch (error) {
      showAlert('danger', 'An error occurred. Please try again.');
    } finally {
      submitBtn.disabled = false;
      submitBtn.innerHTML = originalText;
    }
  });

  // Save Step 5
  async function saveStep5(skip = false) {
    clearErrors();
    const form = document.getElementById('step5Form');
    const formData = new FormData(form);
    if (skip && !formData.get('stripe_account_id')) {
      formData.set('stripe_account_id', '');
    }

    const submitBtn = form.querySelector('button[type="submit"]');
    const originalText = submitBtn.innerHTML;
    submitBtn.disabled = true;
    submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Completing...';

    try {
      const response = await fetch('{{ route("onboarding.step5") }}', {
        method: 'POST',
        body: formData,
        headers: {
          'X-CSRF-TOKEN': document.querySelector('input[name="_token"]').value
        }
      });

      const data = await response.json();
      
      if (data.success) {
        clearErrors();
        showAlert('success', data.message);
        if (data.redirect) {
          setTimeout(() => {
            window.location.href = data.redirect;
          }, 1500);
        }
      } else {
        if (data.errors) {
          displayErrors(data.errors);
        }
        showAlert('danger', data.message || 'Failed to complete onboarding');
      }
    } catch (error) {
      showAlert('danger', 'An error occurred. Please try again.');
    } finally {
      submitBtn.disabled = false;
      submitBtn.innerHTML = originalText;
    }
  }

  document.getElementById('step5Form').addEventListener('submit', (e) => {
    e.preventDefault();
    if (validateStep5()) {
      saveStep5(false);
    }
  });

  // Connect Calendar - Redirect to Google OAuth
  document.getElementById('connectCalendarBtn')?.addEventListener('click', () => {
    window.location.href = '{{ route("google.calendar.redirect") }}';
  });

  // Disconnect Calendar - Show Modal
  const disconnectCalendarBtn = document.getElementById('disconnectCalendarBtn');
  const disconnectCalendarModal = document.getElementById('disconnectCalendarModal');
  const confirmDisconnectBtn = document.getElementById('confirmDisconnectBtn');
  
  if (disconnectCalendarBtn) {
    disconnectCalendarBtn.addEventListener('click', () => {
      const modal = new bootstrap.Modal(disconnectCalendarModal);
      modal.show();
    });
  }

  // Confirm Disconnect from Modal
  if (confirmDisconnectBtn) {
    confirmDisconnectBtn.addEventListener('click', async () => {
      // Close modal
      const modal = bootstrap.Modal.getInstance(disconnectCalendarModal);
      modal.hide();

      const btn = document.getElementById('disconnectCalendarBtn');
      const originalText = btn.innerHTML;
      btn.disabled = true;
      btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Disconnecting...';

      try {
        const response = await fetch('{{ route("google.calendar.disconnect") }}', {
          method: 'POST',
          headers: {
            'X-CSRF-TOKEN': document.querySelector('input[name="_token"]').value,
            'Content-Type': 'application/json',
          }
        });

        const data = await response.json();
        
        if (data.success) {
          showAlert('success', 'Google Calendar disconnected successfully');
          // Reload page to update UI
          setTimeout(() => {
            window.location.reload();
          }, 1000);
        } else {
          showAlert('danger', data.message || 'Failed to disconnect Google Calendar');
          btn.disabled = false;
          btn.innerHTML = originalText;
        }
      } catch (error) {
        showAlert('danger', 'An error occurred while disconnecting. Please try again.');
        btn.disabled = false;
        btn.innerHTML = originalText;
      }
    });
  }

  // Connect Stripe - Redirect to Stripe Connect
  document.getElementById('connectStripeBtn')?.addEventListener('click', () => {
    window.location.href = '{{ route("connect.stripe") }}';
  });

  // Disconnect Stripe - Show Modal
  const disconnectStripeBtn = document.getElementById('disconnectStripeBtn');
  const disconnectStripeModal = document.getElementById('disconnectStripeModal');
  const confirmDisconnectStripeBtn = document.getElementById('confirmDisconnectStripeBtn');
  
  if (disconnectStripeBtn) {
    disconnectStripeBtn.addEventListener('click', () => {
      const modal = new bootstrap.Modal(disconnectStripeModal);
      modal.show();
    });
  }

  // Confirm Disconnect Stripe from Modal
  if (confirmDisconnectStripeBtn) {
    confirmDisconnectStripeBtn.addEventListener('click', async () => {
      // Close modal
      const modal = bootstrap.Modal.getInstance(disconnectStripeModal);
      modal.hide();

      const btn = document.getElementById('disconnectStripeBtn');
      const originalText = btn.innerHTML;
      btn.disabled = true;
      btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Disconnecting...';

      try {
        const response = await fetch('{{ route("connect.stripe.disconnect") }}', {
          method: 'POST',
          headers: {
            'X-CSRF-TOKEN': document.querySelector('input[name="_token"]').value,
            'Content-Type': 'application/json',
          }
        });

        const data = await response.json();
        
        if (data.success) {
          showAlert('success', 'Stripe account disconnected successfully');
          // Reload page to update UI
          setTimeout(() => {
            window.location.reload();
          }, 1000);
        } else {
          showAlert('danger', data.message || 'Failed to disconnect Stripe account');
          btn.disabled = false;
          btn.innerHTML = originalText;
        }
      } catch (error) {
        showAlert('danger', 'An error occurred while disconnecting. Please try again.');
        btn.disabled = false;
        btn.innerHTML = originalText;
      }
    });
  }

  // Add click handlers to stepper items
  document.querySelectorAll('.step-item').forEach((item, index) => {
    const stepNum = index + 1;
    item.addEventListener('click', () => {
      // Allow clicking on completed steps or accessible future steps
      const prevStep = stepNum - 1;
      if (stepNum <= currentStep || (prevStep > 0 && completedSteps.includes(prevStep))) {
        goToStep(stepNum);
      }
    });
  });

  // Initialize stepper on page load
  updateStepper(currentStep);
</script>

<!-- Dropify JS -->
<script src="https://cdn.jsdelivr.net/npm/dropify@0.2.2/dist/js/dropify.min.js"></script>

<!-- Select2 JS -->
<script src="{{ asset('assets/vendor/libs/select2/select2.js') }}"></script>

<script>
  // All world currencies with their symbols and names
  const allCurrencies = [
    { code: 'USD', name: 'United States Dollar', symbol: '$' },
    { code: 'EUR', name: 'Euro', symbol: '€' },
    { code: 'GBP', name: 'British Pound Sterling', symbol: '£' },
    { code: 'JPY', name: 'Japanese Yen', symbol: '¥' },
    { code: 'AUD', name: 'Australian Dollar', symbol: 'A$' },
    { code: 'CAD', name: 'Canadian Dollar', symbol: 'C$' },
    { code: 'CHF', name: 'Swiss Franc', symbol: 'Fr' },
    { code: 'CNY', name: 'Chinese Yuan', symbol: '¥' },
    { code: 'INR', name: 'Indian Rupee', symbol: '₹' },
    { code: 'BRL', name: 'Brazilian Real', symbol: 'R$' },
    { code: 'ZAR', name: 'South African Rand', symbol: 'R' },
    { code: 'SGD', name: 'Singapore Dollar', symbol: 'S$' },
    { code: 'HKD', name: 'Hong Kong Dollar', symbol: 'HK$' },
    { code: 'NZD', name: 'New Zealand Dollar', symbol: 'NZ$' },
    { code: 'MXN', name: 'Mexican Peso', symbol: '$' },
    { code: 'KRW', name: 'South Korean Won', symbol: '₩' },
    { code: 'RUB', name: 'Russian Ruble', symbol: '₽' },
    { code: 'TRY', name: 'Turkish Lira', symbol: '₺' },
    { code: 'SEK', name: 'Swedish Krona', symbol: 'kr' },
    { code: 'NOK', name: 'Norwegian Krone', symbol: 'kr' },
    { code: 'DKK', name: 'Danish Krone', symbol: 'kr' },
    { code: 'PLN', name: 'Polish Zloty', symbol: 'zł' },
    { code: 'THB', name: 'Thai Baht', symbol: '฿' },
    { code: 'IDR', name: 'Indonesian Rupiah', symbol: 'Rp' },
    { code: 'MYR', name: 'Malaysian Ringgit', symbol: 'RM' },
    { code: 'PHP', name: 'Philippine Peso', symbol: '₱' },
    { code: 'VND', name: 'Vietnamese Dong', symbol: '₫' },
    { code: 'AED', name: 'United Arab Emirates Dirham', symbol: 'د.إ' },
    { code: 'SAR', name: 'Saudi Riyal', symbol: '﷼' },
    { code: 'ILS', name: 'Israeli New Shekel', symbol: '₪' },
    { code: 'EGP', name: 'Egyptian Pound', symbol: 'E£' },
    { code: 'ARS', name: 'Argentine Peso', symbol: '$' },
    { code: 'CLP', name: 'Chilean Peso', symbol: '$' },
    { code: 'COP', name: 'Colombian Peso', symbol: '$' },
    { code: 'PEN', name: 'Peruvian Sol', symbol: 'S/' },
    { code: 'CZK', name: 'Czech Koruna', symbol: 'Kč' },
    { code: 'HUF', name: 'Hungarian Forint', symbol: 'Ft' },
    { code: 'RON', name: 'Romanian Leu', symbol: 'lei' },
    { code: 'BGN', name: 'Bulgarian Lev', symbol: 'лв' },
    { code: 'HRK', name: 'Croatian Kuna', symbol: 'kn' },
    { code: 'UAH', name: 'Ukrainian Hryvnia', symbol: '₴' },
    { code: 'PKR', name: 'Pakistani Rupee', symbol: '₨' },
    { code: 'BDT', name: 'Bangladeshi Taka', symbol: '৳' },
    { code: 'LKR', name: 'Sri Lankan Rupee', symbol: '₨' },
    { code: 'NPR', name: 'Nepalese Rupee', symbol: '₨' },
    { code: 'MMK', name: 'Myanmar Kyat', symbol: 'K' },
    { code: 'KZT', name: 'Kazakhstani Tenge', symbol: '₸' },
    { code: 'UZS', name: 'Uzbekistani Som', symbol: 'лв' },
    { code: 'KWD', name: 'Kuwaiti Dinar', symbol: 'د.ك' },
    { code: 'QAR', name: 'Qatari Riyal', symbol: '﷼' },
    { code: 'OMR', name: 'Omani Rial', symbol: '﷼' },
    { code: 'BHD', name: 'Bahraini Dinar', symbol: '.د.ب' },
    { code: 'JOD', name: 'Jordanian Dinar', symbol: 'د.ا' },
    { code: 'LBP', name: 'Lebanese Pound', symbol: '£' },
    { code: 'NGN', name: 'Nigerian Naira', symbol: '₦' },
    { code: 'KES', name: 'Kenyan Shilling', symbol: 'KSh' },
    { code: 'UGX', name: 'Ugandan Shilling', symbol: 'USh' },
    { code: 'TZS', name: 'Tanzanian Shilling', symbol: 'TSh' },
    { code: 'ETB', name: 'Ethiopian Birr', symbol: 'Br' },
    { code: 'GHS', name: 'Ghanaian Cedi', symbol: '₵' },
    { code: 'XAF', name: 'Central African CFA Franc', symbol: 'Fr' },
    { code: 'XOF', name: 'West African CFA Franc', symbol: 'Fr' },
    { code: 'MAD', name: 'Moroccan Dirham', symbol: 'د.م.' },
    { code: 'TND', name: 'Tunisian Dinar', symbol: 'د.ت' },
    { code: 'DZD', name: 'Algerian Dinar', symbol: 'د.ج' },
    { code: 'ZMW', name: 'Zambian Kwacha', symbol: 'ZK' },
    { code: 'BWP', name: 'Botswana Pula', symbol: 'P' },
    { code: 'MZN', name: 'Mozambican Metical', symbol: 'MT' },
    { code: 'AOA', name: 'Angolan Kwanza', symbol: 'Kz' },
    { code: 'MUR', name: 'Mauritian Rupee', symbol: '₨' },
    { code: 'SCR', name: 'Seychellois Rupee', symbol: '₨' },
    { code: 'MGA', name: 'Malagasy Ariary', symbol: 'Ar' },
    { code: 'XPF', name: 'CFP Franc', symbol: 'Fr' },
    { code: 'FJD', name: 'Fijian Dollar', symbol: 'FJ$' },
    { code: 'PGK', name: 'Papua New Guinean Kina', symbol: 'K' },
    { code: 'SBD', name: 'Solomon Islands Dollar', symbol: 'SI$' },
    { code: 'VUV', name: 'Vanuatu Vatu', symbol: 'Vt' },
    { code: 'WST', name: 'Samoan Tala', symbol: 'T' },
    { code: 'TOP', name: 'Tongan Paʻanga', symbol: 'T$' },
    { code: 'XCD', name: 'East Caribbean Dollar', symbol: '$' },
    { code: 'BBD', name: 'Barbadian Dollar', symbol: '$' },
    { code: 'BZD', name: 'Belize Dollar', symbol: '$' },
    { code: 'BMD', name: 'Bermudan Dollar', symbol: '$' },
    { code: 'BND', name: 'Brunei Dollar', symbol: 'B$' },
    { code: 'KYD', name: 'Cayman Islands Dollar', symbol: '$' },
    { code: 'GYD', name: 'Guyanese Dollar', symbol: '$' },
    { code: 'JMD', name: 'Jamaican Dollar', symbol: 'J$' },
    { code: 'TTD', name: 'Trinidad and Tobago Dollar', symbol: 'TT$' },
    { code: 'BSD', name: 'Bahamian Dollar', symbol: '$' },
    { code: 'IQD', name: 'Iraqi Dinar', symbol: 'ع.د' },
    { code: 'IRR', name: 'Iranian Rial', symbol: '﷼' },
    { code: 'AFN', name: 'Afghan Afghani', symbol: '؋' },
    { code: 'CRC', name: 'Costa Rican Colón', symbol: '₡' },
    { code: 'GTQ', name: 'Guatemalan Quetzal', symbol: 'Q' },
    { code: 'HNL', name: 'Honduran Lempira', symbol: 'L' },
    { code: 'NIO', name: 'Nicaraguan Córdoba', symbol: 'C$' },
    { code: 'PAB', name: 'Panamanian Balboa', symbol: 'B/.' },
    { code: 'PYG', name: 'Paraguayan Guaraní', symbol: 'Gs' },
    { code: 'UYU', name: 'Uruguayan Peso', symbol: '$U' },
    { code: 'VES', name: 'Venezuelan Bolívar', symbol: 'Bs' },
    { code: 'BOB', name: 'Bolivian Boliviano', symbol: 'Bs.' },
    { code: 'DOP', name: 'Dominican Peso', symbol: 'RD$' },
    { code: 'HTG', name: 'Haitian Gourde', symbol: 'G' },
    { code: 'CUP', name: 'Cuban Peso', symbol: '₱' },
    { code: 'XPF', name: 'CFP Franc', symbol: 'Fr' },
    { code: 'ANG', name: 'Netherlands Antillean Guilder', symbol: 'ƒ' },
    { code: 'AWG', name: 'Aruban Florin', symbol: 'ƒ' },
    { code: 'SRD', name: 'Surinamese Dollar', symbol: '$' },
  ];

  // Get all IANA timezones
  function getAllTimezones() {
    const timezones = [];
    const timezoneNames = Intl.supportedValuesOf('timeZone');
    
    timezoneNames.forEach(tz => {
      const date = new Date();
      const formatter = new Intl.DateTimeFormat('en', {
        timeZone: tz,
        timeZoneName: 'short'
      });
      const parts = formatter.formatToParts(date);
      const timeZoneName = parts.find(part => part.type === 'timeZoneName')?.value || '';
      
      // Format timezone name for display
      const displayName = tz.replace(/_/g, ' ').split('/').map(part => 
        part.charAt(0).toUpperCase() + part.slice(1).toLowerCase()
      ).join(' / ');
      
      timezones.push({
        value: tz,
        name: displayName,
        offset: timeZoneName
      });
    });
    
    // Sort by timezone name
    return timezones.sort((a, b) => a.name.localeCompare(b.name));
  }

  // Initialize Select2 for currency and timezone when Step 3 is loaded
  function initializeSelect2() {
    // Initialize Currency Select2
    const currencySelect = $('#currency');
    if (currencySelect.length && !currencySelect.hasClass('select2-hidden-accessible')) {
      // Populate currency options
      allCurrencies.forEach(currency => {
        const isSelected = currency.code === '{{ $userDetail->currency ?? '' }}';
        currencySelect.append(
          new Option(`${currency.code} (${currency.symbol}) - ${currency.name}`, currency.code, isSelected, isSelected)
        );
      });

      currencySelect.select2({
        placeholder: 'Search and select currency',
        allowClear: true,
        width: '100%'
      });
    }

    // Initialize Timezone Select2
    const timezoneSelect = $('#timezone');
    if (timezoneSelect.length && !timezoneSelect.hasClass('select2-hidden-accessible')) {
      // Populate timezone options
      const timezones = getAllTimezones();
      const selectedTimezone = '{{ $userDetail->timezone ?? '' }}';
      
      timezones.forEach(tz => {
        const isSelected = tz.value === selectedTimezone;
        timezoneSelect.append(
          new Option(`${tz.name} (${tz.offset})`, tz.value, isSelected, isSelected)
        );
      });

      timezoneSelect.select2({
        placeholder: 'Search and select timezone',
        allowClear: true,
        width: '100%'
      });
    }
  }

  // Store countries and cities data globally
  let countryCityArrayOnboarding = [];
  let uniqueCountriesOnboarding = [];

  // Initialize Country and City Select2
  function initializeCountryCity() {
    const selectedCountry = '{{ $userDetail->country ?? '' }}';
    const selectedCity = '{{ $userDetail->city ?? '' }}';

    // Initialize Country Select2
    const countrySelect = $('#country');
    if (countrySelect.length && !countrySelect.hasClass('select2-hidden-accessible')) {
      countrySelect.select2({
        placeholder: 'Loading countries...',
        allowClear: true,
        width: '100%'
      });

      // Show loading state
      countrySelect.empty().append(new Option('Loading countries...', '', true, true));
      countrySelect.prop('disabled', true);
      countrySelect.trigger('change');

      // Load all countries and cities from API
      $.ajax({
        url: "https://countriesnow.space/api/v0.1/countries",
        method: "GET",
        success: function(response) {
          if (response.data && Array.isArray(response.data)) {
            // Build country-city array
            countryCityArrayOnboarding = [];
            response.data.forEach(item => {
              const country = item.country;
              if (Array.isArray(item.cities)) {
                item.cities.forEach(city => {
                  countryCityArrayOnboarding.push([country, city]);
                });
              }
            });

            // Extract unique countries
            const countrySet = new Set();
            countryCityArrayOnboarding.forEach(item => {
              countrySet.add(item[0]);
            });
            uniqueCountriesOnboarding = Array.from(countrySet).sort();

            // Populate country dropdown
            countrySelect.empty().append(new Option('', ''));
            uniqueCountriesOnboarding.forEach(country => {
              const isSelected = country === selectedCountry;
              countrySelect.append(
                new Option(country, country, isSelected, isSelected)
              );
            });

            // Enable dropdown and update placeholder
            countrySelect.prop('disabled', false);
            countrySelect.select2({
              placeholder: 'Search and select country',
              allowClear: true,
              width: '100%'
            });
            countrySelect.trigger('change');

            // If country is selected, load cities
            if (selectedCountry) {
              loadCitiesForOnboarding(selectedCountry, selectedCity);
            }
          } else {
            console.error("Unexpected API format:", response);
            countrySelect.empty().append(new Option('Error loading countries. Please refresh the page.', ''));
            countrySelect.prop('disabled', false);
            countrySelect.trigger('change');
          }
        },
        error: function(err) {
          console.error("API error:", err);
          countrySelect.empty().append(new Option('Error loading countries. Please refresh the page.', ''));
          countrySelect.prop('disabled', false);
          countrySelect.trigger('change');
        }
      });
    }

    // Initialize City Select2
    const citySelect = $('#city');
    if (citySelect.length && !citySelect.hasClass('select2-hidden-accessible')) {
      citySelect.select2({
        placeholder: 'Select country first',
        allowClear: true,
        width: '100%'
      });
      citySelect.prop('disabled', true);

      // Handle country change
      countrySelect.on('change', function() {
        const country = $(this).val();
        if (country) {
          loadCitiesForOnboarding(country);
        } else {
          citySelect.empty().append(new Option('', '')).val('').trigger('change');
          citySelect.prop('disabled', true);
          citySelect.select2({
            placeholder: 'Select country first',
            allowClear: true,
            width: '100%'
          });
        }
      });
    }
  }

  // Load cities based on selected country (for onboarding) from stored array
  function loadCitiesForOnboarding(country, selectedCity = '') {
    const citySelect = $('#city');
    citySelect.prop('disabled', true);
    citySelect.empty().append(new Option('Loading cities...', '', true, true));
    
    // Update Select2 to show loading placeholder
    citySelect.select2({
      placeholder: 'Loading cities...',
      allowClear: true,
      width: '100%'
    });
    citySelect.trigger('change');

    // Filter cities for the selected country from the stored array
    setTimeout(() => {
      const citiesForCountry = countryCityArrayOnboarding
        .filter(item => item[0] === country)
        .map(item => item[1]);
      
      // Remove duplicates and sort
      const uniqueCities = [...new Set(citiesForCountry)].sort();
      
      citySelect.empty().append(new Option('', ''));
      
      if (uniqueCities.length > 0) {
        uniqueCities.forEach(city => {
          const isSelected = city === selectedCity;
          citySelect.append(
            new Option(city, city, isSelected, isSelected)
          );
        });
      } else {
        citySelect.append(new Option('No cities found for this country', ''));
      }
      
      // Enable dropdown and update placeholder
      citySelect.prop('disabled', false);
      citySelect.select2({
        placeholder: 'Search and select city',
        allowClear: true,
        width: '100%'
      });
      citySelect.trigger('change');
    }, 100); // Small delay to show loading state
  }

  // Note: Select2 initialization is now handled in the goToStep function above

  // Also initialize on page load if already on Step 1 or Step 4
  $(document).ready(function() {
    if (currentStep === 1) {
      setTimeout(() => {
        initDropify();
        initializeCountryCity();
      }, 500);
    }
    if (currentStep === 4) {
      setTimeout(() => {
        initializeSelect2();
      }, 500);
    }
  });
</script>
@endpush

