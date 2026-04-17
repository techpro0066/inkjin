@extends('layouts.onboarding_bookpay')

@section('title', 'Profile')

@push('head')
<link rel="stylesheet" href="https://unpkg.com/cropperjs@1.6.2/dist/cropper.min.css">
@endpush

@section('content')
<form id="profileForm" class="contents" enctype="multipart/form-data">
  @csrf
  <div class="flex-1 p-8 md:p-12 max-w-4xl">
    <div class="mb-10">
      <h2 class="text-3xl font-extrabold text-on-surface tracking-tight">Let's build your profile</h2>
      <p class="text-on-surface-variant mt-2 max-w-lg">Tell us a bit about yourself. This information will be used to set up your professional profile and handle payments securely.</p>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-5 gap-10">
      <div class="lg:col-span-3 space-y-6">
        <div class="grid grid-cols-2 gap-4">
          <div>
            <label for="first_name" class="block text-sm font-semibold text-on-surface mb-2">First Name <span class="text-red-600">*</span></label>
            <input type="text" id="first_name" name="first_name" value="{{ Auth::user()->first_name }}" placeholder="e.g. Julian"
              class="w-full px-4 py-3 rounded-xl border border-outline-variant/30 bg-white focus:ring-2 focus:ring-primary/40 transition-all text-on-surface placeholder:text-outline/50">
            <p id="first_name_error" class="text-error text-xs mt-1 hidden"></p>
          </div>
          <div>
            <label for="last_name" class="block text-sm font-semibold text-on-surface mb-2">Last Name <span class="text-red-600">*</span></label>
            <input type="text" id="last_name" name="last_name" value="{{ Auth::user()->last_name }}" placeholder="e.g. Ink"
              class="w-full px-4 py-3 rounded-xl border border-outline-variant/30 bg-white focus:ring-2 focus:ring-primary/40 transition-all text-on-surface placeholder:text-outline/50">
            <p id="last_name_error" class="text-error text-xs mt-1 hidden"></p>
          </div>
        </div>
        <div>
          <label for="user_name" class="block text-sm font-semibold text-on-surface mb-2">Username <span class="text-red-600">*</span></label>
          <p class="text-on-surface-variant text-sm leading-relaxed mb-3">Match your Inkjin username to your Instagram handle and give your customers a better experience.</p>
          <input type="text" id="user_name" name="user_name" value="{{ $userDetail->user_name ?? '' }}" placeholder="username"
            class="w-full px-4 py-3 rounded-xl border border-outline-variant/30 bg-white focus:ring-2 focus:ring-primary/40 transition-all text-on-surface placeholder:text-outline/50">
          <p class="text-on-surface-variant text-xs mt-1">Use only letters, numbers, periods (.), and underscores (_). Max 30 characters.</p>
          <p id="user_name_error" class="text-error text-xs mt-1 hidden"></p>
        </div>
        <div>
          <label for="mobile_number" class="block text-sm font-semibold text-on-surface mb-2">Mobile Number <span class="text-red-600">*</span></label>
          <input type="tel" id="mobile_number" name="mobile_number" value="{{ $userDetail->mobile_number ?? '' }}" placeholder="+15550000000"
            class="w-full px-4 py-3 rounded-xl border border-outline-variant/30 bg-white focus:ring-2 focus:ring-primary/40 transition-all text-on-surface placeholder:text-outline/50">
          <p class="text-on-surface-variant text-xs mt-1">Use E.164 format: starts with + and country code, no spaces or symbols.</p>
          <p id="mobile_number_error" class="text-error text-xs mt-1 hidden"></p>
        </div>
      </div>
      <div class="lg:col-span-2 flex flex-col items-center">
        <div class="w-full max-w-[240px]">
          <div class="relative w-48 h-48 mx-auto mb-4">
            <img id="profilePreview" src="{{ $userDetail->avatar ? asset($userDetail->avatar) : '' }}" alt="" class="{{ $userDetail->avatar ? '' : 'hidden' }} w-full h-full rounded-full object-cover border-2 border-outline-variant bg-surface-container-low">
            <div id="uploadPlaceholder" class="{{ $userDetail->avatar ? 'hidden' : '' }} w-full h-full rounded-full border-2 border-dashed border-outline-variant bg-surface-container-low flex items-center justify-center">
              <span class="material-symbols-outlined text-5xl text-primary/40">photo_camera</span>
            </div>
            <button type="button" id="openUploadBtn" class="absolute bottom-2 right-2 w-10 h-10 rounded-full bg-primary text-white flex items-center justify-center shadow-lg shadow-primary/30 border-2 border-white hover:opacity-90 transition-opacity">
              <span class="material-symbols-outlined text-lg">upload</span>
            </button>
            <input id="profileImageInput" type="file" name="avatar" accept="image/*" class="hidden">
          </div>
          <p class="text-center font-semibold text-on-surface text-sm">Upload Photo <span class="text-red-600">*</span></p>
          <p class="text-center text-on-surface-variant text-xs mt-1 leading-relaxed">Tip: Professional headshots increase booking conversion by 40%.</p>
          <p id="avatar_error" class="text-error text-xs mt-2 text-center hidden"></p>
        </div>
      </div>
    </div>
  </div>

  <div class="sticky bottom-0 bg-surface border-t border-outline-variant/10 px-8 md:px-12 py-5 flex items-center justify-end mt-auto">
    <button type="submit" id="profileNext" class="inline-flex items-center gap-2 bg-gradient-to-br from-primary to-primary-container text-white font-bold py-3 px-8 rounded-xl shadow-lg shadow-primary/20 hover:opacity-90 transition-all active:scale-[0.98]">
      Next Step <span class="material-symbols-outlined text-lg">arrow_forward</span>
    </button>
  </div>
