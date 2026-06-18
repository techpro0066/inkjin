@extends('layouts.artist_dashboard_layout')

@section('title', 'Payments')

@section('styles')
<style>
  .status-completed { background: #f0fdf4; color: #166534; }
  .status-completed .status-dot { background: #22c55e; }
  .status-pending { background: #fffbeb; color: #b45309; }
  .status-pending .status-dot { background: #f59e0b; }
  .status-cancelled { background: #fef2f2; color: #b91c1c; }
  .status-cancelled .status-dot { background: #ef4444; }
  .status-refunded { background: #eff6ff; color: #1d4ed8; }
  .status-refunded .status-dot { background: #3b82f6; }
  .filter-pill { transition: all 0.2s; }
  .filter-pill.active { background: #310f7a; color: #ffffff; }
  .payments-table th { text-align: left; font-size: 12px; font-weight: 600; color: #494552; text-transform: uppercase; letter-spacing: 0.05em; padding: 12px 16px; border-bottom: 1px solid rgba(202,196,211,0.3); }
  .payments-table td { padding: 14px 16px; font-size: 14px; border-bottom: 1px solid rgba(202,196,211,0.15); }
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
</style>
@endsection

@section('content')
@php
  $currency = $stats['currency_symbol'] ?? '€';
@endphp
<main class="main-content flex-1 min-h-screen">
  <div class="p-6 md:p-10 lg:p-12 max-w-6xl">

    <div class="mb-8">
      <h2 class="text-3xl font-extrabold text-on-surface tracking-tight">Payments</h2>
      <p class="text-on-surface-variant mt-1">Track your earnings, payouts, and payment history.</p>
    </div>

    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4 mb-8">
      <div class="bg-white rounded-2xl border border-outline-variant/10 p-5">
        <div class="flex items-start justify-between mb-3">
          <div>
            <p class="text-xs font-semibold text-on-surface-variant uppercase tracking-wider mb-1">Available Balance</p>
            <p class="text-2xl font-extrabold text-on-surface">{{ $currency }}{{ number_format($stats['available_balance'], 2) }}</p>
          </div>
          <div class="w-10 h-10 rounded-full bg-green-100 flex items-center justify-center flex-shrink-0">
            <span class="material-symbols-outlined text-green-600" style="font-size:20px;">account_balance_wallet</span>
          </div>
        </div>
        <p class="text-xs text-on-surface-variant mb-3">
          @if ($stats['payout_account_connected'] ?? false)
            Payouts are enabled on your account
          @else
            Connect your account to receive payouts
          @endif
        </p>
        @if ($stats['payout_account_connected'] ?? false)
          <p class="text-sm text-on-surface flex items-start gap-2">
            <span class="material-symbols-outlined text-green-600 flex-shrink-0" style="font-size:18px;">check_circle</span>
            <span>Payments will be automatically sent to your account.</span>
          </p>
        @else
          <a href="{{ route('settings.payment') }}" class="inline-flex bg-primary text-white text-sm font-semibold px-4 py-2 rounded-xl hover:bg-primary-container transition-colors">Connect account</a>
        @endif
      </div>

      <div class="bg-white rounded-2xl border border-outline-variant/10 p-5">
        <div class="flex items-start justify-between mb-3">
          <div>
            <p class="text-xs font-semibold text-on-surface-variant uppercase tracking-wider mb-1">Pending</p>
            <p class="text-2xl font-extrabold text-on-surface">{{ $currency }}{{ number_format($stats['pending_total'], 2) }}</p>
          </div>
          <div class="w-10 h-10 rounded-full bg-amber-100 flex items-center justify-center flex-shrink-0">
            <span class="material-symbols-outlined text-amber-600" style="font-size:20px;">schedule</span>
          </div>
        </div>
        <p class="text-xs text-on-surface-variant">{{ $stats['pending_count'] }} {{ Str::plural('payment', $stats['pending_count']) }} processing</p>
      </div>

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
              <th>Amount</th>
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

<script>
  window.inkjinArtistPayments = @json($payments);
  window.inkjinCurrencySymbol = @json($currency);
</script>
<script src="{{ asset('js/artist-payments.js') }}?v=1"></script>
@endsection
