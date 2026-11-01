@php
  $scheduleTab = $scheduleTab ?? '';
  $tabClass = fn (string $key) => $scheduleTab === $key ? 'on' : '';
@endphp
<div class="tabs">
  <a href="{{ route('availability.index') }}" class="{{ $tabClass('availability') }}">Availability</a>
  <a href="{{ route('guest-spots.index') }}" class="{{ $tabClass('guest-spots') }}">Guest spots</a>
  <a href="{{ route('schedule.calendar-sync') }}" class="{{ $tabClass('calendar-sync') }}">Calendar sync</a>
  <a href="{{ route('schedule.booking-rules') }}" class="{{ $tabClass('booking-rules') }}">Booking rules</a>
</div>
