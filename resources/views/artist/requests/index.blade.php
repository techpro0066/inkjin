@extends('layouts.new-artist-dashboard-layout')

@section('title', 'Requests')

@php
  $activeNav = 'bookings';
  $feed = $feed ?? collect();
  $typeCounts = $typeCounts ?? ['all' => 0, 'design' => 0, 'custom' => 0, 'guest' => 0];
  $statusCounts = $statusCounts ?? ['new' => 0, 'quote_sent' => 0, 'booked' => 0, 'declined' => 0];
  $needsReplyCount = (int) ($needsReplyCount ?? 0);
  $repliedCount = (int) ($repliedCount ?? 0);
  $initialType = $initialType ?? 'all';
  $needsReplyItems = $feed->where('needsReply', true)->values();
  $repliedItems = $feed->where('needsReply', false)->values();
@endphp

@section('styles')
@include('artist.partials.payment-link-modal-styles')
<script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
<script>
  tailwind.config = {
    darkMode: 'class',
    theme: {
      extend: {
        colors: {
          'surface-container-high': '#ece6ef',
          'surface-container-lowest': '#ffffff',
          'surface-container': '#f2ecf5',
          'background': '#fdf7ff',
          'primary': '#1a1a1a',
          'surface-dim': '#ded8e1',
          'on-surface-variant': '#494552',
          'surface-container-low': '#f8f1fb',
          'surface': '#fdf7ff',
          'outline': '#7a7583',
          'error-container': '#ffdad6',
          'error': '#ba1a1a',
          'on-primary': '#ffffff',
          'primary-container': '#333333',
          'on-surface': '#1c1b21',
          'outline-variant': '#cac4d3',
          'on-background': '#1c1b21',
          'surface-variant': '#e6e0ea'
        }
      }
    }
  };
</script>
<style>
  .bk-page .chip{cursor:pointer;font:inherit}
  .bk-page .chip.on b{opacity:.85}
  .bk-page .tint{padding:16px 18px}
  .bk-page .bk-filters{display:flex;align-items:center;gap:10px;flex-wrap:wrap}
  .bk-page .bk-filters .bk-search{flex:1;min-width:180px}
  .bk-page .bk-filters .bk-sort{width:170px;flex:none}
  .bk-page .bk-filters .in{margin:0}
  .bk-page .bk-filters select.in{
    display:block;appearance:none;-webkit-appearance:none;
    background-image:url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='16' height='16' viewBox='0 0 24 24' fill='none' stroke='%239A929E' stroke-width='2.2' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpath d='M6 9l6 6 6-6'/%3E%3C/svg%3E");
    background-repeat:no-repeat;background-position:right 12px center;padding-right:34px;cursor:pointer
  }
  .bk-page .bk-filters .in input[type=search],
  .bk-page .bk-filters .in input[type=text]{
    border:0;outline:0;font:inherit;flex:1;min-width:0;background:none;color:inherit;padding:0;margin:0;box-shadow:none
  }
  .bk-page .head{margin:0}
  .bk-page .tabs{margin:26px 0 24px}
  .bk-page .card[data-req]{cursor:default}
  .bk-page .req-section-head{display:flex;justify-content:space-between;margin:20px 0 10px}
  .bk-page .req-section-head.replied{margin:22px 0 10px}
  .bk-page .req-empty{text-align:center;padding:40px 20px;color:var(--muted)}
  @media (max-width:900px){
    .bk-page .bk-filters .bk-sort{width:100%;flex:1 1 100%}
    .bk-page .card[data-req]{display:block!important;padding:14px 16px!important;position:relative}
    .bk-page .card[data-req] .rini{display:none}
    .bk-page .card[data-req]>div:nth-child(2) .row{padding-right:56px;gap:6px!important}
    .bk-page .card[data-req] .rdate{position:absolute;top:15px;right:16px;padding:0!important;flex-basis:auto!important;width:auto!important}
    .bk-page .card[data-req] .rbtns{padding-left:0!important;margin-top:12px;display:flex;gap:8px;width:100%}
    .bk-page .card[data-req] .rbtns .btn{flex:1;justify-content:center;padding:9px 12px}
    .bk-page .rf .lbl{display:none}
    .bk-page .rf>.row:last-child{flex-direction:column;align-items:stretch!important;gap:8px!important;margin-top:10px!important}
    .bk-page .rf .chips{flex-wrap:nowrap;overflow-x:auto;scrollbar-width:none}
    .bk-page .rf .chips .chip{flex-shrink:0}
    }
</style>
@endsection

