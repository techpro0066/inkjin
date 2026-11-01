@php
  $activeTab = $activeTab ?? 'bookings';
  $requestsCount = (int) ($requestsCount ?? 0);
  $bookingsTotalCount = (int) ($bookingsTotalCount ?? 0);
  $paymentLinksCount = (int) ($paymentLinksCount ?? 0);
@endphp
<div class="tabs">
  <a href="{{ route('artist.requests.index') }}" class="{{ $activeTab === 'requests' ? 'on' : '' }}">
    Requests<span class="n{{ $requestsCount === 0 ? ' zero' : '' }}">{{ $requestsCount }}</span>
  </a>
  <a href="{{ route('artist.bookings.index') }}" class="{{ $activeTab === 'bookings' ? 'on' : '' }}">
    Bookings<span class="n{{ $bookingsTotalCount === 0 ? ' zero' : '' }}">{{ $bookingsTotalCount }}</span>
  </a>
  <a href="{{ route('artist.bookings.payment-links') }}" class="{{ $activeTab === 'payment-links' ? 'on' : '' }}">
    Payment links<span class="n{{ $paymentLinksCount === 0 ? ' zero' : '' }}">{{ $paymentLinksCount }}</span>
  </a>
</div>
