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
      <div class="grid grid-cols-2 sm:grid-cols-3 xl:grid-cols-4 gap-4 mb-10">
        <div class="stat-card bg-white rounded-2xl p-5 shadow-sm border border-outline-variant/20">
          <div class="w-10 h-10 rounded-xl bg-primary/10 flex items-center justify-center mb-3">
            <span class="material-symbols-outlined text-primary text-xl">brush</span>
          </div>
          <p class="text-2xl font-extrabold text-on-surface">{{ number_format($stats['artists']) }}</p>
          <p class="text-xs font-semibold text-on-surface-variant mt-1">Total Artists</p>
        </div>
        <div class="stat-card bg-white rounded-2xl p-5 shadow-sm border border-outline-variant/20">
          <div class="w-10 h-10 rounded-xl bg-blue-500/10 flex items-center justify-center mb-3">
            <span class="material-symbols-outlined text-blue-600 text-xl">group</span>
          </div>
          <p class="text-2xl font-extrabold text-on-surface">{{ number_format($stats['clients']) }}</p>
          <p class="text-xs font-semibold text-on-surface-variant mt-1">Total Clients</p>
        </div>
        <div class="stat-card bg-white rounded-2xl p-5 shadow-sm border border-outline-variant/20">
          <div class="w-10 h-10 rounded-xl bg-green-500/10 flex items-center justify-center mb-3">
            <span class="material-symbols-outlined text-green-600 text-xl">calendar_month</span>
          </div>
          <p class="text-2xl font-extrabold text-on-surface">{{ number_format($stats['active_bookings']) }}</p>
          <p class="text-xs font-semibold text-on-surface-variant mt-1">Active Bookings</p>
        </div>
        <a href="{{ route('admin.revenue.index') }}" class="stat-card block bg-white rounded-2xl p-5 shadow-sm border border-outline-variant/20">
          <div class="w-10 h-10 rounded-xl bg-purple-500/10 flex items-center justify-center mb-3">
            <span class="material-symbols-outlined text-purple-600 text-xl">euro</span>
          </div>
          <p class="text-2xl font-extrabold text-on-surface">€{{ number_format($stats['revenue_this_month'], 2) }}</p>
          <p class="text-xs font-semibold text-on-surface-variant mt-1">Revenue This Month</p>
        </a>
        <a href="{{ route('admin.fees.index') }}" class="stat-card block bg-white rounded-2xl p-5 shadow-sm border border-outline-variant/20">
          <div class="w-10 h-10 rounded-xl bg-amber-500/10 flex items-center justify-center mb-3">
            <span class="material-symbols-outlined text-amber-600 text-xl">receipt_long</span>
          </div>
          <p class="text-2xl font-extrabold text-on-surface">€{{ number_format($stats['fees_this_month'], 2) }}</p>
          <p class="text-xs font-semibold text-on-surface-variant mt-1">Fees Collected</p>
        </a>
        <a href="{{ route('admin.payouts.index') }}" class="stat-card block bg-white rounded-2xl p-5 shadow-sm border border-outline-variant/20">
          <div class="w-10 h-10 rounded-xl bg-red-500/10 flex items-center justify-center mb-3">
            <span class="material-symbols-outlined text-red-600 text-xl">schedule_send</span>
          </div>
          <p class="text-2xl font-extrabold text-on-surface">€{{ number_format($stats['pending_payouts'], 2) }}</p>
          <p class="text-xs font-semibold text-on-surface-variant mt-1">Pending Payouts</p>
        </a>
        @php
          $stripeCurrency = $stripeBalance['currency'] ?? 'EUR';
          $stripeSymbol = $stripeCurrency === 'EUR' ? '€' : $stripeCurrency.' ';
          $stripeAvailable = (float) ($stripeBalance['available'] ?? 0);
          $stripePending = (float) ($stripeBalance['pending'] ?? 0);
          $stripeConfigured = (bool) ($stripeBalance['configured'] ?? false);
          $stripeError = $stripeBalance['error'] ?? null;
        @endphp
        <a href="{{ route('admin.stripe-accounts.index') }}" class="stat-card block bg-white rounded-2xl p-5 shadow-sm border border-outline-variant/20">
          <div class="w-10 h-10 rounded-xl bg-teal-500/10 flex items-center justify-center mb-3">
            <span class="material-symbols-outlined text-teal-600 text-xl">account_balance</span>
          </div>
          @if ($stripeConfigured && ! $stripeError)
            <p class="text-2xl font-extrabold text-on-surface">{{ $stripeSymbol }}{{ number_format($stripeAvailable, 2) }}</p>
            <p class="text-xs font-semibold text-on-surface-variant mt-1">Stripe Available Balance</p>
            @if ($stripePending > 0)
              <p class="text-[11px] text-outline mt-1">{{ $stripeSymbol }}{{ number_format($stripePending, 2) }} pending</p>
            @endif
          @else
            <p class="text-2xl font-extrabold text-on-surface">—</p>
            <p class="text-xs font-semibold text-on-surface-variant mt-1">Stripe Available Balance</p>
            @if ($stripeError)
              <p class="text-[11px] text-outline mt-1">{{ $stripeError }}</p>
            @endif
          @endif
        </a>
      </div>

      <div class="grid grid-cols-1 xl:grid-cols-3 gap-6 mb-10">
        <!-- Recent Users -->
        <div class="xl:col-span-2 bg-white rounded-2xl shadow-sm border border-outline-variant/20 overflow-hidden">
          <div class="flex items-center justify-between px-6 py-5 border-b border-outline-variant/15">
            <h3 class="text-lg font-bold text-on-surface">Recent Users</h3>
            <a href="{{ route('admin.users.index') }}" class="text-xs font-semibold text-primary hover:underline">View all users →</a>
          </div>
          <div class="divide-y divide-outline-variant/10">
            @forelse($recentUsers as $user)
              <div class="px-6 py-4 flex items-center justify-between gap-4">
                <div class="flex items-center gap-3 min-w-0">
                  <div class="w-9 h-9 rounded-full bg-primary/10 text-primary font-bold text-xs flex items-center justify-center shrink-0">
                    {{ strtoupper(substr($user->first_name ?? 'U', 0, 1) . substr($user->last_name ?? '', 0, 1)) }}
                  </div>
                  <div class="min-w-0">
                    <p class="text-sm font-semibold text-on-surface truncate">{{ $user->name ?: 'N/A' }}</p>
                    <p class="text-xs text-outline truncate">{{ $user->email }}</p>
                  </div>
                </div>
                <div class="text-right shrink-0">
                  <span class="inline-flex items-center px-2 py-1 rounded-full text-[11px] font-semibold {{ $user->role === 'artist' ? 'bg-primary/10 text-primary' : 'bg-surface-container text-on-surface-variant' }}">
                    {{ $user->role === 'artist' ? 'Artist' : 'Client' }}
                  </span>
                  <p class="text-[11px] text-outline mt-1">{{ $user->created_at?->diffForHumans() }}</p>
                </div>
              </div>
            @empty
              <div class="px-6 py-10 text-center text-sm text-on-surface-variant">No users yet.</div>
            @endforelse
          </div>
        </div>

        <!-- Alerts -->
        <div class="bg-white rounded-2xl shadow-sm border border-outline-variant/20 overflow-hidden h-fit">
          <div class="flex items-center justify-between px-6 py-5 border-b border-outline-variant/15">
            <h3 class="text-lg font-bold text-on-surface">Needs Attention</h3>
            <span class="min-w-6 h-6 px-1.5 rounded-full {{ $attentionCount > 0 ? 'bg-red-500 text-white' : 'bg-green-100 text-green-700' }} text-xs font-bold flex items-center justify-center">{{ number_format($attentionCount) }}</span>
          </div>
          <div class="divide-y divide-outline-variant/10">
            @foreach($attentionItems as $item)
              <div class="px-6 py-4 flex items-center justify-between gap-3">
                <div class="flex items-center gap-3 min-w-0">
                  <div class="w-8 h-8 shrink-0 rounded-lg flex items-center justify-center {{ $item['color'] === 'red' ? 'bg-red-50' : ($item['color'] === 'purple' ? 'bg-purple-50' : 'bg-amber-50') }}">
                    <span class="material-symbols-outlined {{ $item['color'] === 'red' ? 'text-red-600' : ($item['color'] === 'purple' ? 'text-purple-600' : 'text-amber-600') }}" style="font-size:18px;">{{ $item['icon'] }}</span>
                  </div>
                  <div class="min-w-0">
                    <p class="text-sm font-semibold text-on-surface">{{ $item['title'] }}</p>
                    <p class="text-xs text-outline">{{ $item['subtitle'] }}</p>
                  </div>
                </div>
                @if($item['url'])
                  <a href="{{ $item['url'] }}" class="text-xs font-semibold text-primary hover:underline shrink-0">View →</a>
                @endif
              </div>
            @endforeach
          </div>
        </div>
      </div>

      <!-- Live trends -->
      <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-10">
        <div class="bg-white rounded-2xl shadow-sm border border-outline-variant/20 p-6">
          <div class="flex items-end justify-between gap-3 mb-4">
            <h4 class="text-sm font-bold text-on-surface">Bookings This Week</h4>
            <span class="text-xl font-extrabold text-on-surface">{{ number_format($trends['bookings']['total']) }}</span>
          </div>
          <div class="bg-surface-container-low rounded-xl h-48 px-3 pt-4 pb-3 flex items-end gap-2">
            @foreach($trends['bookings']['items'] as $item)
              <div class="flex-1 h-full flex flex-col justify-end items-center gap-1.5" title="{{ $item['label'] }}: {{ number_format($item['value']) }}">
                <span class="text-[10px] font-semibold text-on-surface">{{ number_format($item['value']) }}</span>
                <div class="w-full max-w-7 rounded-t-md bg-primary/75 min-h-[2px]" style="height: {{ ($item['value'] / $trends['bookings']['max']) * 120 }}px"></div>
                <span class="text-[10px] text-outline">{{ $item['label'] }}</span>
              </div>
            @endforeach
          </div>
        </div>
        <div class="bg-white rounded-2xl shadow-sm border border-outline-variant/20 p-6">
          <div class="flex items-end justify-between gap-3 mb-4">
            <h4 class="text-sm font-bold text-on-surface">Revenue — Last 30 Days</h4>
            <span class="text-lg font-extrabold text-on-surface">€{{ number_format($trends['revenue']['total'], 2) }}</span>
          </div>
          <div class="bg-surface-container-low rounded-xl h-48 px-3 pt-4 pb-3 flex items-end gap-[2px]">
            @foreach($trends['revenue']['items'] as $item)
              <div class="flex-1 h-full flex items-end" title="{{ $item['label'] }}: €{{ number_format($item['value'], 2) }}">
                <div class="w-full rounded-t-sm bg-purple-500/75 min-h-[2px]" style="height: {{ ($item['value'] / $trends['revenue']['max']) * 145 }}px"></div>
              </div>
            @endforeach
          </div>
          <div class="flex justify-between text-[10px] text-outline mt-1">
            <span>{{ $trends['revenue']['items'][0]['label'] }}</span>
            <span>{{ $trends['revenue']['items'][count($trends['revenue']['items']) - 1]['label'] }}</span>
          </div>
        </div>
        <div class="bg-white rounded-2xl shadow-sm border border-outline-variant/20 p-6">
          <div class="flex items-end justify-between gap-3 mb-4">
            <h4 class="text-sm font-bold text-on-surface">New Signups — Last 30 Days</h4>
            <span class="text-xl font-extrabold text-on-surface">{{ number_format($trends['signups']['total']) }}</span>
          </div>
          <div class="bg-surface-container-low rounded-xl h-48 p-5 flex flex-col justify-center gap-5">
            @php $signupMax = max(1, $trends['signups']['artists'], $trends['signups']['clients']); @endphp
            <div>
              <div class="flex justify-between text-xs font-semibold mb-1.5">
                <span>Artists</span>
                <span>{{ number_format($trends['signups']['artists']) }}</span>
              </div>
              <div class="h-3 rounded-full bg-white overflow-hidden">
                <div class="h-full rounded-full bg-primary" style="width: {{ ($trends['signups']['artists'] / $signupMax) * 100 }}%"></div>
              </div>
            </div>
            <div>
              <div class="flex justify-between text-xs font-semibold mb-1.5">
                <span>Clients</span>
                <span>{{ number_format($trends['signups']['clients']) }}</span>
              </div>
              <div class="h-3 rounded-full bg-white overflow-hidden">
                <div class="h-full rounded-full bg-blue-500" style="width: {{ ($trends['signups']['clients'] / $signupMax) * 100 }}%"></div>
              </div>
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