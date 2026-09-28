@extends('layouts.new-auth')

@section('title', 'Verify Email | Bookpay by Inkjin')
@section('meta_description', 'Verify your Bookpay email to continue setting up bookings and payments.')
@section('robots', 'noindex, follow')
@section('og_title', 'Verify Email | Bookpay by Inkjin')
@section('og_description', 'Verify your Bookpay email to continue.')
@section('og_image')
{{ asset('design/images/bookpay-og.jpeg') }}
@endsection
@section('twitter_title', 'Verify Email | Bookpay by Inkjin')
@section('twitter_description', 'Verify your Bookpay email to continue.')
@section('twitter_image')
{{ asset('design/images/bookpay-og.jpeg') }}
@endsection

@section('help')
@endsection

@php
  $userEmail = auth()->user()?->email ?? '';
  $codeError = $errors->first('code');
  $sendError = $errors->first('email') ?: session('verification_send_error');
  $justSent = session('email_sent_on_registration')
    || session('status') === 'verification-code-sent'
    || session('status') === 'verification-link-sent';
@endphp

@push('styles')
<style>
  .code {
    letter-spacing: 10px;
    font-size: 22px !important;
    font-weight: 700;
    text-align: center;
  }
  .rs {
    display: flex;
    justify-content: center;
    gap: 6px;
    font-size: 13px;
    color: #6F6874;
    margin-top: 16px;
    flex-wrap: wrap;
    text-align: center;
  }
  .rs a,
  .rs button {
    color: #1A1A1A;
    font-weight: 700;
  }
  .banner[hidden] { display: none !important; }
</style>
@endpush

@section('content')
  <div class="icon"><span class="ms">mark_email_unread</span></div>
  <h1>Verify your email</h1>
  <p class="sub">
    We sent a 4-digit code to <b>{{ $userEmail }}</b>. Check your inbox and your spam folder.
  </p>

  <div class="banner ok" id="sent-banner" role="status" hidden>
    <span class="ms">check_circle</span>
    <span>New code sent.</span>
  </div>

  <div class="banner bad" id="bad-banner" role="alert" @if (! $codeError && ! $sendError) hidden @endif>
    <span class="ms">error</span>
    <span id="bad-banner-text">{{ $codeError ?: ($sendError ?: 'That code isn\'t right. Check the email and try again.') }}</span>
  </div>

  <form id="verify-form" method="POST" action="{{ route('verification.verify-code') }}" novalidate>
    @csrf
    <label class="fl" for="verification_code" style="margin-top:4px">4-digit code</label>
    <div class="in{{ $codeError ? ' bad' : '' }}" id="code-wrap">
      <input
        id="verification_code"
        class="code"
        name="code"
        type="text"
        inputmode="numeric"
        autocomplete="one-time-code"
        maxlength="4"
        aria-label="4-digit code"
        value="{{ old('code') }}"
      >
    </div>
    <button class="btn" id="verify-btn" type="submit" @if (strlen((string) old('code')) !== 4) disabled @endif>
      Verify email
    </button>
  </form>

  <div class="rs" id="resend-row"></div>

  <form id="resend-form" method="POST" action="{{ route('verification.send') }}" hidden>
    @csrf
  </form>

  <div class="alt">
    Wrong email?
    <form method="POST" action="{{ route('logout') }}" style="display:inline">
      @csrf
      <input type="hidden" name="to" value="register">
      <button type="submit" class="link">Change it</button>
    </form>
  </div>
@endsection

