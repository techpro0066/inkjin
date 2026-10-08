@extends('layouts.new-artist-dashboard-layout')

@section('title', 'Account')

@section('styles')
<style>
  .pw-page{max-width:560px}
  .pw-page .fl{font-size:13px;font-weight:700;margin-bottom:6px;display:block}
  .pw-page .help{font-size:12px;color:var(--faint);margin-top:6px}
  .pw-page .ch h3{font-size:16px;font-weight:700;margin:0}
  .pw-page .ch .faint{font-size:12.5px;font-weight:400;margin-top:2px;color:var(--faint)}
  .pw-page .in{
    background:#fff;border:1px solid var(--line);border-radius:10px;padding:0 13px;
    font-family:inherit;font-size:13.5px;font-weight:400;line-height:normal;color:var(--ink);
    display:flex;align-items:center;gap:8px;min-height:40px;width:100%
  }
  .pw-page .in:focus-within{border-color:#3E007C;box-shadow:0 0 0 3px #F3E8FF}
  .pw-page .in.bad{border-color:var(--red)}
  .pw-page .in input{
    border:0;outline:0;flex:1;min-width:0;background:none;padding:10px 0;color:inherit;
    font-family:inherit;font-size:inherit;font-weight:inherit;line-height:inherit
  }
  .pw-page .in .eye{
    border:0;background:none;padding:0;cursor:pointer;display:inline-flex;align-items:center;
    color:var(--faint);line-height:1
  }
  .pw-page .in .eye .ms{font-size:18px;color:inherit}
  .pw-page .in .eye:hover{color:var(--ink)}
  .pw-page .btn{
    display:inline-flex;align-items:center;justify-content:center;gap:8px;
    background:var(--ink);color:#fff;border:0;border-radius:10px;padding:10px 16px;
    font-family:inherit;font-weight:600;font-size:13.5px;line-height:normal;
    white-space:nowrap;cursor:pointer;-webkit-appearance:none;appearance:none
  }
  .pw-page .btn .ms{font-size:18px}
  .pw-page .btn:disabled{opacity:.55;cursor:not-allowed}
  .pw-err{color:var(--red);font-size:12.5px;margin-top:6px}
  .pw-err[hidden]{display:none!important}
  .pw-toast{
    position:fixed;top:22px;right:24px;background:#1A1A1A;color:#fff;padding:12px 18px;border-radius:10px;
    font-weight:600;font-size:14px;z-index:400;box-shadow:0 10px 30px rgba(0,0,0,.22);
    display:flex;gap:10px;align-items:center;opacity:0;transform:translateX(calc(100% + 40px));
    transition:opacity .25s,transform .35s cubic-bezier(.2,.8,.2,1);pointer-events:none
  }
  .pw-toast.on{opacity:1;transform:none}
  .pw-toast.err .ms{color:#FF8A80}
  .pw-toast .ms{color:#3DD68C;font-size:20px}
  @media (max-width:900px){
    .pw-toast{left:16px;right:16px;top:74px}
  }
</style>
@endsection

@section('content')
  <div class="head">
    <div>
      <h1>
        Account
        <a class="help-q"
           href="https://help.inkjin.com/en/articles/17201415-dashboard-account-password"
           target="_blank"
           rel="noopener"
           data-help-article="09b-account-password"
           title="Help with this page"
           aria-label="Help with this page">
          <span class="ms">help</span>
        </a>
      </h1>
      <div class="sub">Your login, contact details, studio and region. Set once, rarely changed.</div>
    </div>
  </div>

  @include('artist.partials.profile-settings-tabs', ['activeProfileTab' => 'password'])

  <div class="pw-page">
    <div class="card">
      <div class="ch">
        <div>
          <h3>Change password</h3>
          <div class="faint" style="font-size:12.5px;margin-top:2px">Use a password you don't use anywhere else</div>
        </div>
      </div>
      <form id="updatePasswordForm" method="post" action="{{ route('password.update') }}" style="padding:18px 22px" novalidate>
        @csrf
        @method('put')

        <span class="fl" style="margin-top:0;display:flex;justify-content:space-between;align-items:baseline">
          Current password
          {{-- <a href="{{ route('password.request') }}" style="font-size:12px;font-weight:600;color:#3E007C;text-decoration:none">Forgot your current password?</a> --}}
        </span>
        <div class="in" id="current_password_wrap">
          <input type="password" id="current_password" name="current_password" autocomplete="current-password" required>
          <button type="button" class="eye" data-target="current_password" aria-label="Show current password">
            <span class="ms">visibility</span>
          </button>
        </div>
        <div class="pw-err" id="current_password_error" hidden></div>

        <span class="fl" style="margin-top:14px">New password</span>
        <div class="in" id="password_wrap">
          <input type="password" id="password" name="password" autocomplete="new-password" required>
          <button type="button" class="eye" data-target="password" aria-label="Show new password">
            <span class="ms">visibility</span>
          </button>
        </div>
        <div class="pw-err" id="password_error" hidden></div>

        <span class="fl" style="margin-top:14px">Confirm new password</span>
        <div class="in" id="password_confirmation_wrap">
          <input type="password" id="password_confirmation" name="password_confirmation" autocomplete="new-password" required>
          <button type="button" class="eye" data-target="password_confirmation" aria-label="Show confirm password">
            <span class="ms">visibility</span>
          </button>
        </div>
        <div class="pw-err" id="password_confirmation_error" hidden></div>

        <div class="help">At least 8 characters, with a number and a symbol</div>

        <div class="row" style="justify-content:flex-end;margin-top:16px">
          <button type="submit" class="btn" id="updatePasswordBtn">
            <span class="ms">lock_reset</span><span id="updatePasswordLabel">Update password</span>
          </button>
        </div>
      </form>
    </div>
  </div>

  <div class="pw-toast" id="passwordToast" role="status">
    <span class="ms" id="passwordToastIcon">check_circle</span>
    <span id="passwordToastMsg">Password updated</span>
  </div>
@endsection

@section('scripts')
<script>
(function () {
  var form = document.getElementById('updatePasswordForm');
  var btn = document.getElementById('updatePasswordBtn');
  var label = document.getElementById('updatePasswordLabel');
  var toast = document.getElementById('passwordToast');
  var toastMsg = document.getElementById('passwordToastMsg');
  var toastIcon = document.getElementById('passwordToastIcon');
  var toastTimer = null;
  var fields = ['current_password', 'password', 'password_confirmation'];

  function showToast(msg, isError) {
    if (!toast || !toastMsg) return;
    toastMsg.textContent = msg || (isError ? 'Something went wrong' : 'Password updated');
    toast.classList.toggle('err', !!isError);
    if (toastIcon) toastIcon.textContent = isError ? 'error' : 'check_circle';
    toast.classList.add('on');
    clearTimeout(toastTimer);
    toastTimer = setTimeout(function () { toast.classList.remove('on'); }, 2800);
  }

  function wrap(name) {
    return document.getElementById(name + '_wrap');
  }

  function clearError(name) {
    var err = document.getElementById(name + '_error');
    var el = wrap(name);
    if (err) { err.hidden = true; err.textContent = ''; }
    if (el) el.classList.remove('bad');
  }

  function setError(name, message) {
    var err = document.getElementById(name + '_error');
    var el = wrap(name);
    if (err) { err.textContent = message || ''; err.hidden = !message; }
    if (el) el.classList.toggle('bad', !!message);
  }

  function clearAll() {
    fields.forEach(clearError);
  }

  function validate() {
    var current = (document.getElementById('current_password').value || '');
    var next = (document.getElementById('password').value || '');
    var confirm = (document.getElementById('password_confirmation').value || '');
    var ok = true;

    if (!current) {
      setError('current_password', 'Enter your current password');
      showToast('Enter your current password', true);
      ok = false;
      return false;
    }
    if (next.length < 8 || !/\d/.test(next) || !/[^A-Za-z0-9]/.test(next)) {
      setError('password', 'Use at least 8 characters, with a number and a symbol');
      showToast('Use at least 8 characters, with a number and a symbol', true);
      ok = false;
      return false;
    }
    if (next !== confirm) {
      setError('password_confirmation', "The new passwords don't match");
      showToast("The new passwords don't match", true);
      ok = false;
      return false;
    }
    return ok;
  }

  document.querySelectorAll('.pw-page .eye').forEach(function (eye) {
    eye.addEventListener('click', function () {
      var input = document.getElementById(eye.getAttribute('data-target'));
      var icon = eye.querySelector('.ms');
      if (!input) return;
      var show = input.getAttribute('type') === 'password';
      input.setAttribute('type', show ? 'text' : 'password');
      if (icon) icon.textContent = show ? 'visibility_off' : 'visibility';
      eye.setAttribute('aria-label', show ? 'Hide password' : 'Show password');
    });
  });

  fields.forEach(function (name) {
    document.getElementById(name)?.addEventListener('input', function () { clearError(name); });
  });

  if (!form) return;

  form.addEventListener('submit', function (e) {
    e.preventDefault();
    clearAll();
    if (!validate()) return;

    btn.disabled = true;
    if (label) label.textContent = 'Updating…';

    fetch(form.action, {
      method: 'POST',
      headers: {
        'Accept': 'application/json',
        'X-Requested-With': 'XMLHttpRequest',
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
      },
      body: new FormData(form),
    })
      .then(function (res) {
        return res.json().then(function (data) {
          return { ok: res.ok, status: res.status, data: data };
        }).catch(function () {
          return { ok: res.ok, status: res.status, data: {} };
        });
      })
      .then(function (result) {
        if (result.ok && result.data && result.data.success) {
          form.reset();
          fields.forEach(function (name) {
            var input = document.getElementById(name);
            if (input) input.setAttribute('type', 'password');
          });
          document.querySelectorAll('.pw-page .eye .ms').forEach(function (icon) {
            icon.textContent = 'visibility';
          });
          showToast(result.data.message || 'Password updated');
          return;
        }

        if (result.status === 422 && result.data && result.data.errors) {
          var errors = result.data.errors;
          var firstMsg = null;
          fields.forEach(function (name) {
            var key = Object.keys(errors).find(function (k) {
              return k === name || k.endsWith('.' + name);
            });
            if (key && errors[key] && errors[key][0]) {
              setError(name, errors[key][0]);
              if (!firstMsg) firstMsg = errors[key][0];
            }
          });
          if (firstMsg) showToast(firstMsg, true);
          return;
        }

        showToast((result.data && result.data.message) || 'Could not update password. Try again.', true);
      })
      .catch(function () {
        showToast('Network error. Try again.', true);
      })
      .finally(function () {
        btn.disabled = false;
        if (label) label.textContent = 'Update password';
      });
  });

  @if (session('status') === 'password-updated')
    showToast('Password updated');
  @endif
})();
</script>
@endsection
