@extends('layouts.user_dashboard_layout')

@section('title', 'Complete payment')

@section('content')
<main class="main-content flex-1 min-h-screen">
  <div class="p-6 md:p-10 lg:p-12 max-w-4xl mx-auto">
    <a href="{{ route('user.requests.confirm-times', $bookingRequest) }}" class="inline-flex items-center gap-1 text-sm text-on-surface-variant hover:text-primary mb-6 transition-colors">
      <span class="material-symbols-outlined text-[18px]">arrow_back</span> Back to times
    </a>

    @if (session('success'))
      <div class="mb-6 rounded-xl border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-800">{{ session('success') }}</div>
    @endif

    <div class="bg-white rounded-2xl border border-outline-variant/20 p-5 mb-6">
      <p class="text-xs text-outline mb-1">{{ $bookingRequest->referenceLabel() }}</p>
      <h1 class="text-xl font-extrabold text-on-surface tracking-tight">Pay deposit &amp; confirm booking</h1>
      <p class="text-sm text-on-surface-variant mt-1">{{ $designTitle }} with <strong>{{ $artistName }}</strong></p>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
      <div class="space-y-4">
        <div class="bg-white rounded-2xl border border-outline-variant/20 p-5">
          <h2 class="text-sm font-bold text-on-surface mb-3">Your appointment</h2>
          <ul class="space-y-2 text-sm text-on-surface-variant">
            @if ($consultSummary)
              <li class="flex items-start gap-2">
                <span class="material-symbols-outlined text-primary text-lg shrink-0">groups</span>
                <span><strong class="text-on-surface">Consultation:</strong> {{ $consultSummary }}</span>
              </li>
            @endif
            @if ($sessionSummary)
              <li class="flex items-start gap-2">
                <span class="material-symbols-outlined text-primary text-lg shrink-0">brush</span>
                <span><strong class="text-on-surface">Tattoo session:</strong> {{ $sessionSummary }}</span>
              </li>
            @endif
          </ul>
        </div>

        <div class="bg-surface-container-low rounded-2xl border border-outline-variant/20 p-5">
          <p class="text-xs font-bold text-on-surface-variant uppercase tracking-wider mb-3">Due now</p>
          <div class="space-y-2 text-sm">
            <div class="flex justify-between">
              <span class="text-on-surface-variant">Deposit ({{ $totals['deposit_meta']['label'] }})</span>
              <span class="font-semibold">€{{ number_format($totals['deposit'], 2) }}</span>
            </div>
            @if ($totals['platform_fee'] > 0)
              <div class="flex justify-between">
                <span class="text-on-surface-variant">Inkjin booking fee</span>
                <span class="font-semibold">€{{ number_format($totals['platform_fee'], 2) }}</span>
              </div>
            @endif
            <hr class="border-outline-variant/20">
            <div class="flex justify-between">
              <span class="font-bold text-on-surface">Total due now</span>
              <span class="font-bold text-primary text-lg">€{{ number_format($totals['total_due'], 2) }}</span>
            </div>
          </div>
          <p class="text-xs text-on-surface-variant mt-3">Remaining at studio (est.): €{{ number_format($remainingBalance, 2) }}</p>
        </div>
      </div>

      <div class="bg-white rounded-2xl border border-outline-variant/20 p-5">
        <h2 class="text-lg font-bold text-on-surface mb-1 flex items-center gap-2">
          <span class="material-symbols-outlined text-primary">lock</span> Secure payment
        </h2>
        <p class="text-sm text-on-surface-variant mb-5">Your card is processed securely by Stripe.</p>

        <div class="space-y-4 mb-4">
          <div>
            <label class="text-xs font-semibold text-on-surface-variant mb-1.5 block">Card number</label>
            <div id="stripeCardNumber" class="w-full border border-outline-variant/30 bg-white rounded-xl px-4 py-3 text-sm min-h-[44px]"></div>
          </div>
          <div class="grid grid-cols-2 gap-3">
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
            <label class="text-xs font-semibold text-on-surface-variant mb-1.5 block" for="inputCardName">Cardholder name</label>
            <input type="text" id="inputCardName" class="w-full border border-outline-variant/30 bg-white rounded-xl px-4 py-3 text-sm focus:outline-none focus:ring-2 focus:ring-primary/30" placeholder="Name on card">
          </div>
        </div>

        <label class="flex items-start gap-2 mb-4 cursor-pointer">
          <input type="checkbox" id="agreePolicy" class="mt-0.5 accent-primary">
          <span class="text-xs text-on-surface-variant">I agree to pay the deposit and booking fee to secure this appointment.</span>
        </label>

        <p id="paymentError" class="text-sm text-error hidden mb-3"></p>

        <button type="button" id="btnConfirmPay" disabled class="w-full py-3.5 rounded-xl font-bold text-white bg-primary disabled:opacity-40 disabled:cursor-not-allowed hover:bg-primary-container transition-colors">
          Pay €{{ number_format($totals['total_due'], 2) }} &amp; confirm
        </button>
      </div>
    </div>
  </div>
