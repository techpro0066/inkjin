@extends('layouts.artist_dashboard_layout')

@section('styles')

<script>
  tailwind.config = {
    darkMode: "class",
    theme: {
      extend: {
        colors: {
          "surface-container-high": "#ece6ef",
          "surface-container-lowest": "#ffffff",
          "surface-container": "#f2ecf5",
          "background": "#fdf7ff",
          "primary": "#310f7a",
          "surface-dim": "#ded8e1",
          "on-surface-variant": "#494552",
          "secondary-fixed": "#e8ddff",
          "on-secondary-fixed-variant": "#4a4168",
          "inverse-surface": "#322f36",
          "error-container": "#ffdad6",
          "inverse-on-surface": "#f5eff8",
          "tertiary": "#452200",
          "surface-container-low": "#f8f1fb",
          "surface": "#fdf7ff",
          "secondary-fixed-dim": "#ccc0ee",
          "on-tertiary-fixed": "#2e1500",
          "on-error": "#ffffff",
          "on-primary-container": "#b69fff",
          "secondary": "#625881",
          "inverse-primary": "#cebdff",
          "primary-fixed": "#e8ddff",
          "outline": "#7a7583",
          "tertiary-fixed": "#ffdcc2",
          "tertiary-container": "#653500",
          "on-secondary": "#ffffff",
          "on-primary": "#ffffff",
          "on-tertiary-fixed-variant": "#6c3a04",
          "error": "#ba1a1a",
          "tertiary-fixed-dim": "#ffb77b",
          "surface-bright": "#fdf7ff",
          "surface-tint": "#664db1",
          "on-error-container": "#93000a",
          "on-primary-fixed": "#21005e",
          "primary-fixed-dim": "#cebdff",
          "on-tertiary-container": "#e49e62",
          "on-primary-fixed-variant": "#4e3397",
          "primary-container": "#482d91",
          "on-surface": "#1c1b21",
          "outline-variant": "#cac4d3",
          "on-tertiary": "#ffffff",
          "surface-container-highest": "#e6e0ea",
          "on-background": "#1c1b21",
          "secondary-container": "#ddd0ff",
          "on-secondary-fixed": "#1e1539",
          "surface-variant": "#e6e0ea",
          "on-secondary-container": "#615780"
        },
        fontFamily: {
          "headline": ["Plus Jakarta Sans"],
          "body": ["Plus Jakarta Sans"],
          "label": ["Plus Jakarta Sans"]
        },
        borderRadius: {"DEFAULT": "0.25rem", "lg": "0.5rem", "xl": "0.75rem", "full": "9999px"},
      },
    },
  }
</script>
<style>
  body { font-family: 'Plus Jakarta Sans', sans-serif; }
  .material-symbols-outlined { font-variation-settings: 'FILL' 0, 'wght' 400, 'GRAD' 0, 'opsz' 24; }
  /* Sidebar */
  
  .mobile-header { display: none; }
  @media (max-width: 1023px) { .mobile-header { display: flex; } }
  @media (min-width: 1024px) {  }

  .sidebar { width: 260px; min-height: 100vh; }
  @media (min-width: 1024px) { .sidebar { display: flex !important; } .main-content { margin-left: 260px; } }
  @media (max-width: 1023px) { .main-content {  padding-top: 70px; } }
  .nav-item { display: flex; align-items: center; gap: 12px; padding: 12px 16px; border-radius: 12px; font-size: 14px; font-weight: 500; color: rgba(255,255,255,0.85); transition: all 0.2s; cursor: pointer; text-decoration: none; }
  .nav-item:hover { background: rgba(255,255,255,0.1); }
  .nav-item.active { background: #ffffff; color: #310f7a; font-weight: 600; }
  .nav-item .material-symbols-outlined { font-size: 20px; }
  /* Progress bar */
  .progress-fill { transition: width 0.4s ease; }
  /* Mobile sidebar — handled by Tailwind responsive classes (hidden lg:flex) */
    .sidebar.open { display: flex !important; }
    
    .main-content {  padding-top: 60px !important; }
    .sidebar-backdrop {
      display: none;
      position: fixed;
      inset: 0;
      background: rgba(0,0,0,0.5);
      z-index: 99;
    }
    .sidebar-backdrop.open { display: block; }

  /* Sub-menu */
  .sub-menu { max-height: 0; overflow: hidden; transition: max-height 0.3s ease; display: flex; flex-direction: column; gap: 2px; margin-top: 4px; margin-left: 32px; }
  .sub-menu.open { max-height: 500px; }
  .sub-menu-item { display: flex; align-items: center; gap: 10px; padding: 8px 16px 8px 48px; border-radius: 10px; font-size: 13px; font-weight: 500; color: rgba(255,255,255,0.7); transition: all 0.2s; cursor: pointer; text-decoration: none; }
  .sub-menu-item:hover { background: rgba(255,255,255,0.08); color: rgba(255,255,255,0.95); }
  .sub-menu-item.active { background: rgba(255,255,255,0.16); color: #ffffff; }
  .sub-menu-item .material-symbols-outlined { font-size: 18px; }

  
  /* Content toggle arrow */
  .content-arrow { transition: transform 0.3s ease; font-size: 18px !important; }
  .content-arrow.rotated { transform: rotate(180deg); }

  
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

    <!-- Welcome Header -->
    <div class="mb-8">
      <div class="flex flex-col sm:flex-row sm:items-end sm:justify-between gap-2 mb-2">
        <h2 class="text-3xl font-extrabold text-on-surface tracking-tight">Welcome back, Artist Name</h2>
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

      <!-- Unread Messages -->
      <div class="stat-card bg-white rounded-2xl p-5 shadow-sm border border-outline-variant/20">
        <div class="flex items-start justify-between mb-4">
          <div class="w-11 h-11 rounded-xl bg-primary/10 flex items-center justify-center">
            <span class="material-symbols-outlined text-primary">mail</span>
          </div>
        </div>
        <p class="text-3xl font-extrabold text-on-surface">8</p>
        <p class="text-sm font-semibold text-on-surface mt-1">Unread Messages</p>
        <p class="text-xs text-on-surface-variant mt-1">3 from new clients</p>
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