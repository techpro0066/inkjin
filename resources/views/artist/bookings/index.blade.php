@extends('layouts.artist_dashboard_layout')

@section('title', 'Bookings')

@section('styles')
<style>
  .artist-bdm { pointer-events: none; opacity: 0; transition: opacity 0.28s cubic-bezier(0.22, 1, 0.36, 1); }
  .artist-bdm.artist-bdm-open { pointer-events: auto; opacity: 1; }
  .artist-bdm-backdrop {
    opacity: 0;
    transition: opacity 0.32s cubic-bezier(0.22, 1, 0.36, 1);
  }
  .artist-bdm.artist-bdm-open .artist-bdm-backdrop { opacity: 1; }
  .artist-bdm-panel {
    opacity: 0;
    transform: translateY(1rem) scale(0.97);
    transition: opacity 0.32s cubic-bezier(0.22, 1, 0.36, 1), transform 0.36s cubic-bezier(0.22, 1, 0.36, 1);
  }
  .artist-bdm.artist-bdm-open .artist-bdm-panel {
    opacity: 1;
    transform: translateY(0) scale(1);
  }
  .artist-bdm-backdrop,
  .artist-bdm-panel { pointer-events: none; }
  .artist-bdm.artist-bdm-open .artist-bdm-backdrop,
  .artist-bdm.artist-bdm-open .artist-bdm-panel { pointer-events: auto; }
  .artist-cbm { pointer-events: none; opacity: 0; transition: opacity 0.28s cubic-bezier(0.22, 1, 0.36, 1); }
  .artist-cbm.artist-cbm-open { pointer-events: auto; opacity: 1; }
  .artist-cbm-backdrop {
    opacity: 0;
    transition: opacity 0.3s cubic-bezier(0.22, 1, 0.36, 1);
  }
  .artist-cbm.artist-cbm-open .artist-cbm-backdrop { opacity: 1; }
  .artist-cbm-panel {
    opacity: 0;
    transform: translateY(1rem) scale(0.97);
    transition: opacity 0.32s cubic-bezier(0.22, 1, 0.36, 1), transform 0.36s cubic-bezier(0.22, 1, 0.36, 1);
  }
  .artist-cbm.artist-cbm-open .artist-cbm-panel {
    opacity: 1;
    transform: translateY(0) scale(1);
  }
  .artist-cbm-backdrop,
  .artist-cbm-panel { pointer-events: none; }
  .artist-cbm.artist-cbm-open .artist-cbm-backdrop,
  .artist-cbm.artist-cbm-open .artist-cbm-panel { pointer-events: auto; }
  .artist-rsm { pointer-events: none; opacity: 0; transition: opacity 0.28s cubic-bezier(0.22, 1, 0.36, 1); }
  .artist-rsm.artist-rsm-open { pointer-events: auto; opacity: 1; }
  .artist-rsm-backdrop { opacity: 0; transition: opacity 0.3s cubic-bezier(0.22, 1, 0.36, 1); }
  .artist-rsm.artist-rsm-open .artist-rsm-backdrop { opacity: 1; }
  .artist-rsm-panel { opacity: 0; transform: translateY(1rem) scale(0.97); transition: opacity 0.32s cubic-bezier(0.22, 1, 0.36, 1), transform 0.36s cubic-bezier(0.22, 1, 0.36, 1); }
  .artist-rsm.artist-rsm-open .artist-rsm-panel { opacity: 1; transform: translateY(0) scale(1); }
  .artist-rsm-backdrop, .artist-rsm-panel { pointer-events: none; }
  .artist-rsm.artist-rsm-open .artist-rsm-backdrop, .artist-rsm.artist-rsm-open .artist-rsm-panel { pointer-events: auto; }
  .artist-complete-modal { pointer-events: none; opacity: 0; transition: opacity 0.28s cubic-bezier(0.22, 1, 0.36, 1); }
  .artist-complete-modal.artist-complete-open { pointer-events: auto; opacity: 1; }
  .artist-complete-backdrop { opacity: 0; transition: opacity 0.3s cubic-bezier(0.22, 1, 0.36, 1); }
  .artist-complete-modal.artist-complete-open .artist-complete-backdrop { opacity: 1; }
  .artist-complete-panel { opacity: 0; transform: translateY(1rem) scale(0.97); transition: opacity 0.32s cubic-bezier(0.22, 1, 0.36, 1), transform 0.36s cubic-bezier(0.22, 1, 0.36, 1); }
  .artist-complete-modal.artist-complete-open .artist-complete-panel { opacity: 1; transform: translateY(0) scale(1); }
  .artist-complete-backdrop, .artist-complete-panel { pointer-events: none; }
  .artist-complete-modal.artist-complete-open .artist-complete-backdrop, .artist-complete-modal.artist-complete-open .artist-complete-panel { pointer-events: auto; }
</style>
@endsection

