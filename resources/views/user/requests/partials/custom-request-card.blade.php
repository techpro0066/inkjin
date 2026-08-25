@php
  $filterKey = $customRequest->userFilterKey();
  $filterStatus = $customRequest->userFilterStatusLabel();
  $badgeClass = $customRequest->statusBadgeClass();
  $isGuestCard = $customRequest->isGuestRequest();
  $guestSpot = $isGuestCard ? $customRequest->guestSpot : null;
  $guestSubtitle = null;
  if ($guestSpot) {
    $guestParts = array_filter([
      trim(implode(', ', array_filter([(string) ($guestSpot->city ?? ''), (string) ($guestSpot->country ?? '')]))),
      ($guestSpot->from_date && $guestSpot->to_date)
        ? $guestSpot->from_date->format('M j') . ' – ' . $guestSpot->to_date->format('M j, Y')
        : null,
    ]);
    $guestSubtitle = $guestParts !== [] ? implode(' · ', $guestParts) : null;
  }
@endphp
<div class="custom-request-card request-card bg-white rounded-2xl shadow-sm border border-outline-variant/20 p-5 cursor-pointer"
     data-request-id="{{ $customRequest->id }}"
     data-status="{{ $filterKey }}"
     data-artist="{{ $customRequest->artistSearchKey() }}"
     data-date="{{ $customRequest->created_at?->format('Y-m-d') ?? '' }}"
     onclick="openUserCustomRequestDetail({{ $customRequest->id }})">
  <div class="flex flex-col sm:flex-row sm:items-start gap-4">
    <div class="w-14 h-14 rounded-xl bg-primary/10 flex items-center justify-center flex-shrink-0 border border-primary/15">
      <span class="material-symbols-outlined text-primary text-2xl">{{ $isGuestCard ? 'luggage' : 'brush' }}</span>
    </div>
    <div class="flex-1 min-w-0">
      <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2 mb-2">
        <div class="flex items-center gap-3 flex-wrap">
          <p class="font-semibold text-on-surface">{{ $customRequest->artistDisplayName() }}</p>
          <span class="request-status-badge inline-flex items-center gap-1.5 {{ $badgeClass }} text-xs font-semibold px-3 py-1 rounded-full">
            <span class="w-1.5 h-1.5 rounded-full status-dot"></span>
            <span class="request-status-label">{{ $filterStatus }}</span>
          </span>
          <span class="text-xs text-outline">{{ $customRequest->referenceLabel() }}</span>
        </div>
        <p class="text-xs text-outline flex-shrink-0">{{ $customRequest->created_at?->format('M j, Y') }}</p>
      </div>
      <p class="font-bold text-on-surface mb-1">{{ $isGuestCard ? 'Guest spot request' : 'Custom tattoo request' }}</p>
      <p class="text-sm text-on-surface-variant mb-3">{{ $guestSubtitle ?: $customRequest->schedulingLabel() }}</p>
      <div class="flex flex-wrap items-center gap-2">
        @if ($customRequest->hasQuote())
          <span class="info-tag text-xs font-medium px-2.5 py-1 rounded-lg flex items-center gap-1">
            <span class="material-symbols-outlined text-sm">request_quote</span> {{ $customRequest->estimatedPriceLabel() }}
          </span>
        @elseif ($customRequest->status === 'pending')
          <span class="text-xs text-on-surface-variant">Waiting for artist response</span>
        @endif
        <div class="ml-auto flex flex-wrap items-center gap-2">
          @if ($customRequest->canAccessConfirmTimesPage())
            <a href="{{ route('user.custom-requests.confirm-times', ['customRequest' => $customRequest, 'fresh' => 1]) }}" onclick="event.stopPropagation();" class="inline-flex items-center gap-1 px-3 py-1.5 rounded-lg bg-primary text-white text-xs font-semibold hover:bg-primary-container transition-colors">
              <span class="material-symbols-outlined text-sm">event</span> Set date &amp; time
            </a>
          @elseif ($customRequest->isGuestRequest() && $customRequest->guestActionBlockMessage())
            <span class="text-xs font-medium {{ $customRequest->guestActionBlockReason() === 'slots_full' ? 'text-amber-600' : 'text-error' }}">
              {{ $customRequest->guestActionBlockMessage() }}
            </span>
          @elseif ($customRequest->isBooked())
            <span class="info-tag text-xs font-medium px-2.5 py-1 rounded-lg flex items-center gap-1">
              <span class="material-symbols-outlined text-sm">check_circle</span> Booked
            </span>
          @endif
          <button type="button" onclick="event.stopPropagation(); openUserCustomRequestDetail({{ $customRequest->id }})" class="text-xs font-semibold text-primary hover:text-primary-container transition-colors flex items-center gap-1">
            View Details <span class="material-symbols-outlined text-sm">arrow_forward</span>
          </button>
        </div>
      </div>
    </div>
  </div>
</div>
