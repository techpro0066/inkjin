@php
  $activeTab = $activeTab ?? '';
  $bookingTabClass = fn (string $key) => $activeTab === $key
      ? 'px-4 py-2 text-sm font-semibold whitespace-nowrap rounded-xl bg-primary text-on-primary transition-all'
      : 'px-4 py-2 text-sm font-semibold whitespace-nowrap rounded-xl text-on-surface-variant hover:bg-surface-container-low hover:text-on-surface transition-all';
@endphp
<div class="booking-page-tabs flex items-center gap-2 mb-6 overflow-x-auto">
  <a href="{{ route('artist.forms.index') }}" class="{{ $bookingTabClass('forms') }}">Forms</a>
  <a href="{{ route('artist-designs.index') }}" class="{{ $bookingTabClass('designs') }}">Available Designs</a>
  <a href="{{ route('portfolio.index') }}" class="{{ $bookingTabClass('portfolio') }}">Portfolio</a>
  <a href="{{ route('guest-spots.index') }}" class="{{ $bookingTabClass('guest-spots') }}">Guest spots</a>
  <a href="{{ route('artist.faq.index') }}" class="{{ $bookingTabClass('faq') }}">FAQ</a>
  <a href="{{ route('personal-page.index') }}" class="{{ $bookingTabClass('style') }}">Content &amp; Style</a>
</div>
