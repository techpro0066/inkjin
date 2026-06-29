@php
  $showIrisCheckout = (bool) ($showIrisTab ?? false);
  $artistSupportsIris = (bool) ($artistSupportsIris ?? false);
  $renderDualCheckout = $showIrisCheckout || $artistSupportsIris;
@endphp
@if ($renderDualCheckout)
<script src="{{ asset('js/qrious.min.js') }}"></script>
<script>
(function () {
  var tabCard = document.getElementById('tabPayCard');
  var tabIris = document.getElementById('tabPayIris');
  var panelCard = document.getElementById('panelPayCard');
  var panelIris = document.getElementById('panelPayIris');
  var btnConfirmPay = document.getElementById('btnConfirmPay');
  var cardExtras = document.getElementById('panelPayCardExtras');
  var irisOrderLoaded = false;
  var irisPollTimer = null;
  var irisExpiryTimer = null;

  function setActiveTab(tab) {
    var isCard = tab === 'card';

    if (tabCard && tabIris) {
      tabCard.setAttribute('aria-selected', isCard ? 'true' : 'false');
      tabIris.setAttribute('aria-selected', isCard ? 'false' : 'true');
      tabCard.classList.toggle('border-primary', isCard);
      tabCard.classList.toggle('bg-primary', isCard);
      tabCard.classList.toggle('text-white', isCard);
      tabCard.classList.toggle('border-outline-variant/60', !isCard);
      tabCard.classList.toggle('text-on-surface-variant', !isCard);
      tabIris.classList.toggle('border-primary', !isCard);
      tabIris.classList.toggle('bg-primary', !isCard);
      tabIris.classList.toggle('text-white', !isCard);
      tabIris.classList.toggle('border-outline-variant/60', isCard);
      tabIris.classList.toggle('text-on-surface-variant', isCard);
    }

    if (panelCard) panelCard.classList.toggle('hidden', !isCard);
    if (panelIris) panelIris.classList.toggle('hidden', isCard);
    if (cardExtras) cardExtras.classList.toggle('hidden', !isCard);
    if (btnConfirmPay) btnConfirmPay.classList.toggle('hidden', !isCard);

    if (!isCard && !irisOrderLoaded && typeof window.startIrisQrPayment === 'function') {
      window.startIrisQrPayment();
    }
  }

  window.checkoutSetActivePayTab = setActiveTab;

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
        expiryEl.textContent = 'QR expired';
        clearIrisTimers();
        document.getElementById('irisQrRegenerate')?.classList.remove('hidden');
        showIrisStatus('Payment window expired. Regenerate the QR to try again.', true);
        return;
      }
      var mins = Math.floor(remainingMs / 60000);
      var secs = Math.floor((remainingMs % 60000) / 1000);
      expiryEl.textContent = 'QR expires in ' + mins + ':' + String(secs).padStart(2, '0');
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
          window.location.href = data.redirect_url || '/';
          return;
        }

        if (data.status === 'expired' || data.status === 'failed' || data.status === 'cancelled') {
          clearIrisTimers();
          document.getElementById('irisQrRegenerate')?.classList.remove('hidden');
          showIrisStatus('Payment ' + data.status + '. Please regenerate the QR.', true);
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
    await ensureQrLibrary();

    var canvas = document.getElementById('irisQrCanvas');
    if (!canvas || !window.QRious) {
      throw new Error('QR renderer is unavailable.');
    }

    new window.QRious({
      element: canvas,
      value: data.checkout_url,
      size: 260,
      background: '#ffffff',
      foreground: '#310f7a',
      level: 'M'
    });

    canvas.classList.remove('hidden');
    document.getElementById('irisQrRegenerate')?.classList.add('hidden');
    showIrisStatus('Waiting for payment… Scan the QR with your banking app.', false);
    startExpiryCountdown(data.expires_at);
    pollVivaStatus(data.order_code);
    irisOrderLoaded = true;
  }

  window.startIrisQrPayment = async function (forceNew) {
    if (!window.vivaOrderUrl) {
      showIrisStatus('IRIS payment is not configured.', true);
      return;
    }

    if (irisOrderLoaded && !forceNew) {
      return;
    }

    showIrisStatus('Preparing QR code…', false);
    document.getElementById('irisQrRegenerate')?.classList.add('hidden');

    try {
      var fetchOptions = {
        method: 'POST',
        headers: {
          'Accept': 'application/json',
          'Content-Type': 'application/json',
          'X-CSRF-TOKEN': window.vivaCsrfToken || ''
        },
        body: JSON.stringify(typeof window.vivaOrderBody === 'function' ? window.vivaOrderBody() : {})
      };

      var res = await fetch(window.vivaOrderUrl, fetchOptions);
      var data = await res.json();
      if (!res.ok || !data.checkout_url) {
        throw new Error(data.message || 'Unable to start IRIS payment.');
      }

      irisOrderLoaded = false;
      await renderIrisOrder(data);
    } catch (e) {
      irisOrderLoaded = false;
      showIrisStatus(e.message || 'Unable to start IRIS payment.', true);
    }
  };

  document.getElementById('irisQrRegenerate')?.addEventListener('click', function () {
    irisOrderLoaded = false;
    clearIrisTimers();
    window.startIrisQrPayment(true);
  });
})();
</script>
@endif
