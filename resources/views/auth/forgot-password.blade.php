@extends('layouts.new-auth')

@section('title', 'Reset Password | Bookpay by Inkjin')
@section('meta_description', 'Forgot your Bookpay password? Reset it here to get back to managing your bookings and payments.')
@section('canonical', 'https://bookpay.inkjin.com/forgot-password')
@section('robots', 'noindex, follow')
@section('og_title', 'Reset Password | Bookpay by Inkjin')
@section('og_description', 'Reset your Bookpay password to get back to your bookings and payments.')
@section('og_url', 'https://bookpay.inkjin.com/forgot-password')
@section('og_image')
{{ asset('design/images/bookpay-og.jpeg') }}
@endsection
@section('twitter_title', 'Reset Password | Bookpay by Inkjin')
@section('twitter_description', 'Reset your Bookpay password to get back to your bookings and payments.')
@section('twitter_image')
{{ asset('design/images/bookpay-og.jpeg') }}
@endsection

@section('content')
  <div id="forgot-app"
    data-action="{{ route('password.email') }}"
    data-login="{{ route('login') }}"
    data-csrf="{{ csrf_token() }}"
    data-initial-email="{{ $initialEmail ?? old('email', '') }}"
    data-auto-send="{{ ! empty($autoSend) ? '1' : '0' }}"
    data-initial-status="{{ session('status') ? 'sent' : '' }}"
    data-status-message="{{ session('status') }}"
    data-email-error="{{ $errors->first('email') }}">
  </div>
@endsection

