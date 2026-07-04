<script>
(function () {
  var googlePaymentRequest = null;
  var applePaymentRequest = null;

  function getWalletConfig() {
    return window.checkoutStripeWalletConfig || null;
  }

  function getAmountCents() {
    var cfg = getWalletConfig();
    if (!cfg || typeof cfg.getAmountCents !== 'function') return 0;
    return Math.max(0, parseInt(cfg.getAmountCents(), 10) || 0);
  }

  function buildPaymentRequest(stripe, cfg, amount) {
    return stripe.paymentRequest({
      country: (cfg.country || 'GR').toUpperCase(),
      currency: String(cfg.currency || 'eur').toLowerCase(),
      total: {
        label: cfg.label || 'Total',
        amount: amount,
      },
      requestPayerName: true,
      requestPayerEmail: true,
    });
  }

  function showWalletTab(tabId, show, activeTabKey) {
    var tab = document.getElementById(tabId);
    if (!tab) return;
    tab.classList.toggle('hidden', !show);
    if (!show && window.checkoutGetActivePayTab && window.checkoutGetActivePayTab() === activeTabKey) {
      window.checkoutSetActivePayTab('card');
    }
  }

  function setWalletUnavailable(unavailableId, mountId, visible) {
    var el = document.getElementById(unavailableId);
    if (el) el.classList.toggle('hidden', !visible);
    var mount = document.getElementById(mountId);
    if (mount) mount.classList.toggle('hidden', visible);
  }

  async function handleWalletPayment(ev, stripe, cfg, policyHintId) {
    var policyHint = document.getElementById(policyHintId);
    if (policyHint) policyHint.classList.add('hidden');

    if (typeof cfg.isPolicyAccepted === 'function' && !cfg.isPolicyAccepted()) {
      if (policyHint) policyHint.classList.remove('hidden');
      ev.complete('fail');
      return;
    }

    try {
      var clientSecret = await cfg.getClientSecret(ev);
      if (!clientSecret) {
        throw new Error('Unable to initialize payment.');
      }

      var confirmResult = await stripe.confirmCardPayment(clientSecret, {
        payment_method: ev.paymentMethod.id,
      }, { handleActions: true });

      if (confirmResult.error) {
        throw new Error(confirmResult.error.message || 'Payment failed.');
      }

      if (!confirmResult.paymentIntent || confirmResult.paymentIntent.status !== 'succeeded') {
        throw new Error('Payment was not completed.');
      }

      ev.complete('success');

      if (typeof cfg.onSuccess === 'function') {
        await cfg.onSuccess(confirmResult.paymentIntent.id, ev);
      }
    } catch (err) {
      ev.complete('fail');
      if (typeof cfg.onError === 'function') {
        cfg.onError(err);
      }
    }
  }

  window.checkoutUpdateWalletAmounts = function () {
    var cfg = getWalletConfig();
    var amount = getAmountCents();
    if (amount < 30) return;
    var total = {
      label: (cfg && cfg.label) ? cfg.label : 'Total',
      amount: amount,
    };
    if (googlePaymentRequest) googlePaymentRequest.update({ total: total });
    if (applePaymentRequest) applePaymentRequest.update({ total: total });
  };

  // Backward compatible alias
  window.checkoutUpdateGooglePayAmount = window.checkoutUpdateWalletAmounts;

  function initWallet(stripe, elements, cfg, options) {
    if (window[options.mountedFlag]) return;

    var amount = getAmountCents();
    if (amount < 30) {
      showWalletTab(options.tabId, false, options.tabKey);
      return;
    }

    var paymentRequest = buildPaymentRequest(stripe, cfg, amount);
    if (options.storeKey === 'google') googlePaymentRequest = paymentRequest;
    if (options.storeKey === 'apple') applePaymentRequest = paymentRequest;

    paymentRequest.canMakePayment().then(function (result) {
      var canUse = options.storeKey === 'google'
        ? !!(result && (result.googlePay || result.googlepay))
        : !!(result && result[options.canMakePaymentKey]);
      showWalletTab(options.tabId, canUse, options.tabKey);

      if (!canUse) {
        setWalletUnavailable(options.unavailableId, options.mountId, false);
        return;
      }

      var button = elements.create('paymentRequestButton', {
        paymentRequest: paymentRequest,
        style: {
          paymentRequestButton: options.buttonStyle,
        },
      });

      var mount = document.getElementById(options.mountId);
      if (!mount) return;

      button.mount('#' + options.mountId);
      setWalletUnavailable(options.unavailableId, options.mountId, false);
      window[options.mountedFlag] = true;
    }).catch(function () {
      showWalletTab(options.tabId, false, options.tabKey);
    });

    paymentRequest.on('paymentmethod', function (ev) {
      handleWalletPayment(ev, stripe, cfg, options.policyHintId);
    });
  }

  window.checkoutInitStripeWallets = function (stripe, elements) {
    var cfg = getWalletConfig();
    if (!stripe || !elements || !cfg || typeof cfg.getClientSecret !== 'function') {
      showWalletTab('tabPayGooglePay', false, 'google_pay');
      showWalletTab('tabPayApplePay', false, 'apple_pay');
      return;
    }

    initWallet(stripe, elements, cfg, {
      storeKey: 'google',
      tabId: 'tabPayGooglePay',
      tabKey: 'google_pay',
      mountId: 'googlePayButtonMount',
      unavailableId: 'googlePayUnavailable',
      policyHintId: 'googlePayPolicyHint',
      mountedFlag: '_checkoutGooglePayMounted',
      canMakePaymentKey: 'googlePay',
      buttonStyle: { type: 'default', theme: 'dark', height: '48px' },
    });

    initWallet(stripe, elements, cfg, {
      storeKey: 'apple',
      tabId: 'tabPayApplePay',
      tabKey: 'apple_pay',
      mountId: 'applePayButtonMount',
      unavailableId: 'applePayUnavailable',
      policyHintId: 'applePayPolicyHint',
      mountedFlag: '_checkoutApplePayMounted',
      canMakePaymentKey: 'applePay',
      buttonStyle: { type: 'buy', theme: 'black', height: '48px' },
    });
  };

  // Backward compatible alias
  window.checkoutInitGooglePay = window.checkoutInitStripeWallets;
})();
</script>
