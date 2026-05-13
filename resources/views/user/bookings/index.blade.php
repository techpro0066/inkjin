@extends('layouts.user_dashboard_layout')

@section('title', 'My Bookings')

@section('styles')
<style>
  .booking-detail-modal .bdm-backdrop { opacity: 0; transition: opacity 0.35s ease; }
  .booking-detail-modal.bdm-open .bdm-backdrop { opacity: 1; }
  .booking-detail-modal .bdm-panel {
    opacity: 0;
    transform: translateY(1rem) scale(0.97);
    transition: opacity 0.38s cubic-bezier(0.22, 1, 0.36, 1), transform 0.38s cubic-bezier(0.22, 1, 0.36, 1);
  }
  .booking-detail-modal.bdm-open .bdm-panel {
    opacity: 1;
    transform: translateY(0) scale(1);
  }
  /* Closed modal must not intercept clicks (children with pointer-events:auto still receive hits otherwise). */
  .booking-detail-modal .bdm-backdrop,
  .booking-detail-modal .bdm-panel {
    pointer-events: none;
  }
  .booking-detail-modal.bdm-open .bdm-backdrop,
  .booking-detail-modal.bdm-open .bdm-panel {
    pointer-events: auto;
  }
  .user-cancel-modal .ucm-backdrop,
  .user-cancel-modal .ucm-panel {
    opacity: 0;
    transition: opacity 0.35s ease, transform 0.38s cubic-bezier(0.22, 1, 0.36, 1);
  }
  .user-cancel-modal .ucm-panel {
    transform: translateY(1rem) scale(0.97);
  }
  .user-cancel-modal.ucm-open .ucm-backdrop,
  .user-cancel-modal.ucm-open .ucm-panel {
    opacity: 1;
  }
  .user-cancel-modal.ucm-open .ucm-panel {
    transform: translateY(0) scale(1);
  }
  .user-cancel-modal .ucm-backdrop,
  .user-cancel-modal .ucm-panel {
    pointer-events: none;
  }
  .user-cancel-modal.ucm-open .ucm-backdrop,
  .user-cancel-modal.ucm-open .ucm-panel {
    pointer-events: auto;
  }
  /* Reschedule modal — calendar styling aligned with public book flow */
  .rsm-cal-card { background: #fff; border-radius: 1rem; border: 1px solid #e6e0ea; overflow: hidden; }
  .rsm-cal-grid { display: grid; grid-template-columns: repeat(7, 1fr); gap: 4px; }
  .rsm-cal-day {
    width: 100%; aspect-ratio: 1; max-height: 40px; border-radius: 50%; display: flex; align-items: center; justify-content: center;
    font-size: 0.8rem; font-weight: 600; cursor: default; transition: background 0.15s;
  }
  .rsm-cal-day.available { color: #1c1b21; cursor: pointer; }
  .rsm-cal-day.available:hover { background: #ece6ef; }
  .rsm-cal-day.unavailable { color: #cac4d3; }
  .rsm-cal-day.unavailable-future { color: #ba1a1a; background: #fff1f1; text-decoration: line-through; font-size: 0.7rem; }
  .rsm-cal-day.blocked-by-artist { color: #5c4033; background: #f4ebe4; font-size: 0.68rem; }
  .rsm-cal-day.fully-booked-day { color: #494552; background: #ece8f0; font-size: 0.68rem; }
  .rsm-cal-day.selected { background: #310f7a; color: #fff; }
  .rsm-cal-day.today { border: 2px solid #310f7a; }
  .rsm-cal-day.empty { pointer-events: none; background: transparent; }
  .rsm-slot { padding: 0.65rem 1rem; border-radius: 0.75rem; border: 1.5px solid #cac4d3; font-size: 0.875rem; font-weight: 600; cursor: pointer; text-align: center; color: #310f7a; background: #fff; }
  .rsm-slot:hover { border-color: #310f7a; background: #f8f1fb; }
  .rsm-slot.selected { background: #310f7a; color: #fff; border-color: #310f7a; }
  .user-rsm-modal .rsm-cal-backdrop { opacity: 0; transition: opacity 0.35s ease; }
  .user-rsm-modal.rsm-open .rsm-cal-backdrop { opacity: 1; }
  .user-rsm-modal .rsm-cal-panel {
    opacity: 0;
    transform: translateY(1rem) scale(0.97);
    transition: opacity 0.38s cubic-bezier(0.22, 1, 0.36, 1), transform 0.38s cubic-bezier(0.22, 1, 0.36, 1);
  }
  .user-rsm-modal.rsm-open .rsm-cal-panel {
    opacity: 1;
    transform: translateY(0) scale(1);
  }
  .user-rsm-modal .rsm-cal-backdrop,
  .user-rsm-modal .rsm-cal-panel { pointer-events: none; }
  .user-rsm-modal.rsm-open .rsm-cal-backdrop,
  .user-rsm-modal.rsm-open .rsm-cal-panel { pointer-events: auto; }
</style>
@endsection

@section('content')
<main class="main-content flex-1 min-h-screen">
    <div class="p-6 md:p-10 lg:p-12 max-w-6xl">

      <!-- Page Header -->
      <div class="mb-6">
        <h2 class="text-2xl font-extrabold text-on-surface tracking-tight">My Bookings</h2>
        <p class="text-sm text-on-surface-variant mt-1">{{ $bookings->count() }} bookings total</p>
      </div>

      <!-- Filter Pills -->
      <div class="flex flex-wrap gap-2 mb-8">
        <button class="filter-pill active text-sm font-semibold px-4 py-2 rounded-full border border-outline-variant/30 bg-surface-container-low" onclick="filterBookings(this,'all')">All</button>
        <button class="filter-pill text-sm font-semibold px-4 py-2 rounded-full border border-outline-variant/30 bg-surface-container-low text-on-surface-variant" onclick="filterBookings(this,'upcoming')">Upcoming</button>
        <button class="filter-pill text-sm font-semibold px-4 py-2 rounded-full border border-outline-variant/30 bg-surface-container-low text-on-surface-variant" onclick="filterBookings(this,'completed')">Completed</button>
        <button class="filter-pill text-sm font-semibold px-4 py-2 rounded-full border border-outline-variant/30 bg-surface-container-low text-on-surface-variant" onclick="filterBookings(this,'cancelled')">Cancelled</button>
      </div>

      <!-- Booking Cards -->
      <div class="space-y-5 mb-8">

        @foreach ($bookings as $booking)
            @php
                $cwRaw = strtolower(trim((string) ($booking->artist->userDetail?->cancellation_window ?? '48h')));
                if (str_contains($cwRaw, 'w')) {
                    preg_match('/(\d+)/', $cwRaw, $cwM);
                    $artistCancellationWindowHours = (int) ($cwM[1] ?? 1) * 168;
                    $nWeeks = (int) ($cwM[1] ?? 1);
                    $cancelWindowHuman = $nWeeks === 1 ? '1 week' : "{$nWeeks} weeks";
                } elseif (str_contains($cwRaw, 'day')) {
                    preg_match('/(\d+)/', $cwRaw, $cwM);
                    $artistCancellationWindowHours = (int) ($cwM[1] ?? 1) * 24;
                    $nDays = (int) ($cwM[1] ?? 1);
                    $cancelWindowHuman = $nDays === 1 ? '1 day' : "{$nDays} days";
                } else {
                    preg_match('/(\d+)/', $cwRaw, $cwM);
                    $artistCancellationWindowHours = (int) ($cwM[1] ?? 48);
                    $cancelWindowHuman = $artistCancellationWindowHours === 1
                        ? '1 hour'
                        : "{$artistCancellationWindowHours} hours";
                }

                $sessionStartUtc = \Carbon\Carbon::parse($booking->booking_date->format('Y-m-d') . ' ' . $booking->start_time_utc);
                $sessionEndUtc = \Carbon\Carbon::parse($booking->booking_date->format('Y-m-d') . ' ' . $booking->end_time_utc);
                $cancellationDeadline = $sessionStartUtc->copy()->subHours($artistCancellationWindowHours);
                $canFullRefund = now()->lt($cancellationDeadline);
                $cardFilterStatus = $sessionEndUtc->isPast() ? 'completed' : 'upcoming';
            @endphp
            @if($booking->status == 'confirmed' || (!empty($artistReschedulePending[$booking->id]) && $booking->status == 'pending'))
                <div class="booking-card bg-white rounded-2xl shadow-sm border border-outline-variant/20 p-6" data-status="{{ $cardFilterStatus }}">
                    <div class="flex flex-col sm:flex-row sm:items-start gap-4">
                        <div class="flex-1 min-w-0">
                            <div class="flex flex-wrap items-center gap-2 mb-3">
                                <span class="inline-flex items-center gap-1.5 {{ !empty($artistReschedulePending[$booking->id]) ? 'bg-amber-50 text-amber-800' : 'bg-green-50 text-green-700' }} text-xs font-semibold px-3 py-1 rounded-full">
                                    <span class="w-1.5 h-1.5 {{ !empty($artistReschedulePending[$booking->id]) ? 'bg-amber-500' : 'bg-green-500' }} rounded-full"></span>
                                    {{ !empty($artistReschedulePending[$booking->id]) ? 'Pending reschedule' : 'Confirmed' }}
                                </span>
                            {{-- <span class="text-xs text-on-surface-variant font-medium">{{ $booking->tattoo->name }}</span> --}}
                            </div>
                            <h3 class="font-bold text-on-surface text-lg mb-2">{{ $booking->tattoo?->title ?? 'Tattoo session' }}</h3>
                            <div class="flex items-center gap-3 mb-3">
                                <div class="w-8 h-8 rounded-full bg-gradient-to-br from-primary to-primary-container flex items-center justify-center flex-shrink-0">
                                    {{-- <span class="text-white text-[/10px] font-bold">{{ $booking->artist->first_name[0] }}{{ $booking->artist->last_name[0] }}</span> --}}
                                    <img src="{{ ($booking->artist->userDetail && $booking->artist->userDetail->avatar != "") ? asset($booking->artist->userDetail->avatar) : asset('design/images/icons/avatar.jpg') }}" alt="{{ $booking->artist->first_name }}" class="w-full h-full object-cover rounded-full">
                                </div>
                                <span class="text-sm text-on-surface-variant">{{ $booking->artist->first_name }} {{ $booking->artist->last_name }}</span>
                            </div>
                            <div class="flex flex-wrap gap-x-6 gap-y-2 text-sm text-on-surface-variant mb-3">
                                <span class="flex items-center gap-1.5">
                                    <span class="material-symbols-outlined text-base text-primary">calendar_today</span>
                                        <strong class="text-on-surface">{{ $booking->booking_date->format('F d, Y') }}</strong>
                                        {{-- strings of time to user's timezone --}}
                                        @php
                                            $start_time = \Carbon\Carbon::createFromFormat('H:i:s', $booking->start_time_utc)->setTimezone($booking->timezone);
                                            $end_time = \Carbon\Carbon::createFromFormat('H:i:s', $booking->end_time_utc)->setTimezone($booking->timezone);
                                        @endphp
                                    <span class="text-on-surface-variant"> at {{ $start_time->format('g:i A') }} - {{ $end_time->format('g:i A') }}</span>
                                </span>
                                <a href="{{ $booking->artist->userDetail->google_maps_link }}" target="_blank" class="flex items-center gap-1.5">
                                    <span class="material-symbols-outlined text-base text-primary">location_on</span>
                                    {{ $booking->artist->userDetail->studio_name }}
                                </a>
                            </div>
                            <div class="flex flex-wrap gap-x-6 gap-y-1 text-sm text-on-surface-variant mb-3">
                            <span><strong class="text-on-surface">Deposit paid:</strong> €{{ $booking->deposit_amount }}</span>
                            <span><strong class="text-on-surface">Remaining:</strong> €{{ $booking->tattoo->min_price - $booking->deposit_amount }}</span>
                            </div>
                            @php
                                $artistRequested = !empty($artistReschedulePending[$booking->id]);
                            @endphp
                            <!-- AR prompt -->
                            <div class="flex items-center gap-2 bg-surface-container-low rounded-lg px-3 py-2 mb-3">
                            <span class="text-sm">📲</span>
                            <p class="text-xs text-on-surface-variant">Not sure about placement? Try it in AR first</p>
                            <a href="#" class="text-xs font-semibold text-primary hover:underline ml-auto whitespace-nowrap">Open App →</a>
                            </div>
                            <div class="rounded-2xl border p-5 mb-3 text-sm overflow-hidden {{ $canFullRefund ? 'border-green-200/90 bg-gradient-to-br from-green-50 via-white to-emerald-50/80 shadow-sm shadow-green-900/[0.06]' : 'border-outline-variant/40 bg-surface-container-low shadow-sm' }}">
                                <div class="flex gap-4">
                                    <div class="flex-shrink-0 w-11 h-11 rounded-2xl flex items-center justify-center {{ $canFullRefund ? 'bg-green-100 text-green-700 ring-1 ring-green-200/60' : 'bg-surface-container-highest text-on-surface-variant ring-1 ring-outline-variant/20' }}">
                                        <span class="material-symbols-outlined text-[24px]">{{ $canFullRefund ? 'verified_user' : 'schedule' }}</span>
                                    </div>
                                    <div class="min-w-0 flex-1">
                                        @if ($canFullRefund)
                                            <p class="font-semibold text-green-950 mb-2 leading-snug">You are still within this artist's full-refund cancellation window.</p>
                                        @else
                                            <p class="font-semibold text-on-surface mb-2 leading-snug">You are inside this artist's no-refund cancellation window.</p>
                                        @endif
                                        <ul class="list-disc pl-5 space-y-1.5 text-on-surface-variant leading-relaxed">
                                            <li>Full refund if canceled at least <strong class="text-on-surface">{{ $cancelWindowHuman }}</strong> before your appointment.</li>
                                            <li>No refund if canceled less than <strong class="text-on-surface">{{ $cancelWindowHuman }}</strong> before your appointment.</li>
                                        </ul>
                                    </div>
                                </div>
                                <div class="mt-5 pt-4 flex justify-end {{ $canFullRefund ? 'border-t border-green-200/50' : 'border-t border-outline-variant/25' }}">
                                    <button type="button"
                                        class="js-user-cancel-open w-full sm:w-auto inline-flex items-center justify-center gap-2 rounded-full px-5 py-2.5 text-sm font-semibold transition-all active:scale-[0.98] {{ $canFullRefund ? 'bg-white text-error border border-red-200/90 shadow-sm hover:bg-red-50 hover:border-red-300' : 'bg-white text-on-surface border border-outline-variant/50 shadow-sm hover:bg-surface-container-highest hover:border-outline-variant' }}"
                                        data-booking-id="{{ $booking->id }}"
                                        data-can-full-refund="{{ $canFullRefund ? '1' : '0' }}"
                                        data-window-human="{{ e($cancelWindowHuman) }}">
                                        <span class="material-symbols-outlined text-[20px]">event_busy</span>
                                        Cancel booking
                                    </button>
                                </div>
                            </div>
                            @php
                                $re = $rescheduleEligibility[$booking->id] ?? ['can_reschedule' => false, 'message' => ''];
                            @endphp
                            @if ($artistRequested)
                                <div class="rounded-xl border border-blue-200 bg-blue-50/70 px-4 py-3 mb-3 text-sm">
                                    <p class="font-semibold text-blue-900">Your artist requested to reschedule this booking.</p>
                                    @if (!empty($booking->reschedule_reason))
                                        <p class="mt-1 text-blue-900/80">Reason: {{ $booking->reschedule_reason }}</p>
                                    @endif
                                </div>
                            @endif
                            <div class="flex flex-wrap gap-2 items-center">
                            <button type="button" class="js-booking-detail-open text-sm font-semibold text-primary hover:underline text-left" data-booking-id="{{ $booking->id }}">View Details</button>
                            @if (!empty($re['can_reschedule']) || $artistRequested)
                                <span class="text-outline">·</span>
                                <button type="button"
                                    class="js-user-reschedule-open text-sm font-semibold text-primary hover:underline text-left"
                                    data-booking-id="{{ $booking->id }}">{{ $artistRequested ? 'Choose New Time' : 'Reschedule' }}</button>
                            @endif
                            </div>
                            @if (empty($re['can_reschedule']) && !$artistRequested && !empty($re['message']))
                                <p class="text-xs text-on-surface-variant mt-2 max-w-xl">{{ $re['message'] }}</p>
                            @endif
                        </div>
                    </div>
                </div>
            @elseif($booking->status == 'cancelled')
                @php
                    $refundAmt = (float) ($booking->refund_amount ?? 0);
                    $forfeited = (float) ($booking->deposit_forfeited ?? 0);
                    $byClient = (int) ($booking->cancelled_by ?? 0) === (int) $booking->user_id;
                    $tz = $booking->timezone ?: 'UTC';
                    $wasStart = \Carbon\Carbon::createFromFormat('H:i:s', (string) $booking->start_time_utc)->setTimezone($tz);
                    $artistUd = $booking->artist?->userDetail;
                    $rebookUrl = ($booking->tattoo && $artistUd?->user_name && $booking->tattoo->slug)
                        ? route('public.tattoo.book', ['user_name' => $artistUd->user_name, 'tattoo_slug' => $booking->tattoo->slug])
                        : null;
                @endphp
                <div class="booking-card bg-white rounded-2xl shadow-sm border border-outline-variant/20 p-6" data-status="cancelled">
                    <div class="flex flex-col sm:flex-row sm:items-start gap-4">
                        <div class="flex-1 min-w-0">
                            <div class="flex flex-wrap items-center gap-2 mb-3">
                                <span class="inline-flex items-center gap-1.5 bg-red-50 text-red-700 text-xs font-semibold px-3 py-1 rounded-full">
                                    <span class="w-1.5 h-1.5 bg-red-500 rounded-full"></span> Cancelled
                                </span>
                                <span class="text-xs text-on-surface-variant font-medium">#INK-{{ str_pad((string) $booking->id, 6, '0', STR_PAD_LEFT) }}</span>
                            </div>
                            <h3 class="font-bold text-on-surface text-lg mb-2">{{ $booking->tattoo?->title ?? 'Tattoo session' }}</h3>
                            <div class="flex items-center gap-3 mb-3">
                                <div class="w-8 h-8 rounded-full bg-gradient-to-br from-primary to-primary-container flex items-center justify-center flex-shrink-0 overflow-hidden">
                                    <img src="{{ ($artistUd && $artistUd->avatar != '') ? asset($artistUd->avatar) : asset('design/images/icons/avatar.jpg') }}" alt="{{ $booking->artist?->first_name ?? '' }}" class="w-full h-full object-cover rounded-full">
                                </div>
                                <span class="text-sm text-on-surface-variant">{{ $booking->artist?->first_name }} {{ $booking->artist?->last_name }}</span>
                            </div>
                            <div class="flex flex-wrap gap-x-6 gap-y-2 text-sm text-on-surface-variant mb-3">
                                <span class="flex items-center gap-1.5">
                                    <span class="material-symbols-outlined text-base text-outline">calendar_today</span>
                                    <span class="line-through">Was {{ $booking->booking_date->format('F j, Y') }} at {{ $wasStart->format('g:i A') }}</span>
                                </span>
                            </div>
                            <div class="rounded-xl border px-4 py-3 mb-3 text-sm {{ $refundAmt > 0.009 ? 'border-green-200 bg-green-50/70 text-green-950' : 'border-amber-200 bg-amber-50/70 text-amber-950' }}">
                                <p class="font-semibold">
                                    @if ($byClient)
                                        You cancelled this booking.
                                    @else
                                        The artist cancelled this booking.
                                    @endif
                                </p>
                                @if ($refundAmt > 0.009)
                                    <p class="mt-1.5 text-on-surface">
                                        Refund: <strong class="tabular-nums">€{{ number_format($refundAmt, 2) }}</strong>
                                        @if ($booking->refund_status)
                                            <span class="text-on-surface-variant font-normal text-xs">({{ $booking->refund_status }})</span>
                                        @endif
                                    </p>
                                @else
                                    <p class="mt-1.5 text-on-surface-variant leading-snug">No refund was issued for the amount you paid, based on the cancellation policy and timing.</p>
                                    @if ($forfeited > 0.009)
                                        <p class="mt-1 text-xs text-on-surface-variant">Deposit not refunded: <strong class="text-on-surface tabular-nums">€{{ number_format($forfeited, 2) }}</strong></p>
                                    @endif
                                @endif
                                @if ($booking->cancelled_at)
                                    <p class="mt-2 text-xs text-on-surface-variant">{{ $booking->cancelled_at->timezone($tz)->format('F j, Y \a\t g:i A') }}</p>
                                @endif
                            </div>
                            <div class="flex flex-wrap gap-2">
                                <button type="button" class="js-booking-detail-open text-sm font-semibold text-primary hover:underline text-left" data-booking-id="{{ $booking->id }}">View Details</button>
                                @if ($rebookUrl)
                                    <span class="text-outline">·</span>
                                    <a href="{{ $rebookUrl }}" class="text-sm font-semibold text-primary hover:underline">Rebook</a>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            @endif
        @endforeach

        <!-- Booking 1: Confirmed -->
        {{-- <div class="booking-card bg-white rounded-2xl shadow-sm border border-outline-variant/20 p-6" data-status="upcoming">
          <div class="flex flex-col sm:flex-row sm:items-start gap-4">
            <div class="flex-1 min-w-0">
              <div class="flex flex-wrap items-center gap-2 mb-3">
                <span class="inline-flex items-center gap-1.5 bg-green-50 text-green-700 text-xs font-semibold px-3 py-1 rounded-full">
                  <span class="w-1.5 h-1.5 bg-green-500 rounded-full"></span> Confirmed
                </span>
                <span class="text-xs text-on-surface-variant font-medium">Session 2 of 4</span>
              </div>
              <h3 class="font-bold text-on-surface text-lg mb-2">Dragon Sleeve</h3>
              <div class="flex items-center gap-3 mb-3">
                <div class="w-8 h-8 rounded-full bg-gradient-to-br from-primary to-primary-container flex items-center justify-center flex-shrink-0">
                  <span class="text-white text-[10px] font-bold">JI</span>
                </div>
                <span class="text-sm text-on-surface-variant">Julian Ink</span>
              </div>
              <div class="flex flex-wrap gap-x-6 gap-y-2 text-sm text-on-surface-variant mb-3">
                <span class="flex items-center gap-1.5">
                  <span class="material-symbols-outlined text-base text-primary">calendar_today</span>
                  <strong class="text-on-surface">April 15, 2:00 PM</strong>
                </span>
                <span class="flex items-center gap-1.5">
                  <span class="material-symbols-outlined text-base text-primary">location_on</span>
                  Ink District Studio, Amsterdam
                </span>
              </div>
              <div class="flex flex-wrap gap-x-6 gap-y-1 text-sm text-on-surface-variant mb-3">
                <span><strong class="text-on-surface">Deposit paid:</strong> €240</span>
                <span><strong class="text-on-surface">Remaining:</strong> €710</span>
              </div>
              <!-- AR prompt -->
              <div class="flex items-center gap-2 bg-surface-container-low rounded-lg px-3 py-2 mb-3">
                <span class="text-sm">📲</span>
                <p class="text-xs text-on-surface-variant">Not sure about placement? Try it in AR first</p>
                <a href="#" class="text-xs font-semibold text-primary hover:underline ml-auto whitespace-nowrap">Open App →</a>
              </div>
              <div class="flex flex-wrap gap-2">
                <a href="#" class="text-sm font-semibold text-primary hover:underline">View Details</a>
                <span class="text-outline">·</span>
                <a href="#" class="text-sm font-semibold text-primary hover:underline">Reschedule</a>
                <span class="text-outline">·</span>
                <a href="#" class="text-sm font-semibold text-error hover:underline">Cancel</a>
                <span class="text-outline">·</span>
                <a href="client-inbox.html" class="text-sm font-semibold text-primary hover:underline">Message Artist</a>
              </div>
            </div>
          </div>
        </div> --}}

        <!-- Booking 2: Rescheduled -->
        {{-- <div class="booking-card bg-white rounded-2xl shadow-sm border border-blue-200 p-6" data-status="rescheduled">
          <div class="flex flex-col sm:flex-row sm:items-start gap-4">
            <div class="flex-1 min-w-0">
              <div class="flex flex-wrap items-center gap-2 mb-3">
                <span class="inline-flex items-center gap-1.5 bg-blue-50 text-blue-700 text-xs font-semibold px-3 py-1 rounded-full">
                  <span class="w-1.5 h-1.5 bg-blue-500 rounded-full"></span> Rescheduled
                </span>
                <span class="text-xs text-on-surface-variant font-medium">Single session</span>
              </div>
              <h3 class="font-bold text-on-surface text-lg mb-2">Rose Mandala</h3>
              <div class="flex items-center gap-3 mb-3">
                <div class="w-8 h-8 rounded-full bg-gradient-to-br from-rose-300 to-rose-400 flex items-center justify-center flex-shrink-0">
                  <span class="text-white text-[10px] font-bold">AF</span>
                </div>
                <span class="text-sm text-on-surface-variant">Alex Fine Line</span>
              </div>
              <div class="flex flex-wrap gap-x-6 gap-y-2 text-sm text-on-surface-variant mb-3">
                <span class="flex items-center gap-1.5">
                  <span class="material-symbols-outlined text-base text-blue-600">calendar_today</span>
                  <span class="line-through text-outline">April 22, 10:00 AM</span>
                  <span class="material-symbols-outlined text-base text-blue-600">arrow_forward</span>
                  <strong class="text-blue-700">April 29, 10:00 AM</strong>
                </span>
              </div>
              <div class="bg-blue-50/50 rounded-lg px-3 py-2 mb-3 text-xs text-blue-700 flex items-center gap-2">
                <span class="material-symbols-outlined text-sm">info</span>
                Rescheduled by artist — originally April 22. New date confirmed.
              </div>
              <div class="flex flex-wrap gap-x-6 gap-y-1 text-sm text-on-surface-variant mb-3">
                <span class="flex items-center gap-1.5">
                  <span class="material-symbols-outlined text-base text-primary">location_on</span>
                  Studio Rosa, Berlin
                </span>
              </div>
              <div class="flex flex-wrap gap-x-6 gap-y-1 text-sm text-on-surface-variant mb-3">
                <span><strong class="text-on-surface">Deposit paid:</strong> €150</span>
                <span><strong class="text-on-surface">Remaining:</strong> €150</span>
              </div>
              <div class="flex flex-wrap gap-2">
                <a href="#" class="text-sm font-semibold text-primary hover:underline">View Details</a>
                <span class="text-outline">·</span>
                <a href="#" class="text-sm font-semibold text-primary hover:underline">Reschedule</a>
                <span class="text-outline">·</span>
                <a href="#" class="text-sm font-semibold text-error hover:underline">Cancel</a>
                <span class="text-outline">·</span>
                <a href="client-inbox.html" class="text-sm font-semibold text-primary hover:underline">Message Artist</a>
              </div>
            </div>
          </div>
        </div> --}}

        <!-- Booking 3: Upcoming -->
        {{-- <div class="booking-card bg-white rounded-2xl shadow-sm border border-outline-variant/20 p-6" data-status="upcoming">
          <div class="flex flex-col sm:flex-row sm:items-start gap-4">
            <div class="flex-1 min-w-0">
              <div class="flex flex-wrap items-center gap-2 mb-3">
                <span class="inline-flex items-center gap-1.5 bg-purple-50 text-purple-700 text-xs font-semibold px-3 py-1 rounded-full">
                  <span class="w-1.5 h-1.5 bg-purple-500 rounded-full"></span> Upcoming
                </span>
                <span class="text-xs text-on-surface-variant font-medium">Session 1 of 2</span>
              </div>
              <h3 class="font-bold text-on-surface text-lg mb-2">Ocean Waves</h3>
              <div class="flex items-center gap-3 mb-3">
                <div class="w-8 h-8 rounded-full bg-gradient-to-br from-primary to-primary-container flex items-center justify-center flex-shrink-0">
                  <span class="text-white text-[10px] font-bold">JI</span>
                </div>
                <span class="text-sm text-on-surface-variant">Julian Ink</span>
              </div>
              <div class="flex flex-wrap gap-x-6 gap-y-2 text-sm text-on-surface-variant mb-3">
                <span class="flex items-center gap-1.5">
                  <span class="material-symbols-outlined text-base text-primary">calendar_today</span>
                  <strong class="text-on-surface">May 3, 11:00 AM</strong>
                </span>
                <span class="flex items-center gap-1.5">
                  <span class="material-symbols-outlined text-base text-primary">location_on</span>
                  Ink District Studio, Amsterdam
                </span>
              </div>
              <div class="flex flex-wrap gap-x-6 gap-y-1 text-sm text-on-surface-variant mb-3">
                <span><strong class="text-on-surface">Deposit paid:</strong> €180</span>
                <span><strong class="text-on-surface">Remaining:</strong> €270</span>
              </div>
              <!-- AR prompt -->
              <div class="flex items-center gap-2 bg-surface-container-low rounded-lg px-3 py-2 mb-3">
                <span class="text-sm">📲</span>
                <p class="text-xs text-on-surface-variant">Not sure about placement? Try it in AR first</p>
                <a href="#" class="text-xs font-semibold text-primary hover:underline ml-auto whitespace-nowrap">Open App →</a>
              </div>
              <div class="flex flex-wrap gap-2">
                <a href="#" class="text-sm font-semibold text-primary hover:underline">View Details</a>
                <span class="text-outline">·</span>
                <a href="#" class="text-sm font-semibold text-primary hover:underline">Reschedule</a>
                <span class="text-outline">·</span>
                <a href="#" class="text-sm font-semibold text-error hover:underline">Cancel</a>
                <span class="text-outline">·</span>
                <a href="client-inbox.html" class="text-sm font-semibold text-primary hover:underline">Message Artist</a>
              </div>
            </div>
          </div>
        </div> --}}

        <!-- Booking 4: Completed -->
        {{-- <div class="booking-card bg-white rounded-2xl shadow-sm border border-outline-variant/20 p-6" data-status="completed">
          <div class="flex flex-col sm:flex-row sm:items-start gap-4">
            <div class="flex-1 min-w-0">
              <div class="flex flex-wrap items-center gap-2 mb-3">
                <span class="inline-flex items-center gap-1.5 bg-gray-100 text-gray-600 text-xs font-semibold px-3 py-1 rounded-full">
                  <span class="w-1.5 h-1.5 bg-gray-400 rounded-full"></span> Completed
                </span>
              </div>
              <h3 class="font-bold text-on-surface text-lg mb-2">Botanical Forearm</h3>
              <div class="flex items-center gap-3 mb-3">
                <div class="w-8 h-8 rounded-full bg-gradient-to-br from-emerald-300 to-emerald-400 flex items-center justify-center flex-shrink-0">
                  <span class="text-white text-[10px] font-bold">MT</span>
                </div>
                <span class="text-sm text-on-surface-variant">Maya Tattoo</span>
              </div>
              <div class="flex flex-wrap gap-x-6 gap-y-2 text-sm text-on-surface-variant mb-3">
                <span class="flex items-center gap-1.5">
                  <span class="material-symbols-outlined text-base text-outline">calendar_today</span>
                  Completed March 10, 2026
                </span>
                <span class="flex items-center gap-1.5">
                  <span class="material-symbols-outlined text-base text-outline">location_on</span>
                  Botanical Ink Studio, Munich
                </span>
              </div>
              <div class="flex flex-wrap gap-x-6 gap-y-1 text-sm text-on-surface-variant mb-3">
                <span><strong class="text-on-surface">Total paid:</strong> €350</span>
              </div>
              <div class="flex flex-wrap gap-2">
                <a href="#" class="text-sm font-semibold text-primary hover:underline">View Details</a>
                <span class="text-outline">·</span>
                <a href="#" class="text-sm font-semibold text-primary hover:underline">Leave Review</a>
                <span class="text-outline">·</span>
                <a href="#" class="text-sm font-semibold text-primary hover:underline">Book Again</a>
              </div>
            </div>
          </div>
        </div> --}}

        <!-- Booking 5: Cancelled -->
        {{-- <div class="booking-card bg-white rounded-2xl shadow-sm border border-outline-variant/20 p-6" data-status="cancelled">
          <div class="flex flex-col sm:flex-row sm:items-start gap-4">
            <div class="flex-1 min-w-0">
              <div class="flex flex-wrap items-center gap-2 mb-3">
                <span class="inline-flex items-center gap-1.5 bg-red-50 text-red-700 text-xs font-semibold px-3 py-1 rounded-full">
                  <span class="w-1.5 h-1.5 bg-red-500 rounded-full"></span> Cancelled
                </span>
              </div>
              <h3 class="font-bold text-on-surface text-lg mb-2">Skull & Serpent</h3>
              <div class="flex items-center gap-3 mb-3">
                <div class="w-8 h-8 rounded-full bg-gradient-to-br from-gray-400 to-gray-500 flex items-center justify-center flex-shrink-0">
                  <span class="text-white text-[10px] font-bold">NB</span>
                </div>
                <span class="text-sm text-on-surface-variant">Nina Blackwork</span>
              </div>
              <div class="flex flex-wrap gap-x-6 gap-y-2 text-sm text-on-surface-variant mb-3">
                <span class="flex items-center gap-1.5">
                  <span class="material-symbols-outlined text-base text-outline">calendar_today</span>
                  <span class="line-through">Was March 5, 2026</span>
                </span>
              </div>
              <p class="text-sm text-red-600 bg-red-50/50 rounded-lg px-3 py-2 mb-3">Cancelled by client — deposit refunded</p>
              <div class="flex flex-wrap gap-2">
                <a href="#" class="text-sm font-semibold text-primary hover:underline">View Details</a>
                <span class="text-outline">·</span>
                <a href="#" class="text-sm font-semibold text-primary hover:underline">Rebook</a>
              </div>
            </div>
          </div>
        </div> --}}

        <!-- Booking 6: Pending -->
        {{-- <div class="booking-card bg-white rounded-2xl shadow-sm border border-outline-variant/20 p-6" data-status="upcoming">
          <div class="flex flex-col sm:flex-row sm:items-start gap-4">
            <div class="flex-1 min-w-0">
              <div class="flex flex-wrap items-center gap-2 mb-3">
                <span class="inline-flex items-center gap-1.5 bg-amber-50 text-amber-700 text-xs font-semibold px-3 py-1 rounded-full">
                  <span class="w-1.5 h-1.5 bg-amber-500 rounded-full"></span> Pending
                </span>
                <span class="text-xs text-on-surface-variant font-medium">Session 3 of 4</span>
              </div>
              <h3 class="font-bold text-on-surface text-lg mb-2">Dragon Sleeve — Session 3</h3>
              <div class="flex items-center gap-3 mb-3">
                <div class="w-8 h-8 rounded-full bg-gradient-to-br from-primary to-primary-container flex items-center justify-center flex-shrink-0">
                  <span class="text-white text-[10px] font-bold">JI</span>
                </div>
                <span class="text-sm text-on-surface-variant">Julian Ink</span>
              </div>
              <div class="flex flex-wrap gap-x-6 gap-y-2 text-sm text-on-surface-variant mb-3">
                <span class="flex items-center gap-1.5">
                  <span class="material-symbols-outlined text-base text-primary">calendar_today</span>
                  <strong class="text-on-surface">May 20, 2:00 PM</strong>
                </span>
                <span class="flex items-center gap-1.5">
                  <span class="material-symbols-outlined text-base text-primary">location_on</span>
                  Ink District Studio, Amsterdam
                </span>
              </div>
              <div class="flex flex-wrap gap-x-6 gap-y-1 text-sm text-on-surface-variant mb-3">
                <span><strong class="text-on-surface">Deposit:</strong> €240 due</span>
              </div>
              <div class="flex flex-wrap gap-2">
                <a href="#" class="text-sm font-semibold text-primary hover:underline">View Details</a>
                <span class="text-outline">·</span>
                <a href="#" class="text-sm font-semibold text-primary hover:underline">Reschedule</a>
                <span class="text-outline">·</span>
                <a href="#" class="text-sm font-semibold text-error hover:underline">Cancel</a>
                <span class="text-outline">·</span>
                <a href="client-inbox.html" class="text-sm font-semibold text-primary hover:underline">Message Artist</a>
              </div>
            </div>
          </div>
        </div> --}}

      </div>

      <!-- Aftercare Guide + App Banner -->
      {{-- <div class="flex flex-col sm:flex-row gap-4 mb-8">
        <a href="https://inkjin.com/tattoo-guides/" class="flex-1 bg-surface-container-low rounded-2xl p-5 flex items-center gap-4 hover:bg-surface-container transition-colors">
          <span class="text-2xl">📖</span>
          <div>
            <p class="font-bold text-on-surface text-sm">Prepare for your session</p>
            <p class="text-xs text-on-surface-variant mt-0.5">Read our Aftercare Guide →</p>
          </div>
        </a>
        <div class="flex-1 bg-surface-container-low rounded-2xl p-5 flex items-center gap-4">
          <span class="text-2xl">🔔</span>
          <div class="flex-1">
            <p class="font-bold text-on-surface text-sm">Never miss an appointment</p>
            <p class="text-xs text-on-surface-variant mt-0.5">Download the app for push notifications</p>
          </div>
          <div class="flex items-center gap-2">
            <a href="#" class="text-xs font-semibold text-primary hover:underline">App Store</a>
            <span class="text-outline text-xs">·</span>
            <a href="#" class="text-xs font-semibold text-primary hover:underline">Google Play</a>
          </div>
        </div>
      </div> --}}

    </div>
</main>
@endsection

@section('modals')
<div id="bookingDetailModal" class="booking-detail-modal fixed inset-0 z-[200] opacity-0 pointer-events-none transition-opacity duration-300 ease-out" aria-hidden="true" role="dialog" aria-labelledby="bookingDetailModalTitle">
  <div class="bdm-backdrop absolute inset-0 bg-primary/55 backdrop-blur-[2px]" data-close-booking-modal></div>
  <div class="relative flex min-h-full items-end sm:items-center justify-center p-4 sm:p-6 pointer-events-none">
    <div class="bdm-panel w-full max-w-lg rounded-2xl bg-white shadow-2xl shadow-primary/15 border border-outline-variant/30 overflow-hidden max-h-[90vh] flex flex-col">
      <div class="relative shrink-0 aspect-[21/9] sm:aspect-[2/1] bg-gradient-to-br from-surface-container-low to-surface-container-high">
        <img id="bdmImage" src="" alt="" class="absolute inset-0 w-full h-full object-cover hidden" width="800" height="400" />
        <div id="bdmImageFallback" class="absolute inset-0 flex items-center justify-center text-on-surface-variant">
          <span class="material-symbols-outlined text-5xl opacity-40">brush</span>
        </div>
        <button type="button" class="absolute top-3 right-3 w-10 h-10 rounded-full bg-black/35 text-white flex items-center justify-center hover:bg-black/50 transition-colors" data-close-booking-modal aria-label="Close">
          <span class="material-symbols-outlined text-[22px]">close</span>
        </button>
      </div>
      <div class="flex-1 overflow-y-auto p-6 sm:p-8">
        <p id="bdmReference" class="text-xs font-semibold text-primary tracking-wide uppercase mb-1"></p>
        <h2 id="bookingDetailModalTitle" class="text-xl font-extrabold text-on-surface tracking-tight mb-4"></h2>
        <div class="flex items-center gap-3 mb-6 pb-6 border-b border-outline-variant/25">
          <img id="bdmArtistAvatar" src="" alt="" class="w-11 h-11 rounded-full object-cover ring-2 ring-primary/15 shrink-0" width="44" height="44" />
          <div>
            <p class="text-xs font-semibold text-on-surface-variant uppercase tracking-wider">Artist</p>
            <p id="bdmArtistName" class="text-sm font-bold text-on-surface"></p>
          </div>
        </div>
        <div id="bdmConsultRow" class="hidden mb-5 rounded-xl bg-surface-container-low/80 border border-outline-variant/20 p-4">
          <p class="text-xs font-semibold text-primary uppercase tracking-wide mb-1">Consultation</p>
          <p id="bdmConsultDate" class="text-sm font-semibold text-on-surface"></p>
          <p id="bdmConsultTime" class="text-sm text-on-surface-variant"></p>
          <p id="bdmConsultNote" class="text-xs text-on-surface-variant mt-2 hidden">Separate from your tattoo session below.</p>
        </div>
        <div class="space-y-3 mb-6">
          <div class="flex gap-3 rounded-xl border border-outline-variant/20 bg-surface-container-low/50 px-4 py-3">
            <span class="material-symbols-outlined text-primary shrink-0">calendar_month</span>
            <div>
              <p class="text-xs font-semibold text-on-surface-variant uppercase tracking-wide">Session date</p>
              <p id="bdmDate" class="text-sm font-bold text-on-surface"></p>
              <p id="bdmTime" class="text-sm text-on-surface-variant mt-0.5"></p>
            </div>
          </div>
          <div class="flex gap-3 rounded-xl border border-outline-variant/20 bg-surface-container-low/50 px-4 py-3">
            <span class="material-symbols-outlined text-primary shrink-0">location_on</span>
            <div class="min-w-0 flex-1">
              <p class="text-xs font-semibold text-on-surface-variant uppercase tracking-wide">Location</p>
              <p id="bdmStudio" class="text-sm font-bold text-on-surface"></p>
              <p id="bdmAddress" class="text-sm text-on-surface-variant mt-0.5"></p>
              <a id="bdmMaps" href="#" target="_blank" rel="noopener noreferrer" class="hidden text-xs font-semibold text-primary hover:underline mt-2 inline-flex items-center gap-1">Open in Maps <span class="material-symbols-outlined text-[14px]">open_in_new</span></a>
            </div>
          </div>
        </div>
        <div id="bdmCompletionRow" class="hidden mb-6 rounded-2xl border border-primary/15 bg-primary/5 px-4 py-4">
          <p class="text-xs font-bold text-primary uppercase tracking-wide mb-1">Completion code</p>
          <p class="text-xs text-on-surface-variant mb-3 leading-snug">Share this with your artist after your session so they can mark the booking complete.</p>
          <p id="bdmCompletionCode" class="font-mono text-xl sm:text-2xl font-extrabold tracking-[0.2em] text-on-surface text-center py-2 break-all"></p>
          <p id="bdmCompletionUsedNote" class="hidden text-xs text-on-surface-variant text-center mt-2">This code was already verified with your artist.</p>
        </div>
        <div class="rounded-2xl bg-gradient-to-br from-primary/5 to-primary-container/10 border border-primary/10 p-5">
          <p class="text-xs font-bold text-primary uppercase tracking-wide mb-4">Payment summary</p>
          <dl class="space-y-3 text-sm">
            <div class="flex justify-between gap-4">
              <dt class="text-on-surface-variant">Design price (from)</dt>
              <dd id="bdmMinPrice" class="font-semibold text-on-surface tabular-nums"></dd>
            </div>
            <div class="flex justify-between gap-4">
              <dt class="text-on-surface-variant">Deposit paid</dt>
              <dd id="bdmDeposit" class="font-semibold text-on-surface tabular-nums"></dd>
            </div>
            <div class="flex justify-between gap-4">
              <dt class="text-on-surface-variant">Booking fee</dt>
              <dd id="bdmFee" class="font-semibold text-on-surface tabular-nums"></dd>
            </div>
            <div class="flex justify-between gap-4 pt-2 border-t border-outline-variant/20">
              <dt class="text-on-surface-variant">Total charged now</dt>
              <dd id="bdmTotal" class="font-bold text-primary tabular-nums"></dd>
            </div>
            <div class="flex justify-between gap-4 pt-2 border-t border-primary/15">
              <dt class="font-bold text-on-surface">Remaining at studio</dt>
              <dd id="bdmRemaining" class="font-extrabold text-on-surface tabular-nums"></dd>
            </div>
          </dl>
        </div>
      </div>
    </div>
  </div>
</div>

<div id="userCancelBookingModal" class="user-cancel-modal fixed inset-0 z-[210] opacity-0 pointer-events-none transition-opacity duration-300 ease-out" aria-hidden="true" role="dialog" aria-labelledby="userCancelBookingModalTitle">
  <div class="ucm-backdrop absolute inset-0 bg-primary/55 backdrop-blur-[2px]" data-close-user-cancel-modal></div>
  <div class="relative flex min-h-full items-end sm:items-center justify-center p-4 sm:p-6 pointer-events-none">
    <div class="ucm-panel w-full max-w-md rounded-2xl bg-white shadow-2xl shadow-primary/15 border border-outline-variant/30 overflow-hidden max-h-[90vh] flex flex-col">
      <div class="flex items-center justify-between gap-3 px-5 py-4 border-b border-outline-variant/20 shrink-0">
        <h2 id="userCancelBookingModalTitle" class="text-lg font-extrabold text-on-surface tracking-tight">Cancel booking</h2>
        <button type="button" class="w-10 h-10 rounded-full bg-surface-container-low text-on-surface flex items-center justify-center hover:bg-surface-container-high transition-colors" data-close-user-cancel-modal aria-label="Close">
          <span class="material-symbols-outlined text-[22px]">close</span>
        </button>
      </div>
      <div class="p-5 sm:p-6 overflow-y-auto flex-1">
        <div id="ucmStep1">
          <p id="ucmFetchError" class="hidden text-xs font-semibold text-error mb-3"></p>
          <div id="ucmLeadFull" class="hidden rounded-lg border border-green-200 bg-green-50/70 px-3 py-2 text-sm text-green-950 mb-3">
            You are still within this artist’s full-refund cancellation window.
          </div>
          <div id="ucmLeadLate" class="hidden rounded-lg border border-amber-200 bg-amber-50/70 px-3 py-2 text-sm text-amber-950 mb-3">
            You are inside this artist’s no-refund cancellation window.
          </div>
          <ul id="ucmPolicyList" class="list-disc pl-5 space-y-1.5 text-sm text-on-surface-variant mb-5">
            <li>Full refund if canceled at least <strong id="ucmWinFull" class="text-on-surface"></strong> before your appointment.</li>
            <li>No refund if canceled less than <strong id="ucmWinLate" class="text-on-surface"></strong> before your appointment.</li>
          </ul>
          <label class="flex items-start gap-3 cursor-pointer group">
            <input type="checkbox" id="ucmAck" class="mt-1 rounded border-outline-variant text-primary focus:ring-primary" />
            <span class="text-sm text-on-surface leading-snug">I understand the refund policy above and want to continue.</span>
          </label>
          <p id="ucmAckError" class="hidden text-xs font-semibold text-error mt-2">Please confirm you have read the policy.</p>
          <div class="mt-6 flex flex-col-reverse sm:flex-row sm:justify-end gap-2">
            <button type="button" class="px-4 py-2.5 rounded-xl text-sm font-semibold border border-outline-variant/40 text-on-surface hover:bg-surface-container-low" data-close-user-cancel-modal>Back</button>
            <button type="button" id="ucmNext" disabled class="px-4 py-2.5 rounded-xl text-sm font-semibold bg-primary text-on-primary opacity-50 cursor-not-allowed">Next</button>
          </div>
        </div>
        <div id="ucmStep2" class="hidden">
          <div id="ucmRefundBox" class="mb-4 rounded-xl border border-outline-variant/25 bg-surface-container-low/50 px-4 py-3">
            <p class="text-xs font-semibold text-on-surface-variant uppercase tracking-wide">Estimated refund</p>
            <p id="ucmRefundAmount" class="text-lg font-extrabold text-on-surface mt-1 tabular-nums">—</p>
            <p id="ucmRefundSub" class="text-xs text-on-surface-variant mt-1.5 leading-snug"></p>
          </div>
          <label for="ucmReason" class="block text-sm font-semibold text-on-surface mb-2">Reason for cancellation <span class="text-error">*</span></label>
          <textarea id="ucmReason" rows="4" maxlength="1000" required class="w-full rounded-xl border border-outline-variant/40 px-3 py-2 text-sm text-on-surface focus:ring-2 focus:ring-primary/30 focus:border-primary" placeholder="Describe why you need to cancel…"></textarea>
          <p id="ucmReasonError" class="hidden text-xs font-semibold text-error mt-2"></p>
          <div class="mt-6 flex flex-col-reverse sm:flex-row sm:justify-between gap-2">
            <button type="button" id="ucmBackToStep1" class="px-4 py-2.5 rounded-xl text-sm font-semibold border border-outline-variant/40 text-on-surface hover:bg-surface-container-low">Back</button>
            <button type="button" id="ucmSubmit" class="px-4 py-2.5 rounded-xl text-sm font-semibold bg-error text-white hover:opacity-95 disabled:opacity-50 disabled:cursor-not-allowed">Confirm cancellation</button>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

@include('user.bookings.partials.reschedule_modal')
@endsection

@section('scripts')
<script>
window.filterBookings = function (clickedBtn, mode) {
  var row = clickedBtn && clickedBtn.parentElement;
  if (!row) return;
  row.querySelectorAll('.filter-pill').forEach(function (p) {
    p.classList.remove('active');
    p.classList.add('text-on-surface-variant');
  });
  clickedBtn.classList.add('active');
  clickedBtn.classList.remove('text-on-surface-variant');
  document.querySelectorAll('.booking-card').forEach(function (card) {
    var st = card.getAttribute('data-status') || '';
    var show = mode === 'all' || st === mode;
    card.classList.toggle('hidden', !show);
  });
};
</script>
<script>
(function () {
  var payload = @json($bookingModalPayload ?? []);
  var modal = document.getElementById('bookingDetailModal');
  if (!modal) return;

  function fmtEUR(n) {
    var v = parseFloat(n);
    if (isNaN(v)) v = 0;
    return '€' + v.toFixed(2);
  }

  function openBookingDetail(id) {
    var d = payload[id] || payload[String(id)];
    if (!d) return;

    document.getElementById('bookingDetailModalTitle').textContent = d.tattooTitle || 'Booking';
    document.getElementById('bdmReference').textContent = d.reference || '';
    document.getElementById('bdmArtistName').textContent = d.artistName || '—';
    document.getElementById('bdmArtistAvatar').src = d.artistAvatar || '';
    document.getElementById('bdmArtistAvatar').alt = d.artistName || '';

    var imgEl = document.getElementById('bdmImage');
    var fb = document.getElementById('bdmImageFallback');
    if (d.tattooImage) {
      imgEl.src = d.tattooImage;
      imgEl.classList.remove('hidden');
      imgEl.alt = d.tattooTitle || '';
      fb.classList.add('hidden');
    } else {
      imgEl.removeAttribute('src');
      imgEl.classList.add('hidden');
      fb.classList.remove('hidden');
    }

    var consultRow = document.getElementById('bdmConsultRow');
    var consultNote = document.getElementById('bdmConsultNote');
    if (d.consultation && d.consultation.mode === 'separate') {
      consultRow.classList.remove('hidden');
      document.getElementById('bdmConsultDate').textContent = d.consultation.date || '';
      document.getElementById('bdmConsultTime').textContent = d.consultation.time || '';
      consultNote.classList.remove('hidden');
    } else {
      consultRow.classList.add('hidden');
      consultNote.classList.add('hidden');
    }

    document.getElementById('bdmDate').textContent = d.bookingDate || '—';
    document.getElementById('bdmTime').textContent = (d.timeStart && d.timeEnd)
      ? (d.timeStart + ' – ' + d.timeEnd + ' · ' + (d.timezone || 'UTC'))
      : '—';

    document.getElementById('bdmStudio').textContent = d.studioName || 'Studio';
    var addr = d.studioAddress || '';
    document.getElementById('bdmAddress').textContent = addr;
    document.getElementById('bdmAddress').classList.toggle('hidden', !addr);

    var maps = document.getElementById('bdmMaps');
    if (d.mapsUrl) {
      maps.href = d.mapsUrl;
      maps.classList.remove('hidden');
    } else {
      maps.classList.add('hidden');
    }

    var completionRow = document.getElementById('bdmCompletionRow');
    var completionCodeEl = document.getElementById('bdmCompletionCode');
    var completionUsedNote = document.getElementById('bdmCompletionUsedNote');
    if (completionRow && completionCodeEl && completionUsedNote) {
      var code = (d.completionCode || '').trim();
      if (code) {
        completionRow.classList.remove('hidden');
        completionCodeEl.textContent = code;
        completionUsedNote.classList.toggle('hidden', !d.completionCodeUsed);
      } else {
        completionRow.classList.add('hidden');
        completionCodeEl.textContent = '';
        completionUsedNote.classList.add('hidden');
      }
    }

    document.getElementById('bdmMinPrice').textContent = fmtEUR(d.designMinPrice);
    document.getElementById('bdmDeposit').textContent = fmtEUR(d.deposit);
    document.getElementById('bdmFee').textContent = fmtEUR(d.platformFee);
    document.getElementById('bdmTotal').textContent = fmtEUR(d.totalPaid);
    document.getElementById('bdmRemaining').textContent = fmtEUR(d.remainingBalance);

    modal.setAttribute('aria-hidden', 'false');
    requestAnimationFrame(function () {
      modal.classList.add('bdm-open', 'opacity-100');
      modal.classList.remove('opacity-0', 'pointer-events-none');
    });
    document.body.style.overflow = 'hidden';
  }

  function closeBookingDetail() {
    modal.classList.remove('bdm-open', 'opacity-100');
    modal.classList.add('opacity-0', 'pointer-events-none');
    modal.setAttribute('aria-hidden', 'true');
    document.body.style.overflow = '';
  }

  document.querySelectorAll('.js-booking-detail-open').forEach(function (btn) {
    btn.addEventListener('click', function () {
      var id = this.getAttribute('data-booking-id');
      if (id) openBookingDetail(id);
    });
  });

  modal.querySelectorAll('[data-close-booking-modal]').forEach(function (el) {
    el.addEventListener('click', closeBookingDetail);
  });

  document.addEventListener('keydown', function (e) {
    if (e.key === 'Escape' && modal.classList.contains('bdm-open')) {
      closeBookingDetail();
    }
  });
})();

(function () {
  var cancelModal = document.getElementById('userCancelBookingModal');
  if (!cancelModal) return;

  var NEXT_LABEL = 'Next';
  var step1 = document.getElementById('ucmStep1');
  var step2 = document.getElementById('ucmStep2');
  var ack = document.getElementById('ucmAck');
  var ackError = document.getElementById('ucmAckError');
  var nextBtn = document.getElementById('ucmNext');
  var reason = document.getElementById('ucmReason');
  var reasonError = document.getElementById('ucmReasonError');
  var backStep = document.getElementById('ucmBackToStep1');
  var submitBtn = document.getElementById('ucmSubmit');
  var fetchErr = document.getElementById('ucmFetchError');
  var refundAmt = document.getElementById('ucmRefundAmount');
  var refundSub = document.getElementById('ucmRefundSub');

  function fmtEUR(n) {
    var v = parseFloat(n);
    if (isNaN(v)) v = 0;
    return '€' + v.toFixed(2);
  }

  function csrfToken() {
    var m = document.querySelector('meta[name="csrf-token"]');
    return m ? m.getAttribute('content') : '';
  }

  function resetCancelModal(clearBookingId) {
    step1.classList.remove('hidden');
    step2.classList.add('hidden');
    ack.checked = false;
    ackError.classList.add('hidden');
    if (fetchErr) {
      fetchErr.classList.add('hidden');
      fetchErr.textContent = '';
    }
    reason.value = '';
    reasonError.classList.add('hidden');
    if (refundAmt) refundAmt.textContent = '—';
    if (refundSub) refundSub.textContent = '';
    nextBtn.disabled = true;
    nextBtn.textContent = NEXT_LABEL;
    nextBtn.classList.add('opacity-50', 'cursor-not-allowed');
    ack.disabled = false;
    if (submitBtn) submitBtn.disabled = false;
    if (clearBookingId) cancelModal.removeAttribute('data-booking-id');
  }

  function openUserCancel(btn) {
    var id = btn.getAttribute('data-booking-id');
    var canFull = btn.getAttribute('data-can-full-refund') === '1';
    var winHuman = btn.getAttribute('data-window-human') || '';
    var leadFull = document.getElementById('ucmLeadFull');
    var leadLate = document.getElementById('ucmLeadLate');
    var policyList = document.getElementById('ucmPolicyList');

    document.getElementById('ucmWinFull').textContent = winHuman;
    document.getElementById('ucmWinLate').textContent = winHuman;
    if (leadFull) {
      leadFull.textContent = 'You are still within this artist’s full-refund cancellation window.';
    }
    if (leadFull) leadFull.classList.toggle('hidden', !canFull);
    if (leadLate) leadLate.classList.toggle('hidden', canFull);
    if (policyList) policyList.classList.remove('hidden');

    resetCancelModal(false);
    cancelModal.setAttribute('data-booking-id', id || '');

    cancelModal.setAttribute('aria-hidden', 'false');
    requestAnimationFrame(function () {
      cancelModal.classList.add('ucm-open', 'opacity-100');
      cancelModal.classList.remove('opacity-0', 'pointer-events-none');
    });
    document.body.style.overflow = 'hidden';
  }

  function closeUserCancel() {
    cancelModal.classList.remove('ucm-open', 'opacity-100');
    cancelModal.classList.add('opacity-0', 'pointer-events-none');
    cancelModal.setAttribute('aria-hidden', 'true');
    document.body.style.overflow = '';
    resetCancelModal(true);
  }

  document.querySelectorAll('.js-user-cancel-open').forEach(function (btn) {
    btn.addEventListener('click', function () {
      openUserCancel(this);
    });
  });

  cancelModal.querySelectorAll('[data-close-user-cancel-modal]').forEach(function (el) {
    el.addEventListener('click', closeUserCancel);
  });

  if (ack && nextBtn) {
    ack.addEventListener('change', function () {
      var on = ack.checked;
      nextBtn.disabled = !on;
      nextBtn.classList.toggle('opacity-50', !on);
      nextBtn.classList.toggle('cursor-not-allowed', !on);
      if (on) {
        ackError.classList.add('hidden');
      }
    });
  }

  if (nextBtn) {
    nextBtn.addEventListener('click', function () {
      if (!ack.checked) {
        ackError.classList.remove('hidden');
        return;
      }
      ackError.classList.add('hidden');
      if (fetchErr) {
        fetchErr.classList.add('hidden');
        fetchErr.textContent = '';
      }

      var id = cancelModal.getAttribute('data-booking-id');
      if (!id) return;

      var token = csrfToken();
      nextBtn.disabled = true;
      nextBtn.textContent = 'Loading…';
      nextBtn.classList.add('opacity-50', 'cursor-not-allowed');
      ack.disabled = true;

      fetch('/api/bookings/' + encodeURIComponent(id) + '/cancellation-info', {
        method: 'GET',
        headers: {
          Accept: 'application/json',
          'X-Requested-With': 'XMLHttpRequest',
          'X-CSRF-TOKEN': token,
        },
        credentials: 'same-origin',
      })
        .then(function (r) {
          return r.json().then(function (j) {
            return { ok: r.ok, status: r.status, body: j };
          });
        })
        .then(function (res) {
          if (!res.ok || !res.body.success) {
            var msg = (res.body && res.body.message) ? res.body.message : 'Could not load cancellation details.';
            throw new Error(msg);
          }
          var d = res.body.data;
          var amt = parseFloat(d.estimated_refund && d.estimated_refund.amount != null ? d.estimated_refund.amount : 0);
          if (refundAmt) refundAmt.textContent = fmtEUR(amt);
          if (refundSub) {
            if (amt < 0.005) {
              refundSub.textContent = 'No refund will be issued for this cancellation based on policy and how close you are to the appointment.';
            } else if (d.is_before_deadline) {
              refundSub.textContent = 'Full refund of what you paid for this booking. Processing can take a few business days depending on your bank.';
            } else {
              refundSub.textContent = 'Partial refund per this artist’s policy; some amounts may be retained.';
            }
          }
          step1.classList.add('hidden');
          step2.classList.remove('hidden');
          reason.focus();
        })
        .catch(function (err) {
          if (fetchErr) {
            fetchErr.textContent = err.message || 'Something went wrong.';
            fetchErr.classList.remove('hidden');
          }
        })
        .finally(function () {
          ack.disabled = false;
          nextBtn.textContent = NEXT_LABEL;
          var on = ack.checked;
          nextBtn.disabled = !on;
          nextBtn.classList.toggle('opacity-50', !on);
          nextBtn.classList.toggle('cursor-not-allowed', !on);
        });
    });
  }

  if (backStep) {
    backStep.addEventListener('click', function () {
      step2.classList.add('hidden');
      step1.classList.remove('hidden');
      reasonError.classList.add('hidden');
    });
  }

  if (reason && reasonError) {
    reason.addEventListener('input', function () {
      reasonError.classList.add('hidden');
    });
  }

  if (submitBtn) {
    submitBtn.addEventListener('click', function () {
      var text = (reason.value || '').trim();
      var maxLen = 1000;
      if (text.length < 3) {
        reasonError.textContent = 'Please enter a reason (at least 3 characters).';
        reasonError.classList.remove('hidden');
        return;
      }
      if (text.length > maxLen) {
        reasonError.textContent = 'Reason must be at most ' + maxLen + ' characters.';
        reasonError.classList.remove('hidden');
        return;
      }
      reasonError.classList.add('hidden');

      var id = cancelModal.getAttribute('data-booking-id');
      if (!id) return;

      var token = csrfToken();
      submitBtn.disabled = true;

      fetch('/api/bookings/' + encodeURIComponent(id) + '/cancel', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          Accept: 'application/json',
          'X-Requested-With': 'XMLHttpRequest',
          'X-CSRF-TOKEN': token,
        },
        credentials: 'same-origin',
        body: JSON.stringify({ reason: text, confirmed: true }),
      })
        .then(function (r) {
          return r.json().then(function (j) {
            return { ok: r.ok, body: j };
          });
        })
        .then(function (res) {
          if (!res.ok || !res.body.success) {
            var msg = res.body.message || 'Cancellation failed.';
            if (res.body.errors) {
              var flat = [];
              Object.keys(res.body.errors).forEach(function (k) {
                var arr = res.body.errors[k];
                if (Array.isArray(arr)) flat = flat.concat(arr);
              });
              if (flat.length) msg = flat.join(' ');
            }
            throw new Error(msg);
          }
          closeUserCancel();
          window.location.reload();
        })
        .catch(function (err) {
          reasonError.textContent = err.message || 'Something went wrong.';
          reasonError.classList.remove('hidden');
        })
        .finally(function () {
          submitBtn.disabled = false;
        });
    });
  }

  document.addEventListener('keydown', function (e) {
    if (e.key === 'Escape' && cancelModal.classList.contains('ucm-open')) {
      closeUserCancel();
    }
  });
})();
</script>
@endsection