@section('content')
<div class="bk-page">
  <div class="head">
          <div>
      <h1>Bookings</h1>
      <div class="sub">Requests, confirmed bookings and payment links in one place.</div>
          </div>
    <button type="button" class="btn" data-open-payment-link>
      <span class="ms">add</span> New payment link
    </button>
      </div>

  @include('artist.bookings.partials.page-tabs', [
    'activeTab' => 'requests',
    'requestsCount' => $requestsCount ?? 0,
    'bookingsTotalCount' => $bookingsTotalCount ?? 0,
    'paymentLinksCount' => $paymentLinksCount ?? 0,
  ])

  @if (!empty($quotesBlockedMessage))
    <div class="note" style="margin-bottom:16px">
      <span class="ms">account_balance</span>
      <div style="flex:1">
        <b>Finish payouts to reply to requests</b>
        <div style="margin-top:2px">{{ $quotesBlockedMessage }}</div>
      </div>
      @if (Route::has('settings.payment'))
        <a href="{{ route('settings.payment') }}" class="btn sm pri" style="text-decoration:none;flex-shrink:0">Finish payouts</a>
      @endif
    </div>
  @endif

  <div class="tint rf" style="padding:16px 18px">
    <div class="row bk-filters" style="gap:10px">
      <div class="bk-search">
        <div class="in">
          <span class="ms">search</span>
          <input type="search" id="reqSearch" placeholder="Search client name or reference" autocomplete="off">
        </div>
                </div>
      <div class="bk-sort">
        <select class="in" id="reqSort">
          <option value="recent" selected>Most recent</option>
          <option value="oldest">Oldest first</option>
        </select>
                </div>
              </div>
    <div class="row" style="gap:28px;margin-top:14px;align-items:flex-start;flex-wrap:wrap">
      <div>
        <div class="lbl" style="margin-bottom:8px">Type</div>
        <div class="chips" id="reqTypeChips">
          <button type="button" class="chip{{ $initialType === 'all' ? ' on' : '' }}" data-type="all">All <b>{{ $typeCounts['all'] ?? 0 }}</b></button>
          <button type="button" class="chip{{ $initialType === 'design' ? ' on' : '' }}" data-type="design">Design <b>{{ $typeCounts['design'] ?? 0 }}</b></button>
          <button type="button" class="chip{{ $initialType === 'custom' ? ' on' : '' }}" data-type="custom">Custom <b>{{ $typeCounts['custom'] ?? 0 }}</b></button>
          <button type="button" class="chip{{ $initialType === 'guest' ? ' on' : '' }}" data-type="guest">Guest spot <b>{{ $typeCounts['guest'] ?? 0 }}</b></button>
        </div>
      </div>
      <div>
        <div class="lbl" style="margin-bottom:8px">Status</div>
        <div class="chips" id="reqStatusChips">
          <button type="button" class="chip" data-status="new">New <b>{{ $statusCounts['new'] ?? 0 }}</b></button>
          <button type="button" class="chip" data-status="quote_sent">Quote sent @if(($statusCounts['quote_sent'] ?? 0) > 0)<b>{{ $statusCounts['quote_sent'] }}</b>@endif</button>
          <button type="button" class="chip" data-status="booked">Booked @if(($statusCounts['booked'] ?? 0) > 0)<b>{{ $statusCounts['booked'] }}</b>@endif</button>
          <button type="button" class="chip" data-status="declined">Declined @if(($statusCounts['declined'] ?? 0) > 0)<b>{{ $statusCounts['declined'] }}</b>@endif</button>
              </div>
                </div>
                </div>
              </div>

  <div id="needsReplySection">
    <div class="req-section-head" id="needsReplyHead">
      <div class="lbl">Needs a reply · <span id="needsReplyVisibleCount">{{ $needsReplyCount }}</span></div>
              </div>
    <div class="grid" style="gap:10px" id="needsReplyList">
      @foreach ($needsReplyItems as $item)
        @include('artist.requests.partials.feed-card', ['item' => $item])
      @endforeach
                </div>
              </div>

  <div id="repliedSection">
    <div class="req-section-head replied" id="repliedHead">
      <div class="lbl">Replied · <span id="repliedVisibleCount">{{ $repliedCount }}</span></div>
        </div>
    <div class="grid" style="gap:10px" id="repliedList">
      @foreach ($repliedItems as $item)
        @include('artist.requests.partials.feed-card', ['item' => $item])
      @endforeach
      </div>
    </div>

  <div id="requestsFeedEmpty" class="req-empty card pad" style="{{ $feed->isEmpty() ? '' : 'display:none' }};margin-top:16px">
    <p style="font-weight:600;color:var(--ink);margin-bottom:4px">No requests found</p>
    <p class="sub" style="margin:0">Try adjusting your filters or search.</p>
    </div>
  </div>

@include('artist.requests.partials.custom-detail-modals')
@include('artist.requests.partials.design-detail-modals')
@include('artist.partials.payment-link-modal')
@include('artist.partials.payment-link-modal-script')

  <script>
