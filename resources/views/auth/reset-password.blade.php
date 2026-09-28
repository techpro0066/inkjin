@extends('layouts.new-auth')

@section('title', 'Set New Password | Bookpay by Inkjin')
@section('meta_description', 'Set a new Bookpay password to get back to managing your bookings and payments.')
@section('robots', 'noindex, follow')
@section('og_title', 'Set New Password | Bookpay by Inkjin')
@section('og_description', 'Set a new Bookpay password to get back to your bookings and payments.')
@section('og_image')
{{ asset('design/images/bookpay-og.jpeg') }}
@endsection
@section('twitter_title', 'Set New Password | Bookpay by Inkjin')
@section('twitter_description', 'Set a new Bookpay password to get back to your bookings and payments.')
@section('twitter_image')
{{ asset('design/images/bookpay-og.jpeg') }}
@endsection

@section('content')
  @php
    $resetEmail = old('email', $request->email);
    $tokenError = $errors->first('email') ?: $errors->first('token');
    $looksExpired = ($linkExpired ?? false) || ($tokenError && (
      str_contains(strtolower($tokenError), 'token')
      || str_contains(strtolower($tokenError), 'expired')
      || str_contains(strtolower($tokenError), 'invalid')
    ));
  @endphp

  <div id="reset-app"
    data-action="{{ route('password.store') }}"
    data-login="{{ route('login') }}"
    data-forgot="{{ route('password.request') }}"
    data-csrf="{{ csrf_token() }}"
    data-token="{{ $request->route('token') }}"
    data-email="{{ $resetEmail }}"
    data-expired="{{ $looksExpired ? '1' : '0' }}"
    data-email-error="{{ $errors->first('email') }}"
    data-password-error="{{ $errors->first('password') }}">
  </div>
@endsection

