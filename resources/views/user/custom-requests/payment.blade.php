@extends('layouts.user_dashboard_layout')

@section('title', 'Complete payment')

@section('styles')
<style>
  .info-tooltip { position: relative; display: inline-flex; cursor: help; }
  .info-tooltip .tooltip-text {
    visibility: hidden; opacity: 0; position: absolute; bottom: calc(100% + 8px); left: 50%;
    transform: translateX(-50%); background: #322f36; color: white; padding: 8px 12px;
    border-radius: 8px; font-size: 0.75rem; width: 220px; text-align: center; z-index: 10;
  }
  .info-tooltip:hover .tooltip-text { visibility: visible; opacity: 1; }
</style>
@endsection

@section('content')
<main class="main-content flex-1 min-h-screen">
  <div class="p-6 md:p-10 lg:p-12 max-w-5xl mx-auto">
    <a href="{{ route('user.custom-requests.confirm-times', ['customRequest' => $customRequest, 'from_payment' => 1]) }}" class="inline-flex items-center gap-1 text-sm text-on-surface-variant hover:text-primary mb-6 transition-colors">
      <span class="material-symbols-outlined text-[18px]">arrow_back</span> Back to times
    </a>

    @if (session('viva_error'))
      <div class="mb-6 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">{{ session('viva_error') }}</div>
    @endif

    @if (session('success'))
      <div class="mb-6 rounded-xl border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-800">{{ session('success') }}</div>
    @endif

    <div class="flex flex-col lg:flex-row gap-6">
      <div class="flex-1 lg:order-1">
        <h2 class="text-xl font-bold mb-1 flex items-center gap-2">
          <span class="material-symbols-outlined text-[22px] text-primary">lock</span> Secure Payment
        </h2>
        <p class="text-sm text-on-surface-variant mb-2">{{ $customRequest->referenceLabel() }}</p>
        <p class="text-sm text-on-surface-variant mb-6">Pay your deposit to confirm this custom tattoo booking with {{ $artistName }}.</p>

        @include('partials.checkout-payment-tabs', [
          'showIrisTab' => $showIrisTab ?? false,
          'artistSupportsIris' => $artistSupportsIris ?? false,
        ])

        <div id="panelPayCard">
        <div class="bg-white rounded-2xl border border-outline-variant/20 p-6 mb-6">
          <div class="space-y-4">
            <div>
              <label class="text-xs font-semibold text-on-surface-variant mb-1.5 block">Card Number</label>
              <div id="stripeCardNumber" class="w-full border border-outline-variant/30 bg-white rounded-xl px-4 py-3 text-sm min-h-[44px]"></div>
            </div>
            <div class="grid grid-cols-2 gap-4">
              <div>
                <label class="text-xs font-semibold text-on-surface-variant mb-1.5 block">Expiry</label>
                <div id="stripeCardExpiry" class="w-full border border-outline-variant/30 bg-white rounded-xl px-4 py-3 text-sm min-h-[44px]"></div>
              </div>
              <div>
                <label class="text-xs font-semibold text-on-surface-variant mb-1.5 block">CVC</label>
                <div id="stripeCardCvc" class="w-full border border-outline-variant/30 bg-white rounded-xl px-4 py-3 text-sm min-h-[44px]"></div>
              </div>
            </div>
            <div>
              <label class="text-xs font-semibold text-on-surface-variant mb-1.5 block" for="inputCardName">Cardholder Name</label>
              <input type="text" id="inputCardName" class="w-full border border-outline-variant/30 bg-white rounded-xl px-4 py-3 text-sm focus:outline-none focus:ring-2 focus:ring-primary/30" placeholder="Name on card">
            </div>
          </div>
        </div>
        </div>

        @include('partials.checkout-iris-panel', [
          'showIrisTab' => $showIrisTab ?? false,
        ])

        <div id="panelPayCardExtras">
        @include('partials.artist-cancellation-policy', ['userDetail' => $userDetail])

        <label class="flex items-start gap-2 mb-4 cursor-pointer">
          <input type="checkbox" id="agreePolicy" class="mt-0.5 accent-primary">
          <span class="text-xs text-on-surface-variant">
            I agree to the
            <a href="javascript:void(0)" onclick="event.preventDefault(); expandCancellationPolicy();" class="text-primary underline">cancellation policy</a>.
          </span>
        </label>

        <p id="paymentError" class="text-sm text-error hidden mb-3"></p>

        <button type="button" id="btnConfirmPay" disabled class="w-full py-4 rounded-xl font-bold text-white bg-primary disabled:opacity-40 disabled:cursor-not-allowed hover:bg-primary-container transition-all text-base shadow-lg shadow-primary/20">
          Confirm &amp; Pay <span id="btnPayTotalAmount">€{{ number_format($totals['total_due'], 2) }}</span>
        </button>
        </div>
      </div>

      <div class="lg:w-[340px] lg:order-2 shrink-0">
        @include('user.custom-requests.partials.booking-summary', [
          'customRequest' => $customRequest,
          'artistName' => $artistName,
          'showConsultRow' => $showConsultRow ?? false,
          'consultDateTime' => $consultDateTime ?? null,
          'sessionDateTimeLabel' => $sessionDateTimeLabel ?? 'Session',
          'sessionDateTime' => $sessionDateTime,
          'durationLabel' => $durationLabel,
          'sessionsLabel' => $sessionsLabel,
          'priceEstimateLabel' => $priceEstimateLabel,
          'depositLabel' => $depositLabel,
          'balanceLabel' => $balanceLabel,
          'totals' => $totals,
        ])
      </div>
    </div>
  </div>
