@extends('layouts.admin_dashboard_layout')

@section('title', 'Dashboard')

@section('content')
  <!-- Main Content -->
  <main class="main-content flex-1 min-h-screen">
    <div class="p-6 md:p-10 lg:p-12 max-w-7xl">

      <!-- Header -->
      <div class="mb-8">
        <div class="flex flex-col sm:flex-row sm:items-end sm:justify-between gap-2 mb-2">
          <h2 class="text-3xl font-extrabold text-on-surface tracking-tight">Admin Dashboard</h2>
          <p class="text-sm text-outline font-medium" id="currentDate"></p>
        </div>
        <p class="text-on-surface-variant mt-1">Platform overview and recent activity.</p>
      </div>

      <!-- Stats Cards -->
      <div class="grid grid-cols-2 sm:grid-cols-3 xl:grid-cols-6 gap-4 mb-10">
        <div class="stat-card bg-white rounded-2xl p-5 shadow-sm border border-outline-variant/20">
          <div class="w-10 h-10 rounded-xl bg-primary/10 flex items-center justify-center mb-3">
            <span class="material-symbols-outlined text-primary text-xl">brush</span>
          </div>
          <p class="text-2xl font-extrabold text-on-surface">342</p>
          <p class="text-xs font-semibold text-on-surface-variant mt-1">Total Artists</p>
        </div>
        <div class="stat-card bg-white rounded-2xl p-5 shadow-sm border border-outline-variant/20">
          <div class="w-10 h-10 rounded-xl bg-blue-500/10 flex items-center justify-center mb-3">
            <span class="material-symbols-outlined text-blue-600 text-xl">group</span>
          </div>
          <p class="text-2xl font-extrabold text-on-surface">1,847</p>
          <p class="text-xs font-semibold text-on-surface-variant mt-1">Total Clients</p>
        </div>
        <div class="stat-card bg-white rounded-2xl p-5 shadow-sm border border-outline-variant/20">
          <div class="w-10 h-10 rounded-xl bg-green-500/10 flex items-center justify-center mb-3">
            <span class="material-symbols-outlined text-green-600 text-xl">calendar_month</span>
          </div>
          <p class="text-2xl font-extrabold text-on-surface">156</p>
          <p class="text-xs font-semibold text-on-surface-variant mt-1">Active Bookings</p>
        </div>
        <div class="stat-card bg-white rounded-2xl p-5 shadow-sm border border-outline-variant/20">
          <div class="w-10 h-10 rounded-xl bg-purple-500/10 flex items-center justify-center mb-3">
            <span class="material-symbols-outlined text-purple-600 text-xl">euro</span>
          </div>
          <p class="text-2xl font-extrabold text-on-surface">€28,450</p>
          <p class="text-xs font-semibold text-on-surface-variant mt-1">Revenue This Month</p>
        </div>
        <div class="stat-card bg-white rounded-2xl p-5 shadow-sm border border-outline-variant/20">
          <div class="w-10 h-10 rounded-xl bg-amber-500/10 flex items-center justify-center mb-3">
            <span class="material-symbols-outlined text-amber-600 text-xl">receipt_long</span>
          </div>
          <p class="text-2xl font-extrabold text-on-surface">€1,560</p>
          <p class="text-xs font-semibold text-on-surface-variant mt-1">Fees Collected</p>
        </div>
        <div class="stat-card bg-white rounded-2xl p-5 shadow-sm border border-outline-variant/20">
          <div class="w-10 h-10 rounded-xl bg-red-500/10 flex items-center justify-center mb-3">
            <span class="material-symbols-outlined text-red-600 text-xl">schedule_send</span>
          </div>
          <p class="text-2xl font-extrabold text-on-surface">€12,300</p>
          <p class="text-xs font-semibold text-on-surface-variant mt-1">Pending Payouts</p>
        </div>
      </div>

      <div class="grid grid-cols-1 xl:grid-cols-3 gap-6 mb-10">
        <!-- Recent Activity -->
        <div class="xl:col-span-2 bg-white rounded-2xl shadow-sm border border-outline-variant/20 overflow-hidden">
          <div class="flex items-center justify-between px-6 py-5 border-b border-outline-variant/15">
            <h3 class="text-lg font-bold text-on-surface">Recent Activity</h3>
          </div>
          <div class="divide-y divide-outline-variant/10">
            <div class="px-6 py-4 flex items-start gap-3">
              <div class="w-8 h-8 rounded-lg bg-green-50 flex items-center justify-center flex-shrink-0 mt-0.5"><span class="material-symbols-outlined text-green-600" style="font-size:18px;">check_circle</span></div>
              <div class="flex-1"><p class="text-sm text-on-surface"><span class="font-semibold">Julian Ink</span> completed onboarding</p><p class="text-xs text-outline mt-0.5">2h ago</p></div>
            </div>
            <div class="px-6 py-4 flex items-start gap-3">
              <div class="w-8 h-8 rounded-lg bg-blue-50 flex items-center justify-center flex-shrink-0 mt-0.5"><span class="material-symbols-outlined text-blue-600" style="font-size:18px;">calendar_month</span></div>
              <div class="flex-1"><p class="text-sm text-on-surface"><span class="font-semibold">Julian Ink</span> booked Dragon Sleeve with Julian Ink</p><p class="text-xs text-outline mt-0.5">3h ago</p></div>
            </div>
            <div class="px-6 py-4 flex items-start gap-3">
              <div class="w-8 h-8 rounded-lg bg-purple-50 flex items-center justify-center flex-shrink-0 mt-0.5"><span class="material-symbols-outlined text-purple-600" style="font-size:18px;">edit</span></div>
              <div class="flex-1"><p class="text-sm text-on-surface"><span class="font-semibold">Maya Tattoo</span> updated pricing for Rose Mandala</p><p class="text-xs text-outline mt-0.5">5h ago</p></div>
            </div>
            <div class="px-6 py-4 flex items-start gap-3">
              <div class="w-8 h-8 rounded-lg bg-amber-50 flex items-center justify-center flex-shrink-0 mt-0.5"><span class="material-symbols-outlined text-amber-600" style="font-size:18px;">person_add</span></div>
              <div class="flex-1"><p class="text-sm text-on-surface">New artist signup: <span class="font-semibold">Alex Fine Line</span></p><p class="text-xs text-outline mt-0.5">Yesterday</p></div>
            </div>
            <div class="px-6 py-4 flex items-start gap-3">
              <div class="w-8 h-8 rounded-lg bg-green-50 flex items-center justify-center flex-shrink-0 mt-0.5"><span class="material-symbols-outlined text-green-600" style="font-size:18px;">payments</span></div>
              <div class="flex-1"><p class="text-sm text-on-surface">Payment of <span class="font-semibold">€240</span> processed for booking #INK-384729</p><p class="text-xs text-outline mt-0.5">Yesterday</p></div>
            </div>
            <div class="px-6 py-4 flex items-start gap-3">
              <div class="w-8 h-8 rounded-lg bg-red-50 flex items-center justify-center flex-shrink-0 mt-0.5"><span class="material-symbols-outlined text-red-600" style="font-size:18px;">flag</span></div>
              <div class="flex-1"><p class="text-sm text-on-surface">Design <span class="font-semibold">"Tribal Wolf"</span> flagged for review</p><p class="text-xs text-outline mt-0.5">Yesterday</p></div>
            </div>
            <div class="px-6 py-4 flex items-start gap-3">
              <div class="w-8 h-8 rounded-lg bg-blue-50 flex items-center justify-center flex-shrink-0 mt-0.5"><span class="material-symbols-outlined text-blue-600" style="font-size:18px;">person_add</span></div>
              <div class="flex-1"><p class="text-sm text-on-surface">New client signup: <span class="font-semibold">Emma Rodriguez</span></p><p class="text-xs text-outline mt-0.5">2 days ago</p></div>
            </div>
            <div class="px-6 py-4 flex items-start gap-3">
              <div class="w-8 h-8 rounded-lg bg-amber-50 flex items-center justify-center flex-shrink-0 mt-0.5"><span class="material-symbols-outlined text-amber-600" style="font-size:18px;">warning</span></div>
              <div class="flex-1"><p class="text-sm text-on-surface">Dispute opened on booking <span class="font-semibold">#INK-382104</span></p><p class="text-xs text-outline mt-0.5">2 days ago</p></div>
            </div>
            <div class="px-6 py-4 flex items-start gap-3">
              <div class="w-8 h-8 rounded-lg bg-green-50 flex items-center justify-center flex-shrink-0 mt-0.5"><span class="material-symbols-outlined text-green-600" style="font-size:18px;">check_circle</span></div>
              <div class="flex-1"><p class="text-sm text-on-surface"><span class="font-semibold">Nina Blackwork</span> verified payout details</p><p class="text-xs text-outline mt-0.5">3 days ago</p></div>
            </div>
            <div class="px-6 py-4 flex items-start gap-3">
              <div class="w-8 h-8 rounded-lg bg-purple-50 flex items-center justify-center flex-shrink-0 mt-0.5"><span class="material-symbols-outlined text-purple-600" style="font-size:18px;">photo_library</span></div>
              <div class="flex-1"><p class="text-sm text-on-surface"><span class="font-semibold">Sofia Color</span> added 5 new portfolio items</p><p class="text-xs text-outline mt-0.5">3 days ago</p></div>
            </div>
          </div>
        </div>

        <!-- Alerts -->
        <div class="bg-white rounded-2xl shadow-sm border border-outline-variant/20 overflow-hidden h-fit">
          <div class="flex items-center justify-between px-6 py-5 border-b border-outline-variant/15">
            <h3 class="text-lg font-bold text-on-surface">Needs Attention</h3>
            <span class="w-6 h-6 rounded-full bg-red-500 text-white text-xs font-bold flex items-center justify-center">3</span>
          </div>
          <div class="divide-y divide-outline-variant/10">
            <div class="px-6 py-4 flex items-center justify-between">
              <div class="flex items-center gap-3">
                <div class="w-8 h-8 rounded-lg bg-amber-50 flex items-center justify-center"><span class="material-symbols-outlined text-amber-600" style="font-size:18px;">account_balance</span></div>
                <div>
                  <p class="text-sm font-semibold text-on-surface">3 artists pending payout review</p>
                  <p class="text-xs text-outline">€4,200 total</p>
                </div>
              </div>
              <a href="admin-payments.html" class="text-xs font-semibold text-primary hover:underline">View →</a>
            </div>
            <div class="px-6 py-4 flex items-center justify-between">
              <div class="flex items-center gap-3">
                <div class="w-8 h-8 rounded-lg bg-red-50 flex items-center justify-center"><span class="material-symbols-outlined text-red-600" style="font-size:18px;">gavel</span></div>
                <div>
                  <p class="text-sm font-semibold text-on-surface">2 disputed payments</p>
                  <p class="text-xs text-outline">Action required</p>
                </div>
              </div>
              <a href="admin-payments.html" class="text-xs font-semibold text-primary hover:underline">View →</a>
            </div>
            <div class="px-6 py-4 flex items-center justify-between">
              <div class="flex items-center gap-3">
                <div class="w-8 h-8 rounded-lg bg-purple-50 flex items-center justify-center"><span class="material-symbols-outlined text-purple-600" style="font-size:18px;">flag</span></div>
                <div>
                  <p class="text-sm font-semibold text-on-surface">5 designs flagged for review</p>
                  <p class="text-xs text-outline">Content moderation</p>
                </div>
              </div>
              <a href="admin-designs.html" class="text-xs font-semibold text-primary hover:underline">View →</a>
            </div>
          </div>
        </div>
      </div>

      <!-- Charts Placeholder -->
      <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-10">
        <div class="bg-white rounded-2xl shadow-sm border border-outline-variant/20 p-6">
          <h4 class="text-sm font-bold text-on-surface mb-4">Bookings This Week</h4>
          <div class="bg-surface-container-low rounded-xl h-48 flex items-center justify-center">
            <div class="text-center">
              <span class="material-symbols-outlined text-outline text-4xl mb-2">bar_chart</span>
              <p class="text-xs text-outline">Chart: 7-day booking trend</p>
            </div>
          </div>
        </div>
        <div class="bg-white rounded-2xl shadow-sm border border-outline-variant/20 p-6">
          <h4 class="text-sm font-bold text-on-surface mb-4">Revenue This Month</h4>
          <div class="bg-surface-container-low rounded-xl h-48 flex items-center justify-center">
            <div class="text-center">
              <span class="material-symbols-outlined text-outline text-4xl mb-2">show_chart</span>
              <p class="text-xs text-outline">Chart: 30-day revenue</p>
            </div>
          </div>
        </div>
        <div class="bg-white rounded-2xl shadow-sm border border-outline-variant/20 p-6">
          <h4 class="text-sm font-bold text-on-surface mb-4">New Signups</h4>
          <div class="bg-surface-container-low rounded-xl h-48 flex items-center justify-center">
            <div class="text-center">
              <span class="material-symbols-outlined text-outline text-4xl mb-2">stacked_line_chart</span>
              <p class="text-xs text-outline">Chart: Artist vs Client signups</p>
            </div>
          </div>
        </div>
      </div>

    </div>
  </main>

  <script>
    const dateEl = document.getElementById('currentDate');
    const now = new Date();
    dateEl.textContent = now.toLocaleDateString('en-US', { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' });
  </script>
@endsection