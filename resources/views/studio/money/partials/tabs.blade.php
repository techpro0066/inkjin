@php
  $moneyTab = $moneyTab ?? 'earnings';
@endphp
<div class="tabs">
  <a href="{{ route('studio.money.earnings') }}" class="{{ $moneyTab === 'earnings' ? 'on' : '' }}">Earnings</a>
  <a href="{{ route('studio.money.payouts') }}" class="{{ $moneyTab === 'payouts' ? 'on' : '' }}">Payouts</a>
</div>
