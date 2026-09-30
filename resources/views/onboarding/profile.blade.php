@extends('layouts.artist-onboarding-layout')

@section('title', 'Profile — Artist onboarding')

@push('styles')
<link rel="stylesheet" href="https://unpkg.com/cropperjs@1.6.2/dist/cropper.min.css">
<style>
  .field-err{color:#C62828;font-size:12px;margin-top:6px}
  .field-err.hidden{display:none}
  input.in.is-err{border-color:#C62828}
  button.btn{border:0;cursor:pointer;font:inherit}
  button.btn:disabled{opacity:.6;cursor:not-allowed}
  .avatar-wrap{width:170px;height:170px;border-radius:50%;border:1.5px dashed #D6C9DB;background:#FBF3FC;margin:0 auto;display:flex;align-items:center;justify-content:center;position:relative;overflow:visible;cursor:pointer}
  .avatar-wrap img{position:absolute;inset:0;width:100%;height:100%;object-fit:cover;display:block;border-radius:50%;z-index:0}
  .avatar-wrap .upload-fab{position:absolute;right:6px;bottom:6px;background:#1A1A1A;color:#fff;border-radius:50%;padding:8px;font-size:18px;border:0;cursor:pointer;line-height:1;z-index:3;box-shadow:0 1px 4px rgba(0,0,0,.2)}
  .avatar-wrap .ph{font-size:40px;color:#9A929E;position:relative;z-index:1}
  .crop-ov{display:none;position:fixed;inset:0;z-index:100;background:rgba(0,0,0,.7);align-items:center;justify-content:center;padding:16px}
  .crop-ov.open{display:flex}
  .crop-box{width:100%;max-width:560px;background:#fff;border-radius:16px;padding:20px 22px;border:1px solid var(--line)}
  .crop-stage{width:100%;height:360px;background:#F7F2F8;border-radius:12px;overflow:hidden}
  .crop-actions{display:flex;justify-content:flex-end;gap:10px;margin-top:16px}
  @media (max-width:700px){
    .profile-grid{grid-template-columns:1fr!important}
    .name-grid{grid-template-columns:1fr!important}
  }
</style>
@endpush

@section('content')
<form id="profileForm" enctype="multipart/form-data">
  @csrf
  <div class="wrap">
    <h1 style="margin-top:6px">Let's build your profile<a class="help-q" href="https://help.inkjin.com/en/articles/17198965-setup-step-1-profile" target="_blank" rel="noopener" data-help-article="O1-profile" title="Help with this page" aria-label="Help with this page"><span class="ms">help</span></a></h1>
    <div class="sub" style="max-width:640px;margin-bottom:24px">Tell us a bit about yourself. We use this to set up your page and to pay you securely.</div>

    <div class="grid profile-grid" style="grid-template-columns:1fr 240px;gap:28px;align-items:start">
      <div class="card pad">
        <div class="grid" style="gap:16px">
          <div class="grid name-grid" style="grid-template-columns:1fr 1fr;gap:12px">
            <div>
              <label class="fl" for="first_name">First name <span style="color:#C62828">*</span></label>
              <input class="in" type="text" id="first_name" name="first_name" value="{{ Auth::user()->first_name }}" placeholder="e.g. Julian" autocomplete="given-name">
              <p id="first_name_error" class="field-err hidden"></p>
            </div>
            <div>
              <label class="fl" for="last_name">Last name <span style="color:#C62828">*</span></label>
              <input class="in" type="text" id="last_name" name="last_name" value="{{ Auth::user()->last_name }}" placeholder="e.g. Ink" autocomplete="family-name">
              <p id="last_name_error" class="field-err hidden"></p>
            </div>
          </div>

          <div>
            <label class="fl" for="user_name">Username <span style="color:#C62828">*</span></label>
            <input class="in" type="text" id="user_name" name="user_name" value="{{ $userDetail->user_name ?? '' }}" placeholder="e.g. julianink" autocomplete="off" spellcheck="false">
            <div class="help" style="display:flex;align-items:center;gap:5px;color:#3E007C;font-weight:600">
              <span class="ms" style="font-size:15px">link</span>
              <span id="un-url">inkjin.com/@{{ $userDetail->user_name ?? 'username' }}</span>
            </div>
            <div class="help">Match your Instagram handle. Letters, numbers, periods and underscores, max 30.</div>
            <p id="user_name_error" class="field-err hidden"></p>
          </div>

          <div>
            <label class="fl" for="mobile_number">Mobile number <span style="color:#C62828">*</span></label>
            <input class="in" type="tel" id="mobile_number" name="mobile_number" value="{{ $userDetail->mobile_number ?? '' }}" placeholder="+30 690 000 0000" autocomplete="tel">
            <div class="help">International format, starting with + and the country code.</div>
            <p id="mobile_number_error" class="field-err hidden"></p>
          </div>
        </div>
      </div>

      <div style="text-align:center">
        <div class="avatar-wrap" id="avatarWrap">
          <img id="profilePreview" src="{{ $userDetail->avatar ? asset($userDetail->avatar) : '' }}" alt="" style="{{ $userDetail->avatar ? '' : 'display:none' }}">
          <span id="uploadPlaceholder" class="ms ph" style="{{ $userDetail->avatar ? 'display:none' : '' }}">photo_camera</span>
          <button type="button" class="upload-fab ms" id="openUploadBtn" aria-label="Upload photo">upload</button>
        </div>
        <input id="profileImageInput" type="file" name="avatar" accept="image/*" hidden>
        <b style="display:block;margin-top:12px">Upload photo <span style="color:#C62828">*</span></b>
        <div class="faint" style="font-size:12px;margin-top:4px">A clear headshot helps clients trust you.</div>
        <p id="avatar_error" class="field-err hidden" style="text-align:center"></p>
      </div>
    </div>

    <div class="obfoot">
      <span></span>
      <button type="submit" id="profileNext" class="btn">Next step<span class="ms">arrow_forward</span></button>
    </div>
  </div>
</form>

<div id="cropperModal" class="crop-ov" role="dialog" aria-modal="true" aria-labelledby="cropTitle">
  <div class="crop-box">
    <h3 id="cropTitle" style="font-size:17px;font-weight:800;margin-bottom:4px">Crop profile photo</h3>
    <p class="sub" style="margin-bottom:14px">Adjust your image to a square crop for a uniform profile photo.</p>
    <div class="crop-stage">
      <img id="cropImage" src="" alt="">
    </div>
    <div class="crop-actions">
      <button id="cancelCropBtn" type="button" class="btn ghost">Cancel</button>
      <button id="applyCropBtn" type="button" class="btn">Use photo</button>
    </div>
  </div>
</div>
@endsection

@push('scripts')
@include('partials.reddit-pixel', ['event' => 'Step_1'])
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
  var $userName = $('#user_name');
  var $unUrl = $('#un-url');

  function syncUsernameUrl() {
    var v = $.trim($userName.val()).replace(/^@/, '');
    $unUrl.text('inkjin.com/@' + (v || 'username'));
  }
  $userName.on('input', syncUsernameUrl);
  syncUsernameUrl();

  $openUploadBtn.on('click', function () {
    $profileImageInput.trigger('click');
  });
  $('#avatarWrap').on('click', function (e) {
    if (e.target === $openUploadBtn[0] || $openUploadBtn[0].contains(e.target)) return;
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
    $cropperModal.addClass('open');
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
    $cropperModal.removeClass('open');
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
      $profilePreview.attr('src', URL.createObjectURL(blob)).show();
      $uploadPlaceholder.hide();
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
        $input.addClass('is-err');
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
      $input.addClass('is-err');
    }
  }

  function hasAvatarSelected() {
    if (croppedBlob) return true;
    if ($profileImageInput[0] && $profileImageInput[0].files && $profileImageInput[0].files.length) return true;
    return $profilePreview.attr('src') && $profilePreview.is(':visible');
  }

  function validateProfileFormClient() {
    var ok = true;
    var firstName = $.trim($('#first_name').val());
    var lastName = $.trim($('#last_name').val());
    var userName = $.trim($('#user_name').val());
    var mobile = $.trim($('#mobile_number').val()).replace(/\s+/g, '');

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
    $('#profileForm').find('#first_name, #last_name, #user_name, #mobile_number').removeClass('is-err');
    if (!validateProfileFormClient()) {
      return;
    }
    $btn.prop('disabled', true).text('Saving...');
    var fd = new FormData(this);
    var mobileVal = $.trim($('#mobile_number').val()).replace(/\s+/g, '');
    fd.set('mobile_number', mobileVal);
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
        $btn.prop('disabled', false).html(originalBtnHtml);
      });
  });
});
</script>
@endpush
