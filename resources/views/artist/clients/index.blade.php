@extends('layouts.artist_dashboard_layout')

@section('title', 'Clients')

@section('styles')
<style>
  .client-row { transition: background 0.15s ease; }
  .client-row:hover { background: #f8f1fb; }
  .status-active { background: #f0fdf4; color: #15803d; }
  .status-active .status-dot { background: #22c55e; }
  .status-past { background: #f5f5f5; color: #6b7280; }
  .status-past .status-dot { background: #9ca3af; }
  .status-new { background: #eff6ff; color: #1d4ed8; }
  .status-new .status-dot { background: #3b82f6; }
  .stat-card { transition: transform 0.2s ease, box-shadow 0.2s ease; }
  .stat-card:hover { transform: translateY(-2px); box-shadow: 0 8px 25px rgba(49,15,122,0.1); }
  .action-btn { transition: all 0.15s; }
  .action-btn:hover { background: #f8f1fb; }
  .detail-panel { max-height: 0; overflow: hidden; transition: max-height 0.4s ease; }
  .detail-panel.open { max-height: 2000px; }
  .clients-tab-btn { transition: color 0.2s, border-color 0.2s; }
  .status-waiting { background: #fffbeb; color: #b45309; }
  .status-waiting .status-dot { background: #f59e0b; }
  .status-notified { background: #f0fdf4; color: #15803d; }
  .status-notified .status-dot { background: #22c55e; }
  .clients-table-scroll {
    overflow-x: auto;
    max-width: 100%;
    -webkit-overflow-scrolling: touch;
    overscroll-behavior-x: contain;
  }
  .clients-table-scroll table { min-width: 720px; }
  @media (max-width: 1023px) {
    .main-content { padding: 16px; padding-top: 70px; }
  }
</style>
@endsection

@section('content')
<main class="main-content flex-1 min-h-screen min-w-0 w-full">
  <div class="p-6 md:p-10 lg:p-12 max-w-6xl w-full min-w-0 mx-auto">

    <div class="mb-8">
      <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-2">
        <div>
          <h2 class="text-3xl font-extrabold text-on-surface tracking-tight">Clients</h2>
          <p class="text-on-surface-variant mt-1" id="clientsCountLabel">{{ $stats['total'] }} {{ Str::plural('client', $stats['total']) }}</p>
        </div>
      </div>
    </div>

    <div class="grid grid-cols-2 sm:grid-cols-4 gap-5 mb-8">
      <div class="stat-card bg-white rounded-2xl p-5 shadow-sm border border-outline-variant/20">
        <div class="flex items-start justify-between mb-3">
          <div class="w-11 h-11 rounded-xl bg-primary/10 flex items-center justify-center">
            <span class="material-symbols-outlined text-primary">group</span>
          </div>
        </div>
        <p class="text-3xl font-extrabold text-on-surface">{{ $stats['total'] }}</p>
        <p class="text-sm font-semibold text-on-surface mt-1">Total Clients</p>
      </div>
      <div class="stat-card bg-white rounded-2xl p-5 shadow-sm border border-outline-variant/20">
        <div class="flex items-start justify-between mb-3">
          <div class="w-11 h-11 rounded-xl bg-green-500/10 flex items-center justify-center">
            <span class="material-symbols-outlined text-green-600">person_check</span>
          </div>
        </div>
        <p class="text-3xl font-extrabold text-on-surface">{{ $stats['active'] }}</p>
        <p class="text-sm font-semibold text-on-surface mt-1">Active Clients</p>
      </div>
      <div class="stat-card bg-white rounded-2xl p-5 shadow-sm border border-outline-variant/20">
        <div class="flex items-start justify-between mb-3">
          <div class="w-11 h-11 rounded-xl bg-blue-500/10 flex items-center justify-center">
            <span class="material-symbols-outlined text-blue-600">person_add</span>
          </div>
        </div>
        <p class="text-3xl font-extrabold text-on-surface">{{ $stats['new_this_month'] }}</p>
        <p class="text-sm font-semibold text-on-surface mt-1">New This Month</p>
      </div>
      <div class="stat-card bg-white rounded-2xl p-5 shadow-sm border border-outline-variant/20">
        <div class="flex items-start justify-between mb-3">
          <div class="w-11 h-11 rounded-xl bg-purple-500/10 flex items-center justify-center">
            <span class="material-symbols-outlined text-purple-600">repeat</span>
          </div>
        </div>
        <p class="text-3xl font-extrabold text-on-surface">{{ $stats['returning_rate'] }}%</p>
        <p class="text-sm font-semibold text-on-surface mt-1">Returning Rate</p>
      </div>
    </div>

    <div class="flex gap-1 border-b border-outline-variant/20 mb-6 overflow-x-auto">
      <button type="button" id="clientsTabClients" data-clients-tab="clients" class="clients-tab-btn shrink-0 px-5 py-3.5 text-sm font-semibold border-b-2 border-primary text-primary whitespace-nowrap">
        Clients
      </button>
      <button type="button" id="clientsTabWaitlist" data-clients-tab="waitlist" class="clients-tab-btn shrink-0 px-5 py-3.5 text-sm font-semibold border-b-2 border-transparent text-on-surface-variant hover:text-on-surface whitespace-nowrap">
        Waitlist
        @if(($stats['waitlist'] ?? 0) > 0)
          <span class="ml-1.5 inline-flex items-center justify-center min-w-[20px] h-5 px-1.5 rounded-full bg-primary/10 text-primary text-xs font-bold">{{ $stats['waitlist'] }}</span>
        @endif
      </button>
    </div>

    <div id="clientsTabPanel">
    <div class="bg-surface-container-low rounded-2xl p-5 mb-6 border border-outline-variant/20">
      <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <div class="lg:col-span-2">
          <label class="block text-xs font-semibold text-on-surface-variant mb-1.5" for="searchInput">Search</label>
          <div class="relative">
            <span class="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-outline text-lg">search</span>
            <input type="text" id="searchInput" placeholder="Search by name, email, or phone..." class="w-full text-sm border border-outline-variant/30 rounded-xl pl-9 pr-3 py-2 bg-white text-on-surface focus:outline-none focus:ring-2 focus:ring-primary/30">
          </div>
        </div>
        <div>
          <label class="block text-xs font-semibold text-on-surface-variant mb-1.5" for="filterSelect">Filter</label>
          <select id="filterSelect" class="w-full text-sm border border-outline-variant/30 rounded-xl px-3 py-2 bg-white text-on-surface focus:outline-none focus:ring-2 focus:ring-primary/30">
            <option value="all">All Clients</option>
            <option value="Active">Active</option>
            <option value="Past">Past</option>
            <option value="New">New</option>
          </select>
        </div>
        <div>
          <label class="block text-xs font-semibold text-on-surface-variant mb-1.5" for="sortSelect">Sort by</label>
          <select id="sortSelect" class="w-full text-sm border border-outline-variant/30 rounded-xl px-3 py-2 bg-white text-on-surface focus:outline-none focus:ring-2 focus:ring-primary/30">
            <option value="recent">Most Recent</option>
            <option value="name">Name A-Z</option>
            <option value="bookings">Most Bookings</option>
          </select>
        </div>
      </div>
    </div>

    <div class="bg-white rounded-2xl shadow-sm border border-outline-variant/20 mb-6 overflow-hidden min-w-0">
      <div class="hidden md:block clients-table-scroll">
        <table class="w-full text-sm">
          <thead id="clientsThead">
            <tr class="bg-surface-container-low/50 text-on-surface-variant text-xs uppercase tracking-wider">
              <th class="text-left px-6 py-3 font-semibold">Client</th>
              <th class="text-left px-6 py-3 font-semibold">Email</th>
              <th class="text-left px-6 py-3 font-semibold">Phone</th>
              <th class="text-left px-6 py-3 font-semibold">Bookings</th>
              <th class="text-left px-6 py-3 font-semibold">Total Spent</th>
              <th class="text-left px-6 py-3 font-semibold">Status</th>
              <th class="text-left px-6 py-3 font-semibold">Actions</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-outline-variant/10" id="clientsBody"></tbody>
        </table>
      </div>
      <div class="md:hidden divide-y divide-outline-variant/10" id="clientsMobile"></div>
      <div id="clientsEmpty" class="hidden p-12 text-center text-sm text-on-surface-variant">
        No clients match your search yet.
      </div>
    </div>
    </div>

    <div id="waitlistTabPanel" class="hidden">
      @if($showWaitlistNotifyButton ?? false)
      <div id="waitlistNotifyBar" class="mb-4 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
        <p class="text-sm text-on-surface-variant">Your books are open. Notify waitlist subscribers that they can book now.</p>
        <button type="button" id="waitlistNotifyBtn" class="inline-flex items-center justify-center gap-2 shrink-0 bg-primary text-white px-5 py-2.5 rounded-xl font-semibold text-sm hover:bg-primary-container transition-colors disabled:opacity-60 disabled:cursor-not-allowed">
          <span class="material-symbols-outlined text-lg">mail</span>
          Send email
        </button>
      </div>
      <p id="waitlistNotifyMessage" class="hidden mb-4 text-sm rounded-xl px-4 py-3"></p>
      @endif

      <div class="bg-surface-container-low rounded-2xl p-5 mb-6 border border-outline-variant/20">
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
          <div class="sm:col-span-2">
            <label class="block text-xs font-semibold text-on-surface-variant mb-1.5" for="waitlistSearchInput">Search</label>
            <div class="relative">
              <span class="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-outline text-lg">search</span>
              <input type="text" id="waitlistSearchInput" placeholder="Search by name or email..." class="w-full text-sm border border-outline-variant/30 rounded-xl pl-9 pr-3 py-2 bg-white text-on-surface focus:outline-none focus:ring-2 focus:ring-primary/30">
            </div>
          </div>
        </div>
      </div>

      <div class="bg-white rounded-2xl shadow-sm border border-outline-variant/20 mb-6 overflow-hidden min-w-0">
        <div class="hidden md:block clients-table-scroll">
          <table class="w-full text-sm">
            <thead>
              <tr class="bg-surface-container-low/50 text-on-surface-variant text-xs uppercase tracking-wider">
                <th class="text-left px-6 py-3 font-semibold">Name</th>
                <th class="text-left px-6 py-3 font-semibold">Email</th>
                <th class="text-left px-6 py-3 font-semibold">Joined</th>
                <th class="text-left px-6 py-3 font-semibold">Status</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-outline-variant/10" id="waitlistBody"></tbody>
          </table>
        </div>
        <div class="md:hidden divide-y divide-outline-variant/10" id="waitlistMobile"></div>
        <div id="waitlistEmpty" class="hidden p-12 text-center text-sm text-on-surface-variant">
          No one on your waitlist yet.
        </div>
      </div>
    </div>

  </div>
</main>

@include('components.waitlist-notify-modal')

<div id="saveToast" class="fixed top-6 right-6 z-50 transform translate-x-full opacity-0 transition-all duration-300 pointer-events-none">
  <div class="flex items-center gap-3 bg-on-surface text-white px-5 py-3 rounded-xl shadow-lg">
    <span class="material-symbols-outlined text-green-400" style="font-size:20px;">check_circle</span>
    <span class="text-sm font-medium">Note saved locally</span>
  </div>
</div>

<script>
  window.inkjinArtistClients = @json($clients);
  window.inkjinArtistWaitlist = @json($waitlist);
  window.inkjinCurrencySymbol = @json($currencySymbol);
  window.inkjinWaitlistNotifyUrl = @json(route('artist.clients.waitlist.notify'));
</script>
<script src="{{ asset('js/waitlist-notify.js') }}?v=2"></script>
<script src="{{ asset('js/artist-clients.js') }}?v=7"></script>
@endsection
