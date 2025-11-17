@extends('layouts.dashboard_layout')

@section('title', 'Studio Information')

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
          <form method="POST" action="{{ route('onboarding.step1') }}" id="studioForm">
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
                <textarea class="form-control @error('studio_address') is-invalid @enderror" id="studio_address" name="studio_address" rows="3" required>{{ old('studio_address', $userDetail->studio_address ?? '') }}</textarea>
                @error('studio_address')
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

@push('scripts')
<script>
  document.getElementById('studioForm').addEventListener('submit', async function(e) {
    e.preventDefault();
    
    const formData = new FormData(this);
    const submitBtn = this.querySelector('button[type="submit"]');
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
        // Show success message and reload
        window.location.reload();
      } else {
        if (data.errors) {
          // Display validation errors
          Object.keys(data.errors).forEach(field => {
            const input = document.getElementById(field);
            const errorDiv = input?.nextElementSibling;
            if (input) {
              input.classList.add('is-invalid');
            }
            if (errorDiv && errorDiv.classList.contains('invalid-feedback')) {
              errorDiv.textContent = Array.isArray(data.errors[field]) ? data.errors[field][0] : data.errors[field];
            }
          });
        }
        alert(data.message || 'Failed to save studio information');
      }
    } catch (error) {
      alert('An error occurred. Please try again.');
    } finally {
      submitBtn.disabled = false;
      submitBtn.innerHTML = originalText;
    }
  });
</script>
@endpush
@endsection

