@php
  $showIrisCheckout = (bool) ($showIrisTab ?? false);
  $artistSupportsIris = (bool) ($artistSupportsIris ?? false);
  $showIrisTabButton = $showIrisCheckout || $artistSupportsIris;
@endphp
<style>
  .checkout-wallet-btn-mount {
    width: 100%;
    min-height: 48px;
    display: flex;
    align-items: stretch;
  }
  .checkout-wallet-btn-mount > div,
  .checkout-wallet-btn-mount .StripeElement {
    width: 100% !important;
    flex: 1 1 auto;
  }
  .checkout-wallet-btn-mount iframe {
    width: 100% !important;
    min-width: 100% !important;
  }
</style>

{{-- Express wallets (Google Pay / Apple Pay) --}}
<div id="checkoutWalletSection" class="hidden mb-2">
  <div class="space-y-3">
    <div id="googlePayWalletRow" class="hidden">
      <div id="googlePayButtonMount" class="checkout-wallet-btn-mount rounded-xl overflow-hidden"></div>
      <p id="googlePayPolicyHint" class="hidden text-sm text-error mt-2">Please accept the cancellation policy below before paying.</p>
    </div>
    <div id="applePayWalletRow" class="hidden">
      <div id="applePayButtonMount" class="checkout-wallet-btn-mount rounded-xl overflow-hidden"></div>
      <p id="applePayPolicyHint" class="hidden text-sm text-error mt-2">Please accept the cancellation policy below before paying.</p>
    </div>
  </div>

  <div id="checkoutWalletDivider" class="flex items-center gap-3 my-6">
    <div class="flex-1 h-px bg-outline-variant/30"></div>
    <span class="text-xs font-medium text-on-surface-variant whitespace-nowrap">or pay another way</span>
    <div class="flex-1 h-px bg-outline-variant/30"></div>
  </div>
</div>

{{-- Card / Klarna / IRIS --}}
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
    id="tabPayKlarna"
    role="tab"
    aria-selected="false"
    class="checkout-pay-tab flex-1 min-w-[7rem] rounded-xl border border-outline-variant/60 px-4 py-2.5 text-sm font-semibold text-on-surface-variant hover:border-primary/40 transition"
  >
    Klarna
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
