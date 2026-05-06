<?php

namespace App\Http\Controllers\UserController;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Services\BookingCalendarAvailabilityService;
use App\Services\ReschedulingService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class BookingsController extends Controller
{
    /**
     * Display a listing of bookings for the authenticated user.
     */
    public function index(Request $request, ReschedulingService $reschedulingService)
    {
        $user = Auth::user();
        $bookings = Booking::query()
            ->where('user_id', $user->id)
            ->with(['tattoo', 'artist.userDetail'])
            ->orderByDesc('id')
            ->get();

        $bookingModalPayload = [];
        $rescheduleEligibility = [];
        $artistReschedulePending = [];
        foreach ($bookings as $booking) {
            if (in_array($booking->status, ['confirmed', 'pending', 'cancelled'], true)) {
                $bookingModalPayload[$booking->id] = $this->buildUserBookingModalPayload($booking);
            }
            $artistReschedulePending[$booking->id] = (
                $booking->status === 'pending'
                && $booking->reschedule_status === 'pending'
                && $booking->reschedule_requested_by === 'artist'
            );
            if ($booking->status === 'confirmed') {
                try {
                    $rescheduleEligibility[$booking->id] = $reschedulingService->canReschedule($booking, (int) $user->id);
                } catch (\Throwable $e) {
                    $rescheduleEligibility[$booking->id] = [
                        'can_reschedule' => false,
                        'reason' => 'error',
                        'message' => 'Reschedule is temporarily unavailable.',
                    ];
                }
            }
        }

        return view('user.bookings.index', compact('bookings', 'bookingModalPayload', 'rescheduleEligibility', 'artistReschedulePending'));
    }

    /**
     * JSON payload for the user reschedule modal (weekly hours, busy map excluding this booking, durations).
     */
    public function rescheduleCalendarData(Request $request, BookingCalendarAvailabilityService $calendar, int $booking_id)
    {
        $booking = Booking::query()
            ->where('user_id', Auth::id())
            ->whereKey($booking_id)
            ->firstOrFail();

        $isArtistRequestedPending = (
            $booking->status === 'pending'
            && $booking->reschedule_status === 'pending'
            && $booking->reschedule_requested_by === 'artist'
        );
        if ($booking->status !== 'confirmed' && ! $isArtistRequestedPending) {
            abort(404);
        }

        try {
            $data = $calendar->calendarPayloadForReschedule($booking);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage() ?: 'Unable to load calendar.',
            ], 422);
        }

        return response()->json([
            'success' => true,
            'data' => $data,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function buildUserBookingModalPayload(Booking $booking): array
    {
        $tattoo = $booking->tattoo;
        $artist = $booking->artist;
        $ud = $artist?->userDetail;

        $image = '';
        if ($tattoo && $tattoo->image) {
            $raw = (string) $tattoo->image;
            $image = str_starts_with($raw, 'http://') || str_starts_with($raw, 'https://')
                ? $raw
                : asset($raw);
        }

        $tz = $booking->timezone ?: 'UTC';
        $bookingDate = $booking->booking_date;
        $dateFormatted = $bookingDate instanceof \Carbon\CarbonInterface
            ? $bookingDate->format('l, F j, Y')
            : (string) $bookingDate;

        $start = Carbon::createFromFormat('H:i:s', (string) $booking->start_time_utc)->setTimezone($tz);
        $end = Carbon::createFromFormat('H:i:s', (string) $booking->end_time_utc)->setTimezone($tz);

        $deposit = (float) ($booking->deposit_amount ?? 0);
        $minPrice = (float) ($tattoo->min_price ?? 0);
        $remaining = max(0, round($minPrice - $deposit, 2));
        $totalPaid = (float) ($booking->total_amount_paid ?? 0);
        $platformFee = (float) ($booking->platform_fee ?? 0);

        $consultation = null;
        if ($booking->has_consultation) {
            $ct = strtolower((string) ($booking->consultation_timing_type ?? 'combined'));
            if ($ct === 'separate'
                && $booking->consultation_date
                && $booking->consultation_start_time_utc
                && $booking->consultation_end_time_utc) {
                $cd = $booking->consultation_date instanceof \Carbon\CarbonInterface
                    ? $booking->consultation_date->format('Y-m-d')
                    : (string) $booking->consultation_date;
                $cs = Carbon::createFromFormat('Y-m-d H:i:s', $cd.' '.$booking->consultation_start_time_utc, 'UTC')->timezone($tz);
                $ce = Carbon::createFromFormat('Y-m-d H:i:s', $cd.' '.$booking->consultation_end_time_utc, 'UTC')->timezone($tz);
                $consultation = [
                    'mode' => 'separate',
                    'date' => $cs->format('l, F j, Y'),
                    'time' => $cs->format('g:i A').' – '.$ce->format('g:i A'),
                ];
            }
        }

        return [
            'id' => $booking->id,
            'reference' => '#INK-'.str_pad((string) $booking->id, 6, '0', STR_PAD_LEFT),
            'tattooTitle' => $tattoo?->title ?? 'Tattoo session',
            'tattooImage' => $image,
            'artistName' => trim(($artist?->first_name ?? '').' '.($artist?->last_name ?? '')),
            'artistAvatar' => ($ud && $ud->avatar) ? asset($ud->avatar) : asset('design/images/icons/avatar.jpg'),
            'bookingDate' => $dateFormatted,
            'timeStart' => $start->format('g:i A'),
            'timeEnd' => $end->format('g:i A'),
            'timezone' => $tz,
            'studioName' => (string) ($ud?->studio_name ?? ''),
            'studioAddress' => (string) ($ud?->studio_address ?? ''),
            'mapsUrl' => (string) ($ud?->google_maps_link ?? ''),
            'deposit' => round($deposit, 2),
            'platformFee' => round($platformFee, 2),
            'totalPaid' => round($totalPaid, 2),
            'remainingBalance' => $remaining,
            'designMinPrice' => round($minPrice, 2),
            'consultation' => $consultation,
        ];
    }
}
