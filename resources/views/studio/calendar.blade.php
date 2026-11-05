@extends('layouts.studio-dashboard-layout')

@section('title', 'Calendar')

@push('styles')
@include('studio.bookings.partials.payment-link-modal-assets')
@include('studio.bookings.partials.booking-detail-modal-styles')
<style>
  .cal-page .cal{display:grid;grid-template-columns:64px repeat(6,1fr);border:1px solid var(--line);border-radius:14px;overflow:hidden;background:#fff}
  .cal-page .cal .hd{padding:10px;font-size:12px;font-weight:700;color:var(--muted);border-bottom:1px solid var(--line);background:#FBF8FC;text-align:center}
  .cal-page .cal .tm{font-size:11px;color:var(--faint);padding:6px 8px;border-top:1px solid #F3EEF4;height:52px}
  .cal-page .cal .cell{border-top:1px solid #F3EEF4;border-left:1px solid #F3EEF4;height:52px;position:relative}
  .cal-page .ev{position:absolute;left:4px;right:4px;border-radius:8px;padding:5px 7px;font-size:11.5px;line-height:1.3;overflow:hidden;cursor:pointer;z-index:2;border:0;text-align:left;font:inherit;display:block;width:auto}
  .cal-page .ev b{display:block;font-size:11.5px}
  .cal-page .ev.busy{background:repeating-linear-gradient(45deg,#F1EEF3,#F1EEF3 6px,#E9E4EC 6px,#E9E4EC 12px);color:#6F6874;cursor:default}
  .cal-page .cal-note{border:1.5px dashed #B99BE0;background:#FBF5FF;border-radius:12px;padding:10px 14px;font-size:12.5px;color:#5B2A94;display:flex;gap:8px;align-items:center;margin-top:14px}
  .cal-page .cal-note .ms{font-size:18px;color:#5B2A94;flex:none}
  .cal-page .legend{gap:14px;font-size:12px;color:var(--muted);flex-wrap:wrap}
  .cal-page .legend i{width:10px;height:10px;border-radius:3px;display:inline-block}
  .cal-page .range.in{min-height:0;width:auto;white-space:nowrap}
  .cal-page select.in{appearance:auto;cursor:pointer;width:auto;min-width:200px;padding:8px 12px;min-height:0}
  .cal-page .empty-cal{padding:36px 22px;text-align:center;color:var(--muted);border:1px solid var(--line);border-radius:14px;background:#fff;margin-top:4px}
  @media(max-width:900px){
    .cal-page .cal{grid-template-columns:48px repeat(6,minmax(90px,1fr));overflow-x:auto}
    .cal-page .ev b{display:none}
    .cal-page .ev{font-size:10px;padding:2px 4px}
  }
</style>
@endpush

@section('content')
<div class="cal-page">
  <div class="head">
    <div>
      <h1>
        Calendar
        <a class="help-q" href="https://help.inkjin.com/en/articles/17338291-studio-dashboard-calendar" target="_blank" rel="noopener" title="Help with this page" aria-label="Help with this page"><span class="ms">help</span></a>
      </h1>
      <div class="sub">Bookings of all your artists at {{ $studioName }}.</div>
    </div>
    <div class="btns">
      <a class="btn ghost" href="{{ route('studio.calendar', ['week' => $prevWeek]) }}" aria-label="Previous week"><span class="ms">chevron_left</span></a>
      <div class="in range">{{ $rangeLabel }}</div>
      <a class="btn ghost" href="{{ route('studio.calendar', ['week' => $nextWeek]) }}" aria-label="Next week"><span class="ms">chevron_right</span></a>
      @if ($weekStart !== $thisWeek)
        <a class="btn ghost sm" href="{{ route('studio.calendar', ['week' => $thisWeek]) }}">Today</a>
      @endif
      @include('studio.bookings.partials.new-booking-button')
    </div>
  </div>

  <div class="row" style="justify-content:space-between;margin:22px 0 14px;flex-wrap:wrap;gap:10px">
    <label class="row" style="gap:8px;font-size:13px;font-weight:600">
      <span class="ms" style="font-size:18px;color:var(--muted)">filter_list</span>
      <select class="in" id="calart" aria-label="Show artist">
        <option value="all">All artists</option>
        @foreach ($artists as $artist)
          <option value="{{ $artist['slug'] }}">● {{ $artist['name'] }}</option>
        @endforeach
      </select>
    </label>
    <div class="row legend">
      @foreach ($artists as $artist)
        <span class="row" style="gap:6px" data-legend="{{ $artist['slug'] }}"><i style="background:{{ $artist['fg'] }}"></i>{{ $artist['name'] }}</span>
      @endforeach
      <span class="row" style="gap:6px"><i style="background:#E9E4EC"></i>Busy</span>
    </div>
  </div>

  <div id="calwrap">
    <div class="cal">
      <div class="hd"></div>
      @foreach ($dayHeaders as $header)
        <div class="hd">{{ $header }}</div>
      @endforeach

      @foreach ($hours as $hi => $hourLabel)
        <div class="tm">{{ $hourLabel }}</div>
        @for ($d = 0; $d < 6; $d++)
          <div class="cell">
            @foreach (($eventsByCell[$d][$hi] ?? []) as $ev)
              <button
                type="button"
                class="ev js-view-booking"
                data-id="{{ $ev['id'] }}"
                data-f="{{ $ev['filter'] }}"
                style="top:{{ $ev['top'] }}px;height:{{ $ev['height'] }}px;background:{{ $ev['bg'] }};color:{{ $ev['fg'] }};border-left:3px solid {{ $ev['fg'] }}"
              >
                <b>{{ $ev['artist'] }}</b>{{ $ev['label'] }}
              </button>
              <script type="application/json" id="bk-data-{{ $ev['id'] }}">@json($ev['modal'])</script>
            @endforeach
          </div>
        @endfor
      @endforeach
    </div>
  </div>

  @if (! ($hasEvents ?? false))
    <div class="empty-cal">
      <div style="font-weight:700;color:var(--ink);margin-bottom:4px">No studio bookings this week</div>
      <div>Sessions at {{ $studioName }} show here once clients pay or book.</div>
    </div>
  @endif

  <div class="cal-note"><span class="ms">lock</span>You see full details only for sessions at your studio. Time an artist spends at another studio or on personal events shows as Busy, with no details.</div>
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
<script>
(function () {
  var sel = document.getElementById('calart');
  if (!sel) return;
  function apply(key) {
    document.querySelectorAll('#calwrap [data-f]').forEach(function (el) {
      el.style.display = (key === 'all' || el.getAttribute('data-f') === key) ? '' : 'none';
    });
    document.querySelectorAll('.cal-page [data-legend]').forEach(function (el) {
      el.style.display = (key === 'all' || el.getAttribute('data-legend') === key) ? '' : 'none';
    });
  }
  sel.addEventListener('change', function () { apply(this.value); });
})();
</script>
@endpush