@push('scripts')
<script>
(function () {
  var root = document.getElementById('reset-app');
  if (!root) return;

  var action = root.dataset.action;
  var loginUrl = root.dataset.login;
  var forgotUrl = root.dataset.forgot;
  var csrf = root.dataset.csrf;
  var token = root.dataset.token || '';
  var email = (root.dataset.email || '').trim();

  function esc(s) {
    return String(s || '')
      .replace(/&/g, '&amp;')
      .replace(/</g, '&lt;')
      .replace(/>/g, '&gt;')
      .replace(/"/g, '&quot;');
  }

  function bindEyes(scope) {
    (scope || document).querySelectorAll('.eye').forEach(function (btn) {
      btn.onclick = function () {
        var input = btn.parentNode.querySelector('input');
        if (!input) return;
        var show = input.type === 'password';
        input.type = show ? 'text' : 'password';
        var icon = btn.querySelector('.ms');
        if (icon) icon.textContent = show ? 'visibility_off' : 'visibility';
        btn.setAttribute('aria-label', show ? 'Hide password' : 'Show password');
      };
    });
  }

  function rules(value) {
    return [
      [value.length >= 8, 'At least 8 characters'],
      [/\d/.test(value), 'A number'],
      [/[^A-Za-z0-9]/.test(value), 'A symbol']
    ];
  }

  function showDone() {
    root.innerHTML =
      '<div class="icon" style="background:#E6F8EE;color:#1F6B4E"><span class="ms">check</span></div>' +
      '<h1>Password changed</h1>' +
      '<p class="sub">You can now sign in with your new password.</p>' +
      '<a class="btn" href="' + loginUrl + '" style="margin-top:0">Sign in<span class="ms">arrow_forward</span></a>';
  }

  function showExpired() {
    var sendHref = email
      ? forgotUrl + (forgotUrl.indexOf('?') >= 0 ? '&' : '?') + 'email=' + encodeURIComponent(email) + '&send=1'
      : forgotUrl;

    root.innerHTML =
      '<div class="icon" style="background:#FFF3DC;color:#B7791F"><span class="ms">link_off</span></div>' +
      '<h1>This link has expired</h1>' +
      '<p class="sub">Reset links work for 60 minutes and only once. Ask for a new one and use it straight away.</p>' +
      '<a class="btn" href="' + sendHref + '" style="margin-top:0">Send a new link<span class="ms">arrow_forward</span></a>' +
      '<a class="back" href="' + loginUrl + '"><span class="ms">arrow_back</span>Back to sign in</a>';
  }

  function showForm(options) {
    options = options || {};
    var passwordError = options.passwordError || '';
    var confirmError = options.confirmError || '';
    var bannerError = options.bannerError || '';

    root.innerHTML =
      '<h1>Set a new password</h1>' +
      '<p class="sub">For <b>' + esc(email || 'your account') + '</b>. Pick one you don\'t use anywhere else.</p>' +
      (bannerError
        ? '<div class="banner bad" role="alert"><span class="ms">error</span><span>' + esc(bannerError) + '</span></div>'
        : '') +
      '<form id="reset-form" novalidate>' +
        '<input type="hidden" name="token" value="' + esc(token) + '">' +
        '<input type="hidden" name="email" value="' + esc(email) + '">' +
        '<label class="fl" for="p1" style="margin-top:0">New password</label>' +
        '<div class="in" id="p1w">' +
          '<input id="p1" name="password" type="password" autocomplete="new-password">' +
          '<button type="button" class="eye" aria-label="Show password"><span class="ms">visibility</span></button>' +
        '</div>' +
        '<div class="meter" aria-hidden="true"><i></i><i></i><i></i></div>' +
        '<ul class="rules" id="rl"></ul>' +
        '<div class="err' + (passwordError ? ' on' : '') + '" id="p1e">' + esc(passwordError || 'Choose a stronger password') + '</div>' +
        '<label class="fl" for="p2">Confirm new password</label>' +
        '<div class="in" id="p2w">' +
          '<input id="p2" name="password_confirmation" type="password" autocomplete="new-password">' +
          '<button type="button" class="eye" aria-label="Show password"><span class="ms">visibility</span></button>' +
        '</div>' +
        '<div class="err' + (confirmError ? ' on' : '') + '" id="p2e">' + esc(confirmError || 'The passwords don\'t match') + '</div>' +
        '<button class="btn" id="go" type="submit">Save new password<span class="ms">arrow_forward</span></button>' +
      '</form>' +
      '<a class="back" href="' + loginUrl + '"><span class="ms">arrow_back</span>Back to sign in</a>';

    bindEyes(root);

    var p1 = document.getElementById('p1');
    var p2 = document.getElementById('p2');
    var p1w = document.getElementById('p1w');
    var p2w = document.getElementById('p2w');
    var p1e = document.getElementById('p1e');
    var p2e = document.getElementById('p2e');
    var rl = document.getElementById('rl');
    var form = document.getElementById('reset-form');
    var submitBtn = document.getElementById('go');
    var originalBtnHtml = submitBtn.innerHTML;
    var meter = root.querySelectorAll('.meter i');

    function updateRules() {
      var list = rules(p1.value);
      var passed = list.filter(function (item) { return item[0]; }).length;
      rl.innerHTML = list.map(function (item) {
        return '<li class="' + (item[0] ? 'ok' : '') + '"><span class="ms">' +
          (item[0] ? 'check_circle' : 'radio_button_unchecked') +
          '</span>' + item[1] + '</li>';
      }).join('');
      var colors = ['#E0A526', '#E0A526', '#1F6B4E'];
      meter.forEach(function (bar, index) {
        bar.style.background = index < passed ? colors[Math.max(passed - 1, 0)] : '#EDE6F0';
      });
    }

    p1.addEventListener('input', function () {
      p1w.classList.remove('bad');
      p1e.classList.remove('on');
      updateRules();
    });
    p2.addEventListener('input', function () {
      p2w.classList.remove('bad');
      p2e.classList.remove('on');
    });

    updateRules();
    setTimeout(function () { p1.focus(); }, 30);

    if (passwordError) p1w.classList.add('bad');
    if (confirmError) p2w.classList.add('bad');

    form.addEventListener('submit', function (e) {
      e.preventDefault();

      var list = rules(p1.value);
      var strong = list.every(function (item) { return item[0]; });
      var match = p1.value === p2.value && !!p2.value;

      p1w.classList.toggle('bad', !strong);
      p1e.classList.toggle('on', !strong);
      if (!strong) p1e.textContent = 'Choose a stronger password';

      p2w.classList.toggle('bad', !match);
      p2e.classList.toggle('on', !match);
      if (!match) p2e.textContent = 'The passwords don\'t match';

      if (!strong || !match) return;

      submitBtn.disabled = true;
      submitBtn.textContent = 'Saving…';

      var body = new FormData(form);
      body.append('_token', csrf);

      fetch(action, {
        method: 'POST',
        body: body,
        headers: {
          'X-Requested-With': 'XMLHttpRequest',
          'Accept': 'application/json'
        },
        credentials: 'same-origin'
      }).then(function (res) {
        return res.json().then(function (data) {
          return { status: res.status, data: data || {} };
        }).catch(function () {
          return { status: res.status, data: {} };
        });
      }).then(function (result) {
        if (result.status >= 200 && result.status < 300) {
          showDone();
          return;
        }

        var errors = result.data.errors || {};
        var emailMsg = errors.email && errors.email[0] ? errors.email[0] : '';
        var tokenMsg = errors.token && errors.token[0] ? errors.token[0] : '';
        var passwordMsg = errors.password && errors.password[0] ? errors.password[0] : '';
        var confirmMsg = errors.password_confirmation && errors.password_confirmation[0]
          ? errors.password_confirmation[0]
          : '';
        var combined = (emailMsg + ' ' + tokenMsg + ' ' + (result.data.message || '')).toLowerCase();

        if (/token|expired|invalid|reset/.test(combined) && !passwordMsg && !confirmMsg) {
          showExpired();
          return;
        }

        showForm({
          passwordError: passwordMsg,
          confirmError: confirmMsg,
          bannerError: (!passwordMsg && !confirmMsg)
            ? (emailMsg || result.data.message || 'Unable to reset password. Please try again.')
            : ''
        });
      }).catch(function () {
        submitBtn.disabled = false;
        submitBtn.innerHTML = originalBtnHtml;
        showForm({
          bannerError: 'Something went wrong. Please try again.'
        });
      });
    });
  }

  if (root.dataset.expired === '1') {
    showExpired();
  } else {
    showForm({
      passwordError: root.dataset.passwordError || '',
      bannerError: root.dataset.emailError || ''
    });
  }
})();
</script>
@endpush