@section('content')
<main class="main-content flex-1 min-h-screen">
    <div class="p-6 md:p-10 lg:p-12 max-w-6xl">

      <!-- Page Header -->
      <div class="mb-8">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-2">
          <div>
            <h2 class="text-3xl font-extrabold text-on-surface tracking-tight">Bookings</h2>
            <p class="text-on-surface-variant mt-1">
              {{ $bookings->total() }} {{ Str::plural('booking', $bookings->total()) }} total
            </p>
          </div>
          <a href="{{ route('availability.index') }}"
            class="inline-flex items-center justify-center gap-2 bg-primary text-white px-5 py-2.5 rounded-xl font-semibold text-sm hover:bg-primary-container transition-colors shadow-sm">
            <span class="material-symbols-outlined text-lg">calendar_month</span> Availability
          </a>
        </div>
      </div>

      <!-- Filters Bar -->
      <div class="bg-surface-container-low rounded-2xl p-5 mb-6 border border-outline-variant/20">
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-4">
          <div>
            <label for="sortBy" class="block text-xs font-semibold text-on-surface-variant mb-1.5">Sort by</label>
            <select id="sortBy" name="sortBy"
              class="w-full text-sm border border-outline-variant/30 rounded-xl px-3 py-2 bg-white text-on-surface focus:outline-none focus:ring-2 focus:ring-primary/30">
              <option value="recent">Appointment date · newest</option>
              <option value="oldest">Appointment date · oldest</option>
              <option value="name">Client name A–Z</option>
              <option value="name-desc">Client name Z–A</option>
            </select>
          </div>
          <div>
            <label for="dateFrom" class="block text-xs font-semibold text-on-surface-variant mb-1.5">From</label>
            <input type="date" id="dateFrom" name="dateFrom"
              class="w-full text-sm border border-outline-variant/30 rounded-xl px-3 py-2 bg-white text-on-surface focus:outline-none focus:ring-2 focus:ring-primary/30">
          </div>
          <div>
            <label for="dateTo" class="block text-xs font-semibold text-on-surface-variant mb-1.5">To</label>
            <input type="date" id="dateTo" name="dateTo"
              class="w-full text-sm border border-outline-variant/30 rounded-xl px-3 py-2 bg-white text-on-surface focus:outline-none focus:ring-2 focus:ring-primary/30">
          </div>
          <div>
            <label for="searchClient" class="block text-xs font-semibold text-on-surface-variant mb-1.5">Search</label>
            <div class="relative">
              <span class="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-outline text-lg">search</span>
              <input type="text" id="searchClient" name="searchClient" placeholder="Client name..."
                class="w-full text-sm border border-outline-variant/30 rounded-xl pl-9 pr-3 py-2 bg-white text-on-surface focus:outline-none focus:ring-2 focus:ring-primary/30">
            </div>
          </div>
        </div>
        <div class="flex flex-wrap gap-2" id="statusPillsRow">
          <button type="button" data-filter-status="all"
            class="filter-pill js-filter-status active text-xs font-semibold px-4 py-1.5 rounded-full border border-outline-variant/30 bg-white">
            All
          </button>
          <button type="button" data-filter-status="confirmed"
            class="filter-pill js-filter-status text-xs font-semibold px-4 py-1.5 rounded-full border border-outline-variant/30 bg-white text-on-surface-variant">
            Confirmed
          </button>
          <button type="button" data-filter-status="pending"
            class="filter-pill js-filter-status text-xs font-semibold px-4 py-1.5 rounded-full border border-outline-variant/30 bg-white text-on-surface-variant">
            Pending
          </button>
          <button type="button" data-filter-status="completed"
            class="filter-pill js-filter-status text-xs font-semibold px-4 py-1.5 rounded-full border border-outline-variant/30 bg-white text-on-surface-variant">
            Completed
          </button>
          <button type="button" data-filter-status="cancelled"
            class="filter-pill js-filter-status text-xs font-semibold px-4 py-1.5 rounded-full border border-outline-variant/30 bg-white text-on-surface-variant">
            Cancelled
          </button>
        </div>
      </div>

      @php
          $statusStyles = [
              'confirmed' => 'bg-green-50 text-green-700 ring-green-500/20',
              'pending' => 'bg-amber-50 text-amber-900 ring-amber-500/20',
              'cancelled' => 'bg-red-50 text-red-700 ring-red-500/20',
              'completed' => 'bg-slate-100 text-slate-700 ring-slate-500/15',
              'no_show' => 'bg-orange-50 text-orange-900 ring-orange-500/20',
              'rescheduled' => 'bg-blue-50 text-blue-800 ring-blue-500/15',
          ];
      @endphp

      <!-- Bookings Table -->
      @if ($bookings->isEmpty())
        <div class="bg-white rounded-2xl border border-outline-variant/20 p-10 text-center text-on-surface-variant">
          <p class="font-medium text-on-surface mb-1">No bookings yet</p>
          <p class="text-sm">Clients will appear here when they book through your availability and designs.</p>
        </div>
      @else
      <div class="bg-white rounded-2xl shadow-sm border border-outline-variant/20 mb-6 overflow-hidden">
          <!-- Desktop -->
        <div class="hidden sm:block overflow-x-auto">
            <table class="w-full text-sm">
            <thead>
              <tr class="bg-surface-container-low/50 text-on-surface-variant text-xs uppercase tracking-wider">
                <th class="text-left px-6 py-3 font-semibold">Client</th>
                <th class="text-left px-6 py-3 font-semibold">Service</th>
                <th class="text-left px-6 py-3 font-semibold">Date</th>
                <th class="text-left px-6 py-3 font-semibold">Time</th>
                <th class="text-left px-6 py-3 font-semibold">Duration</th>
                <th class="text-left px-6 py-3 font-semibold">Status</th>
                <th class="text-left px-6 py-3 font-semibold">Actions</th>
              </tr>
            </thead>
              <tbody id="bookingsTableBody">
                @foreach ($bookings as $booking)
                  @php
                      $bt = $booking->booking_time;
                      $sessionDate = $booking->booking_date?->format('Y-m-d') ?? '';
                      $clientName = trim(($booking->user?->first_name ?? '') . ' ' . ($booking->user?->last_name ?? ''));
                      $clientName = $clientName !== '' ? $clientName : 'Client #' . ($booking->user_id ?? '');
                      $clientLower = Str::lower($clientName);
                      $details = $booking->custom_tattoo_details ?? null;
                      $customTitle = is_array($details) ? ($details['title'] ?? null) : null;
                      $serviceTitle = ($booking->tattoo && filled($booking->tattoo->title))
                          ? $booking->tattoo->title
                          : (filled($customTitle) ? (string) $customTitle : ucfirst((string) $booking->booking_type) . ' booking');
                      $startEnd = ($bt !== null && isset($bt['start'], $bt['end'])) ? "{$bt['start']} – {$bt['end']}" : '—';
                      $duration = '—';
                      if ($bt !== null && isset($bt['duration_minutes'])) {
                          $mins = (int) $bt['duration_minutes'];
                          $hours = intdiv(max(1, $mins), 60);
                          $tail = max(1, $mins) % 60;
                          $duration = $mins < 60
                              ? "{$mins} min"
                              : ($tail ? "{$hours}h {$tail}m" : "{$hours}h");
                      }
                      $stKey = strtolower((string) ($booking->status ?? ''));
                      $badgeCls = $statusStyles[$stKey] ?? 'bg-surface-container text-on-surface ring-outline-variant/20';
                      $label = ucfirst(str_replace('_', ' ', $stKey ?: 'unknown'));
                      $sortStamp = strtotime($sessionDate ?: '1970-01-01') ?: 0;
                      $artistRequestPending = (
                          $booking->reschedule_status === 'pending'
                          && $booking->reschedule_requested_by === 'artist'
                      );
                      $designImage = asset('design/images/icons/avatar.jpg');
                      if ($booking->tattoo && !empty($booking->tattoo->image)) {
                          $imgRaw = (string) $booking->tattoo->image;
                          $designImage = str_starts_with($imgRaw, 'http://') || str_starts_with($imgRaw, 'https://')
                              ? $imgRaw
                              : asset(ltrim($imgRaw, '/'));
                      }
                      $bookingRef = '#INK-' . str_pad((string) $booking->id, 6, '0', STR_PAD_LEFT);
                      $dateLong = $booking->booking_date?->format('l, F j, Y') ?? '—';
                      $clientEmail = (string) ($booking->user?->email ?? '');
                      $questionsAnswersData = is_array($booking->questions_answers ?? null) ? $booking->questions_answers : [];
                  @endphp
                  <tr class="booking-group border-t border-outline-variant/10 hover:bg-surface-container-low/40"
                      data-booking-row
                      data-booking-id="{{ $booking->id }}"
                      data-status="{{ $stKey }}"
                      data-client="{{ e($clientLower) }}"
                      data-date="{{ $sessionDate }}"
                      data-sort-ts="{{ $sortStamp }}"
                      data-sort-name="{{ e($clientLower) }}">
                    <td class="px-6 py-4 font-medium text-on-surface">{{ $clientName }}</td>
                    <td class="px-6 py-4 text-on-surface-variant">{{ Str::limit($serviceTitle, 48) }}</td>
                    <td class="px-6 py-4 text-on-surface">{{ $booking->booking_date?->format('M j, Y') ?? '—' }}</td>
                    <td class="px-6 py-4 text-on-surface-variant">{{ $startEnd }}</td>
                    <td class="px-6 py-4 text-on-surface-variant tabular-nums">{{ $duration }}</td>
                    <td class="px-6 py-4">
                      <span class="inline-flex items-center text-xs font-semibold px-2.5 py-1 rounded-full ring-1 ring-inset {{ $badgeCls }}">{{ $label }}</span>
                    </td>
                    <td class="px-6 py-4">
                      <div class="flex items-center gap-1">
                        <button type="button"
                          class="js-artist-booking-view inline-flex h-9 w-9 items-center justify-center rounded-lg border border-transparent text-on-surface-variant hover:bg-surface-container-low hover:text-on-surface"
                          title="View booking"
                          aria-haspopup="dialog"
                          aria-controls="artistBookingDetailModal"
                          data-booking-ref="{{ e($bookingRef) }}"
                          data-client-name="{{ e($clientName) }}"
                          data-client-email="{{ e($clientEmail) }}"
                          data-service="{{ e($serviceTitle) }}"
                          data-date-display="{{ e($dateLong) }}"
                          data-time-range="{{ e($startEnd) }}"
                          data-duration="{{ e($duration) }}"
                          data-timezone="{{ e($booking->timezone ?: 'UTC') }}"
                          data-status-label="{{ e($label) }}"
                          data-status-badge-class="{{ e($badgeCls) }}"
                          data-booking-type="{{ e(ucfirst((string) ($booking->booking_type ?? ''))) }}"
                          data-deposit="{{ e('€' . number_format((float) ($booking->deposit_amount ?? 0), 2)) }}"
                          data-questions='@json($questionsAnswersData)'
                          data-design-image="{{ e($designImage) }}">
                          <span class="material-symbols-outlined text-[22px]">visibility</span>
                        </button>
                        @if($booking->status === 'confirmed' || $artistRequestPending)
                          @if(!$artistRequestPending)
                          <button type="button"
                            class="js-artist-reschedule-request inline-flex h-9 w-9 items-center justify-center rounded-lg border border-transparent text-on-surface-variant hover:bg-surface-container-low hover:text-primary"
                            title="{{ $artistRequestPending ? 'Reschedule request pending' : 'Request reschedule from user' }}"
                            data-booking-id="{{ $booking->id }}"
                            data-request-url="{{ route('api.bookings.artist-request-reschedule', $booking->id) }}"
                            data-pending="{{ $artistRequestPending ? '1' : '0' }}"
                            data-client-name="{{ e($clientName) }}"
                            data-service="{{ e($serviceTitle) }}"
                            {{ $artistRequestPending ? 'disabled' : '' }}>
                            <span class="material-symbols-outlined text-[22px]">event_repeat</span>
                          </button>
                          @endif
                          @if($booking->status === 'confirmed')
                          <button type="button"
                            class="js-artist-mark-complete inline-flex h-9 w-9 items-center justify-center rounded-lg border border-transparent text-emerald-700 hover:bg-emerald-50"
                            title="Mark completed"
                            data-booking-id="{{ $booking->id }}"
                            data-client-name="{{ e($clientName) }}"
                            data-service="{{ e($serviceTitle) }}"
                            data-mark-completed-url="{{ route('api.bookings.mark-completed', $booking->id) }}">
                            <span class="material-symbols-outlined text-[22px]">task_alt</span>
                          </button>
                          @endif
                          <button type="button"
                            class="js-artist-cancel-booking inline-flex h-9 w-9 items-center justify-center rounded-lg border border-transparent text-error/80 hover:bg-error-container hover:text-error"
                            title="Cancel booking"
                            data-booking-id="{{ $booking->id }}"
                            data-cancel-url="{{ route('api.bookings.cancel', $booking->id) }}"
                            data-cancel-info-url="{{ route('api.bookings.cancellation-info', $booking->id) }}">
                            <span class="material-symbols-outlined text-[22px]">close</span>
                          </button>
                        @endif
                      </div>
                    </td>
                  </tr>
                @endforeach
            </tbody>
          </table>
        </div>

          <!-- Mobile -->
        <div class="sm:hidden divide-y divide-outline-variant/10" id="bookingsMobile">
            @foreach ($bookings as $booking)
              @php
                  $tz = $booking->timezone ?: 'UTC';
                  $bt = $booking->booking_time;
                  $sessionDate = $booking->booking_date?->format('Y-m-d') ?? '';
                  $clientName = trim(($booking->user?->first_name ?? '') . ' ' . ($booking->user?->last_name ?? ''));
                  $clientName = $clientName !== '' ? $clientName : 'Client #' . ($booking->user_id ?? '');
                  $clientLower = Str::lower($clientName);
                  $details = $booking->custom_tattoo_details ?? null;
                  $customTitle = is_array($details) ? ($details['title'] ?? null) : null;
                  $serviceTitle = ($booking->tattoo && filled($booking->tattoo->title))
                      ? $booking->tattoo->title
                      : (filled($customTitle) ? (string) $customTitle : ucfirst((string) $booking->booking_type) . ' booking');
                  $startEnd = ($bt !== null && isset($bt['start'], $bt['end'])) ? "{$bt['start']} – {$bt['end']}" : '—';
                  $duration = '—';
                  if ($bt !== null && isset($bt['duration_minutes'])) {
                      $mins = (int) $bt['duration_minutes'];
                      $hours = intdiv(max(1, $mins), 60);
                      $tail = max(1, $mins) % 60;
                      $duration = $mins < 60 ? "{$mins} min" : ($tail ? "{$hours}h {$tail}m" : "{$hours}h");
                  }
                  $stKey = strtolower((string) ($booking->status ?? ''));
                  $badgeCls = $statusStyles[$stKey] ?? 'bg-surface-container text-on-surface ring-outline-variant/20';
                  $label = ucfirst(str_replace('_', ' ', $stKey ?: 'unknown'));
                  $sortStamp = strtotime($sessionDate ?: '1970-01-01') ?: 0;
                  $artistRequestPending = (
                      $booking->reschedule_status === 'pending'
                      && $booking->reschedule_requested_by === 'artist'
                  );
                  $designImage = asset('design/images/icons/avatar.jpg');
                  if ($booking->tattoo && !empty($booking->tattoo->image)) {
                      $imgRaw = (string) $booking->tattoo->image;
                      $designImage = str_starts_with($imgRaw, 'http://') || str_starts_with($imgRaw, 'https://')
                          ? $imgRaw
                          : asset(ltrim($imgRaw, '/'));
                  }
                  $bookingRef = '#INK-' . str_pad((string) $booking->id, 6, '0', STR_PAD_LEFT);
                  $dateLong = $booking->booking_date?->format('l, F j, Y') ?? '—';
                  $clientEmail = (string) ($booking->user?->email ?? '');
                  $questionsAnswersData = is_array($booking->questions_answers ?? null) ? $booking->questions_answers : [];
              @endphp
              <div class="booking-group p-5 space-y-2"
                   data-booking-row
                   data-booking-id="{{ $booking->id }}"
                   data-status="{{ $stKey }}"
                   data-client="{{ e($clientLower) }}"
                   data-date="{{ $sessionDate }}"
                   data-sort-ts="{{ $sortStamp }}"
                   data-sort-name="{{ e($clientLower) }}">
                <div class="flex justify-between gap-3">
                  <p class="font-semibold text-on-surface">{{ $clientName }}</p>
                  <span class="inline-flex shrink-0 text-xs font-semibold px-2.5 py-1 rounded-full ring-1 ring-inset {{ $badgeCls }}">{{ $label }}</span>
                </div>
                @if ($artistRequestPending)
                  <p class="text-xs font-semibold text-blue-700">Reschedule request sent{{ $booking->reschedule_reason ? ': '.Str::limit($booking->reschedule_reason, 80) : '.' }}</p>
                @endif
                <p class="text-sm text-on-surface">{{ Str::limit($serviceTitle, 80) }}</p>
                <p class="text-sm text-on-surface-variant">{{ $booking->booking_date?->format('l, F j, Y') ?? '—' }} · {{ $startEnd }}</p>
                <p class="text-xs text-on-surface-variant">{{ $duration }} · {{ strtoupper(str_replace('/', ' ', $tz)) }}</p>
                <div class="flex flex-wrap items-center gap-2 pt-1">
                  <button type="button"
                    class="js-artist-booking-view inline-flex h-10 w-10 items-center justify-center rounded-xl border border-outline-variant/25 text-on-surface-variant hover:bg-surface-container-low"
                    title="View booking"
                    aria-haspopup="dialog"
                    aria-controls="artistBookingDetailModal"
                    data-booking-ref="{{ e($bookingRef) }}"
                    data-client-name="{{ e($clientName) }}"
                    data-client-email="{{ e($clientEmail) }}"
                    data-service="{{ e($serviceTitle) }}"
                    data-date-display="{{ e($dateLong) }}"
                    data-time-range="{{ e($startEnd) }}"
                    data-duration="{{ e($duration) }}"
                    data-timezone="{{ e($booking->timezone ?: 'UTC') }}"
                    data-status-label="{{ e($label) }}"
                    data-status-badge-class="{{ e($badgeCls) }}"
                    data-booking-type="{{ e(ucfirst((string) ($booking->booking_type ?? ''))) }}"
                    data-deposit="{{ e('€' . number_format((float) ($booking->deposit_amount ?? 0), 2)) }}"
                    data-questions='@json($questionsAnswersData)'
                    data-design-image="{{ e($designImage) }}">
                    <span class="material-symbols-outlined text-[22px]">visibility</span>
                  </button>
                  @if($booking->status === 'confirmed' || $artistRequestPending)
                    @if(!$artistRequestPending)
                    <button type="button"
                      class="js-artist-reschedule-request inline-flex h-10 w-10 items-center justify-center rounded-xl border border-outline-variant/25 text-on-surface-variant hover:bg-surface-container-low hover:text-primary disabled:opacity-50 disabled:cursor-not-allowed"
                      title="{{ $artistRequestPending ? 'Reschedule request pending' : 'Request reschedule from user' }}"
                      data-booking-id="{{ $booking->id }}"
                      data-request-url="{{ route('api.bookings.artist-request-reschedule', $booking->id) }}"
                      data-pending="{{ $artistRequestPending ? '1' : '0' }}"
                      data-client-name="{{ e($clientName) }}"
                      data-service="{{ e($serviceTitle) }}"
                      {{ $artistRequestPending ? 'disabled' : '' }}>
                      <span class="material-symbols-outlined text-[22px]">event_repeat</span>
                    </button>
                    @endif
                    @if($booking->status === 'confirmed')
                    <button type="button"
                      class="js-artist-mark-complete inline-flex h-10 w-10 items-center justify-center rounded-xl border border-emerald-200 text-emerald-700 hover:bg-emerald-50"
                      title="Mark completed"
                      data-booking-id="{{ $booking->id }}"
                      data-client-name="{{ e($clientName) }}"
                      data-service="{{ e($serviceTitle) }}"
                      data-mark-completed-url="{{ route('api.bookings.mark-completed', $booking->id) }}">
                      <span class="material-symbols-outlined text-[22px]">task_alt</span>
                    </button>
                    @endif
                    <button type="button"
                      class="js-artist-cancel-booking inline-flex h-10 w-10 items-center justify-center rounded-xl border border-error/25 text-error/90 hover:bg-error-container"
                      title="Cancel booking"
                      data-booking-id="{{ $booking->id }}"
                      data-cancel-url="{{ route('api.bookings.cancel', $booking->id) }}"
                      data-cancel-info-url="{{ route('api.bookings.cancellation-info', $booking->id) }}">
                      <span class="material-symbols-outlined text-[22px]">close</span>
                    </button>
                  @endif
                </div>
              </div>
            @endforeach
          </div>
        </div>

        <div class="flex flex-col sm:flex-row items-center justify-between gap-4">
          @if(method_exists($bookings, 'links'))
            {{ $bookings->links() }}
          @endif
        </div>
        <p id="artistBookingsFilteredEmpty" class="hidden mt-4 text-center text-sm text-on-surface-variant">
          No bookings match your filters on this page. Try resetting filters or check another page.
        </p>
      @endif

    </div>
