@extends('layouts.new-auth')

@section('title', 'Log In | Bookpay by Inkjin')
@section('meta_description', 'Log in to Bookpay to manage your bookings, client intake, and payouts. The booking platform built for tattoo artists.')
@section('canonical', 'https://bookpay.inkjin.com/login')
@section('robots', 'noindex, follow')
@section('og_title', 'Log In | Bookpay by Inkjin')
@section('og_description', 'Log in to Bookpay to manage your bookings, client intake, and payouts.')
@section('og_url', 'https://bookpay.inkjin.com/login')
@section('og_image')
{{ asset('design/images/bookpay-og.jpeg') }}
@endsection
@section('twitter_title', 'Log In | Bookpay by Inkjin')
@section('twitter_description', 'Log in to Bookpay to manage your bookings, client intake, and payouts.')
@section('twitter_image')
{{ asset('design/images/bookpay-og.jpeg') }}
@endsection

{{-- Login prototype hides the contact-help line --}}
@section('help')
@endsection

@php
  $loginLocked = $loginLocked ?? false;
  $loginBanner = ($errors->has('email') && ! $errors->has('password')) ? $errors->first('email') : '';
  $loginLocked = $loginLocked
    || $errors->has('locked')
    || ($loginBanner !== '' && str_contains(strtolower($loginBanner), 'too many tries'));
@endphp

@section('content')
  <h1 id="login-title">Welcome back!</h1>
  <p class="sub" id="login-sub">Continue to your bookings and payments.</p>

  @if (session('status') === 'email-changed')
    <div class="banner bad" role="alert">
      <span class="ms">error</span>
      <span>{{ session('message', 'Your email address has been updated. Please verify your new email address before logging in again.') }}</span>
    </div>
  @elseif ($loginLocked)
    <div class="banner bad" role="alert" id="login-alert-server">
      <span class="ms">lock_clock</span>
      <span>
        Too many tries. Wait 15 minutes, or
        <a href="{{ route('password.request') }}" style="color:inherit;font-weight:700">reset your password</a>.
      </span>
    </div>
  @elseif ($loginBanner !== '')
    <div class="banner bad" role="alert" id="login-alert-server">
      <span class="ms">error</span>
      <span>{{ $loginBanner }}</span>
    </div>
  @endif

  <div id="login-alert" class="banner bad" role="alert" style="display:none"></div>

  <form action="{{ route('login') }}" method="POST" id="login-form" novalidate>
    @csrf

    <label class="fl" for="login-email" style="margin-top:4px">Email address</label>
    <div class="in" id="email-wrap">
      <input
        id="login-email"
        name="email"
        type="email"
        autocomplete="email"
        placeholder="you@example.com"
        value="{{ old('email') }}"
        autofocus
      >
    </div>
    <div class="err" id="email-error">Enter a valid email address</div>

    <div class="fl">
      <label for="login-password">Password</label>
      @if (Route::has('password.request'))
        <a href="{{ route('password.request') }}">Forgot password?</a>
      @endif
    </div>
    <div class="in{{ $errors->has('password') ? ' bad' : '' }}" id="password-wrap">
      <input
        id="login-password"
        name="password"
        type="password"
        autocomplete="current-password"
      >
      <button type="button" class="eye" aria-label="Show password">
        <span class="ms">visibility</span>
      </button>
    </div>
    <div class="err{{ $errors->has('password') ? ' on' : '' }}" id="password-error">{{ $errors->first('password') ?: 'Enter your password' }}</div>

    <label class="ck">
      <input type="checkbox" id="remember" name="remember" value="1" {{ old('remember') ? 'checked' : '' }}>
      Keep me signed in on this device
    </label>

    <button class="btn" type="submit" id="login-submit-btn" @if ($loginLocked) disabled @endif>
      Sign in<span class="ms">arrow_forward</span>
    </button>
  </form>

  <div class="alt">
    Don't have an account?
    <a href="{{ route('register') }}">Sign up for free</a>
  </div>
@endsection

