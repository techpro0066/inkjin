@extends('layouts.studio-onboarding-layout')

@section('title', 'Currency and payouts')

@push('styles')
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet">
<style>
  .pill{display:inline-flex;align-items:center;gap:5px;border-radius:20px;font-size:11.5px;font-weight:600;padding:3px 9px;white-space:nowrap}
  .pill:before{content:'';width:5px;height:5px;border-radius:50%;background:currentColor}
  .pill.g{background:#E6F8EE;color:#00A650}
  .btn.disabled,.btn[aria-disabled="true"]{opacity:.4;pointer-events:none}
  .stripe-connect-row{flex-wrap:wrap}
  .stripe-connect-row .btn{margin-left:auto}
  select.js-select2{width:100%}
  .select2-container{width:100%!important;z-index:1}
  .select2-container--open{z-index:10060!important}
  .select2-container--default .select2-selection--single{
    min-height:40px;padding:4px 10px;border-radius:10px;border:1px solid var(--line)!important;background:#fff!important
  }
  .select2-container--default .select2-selection--single .select2-selection__rendered{
    line-height:2rem;padding-left:4px;color:var(--ink);font-size:13.5px
  }
  .select2-container--default .select2-selection--single .select2-selection__arrow{height:40px}
  .select2-container--default.select2-container--focus .select2-selection--single,
  .select2-container--default.select2-container--open .select2-selection--single{
    border-color:var(--pri)!important;box-shadow:0 0 0 3px #F3E8FF
  }
  .select2-dropdown{border-radius:10px;border-color:var(--line);overflow:hidden;font-family:inherit;font-size:13.5px}
  .select2-container--default .select2-results__option--highlighted[aria-selected]{background-color:var(--pri)!important}
  .select2-container--default .select2-search--dropdown .select2-search__field{border-radius:8px;border-color:var(--line)}
  .select2-container--default .select2-selection--single.s2-err{border-color:#C62828!important;box-shadow:0 0 0 3px #FDECEC}
  .field-err{display:none;color:#C62828;font-size:12px;margin-top:6px}
  .field-err.on{display:block}
  /* Stripe Connect embed: avoid clipping focus rings / full-width fields */
  #paymentAccountCard.card{overflow:visible}
  #paymentAccountCard .card-pad{overflow:visible;min-width:0}
  #studioStripeStep{width:100%;max-width:100%;min-width:0;overflow:visible}
  .stripe-connect-shell{
    width:100%;
    max-width:100%;
    min-width:0;
    margin:8px -12px 0;
    padding:8px 12px 12px;
    overflow-x:auto;
    overflow-y:visible;
    -webkit-overflow-scrolling:touch;
  }
  #studioStripeConnectMount{
    min-height:420px;
    width:100%;
    max-width:100%;
    min-width:0;
    background:#fff;
    overflow:visible;
    box-sizing:border-box;
    padding:4px 2px 8px;
  }
  #studioStripeConnectMount > *{
    display:block;
    width:100%!important;
    max-width:100%!important;
    min-width:0!important;
    box-sizing:border-box;
  }
  @media (max-width:560px){
    .stripe-connect-row .btn{margin-left:0;width:100%;justify-content:center}
    .stripe-connect-shell{margin-left:-8px;margin-right:-8px;padding-left:8px;padding-right:8px}
  }
</style>
@endpush

@section('content')
  <h1 style="margin-top:6px">Currency and payouts<a class="help-q" href="https://help.inkjin.com" target="_blank" rel="noopener" title="Help with this page" aria-label="Help with this page"><span class="ms">help</span></a></h1>
  <div class="sub" style="max-width:660px;margin-bottom:24px">Connect your studio's Stripe account. Your share of each split is sent there automatically.</div>

  <div class="note" id="po-nosplit" @if($offersRevenueSplit) hidden @endif style="margin-bottom:14px"><span class="ms">info</span>You turned off revenue split, so you don't need Stripe now. Rent is paid outside Bookpay. You can connect later in Money &gt; Payouts.</div>

  <div class="card" style="margin-bottom:14px">
    <div class="ch">
      <div>
        <h3>Currency</h3>
        <div class="faint" style="font-size:12.5px;margin-top:2px">The currency your studio works in</div>
      </div>
    </div>
    <div class="card-pad">
      <div class="formgrid">
        <div>
          <label class="fl" for="currency">Currency <span style="color:#C62828">*</span></label>
          <select class="js-select2" id="currency" name="currency" data-placeholder="Search and select currency" aria-label="Currency">
            <option value=""></option>
          </select>
          <div id="currency_error" class="field-err"></div>
          <div class="help" id="currencyHelp">Set from your country ({{ $countryLabel }}). Your earnings and reports show in this currency. Your Stripe bank account must be in the same currency.</div>
        </div>
      </div>
    </div>
  </div>

  <div class="card" style="margin-bottom:14px" id="paymentAccountCard">
    <div class="ch">
      <div>
        <h3>Payment account</h3>
        <div class="faint" style="font-size:12.5px;margin-top:2px">Required to receive revenue splits</div>
      </div>
    </div>
    <div class="card-pad">
      <div class="stripe-off" @if($stripeConnected) hidden @endif>
        <div class="formgrid" id="studioSetupFields">
          <div>
            <label class="fl" for="business_country">Business country</label>
            <select class="js-select2" id="business_country" name="country" data-placeholder="Search and select country" aria-label="Business country">
              @foreach ($stripeCountries as $country)
                <option value="{{ $country['code'] }}" @selected(strtoupper((string) $currentCountry) === strtoupper((string) $country['code']))>{{ $country['name'] }}</option>
              @endforeach
            </select>
            <div id="country_error" class="field-err"></div>
            <div class="help">From sign-up. It can't be changed after you connect.</div>
          </div>
          <div>
            <label class="fl" for="business_type">Business type</label>
            <select class="js-select2" id="business_type" name="business_type" data-placeholder="Select business type" data-searchable="false" aria-label="Business type">
              <option value="company" @selected($businessType === 'company')>Company</option>
              <option value="individual" @selected($businessType === 'individual')>Sole trader / individual</option>
            </select>
            <div id="business_type_error" class="field-err"></div>
          </div>
        </div>

        <div class="row stripe-connect-row" style="gap:14px;margin-top:16px" id="stripeConnectIntro">
          <div class="ic"><span class="ms">account_balance</span></div>
          <div style="flex:1;min-width:0">
            <b>Connect with Stripe</b>
            <div class="faint" style="font-size:12.5px">Business details, owner ID and bank account. It takes about 5 minutes and you stay in Bookpay.</div>
          </div>
          @if ($stripeConnectConfigured)
            <button type="button" class="btn" id="stripeConnectBtn">Connect</button>
          @else
            <button type="button" class="btn disabled" disabled>Connect</button>
          @endif
        </div>

        @unless ($stripeConnectConfigured)
          <div class="note err" style="margin-top:14px;background:#FDECEC;border-color:#F5C2C2;color:#C62828"><span class="ms" style="color:#C62828">error</span>Stripe is not configured. Please contact support.</div>
        @endunless

        <div id="studioStripeStep" hidden style="margin-top:18px">
          <div style="margin-bottom:14px;padding-bottom:14px;border-bottom:1px solid var(--line)">
            <p style="font-size:15px;font-weight:700;margin:0">Complete your Stripe payout setup</p>
            <p class="muted" id="studioStripeStepDescription" style="font-size:13.5px;margin-top:6px">Add your details, verify your identity, and connect your bank account below.</p>
          </div>
          <div class="stripe-connect-shell">
            <div id="studioStripeConnectMount"></div>
          </div>
          <p id="studio_stripe_connect_error" class="field-err"></p>
          <p class="help" id="studioStripeConnectHint">Setup finishes automatically when all required steps are complete.</p>
          <button type="button" class="btn ghost" id="studioStripeStepBack" style="margin-top:10px"><span class="ms">arrow_back</span>Back</button>
        </div>
      </div>

      <div class="stripe-on" @unless($stripeConnected) hidden @endunless>
        <div class="row" style="gap:14px">
          <div class="ic" style="background:#E6F8EE;color:#00A650"><span class="ms">check</span></div>
          <div style="flex:1;min-width:0">
            <div class="row" style="gap:8px;flex-wrap:wrap">
              <b id="stripeConnectedLabel">Stripe · connected</b>
              <span class="pill g">Connected</span>
            </div>
            <div class="faint" style="font-size:12.5px">Payouts are sent automatically when funds become available.</div>
          </div>
        </div>
      </div>
    </div>
  </div>

  <div class="note"><span class="ms">info</span>Money from a booking is held until its cancellation window ends. Then your share and the artist's share are sent at the same time.</div>

  <div class="obfoot">
    <a class="btn ghost" href="{{ route('studio.onboarding.terms') }}"><span class="ms">arrow_back</span>Back</a>
    <div class="row" style="gap:0">
      <a class="btn" id="goDashboardBtn" href="{{ route('studio.dashboard') }}" @if($offersRevenueSplit && ! $stripeConnected) hidden @endif>Go to dashboard<span class="ms">arrow_forward</span></a>
    </div>
  </div>
@endsection

@push('scripts')
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script src="{{ asset('design/js/currencies.js') }}"></script>
<script>
(function () {
  var needsStripe = @json((bool) $offersRevenueSplit);
  var currencyByCountry = @json($currencyByCountry ?? []);
  var connected = @json((bool) $stripeConnected);
  var stripeConfigured = @json((bool) $stripeConnectConfigured);
  var dashboardUrl = @json(route('studio.dashboard'));
  var $ = window.jQuery;
  var goDashboardBtn = document.getElementById('goDashboardBtn');
  var currencyHelp = document.getElementById('currencyHelp');
  var csrf = document.querySelector('meta[name="csrf-token"]')?.content || '';

  function initSelect2(selector, options) {
    if (!$ || !$.fn.select2) return;
    var opts = options || {};
    $(selector).each(function () {
      var $el = $(this);
      if ($el.hasClass('select2-hidden-accessible')) return;
      var searchable = opts.searchable != null ? opts.searchable : $el.data('searchable');
      var cfg = {
        width: '100%',
        placeholder: opts.placeholder || $el.data('placeholder') || 'Select',
        allowClear: false,
        dropdownParent: $el.closest('.card, main').length ? $el.closest('.card, main') : $(document.body)
      };
      if (searchable === false || searchable === 0 || searchable === 'false') {
        cfg.minimumResultsForSearch = Infinity;
      }
      $el.select2(cfg);
    });
  }

  var currencyEl = document.getElementById('currency');
  if (currencyEl && typeof fillCurrencySelect === 'function') {
    fillCurrencySelect(currencyEl, @json($currentCurrency) || 'EUR');
  }
  initSelect2('#currency', { placeholder: 'Search and select currency', searchable: true });
  initSelect2('#business_country', { placeholder: 'Search and select country', searchable: true });
  initSelect2('#business_type', { placeholder: 'Select business type', searchable: false });

  function countryName(code) {
    var opt = document.querySelector('#business_country option[value="' + code + '"]');
    return opt ? opt.textContent.trim() : code;
  }

  function clearErrors() {
    ['currency_error', 'country_error', 'business_type_error', 'studio_stripe_connect_error'].forEach(function (id) {
      var el = document.getElementById(id);
      if (el) { el.textContent = ''; el.classList.remove('on'); }
    });
  }

  function showError(id, message) {
    var el = document.getElementById(id);
    if (el) { el.textContent = message || ''; el.classList.toggle('on', !!message); }
  }

  function syncCurrencyFromCountry(code, force) {
    if (!$ || !code) return;
    var next = currencyByCountry[String(code).toUpperCase()] || 'EUR';
    var $currency = $('#currency');
    if (force && $currency.find('option[value="' + next + '"]').length) {
      $currency.val(next).trigger('change');
    }
    if (currencyHelp) {
      currencyHelp.textContent = 'Set from your country (' + countryName(code) + '). Your earnings and reports show in this currency. Your Stripe bank account must be in the same currency.';
    }
  }

  if ($) {
    $('#business_country').on('change', function () {
      syncCurrencyFromCountry(this.value, true);
    });
  }

  function syncNext() {
    if (!goDashboardBtn) return;
    var ok = !needsStripe || connected;
    goDashboardBtn.hidden = !ok;
  }

  function showConnected() {
    connected = true;
    var off = document.querySelector('.stripe-off');
    var on = document.querySelector('.stripe-on');
    if (off) off.hidden = true;
    if (on) on.hidden = false;
    syncNext();
  }

  function readSetup() {
    return {
      currency: document.getElementById('currency')?.value || '',
      country: document.getElementById('business_country')?.value || '',
      business_type: document.getElementById('business_type')?.value || '',
      industry: 'tattoo_studio'
    };
  }

  function validateSetup() {
    clearErrors();
    var setup = readSetup();
    var ok = true;
    if (!setup.currency) { showError('currency_error', 'Please select a currency.'); ok = false; }
    if (!setup.country) { showError('country_error', 'Please select your business country.'); ok = false; }
    if (!setup.business_type) { showError('business_type_error', 'Please select a business type.'); ok = false; }
    return ok ? setup : null;
  }

  function updateStripeDescription(setup) {
    var desc = document.getElementById('studioStripeStepDescription');
    if (!desc || !setup) return;
    var typeLabel = setup.business_type === 'individual' ? 'individual' : 'company';
    desc.textContent = 'Complete Stripe onboarding for your ' + typeLabel + ' account in ' + countryName(setup.country) + '. You will verify your identity and connect your bank account.';
  }

  document.getElementById('studioStripeStepBack')?.addEventListener('click', function () {
    document.getElementById('studioStripeStep').hidden = true;
    document.getElementById('stripeConnectIntro').hidden = false;
    document.getElementById('studioSetupFields').hidden = false;
  });

  document.getElementById('goDashboardBtn')?.addEventListener('click', function (e) {
    e.preventDefault();
    if (goDashboardBtn.hidden) return;

    var setup = null;
    if (!connected) {
      setup = validateSetup();
      if (!setup) return;
    } else {
      setup = readSetup();
    }

    var defaultHtml = goDashboardBtn.innerHTML;
    goDashboardBtn.classList.add('disabled');
    goDashboardBtn.setAttribute('aria-disabled', 'true');
    goDashboardBtn.innerHTML = '<span class="ms">hourglass_top</span>Saving…';

    fetch(@json(route('studio.onboarding.payouts.update')), {
      method: 'POST',
      headers: {
        'X-CSRF-TOKEN': csrf,
        'X-Requested-With': 'XMLHttpRequest',
        'Accept': 'application/json',
        'Content-Type': 'application/json'
      },
      body: JSON.stringify(Object.assign({}, setup || {}, { complete_onboarding: 1 }))
    })
      .then(function (res) {
        return res.json().then(function (data) { return { ok: res.ok, data: data }; });
      })
      .then(function (result) {
        if (result.ok && result.data && result.data.success) {
          window.location.href = (result.data.redirect || dashboardUrl);
          return;
        }
        if (window.bpToast) window.bpToast((result.data && result.data.message) || 'Could not save.', true);
        goDashboardBtn.classList.remove('disabled');
        goDashboardBtn.setAttribute('aria-disabled', 'false');
        goDashboardBtn.innerHTML = defaultHtml;
      })
      .catch(function () {
        if (window.bpToast) window.bpToast('Something went wrong. Please try again.', true);
        goDashboardBtn.classList.remove('disabled');
        goDashboardBtn.setAttribute('aria-disabled', 'false');
        goDashboardBtn.innerHTML = defaultHtml;
      });
  });

  window.__studioPayoutSetup = {
    validateSetup: validateSetup,
    updateStripeDescription: updateStripeDescription,
    showConnected: showConnected,
    stripeConfigured: stripeConfigured,
    csrf: csrf
  };

  syncNext();
})();
</script>

@if ($stripeConnectConfigured && ! $stripeConnected)
<script type="module">
const publishableKey = @json($stripePublishableKey ?? '');
const sessionUrl = @json(route('studio.onboarding.payouts.stripe.session'));
const completeUrl = @json(route('studio.onboarding.payouts.stripe.complete'));
const stripeConnectLocale = @json($stripeConnectLocale ?? 'en-US');
const stripeConnectAppearance = @json(config('services.stripe.connect.appearance', []));

let connectInstance = null;
let stripeSessionData = null;
let onboardingMounted = false;
let completeTriggered = false;
let loadConnectAndInitialize = null;

function helpers() {
  return window.__studioPayoutSetup || {};
}

function csrfToken() {
  return helpers().csrf || document.querySelector('meta[name="csrf-token"]')?.content || '';
}

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
    } catch (err) {
      lastError = err;
    }
  }
  throw lastError || new Error('Could not load Stripe Connect.');
}