</main>
@endsection

@section('scripts')
<script src="https://js.stripe.com/v3/"></script>
@include('partials.checkout-stripe-wallets')
<script>
(function() {
  var csrfToken = @json(csrf_token());
  var stripePublishableKey = @json($stripePublishableKey);
  var intentUrl = @json(route('user.custom-requests.payment.intent', $customRequest));
  var confirmUrl = @json(route('user.custom-requests.payment.confirm', $customRequest));
  window.vivaOrderUrl = @json(route('user.custom-requests.payment.viva.order', $customRequest));
  window.vivaStatusUrl = @json(route('user.custom-requests.payment.viva.status', $customRequest));
  window.vivaCsrfToken = csrfToken;
  window.vivaOrderBody = function () { return {}; };
  var payButtonLabel = @json('€' . number_format($totals['total_due'], 2));
  var stripe = null, stripeElements = null, stripeCardNumber = null, stripeCardExpiry = null, stripeCardCvc = null;
  var cardComplete = { number: false, expiry: false, cvc: false };

  function mountStripe() {
    if (!stripePublishableKey || typeof Stripe === 'undefined' || stripeCardNumber) return;
    stripe = Stripe(stripePublishableKey);
    stripeElements = stripe.elements();
    var style = { base: { fontSize: '14px', color: '#1c1b21', fontFamily: 'Plus Jakarta Sans, system-ui, sans-serif', '::placeholder': { color: '#7a7583' } } };
    stripeCardNumber = stripeElements.create('cardNumber', { style: style });
    stripeCardExpiry = stripeElements.create('cardExpiry', { style: style });
    stripeCardCvc = stripeElements.create('cardCvc', { style: style });
    stripeCardNumber.mount('#stripeCardNumber');
    stripeCardExpiry.mount('#stripeCardExpiry');
    stripeCardCvc.mount('#stripeCardCvc');
    function onChange(e) {
      if (e.elementType === 'cardNumber') cardComplete.number = e.complete;
      if (e.elementType === 'cardExpiry') cardComplete.expiry = e.complete;
      if (e.elementType === 'cardCvc') cardComplete.cvc = e.complete;
      checkReady();
    }
    stripeCardNumber.on('change', onChange);
    stripeCardExpiry.on('change', onChange);
    stripeCardCvc.on('change', onChange);

    if (typeof window.checkoutInitStripeWallets === 'function') {
      window.checkoutInitStripeWallets(stripe, stripeElements);
    }
  }
  function checkReady() {
    var name = String(document.getElementById('inputCardName').value || '').trim().length > 1;
    var agreed = document.getElementById('agreePolicy').checked;
    document.getElementById('btnConfirmPay').disabled = !(name && agreed && cardComplete.number && cardComplete.expiry && cardComplete.cvc);
  }
  document.getElementById('inputCardName').addEventListener('input', checkReady);
  document.getElementById('agreePolicy').addEventListener('change', checkReady);
  function showError(msg) {
    var el = document.getElementById('paymentError');
    el.textContent = msg || '';
    el.classList.toggle('hidden', !msg);
  }
  function expandCancellationPolicy() {
    var content = document.getElementById('cancellationPolicyContent');
    if (content && content.classList.contains('hidden')) {
      if (typeof toggleCancellationPolicy === 'function') toggleCancellationPolicy();
    }
    document.getElementById('cancellationPolicySection')?.scrollIntoView({ behavior: 'smooth', block: 'center' });
  }
  window.expandCancellationPolicy = expandCancellationPolicy;

  document.getElementById('btnConfirmPay').addEventListener('click', async function() {
    var btn = this;
    if (btn.disabled) return;
    showError('');
    btn.disabled = true;
    btn.textContent = 'Processing…';
    try {
      mountStripe();
      var res = await fetch(intentUrl, {
        method: 'POST',
        headers: { 'Accept': 'application/json', 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken },
        body: JSON.stringify({ cardholder_name: document.getElementById('inputCardName').value.trim() })
      });
      var data = await res.json();
      if (!res.ok || !data.client_secret) throw new Error(data.message || 'Unable to start payment.');
      var result = await stripe.confirmCardPayment(data.client_secret, {
        payment_method: { card: stripeCardNumber, billing_details: { name: document.getElementById('inputCardName').value.trim() } }
      });
      if (result.error) throw new Error(result.error.message || 'Payment failed.');
      var savedRes = await fetch(confirmUrl, {
        method: 'POST',
        headers: { 'Accept': 'application/json', 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken },
        body: JSON.stringify({ payment_intent_id: result.paymentIntent.id })
      });
      var saved = await savedRes.json();
      if (!savedRes.ok || !saved.saved) throw new Error(saved.message || 'Unable to save booking.');
      window.location.href = saved.redirect_url || @json(route('user.bookings.index'));
    } catch (e) {
      showError(e.message || 'Payment failed.');
      btn.disabled = false;
      btn.innerHTML = 'Confirm &amp; Pay <span id="btnPayTotalAmount">' + payButtonLabel + '</span>';
      checkReady();
    }
  });

  window.checkoutStripeWalletConfig = {
    country: @json(strtoupper($userDetail->payout_bank_country ?? 'GR')),
    currency: 'eur',
    label: @json('Custom request #' . $customRequest->id),
    getAmountCents: function () {
      return Math.round(@json($totals['total_due']) * 100);
    },
    isPolicyAccepted: function () {
      return document.getElementById('agreePolicy').checked;
    },
    getClientSecret: async function (ev) {
      var payerName = (ev && ev.payerName)
        ? String(ev.payerName).trim()
        : String(document.getElementById('inputCardName').value || '').trim();
      var res = await fetch(intentUrl, {
        method: 'POST',
        headers: { 'Accept': 'application/json', 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken },
        body: JSON.stringify({ cardholder_name: payerName || 'Google Pay' })
      });
      var data = await res.json();
      if (!res.ok || !data.client_secret) throw new Error(data.message || 'Unable to start payment.');
      return data.client_secret;
    },
    onSuccess: async function (paymentIntentId) {
      var savedRes = await fetch(confirmUrl, {
        method: 'POST',
        headers: { 'Accept': 'application/json', 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken },
        body: JSON.stringify({ payment_intent_id: paymentIntentId })
      });
      var saved = await savedRes.json();
      if (!savedRes.ok || !saved.saved) throw new Error(saved.message || 'Unable to save booking.');
      window.location.href = saved.redirect_url || @json(route('user.bookings.index'));
    },
    onError: function (error) {
      showError(error.message || 'Google Pay payment failed.');
    }
  };

  mountStripe();
})();
</script>
@include('partials.checkout-payment-tabs-script')
@endsection