@push('scripts')
<script>
(function () {
  var EMAIL = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
  var MAILS = [
    [/^(gmail|googlemail)\./, 'Gmail', 'https://mail.google.com'],
    [/^(outlook|hotmail|live|msn)\./, 'Outlook', 'https://outlook.live.com/mail/'],
    [/^(yahoo|ymail)\./, 'Yahoo Mail', 'https://mail.yahoo.com'],
    [/^(icloud|me|mac)\.com$/, 'iCloud Mail', 'https://www.icloud.com/mail'],
    [/^(proton|protonmail|pm)\./, 'Proton Mail', 'https://mail.proton.me']
  ];

  var root = document.getElementById('forgot-app');
  if (!root) return;

  var action = root.dataset.action;
  var loginUrl = root.dataset.login;
  var csrf = root.dataset.csrf;
  var mail = (root.dataset.initialEmail || '').trim();
  var timer = null;

  function esc(s) {
    return String(s || '')
      .replace(/&/g, '&amp;')
      .replace(/</g, '&lt;')
      .replace(/>/g, '&gt;')
      .replace(/"/g, '&quot;');
  }

  function inboxButton(email) {
    var domain = (email.split('@')[1] || '').toLowerCase();
    for (var i = 0; i < MAILS.length; i++) {
      if (MAILS[i][0].test(domain)) {
        return '<a class="btn ghost" href="' + MAILS[i][2] + '" target="_blank" rel="noopener" style="margin-top:0"><span class="ms">mail</span>Open ' + MAILS[i][1] + '</a>';
      }
    }
    return '';
  }

  function showForm(prefill, errorMessage) {
    clearInterval(timer);
    root.innerHTML =
      '<h1>Forgot password?</h1>' +
      '<p class="sub">No worries. Enter the email you signed up with and we\'ll send you a link to reset your password.</p>' +
      (errorMessage
        ? '<div class="banner bad" role="alert"><span class="ms">error</span><span>' + esc(errorMessage) + '</span></div>'
        : '') +
      '<form id="forgot-form" novalidate>' +
        '<label class="fl" for="reset-email" style="margin-top:0">Email address</label>' +
        '<div class="in" id="email-wrap"><input id="reset-email" type="email" name="email" autocomplete="email" placeholder="name@company.com" value="' + esc(prefill || '') + '"></div>' +
        '<div class="err" id="email-error">Enter a valid email address</div>' +
        '<button class="btn" id="forgot-submit" type="submit">Send reset link<span class="ms">arrow_forward</span></button>' +
      '</form>' +
      '<a class="back" href="' + loginUrl + '"><span class="ms">arrow_back</span>Back to sign in</a>';

    var emailInput = document.getElementById('reset-email');
    var emailWrap = document.getElementById('email-wrap');
    var emailError = document.getElementById('email-error');
    var form = document.getElementById('forgot-form');
    var submitBtn = document.getElementById('forgot-submit');
    var originalBtnHtml = submitBtn.innerHTML;

    setTimeout(function () { emailInput.focus(); }, 30);

    emailInput.addEventListener('input', function () {
      emailWrap.classList.remove('bad');
      emailError.classList.remove('on');
    });

    form.addEventListener('submit', function (e) {
      e.preventDefault();
      var value = emailInput.value.trim();
      if (!EMAIL.test(value)) {
        emailWrap.classList.add('bad');
        emailError.classList.add('on');
        emailError.textContent = 'Enter a valid email address';
        return;
      }

      mail = value;
      submitBtn.disabled = true;
      submitBtn.textContent = 'Sending…';

      var body = new FormData();
      body.append('_token', csrf);
      body.append('email', mail);

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
          showSent(false);
          return;
        }

        var errors = result.data.errors || {};
        var message = (errors.email && errors.email[0])
          || result.data.message
          || 'Please check your email address and try again.';

        emailWrap.classList.add('bad');
        emailError.classList.add('on');
        emailError.textContent = message;
        submitBtn.disabled = false;
        submitBtn.innerHTML = originalBtnHtml;
      }).catch(function () {
        emailWrap.classList.add('bad');
        emailError.classList.add('on');
        emailError.textContent = 'Something went wrong. Please try again.';
        submitBtn.disabled = false;
        submitBtn.innerHTML = originalBtnHtml;
      });
    });
  }

  function showSent(showResentBanner) {
    clearInterval(timer);
    var openMail = inboxButton(mail);

    root.innerHTML =
      (showResentBanner
        ? '<div class="banner ok" role="status"><span class="ms">check_circle</span><span>We sent a new link.</span></div>'
        : '') +
      '<div class="icon"><span class="ms">mark_email_read</span></div>' +
      '<h1>Check your email</h1>' +
      '<p class="sub">If there\'s a Bookpay account for <b>' + esc(mail) + '</b>, we\'ve sent a link to reset your password. The link works for 60 minutes.</p>' +
      openMail +
      '<p class="small" style="margin-top:' + (openMail ? '18px' : '0') + '">Can\'t find it? Check your spam folder, or <button type="button" class="link" id="resend-btn" disabled>resend in <span id="countdown">60</span>s</button></p>' +
      '<p class="small" style="margin-top:6px"><button type="button" class="link" id="other-email">Use a different email</button></p>' +
      '<a class="back" href="' + loginUrl + '"><span class="ms">arrow_back</span>Back to sign in</a>';

    var seconds = 60;
    var resendBtn = document.getElementById('resend-btn');
    var countdown = document.getElementById('countdown');

    timer = setInterval(function () {
      seconds -= 1;
      if (seconds <= 0) {
        clearInterval(timer);
        resendBtn.disabled = false;
        resendBtn.textContent = 'resend the link';
      } else if (countdown) {
        countdown.textContent = String(seconds);
      }
    }, 1000);

    resendBtn.addEventListener('click', function () {
      if (resendBtn.disabled) return;
      resendBtn.disabled = true;
      resendBtn.textContent = 'Sending…';

      var body = new FormData();
      body.append('_token', csrf);
      body.append('email', mail);

      fetch(action, {
        method: 'POST',
        body: body,
        headers: {
          'X-Requested-With': 'XMLHttpRequest',
          'Accept': 'application/json'
        },
        credentials: 'same-origin'
      }).finally(function () {
        showSent(true);
      });
    });

    document.getElementById('other-email').addEventListener('click', function () {
      showForm(mail);
    });
  }

  function showSending(email) {
    clearInterval(timer);
    root.innerHTML =
      '<div class="icon"><span class="ms">hourglass_top</span></div>' +
      '<h1>Sending reset link</h1>' +
      '<p class="sub">One moment — we\'re sending a new link to <b>' + esc(email) + '</b>.</p>';
  }

  function requestResetLink(email) {
    var body = new FormData();
    body.append('_token', csrf);
    body.append('email', email);

    return fetch(action, {
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
    });
  }

  function autoSendLink(email) {
    mail = email;
    showSending(email);
    requestResetLink(email).then(function (result) {
      if (result.status >= 200 && result.status < 300) {
        showSent(false);
        return;
      }

      var errors = result.data.errors || {};
      var message = (errors.email && errors.email[0])
        || result.data.message
        || 'Please check your email address and try again.';
      showForm(email, message);
    }).catch(function () {
      showForm(email, 'Something went wrong. Please try again.');
    });
  }

  if (root.dataset.initialStatus === 'sent' && mail) {
    showSent(false);
  } else if (root.dataset.autoSend === '1' && EMAIL.test(mail)) {
    autoSendLink(mail);
  } else {
    showForm(mail, root.dataset.emailError || '');
  }
})();
</script>
@endpush
