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
</style>
@endsection

@section('content')
<main class="main-content flex-1 min-h-screen">
  <div class="p-6 md:p-10 lg:p-12 max-w-6xl">

    @php
      $ud = Auth::user()->userDetail;
    @endphp

    @if($ud && $ud->availability_status == 'closed')
    <div id="booksClosedBanner" class="bg-red-50 border border-red-200 rounded-2xl p-4 mb-8 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
      <div class="flex items-start gap-3">
        <span class="material-symbols-outlined text-red-600 mt-0.5">event_busy</span>
        <div>
          <h3 class="text-sm font-bold text-red-900">Your books are currently closed</h3>
          <p class="text-xs text-red-700 mt-1">Clients cannot book your available designs or send custom requests. When you are ready, open your books to start accepting appointments.</p>
        </div>
      </div>
      <a href="{{ route('availability.index') }}" class="whitespace-nowrap px-4 py-2 bg-red-600 text-white rounded-full text-xs font-bold hover:bg-red-700 transition-colors shadow-sm">
        Open Your Books
      </a>
    </div>
    @endif

    @if($ud && ($ud->payment_status ?? '') !== 'approved')
    <div id="paymentNotApprovedBanner" class="bg-amber-50 border border-amber-200 rounded-2xl p-4 mb-8 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
      <div class="flex items-start gap-3">
        <span class="material-symbols-outlined text-amber-600 mt-0.5">payments</span>
        <div>
          <h3 class="text-sm font-bold text-amber-900">Payout setup is not approved yet</h3>
          <p class="text-xs text-amber-800 mt-1">
            @if(($ud->payment_type ?? '') === 'studio_account')
              Your studio still needs to complete or approve payout details before your payment profile is active. You can resend the request from payment settings or follow up with your studio.
            @else
              Complete and approve your payout method so you can receive earnings. Open payment settings to see what is still required.
            @endif
          </p>
        </div>
      </div>
      <a href="{{ route('settings.payment') }}" class="whitespace-nowrap px-4 py-2 bg-amber-600 text-white rounded-full text-xs font-bold hover:bg-amber-700 transition-colors shadow-sm">
        Payment settings
      </a>
    </div>
    @endif

    @if(!empty($needsWeeklyAvailabilitySetup))
    <div class="mb-8 rounded-2xl border border-amber-200/80 bg-gradient-to-br from-amber-50 to-amber-100/40 p-5 sm:p-6 shadow-sm" role="alert">
      <div class="flex flex-col sm:flex-row sm:items-start gap-4">
        <div class="w-11 h-11 rounded-xl bg-amber-100 border border-amber-200/60 flex items-center justify-center flex-shrink-0">
          <span class="material-symbols-outlined text-amber-800 text-2xl">event_available</span>
        </div>
        <div class="min-w-0 flex-1">
          <h3 class="text-sm font-bold text-amber-950">Weekly availability is not set</h3>
          <p class="text-xs text-amber-900/90 mt-1.5 leading-relaxed">Add at least one weekly time range on your availability page. Until you do, clients cannot complete bookings with you.</p>
        </div>
        <a href="{{ route('availability.index') }}" class="self-start sm:self-center shrink-0 inline-flex items-center justify-center gap-1.5 whitespace-nowrap text-xs font-bold text-white bg-amber-700 hover:bg-amber-800 px-4 py-2.5 rounded-xl shadow-sm transition-colors">
          <span class="material-symbols-outlined text-base">calendar_clock</span>
          Set availability
        </a>
      </div>
    </div>
    @endif

    <!-- Welcome Header -->
    <div class="mb-8">
      <div class="flex flex-col sm:flex-row sm:items-end sm:justify-between gap-2 mb-2">
        <h2 class="text-3xl font-extrabold text-on-surface tracking-tight">Welcome back, {{ Auth::user()->first_name }} {{ Auth::user()->last_name }}</h2>
        <p class="text-sm text-outline font-medium" id="currentDate"></p>
      </div>
      <p class="text-on-surface-variant mt-1 max-w-lg">Here's what's happening with your bookings today.</p>
    </div>

    <!-- Stats Cards -->
    <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-5 mb-10">
      <!-- Today's Bookings -->
      <div class="stat-card bg-white rounded-2xl p-5 shadow-sm border border-outline-variant/20">
        <div class="flex items-start justify-between mb-4">
          <div class="w-11 h-11 rounded-xl bg-primary/10 flex items-center justify-center">
            <span class="material-symbols-outlined text-primary">calendar_today</span>
          </div>
        </div>
        <p class="text-3xl font-extrabold text-on-surface">3</p>
        <p class="text-sm font-semibold text-on-surface mt-1">Today's Bookings</p>
        <p class="text-xs text-on-surface-variant mt-1">2 confirmed, 1 pending</p>
      </div>

      <!-- Pending Requests -->
      <div class="stat-card bg-white rounded-2xl p-5 shadow-sm border border-outline-variant/20">
        <div class="flex items-start justify-between mb-4">
          <div class="w-11 h-11 rounded-xl bg-primary/10 flex items-center justify-center">
            <span class="material-symbols-outlined text-primary">pending_actions</span>
          </div>
        </div>
        <p class="text-3xl font-extrabold text-on-surface">5</p>
        <p class="text-sm font-semibold text-on-surface mt-1">Pending Requests</p>
        <p class="text-xs text-on-surface-variant mt-1">3 new since yesterday</p>
      </div>

      <!-- This Month's Revenue -->
      <div class="stat-card bg-white rounded-2xl p-5 shadow-sm border border-outline-variant/20">
        <div class="flex items-start justify-between mb-4">
          <div class="w-11 h-11 rounded-xl bg-primary/10 flex items-center justify-center">
            <span class="material-symbols-outlined text-primary">payments</span>
          </div>
        </div>
        <p class="text-3xl font-extrabold text-on-surface">€2,450</p>
        <p class="text-sm font-semibold text-on-surface mt-1">This Month's Revenue</p>
        <p class="text-xs text-green-600 mt-1 font-medium">+12% from last month</p>
      </div>

      <!-- Waitlist -->
      <div class="stat-card bg-white rounded-2xl p-5 shadow-sm border border-outline-variant/20">
        <div class="flex items-start justify-between mb-4">
          <div class="w-11 h-11 rounded-xl bg-primary/10 flex items-center justify-center">
            <span class="material-symbols-outlined text-primary">notifications_active</span>
          </div>
        </div>
        <p class="text-3xl font-extrabold text-on-surface">42</p>
        <p class="text-sm font-semibold text-on-surface mt-1">Waitlist</p>
        <p class="text-xs text-on-surface-variant mt-1">Clients waiting for books to open</p>
      </div>
    </div>

    <!-- Recent Bookings -->
    <div class="bg-white rounded-2xl shadow-sm border border-outline-variant/20 mb-10 overflow-hidden">
      <div class="flex items-center justify-between px-6 py-5 border-b border-outline-variant/15">
        <h3 class="text-lg font-bold text-on-surface">Recent Bookings</h3>
        <a href="#" class="text-sm font-semibold text-primary hover:underline flex items-center gap-1">
          View All <span class="material-symbols-outlined text-base">arrow_forward</span>
        </a>
      </div>

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
            <tr class="booking-row">
              <td class="px-6 py-4 font-semibold text-on-surface">Sarah M.</td>
              <td class="px-6 py-4 text-on-surface-variant">Full Sleeve Session</td>
              <td class="px-6 py-4 text-on-surface-variant">Mar 30</td>
              <td class="px-6 py-4 text-on-surface-variant">10:00 AM</td>
              <td class="px-6 py-4">
                <span class="inline-flex items-center gap-1.5 bg-green-50 text-green-700 text-xs font-semibold px-3 py-1 rounded-full">
                  <span class="w-1.5 h-1.5 bg-green-500 rounded-full"></span> Confirmed
                </span>
              </td>
            </tr>
            <tr class="booking-row">
              <td class="px-6 py-4 font-semibold text-on-surface">Alex K.</td>
              <td class="px-6 py-4 text-on-surface-variant">Consultation</td>
              <td class="px-6 py-4 text-on-surface-variant">Mar 30</td>
              <td class="px-6 py-4 text-on-surface-variant">2:00 PM</td>
              <td class="px-6 py-4">
                <span class="inline-flex items-center gap-1.5 bg-amber-50 text-amber-700 text-xs font-semibold px-3 py-1 rounded-full">
                  <span class="w-1.5 h-1.5 bg-amber-500 rounded-full"></span> Pending
                </span>
              </td>
            </tr>
            <tr class="booking-row">
              <td class="px-6 py-4 font-semibold text-on-surface">Maria T.</td>
              <td class="px-6 py-4 text-on-surface-variant">Touch-up</td>
              <td class="px-6 py-4 text-on-surface-variant">Mar 31</td>
              <td class="px-6 py-4 text-on-surface-variant">11:00 AM</td>
              <td class="px-6 py-4">
                <span class="inline-flex items-center gap-1.5 bg-green-50 text-green-700 text-xs font-semibold px-3 py-1 rounded-full">
                  <span class="w-1.5 h-1.5 bg-green-500 rounded-full"></span> Confirmed
                </span>
              </td>
            </tr>
            <tr class="booking-row">
              <td class="px-6 py-4 font-semibold text-on-surface">James R.</td>
              <td class="px-6 py-4 text-on-surface-variant">Custom Design</td>
              <td class="px-6 py-4 text-on-surface-variant">Apr 1</td>
              <td class="px-6 py-4 text-on-surface-variant">3:00 PM</td>
              <td class="px-6 py-4">
                <span class="inline-flex items-center gap-1.5 bg-amber-50 text-amber-700 text-xs font-semibold px-3 py-1 rounded-full">
                  <span class="w-1.5 h-1.5 bg-amber-500 rounded-full"></span> Pending
                </span>
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <!-- Mobile Cards -->
      <div class="sm:hidden divide-y divide-outline-variant/10">
        <div class="p-4 booking-row">
          <div class="flex items-center justify-between mb-2">
            <p class="font-semibold text-on-surface">Sarah M.</p>
            <span class="inline-flex items-center gap-1.5 bg-green-50 text-green-700 text-xs font-semibold px-2.5 py-0.5 rounded-full">
              <span class="w-1.5 h-1.5 bg-green-500 rounded-full"></span> Confirmed
            </span>
          </div>
          <p class="text-sm text-on-surface-variant">Full Sleeve Session</p>
          <p class="text-xs text-outline mt-1">Mar 30 · 10:00 AM</p>
        </div>
        <div class="p-4 booking-row">
          <div class="flex items-center justify-between mb-2">
            <p class="font-semibold text-on-surface">Alex K.</p>
            <span class="inline-flex items-center gap-1.5 bg-amber-50 text-amber-700 text-xs font-semibold px-2.5 py-0.5 rounded-full">
              <span class="w-1.5 h-1.5 bg-amber-500 rounded-full"></span> Pending
            </span>
          </div>
          <p class="text-sm text-on-surface-variant">Consultation</p>
          <p class="text-xs text-outline mt-1">Mar 30 · 2:00 PM</p>
        </div>
        <div class="p-4 booking-row">
          <div class="flex items-center justify-between mb-2">
            <p class="font-semibold text-on-surface">Maria T.</p>
            <span class="inline-flex items-center gap-1.5 bg-green-50 text-green-700 text-xs font-semibold px-2.5 py-0.5 rounded-full">
              <span class="w-1.5 h-1.5 bg-green-500 rounded-full"></span> Confirmed
            </span>
          </div>
          <p class="text-sm text-on-surface-variant">Touch-up</p>
          <p class="text-xs text-outline mt-1">Mar 31 · 11:00 AM</p>
        </div>
        <div class="p-4 booking-row">
          <div class="flex items-center justify-between mb-2">
            <p class="font-semibold text-on-surface">James R.</p>
            <span class="inline-flex items-center gap-1.5 bg-amber-50 text-amber-700 text-xs font-semibold px-2.5 py-0.5 rounded-full">
              <span class="w-1.5 h-1.5 bg-amber-500 rounded-full"></span> Pending
            </span>
          </div>
          <p class="text-sm text-on-surface-variant">Custom Design</p>
          <p class="text-xs text-outline mt-1">Apr 1 · 3:00 PM</p>
        </div>
      </div>
    </div>

    <!-- Quick Actions -->
    <div class="mb-10">
      <h3 class="text-lg font-bold text-on-surface mb-5">Quick Actions</h3>
      <div class="grid grid-cols-1 sm:grid-cols-3 gap-5">
        <!-- Block Time Off -->
        <button class="quick-action bg-white rounded-2xl p-5 shadow-sm border border-outline-variant/20 text-left group">
          <div class="w-11 h-11 rounded-xl bg-primary/10 flex items-center justify-center mb-4 group-hover:bg-primary/15 transition-colors">
            <span class="material-symbols-outlined text-primary">event_busy</span>
          </div>
          <p class="font-bold text-on-surface text-sm">Block Time Off</p>
          <p class="text-xs text-on-surface-variant mt-1">Mark dates unavailable</p>
        </button>

        <!-- Share Booking Link -->
        <button class="quick-action bg-white rounded-2xl p-5 shadow-sm border border-outline-variant/20 text-left group">
          <div class="w-11 h-11 rounded-xl bg-primary/10 flex items-center justify-center mb-4 group-hover:bg-primary/15 transition-colors">
            <span class="material-symbols-outlined text-primary">share</span>
          </div>
          <p class="font-bold text-on-surface text-sm">Share Booking Link</p>
          <p class="text-xs text-on-surface-variant mt-1">Copy your personal booking URL</p>
        </button>

        <!-- Update Portfolio -->
        <button class="quick-action bg-white rounded-2xl p-5 shadow-sm border border-outline-variant/20 text-left group">
          <div class="w-11 h-11 rounded-xl bg-primary/10 flex items-center justify-center mb-4 group-hover:bg-primary/15 transition-colors">
            <span class="material-symbols-outlined text-primary">add_photo_alternate</span>
          </div>
          <p class="font-bold text-on-surface text-sm">Update Portfolio</p>
          <p class="text-xs text-on-surface-variant mt-1">Add your latest work</p>
        </button>
      </div>
    </div>

  </div>
</main>
@if(!empty($needsWeeklyAvailabilitySetup))
  @include('components.artist_availability_setup_modal', ['context' => 'dashboard'])
@endif
@endsection

@section('scripts')
<script>
  // Set current date
  const dateEl = document.getElementById('currentDate');
    const now = new Date();
    const options = { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' };
    dateEl.textContent = now.toLocaleDateString('en-US', options);
</script>
@endsection