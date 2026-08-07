@php
  $activeTab = $activeTab ?? '';
@endphp
<div class="booking-page-tabs flex items-center gap-1 mb-6 border-b border-outline-variant/20 pb-0 overflow-x-auto">
  <a href="{{ route('artist.forms.index') }}" class="px-4 py-3 text-sm font-semibold whitespace-nowrap border-b-2 {{ $activeTab === 'forms' ? 'border-primary text-primary' : 'border-transparent text-on-surface-variant hover:text-on-surface hover:border-outline-variant' }} transition-all">Forms</a>
  <a href="{{ route('artist-designs.index') }}" class="px-4 py-3 text-sm font-semibold whitespace-nowrap border-b-2 {{ $activeTab === 'designs' ? 'border-primary text-primary' : 'border-transparent text-on-surface-variant hover:text-on-surface hover:border-outline-variant' }} transition-all">Available Designs</a>
  <a href="{{ route('portfolio.index') }}" class="px-4 py-3 text-sm font-semibold whitespace-nowrap border-b-2 {{ $activeTab === 'portfolio' ? 'border-primary text-primary' : 'border-transparent text-on-surface-variant hover:text-on-surface hover:border-outline-variant' }} transition-all">Portfolio</a>
  <a href="{{ route('guest-spots.index') }}" class="px-4 py-3 text-sm font-semibold whitespace-nowrap border-b-2 {{ $activeTab === 'guest-spots' ? 'border-primary text-primary' : 'border-transparent text-on-surface-variant hover:text-on-surface hover:border-outline-variant' }} transition-all">Guest spots</a>
  <a href="{{ route('artist.faq.index') }}" class="px-4 py-3 text-sm font-semibold whitespace-nowrap border-b-2 {{ $activeTab === 'faq' ? 'border-primary text-primary' : 'border-transparent text-on-surface-variant hover:text-on-surface hover:border-outline-variant' }} transition-all">FAQ</a>
  <a href="{{ route('personal-page.index') }}" class="px-4 py-3 text-sm font-semibold whitespace-nowrap border-b-2 {{ $activeTab === 'style' ? 'border-primary text-primary' : 'border-transparent text-on-surface-variant hover:text-on-surface hover:border-outline-variant' }} transition-all">Content & Style</a>
</div>
