@extends('layouts.artist_dashboard_layout')

@section('title', 'Earnings')

@section('styles')
<style>
  .status-completed { background: #f0fdf4; color: #166534; }
  .status-completed .status-dot { background: #22c55e; }
  .status-pending { background: #fffbeb; color: #b45309; }
  .status-pending .status-dot { background: #f59e0b; }
  .status-available { background: #eff6ff; color: #1d4ed8; }
  .status-available .status-dot { background: #3b82f6; }
  .status-cancelled { background: #fef2f2; color: #b91c1c; }
  .status-cancelled .status-dot { background: #ef4444; }
  .status-refunded { background: #f5f3ff; color: #6d28d9; }
  .status-refunded .status-dot { background: #8b5cf6; }
  .filter-pill { transition: all 0.2s; }
  .filter-pill.active { background: #310f7a; color: #ffffff; }
  .payments-table th { text-align: left; font-size: 12px; font-weight: 600; color: #494552; text-transform: uppercase; letter-spacing: 0.05em; padding: 12px 16px; border-bottom: 1px solid rgba(202,196,211,0.3); }
  .payments-table td { padding: 14px 16px; font-size: 14px; border-bottom: 1px solid rgba(202,196,211,0.15); vertical-align: top; }
  .earnings-breakdown { margin-top: 4px; }
  .earnings-breakdown-line { display: flex; align-items: center; justify-content: space-between; gap: 12px; font-size: 12px; color: #494552; line-height: 1.5; }
  .earnings-breakdown-line + .earnings-breakdown-line { margin-top: 2px; }
  .earnings-breakdown-label { color: #6b6574; }
  .earnings-breakdown-note { font-size: 11px; color: #9b94a6; font-style: italic; }
  .earnings-breakdown-total { font-size: 13px; font-weight: 600; color: #1c1b21; margin-bottom: 2px; }
  .payments-table tbody tr { transition: background 0.15s; }
  .payments-table tbody tr:hover { background: #f8f1fb; }
  .payment-mobile-card { display: none; }
  @media (max-width: 767px) {
    .payments-table-wrapper { display: none; }
    .payment-mobile-card { display: block; }
  }
  .page-btn { min-width: 36px; height: 36px; display: flex; align-items: center; justify-content: center; border-radius: 10px; font-size: 14px; font-weight: 500; transition: all 0.2s; cursor: pointer; border: 1px solid rgba(202,196,211,0.3); background: white; color: #1c1b21; }
  .page-btn:hover:not(:disabled) { background: #f8f1fb; }
  .page-btn.active { background: #310f7a; color: white; border-color: #310f7a; }
  .page-btn:disabled { opacity: 0.4; cursor: not-allowed; }
  .stat-card-clickable { cursor: pointer; transition: box-shadow 0.2s, border-color 0.2s; }
  .stat-card-clickable:hover { box-shadow: 0 4px 14px rgba(49, 15, 122, 0.08); border-color: rgba(49, 15, 122, 0.15); }
  .pending-schedule-row + .pending-schedule-row { border-top: 1px solid rgba(202,196,211,0.2); }
</style>
@endsection

@section('content')
@php
  $currency = $stats['currency_symbol'] ?? '€';
  $payoutConnected = (bool) ($stats['payout_account_connected'] ?? false);
  $payoutMode = in_array(($stats['payout_mode'] ?? 'manual'), ['manual', 'automatic'], true)
    ? ($stats['payout_mode'] ?? 'manual')
    : 'manual';
  $availableBalance = (float) ($stats['available_balance'] ?? 0);
  $pendingSchedule = $stats['pending_schedule'] ?? [];
  $hasPendingSchedule = count($pendingSchedule) > 0;
@endphp
<main class="main-content flex-1 min-h-screen">
  <div class="p-6 md:p-10 lg:p-12 max-w-6xl">

    <div class="mb-8">
      <h2 class="text-3xl font-extrabold text-on-surface tracking-tight">Earnings</h2>
      <p class="text-on-surface-variant mt-1">Track your earnings, payouts, and payment history.</p>
    </div>

    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4 mb-8">
      <div class="bg-white rounded-2xl border border-outline-variant/10 p-5">
        <div class="flex items-start justify-between mb-3">
          <div>
            <p class="text-xs font-semibold text-on-surface-variant uppercase tracking-wider mb-1">Available Balance</p>
            <p class="text-2xl font-extrabold text-on-surface">{{ $currency }}{{ number_format($payoutConnected ? $availableBalance : 0, 2) }}</p>
          </div>
          <div class="w-10 h-10 rounded-full bg-green-100 flex items-center justify-center flex-shrink-0">
            <span class="material-symbols-outlined text-green-700" style="font-size:20px;">credit_card</span>
          </div>
        </div>

        @if (! $payoutConnected)
          <p class="text-sm text-on-surface-variant mb-4 leading-relaxed">You need to set up payouts to receive your available balance.</p>
          <a href="{{ route('settings.payment') }}" class="inline-flex items-center justify-center bg-on-surface text-white text-sm font-semibold px-5 py-2.5 rounded-full hover:opacity-90 transition-opacity">
            Set up payouts
          </a>
        @elseif ($payoutMode === 'manual')
          <p class="text-sm text-on-surface-variant mb-4 leading-relaxed">Request a payout to receive your balance.</p>
          <div class="flex items-center gap-4">
            <button
              type="button"
              id="openRequestPayoutBtn"
              class="inline-flex items-center justify-center bg-on-surface text-white text-sm font-semibold px-5 py-2.5 rounded-full hover:opacity-90 transition-opacity"
            >
              Request payout
            </button>
            <a href="{{ route('settings.payment') }}" class="text-sm font-semibold text-primary hover:opacity-80 transition-opacity">Change</a>
          </div>
        @else
          <p class="text-sm text-on-surface-variant mb-4 leading-relaxed">Your balance is paid out automatically. No action needed.</p>
          <a href="{{ route('settings.payment') }}" class="text-sm font-semibold text-primary hover:opacity-80 transition-opacity">Change</a>
        @endif
      </div>

      <button
        type="button"
        id="openPendingScheduleBtn"
        class="stat-card-clickable w-full text-left bg-white rounded-2xl border border-outline-variant/10 p-5 {{ $hasPendingSchedule ? '' : 'cursor-default' }}"
        @if (! $hasPendingSchedule) disabled @endif
        aria-label="View pending earnings schedule"
      >
        <div class="flex items-start justify-between mb-3">
          <div>
            <p class="text-xs font-semibold text-on-surface-variant uppercase tracking-wider mb-1">Pending</p>
            <p class="text-2xl font-extrabold text-on-surface">{{ $currency }}{{ number_format($stats['pending_total'], 2) }}</p>
          </div>
          <div class="w-10 h-10 rounded-full bg-amber-100 flex items-center justify-center flex-shrink-0">
            <span class="material-symbols-outlined text-amber-600" style="font-size:20px;">schedule</span>
          </div>
        </div>
        @if ($hasPendingSchedule)
          <p class="text-xs text-primary font-semibold">View availability by booking →</p>
        @else
          <p class="text-xs text-on-surface-variant">{{ $stats['pending_count'] }} {{ Str::plural('payment', $stats['pending_count']) }} processing</p>
        @endif
      </button>

      <div class="bg-white rounded-2xl border border-outline-variant/10 p-5">
        <div class="flex items-start justify-between mb-3">
          <div>
            <p class="text-xs font-semibold text-on-surface-variant uppercase tracking-wider mb-1">Total Earned</p>
            <p class="text-2xl font-extrabold text-on-surface">{{ $currency }}{{ number_format($stats['total_earned'], 2) }}</p>
          </div>
          <div class="w-10 h-10 rounded-full bg-purple-100 flex items-center justify-center flex-shrink-0">
            <span class="material-symbols-outlined text-purple-600" style="font-size:20px;">trending_up</span>
          </div>
        </div>
        <p class="text-xs text-on-surface-variant">{{ $stats['since_label'] }}</p>
      </div>
    </div>

    <div class="bg-surface-container-low rounded-2xl p-5 mb-6 border border-outline-variant/20">
      <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-4">
        <div>
          <label for="sortBy" class="block text-xs font-semibold text-on-surface-variant mb-1.5">Sort by</label>
          <select id="sortBy" class="w-full text-sm border border-outline-variant/30 rounded-xl px-3 py-2 bg-white text-on-surface focus:outline-none focus:ring-2 focus:ring-primary/30">
            <option value="recent">Most Recent</option>
            <option value="oldest">Oldest</option>
            <option value="amount-high">Amount High→Low</option>
            <option value="amount-low">Amount Low→High</option>
          </select>
        </div>
        <div>
          <label for="dateFrom" class="block text-xs font-semibold text-on-surface-variant mb-1.5">From</label>
          <input type="date" id="dateFrom" class="w-full text-sm border border-outline-variant/30 rounded-xl px-3 py-2 bg-white text-on-surface focus:outline-none focus:ring-2 focus:ring-primary/30">
        </div>
        <div>
          <label for="dateTo" class="block text-xs font-semibold text-on-surface-variant mb-1.5">To</label>
          <input type="date" id="dateTo" class="w-full text-sm border border-outline-variant/30 rounded-xl px-3 py-2 bg-white text-on-surface focus:outline-none focus:ring-2 focus:ring-primary/30">
        </div>
        <div>
          <label for="searchInput" class="block text-xs font-semibold text-on-surface-variant mb-1.5">Search</label>
          <div class="relative">
            <span class="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-outline text-lg">search</span>
            <input type="text" id="searchInput" placeholder="Search client or booking..." class="w-full text-sm border border-outline-variant/30 rounded-xl pl-9 pr-3 py-2 bg-white text-on-surface focus:outline-none focus:ring-2 focus:ring-primary/30">
          </div>
        </div>
      </div>
      <div class="flex flex-wrap gap-2">
        <button type="button" class="filter-pill active text-xs font-semibold px-4 py-1.5 rounded-full border border-outline-variant/30 bg-white text-on-surface-variant" data-status="all">All</button>
        <button type="button" class="filter-pill text-xs font-semibold px-4 py-1.5 rounded-full border border-outline-variant/30 bg-white text-on-surface-variant" data-status="Completed">
          <span class="inline-block w-1.5 h-1.5 rounded-full bg-green-500 mr-1.5"></span>Completed
        </button>
        <button type="button" class="filter-pill text-xs font-semibold px-4 py-1.5 rounded-full border border-outline-variant/30 bg-white text-on-surface-variant" data-status="Pending">
          <span class="inline-block w-1.5 h-1.5 rounded-full bg-amber-500 mr-1.5"></span>Pending
        </button>
        <button type="button" class="filter-pill text-xs font-semibold px-4 py-1.5 rounded-full border border-outline-variant/30 bg-white text-on-surface-variant" data-status="Failed">
          <span class="inline-block w-1.5 h-1.5 rounded-full bg-red-500 mr-1.5"></span>Failed
        </button>
        <button type="button" class="filter-pill text-xs font-semibold px-4 py-1.5 rounded-full border border-outline-variant/30 bg-white text-on-surface-variant" data-status="Refunded">
          <span class="inline-block w-1.5 h-1.5 rounded-full bg-blue-500 mr-1.5"></span>Refunded
        </button>
      </div>
    </div>

    <div class="bg-white rounded-2xl border border-outline-variant/10 mb-6">
      <div class="payments-table-wrapper overflow-x-auto">
        <table class="payments-table w-full">
          <thead>
            <tr>
              <th>Client</th>
              <th>Service</th>
              <th>Date</th>
              <th>Earnings</th>
              <th>Fee</th>
              <th>Net</th>
              <th>Status</th>
            </tr>
          </thead>
          <tbody id="paymentsTableBody"></tbody>
        </table>
      </div>
      <div id="paymentsMobileCards"></div>
    </div>

    <div class="flex flex-col sm:flex-row items-center justify-between gap-4">
      <p class="text-sm text-on-surface-variant">Showing <span id="showingRange">0</span> of <span id="totalCount">0</span></p>
      <div class="flex items-center gap-2" id="paginationButtons">
        <button type="button" class="page-btn" id="prevBtn" disabled>
          <span class="material-symbols-outlined text-lg">chevron_left</span>
        </button>
        <button type="button" class="page-btn" id="nextBtn" disabled>
          <span class="material-symbols-outlined text-lg">chevron_right</span>
        </button>
      </div>
    </div>

  </div>
</main>

<div id="requestPayoutModal" class="hidden fixed inset-0 z-[200] flex items-center justify-center p-4 bg-black/50" role="dialog" aria-modal="true" aria-labelledby="requestPayoutTitle">
  <div class="bg-white rounded-2xl max-w-md w-full p-6 shadow-xl">
    <div class="flex items-start justify-between gap-3 mb-1">
      <h5 id="requestPayoutTitle" class="text-lg font-bold text-on-surface">Request payout</h5>
      <button type="button" id="closeRequestPayoutBtn" class="rounded-lg p-1 text-on-surface-variant hover:bg-surface-container-low transition-colors" aria-label="Close">
        <span class="material-symbols-outlined text-[22px]">close</span>
      </button>
    </div>
    <p class="text-sm text-on-surface-variant mb-6">Enter how much you want to withdraw from your available balance.</p>

    <form id="requestPayoutForm" class="space-y-5" novalidate>
      <div class="rounded-xl border border-outline-variant/20 bg-surface-container-low px-4 py-3 flex items-center justify-between gap-3">
        <span class="text-xs font-semibold uppercase tracking-wider text-on-surface-variant">Available</span>
        <span class="text-base font-bold text-on-surface">{{ $currency }}{{ number_format($availableBalance, 2) }}</span>
      </div>

      <div>
        <label for="requestPayoutAmount" class="block text-sm font-semibold text-on-surface mb-2">Amount</label>
        <div class="relative">
          <span class="absolute left-4 top-1/2 -translate-y-1/2 text-sm font-semibold text-on-surface-variant">{{ $currency }}</span>
          <input
            type="number"
            id="requestPayoutAmount"
            name="amount"
            inputmode="decimal"
            min="0.01"
            step="0.01"
            max="{{ number_format($availableBalance, 2, '.', '') }}"
            placeholder="0.00"
            class="w-full text-sm border border-outline-variant/30 rounded-xl pl-9 pr-4 py-3 bg-white text-on-surface focus:outline-none focus:ring-2 focus:ring-primary/30"
          >
        </div>
        <div class="mt-2 flex items-center justify-between gap-3">
          <p class="text-xs text-on-surface-variant">Max {{ $currency }}{{ number_format($availableBalance, 2) }}</p>
          <button type="button" id="requestPayoutMaxBtn" class="text-xs font-semibold text-primary hover:opacity-80 transition-opacity">
            Withdraw all
          </button>
        </div>
        <p id="requestPayoutError" class="hidden text-error text-sm mt-2"></p>
      </div>

      <div class="flex justify-end gap-3 pt-1">
        <button type="button" id="cancelRequestPayoutBtn" class="rounded-xl px-5 py-2.5 text-sm font-semibold text-on-surface hover:bg-surface-container-low">
          Cancel
        </button>
        <button type="submit" id="submitRequestPayoutBtn" class="rounded-xl px-5 py-2.5 text-sm font-semibold bg-on-surface text-white hover:opacity-90 disabled:opacity-60">
          Request payout
        </button>
      </div>
    </form>
  </div>
</div>

<div id="pendingScheduleModal" class="hidden fixed inset-0 z-[200] flex items-center justify-center p-4 bg-black/50" role="dialog" aria-modal="true" aria-labelledby="pendingScheduleTitle">
  <div class="bg-white rounded-2xl max-w-lg w-full max-h-[85vh] flex flex-col shadow-xl">
    <div class="flex items-start justify-between gap-3 p-6 pb-4 border-b border-outline-variant/15 shrink-0">
      <div>
        <h5 id="pendingScheduleTitle" class="text-lg font-bold text-on-surface">Pending earnings</h5>
        <p class="text-sm text-on-surface-variant mt-1">When each booking becomes available to withdraw.</p>
      </div>
      <button type="button" id="closePendingScheduleBtn" class="rounded-lg p-1 text-on-surface-variant hover:bg-surface-container-low transition-colors" aria-label="Close">
        <span class="material-symbols-outlined text-[22px]">close</span>
      </button>
    </div>
    <div class="overflow-y-auto flex-1 px-6 py-4">
      <div class="rounded-xl border border-outline-variant/20 bg-surface-container-low px-4 py-3 flex items-center justify-between gap-3 mb-4">
        <span class="text-xs font-semibold uppercase tracking-wider text-on-surface-variant">Total pending</span>
        <span class="text-base font-bold text-on-surface">{{ $currency }}{{ number_format($stats['pending_total'], 2) }}</span>
      </div>
      @if ($hasPendingSchedule)
        <div class="rounded-xl border border-outline-variant/20 overflow-hidden">
          @foreach ($pendingSchedule as $item)
            <div class="pending-schedule-row px-4 py-3.5 bg-white">
              <div class="flex items-start justify-between gap-3">
                <div class="min-w-0">
                  <p class="text-sm font-semibold text-on-surface truncate">{{ $item['client'] }}</p>
                  <p class="text-xs text-on-surface-variant truncate">{{ $item['service'] }}</p>
                  <p class="text-[11px] text-outline mt-0.5">{{ $item['reference'] }}</p>
                </div>
                <p class="text-sm font-bold text-on-surface shrink-0">{{ $currency }}{{ number_format($item['amount'], 2) }}</p>
              </div>
              <div class="mt-2 flex items-center justify-between gap-3 text-xs">
                <span class="text-on-surface-variant">{{ $item['reason'] }}</span>
                <span class="font-semibold text-amber-700 shrink-0">{{ $item['available_label'] }}</span>
              </div>
            </div>
          @endforeach
        </div>
      @else
        <p class="text-sm text-on-surface-variant text-center py-8">No pending earnings right now.</p>
      @endif
    </div>
    <div class="p-6 pt-4 border-t border-outline-variant/15 shrink-0">
      <button type="button" id="dismissPendingScheduleBtn" class="w-full rounded-xl px-5 py-2.5 text-sm font-semibold bg-on-surface text-white hover:opacity-90">
        Close
      </button>
    </div>
  </div>
</div>

<script>
  window.inkjinArtistPayments = @json($payments);
  window.inkjinCurrencySymbol = @json($currency);
  window.inkjinAvailableBalance = @json(round($availableBalance, 2));

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
        errorEl.classList.add('hidden');
        return;
      }
      errorEl.textContent = message;
      errorEl.classList.remove('hidden');
    }

    function openModal() {
      if (available < 0.01) {
        if (typeof showSaveToast === 'function') {
          showSaveToast('No available balance to withdraw yet.');
        }
        return;
      }
      setError('');
      modal.classList.remove('hidden');
      if (amountInput) {
        amountInput.value = '';
        setTimeout(function () { amountInput.focus(); }, 50);
      }
    }

    function closeModal() {
      if (saving) return;
      modal.classList.add('hidden');
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

      const raw = amountInput ? amountInput.value : '';
      const amount = Number(raw);
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
            const msg = data.message
              || (data.errors && data.errors.amount && data.errors.amount[0])
              || 'Could not process payout request.';
            throw new Error(msg);
          }
          return data;
        })
        .then(function (data) {
          if (typeof showSaveToast === 'function') {
            showSaveToast(data.message || 'Payout sent.');
          }
          window.location.reload();
        })
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
      modal.classList.remove('hidden');
    }

    function closeModal() {
      modal.classList.add('hidden');
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
<script src="{{ asset('js/artist-payments.js') }}?v=3"></script>
@endsection
