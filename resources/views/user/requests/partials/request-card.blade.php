@php
  $filterKey = $request->userFilterKey();
  $filterStatus = $request->userFilterStatusLabel();
  $badgeClass = $request->statusBadgeClass();
@endphp
<div class="request-card bg-white rounded-2xl shadow-sm border border-outline-variant/20 p-5 cursor-pointer"
     data-request-id="{{ $request->id }}"
     data-status="{{ $filterKey }}"
     data-artist="{{ $request->artistSearchKey() }}"
     data-date="{{ $request->created_at?->format('Y-m-d') ?? '' }}"
     onclick="openUserRequestDetail({{ $request->id }})">
  <div class="flex flex-col sm:flex-row sm:items-start gap-4">
    <div class="w-14 h-14 rounded-xl bg-surface-container flex items-center justify-center flex-shrink-0 border border-outline-variant/20 overflow-hidden">
      @if ($request->designImageUrl())
        <img src="{{ $request->designImageUrl() }}" alt="" class="w-full h-full object-cover">
      @else
        <span class="material-symbols-outlined text-outline text-2xl">palette</span>
      @endif
    </div>
    <div class="flex-1 min-w-0">
      <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2 mb-2">
        <div class="flex items-center gap-3 flex-wrap">
          <p class="font-semibold text-on-surface">{{ $request->artistDisplayName() }}</p>
          <span class="request-status-badge inline-flex items-center gap-1.5 {{ $badgeClass }} text-xs font-semibold px-3 py-1 rounded-full">
            <span class="w-1.5 h-1.5 rounded-full status-dot"></span>
            <span class="request-status-label">{{ $filterStatus }}</span>
          </span>
          <span class="text-xs text-outline">{{ $request->referenceLabel() }}</span>
        </div>
        <p class="text-xs text-outline flex-shrink-0">{{ $request->created_at?->format('M j, Y') }}</p>
      </div>
      <p class="font-bold text-on-surface mb-1">{{ $request->tattoo?->title ?? 'Design' }}</p>
      <p class="text-sm text-on-surface-variant mb-3">{{ $request->designStyleLabel() }} · {{ $request->priceLabel() }} · {{ $request->schedulingLabel() }}</p>
      <div class="flex flex-wrap items-center gap-2">
        @if ($request->hasArtistOffer())
          <span class="info-tag text-xs font-medium px-2.5 py-1 rounded-lg flex items-center gap-1">
            <span class="material-symbols-outlined text-sm">event_available</span> Times offered
          </span>
        @elseif ($request->status === 'pending')
          <span class="text-xs text-on-surface-variant">Waiting for artist response</span>
        @endif
        <div class="ml-auto flex flex-wrap items-center gap-2">
          @if ($request->canPay())
            <a href="{{ route('user.requests.payment', $request) }}" onclick="event.stopPropagation();" class="inline-flex items-center gap-1 px-3 py-1.5 rounded-lg bg-primary text-white text-xs font-semibold hover:bg-primary-container transition-colors">
              <span class="material-symbols-outlined text-sm">payments</span> Complete payment
            </a>
          @elseif ($request->canSelectTimes())
            <a href="{{ route('user.requests.confirm-times', $request) }}" onclick="event.stopPropagation();" class="inline-flex items-center gap-1 px-3 py-1.5 rounded-lg bg-primary text-white text-xs font-semibold hover:bg-primary-container transition-colors">
              <span class="material-symbols-outlined text-sm">event</span> Set date &amp; time
            </a>
          @elseif ($request->isBooked())
            <span class="info-tag text-xs font-medium px-2.5 py-1 rounded-lg flex items-center gap-1">
              <span class="material-symbols-outlined text-sm">check_circle</span> Booked
            </span>
          @endif
          <button type="button" onclick="event.stopPropagation(); openUserRequestDetail({{ $request->id }})" class="text-xs font-semibold text-primary hover:text-primary-container transition-colors flex items-center gap-1">
            View Details <span class="material-symbols-outlined text-sm">arrow_forward</span>
          </button>
        </div>
      </div>
    </div>
  </div>
</div>
