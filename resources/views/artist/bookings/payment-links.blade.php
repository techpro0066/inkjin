@extends('layouts.new-artist-dashboard-layout')

@section('title', 'Payment links')

@php
  $activeNav = 'bookings';
  $filter = $filter ?? 'all';
  $search = $search ?? '';
  $filterCounts = $filterCounts ?? ['all' => 0, 'open' => 0, 'paid' => 0, 'expired' => 0];
@endphp

@section('styles')
@include('artist.partials.payment-link-modal-styles')
<style>
  .pl-page .chip{cursor:pointer;text-decoration:none;display:inline-flex;align-items:center;gap:4px}
  .pl-page .chip b{font-weight:700;margin-left:2px;opacity:.75}
  .pl-page .chip.on b{opacity:.9}
  .pl-page .pl-search{width:280px;max-width:100%}
  .pl-page .pl-search .in{padding:0 13px}
  .pl-page .pl-search input{
    border:0!important;outline:0!important;box-shadow:none!important;background:transparent!important;
    font:inherit;font-size:13.5px;flex:1;min-width:0;padding:10px 0;color:var(--ink);width:100%
  }
  .pl-page .pl-search input::placeholder{color:var(--faint)}
  .pl-page .pl-act .ms{font-size:22px;cursor:pointer;user-select:none}
  .pl-page .pl-act .ms:hover{color:var(--ink)}
  .pl-page .pl-act button{
    background:none;border:0;padding:0;margin:0;font:inherit;line-height:1;cursor:pointer;color:inherit
  }
  .pl-menu{
    position:absolute;background:#fff;border:1px solid var(--line);border-radius:14px;
    box-shadow:0 14px 40px rgba(40,10,60,.18);width:230px;padding:6px;z-index:80
  }
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
  #pl-ex-m{position:fixed;inset:0;background:rgba(20,10,30,.45);display:flex;align-items:center;justify-content:center;z-index:320;padding:24px}
  #pl-ex-m[hidden],#pl-ex-m [hidden]{display:none!important}
  #pl-ex-m .pl-ex-box{
    background:#fff;border-radius:18px;width:520px;max-width:100%;
    display:flex;flex-direction:column;box-shadow:0 20px 60px rgba(0,0,0,.25);font-size:13.5px;color:#1A1A1A;overflow:hidden
  }
  #pl-ex-m .pl-ex-ph{display:flex;gap:12px;align-items:flex-start;padding:20px 24px 14px;border-bottom:1px solid #EFE8F1}
  #pl-ex-m .pl-ex-ic{width:40px;height:40px;border-radius:12px;background:#F0EDF2;color:#6F6874;display:flex;align-items:center;justify-content:center;flex:none}
  #pl-ex-m .pl-ex-ic .ms{font-size:22px}
  #pl-ex-m .pl-ex-ph h2{font-size:19px;font-weight:800;margin:0;letter-spacing:-.2px}
  #pl-ex-m .pl-ex-sub{color:#6F6874;font-size:13.5px;margin-top:4px;line-height:1.4}
  #pl-ex-m .pl-ex-x{margin-left:auto;border:0;background:none;cursor:pointer;color:#8F8A95;padding:2px;display:flex}
  #pl-ex-m .pl-ex-x:hover{color:#1A1A1A}
  #pl-ex-m .pl-ex-x .ms{font-size:22px}
  #pl-ex-m .pl-ex-bd{padding:18px 24px}
  #pl-ex-m .pl-ex-card{
    display:flex;align-items:center;gap:12px;background:#FAF0FB;border:1px solid #F1E7F3;
    border-radius:12px;padding:12px 14px;margin:0 0 16px
  }
  #pl-ex-m .pl-ex-card-ic{
    width:36px;height:36px;border-radius:10px;background:#F0EDF2;color:#6F6874;
    display:flex;align-items:center;justify-content:center;flex:none
  }
  #pl-ex-m .pl-ex-card-ic .ms{font-size:20px}
  #pl-ex-m .pl-ex-card-main{flex:1;min-width:0}
  #pl-ex-m .pl-ex-card-title{font-size:13.5px;font-weight:700;color:#1A1A1A;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
  #pl-ex-m .pl-ex-card-meta{font-size:12px;color:#6F6874;margin-top:2px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
  #pl-ex-m .pl-ex-checks{display:flex;flex-direction:column;gap:8px;margin:0;padding:0;list-style:none}
  #pl-ex-m .pl-ex-checks li{display:flex;align-items:flex-start;gap:8px;font-size:13.5px;color:#3F3A45;line-height:1.4}
  #pl-ex-m .pl-ex-checks .ms{font-size:18px;color:#00A650;flex:none;margin-top:1px}
  #pl-ex-m .pl-ex-ft{display:flex;gap:10px;justify-content:flex-end;padding:14px 24px;border-top:1px solid #EFE8F1}
  #pl-ex-m .pl-ex-pbtn{display:inline-flex;align-items:center;gap:8px;background:#1A1A1A;color:#fff;border-radius:10px;padding:10px 16px;font:inherit;font-weight:600;font-size:13.5px;border:0;cursor:pointer;white-space:nowrap}
  #pl-ex-m .pl-ex-pbtn .ms{font-size:18px}
  #pl-ex-m .pl-ex-pbtn.pl-gh{background:#fff;color:#1A1A1A;border:1px solid #E6DEE9}
  #pl-ex-m .pl-ex-pbtn:disabled{opacity:.45;cursor:not-allowed}
  @media (max-width:560px){
    #pl-ex-m{padding:0;align-items:flex-end}
    #pl-ex-m .pl-ex-box{width:100%;border-radius:18px 18px 0 0}
    #pl-ex-m .pl-ex-ft .pl-ex-pbtn{flex:1;justify-content:center}
    #pl-ex-m .pl-ex-card{flex-wrap:wrap}
  }
  #pl-qr-m{position:fixed;inset:0;background:rgba(20,10,30,.45);display:flex;align-items:center;justify-content:center;z-index:320;padding:24px}
  #pl-qr-m[hidden],#pl-qr-m [hidden]{display:none!important}
  #pl-qr-m .pl-qr-box{
    background:#fff;border-radius:18px;width:560px;max-width:100%;max-height:calc(100vh - 48px);max-height:calc(100dvh - 48px);
    display:flex;flex-direction:column;box-shadow:0 20px 60px rgba(0,0,0,.25);font-size:13.5px;color:#1A1A1A;overflow:hidden
  }
  #pl-qr-m .pl-qr-ph{display:flex;gap:12px;align-items:flex-start;padding:20px 24px 14px;border-bottom:1px solid #EFE8F1;flex-shrink:0}
  #pl-qr-m .pl-qr-ic{width:40px;height:40px;border-radius:12px;background:#F3E8FF;color:#3E007C;display:flex;align-items:center;justify-content:center;flex:none}
  #pl-qr-m .pl-qr-ic .ms{font-size:22px}
  #pl-qr-m .pl-qr-ph h2{font-size:19px;font-weight:800;margin:0;letter-spacing:-.2px}
  #pl-qr-m .pl-qr-sub{color:#6F6874;font-size:13.5px;margin-top:4px;line-height:1.4}
  #pl-qr-m .pl-qr-x{margin-left:auto;border:0;background:none;cursor:pointer;color:#8F8A95;padding:2px;display:flex;align-items:center;justify-content:center}
  #pl-qr-m .pl-qr-x:hover{color:#1A1A1A}
  #pl-qr-m .pl-qr-x .ms{font-size:22px}
  #pl-qr-m .pl-qr-bd{padding:18px 24px;overflow:auto;flex:1;min-height:0}
  #pl-qr-m .pl-qr-frame{display:flex;justify-content:center;padding:16px;background:#fff;border:1px solid #EFE8F1;border-radius:16px;width:max-content;margin:4px auto 12px}
  #pl-qr-m .pl-qr-frame img{width:168px;height:168px;display:block}
  #pl-qr-m .pl-qr-hint{text-align:center;font-size:13.5px;margin:0 0 14px;color:#1A1A1A;line-height:1.45}
  #pl-qr-m .pl-qr-lb{display:block;font-size:13px;font-weight:700;margin:0 0 6px}
  #pl-qr-m .pl-qr-lnk{display:flex;gap:8px;align-items:center}
  #pl-qr-m .pl-qr-fi{display:flex;align-items:center;gap:6px;background:#fff;border:1px solid #E6DEE9;border-radius:10px;padding:0 13px;min-height:42px;flex:1;min-width:0}
  #pl-qr-m .pl-qr-fi span{overflow:hidden;text-overflow:ellipsis;white-space:nowrap;font-size:13.5px;color:#1A1A1A}
  #pl-qr-m .pl-qr-pbtn{display:inline-flex;align-items:center;gap:8px;background:#1A1A1A;color:#fff;border-radius:10px;padding:10px 16px;font:inherit;font-weight:600;font-size:13.5px;border:0;cursor:pointer;white-space:nowrap}
  #pl-qr-m .pl-qr-pbtn.pl-gh{background:#fff;color:#1A1A1A;border:1px solid #E6DEE9}
  #pl-qr-m .pl-qr-pbtn .ms{font-size:18px}
  #pl-qr-m .pl-qr-ft{display:flex;gap:10px;justify-content:flex-end;padding:14px 24px;border-top:1px solid #EFE8F1;flex-shrink:0}
  @media (max-width:560px){
    #pl-qr-m{padding:0;align-items:flex-end}
    #pl-qr-m .pl-qr-box{width:100%;max-height:92vh;border-radius:18px 18px 0 0}
    #pl-qr-m .pl-qr-ft .pl-qr-pbtn{flex:1;justify-content:center}
  }
  @media (max-width:900px){
    .pl-menu{position:fixed!important;left:12px!important;right:12px!important;top:auto!important;bottom:12px!important;width:auto!important}
    .pl-menu-sh{display:block;position:fixed;inset:0;background:rgba(26,16,32,.35);z-index:79}
    .pl-page .pl-filters{flex-direction:column;align-items:stretch!important}
    .pl-page .pl-search{width:100%}
    .pl-page .card:has(table){overflow:visible!important;background:none!important;border:0!important}
    .pl-page table,.pl-page tbody,.pl-page tr,.pl-page td{display:block;width:100%}
    .pl-page tr:has(th){display:none}
    .pl-page tbody tr{position:relative;background:#fff;border:1px solid var(--line);border-radius:14px;padding:12px 14px;margin-bottom:10px}
    .pl-page td{border:0!important;padding:5px 0!important;display:flex!important;justify-content:space-between;align-items:center;gap:12px;text-align:right}
    .pl-page td::before{content:attr(data-label);font-size:10.5px;font-weight:700;letter-spacing:.7px;color:var(--muted);text-align:left;flex-shrink:0}
    .pl-page td:first-child{padding:0 44px 8px 0!important;margin-bottom:4px;border-bottom:1px solid #F3EEF4!important;justify-content:flex-start;text-align:left}
    .pl-page td:first-child::before{display:none}
    .pl-page td[data-label="ACTIONS"]{position:absolute;top:10px;right:10px;width:auto;padding:0!important}
    .pl-page td[data-label="ACTIONS"]::before{display:none}
  }
</style>
@endsection

@section('content')
<div class="pl-page">
  <div class="head">
    <div>
      <h1>
        Bookings
        <a class="help-q" href="https://help.inkjin.com/en/articles/17200870-dashboard-bookings-payment-links" target="_blank" rel="noopener" title="Help with this page" aria-label="Help with this page">
          <span class="ms">help</span>
        </a>
      </h1>
      <div class="sub">Requests, confirmed bookings and payment links in one place.</div>
    </div>
    <button type="button" class="btn" data-open-payment-link>
      <span class="ms">add</span> New payment link
    </button>
  </div>

  @include('artist.bookings.partials.page-tabs', [
    'activeTab' => 'payment-links',
    'requestsCount' => $requestsCount ?? 0,
    'bookingsTotalCount' => $bookingsTotalCount ?? 0,
    'paymentLinksCount' => $paymentLinksCount ?? $filterCounts['all'],
  ])

  <div class="note" style="margin-bottom:14px">
    <span class="ms">info</span>
    Standalone links create a booking once paid. Balance links from a booking collect the remaining amount.
  </div>

  <form method="get" action="{{ route('artist.bookings.payment-links') }}" class="row pl-filters" style="justify-content:space-between;margin-bottom:12px;gap:12px;flex-wrap:wrap">
    <input type="hidden" name="filter" value="{{ $filter }}">
    <div class="chips">
      @foreach ([
        'all' => 'All',
        'open' => 'Open',
        'paid' => 'Paid',
        'expired' => 'Expired',
      ] as $key => $label)
        <a href="{{ route('artist.bookings.payment-links', array_filter(['filter' => $key === 'all' ? null : $key, 'q' => $search !== '' ? $search : null])) }}"
          class="chip{{ $filter === $key ? ' on' : '' }}">
          {{ $label }}@if ($key === 'all') <b>{{ $filterCounts['all'] }}</b>@endif
        </a>
      @endforeach
    </div>
    <div class="pl-search">
      <label class="in" for="plSearch">
        <span class="ms">search</span>
        <input type="search" name="q" id="plSearch" value="{{ $search }}" placeholder="Search links" autocomplete="off" aria-label="Search links">
      </label>
    </div>
  </form>

  @if ($paymentLinks->isEmpty())
    <div class="card pad" style="text-align:center;color:var(--muted)">
      <p style="font-weight:600;color:var(--ink);margin-bottom:4px">No payment links yet</p>
      <p class="sub" style="margin:0">Create a link to collect a deposit or full payment, then find it here anytime.</p>
    </div>
  @else
    <div class="card" style="overflow:hidden;margin-bottom:24px">
      <table>
        <thead>
          <tr>
            <th>TITLE</th>
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
              $createdLabel = optional($link->created_at)->format('M j') ?? '—';
              $bookingId = $link->booking_id ?? null;
              $badge = trim((string) ($link->badge ?? ''));
              $depositAmt = (float) ($link->deposit ?? 0);
              $totalAmt = (float) ($link->total ?? 0);
              $amountKind = ($link->source ?? '') === 'booking'
                ? 'balance'
                : (abs($depositAmt - $totalAmt) < 0.009 ? 'full payment' : 'deposit');
              $amountLabel = '€'.number_format($depositAmt, $depositAmt == floor($depositAmt) ? 0 : 2).' '.$amountKind;
            @endphp
            <tr data-plid="{{ $link->source }}-{{ $link->id }}">
              <td data-label="TITLE">
                <b style="font-weight:600">{{ $link->title }}</b>
                @if ($badge !== '')
                  <div style="margin-top:4px"><span class="pill nd k">{{ $badge }}</span></div>
                @endif
              </td>
              <td class="muted" data-label="CLIENT">{{ $link->client }}</td>
              <td data-label="DEPOSIT">€{{ number_format($depositAmt, 2) }}</td>
              <td data-label="TOTAL"><b style="font-weight:600">€{{ number_format($totalAmt, 2) }}</b></td>
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
                    data-source="{{ e($link->source) }}"
                    data-id="{{ (int) $link->id }}"
                    data-link="{{ e($publicUrl) }}"
                    data-title="{{ e($link->title) }}"
                    data-amount="{{ e($amountLabel) }}"
                    data-deposit="€{{ number_format($depositAmt, 2) }}"
                    data-total="€{{ number_format($totalAmt, 2) }}"
                    data-created="{{ e($createdLabel) }}"
                    data-status="{{ $status }}"
                    data-booking="{{ $bookingId ?: '' }}">
                    <span class="ms faint">more_horiz</span>
                  </button>
                </div>
              </td>
            </tr>
          @endforeach
        </tbody>
      </table>
    </div>

    <div class="row" style="justify-content:center;margin-bottom:24px">
      {{ $paymentLinks->links() }}
    </div>
  @endif
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
        <li><span class="ms">check_circle</span><span>You can duplicate it later to reuse the details</span></li>
      </ul>
    </div>
    <div class="pl-ex-ft">
      <button type="button" class="pl-ex-pbtn pl-gh" id="pl-ex-cancel">Keep it open</button>
      <button type="button" class="pl-ex-pbtn" id="pl-ex-go"><span class="ms">timer_off</span>Expire now</button>
    </div>
  </div>
</div>

<div id="paymentLinkCopyToast" class="bpw-toast" role="status" aria-live="polite">
  <span class="ms">check_circle</span>
  <span id="paymentLinkToastMsg">Link copied</span>
</div>

@include('artist.partials.payment-link-modal')
@endsection

@section('scripts')
@include('artist.partials.payment-link-modal-script')
<script>
(function () {
  var toast = document.getElementById('paymentLinkCopyToast');
  var toastMsg = document.getElementById('paymentLinkToastMsg');
  var toastTimer = null;
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
  var expireUrlBase = @json(rtrim(route('artist.bookings.payment-links'), '/'));
  var csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
  var searchInput = document.getElementById('plSearch');
  var searchTimer = null;

  function showToast(message) {
    if (!toast) return;
    if (toastMsg) toastMsg.textContent = message || 'Done';
    toast.classList.add('on');
    clearTimeout(toastTimer);
    toastTimer = setTimeout(function () { toast.classList.remove('on'); }, 2200);
  }

  function showCopied() {
    showToast('Link copied');
  }

  function copyLink(url) {
    if (!url) return;
    if (navigator.clipboard && navigator.clipboard.writeText) {
      navigator.clipboard.writeText(url).then(showCopied).catch(function () { fallbackCopy(url); });
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
    try { document.execCommand('copy'); showCopied(); } catch (e) {}
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
    exGo.innerHTML = loading
      ? 'Expiring…'
      : '<span class="ms">timer_off</span>Expire now';
  }

  function openExpire(opts) {
    expireTarget = { source: opts.source || '', id: opts.id || '' };
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

  function expireUrl(source, id) {
    return expireUrlBase + '/' + encodeURIComponent(source) + '/' + encodeURIComponent(id) + '/expire';
  }

  function confirmExpire() {
    if (!expireTarget || expireBusy) return;
    expireBusy = true;
    setExpireGoLabel(true);
    fetch(expireUrl(expireTarget.source, expireTarget.id), {
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
    var source = btn.getAttribute('data-source') || '';
    var id = btn.getAttribute('data-id') || '';

    var html = '';
    html += '<button type="button" data-a="copy" data-link="' + link.replace(/"/g, '&quot;') + '"><span class="ms">content_copy</span>Copy link</button>';
    html += '<button type="button" data-a="qr" data-link="' + link.replace(/"/g, '&quot;') + '" data-title="' + title.replace(/"/g, '&quot;') + '" data-amount="' + amount.replace(/"/g, '&quot;') + '"><span class="ms">qr_code_2</span>Show QR</button>';
    if (bookingId) {
      html += '<a href="{{ route('artist.bookings.index') }}?q=' + encodeURIComponent(bookingId) + '"><span class="ms">visibility</span>View booking</a>';
    }
    if (status === 'open' && source && id) {
      html += '<hr>';
      html += '<button type="button" class="red" data-a="expire"'
        + ' data-source="' + source.replace(/"/g, '&quot;') + '"'
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
    var top = rect.bottom + window.scrollY + 6;
    var left = rect.right + window.scrollX - 230;
    if (left < 12) left = 12;
    if (window.innerWidth > 900) {
      menu.style.top = top + 'px';
      menu.style.left = left + 'px';
    } else {
      menu.style.top = '';
      menu.style.left = '';
    }
  }

  document.querySelectorAll('.js-pl-copy').forEach(function (btn) {
    btn.addEventListener('click', function () {
      copyLink(btn.getAttribute('data-link') || '');
    });
  });

  document.querySelectorAll('.js-pl-qr').forEach(function (btn) {
    btn.addEventListener('click', function () {
      openQr(
        btn.getAttribute('data-link') || '',
        btn.getAttribute('data-title') || '',
        btn.getAttribute('data-amount') || ''
      );
    });
  });

  document.querySelectorAll('.js-pl-more').forEach(function (btn) {
    btn.addEventListener('click', function (e) {
      e.stopPropagation();
      if (!menu.hidden && menu.dataset.for === String(btn)) {
        closeMenu();
        return;
      }
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
      openQr(
        item.getAttribute('data-link') || '',
        item.getAttribute('data-title') || '',
        item.getAttribute('data-amount') || ''
      );
    }
    if (action === 'expire') {
      openExpire({
        source: item.getAttribute('data-source') || '',
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
    if (e.key === 'Escape') {
      closeMenu();
      closeQr();
      closeExpire();
    }
  });

  document.getElementById('pl-qr-x')?.addEventListener('click', closeQr);
  document.getElementById('pl-qr-done')?.addEventListener('click', closeQr);
  document.getElementById('pl-qr-copy')?.addEventListener('click', function () { copyLink(lastQrLink); });
  document.getElementById('pl-qr-copy-inline')?.addEventListener('click', function () { copyLink(lastQrLink); });
  qrModal?.addEventListener('click', function (e) {
    if (e.target === qrModal) closeQr();
  });

  document.getElementById('pl-ex-x')?.addEventListener('click', closeExpire);
  document.getElementById('pl-ex-cancel')?.addEventListener('click', closeExpire);
  exGo?.addEventListener('click', confirmExpire);
  exModal?.addEventListener('click', function (e) {
    if (e.target === exModal) closeExpire();
  });

  if (searchInput) {
    searchInput.addEventListener('keydown', function (e) {
      if (e.key === 'Enter') {
        e.preventDefault();
        searchInput.form?.submit();
      }
    });
    searchInput.addEventListener('input', function () {
      clearTimeout(searchTimer);
      searchTimer = setTimeout(function () {
        searchInput.form?.submit();
      }, 400);
    });
  }
})();
</script>
@endsection