function showStripeError(message) {
  const el = document.getElementById('studio_stripe_connect_error');
  if (el) {
    el.textContent = message || '';
    el.classList.toggle('on', !!message);
  }
}

function showSetupError(id, message) {
  const el = document.getElementById(id);
  if (el) {
    el.textContent = message || '';
    el.classList.toggle('on', !!message);
  }
}

async function createSession(setup) {
  const res = await fetch(sessionUrl, {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json',
      'X-CSRF-TOKEN': csrfToken(),
      Accept: 'application/json'
    },
    body: JSON.stringify(setup)
  });
  const data = await res.json().catch(() => ({}));
  if (res.status === 422 && data.errors) {
    if (data.errors.currency?.[0]) showSetupError('currency_error', data.errors.currency[0]);
    if (data.errors.country?.[0]) showSetupError('country_error', data.errors.country[0]);
    if (data.errors.business_type?.[0]) showSetupError('business_type_error', data.errors.business_type[0]);
    throw new Error(data.message || 'Please check your answers and try again.');
  }
  if (!res.ok || !data.client_secret) throw new Error(data.message || 'Could not start Stripe onboarding.');
  stripeSessionData = data;
  return data;
}

async function finalize() {
  if (completeTriggered || !stripeSessionData?.account_id) return;
  completeTriggered = true;
  const res = await fetch(completeUrl, {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json',
      'X-CSRF-TOKEN': csrfToken(),
      Accept: 'application/json'
    },
    body: JSON.stringify({ account_id: stripeSessionData.account_id })
  });
  const data = await res.json().catch(() => ({}));
  if (!res.ok || !data.success) {
    completeTriggered = false;
    throw new Error(data.message || 'Could not save Stripe payout setup.');
  }
  if (typeof helpers().showConnected === 'function') helpers().showConnected();
  if (window.bpToast) window.bpToast('Stripe connected');
}

