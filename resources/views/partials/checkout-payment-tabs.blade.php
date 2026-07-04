@php
  $showIrisCheckout = (bool) ($showIrisTab ?? false);
  $artistSupportsIris = (bool) ($artistSupportsIris ?? false);
  $showIrisTabButton = $showIrisCheckout || $artistSupportsIris;
@endphp
<div
  id="checkoutPayTablist"
  class="flex flex-wrap gap-2 mb-4"
  role="tablist"
  aria-label="Payment method"
>
  <button
    type="button"
    id="tabPayCard"
    role="tab"
    aria-selected="true"
    class="checkout-pay-tab flex-1 min-w-[7rem] rounded-xl border border-primary bg-primary px-4 py-2.5 text-sm font-semibold text-white transition"
  >
    <span class="material-symbols-outlined text-[16px] align-middle">credit_card</span>
    Card
  </button>
  <button
    type="button"
    id="tabPayGooglePay"
    role="tab"
    aria-selected="false"
    class="checkout-pay-tab hidden flex-1 min-w-[7rem] rounded-xl border border-outline-variant/60 px-4 py-2.5 text-sm font-semibold text-on-surface-variant hover:border-primary/40 transition"
  >
    <span class="material-symbols-outlined text-[16px] align-middle">account_balance_wallet</span>
    Google Pay
  </button>
  <button
    type="button"
    id="tabPayApplePay"
    role="tab"
    aria-selected="false"
    class="checkout-pay-tab hidden flex-1 min-w-[7rem] rounded-xl border border-outline-variant/60 px-4 py-2.5 text-sm font-semibold text-on-surface-variant hover:border-primary/40 transition"
  >
    <span class="material-symbols-outlined text-[16px] align-middle">phone_iphone</span>
    Apple Pay
  </button>
  @if ($showIrisTabButton)
    <button
      type="button"
      id="tabPayIris"
      role="tab"
      aria-selected="false"
      class="checkout-pay-tab flex-1 min-w-[7rem] rounded-xl border border-outline-variant/60 px-4 py-2.5 text-sm font-semibold text-on-surface-variant hover:border-primary/40 transition{{ $showIrisCheckout ? '' : ' hidden' }}"
    >
      <span class="material-symbols-outlined text-[16px] align-middle">qr_code_2</span>
      IRIS
    </button>
  @endif
</div>
