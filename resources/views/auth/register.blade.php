@extends('layouts.new-auth')

@section('title', 'Sign Up | Bookpay by Inkjin')
@section('meta_description', 'Create your Bookpay account. Bookings and payments built for tattoo artists and studios.')
@section('canonical', 'https://bookpay.inkjin.com/register')
@section('robots', 'noindex, follow')
@section('og_title', 'Sign Up | Bookpay by Inkjin')
@section('og_description', 'Create your Bookpay account for bookings and payments.')
@section('og_url', 'https://bookpay.inkjin.com/register')
@section('og_image')
{{ asset('design/images/bookpay-og.jpeg') }}
@endsection
@section('twitter_title', 'Sign Up | Bookpay by Inkjin')
@section('twitter_description', 'Create your Bookpay account for bookings and payments.')
@section('twitter_image')
{{ asset('design/images/bookpay-og.jpeg') }}
@endsection

@section('help')
@endsection

@php
  $resolvedReferrerUserId = $referrerUserId ?? session('artist_referral_referrer_user_id');
  $initialRole = old('role', request()->query('as') === 'studio' ? 'studio' : 'artist');
  if (! in_array($initialRole, ['artist', 'studio'], true)) {
      $initialRole = 'artist';
  }
  $referralOptions = [
      'instagram' => 'Instagram',
      'friend' => 'Friend / Referral',
      'inkjin_team' => 'Inkjin team',
      'google' => 'Google / Search',
      'ai' => 'AI (ChatGPT, Claude, Gemini, etc.)',
      'convention' => 'Tattoo Convention',
      'blog' => 'Blog / Article',
      'other' => 'Other',
  ];
  $oldReferral = old('referral_source', '');
  $sideCopy = [
      'artist' => [
          'title' => 'Booking and payments for tattoo artists.',
          'sub' => 'Manage your bookings, collect payments and get the right details from clients, without the back-and-forth.',
          'pills' => ['100% free for artists', 'No fees or monthly subscriptions', 'Automatic payouts', 'Real-time reporting'],
      ],
      'studio' => [
          'title' => 'Manage your tattoo studio on one platform.',
          'sub' => 'Invite artists, set commission splits, view unified bookings and earnings, and let Stripe handle the payout split automatically.',
          'pills' => ['100% free for studios', 'Invite artists', 'Revenue split with artists', 'Automated payouts', 'Manage bookings'],
      ],
  ];
  $side = $sideCopy[$initialRole];
@endphp

@section('auth_side')
<aside class="sp" aria-label="About Bookpay">
  <a class="logo" href="{{ url('/') }}" title="Bookpay by Inkjin">
    <b>bookpay</b>
    <span>FOR TATTOO ARTISTS AND STUDIOS<br>BY INKJIN</span>
  </a>
  <div class="sp-main">
    <h2 id="sp-t">{{ $side['title'] }}</h2>
    <p id="sp-s">{{ $side['sub'] }}</p>
    <ul class="sp-pills" id="sp-p">
      @foreach ($side['pills'] as $pill)
        <li>✓ {{ $pill }}</li>
      @endforeach
    </ul>
    <a class="sp-btn" href="https://inkjin.com/bookpay" target="_blank" rel="noopener">
      Learn more about Bookpay<span class="ms">arrow_forward</span>
    </a>
  </div>
</aside>
@endsection

