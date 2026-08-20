@extends('layouts.admin_dashboard_layout')

@section('title', 'Bookings')

@section('content')
@php
  $statusClasses = [
    'pending' => 'bg-amber-50 text-amber-700',
    'confirmed' => 'bg-green-50 text-green-700',
    'completed' => 'bg-blue-50 text-blue-700',
    'cancelled' => 'bg-red-50 text-red-700',
    'no_show' => 'bg-orange-50 text-orange-700',
    'rescheduled' => 'bg-purple-50 text-purple-700',
  ];
  $typeClasses = [
    'flash' => 'bg-sky-50 text-sky-700',
    'custom' => 'bg-violet-50 text-violet-700',
    'payment_link' => 'bg-fuchsia-50 text-fuchsia-700',
  ];
@endphp
<main class="main-content flex-1 min-h-screen">
  <div class="p-6 md:p-10 lg:p-12 max-w-7xl">
    <div class="mb-6 flex flex-col sm:flex-row sm:items-end sm:justify-between gap-3">
      <div>
        <h2 class="text-3xl font-extrabold text-on-surface tracking-tight">Bookings</h2>
        <p class="text-on-surface-variant mt-1">Chronological list of all platform bookings.</p>
      </div>
      <p class="text-sm font-semibold text-on-surface-variant">{{ number_format($total) }} {{ Str::plural('booking', $total) }}</p>
    </div>

    <div class="flex gap-2 overflow-x-auto pb-2 mb-6">
      <a href="{{ route('admin.bookings.index') }}" class="shrink-0 px-4 py-2 rounded-xl text-sm font-semibold bg-primary text-white">Bookings</a>
      <a href="{{ route('admin.requests.index') }}" class="shrink-0 px-4 py-2 rounded-xl text-sm font-semibold bg-white border border-outline-variant/20 text-on-surface-variant hover:text-primary">Requests</a>
    </div>

    <form method="GET" class="bg-surface-container-low rounded-2xl p-5 mb-6 border border-outline-variant/20">
      <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-7 gap-4">
        <div class="xl:col-span-2">
          <label for="bookingSearch" class="block text-xs font-semibold text-on-surface-variant mb-1.5">Search</label>
          <div class="relative">
            <span class="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-outline text-lg">search</span>
            <input id="bookingSearch" name="q" value="{{ $filters['q'] }}" placeholder="Client, artist, or booking ID…" class="w-full text-sm border border-outline-variant/30 rounded-xl pl-9 pr-3 py-2 bg-white text-on-surface focus:outline-none focus:ring-2 focus:ring-primary/30">
          </div>
        </div>
        <div>
          <label for="bookingStatus" class="block text-xs font-semibold text-on-surface-variant mb-1.5">Status</label>
          <select id="bookingStatus" name="status" class="w-full text-sm border border-outline-variant/30 rounded-xl px-3 py-2 bg-white text-on-surface focus:outline-none focus:ring-2 focus:ring-primary/30">
            <option value="all">All statuses</option>
            @foreach($statuses as $status)
              <option value="{{ $status }}" @selected($filters['status'] === $status)>{{ str_replace('_', ' ', ucfirst($status)) }}</option>
            @endforeach
          </select>
        </div>
        <div>
          <label for="bookingType" class="block text-xs font-semibold text-on-surface-variant mb-1.5">Type</label>
          <select id="bookingType" name="type" class="w-full text-sm border border-outline-variant/30 rounded-xl px-3 py-2 bg-white text-on-surface focus:outline-none focus:ring-2 focus:ring-primary/30">
            <option value="all">All types</option>
            @foreach($types as $key => $label)
              <option value="{{ $key }}" @selected($filters['type'] === $key)>{{ $label }}</option>
            @endforeach
          </select>
        </div>
        <div>
          <label for="bookingFrom" class="block text-xs font-semibold text-on-surface-variant mb-1.5">From</label>
          <input type="date" id="bookingFrom" name="from" value="{{ $filters['from'] }}" class="w-full text-sm border border-outline-variant/30 rounded-xl px-3 py-2 bg-white text-on-surface focus:outline-none focus:ring-2 focus:ring-primary/30">
        </div>
        <div>
          <label for="bookingTo" class="block text-xs font-semibold text-on-surface-variant mb-1.5">To</label>
          <input type="date" id="bookingTo" name="to" value="{{ $filters['to'] }}" class="w-full text-sm border border-outline-variant/30 rounded-xl px-3 py-2 bg-white text-on-surface focus:outline-none focus:ring-2 focus:ring-primary/30">
        </div>
        @include('admin.partials.per-page', ['perPage' => $perPage ?? ($filters['per_page'] ?? 10), 'selectId' => 'bookingsPerPage'])
      </div>
      <div class="mt-4 flex flex-wrap justify-end gap-2">
        <a href="{{ route('admin.bookings.index') }}" class="px-4 py-2 rounded-xl text-sm font-semibold border border-outline-variant/30 bg-white text-on-surface-variant hover:text-on-surface">Clear</a>
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
            @forelse($bookings as $booking)
              @php
                $typeKey = $booking->adminTypeKey();
                $amount = $booking->quoteAmount();
                $deposit = (float) ($booking->deposit_amount ?? 0);
              @endphp
              <tr class="hover:bg-surface-container-low/50">
                <td class="px-5 py-4 text-on-surface-variant whitespace-nowrap">
                  <p class="font-medium text-on-surface">{{ $booking->created_at?->format('M j, Y') }}</p>
                  <p class="text-xs text-outline">{{ $booking->created_at?->format('H:i') }}</p>
                </td>
                <td class="px-5 py-4">
                  <p class="font-semibold text-on-surface">{{ $booking->referenceLabel() }}</p>
                  <p class="text-xs text-outline truncate max-w-[180px]" title="{{ $booking->displayTitle() }}">{{ $booking->displayTitle() }}</p>
                </td>
                <td class="px-5 py-4">
                  <p class="font-semibold">{{ $booking->user?->name ?: 'Client #'.$booking->user_id }}</p>
                  <p class="text-xs text-outline">{{ $booking->user?->email }}</p>
                </td>
                <td class="px-5 py-4">
                  <p class="font-semibold">{{ $booking->artist?->name ?: 'Artist #'.$booking->artist_user_id }}</p>
                  <p class="text-xs text-outline">{{ $booking->artist?->email }}</p>
                </td>
                <td class="px-5 py-4">
                  <span class="inline-flex px-2.5 py-1 rounded-full text-xs font-semibold {{ $typeClasses[$typeKey] ?? 'bg-gray-100 text-gray-700' }}">{{ $booking->adminTypeLabel() }}</span>
                </td>
                <td class="px-5 py-4">
                  <span class="inline-flex px-2.5 py-1 rounded-full text-xs font-semibold {{ $statusClasses[$booking->status] ?? 'bg-gray-100 text-gray-700' }}">{{ str_replace('_', ' ', ucfirst((string) $booking->status)) }}</span>
                </td>
                <td class="px-5 py-4 font-semibold whitespace-nowrap">
                  {{ $deposit > 0 ? '€'.number_format($deposit, 2) : '—' }}
                </td>
                <td class="px-5 py-4 font-semibold whitespace-nowrap">
                  {{ $amount > 0 ? '€'.number_format($amount, 2) : '—' }}
                </td>
                <td class="px-5 py-4 text-right">
                  <a href="{{ route('admin.bookings.show', $booking) }}" class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl text-xs font-semibold bg-primary/10 text-primary hover:bg-primary hover:text-white transition-colors">
                    <span class="material-symbols-outlined text-[16px]">visibility</span>
                    View
                  </a>
                </td>
              </tr>
            @empty
              <tr>
                <td colspan="9" class="px-5 py-12 text-center text-on-surface-variant">No bookings match these filters.</td>
              </tr>
            @endforelse
          </tbody>
        </table>
      </div>
      @if($bookings->hasPages())
        <div class="px-5 py-4 border-t border-outline-variant/15">
          {{ $bookings->onEachSide(1)->links('admin.partials.pagination') }}
        </div>
      @endif
    </div>
  </div>
</main>
@endsection