</main>

<div id="artistBookingDetailModal"
  class="artist-bdm fixed inset-0 z-[100] flex items-end sm:items-center justify-center p-4 sm:p-6"
  aria-hidden="true"
  aria-modal="true"
  role="dialog"
  aria-labelledby="abdmTitle">
  <div class="artist-bdm-backdrop absolute inset-0 bg-black/45" data-close-artist-bdm></div>
  <div class="artist-bdm-panel relative w-full max-w-lg max-h-[min(90vh,640px)] overflow-y-auto rounded-2xl bg-white shadow-xl border border-outline-variant/20">
    <div class="sticky top-0 flex items-center justify-between gap-3 px-5 py-4 border-b border-outline-variant/15 bg-white/95 backdrop-blur-sm rounded-t-2xl z-10">
      <h3 id="abdmTitle" class="text-lg font-bold text-on-surface truncate pr-2">—</h3>
      <button type="button" class="shrink-0 inline-flex h-10 w-10 items-center justify-center rounded-xl text-on-surface-variant hover:bg-surface-container-low" data-close-artist-bdm aria-label="Close">
        <span class="material-symbols-outlined text-[22px]">close</span>
      </button>
    </div>
    <div class="p-5 space-y-5">
      <div class="flex gap-4">
        <div class="shrink-0 w-24 h-24 rounded-xl overflow-hidden bg-surface-container-low border border-outline-variant/20">
          <img id="abdmImage" src="" alt="" class="w-full h-full object-cover" width="96" height="96">
        </div>
        <div class="min-w-0 flex-1 space-y-2">
          <p id="abdmRef" class="text-xs font-semibold text-on-surface-variant tabular-nums">—</p>
          <p class="flex flex-wrap items-center gap-2">
            <span id="abdmStatus" class="inline-flex text-xs font-semibold px-2.5 py-1 rounded-full ring-1 ring-inset">—</span>
          </p>
          <p id="abdmType" class="text-sm text-on-surface-variant">—</p>
        </div>
      </div>
      <dl class="grid grid-cols-1 gap-3 text-sm">
        <div class="flex justify-between gap-4 py-2 border-b border-outline-variant/10">
          <dt class="text-on-surface-variant font-medium">Client</dt>
          <dd id="abdmClientName" class="text-on-surface font-semibold text-right">—</dd>
        </div>
        <div id="abdmClientEmailRow" class="flex justify-between gap-4 py-2 border-b border-outline-variant/10">
          <dt class="text-on-surface-variant font-medium">Email</dt>
          <dd id="abdmClientEmail" class="text-on-surface text-right break-all">—</dd>
        </div>
        <div class="flex justify-between gap-4 py-2 border-b border-outline-variant/10">
          <dt class="text-on-surface-variant font-medium">Service</dt>
          <dd id="abdmService" class="text-on-surface text-right">—</dd>
        </div>
        <div class="flex justify-between gap-4 py-2 border-b border-outline-variant/10">
          <dt class="text-on-surface-variant font-medium">Date</dt>
          <dd id="abdmDate" class="text-on-surface text-right">—</dd>
        </div>
        <div class="flex justify-between gap-4 py-2 border-b border-outline-variant/10">
          <dt class="text-on-surface-variant font-medium">Time</dt>
          <dd id="abdmTime" class="text-on-surface text-right">—</dd>
        </div>
        <div class="flex justify-between gap-4 py-2 border-b border-outline-variant/10">
          <dt class="text-on-surface-variant font-medium">Duration</dt>
          <dd id="abdmDuration" class="text-on-surface text-right tabular-nums">—</dd>
        </div>
        <div class="flex justify-between gap-4 py-2 border-b border-outline-variant/10">
          <dt class="text-on-surface-variant font-medium">Timezone</dt>
          <dd id="abdmTimezone" class="text-on-surface text-right text-xs">—</dd>
        </div>
        <div class="flex justify-between gap-4 py-2">
          <dt class="text-on-surface-variant font-medium">Deposit</dt>
          <dd id="abdmDeposit" class="text-on-surface font-semibold text-right tabular-nums">—</dd>
        </div>
      </dl>
      <div id="abdmQaSection" class="hidden rounded-xl border border-outline-variant/20 bg-surface-container-low/40 p-4">
        <p class="text-xs font-semibold uppercase tracking-wide text-on-surface-variant mb-3">Client answers</p>
        <div id="abdmQaList" class="space-y-3 text-sm"></div>
      </div>
    </div>
        </div>
      </div>

