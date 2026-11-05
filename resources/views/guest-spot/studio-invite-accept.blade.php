@php
  $artistPercent = (int) ($artistPercent ?? 60);
  $studioPercent = (int) ($studioPercent ?? (100 - $artistPercent));
  $studioAlreadyConnected = (bool) ($studioAlreadyConnected ?? false);
  $stripeConnectConfigured = (bool) ($stripeConnectConfigured ?? false);
  $showStripeFlow = ! $studioAlreadyConnected && $stripeConnectConfigured;
@endphp
<!DOCTYPE html>
<html lang="en">
<head>
  @include('layouts.partials.google-analytics')
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="color-scheme" content="only light">
  <meta name="csrf-token" content="{{ csrf_token() }}">
  <title>Guest spot invitation — Bookpay</title>
  <link rel="icon" href="{{ asset('design/images/icons/favicon.png') }}">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
  <link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@700&display=swap" rel="stylesheet">
  <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@24,400,0,0&display=block" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet">
  <style>
    *{box-sizing:border-box}
    body{margin:0;min-height:100vh;background:#FFF6FF;color:#1A1A1A;font-family:'Plus Jakarta Sans',system-ui,sans-serif;font-size:14.5px;display:flex;flex-direction:column;align-items:center;padding:44px 16px 40px}
    .ms{font-family:'Material Symbols Outlined';font-weight:normal;font-style:normal;font-size:20px;line-height:1;display:inline-block;-webkit-font-smoothing:antialiased}
    .logo{text-align:center;margin-bottom:28px;text-decoration:none;color:inherit}
    .logo b{display:block;font-family:'Space Grotesk',system-ui,sans-serif;font-size:30px;font-weight:700;letter-spacing:-.055em;line-height:1}
    .logo span{display:block;font-size:8.5px;letter-spacing:1.2px;margin-top:6px;line-height:1.3;color:#6F6874}
    .card{background:#fff;border:1px solid #F0E4F5;border-radius:16px;width:100%;max-width:480px;padding:34px 34px 30px;box-shadow:0 12px 40px rgba(62,0,124,.05);overflow:visible;min-width:0}
    .card:has(#studioStripeStep:not(.hidden)){max-width:640px;padding:28px 18px 24px}
    h1{font-size:26px;font-weight:800;letter-spacing:-.6px;text-align:center;margin:0 0 8px}
    .sub{text-align:center;color:#3F3A43;line-height:1.5;margin:0 0 24px}
    .fl{display:block;font-size:13px;font-weight:700;margin:16px 0 7px}
    .in{display:flex;align-items:center;border:1px solid #DCD2E0;border-radius:10px;background:#fff}
    .in:focus-within{border-color:#3E007C;box-shadow:0 0 0 3px #EEE3FA}
    .in.bad{border-color:#C62828}
    .in input,.in select{flex:1;border:0;outline:0;font:inherit;font-size:14.5px;padding:12px 14px;background:none;min-width:0;color:inherit}
    .in .eye{border:0;background:none;padding:0 12px;cursor:pointer;color:#9A929E;display:inline-flex}
    .err{color:#C62828;font-size:12.5px;margin-top:6px;display:none}.err.on{display:block}
    .hint{font-size:12.5px;color:#6F6874;margin-top:6px;line-height:1.45}
    .ck{display:flex;gap:10px;align-items:flex-start;font-size:13px;margin-top:16px;cursor:pointer}
    .ck input{width:16px;height:16px;min-width:16px;margin-top:2px;accent-color:#3E007C}
    .btn{display:flex;justify-content:center;align-items:center;gap:8px;width:100%;font:inherit;font-weight:700;font-size:15px;border:0;border-radius:12px;background:#1A1A1A;color:#fff;padding:14px;cursor:pointer;margin-top:22px;text-decoration:none}
    .btn.ghost{background:#fff;color:#1A1A1A;border:1px solid #E8DFEA}
    .btn:disabled{opacity:.5;cursor:not-allowed}
    .banner{display:flex;gap:10px;align-items:flex-start;border-radius:10px;padding:11px 13px;font-size:13px;line-height:1.45;margin-bottom:16px}
    .banner.bad{background:#FDECEC;color:#B3261E}.banner.ok{background:#E6F8EE;color:#1F6B4E}.banner .ms{font-size:18px;flex:none}
    .ac{display:flex;gap:14px;align-items:center;border:1px solid #F0E4F5;background:#FBF7FE;border-radius:14px;padding:14px}
    .ac .av{width:56px;height:56px;border-radius:50%;background:#EDE6F0;background-size:cover;background-position:center;flex:none;display:flex;align-items:center;justify-content:center;font-weight:800;color:#3E007C}
    .ac b{font-size:16px;display:block}.ac small{display:block;color:#6F6874;font-size:12.5px;margin-top:2px;line-height:1.45}
    .box{border:1px solid #F0E4F5;border-radius:14px;padding:14px 16px;margin-top:14px}
    .lbl{font-size:11.5px;font-weight:700;letter-spacing:.06em;text-transform:uppercase;color:#8A8290;margin-bottom:10px}
    .kv{display:flex;justify-content:space-between;align-items:center;gap:10px;font-size:14px;margin-top:8px}.kv span{color:#6F6874}
    .split{display:grid;grid-template-columns:1fr 1fr;gap:10px;margin-top:4px}.split div{background:#FBF7FE;border-radius:10px;padding:10px 12px}.split small{display:block;color:#6F6874;font-size:12px}.split b{font-size:20px}
    .free{display:flex;gap:10px;align-items:flex-start;background:#E6F8EE;color:#1F6B4E;border-radius:12px;padding:12px 14px;margin-top:14px;font-size:13.5px;line-height:1.45}.free .ms{font-size:19px;flex:none}.free small{display:block;color:#3F6B57;font-size:12.5px;margin-top:2px}
    .two{display:flex;gap:10px;margin-top:22px}.two .btn{margin-top:0}
    .alt{text-align:center;font-size:13px;margin-top:20px;padding-top:18px;border-top:1px solid #F0E4F5}.alt a{color:#1A1A1A;font-weight:700;text-decoration:none}
    .md{position:fixed;inset:0;background:rgba(20,10,30,.45);display:flex;align-items:center;justify-content:center;padding:16px;z-index:50}.md[hidden]{display:none}.md .mb{background:#fff;border-radius:16px;max-width:420px;width:100%;padding:24px}
    .stripe{display:flex;gap:12px;align-items:center;border:1px solid #F0E4F5;border-radius:14px;padding:14px 16px;margin-top:14px}
    .stripe .sic{width:40px;height:40px;border-radius:10px;background:#635BFF;color:#fff;display:flex;align-items:center;justify-content:center;font-weight:800;flex:none}
    .rs{display:flex;justify-content:center;gap:6px;font-size:13px;color:#6F6874;margin-top:16px;flex-wrap:wrap}
    .link{background:none;border:0;font:inherit;font-weight:700;color:#1A1A1A;cursor:pointer;padding:0;text-decoration:underline}
    .small{font-size:13px;color:#6F6874;text-align:center;line-height:1.5}
    .hidden{display:none!important}
    select.js-select2{width:100%}
    .in:has(> select.js-select2){
      display:block;border:0;background:transparent;box-shadow:none;padding:0
    }
    .in:has(> select.js-select2):focus-within{border:0;box-shadow:none}
    .select2-container{width:100%!important;z-index:1}
    .select2-container--open{z-index:40!important}
    .select2-container--default .select2-selection--single{
      min-height:46px;padding:6px 12px;border-radius:10px;border:1px solid #DCD2E0!important;background:#fff!important
    }
    .select2-container--default .select2-selection--single .select2-selection__rendered{
      line-height:32px;padding-left:2px;color:#1A1A1A;font-size:14.5px;font-family:inherit
    }
    .select2-container--default .select2-selection--single .select2-selection__placeholder{color:#9A929E}
    .select2-container--default .select2-selection--single .select2-selection__arrow{height:44px;right:8px}
    .select2-container--default.select2-container--focus .select2-selection--single,
    .select2-container--default.select2-container--open .select2-selection--single{
      border-color:#3E007C!important;box-shadow:0 0 0 3px #EEE3FA
    }
    .select2-container--default .select2-selection--single.s2-err{
      border-color:#C62828!important;box-shadow:0 0 0 3px #FDECEC
    }
    .select2-dropdown{border-radius:10px;border-color:#DCD2E0;overflow:hidden;font-family:inherit;font-size:14px}
    .select2-container--default .select2-results__option--highlighted[aria-selected]{background-color:#3E007C!important}
    .select2-container--default .select2-search--dropdown .select2-search__field{
      border-radius:8px;border-color:#DCD2E0;padding:8px 10px;font:inherit
    }
    #studioStripeStep{width:100%;max-width:100%;min-width:0;overflow:visible}
    .stripe-connect-shell{
      width:100%;max-width:100%;min-width:0;margin:8px 0 0;padding:0;
      overflow:visible;-webkit-overflow-scrolling:touch
    }
    #studioStripeConnectMount{
      min-height:420px;width:100%;max-width:100%;min-width:0;
      background:#fff;overflow:visible;box-sizing:border-box
    }
    #studioStripeConnectMount > *{
      display:block;width:100%!important;max-width:100%!important;min-width:0!important;box-sizing:border-box
    }
    #studioStripeConnectMount iframe{
      width:100%!important;max-width:100%!important;min-width:0!important;border:0
    }
    footer{margin-top:auto;padding-top:40px;text-align:center;font-size:13px}
    footer nav{display:flex;gap:28px;justify-content:center;flex-wrap:wrap}
    footer a{color:#1A1A1A;text-decoration:none}
    footer small{display:block;color:#6F6874;margin-top:12px;font-size:12.5px}
    @media (max-width:640px){
      body:has(#studioStripeStep:not(.hidden)){padding-left:8px;padding-right:8px}
      .card:has(#studioStripeStep:not(.hidden)){max-width:100%;padding:22px 10px 20px}
    }
    @media (max-width:480px){
      .card{padding:26px 20px 22px}
      h1{font-size:23px}.two{flex-direction:column}.split b{font-size:18px}body{padding-top:32px}
    }
  </style>
</head>
<body>
  <a class="logo" href="{{ url('/') }}" title="Bookpay">
    <b>bookpay</b>
    <span>FOR TATTOO ARTISTS AND STUDIOS<br>BY INKJIN</span>
  </a>

  <main class="card" id="app">
    @if ($errors->any())
      <div class="banner bad"><span class="ms">info</span><div>{{ $errors->first() }}</div></div>
    @endif

    @if (!empty($wrongAccount))
      <div class="banner bad"><span class="ms">info</span>
        <div>Sign in with <b>{{ $studioEmail }}</b> to respond to this invite.</div>
      </div>
      <a class="btn" href="{{ route('logout') }}" onclick="event.preventDefault();document.getElementById('logout-form').submit();">Switch account</a>
      <form id="logout-form" method="post" action="{{ route('logout') }}" style="display:none">@csrf</form>
    @else
      <div id="studioDecisionPanel">
        <h1>Guest spot invitation</h1>
        <p class="sub">{{ $artistName }} wants to do a guest spot at {{ $studioNameValue }}, and invited you to Bookpay so your share of each booking is paid to you automatically.</p>

        <div class="ac">
          <div class="av" @if(!empty($artistAvatarUrl)) style="background-image:url('{{ $artistAvatarUrl }}')" @endif>
            @if (empty($artistAvatarUrl))
              {{ $artistInitials }}
            @endif
          </div>
          <div style="min-width:0">
            <b>{{ $artistName }}</b>
            @if ($artistMeta !== '')
              <small>{{ $artistMeta }}</small>
            @endif
            @if (!empty($artistStyleLine))
              <small>{{ $artistStyleLine }}</small>
            @endif
          </div>
        </div>

        <div class="box">
          <div class="lbl">Guest spot</div>
          @if ($dateRangeLabel !== '')
            <div class="kv"><span>Dates</span><b>{{ $dateRangeLabel }}</b></div>
          @endif
          @if ($whereLabel !== '')
            <div class="kv"><span>Where</span><b>{{ $whereLabel }}</b></div>
          @endif
        </div>

        <div class="box">
          <div class="lbl">What {{ $artistFirst }} proposed</div>
          <div class="kv"><span>Relationship</span><b>Guest</b></div>
          <div class="kv" style="margin-top:12px"><span>Revenue split</span></div>
          <div class="split">
            <div>
              <small>{{ $artistFirst }} gets</small>
              <b>{{ $artistPercent }}%</b>
            </div>
            <div>
              <small>Your studio gets</small>
              <b>{{ $studioPercent }}%</b>
            </div>
          </div>
        </div>

        <div class="free">
          <span class="ms" style="font-variation-settings:'FILL' 1">verified</span>
          <div>
            <b>Free for studios. No monthly fee, no setup fee, no commission.</b>
            <small>Stripe's processing fee is shared with {{ $artistFirst }} by the same split.</small>
          </div>
        </div>

        @if (!empty($needsAuth))
          <a class="btn" href="{{ route('login', ['redirect' => route('guest-spot.studio-invite.show', ['token' => $token])]) }}">Log in to accept</a>
          <button type="button" class="btn ghost" id="dec" style="color:#C62828;margin-top:10px">Decline</button>
        @elseif (!empty($showSignup))
          <form id="studioInviteForm" novalidate>
            <div class="lbl" style="margin:20px 0 0">Your studio</div>

            <label class="fl" for="sn">Studio name</label>
            <div class="in"><input id="sn" value="{{ $studioNameValue }}" readonly></div>
            <p class="hint">Filled in from {{ $artistFirst }}'s invite. You can change it later in your dashboard.</p>

            <label class="fl" for="em">Studio email</label>
            <div class="in"><input id="em" type="email" value="{{ $studioEmail }}" readonly></div>
            <p class="hint">The email {{ $artistFirst }} sent the invite to. No code needed — the invite link confirms it.</p>

            <label class="fl" for="pw">Password</label>
            <div class="in" id="pww">
              <input id="pw" name="password" type="password" autocomplete="new-password" required>
              <button type="button" class="eye" id="eye" aria-label="Show password"><span class="ms">visibility</span></button>
            </div>
            <p class="hint">At least 8 characters.</p>
            <p class="err" id="pwe">Use at least 8 characters</p>

            <label class="ck">
              <input type="checkbox" name="terms" value="1" id="tc" required>
              <span>I agree to the <a href="https://inkjin.com/en/artist-terms" target="_blank" rel="noopener" style="color:inherit;font-weight:700">Terms of Use</a> and <a href="https://inkjin.com/en/privacy" target="_blank" rel="noopener" style="color:inherit;font-weight:700">Privacy Policy</a></span>
            </label>
            <p class="err" id="tce">Tick the box to continue</p>
            <p class="err" id="formErr" role="alert"></p>

            <div class="two">
              <button type="button" class="btn ghost" id="dec">Decline</button>
              <button type="submit" class="btn" id="go">Accept invitation</button>
            </div>
          </form>
          <div class="alt">Already have a studio account? <a href="{{ route('login', ['redirect' => route('guest-spot.studio-invite.show', ['token' => $token])]) }}">Sign in</a></div>
        @else
          <p class="err" id="formErr" role="alert"></p>
          <div class="two">
            <button type="button" class="btn ghost" id="dec">Decline</button>
            <button type="button" class="btn" id="goExisting">Accept invitation</button>
          </div>
        @endif
      </div>

      @if ($showStripeFlow)
        <div id="studioPayoutsPanel" class="hidden">
          <h1>Get your share paid out</h1>
          <p class="sub">Connect Stripe so your <b>{{ $studioPercent }}%</b> of each of {{ $artistFirst }}'s bookings goes straight to your bank account.</p>

          <div class="banner ok" id="studioPayoutsToast" hidden>
            <span class="ms">check_circle</span>
            <span id="studioPayoutsToastText">Account created. Split confirmed</span>
          </div>

          <div class="stripe" id="studioStripeStatusRow">
            <div class="sic">S</div>
            <div style="flex:1;min-width:0">
              <b>Stripe</b>
              <div class="hint" style="margin-top:2px" id="studioStripeStatusText">Not connected. Takes about 5 minutes.</div>
            </div>
            <button type="button" class="btn" id="studioStripeConnectBtn" style="width:auto;margin-top:0;padding:10px 14px;font-size:14px">Connect</button>
          </div>

          <button type="button" class="btn" id="studioPayoutsContinue" disabled>Continue</button>
          <div class="rs"><button type="button" class="link" id="studioPayoutsSkip">Skip for now</button></div>
          <p class="small" style="margin-top:6px">If you skip, {{ $artistFirst }} is paid directly until you connect Stripe. Your share starts with the next bookings after that.</p>
        </div>

        <div id="studioConnectPanel" class="hidden" style="margin-top:8px">
          <div id="studioSetupStep">
            <h1 style="font-size:22px;text-align:left">Connect your studio bank account</h1>
            <p class="sub" style="text-align:left;margin-bottom:8px">Answer a few questions so we can set up the right payout account.</p>

            <label class="fl" for="studio_business_type">Account type</label>
            <div class="in">
              <select id="studio_business_type" name="business_type" class="js-select2" data-placeholder="Select type" data-searchable="false">
                <option value=""></option>
                <option value="individual">Individual</option>
                <option value="company">Business</option>
              </select>
            </div>
            <p id="studio_business_type_error" class="err"></p>
            <p class="hint"><b>Individual</b> — you work under your own name. <b>Business</b> — registered studio or company.</p>

            <label class="fl" for="studio_country">Country</label>
            <div class="in">
              <select id="studio_country" name="country" class="js-select2" data-placeholder="Search and select country" data-searchable="true">
                <option value=""></option>
                @foreach ($stripeSupportedCountries as $country)
                  <option value="{{ $country['code'] }}">{{ $country['name'] }}</option>
                @endforeach
              </select>
            </div>
            <p id="studio_country_error" class="err"></p>

            <label class="fl" for="studio_industry">What best describes you?</label>
            <div class="in">
              <select id="studio_industry" name="industry" class="js-select2" data-placeholder="Select option" data-searchable="false">
                <option value=""></option>
                <option value="tattoo_studio">Tattoo studio — We do tattoos and body art</option>
                <option value="tattoo_beauty">Tattoo &amp; beauty studio — Also beauty, piercing, or barber</option>
                <option value="other">Other — Something else</option>
              </select>
            </div>
            <p id="studio_industry_error" class="err"></p>
            <p id="studio_setup_form_error" class="err" role="alert"></p>
            <button type="button" class="btn" id="studioSetupContinue">Continue <span class="ms">arrow_forward</span></button>
            <button type="button" class="btn ghost" id="studioSetupBack">Back</button>
          </div>

          <div id="studioStripeStep" class="hidden">
            <h1 style="font-size:22px;text-align:left">Complete your Stripe payout setup</h1>
            <p class="sub" style="text-align:left" id="studioStripeStepDescription">Add your details, verify your identity, and connect your bank account below.</p>
            <div class="stripe-connect-shell">
              <div id="studioStripeConnectMount"></div>
            </div>
            <p id="studio_stripe_connect_error" class="err"></p>
            <p class="hint" id="studioStripeConnectHint">Setup finishes automatically when all required steps are complete.</p>
            <button type="button" class="btn ghost" id="studioStripeStepBack">Back</button>
          </div>
        </div>

        <div id="studioSkippedPanel" class="hidden" style="text-align:center">
          <div style="width:54px;height:54px;border-radius:50%;background:#F3E8FF;color:#3E007C;display:flex;align-items:center;justify-content:center;margin:0 auto 16px">
            <span class="ms" style="font-size:26px">schedule</span>
          </div>
          <h1>You can connect Stripe later</h1>
          <p class="sub">{{ $artistFirst }} is paid directly until you connect Stripe. Your share starts with the next bookings after that.</p>
          <a class="btn" href="{{ route('studio.dashboard') }}">Go to Bookpay</a>
        </div>
      @elseif (! $studioAlreadyConnected && ! $stripeConnectConfigured)
        <div class="banner bad" style="margin-top:16px"><span class="ms">error</span><div>Stripe is not configured. Please contact support.</div></div>
      @endif
    @endif
  </main>

  <div class="md" id="decm" hidden>
    <div class="mb">
      <h1 style="font-size:20px;text-align:left">Decline {{ $artistFirst }}'s invitation?</h1>
      <p class="sub" style="text-align:left;margin-bottom:14px">{{ $artistFirst }} is told you declined, and keeps being paid directly. You can still join Bookpay later.</p>
      <p id="decline_form_error" class="err" role="alert"></p>
      <div class="two">
        <button type="button" class="btn ghost" id="decx">Cancel</button>
        <button type="button" class="btn" id="decok" style="background:#C62828">Decline</button>
      </div>
    </div>
  </div>

  <footer>
    <nav>
      <a href="https://inkjin.com/en/privacy">Privacy Policy</a>
      <a href="https://inkjin.com/en/artist-terms">Terms of Service</a>
      <a href="https://help.inkjin.com">Help Center</a>
    </nav>
    <small>© {{ date('Y') }} Inkjin. All rights reserved.</small>
  </footer>

  <script src="https://cdn.jsdelivr.net/npm/jquery@3.7.1/dist/jquery.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

  @unless (!empty($wrongAccount))
  <script>
  (function () {
    function initStudioInviteSelect2() {
      if (!window.jQuery || !jQuery.fn.select2) return;
      jQuery('#studio_business_type, #studio_country, #studio_industry').each(function () {
        var $el = jQuery(this);
        if ($el.hasClass('select2-hidden-accessible')) {
          $el.trigger('change.select2');
          return;
        }
        var searchable = $el.data('searchable');
        var cfg = {
          width: '100%',
          placeholder: $el.data('placeholder') || 'Select',
          allowClear: false,
          dropdownParent: $el.closest('.card, main, body')
        };
        if (searchable === false || searchable === 0 || searchable === 'false') {
          cfg.minimumResultsForSearch = Infinity;
        }
        $el.select2(cfg);
        $el.on('change', function () {
          $el.next('.select2-container').find('.select2-selection').removeClass('s2-err');
          var err = document.getElementById('studio_' + this.name + '_error');
          if (err) { err.classList.remove('on'); err.textContent = ''; }
        });
      });
    }
    window.initStudioInviteSelect2 = initStudioInviteSelect2;

    const acceptUrl = @json($acceptUrl);
    const declineUrl = @json($declineUrl);
    const studioAlreadyConnected = @json($studioAlreadyConnected);
    const showStripeFlow = @json($showStripeFlow);
    window.__gsInviteCsrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';

    function csrfToken() {
      return window.__gsInviteCsrf
        || document.querySelector('meta[name="csrf-token"]')?.getAttribute('content')
        || '';
    }
    function refreshCsrfToken(token) {
      if (!token) return;
      window.__gsInviteCsrf = token;
      const meta = document.querySelector('meta[name="csrf-token"]');
      if (meta) meta.setAttribute('content', token);
    }

    function showPayoutsPanel(toastText) {
      document.getElementById('studioDecisionPanel')?.classList.add('hidden');
      document.getElementById('studioConnectPanel')?.classList.add('hidden');
      document.getElementById('studioSkippedPanel')?.classList.add('hidden');
      const panel = document.getElementById('studioPayoutsPanel');
      panel?.classList.remove('hidden');
      if (toastText) {
        const toast = document.getElementById('studioPayoutsToast');
        const toastTextEl = document.getElementById('studioPayoutsToastText');
        if (toastTextEl) toastTextEl.textContent = toastText;
        if (toast) toast.hidden = false;
      }
      panel?.scrollIntoView({ behavior: 'smooth', block: 'start' });
    }
    function showConnectPanel() {
      document.getElementById('studioDecisionPanel')?.classList.add('hidden');
      document.getElementById('studioPayoutsPanel')?.classList.add('hidden');
      document.getElementById('studioSkippedPanel')?.classList.add('hidden');
      document.getElementById('studioSetupStep')?.classList.remove('hidden');
      document.getElementById('studioStripeStep')?.classList.add('hidden');
      document.getElementById('studioConnectPanel')?.classList.remove('hidden');
      if (typeof window.initStudioInviteSelect2 === 'function') window.initStudioInviteSelect2();
      document.getElementById('studioConnectPanel')?.scrollIntoView({ behavior: 'smooth', block: 'start' });
    }
    function showSkippedPanel() {
      document.getElementById('studioDecisionPanel')?.classList.add('hidden');
      document.getElementById('studioPayoutsPanel')?.classList.add('hidden');
      document.getElementById('studioConnectPanel')?.classList.add('hidden');
      document.getElementById('studioSkippedPanel')?.classList.remove('hidden');
    }

    async function acceptInvite(payload, btn) {
      const formErr = document.getElementById('formErr');
      if (formErr) { formErr.textContent = ''; formErr.classList.remove('on'); }
      if (btn) { btn.disabled = true; btn.textContent = 'Working…'; }
      try {
        const res = await fetch(acceptUrl, {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': csrfToken(),
            Accept: 'application/json',
          },
          body: JSON.stringify(payload),
        });
        const data = await res.json().catch(() => ({}));
        if (res.status === 419) throw new Error(data.message || 'Your session has expired. Please refresh.');
        if (res.status === 422 && data.errors) {
          if (data.errors.password?.[0]) {
            document.getElementById('pwe').textContent = data.errors.password[0];
            document.getElementById('pwe').classList.add('on');
            document.getElementById('pww')?.classList.add('bad');
          }
          if (data.errors.terms?.[0]) {
            document.getElementById('tce').textContent = data.errors.terms[0];
            document.getElementById('tce').classList.add('on');
          }
          if (data.message && formErr) { formErr.textContent = data.message; formErr.classList.add('on'); }
          return;
        }
        if (!res.ok || !data.success) throw new Error(data.message || 'Could not accept the invitation.');
        refreshCsrfToken(data.csrf_token);
        if (data.redirect) { window.location.href = data.redirect; return; }
        if (data.next === 'stripe' || (showStripeFlow && !studioAlreadyConnected && document.getElementById('studioPayoutsPanel'))) {
          showPayoutsPanel(data.message || 'Account created. Split confirmed');
          return;
        }
        window.location.href = @json(route('studio.dashboard'));
      } catch (err) {
        if (formErr) { formErr.textContent = err.message || 'Could not accept the invitation.'; formErr.classList.add('on'); }
      } finally {
        if (btn) { btn.disabled = false; btn.textContent = 'Accept invitation'; }
      }
    }

    document.getElementById('eye')?.addEventListener('click', function () {
      const i = document.getElementById('pw');
      if (!i) return;
      const show = i.type === 'password';
      i.type = show ? 'text' : 'password';
      this.querySelector('.ms').textContent = show ? 'visibility_off' : 'visibility';
    });

    document.getElementById('studioInviteForm')?.addEventListener('submit', function (e) {
      e.preventDefault();
      document.getElementById('pww')?.classList.remove('bad');
      document.getElementById('pwe')?.classList.remove('on');
      document.getElementById('tce')?.classList.remove('on');
      let ok = true;
      const pw = document.getElementById('pw')?.value || '';
      if (pw.length < 8) {
        document.getElementById('pww')?.classList.add('bad');
        document.getElementById('pwe')?.classList.add('on');
        ok = false;
      }
      if (!document.getElementById('tc')?.checked) {
        document.getElementById('tce')?.classList.add('on');
        ok = false;
      }
      if (!ok) return;
      acceptInvite({ password: pw, terms: 1 }, document.getElementById('go'));
    });

    document.getElementById('goExisting')?.addEventListener('click', function () {
      acceptInvite({}, this);
    });

    document.getElementById('dec')?.addEventListener('click', function () {
      document.getElementById('decm').hidden = false;
    });
    document.getElementById('decx')?.addEventListener('click', function () {
      document.getElementById('decm').hidden = true;
    });
    document.getElementById('decok')?.addEventListener('click', async function () {
      const btn = this;
      const err = document.getElementById('decline_form_error');
      if (err) { err.textContent = ''; err.classList.remove('on'); }
      btn.disabled = true;
      try {
        const res = await fetch(declineUrl, {
          method: 'POST',
          headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken(), Accept: 'application/json' },
          body: JSON.stringify({}),
        });
        const data = await res.json().catch(() => ({}));
        if (!res.ok) throw new Error(data.message || 'Could not decline the invitation.');
        document.getElementById('decm').hidden = true;
        const app = document.getElementById('app');
        if (app) {
          const esc = (s) => String(s || '').replace(/[&<>"]/g, (c) => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;'}[c]));
          app.innerHTML = '<div style="text-align:center"><div style="width:54px;height:54px;border-radius:50%;background:#F3E8FF;color:#3E007C;display:flex;align-items:center;justify-content:center;margin:0 auto 16px"><span class="ms" style="font-size:26px">do_not_disturb_on</span></div><h1>' + esc(data.title || 'Invitation declined') + '</h1><p class="sub">' + esc(data.message || 'We let the artist know.') + '</p></div>';
        }
      } catch (e) {
        if (err) { err.textContent = e.message || 'Could not decline.'; err.classList.add('on'); }
        btn.disabled = false;
      }
    });

    document.getElementById('studioStripeConnectBtn')?.addEventListener('click', showConnectPanel);
    document.getElementById('studioPayoutsSkip')?.addEventListener('click', showSkippedPanel);
    document.getElementById('studioPayoutsContinue')?.addEventListener('click', function () {
      if (this.disabled) return;
      window.location.href = @json(route('studio.dashboard'));
    });
    document.getElementById('studioSetupBack')?.addEventListener('click', function () { showPayoutsPanel(); });
    document.getElementById('studioStripeStepBack')?.addEventListener('click', function () {
      document.getElementById('studioStripeStep')?.classList.add('hidden');
      document.getElementById('studioSetupStep')?.classList.remove('hidden');
    });
  })();
  </script>
  @endunless

  @if ($showStripeFlow && empty($wrongAccount))
  <script type="module">
const publishableKey = @json($stripePublishableKey ?? '');
const sessionUrl = @json($stripeSessionUrl);
const completeUrl = @json($stripeCompleteUrl);
const stripeConnectLocale = @json($stripeConnectLocale ?? 'en-US');
const stripeConnectAppearance = @json(config('services.stripe.connect.appearance', []));
function csrfToken() {
  return window.__gsInviteCsrf
    || document.querySelector('meta[name="csrf-token"]')?.getAttribute('content')
    || '';
}

let connectInstance = null;
let stripeSessionData = null;
let onboardingMounted = false;
let completeTriggered = false;
let loadConnectAndInitialize = null;

async function ensureStripeConnectLoader() {
  if (typeof loadConnectAndInitialize === 'function') return loadConnectAndInitialize;
  const urls = [
    'https://esm.sh/@stripe/connect-js@3.3.34/pure',
    'https://cdn.jsdelivr.net/npm/@stripe/connect-js@3.3.34/+esm',
  ];
  let lastError = null;
  for (const url of urls) {
    try {
      const mod = await import(url);
      if (typeof mod.loadConnectAndInitialize === 'function') {
        loadConnectAndInitialize = mod.loadConnectAndInitialize;
        return loadConnectAndInitialize;
      }
    } catch (err) { lastError = err; }
  }
  throw lastError || new Error('Could not load Stripe Connect.');
}

function clearStudioSetupErrors() {
  ['studio_business_type_error', 'studio_country_error', 'studio_industry_error', 'studio_stripe_connect_error', 'studio_setup_form_error'].forEach((id) => {
    const el = document.getElementById(id);
    if (el) { el.classList.remove('on'); el.textContent = ''; }
  });
  ['studio_business_type', 'studio_country', 'studio_industry'].forEach((id) => {
    const el = document.getElementById(id);
    if (!el) return;
    el.closest('.in')?.classList.remove('bad');
    if (window.jQuery) {
      jQuery(el).next('.select2-container').find('.select2-selection').removeClass('s2-err');
    }
  });
}
function showStudioSetupError(field, message) {
  const el = document.getElementById(`studio_${field}_error`);
  if (el) { el.textContent = message; el.classList.add('on'); }
  const select = document.getElementById(`studio_${field}`);
  if (select && window.jQuery) {
    jQuery(select).next('.select2-container').find('.select2-selection').addClass('s2-err');
  }
}
function showStripeError(message) {
  const errEl = document.getElementById('studio_stripe_connect_error');
  if (errEl) { errEl.textContent = message || ''; errEl.classList.toggle('on', !!message); }
}
function showSetupFormError(message) {
  const errEl = document.getElementById('studio_setup_form_error');
  if (errEl) { errEl.textContent = message || ''; errEl.classList.toggle('on', !!message); }
  showStripeError(message);
}
function readStudioSetup() {
  return {
    business_type: document.getElementById('studio_business_type')?.value || '',
    country: document.getElementById('studio_country')?.value || '',
    industry: document.getElementById('studio_industry')?.value || '',
  };
}
function validateStudioSetup() {
  clearStudioSetupErrors();
  const setup = readStudioSetup();
  let valid = true;
  if (!setup.business_type) { showStudioSetupError('business_type', 'Please select an account type.'); valid = false; }
  if (!setup.country) { showStudioSetupError('country', 'Please select your country.'); valid = false; }
  if (!setup.industry) { showStudioSetupError('industry', 'Please select what best describes you.'); valid = false; }
  return valid ? setup : null;
}
function updateStripeStepDescription(setup) {
  const desc = document.getElementById('studioStripeStepDescription');
  if (!desc || !setup) return;
  const typeLabel = setup.business_type === 'individual' ? 'individual' : 'business';
  const countrySelect = document.getElementById('studio_country');
  const countryName = countrySelect?.selectedOptions?.[0]?.text || setup.country;
  desc.textContent = `Complete Stripe onboarding for your ${typeLabel} account in ${countryName}. You will verify your identity and connect your bank account.`;
}

async function createStudioStripeSession(setup) {
  const res = await fetch(sessionUrl, {
    method: 'POST',
    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken(), Accept: 'application/json' },
    body: JSON.stringify(setup),
  });
  const data = await res.json().catch(() => ({}));
  if (res.status === 419) throw new Error(data.message || 'Your session has expired. Please refresh.');
  if (res.status === 422 && data.errors) {
    const errors = data.errors;
    if (errors.business_type?.[0]) showStudioSetupError('business_type', errors.business_type[0]);
    if (errors.country?.[0]) showStudioSetupError('country', errors.country[0]);
    if (errors.industry?.[0]) showStudioSetupError('industry', errors.industry[0]);
    throw new Error(data.message || 'Please check your answers and try again.');
  }
  if (!res.ok || !data.client_secret) throw new Error(data.message || 'Could not start Stripe onboarding.');
  stripeSessionData = data;
  return data;
}

async function finalizeStudioOnboarding() {
  if (completeTriggered || !stripeSessionData?.account_id) return;
  completeTriggered = true;
  const res = await fetch(completeUrl, {
    method: 'POST',
    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken(), Accept: 'application/json' },
    body: JSON.stringify({ account_id: stripeSessionData.account_id }),
  });
  const data = await res.json().catch(() => ({}));
  if (res.status === 419) {
    completeTriggered = false;
    throw new Error(data.message || 'Your session has expired. Please refresh.');
  }
  if (!res.ok || !data.success) {
    completeTriggered = false;
    throw new Error(data.message || 'Could not save Stripe payout setup.');
  }
  window.location.href = data.redirect || @json(route('studio.dashboard'));
}

async function mountStudioStripeOnboarding() {
  const container = document.getElementById('studioStripeConnectMount');
  if (!publishableKey || !container || !stripeSessionData?.client_secret) {
    throw new Error('Stripe is not ready. Please try again.');
  }
  if (onboardingMounted) {
    container.innerHTML = '';
    onboardingMounted = false;
    connectInstance = null;
  }
  container.innerHTML = '<p class="hint" style="padding:24px">Loading Stripe onboarding…</p>';
  const loadConnect = await ensureStripeConnectLoader();
  connectInstance = loadConnect({
    publishableKey,
    fetchClientSecret: async () => stripeSessionData.client_secret,
    locale: stripeConnectLocale || 'en-US',
    appearance: stripeConnectAppearance,
  });
  connectInstance.update({ locale: stripeConnectLocale || 'en-US' });
  const accountOnboarding = connectInstance.create('account-onboarding');
  const collectionOptions = stripeSessionData.collection_options || {};
  accountOnboarding.setCollectionOptions({
    fields: collectionOptions.fields || 'eventually_due',
    futureRequirements: collectionOptions.futureRequirements || 'include',
    ...(collectionOptions.requirements ? { requirements: collectionOptions.requirements } : {}),
  });
  accountOnboarding.setOnExit(async () => {
    document.getElementById('studioStripeConnectHint')?.classList.add('hidden');
    await new Promise((resolve) => setTimeout(resolve, 1500));
    try {
      await finalizeStudioOnboarding();
    } catch (err) {
      completeTriggered = false;
      showStripeError(err.message || 'Could not complete payout setup.');
    }
  });
  container.innerHTML = '';
  accountOnboarding.style.cssText = 'display:block;width:100%;max-width:100%;min-width:0;box-sizing:border-box';
  container.appendChild(accountOnboarding);
  onboardingMounted = true;
}

document.getElementById('studioSetupContinue')?.addEventListener('click', async () => {
  const setup = validateStudioSetup();
  if (!setup) return;
  updateStripeStepDescription(setup);
  const btn = document.getElementById('studioSetupContinue');
  const originalHtml = btn?.innerHTML;
  if (btn) { btn.disabled = true; btn.innerHTML = 'Loading Stripe…'; }
  showSetupFormError('');
  completeTriggered = false;
  try {
    await createStudioStripeSession(setup);
    document.getElementById('studioSetupStep')?.classList.add('hidden');
    document.getElementById('studioStripeStep')?.classList.remove('hidden');
    await mountStudioStripeOnboarding();
  } catch (err) {
    document.getElementById('studioSetupStep')?.classList.remove('hidden');
    document.getElementById('studioStripeStep')?.classList.add('hidden');
    const container = document.getElementById('studioStripeConnectMount');
    if (container) container.innerHTML = '';
    showSetupFormError(err.message || 'Could not load Stripe onboarding.');
  } finally {
    if (btn) { btn.disabled = false; btn.innerHTML = originalHtml || 'Continue <span class="ms">arrow_forward</span>'; }
  }
});
  </script>
  @endif
</body>
</html>
