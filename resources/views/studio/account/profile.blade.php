@extends('layouts.studio-dashboard-layout')

@section('title', 'Account · Profile')

@push('styles')
<link rel="stylesheet" href="https://unpkg.com/cropperjs@1.6.2/dist/cropper.min.css">
<style>
  .owner-av{width:56px;height:56px;border-radius:50%;background:#F3E8FF;color:#3E007C;display:flex;align-items:center;justify-content:center;font-weight:800;overflow:hidden;flex:none;position:relative}
  .owner-av img{position:absolute;inset:0;width:100%;height:100%;object-fit:cover;display:block;z-index:2}
  .owner-av .ini{position:relative;z-index:1}
  .owner-av.has-img .ini{display:none!important}
  #avatar_error{display:none;color:#C62828}
  .crop-ov{display:none;position:fixed;inset:0;z-index:300;background:rgba(0,0,0,.7);align-items:center;justify-content:center;padding:16px}
  .crop-ov.open{display:flex}
  .crop-box{width:100%;max-width:560px;background:#fff;border-radius:16px;padding:20px 22px;border:1px solid var(--line);box-shadow:0 20px 60px rgba(0,0,0,.25)}
  .crop-box h3{margin:0 0 4px;font-size:17px;font-weight:800}
  .crop-stage{width:100%;height:360px;background:#F7F2F8;border-radius:12px;overflow:hidden;margin-top:14px}
  .crop-stage img{display:block;max-width:100%}
  .crop-actions{display:flex;justify-content:flex-end;gap:10px;margin-top:16px}
</style>
@endpush

@section('content')
  <div class="head">
    <div>
      <h1>Account<a class="help-q" href="https://help.inkjin.com" target="_blank" rel="noopener" title="Help with this page" aria-label="Help with this page"><span class="ms">help</span></a></h1>
      <div class="sub">Your login and your studio's details.</div>
    </div>
  </div>

  @include('studio.account._tabs')

  <form id="studioProfileForm" method="POST" action="{{ route('studio.account.profile.update') }}" enctype="multipart/form-data" novalidate>
    @csrf

    <div class="card" style="margin-bottom:14px">
      <div class="ch">
        <div>
          <h3>Owner</h3>
          <div class="faint" style="font-size:12.5px;margin-top:2px">The person who manages this studio account</div>
        </div>
      </div>
      <div style="padding:18px 22px">
        <div class="row" style="gap:16px;margin-bottom:16px">
          <div id="ownerAvatar" class="owner-av{{ $ownerAvatarUrl ? ' has-img' : '' }}">
            <img id="ownerAvatarImg" src="{{ $ownerAvatarUrl ?? '' }}" alt="" @unless($ownerAvatarUrl) style="display:none" @endunless>
            <span class="ini">{{ $ownerInitials }}</span>
          </div>
          <div>
            <button type="button" class="btn ghost sm" id="changePhotoBtn"><span class="ms">upload</span>Change photo</button>
            <input id="profileImageInput" type="file" accept="image/*" hidden>
            <div class="help">Shown to your artists in their dashboard.</div>
            <div id="avatar_error" class="help"></div>
          </div>
        </div>
        <div class="formgrid">
          <div>
            <label class="fl" for="first_name">First name <span style="color:#C62828">*</span></label>
            <input class="in" id="first_name" name="first_name" type="text" value="{{ old('first_name', $ownerFirstName) }}" autocomplete="given-name">
            <div id="first_name_error" class="help" style="display:none;color:#C62828"></div>
          </div>
          <div>
            <label class="fl" for="last_name">Last name <span style="color:#C62828">*</span></label>
            <input class="in" id="last_name" name="last_name" type="text" value="{{ old('last_name', $ownerLastName) }}" autocomplete="family-name">
            <div id="last_name_error" class="help" style="display:none;color:#C62828"></div>
          </div>
          <div>
            <label class="fl" for="mobile">Mobile</label>
            <input class="in" id="mobile" name="mobile" type="text" value="{{ old('mobile', $ownerMobile) }}" autocomplete="tel">
            <div id="mobile_error" class="help" style="display:none;color:#C62828"></div>
          </div>
          <div>
            <span class="fl">Email</span>
            <div class="in" style="background:#F5F2F6">{{ $ownerEmail }}<span class="ms faint" style="margin-left:auto">lock</span></div>
            <div class="help">Contact support to change it.</div>
          </div>
        </div>
      </div>
    </div>

    <div class="pgsaverow">
      <button class="btn pgsave" id="profileSaveBtn" type="submit"><span class="ms">save</span>Save changes</button>
    </div>
  </form>

  <div id="cropperModal" class="crop-ov" role="dialog" aria-modal="true" aria-labelledby="cropTitle">
    <div class="crop-box">
      <h3 id="cropTitle">Crop profile photo</h3>
      <div class="sub">Adjust your image to a square crop for a uniform profile photo.</div>
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
<script src="https://unpkg.com/cropperjs@1.6.2/dist/cropper.min.js"></script>
<script>
(function () {
  var form = document.getElementById('studioProfileForm');
  if (!form) return;

  var btn = document.getElementById('profileSaveBtn');
  var avatarWrap = document.getElementById('ownerAvatar');
  var avatarImg = document.getElementById('ownerAvatarImg');
  var changePhotoBtn = document.getElementById('changePhotoBtn');
  var profileImageInput = document.getElementById('profileImageInput');
  var cropperModal = document.getElementById('cropperModal');
  var cropImage = document.getElementById('cropImage');
  var fields = ['first_name', 'last_name', 'mobile', 'avatar'];
  var defaultBtnHtml = btn ? btn.innerHTML : '';
  var cropper = null;
  var objectUrl = '';
  var croppedBlob = null;
  var previewUrl = '';

  function clearFieldError(name) {
    var el = document.getElementById(name + '_error');
    if (el) {
      el.textContent = '';
      el.style.display = 'none';
    }
  }

  function setFieldError(name, message) {
    var el = document.getElementById(name + '_error');
    if (!el) return;
    el.textContent = message || '';
    el.style.display = message ? 'block' : 'none';
  }

  function clearAllErrors() {
    fields.forEach(clearFieldError);
  }

  function setAvatarPreview(url) {
    if (!avatarImg || !avatarWrap) return;
    var previous = previewUrl;
    previewUrl = '';

    if (!url) {
      if (previous) URL.revokeObjectURL(previous);
      avatarImg.removeAttribute('src');
      avatarImg.style.display = 'none';
      avatarWrap.classList.remove('has-img');
      return;
    }

    if (previous && previous !== url) URL.revokeObjectURL(previous);
    if (String(url).indexOf('blob:') === 0) previewUrl = url;

    avatarImg.src = url;
    avatarImg.style.display = 'block';
    avatarWrap.classList.add('has-img');
  }

  function closeCropperModal() {
    cropperModal.classList.remove('open');
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

  fields.slice(0, 3).forEach(function (name) {
    var input = document.getElementById(name);
    if (input) input.addEventListener('input', function () { clearFieldError(name); });
  });

  changePhotoBtn.addEventListener('click', function () {
    clearFieldError('avatar');
    profileImageInput.click();
  });

  profileImageInput.addEventListener('change', function (e) {
    clearFieldError('avatar');
    var file = e.target.files && e.target.files[0];
    if (!file) return;
    if (!/^image\//.test(file.type)) {
      setFieldError('avatar', 'Please choose a valid image file.');
      profileImageInput.value = '';
      return;
    }
    if (objectUrl) URL.revokeObjectURL(objectUrl);
    objectUrl = URL.createObjectURL(file);
    cropImage.src = objectUrl;
    cropperModal.classList.add('open');
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

  document.getElementById('cancelCropBtn').addEventListener('click', closeCropperModal);
  cropperModal.addEventListener('click', function (e) {
    if (e.target === cropperModal) closeCropperModal();
  });

  document.getElementById('applyCropBtn').addEventListener('click', function () {
    if (!cropper) return;
    var canvas = cropper.getCroppedCanvas({ width: 512, height: 512, imageSmoothingQuality: 'high' });
    canvas.toBlob(function (blob) {
      if (!blob) {
        setFieldError('avatar', 'Could not crop this image. Try another photo.');
        return;
      }
      croppedBlob = blob;
      setAvatarPreview(URL.createObjectURL(blob));
      clearFieldError('avatar');
      closeCropperModal();
    }, 'image/jpeg', 0.92);
  });

  form.addEventListener('submit', function (e) {
    e.preventDefault();
    clearAllErrors();

    if (btn) {
      btn.disabled = true;
      btn.innerHTML = '<span class="ms">hourglass_top</span>Saving…';
    }

    var body = new FormData(form);
    if (croppedBlob) {
      var avatarFile = croppedBlob;
      try {
        avatarFile = new File([croppedBlob], 'avatar.jpg', { type: croppedBlob.type || 'image/jpeg' });
      } catch (err) {}
      body.set('avatar', avatarFile, 'avatar.jpg');
    }

    fetch(form.action, {
      method: 'POST',
      headers: {
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || form.querySelector('input[name="_token"]').value,
        'X-Requested-With': 'XMLHttpRequest',
        'Accept': 'application/json'
      },
      body: body
    })
      .then(function (response) {
        return response.json().then(function (data) {
          return { ok: response.ok, status: response.status, data: data };
        }).catch(function () {
          return { ok: false, status: response.status, data: {} };
        });
      })
      .then(function (result) {
        if (result.ok && result.data && result.data.success) {
          if (window.bpToast) window.bpToast('Changes saved');
          croppedBlob = null;
          if (result.data.owner) {
            var ini = avatarWrap && avatarWrap.querySelector('.ini');
            if (ini && result.data.owner.initials) ini.textContent = result.data.owner.initials;
            if (result.data.owner.avatar) {
              setAvatarPreview(result.data.owner.avatar);
            }
          }
          return;
        }

        var errors = (result.data && result.data.errors) || {};
        fields.forEach(function (name) {
          if (errors[name] && errors[name][0]) setFieldError(name, errors[name][0]);
        });
      })
      .catch(function () {
        if (window.bpToast) window.bpToast('Something went wrong. Please try again.', true);
        else setFieldError('first_name', 'Something went wrong. Please try again.');
      })
      .finally(function () {
        if (btn) {
          btn.disabled = false;
          btn.innerHTML = defaultBtnHtml;
        }
      });
  });
})();
</script>
@endpush
