@extends('layouts.studio-onboarding-layout')

@section('title', 'Your details')

@push('styles')
<style>
  .field-err{display:none;color:#C62828;font-size:12px;margin-top:6px}
  .in.err{border-color:#C62828!important;box-shadow:0 0 0 3px #FDECEC}
</style>
@endpush

@section('content')
  <h1 style="margin-top:6px">Your details<a class="help-q" href="https://help.inkjin.com" target="_blank" rel="noopener" title="Help with this page" aria-label="Help with this page"><span class="ms">help</span></a></h1>
  <div class="sub" style="max-width:660px;margin-bottom:24px">You're the owner of this studio account. We use these details to contact you. Clients don't see them.</div>

  <form id="studioOnboardingOwnerForm" method="POST" action="{{ route('studio.onboarding.owner.update') }}" enctype="multipart/form-data" novalidate>
    @csrf

    <div class="card" style="margin-bottom:14px">
      <div class="ch">
        <div>
          <h3>Owner</h3>
          <div class="faint" style="font-size:12.5px;margin-top:2px"></div>
        </div>
      </div>
      <div style="padding:18px 22px">
        <div class="row" style="gap:16px;margin-bottom:16px">
          <div class="avatar" id="ownerAvatarPreview" style="width:64px;height:64px;border-radius:50%;background:#F3E8FF;color:#3E007C;display:flex;align-items:center;justify-content:center;font-weight:800;font-size:18px">
            @if (!empty($ownerAvatarUrl))
              <img src="{{ $ownerAvatarUrl }}" alt="">
            @else
              {{ $ownerInitials }}
            @endif
          </div>
          <div>
            <a class="btn ghost sm" href="#" id="uploadPhotoBtn"><span class="ms">upload</span>Upload photo</a>
            <input type="file" id="avatar" name="avatar" accept="image/jpeg,image/png,image/webp,image/gif" hidden>
            <div class="help">Required. Shown to your artists in their dashboard.</div>
            <div id="avatar_error" class="field-err"></div>
          </div>
        </div>
        <div class="formgrid">
          <div>
            <span class="fl">First name <span style="color:#C62828">*</span></span>
            <input class="in" id="first_name" name="first_name" type="text" value="{{ old('first_name', $ownerFirstName) }}" placeholder="">
            <div id="first_name_error" class="field-err"></div>
          </div>
          <div>
            <span class="fl">Last name <span style="color:#C62828">*</span></span>
            <input class="in" id="last_name" name="last_name" type="text" value="{{ old('last_name', $ownerLastName) }}" placeholder="">
            <div id="last_name_error" class="field-err"></div>
          </div>
          <div>
            <span class="fl">Email</span>
            <input class="in" id="email" type="email" value="{{ $ownerEmail }}" placeholder="" readonly style="background:#F7F4F8;color:#6F6874">
            <div class="help">Your login, from sign-up. Change it in Account &gt; Profile.</div>
          </div>
          <div>
            <span class="fl">Mobile <span style="color:#C62828">*</span></span>
            <input class="in" id="mobile" name="mobile" type="tel" value="{{ old('mobile', $ownerMobile) }}" placeholder="+30 69X XXX XXXX">
            <div class="help">For account and security messages.</div>
            <div id="mobile_error" class="field-err"></div>
          </div>
        </div>
      </div>
    </div>

    <div class="card" style="margin-bottom:14px">
      <div class="ch">
        <div>
          <h3>Do you tattoo?</h3>
          <div class="faint" style="font-size:12.5px;margin-top:2px">Your studio login is only for running the studio.</div>
        </div>
      </div>
      <div style="padding:18px 22px">
        <span class="fl">Are you also a tattoo artist at this studio? <span style="color:#C62828">*</span></span>
        <input type="hidden" id="owner_is_artist" name="owner_is_artist" value="{{ old('owner_is_artist', $isTattooArtist ?? 'no') }}">
        <div class="grid" data-group="own" style="grid-template-columns:1fr 1fr;gap:10px">
          <div class="opt{{ ($isTattooArtist ?? 'no') === 'yes' ? ' on' : '' }}" data-v="yes" role="radio" aria-checked="{{ ($isTattooArtist ?? 'no') === 'yes' ? 'true' : 'false' }}" tabindex="0">
            <span class="ms rad">{{ ($isTattooArtist ?? 'no') === 'yes' ? 'radio_button_checked' : 'radio_button_unchecked' }}</span>
            <div style="flex:1"><b>Yes</b><small>I tattoo here too</small></div>
          </div>
          <div class="opt{{ ($isTattooArtist ?? 'no') === 'no' ? ' on' : '' }}" data-v="no" role="radio" aria-checked="{{ ($isTattooArtist ?? 'no') === 'no' ? 'true' : 'false' }}" tabindex="0">
            <span class="ms rad">{{ ($isTattooArtist ?? 'no') === 'no' ? 'radio_button_checked' : 'radio_button_unchecked' }}</span>
            <div style="flex:1"><b>No</b><small>I only run the studio</small></div>
          </div>
        </div>
        <div id="owner_is_artist_error" class="field-err"></div>
        <div data-when="own:yes" @if(($isTattooArtist ?? 'no') !== 'yes') hidden @endif>
          <div class="note" style="margin-top:12px;background:#E6F8EE;border-color:#BDE8CE;color:#0E6B37">
            <span class="ms" style="color:#00A650">mark_email_read</span>
            <span>After you finish studio setup, we'll email you an invitation to set up your own artist account. It's a separate login from this studio account. Once you set it up, you're linked to this studio as <b>Co-owner</b> and show on your studio page.</span>
          </div>
        </div>
      </div>
    </div>

    <div class="obfoot">
      <a class="btn ghost" href="{{ route('studio.onboarding.profile') }}"><span class="ms">arrow_back</span>Back</a>
      <div class="row" style="gap:0">
        <button type="submit" class="btn" id="nextStepBtn">Next step<span class="ms">arrow_forward</span></button>
      </div>
    </div>
  </form>
@endsection

@push('scripts')
<script>
(function () {
  var form = document.getElementById('studioOnboardingOwnerForm');
  var btn = document.getElementById('nextStepBtn');
  var defaultBtnHtml = btn ? btn.innerHTML : '';
  var fields = ['first_name', 'last_name', 'mobile', 'owner_is_artist', 'avatar'];
  var artistInput = document.getElementById('owner_is_artist');
  var preview = document.getElementById('ownerAvatarPreview');

  var uploadBtn = document.getElementById('uploadPhotoBtn');
  var fileInput = document.getElementById('avatar');
  if (uploadBtn && fileInput) {
    uploadBtn.addEventListener('click', function (e) {
      e.preventDefault();
      fileInput.click();
    });
    fileInput.addEventListener('change', function () {
      clearFieldError('avatar');
      var file = fileInput.files && fileInput.files[0];
      if (!file || !preview) return;
      preview.innerHTML = '<img src="' + URL.createObjectURL(file) + '" alt="">';
      if (window.bpToast) window.bpToast('Photo selected');
    });
  }

  document.querySelectorAll('[data-group] .opt').forEach(function (opt) {
    opt.addEventListener('click', function () {
      var group = opt.closest('[data-group]');
      if (!group) return;
      var value = opt.getAttribute('data-v');
      if (artistInput) artistInput.value = value;
      clearFieldError('owner_is_artist');
      group.querySelectorAll('.opt').forEach(function (item) {
        var on = item === opt;
        item.classList.toggle('on', on);
        item.setAttribute('aria-checked', on ? 'true' : 'false');
        var rad = item.querySelector('.rad');
        if (rad) rad.textContent = on ? 'radio_button_checked' : 'radio_button_unchecked';
      });
      document.querySelectorAll('[data-when^="' + group.getAttribute('data-group') + ':"]').forEach(function (el) {
        var allowed = el.getAttribute('data-when').split(':')[1].split('|');
        el.hidden = allowed.indexOf(value) < 0;
      });
    });
  });

  function clearFieldError(name) {
    var el = document.getElementById(name + '_error');
    if (el) {
      el.textContent = '';
      el.style.display = 'none';
    }
    var input = document.getElementById(name);
    if (input && input.classList) input.classList.remove('err');
  }

  function setFieldError(name, message) {
    var el = document.getElementById(name + '_error');
    if (el) {
      el.textContent = message || '';
      el.style.display = message ? 'block' : 'none';
    }
    var input = document.getElementById(name);
    if (input && input.classList && name !== 'avatar' && name !== 'owner_is_artist') {
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
          if (result.data.owner && result.data.owner.avatar && preview) {
            preview.innerHTML = '<img src="' + result.data.owner.avatar + '" alt="">';
          }
          setTimeout(function () {
            window.location.href = @json(route('studio.onboarding.location'));
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
          window.bpToast((result.data && result.data.message) || 'Could not save details.', true);
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
