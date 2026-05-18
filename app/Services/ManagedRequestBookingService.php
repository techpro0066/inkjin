<?php

namespace App\Services;

use App\Mail\BookingConfirmationMail;
use App\Models\AvailabilityOverride;
use App\Models\Booking;
use App\Models\BookingRequest;
use App\Models\User;
use App\Services\CancellationService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Stripe\PaymentIntent;

class ManagedRequestBookingService
{
    public function __construct(
        private readonly BookingCheckoutPricingService $pricing = new BookingCheckoutPricingService,
    ) {}

    public function artistLocalDateIsBlocked(int $artistUserId, string $ymd): bool
    {
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $ymd)) {
            return false;
        }

        return AvailabilityOverride::query()
            ->where('user_id', $artistUserId)
            ->whereNotNull('start_date')
            ->whereNotNull('end_date')
            ->whereDate('start_date', '<=', $ymd)
            ->whereDate('end_date', '>=', $ymd)
            ->exists();
    }

    /**
     * @return array{date: string, start_time_utc: string, end_time_utc: string}
     */
    public function slotRangeToUtc(string $dateYmd, string $fromHi, string $toHi, string $timezone): array
    {
        $from = substr(trim($fromHi), 0, 5);
        $to = substr(trim($toHi), 0, 5);
        $start = Carbon::createFromFormat('Y-m-d H:i', $dateYmd.' '.$from, $timezone)->utc();
        $end = Carbon::createFromFormat('Y-m-d H:i', $dateYmd.' '.$to, $timezone)->utc();

        return [
            'date' => $dateYmd,
            'start_time_utc' => $start->format('H:i:s'),
            'end_time_utc' => $end->format('H:i:s'),
        ];
    }

    /**
     * @return array{0: string, 1: string, 2: string}|null [from, to, date]
     */
    public function firstClientRange(BookingRequest $bookingRequest, string $kind): ?array
    {
        $raw = $kind === 'consult'
            ? $bookingRequest->client_consultation_slots
            : $bookingRequest->client_session_slots;

        $slots = $bookingRequest->normalizedArtistSlots($raw);
        if ($slots === []) {
            return null;
        }

        $date = $slots[0]['date'];
        $range = $slots[0]['ranges'][0] ?? null;
        if (!$range) {
            return null;
        }

        return [$range['from'], $range['to'], $date];
    }

    public function createBookingFromRequest(BookingRequest $bookingRequest, PaymentIntent $intent): Booking
    {
        $existing = Booking::query()->where('payment_intent_id', $intent->id)->first();
        if ($existing) {
            $this->linkRequestToBooking($bookingRequest, $existing);

            return $existing;
        }

        if ($bookingRequest->booking_id) {
            $linked = Booking::query()->find($bookingRequest->booking_id);
            if ($linked) {
                return $linked;
            }
        }

        $bookingRequest->load(['tattoo', 'artist.userDetail', 'user']);

        $userDetail = $bookingRequest->artist?->userDetail;
        $tattoo = $bookingRequest->tattoo;
        if (!$userDetail || !$tattoo || !$bookingRequest->user) {
            throw new \RuntimeException('Booking request is missing required relations.');
        }

        $artistTimezone = $userDetail->timezone ?: 'UTC';
        $sessionRange = $this->firstClientRange($bookingRequest, 'session');
        if (!$sessionRange) {
            throw new \RuntimeException('Session date and time are required.');
        }

        [$sessionFrom, $sessionTo, $sessionDate] = $sessionRange;
        $sessionUtc = $this->slotRangeToUtc($sessionDate, $sessionFrom, $sessionTo, $artistTimezone);

        if ($this->artistLocalDateIsBlocked((int) $bookingRequest->artist_id, $sessionUtc['date'])) {
            throw new \RuntimeException('The selected session date is no longer available.');
        }

        $hasConsult = $bookingRequest->hasConsultation();
        $consultDate = null;
        $consultStartUtc = null;
        $consultEndUtc = null;
        $consultationTiming = null;

        if ($hasConsult) {
            $consultRange = $this->firstClientRange($bookingRequest, 'consult');
            if (!$consultRange) {
                throw new \RuntimeException('Consultation date and time are required.');
            }
            [$consultFrom, $consultTo, $consultDateYmd] = $consultRange;
            $consultUtc = $this->slotRangeToUtc($consultDateYmd, $consultFrom, $consultTo, $artistTimezone);

            if ($this->artistLocalDateIsBlocked((int) $bookingRequest->artist_id, $consultUtc['date'])) {
                throw new \RuntimeException('The selected consultation date is no longer available.');
            }

            $consultDate = $consultUtc['date'];
            $consultStartUtc = $consultUtc['start_time_utc'];
            $consultEndUtc = $consultUtc['end_time_utc'];
            $consultationTiming = 'separate';
        }

        $totals = $this->pricing->checkoutTotals($userDetail, (float) $tattoo->min_price);

        $booking = Booking::create([
            'user_id' => $bookingRequest->user_id,
            'artist_user_id' => $bookingRequest->artist_id,
            'tattoo_id' => $tattoo->id,
            'booking_type' => 'custom',
            'cancellation_window_hours' => CancellationService::hoursFromArtistWindow($userDetail->cancellation_window ?? '48h'),
            'booking_date' => $sessionUtc['date'],
            'start_time_utc' => $sessionUtc['start_time_utc'],
            'end_time_utc' => $sessionUtc['end_time_utc'],
            'timezone' => $artistTimezone,
            'has_consultation' => $hasConsult,
            'consultation_date' => $consultDate,
            'consultation_start_time_utc' => $consultStartUtc,
            'consultation_end_time_utc' => $consultEndUtc,
            'consultation_timing_type' => $consultationTiming,
            'status' => 'confirmed',
            'payment_intent_id' => $intent->id,
            'payment_status' => 'paid',
            'deposit_amount' => $totals['deposit'],
            'platform_fee' => $totals['platform_fee'],
            'total_amount_paid' => $totals['total_due'],
            'currency' => strtoupper((string) ($intent->currency ?: 'eur')),
            'questions_answers' => is_array($bookingRequest->questions_answers)
                ? $bookingRequest->questions_answers
                : [],
            'notes' => trim($bookingRequest->additionalNotes()."\n\nManaged request: ".$bookingRequest->referenceLabel()),
        ]);

        if (!$booking->completion_code) {
            do {
                $code = strtoupper(Str::random(6));
            } while (Booking::query()->where('completion_code', $code)->exists());
            $booking->completion_code = $code;
            $booking->save();
        }

        $this->linkRequestToBooking($bookingRequest, $booking);
        $this->sendConfirmationEmails($booking);

        return $booking;
    }

    private function linkRequestToBooking(BookingRequest $bookingRequest, Booking $booking): void
    {
        if ($bookingRequest->status !== 'moved_to_booking' || (int) $bookingRequest->booking_id !== (int) $booking->id) {
            $bookingRequest->update([
                'status' => 'moved_to_booking',
                'booking_id' => $booking->id,
            ]);
        }
    }

    private function sendConfirmationEmails(Booking $booking): void
    {
        $booking->load(['user', 'artist']);
        $clientEmail = (string) ($booking->user?->email ?? '');
        $artistEmail = (string) ($booking->artist?->email ?? '');

        if ($clientEmail !== '') {
            try {
                Mail::to($clientEmail)->send(new BookingConfirmationMail($booking, false));
            } catch (\Throwable $e) {
                Log::error('Failed to send client booking confirmation email (managed request)', [
                    'booking_id' => $booking->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        if ($artistEmail !== '') {
            try {
                Mail::to($artistEmail)->send(new BookingConfirmationMail($booking, true, []));
            } catch (\Throwable $e) {
                Log::error('Failed to send artist booking notification email (managed request)', [
                    'booking_id' => $booking->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }
}
