@php
  $activeTab = $activeTab ?? '';
  $settingsTabClass = fn (string $key) => $activeTab === $key
      ? 'px-4 py-3 text-sm font-semibold whitespace-nowrap border-b-2 border-primary text-primary transition-all'
      : 'px-4 py-3 text-sm font-semibold whitespace-nowrap border-b-2 border-transparent text-on-surface-variant hover:text-on-surface hover:border-outline-variant transition-all';
@endphp
<div class="flex items-center gap-1 mb-6 border-b border-outline-variant/20 pb-0 overflow-x-auto">
  <a href="{{ route('profile.edit') }}" class="{{ $settingsTabClass('profile') }}">Profile</a>
  <a href="{{ route('settings.styles') }}" class="{{ $settingsTabClass('styles') }}">Styles &amp; Social</a>
  <a href="{{ route('settings.studio') }}" class="{{ $settingsTabClass('studio') }}">Studio</a>
  <a href="{{ route('availability.index') }}" class="{{ $settingsTabClass('availability') }}">Availability</a>
  <a href="{{ route('settings.preferences') }}" class="{{ $settingsTabClass('payments') }}">Payments</a>
  <a href="{{ route('settings.calendar') }}" class="{{ $settingsTabClass('calendar') }}">Calendar</a>
  <a href="{{ route('settings.payment') }}" class="{{ $settingsTabClass('payouts') }}">Payouts</a>
  <a href="{{ route('settings.other') }}" class="{{ $settingsTabClass('other') }}">Other</a>
</div>