<div id="artistCancelBookingModal"
  class="artist-cbm fixed inset-0 z-[110] flex items-end sm:items-center justify-center p-4 sm:p-6"
  aria-hidden="true"
  aria-modal="true"
  role="dialog"
  aria-labelledby="acbmTitle">
  <div class="artist-cbm-backdrop absolute inset-0 bg-black/45" data-close-artist-cbm></div>
  <div class="artist-cbm-panel relative w-full max-w-lg rounded-2xl bg-white shadow-xl border border-outline-variant/20">
    <div class="px-5 py-4 border-b border-outline-variant/15 flex items-center justify-between">
      <h3 id="acbmTitle" class="text-lg font-bold text-on-surface">Cancel booking</h3>
      <button type="button" class="inline-flex h-10 w-10 items-center justify-center rounded-xl text-on-surface-variant hover:bg-surface-container-low" data-close-artist-cbm aria-label="Close">
        <span class="material-symbols-outlined text-[22px]">close</span>
      </button>
    </div>
    <div class="p-5 space-y-4">
      <div class="rounded-xl border border-green-200 bg-green-50/80 p-4 text-sm">
        <p id="acbmRefundLead" class="font-semibold text-green-900">Full refund will be given to the user.</p>
        <p id="acbmRefundSub" class="text-green-900/80 mt-1">Refund amount: €0.00</p>
      </div>
      <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 text-sm">
        <div class="rounded-lg bg-surface-container-low px-3 py-2">
          <p class="text-on-surface-variant text-xs">Booking ID</p>
          <p id="acbmBookingId" class="font-semibold text-on-surface">—</p>
        </div>
        <div class="rounded-lg bg-surface-container-low px-3 py-2">
          <p class="text-on-surface-variant text-xs">Cancellation window</p>
          <p id="acbmWindow" class="font-semibold text-on-surface">—</p>
        </div>
      </div>
      <div>
        <label for="acbmReason" class="block text-sm font-semibold text-on-surface mb-1.5">Reason for cancellation <span class="text-error">*</span></label>
        <textarea id="acbmReason" rows="4" maxlength="1000"
          class="w-full text-sm border border-outline-variant/30 rounded-xl px-3 py-2 bg-white text-on-surface focus:outline-none focus:ring-2 focus:ring-primary/30"
          placeholder="Please tell the client why this booking is being cancelled..."></textarea>
        <p id="acbmReasonError" class="hidden text-xs font-semibold text-error mt-1.5">Reason is required.</p>
      </div>
      <p id="acbmError" class="hidden text-xs font-semibold text-error"></p>
      <div class="flex items-center justify-end gap-2 pt-1">
        <button type="button" class="px-4 py-2.5 rounded-xl text-sm font-semibold border border-outline-variant/40 text-on-surface hover:bg-surface-container-low" data-close-artist-cbm>Back</button>
        <button type="button" id="acbmSubmit" class="px-4 py-2.5 rounded-xl text-sm font-semibold bg-error text-white hover:opacity-95 disabled:opacity-60 disabled:cursor-not-allowed">Confirm cancellation</button>
      </div>
    </div>
        </div>
      </div>

