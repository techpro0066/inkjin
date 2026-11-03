@php
  $bookingsTab = $bookingsTab ?? 'bookings';
@endphp
<div class="tabs">
  <a href="{{ route('studio.bookings') }}" class="{{ $bookingsTab === 'bookings' ? 'on' : '' }}">
    Bookings
    <span class="n">{{ (int) ($bookingsCount ?? 0) }}</span>
  </a>
  <a href="{{ route('studio.bookings.requests') }}" class="{{ $bookingsTab === 'requests' ? 'on' : '' }}">
    Requests
    <span class="n{{ (int) ($requestsCount ?? 0) === 0 ? ' zero' : '' }}">{{ (int) ($requestsCount ?? 0) }}</span>
  </a>
  <a href="{{ route('studio.bookings.payment-links') }}" class="{{ $bookingsTab === 'payment-links' ? 'on' : '' }}">
    Payment links
    <span class="n">{{ (int) ($paymentLinksCount ?? 0) }}</span>
  </a>
</div>
