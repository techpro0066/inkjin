@extends('layouts.user_dashboard_layout')

@section('title', 'Dashboard')

@section('content')
<main class="main-content flex-1 min-h-screen">
    <div class="p-6 md:p-10 lg:p-12 max-w-6xl">

      <!-- Welcome Header -->
      <div class="mb-8">
        <div class="flex flex-col sm:flex-row sm:items-end sm:justify-between gap-2 mb-2">
          <h2 class="text-3xl font-extrabold text-on-surface tracking-tight">Welcome back, {{ Auth::user()->first_name }}</h2>
          <p class="text-sm text-outline font-medium" id="currentDate"></p>
        </div>
        <p class="text-on-surface-variant mt-1 max-w-lg">Here's what's happening with your bookings.</p>
      </div>

      <!-- Stats Cards -->
      <div class="grid grid-cols-2 xl:grid-cols-4 gap-5 mb-8">
        <div class="stat-card bg-white rounded-2xl p-5 shadow-sm border border-outline-variant/20">
          <div class="flex items-start justify-between mb-4">
            <div class="w-11 h-11 rounded-xl bg-primary/10 flex items-center justify-center">
              <span class="material-symbols-outlined text-primary">calendar_today</span>
            </div>
          </div>
          <p class="text-3xl font-extrabold text-on-surface">2</p>
          <p class="text-sm font-semibold text-on-surface mt-1">Upcoming Bookings</p>
          <p class="text-xs text-on-surface-variant mt-1">Next one April 15</p>
        </div>
        <div class="stat-card bg-white rounded-2xl p-5 shadow-sm border border-outline-variant/20">
          <div class="flex items-start justify-between mb-4">
            <div class="w-11 h-11 rounded-xl bg-amber-500/10 flex items-center justify-center">
              <span class="material-symbols-outlined text-amber-600">pending_actions</span>
            </div>
          </div>
          <p class="text-3xl font-extrabold text-on-surface">1</p>
          <p class="text-sm font-semibold text-on-surface mt-1">Pending Requests</p>
          <p class="text-xs text-on-surface-variant mt-1">Waiting for artist reply</p>
        </div>
        <div class="stat-card bg-white rounded-2xl p-5 shadow-sm border border-outline-variant/20">
          <div class="flex items-start justify-between mb-4">
            <div class="w-11 h-11 rounded-xl bg-primary/10 flex items-center justify-center">
              <span class="material-symbols-outlined text-primary">mail</span>
            </div>
          </div>
          <p class="text-3xl font-extrabold text-on-surface">3</p>
          <p class="text-sm font-semibold text-on-surface mt-1">Unread Messages</p>
          <p class="text-xs text-on-surface-variant mt-1">1 new today</p>
        </div>
        <div class="stat-card bg-white rounded-2xl p-5 shadow-sm border border-outline-variant/20">
          <div class="flex items-start justify-between mb-4">
            <div class="w-11 h-11 rounded-xl bg-green-500/10 flex items-center justify-center">
              <span class="material-symbols-outlined text-green-600">payments</span>
            </div>
          </div>
          <p class="text-3xl font-extrabold text-on-surface">€1,450</p>
          <p class="text-sm font-semibold text-on-surface mt-1">Total Spent</p>
          <p class="text-xs text-on-surface-variant mt-1">Across 6 sessions</p>
        </div>
      </div>

      <!-- AR App Download Banner -->
      <div id="arBanner" class="relative mb-8 bg-gradient-to-r from-primary via-primary-container to-primary rounded-2xl p-6 text-white overflow-hidden">
        <button onclick="document.getElementById('arBanner').style.display='none'" class="absolute top-4 right-4 w-8 h-8 rounded-full bg-white/15 hover:bg-white/25 flex items-center justify-center transition-colors">
          <span class="material-symbols-outlined text-white text-lg">close</span>
        </button>
        <div class="flex flex-col md:flex-row items-start md:items-center gap-5">
          <div class="flex-1">
            <div class="flex items-center gap-2 mb-2">
              <span class="text-2xl">📱</span>
              <h3 class="text-lg font-bold">Try tattoos on your body with AR</h3>
            </div>
            <p class="text-white/80 text-sm mb-4 max-w-md">See exactly how your tattoo will look before you commit. Manage bookings, message artists, and get reminders — all on the go.</p>
            <div class="flex flex-wrap items-center gap-3">
              <a href="#" class="inline-flex items-center gap-2 bg-white text-primary font-semibold text-sm px-5 py-2.5 rounded-full hover:bg-white/90 transition-colors">
                <span class="material-symbols-outlined text-lg">apple</span> App Store
              </a>
              <a href="#" class="inline-flex items-center gap-2 bg-white/15 text-white font-semibold text-sm px-5 py-2.5 rounded-full hover:bg-white/25 transition-colors border border-white/20">
                <span class="material-symbols-outlined text-lg">shop</span> Google Play
              </a>
            </div>
          </div>
          <div class="hidden md:flex flex-col items-center gap-2 bg-white/10 rounded-2xl p-4 border border-white/15">
            <span class="material-symbols-outlined text-3xl text-white/90">phone_iphone</span>
            <div class="flex items-center gap-1">
              <span class="material-symbols-outlined text-sm text-amber-300">auto_awesome</span>
              <span class="text-xs font-bold text-white/90 uppercase tracking-wide">AR Try-On</span>
              <span class="material-symbols-outlined text-sm text-amber-300">auto_awesome</span>
            </div>
            <p class="text-[10px] text-white/60">Preview on your skin</p>
          </div>
        </div>
      </div>

      <!-- Upcoming Appointments -->
      <div class="bg-white rounded-2xl shadow-sm border border-outline-variant/20 mb-8 overflow-hidden">
        <div class="flex items-center justify-between px-6 py-5 border-b border-outline-variant/15">
          <h3 class="text-lg font-bold text-on-surface">Upcoming Appointments</h3>
          <a href="client-bookings.html" class="text-sm font-semibold text-primary hover:underline flex items-center gap-1">
            View All <span class="material-symbols-outlined text-base">arrow_forward</span>
          </a>
        </div>
        <div class="divide-y divide-outline-variant/10">
          <!-- Booking 1 -->
          <div class="p-5 flex flex-col sm:flex-row sm:items-center gap-4 hover:bg-surface-container-low/50 transition-colors">
            <div class="flex items-center gap-3 flex-1 min-w-0">
              <div class="w-11 h-11 rounded-full bg-gradient-to-br from-primary to-primary-container flex items-center justify-center flex-shrink-0">
                <span class="text-white text-xs font-bold">JI</span>
              </div>
              <div class="min-w-0">
                <p class="font-semibold text-on-surface text-sm">Dragon Sleeve — Session 2 of 4</p>
                <p class="text-xs text-on-surface-variant mt-0.5">Julian Ink · Ink District Studio, Amsterdam</p>
              </div>
            </div>
            <div class="flex items-center gap-3 sm:gap-5 flex-shrink-0">
              <div class="text-right">
                <p class="text-sm font-semibold text-on-surface">April 15, 2:00 PM</p>
                <span class="inline-flex items-center gap-1.5 bg-green-50 text-green-700 text-xs font-semibold px-2.5 py-0.5 rounded-full mt-1">
                  <span class="w-1.5 h-1.5 bg-green-500 rounded-full"></span> Confirmed
                </span>
              </div>
              <a href="#" class="text-sm font-semibold text-primary hover:underline whitespace-nowrap">View Details</a>
            </div>
          </div>
          <!-- Booking 2 -->
          <div class="p-5 flex flex-col sm:flex-row sm:items-center gap-4 hover:bg-surface-container-low/50 transition-colors">
            <div class="flex items-center gap-3 flex-1 min-w-0">
              <div class="w-11 h-11 rounded-full bg-gradient-to-br from-rose-300 to-rose-400 flex items-center justify-center flex-shrink-0">
                <span class="text-white text-xs font-bold">AF</span>
              </div>
              <div class="min-w-0">
                <p class="font-semibold text-on-surface text-sm">Rose Mandala — Single Session</p>
                <p class="text-xs text-on-surface-variant mt-0.5">Alex Fine Line · Studio Rosa, Berlin</p>
              </div>
            </div>
            <div class="flex items-center gap-3 sm:gap-5 flex-shrink-0">
              <div class="text-right">
                <p class="text-sm font-semibold text-on-surface">April 22, 10:00 AM</p>
                <span class="inline-flex items-center gap-1.5 bg-amber-50 text-amber-700 text-xs font-semibold px-2.5 py-0.5 rounded-full mt-1">
                  <span class="w-1.5 h-1.5 bg-amber-500 rounded-full"></span> Pending
                </span>
              </div>
              <a href="#" class="text-sm font-semibold text-primary hover:underline whitespace-nowrap">View Details</a>
            </div>
          </div>
          <!-- Booking 3 -->
          <div class="p-5 flex flex-col sm:flex-row sm:items-center gap-4 hover:bg-surface-container-low/50 transition-colors">
            <div class="flex items-center gap-3 flex-1 min-w-0">
              <div class="w-11 h-11 rounded-full bg-gradient-to-br from-primary to-primary-container flex items-center justify-center flex-shrink-0">
                <span class="text-white text-xs font-bold">JI</span>
              </div>
              <div class="min-w-0">
                <p class="font-semibold text-on-surface text-sm">Ocean Waves — Session 1 of 2</p>
                <p class="text-xs text-on-surface-variant mt-0.5">Julian Ink · Ink District Studio, Amsterdam</p>
              </div>
            </div>
            <div class="flex items-center gap-3 sm:gap-5 flex-shrink-0">
              <div class="text-right">
                <p class="text-sm font-semibold text-on-surface">May 3, 11:00 AM</p>
                <span class="inline-flex items-center gap-1.5 bg-purple-50 text-purple-700 text-xs font-semibold px-2.5 py-0.5 rounded-full mt-1">
                  <span class="w-1.5 h-1.5 bg-purple-500 rounded-full"></span> Upcoming
                </span>
              </div>
              <a href="#" class="text-sm font-semibold text-primary hover:underline whitespace-nowrap">View Details</a>
            </div>
          </div>
        </div>
      </div>

      <!-- Recent Messages -->
      <div class="bg-white rounded-2xl shadow-sm border border-outline-variant/20 mb-8 overflow-hidden">
        <div class="flex items-center justify-between px-6 py-5 border-b border-outline-variant/15">
          <h3 class="text-lg font-bold text-on-surface">Recent Messages</h3>
          <a href="client-inbox.html" class="text-sm font-semibold text-primary hover:underline flex items-center gap-1">
            View All Messages <span class="material-symbols-outlined text-base">arrow_forward</span>
          </a>
        </div>
        <div class="divide-y divide-outline-variant/10">
          <a href="client-inbox.html" class="p-4 flex items-center gap-3 hover:bg-surface-container-low/50 transition-colors">
            <div class="relative flex-shrink-0">
              <div class="w-10 h-10 rounded-full bg-gradient-to-br from-primary to-primary-container flex items-center justify-center">
                <span class="text-white text-xs font-bold">JI</span>
              </div>
              <span class="absolute -top-0.5 -right-0.5 w-3 h-3 bg-primary rounded-full border-2 border-white"></span>
            </div>
            <div class="flex-1 min-w-0">
              <div class="flex items-center justify-between">
                <p class="font-semibold text-on-surface text-sm">Julian Ink</p>
                <span class="text-xs text-outline">2h ago</span>
              </div>
              <p class="text-sm text-on-surface-variant truncate">I've updated the design based on your feedback — check it out!</p>
            </div>
          </a>
          <a href="client-inbox.html" class="p-4 flex items-center gap-3 hover:bg-surface-container-low/50 transition-colors">
            <div class="w-10 h-10 rounded-full bg-gradient-to-br from-emerald-300 to-emerald-400 flex items-center justify-center flex-shrink-0">
              <span class="text-white text-xs font-bold">MT</span>
            </div>
            <div class="flex-1 min-w-0">
              <div class="flex items-center justify-between">
                <p class="font-semibold text-on-surface text-sm">Maya Tattoo</p>
                <span class="text-xs text-outline">Yesterday</span>
              </div>
              <p class="text-sm text-on-surface-variant truncate">Your session is confirmed for March 10 — see you then!</p>
            </div>
          </a>
          <a href="client-inbox.html" class="p-4 flex items-center gap-3 hover:bg-surface-container-low/50 transition-colors">
            <div class="w-10 h-10 rounded-full bg-gradient-to-br from-rose-300 to-rose-400 flex items-center justify-center flex-shrink-0">
              <span class="text-white text-xs font-bold">AF</span>
            </div>
            <div class="flex-1 min-w-0">
              <div class="flex items-center justify-between">
                <p class="font-semibold text-on-surface text-sm">Alex Fine Line</p>
                <span class="text-xs text-outline">3 days ago</span>
              </div>
              <p class="text-sm text-on-surface-variant truncate">Here's the quote for your rose mandala — let me know what you think</p>
            </div>
          </a>
        </div>
      </div>

      <!-- Tattoo Guides -->
      <div class="mb-8">
        <div class="flex items-center justify-between mb-5">
          <div>
            <h3 class="text-lg font-bold text-on-surface">Preparing for your tattoo?</h3>
            <p class="text-sm text-on-surface-variant mt-0.5">Helpful guides to get you ready</p>
          </div>
          <a href="https://inkjin.com/tattoo-guides/" class="text-sm font-semibold text-primary hover:underline flex items-center gap-1">
            All Guides <span class="material-symbols-outlined text-base">arrow_forward</span>
          </a>
        </div>
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
          <a href="https://inkjin.com/tattoo-guides/how-much-do-tattoos-cost" class="guide-card bg-surface-container-low rounded-2xl p-5 block">
            <span class="text-2xl mb-3 block">💰</span>
            <p class="font-bold text-on-surface text-sm mb-1">How Much Do Tattoos Cost?</p>
            <p class="text-xs text-on-surface-variant">Pricing factors & what to expect</p>
          </a>
          <a href="https://inkjin.com/tattoo-guides/tattoo-pain-chart" class="guide-card bg-surface-container-low rounded-2xl p-5 block">
            <span class="text-2xl mb-3 block">🗺️</span>
            <p class="font-bold text-on-surface text-sm mb-1">Tattoo Pain Chart</p>
            <p class="text-xs text-on-surface-variant">Find out what to expect for your placement</p>
          </a>
          <a href="https://inkjin.com/tattoo-guides/how-much-to-tip-tattoo-artist" class="guide-card bg-surface-container-low rounded-2xl p-5 block">
            <span class="text-2xl mb-3 block">🤝</span>
            <p class="font-bold text-on-surface text-sm mb-1">How to Tip Your Artist</p>
            <p class="text-xs text-on-surface-variant">Tipping etiquette & guidelines</p>
          </a>
          <a href="https://inkjin.com/tattoo-guides/first-tattoo-ideas" class="guide-card bg-surface-container-low rounded-2xl p-5 block">
            <span class="text-2xl mb-3 block">✨</span>
            <p class="font-bold text-on-surface text-sm mb-1">First Tattoo Guide</p>
            <p class="text-xs text-on-surface-variant">Everything you need to know</p>
          </a>
        </div>
      </div>

      <!-- Active Requests -->
      <div class="bg-white rounded-2xl shadow-sm border border-outline-variant/20 mb-8 overflow-hidden">
        <div class="flex items-center justify-between px-6 py-5 border-b border-outline-variant/15">
          <h3 class="text-lg font-bold text-on-surface">Active Requests</h3>
          <a href="client-requests.html" class="text-sm font-semibold text-primary hover:underline flex items-center gap-1">
            View All Requests <span class="material-symbols-outlined text-base">arrow_forward</span>
          </a>
        </div>
        <div class="divide-y divide-outline-variant/10">
          <div class="p-5 flex flex-col sm:flex-row sm:items-center gap-3 hover:bg-surface-container-low/50 transition-colors">
            <div class="flex-1 min-w-0">
              <div class="flex items-center gap-2 mb-1">
                <span class="inline-flex items-center gap-1.5 bg-blue-50 text-blue-700 text-xs font-semibold px-2.5 py-0.5 rounded-full">
                  <span class="w-1.5 h-1.5 bg-blue-500 rounded-full"></span> Quoted
                </span>
                <span class="text-xs text-outline">3 days ago</span>
              </div>
              <p class="font-semibold text-on-surface text-sm">Dragon sleeve custom design — Japanese style</p>
              <p class="text-xs text-on-surface-variant mt-0.5">Julian Ink · Quote: <strong class="text-on-surface">€950</strong></p>
            </div>
            <a href="client-requests.html" class="text-sm font-semibold text-primary hover:underline whitespace-nowrap">View Quote</a>
          </div>
          <div class="p-5 flex flex-col sm:flex-row sm:items-center gap-3 hover:bg-surface-container-low/50 transition-colors">
            <div class="flex-1 min-w-0">
              <div class="flex items-center gap-2 mb-1">
                <span class="inline-flex items-center gap-1.5 bg-amber-50 text-amber-700 text-xs font-semibold px-2.5 py-0.5 rounded-full">
                  <span class="w-1.5 h-1.5 bg-amber-500 rounded-full"></span> Pending
                </span>
                <span class="text-xs text-outline">1 day ago</span>
              </div>
              <p class="font-semibold text-on-surface text-sm">Minimalist constellation — dotwork style</p>
              <p class="text-xs text-on-surface-variant mt-0.5">Maya Tattoo · Waiting for artist response</p>
            </div>
            <span class="text-xs text-on-surface-variant whitespace-nowrap">Awaiting reply</span>
          </div>
        </div>
      </div>

      <!-- Quick Actions -->
      <div class="mb-10">
        <h3 class="text-lg font-bold text-on-surface mb-5">Quick Actions</h3>
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-5">
          <a href="#" class="quick-action bg-white rounded-2xl p-5 shadow-sm border border-outline-variant/20 text-left group block">
            <div class="w-11 h-11 rounded-xl bg-primary/10 flex items-center justify-center mb-4 group-hover:bg-primary/15 transition-colors">
              <span class="material-symbols-outlined text-primary">search</span>
            </div>
            <p class="font-bold text-on-surface text-sm">Find an Artist</p>
            <p class="text-xs text-on-surface-variant mt-1">Browse tattoo artists near you</p>
          </a>
          <a href="#" class="quick-action bg-white rounded-2xl p-5 shadow-sm border border-outline-variant/20 text-left group block">
            <div class="w-11 h-11 rounded-xl bg-primary/10 flex items-center justify-center mb-4 group-hover:bg-primary/15 transition-colors">
              <span class="material-symbols-outlined text-primary">edit</span>
            </div>
            <p class="font-bold text-on-surface text-sm">Request Custom Tattoo</p>
            <p class="text-xs text-on-surface-variant mt-1">Describe your dream design</p>
          </a>
          <a href="#" class="quick-action bg-white rounded-2xl p-5 shadow-sm border border-outline-variant/20 text-left group block">
            <div class="w-11 h-11 rounded-xl bg-primary/10 flex items-center justify-center mb-4 group-hover:bg-primary/15 transition-colors">
              <span class="material-symbols-outlined text-primary">palette</span>
            </div>
            <p class="font-bold text-on-surface text-sm">Browse Designs</p>
            <p class="text-xs text-on-surface-variant mt-1">Explore available tattoo designs</p>
          </a>
        </div>
      </div>

    </div>
</main>
@endsection

@section('scripts')

    <script>
        const dateEl = document.getElementById('currentDate');
        const now = new Date();
        dateEl.textContent = now.toLocaleDateString('en-US', { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' });
    </script>
    
@endsection