@extends('layouts.studio-dashboard-layout')

@section('title', 'Account · Password')

@section('content')
<div class="head">
  <div>
    <h1>Account<a class="help-q" href="https://help.inkjin.com" target="_blank" rel="noopener" title="Help with this page" aria-label="Help with this page"><span class="ms">help</span></a></h1>
    <div class="sub">Your login and your studio's details.</div>
  </div>
</div>

  @include('studio.account._tabs')

  <div style="max-width:560px">
    <div class="card">
      <div class="ch">
        <div>
          <h3>Change password</h3>
          <div class="faint" style="font-size:12.5px;margin-top:2px">Use a password you don't use anywhere else</div>
        </div>
      </div>
      <form id="studioPasswordForm" method="POST" action="{{ route('password.update') }}" style="padding:18px 22px" novalidate>
        @csrf
        @method('PUT')

        <div class="fl" style="margin-top:0;display:flex;justify-content:space-between;align-items:baseline">
          <label for="current_password">Current password</label>
        </div>
        <div class="pw-wrap">
          <input class="in" id="current_password" name="current_password" type="password" autocomplete="current-password" aria-label="Current password">
          <button type="button" class="pw-eye" data-target="current_password" aria-label="Show current password"><span class="ms">visibility</span></button>
        </div>
        <div id="current_password_error" class="help" style="display:none;color:#C62828"></div>

        <label class="fl" for="password" style="margin-top:14px">New password</label>
        <div class="pw-wrap">
          <input class="in" id="password" name="password" type="password" autocomplete="new-password" aria-label="New password">
          <button type="button" class="pw-eye" data-target="password" aria-label="Show new password"><span class="ms">visibility</span></button>
        </div>
        <div id="password_error" class="help" style="display:none;color:#C62828"></div>

        <label class="fl" for="password_confirmation" style="margin-top:14px">Confirm new password</label>
        <div class="pw-wrap">
          <input class="in" id="password_confirmation" name="password_confirmation" type="password" autocomplete="new-password" aria-label="Confirm new password">
          <button type="button" class="pw-eye" data-target="password_confirmation" aria-label="Show confirm password"><span class="ms">visibility</span></button>
        </div>
        <div id="password_confirmation_error" class="help" style="display:none;color:#C62828"></div>
        <div class="help">At least 8 characters, with a number and a symbol</div>

        <div class="row" style="justify-content:flex-end;margin-top:16px">
          <button type="submit" id="passwordSaveBtn" class="btn"><span class="ms">lock_reset</span>Update password</button>
        </div>
      </form>
    </div>
  </div>
@endsection

@push('styles')
<style>
  .pw-wrap{position:relative}
  .pw-wrap .in{padding-right:44px}
  .pw-eye{position:absolute;right:10px;top:50%;transform:translateY(-50%);border:0;background:none;padding:4px;cursor:pointer;color:var(--faint);display:inline-flex;align-items:center;justify-content:center;line-height:1}
  .pw-eye:hover{color:var(--ink)}
  .pw-eye .ms{font-size:20px}
</style>
@endpush

@push('scripts')
<script>
(function () {
  var form = document.getElementById('studioPasswordForm');
  if (!form) return;

  var btn = document.getElementById('passwordSaveBtn');
  var fields = ['current_password', 'password', 'password_confirmation'];
  var defaultBtnHtml = btn ? btn.innerHTML : '';

  form.querySelectorAll('.pw-eye').forEach(function (toggleBtn) {
    toggleBtn.addEventListener('click', function () {
      var target = document.getElementById(toggleBtn.getAttribute('data-target'));
      if (!target) return;
      var icon = toggleBtn.querySelector('.ms');
      var show = target.getAttribute('type') === 'password';
      target.setAttribute('type', show ? 'text' : 'password');
      if (icon) icon.textContent = show ? 'visibility_off' : 'visibility';
      toggleBtn.setAttribute('aria-label', show ? 'Hide password' : 'Show password');
    });
  });

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

  function fieldErrorMessage(errors, name) {
    if (!errors) return '';
    if (errors[name] && errors[name][0]) return errors[name][0];
    var key = Object.keys(errors).find(function (k) {
      return k === name || k.endsWith('.' + name);
    });
    return key && errors[key] && errors[key][0] ? errors[key][0] : '';
  }

  fields.forEach(function (name) {
    var input = document.getElementById(name);
    if (input) input.addEventListener('input', function () { clearFieldError(name); });
  });

  form.addEventListener('submit', function (e) {
    e.preventDefault();
    clearAllErrors();

    if (btn) {
      btn.disabled = true;
      btn.innerHTML = '<span class="ms">hourglass_top</span>Updating…';
    }

    var body = new FormData(form);
    body.set('_method', 'PUT');

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
          form.reset();
          fields.forEach(function (name) {
            var input = document.getElementById(name);
            if (input) input.setAttribute('type', 'password');
          });
          form.querySelectorAll('.pw-eye .ms').forEach(function (icon) {
            icon.textContent = 'visibility';
          });
          if (window.bpToast) window.bpToast('Password updated');
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
          window.bpToast((result.data && result.data.message) || 'Could not update password.', true);
        }
      })
      .catch(function () {
        if (window.bpToast) window.bpToast('Something went wrong. Please try again.', true);
        else setFieldError('current_password', 'Something went wrong. Please try again.');
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
