<div class="bg-white rounded-2xl border border-outline-variant/20 p-5 lg:sticky lg:top-24">
  <h3 class="text-sm font-bold text-on-surface-variant uppercase tracking-wider mb-4">Booking Summary</h3>
  <div class="space-y-3 text-sm">
    <div class="flex justify-between gap-3">
      <span class="text-on-surface-variant shrink-0">Request</span>
      <span class="font-semibold text-right">{{ $customRequest->referenceLabel() }}</span>
    </div>
    <div class="flex justify-between gap-3">
      <span class="text-on-surface-variant shrink-0">Artist</span>
      <span class="font-semibold text-right">{{ $artistName }}</span>
    </div>
    @if (!empty($showConsultRow) && !empty($consultDateTime))
      <div class="flex justify-between gap-3">
        <span class="text-on-surface-variant shrink-0">Consultation</span>
        <span class="font-semibold text-right text-xs sm:text-sm">{{ $consultDateTime }}</span>
      </div>
    @endif
    <div class="flex justify-between gap-3">
      <span class="text-on-surface-variant shrink-0">{{ $sessionDateTimeLabel ?? 'Session' }}</span>
      <span class="font-semibold text-right text-xs sm:text-sm">{{ $sessionDateTime }}</span>
    </div>
    <div class="flex justify-between gap-3">
      <span class="text-on-surface-variant shrink-0">Duration</span>
      <span class="font-semibold text-right">{{ $durationLabel }}</span>
    </div>
    <div class="flex justify-between gap-3">
      <span class="text-on-surface-variant shrink-0">Sessions quoted</span>
      <span class="font-semibold text-right">{{ $sessionsLabel }}</span>
    </div>
  </div>
  <hr class="border-outline-variant/20 my-4">
  <div class="flex justify-between gap-3 text-sm mb-3">
    <span class="font-semibold text-on-surface">Quote</span>
    <span class="font-semibold text-right">{{ $priceEstimateLabel }}</span>
  </div>
  @php
    $vatApplies = !empty($totals['tax_amount']) && $totals['tax_amount'] > 0;
    $vatRateLabel = (int) \App\Support\EuVat::EU_RATE;
    $feeVatNote = "Your deposit is paid to the artist for the tattoo service — no Inkjin fee applies to it. Inkjin's booking fee, shown below, is a separate charge"
      .($vatApplies ? ' and includes VAT ('.$vatRateLabel.'%).' : '.');
  @endphp
  <div class="rounded-xl px-3 py-2.5 mb-3 text-xs leading-relaxed text-on-surface bg-primary/5 border border-primary/10">
    {{ $feeVatNote }}
  </div>
  <div class="bg-surface-container-low rounded-xl p-3 mb-3">
    <p class="text-xs font-bold text-on-surface-variant uppercase tracking-wider mb-2">Due now</p>
    <div class="space-y-1.5 text-sm">
      @if (!empty($showConsultRow))
        <div class="flex justify-between">
          <span class="text-on-surface-variant">Consultation</span>
          <span class="font-semibold text-green-600">Free</span>
        </div>
      @endif
      <div class="flex justify-between">
        <span class="text-on-surface-variant">{{ $depositLabel ?: 'Artist Deposit' }}</span>
        <span class="font-semibold">€{{ number_format($totals['deposit'], 2) }}</span>
      </div>
      <div class="flex justify-between items-center gap-2">
        <span class="text-on-surface-variant flex items-center gap-1">
          Inkjin Booking Fee
          <span class="info-tooltip">
            <span class="material-symbols-outlined text-[14px] text-outline">info</span>
            <span class="tooltip-text" style="width:260px;text-align:left;">Inkjin's fee for booking, payment processing, and platform support — separate from the artist's price for the tattoo.@if($vatApplies) VAT ({{ $vatRateLabel }}%) applies to this fee.@endif</span>
          </span>
        </span>
        <span class="font-semibold">€{{ number_format($totals['platform_fee'], 2) }}</span>
      </div>
      @if($vatApplies)
        <div class="flex justify-between">
          <span class="text-on-surface-variant">{{ $totals['tax_label'] ?? 'VAT on booking fee ('.$vatRateLabel.'%)' }}</span>
          <span class="font-semibold">€{{ number_format($totals['tax_amount'], 2) }}</span>
        </div>
      @endif
      <div class="flex justify-between pt-2 border-t border-outline-variant/20 font-bold">
        <span>Total due now</span>
        <span>€{{ number_format($totals['total_due'], 2) }}</span>
      </div>
    </div>
  </div>
  <p class="text-xs text-on-surface-variant">Remaining balance at studio: <strong class="text-on-surface">{{ $balanceLabel }}</strong></p>
</div>
