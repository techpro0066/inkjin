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
  $oldCountry = old('payout_bank_country', '');
  $oldUnlisted = old('unlisted_country', '');
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

  .cs { position: relative; }
  .cs[hidden] { display: none !important; }
  .cs-b {
    display: flex; align-items: center; width: 100%; text-align: left; cursor: pointer;
    font: inherit; font-size: 14.5px; background: #fff; border: 1px solid #DCD2E0;
    border-radius: 10px; padding: 12px 14px; color: inherit;
  }
  .cs-b:focus-visible,
  .cs-b[aria-expanded="true"] {
    border-color: #3E007C; box-shadow: 0 0 0 3px #EEE3FA; outline: 0;
  }
  .cs.bad .cs-b { border-color: #C62828; }
  .cs-ph { color: #9A929E; }
  .cs-p {
    position: absolute; left: 0; right: 0; top: 100%; margin-top: 4px; background: #fff;
    border: 1px solid #E8DFEA; border-radius: 12px;
    box-shadow: 0 12px 30px rgba(30, 15, 45, .14); z-index: 30; padding: 8px;
  }
  .cs-p[hidden] { display: none; }
  .cs-q {
    width: 100%; margin-bottom: 6px; border: 1px solid #DCD2E0; border-radius: 8px;
    font: inherit; padding: 9px 11px; outline: 0; box-sizing: border-box;
  }
  .cs-q:focus { border-color: #3E007C; }
  .cs-l { max-height: 240px; overflow: auto; }
  .cs-o { padding: 10px 12px; border-radius: 8px; font-size: 14px; cursor: pointer; }
  .cs-o:hover, .cs-o.kb { background: #F3E8FF; }
  .cs-o.sel { background: #3E007C; color: #fff; }
  .cs-o.nl { font-weight: 600; color: #3E007C; }
  .cs-o.nl.sel { color: #fff; }
  .cs-sep { border-top: 1px solid #EDE6F0; margin: 6px 4px; }
  .cs-none { padding: 10px 12px; font-size: 13px; color: #9A929E; }

  .hs { position: relative; }
  .hs-b {
    display: flex; align-items: center; width: 100%; text-align: left; cursor: pointer;
    font: inherit; font-size: 14.5px; background: #fff; border: 1px solid #DCD2E0;
    border-radius: 10px; padding: 12px 14px; color: inherit;
  }
  .hs-b:focus-visible,
  .hs-b[aria-expanded="true"] {
    border-color: #3E007C; box-shadow: 0 0 0 3px #EEE3FA; outline: 0;
  }
  .hs-ph { color: #9A929E; }
  .hs-p {
    position: absolute; left: 0; right: 0; top: 100%; margin-top: 4px; background: #fff;
    border: 1px solid #E8DFEA; border-radius: 12px;
    box-shadow: 0 12px 30px rgba(30, 15, 45, .14); z-index: 30; padding: 8px;
  }
  .hs-p[hidden] { display: none; }
  .hs-q {
    width: 100%; margin-bottom: 6px; border: 1px solid #DCD2E0; border-radius: 8px;
    font: inherit; padding: 9px 11px; outline: 0; box-sizing: border-box;
  }
  .hs-q:focus { border-color: #3E007C; }
  .hs-l { max-height: 220px; overflow: auto; }
  .hs-o { padding: 10px 12px; border-radius: 8px; font-size: 14px; cursor: pointer; }
  .hs-o:hover { background: #F3E8FF; }
  .hs-o.sel { background: #3E007C; color: #fff; }
  .hs-none { padding: 10px 12px; font-size: 13px; color: #9A929E; }

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
    <input type="hidden" name="payout_bank_country" id="payout_bank_country" value="{{ $oldCountry }}">
    <input type="hidden" name="unlisted_country" id="unlisted_country" value="{{ $oldUnlisted }}">
    <input type="hidden" name="referral_source" id="referral_source" value="{{ $oldReferral }}">

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

    <div class="cs" id="cs1">
      <label class="fl" id="cs1-l">Where are you based?</label>
      <button type="button" class="cs-b" aria-haspopup="listbox" aria-expanded="false" aria-labelledby="cs1-l">
        <span class="cs-v cs-ph">Select country</span>
        <span class="ms" style="margin-left:auto;color:#9A929E">expand_more</span>
      </button>
      <div class="cs-p" hidden>
        <input class="cs-q" type="text" placeholder="Search" aria-label="Search countries" autocomplete="off">
        <div class="cs-l" role="listbox"></div>
      </div>
    </div>
    <div class="err" id="country-error">Choose where you are based</div>

    <div class="cs" id="cs2" hidden>
      <label class="fl" id="cs2-l">Select your country</label>
      <button type="button" class="cs-b" aria-haspopup="listbox" aria-expanded="false" aria-labelledby="cs2-l">
        <span class="cs-v cs-ph">Select country</span>
        <span class="ms" style="margin-left:auto;color:#9A929E">expand_more</span>
      </button>
      <div class="cs-p" hidden>
        <input class="cs-q" type="text" placeholder="Search" aria-label="Search countries" autocomplete="off">
        <div class="cs-l" role="listbox"></div>
      </div>
    </div>
    <div class="err" id="unlisted-error">Choose your country</div>
    <div class="hint">Sets your country, currency and time zone. You can change them later.</div>

    <div class="hs">
      <label class="fl" id="hs-l" style="justify-content:flex-start;gap:4px">
        How did you hear about us? <span style="color:#9A929E;font-weight:400">(optional)</span>
      </label>
      <button type="button" class="hs-b" id="hs-b" aria-haspopup="listbox" aria-expanded="false" aria-labelledby="hs-l">
        <span id="hs-v" class="{{ $oldReferral !== '' && isset($referralOptions[$oldReferral]) ? '' : 'hs-ph' }}">
          {{ ($oldReferral !== '' && isset($referralOptions[$oldReferral])) ? $referralOptions[$oldReferral] : 'Select...' }}
        </span>
        <span class="ms" style="margin-left:auto;color:#9A929E">expand_more</span>
      </button>
      <div class="hs-p" id="hs-p" hidden>
        <input class="hs-q" id="hs-q" type="text" placeholder="Search" aria-label="Search options" autocomplete="off">
        <div class="hs-l" role="listbox">
          @foreach ($referralOptions as $value => $label)
            <div
              class="hs-o{{ $oldReferral === $value ? ' sel' : '' }}"
              role="option"
              tabindex="-1"
              data-value="{{ $value }}"
              aria-selected="{{ $oldReferral === $value ? 'true' : 'false' }}"
            >{{ $label }}</div>
          @endforeach
          <div class="hs-none" hidden>No matches</div>
        </div>
      </div>
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
<script>
(function () {
  var EMAIL = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
  var NL = '__not_listed__';
  var NL_LABEL = 'My country is not listed here';
  var form = document.getElementById('register-form');
  if (!form) return;

  var roleInput = document.getElementById('role-input');
  var emailInput = document.getElementById('signup-email');
  var passwordInput = document.getElementById('signup-password');
  var passwordConfirmInput = document.getElementById('signup-password-confirmation');
  var countryInput = document.getElementById('payout_bank_country');
  var unlistedInput = document.getElementById('unlisted_country');
  var referralInput = document.getElementById('referral_source');
  var termsInput = document.getElementById('terms');
  var alertEl = document.getElementById('register-alert');
  var submitBtn = document.getElementById('signup-submit');
  var originalBtnHtml = submitBtn.innerHTML;
  var sideCopy = @json($sideCopy);
  var mainCountries = @json($registrationCountries);
  var otherCountries = @json(array_values($unlistedCountries));
  var oldCountry = @json($oldCountry);
  var oldUnlisted = @json($oldUnlisted);

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
    if (wrap) wrap.classList.toggle('bad', !!on);
    if (err) {
      err.classList.toggle('on', !!on);
      if (on && message) err.textContent = message;
    }
  }

  function clearErrors() {
    hideAlert();
    setErr('email-wrap', 'email-error', false);
    setErr('password-wrap', 'password-error', false);
    setErr('password-confirm-wrap', 'password-confirm-error', false);
    setErr('cs1', 'country-error', false);
    setErr('cs2', 'unlisted-error', false);
    setErr(null, 'terms-error', false);
    setErr(null, 'role-error', false);
  }

  [emailInput, passwordInput, passwordConfirmInput].forEach(function (el) {
    el.addEventListener('input', clearErrors);
  });
  termsInput.addEventListener('change', function () {
    setErr(null, 'terms-error', false);
  });

  /* ---------- Custom country pickers ---------- */
  function makePicker(root, items, onPick) {
    var b = root.querySelector('.cs-b');
    var p = root.querySelector('.cs-p');
    var q = root.querySelector('.cs-q');
    var l = root.querySelector('.cs-l');
    var v = root.querySelector('.cs-v');
    var val = '';
    var kb = -1;

    l.innerHTML = items.map(function (item) {
      if (item === '-') return '<div class="cs-sep" role="separator"></div>';
      var value = typeof item === 'string' ? item : item.value;
      var label = typeof item === 'string' ? item : item.label;
      var nl = value === NL;
      return '<div class="cs-o' + (nl ? ' nl' : '') + '" role="option" tabindex="-1" data-v="' +
        String(value).replace(/"/g, '&quot;') + '">' + label + '</div>';
    }).join('') + '<div class="cs-none" hidden>No matches</div>';

    var os = [].slice.call(l.querySelectorAll('.cs-o'));
    var seps = [].slice.call(l.querySelectorAll('.cs-sep'));
    var none = l.querySelector('.cs-none');

    function vis() { return os.filter(function (o) { return !o.hidden; }); }
    function hl(i) {
      var vs = vis();
      os.forEach(function (o) { o.classList.remove('kb'); });
      kb = Math.max(0, Math.min(i, vs.length - 1));
      if (vs[kb]) {
        vs[kb].classList.add('kb');
        vs[kb].scrollIntoView({ block: 'nearest' });
      }
    }
    function filt() {
      var t = q.value.trim().toLowerCase();
      var n = 0;
      os.forEach(function (o) {
        var s = o.textContent.toLowerCase().indexOf(t) > -1 || (t && o.classList.contains('nl'));
        o.hidden = !s;
        if (s) n++;
      });
      seps.forEach(function (x) { x.hidden = !!t; });
      none.hidden = n > 0;
      kb = -1;
    }
    function open() {
      document.querySelectorAll('.cs-p, .hs-p').forEach(function (x) {
        if (x !== p) x.hidden = true;
      });
      p.hidden = false;
      b.setAttribute('aria-expanded', 'true');
      q.value = '';
      filt();
      var s = l.querySelector('.cs-o.sel');
      if (s) s.scrollIntoView({ block: 'nearest' });
      setTimeout(function () { q.focus(); }, 0);
    }
    function close() {
      p.hidden = true;
      b.setAttribute('aria-expanded', 'false');
    }
    function set(t, silent) {
      val = t || '';
      os.forEach(function (x) {
        var on = x.dataset.v === val;
        x.classList.toggle('sel', on);
        x.setAttribute('aria-selected', on ? 'true' : 'false');
      });
      var selected = os.find(function (x) { return x.dataset.v === val; });
      v.textContent = selected ? selected.textContent : 'Select country';
      v.classList.toggle('cs-ph', !val);
      root.classList.remove('bad');
      if (!silent) onPick(val);
    }

    b.addEventListener('click', function (e) {
      e.stopPropagation();
      p.hidden ? open() : close();
    });
    b.addEventListener('keydown', function (e) {
      if (e.key === 'ArrowDown' || e.key === 'Enter' || e.key === ' ') {
        e.preventDefault();
        open();
      }
    });
    q.addEventListener('input', filt);
    q.addEventListener('keydown', function (e) {
      var vs = vis();
      if (e.key === 'Escape') { close(); b.focus(); }
      else if (e.key === 'ArrowDown') { e.preventDefault(); hl(kb + 1); }
      else if (e.key === 'ArrowUp') { e.preventDefault(); hl(kb - 1); }
      else if (e.key === 'Enter' && vs.length) {
        e.preventDefault();
        set((vs[kb] || vs[0]).dataset.v);
        close();
        b.focus();
      }
    });
    os.forEach(function (o) {
      o.addEventListener('click', function (e) {
        e.stopPropagation();
        set(o.dataset.v);
        close();
        b.focus();
      });
    });
    p.addEventListener('click', function (e) { e.stopPropagation(); });
    document.addEventListener('click', function () { if (!p.hidden) close(); });

    return {
      set: set,
      get: function () { return val; },
      clearError: function () { root.classList.remove('bad'); }
    };
  }

  var cs2 = document.getElementById('cs2');
  var c2 = makePicker(cs2, otherCountries, function (v) {
    if (c1.get() === NL) unlistedInput.value = v;
    document.getElementById('unlisted-error').classList.remove('on');
    cs2.classList.remove('bad');
  });

  var mainItems = mainCountries.map(function (c) {
    return { value: c.code, label: c.name };
  }).concat(['-', { value: NL, label: NL_LABEL }]);

  var c1 = makePicker(document.getElementById('cs1'), mainItems, function (v) {
    var nl = v === NL;
    cs2.hidden = !nl;
    if (!nl) {
      c2.set('', true);
      unlistedInput.value = '';
      countryInput.value = v;
    } else {
      countryInput.value = NL;
      unlistedInput.value = c2.get();
    }
    document.getElementById('country-error').classList.remove('on');
    document.getElementById('unlisted-error').classList.remove('on');
    document.getElementById('cs1').classList.remove('bad');
    cs2.classList.remove('bad');
  });

  if (oldCountry === NL) {
    c1.set(NL, true);
    cs2.hidden = false;
    if (oldUnlisted) c2.set(oldUnlisted, true);
  } else if (oldCountry) {
    c1.set(oldCountry, true);
  }

  /* ---------- Hear about us ---------- */
  (function () {
    var b = document.getElementById('hs-b');
    var p = document.getElementById('hs-p');
    var q = document.getElementById('hs-q');
    var v = document.getElementById('hs-v');
    var os = [].slice.call(document.querySelectorAll('.hs-o'));
    var none = document.querySelector('.hs-none');
    if (!b) return;

    function open() {
      document.querySelectorAll('.cs-p').forEach(function (x) { x.hidden = true; });
      p.hidden = false;
      b.setAttribute('aria-expanded', 'true');
      q.value = '';
      filt();
      setTimeout(function () { q.focus(); }, 0);
    }
    function close() {
      p.hidden = true;
      b.setAttribute('aria-expanded', 'false');
    }
    function filt() {
      var t = q.value.toLowerCase();
      var n = 0;
      os.forEach(function (o) {
        var s = o.textContent.toLowerCase().indexOf(t) > -1;
        o.hidden = !s;
        if (s) n++;
      });
      none.hidden = n > 0;
    }
    function pick(o) {
      os.forEach(function (x) {
        x.classList.toggle('sel', x === o);
        x.setAttribute('aria-selected', x === o ? 'true' : 'false');
      });
      v.textContent = o.textContent;
      v.className = '';
      referralInput.value = o.getAttribute('data-value') || '';
      close();
      b.focus();
    }

    b.addEventListener('click', function (e) {
      e.stopPropagation();
      p.hidden ? open() : close();
    });
    q.addEventListener('input', filt);
    q.addEventListener('keydown', function (e) {
      var vis = os.filter(function (o) { return !o.hidden; });
      if (e.key === 'Escape') { close(); b.focus(); }
      if (e.key === 'Enter' && vis.length) { e.preventDefault(); pick(vis[0]); }
    });
    os.forEach(function (o) {
      o.addEventListener('click', function (e) { e.stopPropagation(); pick(o); });
    });
    p.addEventListener('click', function (e) { e.stopPropagation(); });
    document.addEventListener('click', function () { if (!p.hidden) close(); });
  })();

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
    var country = c1.get();
    var unlisted = c2.get();
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
      setErr('cs1', 'country-error', true, 'Choose where you are based');
      ok = false;
    } else if (country === NL && !unlisted) {
      setErr('cs2', 'unlisted-error', true, 'Choose your country');
      ok = false;
    }
    if (!termsInput.checked) {
      setErr(null, 'terms-error', true, 'Tick the box to continue');
      ok = false;
    }
    if (!ok) return;

    countryInput.value = country;
    unlistedInput.value = country === NL ? unlisted : '';

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
        return { status: res.status, data: data || {} };
      }).catch(function () {
        return { status: res.status, data: {} };
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
        if (errors.payout_bank_country) setErr('cs1', 'country-error', true, errors.payout_bank_country[0]);
        if (errors.unlisted_country) setErr('cs2', 'unlisted-error', true, errors.unlisted_country[0]);
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
