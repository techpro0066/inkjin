@extends('layouts.dashboard_layout')

@section('title', 'Profile Settings')

@push('styles')
<style>
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
<div class="container-xxl flex-grow-1 container-p-y">
  <h4 class="fw-bold py-3 mb-4">
    <span class="text-muted fw-light">Settings /</span> Profile
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
          <h5 class="card-title mb-0">Profile Information</h5>
          <p class="text-muted mb-0">Update your profile details</p>
        </div>
        <div class="card-body">
          <form method="POST" action="{{ route('settings.profile.update') }}" id="profileForm" enctype="multipart/form-data">
            @csrf
            <div class="row g-3">
              <div class="col-md-6 mb-3">
                <label class="form-label">Profile Avatar <span class="text-danger">*</span></label>
                <input type="file" class="dropify" id="avatar" name="avatar" data-allowed-file-extensions="jpg jpeg png heif heic" data-max-file-size="2M" data-show-errors="true" data-height="200" data-default-file="{{ $userDetail->avatar ? asset($userDetail->avatar) : '' }}" data-allowed-formats="square">
                <small class="text-muted d-block mt-2">Recommended: 400x400, Max 2MB (JPG, PNG, HEIF, HEIC)</small>
                @error('avatar')
                  <div class="text-danger mt-1" style="font-size: 0.875rem;">{{ $message }}</div>
                @enderror
              </div>
              <div class="col-12"></div>
              
              <div class="col-md-6">
                <label for="user_name" class="form-label">User Name <span class="text-danger">*</span></label>
                <input type="text" class="form-control @error('user_name') is-invalid @enderror" id="user_name" name="user_name" value="{{ old('user_name', $userDetail->user_name ?? '') }}" placeholder="Enter your name" required>
                @error('user_name')
                  <div class="invalid-feedback">{{ $message }}</div>
                @enderror
              </div>
              
              <div class="col-md-6">
                <label for="mobile_number" class="form-label">Mobile Number <span class="text-danger">*</span></label>
                <input type="text" class="form-control @error('mobile_number') is-invalid @enderror" id="mobile_number" name="mobile_number" value="{{ old('mobile_number', $userDetail->mobile_number ?? '') }}" placeholder="Enter mobile number" required>
                @error('mobile_number')
                  <div class="invalid-feedback">{{ $message }}</div>
                @enderror
              </div>
              
              <div class="col-md-6">
                <label for="country" class="form-label">Country <span class="text-danger">*</span></label>
                <select class="form-select select2 @error('country') is-invalid @enderror" id="country" name="country" data-placeholder="Search and select country" required>
                  <option value=""></option>
                </select>
                @error('country')
                  <div class="invalid-feedback">{{ $message }}</div>
                @enderror
              </div>
              
              <div class="col-md-6">
                <label for="city" class="form-label">City <span class="text-danger">*</span></label>
                <select class="form-select select2 @error('city') is-invalid @enderror" id="city" name="city" data-placeholder="Search and select city" required disabled>
                  <option value=""></option>
                </select>
                @error('city')
                  <div class="invalid-feedback">{{ $message }}</div>
                @enderror
              </div>
            </div>
            
            <div class="d-flex justify-content-end mt-4">
              <button type="submit" class="btn btn-primary">
                <i class="ti ti-check me-2"></i>
                Update Profile
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
<!-- Dropify JS -->
<script src="https://cdn.jsdelivr.net/npm/dropify@0.2.2/dist/js/dropify.min.js"></script>

<!-- Select2 JS -->
<script src="{{ asset('assets/vendor/libs/select2/select2.js') }}"></script>

<script>
  const selectedCountry = '{{ old('country', $userDetail->country ?? '') }}';
  const selectedCity = '{{ old('city', $userDetail->city ?? '') }}';
  
  // Store countries and cities data
  let countryCityArray = [];
  let uniqueCountries = [];

  // Initialize Dropify
  $(document).ready(function() {
    $('.dropify').dropify({
      messages: {
        'default': 'Drag and drop an image here or click',
        'replace': 'Drag and drop or click to replace',
        'remove': 'Remove',
        'error': 'Ooops, something wrong happened.'
      },
      error: {
        'fileSize': 'The file size is too big (2MB max).',
        'fileExtension': 'The file extension is not allowed (jpg, jpeg, png, heif, heic only).'
      }
    });

    // Initialize Country Select2
    const countrySelect = $('#country');
    countrySelect.select2({
      placeholder: 'Loading countries...',
      allowClear: true,
      width: '100%'
    });

    // Show loading state
    countrySelect.empty().append(new Option('Loading countries...', '', true, true));
    countrySelect.prop('disabled', true);
    countrySelect.trigger('change');

    // Initialize City Select2
    const citySelect = $('#city');
    citySelect.select2({
      placeholder: 'Select country first',
      allowClear: true,
      width: '100%'
    });
    citySelect.prop('disabled', true);

    // Load all countries and cities from API
    $.ajax({
      url: "https://countriesnow.space/api/v0.1/countries",
      method: "GET",
      success: function(response) {
        if (response.data && Array.isArray(response.data)) {
          // Build country-city array
          countryCityArray = [];
          response.data.forEach(item => {
            const country = item.country;
            if (Array.isArray(item.cities)) {
              item.cities.forEach(city => {
                countryCityArray.push([country, city]);
              });
            }
          });

          // Extract unique countries
          const countrySet = new Set();
          countryCityArray.forEach(item => {
            countrySet.add(item[0]);
          });
          uniqueCountries = Array.from(countrySet).sort();

          // Populate country dropdown
          countrySelect.empty().append(new Option('', ''));
          uniqueCountries.forEach(country => {
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
            loadCities(selectedCountry);
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

    // Handle country change
    countrySelect.on('change', function() {
      const country = $(this).val();
      if (country) {
        loadCities(country);
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
  });

  // Load cities based on selected country from the stored array
  function loadCities(country) {
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
      const citiesForCountry = countryCityArray
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

  // Form submission with validation
  document.getElementById('profileForm').addEventListener('submit', function(e) {
    const avatarInput = document.getElementById('avatar');
    const hasDefaultFile = $(avatarInput).data('default-file') && $(avatarInput).data('default-file') !== '';
    const hasNewFile = avatarInput.files && avatarInput.files.length > 0;
    
    if (!hasDefaultFile && !hasNewFile) {
      e.preventDefault();
      alert('Please upload a profile avatar.');
      return false;
    }
  });
</script>
@endpush

