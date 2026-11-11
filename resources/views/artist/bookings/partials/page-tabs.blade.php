@php
  $activeTab = $activeTab ?? 'bookings';
  $requestsCount = (int) ($requestsCount ?? 0);
  $bookingsTotalCount = (int) ($bookingsTotalCount ?? 0);
  $paymentLinksCount = (int) ($paymentLinksCount ?? 0);
@endphp
{{-- Count pills: active = dark, inactive = gray (no amber zero state). --}}
<style>
  .bk-menu-tabs.tabs .n,
  .bk-menu-tabs.tabs .n.zero{
    background:var(--chip);color:var(--muted)
  }
  .bk-menu-tabs.tabs a.on .n,
  .bk-menu-tabs.tabs a.on .n.zero{
    background:var(--ink);color:#fff
  }
</style>
<div class="tabs bk-menu-tabs">
  <a href="{{ route('artist.requests.index') }}" class="{{ $activeTab === 'requests' ? 'on' : '' }}">
    Requests<span class="n">{{ $requestsCount }}</span>
  </a>
  <a href="{{ route('artist.bookings.index') }}" class="{{ $activeTab === 'bookings' ? 'on' : '' }}">
    Bookings<span class="n">{{ $bookingsTotalCount }}</span>
  </a>
  <a href="{{ route('artist.bookings.payment-links') }}" class="{{ $activeTab === 'payment-links' ? 'on' : '' }}">
    Payment links<span class="n">{{ $paymentLinksCount }}</span>
  </a>
</div>
