@extends('layouts.admin_dashboard_layout')

@php
  $sectionConfig = [
    'revenue' => [
      'title' => 'Revenue',
      'subtitle' => 'Track customer payments, refunds, and net platform revenue.',
      'empty' => 'No revenue records match these filters.',
    ],
    'fees' => [
      'title' => 'Fees',
      'subtitle' => 'Review platform fees collected from bookings and refunded fees.',
      'empty' => 'No fee records match these filters.',
    ],
    'payouts' => [
      'title' => 'Payouts',
      'subtitle' => 'Monitor artist payout processing, completed transfers, and failures.',
      'empty' => 'No payout records match these filters.',
    ],
  ][$section];
  $cardClasses = [
    'purple' => ['bg' => 'bg-purple-100', 'text' => 'text-purple-700'],
    'green' => ['bg' => 'bg-green-100', 'text' => 'text-green-700'],
    'red' => ['bg' => 'bg-red-100', 'text' => 'text-red-700'],
    'amber' => ['bg' => 'bg-amber-100', 'text' => 'text-amber-700'],
  ];
  $statusClasses = [
    'paid' => 'bg-green-50 text-green-700',
    'completed' => 'bg-green-50 text-green-700',
    'pending' => 'bg-amber-50 text-amber-700',
    'refunded' => 'bg-blue-50 text-blue-700',
    'failed' => 'bg-red-50 text-red-700',
  ];
@endphp

@section('title', $sectionConfig['title'])

