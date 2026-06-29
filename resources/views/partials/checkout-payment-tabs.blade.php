@php
  $showIrisCheckout = (bool) ($showIrisTab ?? false);
  $artistSupportsIris = (bool) ($artistSupportsIris ?? false);
  $renderDualCheckout = $showIrisCheckout || $artistSupportsIris;
@endphp
@if ($renderDualCheckout)
  <div
    id="checkoutPayTablist"
    class="flex gap-2 mb-4{{ $showIrisCheckout ? '' : ' hidden' }}"
    role="tablist"
    aria-label="Payment method"
  >
    <button
      type="button"
      id="tabPayCard"
      role="tab"
      aria-selected="true"
      class="checkout-pay-tab flex-1 rounded-xl border border-primary bg-primary px-4 py-2.5 text-sm font-semibold text-white transition"
    >
    {{-- card icon --}}
    <span class="material-symbols-outlined text-[16px]">credit_card</span>
      Card
    </button>
    <button
      type="button"
      id="tabPayIris"
      role="tab"
      aria-selected="false"
      class="checkout-pay-tab flex-1 rounded-xl border border-outline-variant/60 px-4 py-2.5 text-sm font-semibold text-on-surface-variant hover:border-primary/40 transition"
    >
    {{-- iris icon --}}
    <span class="material-symbols-outlined text-[16px]">qr_code_2</span>
      IRIS
    </button>
  </div>
@endif
