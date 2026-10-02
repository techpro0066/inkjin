@extends('layouts.studio-onboarding-layout')

@section('title', 'Studio profile')

@push('styles')
<style>
  .field-err{display:none;color:#C62828;font-size:12px;margin-top:6px}
  .in.err{border-color:#C62828!important;box-shadow:0 0 0 3px #FDECEC}
</style>
@endpush

@section('content')
  <h1 style="margin-top:6px">Tell clients about your studio<a class="help-q" href="https://help.inkjin.com" target="_blank" rel="noopener" title="Help with this page" aria-label="Help with this page"><span class="ms">help</span></a></h1>
  <div class="sub" style="max-width:660px;margin-bottom:24px">Your artists and their clients see these details. Only your studio can change them.</div>

  <form id="studioOnboardingProfileForm" method="POST" action="{{ route('studio.onboarding.profile.update') }}" enctype="multipart/form-data" novalidate>
    @csrf

    <div class="card" style="margin-bottom:14px">
      <div class="ch">
        <div>
          <h3>Studio</h3>
          <div class="faint" style="font-size:12.5px;margin-top:2px"></div>
        </div>
      </div>
      <div style="padding:18px 22px">
        <div class="formgrid">
          <div class="full">
            <span class="fl">Studio name <span style="color:#C62828">*</span></span>
            <input class="in" id="name" name="name" type="text" value="{{ old('name', $studioName) }}" placeholder="">
            <div id="name_error" class="field-err"></div>
          </div>
          <div class="full">
            <span class="fl">Username <span style="color:#C62828">*</span></span>
            <input class="in" id="username" name="username" type="text" value="{{ old('username', $username) }}" placeholder="e.g. openinkstudio" autocomplete="off" spellcheck="false">
            <div class="help" style="display:flex;align-items:center;gap:5px;color:#3E007C;font-weight:600">
              <span class="ms" style="font-size:15px">link</span>
              <span id="un-url">inkjin.com/@{{ ($username ?? '') !== '' ? $username : 'username' }}</span>
            </div>
            <div class="help">Match your Instagram handle. Letters, numbers, periods and underscores, max 30.</div>
            <div id="username_error" class="field-err"></div>
          </div>
          <div>
            <span class="fl">Tattooing since</span>
            <input class="in" id="tattooing_since" name="tattooing_since" type="number" value="{{ old('tattooing_since', $tattooingSince) }}" placeholder="e.g. 2015">
            <div class="help">The year the studio opened. Optional.</div>
            <div id="tattooing_since_error" class="field-err"></div>
          </div>
        </div>
      </div>
    </div>

    <div class="card" style="margin-bottom:14px">
      <div class="ch">
        <div>
          <h3>Contact and logo</h3>
          <div class="faint" style="font-size:12.5px;margin-top:2px"></div>
        </div>
      </div>
      <div style="padding:18px 22px">
        <div class="row" style="gap:16px;margin-bottom:14px">
          <div class="ic" id="logoPreview" style="width:64px;height:64px;border-radius:14px;background:#3E007C;color:#fff;font-weight:800;font-size:18px">
            @if (!empty($studioLogoUrl))
              <img src="{{ $studioLogoUrl }}" alt="">
            @else
              {{ $studioInitials }}
            @endif
          </div>
          <div>
            <a class="btn ghost sm" href="#" id="uploadLogoBtn"><span class="ms">upload</span>Upload logo</a>
            <input type="file" id="logo" name="logo" accept="image/jpeg,image/png,image/webp,image/gif" hidden>
            <div class="help">Required. Square image, at least 400 × 400.</div>
            <div id="logo_error" class="field-err"></div>
          </div>
        </div>
        <div class="formgrid">
          <div>
            <span class="fl">Business phone <span style="color:#C62828">*</span></span>
            <input class="in" id="business_phone" name="business_phone" type="tel" value="{{ old('business_phone', $businessPhone) }}" placeholder="+30 210 123 4567">
            <div class="help">Shown on the studio page and in booking emails.</div>
            <div id="business_phone_error" class="field-err"></div>
          </div>
          <div>
            <span class="fl">Contact email</span>
            <input class="in" id="contact_email" name="contact_email" type="text" value="{{ old('contact_email', $contactEmail) }}" placeholder="">
            <div class="help">Shown on the studio page. Can differ from your login.</div>
            <div id="contact_email_error" class="field-err"></div>
          </div>
          <div>
            <span class="fl">Instagram</span>
            <input class="in" id="instagram" name="instagram" type="text" value="{{ old('instagram', $instagram) }}" placeholder="instagram.com/yourstudio">
            <div class="help">Optional. Shown on your studio page.</div>
            <div id="instagram_error" class="field-err"></div>
          </div>
          <div>
            <span class="fl">Website</span>
            <input class="in" id="website" name="website" type="text" value="{{ old('website', $website) }}" placeholder="yourstudio.com">
            <div class="help">Optional. Add more links later in Studio Page &gt; About.</div>
            <div id="website_error" class="field-err"></div>
          </div>
        </div>
      </div>
    </div>

    <div class="obfoot">
      <span></span>
      <div class="row" style="gap:0">
        <button type="submit" class="btn" id="nextStepBtn">Next step<span class="ms">arrow_forward</span></button>
      </div>
    </div>
  </form>
@endsection

@push('scripts')
<script>
(function () {
  var form = document.getElementById('studioOnboardingProfileForm');
  var btn = document.getElementById('nextStepBtn');
  var defaultBtnHtml = btn ? btn.innerHTML : '';
  var fields = ['name', 'username', 'tattooing_since', 'business_phone', 'contact_email', 'instagram', 'website', 'logo'];

  var usernameInput = document.getElementById('username');
  var usernamePreview = document.getElementById('un-url');
  if (usernameInput && usernamePreview) {
    function syncPreview() {
      var v = (usernameInput.value || '').trim().replace(/^@/, '');
      usernamePreview.textContent = 'inkjin.com/@' + (v || 'username');
    }
    usernameInput.addEventListener('input', syncPreview);
    syncPreview();
  }

  var uploadBtn = document.getElementById('uploadLogoBtn');
  var fileInput = document.getElementById('logo');
  var logoPreview = document.getElementById('logoPreview');
  if (uploadBtn && fileInput) {
    uploadBtn.addEventListener('click', function (e) {
      e.preventDefault();
      fileInput.click();
    });
    fileInput.addEventListener('change', function () {
      clearFieldError('logo');
      var file = fileInput.files && fileInput.files[0];
      if (!file || !logoPreview) return;
      logoPreview.innerHTML = '<img src="' + URL.createObjectURL(file) + '" alt="">';
      if (window.bpToast) window.bpToast('Logo selected');
    });
  }

  function clearFieldError(name) {
    var el = document.getElementById(name + '_error');
    if (el) {
      el.textContent = '';
      el.style.display = 'none';
    }
    var input = document.getElementById(name);
    if (input) input.classList.remove('err');
  }

  function setFieldError(name, message) {
    var el = document.getElementById(name + '_error');
    if (el) {
      el.textContent = message || '';
      el.style.display = message ? 'block' : 'none';
    }
    var input = document.getElementById(name);
    if (input && name !== 'logo') {
      if (message) input.classList.add('err');
      else input.classList.remove('err');
    }
  }

  function clearAllErrors() {
    fields.forEach(clearFieldError);
  }

  function fieldErrorMessage(errors, name) {
    if (!errors) return '';
    if (errors[name] && errors[name][0]) return errors[name][0];
    return '';
  }

  fields.forEach(function (name) {
    var input = document.getElementById(name);
    if (!input) return;
    input.addEventListener('input', function () { clearFieldError(name); });
    input.addEventListener('change', function () { clearFieldError(name); });
  });

  if (!form) return;

  form.addEventListener('submit', function (e) {
    e.preventDefault();
    clearAllErrors();

    if (btn) {
      btn.disabled = true;
      btn.innerHTML = '<span class="ms">hourglass_top</span>Saving…';
    }

    fetch(form.action, {
      method: 'POST',
      headers: {
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || form.querySelector('input[name="_token"]').value,
        'X-Requested-With': 'XMLHttpRequest',
        'Accept': 'application/json'
      },
      body: new FormData(form)
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
          if (result.data.studio && result.data.studio.logo && logoPreview) {
            logoPreview.innerHTML = '<img src="' + result.data.studio.logo + '" alt="">';
          }
          setTimeout(function () {
            window.location.href = @json(route('studio.onboarding.owner'));
          }, 400);
          return;
        }

        var errors = (result.data && result.data.errors) || {};
        var shown = false;
        fields.forEach(function (name) {
          var msg = fieldErrorMessage(errors, name);
          if (msg) {
            setFieldError(name, msg);
            shown = true;
          }
        });

        if (!shown && window.bpToast) {
          window.bpToast((result.data && result.data.message) || 'Could not save profile.', true);
        }
      })
      .catch(function () {
        if (window.bpToast) window.bpToast('Something went wrong. Please try again.', true);
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
