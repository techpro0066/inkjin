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

<!-- Google Places API -->
<script src="https://maps.googleapis.com/maps/api/js?key={{ config('services.google.place_api_key') }}&libraries=places"></script>
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

          <!-- Profile -->
          <div class="step-content {{ $currentStep == 1 ? 'active' : '' }}" id="onboarding-profile">
            <h5 class="mb-4">Complete Profile</h5>
            <form id="profileForm" enctype="multipart/form-data">
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
                  <label for="first_name" class="form-label">First Name <span class="text-danger">*</span></label>
                  <input type="text" class="form-control" id="first_name" name="first_name" value="{{ Auth::user()->first_name ?? '' }}" placeholder="Enter your first name">
                  <p class="text-danger mt-1 mb-0" id="first_name_error" style="display: none; font-size: 0.875rem;"></p>
                </div>
                
                <div class="col-md-6">
                  <label for="last_name" class="form-label">Last Name <span class="text-danger">*</span></label>
                  <input type="text" class="form-control" id="last_name" name="last_name" value="{{ Auth::user()->last_name ?? '' }}" placeholder="Enter your last name">
                  <p class="text-danger mt-1 mb-0" id="last_name_error" style="display: none; font-size: 0.875rem;"></p>
                </div>
                
                <div class="col-md-6">
                  <label for="user_name" class="form-label">User Name <span class="text-danger">*</span></label>
                  <input type="text" class="form-control" id="user_name" name="user_name" value="{{ $userDetail->user_name ?? '' }}" placeholder="Enter your username">
                  <p class="text-danger mt-1 mb-0" id="user_name_error" style="display: none; font-size: 0.875rem;"></p>
                </div>
                
                <div class="col-md-6">
                  <label for="mobile_number" class="form-label">Mobile Number <span class="text-danger">*</span></label>
                  <input type="text" class="form-control" id="mobile_number" name="mobile_number" value="{{ $userDetail->mobile_number ?? '' }}" placeholder="Enter mobile number">
                  <p class="text-danger mt-1 mb-0" id="mobile_number_error" style="display: none; font-size: 0.875rem;"></p>
                </div>
              </div>
              
              <div class="d-flex justify-content-end mt-4">
                <button type="submit" class="btn btn-primary">
                  Next Step <i class="ti ti-arrow-right ms-2"></i>
                </button>
              </div>
            </form>
          </div>

          <!-- Studio -->
          <div class="step-content {{ $currentStep == 2 ? 'active' : '' }}" id="onboarding-studio">
            <h5 class="mb-4">Studio Information</h5>
            <form id="studioForm">
              @csrf
              <div class="row g-3">
                 <div class="col-12">
                   <label for="studio_name" class="form-label">Studio Name <span class="text-danger">*</span></label>
                   <input type="text" class="form-control" id="studio_name" name="studio_name" value="{{ $userDetail->studio_name ?? '' }}">
                   <p class="text-danger mt-1 mb-0" id="studio_name_error" style="display: none; font-size: 0.875rem;"></p>
                 </div>
                 
                 <div class="col-12">
                   <label for="studio_address" class="form-label">Studio Address <span class="text-danger">*</span></label>
                   <input type="text" class="form-control" id="studio_address" name="studio_address" value="{{ $userDetail->studio_address ?? '' }}" placeholder="Start typing your address...">
                   <small class="text-muted d-block mt-1">Start typing and select from Google suggestions to auto-fill address fields</small>
                   <p class="text-danger mt-1 mb-0" id="studio_address_error" style="display: none; font-size: 0.875rem;"></p>
                 </div>

                 <div class="col-md-6">
                   <label for="street_name" class="form-label">Street Name <span class="text-danger">*</span></label>
                   <input type="text" class="form-control" id="street_name" name="street_name" value="{{ $userDetail->street_name ?? '' }}" placeholder="Enter street name">
                   <p class="text-danger mt-1 mb-0" id="street_name_error" style="display: none; font-size: 0.875rem;"></p>
                 </div>

                 <div class="col-md-6">
                   <label for="street_number" class="form-label">Street Number <span class="text-danger">*</span></label>
                   <input type="text" class="form-control" id="street_number" name="street_number" value="{{ $userDetail->street_number ?? '' }}" placeholder="Enter street number">
                   <p class="text-danger mt-1 mb-0" id="street_number_error" style="display: none; font-size: 0.875rem;"></p>
                 </div>

                 <div class="col-md-6">
                   <label for="city" class="form-label">City <span class="text-danger">*</span></label>
                   <input type="text" class="form-control" id="city" name="city" value="{{ $userDetail->city ?? '' }}" placeholder="Enter city">
                   <p class="text-danger mt-1 mb-0" id="city_error" style="display: none; font-size: 0.875rem;"></p>
                 </div>

                 <div class="col-md-6">
                   <label for="state" class="form-label">Province/State <span class="text-danger">*</span></label>
                   <input type="text" class="form-control" id="state" name="state" value="{{ $userDetail->state ?? '' }}" placeholder="Enter state">
                   <p class="text-danger mt-1 mb-0" id="state_error" style="display: none; font-size: 0.875rem;"></p>
                 </div>

                 <div class="col-md-6">
                   <label for="postal_code" class="form-label">Postal Code <span class="text-danger">*</span></label>
                   <input type="text" class="form-control" id="postal_code" name="postal_code" value="{{ $userDetail->postal_code ?? '' }}" placeholder="Enter postal code">
                   <p class="text-danger mt-1 mb-0" id="postal_code_error" style="display: none; font-size: 0.875rem;"></p>
                 </div>

                 <div class="col-md-6">
                   <label for="country" class="form-label">Country <span class="text-danger">*</span></label>
                   <input type="text" class="form-control" id="country" name="country" value="{{ $userDetail->country ?? '' }}" placeholder="Enter country">
                   <p class="text-danger mt-1 mb-0" id="country_error" style="display: none; font-size: 0.875rem;"></p>
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

          <!-- Calendar / scheduling -->
          <div class="step-content {{ $currentStep == 3 ? 'active' : '' }}" id="onboarding-calendar">
            <h5 class="mb-4">Scheduling Type</h5>
            <p class="text-muted mb-4">Choose how you want to manage your scheduling. This step is required.</p>
            <form id="calendarForm">
              @csrf
              <input type="hidden" name="scheduling_type" id="scheduling_type" value="{{ $userDetail->scheduling_type ?? '' }}">
              
              <div class="row g-3 mb-4">
                <div class="col-md-6">
                  <div class="card border-2 h-100 cursor-pointer scheduling-option {{ ($userDetail->scheduling_type ?? '') == 'auto' ? 'border-primary' : 'border-dashed' }}" 
                       data-scheduling-type="auto"
                       onclick="selectSchedulingType('auto', this)" 
                       style="cursor: pointer; transition: all 0.3s;">
                    <div class="card-body text-center py-5">
                      <i class="ti ti-calendar-automated ti-3x {{ ($userDetail->scheduling_type ?? '') == 'auto' ? 'text-primary' : 'text-muted' }} mb-3"></i>
                      <h6 class="mb-2">Auto Scheduling</h6>
                      <p class="text-muted mb-3">Connect your Google Calendar to automatically sync your availability and bookings.</p>
                      @if(($userDetail->scheduling_type ?? '') == 'auto')
                        <span class="badge bg-primary mb-3">
                          <i class="ti ti-check me-1"></i> Selected
                        </span>
                      @endif
                    </div>
                  </div>
                </div>
                
                <div class="col-md-6">
                  <div class="card border-2 h-100 cursor-pointer scheduling-option {{ ($userDetail->scheduling_type ?? '') == 'managed' ? 'border-primary' : 'border-dashed' }}" 
                       data-scheduling-type="managed"
                       onclick="selectSchedulingType('managed', this)" 
                       style="cursor: pointer; transition: all 0.3s;">
                    <div class="card-body text-center py-5">
                      <i class="ti ti-calendar-user ti-3x {{ ($userDetail->scheduling_type ?? '') == 'managed' ? 'text-primary' : 'text-muted' }} mb-3"></i>
                      <h6 class="mb-2">Managed Scheduling</h6>
                      <p class="text-muted mb-3">Manage your schedule manually without connecting a calendar.</p>
                      @if(($userDetail->scheduling_type ?? '') == 'managed')
                        <span class="badge bg-primary mb-3">
                          <i class="ti ti-check me-1"></i> Selected
                        </span>
                      @endif
                    </div>
                  </div>
                </div>
              </div>
              
              <!-- Calendar Connection Section (only shown for Auto Scheduling) -->
              <div class="row g-3 mb-4" id="calendarConnectionSection" style="display: {{ ($userDetail->scheduling_type ?? '') == 'auto' ? 'flex' : 'none' }};">
                <div class="col-12">
                  <div class="card border-2 {{ ($userDetail->google_calendar_token ?? null) ? 'border-success' : 'border-dashed' }}">
                    <div class="card-body text-center py-4">
                      <i class="ti ti-calendar ti-3x {{ ($userDetail->google_calendar_token ?? null) ? 'text-success' : 'text-muted' }} mb-3"></i>
                      <h6 class="mb-2">Connect Your Google Calendar</h6>
                      <p class="text-muted mb-4">Connect your Google Calendar to enable automatic scheduling.</p>
                      
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
              
              <p class="text-danger mt-1 mb-3" id="scheduling_type_error" style="display: none; font-size: 0.875rem;"></p>
              
              <div class="d-flex justify-content-between mt-4">
                <button type="button" class="btn btn-label-secondary" onclick="goToStep(2)">
                  <i class="ti ti-arrow-left me-2"></i> Previous
                </button>
                <button type="submit" class="btn btn-primary" id="calendarSubmitBtn">
                    Next Step <i class="ti ti-arrow-right ms-2"></i>
                  </button>
              </div>
            </form>
          </div>

          <!-- Preferences -->
          <div class="step-content {{ $currentStep == 4 ? 'active' : '' }}" id="onboarding-preferences">
            <h5 class="mb-4">Preferences</h5>
            <form id="preferencesForm">
              @csrf
              
              <!-- General Section -->
              <div class="card mb-4">
                <div class="card-header bg-light">
                  <h6 class="mb-0">
                    <i class="ti ti-settings me-2"></i>General
                  </h6>
                </div>
                <div class="card-body">
              <div class="row g-3">
                <div class="col-md-6">
                  <label for="timezone" class="form-label">Timezone <span class="text-danger">*</span></label>
                   <select class="form-select select2" id="timezone" name="timezone" data-placeholder="Search and select timezone">
                        <option value="" selected disabled>Select Timezone</option>
                   </select>
                   <p class="text-danger mt-1 mb-0" id="timezone_error" style="display: none; font-size: 0.875rem;"></p>
                </div>
                
                <div class="col-md-6">
                      <label for="date_time_format" class="form-label">Date Format <span class="text-danger">*</span></label>
                   <select class="form-select" id="date_time_format" name="date_time_format">
                        <option value="" selected disabled>Select Format</option>
                     <option value="MM/DD/YYYY" {{ ($userDetail->date_time_format ?? '') == 'MM/DD/YYYY' ? 'selected' : '' }}>MM/DD/YYYY</option>
                     <option value="DD/MM/YYYY" {{ ($userDetail->date_time_format ?? '') == 'DD/MM/YYYY' ? 'selected' : '' }}>DD/MM/YYYY</option>
                     <option value="YYYY-MM-DD" {{ ($userDetail->date_time_format ?? '') == 'YYYY-MM-DD' ? 'selected' : '' }}>YYYY-MM-DD</option>
                   </select>
                   <p class="text-danger mt-1 mb-0" id="date_time_format_error" style="display: none; font-size: 0.875rem;"></p>
                    </div>
                  </div>
                </div>
                </div>
                
              <!-- Payment Section -->
              <div class="card mb-4">
                <div class="card-header bg-light">
                  <h6 class="mb-0">
                    <i class="ti ti-currency-dollar me-2"></i>Payment
                  </h6>
                </div>
                <div class="card-body">
                  <div class="row g-3">
                <div class="col-md-6">
                      <label for="currency" class="form-label">Currency <span class="text-danger">*</span></label>
                      <select class="form-select select2" id="currency" name="currency" data-placeholder="Search and select currency">
                        <option value="" selected disabled>Select Currency</option>
                      </select>
                      <p class="text-danger mt-1 mb-0" id="currency_error" style="display: none; font-size: 0.875rem;"></p>
                </div>
                
                <div class="col-md-6">
                  <label for="minimum_deposit_type" class="form-label">Deposit Type <span class="text-danger">*</span></label>
                   <select class="form-select" id="minimum_deposit_type" name="minimum_deposit_type">
                        <option value="" selected disabled>Select Type</option>
                        <option value="amount" {{ ($userDetail->minimum_deposit_type ?? '') == 'amount' ? 'selected' : '' }}>Amount</option>
                     <option value="percentage" {{ ($userDetail->minimum_deposit_type ?? '') == 'percentage' ? 'selected' : '' }}>Percentage</option>
                   </select>
                   <p class="text-danger mt-1 mb-0" id="minimum_deposit_type_error" style="display: none; font-size: 0.875rem;"></p>
                </div>
                
                <div class="col-md-6">
                      <label for="minimum_deposit_amount" class="form-label">Minimum Deposit <span class="deposit-type-selected"></span> <span class="text-danger">*</span></label>
                      <input type="text" class="form-control" id="minimum_deposit_amount" name="minimum_deposit_amount" value="{{ $userDetail->minimum_deposit_amount ?? '' }}" placeholder="Enter amount">
                      <p class="text-danger mt-1 mb-0" id="minimum_deposit_amount_error" style="display: none; font-size: 0.875rem;"></p>
                </div>
                
                    <div class="col-12">
                      <label class="form-label">Inkjin Booking Fee <span class="text-danger">*</span></label>
                      <div class="form-check mb-2">
                        <input class="form-check-input" type="radio" name="booking_fee_type" id="booking_fee_client" value="client" {{ ($userDetail->booking_fee_type ?? '') == 'client' ? 'checked' : '' }}>
                        <label class="form-check-label" for="booking_fee_client">
                          <strong>Client pays</strong> – 10€ will be added to the client's total
                        </label>
                      </div>
                      <div class="form-check mb-2">
                        <input class="form-check-input" type="radio" name="booking_fee_type" id="booking_fee_artist" value="artist" {{ ($userDetail->booking_fee_type ?? '') == 'artist' ? 'checked' : '' }}>
                        <label class="form-check-label" for="booking_fee_artist">
                          <strong>Artist pays</strong> – 10€ will be deducted from your payout
                        </label>
                      </div>
                      <div class="form-check">
                        <input class="form-check-input" type="radio" name="booking_fee_type" id="booking_fee_split" value="split" {{ ($userDetail->booking_fee_type ?? '') == 'split' ? 'checked' : '' }}>
                        <label class="form-check-label" for="booking_fee_split">
                          <strong>Split</strong> – Evenly split between you and the client. 5€ will be added to your client's total and 5€ will be deducted from your payout.
                        </label>
                      </div>
                      <p class="text-danger mt-1 mb-0" id="booking_fee_type_error" style="display: none; font-size: 0.875rem;"></p>
                    </div>
                  </div>
                </div>
              </div>

              <!-- Scheduling Section -->
              <div class="card mb-4">
                <div class="card-header bg-light">
                  <h6 class="mb-0">
                    <i class="ti ti-calendar me-2"></i>Scheduling
                  </h6>
                </div>
                <div class="card-body">
                  <div class="row g-3">
                <div class="col-md-6">
                      <label for="reschedule_times" class="form-label">Allow clients to reschedule? <span class="text-danger">*</span></label>
                   <select class="form-select" id="reschedule_times" name="reschedule_times">
                        <option value="" selected disabled>Select Option</option>
                     <option value="never" {{ ($userDetail->reschedule_times ?? '') == 'never' ? 'selected' : '' }}>Never</option>
                     <option value="once" {{ ($userDetail->reschedule_times ?? '') == 'once' ? 'selected' : '' }}>Once</option>
                     <option value="twice" {{ ($userDetail->reschedule_times ?? '') == 'twice' ? 'selected' : '' }}>Twice</option>
                     <option value="unlimited" {{ ($userDetail->reschedule_times ?? '') == 'unlimited' ? 'selected' : '' }}>Unlimited</option>
                   </select>
                   <p class="text-danger mt-1 mb-0" id="reschedule_times_error" style="display: none; font-size: 0.875rem;"></p>
                </div>
                
                <div class="col-md-6">
                      <label for="cancellation_window" class="form-label">How long do clients have to cancel a booking to get a full refund? <span class="text-danger">*</span></label>
                      <select class="form-select" id="cancellation_window" name="cancellation_window">
                        <option value="" selected disabled>Select Window</option>
                        <option value="24h" {{ ($userDetail->cancellation_window ?? '') == '24h' ? 'selected' : '' }}>24 Hours</option>
                        <option value="48h" {{ ($userDetail->cancellation_window ?? '') == '48h' ? 'selected' : '' }}>48 Hours</option>
                        <option value="72h" {{ ($userDetail->cancellation_window ?? '') == '72h' ? 'selected' : '' }}>72 Hours</option>
                        <option value="1w" {{ ($userDetail->cancellation_window ?? '') == '1w' ? 'selected' : '' }}>1 Week</option>
                        <option value="2w" {{ ($userDetail->cancellation_window ?? '') == '2w' ? 'selected' : '' }}>2 Weeks</option>
                      </select>
                      <p class="text-danger mt-1 mb-0" id="cancellation_window_error" style="display: none; font-size: 0.875rem;"></p>
                    </div>
                    
                    <div class="col-md-6">
                      <label for="session_buffer_period" class="form-label">Time between sessions (minutes) <span class="text-danger">*</span></label>
                  <input type="number" class="form-control" id="session_buffer_period" name="session_buffer_period" value="{{ $userDetail->session_buffer_period ?? '' }}" placeholder="e.g., 15, 30, 60" min="0" step="1">
                  <small class="text-muted">Time between sessions for rest, clean up, or preparation</small>
                  <p class="text-danger mt-1 mb-0" id="session_buffer_period_error" style="display: none; font-size: 0.875rem;"></p>
                    </div>
                  </div>
                </div>
                </div>
                
              <!-- Consultation Section (Optional) -->
              <div class="card mb-4">
                <div class="card-header bg-light">
                  <h6 class="mb-0">
                    <i class="ti ti-message-circle me-2"></i>Consultation Settings
                  </h6>
                </div>
                <div class="card-body">
                  <div class="row g-3">
                    <div class="col-12">
                  <label class="form-label">Require Consultation Session</label>
                  <div class="form-check form-switch">
                    <input class="form-check-input" type="checkbox" id="require_consultation" name="require_consultation" value="1" {{ ($userDetail->require_consultation ?? false) ? 'checked' : '' }} onchange="toggleSessionFields()">
                    <label class="form-check-label" for="require_consultation">
                      Require consultation session when booking a tattoo
                    </label>
                  </div>
                  <small class="text-muted d-block mt-1">When enabled, clients must book a consultation before booking a tattoo session</small>
                  <p class="text-danger mt-1 mb-0" id="require_consultation_error" style="display: none; font-size: 0.875rem;"></p>
                </div>
                
                <div class="col-md-6" id="session_type_container" style="display: {{ ($userDetail->require_consultation ?? false) ? 'block' : 'none' }};">
                  <label for="session_type" class="form-label">Session Type <span class="text-danger">*</span></label>
                  <select class="form-select" id="session_type" name="session_type">
                        <option value="" selected disabled>Select Session Type</option>
                    <option value="online" {{ ($userDetail->session_type ?? '') == 'online' ? 'selected' : '' }}>Online Session</option>
                    <option value="physical" {{ ($userDetail->session_type ?? '') == 'physical' ? 'selected' : '' }}>Physical Session</option>
                    <option value="both" {{ ($userDetail->session_type ?? '') == 'both' ? 'selected' : '' }}>Both (Online & Physical)</option>
                  </select>
                  <small class="text-muted d-block mt-1">Choose whether you offer online sessions, physical sessions, or both</small>
                  <p class="text-danger mt-1 mb-0" id="session_type_error" style="display: none; font-size: 0.875rem;"></p>
                </div>
                
                <div class="col-md-6" id="session_duration_container" style="display: {{ ($userDetail->require_consultation ?? false) ? 'block' : 'none' }};">
                  <label for="session_duration_minutes" class="form-label">Session Duration (minutes) <span class="text-danger">*</span></label>
                  <input type="number" class="form-control" id="session_duration_minutes" name="session_duration_minutes" value="{{ $userDetail->session_duration_minutes ?? '' }}" placeholder="e.g., 30, 60, 90" min="15" max="480" step="15">
                  <small class="text-muted d-block mt-1">Duration for the consultation session (minimum 15 minutes, maximum 8 hours)</small>
                  <p class="text-danger mt-1 mb-0" id="session_duration_minutes_error" style="display: none; font-size: 0.875rem;"></p>
                </div>
                
                <div class="col-md-6" id="consultation_timing_container" style="display: {{ ($userDetail->require_consultation ?? false) ? 'block' : 'none' }};">
                  <label for="consultation_timing" class="form-label">Consultation Timing <span class="text-danger">*</span></label>
                  <select class="form-select" id="consultation_timing" name="consultation_timing" onchange="toggleGapFields()">
                        <option value="" selected disabled>Select Timing</option>
                    <option value="combined" {{ ($userDetail->consultation_timing ?? '') == 'combined' ? 'selected' : '' }}>Add with Tattoo Session</option>
                    <option value="separate" {{ ($userDetail->consultation_timing ?? '') == 'separate' ? 'selected' : '' }}>Separate from Tattoo Session</option>
                  </select>
                  <small class="text-muted d-block mt-1">
                    <strong>Combined:</strong> Consultation time is added to the tattoo session duration<br>
                    <strong>Separate:</strong> Consultation is a standalone session, separate from the tattoo session
                  </small>
                  <p class="text-danger mt-1 mb-0" id="consultation_timing_error" style="display: none; font-size: 0.875rem;"></p>
                </div>
              </div>
              
              <!-- Gap between consultation and tattoo session (only for separate mode) -->
              <div class="row g-3 mt-2" id="gap_fields_container" style="display: {{ (($userDetail->require_consultation ?? false) && ($userDetail->consultation_timing ?? '') == 'separate') ? 'flex' : 'none' }};">
                <div class="col-12">
                  <div class="form-check">
                    <input class="form-check-input" type="checkbox" id="require_gap_between_consultation_tattoo" name="require_gap_between_consultation_tattoo" value="1" {{ ($userDetail->require_gap_between_consultation_tattoo ?? false) ? 'checked' : '' }} onchange="toggleGapDurationFields()">
                    <label class="form-check-label" for="require_gap_between_consultation_tattoo">
                      Require gap/window time between consultation and tattoo session
                    </label>
                  </div>
                  <small class="text-muted d-block mt-1">Enable this if you want to enforce a minimum time gap between consultation completion and tattoo session booking</small>
                </div>
                
                <div class="col-md-6" id="gap_duration_container" style="display: {{ ($userDetail->require_gap_between_consultation_tattoo ?? false) ? 'block' : 'none' }};">
                  <label for="consultation_tattoo_gap_value" class="form-label">Gap Duration <span class="text-danger">*</span></label>
                  <input type="number" class="form-control" id="consultation_tattoo_gap_value" name="consultation_tattoo_gap_value" value="{{ $userDetail->consultation_tattoo_gap_value ?? '' }}" placeholder="e.g., 1, 2, 7" min="1">
                  <p class="text-danger mt-1 mb-0" id="consultation_tattoo_gap_value_error" style="display: none; font-size: 0.875rem;"></p>
                </div>
                
                <div class="col-md-6" id="gap_unit_container" style="display: {{ ($userDetail->require_gap_between_consultation_tattoo ?? false) ? 'block' : 'none' }};">
                  <label for="consultation_tattoo_gap_unit" class="form-label">Gap Unit <span class="text-danger">*</span></label>
                  <select class="form-select" id="consultation_tattoo_gap_unit" name="consultation_tattoo_gap_unit">
                        <option value="" selected disabled>Select Unit</option>
                    <option value="minutes" {{ ($userDetail->consultation_tattoo_gap_unit ?? '') == 'minutes' ? 'selected' : '' }}>Minutes</option>
                    <option value="hours" {{ ($userDetail->consultation_tattoo_gap_unit ?? '') == 'hours' ? 'selected' : '' }}>Hours</option>
                    <option value="days" {{ ($userDetail->consultation_tattoo_gap_unit ?? '') == 'days' ? 'selected' : '' }}>Days</option>
                  </select>
                  <p class="text-danger mt-1 mb-0" id="consultation_tattoo_gap_unit_error" style="display: none; font-size: 0.875rem;"></p>
                    </div>
                  </div>
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

          <!-- Payment -->
          <div class="step-content {{ $currentStep == 5 ? 'active' : '' }}" id="onboarding-payment">
            <h5 class="mb-4">Payment Setup</h5>
            <form id="onboardingPaymentForm">
              @csrf
              <div class="row g-3">
                <!-- Payment Type Selection -->
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
                  <p class="text-danger mt-1 mb-0" id="payment_type_error" style="display: none; font-size: 0.875rem;"></p>
                </div>

                <!-- Artist Account - Stripe Connect -->
                <div class="col-12" id="artist_stripe_section" style="display: none;">
                  <div class="card border-2 {{ ($userDetail->stripe_account_id ?? null) ? 'border-success' : 'border-dashed' }}">
                    <div class="card-body text-center py-5">
                      <i class="ti ti-credit-card ti-3x {{ ($userDetail->stripe_account_id ?? null) ? 'text-success' : 'text-muted' }} mb-3"></i>
                      <h6 class="mb-2">Connect Your Stripe Account <span class="text-danger">*</span></h6>
                      <p class="text-muted mb-4">Please connect your Stripe account to receive payments. This is required to complete onboarding.</p>
                      
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

                <!-- Studio Account - Studio Info -->
                <div class="col-12" id="studio_section" style="display: none;">
                  <div class="card">
                    <div class="card-body">
                      <div class="alert alert-info mb-4">
                        <i class="ti ti-info-circle me-2"></i>
                        Payments will go to your studio's Stripe account. An email will be sent to the studio to connect their Stripe account.
                      </div>
                      
                      <div class="mb-3">
                        <label for="studio_name_display" class="form-label">Studio Name</label>
                        <input type="text" class="form-control" id="studio_name_display" value="{{ $userDetail->studio_name ?? '' }}" readonly>
                        <small class="text-muted">This is the studio name from step 2</small>
                      </div>
                      
                      <div class="mb-3">
                        <label for="studio_email" class="form-label">Studio Email <span class="text-danger">*</span></label>
                        <input type="email" class="form-control" id="studio_email" name="studio_email" value="{{ $userDetail->studio_email ?? '' }}" placeholder="Enter studio email address">
                        <small class="text-muted">The studio will receive an email to connect their Stripe account</small>
                        <p class="text-danger mt-1 mb-0" id="studio_email_error" style="display: none; font-size: 0.875rem;"></p>
                      </div>
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
              
              <div class="d-flex justify-content-between mt-4">
                <button type="button" class="btn btn-label-secondary" onclick="goToStep(4)">
                  <i class="ti ti-arrow-left me-2"></i> Previous
                </button>
                <div>
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
  
  // Toggle session type, duration, and consultation timing fields based on consultation requirement
  function toggleSessionFields() {
    const requireConsultation = document.getElementById('require_consultation');
    const sessionTypeContainer = document.getElementById('session_type_container');
    const sessionDurationContainer = document.getElementById('session_duration_container');
    const consultationTimingContainer = document.getElementById('consultation_timing_container');
    const sessionType = document.getElementById('session_type');
    const sessionDuration = document.getElementById('session_duration_minutes');
    const consultationTiming = document.getElementById('consultation_timing');
    
    if (!requireConsultation) {
      return; // Element not found, might not be on current step
    }
    
    if (requireConsultation.checked) {
      // Show fields and make them required
      if (sessionTypeContainer) {
        sessionTypeContainer.style.display = 'block';
      }
      if (sessionDurationContainer) {
        sessionDurationContainer.style.display = 'block';
      }
      if (consultationTimingContainer) {
        consultationTimingContainer.style.display = 'block';
      }
      if (sessionType) {
        sessionType.required = true;
        sessionType.setAttribute('required', 'required');
      }
      if (sessionDuration) {
        sessionDuration.required = true;
        sessionDuration.setAttribute('required', 'required');
      }
      if (consultationTiming) {
        consultationTiming.required = true;
        consultationTiming.setAttribute('required', 'required');
      }
    } else {
      // Hide fields and make them optional
      if (sessionTypeContainer) {
        sessionTypeContainer.style.display = 'none';
      }
      if (sessionDurationContainer) {
        sessionDurationContainer.style.display = 'none';
      }
      if (consultationTimingContainer) {
        consultationTimingContainer.style.display = 'none';
      }
      if (sessionType) {
        sessionType.required = false;
        sessionType.removeAttribute('required');
        sessionType.value = ''; // Clear value
      }
      if (sessionDuration) {
        sessionDuration.required = false;
        sessionDuration.removeAttribute('required');
        sessionDuration.value = ''; // Clear value
      }
      if (consultationTiming) {
        consultationTiming.required = false;
        consultationTiming.removeAttribute('required');
        consultationTiming.value = ''; // Clear value
      }
      // Hide gap fields when consultation is disabled
      toggleGapFields();
    }
  }
  
  // Toggle gap fields based on consultation timing selection
  function toggleGapFields() {
    const consultationTiming = document.getElementById('consultation_timing');
    const gapFieldsContainer = document.getElementById('gap_fields_container');
    const requireConsultation = document.getElementById('require_consultation');
    
    if (!consultationTiming || !gapFieldsContainer) {
      return; // Elements not found
    }
    
    // Only show gap fields if consultation is required AND timing is separate
    if (requireConsultation && requireConsultation.checked && consultationTiming.value === 'separate') {
      gapFieldsContainer.style.display = 'flex';
    } else {
      gapFieldsContainer.style.display = 'none';
      // Reset gap fields when hidden
      const requireGap = document.getElementById('require_gap_between_consultation_tattoo');
      const gapValue = document.getElementById('consultation_tattoo_gap_value');
      const gapUnit = document.getElementById('consultation_tattoo_gap_unit');
      if (requireGap) requireGap.checked = false;
      if (gapValue) gapValue.value = '';
      if (gapUnit) gapUnit.value = '';
      toggleGapDurationFields();
    }
  }
  
  // Toggle gap duration fields based on require gap checkbox
  function toggleGapDurationFields() {
    const requireGap = document.getElementById('require_gap_between_consultation_tattoo');
    const gapDurationContainer = document.getElementById('gap_duration_container');
    const gapUnitContainer = document.getElementById('gap_unit_container');
    const gapValue = document.getElementById('consultation_tattoo_gap_value');
    const gapUnit = document.getElementById('consultation_tattoo_gap_unit');
    
    if (!requireGap || !gapDurationContainer || !gapUnitContainer) {
      return; // Elements not found
    }
    
    if (requireGap.checked) {
      gapDurationContainer.style.display = 'block';
      gapUnitContainer.style.display = 'block';
      if (gapValue) {
        gapValue.required = true;
        gapValue.setAttribute('required', 'required');
      }
      if (gapUnit) {
        gapUnit.required = true;
        gapUnit.setAttribute('required', 'required');
      }
    } else {
      gapDurationContainer.style.display = 'none';
      gapUnitContainer.style.display = 'none';
      if (gapValue) {
        gapValue.required = false;
        gapValue.removeAttribute('required');
        gapValue.value = '';
      }
      if (gapUnit) {
        gapUnit.required = false;
        gapUnit.removeAttribute('required');
        gapUnit.value = '';
      }
    }
  }
  
  // Make function globally accessible
  window.toggleSessionFields = toggleSessionFields;
  
  // Initialize on page load
  document.addEventListener('DOMContentLoaded', function() {
    toggleSessionFields();
    toggleGapFields();
    toggleGapDurationFields();
  });

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

  // Validate profile step
  function validateProfile() {
    clearErrors();
    const errors = {};
    let isValid = true;

    // Avatar validation (required)
    const avatarInput = document.getElementById('avatar');
    if (!avatarInput) {
      return true; // Not on step 1, skip validation
    }
    
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

    // First name validation (required)
    const firstNameEl = document.getElementById('first_name');
    if (firstNameEl) {
      const firstName = firstNameEl.value.trim();
      if (!firstName) {
        errors.first_name = 'This field is required.';
        isValid = false;
      }
    }

    // Last name validation (required)
    const lastNameEl = document.getElementById('last_name');
    if (lastNameEl) {
      const lastName = lastNameEl.value.trim();
      if (!lastName) {
        errors.last_name = 'This field is required.';
        isValid = false;
      }
    }

    // User name validation (required)
    const userNameEl = document.getElementById('user_name');
    if (userNameEl) {
      const userName = userNameEl.value.trim();
    if (!userName) {
      errors.user_name = 'This field is required.';
      isValid = false;
      }
    }

    // Mobile number validation (required)
    const mobileNumberEl = document.getElementById('mobile_number');
    if (mobileNumberEl) {
      const mobileNumber = mobileNumberEl.value.trim();
    if (!mobileNumber) {
      errors.mobile_number = 'This field is required.';
      isValid = false;
    }
    }


    if (!isValid) {
      displayErrors(errors);
    }

    return isValid;
  }

  // Validate studio step
  function validateStudio() {
    clearErrors();
    const errors = {};
    let isValid = true;

    const studioNameEl = document.getElementById('studio_name');
    if (!studioNameEl) {
      return true; // Not on step 2, skip validation
    }
    
    const studioName = studioNameEl.value.trim();
    if (!studioName) {
      errors.studio_name = 'This field is required.';
      isValid = false;
    }

    const studioAddressEl = document.getElementById('studio_address');
    if (studioAddressEl) {
      const studioAddress = studioAddressEl.value.trim();
    if (!studioAddress) {
      errors.studio_address = 'This field is required.';
      isValid = false;
      }
    }

    const streetNameEl = document.getElementById('street_name');
    if (streetNameEl) {
      const streetName = streetNameEl.value.trim();
      if (!streetName) {
        errors.street_name = 'This field is required.';
        isValid = false;
      }
    }

    const streetNumberEl = document.getElementById('street_number');
    if (streetNumberEl) {
      const streetNumber = streetNumberEl.value.trim();
      if (!streetNumber) {
        errors.street_number = 'This field is required.';
        isValid = false;
      }
    }

    const cityEl = document.getElementById('city');
    if (cityEl) {
      const city = cityEl.value.trim();
      if (!city) {
        errors.city = 'This field is required.';
        isValid = false;
      }
    }

    const stateEl = document.getElementById('state');
    if (stateEl) {
      const state = stateEl.value.trim();
      if (!state) {
        errors.state = 'This field is required.';
        isValid = false;
      }
    }

    const postalCodeEl = document.getElementById('postal_code');
    if (postalCodeEl) {
      const postalCode = postalCodeEl.value.trim();
      if (!postalCode) {
        errors.postal_code = 'This field is required.';
        isValid = false;
      }
    }

    const countryEl = document.getElementById('country');
    if (countryEl) {
      const country = countryEl.value.trim();
      if (!country) {
        errors.country = 'This field is required.';
        isValid = false;
      }
    }

    if (!isValid) {
      displayErrors(errors);
    }

    return isValid;
  }

  // Validate calendar / scheduling step
  function validateCalendar() {
    clearErrors();
    const errors = {};
    let isValid = true;

    const schedulingTypeEl = document.getElementById('scheduling_type');
    if (!schedulingTypeEl) {
      return true; // Not on step 3, skip validation
    }
    
    const schedulingType = schedulingTypeEl.value.trim();
    const schedulingTypeError = document.getElementById('scheduling_type_error');
    
    if (!schedulingType) {
      errors.scheduling_type = 'Please select a scheduling type.';
      isValid = false;
      if (schedulingTypeError) {
        schedulingTypeError.textContent = errors.scheduling_type;
        schedulingTypeError.style.display = 'block';
      }
    } else {
      if (schedulingTypeError) {
        schedulingTypeError.style.display = 'none';
      }
    }

    // If auto scheduling is selected, calendar connection is required
    if (schedulingType === 'auto') {
      const calendarConnectedEl = document.getElementById('google_calendar_connected');
      if (calendarConnectedEl) {
        const calendarConnected = calendarConnectedEl.value;
        if (calendarConnected !== '1') {
          errors.google_calendar_connected = 'Please connect your Google Calendar for auto scheduling.';
          isValid = false;
        }
      } else {
        errors.google_calendar_connected = 'Please connect your Google Calendar for auto scheduling.';
        isValid = false;
      }
    }

    if (!isValid) {
      displayErrors(errors);
    }

    return isValid;
  }

  // Handle scheduling type selection
  function selectSchedulingType(type, element) {
    const schedulingTypeEl = document.getElementById('scheduling_type');
    if (!schedulingTypeEl) {
      return; // Not on step 3, exit early
    }
    
    schedulingTypeEl.value = type;
    
    // Update UI to show selected state
    document.querySelectorAll('.scheduling-option').forEach(option => {
      option.classList.remove('border-primary');
      option.classList.add('border-dashed');
      const icon = option.querySelector('i');
      if (icon) {
        icon.classList.remove('text-primary');
        icon.classList.add('text-muted');
      }
      const badge = option.querySelector('.badge');
      if (badge) {
        badge.remove();
      }
    });

    // Update the selected option
    if (element) {
      element.classList.remove('border-dashed');
      element.classList.add('border-primary');
      const icon = element.querySelector('i');
      if (icon) {
        icon.classList.remove('text-muted');
        icon.classList.add('text-primary');
      }
      
      // Add selected badge
      const cardBody = element.querySelector('.card-body');
      if (cardBody && !cardBody.querySelector('.badge')) {
        const badge = document.createElement('span');
        badge.className = 'badge bg-primary mb-3';
        badge.innerHTML = '<i class="ti ti-check me-1"></i> Selected';
        // Insert after the icon
        const iconElement = cardBody.querySelector('i');
        if (iconElement && iconElement.nextSibling) {
          cardBody.insertBefore(badge, iconElement.nextSibling);
        } else {
          cardBody.insertBefore(badge, cardBody.firstChild.nextSibling);
        }
      }
    }

    // Show/hide calendar connection section
    const calendarSection = document.getElementById('calendarConnectionSection');
    if (calendarSection) {
      if (type === 'auto') {
        calendarSection.style.display = 'flex';
      } else {
        calendarSection.style.display = 'none';
        // Clear calendar connection requirement for managed scheduling
        const calendarConnectedEl = document.getElementById('google_calendar_connected');
        if (calendarConnectedEl) {
          calendarConnectedEl.value = '0';
        }
      }
    }

    // Clear any previous errors
    const schedulingTypeError = document.getElementById('scheduling_type_error');
    if (schedulingTypeError) {
      schedulingTypeError.style.display = 'none';
    }
  }

  // Validate preferences step
  function validatePreferences() {
    clearErrors();
    const errors = {};
    let isValid = true;

    // Currency validation (required)
    const currencyEl = document.getElementById('currency');
    if (!currencyEl) {
      return true; // Not on step 4, skip validation
    }
    
    const currency = currencyEl.value ? currencyEl.value.trim() : ($(currencyEl).val() ? $(currencyEl).val().trim() : '');
    if (!currency) {
      errors.currency = 'This field is required.';
      isValid = false;
    }

    // Timezone validation (required)
    const timezoneEl = document.getElementById('timezone');
    if (timezoneEl) {
      const timezone = timezoneEl.value ? timezoneEl.value.trim() : ($(timezoneEl).val() ? $(timezoneEl).val().trim() : '');
    if (!timezone) {
      errors.timezone = 'This field is required.';
      isValid = false;
      }
    }

    // Date & Time Format validation (required)
    const dateTimeFormatEl = document.getElementById('date_time_format');
    if (dateTimeFormatEl) {
      const dateTimeFormat = dateTimeFormatEl.value.trim();
    if (!dateTimeFormat) {
      errors.date_time_format = 'This field is required.';
      isValid = false;
      }
    }

    // Minimum Deposit Amount validation (required)
    const minDepositAmountEl = document.getElementById('minimum_deposit_amount');
    if (minDepositAmountEl) {
      const minDepositAmount = minDepositAmountEl.value.trim();
    if (!minDepositAmount) {
      errors.minimum_deposit_amount = 'This field is required.';
      isValid = false;
    } else if (isNaN(minDepositAmount) || parseFloat(minDepositAmount) < 0) {
      errors.minimum_deposit_amount = 'Please enter a valid number (0 or greater).';
      isValid = false;
      }
    }

    // Minimum Deposit Type validation (required)
    const minDepositTypeEl = document.getElementById('minimum_deposit_type');
    if (minDepositTypeEl) {
      const minDepositType = minDepositTypeEl.value.trim();
    if (!minDepositType) {
      errors.minimum_deposit_type = 'This field is required.';
      isValid = false;
    }
    }

    // Booking Fee Type validation (required)
    const bookingFeeTypeEls = document.querySelectorAll('input[name="booking_fee_type"]:checked');
    if (bookingFeeTypeEls.length === 0) {
      errors.booking_fee_type = 'Please select a booking fee option.';
      isValid = false;
    }

    // Reschedule Times validation (required)
    const rescheduleTimesEl = document.getElementById('reschedule_times');
    if (rescheduleTimesEl) {
      const rescheduleTimes = rescheduleTimesEl.value.trim();
    if (!rescheduleTimes) {
      errors.reschedule_times = 'This field is required.';
      isValid = false;
      }
    }

    // Reschedule Refund Window validation (required)
    const rescheduleRefundWindowEl = document.getElementById('cancellation_window');
    if (rescheduleRefundWindowEl) {
      const rescheduleRefundWindow = rescheduleRefundWindowEl.value.trim();
      if (!rescheduleRefundWindow) {
        errors.cancellation_window = 'This field is required.';
        isValid = false;
      }
    }

    // Session Buffer Period validation (required)
    const sessionBufferPeriodEl = document.getElementById('session_buffer_period');
    if (sessionBufferPeriodEl) {
      const sessionBufferPeriod = sessionBufferPeriodEl.value.trim();
    if (!sessionBufferPeriod) {
      errors.session_buffer_period = 'This field is required.';
      isValid = false;
    } else if (isNaN(sessionBufferPeriod) || parseInt(sessionBufferPeriod) < 0) {
      errors.session_buffer_period = 'Please enter a valid number (0 or greater).';
      isValid = false;
      }
    }

    // Session Type and Duration validation (only if consultation is required)
    const requireConsultationEl = document.getElementById('require_consultation');
    const requireConsultation = requireConsultationEl && requireConsultationEl.checked;
    if (requireConsultation) {
      const sessionTypeEl = document.getElementById('session_type');
      if (sessionTypeEl) {
        const sessionType = sessionTypeEl.value.trim();
      if (!sessionType) {
        errors.session_type = 'This field is required when consultation is enabled.';
        isValid = false;
        }
      }

      const sessionDurationEl = document.getElementById('session_duration_minutes');
      if (sessionDurationEl) {
        const sessionDuration = sessionDurationEl.value.trim();
      if (!sessionDuration) {
        errors.session_duration_minutes = 'This field is required when consultation is enabled.';
        isValid = false;
      } else if (isNaN(sessionDuration) || parseInt(sessionDuration) < 15 || parseInt(sessionDuration) > 480) {
        errors.session_duration_minutes = 'Please enter a valid duration between 15 and 480 minutes.';
        isValid = false;
        }
      }
    }

    if (!isValid) {
      displayErrors(errors);
    }

    return isValid;
  }

  // Validate payment step (Stripe connection when applicable)
  function validatePayment() {
    clearErrors();
    const errors = {};
    let isValid = true;

    // Check if we're on payment step
    const paymentFormEl = document.getElementById('onboardingPaymentForm');
    if (!paymentFormEl) {
      return true; // Not on payment step, skip validation
    }

    // Check payment type is selected
    const paymentTypeRadios = document.querySelectorAll('input[name="payment_type"]');
    let paymentTypeSelected = false;
    let selectedPaymentType = '';
    
    paymentTypeRadios.forEach(radio => {
      if (radio.checked) {
        paymentTypeSelected = true;
        selectedPaymentType = radio.value;
      }
    });

    if (!paymentTypeSelected) {
      errors.payment_type = 'Please select a payment type.';
      isValid = false;
    }

    // Conditional validation based on payment type
    if (paymentTypeSelected) {
      if (selectedPaymentType === 'artist_account') {
        // Artist account: Stripe must be connected
        const stripeAccountIdEl = document.getElementById('stripe_account_id');
        if (stripeAccountIdEl) {
          const stripeAccountId = stripeAccountIdEl.value.trim();
    if (!stripeAccountId) {
      errors.stripe_account_id = 'Please connect your Stripe account to proceed.';
      isValid = false;
      showAlert('warning', 'Please connect your Stripe account to complete onboarding.');
          }
        }
      } else if (selectedPaymentType === 'studio_account') {
        // Studio account: Email is required
        const studioEmailEl = document.getElementById('studio_email');
        if (studioEmailEl) {
          const studioEmail = studioEmailEl.value.trim();
          if (!studioEmail) {
            errors.studio_email = 'Studio email is required.';
            isValid = false;
          } else {
            // Validate email format
            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            if (!emailRegex.test(studioEmail)) {
              errors.studio_email = 'Please enter a valid email address.';
              isValid = false;
            }
          }
        }
      }
      // inkjin_account: No additional validation needed
    }

    if (!isValid) {
      displayErrors(errors);
    }

    return isValid;
  }

  // Handle payment type change to show/hide sections
  function handlePaymentTypeChange() {
    const paymentTypeRadios = document.querySelectorAll('input[name="payment_type"]');
    const artistSection = document.getElementById('artist_stripe_section');
    const studioSection = document.getElementById('studio_section');
    const inkjinSection = document.getElementById('inkjin_section');

    // Hide all sections first
    if (artistSection) artistSection.style.display = 'none';
    if (studioSection) studioSection.style.display = 'none';
    if (inkjinSection) inkjinSection.style.display = 'none';

    // Show relevant section based on selection
    paymentTypeRadios.forEach(radio => {
      if (radio.checked) {
        if (radio.value === 'artist_account' && artistSection) {
          artistSection.style.display = 'block';
        } else if (radio.value === 'studio_account' && studioSection) {
          studioSection.style.display = 'block';
        } else if (radio.value === 'inkjin_account' && inkjinSection) {
          inkjinSection.style.display = 'block';
        }
      }
    });

    // Clear errors when switching
    clearErrors();
  }

  // Initialize payment type sections on page load
  document.addEventListener('DOMContentLoaded', function() {
    // Trigger initial display based on saved payment type
    const savedPaymentType = document.querySelector('input[name="payment_type"]:checked');
    if (savedPaymentType) {
      handlePaymentTypeChange();
    }
  });

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
      
      // Re-initialize Dropify if navigating to step 1
      if (step === 1) {
        setTimeout(() => {
          initDropify();
        }, 100);
      }
      
      // Initialize Google Places Autocomplete if navigating to step 2
      if (step === 2) {
        setTimeout(() => {
          initializeGooglePlaces();
        }, 100);
      }
      
      // Re-initialize Select2 if navigating to step 4
      if (step === 4) {
        setTimeout(() => {
          if (typeof initializeSelect2 === 'function') {
            initializeSelect2();
          }
          // Initialize session fields toggle
          if (typeof toggleSessionFields === 'function') {
            toggleSessionFields();
          }
        }, 100);
      }
    }
    
    // Update current step variable - allow going back
    currentStep = step;
    
    // Update stepper
    updateStepper(step);
  }

  // Save profile
  document.getElementById('profileForm').addEventListener('submit', async (e) => {
    e.preventDefault();
    
    // Validate before submitting
    if (!validateProfile()) {
      return;
    }

    const formData = new FormData(e.target);
    const submitBtn = e.target.querySelector('button[type="submit"]');
    const originalText = submitBtn.innerHTML;
    submitBtn.disabled = true;
    submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Saving...';

    try {
      const response = await fetch('{{ route("onboarding.profile.save") }}', {
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

  // Save studio
  document.getElementById('studioForm').addEventListener('submit', async (e) => {
    e.preventDefault();
    
    // Validate before submitting
    if (!validateStudio()) {
      return;
    }

    const formData = new FormData(e.target);
    const submitBtn = e.target.querySelector('button[type="submit"]');
    const originalText = submitBtn.innerHTML;
    submitBtn.disabled = true;
    submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Saving...';

    try {
      const response = await fetch('{{ route("onboarding.studio.save") }}', {
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

  // Save calendar / scheduling
  async function saveCalendar() {
    clearErrors();
    
    // Validate before submitting
    if (!validateCalendar()) {
      return;
    }

    const form = document.getElementById('calendarForm');
    const formData = new FormData(form);

    const submitBtn = form.querySelector('button[type="submit"]');
    const originalText = submitBtn.innerHTML;
    submitBtn.disabled = true;
    submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Saving...';

    try {
      const response = await fetch('{{ route("onboarding.calendar.save") }}', {
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

  document.getElementById('calendarForm').addEventListener('submit', (e) => {
    e.preventDefault();
    saveCalendar();
  });

  // Save preferences
  document.getElementById('preferencesForm').addEventListener('submit', async (e) => {
    e.preventDefault();
    
    // Validate before submitting
    if (!validatePreferences()) {
      return;
    }

    const formData = new FormData(e.target);
    
    // Clear session fields if consultation is not required
    const requireConsultation = document.getElementById('require_consultation') && document.getElementById('require_consultation').checked;
    if (!requireConsultation) {
      const sessionType = document.getElementById('session_type');
      const sessionDuration = document.getElementById('session_duration_minutes');
      if (sessionType) {
        sessionType.value = '';
        formData.set('session_type', '');
      }
      if (sessionDuration) {
        sessionDuration.value = '';
        formData.set('session_duration_minutes', '');
      }
    }
    
    const submitBtn = e.target.querySelector('button[type="submit"]');
    const originalText = submitBtn.innerHTML;
    submitBtn.disabled = true;
    submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Saving...';

    try {
      const response = await fetch('{{ route("onboarding.preferences.save") }}', {
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

  // Save payment
  async function savePayment() {
    clearErrors();
    const form = document.getElementById('onboardingPaymentForm');
    const formData = new FormData(form);

    const submitBtn = form.querySelector('button[type="submit"]');
    const originalText = submitBtn.innerHTML;
    submitBtn.disabled = true;
    submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Completing...';

    try {
      const response = await fetch('{{ route("onboarding.payment.save") }}', {
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

  document.getElementById('onboardingPaymentForm').addEventListener('submit', (e) => {
    e.preventDefault();
    if (validatePayment()) {
      savePayment();
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

  // Initialize Google Places Autocomplete
  let autocomplete = null;
  function initializeGooglePlaces() {
    const addressInput = document.getElementById('studio_address');
    if (!addressInput) {
      return;
    }

    // Check if Google Maps API is loaded
    if (typeof google === 'undefined' || !google.maps || !google.maps.places) {
      console.error('Google Maps API not loaded');
      return;
    }

    // Initialize autocomplete
    autocomplete = new google.maps.places.Autocomplete(addressInput, {
      types: ['address'],
      componentRestrictions: { country: [] }, // Allow all countries
      fields: ['address_components', 'formatted_address', 'geometry', 'place_id']
    });

    // Listen for place selection
    autocomplete.addListener('place_changed', function() {
      const place = autocomplete.getPlace();
      
      if (!place.address_components) {
        return;
      }

      // Reset all address fields (with null checks)
      const streetNameEl = document.getElementById('street_name');
      const streetNumberEl = document.getElementById('street_number');
      const cityEl = document.getElementById('city');
      const stateEl = document.getElementById('state');
      const postalCodeEl = document.getElementById('postal_code');
      const countryEl = document.getElementById('country');
      
      if (streetNameEl) streetNameEl.value = '';
      if (streetNumberEl) streetNumberEl.value = '';
      if (cityEl) cityEl.value = '';
      if (stateEl) stateEl.value = '';
      if (postalCodeEl) postalCodeEl.value = '';
      if (countryEl) countryEl.value = '';

      // Parse address components
      let streetNumber = '';
      let streetName = '';
      let city = '';
      let state = '';
      let postalCode = '';
      let country = '';

      for (const component of place.address_components) {
        const types = component.types;

        // Street number
        if (types.includes('street_number')) {
          streetNumber = component.long_name;
        }
        
        // Street name (route)
        if (types.includes('route')) {
          streetName = component.long_name;
  }

        // City - try different types for different countries
        if (types.includes('locality')) {
          city = component.long_name;
        } else if (types.includes('administrative_area_level_2') && !city) {
          city = component.long_name;
        } else if (types.includes('postal_town') && !city) {
          city = component.long_name;
        }
        
        // State/Province
        if (types.includes('administrative_area_level_1')) {
          state = component.short_name || component.long_name;
        }
        
        // Postal code
        if (types.includes('postal_code')) {
          postalCode = component.long_name;
        }
        
        // Country
        if (types.includes('country')) {
          country = component.long_name;
        }
      }

      // Populate form fields (with null checks)
      if (streetNumber && streetNumberEl) {
        streetNumberEl.value = streetNumber;
      }
      if (streetName && streetNameEl) {
        streetNameEl.value = streetName;



      }
      if (city && cityEl) {
        cityEl.value = city;
      }
      if (state && stateEl) {
        stateEl.value = state;
      }
      if (postalCode && postalCodeEl) {
        postalCodeEl.value = postalCode;
      }
      if (country && countryEl) {
        countryEl.value = country;
      }
      
      // Update studio_address with formatted address
      addressInput.value = place.formatted_address;

      // Update Google Maps link if available
      if (place.place_id) {
        const mapsLinkEl = document.getElementById('google_maps_link');
        if (mapsLinkEl) {
          mapsLinkEl.value = `https://www.google.com/maps/place/?q=place_id:${place.place_id}`;
        }
      }
    });
  }

  // Also initialize on page load if already on Step 1, Step 2, or Step 4
  $(document).ready(function() {
    if (currentStep === 1) {
      setTimeout(() => {
        initDropify();
      }, 500);
    }
    if (currentStep === 2) {
      setTimeout(() => {
        initializeGooglePlaces();
      }, 500);
    }
    if (currentStep === 4) {
      setTimeout(() => {
        initializeSelect2();
        toggleSessionFields();
      }, 500);
    }
  });

  $(document).on('change', '#minimum_deposit_type', function() {
    const minimumDepositType = $(this).val();

    $('.deposit-type-selected').text('');
    $('#minimum_deposit_amount').attr('placeholder', '');

    if (minimumDepositType === 'fixed') {
      $('.deposit-type-selected').text(' Amount');
      $('#minimum_deposit_amount').attr('placeholder', 'Enter amount');
    } else if (minimumDepositType === 'percentage') {
      $('.deposit-type-selected').text(' Percentage');
      $('#minimum_deposit_amount').attr('placeholder', 'Enter percentage');
    }
  });
</script>
@endpush

