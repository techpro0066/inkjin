@extends('layouts.studio-dashboard-layout')

@section('title', 'Bookings · Payment links')

@php
  $filter = $filter ?? 'all';
  $search = $search ?? '';
  $filterCounts = $filterCounts ?? ['all' => 0, 'open' => 0, 'paid' => 0, 'expired' => 0];
  $paymentLinks = $paymentLinks ?? collect();
@endphp

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
  .bk .fchip.on{background:#1A1A1A;border-color:#1A1A1A;color:#fff}
  .bk .pill{gap:5px}
  .bk .pill:before{content:'';width:5px;height:5px;border-radius:50%;background:currentColor}
  .bk .pill.nd:before{display:none}
  .bk .pill.b{background:#E8F0FF;color:#1E5BD8}
  .bk .pill.g{background:#E6F8EE;color:#00A650}
  .bk .pill.k{background:#F0EDF2;color:#6F6874}
  .bk .search{display:flex;align-items:center;gap:8px;background:#fff;border:1px solid var(--line);border-radius:10px;padding:8px 12px;min-width:220px;max-width:100%}
  .bk .search input{border:0;outline:0;font:inherit;font-size:13.5px;width:100%;background:none}
  .bk .search .ms{color:var(--faint);font-size:18px}
  .bk .empty{padding:64px 22px;text-align:center;color:var(--muted)}
  .bk .empty .ms{font-size:40px;color:var(--faint);margin-bottom:10px}
  .bk .help{font-size:12.5px;color:var(--faint);margin-top:12px;line-height:1.45}
  .bk .pl-act .ms{font-size:22px;cursor:pointer;user-select:none}
  .bk .pl-act .ms:hover{color:var(--ink)}
  .bk .pl-act button{background:none;border:0;padding:0;margin:0;font:inherit;line-height:1;cursor:pointer;color:inherit}
  /* Menu is rendered outside .bk — do not scope under .bk */
  .pl-menu{
    position:fixed;background:#fff;border:1px solid var(--line);border-radius:14px;
    box-shadow:0 14px 40px rgba(40,10,60,.18);width:230px;padding:6px;z-index:200
  }
  .pl-menu[hidden]{display:none!important}
  .pl-menu button,.pl-menu a{
    display:flex;width:100%;align-items:center;gap:10px;padding:9px 12px;border-radius:9px;
    font:inherit;font-size:13.5px;font-weight:500;background:none;border:0;cursor:pointer;
    color:var(--ink);text-align:left;text-decoration:none
  }
  .pl-menu button:hover,.pl-menu a:hover{background:#F3E8FF}
  .pl-menu .ms{font-size:19px;color:var(--muted)}
  .pl-menu .red,.pl-menu .red .ms{color:#C62828}
  .pl-menu hr{border:0;border-top:1px solid #F0EAF2;margin:5px 4px}
  .pl-menu-sh{display:none}
  .pl-menu-sh[hidden]{display:none!important}
  #pl-ex-m,#pl-qr-m{position:fixed;inset:0;background:rgba(20,10,30,.45);display:flex;align-items:center;justify-content:center;z-index:320;padding:24px}
  #pl-ex-m[hidden],#pl-qr-m[hidden]{display:none!important}
  #pl-ex-m .pl-ex-box,#pl-qr-m .pl-qr-box{
    background:#fff;border-radius:18px;width:560px;max-width:100%;
    display:flex;flex-direction:column;box-shadow:0 20px 60px rgba(0,0,0,.25);font-size:13.5px;color:#1A1A1A;overflow:hidden
  }
  #pl-ex-m .pl-ex-ph,#pl-qr-m .pl-qr-ph{display:flex;gap:12px;align-items:flex-start;padding:20px 24px 14px;border-bottom:1px solid #EFE8F1}
  #pl-ex-m .pl-ex-ic,#pl-qr-m .pl-qr-ic{width:40px;height:40px;border-radius:12px;background:#F0EDF2;color:#6F6874;display:flex;align-items:center;justify-content:center;flex:none}
  #pl-qr-m .pl-qr-ic{background:#F3E8FF;color:#3E007C}
  #pl-ex-m .pl-ex-ic .ms,#pl-qr-m .pl-qr-ic .ms{font-size:22px}
  #pl-ex-m .pl-ex-ph h2,#pl-qr-m .pl-qr-ph h2{font-size:19px;font-weight:800;margin:0;letter-spacing:-.2px}
  #pl-ex-m .pl-ex-sub,#pl-qr-m .pl-qr-sub{color:#6F6874;font-size:13.5px;margin-top:4px;line-height:1.4}
  #pl-ex-m .pl-ex-x,#pl-qr-m .pl-qr-x{margin-left:auto;border:0;background:none;cursor:pointer;color:#8F8A95;padding:2px;display:flex}
  #pl-ex-m .pl-ex-bd,#pl-qr-m .pl-qr-bd{padding:18px 24px}
  #pl-ex-m .pl-ex-card{display:flex;align-items:center;gap:12px;background:#FAF0FB;border:1px solid #F1E7F3;border-radius:12px;padding:12px 14px;margin:0 0 16px}
  #pl-ex-m .pl-ex-card-ic{width:36px;height:36px;border-radius:10px;background:#F0EDF2;color:#6F6874;display:flex;align-items:center;justify-content:center;flex:none}
  #pl-ex-m .pl-ex-card-main{flex:1;min-width:0}
  #pl-ex-m .pl-ex-card-title{font-size:13.5px;font-weight:700;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
  #pl-ex-m .pl-ex-card-meta{font-size:12px;color:#6F6874;margin-top:2px}
  #pl-ex-m .pl-ex-checks{display:flex;flex-direction:column;gap:8px;margin:0;padding:0;list-style:none}
  #pl-ex-m .pl-ex-checks li{display:flex;align-items:flex-start;gap:8px;font-size:13.5px;color:#3F3A45;line-height:1.4}
  #pl-ex-m .pl-ex-checks .ms{font-size:18px;color:#00A650;flex:none;margin-top:1px}
  #pl-ex-m .pl-ex-ft,#pl-qr-m .pl-qr-ft{display:flex;gap:10px;justify-content:flex-end;padding:14px 24px;border-top:1px solid #EFE8F1}
  #pl-ex-m .pl-ex-pbtn,#pl-qr-m .pl-qr-pbtn{display:inline-flex;align-items:center;gap:8px;background:#1A1A1A;color:#fff;border-radius:10px;padding:10px 16px;font:inherit;font-weight:600;font-size:13.5px;border:0;cursor:pointer}
  #pl-ex-m .pl-ex-pbtn.pl-gh,#pl-qr-m .pl-qr-pbtn.pl-gh{background:#fff;color:#1A1A1A;border:1px solid #E6DEE9}
  #pl-qr-m .pl-qr-frame{display:flex;justify-content:center;padding:16px;border:1px solid #EFE8F1;border-radius:16px;width:max-content;margin:4px auto 12px}
  #pl-qr-m .pl-qr-frame img{width:168px;height:168px;display:block}
  #pl-qr-m .pl-qr-hint{text-align:center;font-size:13.5px;margin:0 0 14px;line-height:1.45}
  #pl-qr-m .pl-qr-lb{display:block;font-size:13px;font-weight:700;margin:0 0 6px}
  #pl-qr-m .pl-qr-lnk{display:flex;gap:8px;align-items:center}
  #pl-qr-m .pl-qr-fi{display:flex;align-items:center;gap:6px;border:1px solid #E6DEE9;border-radius:10px;padding:0 13px;min-height:42px;flex:1;min-width:0}
  #pl-qr-m .pl-qr-fi span{overflow:hidden;text-overflow:ellipsis;white-space:nowrap;font-size:13.5px}
  @media (max-width:900px){
    .bk .card:has(table){overflow:visible!important;background:none!important;border:0!important}
    .bk table,.bk tbody,.bk tr,.bk td{display:block;width:100%}
    .bk tr:has(th){display:none}
    .bk tbody tr{position:relative;background:#fff;border:1px solid var(--line);border-radius:14px;padding:12px 14px;margin-bottom:10px}
    .bk td{border:0!important;padding:5px 0!important;display:flex!important;justify-content:space-between;align-items:center;gap:12px;text-align:right}
    .bk td::before{content:attr(data-label);font-size:10.5px;font-weight:700;letter-spacing:.7px;color:var(--muted);text-align:left;flex-shrink:0}
    .bk td:first-child{padding:0 44px 8px 0!important;margin-bottom:4px;border-bottom:1px solid #F3EEF4!important;justify-content:flex-start;text-align:left}
    .bk td:first-child::before{display:none}
    .bk td[data-label="ACTIONS"]{position:absolute;top:10px;right:10px;width:auto;padding:0!important}
    .bk td[data-label="ACTIONS"]::before{display:none}
    .pl-menu{left:12px!important;right:12px!important;top:auto!important;bottom:12px!important;width:auto!important}
    .pl-menu-sh{display:block;position:fixed;inset:0;background:rgba(26,16,32,.35);z-index:199}
    .bk .pl-filters{flex-direction:column;align-items:stretch!important}
    .bk .search{width:100%}
  }
</style>
@endpush

@section('content')
<div class="bk">
  <div class="head">
    <div>
      <h1>
        Bookings
        <a class="help-q" href="https://help.inkjin.com/en/articles/17338370-studio-dashboard-bookings-payment-links" target="_blank" rel="noopener" title="Help with this page" aria-label="Help with this page">
          <span class="ms">help</span>
        </a>
      </h1>
      <div class="sub">Bookings and requests for your artists at {{ $studioName }}.</div>
    </div>
    <div class="btns">
      <a class="btn ghost" href="{{ route('studio.bookings.payment-links.export', array_filter(['filter' => ($filter ?? 'all') !== 'all' ? ($filter ?? null) : null, 'q' => ($search ?? '') !== '' ? $search : null])) }}">
        <span class="ms">download</span>Export
      </a>
      @include('studio.bookings.partials.new-booking-button')
    </div>
  </div>

  @include('studio.bookings.partials.tabs')

  <form method="get" action="{{ route('studio.bookings.payment-links') }}" class="row pl-filters" style="justify-content:space-between;margin-bottom:12px;gap:12px;flex-wrap:wrap">
    <input type="hidden" name="filter" value="{{ $filter }}">
    <div class="fchips">
      @foreach ([
        'all' => 'All',
        'open' => 'Open',
        'paid' => 'Paid',
        'expired' => 'Expired',
      ] as $key => $label)
        <a href="{{ route('studio.bookings.payment-links', array_filter(['filter' => $key === 'all' ? null : $key, 'q' => $search !== '' ? $search : null])) }}"
          class="fchip{{ $filter === $key ? ' on' : '' }}">{{ $label }}</a>
      @endforeach
    </div>
    <div class="search">
      <span class="ms">search</span>
      <input type="search" name="q" id="plSearch" value="{{ $search }}" placeholder="Search links" autocomplete="off" aria-label="Search links">
    </div>
  </form>

  @if ($paymentLinks->isEmpty())
    <div class="card">
      <div class="empty">
        <div class="ms">link</div>
        <div style="font-weight:700;color:var(--ink);margin-bottom:4px">Nothing here yet</div>
        <div>Use <b style="font-weight:600">New booking</b> to create a studio payment link.</div>
      </div>
    </div>
  @else
    <div class="card" style="overflow:hidden" id="pltable">
      <table>
        <thead>
          <tr>
            <th>TITLE</th>
            <th>ARTIST</th>
            <th>CLIENT</th>
            <th>DEPOSIT</th>
            <th>TOTAL</th>
            <th>STATUS</th>
            <th>CREATED</th>
            <th>ACTIONS</th>
          </tr>
        </thead>
        <tbody>
          @foreach ($paymentLinks as $link)
            @php
              $status = (string) ($link->status ?? 'open');
              $statusLabel = match ($status) {
                'paid' => 'Paid',
                'expired' => 'Expired',
                default => 'Open',
              };
              $pillClass = match ($status) {
                'paid' => 'g',
                'expired' => 'k',
                default => 'b',
              };
              $publicUrl = (string) ($link->url ?? '');
              $createdLabel = optional($link->created_at)->format('d/m') ?? '—';
              $depositAmt = (float) ($link->deposit ?? 0);
              $totalAmt = (float) ($link->total ?? 0);
              $amountKind = abs($depositAmt - $totalAmt) < 0.009 ? 'full payment' : 'deposit';
              $amountLabel = '€'.number_format($depositAmt, $depositAmt == floor($depositAmt) ? 0 : 2).' '.$amountKind;
            @endphp
            <tr>
              <td data-label="TITLE"><b style="font-weight:600">{{ $link->title }}</b></td>
              <td data-label="ARTIST">{{ $link->artist }}</td>
              <td class="muted" data-label="CLIENT">{{ $link->client }}</td>
              <td data-label="DEPOSIT">€{{ number_format($depositAmt, $depositAmt == floor($depositAmt) ? 0 : 2) }}</td>
              <td data-label="TOTAL"><b style="font-weight:600">€{{ number_format($totalAmt, $totalAmt == floor($totalAmt) ? 0 : 2) }}</b></td>
              <td data-label="STATUS"><span class="pill {{ $pillClass }}">{{ $statusLabel }}</span></td>
              <td class="muted" data-label="CREATED">{{ $createdLabel }}</td>
              <td data-label="ACTIONS">
                <div class="row pl-act" style="gap:14px">
                  <button type="button" class="js-pl-copy" title="Copy link" aria-label="Copy link" data-link="{{ e($publicUrl) }}">
                    <span class="ms faint">content_copy</span>
                  </button>
                  <button type="button" class="js-pl-qr" title="Show QR code" aria-label="Show QR code"
                    data-link="{{ e($publicUrl) }}"
                    data-title="{{ e($link->title) }}"
                    data-amount="{{ e($amountLabel) }}">
                    <span class="ms faint">qr_code_2</span>
                  </button>
                  <button type="button" class="js-pl-more" title="More" aria-label="More"
                    data-id="{{ (int) $link->id }}"
                    data-link="{{ e($publicUrl) }}"
                    data-title="{{ e($link->title) }}"
                    data-amount="{{ e($amountLabel) }}"
                    data-deposit="€{{ number_format($depositAmt, 2) }}"
                    data-total="€{{ number_format($totalAmt, 2) }}"
                    data-created="{{ e($createdLabel) }}"
                    data-status="{{ $status }}"
                    data-booking="{{ $link->booking_id ?: '' }}">
                    <span class="ms faint">more_horiz</span>
                  </button>
                </div>
              </td>
            </tr>
          @endforeach
        </tbody>
      </table>
    </div>

    <div class="row" style="justify-content:center;margin:16px 0 8px">
      {{ $paymentLinks->links() }}
    </div>
  @endif

  <p class="help">Links your studio made with New booking. Once the client pays, the booking is added to the artist's calendar and to Bookings. Links artists make themselves stay in their own dashboards.</p>
</div>

<div class="pl-menu-sh" id="plMenuSh" hidden></div>
<div class="pl-menu" id="plMenu" hidden role="menu"></div>

<div id="pl-qr-m" hidden role="dialog" aria-modal="true" aria-labelledby="pl-qr-t">
  <div class="pl-qr-box">
    <div class="pl-qr-ph">
      <div class="pl-qr-ic"><span class="ms">qr_code_2</span></div>
      <div>
        <h2 id="pl-qr-t">Payment link</h2>
        <div class="pl-qr-sub" id="pl-qr-sub"></div>
      </div>
      <button type="button" class="pl-qr-x" id="pl-qr-x" aria-label="Close"><span class="ms">close</span></button>
    </div>
    <div class="pl-qr-bd">
      <div class="pl-qr-frame"><img id="pl-qr-img" alt="Payment link QR code" width="168" height="168"></div>
      <div class="pl-qr-hint">Show this QR code, or copy the link and send it to the client.</div>
      <span class="pl-qr-lb">Link</span>
      <div class="pl-qr-lnk">
        <div class="pl-qr-fi"><span id="pl-qr-url"></span></div>
        <button type="button" class="pl-qr-pbtn pl-gh" id="pl-qr-copy-inline"><span class="ms">content_copy</span>Copy</button>
      </div>
    </div>
    <div class="pl-qr-ft">
      <button type="button" class="pl-qr-pbtn pl-gh" id="pl-qr-copy"><span class="ms">content_copy</span>Copy link</button>
      <button type="button" class="pl-qr-pbtn" id="pl-qr-done">Done</button>
    </div>
  </div>
</div>

<div id="pl-ex-m" hidden role="dialog" aria-modal="true" aria-labelledby="pl-ex-t">
  <div class="pl-ex-box">
    <div class="pl-ex-ph">
      <div class="pl-ex-ic"><span class="ms">timer_off</span></div>
      <div>
        <h2 id="pl-ex-t">Expire this link now?</h2>
        <div class="pl-ex-sub">Nobody will be able to pay with it after this.</div>
      </div>
      <button type="button" class="pl-ex-x" id="pl-ex-x" aria-label="Close"><span class="ms">close</span></button>
    </div>
    <div class="pl-ex-bd">
      <div class="pl-ex-card">
        <div class="pl-ex-card-ic"><span class="ms">link</span></div>
        <div class="pl-ex-card-main">
          <div class="pl-ex-card-title" id="pl-ex-title">Payment link</div>
          <div class="pl-ex-card-meta" id="pl-ex-meta"></div>
        </div>
        <span class="pill b" id="pl-ex-pill">Open</span>
      </div>
      <ul class="pl-ex-checks">
        <li><span class="ms">check_circle</span><span>The link stays in your list as Expired</span></li>
      </ul>
    </div>
    <div class="pl-ex-ft">
      <button type="button" class="pl-ex-pbtn pl-gh" id="pl-ex-cancel">Keep it open</button>
      <button type="button" class="pl-ex-pbtn" id="pl-ex-go"><span class="ms">timer_off</span>Expire now</button>
    </div>
  </div>
</div>

@include('studio.bookings.partials.payment-link-modal')
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/jquery@3.7.1/dist/jquery.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
@include('studio.bookings.partials.payment-link-modal-script')
<script>
(function () {
  var menu = document.getElementById('plMenu');
  var menuSh = document.getElementById('plMenuSh');
  var qrModal = document.getElementById('pl-qr-m');
  var qrImg = document.getElementById('pl-qr-img');
  var qrUrl = document.getElementById('pl-qr-url');
  var qrTitle = document.getElementById('pl-qr-t');
  var qrSub = document.getElementById('pl-qr-sub');
  var exModal = document.getElementById('pl-ex-m');
  var exTitle = document.getElementById('pl-ex-title');
  var exMeta = document.getElementById('pl-ex-meta');
  var exGo = document.getElementById('pl-ex-go');
  var lastQrLink = '';
  var expireTarget = null;
  var expireBusy = false;
  var expireUrlTpl = @json(route('studio.bookings.payment-links.expire', ['id' => '__ID__']));
  var csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
  var searchInput = document.getElementById('plSearch');
  var searchTimer = null;
  var bookingsIndexUrl = @json(route('studio.bookings'));

  function showToast(message, bad) {
    if (typeof window.bpToast === 'function') {
      window.bpToast(message || 'Done', !!bad);
      return;
    }
  }

  function copyLink(url) {
    if (!url) return;
    if (navigator.clipboard && navigator.clipboard.writeText) {
      navigator.clipboard.writeText(url).then(function () { showToast('Link copied'); }).catch(function () { fallbackCopy(url); });
      return;
    }
    fallbackCopy(url);
  }

  function fallbackCopy(url) {
    var input = document.createElement('textarea');
    input.value = url;
    input.setAttribute('readonly', '');
    input.style.position = 'absolute';
    input.style.left = '-9999px';
    document.body.appendChild(input);
    input.select();
    try { document.execCommand('copy'); showToast('Link copied'); } catch (e) {}
    document.body.removeChild(input);
  }

  function displayUrl(url) {
    return String(url || '').replace(/^https?:\/\//i, '');
  }

  function openQr(url, title, amountLabel) {
    lastQrLink = url || '';
    if (qrImg && lastQrLink) {
      qrImg.src = 'https://api.qrserver.com/v1/create-qr-code/?size=168x168&data=' + encodeURIComponent(lastQrLink);
    }
    if (qrUrl) qrUrl.textContent = displayUrl(lastQrLink);
    if (qrTitle) qrTitle.textContent = title || 'Payment link';
    if (qrSub) {
      qrSub.textContent = amountLabel || '';
      qrSub.hidden = !amountLabel;
    }
    if (qrModal) {
      qrModal.hidden = false;
      document.body.style.overflow = 'hidden';
    }
  }

  function closeQr() {
    if (!qrModal) return;
    qrModal.hidden = true;
    if ((!exModal || exModal.hidden)) document.body.style.overflow = '';
  }

  function setExpireGoLabel(loading) {
    if (!exGo) return;
    exGo.disabled = !!loading;
    exGo.innerHTML = loading ? 'Expiring…' : '<span class="ms">timer_off</span>Expire now';
  }

  function openExpire(opts) {
    expireTarget = { id: opts.id || '' };
    if (exTitle) exTitle.textContent = opts.title || 'Payment link';
    if (exMeta) {
      var parts = [];
      if (opts.deposit) parts.push('Deposit ' + opts.deposit);
      if (opts.total) parts.push('Total ' + opts.total);
      if (opts.created) parts.push('created ' + opts.created);
      exMeta.textContent = parts.join(' · ');
    }
    setExpireGoLabel(false);
    if (exModal) {
      exModal.hidden = false;
      document.body.style.overflow = 'hidden';
    }
  }

  function closeExpire() {
    if (!exModal) return;
    exModal.hidden = true;
    expireTarget = null;
    if ((!qrModal || qrModal.hidden)) document.body.style.overflow = '';
  }

  function confirmExpire() {
    if (!expireTarget || expireBusy) return;
    expireBusy = true;
    setExpireGoLabel(true);
    var url = expireUrlTpl.replace('__ID__', encodeURIComponent(expireTarget.id));
    fetch(url, {
      method: 'POST',
      headers: {
        'Accept': 'application/json',
        'X-Requested-With': 'XMLHttpRequest',
        'X-CSRF-TOKEN': csrf,
      },
    }).then(function (res) {
      return res.json().then(function (data) {
        return { status: res.status, data: data };
      }).catch(function () {
        return { status: res.status, data: {} };
      });
    }).then(function (result) {
      if (result.data && result.data.success) {
        closeExpire();
        showToast(result.data.message || 'Link expired');
        window.location.reload();
        return;
      }
      showToast((result.data && result.data.message) || 'Could not expire this link.');
      setExpireGoLabel(false);
    }).catch(function () {
      showToast('Could not expire this link.');
      setExpireGoLabel(false);
    }).finally(function () {
      expireBusy = false;
    });
  }

  function closeMenu() {
    if (menu) menu.hidden = true;
    if (menuSh) menuSh.hidden = true;
  }

  function openMenu(btn) {
    if (!menu || !btn) return;
    var link = btn.getAttribute('data-link') || '';
    var title = btn.getAttribute('data-title') || '';
    var amount = btn.getAttribute('data-amount') || '';
    var status = btn.getAttribute('data-status') || '';
    var bookingId = btn.getAttribute('data-booking') || '';
    var id = btn.getAttribute('data-id') || '';

    var html = '';
    html += '<button type="button" data-a="copy" data-link="' + link.replace(/"/g, '&quot;') + '"><span class="ms">content_copy</span>Copy link</button>';
    html += '<button type="button" data-a="qr" data-link="' + link.replace(/"/g, '&quot;') + '" data-title="' + title.replace(/"/g, '&quot;') + '" data-amount="' + amount.replace(/"/g, '&quot;') + '"><span class="ms">qr_code_2</span>Show QR</button>';
    if (bookingId) {
      html += '<a href="' + bookingsIndexUrl + '?q=' + encodeURIComponent(bookingId) + '"><span class="ms">visibility</span>View booking</a>';
    }
    if (status === 'open' && id) {
      html += '<hr>';
      html += '<button type="button" class="red" data-a="expire"'
        + ' data-id="' + id.replace(/"/g, '&quot;') + '"'
        + ' data-title="' + title.replace(/"/g, '&quot;') + '"'
        + ' data-deposit="' + (btn.getAttribute('data-deposit') || '').replace(/"/g, '&quot;') + '"'
        + ' data-total="' + (btn.getAttribute('data-total') || '').replace(/"/g, '&quot;') + '"'
        + ' data-created="' + (btn.getAttribute('data-created') || '').replace(/"/g, '&quot;') + '"'
        + '><span class="ms">timer_off</span>Expire now</button>';
    }
    menu.innerHTML = html;
    menu.hidden = false;
    if (menuSh) menuSh.hidden = false;

    var rect = btn.getBoundingClientRect();
    if (window.innerWidth > 900) {
      var top = rect.bottom + 6;
      var left = rect.right - 230;
      if (left < 12) left = 12;
      var maxTop = window.innerHeight - menu.offsetHeight - 12;
      if (top > maxTop) top = Math.max(12, rect.top - menu.offsetHeight - 6);
      menu.style.top = top + 'px';
      menu.style.left = left + 'px';
      menu.style.right = '';
      menu.style.bottom = '';
    } else {
      menu.style.top = '';
      menu.style.left = '';
      menu.style.right = '';
      menu.style.bottom = '';
    }
  }

  document.querySelectorAll('.js-pl-copy').forEach(function (btn) {
    btn.addEventListener('click', function () { copyLink(btn.getAttribute('data-link') || ''); });
  });
  document.querySelectorAll('.js-pl-qr').forEach(function (btn) {
    btn.addEventListener('click', function () {
      openQr(btn.getAttribute('data-link') || '', btn.getAttribute('data-title') || '', btn.getAttribute('data-amount') || '');
    });
  });
  document.querySelectorAll('.js-pl-more').forEach(function (btn) {
    btn.addEventListener('click', function (e) {
      e.stopPropagation();
      menu.dataset.for = String(btn);
      openMenu(btn);
    });
  });

  menu?.addEventListener('click', function (e) {
    var item = e.target.closest('[data-a]');
    if (!item) return;
    var action = item.getAttribute('data-a');
    if (action === 'copy') copyLink(item.getAttribute('data-link') || '');
    if (action === 'qr') {
      openQr(item.getAttribute('data-link') || '', item.getAttribute('data-title') || '', item.getAttribute('data-amount') || '');
    }
    if (action === 'expire') {
      openExpire({
        id: item.getAttribute('data-id') || '',
        title: item.getAttribute('data-title') || '',
        deposit: item.getAttribute('data-deposit') || '',
        total: item.getAttribute('data-total') || '',
        created: item.getAttribute('data-created') || '',
      });
    }
    closeMenu();
  });

  menuSh?.addEventListener('click', closeMenu);
  document.addEventListener('click', function (e) {
    if (!menu || menu.hidden) return;
    if (e.target.closest('#plMenu') || e.target.closest('.js-pl-more')) return;
    closeMenu();
  });
  document.addEventListener('keydown', function (e) {
    if (e.key === 'Escape') { closeMenu(); closeQr(); closeExpire(); }
  });

  document.getElementById('pl-qr-x')?.addEventListener('click', closeQr);
  document.getElementById('pl-qr-done')?.addEventListener('click', closeQr);
  document.getElementById('pl-qr-copy')?.addEventListener('click', function () { copyLink(lastQrLink); });
  document.getElementById('pl-qr-copy-inline')?.addEventListener('click', function () { copyLink(lastQrLink); });
  qrModal?.addEventListener('click', function (e) { if (e.target === qrModal) closeQr(); });

  document.getElementById('pl-ex-x')?.addEventListener('click', closeExpire);
  document.getElementById('pl-ex-cancel')?.addEventListener('click', closeExpire);
  exGo?.addEventListener('click', confirmExpire);
  exModal?.addEventListener('click', function (e) { if (e.target === exModal) closeExpire(); });

  if (searchInput) {
    searchInput.addEventListener('keydown', function (e) {
      if (e.key === 'Enter') { e.preventDefault(); searchInput.form?.submit(); }
    });
    searchInput.addEventListener('input', function () {
      clearTimeout(searchTimer);
      searchTimer = setTimeout(function () { searchInput.form?.submit(); }, 400);
    });
  }
})();
</script>
@endpush
