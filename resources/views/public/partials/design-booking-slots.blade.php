@php
  $claimed = $design->claimedBookingCount();
  $limit = $design->effectiveRepeatLimit();
  $remaining = $design->remainingBookingSlots();
  $soldOut = $design->isSoldOut();
  $compact = !empty($compact);
@endphp
<div class="{{ $compact ? 'text-[11px]' : 'text-xs' }} text-on-surface-variant {{ $class ?? '' }}">
  <span class="font-bold text-on-surface">{{ $claimed }}/{{ $limit }}</span>
  <span> booked</span>
  @if($soldOut)
    <span> · </span><span class="font-semibold text-on-surface-variant">Sold out</span>
  @elseif($remaining > 0)
    <span> · </span><span class="font-semibold text-primary">{{ $remaining }} left</span>
  @endif
</div>
