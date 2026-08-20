@extends('layouts.admin_dashboard_layout')

@section('title', 'Requests')

@section('content')
@php
  $statusClasses = [
    'pending' => 'bg-amber-50 text-amber-700',
    'confirmed' => 'bg-blue-50 text-blue-700',
    'cancelled' => 'bg-red-50 text-red-700',
    'moved_to_booking' => 'bg-green-50 text-green-700',
  ];
  $typeClasses = [
    'flash' => 'bg-sky-50 text-sky-700',
    'custom' => 'bg-violet-50 text-violet-700',
  ];
@endphp
<main class="main-content flex-1 min-h-screen">
  <div class="p-6 md:p-10 lg:p-12 max-w-7xl">
    <div class="mb-6 flex flex-col sm:flex-row sm:items-end sm:justify-between gap-3">
      <div>
        <h2 class="text-3xl font-extrabold text-on-surface tracking-tight">Requests</h2>
        <p class="text-on-surface-variant mt-1">Chronological list of flash and custom requests.</p>
      </div>
      <p class="text-sm font-semibold text-on-surface-variant">{{ number_format($total) }} {{ Str::plural('request', $total) }}</p>
    </div>

    <div class="flex gap-2 overflow-x-auto pb-2 mb-6">
      <a href="{{ route('admin.bookings.index') }}" class="shrink-0 px-4 py-2 rounded-xl text-sm font-semibold bg-white border border-outline-variant/20 text-on-surface-variant hover:text-primary">Bookings</a>
      <a href="{{ route('admin.requests.index') }}" class="shrink-0 px-4 py-2 rounded-xl text-sm font-semibold bg-primary text-white">Requests</a>
    </div>

    <form method="GET" class="bg-surface-container-low rounded-2xl p-5 mb-6 border border-outline-variant/20">
      <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-6 gap-4">
        <div class="xl:col-span-2">
          <label for="requestSearch" class="block text-xs font-semibold text-on-surface-variant mb-1.5">Search</label>
          <div class="relative">
            <span class="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-outline text-lg">search</span>
            <input id="requestSearch" name="q" value="{{ $filters['q'] }}" placeholder="Client, artist, or request ID…" class="w-full text-sm border border-outline-variant/30 rounded-xl pl-9 pr-3 py-2 bg-white text-on-surface focus:outline-none focus:ring-2 focus:ring-primary/30">
          </div>
        </div>
        <div>
          <label for="requestStatus" class="block text-xs font-semibold text-on-surface-variant mb-1.5">Status</label>
          <select id="requestStatus" name="status" class="w-full text-sm border border-outline-variant/30 rounded-xl px-3 py-2 bg-white text-on-surface focus:outline-none focus:ring-2 focus:ring-primary/30">
            <option value="all">All statuses</option>
            @foreach($statuses as $status)
              <option value="{{ $status }}" @selected($filters['status'] === $status)>{{ str_replace('_', ' ', ucfirst($status)) }}</option>
            @endforeach
          </select>
        </div>
        <div>
          <label for="requestType" class="block text-xs font-semibold text-on-surface-variant mb-1.5">Type</label>
          <select id="requestType" name="type" class="w-full text-sm border border-outline-variant/30 rounded-xl px-3 py-2 bg-white text-on-surface focus:outline-none focus:ring-2 focus:ring-primary/30">
            <option value="all">All types</option>
            @foreach($types as $key => $label)
              <option value="{{ $key }}" @selected($filters['type'] === $key)>{{ $label }}</option>
            @endforeach
          </select>
        </div>
        <div>
          <label for="requestFrom" class="block text-xs font-semibold text-on-surface-variant mb-1.5">From</label>
          <input type="date" id="requestFrom" name="from" value="{{ $filters['from'] }}" class="w-full text-sm border border-outline-variant/30 rounded-xl px-3 py-2 bg-white text-on-surface focus:outline-none focus:ring-2 focus:ring-primary/30">
        </div>
        <div>
          <label for="requestTo" class="block text-xs font-semibold text-on-surface-variant mb-1.5">To</label>
          <input type="date" id="requestTo" name="to" value="{{ $filters['to'] }}" class="w-full text-sm border border-outline-variant/30 rounded-xl px-3 py-2 bg-white text-on-surface focus:outline-none focus:ring-2 focus:ring-primary/30">
        </div>
      </div>
      <div class="mt-4 flex flex-wrap justify-end gap-2">
        <a href="{{ route('admin.requests.index') }}" class="px-4 py-2 rounded-xl text-sm font-semibold border border-outline-variant/30 bg-white text-on-surface-variant hover:text-on-surface">Clear</a>
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
              <th class="text-left px-5 py-3 font-semibold">Date</th>
              <th class="text-left px-5 py-3 font-semibold">ID</th>
              <th class="text-left px-5 py-3 font-semibold">Client</th>
              <th class="text-left px-5 py-3 font-semibold">Artist</th>
              <th class="text-left px-5 py-3 font-semibold">Type</th>
              <th class="text-left px-5 py-3 font-semibold">Status</th>
              <th class="text-left px-5 py-3 font-semibold">Deposit</th>
              <th class="text-left px-5 py-3 font-semibold">Amount</th>
              <th class="text-right px-5 py-3 font-semibold">Actions</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-outline-variant/10">
            @forelse($requests as $row)
              <tr class="hover:bg-surface-container-low/50">
                <td class="px-5 py-4 text-on-surface-variant whitespace-nowrap">
                  <p class="font-medium text-on-surface">{{ $row['date']?->format('M j, Y') }}</p>
                  <p class="text-xs text-outline">{{ $row['date']?->format('H:i') }}</p>
                </td>
                <td class="px-5 py-4 font-semibold text-on-surface">{{ $row['id'] }}</td>
                <td class="px-5 py-4">
                  <p class="font-semibold">{{ $row['client_name'] }}</p>
                  <p class="text-xs text-outline">{{ $row['client_email'] }}</p>
                </td>
                <td class="px-5 py-4">
                  <p class="font-semibold">{{ $row['artist_name'] }}</p>
                  <p class="text-xs text-outline">{{ $row['artist_email'] }}</p>
                </td>
                <td class="px-5 py-4">
                  <span class="inline-flex px-2.5 py-1 rounded-full text-xs font-semibold {{ $typeClasses[$row['type_key']] ?? 'bg-gray-100 text-gray-700' }}">{{ $row['type'] }}</span>
                </td>
                <td class="px-5 py-4">
                  <span class="inline-flex px-2.5 py-1 rounded-full text-xs font-semibold {{ $statusClasses[$row['status_key']] ?? 'bg-gray-100 text-gray-700' }}">{{ $row['status'] }}</span>
                </td>
                <td class="px-5 py-4 font-semibold whitespace-nowrap">
                  {{ $row['deposit'] !== null ? '€'.number_format($row['deposit'], 2) : '—' }}
                </td>
                <td class="px-5 py-4 font-semibold whitespace-nowrap">
                  {{ $row['amount'] !== null ? '€'.number_format($row['amount'], 2) : '—' }}
                </td>
                <td class="px-5 py-4 text-right">
                  <a href="{{ $row['view_url'] }}" class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl text-xs font-semibold bg-primary/10 text-primary hover:bg-primary hover:text-white transition-colors">
                    <span class="material-symbols-outlined text-[16px]">visibility</span>
                    View
                  </a>
                </td>
              </tr>
            @empty
              <tr>
                <td colspan="9" class="px-5 py-12 text-center text-on-surface-variant">No requests match these filters.</td>
              </tr>
            @endforelse
          </tbody>
        </table>
      </div>
      @if($requests->hasPages())
        <div class="px-5 py-4 border-t border-outline-variant/15">
          {{ $requests->onEachSide(1)->links('admin.partials.pagination') }}
        </div>
      @endif
    </div>
  </div>
</main>
@endsection
