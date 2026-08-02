@php
  /**
   * @var array{deposit?: float, platform_fee?: float, tax_amount?: float}|null $checkoutReceiptTotals
   */
  $checkoutReceiptTotals = $checkoutReceiptTotals ?? null;
  $depositAmt = is_array($checkoutReceiptTotals) ? (float) ($checkoutReceiptTotals['deposit'] ?? 0) : 0.0;
  $feeAmt = is_array($checkoutReceiptTotals) ? (float) ($checkoutReceiptTotals['platform_fee'] ?? 0) : 0.0;
  $taxAmt = is_array($checkoutReceiptTotals) ? (float) ($checkoutReceiptTotals['tax_amount'] ?? 0) : 0.0;
  $hasAmounts = is_array($checkoutReceiptTotals);

  $receiptHtml = '';
  if ($hasAmounts) {
      $receiptHtml = "You'll receive a receipt from Inkjin on behalf of the artist for your "
          .'<strong>€'.number_format($depositAmt, 2).'</strong> deposit';

      if ($feeAmt > 0) {
          $receiptHtml .= ', and a separate Inkjin invoice for the '
              .'<strong>€'.number_format($feeAmt, 2).'</strong>';

          if ($taxAmt > 0) {
              $receiptHtml .= ' + <strong>€'.number_format($taxAmt, 2).'</strong> VAT';
          }

          $receiptHtml .= ' booking fee';
      }

      $receiptHtml .= '.';
  }
@endphp

<p id="checkoutReceiptNote" class="text-xs text-on-surface-variant text-center mt-3 leading-relaxed {{ $hasAmounts ? '' : 'hidden' }}">
  {!! $receiptHtml !!}
</p>
