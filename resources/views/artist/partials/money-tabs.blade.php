@php
  $moneyTab = $moneyTab ?? 'earnings';
  $tabClass = fn (string $key) => $moneyTab === $key ? 'on' : '';
@endphp
<div class="tabs">
  <a href="{{ route('artist.payments.index') }}" class="{{ $tabClass('earnings') }}">Earnings</a>
  <a href="{{ route('settings.preferences') }}" class="{{ $tabClass('deposits') }}">Deposits &amp; rates</a>
  <a href="{{ route('settings.payment') }}" class="{{ $tabClass('payouts') }}">Payouts</a>
</div>
