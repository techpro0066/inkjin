@extends('layouts.admin_dashboard_layout')

@section('title', 'Stripe Connected Accounts')

@section('content')
@php
  $statusClasses = [
    'ready' => 'bg-green-50 text-green-700',
    'action_required' => 'bg-amber-50 text-amber-700',
    'restricted' => 'bg-red-50 text-red-700',
    'pending' => 'bg-blue-50 text-blue-700',
    'error' => 'bg-red-50 text-red-700',
    'unknown' => 'bg-surface-container-high text-on-surface-variant',
  ];
@endphp
<main class="main-content flex-1 min-h-screen">
  <div class="p-6 md:p-10 lg:p-12 max-w-7xl">
    <div class="mb-6 flex flex-col sm:flex-row sm:items-end sm:justify-between gap-3">
      <div>
        <h2 class="text-3xl font-extrabold text-on-surface tracking-tight">Stripe Connected Accounts</h2>
        <p class="text-on-surface-variant mt-1">All artist and studio Stripe Connect accounts and their current status.</p>
      </div>
      <p class="text-sm font-semibold text-on-surface-variant">{{ number_format($summary['total']) }} {{ Str::plural('account', $summary['total']) }}</p>
    </div>

    @unless($stripeConfigured)
      <div class="mb-6 rounded-2xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-900">
        Stripe is not configured on this server. Account IDs are listed below, but live status cannot be fetched.
      </div>
    @endunless

    <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-4 mb-6">
      <div class="bg-white rounded-2xl border border-outline-variant/20 p-5 shadow-sm">
        <p class="text-xs uppercase tracking-wider font-semibold text-on-surface-variant">Total accounts</p>
        <p class="text-2xl font-extrabold text-on-surface mt-1">{{ number_format($summary['total']) }}</p>
      </div>
      <div class="bg-white rounded-2xl border border-outline-variant/20 p-5 shadow-sm">
        <p class="text-xs uppercase tracking-wider font-semibold text-on-surface-variant">Ready</p>
        <p class="text-2xl font-extrabold text-green-700 mt-1">{{ number_format($summary['ready']) }}</p>
      </div>
      <div class="bg-white rounded-2xl border border-outline-variant/20 p-5 shadow-sm">
        <p class="text-xs uppercase tracking-wider font-semibold text-on-surface-variant">Action required</p>
        <p class="text-2xl font-extrabold text-amber-700 mt-1">{{ number_format($summary['action_required']) }}</p>
      </div>
      <div class="bg-white rounded-2xl border border-outline-variant/20 p-5 shadow-sm">
        <p class="text-xs uppercase tracking-wider font-semibold text-on-surface-variant">Restricted</p>
        <p class="text-2xl font-extrabold text-red-700 mt-1">{{ number_format($summary['restricted']) }}</p>
      </div>
    </div>

    <form method="GET" class="bg-surface-container-low rounded-2xl p-5 mb-6 border border-outline-variant/20">
      <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-4">
        <div class="xl:col-span-2">
          <label for="stripeAccountSearch" class="block text-xs font-semibold text-on-surface-variant mb-1.5">Search</label>
          <div class="relative">
            <span class="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-outline text-lg">search</span>
            <input id="stripeAccountSearch" name="q" value="{{ $filters['q'] }}" placeholder="Account ID, owner, email, or status…" class="w-full text-sm border border-outline-variant/30 rounded-xl pl-9 pr-3 py-2 bg-white text-on-surface focus:outline-none focus:ring-2 focus:ring-primary/30">
          </div>
        </div>
        @include('admin.partials.per-page', ['perPage' => $perPage ?? ($filters['per_page'] ?? 10), 'selectId' => 'stripeAccountsPerPage'])
      </div>
      <div class="mt-4 flex flex-wrap justify-end gap-2">
        <a href="{{ route('admin.stripe-accounts.index') }}" class="px-4 py-2 rounded-xl text-sm font-semibold border border-outline-variant/30 bg-white text-on-surface-variant hover:text-on-surface">Clear</a>
        <button class="inline-flex items-center gap-2 bg-primary text-white px-4 py-2 rounded-xl font-semibold text-sm hover:bg-primary-container">
          <span class="material-symbols-outlined text-[18px]">filter_alt</span> Apply filters
        </button>
      </div>
    </form>

    <div class="bg-white rounded-2xl shadow-sm border border-outline-variant/20 overflow-hidden">
      <div class="overflow-x-auto">
        <table class="w-full text-sm min-w-[1100px]">
          <thead>
            <tr class="bg-surface-container-low/50 text-on-surface-variant text-xs uppercase tracking-wider">
              <th class="text-left px-5 py-3 font-semibold">Account ID</th>
              <th class="text-left px-5 py-3 font-semibold">Owner</th>
              <th class="text-left px-5 py-3 font-semibold">Type</th>
              <th class="text-left px-5 py-3 font-semibold">Payment setup</th>
              <th class="text-left px-5 py-3 font-semibold">Charges</th>
              <th class="text-left px-5 py-3 font-semibold">Payouts</th>
              <th class="text-left px-5 py-3 font-semibold">Requirements due</th>
              <th class="text-left px-5 py-3 font-semibold">Status</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-outline-variant/10">
            @forelse($accounts as $account)
              @php $statusClass = $statusClasses[$account['status_key']] ?? $statusClasses['unknown']; @endphp
              <tr class="hover:bg-surface-container-low/40">
                <td class="px-5 py-4 font-mono text-xs text-on-surface">{{ $account['account_id'] }}</td>
                <td class="px-5 py-4">
                  <div class="font-semibold text-on-surface">{{ $account['owner_name'] }}</div>
                  @if($account['owner_email'] !== '')
                    <div class="text-xs text-on-surface-variant mt-0.5">{{ $account['owner_email'] }}</div>
                  @endif
                  @if($account['owner_username'] !== '')
                    <div class="text-xs text-on-surface-variant mt-0.5">@{{ $account['owner_username'] }}</div>
                  @endif
                </td>
                <td class="px-5 py-4">
                  <span class="inline-flex items-center rounded-full px-2.5 py-1 text-xs font-semibold bg-surface-container-high text-on-surface-variant">
                    {{ $account['owner_type'] }}
                  </span>
                </td>
                <td class="px-5 py-4 text-on-surface-variant">
                  @if($account['payment_type'] !== '')
                    <div>{{ ucwords(str_replace('_', ' ', $account['payment_type'])) }}</div>
                  @else
                    <span>—</span>
                  @endif
                  @if($account['payment_status'] !== '')
                    <div class="text-xs mt-0.5">{{ ucfirst($account['payment_status']) }}</div>
                  @endif
                  @if($account['studio_name'] !== '' && $account['owner_type'] === 'Artist')
                    <div class="text-xs mt-0.5">{{ $account['studio_name'] }}</div>
                  @endif
                </td>
                <td class="px-5 py-4">
                  @if($account['charges_enabled'] === null)
                    <span class="text-on-surface-variant">—</span>
                  @elseif($account['charges_enabled'])
                    <span class="text-green-700 font-semibold">Enabled</span>
                  @else
                    <span class="text-red-700 font-semibold">Disabled</span>
                  @endif
                </td>
                <td class="px-5 py-4">
                  @if($account['payouts_enabled'] === null)
                    <span class="text-on-surface-variant">—</span>
                  @elseif($account['payouts_enabled'])
                    <span class="text-green-700 font-semibold">Enabled</span>
                  @else
                    <span class="text-red-700 font-semibold">Disabled</span>
                  @endif
                </td>
                <td class="px-5 py-4 text-on-surface-variant">
                  {{ $account['requirements_due'] === null ? '—' : number_format($account['requirements_due']) }}
                </td>
                <td class="px-5 py-4">
                  <span class="inline-flex items-center rounded-full px-2.5 py-1 text-xs font-semibold {{ $statusClass }}">
                    {{ $account['status_label'] }}
                  </span>
                  @if(!empty($account['disabled_reason']))
                    <div class="text-xs text-red-700 mt-1 max-w-xs">{{ $account['disabled_reason'] }}</div>
                  @elseif(!empty($account['status_error']))
                    <div class="text-xs text-red-700 mt-1 max-w-xs">{{ $account['status_error'] }}</div>
                  @endif
                </td>
              </tr>
            @empty
              <tr>
                <td colspan="8" class="px-5 py-12 text-center text-on-surface-variant">
                  No Stripe connected accounts found.
                </td>
              </tr>
            @endforelse
          </tbody>
        </table>
      </div>
      @if($accounts->hasPages())
        <div class="px-5 py-4 border-t border-outline-variant/10">
          {{ $accounts->links() }}
        </div>
      @endif
    </div>
  </div>
</main>
@endsection
