@extends('layouts.artist_dashboard_layout')

@section('title', 'Profile Settings')

@section('styles')
<style>
  @media (max-width: 1023px) {
      .main-content { overflow-x: hidden; padding: 16px; padding-top: 70px; }
      body { overflow-x: hidden; }
    }
</style>
@endsection

@section('content')

<main class="main-content flex-1 min-h-screen flex flex-col">
  <link rel="stylesheet" href="https://unpkg.com/cropperjs@1.6.2/dist/cropper.min.css">

  <div class="flex-1 p-6 md:p-10 lg:p-12 max-w-4xl">

    <!-- Settings Tabs -->
    <div class="flex items-center gap-1 mb-6 border-b border-outline-variant/20 pb-0 overflow-x-auto">
      <a href="javascript:void(0)" class="px-4 py-3 text-sm font-semibold whitespace-nowrap border-b-2 border-primary text-primary hover:text-on-surface hover:border-outline-variant transition-all">Profile</a>
      <a href="{{ route('settings.styles') }}" class="px-4 py-3 text-sm font-semibold whitespace-nowrap border-b-2 border-transparent text-on-surface-variant hover:text-on-surface hover:border-outline-variant transition-all">Styles &amp; Social</a>
      <a href="{{ route('settings.studio') }}" class="px-4 py-3 text-sm font-semibold whitespace-nowrap border-b-2 border-transparent text-on-surface-variant hover:text-on-surface hover:border-outline-variant transition-all">Studio</a>
      <a href="{{ route('settings.preferences') }}" class="px-4 py-3 text-sm font-semibold whitespace-nowrap border-b-2 border-transparent text-on-surface-variant hover:text-on-surface hover:border-outline-variant transition-all">Preferences</a>
      <a href="{{ route('settings.calendar') }}" class="px-4 py-3 text-sm font-semibold whitespace-nowrap border-b-2 border-transparent text-on-surface-variant hover:text-on-surface hover:border-outline-variant transition-all">Calendar</a>
      <a href="{{route('settings.payment')}}" class="px-4 py-3 text-sm font-semibold whitespace-nowrap border-b-2 border-transparent text-on-surface-variant hover:text-on-surface hover:border-outline-variant transition-all">Payments</a>
      {{-- <a href="{{route('settings.notifications')}}" class="px-4 py-3 text-sm font-semibold whitespace-nowrap border-b-2 border-transparent text-on-surface-variant hover:text-on-surface hover:border-outline-variant transition-all">Notifications</a> --}}
    </div>

    <!-- Page Header -->
    <div class="mb-8">
      <h2 class="text-3xl font-extrabold text-on-surface tracking-tight">Profile Settings</h2>
      <p class="text-on-surface-variant mt-1">Update your personal information and profile photo.</p>
    </div>

    <div id="profile-success-alert" class="hidden mb-6 rounded-xl border border-emerald-200 bg-emerald-50 text-emerald-800 px-4 py-3 text-sm"></div>

    <form id="profileForm" method="post" action="{{ route('profile.update') }}" enctype="multipart/form-data">
      @csrf
      @method('patch')
      <div class="grid grid-cols-1 lg:grid-cols-5 gap-10">
        <div class="lg:col-span-3">
          <div class="bg-surface-container-low rounded-2xl p-6 space-y-6">
            <div class="grid grid-cols-2 gap-4">
              <div>
                <label for="first_name" class="block text-sm font-semibold text-on-surface mb-2">First Name</label>
                <input type="text" id="first_name" name="first_name" value="{{ old('first_name', $user->first_name) }}" class="w-full text-sm border border-outline-variant/30 rounded-xl px-4 py-3 bg-white text-on-surface focus:outline-none focus:ring-2 focus:ring-primary/30">
                <p id="first_name_error" class="text-error text-xs mt-1 hidden"></p>
              </div>
              <div>
                <label for="last_name" class="block text-sm font-semibold text-on-surface mb-2">Last Name</label>
                <input type="text" id="last_name" name="last_name" value="{{ old('last_name', $user->last_name) }}" class="w-full text-sm border border-outline-variant/30 rounded-xl px-4 py-3 bg-white text-on-surface focus:outline-none focus:ring-2 focus:ring-primary/30">
                <p id="last_name_error" class="text-error text-xs mt-1 hidden"></p>
              </div>
            </div>

            <div>
              <label for="user_name" class="block text-sm font-semibold text-on-surface mb-2">Username</label>
              <p class="text-on-surface-variant text-sm leading-relaxed mb-2">Match your Inkjin username to your Instagram handle and give your customers a better experience.</p>
              <div class="relative">
                <span class="absolute left-4 top-1/2 -translate-y-1/2 text-outline text-sm font-medium">@</span>
                <input type="text" id="user_name" name="user_name" value="{{ old('user_name', $userDetail->user_name ?? '') }}" class="w-full text-sm border border-outline-variant/30 rounded-xl pl-9 pr-4 py-3 bg-white text-on-surface focus:outline-none focus:ring-2 focus:ring-primary/30">
              </div>
              <p class="text-on-surface-variant text-xs mt-1">Use only letters, numbers, periods (.), and underscores (_). Max 30 characters.</p>
              <p id="user_name_error" class="text-error text-xs mt-1 hidden"></p>
            </div>

            <div>
              <label for="mobile_number" class="block text-sm font-semibold text-on-surface mb-2">Mobile Number</label>
              <input type="tel" id="mobile_number" name="mobile_number" value="{{ old('mobile_number', $userDetail->mobile_number ?? '') }}" class="w-full text-sm border border-outline-variant/30 rounded-xl px-4 py-3 bg-white text-on-surface focus:outline-none focus:ring-2 focus:ring-primary/30">
              <p class="text-on-surface-variant text-xs mt-1">Use E.164 format: starts with + and country code, no spaces or symbols.</p>
              <p id="mobile_number_error" class="text-error text-xs mt-1 hidden"></p>
            </div>

            <div>
              <label for="email" class="block text-sm font-semibold text-on-surface mb-2">Email</label>
              <input type="email" id="email" value="{{ $user->email }}" readonly class="w-full text-sm border border-outline-variant/30 rounded-xl px-4 py-3 bg-surface text-on-surface-variant" style="cursor: not-allowed; background-color: #e5e5e5;">
            </div>
          </div>
        </div>

        <div class="lg:col-span-2 flex flex-col items-center">
          <div class="w-full max-w-[240px]">
            <div class="relative w-48 h-48 mx-auto mb-4">
              <img id="profilePreview" src="{{ $userDetail && $userDetail->avatar ? asset($userDetail->avatar) : '' }}" alt="" class="{{ $userDetail && $userDetail->avatar ? '' : 'hidden' }} w-full h-full rounded-full object-cover border-2 border-outline-variant bg-surface-container-low">
              <div id="uploadPlaceholder" class="{{ $userDetail && $userDetail->avatar ? 'hidden' : '' }} w-full h-full rounded-full border-2 border-dashed border-outline-variant bg-surface-container-low flex items-center justify-center">
                <span class="material-symbols-outlined text-5xl text-primary/40">photo_camera</span>
              </div>
              <button type="button" id="openUploadBtn" class="absolute bottom-2 right-2 w-10 h-10 rounded-full bg-primary text-white flex items-center justify-center shadow-lg shadow-primary/30 border-2 border-white hover:opacity-90 transition-opacity">
                <span class="material-symbols-outlined text-lg">upload</span>
              </button>
              <input id="profileImageInput" type="file" name="avatar" accept="image/*" class="hidden">
            </div>
            <p class="text-center font-semibold text-on-surface text-sm">Change Photo</p>
            <p class="text-center text-on-surface-variant text-xs mt-1 leading-relaxed">Tip: Professional headshots increase booking conversion by 40%.</p>
            <p id="avatar_error" class="text-error text-xs mt-2 text-center hidden"></p>
          </div>
        </div>
      </div>

      <div class="sticky bottom-0 bg-surface border-t border-outline-variant/10 px-6 md:px-10 lg:px-12 py-5 flex items-center justify-end mt-8">
        <button type="submit" id="saveProfileBtn" class="inline-flex items-center gap-2 bg-gradient-to-br from-primary to-primary-container text-white font-bold py-3 px-8 rounded-xl shadow-lg shadow-primary/20 hover:opacity-90 transition-all active:scale-[0.98]">
          <span class="material-symbols-outlined text-lg">save</span> Save Changes
        </button>
      </div>
    </form>
  </div>

  <div id="cropperModal" class="hidden fixed inset-0 z-[100] bg-black/70 items-center justify-center p-4">
    <div class="w-full max-w-xl rounded-2xl bg-white p-4 md:p-6 shadow-2xl">
      <h3 class="text-lg font-bold text-on-surface mb-2">Crop Profile Photo</h3>
      <p class="text-sm text-on-surface-variant mb-4">Adjust your image to a square crop for a uniform profile photo.</p>
      <div class="w-full h-[360px] bg-surface-container rounded-xl overflow-hidden">
        <img id="cropImage" src="" alt="" class="max-w-full">
      </div>
      <div class="mt-5 flex items-center justify-end gap-3">
        <button id="cancelCropBtn" type="button" class="px-4 py-2 rounded-lg border border-outline-variant/40 text-on-surface-variant hover:bg-surface-container-low">Cancel</button>
        <button id="applyCropBtn" type="button" class="px-5 py-2 rounded-lg bg-primary text-white font-semibold hover:opacity-90">Use Photo</button>
      </div>
    </div>
  </div>

  <script src="https://unpkg.com/cropperjs@1.6.2/dist/cropper.min.js"></script>
  <script>
    (function () {
      var USERNAME_PATTERN = /^[A-Za-z0-9._]{1,30}$/;
      var E164_PATTERN = /^\+[1-9]\d{1,14}$/;
      var cropper = null;
      var objectUrl = '';
      var croppedBlob = null;

      var profileForm = document.getElementById('profileForm');
      var saveBtn = document.getElementById('saveProfileBtn');
      var successAlert = document.getElementById('profile-success-alert');
      var openUploadBtn = document.getElementById('openUploadBtn');
      var profileImageInput = document.getElementById('profileImageInput');
      var cropperModal = document.getElementById('cropperModal');
      var cropImage = document.getElementById('cropImage');
      var profilePreview = document.getElementById('profilePreview');
      var uploadPlaceholder = document.getElementById('uploadPlaceholder');

      function clearFieldError(fieldName) {
        var input = document.getElementById(fieldName);
        var error = document.getElementById(fieldName + '_error');
        if (input) input.classList.remove('border-error');
        if (error) {
          error.textContent = '';
          error.classList.add('hidden');
        }
      }

      function setFieldError(fieldName, message) {
        var input = document.getElementById(fieldName);
        var error = document.getElementById(fieldName + '_error');
        if (input) input.classList.add('border-error');
        if (error) {
          error.textContent = message;
          error.classList.remove('hidden');
        }
      }

      function clearAllErrors() {
        ['avatar', 'first_name', 'last_name', 'user_name', 'mobile_number'].forEach(clearFieldError);
      }

      function scrollToFirstError() {
        var firstError = profileForm.querySelector('p[id$="_error"]:not(.hidden)');
        if (firstError) firstError.scrollIntoView({ behavior: 'smooth', block: 'center' });
      }

      function validateProfileClient() {
        var ok = true;

        var firstName = (document.getElementById('first_name').value || '').trim();
        var lastName = (document.getElementById('last_name').value || '').trim();
        var userName = (document.getElementById('user_name').value || '').trim();
        var mobile = (document.getElementById('mobile_number').value || '').trim();

        if (!firstName) {
          setFieldError('first_name', 'First name is required.');
          ok = false;
        }

        if (!lastName) {
          setFieldError('last_name', 'Last name is required.');
          ok = false;
        }

        if (!userName) {
          setFieldError('user_name', 'Username is required.');
          ok = false;
        } else if (!USERNAME_PATTERN.test(userName)) {
          setFieldError('user_name', 'Username can only include letters, numbers, periods (.) and underscores (_) and must be 1-30 characters.');
          ok = false;
        }

        if (!mobile) {
          setFieldError('mobile_number', 'Mobile number is required.');
          ok = false;
        } else if (!E164_PATTERN.test(mobile)) {
          setFieldError('mobile_number', 'Mobile number must be in E.164 format (example: +447911123456) with no spaces, dashes, or parentheses.');
          ok = false;
        }

        return ok;
      }

      ['first_name', 'last_name', 'user_name', 'mobile_number'].forEach(function (field) {
        var el = document.getElementById(field);
        if (el) el.addEventListener('input', function () { clearFieldError(field); });
      });

      openUploadBtn.addEventListener('click', function () {
        profileImageInput.click();
      });

      profileImageInput.addEventListener('change', function (e) {
        clearFieldError('avatar');
        var file = e.target.files && e.target.files[0];
        if (!file) return;
        if (objectUrl) URL.revokeObjectURL(objectUrl);
        objectUrl = URL.createObjectURL(file);
        cropImage.src = objectUrl;
        cropperModal.classList.remove('hidden');
        cropperModal.classList.add('flex');
        if (cropper) cropper.destroy();
        cropper = new Cropper(cropImage, {
          aspectRatio: 1,
          viewMode: 1,
          dragMode: 'move',
          background: false,
          autoCropArea: 1,
          responsive: true
        });
      });

      function closeCropperModal() {
        cropperModal.classList.add('hidden');
        cropperModal.classList.remove('flex');
        if (cropper) {
          cropper.destroy();
          cropper = null;
        }
        cropImage.src = '';
        profileImageInput.value = '';
        if (objectUrl) {
          URL.revokeObjectURL(objectUrl);
          objectUrl = '';
        }
      }

      document.getElementById('cancelCropBtn').addEventListener('click', closeCropperModal);
      cropperModal.addEventListener('click', function (e) {
        if (e.target === cropperModal) closeCropperModal();
      });

      document.getElementById('applyCropBtn').addEventListener('click', function () {
        if (!cropper) return;
        var canvas = cropper.getCroppedCanvas({ width: 512, height: 512, imageSmoothingQuality: 'high' });
        canvas.toBlob(function (blob) {
          croppedBlob = blob;
          profilePreview.src = URL.createObjectURL(blob);
          profilePreview.classList.remove('hidden');
          uploadPlaceholder.classList.add('hidden');
          clearFieldError('avatar');
          closeCropperModal();
        }, 'image/jpeg', 0.92);
      });

      profileForm.addEventListener('submit', function (e) {
        e.preventDefault();
        clearAllErrors();
        if (!validateProfileClient()) {
          scrollToFirstError();
          return;
        }
        successAlert.classList.add('hidden');
        saveBtn.disabled = true;
        saveBtn.innerHTML = '<span class="material-symbols-outlined text-lg">hourglass_top</span> Saving...';

        var formData = new FormData(profileForm);
        if (croppedBlob) {
          formData.delete('avatar');
          formData.append('avatar', croppedBlob, 'avatar.jpg');
        }

        fetch(@json(route('profile.update')), {
          method: 'POST',
          headers: {
            'X-CSRF-TOKEN': @json(csrf_token()),
            'X-Requested-With': 'XMLHttpRequest',
            Accept: 'application/json'
          },
          body: formData
        })
          .then(function (response) {
            return response.json().then(function (data) {
              return { status: response.status, ok: response.ok, data: data };
            });
          })
          .then(function (result) {
            if (result.ok && result.data.success) {
              successAlert.textContent = result.data.message || 'Profile updated successfully!';
              successAlert.classList.remove('hidden');
              showSaveToast();
              if (result.data.avatar) {
                profilePreview.src = result.data.avatar;
                profilePreview.classList.remove('hidden');
                uploadPlaceholder.classList.add('hidden');
              }
              return;
            }
            if (result.status === 422 && result.data && result.data.errors) {
              Object.keys(result.data.errors).forEach(function (field) {
                setFieldError(field, result.data.errors[field][0]);
              });
              scrollToFirstError();
              return;
            }
            alert('Something went wrong. Please try again.');
          })
          .catch(function () {
            alert('Network error. Please try again.');
          })
          .finally(function () {
            saveBtn.disabled = false;
            saveBtn.innerHTML = '<span class="material-symbols-outlined text-lg">save</span> Save Changes';
          });
      });
    })();
  </script>
</main>
@endsection