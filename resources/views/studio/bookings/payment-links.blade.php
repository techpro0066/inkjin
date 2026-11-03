@extends('layouts.studio-dashboard-layout')

@section('title', 'Bookings · Payment links')

@push('styles')
<style>
  .bk .tabs a{display:inline-flex;align-items:center;gap:7px}
  .bk .tabs .n{background:var(--chip);color:var(--muted);border-radius:10px;font-size:11px;padding:1px 7px;font-weight:600}
  .bk .tabs .n.zero{background:#FFF3DE;color:#B86E00}
  .bk .tabs a.on .n{background:var(--ink);color:#fff}
  .bk .tabs a.on .n.zero{background:#FFF3DE;color:#B86E00}
  .bk .empty{padding:64px 22px;text-align:center;color:var(--muted)}
  .bk .empty .ms{font-size:40px;color:var(--faint);margin-bottom:10px}
</style>
@endpush

@section('content')
<div class="bk">
  <div class="head">
    <div>
      <h1>Bookings</h1>
      <div class="sub">Bookings and requests for your artists at {{ $studioName }}.</div>
    </div>
  </div>

  @include('studio.bookings.partials.tabs')

  <div class="card">
    <div class="empty">
      <div class="ms">link</div>
      <div style="font-weight:700;color:var(--ink);margin-bottom:4px">Nothing here yet</div>
      <div>Studio payment links will show up in this tab.</div>
    </div>
  </div>
</div>
@endsection
