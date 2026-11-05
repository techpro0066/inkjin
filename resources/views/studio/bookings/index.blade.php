@extends('layouts.studio-dashboard-layout')

@section('title', 'Bookings')

@push('styles')
@include('studio.bookings.partials.payment-link-modal-assets')
<style>
  .bk .tabs a{display:inline-flex;align-items:center;gap:7px}
  .bk .tabs .n{background:var(--chip);color:var(--muted);border-radius:10px;font-size:11px;padding:1px 7px;font-weight:600}
  .bk .tabs .n.zero{background:#FFF3DE;color:#B86E00}
  .bk .tabs a.on .n{background:var(--ink);color:#fff}
  .bk .tabs a.on .n.zero{background:#FFF3DE;color:#B86E00}
  .bk .fchips{display:flex;gap:8px;flex-wrap:wrap}
  .bk .fchip{border:1px solid var(--line);background:#fff;border-radius:20px;padding:6px 13px;font-size:12.5px;font-weight:600;color:var(--muted);text-decoration:none}
  .bk .fchip.on{background:var(--pri);border-color:var(--pri);color:#fff}
  .bk .pill{gap:5px}
  .bk .pill:before{content:'';width:5px;height:5px;border-radius:50%;background:currentColor}
  .bk .pill.nd:before{display:none}
  .bk .pill.b{background:#E8F0FF;color:#1E5BD8}
  .bk .pill.r{background:#FDECEC;color:#C62828}
  .bk .search{display:flex;align-items:center;gap:8px;background:#fff;border:1px solid var(--line);border-radius:10px;padding:8px 12px;min-width:220px}
  .bk .search input{border:0;outline:0;font:inherit;font-size:13.5px;width:100%;background:none}
  .bk .search .ms{color:var(--faint);font-size:18px}
  .bk .empty{padding:48px 22px;text-align:center;color:var(--muted)}
  @media (max-width:900px){
    .bk .card:has(table){overflow:visible!important;background:none!important;border:0!important}
    .bk table,.bk tbody,.bk tr,.bk td{display:block;width:100%}
    .bk tr:has(th){display:none}
    .bk tbody tr{background:#fff;border:1px solid var(--line);border-radius:14px;padding:12px 14px;margin-bottom:10px}
    .bk td{border:0!important;padding:5px 0!important;display:flex!important;justify-content:space-between;align-items:center;gap:12px;text-align:right}
    .bk td::before{content:attr(data-label);font-size:10.5px;font-weight:700;letter-spacing:.7px;color:var(--muted);text-align:left;flex-shrink:0}
    .bk td:first-child{padding:0 0 8px!important;margin-bottom:4px;border-bottom:1px solid #F3EEF4!important;justify-content:flex-start;text-align:left}
    .bk td:first-child::before{display:none}
    .bk td:last-child{justify-content:flex-end}
    .bk td:last-child::before{display:none}
  }
</style>
@include('studio.bookings.partials.booking-detail-modal-styles')
@endpush

@section('content')
<div class="bk">
  <div class="head">
    <div>
      <h1>Bookings</h1>
      <div class="sub">Bookings and requests for your artists at {{ $studioName }}.</div>
    </div>
    <div class="btns">
      @include('studio.bookings.partials.new-booking-button')
    </div>
  </div>

  @include('studio.bookings.partials.tabs')

  <div class="row" style="justify-content:space-between;margin-bottom:12px;flex-wrap:wrap;gap:10px">
    <div class="fchips">
      <a class="fchip {{ ($activeFilter ?? 'all') === 'all' ? 'on' : '' }}" href="{{ route('studio.bookings', array_filter(['q' => $searchQuery ?? null])) }}">All</a>
      <a class="fchip {{ ($activeFilter ?? '') === 'upcoming' ? 'on' : '' }}" href="{{ route('studio.bookings', array_filter(['f' => 'upcoming', 'q' => $searchQuery ?? null])) }}">Upcoming</a>
      <a class="fchip {{ ($activeFilter ?? '') === 'past' ? 'on' : '' }}" href="{{ route('studio.bookings', array_filter(['f' => 'past', 'q' => $searchQuery ?? null])) }}">Past</a>
      @foreach($artistFilters as $af)
        <a class="fchip {{ ($activeFilter ?? '') === $af['slug'] ? 'on' : '' }}" href="{{ route('studio.bookings', array_filter(['f' => $af['slug'], 'q' => $searchQuery ?? null])) }}">{{ $af['name'] }}</a>
      @endforeach
    </div>
    <form method="GET" action="{{ route('studio.bookings') }}" class="search">
      @if(($activeFilter ?? 'all') !== 'all')
        <input type="hidden" name="f" value="{{ $activeFilter }}">
      @endif
      <span class="ms">search</span>
      <input type="search" name="q" value="{{ $searchQuery ?? '' }}" placeholder="Client or booking #">
    </form>
  </div>

  <div class="card" style="overflow:hidden" id="bktable">
    @if($bookings->isEmpty())
      <div class="empty">
        <div style="font-weight:700;color:var(--ink);margin-bottom:4px">No bookings yet</div>
        <div>When clients book your artists through the studio, they’ll show up here.</div>
      </div>
    @else
      <table>
        <thead>
          <tr>
            <th>Client</th>
            <th>Artist</th>
            <th>Service</th>
            <th>Date</th>
            <th>Status</th>
            <th>Booked through</th>
            <th>Studio share</th>
            <th></th>
          </tr>
        </thead>
        <tbody>
          @foreach($bookings as $row)
            <tr>
              <td data-label="Client"><b style="font-weight:600">{{ $row['client'] }}</b></td>
              <td data-label="Artist">{{ $row['artist'] }}</td>
              <td data-label="Service" class="muted">{{ $row['service'] }}</td>
              <td data-label="Date">{{ $row['date'] }}</td>
              <td data-label="Status"><span class="pill {{ $row['status_pill'] }}">{{ $row['status'] }}</span></td>
              <td data-label="Booked through"><span class="pill nd k">{{ $row['through'] }}</span></td>
              <td data-label="Studio share"><b>{{ $row['share'] }}</b></td>
              <td data-label="">
                <button type="button" class="ms faint js-view-booking" style="border:0;background:none;cursor:pointer;color:var(--faint)"
                  data-id="{{ $row['id'] }}"
                  aria-label="View booking">visibility</button>
                <script type="application/json" id="bk-data-{{ $row['id'] }}">@json($row['modal'])</script>
              </td>
            </tr>
          @endforeach
        </tbody>
      </table>
    @endif
  </div>

  <div class="help">Studio page: the client picked the artist on your studio page. Artist link: the client came through the artist's own page. Studio (New booking): your studio made a payment link for the artist. Either way, the artist manages the booking and the split is the same.</div>
</div>

@include('studio.bookings.partials.booking-detail-modal')
@include('studio.bookings.partials.message-artist-modal')
@include('studio.bookings.partials.payment-link-modal')
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/jquery@3.7.1/dist/jquery.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
@include('studio.bookings.partials.payment-link-modal-script')
@include('studio.bookings.partials.booking-detail-modal-script')
<script>
  window.studioBookingMessageModal = {
    apiBase: @json(url('/api/chat')),
    csrf: @json(csrf_token()),
    userId: @json((string) auth()->id()),
    inboxUrl: @json(route('studio.chat.index')),
  };
</script>
<script src="{{ asset('js/studio-booking-message-modal.js') }}?v=1"></script>
@endpush
