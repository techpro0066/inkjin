@extends('layouts.dashboard_layout')

@section('title', 'Update Profile')

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

<style>
  .cursor-pointer {
    cursor: pointer;
  }
  .dropify-wrapper .dropify-message p {
    font-size: 0.875rem;
  }
  .input-group-merge .input-group-text {
    border-left: 0;
  }
  .input-group-merge .form-control {
    border-right: 0;
  }
  .input-group-merge .form-control:focus {
    border-right: 0;
  }
  .input-group-merge .form-control.is-invalid {
    border-right: 0;
  }
</style>
@endpush

@section('content')
<div class="container-xxl flex-grow-1 container-p-y">
  <h4 class="fw-bold py-3 mb-4">
    <span class="text-muted fw-light">Settings /</span> Update Profile
  </h4>
  

  @if (session('status') === 'profile-updated')
    <div class="alert alert-success alert-dismissible fade show" role="alert">
      <i class="ti ti-check-circle me-2"></i>
      Profile updated successfully!
      <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
  @endif

  @if (session('status') === 'password-updated')
    <div class="alert alert-success alert-dismissible fade show" role="alert">
      <i class="ti ti-check-circle me-2"></i>
      Password updated successfully!
      <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
  @endif


  <div class="row">
    <!-- Update Profile Information -->
    <div class="col-12 mb-4">
      <div class="card">
        <div class="card-header">
          <h5 class="card-title mb-0">Profile Information</h5>
          <p class="text-muted mb-0">Update your account's basic profile information. Email cannot be changed here.</p>
        </div>
        <div class="card-body">
          <form method="post" action="{{ route('profile.update') }}" enctype="multipart/form-data">
            @csrf
            @method('patch')

            <div class="row g-4">
              <div class="col-md-4">
                <label class="form-label">Profile Avatar</label>
                <input
                  type="file"
                  class="dropify"
                  id="avatar"
                  name="avatar"
                  data-allowed-file-extensions="jpg jpeg png heif heic"
                  data-max-file-size="2M"
                  data-show-errors="true"
                  data-height="180"
                  data-default-file="{{ $userDetail && $userDetail->avatar ? asset($userDetail->avatar) : '' }}"
                >
                <small class="text-muted d-block mt-2">
                  Recommended: 400x400, Max 2MB (JPG, PNG, HEIF, HEIC)
                </small>
              </div>

              <div class="col-md-8">
                <div class="row">
                  <div class="col-md-6 mb-3">
                    <label for="first_name" class="form-label">First Name</label>
                    <input
                      type="text"
                      class="form-control @error('first_name') is-invalid @enderror"
                      id="first_name"
                      name="first_name"
                      value="{{ old('first_name', $user->first_name) }}"
                      required
                      autofocus
                    >
                    @error('first_name')
                      <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                  </div>

                  <div class="col-md-6 mb-3">
                    <label for="last_name" class="form-label">Last Name</label>
                    <input
                      type="text"
                      class="form-control @error('last_name') is-invalid @enderror"
                      id="last_name"
                      name="last_name"
                      value="{{ old('last_name', $user->last_name) }}"
                      required
                    >
                    @error('last_name')
                      <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                  </div>

                  <div class="col-md-6 mb-3">
                    <label for="user_name" class="form-label">User Name</label>
                    <input
                      type="text"
                      class="form-control @error('user_name') is-invalid @enderror"
                      id="user_name"
                      name="user_name"
                      value="{{ old('user_name', $userDetail->user_name ?? '') }}"
                      required
                    >
                    @error('user_name')
                      <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                  </div>

                  <div class="col-md-6 mb-3">
                    <label for="mobile_number" class="form-label">Mobile Number</label>
                    <input
                      type="text"
                      class="form-control @error('mobile_number') is-invalid @enderror"
                      id="mobile_number"
                      name="mobile_number"
                      value="{{ old('mobile_number', $userDetail->mobile_number ?? '') }}"
                      required
                    >
                    @error('mobile_number')
                      <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                  </div>

                  <div class="col-12 mb-3">
                    <label for="email" class="form-label">Email</label>
                    <input
                      type="email"
                      class="form-control bg-light"
                      id="email"
                      name="email"
                      value="{{ $user->email }}"
                      readonly
                    >
                  </div>
                </div>
              </div>
            </div>

            <div class="d-flex justify-content-end mt-3">
              <button type="submit" class="btn btn-primary">
                <i class="ti ti-device-floppy me-2"></i>
                Save Changes
              </button>
            </div>
          </form>
        </div>
      </div>
    </div>

    <!-- Update Password -->
    <div class="col-12 mb-4" id="password">
      <div class="card">
        <div class="card-header">
          <h5 class="card-title mb-0">Update Password</h5>
          <p class="text-muted mb-0">Ensure your account is using a long, random password to stay secure.</p>
        </div>
        <div class="card-body">
          <form method="post" action="{{ route('password.update') }}">
            @csrf
            @method('put')

            <div class="mb-3">
              <label for="current_password" class="form-label">Current Password</label>
              <div class="input-group input-group-merge">
                <input type="password" class="form-control @error('current_password', 'updatePassword') is-invalid @enderror" id="current_password" name="current_password" autocomplete="current-password">
                <span class="input-group-text cursor-pointer" id="toggle-current-password">
                  <i class="ti ti-eye eye-icon" id="eye-current-password"></i>
                </span>
                @error('current_password', 'updatePassword')
                  <div class="invalid-feedback">{{ $message }}</div>
                @enderror
              </div>
            </div>

            <div class="mb-3">
              <label for="password" class="form-label">New Password</label>
              <div class="input-group input-group-merge">
                <input type="password" class="form-control @error('password', 'updatePassword') is-invalid @enderror" id="password" name="password" autocomplete="new-password">
                <span class="input-group-text cursor-pointer" id="toggle-password">
                  <i class="ti ti-eye eye-icon" id="eye-password"></i>
                </span>
                @error('password', 'updatePassword')
                  <div class="invalid-feedback">{{ $message }}</div>
                @enderror
                </div>
            </div>

            <div class="mb-3">
              <label for="password_confirmation" class="form-label">Confirm Password</label>
              <div class="input-group input-group-merge">
                <input type="password" class="form-control" id="password_confirmation" name="password_confirmation" autocomplete="new-password">
                <span class="input-group-text cursor-pointer" id="toggle-password-confirmation">
                  <i class="ti ti-eye eye-icon" id="eye-password-confirmation"></i>
                </span>
                </div>
            </div>

            <div class="d-flex justify-content-end">
              <button type="submit" class="btn btn-primary">
                <i class="ti ti-key me-2"></i>
                Update Password
              </button>
            </div>
          </form>
            </div>
        </div>
    </div>

    <!-- Delete Account -->
    <div class="col-12 mb-4">
      <div class="card">
        <div class="card-header">
          <h5 class="card-title mb-0 text-danger">Delete Account</h5>
          <p class="text-muted mb-0">Once your account is deleted, all of its resources and data will be permanently deleted.</p>
        </div>
        <div class="card-body">
          <p class="text-muted mb-4">
            Before deleting your account, please download any data or information that you wish to retain. Once your account is deleted, all of its resources and data will be permanently deleted.
          </p>
          <button type="button" class="btn btn-danger" id="deleteAccountBtn" data-bs-toggle="modal" data-bs-target="#deleteAccountModal">
            <i class="ti ti-trash me-2"></i>
            Delete Account
          </button>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- Delete Account Confirmation Modal -->