@push('styles')
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet">
<style>
  .roles { display: grid; grid-template-columns: 1fr 1fr; gap: 10px; }
  .role {
    display: flex; gap: 10px; align-items: flex-start;
    border: 1.5px solid #E8DFEA; border-radius: 12px; padding: 12px 13px;
    cursor: pointer; background: #fff; text-align: left; font: inherit; color: inherit;
  }
  .role .ms { color: #9A929E; font-size: 20px; flex: none; margin-top: 1px; }
  .role b { display: block; font-size: 14px; }
  .role small { display: block; color: #6F6874; font-size: 12.5px; line-height: 1.4; margin-top: 2px; }
  .role[aria-checked="true"] { border-color: #3E007C; background: #FBF7FE; }
  .role[aria-checked="true"] .ms { color: #3E007C; }
  .hint { font-size: 12.5px; color: #6F6874; margin-top: 6px; line-height: 1.45; }
  .ck { align-items: flex-start; }
  .ck input { margin-top: 2px; }

  .select-wrap { width: 100%; }
  .select-wrap .select2-container { width: 100% !important; font: inherit; }
  .select2-container--open { z-index: 10060 !important; }
  .select-wrap .select2-container--default .select2-selection--single {
    height: 46px;
    border: 1px solid #DCD2E0 !important;
    border-radius: 10px !important;
    background: #fff !important;
    outline: 0;
  }
  .select-wrap .select2-container--default .select2-selection--single .select2-selection__rendered {
    line-height: 44px;
    padding-left: 14px;
    padding-right: 32px;
    color: #1A1A1A;
    font-size: 14.5px;
  }
  .select-wrap .select2-container--default .select2-selection--single .select2-selection__placeholder {
    color: #9A929E;
  }
  .select-wrap .select2-container--default .select2-selection--single .select2-selection__arrow {
    height: 44px;
    right: 8px;
  }
  .select-wrap .select2-container--default .select2-selection--single .select2-selection__arrow b {
    border-color: #9A929E transparent transparent transparent;
  }
  .select-wrap .select2-container--default.select2-container--focus .select2-selection--single,
  .select-wrap .select2-container--default.select2-container--open .select2-selection--single {
    border-color: #3E007C !important;
    box-shadow: 0 0 0 3px #EEE3FA;
  }
  .select-wrap.bad .select2-container--default .select2-selection--single,
  .select-wrap.bad .select2-container--default.select2-container--focus .select2-selection--single {
    border-color: #C62828 !important;
    box-shadow: none;
  }
  .select2-dropdown {
    border: 1px solid #E8DFEA !important;
    border-radius: 12px !important;
    box-shadow: 0 12px 30px rgba(30, 15, 45, .14);
    overflow: hidden;
    font-family: 'Plus Jakarta Sans', system-ui, sans-serif;
    font-size: 14px;
    color: #1A1A1A;
  }
  .select2-container--default .select2-results__option { padding: 10px 12px; }
  .select2-container--default .select2-results__option--highlighted.select2-results__option--selectable {
    background-color: #F3E8FF !important;
    color: #1A1A1A !important;
  }
  .select2-container--default .select2-results__option--selected {
    background-color: #3E007C !important;
    color: #fff !important;
  }
  .select2-container--default .select2-search--dropdown .select2-search__field {
    border: 1px solid #DCD2E0 !important;
    border-radius: 8px !important;
    padding: 9px 11px;
    font: inherit;
    outline: 0;
  }
  .select2-container--default .select2-search--dropdown .select2-search__field:focus {
    border-color: #3E007C !important;
  }

  .cna {
    position: fixed; inset: 0; background: rgba(26, 16, 32, .55);
    display: flex; align-items: center; justify-content: center;
    padding: 16px; z-index: 220;
  }
  .cna[hidden] { display: none !important; }
  .cna-b {
    background: #fff; border-radius: 18px; max-width: 420px; width: 100%;
    padding: 28px; box-shadow: 0 20px 60px rgba(0, 0, 0, .25);
  }
  .cna-i {
    width: 56px; height: 56px; border-radius: 16px; background: #F3E8FF;
    border: 1px solid #E2CFFA; color: #3E007C;
    display: flex; align-items: center; justify-content: center; margin-bottom: 18px;
  }
  .cna-i .ms { font-size: 28px; }
  .cna h2 { font-size: 20px; margin: 0 0 10px; letter-spacing: -.3px; }
  .cna p { margin: 0; color: #6F6874; font-size: 14px; line-height: 1.55; }

  @media (max-width: 480px) {
    .roles { grid-template-columns: 1fr; }
  }
</style>
@endpush

@section('content')
  <h1>Create your account</h1>
  <p class="sub">One account for your bookings and payments.</p>

  <div id="register-alert" class="banner bad" role="alert" style="display:none"></div>

  <form id="register-form" action="{{ route('register.store') }}" method="POST" novalidate>
    @csrf
    @if ($resolvedReferrerUserId)
      <input type="hidden" name="referrer_user_id" value="{{ $resolvedReferrerUserId }}">
    @endif
    <input type="hidden" name="role" id="role-input" value="{{ $initialRole }}">

    <div class="fl" style="margin-top:0">I'm signing up as</div>
    <div class="roles" role="radiogroup" aria-label="Account type">
      <button type="button" class="role" data-role="artist" role="radio" aria-checked="{{ $initialRole === 'artist' ? 'true' : 'false' }}">
        <span class="ms">{{ $initialRole === 'artist' ? 'radio_button_checked' : 'radio_button_unchecked' }}</span>
        <span><b>An artist</b><small>I tattoo and take my own bookings.</small></span>
      </button>
      <button type="button" class="role" data-role="studio" role="radio" aria-checked="{{ $initialRole === 'studio' ? 'true' : 'false' }}">
        <span class="ms">{{ $initialRole === 'studio' ? 'radio_button_checked' : 'radio_button_unchecked' }}</span>
        <span><b>A studio</b><small>I run a studio with one or more artists.</small></span>
      </button>
    </div>
    <div class="err" id="role-error">Please choose how you are signing up</div>

    <label class="fl" for="signup-email">Email address</label>
    <div class="in" id="email-wrap">
      <input id="signup-email" name="email" type="email" autocomplete="email" placeholder="you@example.com" value="{{ old('email') }}">
    </div>
    <div class="err" id="email-error">Enter a valid email address</div>

    <label class="fl" for="signup-password">Password</label>
    <div class="in" id="password-wrap">
      <input id="signup-password" name="password" type="password" autocomplete="new-password">
      <button type="button" class="eye" aria-label="Show password"><span class="ms">visibility</span></button>
    </div>
    <div class="hint">At least 8 characters.</div>
    <div class="err" id="password-error">Use at least 8 characters</div>

    <label class="fl" for="signup-password-confirmation">Confirm password</label>
    <div class="in" id="password-confirm-wrap">
      <input id="signup-password-confirmation" name="password_confirmation" type="password" autocomplete="new-password">
      <button type="button" class="eye" aria-label="Show password"><span class="ms">visibility</span></button>
    </div>
    <div class="err" id="password-confirm-error">Passwords don't match</div>

    <label class="fl" for="payout_bank_country">Where are you based?</label>
    <div class="select-wrap" id="country-wrap">
      <select class="js-select2" id="payout_bank_country" name="payout_bank_country" data-placeholder="Select country">
        <option value=""></option>
        @foreach ($registrationCountries as $country)
          <option value="{{ $country['code'] }}" @selected(old('payout_bank_country') === $country['code'])>{{ $country['name'] }}</option>
        @endforeach
        <option disabled>──────────</option>
        <option value="__not_listed__" @selected(old('payout_bank_country') === '__not_listed__')">My country is not listed here</option>
      </select>
    </div>
    <div class="err" id="country-error">Choose where you are based</div>

    <div id="unlisted-country-wrap" style="{{ old('payout_bank_country') === '__not_listed__' ? '' : 'display:none' }}">
      <label class="fl" for="unlisted_country">Select your country</label>
      <div class="select-wrap" id="unlisted-wrap">
        <select class="js-select2" id="unlisted_country" name="unlisted_country" data-placeholder="Select country">
          <option value=""></option>
          @foreach ($unlistedCountries as $countryName)
            <option value="{{ $countryName }}" @selected(old('unlisted_country') === $countryName)>{{ $countryName }}</option>
          @endforeach
        </select>
      </div>
      <div class="err" id="unlisted-error">Choose your country</div>
    </div>
    <div class="hint">Sets your country, currency and time zone. You can change them later.</div>

    <label class="fl" for="referral_source" style="justify-content:flex-start;gap:4px">
      How did you hear about us? <span style="color:#9A929E;font-weight:400">(optional)</span>
    </label>
    <div class="select-wrap" id="referral-wrap">
      <select class="js-select2" id="referral_source" name="referral_source" data-placeholder="Select...">
        <option value=""></option>
        @foreach ($referralOptions as $value => $label)
          <option value="{{ $value }}" @selected($oldReferral === $value)>{{ $label }}</option>
        @endforeach
      </select>
    </div>

    <label class="ck">
      <input type="checkbox" id="terms" name="terms" value="1" {{ old('terms') ? 'checked' : '' }}>
      <span>I agree to the <a href="https://inkjin.com/en/artist-terms" target="_blank" rel="noopener noreferrer" style="color:inherit;font-weight:700">Terms of Use</a> and <a href="https://inkjin.com/en/privacy" target="_blank" rel="noopener noreferrer" style="color:inherit;font-weight:700">Privacy Policy</a></span>
    </label>
    <div class="err" id="terms-error">Tick the box to continue</div>

    <button class="btn" type="submit" id="signup-submit">Create account<span class="ms">arrow_forward</span></button>
  </form>

  <div class="alt">
    Already have an account?
    <a href="{{ route('login') }}">Sign in</a>
  </div>
@endsection

@push('scripts')
@include('partials.reddit-pixel', ['event' => 'PageVisit'])
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script>
(function () {
  var EMAIL = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
  var form = document.getElementById('register-form');
  if (!form) return;

  var $ = window.jQuery;
  var roleInput = document.getElementById('role-input');
  var emailInput = document.getElementById('signup-email');
  var passwordInput = document.getElementById('signup-password');
  var passwordConfirmInput = document.getElementById('signup-password-confirmation');
  var countrySelect = document.getElementById('payout_bank_country');
  var unlistedSelect = document.getElementById('unlisted_country');
  var unlistedWrap = document.getElementById('unlisted-country-wrap');
  var termsInput = document.getElementById('terms');
  var alertEl = document.getElementById('register-alert');
  var submitBtn = document.getElementById('signup-submit');
  var originalBtnHtml = submitBtn.innerHTML;
  var registerPageUrl = @json(route('register'));
  var sideCopy = @json($sideCopy);

  if ($ && $.fn.select2) {
    $('.js-select2').each(function () {
      var $el = $(this);
      $el.select2({
        width: '100%',
        dropdownParent: $(document.body),
        placeholder: $el.data('placeholder') || 'Select...',
        allowClear: false
      });
    });
  }

  function updateSidePanel(role) {
    var data = sideCopy[role] || sideCopy.artist;
    var titleEl = document.getElementById('sp-t');
    var subEl = document.getElementById('sp-s');
    var pillsEl = document.getElementById('sp-p');
    if (!titleEl || !subEl || !pillsEl) return;
    titleEl.textContent = data.title;
    subEl.textContent = data.sub;
    pillsEl.innerHTML = data.pills.map(function (x) {
      return '<li>✓ ' + x + '</li>';
    }).join('');
  }

  function setRole(role) {
    roleInput.value = role;
    document.querySelectorAll('.role').forEach(function (btn) {
      var on = btn.dataset.role === role;
      btn.setAttribute('aria-checked', on ? 'true' : 'false');
      var icon = btn.querySelector('.ms');
      if (icon) icon.textContent = on ? 'radio_button_checked' : 'radio_button_unchecked';
    });
    document.getElementById('role-error').classList.remove('on');
    updateSidePanel(role);
  }

  document.querySelectorAll('.role').forEach(function (btn) {
    btn.addEventListener('click', function () { setRole(btn.dataset.role); });
  });

  function toggleUnlisted() {
    var show = countrySelect.value === '__not_listed__';
    unlistedWrap.style.display = show ? '' : 'none';
    if (!show && $) {
      $(unlistedSelect).val(null).trigger('change');
    } else if (!show) {
      unlistedSelect.value = '';
    }
  }

  function showAlert(message) {
    alertEl.style.display = 'flex';
    alertEl.innerHTML = '<span class="ms">error</span><span></span>';
    alertEl.querySelector('span:last-child').textContent = message;
  }

  function hideAlert() {
    alertEl.style.display = 'none';
    alertEl.innerHTML = '';
  }

  function setErr(wrapId, errId, on, message) {
    var wrap = wrapId ? document.getElementById(wrapId) : null;
    var err = document.getElementById(errId);
    if (wrap) wrap.classList.toggle('bad', on);
    if (err) {
      err.classList.toggle('on', on);
      if (on && message) err.textContent = message;
    }
  }

  function clearErrors() {
    hideAlert();
    setErr('email-wrap', 'email-error', false);
    setErr('password-wrap', 'password-error', false);
    setErr('password-confirm-wrap', 'password-confirm-error', false);
    setErr('country-wrap', 'country-error', false);
    setErr('unlisted-wrap', 'unlisted-error', false);
    setErr(null, 'terms-error', false);
    setErr(null, 'role-error', false);
  }

  if ($) {
    $(countrySelect).on('change', function () {
      toggleUnlisted();
      clearErrors();
    });
    $(unlistedSelect).on('change', clearErrors);
  } else {
    countrySelect.addEventListener('change', function () {
      toggleUnlisted();
      clearErrors();
    });
    unlistedSelect.addEventListener('change', clearErrors);
  }
  toggleUnlisted();

  [emailInput, passwordInput, passwordConfirmInput].forEach(function (el) {
    el.addEventListener('input', clearErrors);
  });
  termsInput.addEventListener('change', function () {
    setErr(null, 'terms-error', false);
  });

  var modal = document.getElementById('country-not-available-modal');
  function showCountryModal() {
    if (modal) modal.hidden = false;
  }
  var countryOk = document.getElementById('country-not-available-ok');
  if (countryOk) {
    countryOk.addEventListener('click', function () {
      if (modal) modal.hidden = true;
      submitBtn.disabled = false;
      submitBtn.innerHTML = originalBtnHtml;
    });
  }
  document.addEventListener('keydown', function (e) {
    if (e.key === 'Escape' && modal && !modal.hidden) countryOk.click();
  });

  @if (session('country_not_available'))
    showCountryModal();
  @endif

  form.addEventListener('submit', function (e) {
    e.preventDefault();
    clearErrors();

    var role = roleInput.value;
    var email = (emailInput.value || '').trim();
    var password = passwordInput.value || '';
    var passwordConfirm = passwordConfirmInput.value || '';
    var country = countrySelect.value;
    var ok = true;

    if (role !== 'artist' && role !== 'studio') {
      setErr(null, 'role-error', true);
      ok = false;
    }
    if (!EMAIL.test(email)) {
      setErr('email-wrap', 'email-error', true, 'Enter a valid email address');
      ok = false;
    }
    if (password.length < 8) {
      setErr('password-wrap', 'password-error', true, 'Use at least 8 characters');
      ok = false;
    }
    if (password.length >= 8 && password !== passwordConfirm) {
      setErr('password-confirm-wrap', 'password-confirm-error', true, 'Passwords don\'t match');
      ok = false;
    }
    if (!country) {
      setErr('country-wrap', 'country-error', true, 'Choose where you are based');
      ok = false;
    }
    if (country === '__not_listed__' && !unlistedSelect.value) {
      setErr('unlisted-wrap', 'unlisted-error', true, 'Choose your country');
      ok = false;
    }
    if (!termsInput.checked) {
      setErr(null, 'terms-error', true, 'Tick the box to continue');
      ok = false;
    }
    if (!ok) return;

    submitBtn.disabled = true;
    submitBtn.textContent = 'Creating your account…';

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
        return { status: res.status, data: data || {}, url: res.url };
      }).catch(function () {
        return { status: res.status, data: {}, url: res.url };
      });
    }).then(function (result) {
      if (result.status >= 200 && result.status < 300) {
        if (result.data.country_not_available) {
          showCountryModal();
          return;
        }
        window.location.href = @json(route('verification.notice'));
        return;
      }

      if (result.status === 429) {
        showAlert('Too many signup attempts. Please wait a bit and try again.');
      } else if (result.status === 422) {
        var errors = result.data.errors || {};
        if (errors.role) setErr(null, 'role-error', true, errors.role[0]);
        if (errors.email) setErr('email-wrap', 'email-error', true, errors.email[0]);
        if (errors.password) setErr('password-wrap', 'password-error', true, errors.password[0]);
        if (errors.password_confirmation) setErr('password-confirm-wrap', 'password-confirm-error', true, errors.password_confirmation[0]);
        if (errors.payout_bank_country) setErr('country-wrap', 'country-error', true, errors.payout_bank_country[0]);
        if (errors.unlisted_country) setErr('unlisted-wrap', 'unlisted-error', true, errors.unlisted_country[0]);
        if (errors.terms) setErr(null, 'terms-error', true, errors.terms[0]);
        if (!errors.role && !errors.email && !errors.password && !errors.password_confirmation && !errors.payout_bank_country && !errors.unlisted_country && !errors.terms) {
          showAlert(result.data.message || 'Registration failed. Please check your details.');
        }
      } else {
        showAlert('Something went wrong while signing up. Please try again.');
      }

      submitBtn.disabled = false;
      submitBtn.innerHTML = originalBtnHtml;
    }).catch(function () {
      showAlert('Something went wrong while signing up. Please try again.');
      submitBtn.disabled = false;
      submitBtn.innerHTML = originalBtnHtml;
    });
  });
})();
</script>

<div id="country-not-available-modal" class="cna" hidden role="dialog" aria-modal="true" aria-labelledby="country-na-title">
  <div class="cna-b">
    <div class="cna-i"><span class="ms">public_off</span></div>
    <h2 id="country-na-title">Bookpay isn't in your country yet</h2>
    <p>We're expanding country by country. We'll email you the moment we launch in your area. No need to do anything else right now.</p>
    <button type="button" class="btn" id="country-not-available-ok" style="margin-top:18px">OK</button>
  </div>
</div>
@endpush