</main>
@endsection

@section('scripts')
<script src="https://js.stripe.com/v3/"></script>
<script>
(function() {
  var csrfToken = @json(csrf_token());
  var stripePublishableKey = @json($stripePublishableKey);
  var intentUrl = @json(route('user.requests.payment.intent', $bookingRequest));
  var confirmUrl = @json(route('user.requests.payment.confirm', $bookingRequest));

  var stripe = null;
  var stripeElements = null;
  var stripeCardNumber = null;
  var stripeCardExpiry = null;
  var stripeCardCvc = null;
  var cardComplete = { number: false, expiry: false, cvc: false };

  function mountStripe() {
    if (!stripePublishableKey || typeof Stripe === 'undefined' || stripeCardNumber) return;
    stripe = Stripe(stripePublishableKey);
    stripeElements = stripe.elements();
    var style = { base: { fontSize: '14px', color: '#1c1b21', '::placeholder': { color: '#7a7583' } } };
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
  }

  function checkReady() {
    var name = String(document.getElementById('inputCardName').value || '').trim().length > 1;
    var agreed = document.getElementById('agreePolicy').checked;
    var ready = name && agreed && cardComplete.number && cardComplete.expiry && cardComplete.cvc;
    document.getElementById('btnConfirmPay').disabled = !ready;
  }

  document.getElementById('inputCardName').addEventListener('input', checkReady);
  document.getElementById('agreePolicy').addEventListener('change', checkReady);

  function showError(msg) {
    var el = document.getElementById('paymentError');
    el.textContent = msg || '';
    el.classList.toggle('hidden', !msg);
  }

  async function createIntent() {
    var res = await fetch(intentUrl, {
      method: 'POST',
      headers: {
        'Accept': 'application/json',
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': csrfToken
      },
      body: JSON.stringify({ cardholder_name: document.getElementById('inputCardName').value.trim() })
    });
    var data = await res.json();
    if (!res.ok || !data.client_secret) throw new Error(data.message || 'Unable to start payment.');
    return data.client_secret;
  }

  async function saveBooking(paymentIntentId) {
    var res = await fetch(confirmUrl, {
      method: 'POST',
      headers: {
        'Accept': 'application/json',
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': csrfToken
      },
      body: JSON.stringify({ payment_intent_id: paymentIntentId })
    });
    var data = await res.json();
    if (!res.ok || !data.saved) throw new Error(data.message || 'Unable to save booking.');
    return data;
  }

  document.getElementById('btnConfirmPay').addEventListener('click', async function() {
    var btn = this;
    if (btn.disabled) return;
    showError('');
    btn.disabled = true;
    btn.textContent = 'Processing…';
    try {
      mountStripe();
      var clientSecret = await createIntent();
      var result = await stripe.confirmCardPayment(clientSecret, {
        payment_method: {
          card: stripeCardNumber,
          billing_details: { name: document.getElementById('inputCardName').value.trim() }
        }
      });
      if (result.error) throw new Error(result.error.message || 'Payment failed.');
      if (!result.paymentIntent || result.paymentIntent.status !== 'succeeded') {
        throw new Error('Payment was not completed.');
      }
      var saved = await saveBooking(result.paymentIntent.id);
      window.location.href = saved.redirect_url || @json(route('user.bookings.index'));
    } catch (e) {
      showError(e.message || 'Payment failed.');
      btn.disabled = false;
      btn.textContent = 'Pay €{{ number_format($totals['total_due'], 2) }} & confirm';
      checkReady();
    }
  });

  mountStripe();
})();
</script>
@endsection
