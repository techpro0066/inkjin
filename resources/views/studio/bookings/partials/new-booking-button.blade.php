@php
  $variant = $variant ?? 'primary'; // primary | ghost
  $btnClass = $variant === 'ghost' ? 'btn ghost' : 'btn';
@endphp
@if (! empty($profileComplete))
  <button type="button" class="{{ $btnClass }}" data-nb>
    <span class="ms">add</span>New booking
  </button>
@else
  <button type="button" class="{{ $btnClass }}" data-locked="New booking">
    <span class="ms">add</span>New booking
  </button>
@endif
