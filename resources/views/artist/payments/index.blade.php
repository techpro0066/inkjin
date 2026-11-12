@extends('layouts.new-artist-dashboard-layout')

@section('title', 'Money · Earnings')

@php
  $activeNav = 'money';
  $currency = $stats['currency_symbol'] ?? '€';
  $payoutConnected = (bool) ($stats['payout_account_connected'] ?? false);
  $payoutMode = in_array(($stats['payout_mode'] ?? 'automatic'), ['manual', 'automatic'], true)
    ? ($stats['payout_mode'] ?? 'automatic')
    : 'automatic';
  $availableBalance = (float) ($stats['available_balance'] ?? 0);
  $pendingSchedule = $stats['pending_schedule'] ?? [];
  $hasPendingSchedule = count($pendingSchedule) > 0;
  $payoutTypeLabel = (string) ($stats['payout_type_label'] ?? 'Artist payouts');
  $nextPayoutMeta = (string) ($stats['next_payout_meta'] ?? 'Paid automatically to your bank');
  $nextArrivalLabel = $stats['next_arrival_label'] ?? null;
  $outstandingArtistFee = (float) ($stats['outstanding_artist_fee'] ?? 0);
@endphp

@section('styles')
<style>
  .money-page .help-q{
    display:inline-flex;align-items:center;justify-content:center;width:26px;height:26px;margin-left:8px;
    border-radius:50%;color:#9A929E;vertical-align:middle;text-decoration:none;position:relative;top:-2px
  }
  .money-page .help-q .ms{font-size:22px}
  .money-page .help-q:hover{color:#3E007C;background:#F3E8FF}
  .money-page .kpi-dark{background:#1A1A1A;color:#fff;border-color:#1A1A1A}
  .money-page .kpi-dark .lbl{color:#bdbdbd}
  .money-page .kpi-meta{
    margin-top:16px;gap:8px;background:#2A2A2A;border-radius:10px;padding:9px 12px;font-size:13px;color:#dedede
  }
  .money-page .kpi-meta .ms{font-size:18px}
  .money-page .stat-click{cursor:pointer;border:0;background:inherit;font:inherit;color:inherit;text-align:left;width:100%;padding:0}
  .money-page .stat-click:disabled{cursor:default;opacity:1}
  .money-page .pay-row{cursor:pointer}
  .money-page .pay-row:hover td{background:#FBF8FC}
  .money-page .chip{cursor:pointer;font:inherit}
  .money-page select.in{appearance:auto;-webkit-appearance:menulist}
  .money-page button.btn{border:0;cursor:pointer;font:inherit}
  .money-page .bdm{
    position:fixed;inset:0;background:rgba(20,10,30,.45);display:none;align-items:center;justify-content:center;
    z-index:80;padding:16px
  }
  .money-page .bdm.open{display:flex}
  .money-page .bdm[hidden]{display:none!important}
  .money-page .bdm.open:not([hidden]){display:flex!important}
  .money-page .bdm-box{
    background:#fff;border-radius:22px;width:440px;max-width:100%;max-height:min(86vh,640px);
    box-shadow:0 20px 60px rgba(0,0,0,.25);overflow:hidden;display:flex;flex-direction:column
  }
  .money-page .bdm-h{padding:22px 22px 14px;border-bottom:1px solid var(--line);display:flex;gap:12px;align-items:flex-start}
  .money-page .bdm-h h2{font-size:18px;font-weight:800;letter-spacing:-.3px;flex:1}
  .money-page .bdm-x{border:0;background:none;cursor:pointer;padding:4px;border-radius:8px;color:var(--muted)}
  .money-page .bdm-x:hover{background:var(--chip);color:var(--ink)}
  .money-page .bdm-b{padding:8px 22px 18px;overflow:auto}
  .money-page .bdm-r{display:flex;justify-content:space-between;gap:16px;padding:10px 0;border-bottom:1px solid #F3EEF4;font-size:13.5px}
  .money-page .bdm-r:last-child{border-bottom:0}
  .money-page .bdm-r > span{color:var(--muted);min-width:0}
  .money-page .bdm-r b{font-weight:700;text-align:right;white-space:nowrap}
  .money-page .bdm-note{display:block;font-size:12px;color:var(--faint,#9A929E);margin-top:2px;line-height:1.35;font-weight:400}
  .money-page .bdm-tot{display:flex;justify-content:space-between;gap:16px;padding:14px 0 4px;font-size:15px;font-weight:800}
  .money-page .page-btn{
    min-width:36px;height:36px;display:inline-flex;align-items:center;justify-content:center;border-radius:10px;
    font-size:14px;font-weight:500;border:1px solid var(--line);background:#fff;color:var(--ink);cursor:pointer
  }
  .money-page .page-btn:hover:not(:disabled){background:var(--tint)}
  .money-page .page-btn.active{background:var(--ink);color:#fff;border-color:var(--ink)}
  .money-page .page-btn:disabled{opacity:.4;cursor:not-allowed}
  @media(max-width:900px){
    .money-page .card:has(table){overflow:visible;background:none;border:0}
    .money-page table,.money-page tbody,.money-page tr,.money-page td{display:block;width:100%}
    .money-page tr:has(th){display:none}
    .money-page tbody tr{
      position:relative;background:#fff;border:1px solid var(--line);border-radius:14px;padding:12px 14px;margin-bottom:10px
    }
    .money-page td{
      border:0!important;padding:5px 0!important;display:flex!important;justify-content:space-between;align-items:center;
      gap:12px;font-size:13.5px;text-align:right
    }
    .money-page td::before{
      content:attr(data-label);font-size:10.5px;font-weight:700;letter-spacing:.7px;color:var(--muted);text-align:left;flex-shrink:0
    }
    .money-page td:first-child{
      padding:0 0 8px!important;margin-bottom:4px;border-bottom:1px solid #F3EEF4!important;
      justify-content:flex-start;text-align:left
    }
    .money-page td:first-child::before{display:none}
  }
</style>
@endsection

@section('content')
<div class="money-page">
  <div class="head">
    <div>
      <h1>
        Money
        <a class="help-q" href="https://help.inkjin.com/en/articles/17201180-dashboard-money-earnings" target="_blank" rel="noopener" title="Help with this page" aria-label="Help with this page">
          <span class="ms">help</span>
        </a>
      </h1>
      <div class="sub">What you've earned, how clients pay you, and where your payouts go.</div>
    </div>
  </div>

  @include('artist.partials.money-tabs', ['moneyTab' => 'earnings'])

  <div class="grid" style="grid-template-columns:1.2fr 1fr 1fr">
    <div class="card pad kpi-dark">
      <div class="lbl">{{ $payoutMode === 'automatic' ? 'Next payout' : 'Available' }}</div>
      <div style="font-size:34px;font-weight:800;margin:8px 0 4px;letter-spacing:-.6px">
        {{ $currency }}{{ number_format($payoutConnected ? $availableBalance : 0, 2) }}
      </div>
      <div style="font-size:12.5px;color:#bdbdbd">{{ $nextPayoutMeta }}</div>
      @if($nextArrivalLabel)
        <div class="row kpi-meta">
          <span class="ms">event</span>
          <span>{{ $nextArrivalLabel }}</span>
        </div>
      @elseif(! $payoutConnected)
        <div class="row kpi-meta">
          <span class="ms">link</span>
          <span>Connect payouts to receive this balance</span>
        </div>
      @endif
      @if($outstandingArtistFee > 0.009)
        <div class="row kpi-meta">
          <span class="ms">receipt_long</span>
          <span>{{ $currency }}{{ number_format($outstandingArtistFee, 2) }} booking fee balance will be deducted from future payouts</span>
        </div>
      @endif
      @if($payoutConnected && $payoutMode === 'manual' && $availableBalance > 0.009)
        <div style="margin-top:14px">
          <button type="button" class="btn sm" id="openRequestPayoutBtn" style="background:#fff;color:#1A1A1A">Request payout</button>
        </div>
      @elseif(! $payoutConnected)
        <div style="margin-top:14px">
          <a href="{{ route('settings.payment') }}" class="btn sm" style="background:#fff;color:#1A1A1A">Set up payouts</a>
        </div>
      @endif
    </div>

    <div class="card pad">
      <button type="button" class="stat-click" id="openPendingScheduleBtn" @if(! $hasPendingSchedule) disabled @endif aria-label="View pending earnings">
        <div class="row" style="justify-content:space-between">
          <div class="lbl">Pending</div>
          <span class="ms" style="color:#B86E00">schedule</span>
        </div>
        <div style="font-size:30px;font-weight:800;margin:8px 0 4px">{{ $currency }}{{ number_format($stats['pending_total'], 2) }}</div>
        <div class="faint" style="font-size:12.5px">Released after each session, then paid out</div>
        <div style="margin-top:16px;font-weight:600;font-size:13px;color:{{ $hasPendingSchedule ? 'var(--pri)' : 'var(--muted)' }}">
          {{ $hasPendingSchedule ? 'View by booking →' : 'No pending payouts' }}
        </div>
      </button>
    </div>

    <div class="card pad">
      <div class="row" style="justify-content:space-between">
        <div class="lbl">Total earned</div>
        <span class="ms" style="color:#7A1FC4">trending_up</span>
      </div>
      <div style="font-size:30px;font-weight:800;margin:8px 0 4px">{{ $currency }}{{ number_format($stats['total_earned'], 2) }}</div>
      <div class="faint" style="font-size:12.5px">{{ $stats['since_label'] }}</div>
    </div>
  </div>

  <div class="card row" style="padding:14px 20px;margin-top:14px;gap:14px;align-items:center;flex-wrap:wrap">
    <div class="ic"><span class="ms">account_balance</span></div>
    <div style="flex:1;min-width:200px">
      <b>Payouts go to your bank via Stripe</b>
      @if($payoutConnected)
        <span class="pill g" style="margin-left:6px">Connected</span>
      @else
        <span class="pill a" style="margin-left:6px">Not connected</span>
      @endif
      <div class="faint" style="font-size:12.5px;margin-top:2px">
        {{ $payoutTypeLabel }}
        · {{ $payoutMode === 'automatic' ? 'Paid out automatically when funds become available' : 'Manual payouts' }}
      </div>
    </div>
    <a href="{{ route('settings.payment') }}" class="btn sm ghost">Payout settings</a>
    <a href="{{ route('settings.preferences') }}" class="btn sm ghost">Deposits &amp; rates</a>
  </div>

  <div class="row" style="justify-content:space-between;margin:28px 0 12px;gap:12px;flex-wrap:wrap">
    <h3 style="margin:0">Payment history</h3>
    <div class="chips" id="statusFilters">
      <button type="button" class="chip on" data-status="all">All</button>
      <button type="button" class="chip" data-status="Pending">Pending</button>
      <button type="button" class="chip" data-status="Available">On the way</button>
      <button type="button" class="chip" data-status="Completed">Paid out</button>
      <button type="button" class="chip" data-status="Refunded">Refunded</button>
      <button type="button" class="btn sm ghost" id="exportCsvBtn"><span class="ms" style="font-size:16px">download</span>Export CSV</button>
    </div>
  </div>

  <div class="card" style="overflow:hidden">
    <div style="padding:14px 16px;border-bottom:1px solid #F3EEF4;display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:10px" class="money-filters">
      <div>
        <label class="fl" for="sortBy">Sort by</label>
        <select id="sortBy" class="in">
          <option value="recent">Most recent</option>
          <option value="oldest">Oldest</option>
          <option value="amount-high">Amount high → low</option>
          <option value="amount-low">Amount low → high</option>
        </select>
      </div>
      <div>
        <label class="fl" for="dateFrom">From</label>
        <input type="date" id="dateFrom" class="in">
      </div>
      <div>
        <label class="fl" for="dateTo">To</label>
        <input type="date" id="dateTo" class="in">
      </div>
      <div>
        <label class="fl" for="searchInput">Search</label>
        <input type="text" id="searchInput" class="in" placeholder="Client, service, or reference">
      </div>
    </div>

    <table>
      <thead>
        <tr>
          <th>Client</th>
          <th>Service</th>
          <th>Date</th>
          <th>Collected</th>
          <th>Fee</th>
          <th>Studio gets</th>
          <th>You get</th>
          <th>Status</th>
        </tr>
      </thead>
      <tbody id="paymentsTableBody"></tbody>
    </table>
    <div id="paymentsMobileCards" hidden></div>
    <div class="help" style="margin:0;padding:12px 16px;border-top:1px solid #F3EEF4">
      Stripe charges 1.5% + €0.25 per payment (EU cards; other cards cost more). You pay it. With a studio split, you and the studio each pay your own part, and the Stripe fee column shows yours. The €10 Service Booking Fee is paid by the client and goes to Bookpay, so it isn't in these amounts. Tap a row to see the breakdown.
    </div>
  </div>

  <div class="row" style="justify-content:space-between;margin-top:16px;gap:12px;flex-wrap:wrap">
    <p class="muted" style="margin:0;font-size:13px">Showing <span id="showingRange">0</span> of <span id="totalCount">0</span></p>
    <div class="row" style="gap:8px" id="paginationButtons">
      <button type="button" class="page-btn" id="prevBtn" disabled><span class="ms">chevron_left</span></button>
      <button type="button" class="page-btn" id="nextBtn" disabled><span class="ms">chevron_right</span></button>
    </div>
  </div>

{{-- Breakdown modal --}}
<div class="bdm" id="breakdownModal" role="dialog" aria-modal="true" aria-labelledby="breakdownTitle" hidden>
  <div class="bdm-box">
    <div class="bdm-h">
      <h2 id="breakdownTitle">Breakdown</h2>
      <button type="button" class="bdm-x" id="closeBreakdownBtn" aria-label="Close"><span class="ms">close</span></button>
    </div>
    <div class="bdm-b" id="breakdownBody"></div>
    <div class="bdm-f" style="padding:0 22px 18px;display:flex;justify-content:flex-end">
      <button type="button" class="btn" id="closeBreakdownFooterBtn" style="min-width:110px;justify-content:center">Close</button>
    </div>
  </div>
</div>

{{-- Request payout modal --}}
<div id="requestPayoutModal" class="bdm" role="dialog" aria-modal="true" aria-labelledby="requestPayoutTitle" hidden>
  <div class="bdm-box" style="width:420px">
    <div class="bdm-h">
      <h2 id="requestPayoutTitle">Request payout</h2>
      <button type="button" class="bdm-x" id="closeRequestPayoutBtn" aria-label="Close"><span class="ms">close</span></button>
    </div>
    <div class="bdm-b">
      <p class="muted" style="margin:0 0 16px;font-size:13.5px;line-height:1.5">Enter how much you want to withdraw from your available balance.</p>
      <form id="requestPayoutForm" novalidate>
        <div class="card pad" style="margin-bottom:14px;display:flex;justify-content:space-between;align-items:center">
          <span class="lbl" style="margin:0">Available</span>
          <b>{{ $currency }}{{ number_format($availableBalance, 2) }}</b>
        </div>
        <label class="fl" for="requestPayoutAmount">Amount</label>
        <div class="in" style="padding-left:12px">
          <span class="muted">{{ $currency }}</span>
          <input type="number" id="requestPayoutAmount" name="amount" inputmode="decimal" min="0.01" step="0.01" max="{{ number_format($availableBalance, 2, '.', '') }}" placeholder="0.00" style="border:0;outline:0;width:100%;font:inherit;background:transparent">
        </div>
        <div class="row" style="justify-content:space-between;margin-top:8px">
          <span class="help" style="margin:0">Max {{ $currency }}{{ number_format($availableBalance, 2) }}</span>
          <button type="button" id="requestPayoutMaxBtn" class="btn sm ghost" style="padding:4px 10px">Withdraw all</button>
        </div>
        <p id="requestPayoutError" class="help" style="margin-top:10px;color:var(--red);display:none"></p>
        <div class="row" style="gap:10px;margin-top:18px">
          <button type="button" id="cancelRequestPayoutBtn" class="btn ghost" style="flex:1;justify-content:center">Cancel</button>
          <button type="submit" id="submitRequestPayoutBtn" class="btn pri" style="flex:1;justify-content:center">Request payout</button>
        </div>
      </form>
    </div>
  </div>
</div>

{{-- Pending schedule modal --}}
<div id="pendingScheduleModal" class="bdm" role="dialog" aria-modal="true" aria-labelledby="pendingScheduleTitle" hidden>
  <div class="bdm-box" style="width:480px">
    <div class="bdm-h">
      <div style="flex:1">
        <h2 id="pendingScheduleTitle">Pending earnings</h2>
        <p class="faint" style="font-size:13px;margin-top:4px">When each booking becomes available to withdraw.</p>
      </div>
      <button type="button" class="bdm-x" id="closePendingScheduleBtn" aria-label="Close"><span class="ms">close</span></button>
    </div>
    <div class="bdm-b">
      <div class="card pad" style="margin-bottom:14px;display:flex;justify-content:space-between;align-items:center">
        <span class="lbl" style="margin:0">Total pending</span>
        <b>{{ $currency }}{{ number_format($stats['pending_total'], 2) }}</b>
      </div>
      @if($hasPendingSchedule)
        <div class="card" style="overflow:hidden">
          @foreach($pendingSchedule as $item)
            <div style="padding:12px 14px;border-bottom:1px solid #F3EEF4">
              <div class="row" style="justify-content:space-between;gap:12px;align-items:flex-start">
                <div style="min-width:0">
                  <b style="font-size:13.5px">{{ $item['client'] }}</b>
                  <div class="faint" style="font-size:12.5px">{{ $item['service'] }}</div>
                  <div class="faint" style="font-size:11.5px">{{ $item['reference'] }}</div>
                </div>
                <b style="font-size:13.5px;white-space:nowrap">{{ $currency }}{{ number_format($item['amount'], 2) }}</b>
              </div>
              <div class="row" style="justify-content:space-between;margin-top:8px;gap:10px;font-size:12.5px">
                <span class="muted">{{ $item['reason'] }}</span>
                <span style="color:var(--amber);font-weight:700;white-space:nowrap">{{ $item['available_label'] }}</span>
              </div>
            </div>
          @endforeach
        </div>
      @else
        <p class="muted" style="text-align:center;padding:28px 0">No pending earnings right now.</p>
      @endif
      <button type="button" id="dismissPendingScheduleBtn" class="btn" style="width:100%;justify-content:center;margin-top:14px">Close</button>
    </div>
  </div>
</div>
</div>
@endsection

@section('scripts')
<script>
  window.inkjinArtistPayments = @json($payments);
  window.inkjinCurrencySymbol = @json($currency);
  window.inkjinAvailableBalance = @json(round($availableBalance, 2));
</script>
<script>
  (function () {
    const modal = document.getElementById('requestPayoutModal');
    const openBtn = document.getElementById('openRequestPayoutBtn');
    const closeBtn = document.getElementById('closeRequestPayoutBtn');
    const cancelBtn = document.getElementById('cancelRequestPayoutBtn');
    const maxBtn = document.getElementById('requestPayoutMaxBtn');
    const amountInput = document.getElementById('requestPayoutAmount');
    const errorEl = document.getElementById('requestPayoutError');
    const submitBtn = document.getElementById('submitRequestPayoutBtn');
    const form = document.getElementById('requestPayoutForm');
    if (!modal || !form) return;

    let available = Number(window.inkjinAvailableBalance || 0);
    let saving = false;
    const requestUrl = @json(route('artist.payments.request-payout'));
    const csrf = @json(csrf_token());

    function setError(message) {
      if (!errorEl) return;
      if (!message) {
        errorEl.textContent = '';
        errorEl.style.display = 'none';
        return;
      }
      errorEl.textContent = message;
      errorEl.style.display = '';
    }

    function openModal() {
      if (available < 0.01) return;
      setError('');
      modal.hidden = false;
      modal.classList.add('open');
      if (amountInput) {
        amountInput.value = '';
        setTimeout(function () { amountInput.focus(); }, 50);
      }
    }

    function closeModal() {
      if (saving) return;
      modal.classList.remove('open');
      modal.hidden = true;
      setError('');
    }

    openBtn?.addEventListener('click', openModal);
    closeBtn?.addEventListener('click', closeModal);
    cancelBtn?.addEventListener('click', closeModal);
    modal.addEventListener('click', function (e) {
      if (e.target === modal) closeModal();
    });

    maxBtn?.addEventListener('click', function () {
      if (!amountInput) return;
      amountInput.value = available > 0 ? available.toFixed(2) : '';
      amountInput.focus();
    });

    form.addEventListener('submit', function (e) {
      e.preventDefault();
      if (saving) return;
      const amount = Number(amountInput ? amountInput.value : '');
      if (!Number.isFinite(amount) || amount < 0.01) {
        setError('Enter an amount greater than zero.');
        return;
      }
      if (amount > available + 0.001) {
        setError('Amount cannot exceed your available balance.');
        return;
      }

      saving = true;
      setError('');
      if (submitBtn) {
        submitBtn.disabled = true;
        submitBtn.textContent = 'Sending…';
      }

      fetch(requestUrl, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'Accept': 'application/json',
          'X-CSRF-TOKEN': csrf,
          'X-Requested-With': 'XMLHttpRequest',
        },
        body: JSON.stringify({ amount: amount }),
      })
        .then(async function (res) {
          const data = await res.json().catch(function () { return {}; });
          if (!res.ok || !data.success) {
            throw new Error(data.message || (data.errors && data.errors.amount && data.errors.amount[0]) || 'Could not process payout request.');
          }
          return data;
        })
        .then(function () { window.location.reload(); })
        .catch(function (err) {
          setError(err.message || 'Could not process payout request.');
          saving = false;
          if (submitBtn) {
            submitBtn.disabled = false;
            submitBtn.textContent = 'Request payout';
          }
        });
    });
  })();
</script>
<script>
  (function () {
    const openBtn = document.getElementById('openPendingScheduleBtn');
    const modal = document.getElementById('pendingScheduleModal');
    const closeBtn = document.getElementById('closePendingScheduleBtn');
    const dismissBtn = document.getElementById('dismissPendingScheduleBtn');
    if (!openBtn || !modal) return;

    function openModal() {
      modal.hidden = false;
      modal.classList.add('open');
    }
    function closeModal() {
      modal.classList.remove('open');
      modal.hidden = true;
    }

    openBtn.addEventListener('click', function () {
      if (openBtn.disabled) return;
      openModal();
    });
    closeBtn?.addEventListener('click', closeModal);
    dismissBtn?.addEventListener('click', closeModal);
    modal.addEventListener('click', function (e) {
      if (e.target === modal) closeModal();
    });
  })();
</script>
<script src="{{ asset('js/artist-payments.js') }}?v=6"></script>
<style>
  @media(max-width:900px){
    .money-page .money-filters{grid-template-columns:1fr 1fr!important}
  }
  @media(max-width:560px){
    .money-page .money-filters{grid-template-columns:1fr!important}
    .money-page .grid[style*="1.2fr"]{grid-template-columns:1fr!important}
  }
</style>
@endsection