<div id="artistRescheduleRequestModal"
  class="artist-rsm fixed inset-0 z-[115] flex items-end sm:items-center justify-center p-4 sm:p-6"
  aria-hidden="true"
  aria-modal="true"
  role="dialog"
  aria-labelledby="arsmTitle">
  <div class="artist-rsm-backdrop absolute inset-0 bg-black/45" data-close-artist-rsm></div>
  <div class="artist-rsm-panel relative w-full max-w-lg rounded-2xl bg-white shadow-xl border border-outline-variant/20">
    <div class="px-5 py-4 border-b border-outline-variant/15 flex items-center justify-between">
      <h3 id="arsmTitle" class="text-lg font-bold text-on-surface">Request reschedule</h3>
      <button type="button" class="inline-flex h-10 w-10 items-center justify-center rounded-xl text-on-surface-variant hover:bg-surface-container-low" data-close-artist-rsm aria-label="Close">
        <span class="material-symbols-outlined text-[22px]">close</span>
      </button>
    </div>
    <div class="p-5 space-y-4">
      <p class="text-sm text-on-surface-variant">
        Send a reschedule request to <strong id="arsmClient" class="text-on-surface">the client</strong>.
        The client will choose a new date and time from their dashboard.
      </p>
      <div class="rounded-lg bg-surface-container-low px-3 py-2 text-sm">
        <p class="text-on-surface-variant text-xs">Booking</p>
        <p id="arsmService" class="font-semibold text-on-surface">—</p>
      </div>
      <div>
        <label for="arsmReason" class="block text-sm font-semibold text-on-surface mb-1.5">Reason <span class="text-error">*</span></label>
        <textarea id="arsmReason" rows="4" maxlength="1000"
          class="w-full text-sm border border-outline-variant/30 rounded-xl px-3 py-2 bg-white text-on-surface focus:outline-none focus:ring-2 focus:ring-primary/30"
          placeholder="Example: Unexpected emergency at the studio, please choose a new slot."></textarea>
      </div>
      <p id="arsmError" class="hidden text-xs font-semibold text-error"></p>
      <div class="flex items-center justify-end gap-2 pt-1">
        <button type="button" class="px-4 py-2.5 rounded-xl text-sm font-semibold border border-outline-variant/40 text-on-surface hover:bg-surface-container-low" data-close-artist-rsm>Back</button>
        <button type="button" id="arsmSubmit" class="px-4 py-2.5 rounded-xl text-sm font-semibold bg-primary text-on-primary hover:opacity-95 disabled:opacity-60 disabled:cursor-not-allowed">Send request</button>
      </div>
    </div>
  </div>
</div>

<div id="artistMarkCompleteModal"
  class="artist-complete-modal fixed inset-0 z-[116] flex items-end sm:items-center justify-center p-4 sm:p-6"
  aria-hidden="true"
  aria-modal="true"
  role="dialog"
  aria-labelledby="amcTitle">
  <div class="artist-complete-backdrop absolute inset-0 bg-black/45" data-close-artist-complete></div>
  <div class="artist-complete-panel relative w-full max-w-lg rounded-2xl bg-white shadow-xl border border-outline-variant/20">
    <div class="px-5 py-4 border-b border-outline-variant/15 flex items-center justify-between">
      <h3 id="amcTitle" class="text-lg font-bold text-on-surface">Mark Booking Completed</h3>
      <button type="button" class="inline-flex h-10 w-10 items-center justify-center rounded-xl text-on-surface-variant hover:bg-surface-container-low" data-close-artist-complete aria-label="Close">
        <span class="material-symbols-outlined text-[22px]">close</span>
      </button>
    </div>
    <div class="p-5 space-y-4">
      <p class="text-sm text-on-surface-variant">
        Ask <strong id="amcClient" class="text-on-surface">the client</strong> for their completion code (shared in booking confirmation email) and enter it below.
      </p>
      <div class="rounded-lg bg-surface-container-low px-3 py-2 text-sm">
        <p class="text-on-surface-variant text-xs">Booking</p>
        <p id="amcService" class="font-semibold text-on-surface">—</p>
      </div>
      <div>
        <label for="amcCode" class="block text-sm font-semibold text-on-surface mb-1.5">Completion code <span class="text-error">*</span></label>
        <input id="amcCode" type="text" maxlength="32" autocomplete="off"
          class="w-full text-sm border border-outline-variant/30 rounded-xl px-3 py-2 bg-white text-on-surface focus:outline-none focus:ring-2 focus:ring-primary/30"
          placeholder="Enter code from client">
      </div>
      <p id="amcError" class="hidden text-xs font-semibold text-error"></p>
      <div class="flex items-center justify-end gap-2 pt-1">
        <button type="button" class="px-4 py-2.5 rounded-xl text-sm font-semibold border border-outline-variant/40 text-on-surface hover:bg-surface-container-low" data-close-artist-complete>Back</button>
        <button type="button" id="amcSubmit" class="px-4 py-2.5 rounded-xl text-sm font-semibold bg-emerald-700 text-white hover:opacity-95 disabled:opacity-60 disabled:cursor-not-allowed">Confirm completed</button>
      </div>
    </div>
  </div>