async function mountOnboarding() {
  const container = document.getElementById('studioStripeConnectMount');
  if (!publishableKey || !container || !stripeSessionData?.client_secret) {
    throw new Error('Stripe is not ready. Please try again.');
  }

  if (onboardingMounted) {
    container.innerHTML = '';
    onboardingMounted = false;
    connectInstance = null;
  }

  container.innerHTML = '<p class="help" style="padding:24px">Loading Stripe onboarding…</p>';
  const loadConnect = await ensureStripeConnectLoader();
  connectInstance = loadConnect({
    publishableKey,
    fetchClientSecret: async () => stripeSessionData.client_secret,
    locale: stripeConnectLocale || 'en-US',
    appearance: stripeConnectAppearance
  });
  connectInstance.update({ locale: stripeConnectLocale || 'en-US' });
  const accountOnboarding = connectInstance.create('account-onboarding');
  const collectionOptions = stripeSessionData.collection_options || {};
  accountOnboarding.setCollectionOptions({
    fields: collectionOptions.fields || 'eventually_due',
    futureRequirements: collectionOptions.futureRequirements || 'include',
    ...(collectionOptions.requirements ? { requirements: collectionOptions.requirements } : {})
  });
  accountOnboarding.setOnExit(async () => {
    document.getElementById('studioStripeConnectHint')?.classList.add('hidden');
    await new Promise((resolve) => setTimeout(resolve, 1500));
    try {
      await finalize();
    } catch (err) {
      completeTriggered = false;
      showStripeError(err.message || 'Could not complete payout setup.');
    }
  });
  container.innerHTML = '';
  container.appendChild(accountOnboarding);
  onboardingMounted = true;
}

