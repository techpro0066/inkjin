@php
  $showIrisCheckout = (bool) ($showIrisTab ?? false);
  $artistSupportsIris = (bool) ($artistSupportsIris ?? false);
  $renderDualCheckout = $showIrisCheckout || $artistSupportsIris;
@endphp
@if ($renderDualCheckout)
  <div id="panelPayIris" class="hidden">
    <div class="bg-white rounded-2xl border border-outline-variant/20 p-8 mb-6 text-center">
      <p id="irisIntroDesktop" class="text-sm text-on-surface-variant mb-4">
        Scan with your Greek banking app to pay via IRIS.
      </p>
      <p id="irisIntroMobile" class="hidden text-sm text-on-surface-variant mb-4">
        Tap below to open IRIS in your banking app.
      </p>
      <div id="irisQrDesktopWrap">
        <canvas id="irisQrCanvas" class="mx-auto mb-4 hidden max-w-full"></canvas>
      </div>
      <a
        id="irisMobilePayBtn"
        href="#"
        class="hidden inline-flex items-center justify-center gap-2 w-full sm:w-auto px-8 py-4 bg-primary text-on-primary rounded-full font-bold text-base hover:bg-primary-container transition-colors shadow-lg shadow-primary/20 mb-4"
      >
        <span class="material-symbols-outlined text-[20px]">account_balance</span>
        Pay with IRIS
      </a>
      <p id="irisQrStatus" class="text-sm text-on-surface-variant hidden"></p>
      <p id="irisQrExpiry" class="text-xs text-on-surface-variant mt-2 hidden"></p>
      <button type="button" id="irisQrRegenerate" class="hidden mt-4 text-sm font-semibold text-primary hover:underline">
        Regenerate QR
      </button>
    </div>
  </div>
@endif
