@extends('layouts.dashboard_layout')

@section('title', 'Studio Information')

@push('styles')
<!-- Google Places API -->
<script src="https://maps.googleapis.com/maps/api/js?key={{ env('GOOGLE_PLACE_API_KEY') }}&libraries=places"></script>
@endpush

@section('content')
<div class="container-xxl flex-grow-1 container-p-y">
  <h4 class="fw-bold py-3 mb-4">
    <span class="text-muted fw-light">Settings /</span> Studio Information
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
          <h5 class="card-title mb-0">Studio Details</h5>
          <p class="text-muted mb-0">Update your studio information</p>
        </div>
        <div class="card-body">
          <form method="POST" action="{{ route('settings.studio.update') }}" id="studioForm">
            @csrf
            <div class="row g-3">
              <div class="col-12">
                <label for="studio_name" class="form-label">Studio Name <span class="text-danger">*</span></label>
                <input type="text" class="form-control @error('studio_name') is-invalid @enderror" id="studio_name" name="studio_name" value="{{ old('studio_name', $userDetail->studio_name ?? '') }}" required>
                @error('studio_name')
                  <div class="invalid-feedback">{{ $message }}</div>
                @enderror
              </div>
              
              <div class="col-12">
                <label for="studio_address" class="form-label">Studio Address <span class="text-danger">*</span></label>
                <input type="text" class="form-control @error('studio_address') is-invalid @enderror" id="studio_address" name="studio_address" value="{{ old('studio_address', $userDetail->studio_address ?? '') }}" placeholder="Start typing your address..." required>
                <small class="text-muted d-block mt-1">Start typing and select from Google suggestions to auto-fill address fields</small>
                @error('studio_address')
                  <div class="invalid-feedback">{{ $message }}</div>
                @enderror
              </div>

              <div class="col-md-6">
                <label for="street_name" class="form-label">Street Name <span class="text-danger">*</span></label>
                <input type="text" class="form-control @error('street_name') is-invalid @enderror" id="street_name" name="street_name" value="{{ old('street_name', $userDetail->street_name ?? '') }}" placeholder="Enter street name" required>
                @error('street_name')
                  <div class="invalid-feedback">{{ $message }}</div>
                @enderror
              </div>

              <div class="col-md-6">
                <label for="street_number" class="form-label">Street Number <span class="text-danger">*</span></label>
                <input type="text" class="form-control @error('street_number') is-invalid @enderror" id="street_number" name="street_number" value="{{ old('street_number', $userDetail->street_number ?? '') }}" placeholder="Enter street number" required>
                @error('street_number')
                  <div class="invalid-feedback">{{ $message }}</div>
                @enderror
              </div>

              <div class="col-md-6">
                <label for="city" class="form-label">City <span class="text-danger">*</span></label>
                <input type="text" class="form-control @error('city') is-invalid @enderror" id="city" name="city" value="{{ old('city', $userDetail->city ?? '') }}" placeholder="Enter city" required>
                @error('city')
                  <div class="invalid-feedback">{{ $message }}</div>
                @enderror
              </div>

              <div class="col-md-6">
                <label for="state" class="form-label">Province/State <span class="text-danger">*</span></label>
                <input type="text" class="form-control @error('state') is-invalid @enderror" id="state" name="state" value="{{ old('state', $userDetail->state ?? '') }}" placeholder="Enter state" required>
                @error('state')
                  <div class="invalid-feedback">{{ $message }}</div>
                @enderror
              </div>

              <div class="col-md-6">
                <label for="postal_code" class="form-label">Postal Code <span class="text-danger">*</span></label>
                <input type="text" class="form-control @error('postal_code') is-invalid @enderror" id="postal_code" name="postal_code" value="{{ old('postal_code', $userDetail->postal_code ?? '') }}" placeholder="Enter postal code" required>
                @error('postal_code')
                  <div class="invalid-feedback">{{ $message }}</div>
                @enderror
              </div>

              <div class="col-md-6">
                <label for="country" class="form-label">Country <span class="text-danger">*</span></label>
                <input type="text" class="form-control @error('country') is-invalid @enderror" id="country" name="country" value="{{ old('country', $userDetail->country ?? '') }}" placeholder="Enter country" required>
                @error('country')
                  <div class="invalid-feedback">{{ $message }}</div>
                @enderror
              </div>
              
              <div class="col-12">
                <label for="google_maps_link" class="form-label">Google Maps Link</label>
                <input type="text" class="form-control @error('google_maps_link') is-invalid @enderror" id="google_maps_link" name="google_maps_link" value="{{ old('google_maps_link', $userDetail->google_maps_link ?? '') }}" placeholder="https://maps.google.com/...">
                <small class="text-muted">Optional: Add your studio's Google Maps link</small>
                @error('google_maps_link')
                  <div class="invalid-feedback">{{ $message }}</div>
                @enderror
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
@endsection

@push('scripts')
<script>
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

  // Initialize on page load
  $(document).ready(function() {
    setTimeout(() => {
      initializeGooglePlaces();
    }, 500);
  });
</script>
@endpush
