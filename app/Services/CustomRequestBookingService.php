<?php

namespace App\Services;

use App\Exceptions\GoogleCalendarEventRequiredException;
use App\Mail\BookingConfirmationMail;
use App\Models\Booking;
use App\Models\CustomRequest;
use App\Services\CancellationService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Stripe\PaymentIntent;

class CustomRequestBookingService
{
    public function __construct(
        private readonly ManagedRequestBookingService $managedBooking = new ManagedRequestBookingService,
        private readonly BookingCheckoutPricingService $pricing = new BookingCheckoutPricingService,
    ) {}

    /**
     * @return array{0: string, 1: string, 2: string}|null [from, to, date]
     */
    public function firstClientRange(CustomRequest $customRequest, string $kind = 'session'): ?array
    {
        $raw = $kind === 'consult'
            ? $customRequest->client_consultation_slots
            : $customRequest->client_session_slots;

        $slots = $customRequest->normalizedArtistSlots($raw);
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

    /**
     * @return array{0: string, 1: string, 2: string}|null [from, to, date]
     * @deprecated Use firstClientRange($customRequest, 'session')
     */
    public function firstClientSessionRange(CustomRequest $customRequest): ?array
    {
        return $this->firstClientRange($customRequest, 'session');
    }

    public function createBookingFromCustomRequest(CustomRequest $customRequest, PaymentIntent $intent): Booking
    {
        $existing = Booking::query()->where('payment_intent_id', $intent->id)->first();
        if ($existing) {
            $this->linkCustomRequestToBooking($customRequest, $existing);

            return $existing;
        }

        if ($customRequest->booking_id) {
            $linked = Booking::query()->find($customRequest->booking_id);
            if ($linked) {
                return $linked;
            }
        }

        $customRequest->load(['artist.userDetail', 'user']);
        $userDetail = $customRequest->artist?->userDetail;
        if (!$userDetail || !$customRequest->user) {
            throw new \RuntimeException('Custom request is missing required relations.');
        }

        app(GoogleCalendarBookingSyncService::class)->assertReadyForPayment($userDetail);

        $sessionRange = $this->firstClientRange($customRequest, 'session');
        if (!$sessionRange) {
            throw new \RuntimeException('Session date and time are required.');
        }

        $artistTimezone = $userDetail->timezone ?: 'UTC';
        [$sessionFrom, $sessionTo, $sessionDate] = $sessionRange;
        $sessionUtc = $this->managedBooking->slotRangeToUtc($sessionDate, $sessionFrom, $sessionTo, $artistTimezone);

        if ($this->managedBooking->artistLocalDateIsBlocked(
            (int) $customRequest->artist_id,
            $sessionUtc['date'],
            $customRequest->isGuestRequest() ? (int) $customRequest->guest_id : null
        )) {
            throw new \RuntimeException('The selected session date is no longer available.');
        }

        $hasConsult = $customRequest->autoRequiresConsultation();
        $consultDate = null;
        $consultStartUtc = null;
        $consultEndUtc = null;
        $consultationTiming = null;

        if ($hasConsult) {
            $consultRange = $this->firstClientRange($customRequest, 'consult');
            if (!$consultRange) {
                throw new \RuntimeException('Consultation date and time are required.');
            }

            [$consultFrom, $consultTo, $consultDateYmd] = $consultRange;
            $consultUtc = $this->managedBooking->slotRangeToUtc($consultDateYmd, $consultFrom, $consultTo, $artistTimezone);

            if ($this->managedBooking->artistLocalDateIsBlocked(
                (int) $customRequest->artist_id,
                $consultUtc['date'],
                $customRequest->isGuestRequest() ? (int) $customRequest->guest_id : null
            )) {
                throw new \RuntimeException('The selected consultation date is no longer available.');
            }

            $consultDate = $consultUtc['date'];
            $consultStartUtc = $consultUtc['start_time_utc'];
            $consultEndUtc = $consultUtc['end_time_utc'];
            $consultationTiming = $customRequest->autoConsultationTiming();
        }

        $quotePrice = $customRequest->checkoutPriceAmount();
        $clientPhone = $customRequest->user?->phone_number;
        $totals = $this->pricing->checkoutTotals($userDetail, $quotePrice, $clientPhone);
        $guestSpotConsumed = $this->consumeGuestSpotForPayment($customRequest);

        $booking = Booking::create([
            'user_id' => $customRequest->user_id,
            'artist_user_id' => $customRequest->artist_id,
            'tattoo_id' => null,
            'booking_type' => 'custom',
            'custom_tattoo_details' => $this->customTattooDetailsPayload($customRequest, $guestSpotConsumed),
            'cancellation_window_hours' => CancellationService::hoursFromArtistWindow($userDetail->cancellation_window ?? '48h'),
            'booking_date' => $sessionUtc['date'],
            'start_time_utc' => $sessionUtc['start_time_utc'],
            'end_time_utc' => $sessionUtc['end_time_utc'],
            'timezone' => $artistTimezone,
            'has_consultation' => $hasConsult,
            'consultation_date' => $consultDate,
            'consultation_start_time_utc' => $consultStartUtc,
            'consultation_end_time_utc' => $consultEndUtc,
            'consultation_timing_type' => $hasConsult ? $consultationTiming : null,
            'status' => 'confirmed',
            'payment_intent_id' => $intent->id,
            'payment_status' => 'paid',
            'deposit_amount' => $totals['deposit'],
            'platform_fee' => $totals['platform_fee'],
            'tax_amount' => $totals['tax_amount'],
            'tax_rate' => $totals['tax_rate'],
            'tax_country' => $totals['tax_country'],
            'tax_label' => $totals['tax_label'],
            'total_amount_paid' => $totals['total_due'],
            'currency' => strtoupper((string) ($intent->currency ?: 'eur')),
            'questions_answers' => is_array($customRequest->questions_answers)
                ? $customRequest->questions_answers
                : [],
            'notes' => trim(
                (string) ($customRequest->anything_else_notes ?? '')."\n\nCustom request: ".$customRequest->referenceLabel()
            ),
        ]);

        if (!$booking->completion_code) {
            do {
                $code = strtoupper(Str::random(6));
            } while (Booking::query()->where('completion_code', $code)->exists());
            $booking->completion_code = $code;
            $booking->save();
        }

        $this->createGoogleCalendarEvent($booking);

        $this->linkCustomRequestToBooking($customRequest, $booking);
        $this->sendConfirmationEmails($booking);

        return $booking;
    }

    public function createBookingFromVivaPayment(
        CustomRequest $customRequest,
        int $orderCode,
        string $transactionId,
    ): Booking {
        $existing = Booking::query()->where('viva_order_code', $orderCode)->first();
        if ($existing) {
            $this->linkCustomRequestToBooking($customRequest, $existing);

            return $existing;
        }

        if ($customRequest->booking_id) {
            $linked = Booking::query()->find($customRequest->booking_id);
            if ($linked) {
                return $linked;
            }
        }

        $customRequest->load(['artist.userDetail', 'user']);
        $userDetail = $customRequest->artist?->userDetail;
        if (!$userDetail || !$customRequest->user) {
            throw new \RuntimeException('Custom request is missing required relations.');
        }

        app(GoogleCalendarBookingSyncService::class)->assertReadyForPayment($userDetail);

        $sessionRange = $this->firstClientRange($customRequest, 'session');
        if (!$sessionRange) {
            throw new \RuntimeException('Session date and time are required.');
        }

        $artistTimezone = $userDetail->timezone ?: 'UTC';
        [$sessionFrom, $sessionTo, $sessionDate] = $sessionRange;
        $sessionUtc = $this->managedBooking->slotRangeToUtc($sessionDate, $sessionFrom, $sessionTo, $artistTimezone);

        if ($this->managedBooking->artistLocalDateIsBlocked(
            (int) $customRequest->artist_id,
            $sessionUtc['date'],
            $customRequest->isGuestRequest() ? (int) $customRequest->guest_id : null
        )) {
            throw new \RuntimeException('The selected session date is no longer available.');
        }

        $hasConsult = $customRequest->autoRequiresConsultation();
        $consultDate = null;
        $consultStartUtc = null;
        $consultEndUtc = null;
        $consultationTiming = null;

        if ($hasConsult) {
            $consultRange = $this->firstClientRange($customRequest, 'consult');
            if (!$consultRange) {
                throw new \RuntimeException('Consultation date and time are required.');
            }

            [$consultFrom, $consultTo, $consultDateYmd] = $consultRange;
            $consultUtc = $this->managedBooking->slotRangeToUtc($consultDateYmd, $consultFrom, $consultTo, $artistTimezone);

            if ($this->managedBooking->artistLocalDateIsBlocked(
                (int) $customRequest->artist_id,
                $consultUtc['date'],
                $customRequest->isGuestRequest() ? (int) $customRequest->guest_id : null
            )) {
                throw new \RuntimeException('The selected consultation date is no longer available.');
            }

            $consultDate = $consultUtc['date'];
            $consultStartUtc = $consultUtc['start_time_utc'];
            $consultEndUtc = $consultUtc['end_time_utc'];
            $consultationTiming = $customRequest->autoConsultationTiming();
        }

        $quotePrice = $customRequest->checkoutPriceAmount();
        $clientPhone = $customRequest->user?->phone_number;
        $totals = $this->pricing->checkoutTotals($userDetail, $quotePrice, $clientPhone);
        $guestSpotConsumed = $this->consumeGuestSpotForPayment($customRequest);

        $booking = Booking::create([
            'user_id' => $customRequest->user_id,
            'artist_user_id' => $customRequest->artist_id,
            'tattoo_id' => null,
            'booking_type' => 'custom',
            'custom_tattoo_details' => $this->customTattooDetailsPayload($customRequest, $guestSpotConsumed),
            'cancellation_window_hours' => CancellationService::hoursFromArtistWindow($userDetail->cancellation_window ?? '48h'),
            'booking_date' => $sessionUtc['date'],
            'start_time_utc' => $sessionUtc['start_time_utc'],
            'end_time_utc' => $sessionUtc['end_time_utc'],
            'timezone' => $artistTimezone,
            'has_consultation' => $hasConsult,
            'consultation_date' => $consultDate,
            'consultation_start_time_utc' => $consultStartUtc,
            'consultation_end_time_utc' => $consultEndUtc,
            'consultation_timing_type' => $hasConsult ? $consultationTiming : null,
            'status' => 'confirmed',
            'payment_provider' => 'viva_iris',
            'payment_intent_id' => null,
            'viva_order_code' => $orderCode,
            'viva_transaction_id' => $transactionId,
            'payment_status' => 'paid',
            'deposit_amount' => $totals['deposit'],
            'platform_fee' => $totals['platform_fee'],
            'tax_amount' => $totals['tax_amount'],
            'tax_rate' => $totals['tax_rate'],
            'tax_country' => $totals['tax_country'],
            'tax_label' => $totals['tax_label'],
            'total_amount_paid' => $totals['total_due'],
            'currency' => 'EUR',
            'questions_answers' => is_array($customRequest->questions_answers)
                ? $customRequest->questions_answers
                : [],
            'notes' => trim(
                (string) ($customRequest->anything_else_notes ?? '')."\n\nCustom request: ".$customRequest->referenceLabel()
            ),
        ]);

        if (!$booking->completion_code) {
            do {
                $code = strtoupper(Str::random(6));
            } while (Booking::query()->where('completion_code', $code)->exists());
            $booking->completion_code = $code;
            $booking->save();
        }

        $this->createGoogleCalendarEvent($booking);

        $this->linkCustomRequestToBooking($customRequest, $booking);
        $this->sendConfirmationEmails($booking);

        return $booking;
    }

    private function linkCustomRequestToBooking(CustomRequest $customRequest, Booking $booking): void
    {
        if ($customRequest->status !== 'moved_to_booking' || (int) $customRequest->booking_id !== (int) $booking->id) {
            $customRequest->update([
                'status' => 'moved_to_booking',
                'booking_id' => $booking->id,
            ]);
        }
    }

    private function consumeGuestSpotForPayment(CustomRequest $customRequest): bool
    {
        return app(GuestSpotHoldService::class)->convertHoldOnPayment($customRequest);
    }

    /**
     * @return array<string, mixed>
     */
    private function customTattooDetailsPayload(CustomRequest $customRequest, bool $guestSpotConsumed): array
    {
        $details = [
            'custom_request_id' => $customRequest->id,
            'reference' => $customRequest->referenceLabel(),
            'estimated_price' => (float) $customRequest->estimated_price,
            'estimated_time' => $customRequest->estimated_time,
            'number_of_sessions' => $customRequest->number_of_sessions,
        ];

        if ($customRequest->isGuestRequest()) {
            $details['is_guest'] = true;
            $details['guest_id'] = (int) $customRequest->guest_id;
            $details['guest_spot_consumed'] = $guestSpotConsumed;
            $details['studio_label'] = $customRequest->clientStudioLabel();
            $details['studio_location_lines'] = $customRequest->clientStudioLocationLines();
            $details['google_maps_link'] = $customRequest->clientGoogleMapsLink();
        } else {
            $details['studio_label'] = $customRequest->clientStudioLabel();
            $details['studio_location_lines'] = $customRequest->clientStudioLocationLines();
            $details['google_maps_link'] = $customRequest->clientGoogleMapsLink();
        }

        return $details;
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
                Log::error('Failed to send client booking confirmation email (custom request)', [
                    'booking_id' => $booking->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        if ($artistEmail !== '') {
            try {
                Mail::to($artistEmail)->send(new BookingConfirmationMail($booking, true, []));
            } catch (\Throwable $e) {
                Log::error('Failed to send artist booking notification email (custom request)', [
                    'booking_id' => $booking->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }

    private function createGoogleCalendarEvent(Booking $booking): void
    {
        $calendarSync = app(GoogleCalendarBookingSyncService::class);

        try {
            $calendarSync->syncForBooking($booking);
        } catch (GoogleCalendarEventRequiredException $e) {
            $calendarSync->abortFailedCalendarBooking($booking, $e->getMessage());
            throw $e;
        }
    }
}
