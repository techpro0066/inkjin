@php
  $showIrisCheckout = (bool) ($showIrisTab ?? false);
  $artistSupportsIris = (bool) ($artistSupportsIris ?? false);
  $renderDualCheckout = $showIrisCheckout || $artistSupportsIris;
@endphp
@if ($renderDualCheckout)
  <div id="panelPayIris" class="hidden">
    <div class="bg-white rounded-2xl border border-outline-variant/20 p-8 mb-6 text-center">
      <canvas id="irisQrCanvas" class="mx-auto mb-4 hidden"></canvas>
      <p id="irisQrStatus" class="text-sm text-on-surface-variant hidden"></p>
      <p id="irisQrExpiry" class="text-xs text-on-surface-variant mt-2 hidden"></p>
      <button type="button" id="irisQrRegenerate" class="hidden mt-4 text-sm font-semibold text-primary hover:underline">
        Regenerate QR
      </button>
    </div>
  </div>
@endif
