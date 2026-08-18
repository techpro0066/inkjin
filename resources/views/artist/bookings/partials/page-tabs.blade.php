@php
  $activeTab = $activeTab ?? 'bookings';
@endphp
<div class="flex items-center gap-1 mb-6 border-b border-outline-variant/20 pb-0 overflow-x-auto">
  <a href="{{ route('artist.bookings.index') }}" class="px-4 py-3 text-sm font-semibold whitespace-nowrap border-b-2 {{ $activeTab === 'bookings' ? 'border-primary text-primary' : 'border-transparent text-on-surface-variant hover:text-on-surface hover:border-outline-variant' }} transition-all">Bookings</a>
  <a href="{{ route('artist.bookings.payment-links') }}" class="px-4 py-3 text-sm font-semibold whitespace-nowrap border-b-2 {{ $activeTab === 'payment-links' ? 'border-primary text-primary' : 'border-transparent text-on-surface-variant hover:text-on-surface hover:border-outline-variant' }} transition-all">Payment links</a>
</div>