</form>

<div id="cropperModal" class="hidden fixed inset-0 z-[100] bg-black/70 items-center justify-center p-4">
  <div class="w-full max-w-xl rounded-2xl bg-white p-4 md:p-6 shadow-2xl">
    <h3 class="text-lg font-bold text-on-surface mb-2">Crop Profile Photo</h3>
    <p class="text-sm text-on-surface-variant mb-4">Adjust your image to a square crop for a uniform profile photo.</p>
    <div class="w-full h-[360px] bg-surface-container rounded-xl overflow-hidden">
      <img id="cropImage" src="" alt="" class="max-w-full">
    </div>
    <div class="mt-5 flex items-center justify-end gap-3">
      <button id="cancelCropBtn" type="button" class="px-4 py-2 rounded-lg border border-outline-variant/40 text-on-surface-variant hover:bg-surface-container-low transition-colors">Cancel</button>
      <button id="applyCropBtn" type="button" class="px-5 py-2 rounded-lg bg-primary text-white font-semibold hover:opacity-90 transition-opacity">Use Photo</button>
    </div>
  </div>
</div>
@endsection

@push('scripts')
<script src="https://unpkg.com/cropperjs@1.6.2/dist/cropper.min.js"></script>
<script>
$(function () {
  var USERNAME_PATTERN = /^[A-Za-z0-9._]{1,30}$/;
  var E164_PATTERN = /^\+[1-9]\d{1,14}$/;
  var cropper = null;
  var objectUrl = '';
  var croppedBlob = null;
  var $openUploadBtn = $('#openUploadBtn');
  var $profileImageInput = $('#profileImageInput');
  var $cropperModal = $('#cropperModal');
  var $cropImage = $('#cropImage');
  var $profilePreview = $('#profilePreview');
  var $uploadPlaceholder = $('#uploadPlaceholder');

  $openUploadBtn.on('click', function () {
    $profileImageInput.trigger('click');
  });

  $.each(['first_name', 'last_name', 'user_name', 'mobile_number'], function (_, fieldName) {
    $('#' + fieldName).on('input', function () {
      if (typeof window.clearOnboardingFieldError === 'function') {
        window.clearOnboardingFieldError(fieldName);
      }
    });
  });

  $profileImageInput.on('change', function (e) {
    if (typeof window.clearOnboardingFieldError === 'function') {
      window.clearOnboardingFieldError('avatar');
    }
    var file = e.target.files && e.target.files[0];
    if (!file) return;
    if (objectUrl) URL.revokeObjectURL(objectUrl);
    objectUrl = URL.createObjectURL(file);
    $cropImage.attr('src', objectUrl);
    $cropperModal.removeClass('hidden').addClass('flex');
    if (cropper) cropper.destroy();
    cropper = new Cropper($cropImage[0], {
      aspectRatio: 1,
      viewMode: 1,
      dragMode: 'move',
      background: false,
      autoCropArea: 1,
      responsive: true,
    });
  });

  function closeModal() {
    $cropperModal.addClass('hidden').removeClass('flex');
    if (cropper) {
      cropper.destroy();
      cropper = null;
    }
    $cropImage.attr('src', '');
    $profileImageInput.val('');
    if (objectUrl) {
      URL.revokeObjectURL(objectUrl);
      objectUrl = '';
    }
  }

  $('#cancelCropBtn').on('click', closeModal);
  $cropperModal.on('click', function (e) {
    if (e.target === $cropperModal[0]) closeModal();
  });

  $('#applyCropBtn').on('click', function () {
    if (!cropper) return;
    var canvas = cropper.getCroppedCanvas({ width: 512, height: 512, imageSmoothingQuality: 'high' });
    canvas.toBlob(function (blob) {
      croppedBlob = blob;
      $profilePreview.attr('src', URL.createObjectURL(blob)).removeClass('hidden');
      $uploadPlaceholder.addClass('hidden');
      if (typeof window.clearOnboardingFieldError === 'function') {
        window.clearOnboardingFieldError('avatar');
      }
      closeModal();
    }, 'image/jpeg', 0.92);
  });

  function showProfileValidationErrors(errors) {
    $.each(errors, function (k, messages) {
      var $el = $('#' + k + '_error');
      if ($el.length) {
        $el.text(messages[0]).removeClass('hidden');
      }
      var $input = $('#' + k);
      if ($input.length) {
        $input.addClass('border-error ring-2 ring-error/40');
      }
    });
    if (typeof window.scrollToFirstOnboardingError === 'function') {
      window.scrollToFirstOnboardingError(document.getElementById('profileForm'));
    }
  }

  function setProfileFieldError(field, message) {
    var $error = $('#' + field + '_error');
    if ($error.length) {
      $error.text(message).removeClass('hidden');
    }
    var $input = $('#' + field);
    if ($input.length) {
      $input.addClass('border-error ring-2 ring-error/40');
    }
  }

  function hasAvatarSelected() {
    if (croppedBlob) return true;
    if ($profileImageInput[0] && $profileImageInput[0].files && $profileImageInput[0].files.length) return true;
    return $profilePreview.attr('src') && !$profilePreview.hasClass('hidden');
  }

  function validateProfileFormClient() {
    var ok = true;

    var firstName = $.trim($('#first_name').val());
    var lastName = $.trim($('#last_name').val());
    var userName = $.trim($('#user_name').val());
    var mobile = $.trim($('#mobile_number').val());

    if (!firstName) {
      setProfileFieldError('first_name', 'First name is required.');
      ok = false;
    }

    if (!lastName) {
      setProfileFieldError('last_name', 'Last name is required.');
      ok = false;
    }

    if (!userName) {
      setProfileFieldError('user_name', 'Username is required.');
      ok = false;
    } else if (!USERNAME_PATTERN.test(userName)) {
      setProfileFieldError('user_name', 'Username can only include letters, numbers, periods (.) and underscores (_) and must be 1-30 characters.');
      ok = false;
    }

    if (!mobile) {
      setProfileFieldError('mobile_number', 'Mobile number is required.');
      ok = false;
    } else if (!E164_PATTERN.test(mobile)) {
      setProfileFieldError('mobile_number', 'Mobile number must be in E.164 format (example: +447911123456) with no spaces, dashes, or parentheses.');
      ok = false;
    }

    if (!hasAvatarSelected()) {
      setProfileFieldError('avatar', 'Profile photo is required.');
      ok = false;
    }

    if (!ok && typeof window.scrollToFirstOnboardingError === 'function') {
      window.scrollToFirstOnboardingError(document.getElementById('profileForm'));
    }
    return ok;
  }

  $('#profileForm').on('submit', function (e) {
    e.preventDefault();
    var $btn = $('#profileNext');
    var originalBtnHtml = $btn.html();
    $('#profileForm').find('[id$="_error"]').addClass('hidden').text('');
    $('#profileForm').find('#first_name, #last_name, #user_name, #mobile_number, #avatar').removeClass('border-error ring-2 ring-error/40');
    if (!validateProfileFormClient()) {
      return;
    }
    $btn.prop('disabled', true);
    $btn.text('Saving...');
    var fd = new FormData(this);
    if (croppedBlob) {
      fd.delete('avatar');
      fd.append('avatar', croppedBlob, 'avatar.jpg');
    }
    $.ajax({
      url: @json(route('onboarding.profile.save')),
      type: 'POST',
      data: fd,
      processData: false,
      contentType: false,
      headers: {
        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content'),
        Accept: 'application/json',
      },
    })
      .done(function (data) {
        if (data.success && data.redirect) {
          window.location.href = data.redirect;
        } else if (data.errors) {
          showProfileValidationErrors(data.errors);
        }
      })
      .fail(function (xhr) {
        if (xhr.status === 422 && xhr.responseJSON && xhr.responseJSON.errors) {
          showProfileValidationErrors(xhr.responseJSON.errors);
        } else {
          alert('Network error');
        }
      })
      .always(function () {
        $btn.prop('disabled', false);
        $btn.html(originalBtnHtml);
      });
  });
});
</script>
@endpush
