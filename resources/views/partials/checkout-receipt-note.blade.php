@php
  /**
   * @var array{deposit?: float, platform_fee?: float, tax_amount?: float}|null $checkoutReceiptTotals
   */
  $checkoutReceiptTotals = $checkoutReceiptTotals ?? null;
  $depositAmt = is_array($checkoutReceiptTotals) ? (float) ($checkoutReceiptTotals['deposit'] ?? 0) : 0.0;
  $feeAmt = is_array($checkoutReceiptTotals) ? (float) ($checkoutReceiptTotals['platform_fee'] ?? 0) : 0.0;
  $taxAmt = is_array($checkoutReceiptTotals) ? (float) ($checkoutReceiptTotals['tax_amount'] ?? 0) : 0.0;
  $hasAmounts = is_array($checkoutReceiptTotals);
  $currencyCode = \App\Support\MoneyCurrency::code($currencyCode ?? $userDetail?->currency ?? 'EUR');

  $receiptHtml = '';
  if ($hasAmounts) {
      $receiptHtml = "You'll receive a receipt from Inkjin on behalf of the artist for your "
          .'<strong>'.e(\App\Support\MoneyCurrency::format($depositAmt, $currencyCode)).'</strong> deposit';

      if ($feeAmt > 0) {
          $receiptHtml .= ', and a separate Inkjin invoice for the '
              .'<strong>'.e(\App\Support\MoneyCurrency::format($feeAmt, $currencyCode)).'</strong>';

          if ($taxAmt > 0) {
              $receiptHtml .= ' + <strong>'.e(\App\Support\MoneyCurrency::format($taxAmt, $currencyCode)).'</strong> VAT';
          }

          $receiptHtml .= ' booking fee';
      }

      $receiptHtml .= '.';
  }
@endphp

<p id="checkoutReceiptNote" class="text-xs text-on-surface-variant text-center mt-3 leading-relaxed {{ $hasAmounts ? '' : 'hidden' }}">
  {!! $receiptHtml !!}
</p>
