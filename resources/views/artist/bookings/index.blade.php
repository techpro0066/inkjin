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
  .artist-rpm { pointer-events: none; opacity: 0; transition: opacity 0.28s cubic-bezier(0.22, 1, 0.36, 1); }
  .artist-rpm.artist-rpm-open { pointer-events: auto; opacity: 1; }
  .artist-rpm-backdrop { opacity: 0; transition: opacity 0.3s cubic-bezier(0.22, 1, 0.36, 1); }
  .artist-rpm.artist-rpm-open .artist-rpm-backdrop { opacity: 1; }
  .artist-rpm-panel { opacity: 0; transform: translateY(1rem) scale(0.97); transition: opacity 0.32s cubic-bezier(0.22, 1, 0.36, 1), transform 0.36s cubic-bezier(0.22, 1, 0.36, 1); }
  .artist-rpm.artist-rpm-open .artist-rpm-panel { opacity: 1; transform: translateY(0) scale(1); }
  .artist-rpm-backdrop, .artist-rpm-panel { pointer-events: none; }
  .artist-rpm.artist-rpm-open .artist-rpm-backdrop, .artist-rpm.artist-rpm-open .artist-rpm-panel { pointer-events: auto; }
  .artist-urm { pointer-events: none; opacity: 0; transition: opacity 0.28s cubic-bezier(0.22, 1, 0.36, 1); }
  .artist-urm.artist-urm-open { pointer-events: auto; opacity: 1; }
  .artist-urm-backdrop { opacity: 0; transition: opacity 0.3s cubic-bezier(0.22, 1, 0.36, 1); }
  .artist-urm.artist-urm-open .artist-urm-backdrop { opacity: 1; }
  .artist-urm-panel { opacity: 0; transform: translateY(1rem) scale(0.97); transition: opacity 0.32s cubic-bezier(0.22, 1, 0.36, 1), transform 0.36s cubic-bezier(0.22, 1, 0.36, 1); }
  .artist-urm.artist-urm-open .artist-urm-panel { opacity: 1; transform: translateY(0) scale(1); }
  .artist-urm-backdrop, .artist-urm-panel { pointer-events: none; }
  .artist-urm.artist-urm-open .artist-urm-backdrop, .artist-urm.artist-urm-open .artist-urm-panel { pointer-events: auto; }
  .rpm-option-dot { width: 18px; height: 18px; border-radius: 9999px; border: 2px solid #c9c4ce; flex-shrink: 0; display: inline-flex; align-items: center; justify-content: center; }
  .rpm-option-dot::after { content: ''; width: 10px; height: 10px; border-radius: 9999px; background: transparent; }
  .rpm-option[aria-checked="true"] .rpm-option-dot { border-color: #1b5e4a; }
  .rpm-option[aria-checked="true"] .rpm-option-dot::after { background: #1b5e4a; }
  .rpm-option[aria-checked="true"] .rpm-option-label { font-weight: 700; color: #1c1b21; }
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

      @include('artist.bookings.partials.page-tabs', ['activeTab' => 'bookings'])

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
                <th class="text-left px-6 py-3 font-semibold">Booking</th>
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
                      $bookingRef = $booking->referenceLabel();
                      $dateLong = $booking->booking_date?->format('l, F j, Y') ?? '—';
                      $clientEmail = (string) ($booking->user?->email ?? '');
                      $questionsAnswersData = is_array($booking->questions_answers ?? null) ? $booking->questions_answers : [];
                      $balanceLabel = $booking->estimatedBalanceLabel();
                      $balanceDue = $booking->remainingBalanceAmount();
                      $showBalanceDue = $balanceDue > 0
                          && ! $booking->remaining_amount_released
                          && ! $booking->full_amount_paid
                          && ! in_array($stKey, ['cancelled', 'completed', 'declined'], true);
                      $balanceDueRounded = round($balanceDue, 2);
                      $balanceDueLabel = fmod($balanceDueRounded, 1.0) === 0.0
                          ? '€'.(string) (int) $balanceDueRounded
                          : '€'.number_format($balanceDueRounded, 2, '.', '');
                      $paidRounded = round((float) ($booking->deposit_amount ?? 0), 2);
                      $paidLabel = fmod($paidRounded, 1.0) === 0.0
                          ? '€'.(string) (int) $paidRounded
                          : '€'.number_format($paidRounded, 2, '.', '');
                      $totalRounded = round($paidRounded + $balanceDue, 2);
                      $totalLabel = fmod($totalRounded, 1.0) === 0.0
                          ? '€'.(string) (int) $totalRounded
                          : '€'.number_format($totalRounded, 2, '.', '');
                      $unsettledCollection = $booking->latestBalanceCollection;
                      $isUnsettledReminder = $unsettledCollection && $unsettledCollection->isUnsettledOpen();
                      $expectedPhrase = $isUnsettledReminder ? $unsettledCollection->expectedDuePhrase() : '';
                      $collectBarLabel = $isUnsettledReminder
                          ? $balanceDueLabel.' due · '.$expectedPhrase
                          : $balanceDueLabel.' balance due';
                      $unsettledNote = $isUnsettledReminder ? trim((string) $unsettledCollection->note) : '';
                      $unsettledFirstName = trim((string) strtok($clientName, ' '));
                      $unsettledNudge = $isUnsettledReminder
                          ? (string) ($unsettledCollection->reminderNudge($unsettledFirstName, $balanceDueLabel) ?? '')
                          : '';
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
                    <td class="px-6 py-4 font-medium text-on-surface tabular-nums">{{ $bookingRef }}</td>
                    <td class="px-6 py-4 text-on-surface">{{ $booking->booking_date?->format('M j, Y') ?? '—' }}</td>
                    <td class="px-6 py-4 text-on-surface-variant">{{ $startEnd }}</td>
                    <td class="px-6 py-4 text-on-surface-variant tabular-nums">{{ $duration }}</td>
                    <td class="px-6 py-4">
                      <span class="js-artist-status-badge inline-flex items-center text-xs font-semibold px-2.5 py-1 rounded-full ring-1 ring-inset {{ $badgeCls }}">{{ $label }}</span>
                    </td>
                    <td class="px-6 py-4">
                      @if($showBalanceDue)
                        <div class="js-artist-collect-wrap flex items-center justify-between gap-3 rounded-xl bg-[#f4eee4] px-3 py-2 mb-2 min-w-[13.5rem]">
                          <p class="js-artist-collect-label text-sm font-bold text-[#8a5a12] whitespace-nowrap">{{ $collectBarLabel }}</p>
                          <button type="button"
                            class="js-artist-collect-payment text-sm font-bold text-[#1b5e4a] underline underline-offset-2 whitespace-nowrap"
                            aria-haspopup="dialog"
                            aria-controls="artistRequestPaymentModal"
                            data-booking-id="{{ $booking->id }}"
                            data-store-url="{{ route('api.bookings.balance-collections.store', $booking->id) }}"
                            data-service="{{ e($serviceTitle) }}"
                            data-client-name="{{ e($clientName) }}"
                            data-booking-ref="{{ e($bookingRef) }}"
                            data-balance-label="{{ e($balanceDueLabel) }}"
                            data-paid-label="{{ e($paidLabel) }}"
                            data-total-label="{{ e($totalLabel) }}"
                            data-date-display="{{ e($dateLong) }}"
                            data-time-range="{{ e($startEnd) }}"
                            data-status-label="{{ e($label) }}"
                            data-status-badge-class="{{ e($badgeCls) }}"
                            data-unsettled="{{ $isUnsettledReminder ? '1' : '0' }}"
                            data-expected-label="{{ e($expectedPhrase) }}"
                            data-unsettled-note="{{ e($unsettledNote) }}"
                            data-nudge="{{ e($unsettledNudge) }}">Collect</button>
                        </div>
                      @endif
                      <div class="js-artist-row-actions flex items-center gap-1">
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
                          data-balance="{{ e($balanceLabel) }}"
                          data-questions='@json($questionsAnswersData)'
                          data-design-image="{{ e($designImage) }}">
                          <span class="material-symbols-outlined text-[22px]">visibility</span>
                        </button>
                        @if($booking->isOpenForChat())
                          <a href="{{ route('artist.chat.index', ['client' => $booking->user_id, 'booking' => $booking->id]) }}"
                            class="inline-flex h-9 w-9 items-center justify-center rounded-lg border border-transparent text-on-surface-variant hover:bg-surface-container-low hover:text-primary"
                            title="Message client">
                            <span class="material-symbols-outlined text-[22px]">chat</span>
                          </a>
                        @endif
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
                            title="{{ $showBalanceDue ? 'Collect remaining balance' : 'Mark completed' }}"
                            data-booking-id="{{ $booking->id }}"
                            data-has-balance="{{ $showBalanceDue ? '1' : '0' }}"
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
                            data-cancel-info-url="{{ route('api.bookings.cancellation-info', $booking->id) }}"
                            data-booking-ref="{{ e($bookingRef) }}">
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
                  $bookingRef = $booking->referenceLabel();
                  $dateLong = $booking->booking_date?->format('l, F j, Y') ?? '—';
                  $clientEmail = (string) ($booking->user?->email ?? '');
                  $questionsAnswersData = is_array($booking->questions_answers ?? null) ? $booking->questions_answers : [];
                  $balanceLabel = $booking->estimatedBalanceLabel();
                  $balanceDue = $booking->remainingBalanceAmount();
                  $showBalanceDue = $balanceDue > 0
                      && ! $booking->remaining_amount_released
                      && ! $booking->full_amount_paid
                      && ! in_array($stKey, ['cancelled', 'completed', 'declined'], true);
                  $balanceDueRounded = round($balanceDue, 2);
                  $balanceDueLabel = fmod($balanceDueRounded, 1.0) === 0.0
                      ? '€'.(string) (int) $balanceDueRounded
                      : '€'.number_format($balanceDueRounded, 2, '.', '');
                  $paidRounded = round((float) ($booking->deposit_amount ?? 0), 2);
                  $paidLabel = fmod($paidRounded, 1.0) === 0.0
                      ? '€'.(string) (int) $paidRounded
                      : '€'.number_format($paidRounded, 2, '.', '');
                  $totalRounded = round($paidRounded + $balanceDue, 2);
                  $totalLabel = fmod($totalRounded, 1.0) === 0.0
                      ? '€'.(string) (int) $totalRounded
                      : '€'.number_format($totalRounded, 2, '.', '');
                  $unsettledCollection = $booking->latestBalanceCollection;
                  $isUnsettledReminder = $unsettledCollection && $unsettledCollection->isUnsettledOpen();
                  $expectedPhrase = $isUnsettledReminder ? $unsettledCollection->expectedDuePhrase() : '';
                  $collectBarLabel = $isUnsettledReminder
                      ? $balanceDueLabel.' due · '.$expectedPhrase
                      : $balanceDueLabel.' balance due';
                  $unsettledNote = $isUnsettledReminder ? trim((string) $unsettledCollection->note) : '';
                  $unsettledFirstName = trim((string) strtok($clientName, ' '));
                  $unsettledNudge = $isUnsettledReminder
                      ? (string) ($unsettledCollection->reminderNudge($unsettledFirstName, $balanceDueLabel) ?? '')
                      : '';
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
                  <span class="js-artist-status-badge inline-flex shrink-0 text-xs font-semibold px-2.5 py-1 rounded-full ring-1 ring-inset {{ $badgeCls }}">{{ $label }}</span>
                </div>
                @if ($artistRequestPending)
                  <p class="text-xs font-semibold text-blue-700">Reschedule request sent{{ $booking->reschedule_reason ? ': '.Str::limit($booking->reschedule_reason, 80) : '.' }}</p>
                @endif
                <p class="text-sm font-medium text-on-surface tabular-nums">{{ $bookingRef }}</p>
                <p class="text-sm text-on-surface-variant">{{ $booking->booking_date?->format('l, F j, Y') ?? '—' }} · {{ $startEnd }}</p>
                <p class="text-xs text-on-surface-variant">{{ $duration }} · {{ strtoupper(str_replace('/', ' ', $tz)) }}</p>
                @if($showBalanceDue)
                  <div class="js-artist-collect-wrap flex items-center justify-between gap-3 rounded-xl bg-[#f4eee4] px-3.5 py-2.5">
                    <p class="js-artist-collect-label text-sm font-bold text-[#8a5a12]">{{ $collectBarLabel }}</p>
                    <button type="button"
                      class="js-artist-collect-payment text-sm font-bold text-[#1b5e4a] underline underline-offset-2"
                      aria-haspopup="dialog"
                      aria-controls="artistRequestPaymentModal"
                      data-booking-id="{{ $booking->id }}"
                      data-store-url="{{ route('api.bookings.balance-collections.store', $booking->id) }}"
                      data-service="{{ e($serviceTitle) }}"
                      data-client-name="{{ e($clientName) }}"
                      data-booking-ref="{{ e($bookingRef) }}"
                      data-balance-label="{{ e($balanceDueLabel) }}"
                      data-paid-label="{{ e($paidLabel) }}"
                      data-total-label="{{ e($totalLabel) }}"
                      data-date-display="{{ e($dateLong) }}"
                      data-time-range="{{ e($startEnd) }}"
                      data-status-label="{{ e($label) }}"
                      data-status-badge-class="{{ e($badgeCls) }}"
                      data-unsettled="{{ $isUnsettledReminder ? '1' : '0' }}"
                      data-expected-label="{{ e($expectedPhrase) }}"
                      data-unsettled-note="{{ e($unsettledNote) }}"
                      data-nudge="{{ e($unsettledNudge) }}">Collect</button>
                  </div>
                @endif
                <div class="js-artist-row-actions flex flex-wrap items-center gap-2 pt-1">
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
                    data-balance="{{ e($balanceLabel) }}"
                    data-questions='@json($questionsAnswersData)'
                    data-design-image="{{ e($designImage) }}">
                    <span class="material-symbols-outlined text-[22px]">visibility</span>
                  </button>
                  @if($booking->isOpenForChat())
                    <a href="{{ route('artist.chat.index', ['client' => $booking->user_id, 'booking' => $booking->id]) }}"
                      class="inline-flex h-10 w-10 items-center justify-center rounded-xl border border-outline-variant/25 text-on-surface-variant hover:bg-surface-container-low hover:text-primary"
                      title="Message client">
                      <span class="material-symbols-outlined text-[22px]">chat</span>
                    </a>
                  @endif
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
                      title="{{ $showBalanceDue ? 'Collect remaining balance' : 'Mark completed' }}"
                      data-booking-id="{{ $booking->id }}"
                      data-has-balance="{{ $showBalanceDue ? '1' : '0' }}"
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
                      data-cancel-info-url="{{ route('api.bookings.cancellation-info', $booking->id) }}"
                      data-booking-ref="{{ e($bookingRef) }}">
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
        <div class="flex justify-between gap-4 py-2 border-b border-outline-variant/10">
          <dt class="text-on-surface-variant font-medium">Deposit</dt>
          <dd id="abdmDeposit" class="text-on-surface font-semibold text-right tabular-nums">—</dd>
        </div>
        <div class="flex justify-between gap-4 py-2">
          <dt class="text-on-surface-variant font-medium">Balance (est.)</dt>
          <dd id="abdmBalance" class="text-on-surface font-semibold text-right tabular-nums">—</dd>
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

<div id="artistUnsettledReminderModal"
  class="artist-urm fixed inset-0 z-[116] flex items-end sm:items-center justify-center p-4 sm:p-6"
  aria-hidden="true"
  aria-modal="true"
  role="dialog"
  aria-labelledby="urmClient">
  <div class="artist-urm-backdrop absolute inset-0 bg-black/45" data-close-artist-urm></div>
  <div class="artist-urm-panel relative w-full max-w-md overflow-hidden rounded-2xl bg-white shadow-xl border border-outline-variant/20">
    <button type="button" class="absolute top-3 right-3 inline-flex h-9 w-9 items-center justify-center rounded-xl text-on-surface-variant hover:bg-surface-container-low" data-close-artist-urm aria-label="Close">
      <span class="material-symbols-outlined text-[20px]">close</span>
    </button>
    <div class="p-5 space-y-3">
      <div class="flex items-start justify-between gap-3 pr-8">
        <p id="urmClient" class="text-lg font-bold text-on-surface">—</p>
        <span id="urmStatus" class="inline-flex shrink-0 items-center text-xs font-semibold px-2.5 py-1 rounded-full ring-1 ring-inset bg-green-50 text-green-700 ring-green-500/20">Confirmed</span>
      </div>
      <p id="urmBookingRef" class="text-sm font-medium text-on-surface-variant tabular-nums">—</p>
      <p id="urmWhen" class="text-sm text-on-surface">—</p>
      <div class="flex items-center justify-between gap-3 rounded-xl bg-[#f4eee4] px-3.5 py-2.5">
        <p id="urmDueLine" class="text-sm font-bold text-[#8a5a12]">—</p>
        <button type="button" id="urmCollectBtn" class="text-sm font-bold text-[#1b5e4a] underline underline-offset-2 whitespace-nowrap">Collect</button>
      </div>
      <ul id="urmNotes" class="space-y-1 text-sm text-on-surface-variant list-disc pl-5">
        <li id="urmProofNote">Not marked completed — no proof yet</li>
        <li id="urmSavedNote" class="hidden"></li>
        <li id="urmNudgeNote" class="hidden"></li>
      </ul>
    </div>
  </div>
</div>

<div id="artistRequestPaymentModal"
  class="artist-rpm fixed inset-0 z-[117] flex items-end sm:items-center justify-center p-4 sm:p-6"
  aria-hidden="true"
  aria-modal="true"
  role="dialog"
  aria-labelledby="rpmTitle">
  <div class="artist-rpm-backdrop absolute inset-0 bg-black/45" data-close-artist-rpm></div>
  <div class="artist-rpm-panel relative w-full max-w-md max-h-[min(90vh,720px)] overflow-y-auto rounded-2xl bg-white shadow-xl border border-outline-variant/20">
    <div class="sticky top-0 z-10 px-5 py-4 border-b border-outline-variant/15 flex items-center justify-between bg-white rounded-t-2xl">
      <h3 id="rpmTitle" class="text-lg font-bold text-on-surface">Request payment</h3>
      <button type="button" class="inline-flex h-10 w-10 items-center justify-center rounded-xl text-on-surface-variant hover:bg-surface-container-low" data-close-artist-rpm aria-label="Close">
        <span class="material-symbols-outlined text-[22px]">close</span>
      </button>
    </div>
    <div id="rpmSettleView" class="p-5 space-y-3">
      <div class="rounded-xl bg-[#f3eef8] px-4 py-3">
        <p class="text-xs text-on-surface-variant">Booking</p>
        <p id="rpmBookingLine" class="mt-0.5 text-sm font-bold text-on-surface">—</p>
      </div>
      <div class="rounded-xl bg-[#f8f1dc] px-4 py-3.5">
        <p id="rpmBalanceLine" class="text-sm font-bold text-[#6f5728]">Outstanding balance: —</p>
        <p class="mt-1 text-sm text-[#6f5728]">How was it settled?</p>
        <div class="mt-3 space-y-2.5" role="radiogroup" aria-label="How was it settled?">
          <div class="space-y-1">
            <div class="flex items-center justify-between gap-3">
              <button type="button" class="rpm-option flex min-w-0 items-center gap-2.5 text-left" data-rpm-value="link" aria-checked="true" role="radio">
                <span class="rpm-option-dot" aria-hidden="true"></span>
                <span class="rpm-option-label text-sm text-on-surface">Client will pay by link</span>
              </button>
              <div id="rpmLinkAmountWrap" class="flex-shrink-0 inline-flex items-center gap-1.5 rounded-full bg-white px-2.5 py-1">
                <span id="rpmLinkSettleAmountDisplay" class="text-xs font-semibold text-on-surface tabular-nums">€ 0</span>
                <input id="rpmLinkSettleAmountInput" type="text" inputmode="decimal" class="hidden w-16 bg-transparent text-xs font-semibold text-on-surface tabular-nums outline-none" aria-label="Payment link amount">
                <button type="button" id="rpmLinkSettleAmountEdit" class="text-[11px] font-semibold text-[#1a4d9e] hover:underline">edit</button>
              </div>
            </div>
            <p id="rpmLinkAmountError" class="hidden text-xs font-semibold text-error text-right"></p>
          </div>
          <div class="space-y-1">
            <div class="flex items-center justify-between gap-3">
              <button type="button" class="rpm-option flex min-w-0 items-center gap-2.5 text-left" data-rpm-value="cash" aria-checked="false" role="radio">
                <span class="rpm-option-dot" aria-hidden="true"></span>
                <span class="rpm-option-label text-sm text-on-surface">Paid in cash</span>
              </button>
              <div id="rpmCashAmountWrap" class="hidden flex-shrink-0 inline-flex items-center gap-1.5 rounded-full bg-white px-2.5 py-1">
                <span id="rpmCashAmountDisplay" class="text-xs font-semibold text-on-surface tabular-nums">€ 0</span>
                <input id="rpmCashAmountInput" type="text" inputmode="decimal" class="hidden w-16 bg-transparent text-xs font-semibold text-on-surface tabular-nums outline-none" aria-label="Cash amount">
                <button type="button" id="rpmCashAmountEdit" class="text-[11px] font-semibold text-[#1a4d9e] hover:underline">edit</button>
              </div>
            </div>
            <p id="rpmCashAmountError" class="hidden text-xs font-semibold text-error text-right"></p>
          </div>
          <button type="button" class="rpm-option flex w-full items-center justify-between gap-3 text-left" data-rpm-value="unsettled" aria-checked="false" role="radio">
            <span class="inline-flex items-center gap-2.5 min-w-0">
              <span class="rpm-option-dot" aria-hidden="true"></span>
              <span class="rpm-option-label text-sm text-on-surface">Not settled yet</span>
            </span>
            <span class="text-[11px] text-on-surface-variant/80 whitespace-nowrap">keeps Collect on card</span>
          </button>
        </div>
      </div>
      <div id="rpmCashFields" class="hidden space-y-3">
        <p id="rpmCashHelp" class="text-sm text-on-surface-variant leading-relaxed">Ask the client for their completion code (shared in booking confirmation email) to confirm the cash payment.</p>
        <div>
          <label for="rpmCompletionCode" class="block text-sm font-semibold text-on-surface mb-1.5">Completion code <span class="text-error">*</span></label>
          <input id="rpmCompletionCode" type="text" maxlength="32" autocomplete="off"
            class="w-full rounded-xl border border-outline-variant/40 bg-white px-4 py-3 text-sm text-on-surface placeholder:text-outline/60 outline-none focus:ring-2 focus:ring-primary/30 focus:border-primary"
            placeholder="Enter code from client">
          <p id="rpmCompletionCodeError" class="hidden text-xs font-semibold text-error mt-1.5"></p>
        </div>
      </div>
      <div id="rpmUnsettledFields" class="hidden space-y-3">
        <div>
          <p class="text-sm font-bold text-on-surface mb-2">When do you expect the payment?</p>
          <div class="flex flex-wrap gap-2" role="radiogroup" aria-label="When do you expect the payment?">
            <button type="button" class="rpm-when-chip rounded-full px-3.5 py-1.5 text-sm font-semibold border bg-[#e7f1ff] border-transparent text-[#1a4d9e]" data-when-value="3-days" aria-pressed="true">3 days</button>
            <button type="button" class="rpm-when-chip rounded-full px-3.5 py-1.5 text-sm font-semibold border bg-white border-outline-variant/40 text-on-surface" data-when-value="1-week" aria-pressed="false">1 week</button>
            <button type="button" class="rpm-when-chip rounded-full px-3.5 py-1.5 text-sm font-semibold border bg-white border-outline-variant/40 text-on-surface" data-when-value="pick-date" aria-pressed="false">Pick date</button>
            <button type="button" class="rpm-when-chip rounded-full px-3.5 py-1.5 text-sm font-semibold border bg-white border-outline-variant/40 text-on-surface" data-when-value="no-date" aria-pressed="false">No date</button>
          </div>
          <p id="rpmWhenError" class="hidden text-xs font-semibold text-error mt-1.5"></p>
          <input id="rpmUnsettledDate" type="date" class="hidden mt-2 w-full rounded-xl border border-outline-variant/40 bg-white px-4 py-2.5 text-sm text-on-surface outline-none focus:ring-2 focus:ring-primary/30 focus:border-primary">
          <p id="rpmDateError" class="hidden text-xs font-semibold text-error mt-1.5"></p>
        </div>
        <div>
          <label for="rpmUnsettledNote" class="block text-sm text-on-surface-variant mb-1.5">Note · optional</label>
          <input id="rpmUnsettledNote" type="text"
            class="w-full rounded-xl border border-outline-variant/40 bg-white px-4 py-3 text-sm text-on-surface placeholder:text-outline/60 outline-none focus:ring-2 focus:ring-primary/30 focus:border-primary"
            placeholder="Bank transfer on Friday">
        </div>
      </div>
      <div class="flex items-start justify-between gap-3 pt-1">
        <button type="button" class="rounded-xl border border-outline-variant/40 bg-white px-5 py-2.5 text-sm font-semibold text-on-surface hover:bg-surface-container-low" data-close-artist-rpm>Back</button>
        <div class="text-right">
          <button type="button" id="rpmActionBtn" class="rounded-xl bg-[#1b5e4a] px-5 py-2.5 text-sm font-bold text-white disabled:opacity-70 disabled:cursor-wait">Get payment link</button>
          <p id="rpmActionHint" class="mt-1.5 text-[11px] text-on-surface-variant">sends link — completes when client pays</p>
        </div>
      </div>
      <p id="rpmError" class="hidden text-xs font-semibold text-error"></p>
    </div>
    <div id="rpmLinkView" class="hidden p-5 space-y-4">
      <div class="text-sm text-on-surface-variant space-y-0.5">
        <p id="rpmLinkClientLine">—</p>
        <p id="rpmLinkTotalsLine">—</p>
      </div>
      <div>
        <p class="text-sm text-on-surface-variant mb-1.5">Amount</p>
        <div class="rounded-xl border border-outline-variant/40 bg-white px-4 py-3">
          <span id="rpmLinkAmountDisplay" class="text-base font-bold text-on-surface tabular-nums">€ 0</span>
        </div>
      </div>
      <div>
        <p class="text-sm text-on-surface-variant mb-1.5">Message for your client</p>
        <div id="rpmLinkMessage" class="rounded-xl border border-outline-variant/40 bg-white px-4 py-3 text-sm text-on-surface leading-relaxed"></div>
      </div>
      <div class="space-y-2">
        <button type="button" id="rpmCopyLinkBtn" class="w-full rounded-xl bg-[#1c1b21] px-4 py-3.5 text-sm font-bold text-white">Copy payment link</button>
        <div class="grid grid-cols-2 gap-2">
          <button type="button" id="rpmCopyMessageBtn" class="rounded-xl border border-outline-variant/40 bg-white px-4 py-3 text-sm font-semibold text-on-surface">Copy message</button>
          <button type="button" id="rpmShowQrBtn" class="rounded-xl border border-outline-variant/40 bg-white px-4 py-3 text-sm font-semibold text-on-surface" aria-expanded="false" aria-controls="rpmQrWrap">Show QR</button>
        </div>
        <div id="rpmQrWrap" class="hidden">
          <div class="rounded-xl border border-outline-variant/40 bg-white px-4 py-5 flex flex-col items-center justify-center">
            <img id="rpmQrImage" alt="Payment link QR code" class="h-48 w-48 rounded-lg bg-white object-contain">
            <p class="mt-3 text-xs text-on-surface-variant">Scan to open the payment link</p>
          </div>
        </div>
      </div>
      <div class="space-y-1.5 text-sm text-on-surface-variant">
        <p>Client can pay in full or split with Klarna.</p>
        <p>Balance links don’t expire.</p>
        <p id="rpmLinkStatusLine" class="text-on-surface-variant/80">Card: — requested · link sent · <span class="text-[#1a4d9e] font-semibold">Resend</span></p>
        <p class="font-bold text-[#1b5e4a]">Client pays → booking auto-marked Completed</p>
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
<script src="{{ asset('js/question-answer-display.js') }}"></script>
<script>
(function () {
  var liveUrl = @json(route('api.bookings.live-status'));
  var completedBadgeClass = 'js-artist-status-badge inline-flex items-center text-xs font-semibold px-2.5 py-1 rounded-full ring-1 ring-inset bg-slate-100 text-slate-700 ring-slate-500/15';

  function markSettled(bookingId, message) {
    var id = String(bookingId || '');
    if (!id) return;
    document.querySelectorAll('[data-booking-row][data-booking-id="' + id + '"]').forEach(function (row) {
      row.setAttribute('data-status', 'completed');
      row.querySelectorAll('.js-artist-collect-wrap').forEach(function (el) {
        el.classList.add('hidden');
      });
      row.querySelectorAll('.js-artist-row-actions').forEach(function (actions) {
        Array.prototype.forEach.call(actions.children, function (child) {
          if (!child.classList.contains('js-artist-booking-view')) {
            child.classList.add('hidden');
          }
        });
      });
      row.querySelectorAll('.js-artist-status-badge').forEach(function (badge) {
        badge.className = completedBadgeClass + (badge.classList.contains('shrink-0') ? ' shrink-0' : '');
        badge.textContent = 'Completed';
      });
    });
    if (message && typeof showSaveToast === 'function') {
      showSaveToast(message);
    }
  }

  function ifSettled(bookingId, onOpen, checkBalanceCollected) {
    var id = String(bookingId || '');
    if (!id) {
      if (onOpen) onOpen();
      return;
    }
    fetch(liveUrl + '?ids=' + encodeURIComponent(id), {
      headers: {
        'Accept': 'application/json',
        'X-Requested-With': 'XMLHttpRequest',
      },
    })
      .then(function (r) { return r.json(); })
      .then(function (data) {
        var bookings = (data && data.bookings) || [];
        var item = null;
        for (var i = 0; i < bookings.length; i++) {
          if (String(bookings[i].id) === id) {
            item = bookings[i];
            break;
          }
        }
        var alreadyDone = item && (item.completed || item.settled);
        if (checkBalanceCollected && item && item.balance_collected) {
          alreadyDone = true;
        }
        if (alreadyDone) {
          markSettled(id, item.message || 'This booking is already completed.');
          return;
        }
        if (onOpen) onOpen();
      })
      .catch(function () {
        if (onOpen) onOpen();
      });
  }

  window.artistBookingsMarkSettled = markSettled;
  window.artistBookingsIfSettled = ifSettled;
})();
</script>
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
          if (typeof flashSaveToast === 'function') {
            flashSaveToast((res.data && res.data.message) || 'Reschedule request sent.');
          }
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
          var bookingId = activeBtn ? activeBtn.getAttribute('data-booking-id') : '';
          closeModal();
          if (typeof window.artistBookingsMarkSettled === 'function') {
            window.artistBookingsMarkSettled(bookingId, (res.data && res.data.message) || 'Booking marked as completed.');
          } else if (typeof showSaveToast === 'function') {
            showSaveToast((res.data && res.data.message) || 'Booking marked as completed.');
          }
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
      var bookingId = btn.getAttribute('data-booking-id');
      if (btn.getAttribute('data-has-balance') === '1') {
        var collectBtn = document.querySelector('.js-artist-collect-payment[data-booking-id="' + bookingId + '"]');
        if (collectBtn) {
          collectBtn.click();
          return;
        }
      }
      if (typeof window.artistBookingsIfSettled === 'function') {
        window.artistBookingsIfSettled(bookingId, function () {
          openModal(btn);
        });
        return;
      }
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
    var bookingRef = btn.getAttribute('data-booking-ref') || '';
    if (bookingIdEl) bookingIdEl.textContent = bookingRef || ('INK-FL-' + String(bookingId).padStart(5, '0'));
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
          if (typeof flashSaveToast === 'function') {
            flashSaveToast((res.data && res.data.message) || 'Booking cancelled.');
          }
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
  var balEl = document.getElementById('abdmBalance');
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
    if (balEl) balEl.textContent = ds.balance || '—';
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
          var payload = window.QuestionAnswerDisplay
            ? window.QuestionAnswerDisplay.normalizePayload(pair[1], 'Question #' + (qKey || '—'))
            : { question: 'Question #' + (qKey || '—'), type: '', answer: pair[1] };
          if (window.QuestionAnswerDisplay) {
            window.QuestionAnswerDisplay.appendAnswerBlock(qaListEl, payload);
          } else {
            var item = document.createElement('div');
            item.className = 'border-b border-outline-variant/15 pb-2 last:border-b-0 last:pb-0';
            var q = document.createElement('p');
            q.className = 'text-xs font-semibold text-on-surface-variant';
            q.textContent = payload.question;
            var a = document.createElement('p');
            a.className = 'text-on-surface mt-1 break-words';
            a.textContent = window.QuestionAnswerDisplay
              ? window.QuestionAnswerDisplay.formatAnswerForReview(payload.answer, payload.type)
              : (Array.isArray(payload.answer)
                ? payload.answer.map(function (_, idx) { return 'Photo ' + (idx + 1); }).join(', ')
                : String(payload.answer ?? '—'));
            item.appendChild(q);
            item.appendChild(a);
            qaListEl.appendChild(item);
          }
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

  var openBookingId = new URLSearchParams(window.location.search).get('booking');
  if (openBookingId) {
    var openRow = document.querySelector('[data-booking-row][data-booking-id="' + openBookingId + '"]');
    var openBtn = openRow ? openRow.querySelector('.js-artist-booking-view') : null;
    if (openBtn) {
      openModal(openBtn);
    }
  }

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
<script>
(function () {
  var modal = document.getElementById('artistRequestPaymentModal');
  if (!modal) return;

  var bookingLine = document.getElementById('rpmBookingLine');
  var balanceLine = document.getElementById('rpmBalanceLine');
  var actionBtn = document.getElementById('rpmActionBtn');
  var actionHint = document.getElementById('rpmActionHint');
  var cashFields = document.getElementById('rpmCashFields');
  var cashHelp = document.getElementById('rpmCashHelp');
  var cashAmountWrap = document.getElementById('rpmCashAmountWrap');
  var cashAmountDisplay = document.getElementById('rpmCashAmountDisplay');
  var cashAmountInput = document.getElementById('rpmCashAmountInput');
  var cashAmountEdit = document.getElementById('rpmCashAmountEdit');
  var linkAmountWrap = document.getElementById('rpmLinkAmountWrap');
  var linkSettleAmountDisplay = document.getElementById('rpmLinkSettleAmountDisplay');
  var linkSettleAmountInput = document.getElementById('rpmLinkSettleAmountInput');
  var linkSettleAmountEdit = document.getElementById('rpmLinkSettleAmountEdit');
  var completionCode = document.getElementById('rpmCompletionCode');
  var unsettledFields = document.getElementById('rpmUnsettledFields');
  var unsettledNote = document.getElementById('rpmUnsettledNote');
  var unsettledDate = document.getElementById('rpmUnsettledDate');
  var whenChips = modal.querySelectorAll('.rpm-when-chip');
  var options = modal.querySelectorAll('.rpm-option');
  var settleView = document.getElementById('rpmSettleView');
  var linkView = document.getElementById('rpmLinkView');
  var linkClientLine = document.getElementById('rpmLinkClientLine');
  var linkTotalsLine = document.getElementById('rpmLinkTotalsLine');
  var linkAmountDisplay = document.getElementById('rpmLinkAmountDisplay');
  var linkMessage = document.getElementById('rpmLinkMessage');
  var linkStatusLine = document.getElementById('rpmLinkStatusLine');
  var copyLinkBtn = document.getElementById('rpmCopyLinkBtn');
  var copyMessageBtn = document.getElementById('rpmCopyMessageBtn');
  var showQrBtn = document.getElementById('rpmShowQrBtn');
  var qrWrap = document.getElementById('rpmQrWrap');
  var qrImage = document.getElementById('rpmQrImage');
  var errorEl = document.getElementById('rpmError');
  var reminderModal = document.getElementById('artistUnsettledReminderModal');
  var reminderCollectBtn = document.getElementById('urmCollectBtn');
  var reminderSourceBtn = null;
  var currentAmountLabel = '€ 0';
  var currentOption = 'link';
  var currentWhenValue = '3-days';
  var currentStoreUrl = '';
  var currentBookingId = '';
  var currentContext = {
    clientName: '',
    firstName: 'there',
    bookingRef: '',
    paidLabel: '€0',
    totalLabel: '€0',
    service: 'session',
    linkUrl: 'bookpay.ink/p/x7Kq2mPa'
  };
  var copy = {
    link: { label: 'Get payment link', hint: 'sends link — completes when client pays', busy: 'Creating link...', busyHint: 'Please wait' },
    cash: { label: 'Confirm completed', hint: 'code confirms cash — marks completed', busy: 'Confirming...', busyHint: 'Marking booking completed' },
    unsettled: { label: 'Save', hint: 'booking stays open — nudge on the date', busy: 'Saving...', busyHint: 'Please wait' }
  };

  function compactEuro(amountLabel) {
    return String(amountLabel || '€0').replace(/^€\s*/, '€');
  }

  function parseAmountValue(label) {
    var cleaned = String(label || '').replace(/[^\d.,]/g, '').replace(',', '.');
    var amount = parseFloat(cleaned);
    return isFinite(amount) ? Math.round(amount * 100) / 100 : 0;
  }

  function firstError(errors, field) {
    if (!errors || !errors[field]) return '';
    return Array.isArray(errors[field]) ? (errors[field][0] || '') : String(errors[field]);
  }

  function setTextError(el, message) {
    if (!el) return;
    el.textContent = message || '';
    el.classList.toggle('hidden', !message);
  }

  function setInputInvalid(el, invalid) {
    if (!el) return;
    el.classList.toggle('border-error', !!invalid);
    el.classList.toggle('border-outline-variant/40', !invalid);
    el.setAttribute('aria-invalid', invalid ? 'true' : 'false');
  }

  function setWrapInvalid(el, invalid) {
    if (!el) return;
    el.classList.toggle('ring-1', !!invalid);
    el.classList.toggle('ring-error', !!invalid);
  }

  function showRpmError(message) {
    setTextError(errorEl, message);
  }

  function clearFieldErrors() {
    setTextError(document.getElementById('rpmLinkAmountError'), '');
    setTextError(document.getElementById('rpmCashAmountError'), '');
    setTextError(document.getElementById('rpmCompletionCodeError'), '');
    setTextError(document.getElementById('rpmWhenError'), '');
    setTextError(document.getElementById('rpmDateError'), '');
    setInputInvalid(completionCode, false);
    setInputInvalid(unsettledDate, false);
    setWrapInvalid(linkAmountWrap, false);
    setWrapInvalid(cashAmountWrap, false);
    showRpmError('');
  }

  function showAmountError(message) {
    var isCash = currentOption === 'cash';
    var isLink = currentOption === 'link';
    setTextError(document.getElementById('rpmLinkAmountError'), isLink ? message : '');
    setTextError(document.getElementById('rpmCashAmountError'), isCash ? message : '');
    setWrapInvalid(linkAmountWrap, isLink && !!message);
    setWrapInvalid(cashAmountWrap, isCash && !!message);
    if (!isCash && !isLink && message) showRpmError(message);
  }

  function applyFieldErrors(errors) {
    clearFieldErrors();
    var amountMsg = firstError(errors, 'amount');
    var codeMsg = firstError(errors, 'completion_code');
    var whenMsg = firstError(errors, 'expected_payment_type');
    var dateMsg = firstError(errors, 'expected_payment_date');
    if (amountMsg) showAmountError(amountMsg);
    if (codeMsg) {
      setTextError(document.getElementById('rpmCompletionCodeError'), codeMsg);
      setInputInvalid(completionCode, true);
    }
    if (whenMsg) setTextError(document.getElementById('rpmWhenError'), whenMsg);
    if (dateMsg) {
      setTextError(document.getElementById('rpmDateError'), dateMsg);
      setInputInvalid(unsettledDate, true);
    }
    var mapped = { amount: true, completion_code: true, expected_payment_type: true, expected_payment_date: true };
    var leftover = '';
    Object.keys(errors || {}).forEach(function (key) {
      if (!mapped[key] && !leftover) leftover = firstError(errors, key);
    });
    if (leftover) showRpmError(leftover);
  }

  function csrfToken() {
    var meta = document.querySelector('meta[name="csrf-token"]');
    return meta ? meta.getAttribute('content') : '';
  }

  function submitBalanceCollection() {
    clearFieldErrors();
    if (currentOption === 'cash') commitCashAmount();
    if (currentOption === 'link') commitSettleAmount('link');
    var amount = parseAmountValue(currentAmountLabel);
    if (!(amount > 0)) {
      showAmountError('Amount must be greater than 0.');
      return;
    }

    var typeMap = { link: 'payment_link', cash: 'paid_in_cash', unsettled: 'not_settled_yet' };
    var whenMap = { '3-days': '3_days', '1-week': '1_week', 'pick-date': 'pick_date', 'no-date': 'no_date' };
    var payload = {
      collection_type: typeMap[currentOption] || 'payment_link',
      amount: amount
    };

    if (currentOption === 'cash') {
      var code = completionCode ? completionCode.value.trim() : '';
      if (!code) {
        setTextError(document.getElementById('rpmCompletionCodeError'), 'Please enter the completion code.');
        setInputInvalid(completionCode, true);
        if (completionCode) completionCode.focus();
        return;
      }
      payload.completion_code = code;
    }

    if (currentOption === 'unsettled') {
      payload.expected_payment_type = whenMap[currentWhenValue] || '3_days';
      payload.note = unsettledNote ? unsettledNote.value.trim() : '';
      if (currentWhenValue === 'pick-date') {
        var picked = unsettledDate ? unsettledDate.value : '';
        if (!picked) {
          setTextError(document.getElementById('rpmDateError'), 'Please pick a date.');
          setInputInvalid(unsettledDate, true);
          if (unsettledDate) unsettledDate.focus();
          return;
        }
        payload.expected_payment_date = picked;
      }
    }

    if (!currentStoreUrl) {
      showRpmError('Could not save this collection.');
      return;
    }

    var selected = copy[currentOption] || copy.link;
    if (actionBtn) {
      actionBtn.disabled = true;
      actionBtn.textContent = selected.busy || 'Please wait...';
    }
    if (actionHint) actionHint.textContent = selected.busyHint || 'Please wait';

    fetch(currentStoreUrl, {
      method: 'POST',
      headers: {
        'Accept': 'application/json',
        'Content-Type': 'application/json',
        'X-Requested-With': 'XMLHttpRequest',
        'X-CSRF-TOKEN': csrfToken()
      },
      body: JSON.stringify(payload)
    }).then(function (response) {
      return response.json().then(function (data) {
        return { status: response.status, data: data };
      });
    }).then(function (result) {
      if (!result.data || !result.data.success) {
        if (result.data && result.data.errors) {
          applyFieldErrors(result.data.errors);
          return;
        }
        showRpmError((result.data && result.data.message) || 'Could not save.');
        return;
      }
      if (currentOption === 'link') {
        var collection = result.data.collection || {};
        currentContext.linkUrl = collection.payment_link_url || currentContext.linkUrl;
        if (collection.client_message && linkMessage) {
          currentContext.savedMessage = collection.client_message;
        }
        document.querySelectorAll('.js-artist-collect-payment[data-booking-id="' + currentBookingId + '"]').forEach(function (btn) {
          btn.setAttribute('data-unsettled', '0');
        });
        fillLinkView();
        if (collection.client_message && linkMessage) {
          linkMessage.textContent = collection.client_message;
        }
        showLinkView();
        return;
      }
      if (result.data.booking_completed) {
        var bookingId = currentBookingId || '';
        closeModal();
        if (typeof window.artistBookingsMarkSettled === 'function') {
          window.artistBookingsMarkSettled(bookingId, result.data.message || 'Cash payment recorded. Booking marked as completed.');
        } else if (typeof showSaveToast === 'function') {
          showSaveToast(result.data.message || 'Cash payment recorded. Booking marked as completed.');
        }
        return;
      }
      if (currentOption === 'unsettled') {
        markCollectAsUnsettled(currentBookingId, result.data.collection || {});
      }
      closeModal();
      if (typeof showSaveToast === 'function') {
        showSaveToast(result.data.message || 'Saved.');
      }
    }).catch(function () {
      showRpmError('Could not save.');
    }).finally(function () {
      var selected = copy[currentOption] || copy.link;
      if (actionBtn) {
        actionBtn.disabled = false;
        actionBtn.textContent = selected.label;
      }
      if (actionHint) actionHint.textContent = selected.hint;
    });
  }

  function firstNameFrom(fullName) {
    var name = String(fullName || '').trim();
    if (!name) return 'there';
    return name.split(/\s+/)[0];
  }

  function showSettleView() {
    if (settleView) settleView.classList.remove('hidden');
    if (linkView) linkView.classList.add('hidden');
  }

  function buildLinkMessage(amountLabel) {
    var remaining = compactEuro(amountLabel);
    return 'Hi ' + currentContext.firstName + ' — here’s the payment link for the remaining ' + remaining + ' for your ' + currentContext.service + ' session: ' + currentContext.linkUrl + ' — you can pay in full or split it with Klarna.';
  }

  function fillLinkView() {
    var remaining = compactEuro(currentAmountLabel);
    if (linkClientLine) {
      linkClientLine.textContent = [currentContext.clientName, currentContext.bookingRef].filter(Boolean).join(' · ') || '—';
    }
    if (linkTotalsLine) {
      linkTotalsLine.textContent = currentContext.totalLabel + ' total · ' + currentContext.paidLabel + ' paid';
    }
    if (linkAmountDisplay) linkAmountDisplay.textContent = currentAmountLabel;
    if (linkMessage) linkMessage.textContent = buildLinkMessage(currentAmountLabel);
    if (linkStatusLine) {
      linkStatusLine.innerHTML = 'Card: ' + remaining + ' requested · link sent · <span class="text-[#1a4d9e] font-semibold">Resend</span>';
    }
  }

  function showLinkView() {
    fillLinkView();
    resetCopyButtons();
    hideQr();
    if (settleView) settleView.classList.add('hidden');
    if (linkView) linkView.classList.remove('hidden');
  }

  function hideQr() {
    if (qrWrap) qrWrap.classList.add('hidden');
    if (qrImage) qrImage.removeAttribute('src');
    if (showQrBtn) {
      showQrBtn.textContent = 'Show QR';
      showQrBtn.setAttribute('aria-expanded', 'false');
    }
  }

  function showQr() {
    var url = currentContext.linkUrl || '';
    if (!url || !qrWrap || !qrImage) return;
    qrImage.src = 'https://api.qrserver.com/v1/create-qr-code/?size=200x200&data=' + encodeURIComponent(url);
    qrWrap.classList.remove('hidden');
    if (showQrBtn) {
      showQrBtn.textContent = 'Hide QR';
      showQrBtn.setAttribute('aria-expanded', 'true');
    }
  }

  function resetCopyButtons() {
    if (copyLinkBtn) {
      clearTimeout(copyLinkBtn._copyTimer);
      copyLinkBtn.textContent = 'Copy payment link';
    }
    if (copyMessageBtn) {
      clearTimeout(copyMessageBtn._copyTimer);
      copyMessageBtn.textContent = 'Copy message';
    }
  }

  function copyText(text, button, idleLabel) {
    if (!text || !button) return;
    var done = function () {
      button.textContent = 'Copied';
      clearTimeout(button._copyTimer);
      button._copyTimer = setTimeout(function () {
        button.textContent = idleLabel;
      }, 1500);
    };
    if (navigator.clipboard && navigator.clipboard.writeText) {
      navigator.clipboard.writeText(text).then(done).catch(function () {
        fallbackCopy(text, done);
      });
      return;
    }
    fallbackCopy(text, done);
  }

  function fallbackCopy(text, done) {
    var temp = document.createElement('textarea');
    temp.value = text;
    temp.setAttribute('readonly', '');
    temp.style.position = 'absolute';
    temp.style.left = '-9999px';
    document.body.appendChild(temp);
    temp.select();
    try {
      document.execCommand('copy');
      done();
    } catch (e) {}
    document.body.removeChild(temp);
  }

  function setWhenChip(value) {
    currentWhenValue = value || '3-days';
    whenChips.forEach(function (chip) {
      var selected = chip.getAttribute('data-when-value') === value;
      chip.setAttribute('aria-pressed', selected ? 'true' : 'false');
      chip.classList.toggle('bg-[#e7f1ff]', selected);
      chip.classList.toggle('border-transparent', selected);
      chip.classList.toggle('text-[#1a4d9e]', selected);
      chip.classList.toggle('bg-white', !selected);
      chip.classList.toggle('border-outline-variant/40', !selected);
      chip.classList.toggle('text-on-surface', !selected);
    });
    if (unsettledDate) {
      unsettledDate.classList.toggle('hidden', value !== 'pick-date');
      if (value !== 'pick-date') {
        setTextError(document.getElementById('rpmDateError'), '');
        setInputInvalid(unsettledDate, false);
      }
      if (value === 'pick-date') {
        var today = new Date();
        var ymd = today.getFullYear() + '-' + String(today.getMonth() + 1).padStart(2, '0') + '-' + String(today.getDate()).padStart(2, '0');
        unsettledDate.setAttribute('min', ymd);
        unsettledDate.focus();
      }
    }
    setTextError(document.getElementById('rpmWhenError'), '');
  }

  function formatCashAmount(raw) {
    var cleaned = String(raw || '').replace(/[^\d.,]/g, '').replace(',', '.');
    var amount = parseFloat(cleaned);
    if (!isFinite(amount) || amount < 0) return currentAmountLabel;
    var rounded = Math.round(amount * 100) / 100;
    return rounded % 1 === 0 ? '€ ' + String(Math.round(rounded)) : '€ ' + rounded.toFixed(2);
  }

  function outstandingLabel(amountLabel) {
    return 'Outstanding balance: ' + String(amountLabel || '—').replace(/^€\s*/, '€');
  }

  function setOutstandingBalance(amountLabel) {
    if (balanceLine) balanceLine.textContent = outstandingLabel(amountLabel);
  }

  function updateSettleAmountDisplays() {
    if (cashAmountDisplay) cashAmountDisplay.textContent = currentAmountLabel;
    if (linkSettleAmountDisplay) linkSettleAmountDisplay.textContent = currentAmountLabel;
    setOutstandingBalance(currentAmountLabel);
  }

  function setSettleAmountEditing(editing, source) {
    var isLink = source === 'link';
    var display = isLink ? linkSettleAmountDisplay : cashAmountDisplay;
    var input = isLink ? linkSettleAmountInput : cashAmountInput;
    var editBtn = isLink ? linkSettleAmountEdit : cashAmountEdit;
    if (!display || !input || !editBtn) return;
    display.classList.toggle('hidden', editing);
    input.classList.toggle('hidden', !editing);
    editBtn.classList.toggle('hidden', editing);
    if (editing) {
      input.value = currentAmountLabel.replace(/^€\s*/, '');
      input.focus();
      input.select();
    }
  }

  function setCashEditing(editing) {
    setSettleAmountEditing(editing, 'cash');
  }

  function setLinkSettleAmountEditing(editing) {
    setSettleAmountEditing(editing, 'link');
  }

  function commitSettleAmount(source) {
    var input = source === 'link' ? linkSettleAmountInput : cashAmountInput;
    currentAmountLabel = formatCashAmount(input ? input.value : currentAmountLabel);
    updateSettleAmountDisplays();
    setSettleAmountEditing(false, source);
    showAmountError('');
  }

  function commitCashAmount() {
    commitSettleAmount('cash');
  }

  function setOption(value) {
    currentOption = value || 'link';
    options.forEach(function (option) {
      option.setAttribute('aria-checked', option.getAttribute('data-rpm-value') === value ? 'true' : 'false');
    });
    var isCash = value === 'cash';
    var isLink = value === 'link';
    var isUnsettled = value === 'unsettled';
    if (cashFields) cashFields.classList.toggle('hidden', !isCash);
    if (cashAmountWrap) cashAmountWrap.classList.toggle('hidden', !isCash);
    if (linkAmountWrap) linkAmountWrap.classList.toggle('hidden', !isLink);
    if (unsettledFields) unsettledFields.classList.toggle('hidden', !isUnsettled);
    if (!isCash) setCashEditing(false);
    if (!isLink) setLinkSettleAmountEditing(false);
    var selected = copy[value] || copy.link;
    if (actionBtn) actionBtn.textContent = selected.label;
    if (actionHint) actionHint.textContent = selected.hint;
    clearFieldErrors();
  }

  function openModal(btn) {
    var ds = btn.dataset || {};
    var service = (ds.service || '').trim();
    var client = (ds.clientName || '').trim() || 'the client';
    var line = [service, (ds.clientName || '').trim()].filter(Boolean).join(' · ');
    currentAmountLabel = formatCashAmount(ds.balanceLabel || '0');
    currentStoreUrl = ds.storeUrl || '';
    currentBookingId = ds.bookingId || '';
    currentContext = {
      clientName: (ds.clientName || '').trim(),
      firstName: firstNameFrom(ds.clientName),
      bookingRef: (ds.bookingRef || '').trim(),
      paidLabel: (ds.paidLabel || '€0').trim() || '€0',
      totalLabel: (ds.totalLabel || '€0').trim() || '€0',
      service: (ds.service || '').trim() || 'session',
      linkUrl: 'bookpay.ink/p/x7Kq2mPa'
    };
    if (bookingLine) bookingLine.textContent = line || 'Booking';
    setOutstandingBalance(currentAmountLabel);
    if (cashHelp) {
      cashHelp.textContent = 'Ask ' + client + ' for their completion code (shared in booking confirmation email) to confirm the cash payment.';
    }
    if (cashAmountDisplay) cashAmountDisplay.textContent = currentAmountLabel;
    if (linkSettleAmountDisplay) linkSettleAmountDisplay.textContent = currentAmountLabel;
    if (completionCode) completionCode.value = '';
    if (unsettledNote) unsettledNote.value = '';
    if (unsettledDate) unsettledDate.value = '';
    setWhenChip('3-days');
    setCashEditing(false);
    setLinkSettleAmountEditing(false);
    setOption('link');
    clearFieldErrors();
    showSettleView();
    modal.classList.add('artist-rpm-open');
    modal.setAttribute('aria-hidden', 'false');
    document.body.style.overflow = 'hidden';
  }

  function closeModal() {
    hideQr();
    modal.classList.remove('artist-rpm-open');
    modal.setAttribute('aria-hidden', 'true');
    document.body.style.overflow = '';
  }

  function setNoteText(el, text) {
    if (!el) return;
    var value = String(text || '').trim();
    el.textContent = value;
    el.classList.toggle('hidden', !value);
  }

  function markCollectAsUnsettled(bookingId, collection) {
    var id = String(bookingId || '');
    if (!id) return;
    collection = collection || {};
    var amount = collection.amount_label || '';
    var expected = collection.expected_label || '';
    var note = collection.note || '';
    var nudge = collection.nudge || '';
    var barText = [amount ? amount + ' due' : '', expected].filter(Boolean).join(' · ');
    document.querySelectorAll('.js-artist-collect-payment[data-booking-id="' + id + '"]').forEach(function (btn) {
      btn.setAttribute('data-unsettled', '1');
      if (amount) btn.setAttribute('data-balance-label', amount);
      btn.setAttribute('data-expected-label', expected);
      btn.setAttribute('data-unsettled-note', note);
      btn.setAttribute('data-nudge', nudge);
      var wrap = btn.closest('.js-artist-collect-wrap');
      var label = wrap ? wrap.querySelector('.js-artist-collect-label') : null;
      if (label && barText) label.textContent = barText;
    });
  }

  function closeReminder() {
    if (!reminderModal) return;
    reminderModal.classList.remove('artist-urm-open');
    reminderModal.setAttribute('aria-hidden', 'true');
    if (!modal.classList.contains('artist-rpm-open')) {
      document.body.style.overflow = '';
    }
  }

  function openReminder(btn) {
    if (!reminderModal) {
      openModal(btn);
      return;
    }
    reminderSourceBtn = btn;
    var ds = btn.dataset || {};
    var client = (ds.clientName || '').trim() || 'Client';
    var amount = (ds.balanceLabel || '').trim();
    var expected = (ds.expectedLabel || '').trim();
    var note = (ds.unsettledNote || '').trim();
    var nudge = (ds.nudge || '').trim();
    var dateLine = [(ds.dateDisplay || '').trim(), (ds.timeRange || '').trim()].filter(Boolean).join(' · ');
    var clientEl = document.getElementById('urmClient');
    var refEl = document.getElementById('urmBookingRef');
    var whenEl = document.getElementById('urmWhen');
    var dueEl = document.getElementById('urmDueLine');
    var statusEl = document.getElementById('urmStatus');
    if (clientEl) clientEl.textContent = client;
    if (refEl) refEl.textContent = (ds.bookingRef || '').trim() || '—';
    if (whenEl) whenEl.textContent = dateLine || '—';
    if (dueEl) dueEl.textContent = [amount ? amount + ' due' : '', expected].filter(Boolean).join(' · ') || 'Balance due';
    if (statusEl) {
      statusEl.textContent = (ds.statusLabel || '').trim() || 'Confirmed';
      statusEl.className = 'inline-flex shrink-0 items-center text-xs font-semibold px-2.5 py-1 rounded-full ring-1 ring-inset ' + ((ds.statusBadgeClass || '').trim() || 'bg-green-50 text-green-700 ring-green-500/20');
    }
    setNoteText(document.getElementById('urmSavedNote'), note ? 'Note saved: “' + note + '”' : '');
    setNoteText(document.getElementById('urmNudgeNote'), nudge);
    reminderModal.classList.add('artist-urm-open');
    reminderModal.setAttribute('aria-hidden', 'false');
    document.body.style.overflow = 'hidden';
  }

  function openCollectFlow(btn) {
    if (btn.getAttribute('data-unsettled') === '1') {
      openReminder(btn);
      return;
    }
    openModal(btn);
  }

  document.querySelectorAll('.js-artist-collect-payment').forEach(function (btn) {
    btn.addEventListener('click', function () {
      var bookingId = btn.getAttribute('data-booking-id');
      if (typeof window.artistBookingsIfSettled === 'function') {
        window.artistBookingsIfSettled(bookingId, function () {
          openCollectFlow(btn);
        }, true);
        return;
      }
      openCollectFlow(btn);
    });
  });

  options.forEach(function (option) {
    option.addEventListener('click', function () {
      setOption(option.getAttribute('data-rpm-value'));
    });
  });

  whenChips.forEach(function (chip) {
    chip.addEventListener('click', function () {
      setWhenChip(chip.getAttribute('data-when-value'));
    });
  });

  if (actionBtn) {
    actionBtn.addEventListener('click', function () {
      submitBalanceCollection();
    });
  }

  if (linkSettleAmountEdit) {
    linkSettleAmountEdit.addEventListener('click', function (e) {
      e.preventDefault();
      e.stopPropagation();
      setLinkSettleAmountEditing(true);
    });
  }
  if (linkSettleAmountInput) {
    linkSettleAmountInput.addEventListener('keydown', function (e) {
      if (e.key === 'Enter') {
        e.preventDefault();
        commitSettleAmount('link');
      }
      if (e.key === 'Escape') {
        e.preventDefault();
        setLinkSettleAmountEditing(false);
      }
    });
    linkSettleAmountInput.addEventListener('blur', function () {
      commitSettleAmount('link');
    });
    linkSettleAmountInput.addEventListener('input', function () {
      setOutstandingBalance(formatCashAmount(linkSettleAmountInput.value));
      showAmountError('');
    });
  }

  if (cashAmountEdit) {
    cashAmountEdit.addEventListener('click', function (e) {
      e.preventDefault();
      e.stopPropagation();
      setCashEditing(true);
    });
  }
  if (cashAmountInput) {
    cashAmountInput.addEventListener('keydown', function (e) {
      if (e.key === 'Enter') {
        e.preventDefault();
        commitCashAmount();
      }
      if (e.key === 'Escape') {
        e.preventDefault();
        setCashEditing(false);
      }
    });
    cashAmountInput.addEventListener('blur', commitCashAmount);
    cashAmountInput.addEventListener('input', function () {
      setOutstandingBalance(formatCashAmount(cashAmountInput.value));
      showAmountError('');
    });
  }

  if (completionCode) {
    completionCode.addEventListener('input', function () {
      setTextError(document.getElementById('rpmCompletionCodeError'), '');
      setInputInvalid(completionCode, false);
    });
  }

  if (unsettledDate) {
    unsettledDate.addEventListener('change', function () {
      setTextError(document.getElementById('rpmDateError'), '');
      setInputInvalid(unsettledDate, false);
    });
  }

  if (copyLinkBtn) {
    copyLinkBtn.addEventListener('click', function () {
      copyText(currentContext.linkUrl || '', copyLinkBtn, 'Copy payment link');
    });
  }

  if (copyMessageBtn) {
    copyMessageBtn.addEventListener('click', function () {
      var message = linkMessage ? (linkMessage.textContent || '').trim() : '';
      copyText(message, copyMessageBtn, 'Copy message');
    });
  }

  if (showQrBtn) {
    showQrBtn.addEventListener('click', function () {
      if (qrWrap && !qrWrap.classList.contains('hidden')) {
        hideQr();
        return;
      }
      showQr();
    });
  }

  modal.querySelectorAll('[data-close-artist-rpm]').forEach(function (el) {
    el.addEventListener('click', closeModal);
  });

  if (reminderCollectBtn) {
    reminderCollectBtn.addEventListener('click', function () {
      var btn = reminderSourceBtn;
      closeReminder();
      if (btn) openModal(btn);
    });
  }
  if (reminderModal) {
    reminderModal.querySelectorAll('[data-close-artist-urm]').forEach(function (el) {
      el.addEventListener('click', closeReminder);
    });
  }

  document.addEventListener('keydown', function (e) {
    if (e.key !== 'Escape') return;
    if (modal.classList.contains('artist-rpm-open')) {
      closeModal();
      return;
    }
    if (reminderModal && reminderModal.classList.contains('artist-urm-open')) {
      closeReminder();
    }
  });
})();
</script>
@endsection