function resetStripeStepUi() {
  const container = document.getElementById('studioStripeConnectMount');
  if (container) container.innerHTML = '';
  const step = document.getElementById('studioStripeStep');
  const intro = document.getElementById('stripeConnectIntro');
  const fields = document.getElementById('studioSetupFields');
  if (step) step.hidden = true;
  if (intro) intro.hidden = false;
  if (fields) fields.hidden = false;
}

document.getElementById('stripeConnectBtn')?.addEventListener('click', async () => {
  const api = helpers();
  const setup = typeof api.validateSetup === 'function' ? api.validateSetup() : null;
  if (!setup) return;
  if (typeof api.updateStripeDescription === 'function') api.updateStripeDescription(setup);

  const btn = document.getElementById('stripeConnectBtn');
  const original = btn?.innerHTML;
  if (btn) { btn.disabled = true; btn.innerHTML = 'Loading…'; }

  showStripeError('');
  completeTriggered = false;

  try {
    await createSession(setup);
    document.getElementById('stripeConnectIntro').hidden = true;
    document.getElementById('studioSetupFields').hidden = true;
    document.getElementById('studioStripeStep').hidden = false;
    await mountOnboarding();
  } catch (err) {
    resetStripeStepUi();
    showStripeError(err.message || 'Could not load Stripe onboarding.');
  } finally {
    if (btn) { btn.disabled = false; btn.innerHTML = original || 'Connect'; }
  }
});
</script>
@endif
@endpush