@push('scripts')
<script>
(function () {
  var EMAIL = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
  var RETURNING_KEY = 'bp-signed-in-before';
  var form = document.getElementById('login-form');
  var alertEl = document.getElementById('login-alert');
  var titleEl = document.getElementById('login-title');
  var subEl = document.getElementById('login-sub');
  var emailInput = document.getElementById('login-email');
  var passwordInput = document.getElementById('login-password');
  var emailWrap = document.getElementById('email-wrap');
  var passwordWrap = document.getElementById('password-wrap');
  var emailError = document.getElementById('email-error');
  var passwordError = document.getElementById('password-error');
  var forgotUrl = @json(route('password.request'));
  var submitBtn = document.getElementById('login-submit-btn');
  var originalBtnHtml = submitBtn ? submitBtn.innerHTML : '';
  var isLocked = @json($loginLocked);

  function setLocked(locked) {
    isLocked = !!locked;
    if (submitBtn) {
      submitBtn.disabled = isLocked;
      if (isLocked) submitBtn.innerHTML = originalBtnHtml;
    }
  }

  function isReturningUser() {
    try {
      return localStorage.getItem(RETURNING_KEY) === '1';
    } catch (e) {
      return false;
    }
  }

  function markReturningUser() {
    try {
      localStorage.setItem(RETURNING_KEY, '1');
    } catch (e) { /* ignore */ }
  }

  function applyHeadingCopy() {
    if (!titleEl || !subEl) return;
    if (isReturningUser()) {
      titleEl.textContent = 'Welcome back!';
      subEl.textContent = 'Continue to your bookings and payments.';
    } else {
      titleEl.textContent = 'Sign in to Bookpay';
      subEl.textContent = 'Manage your bookings and payments.';
    }
  }

  applyHeadingCopy();

  function showAlert(message, options) {
    options = options || {};
    if (!alertEl) return;
    var serverBanner = document.getElementById('login-alert-server');
    if (serverBanner) serverBanner.style.display = 'none';

    alertEl.style.display = 'flex';
    if (options.locked) {
      alertEl.innerHTML =
        '<span class="ms">lock_clock</span>' +
        '<span>Too many tries. Wait 15 minutes, or <a href="' + forgotUrl + '" style="color:inherit;font-weight:700">reset your password</a>.</span>';
      setLocked(true);
      return;
    }

    alertEl.innerHTML = '<span class="ms">error</span><span></span>';
    alertEl.querySelector('span:last-child').textContent = message || 'That email and password don\'t match. Check them and try again.';
  }

  function hideAlert() {
    if (isLocked) return;
    if (!alertEl) return;
    alertEl.style.display = 'none';
    alertEl.innerHTML = '';
  }

  function setFieldError(wrap, err, on, message) {
    if (wrap) wrap.classList.toggle('bad', on);
    if (err) {
      err.classList.toggle('on', on);
      if (on && message) err.textContent = message;
    }
  }

  function clearErrors() {
    if (isLocked) return;
    hideAlert();
    var serverBanner = document.getElementById('login-alert-server');
    if (serverBanner) serverBanner.style.display = 'none';
    setFieldError(emailWrap, emailError, false);
    setFieldError(passwordWrap, passwordError, false);
    if (submitBtn) submitBtn.disabled = false;
  }

  if (emailInput) {
    emailInput.addEventListener('input', function () {
      if (isLocked) return;
      setFieldError(emailWrap, emailError, false);
      hideAlert();
      var serverBanner = document.getElementById('login-alert-server');
      if (serverBanner) serverBanner.style.display = 'none';
      if (submitBtn) submitBtn.disabled = false;
    });
  }
  if (passwordInput) {
    passwordInput.addEventListener('input', function () {
      if (isLocked) return;
      setFieldError(passwordWrap, passwordError, false);
      hideAlert();
      var serverBanner = document.getElementById('login-alert-server');
      if (serverBanner) serverBanner.style.display = 'none';
      if (submitBtn) submitBtn.disabled = false;
    });
  }

  if (!form) return;

  form.addEventListener('submit', function (e) {
    e.preventDefault();
    if (isLocked) {
      showAlert('', { locked: true });
      return;
    }
    clearErrors();

    var email = (emailInput.value || '').trim();
    var password = passwordInput.value || '';
    var ok = true;

    if (!EMAIL.test(email)) {
      setFieldError(emailWrap, emailError, true, 'Enter a valid email address');
      ok = false;
    }
    if (!password) {
      setFieldError(passwordWrap, passwordError, true, 'Enter your password');
      ok = false;
    }
    if (!ok) return;

    submitBtn.disabled = true;
    submitBtn.textContent = 'Signing in…';

    var body = new FormData(form);

    fetch(form.action, {
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
        markReturningUser();
        if (result.data.country_not_available && result.data.redirect) {
          window.location.href = result.data.redirect;
          return;
        }
        window.location.href = @json(authenticated_home_url());
        return;
      }

      if (result.status === 422) {
        var errors = result.data.errors || {};
        var emailMsg = errors.email && errors.email.length ? errors.email[0] : '';
        var locked = !!(errors.locked && errors.locked.length) || /too many tries/i.test(emailMsg);

        if (locked) {
          showAlert(emailMsg, { locked: true });
          return;
        }

        if (emailMsg && /don'?t match|credentials/i.test(emailMsg)) {
          showAlert(emailMsg);
        } else if (emailMsg) {
          setFieldError(emailWrap, emailError, true, emailMsg);
        }

        if (errors.password && errors.password.length) {
          setFieldError(passwordWrap, passwordError, true, errors.password[0]);
        }

        if (!emailMsg && !(errors.password && errors.password.length)) {
          showAlert(result.data.message || 'That email and password don\'t match. Check them and try again.');
        }
      } else if (result.status === 429) {
        showAlert('', { locked: true });
        return;
      } else {
        showAlert('Something went wrong while signing in. Please try again.');
      }

      if (isLocked) {
        setLocked(true);
        return;
      }

      submitBtn.disabled = false;
      submitBtn.innerHTML = originalBtnHtml;
    }).catch(function () {
      showAlert('Something went wrong while signing in. Please try again.');
      if (isLocked) {
        setLocked(true);
        return;
      }
      submitBtn.disabled = false;
      submitBtn.innerHTML = originalBtnHtml;
    });
  });
})();
</script>
@endpush