@push('scripts')
@include('partials.reddit-pixel', ['event' => 'SignUp'])
<script>
(function () {
  var COOLDOWN = 60;
  var STORAGE_KEY = 'email_verification_cooldown';
  var form = document.getElementById('verify-form');
  var resendForm = document.getElementById('resend-form');
  var codeInput = document.getElementById('verification_code');
  var codeWrap = document.getElementById('code-wrap');
  var verifyBtn = document.getElementById('verify-btn');
  var resendRow = document.getElementById('resend-row');
  var sentBanner = document.getElementById('sent-banner');
  var badBanner = document.getElementById('bad-banner');
  var badBannerText = document.getElementById('bad-banner-text');
  var originalBtnHtml = verifyBtn ? verifyBtn.innerHTML : 'Verify email';
  var timer = null;
  var remaining = 0;
  var isResending = false;
  var justSent = @json((bool) $justSent);
  var clearRegistrationFlagUrl = @json(route('verification.clear-registration-flag'));

  if (!form || !codeInput || !verifyBtn || !resendRow || !resendForm) return;

  function showSent() {
    sentBanner.hidden = false;
    badBanner.hidden = true;
  }

  function showBad(message) {
    badBanner.hidden = false;
    sentBanner.hidden = true;
    if (message) badBannerText.textContent = message;
  }

  function hideBanners() {
    sentBanner.hidden = true;
    badBanner.hidden = true;
  }

  function csrfToken() {
    var el = form.querySelector('input[name="_token"]') || resendForm.querySelector('input[name="_token"]');
    return el ? el.value : '';
  }

  function renderResend() {
    if (remaining > 0) {
      var secs = remaining % 60;
      var mins = Math.floor(remaining / 60);
      resendRow.innerHTML = 'Send a new code in ' + mins + ':' + ('0' + secs).slice(-2);
      return;
    }

    resendRow.innerHTML = 'Didn\u2019t get it? <button type="button" class="link" id="resend-btn">Send a new code</button>';
    var btn = document.getElementById('resend-btn');
    if (btn) btn.addEventListener('click', sendNewCode);
  }

  function startCooldown(seconds, startedAt) {
    remaining = Math.max(0, seconds | 0);
    try {
      localStorage.setItem(STORAGE_KEY, JSON.stringify({
        timestamp: startedAt || Date.now(),
        duration: COOLDOWN
      }));
    } catch (e) { /* ignore */ }

    if (timer) clearInterval(timer);
    renderResend();
    timer = setInterval(function () {
      remaining -= 1;
      if (remaining <= 0) {
        clearInterval(timer);
        timer = null;
        remaining = 0;
        try { localStorage.removeItem(STORAGE_KEY); } catch (e) { /* ignore */ }
      }
      renderResend();
    }, 1000);
  }

  function restoreCooldown() {
    try {
      var raw = localStorage.getItem(STORAGE_KEY);
      if (!raw) return false;
      var data = JSON.parse(raw);
      var elapsed = Math.floor((Date.now() - data.timestamp) / 1000);
      var left = COOLDOWN - elapsed;
      if (left > 0) {
        startCooldown(left, data.timestamp);
        return true;
      }
      localStorage.removeItem(STORAGE_KEY);
    } catch (e) { /* ignore */ }
    return false;
  }

  function clearRegistrationFlag() {
    @if (session('email_sent_on_registration'))
      fetch(clearRegistrationFlagUrl, {
        method: 'POST',
        headers: {
          'X-CSRF-TOKEN': csrfToken(),
          'Content-Type': 'application/json',
          'Accept': 'application/json'
        },
        body: JSON.stringify({}),
        credentials: 'same-origin'
      }).catch(function () {});
    @endif
  }

  function sendNewCode() {
    if (isResending || remaining > 0) return;
    isResending = true;
    resendRow.innerHTML = 'Sending…';

    var body = new FormData(resendForm);

    fetch(resendForm.action, {
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
      isResending = false;
      if (result.status >= 200 && result.status < 300) {
        showSent();
        codeWrap.classList.remove('bad');
        startCooldown(COOLDOWN);
        return;
      }
      if (result.status === 429) {
        showBad('Too many requests. Please wait and try again.');
      } else {
        showBad((result.data && result.data.message) || 'Unable to resend the code right now. Please try again.');
      }
      renderResend();
    }).catch(function () {
      isResending = false;
      showBad('Network error. Please check your connection and try again.');
      renderResend();
    });
  }

  codeInput.addEventListener('input', function () {
    codeInput.value = codeInput.value.replace(/\D/g, '').slice(0, 4);
    verifyBtn.disabled = codeInput.value.length !== 4;
    codeWrap.classList.remove('bad');
    if (!badBanner.hidden && badBannerText.textContent.indexOf('code') !== -1) {
      badBanner.hidden = true;
    }
    if (codeInput.value.length === 4) {
      form.requestSubmit ? form.requestSubmit() : form.submit();
    }
  });

  form.addEventListener('submit', function () {
    if (codeInput.value.length !== 4) return;
    verifyBtn.disabled = true;
    verifyBtn.textContent = 'Verifying…';
  });

  if (restoreCooldown()) {
    // keep existing timer
  } else if (justSent) {
    startCooldown(COOLDOWN);
    clearRegistrationFlag();
  } else {
    remaining = 0;
    renderResend();
    sendNewCode();
  }

  window.addEventListener('beforeunload', function () {
    if (timer) clearInterval(timer);
  });
})();
</script>
@endpush