<div class="modal fade" id="deleteAccountModal" tabindex="-1" aria-labelledby="deleteAccountModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="deleteAccountModalLabel">
          <i class="ti ti-alert-triangle text-danger me-2"></i>
          Delete Account
        </h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <form method="post" action="{{ route('profile.destroy') }}" id="deleteAccountForm">
        @csrf
        @method('delete')
        <div class="modal-body">
          <p class="mb-3">Are you sure you want to delete your account? Once your account is deleted, all of its resources and data will be permanently deleted. Please enter your password to confirm you would like to permanently delete your account.</p>
          
          <div class="mb-3">
            <label for="delete_password" class="form-label">Password</label>
            <input type="password" class="form-control @error('password', 'userDeletion') is-invalid @enderror" id="delete_password" name="password" placeholder="Enter your password" required autofocus>
            @error('password', 'userDeletion')
              <div class="invalid-feedback">{{ $message }}</div>
            @enderror
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-label-secondary" data-bs-dismiss="modal">
            Cancel
          </button>
          <button type="submit" class="btn btn-danger" id="confirmDeleteBtn">
            <i class="ti ti-trash me-2"></i>
            Delete Account
          </button>
        </div>
      </form>
    </div>
  </div>
</div>
@endsection

@push('scripts')
<!-- Dropify JS -->
<script src="https://cdn.jsdelivr.net/npm/dropify@0.2.2/dist/js/dropify.min.js"></script>
<script>
  // Function to scroll to first error on page
  function scrollToFirstError() {
    // Check for Laravel validation errors (server-side)
    const invalidFields = document.querySelectorAll('.is-invalid, .form-control.is-invalid, .form-select.is-invalid');
    if (invalidFields.length > 0) {
      setTimeout(() => {
        const firstField = invalidFields[0];
        firstField.scrollIntoView({ behavior: 'smooth', block: 'center' });
        if (firstField.focus) {
          firstField.focus();
        }
      }, 100);
    }
  }

  // Password toggle functionality
  function initPasswordToggle() {
    // Toggle for Current Password
    $('.eye-icon').on('click', function() {
      const input = $(this).closest('.input-group-merge').find('input');
      
      if (input.attr('type') === 'password') {
        input.attr('type', 'text');
        $(this).removeClass('ti-eye').addClass('ti-eye-off');
      } else {
        input.attr('type', 'password');
        $(this).removeClass('ti-eye-off').addClass('ti-eye');
      }
    });
  }

  // Delete Account Modal Handling
  const deleteAccountModal = document.getElementById('deleteAccountModal');
  const deleteAccountForm = document.getElementById('deleteAccountForm');
  
  // Show modal if there are validation errors
  @if($errors->userDeletion->any())
    const modal = new bootstrap.Modal(deleteAccountModal);
    modal.show();
  @endif

  // Reset form when modal is hidden
  if (deleteAccountModal) {
    deleteAccountModal.addEventListener('hidden.bs.modal', function () {
      deleteAccountForm.reset();
      // Clear validation errors
      const passwordInput = document.getElementById('delete_password');
      if (passwordInput) {
        passwordInput.classList.remove('is-invalid');
        const errorFeedback = passwordInput.parentElement.querySelector('.invalid-feedback');
        if (errorFeedback) {
          errorFeedback.remove();
        }
      }
    });
  }

  // Handle form submission with loading state
  if (deleteAccountForm) {
    deleteAccountForm.addEventListener('submit', function(e) {
      const confirmBtn = document.getElementById('confirmDeleteBtn');
      if (confirmBtn) {
        confirmBtn.disabled = true;
        confirmBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Deleting...';
      }
    });
  }

  // Initialize on page load
  $(document).ready(function() {
    // Init Dropify for avatar
    if ($('.dropify').length) {
      $('.dropify').dropify();
    }

    initPasswordToggle();
    // Scroll to errors if page has validation errors
    scrollToFirstError();
  });
</script>
@endpush
