@extends('layouts.artist_dashboard_layout')

@section('title', 'Dashboard')

@section('styles')
<style>
  /* Stat card hover */
  .stat-card { transition: transform 0.2s ease, box-shadow 0.2s ease; }
    .stat-card:hover { transform: translateY(-2px); box-shadow: 0 8px 25px rgba(49,15,122,0.1); }

    
/* Quick action hover */
    .quick-action { transition: transform 0.2s ease, box-shadow 0.2s ease; }
    .quick-action:hover { transform: translateY(-2px); box-shadow: 0 8px 25px rgba(49,15,122,0.1); }

    
/* Table row hover */
    .booking-row { transition: background 0.15s ease; }
    .booking-row:hover { background: #f8f1fb; }
  
    /* Mobile overflow fixes */
    @media (max-width: 1023px) {
      .main-content { overflow-x: hidden; padding: 16px; padding-top: 70px; }
      body { overflow-x: hidden; }
    }
    .filter-pills { flex-wrap: wrap; }
    .request-card { overflow: hidden; word-break: break-word; }

    .status-new { background: #f3e8ff; color: #6b21a8; }
    .status-new .status-dot { background: #9333ea; }
    .status-confirmed { background: #f0fdf4; color: #15803d; }
    .status-confirmed .status-dot { background: #22c55e; }
    .status-declined { background: #fef2f2; color: #b91c1c; }
    .status-declined .status-dot { background: #ef4444; }
</style>
@endsection

@section('content')
<main class="main-content flex-1 min-h-screen">
  <div class="p-6 md:p-10 lg:p-12 max-w-6xl">

    @php
      $ud = Auth::user()->userDetail;
      $stats = $dashboardStats ?? [];
      $currency = $stats['currency_symbol'] ?? '€';
      $todayTotal = (int) ($stats['today_bookings_total'] ?? 0);
      $todayConfirmed = (int) ($stats['today_bookings_confirmed'] ?? 0);
      $todayPending = (int) ($stats['today_bookings_pending'] ?? 0);
      $monthRevenue = (float) ($stats['month_revenue'] ?? 0);
      $revenueChange = $stats['revenue_change_percent'] ?? null;
      $waitlistCount = (int) ($stats['waitlist_count'] ?? 0);
      $showWaitlistNotifyButton = (bool) ($stats['show_waitlist_notify_button'] ?? false);
      $booksClosed = $ud && ($ud->availability_status ?? '') === 'closed';
      $artistUsername = $ud->user_name ?? '';
      $bookingPageUrl = $artistUsername !== ''
          ? route('public.artist', ['username' => $artistUsername])
          : '';
      $quickActionCount = 3 + (($showWaitlistNotifyButton || $booksClosed) ? 1 : 0);

      $todaySubtitle = $todayTotal === 0
          ? 'No bookings scheduled today'
          : collect([
              $todayConfirmed > 0 ? $todayConfirmed.' confirmed' : null,
              $todayPending > 0 ? $todayPending.' pending' : null,
          ])->filter()->implode(', ');

      if ($revenueChange === null) {
          $revenueSubtitle = $monthRevenue > 0 ? 'First revenue this month' : 'No paid bookings this month';
          $revenueSubtitleClass = 'text-on-surface-variant';
      } elseif ($revenueChange > 0) {
          $revenueSubtitle = '+'.$revenueChange.'% from last month';
          $revenueSubtitleClass = 'text-green-600';
      } elseif ($revenueChange < 0) {
          $revenueSubtitle = $revenueChange.'% from last month';
          $revenueSubtitleClass = 'text-red-600';
      } else {
          $revenueSubtitle = 'Same as last month';
          $revenueSubtitleClass = 'text-on-surface-variant';
      }
    @endphp

    <div class="dashboard-notices mb-8">
      @if(!empty($needsWeeklyAvailabilitySetup))
        @include('artist.dashboard.partials.notice', [
          'id' => 'weeklyAvailabilityNotice',
          'theme' => 'amber',
          'icon' => 'event_available',
          'title' => 'Weekly availability is not set',
          'description' => 'Set the days and times you\'re available. Until you do, clients can\'t book you.',
          'buttonText' => 'Set availability',
          'buttonIcon' => 'calendar_clock',
          'buttonUrl' => route('availability.index'),
        ])
      @endif

      @if(!empty($showCustomizePageNotice))
        @include('artist.dashboard.partials.notice', [
          'id' => 'customizePageNotice',
          'theme' => 'blue',
          'icon' => 'palette',
          'title' => 'Customize your booking page',
          'description' => 'Manage your colors, bio, flash designs, portfolio, and intake forms.',
          'buttonText' => 'Customize page',
          'buttonIcon' => 'tune',
          'buttonUrl' => route('personal-page.index', ['from' => 'dashboard_notice']),
        ])
      @endif

      @if($ud && $ud->availability_status == 'closed')
        @include('artist.dashboard.partials.notice', [
          'id' => 'booksClosedBanner',
          'theme' => 'red',
          'icon' => 'event_busy',
          'title' => 'Your books are currently closed',
          'description' => 'Clients can\'t book your designs or send custom requests. Open your books when you\'re ready to start accepting appointments.',
          'buttonText' => 'Open your books',
          'buttonIcon' => 'event_available',
          'buttonUrl' => route('availability.index') . '?tab=status',
        ])
      @endif
    </div>

    <!-- Welcome Header -->
    <div class="mb-8">
      <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div class="min-w-0">
          <h2 class="text-3xl font-extrabold text-on-surface tracking-tight">Welcome back, {{ Auth::user()->first_name }} {{ Auth::user()->last_name }}</h2>
          <p class="text-on-surface-variant mt-1 max-w-lg">Here's what's happening with your bookings today.</p>
        </div>
        <a href="{{ route('artist.payment-link') }}"
          @class([
            'inline-flex items-center justify-center gap-2 px-5 py-2.5 rounded-xl font-semibold text-sm transition-colors shadow-sm shrink-0 w-full sm:w-auto',
            !empty($canCreatePaymentLinks)
              ? 'bg-primary text-white hover:bg-primary-container'
              : 'bg-surface-container-high text-on-surface-variant pointer-events-none opacity-60',
          ])
          @if(empty($canCreatePaymentLinks)) aria-disabled="true" tabindex="-1" @endif>
          <span class="material-symbols-outlined text-lg">add</span> New payment link
        </a>
      </div>
    </div>

    <!-- Stats Cards -->
    <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-5 mb-10">
      <!-- Today's Bookings -->
      <a href="{{ route('artist.bookings.index') }}" class="stat-card bg-white rounded-2xl p-5 shadow-sm border border-outline-variant/20 block">
        <div class="flex items-start justify-between mb-4">
          <div class="w-11 h-11 rounded-xl bg-primary/10 flex items-center justify-center">
            <span class="material-symbols-outlined text-primary">calendar_today</span>
          </div>
        </div>
        <p class="text-3xl font-extrabold text-on-surface">{{ $todayTotal }}</p>
        <p class="text-sm font-semibold text-on-surface mt-1">Today's Bookings</p>
        <p class="text-xs text-on-surface-variant mt-1">{{ $todaySubtitle }}</p>
      </a>

      <!-- Pending Custom Requests -->
      <a href="{{ route('artist.custom-requests.index') }}" class="stat-card bg-white rounded-2xl p-5 shadow-sm border border-outline-variant/20 block">
        <div class="flex items-start justify-between mb-4">
          <div class="w-11 h-11 rounded-xl bg-primary/10 flex items-center justify-center">
            <span class="material-symbols-outlined text-primary">brush</span>
          </div>
        </div>
        <p class="text-3xl font-extrabold text-on-surface">{{ $pendingCustomRequestsCount ?? 0 }}</p>
        <p class="text-sm font-semibold text-on-surface mt-1">Custom Requests</p>
        <p class="text-xs text-on-surface-variant mt-1">Pending review</p>
      </a>

      <!-- This Month's Revenue -->
      <a href="{{ route('artist.payments.index') }}" class="stat-card bg-white rounded-2xl p-5 shadow-sm border border-outline-variant/20 block">
        <div class="flex items-start justify-between mb-4">
          <div class="w-11 h-11 rounded-xl bg-primary/10 flex items-center justify-center">
            <span class="material-symbols-outlined text-primary">payments</span>
          </div>
        </div>
        <p class="text-3xl font-extrabold text-on-surface">{{ $currency }}{{ number_format($monthRevenue, 0) }}</p>
        <p class="text-sm font-semibold text-on-surface mt-1">This Month's Revenue</p>
        <p class="text-xs {{ $revenueSubtitleClass }} mt-1 font-medium">{{ $revenueSubtitle }}</p>
      </a>

      <!-- Waitlist -->
      <div class="stat-card bg-white rounded-2xl p-5 shadow-sm border border-outline-variant/20 flex flex-col">
        <div class="flex items-start justify-between mb-4">
          <div class="w-11 h-11 rounded-xl bg-primary/10 flex items-center justify-center">
            <span class="material-symbols-outlined text-primary">notifications_active</span>
          </div>
        </div>
        <p id="dashboardWaitlistCount" class="text-3xl font-extrabold text-on-surface">{{ $waitlistCount }}</p>
        <p class="text-sm font-semibold text-on-surface mt-1">Waitlist</p>
        <p id="dashboardWaitlistSubtitle" class="text-xs text-on-surface-variant mt-1">{{ $waitlistCount === 1 ? 'Client waiting' : 'Clients waiting' }} to be notified</p>
        @if($showWaitlistNotifyButton)
        <div id="dashboardWaitlistNotifyWrap" class="mt-4 pt-4 border-t border-outline-variant/15">
          <button type="button" id="dashboardWaitlistNotifyBtn" data-pending-count="{{ $waitlistCount }}" class="w-full inline-flex items-center justify-center gap-2 bg-primary text-white px-4 py-2.5 rounded-xl font-semibold text-xs hover:bg-primary-container transition-colors disabled:opacity-60 disabled:cursor-not-allowed">
            <span class="material-symbols-outlined text-base">mail</span>
            Send email
          </button>
        </div>
        @endif
        <p id="dashboardWaitlistNotifyMessage" class="hidden mt-3 text-xs rounded-xl px-3 py-2"></p>
      </div>
    </div>

    <!-- Recent Custom Requests -->
    <div class="bg-white rounded-2xl shadow-sm border border-outline-variant/20 mb-10 overflow-hidden">
      <div class="flex items-center justify-between px-6 py-5 border-b border-outline-variant/15">
        <h3 class="text-lg font-bold text-on-surface">Recent Custom Requests</h3>
        <a href="{{ route('artist.custom-requests.index') }}" class="text-sm font-semibold text-primary hover:underline flex items-center gap-1">
          View All <span class="material-symbols-outlined text-base">arrow_forward</span>
        </a>
      </div>

      @forelse($recentCustomRequests ?? [] as $customRequest)
        <div class="booking-row px-6 py-4 border-b border-outline-variant/10 last:border-b-0 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
          <div class="min-w-0">
            <div class="flex items-center gap-2 flex-wrap mb-1">
              <p class="font-semibold text-on-surface">{{ $customRequest->clientDisplayName() }}</p>
              <span class="inline-flex items-center gap-1.5 {{ $customRequest->statusBadgeClass() }} text-xs font-semibold px-2.5 py-0.5 rounded-full">
                <span class="w-1.5 h-1.5 rounded-full status-dot"></span> {{ $customRequest->filterStatusLabel() }}
              </span>
            </div>
            <p class="text-sm text-on-surface-variant">{{ $customRequest->referenceLabel() }} · {{ $customRequest->created_at?->format('M j, Y') }}</p>
          </div>
          <a href="{{ route('artist.custom-requests.index') }}?open={{ $customRequest->id }}" class="text-sm font-semibold text-primary hover:text-primary-container whitespace-nowrap">View</a>
        </div>
      @empty
        <div class="px-6 py-10 text-center">
          <span class="material-symbols-outlined text-3xl text-outline mb-2 block">brush</span>
          <p class="text-sm text-on-surface-variant">No custom requests yet.</p>
        </div>
      @endforelse
    </div>

    <!-- Recent Bookings -->
    <div class="bg-white rounded-2xl shadow-sm border border-outline-variant/20 mb-10 overflow-hidden">
      <div class="flex items-center justify-between px-6 py-5 border-b border-outline-variant/15">
        <h3 class="text-lg font-bold text-on-surface">Recent Bookings</h3>
        <a href="{{ route('artist.bookings.index') }}" class="text-sm font-semibold text-primary hover:underline flex items-center gap-1">
          View All <span class="material-symbols-outlined text-base">arrow_forward</span>
        </a>
      </div>

      @if(count($recentBookings ?? []) > 0)
      <!-- Desktop Table -->
      <div class="hidden sm:block overflow-x-auto">
        <table class="w-full text-sm">
          <thead>
            <tr class="bg-surface-container-low/50 text-on-surface-variant text-xs uppercase tracking-wider">
              <th class="text-left px-6 py-3 font-semibold">Client</th>
              <th class="text-left px-6 py-3 font-semibold">Service</th>
              <th class="text-left px-6 py-3 font-semibold">Date</th>
              <th class="text-left px-6 py-3 font-semibold">Time</th>
              <th class="text-left px-6 py-3 font-semibold">Status</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-outline-variant/10">
            @foreach($recentBookings as $booking)
            <tr class="booking-row">
              <td class="px-6 py-4 font-semibold text-on-surface">{{ $booking['client_name'] }}</td>
              <td class="px-6 py-4 text-on-surface-variant">{{ $booking['service'] }}</td>
              <td class="px-6 py-4 text-on-surface-variant">{{ $booking['date'] }}</td>
              <td class="px-6 py-4 text-on-surface-variant">{{ $booking['time'] }}</td>
              <td class="px-6 py-4">
                <span class="inline-flex items-center gap-1.5 {{ $booking['badge_class'] }} text-xs font-semibold px-3 py-1 rounded-full">
                  <span class="w-1.5 h-1.5 {{ $booking['dot_class'] }} rounded-full"></span> {{ $booking['status'] }}
                </span>
              </td>
            </tr>
            @endforeach
          </tbody>
        </table>
      </div>

      <!-- Mobile Cards -->
      <div class="sm:hidden divide-y divide-outline-variant/10">
        @foreach($recentBookings as $booking)
        <div class="p-4 booking-row">
          <div class="flex items-center justify-between mb-2">
            <p class="font-semibold text-on-surface">{{ $booking['client_name'] }}</p>
            <span class="inline-flex items-center gap-1.5 {{ $booking['badge_class'] }} text-xs font-semibold px-2.5 py-0.5 rounded-full">
              <span class="w-1.5 h-1.5 {{ $booking['dot_class'] }} rounded-full"></span> {{ $booking['status'] }}
            </span>
          </div>
          <p class="text-sm text-on-surface-variant">{{ $booking['service'] }}</p>
          <p class="text-xs text-outline mt-1">{{ $booking['date'] }} · {{ $booking['time'] }}</p>
        </div>
        @endforeach
      </div>
      @else
        <div class="px-6 py-10 text-center">
          <span class="material-symbols-outlined text-3xl text-outline mb-2 block">calendar_month</span>
          <p class="text-sm text-on-surface-variant">No bookings yet.</p>
        </div>
      @endif
    </div>

    <!-- Quick Actions -->
    <div class="mb-10">
      <h3 class="text-lg font-bold text-on-surface mb-5">Quick Actions</h3>
      <div class="grid grid-cols-1 sm:grid-cols-2 {{ $quickActionCount > 3 ? 'xl:grid-cols-4' : 'lg:grid-cols-3' }} gap-5">
        <a href="{{ route('availability.index') }}?tab=blocked" class="quick-action bg-white rounded-2xl p-5 shadow-sm border border-outline-variant/20 text-left group block">
          <div class="w-11 h-11 rounded-xl bg-primary/10 flex items-center justify-center mb-4 group-hover:bg-primary/15 transition-colors">
            <span class="material-symbols-outlined text-primary">event_busy</span>
          </div>
          <p class="font-bold text-on-surface text-sm">Block Time Off</p>
          <p class="text-xs text-on-surface-variant mt-1">Mark dates unavailable</p>
        </a>

        <button type="button" id="dashboardCopyBookingLinkBtn" data-booking-url="{{ $bookingPageUrl }}" class="quick-action bg-white rounded-2xl p-5 shadow-sm border border-outline-variant/20 text-left group w-full {{ $bookingPageUrl === '' ? 'opacity-60 cursor-not-allowed' : '' }}" @disabled($bookingPageUrl === '')>
          <div class="w-11 h-11 rounded-xl bg-primary/10 flex items-center justify-center mb-4 group-hover:bg-primary/15 transition-colors">
            <span class="material-symbols-outlined text-primary">share</span>
          </div>
          <p class="font-bold text-on-surface text-sm">Share Booking Link</p>
          <p class="text-xs text-on-surface-variant mt-1">Copy your personal booking URL</p>
        </button>

        <a href="{{ route('portfolio.index') }}" class="quick-action bg-white rounded-2xl p-5 shadow-sm border border-outline-variant/20 text-left group block">
          <div class="w-11 h-11 rounded-xl bg-primary/10 flex items-center justify-center mb-4 group-hover:bg-primary/15 transition-colors">
            <span class="material-symbols-outlined text-primary">add_photo_alternate</span>
          </div>
          <p class="font-bold text-on-surface text-sm">Update Portfolio</p>
          <p class="text-xs text-on-surface-variant mt-1">Add your latest work</p>
        </a>

        @if($showWaitlistNotifyButton)
        <button type="button" id="dashboardQuickWaitlistNotifyBtn" data-pending-count="{{ $waitlistCount }}" class="quick-action bg-white rounded-2xl p-5 shadow-sm border border-outline-variant/20 text-left group w-full">
          <div class="w-11 h-11 rounded-xl bg-primary/10 flex items-center justify-center mb-4 group-hover:bg-primary/15 transition-colors">
            <span class="material-symbols-outlined text-primary">mail</span>
          </div>
          <p class="font-bold text-on-surface text-sm">Notify Waitlist</p>
          <p class="text-xs text-on-surface-variant mt-1">Email {{ $waitlistCount }} {{ $waitlistCount === 1 ? 'subscriber' : 'subscribers' }} your books are open</p>
        </button>
        @elseif($booksClosed)
        <a href="{{ route('availability.index') }}?tab=status" class="quick-action bg-white rounded-2xl p-5 shadow-sm border border-outline-variant/20 text-left group block">
          <div class="w-11 h-11 rounded-xl bg-red-50 flex items-center justify-center mb-4 group-hover:bg-red-100 transition-colors">
            <span class="material-symbols-outlined text-red-600">event_available</span>
          </div>
          <p class="font-bold text-on-surface text-sm">Open Your Books</p>
          <p class="text-xs text-on-surface-variant mt-1">
            @if($waitlistCount > 0)
              {{ $waitlistCount }} {{ $waitlistCount === 1 ? 'client' : 'clients' }} on your waitlist
            @else
              Start accepting bookings again
            @endif
          </p>
        </a>
        @endif
      </div>
    </div>

  </div>
</main>

<div id="dashboardCopyLinkToast" class="fixed bottom-6 right-6 z-[130] translate-x-full opacity-0 pointer-events-none transition-all duration-300 max-w-sm" role="status" aria-live="polite">
  <div class="bg-on-surface text-white text-sm font-semibold px-4 py-3 rounded-xl shadow-lg flex items-center gap-2">
    <span class="material-symbols-outlined text-base text-green-300">check_circle</span>
    <span id="dashboardCopyLinkToastMessage">Booking link copied.</span>
  </div>
</div>

@if($showWaitlistNotifyButton ?? false)
  @include('components.waitlist-notify-modal')
@endif
@endsection

@section('scripts')
@include('partials.reddit-pixel', ['event' => 'Active'])
@if($showWaitlistNotifyButton ?? false)
<script src="{{ asset('js/waitlist-notify.js') }}?v=2"></script>
<script>
  window.InkjinWaitlistNotify?.init({
    triggers: [
      document.getElementById('dashboardWaitlistNotifyBtn'),
      document.getElementById('dashboardQuickWaitlistNotifyBtn'),
    ].filter(Boolean),
    notifyUrl: @json(route('artist.clients.waitlist.notify')),
    messageEl: document.getElementById('dashboardWaitlistNotifyMessage'),
    getPendingCount(trigger) {
      return parseInt(trigger?.dataset.pendingCount || '0', 10) || 0;
    },
    onSuccess(data) {
      const pending = Array.isArray(data.waitlist)
        ? data.waitlist.filter((entry) => entry.status_key === 'pending').length
        : 0;

      const countEl = document.getElementById('dashboardWaitlistCount');
      const subtitleEl = document.getElementById('dashboardWaitlistSubtitle');
      const wrapEl = document.getElementById('dashboardWaitlistNotifyWrap');
      const btn = document.getElementById('dashboardWaitlistNotifyBtn');
      const quickBtn = document.getElementById('dashboardQuickWaitlistNotifyBtn');

      if (countEl) countEl.textContent = String(pending);
      if (subtitleEl) {
        subtitleEl.textContent = pending === 1 ? 'Client waiting to be notified' : 'Clients waiting to be notified';
      }
      if (btn) btn.dataset.pendingCount = String(pending);
      if (quickBtn) quickBtn.dataset.pendingCount = String(pending);

      if (data.show_waitlist_notify_button === false) {
        wrapEl?.classList.add('hidden');
        quickBtn?.classList.add('hidden');
      }
    },
  });
</script>
@endif
<script>

  (function () {
    const copyBtn = document.getElementById('dashboardCopyBookingLinkBtn');
    const toast = document.getElementById('dashboardCopyLinkToast');
    const toastMessage = document.getElementById('dashboardCopyLinkToastMessage');
    let toastTimer = null;

    function showCopyToast(message) {
      if (!toast || !toastMessage) return;
      toastMessage.textContent = message;
      toast.classList.remove('translate-x-full', 'opacity-0', 'pointer-events-none');
      toast.classList.add('translate-x-0', 'opacity-100');
      clearTimeout(toastTimer);
      toastTimer = setTimeout(function () {
        toast.classList.add('translate-x-full', 'opacity-0', 'pointer-events-none');
        toast.classList.remove('translate-x-0', 'opacity-100');
      }, 3000);
    }

    copyBtn?.addEventListener('click', function () {
      const url = copyBtn.dataset.bookingUrl || '';
      if (!url) {
        showCopyToast('Set up your username first to share your booking link.');
        return;
      }

      const done = function () {
        showCopyToast('Booking link copied.');
      };
      const fail = function () {
        showCopyToast('Could not copy link. Please copy it manually.');
      };

      if (navigator.clipboard && navigator.clipboard.writeText) {
        navigator.clipboard.writeText(url).then(done).catch(fail);
      } else {
        fail();
      }
    });
  })();
</script>
@endsection