</div>
@endsection

@section('scripts')
<script>
(function () {
  var tableBody = document.getElementById('bookingsTableBody');
  var mobileWrap = document.getElementById('bookingsMobile');
  var pills = document.querySelectorAll('.js-filter-status');
  var sortBy = document.getElementById('sortBy');
  var dateFrom = document.getElementById('dateFrom');
  var dateTo = document.getElementById('dateTo');
  var searchClient = document.getElementById('searchClient');
  var emptyHint = document.getElementById('artistBookingsFilteredEmpty');
  var statusFilter = 'all';

  function getRows() {
    return document.querySelectorAll('[data-booking-row].booking-group');
  }

  function setPillUi(activeBtn) {
    pills.forEach(function (p) {
      p.classList.remove('active', 'ring-2', 'ring-primary/30');
      p.classList.add('text-on-surface-variant');
    });
    if (activeBtn) {
      activeBtn.classList.add('active', 'ring-2', 'ring-primary/30');
      activeBtn.classList.remove('text-on-surface-variant');
    }
  }

  pills.forEach(function (btn) {
    btn.addEventListener('click', function () {
      statusFilter = btn.getAttribute('data-filter-status') || 'all';
      setPillUi(btn);
      applyFiltersAndSort();
    });
  });

  function rowMatches(el) {
    var st = (el.getAttribute('data-status') || '').toLowerCase();
    var client = (el.getAttribute('data-client') || '').toLowerCase();
    var d = el.getAttribute('data-date') || '';
    var q = (searchClient && searchClient.value || '').trim().toLowerCase();
    var df = dateFrom && dateFrom.value;
    var dt = dateTo && dateTo.value;

    if (statusFilter !== 'all' && st !== statusFilter) return false;
    if (df && (!d || d < df)) return false;
    if (dt && (!d || d > dt)) return false;
    if (q && client.indexOf(q) === -1) return false;
    return true;
  }

  function applyFiltersOnly() {
    var any = false;
    getRows().forEach(function (el) {
      var ok = rowMatches(el);
      el.classList.toggle('hidden', !ok);
      if (ok) any = true;
    });
    if (emptyHint) emptyHint.classList.toggle('hidden', any);
  }

  function reorderBody(container, rows) {
    if (!container) return;
    rows.forEach(function (tr) {
      container.appendChild(tr);
    });
  }

  function applySort() {
    if (!sortBy) return applyFiltersOnly();
    var mode = sortBy.value || 'recent';
    var tbody = tableBody;
    var mobile = mobileWrap;
    var deskRows = tbody ? Array.prototype.slice.call(tbody.querySelectorAll('tr.booking-group')) : [];
    var mobRows = mobile ? Array.prototype.slice.call(mobile.querySelectorAll('.booking-group')) : [];

    function cmp(a, b) {
      if (mode === 'name' || mode === 'name-desc') {
        var an = (a.getAttribute('data-sort-name') || '').toLowerCase();
        var bn = (b.getAttribute('data-sort-name') || '').toLowerCase();
        return mode === 'name' ? an.localeCompare(bn) : bn.localeCompare(an);
      }
      var at = parseInt(a.getAttribute('data-sort-ts') || '0', 10);
      var bt = parseInt(b.getAttribute('data-sort-ts') || '0', 10);
      return mode === 'oldest' ? at - bt : bt - at;
    }

    deskRows.sort(cmp);
    mobRows.sort(cmp);
    reorderBody(tbody, deskRows);
    reorderBody(mobile, mobRows);

    applyFiltersOnly();
  }

  function applyFiltersAndSort() {
    applySort();
  }

  if (sortBy) sortBy.addEventListener('change', applyFiltersAndSort);
  if (dateFrom) dateFrom.addEventListener('change', applyFiltersAndSort);
  if (dateTo) dateTo.addEventListener('change', applyFiltersAndSort);
  if (searchClient) searchClient.addEventListener('input', applyFiltersAndSort);

  applyFiltersOnly();
})();
</script>
<script>
(function () {
  var modal = document.getElementById('artistRescheduleRequestModal');
  if (!modal) return;

  var clientEl = document.getElementById('arsmClient');
  var serviceEl = document.getElementById('arsmService');
  var reasonEl = document.getElementById('arsmReason');
  var errorEl = document.getElementById('arsmError');
  var submitBtn = document.getElementById('arsmSubmit');
  var activeBtn = null;

  function csrfToken() {
    var m = document.querySelector('meta[name="csrf-token"]');
    return m ? m.getAttribute('content') : '';
  }

  function openModal(btn) {
    activeBtn = btn;
    if (clientEl) clientEl.textContent = btn.getAttribute('data-client-name') || 'the client';
    if (serviceEl) serviceEl.textContent = btn.getAttribute('data-service') || 'Booking';
    if (reasonEl) reasonEl.value = '';
    if (errorEl) {
      errorEl.classList.add('hidden');
      errorEl.textContent = '';
    }
    modal.setAttribute('aria-hidden', 'false');
    document.body.style.overflow = 'hidden';
    requestAnimationFrame(function () {
      modal.classList.add('artist-rsm-open');
    });
  }

  function closeModal() {
    modal.classList.remove('artist-rsm-open');
    modal.setAttribute('aria-hidden', 'true');
    document.body.style.overflow = '';
    activeBtn = null;
  }

  function submitRequest() {
    if (!activeBtn) return;
    var url = activeBtn.getAttribute('data-request-url');
    if (!url) return;
    var reason = reasonEl ? reasonEl.value.trim() : '';
    if (reason.length < 3) {
      if (errorEl) {
        errorEl.textContent = 'Reason is required (minimum 3 characters).';
        errorEl.classList.remove('hidden');
      }
      return;
    }
    submitBtn.disabled = true;
    fetch(url, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json',
        'X-CSRF-TOKEN': csrfToken(),
        'X-Requested-With': 'XMLHttpRequest',
      },
      body: JSON.stringify({ reason: reason }),
    })
      .then(function (r) {
        return r.text().then(function (body) {
          var data = {};
          try {
            data = body ? JSON.parse(body) : {};
          } catch (e) {
            data = { message: body ? body.slice(0, 240) : 'HTTP ' + r.status };
          }
          return { ok: r.ok, data: data };
        });
      })
      .then(function (res) {
        if (res.ok && res.data && res.data.success) {
          window.location.reload();
          return;
        }
        if (errorEl) {
          errorEl.textContent = (res.data && res.data.message) || 'Could not send request.';
          errorEl.classList.remove('hidden');
        }
      })
      .catch(function () {
        if (errorEl) {
          errorEl.textContent = 'Network error. Try again.';
          errorEl.classList.remove('hidden');
        }
      })
      .finally(function () {
        submitBtn.disabled = false;
      });
  }

  document.querySelectorAll('.js-artist-reschedule-request').forEach(function (btn) {
    btn.addEventListener('click', function () {
      if (btn.getAttribute('data-pending') === '1' || btn.disabled) return;
      openModal(btn);
    });
  });

  modal.querySelectorAll('[data-close-artist-rsm]').forEach(function (el) {
    el.addEventListener('click', closeModal);
  });
  if (submitBtn) submitBtn.addEventListener('click', submitRequest);

  document.addEventListener('keydown', function (e) {
    if (e.key === 'Escape' && modal.classList.contains('artist-rsm-open')) {
      closeModal();
    }
  });
})();
</script>
<script>
(function () {
  var modal = document.getElementById('artistMarkCompleteModal');
  if (!modal) return;

  var clientEl = document.getElementById('amcClient');
  var serviceEl = document.getElementById('amcService');
  var codeEl = document.getElementById('amcCode');
  var errorEl = document.getElementById('amcError');
  var submitBtn = document.getElementById('amcSubmit');
  var activeBtn = null;

  function csrfToken() {
    var m = document.querySelector('meta[name="csrf-token"]');
    return m ? m.getAttribute('content') : '';
  }

  function openModal(btn) {
    activeBtn = btn;
    if (clientEl) clientEl.textContent = btn.getAttribute('data-client-name') || 'the client';
    if (serviceEl) serviceEl.textContent = btn.getAttribute('data-service') || 'Booking';
    if (codeEl) codeEl.value = '';
    if (errorEl) {
      errorEl.classList.add('hidden');
      errorEl.textContent = '';
    }
    modal.setAttribute('aria-hidden', 'false');
    document.body.style.overflow = 'hidden';
    requestAnimationFrame(function () {
      modal.classList.add('artist-complete-open');
    });
  }

  function closeModal() {
    modal.classList.remove('artist-complete-open');
    modal.setAttribute('aria-hidden', 'true');
    document.body.style.overflow = '';
    activeBtn = null;
  }

  function showError(msg) {
    if (!errorEl) return;
    errorEl.textContent = msg || 'Something went wrong.';
    errorEl.classList.remove('hidden');
  }

  function submitComplete() {
    if (!activeBtn) return;
    var url = activeBtn.getAttribute('data-mark-completed-url');
    var code = codeEl ? codeEl.value.trim() : '';
    if (!url) return;
    if (!code) {
      showError('Completion code is required.');
      return;
    }
    if (errorEl) errorEl.classList.add('hidden');
    submitBtn.disabled = true;
    fetch(url, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json',
        'X-CSRF-TOKEN': csrfToken(),
        'X-Requested-With': 'XMLHttpRequest',
      },
      body: JSON.stringify({ completion_code: code, confirmed: true }),
    })
      .then(function (r) {
        return r.text().then(function (body) {
          var data = {};
          try { data = body ? JSON.parse(body) : {}; } catch (e) { data = { message: body ? body.slice(0, 240) : 'HTTP ' + r.status }; }
          return { ok: r.ok, data: data };
        });
      })
      .then(function (res) {
        if (res.ok && res.data && res.data.success) {
          window.location.reload();
          return;
        }
        showError((res.data && res.data.message) || 'Could not mark completed.');
      })
      .catch(function () {
        showError('Network error. Try again.');
      })
      .finally(function () {
        submitBtn.disabled = false;
      });
  }

  document.querySelectorAll('.js-artist-mark-complete').forEach(function (btn) {
    btn.addEventListener('click', function () {
      openModal(btn);
    });
  });

  modal.querySelectorAll('[data-close-artist-complete]').forEach(function (el) {
    el.addEventListener('click', closeModal);
  });
  if (submitBtn) submitBtn.addEventListener('click', submitComplete);
  if (codeEl) {
    codeEl.addEventListener('input', function () {
      if (errorEl && codeEl.value.trim()) errorEl.classList.add('hidden');
    });
  }

  document.addEventListener('keydown', function (e) {
    if (e.key === 'Escape' && modal.classList.contains('artist-complete-open')) {
      closeModal();
    }
  });
})();
</script>
<script>
(function () {
  var modal = document.getElementById('artistCancelBookingModal');
  if (!modal) return;

  var bookingIdEl = document.getElementById('acbmBookingId');
  var windowEl = document.getElementById('acbmWindow');
  var refundLeadEl = document.getElementById('acbmRefundLead');
  var refundSubEl = document.getElementById('acbmRefundSub');
  var reasonEl = document.getElementById('acbmReason');
  var reasonErrorEl = document.getElementById('acbmReasonError');
  var errorEl = document.getElementById('acbmError');
  var submitBtn = document.getElementById('acbmSubmit');
  var active = null;

  function csrfToken() {
    var m = document.querySelector('meta[name="csrf-token"]');
    return m ? m.getAttribute('content') : '';
  }

  function fmtMoney(n, currency) {
    var v = parseFloat(n);
    if (isNaN(v)) v = 0;
    var cc = (currency || 'EUR').toUpperCase();
    var symbol = cc === 'EUR' ? '€' : (cc === 'USD' ? '$' : cc + ' ');
    return symbol + v.toFixed(2);
  }

  function showError(msg) {
    if (!errorEl) return;
    errorEl.textContent = msg || 'Could not load cancellation info.';
    errorEl.classList.remove('hidden');
  }

  function clearError() {
    if (!errorEl) return;
    errorEl.classList.add('hidden');
    errorEl.textContent = '';
  }

  function openModal() {
    modal.setAttribute('aria-hidden', 'false');
    document.body.style.overflow = 'hidden';
    requestAnimationFrame(function () {
      modal.classList.add('artist-cbm-open');
    });
  }

  function closeModal() {
    modal.classList.remove('artist-cbm-open');
    modal.setAttribute('aria-hidden', 'true');
    document.body.style.overflow = '';
    active = null;
    clearError();
    if (reasonEl) reasonEl.value = '';
    if (reasonErrorEl) reasonErrorEl.classList.add('hidden');
  }

  function setRefundUi(data) {
    var estimated = data && data.estimated_refund ? data.estimated_refund : {};
    var amount = parseFloat(estimated.amount || 0);
    var forfeited = parseFloat(estimated.deposit_forfeited || 0);
    var currency = data && data.currency ? data.currency : 'EUR';
    var eligibility = (data && data.refund_eligibility) ? data.refund_eligibility : '';
    if (eligibility === 'full_refund') {
      refundLeadEl.textContent = 'Full refund will be given to the user.';
      refundSubEl.textContent = 'Refund amount: ' + fmtMoney(amount, currency);
    } else if (eligibility === 'partial_refund') {
      refundLeadEl.textContent = 'A partial refund will be given to the user.';
      refundSubEl.textContent = 'Refund amount: ' + fmtMoney(amount, currency) + ' (deposit forfeited: ' + fmtMoney(forfeited, currency) + ')';
    } else {
      refundLeadEl.textContent = 'No refund will be given to the user.';
      refundSubEl.textContent = 'Deposit forfeited: ' + fmtMoney(forfeited, currency);
    }
  }

  function loadInfoAndOpen(btn) {
    var infoUrl = btn.getAttribute('data-cancel-info-url');
    var bookingId = btn.getAttribute('data-booking-id') || '—';
    if (bookingIdEl) bookingIdEl.textContent = '#INK-' + String(bookingId).padStart(6, '0');
    if (windowEl) windowEl.textContent = 'Loading...';
    if (refundLeadEl) refundLeadEl.textContent = 'Loading refund details...';
    if (refundSubEl) refundSubEl.textContent = '';
    clearError();
    openModal();
    if (!infoUrl) {
      showError('Cancellation info URL is missing.');
      return;
    }
    fetch(infoUrl, {
      method: 'GET',
      headers: {
        'Accept': 'application/json',
        'X-Requested-With': 'XMLHttpRequest',
      },
    })
      .then(function (r) {
        return r.json().then(function (data) {
          return { ok: r.ok, data: data };
        });
      })
      .then(function (res) {
        if (!(res.ok && res.data && res.data.success && res.data.data)) {
          throw new Error((res.data && res.data.message) || 'Could not load cancellation info.');
        }
        var info = res.data.data;
        var w = parseInt(info.cancellation_window_hours || 0, 10);
        if (windowEl) windowEl.textContent = (w > 0 ? (w + ' hours') : '—');
        setRefundUi(info);
      })
      .catch(function (err) {
        if (windowEl) windowEl.textContent = '—';
        if (refundLeadEl) refundLeadEl.textContent = 'Could not load refund details.';
        if (refundSubEl) refundSubEl.textContent = '';
        showError(err && err.message ? err.message : 'Network error while loading details.');
      });
  }

  function submitCancellation() {
    if (!active) return;
    var reason = reasonEl ? reasonEl.value.trim() : '';
    if (!reason) {
      if (reasonErrorEl) reasonErrorEl.classList.remove('hidden');
      return;
    }
    if (reasonErrorEl) reasonErrorEl.classList.add('hidden');
    clearError();
    submitBtn.disabled = true;
    fetch(active.getAttribute('data-cancel-url'), {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json',
        'X-CSRF-TOKEN': csrfToken(),
        'X-Requested-With': 'XMLHttpRequest',
      },
      body: JSON.stringify({ confirmed: true, reason: reason }),
    })
      .then(function (r) {
        return r.text().then(function (body) {
          var data = {};
          try {
            data = body ? JSON.parse(body) : {};
          } catch (e) {
            data = { message: body ? body.slice(0, 240) : 'HTTP ' + r.status };
          }
          return { ok: r.ok, data: data };
        });
      })
      .then(function (res) {
        if (res.ok && res.data && res.data.success) {
          window.location.reload();
          return;
        }
        showError((res.data && (res.data.message || (res.data.errors && JSON.stringify(res.data.errors)))) || 'Could not cancel.');
      })
      .catch(function () {
        showError('Network error. Try again.');
      })
      .finally(function () {
        submitBtn.disabled = false;
      });
  }

  document.querySelectorAll('.js-artist-cancel-booking').forEach(function (btn) {
    btn.addEventListener('click', function () {
      active = btn;
      loadInfoAndOpen(btn);
    });
  });

  modal.querySelectorAll('[data-close-artist-cbm]').forEach(function (el) {
    el.addEventListener('click', closeModal);
  });

  if (reasonEl) {
    reasonEl.addEventListener('input', function () {
      if (reasonErrorEl && reasonEl.value.trim()) reasonErrorEl.classList.add('hidden');
    });
  }
  if (submitBtn) submitBtn.addEventListener('click', submitCancellation);

  document.addEventListener('keydown', function (e) {
    if (e.key === 'Escape' && modal.classList.contains('artist-cbm-open')) {
      closeModal();
    }
  });
})();
</script>
<script>
(function () {
  var modal = document.getElementById('artistBookingDetailModal');
  if (!modal) return;

  var titleEl = document.getElementById('abdmTitle');
  var imgEl = document.getElementById('abdmImage');
  var refEl = document.getElementById('abdmRef');
  var statusEl = document.getElementById('abdmStatus');
  var typeEl = document.getElementById('abdmType');
  var nameEl = document.getElementById('abdmClientName');
  var emailRow = document.getElementById('abdmClientEmailRow');
  var emailEl = document.getElementById('abdmClientEmail');
  var serviceEl = document.getElementById('abdmService');
  var dateEl = document.getElementById('abdmDate');
  var timeEl = document.getElementById('abdmTime');
  var durEl = document.getElementById('abdmDuration');
  var tzEl = document.getElementById('abdmTimezone');
  var depEl = document.getElementById('abdmDeposit');
  var qaSectionEl = document.getElementById('abdmQaSection');
  var qaListEl = document.getElementById('abdmQaList');

  function openModal(btn) {
    var ds = btn.dataset;
    if (titleEl) titleEl.textContent = ds.service || 'Booking';
    if (imgEl) {
      var fallback = '{{ e(asset('design/images/icons/avatar.jpg')) }}';
      imgEl.onerror = function () {
        imgEl.onerror = null;
        imgEl.src = fallback;
      };
      imgEl.src = ds.designImage || fallback;
      imgEl.alt = ds.service || '';
    }
    if (refEl) refEl.textContent = ds.bookingRef || '';
    if (statusEl) {
      statusEl.textContent = ds.statusLabel || '—';
      statusEl.className =
        'inline-flex text-xs font-semibold px-2.5 py-1 rounded-full ring-1 ring-inset ' +
        (ds.statusBadgeClass || 'bg-surface-container text-on-surface ring-outline-variant/20');
    }
    if (typeEl) typeEl.textContent = ds.bookingType ? ds.bookingType + ' booking' : '—';
    if (nameEl) nameEl.textContent = ds.clientName || '—';
    if (emailRow && emailEl) {
      var em = (ds.clientEmail || '').trim();
      if (em) {
        emailRow.classList.remove('hidden');
        emailEl.textContent = em;
      } else {
        emailRow.classList.add('hidden');
        emailEl.textContent = '';
      }
    }
    if (serviceEl) serviceEl.textContent = ds.service || '—';
    if (dateEl) dateEl.textContent = ds.dateDisplay || '—';
    if (timeEl) timeEl.textContent = ds.timeRange || '—';
    if (durEl) durEl.textContent = ds.duration || '—';
    if (tzEl) tzEl.textContent = ds.timezone || 'UTC';
    if (depEl) depEl.textContent = ds.deposit || '—';
    if (qaSectionEl && qaListEl) {
      qaListEl.innerHTML = '';
      var rawQuestions = ds.questions || '';
      var parsedQuestions = null;
      try {
        parsedQuestions = rawQuestions ? JSON.parse(rawQuestions) : null;
      } catch (e) {
        parsedQuestions = null;
      }
      var entries = parsedQuestions && typeof parsedQuestions === 'object'
        ? Object.entries(parsedQuestions)
        : [];
      if (!entries.length) {
        qaSectionEl.classList.add('hidden');
      } else {
        qaSectionEl.classList.remove('hidden');
        entries.forEach(function (pair) {
          var qKey = String(pair[0] || '').trim();
          var ans = pair[1];
          var answerPayload = (ans && typeof ans === 'object' && !Array.isArray(ans))
            ? ans
            : { answer: ans };
          var questionText = String(answerPayload.question || ('Question #' + (qKey || '—')));
          var answerType = String(answerPayload.type || '');
          var answerValue = answerPayload.answer;
          var answerText = Array.isArray(answerValue) ? answerValue.join(', ') : String(answerValue ?? '');
          var isImageAnswer = answerType === 'image'
            || /^https?:\/\//i.test(answerText)
            || answerText.indexOf('/uploads/') === 0;
          var item = document.createElement('div');
          item.className = 'border-b border-outline-variant/15 pb-2 last:border-b-0 last:pb-0';

          var q = document.createElement('p');
          q.className = 'text-xs font-semibold text-on-surface-variant';
          q.textContent = questionText;

          var a;
          if (isImageAnswer && answerText) {
            a = document.createElement('a');
            a.className = 'text-primary mt-1 inline-block break-all underline';
            a.href = answerText;
            a.target = '_blank';
            a.rel = 'noopener noreferrer';
            a.textContent = 'View uploaded image';
          } else {
            a = document.createElement('p');
            a.className = 'text-on-surface mt-1 break-words';
            a.textContent = answerText || '—';
          }

          item.appendChild(q);
          item.appendChild(a);
          qaListEl.appendChild(item);
        });
      }
    }

    modal.setAttribute('aria-hidden', 'false');
    document.body.style.overflow = 'hidden';
    requestAnimationFrame(function () {
      modal.classList.add('artist-bdm-open');
    });
  }

  function closeModal() {
    modal.classList.remove('artist-bdm-open');
    modal.setAttribute('aria-hidden', 'true');
    document.body.style.overflow = '';
  }

  document.querySelectorAll('.js-artist-booking-view').forEach(function (btn) {
    btn.addEventListener('click', function () {
      openModal(btn);
    });
  });

  modal.querySelectorAll('[data-close-artist-bdm]').forEach(function (el) {
    el.addEventListener('click', closeModal);
  });

  document.addEventListener('keydown', function (e) {
    if (e.key === 'Escape' && modal.classList.contains('artist-bdm-open')) {
      closeModal();
    }
  });
})();
</script>
@endsection
