<script>
(function () {
  var tabCard = document.getElementById('tabPayCard');
  var tabIris = document.getElementById('tabPayIris');
  var panelCard = document.getElementById('panelPayCard');
  var panelIris = document.getElementById('panelPayIris');
  var btnConfirmPay = document.getElementById('btnConfirmPay');
  var cardExtras = document.getElementById('panelPayCardExtras');
  var activePayTab = 'card';
  var irisOrderLoaded = false;
  var irisPollTimer = null;
  var irisExpiryTimer = null;
  var irisOrderCode = null;
  var irisCheckoutUrl = null;

  function styleTab(btn, isActive) {
    if (!btn) return;
    btn.setAttribute('aria-selected', isActive ? 'true' : 'false');
    btn.classList.toggle('border-primary', isActive);
    btn.classList.toggle('bg-primary', isActive);
    btn.classList.toggle('text-white', isActive);
    btn.classList.toggle('border-outline-variant/60', !isActive);
    btn.classList.toggle('text-on-surface-variant', !isActive);
  }

  function isMobileCheckout() {
    return window.matchMedia('(max-width: 768px)').matches
      || /Android|webOS|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/i.test(navigator.userAgent || '');
  }

  function setIrisRegenerateLabel() {
    var btn = document.getElementById('irisQrRegenerate');
    if (!btn) return;
    btn.textContent = isMobileCheckout() ? 'Try again' : 'Regenerate QR';
  }

  function syncIrisIntroCopy() {
    var mobile = isMobileCheckout();
    document.getElementById('irisIntroDesktop')?.classList.toggle('hidden', mobile);
    document.getElementById('irisIntroMobile')?.classList.toggle('hidden', !mobile);
  }

  function showIrisDesktopQr(show) {
    document.getElementById('irisQrDesktopWrap')?.classList.toggle('hidden', !show);
    var canvas = document.getElementById('irisQrCanvas');
    if (canvas) canvas.classList.toggle('hidden', !show);
  }

  function showIrisMobilePayButton(show, checkoutUrl) {
    var btn = document.getElementById('irisMobilePayBtn');
    if (!btn) return;
    if (show && checkoutUrl) {
      btn.href = checkoutUrl;
      btn.classList.remove('hidden');
    } else {
      btn.href = '#';
      btn.classList.add('hidden');
    }
  }

  function setActiveTab(tab) {
    if (tab === 'iris' && tabIris && tabIris.classList.contains('hidden')) {
      tab = 'card';
    }

    activePayTab = tab;
    var isCard = tab === 'card';
    var isIris = tab === 'iris';

    styleTab(tabCard, isCard);
    styleTab(tabIris, isIris);

    if (panelCard) panelCard.classList.toggle('hidden', !isCard);
    if (panelIris) panelIris.classList.toggle('hidden', !isIris);
    if (cardExtras) cardExtras.classList.toggle('hidden', isIris);
    if (btnConfirmPay) btnConfirmPay.classList.toggle('hidden', !isCard);

    if (isIris && !irisOrderLoaded && typeof window.startIrisQrPayment === 'function') {
      window.startIrisQrPayment();
    }
  }

  window.checkoutSetActivePayTab = setActiveTab;
  window.checkoutGetActivePayTab = function () { return activePayTab; };

  tabCard?.addEventListener('click', function () { setActiveTab('card'); });
  tabIris?.addEventListener('click', function () { setActiveTab('iris'); });

  function showIrisStatus(message, isError) {
    var el = document.getElementById('irisQrStatus');
    if (!el) return;
    el.textContent = message || '';
    el.classList.toggle('hidden', !message);
    el.classList.toggle('text-error', !!isError);
    el.classList.toggle('text-on-surface-variant', !isError);
  }

  function clearPollTimer() {
    if (irisPollTimer) {
      clearInterval(irisPollTimer);
      irisPollTimer = null;
    }
  }

  function clearExpiryTimer() {
    if (irisExpiryTimer) {
      clearInterval(irisExpiryTimer);
      irisExpiryTimer = null;
    }
  }

  function clearIrisTimers() {
    clearPollTimer();
    clearExpiryTimer();
  }

  function startExpiryCountdown(expiresAtIso) {
    var expiryEl = document.getElementById('irisQrExpiry');
    if (!expiryEl || !expiresAtIso) return;

    clearExpiryTimer();
    expiryEl.classList.remove('hidden');

    function tick() {
      var remainingMs = new Date(expiresAtIso).getTime() - Date.now();
      if (remainingMs <= 0) {
        expiryEl.textContent = isMobileCheckout() ? 'Payment link expired' : 'QR expired';
        clearIrisTimers();
        document.getElementById('irisQrRegenerate')?.classList.remove('hidden');
        showIrisMobilePayButton(false);
        showIrisStatus(
          isMobileCheckout()
            ? 'Payment window expired. Tap try again to reopen IRIS.'
            : 'Payment window expired. Regenerate the QR to try again.',
          true
        );
        return;
      }
      var mins = Math.floor(remainingMs / 60000);
      var secs = Math.floor((remainingMs % 60000) / 1000);
      expiryEl.textContent = (isMobileCheckout() ? 'Link expires in ' : 'QR expires in ') + mins + ':' + String(secs).padStart(2, '0');
    }

    tick();
    irisExpiryTimer = setInterval(tick, 1000);
  }

  function pollVivaStatus(orderCode) {
    if (!window.vivaStatusUrl) return;

    clearPollTimer();

    irisPollTimer = setInterval(async function () {
      try {
        var extra = typeof window.vivaStatusExtraQuery === 'function' ? window.vivaStatusExtraQuery() : '';
        var url = window.vivaStatusUrl + (window.vivaStatusUrl.indexOf('?') >= 0 ? '&' : '?') + 'order_code=' + encodeURIComponent(orderCode);
        if (extra) {
          url += '&' + extra;
        }
        var res = await fetch(url, { headers: { 'Accept': 'application/json' } });
        var data = await res.json();
        if (!res.ok) return;

        if (data.status === 'paid') {
          clearIrisTimers();
          showIrisStatus('Payment confirmed. Redirecting…', false);
          if (typeof window.clearBookingDraftSession === 'function') {
            window.clearBookingDraftSession();
          }
          window.location.href = data.redirect_url || '/';
          return;
        }

        if (data.status === 'expired' || data.status === 'failed' || data.status === 'cancelled') {
          clearIrisTimers();
          irisOrderLoaded = false;
          showIrisMobilePayButton(false);
          document.getElementById('irisQrRegenerate')?.classList.remove('hidden');
          showIrisStatus(
            isMobileCheckout()
              ? 'Payment ' + data.status + '. Please tap try again.'
              : 'Payment ' + data.status + '. Please regenerate the QR.',
            true
          );
        }
      } catch (e) {
        // Keep polling
      }
    }, 5000);

    setTimeout(function () {
      clearPollTimer();
    }, 600000);
  }

  function ensureQrLibrary() {
    if (window.QRious) {
      return Promise.resolve();
    }

    return new Promise(function (resolve, reject) {
      var script = document.createElement('script');
      script.src = @json(asset('js/qrious.min.js'));
      script.onload = function () { resolve(); };
      script.onerror = function () { reject(new Error('QR library failed to load.')); };
      document.head.appendChild(script);
    });
  }

  async function renderIrisOrder(data) {
    irisOrderCode = data.order_code || null;
    irisCheckoutUrl = data.checkout_url || null;
    syncIrisIntroCopy();
    setIrisRegenerateLabel();

    if (isMobileCheckout()) {
      showIrisDesktopQr(false);
      showIrisMobilePayButton(true, irisCheckoutUrl);
      document.getElementById('irisQrRegenerate')?.classList.add('hidden');
      showIrisStatus('Tap below to open IRIS in your banking app.', false);
      startExpiryCountdown(data.expires_at);
      pollVivaStatus(data.order_code);
      irisOrderLoaded = true;
      return;
    }

    await ensureQrLibrary();

    var canvas = document.getElementById('irisQrCanvas');
    if (!canvas || !window.QRious) {
      throw new Error('QR renderer is unavailable.');
    }

    showIrisMobilePayButton(false);
    showIrisDesktopQr(true);
    document.getElementById('irisQrRegenerate')?.classList.add('hidden');

    new window.QRious({
      element: canvas,
      value: data.checkout_url,
      size: 260,
      background: '#ffffff',
      foreground: '#310f7a',
      level: 'M'
    });

    canvas.classList.remove('hidden');
    showIrisStatus('Waiting for payment… Scan the QR with your banking app.', false);
    startExpiryCountdown(data.expires_at);
    pollVivaStatus(data.order_code);
    irisOrderLoaded = true;
  }

  window.startIrisQrPayment = async function (forceNew) {
    if (!window.vivaOrderUrl || !tabIris) {
      showIrisStatus('IRIS payment is not configured.', true);
      return;
    }

    if (irisOrderLoaded && !forceNew) {
      return;
    }

    syncIrisIntroCopy();
    showIrisStatus(isMobileCheckout() ? 'Preparing IRIS redirect…' : 'Preparing QR code…', false);
    document.getElementById('irisQrRegenerate')?.classList.add('hidden');
    showIrisMobilePayButton(false);
    showIrisDesktopQr(false);

    try {
      var res = await fetch(window.vivaOrderUrl, {
        method: 'POST',
        headers: {
          'Accept': 'application/json',
          'Content-Type': 'application/json',
          'X-CSRF-TOKEN': window.vivaCsrfToken || '',
          'X-Requested-With': 'XMLHttpRequest'
        },
        credentials: 'same-origin',
        body: JSON.stringify(typeof window.vivaOrderBody === 'function' ? window.vivaOrderBody() : {})
      });
      var data = await res.json();
      if (!res.ok || !data.checkout_url) {
        throw new Error(data.message || 'Unable to start IRIS payment.');
      }

      irisOrderLoaded = false;
      clearIrisTimers();
      await renderIrisOrder(data);
    } catch (e) {
      irisOrderLoaded = false;
      var message = e && e.message ? e.message : 'Unable to start IRIS payment.';
      if (message === 'NetworkError when attempting to fetch resource.' || message === 'Failed to fetch') {
        message = 'Could not reach the payment server. Check your connection and try again.';
      }
      showIrisStatus(message, true);
    }
  };

  document.getElementById('irisQrRegenerate')?.addEventListener('click', function () {
    irisOrderLoaded = false;
    irisOrderCode = null;
    irisCheckoutUrl = null;
    clearIrisTimers();
    window.startIrisQrPayment(true);
  });

  setIrisRegenerateLabel();
  syncIrisIntroCopy();
  setActiveTab('card');
})();
</script>