@section('content')
<main class="main-content flex-1 min-h-screen">
  <div class="p-6 md:p-10 lg:p-12 max-w-7xl">
    <div class="mb-6">
      <h2 class="text-3xl font-extrabold text-on-surface tracking-tight">{{ $sectionConfig['title'] }}</h2>
      <p class="text-on-surface-variant mt-1">{{ $sectionConfig['subtitle'] }}</p>
    </div>

    <div class="flex gap-2 overflow-x-auto pb-2 mb-6">
      <a href="{{ route('admin.revenue.index') }}" class="shrink-0 px-4 py-2 rounded-xl text-sm font-semibold {{ $section === 'revenue' ? 'bg-primary text-white' : 'bg-white border border-outline-variant/20 text-on-surface-variant hover:text-primary' }}">Revenue</a>
      <a href="{{ route('admin.fees.index') }}" class="shrink-0 px-4 py-2 rounded-xl text-sm font-semibold {{ $section === 'fees' ? 'bg-primary text-white' : 'bg-white border border-outline-variant/20 text-on-surface-variant hover:text-primary' }}">Fees</a>
      <a href="{{ route('admin.payouts.index') }}" class="shrink-0 px-4 py-2 rounded-xl text-sm font-semibold {{ $section === 'payouts' ? 'bg-primary text-white' : 'bg-white border border-outline-variant/20 text-on-surface-variant hover:text-primary' }}">Payouts</a>
    </div>

    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-6">
      @foreach($summaryCards as $card)
        @php $colors = $cardClasses[$card['color']] ?? $cardClasses['purple']; @endphp
        <div class="bg-white rounded-2xl border border-outline-variant/20 p-5 shadow-sm">
          <div class="flex items-start justify-between gap-3">
            <div>
              <p class="text-xs uppercase tracking-wider font-semibold text-on-surface-variant">{{ $card['label'] }}</p>
              <p class="text-2xl font-extrabold text-on-surface mt-1">€{{ number_format($card['value'], 2) }}</p>
            </div>
            <div class="w-10 h-10 rounded-xl {{ $colors['bg'] }} flex items-center justify-center shrink-0">
              <span class="material-symbols-outlined {{ $colors['text'] }} text-xl">{{ $card['icon'] }}</span>
            </div>
          </div>
        </div>
      @endforeach
    </div>

    <form method="GET" class="bg-surface-container-low rounded-2xl p-5 mb-6 border border-outline-variant/20">
      <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-5 gap-4">
        <div class="xl:col-span-2">
          <label for="financeSearch" class="block text-xs font-semibold text-on-surface-variant mb-1.5">Search</label>
          <div class="relative">
            <span class="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-outline text-lg">search</span>
            <input id="financeSearch" name="q" value="{{ $filters['q'] }}" placeholder="{{ $section === 'payouts' ? 'Artist, payout or booking ID...' : 'Client, artist or payment ID...' }}" class="w-full text-sm border border-outline-variant/30 rounded-xl pl-9 pr-3 py-2 bg-white text-on-surface focus:outline-none focus:ring-2 focus:ring-primary/30">
          </div>
        </div>
        <div>
          <label for="financeStatus" class="block text-xs font-semibold text-on-surface-variant mb-1.5">Status</label>
          <select id="financeStatus" name="status" class="w-full text-sm border border-outline-variant/30 rounded-xl px-3 py-2 bg-white text-on-surface focus:outline-none focus:ring-2 focus:ring-primary/30">
            <option value="all">All statuses</option>
            @foreach($statuses as $status)
              <option value="{{ $status }}" {{ $filters['status'] === $status ? 'selected' : '' }}>{{ ucfirst($status) }}</option>
            @endforeach
          </select>
        </div>
        <div>
          <label for="financeFrom" class="block text-xs font-semibold text-on-surface-variant mb-1.5">From</label>
          <input type="date" id="financeFrom" name="from" value="{{ $filters['from'] }}" class="w-full text-sm border border-outline-variant/30 rounded-xl px-3 py-2 bg-white text-on-surface focus:outline-none focus:ring-2 focus:ring-primary/30">
        </div>
        <div>
          <label for="financeTo" class="block text-xs font-semibold text-on-surface-variant mb-1.5">To</label>
          <input type="date" id="financeTo" name="to" value="{{ $filters['to'] }}" class="w-full text-sm border border-outline-variant/30 rounded-xl px-3 py-2 bg-white text-on-surface focus:outline-none focus:ring-2 focus:ring-primary/30">
        </div>
      </div>
      <div class="mt-4 flex flex-wrap justify-end gap-2">
        <a href="{{ url()->current() }}" class="px-4 py-2 rounded-xl text-sm font-semibold border border-outline-variant/30 bg-white text-on-surface-variant hover:text-on-surface">Clear</a>
        <button class="inline-flex items-center gap-2 bg-primary text-white px-4 py-2 rounded-xl font-semibold text-sm hover:bg-primary-container">
          <span class="material-symbols-outlined text-[18px]">filter_alt</span> Apply filters
        </button>
      </div>
    </form>

    <div class="bg-white rounded-2xl shadow-sm border border-outline-variant/20 overflow-hidden">
      <div class="overflow-x-auto">
        <table class="w-full text-sm min-w-[900px]">
          <thead>
            @if($section === 'payouts')
              <tr class="bg-surface-container-low/50 text-on-surface-variant text-xs uppercase tracking-wider">
                <th class="text-left px-5 py-3 font-semibold">Payout</th>
                <th class="text-left px-5 py-3 font-semibold">Artist</th>
                <th class="text-left px-5 py-3 font-semibold">Booking</th>
                <th class="text-left px-5 py-3 font-semibold">Amount</th>
                <th class="text-left px-5 py-3 font-semibold">Transfer</th>
                <th class="text-left px-5 py-3 font-semibold">Date</th>
                <th class="text-left px-5 py-3 font-semibold">Status</th>
              </tr>
            @else
              <tr class="bg-surface-container-low/50 text-on-surface-variant text-xs uppercase tracking-wider">
                <th class="text-left px-5 py-3 font-semibold">Booking</th>
                <th class="text-left px-5 py-3 font-semibold">Client</th>
                <th class="text-left px-5 py-3 font-semibold">Artist</th>
                <th class="text-left px-5 py-3 font-semibold">{{ $section === 'fees' ? 'Platform Fee' : 'Gross Amount' }}</th>
                <th class="text-left px-5 py-3 font-semibold">{{ $section === 'fees' ? 'Fee Refund' : 'Refund' }}</th>
                <th class="text-left px-5 py-3 font-semibold">Provider</th>
                <th class="text-left px-5 py-3 font-semibold">Date</th>
                <th class="text-left px-5 py-3 font-semibold">Status</th>
              </tr>
            @endif
          </thead>
          <tbody class="divide-y divide-outline-variant/10">
            @forelse($records as $record)
              @if($section === 'payouts')
                @php
                  $booking = $record->booking;
                  $artist = $booking?->artist;
                @endphp
                <tr class="hover:bg-surface-container-low/50">
                  <td class="px-5 py-4 font-semibold">#{{ $record->id }}</td>
                  <td class="px-5 py-4">
                    <p class="font-semibold">{{ $artist?->name ?: 'Artist #'.($booking?->artist_user_id ?? '—') }}</p>
                    <p class="text-xs text-outline">{{ $artist?->email }}</p>
                  </td>
                  <td class="px-5 py-4 text-on-surface-variant">#{{ $record->booking_id }}</td>
                  <td class="px-5 py-4 font-bold">{{ strtoupper($record->currency ?? 'EUR') }} {{ number_format((float) $record->amount, 2) }}</td>
                  <td class="px-5 py-4 text-xs text-on-surface-variant max-w-[180px] truncate" title="{{ $record->stripe_transfer_id }}">{{ $record->stripe_transfer_id ?: '—' }}</td>
                  <td class="px-5 py-4 text-on-surface-variant">{{ $record->created_at?->format('M j, Y') }}</td>
                  <td class="px-5 py-4">
                    <span class="inline-flex px-2.5 py-1 rounded-full text-xs font-semibold {{ $statusClasses[$record->status] ?? 'bg-gray-100 text-gray-700' }}">{{ ucfirst($record->status) }}</span>
                    @if($record->failure_reason)
                      <p class="text-[11px] text-red-600 mt-1 max-w-[180px]" title="{{ $record->failure_reason }}">{{ Str::limit($record->failure_reason, 45) }}</p>
                    @endif
                  </td>
                </tr>
              @else
                @php
                  $status = $section === 'fees' && $record->platform_fee_refunded ? 'refunded' : $record->payment_status;
                  $refund = $section === 'fees'
                    ? ($record->platform_fee_refunded ? (float) $record->platform_fee : 0)
                    : (float) $record->refund_amount;
                @endphp
                <tr class="hover:bg-surface-container-low/50">
                  <td class="px-5 py-4">
                    <p class="font-semibold">{{ $record->referenceLabel() }}</p>
                    <p class="text-xs text-outline">{{ $record->displayTitle() }}</p>
                  </td>
                  <td class="px-5 py-4">
                    <p class="font-semibold">{{ $record->user?->name ?: 'Client #'.$record->user_id }}</p>
                    <p class="text-xs text-outline">{{ $record->user?->email }}</p>
                  </td>
                  <td class="px-5 py-4">
                    <p class="font-semibold">{{ $record->artist?->name ?: 'Artist #'.$record->artist_user_id }}</p>
                    <p class="text-xs text-outline">{{ $record->artist?->email }}</p>
                  </td>
                  <td class="px-5 py-4 font-bold">{{ strtoupper($record->currency ?? 'EUR') }} {{ number_format((float) ($section === 'fees' ? $record->platform_fee : $record->total_amount_paid), 2) }}</td>
                  <td class="px-5 py-4 {{ $refund > 0 ? 'text-red-700 font-semibold' : 'text-on-surface-variant' }}">{{ strtoupper($record->currency ?? 'EUR') }} {{ number_format($refund, 2) }}</td>
                  <td class="px-5 py-4 text-on-surface-variant">{{ ucfirst($record->payment_provider ?: 'Stripe') }}</td>
                  <td class="px-5 py-4 text-on-surface-variant">{{ $record->created_at?->format('M j, Y') }}</td>
                  <td class="px-5 py-4"><span class="inline-flex px-2.5 py-1 rounded-full text-xs font-semibold {{ $statusClasses[$status] ?? 'bg-gray-100 text-gray-700' }}">{{ ucfirst($status) }}</span></td>
                </tr>
              @endif
            @empty
              <tr>
                <td colspan="8" class="px-6 py-14 text-center text-sm text-on-surface-variant">{{ $sectionConfig['empty'] }}</td>
              </tr>
            @endforelse
          </tbody>
        </table>
      </div>
    </div>

    @if($records->hasPages())
      <div class="mt-6 bg-white rounded-2xl border border-outline-variant/20 px-5 py-4">
        {{ $records->onEachSide(1)->links('admin.partials.pagination') }}
      </div>
    @endif
  </div>
</main>
@endsection
