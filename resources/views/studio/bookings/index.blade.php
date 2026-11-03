@extends('layouts.studio-dashboard-layout')

@section('title', 'Bookings')

@push('styles')
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

  .smodal{position:fixed;inset:0;background:rgba(20,10,30,.45);display:flex;align-items:center;justify-content:center;padding:24px;z-index:400}
  .smodal[hidden]{display:none!important}
  .smodal .smbox{background:#fff;border-radius:22px;width:min(720px,100%);max-height:calc(100vh - 48px);max-height:calc(100dvh - 48px);display:flex;flex-direction:column;box-shadow:0 20px 60px rgba(0,0,0,.25);overflow:hidden}
  .smodal .smh{display:flex;align-items:flex-start;justify-content:space-between;gap:12px;padding:22px 24px 14px;border-bottom:1px solid #F0EAF2;flex-shrink:0}
  .smodal .smh h2{font-size:20px;font-weight:800;margin:0;letter-spacing:-.3px;color:#1A1A1A}
  .smodal .smb{padding:16px 24px 20px;overflow:auto;min-height:0;flex:1 1 auto}
  .smodal .smf{padding:14px 24px;border-top:1px solid #F0EAF2;display:flex;align-items:center;justify-content:flex-end;gap:10px;background:#FCFAFD;flex-shrink:0}
  .smodal .x{border:0;background:none;cursor:pointer;color:#9A929E;width:34px;height:34px;border-radius:50%;display:flex;align-items:center;justify-content:center}
  .smodal .x:hover{background:#F4F0F6;color:#1A1A1A}
  .smodal .sbdm-tabs{display:flex;gap:28px;border-bottom:1px solid #E8DFEA;margin:0 0 16px;overflow-x:auto}
  .smodal .sbdm-tabs button{padding:0 2px 12px;font-weight:600;color:#6F6874;font-size:14.5px;background:none;border:0;cursor:pointer;flex-shrink:0;white-space:nowrap;font:inherit}
  .smodal .sbdm-tabs button.on{color:#1A1A1A;box-shadow:inset 0 -2.5px 0 #1A1A1A}
  .smodal .sbdm-kv{display:flex;justify-content:space-between;gap:12px;padding:10px 0;border-bottom:1px solid #F3EEF4;font-size:13.5px}
  .smodal .sbdm-kv > span:first-child{color:#6F6874}
  .smodal .sbdm-kv > span:last-child{font-weight:600;text-align:right;color:#1A1A1A}
  .smodal .sbdm-grid2{display:grid;grid-template-columns:1fr 1fr;column-gap:24px}
  .smodal .sbdm-pill{display:inline-flex;align-items:center;gap:5px;border-radius:20px;font-size:11.5px;font-weight:600;padding:3px 9px;white-space:nowrap}
  .smodal .sbdm-pill:before{content:'';width:5px;height:5px;border-radius:50%;background:currentColor}
  .smodal .sbdm-pill.g{background:#E6F8EE;color:#00A650}
  .smodal .sbdm-pill.a{background:#FFF3DE;color:#B86E00}
  .smodal .sbdm-pill.r{background:#FDECEC;color:#C62828}
  .smodal .sbdm-pill.k{background:#F0EDF2;color:#6F6874}
  .smodal .sbdm-pill.b{background:#E8F0FF;color:#1E5BD8}
  .smodal .sbdm-faint{color:#9A929E}
  .smodal .sbdm-muted{color:#6F6874}
  .smodal .sbdm-tint{background:#FAF0FB;border:1px solid #F1E7F3;border-radius:16px;padding:10px 12px;font-size:12.5px;line-height:1.6;color:#5B2A94}
  .smodal .sbdm-btn{display:inline-flex;align-items:center;gap:8px;background:#1A1A1A;color:#fff;border-radius:10px;padding:10px 16px;font-weight:600;font-size:13.5px;white-space:nowrap;border:0;cursor:pointer;font:inherit;text-decoration:none}
  .smodal .sbdm-btn.ghost{background:#fff;color:#1A1A1A;border:1px solid #E8DFEA}
  .smodal .sbdm-btn .ms{font-size:18px}
  .smodal .sbdm-ans{border:1px solid #E8DFEA;border-radius:12px;padding:10px 12px;margin-top:8px;font-size:13px}
  .smodal .sbdm-ans b{display:block;font-size:12px;color:#6F6874;font-weight:600;margin-bottom:2px}
  .smodal .sbdm-req-banner{display:flex;align-items:center;gap:12px;background:#FAF0FB;border:1px solid #F1E7F3;border-radius:14px;padding:12px 14px}
  .smodal .sbdm-req-ic{width:38px;height:38px;border-radius:50%;background:#fff;color:#3E007C;display:flex;align-items:center;justify-content:center;flex-shrink:0;box-shadow:0 0 0 1px #F1E7F3}
  .smodal .sbdm-req-ic .ms{font-size:20px}
  .smodal .sbdm-req-banner b{display:block;font-size:14.5px;font-weight:700;color:#1A1A1A}
  .smodal .sbdm-req-banner .sbdm-faint{font-size:12.5px;margin-top:2px}
  .smodal .sbdm-req-qa{display:grid;grid-template-columns:1fr 1fr;column-gap:20px;margin-top:14px}
  .smodal .sbdm-req-qa-item{padding:12px 0;border-bottom:1px solid #F3EEF4;min-width:0}
  .smodal .sbdm-req-qa-item .lbl{font-size:10.5px;letter-spacing:.8px;font-weight:700;color:#6F6874;text-transform:uppercase;margin:0}
  .smodal .sbdm-req-qa-item .val{font-size:14px;font-weight:600;margin-top:4px;color:#1A1A1A;word-break:break-word;line-height:1.4}
  .smodal .sbdm-req-caption{font-size:12.5px;color:#9A929E;font-style:italic;margin:14px 0 0;line-height:1.45}
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
    .smodal .sbdm-grid2{grid-template-columns:1fr}
    .smodal .sbdm-req-qa{grid-template-columns:1fr}
    .smodal .smf{flex-wrap:wrap}
    .smodal .sbdm-btn{flex:1 1 auto;justify-content:center}
  }
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

{{-- Booking detail modal (artist A02 layout, studio-facing money) --}}
<div class="smodal" id="m-booking" hidden>
  <div class="smbox" role="dialog" aria-modal="true" aria-labelledby="m-booking-t">
    <div class="smh">
      <div><h2 id="m-booking-t">Booking</h2></div>
      <button type="button" class="x" data-close aria-label="Close"><span class="ms">close</span></button>
    </div>
    <div class="smb">
      <div class="sbdm-tabs" role="tablist" aria-label="Booking detail sections">
        <button type="button" class="on" data-sbdm-tab="details" role="tab" aria-selected="true">Details</button>
        <button type="button" data-sbdm-tab="request" role="tab" aria-selected="false">Original request</button>
      </div>

      <div id="sbdmPanelDetails" role="tabpanel">
        <div style="display:flex;align-items:center;gap:16px;margin-bottom:8px">
          <div id="sbdmImage" style="width:84px;height:84px;border-radius:14px;background:#EEE center/cover;flex-shrink:0"></div>
          <div>
            <div id="sbdmRef" class="sbdm-faint" style="font-size:12.5px;font-weight:600">—</div>
            <span id="sbdmStatus" class="sbdm-pill g" style="margin:6px 0;display:inline-flex">Confirmed</span>
            <div id="sbdmType" class="sbdm-muted" style="font-size:13px">—</div>
          </div>
        </div>

        <div class="sbdm-grid2">
          <div>
            <div class="sbdm-kv"><span>Client</span><span id="sbdmClient">—</span></div>
            <div class="sbdm-kv"><span>Artist</span><span id="sbdmArtist">—</span></div>
            <div class="sbdm-kv"><span>Service</span><span id="sbdmService">—</span></div>
            <div class="sbdm-kv"><span>Booked through</span><span id="sbdmThrough">—</span></div>
            <div class="sbdm-kv"><span>Deposit</span><span id="sbdmDeposit">—</span></div>
          </div>
          <div>
            <div class="sbdm-kv"><span>Date</span><span id="sbdmDate">—</span></div>
            <div class="sbdm-kv"><span>Time</span><span id="sbdmTime">—</span></div>
            <div class="sbdm-kv"><span>Duration</span><span id="sbdmDuration">—</span></div>
            <div class="sbdm-kv"><span>Timezone</span><span id="sbdmTimezone">—</span></div>
            <div class="sbdm-kv"><span>Balance (est.)</span><span id="sbdmBalance">—</span></div>
          </div>
        </div>

        <div style="margin-top:18px">
          <div style="display:flex;align-items:center;justify-content:space-between;gap:12px;margin-bottom:4px">
            <div class="lbl" style="margin:0;font-size:11px;letter-spacing:.8px;font-weight:700;color:#6F6874;text-transform:uppercase">Money for this booking</div>
            <span id="sbdmMoneyPill" class="sbdm-pill a">Pending</span>
          </div>
          <div class="sbdm-kv" style="padding:8px 0;border-bottom:1px solid #F3EEF4"><span id="sbdmMoneyDepositLabel">Client paid · deposit</span><span id="sbdmMoneyDeposit">—</span></div>
          <div class="sbdm-kv" id="sbdmBookingFeeRow" style="padding:8px 0;border-bottom:1px solid #F3EEF4;display:none"><span>Service Booking Fee <span id="sbdmBookingFeeNote" class="sbdm-faint" style="font-size:12px"></span></span><span id="sbdmBookingFee">—</span></div>
          <div class="sbdm-kv" style="padding:8px 0;border-bottom:1px solid #F3EEF4"><span>Stripe fee <span class="sbdm-faint" style="font-size:12px">1.5% + €0.25</span></span><span id="sbdmStripeFee">—</span></div>
          <div class="sbdm-kv" id="sbdmSplitRow" style="padding:8px 0;border-bottom:1px solid #F3EEF4;display:none"><span>Split</span><span id="sbdmSplitValue">—</span></div>
          <div class="sbdm-kv" id="sbdmArtistGetsRow" style="padding:8px 0;border-bottom:1px solid #F3EEF4;display:none"><span><span id="sbdmArtistGetsLabel">Artist gets</span> <span id="sbdmArtistGetsNote" class="sbdm-faint" style="font-size:12px"></span></span><span id="sbdmArtistGets">—</span></div>
          <div class="sbdm-kv" id="sbdmStudioShareRow" style="padding:8px 0;border-bottom:1px solid #F3EEF4;display:none"><span>Studio share <span id="sbdmStudioShareNote" class="sbdm-faint" style="font-size:12px"></span></span><span id="sbdmStudioShare">—</span></div>
          <div class="sbdm-kv" id="sbdmStudioStripeRow" style="padding:8px 0;border-bottom:1px solid #F3EEF4;display:none"><span>Your part of the Stripe fee <span id="sbdmStudioStripeNote" class="sbdm-faint" style="font-size:12px"></span></span><span id="sbdmStudioStripe">—</span></div>
          <div class="sbdm-kv" style="padding:12px 0 4px;font-size:15px;border-bottom:0"><span style="color:#1A1A1A;font-weight:700">Studio gets</span><span id="sbdmStudioGets" style="font-weight:800;font-size:18px">—</span></div>
          <div id="sbdmFootnote" class="sbdm-faint" style="font-size:12.5px;margin-top:2px;display:none"></div>
          <div id="sbdmBalanceNote" class="sbdm-tint" style="margin-top:12px;display:none"></div>
        </div>
      </div>

      <div id="sbdmPanelRequest" role="tabpanel" hidden>
        <div id="sbdmReqBanner" class="sbdm-req-banner">
          <div class="sbdm-req-ic"><span class="ms" id="sbdmReqIcon">brush</span></div>
          <div style="min-width:0">
            <b id="sbdmReqTitle">Custom tattoo request</b>
            <div id="sbdmReqMeta" class="sbdm-faint">—</div>
          </div>
        </div>
        <div id="sbdmQaList" class="sbdm-req-qa"></div>
        <p id="sbdmQaEmpty" class="sbdm-muted" style="font-size:13.5px;margin-top:14px;display:none">No intake form answers for this booking.</p>
        <p id="sbdmReqCaption" class="sbdm-req-caption" style="display:none"></p>
      </div>
    </div>
    <div class="smf">
      <a id="sbdmMessage" class="sbdm-btn ghost" href="#" style="margin-right:auto;display:none"><span class="ms">chat_bubble</span><span id="sbdmMessageLabel">Message</span></a>
      <button type="button" class="sbdm-btn" data-close>Close</button>
    </div>
  </div>
</div>
@endsection

@push('scripts')
<script>
(function () {
  var modal = document.getElementById('m-booking');
  if (!modal) return;
  if (modal.parentNode !== document.body) document.body.appendChild(modal);

  function esc(s) {
    return String(s == null ? '' : s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
  }
  function text(id, v) {
    var el = document.getElementById(id);
    if (el) el.textContent = v == null || v === '' ? '—' : String(v);
  }
  function show(id, on) {
    var el = document.getElementById(id);
    if (el) el.style.display = on ? '' : 'none';
  }
  function loadModal(id) {
    var el = document.getElementById('bk-data-' + id);
    if (!el) return null;
    try { return JSON.parse(el.textContent || '{}'); } catch (e) { return null; }
  }

  function setTab(name) {
    modal.querySelectorAll('[data-sbdm-tab]').forEach(function (btn) {
      var on = btn.getAttribute('data-sbdm-tab') === name;
      btn.classList.toggle('on', on);
      btn.setAttribute('aria-selected', on ? 'true' : 'false');
    });
    document.getElementById('sbdmPanelDetails').hidden = name !== 'details';
    document.getElementById('sbdmPanelRequest').hidden = name !== 'request';
  }

  function open(btn) {
    var data = loadModal(btn.getAttribute('data-id'));
    if (!data) return;

    document.getElementById('m-booking-t').textContent = data.title || 'Booking';
    text('sbdmRef', data.reference);
    var status = document.getElementById('sbdmStatus');
    status.className = 'sbdm-pill ' + (data.status_pill || 'k');
    status.style.margin = '6px 0';
    status.style.display = 'inline-flex';
    status.textContent = data.status || '—';
    text('sbdmType', data.type_label);
    text('sbdmClient', data.client);
    text('sbdmArtist', data.artist);
    text('sbdmService', data.service);
    text('sbdmThrough', data.through);
    text('sbdmDeposit', data.deposit);
    text('sbdmDate', data.date);
    text('sbdmTime', data.time);
    text('sbdmDuration', data.duration);
    text('sbdmTimezone', data.timezone);
    text('sbdmBalance', data.balance);

    var img = document.getElementById('sbdmImage');
    if (data.image_url) {
      img.style.backgroundImage = 'url("' + String(data.image_url).replace(/"/g, '\\"') + '")';
    } else {
      img.style.backgroundImage = '';
    }

    var moneyPill = document.getElementById('sbdmMoneyPill');
    moneyPill.className = 'sbdm-pill ' + (data.money_pill_class || 'a');
    moneyPill.textContent = data.money_pill || 'Pending';

    text('sbdmMoneyDepositLabel', data.deposit_money_label || 'Client paid · deposit');
    text('sbdmMoneyDeposit', data.deposit_money);
    text('sbdmStripeFee', data.stripe_fee);

    if (data.booking_fee) {
      show('sbdmBookingFeeRow', true);
      text('sbdmBookingFee', data.booking_fee);
      text('sbdmBookingFeeNote', data.booking_fee_note ? '(' + data.booking_fee_note + ')' : '');
    } else {
      show('sbdmBookingFeeRow', false);
    }

    var hasSplit = !!data.has_split;
    show('sbdmSplitRow', hasSplit);
    show('sbdmArtistGetsRow', hasSplit);
    show('sbdmStudioShareRow', hasSplit);
    show('sbdmStudioStripeRow', hasSplit);
    if (hasSplit) {
      text('sbdmSplitValue', data.split_value);
      text('sbdmArtistGetsLabel', data.artist_gets_label || 'Artist gets');
      text('sbdmArtistGetsNote', data.artist_gets_note ? '(' + data.artist_gets_note + ')' : '');
      text('sbdmArtistGets', data.artist_gets);
      text('sbdmStudioShareNote', data.studio_share_note ? '(' + data.studio_share_note + ')' : '');
      text('sbdmStudioShare', data.studio_share);
      text('sbdmStudioStripeNote', data.studio_stripe_note ? '(' + data.studio_stripe_note + ')' : '');
      text('sbdmStudioStripe', data.studio_stripe);
    }
    text('sbdmStudioGets', data.studio_gets);

    if (data.footnote) {
      show('sbdmFootnote', true);
      text('sbdmFootnote', data.footnote);
    } else {
      show('sbdmFootnote', false);
    }
    if (data.balance_note) {
      show('sbdmBalanceNote', true);
      text('sbdmBalanceNote', data.balance_note);
    } else {
      show('sbdmBalanceNote', false);
    }

    var req = data.request || {};
    text('sbdmReqTitle', req.title || 'Original request');
    text('sbdmReqMeta', req.meta || '—');
    var reqIcon = document.getElementById('sbdmReqIcon');
    if (reqIcon) reqIcon.textContent = req.icon || 'brush';

    var qa = Array.isArray(req.fields) && req.fields.length
      ? req.fields
      : (Array.isArray(data.answers) ? data.answers : []);
    var qaList = document.getElementById('sbdmQaList');
    var qaEmpty = document.getElementById('sbdmQaEmpty');
    var reqCaption = document.getElementById('sbdmReqCaption');
    qaList.innerHTML = '';
    if (!qa.length) {
      qaEmpty.style.display = '';
      qaList.style.display = 'none';
    } else {
      qaEmpty.style.display = 'none';
      qaList.style.display = '';
      qa.forEach(function (a) {
        var label = a.label || a.q || '';
        var value = a.value || a.a || '—';
        qaList.innerHTML += '<div class="sbdm-req-qa-item"><div class="lbl">' + esc(label) + '</div><div class="val">' + esc(value) + '</div></div>';
      });
    }
    if (req.caption) {
      reqCaption.style.display = '';
      reqCaption.textContent = req.caption;
    } else {
      reqCaption.style.display = 'none';
      reqCaption.textContent = '';
    }

    var msg = document.getElementById('sbdmMessage');
    if (data.message_url) {
      msg.style.display = '';
      msg.href = data.message_url;
      document.getElementById('sbdmMessageLabel').textContent = data.message_label || 'Message';
    } else {
      msg.style.display = 'none';
    }

    setTab('details');
    modal.hidden = false;
  }

  modal.querySelectorAll('[data-sbdm-tab]').forEach(function (btn) {
    btn.addEventListener('click', function () { setTab(btn.getAttribute('data-sbdm-tab')); });
  });
  document.querySelectorAll('.js-view-booking').forEach(function (btn) {
    btn.addEventListener('click', function () { open(btn); });
  });
  modal.querySelectorAll('[data-close]').forEach(function (el) {
    el.addEventListener('click', function () { modal.hidden = true; });
  });
  modal.addEventListener('click', function (e) {
    if (e.target === modal) modal.hidden = true;
  });
  document.addEventListener('keydown', function (e) {
    if (e.key === 'Escape' && !modal.hidden) modal.hidden = true;
  });
})();
</script>
@endpush
