@php
  $artistsTab = $artistsTab ?? 'artists';
@endphp
<div class="tabs">
  <a href="{{ route('studio.artists') }}" class="{{ $artistsTab === 'artists' ? 'on' : '' }}">
    Artists<span class="pill nd k">{{ $artistCount }}</span>
  </a>
  <a href="{{ route('studio.artists.join-requests') }}" class="{{ $artistsTab === 'join-requests' ? 'on' : '' }}">
    Join requests<span class="pill nd k">{{ $joinRequestCount }}</span>
  </a>
  <a href="{{ route('studio.artists.invites') }}" class="{{ $artistsTab === 'invites' ? 'on' : '' }}">
    Invites<span class="pill nd k">{{ $inviteCount }}</span>
  </a>
  <a href="{{ route('studio.artists.guests') }}" class="{{ $artistsTab === 'guests' ? 'on' : '' }}">
    Guest artists<span class="pill nd k">{{ $guestCount }}</span>
  </a>
</div>
