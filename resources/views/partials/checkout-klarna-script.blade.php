<script>
(function () {
  function formatMoney(cents, currency) {
    var amount = (Number(cents) || 0) / 100;
    var code = String(currency || 'eur').toUpperCase();
    try {
      return new Intl.NumberFormat(undefined, { style: 'currency', currency: code }).format(amount);
    } catch (e) {
      return (code === 'EUR' ? '€' : code + ' ') + amount.toFixed(2);
    }
  }

  function updateKlarnaAmountLabel() {
    var label = document.getElementById('klarnaAmountLabel');
    if (!label) return;
    var cfg = window.checkoutKlarnaConfig || {};
    var cents = typeof cfg.getAmountCents === 'function' ? cfg.getAmountCents() : (cfg.amountCents || 0);
    var currency = typeof cfg.getCurrency === 'function' ? cfg.getCurrency() : (cfg.currency || 'eur');
    var installment = Math.ceil((Number(cents) || 0) / 3);
    label.textContent = '3 payments of ' + formatMoney(installment, currency);
  }

  function setKlarnaError(message) {
    var el = document.getElementById('klarnaFormError');
    if (!el) return;
    el.textContent = message || '';
    el.classList.toggle('hidden', !message);
  }

  function readBillingDetails() {
    var name = String(document.getElementById('inputKlarnaName')?.value || '').trim();
    var email = String(document.getElementById('inputKlarnaEmail')?.value || '').trim();
    var country = String(document.getElementById('inputKlarnaCountry')?.value || 'GR').trim().toUpperCase();
    return { name: name, email: email, address: { country: country } };
  }

  function validateBillingDetails(details) {
    if (!details.name || details.name.length < 2) return 'Please enter your full name.';
    if (!details.email || !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(details.email)) return 'Please enter a valid email.';
    if (!details.address || !details.address.country) return 'Please select your country.';
    return '';
  }

  function buildReturnUrl() {
    var cfg = window.checkoutKlarnaConfig || {};
    if (typeof cfg.getReturnUrl === 'function') {
      return cfg.getReturnUrl();
    }
    if (cfg.returnUrl) return cfg.returnUrl;
    var url = new URL(window.location.href);
    url.searchParams.delete('payment_intent');
    url.searchParams.delete('payment_intent_client_secret');
    url.searchParams.delete('redirect_status');
    url.searchParams.set('klarna', 'return');
    return url.toString();
  }

  async function startKlarnaPayment() {
    var cfg = window.checkoutKlarnaConfig || {};
    var btn = document.getElementById('btnConfirmKlarna');
    setKlarnaError('');

    if (typeof cfg.isPolicyAccepted === 'function' && !cfg.isPolicyAccepted()) {
      setKlarnaError('Please accept the cancellation policy and terms.');
      if (typeof window.expandCancellationPolicy === 'function') window.expandCancellationPolicy();
      return;
    }

    var billing = readBillingDetails();
    var validationError = validateBillingDetails(billing);
    if (validationError) {
      setKlarnaError(validationError);
      return;
    }

    if (!cfg.getStripe || typeof cfg.getClientSecret !== 'function') {
      setKlarnaError('Klarna payment is not configured.');
      return;
    }

    var stripe = cfg.getStripe();
    if (!stripe) {
      setKlarnaError('Stripe is not ready yet. Please try again.');
      return;
    }

    var originalLabel = btn ? btn.textContent : '';
    if (btn) {
      btn.disabled = true;
      btn.textContent = 'Redirecting to Klarna…';
    }

    try {
      if (typeof cfg.beforeRedirect === 'function') {
        await cfg.beforeRedirect(billing);
      }

      var secretResult = await cfg.getClientSecret(billing);
      var clientSecret = typeof secretResult === 'string'
        ? secretResult
        : (secretResult && secretResult.client_secret);
      if (!clientSecret) {
        throw new Error('Unable to initialize Klarna payment.');
      }

      var confirmResult = await stripe.confirmKlarnaPayment(clientSecret, {
        payment_method: {
          billing_details: billing
        },
        return_url: buildReturnUrl()
      });

      if (confirmResult.error) {
        throw new Error(confirmResult.error.message || 'Klarna payment failed.');
      }
    } catch (error) {
      setKlarnaError(error.message || 'Klarna payment failed.');
      if (btn) {
        btn.disabled = false;
        btn.textContent = originalLabel || 'Pay with Klarna';
      }
      if (typeof cfg.onError === 'function') cfg.onError(error);
    }
  }

  async function handleKlarnaReturn() {
    var params = new URLSearchParams(window.location.search);
    var clientSecret = params.get('payment_intent_client_secret');
    var redirectStatus = params.get('redirect_status');
    var isKlarnaReturn = params.get('klarna') === 'return' || !!clientSecret;
    if (!isKlarnaReturn || !clientSecret) return false;

    var cfg = window.checkoutKlarnaConfig || {};
    if (!cfg.getStripe || typeof cfg.onSuccess !== 'function') return false;

    var stripe = cfg.getStripe();
    if (!stripe) return false;

    if (typeof window.checkoutSetActivePayTab === 'function') {
      window.checkoutSetActivePayTab('klarna');
    }

    if (typeof cfg.onReturnStart === 'function') {
      try { cfg.onReturnStart(); } catch (e) {}
    }

    var btn = document.getElementById('btnConfirmKlarna');
    if (btn) {
      btn.disabled = true;
      btn.textContent = 'Confirming Klarna…';
    }
    setKlarnaError('');

    try {
      if (redirectStatus && redirectStatus !== 'succeeded' && redirectStatus !== 'pending') {
        throw new Error('Klarna payment was not completed. Please try again.');
      }

      var retrieved = await stripe.retrievePaymentIntent(clientSecret);
      var intent = retrieved.paymentIntent;
      if (!intent) throw new Error('Unable to verify Klarna payment.');

      if (intent.status !== 'succeeded' && intent.status !== 'requires_capture') {
        throw new Error('Klarna payment was not completed. Please try again.');
      }

      await cfg.onSuccess(intent.id, intent);

      try {
        var clean = new URL(window.location.href);
        clean.searchParams.delete('payment_intent');
        clean.searchParams.delete('payment_intent_client_secret');
        clean.searchParams.delete('redirect_status');
        clean.searchParams.delete('klarna');
        window.history.replaceState({}, document.title, clean.pathname + clean.search + clean.hash);
      } catch (e) {}

      return true;
    } catch (error) {
      setKlarnaError(error.message || 'Klarna payment failed.');
      if (btn) {
        btn.disabled = false;
        btn.textContent = 'Pay with Klarna';
      }
      if (typeof cfg.onError === 'function') cfg.onError(error);
      return false;
    }
  }

  window.checkoutUpdateKlarnaAmount = updateKlarnaAmountLabel;
  window.checkoutStartKlarnaPayment = startKlarnaPayment;
  window.checkoutHandleKlarnaReturn = handleKlarnaReturn;

  document.getElementById('btnConfirmKlarna')?.addEventListener('click', function () {
    startKlarnaPayment();
  });

  ['inputKlarnaName', 'inputKlarnaEmail', 'inputKlarnaCountry'].forEach(function (id) {
    document.getElementById(id)?.addEventListener('input', function () { setKlarnaError(''); });
    document.getElementById(id)?.addEventListener('change', function () { setKlarnaError(''); });
  });

  updateKlarnaAmountLabel();

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', function () {
      updateKlarnaAmountLabel();
      handleKlarnaReturn();
    });
  } else {
    handleKlarnaReturn();
  }
})();
</script>