(function () {
  var typeFilter = @json($initialType);
  var statusFilter = 'all';
  var needsList = document.getElementById('needsReplyList');
  var repliedList = document.getElementById('repliedList');
  var needsSection = document.getElementById('needsReplySection');
  var repliedSection = document.getElementById('repliedSection');
  var emptyEl = document.getElementById('requestsFeedEmpty');
  var searchEl = document.getElementById('reqSearch');
  var sortEl = document.getElementById('reqSort');

  function statusPillClass(key) {
    if (key === 'quote_sent') return 'b';
    if (key === 'booked') return 'g';
    if (key === 'declined') return 'r';
    return 'p';
  }

  function rebuildCardActions(card) {
    var actions = card.querySelector('[data-card-actions]');
    if (!actions) return;
    var needsReply = card.dataset.needsReply === '1';
    var openAs = card.dataset.openAs || (card.dataset.kind === 'design' ? 'design' : 'custom');
    var id = card.dataset.id;
    var openFn = openAs === 'design' ? 'openRequestDetail' : 'openCustomRequestDetail';
    var ctaLabel = needsReply
      ? (openAs === 'design' ? 'View details & times' : 'View details & quote')
      : 'View details';
    var chatHtml = '';
    var chatHref = card.dataset.chatUrl || '';
    if (!needsReply && chatHref) {
      chatHtml = '<a href="' + chatHref + '" class="btn sm ghost" onclick="event.stopPropagation();" style="text-decoration:none"><span class="ms" style="font-size:16px">chat_bubble</span>Message</a>';
    }
    actions.innerHTML = chatHtml +
      '<button type="button" class="btn sm ' + (needsReply ? 'pri' : 'ghost') + '" onclick="event.stopPropagation(); ' + openFn + '(' + id + ')">' + ctaLabel + '</button>';
    card.style.borderLeft = needsReply ? '3px solid #7A1FC4' : '';
  }

  function placeCard(card, rebuildActions) {
    var target = card.dataset.needsReply === '1' ? needsList : repliedList;
    if (card.parentElement !== target) target.appendChild(card);
    if (rebuildActions) rebuildCardActions(card);
  }

  window.syncFeedCardAfterUpdate = function (card) {
    if (!card) return;
    placeCard(card, true);
    applyRequestsFeedFilters();
  };

  function applyRequestsFeedFilters() {
    var search = (searchEl.value || '').toLowerCase().trim();
    var sort = sortEl.value || 'recent';
    var cards = Array.prototype.slice.call(document.querySelectorAll('.bk-page .card[data-req]'));

    cards.sort(function (a, b) {
      var da = a.dataset.dateSort || '';
      var db = b.dataset.dateSort || '';
      return sort === 'oldest' ? da.localeCompare(db) : db.localeCompare(da);
    });

    var needsVisible = 0;
    var repliedVisible = 0;

    cards.forEach(function (card) {
      placeCard(card, false);
      var matchType = typeFilter === 'all' || card.dataset.kind === typeFilter;
      var matchStatus = statusFilter === 'all' || card.dataset.statusKey === statusFilter;
      var matchSearch = !search || (card.dataset.search || '').indexOf(search) !== -1;
      var show = matchType && matchStatus && matchSearch;
      card.style.display = show ? '' : 'none';
      if (show) {
        if (card.dataset.needsReply === '1') needsVisible++;
        else repliedVisible++;
      }
    });

    document.getElementById('needsReplyVisibleCount').textContent = String(needsVisible);
    document.getElementById('repliedVisibleCount').textContent = String(repliedVisible);
    needsSection.style.display = needsVisible > 0 ? '' : 'none';
    repliedSection.style.display = repliedVisible > 0 ? '' : 'none';
    emptyEl.style.display = (needsVisible + repliedVisible) === 0 ? '' : 'none';
  }

  window.applyRequestsFeedFilters = applyRequestsFeedFilters;

  document.getElementById('reqTypeChips').addEventListener('click', function (e) {
    var chip = e.target.closest('.chip');
    if (!chip) return;
    typeFilter = chip.dataset.type || 'all';
    Array.prototype.forEach.call(document.querySelectorAll('#reqTypeChips .chip'), function (el) {
      el.classList.toggle('on', el === chip);
    });
    applyRequestsFeedFilters();
  });

  document.getElementById('reqStatusChips').addEventListener('click', function (e) {
    var chip = e.target.closest('.chip');
    if (!chip) return;
    var next = chip.dataset.status || 'all';
    if (statusFilter === next) {
      statusFilter = 'all';
      chip.classList.remove('on');
        } else {
      statusFilter = next;
      Array.prototype.forEach.call(document.querySelectorAll('#reqStatusChips .chip'), function (el) {
        el.classList.toggle('on', el === chip);
      });
    }
    applyRequestsFeedFilters();
  });

  searchEl.addEventListener('input', applyRequestsFeedFilters);
  sortEl.addEventListener('change', applyRequestsFeedFilters);

  applyRequestsFeedFilters();
})();
</script>
@endsection
