<?php

namespace App\Services;

use App\Http\Controllers\GoogleCalendarController;
use App\Mail\BookingConfirmationMail;
use App\Models\Booking;
use App\Models\PendingVivaPayment;
use App\Models\User;
use App\Models\UserDetail;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class PublicVivaBookingService
{
    public function __construct(
        private readonly ManagedRequestBookingService $managedBooking,
    ) {}

    public function createFromPending(PendingVivaPayment $pending, string $transactionId): Booking
    {
        $metadata = is_array($pending->metadata) ? $pending->metadata : [];
        $artistUsername = (string) ($metadata['artist_username'] ?? '');
        $tattooSlug = (string) ($metadata['tattoo_slug'] ?? '');
        $payload = is_array($metadata['booking_payload'] ?? null) ? $metadata['booking_payload'] : [];

        $userDetail = UserDetail::query()->where('user_name', $artistUsername)->first();
        if (! $userDetail || ! $userDetail->user || $userDetail->user->role !== 'artist') {
            throw new \RuntimeException('Artist not found.');
        }

        $design = $userDetail->user->artistDesigns()
            ->where('slug', $tattooSlug)
            ->where('is_active', true)
            ->first();

        if (! $design) {
            throw new \RuntimeException('Tattoo design not found.');
        }

        if ($design->isSoldOut()) {
            throw new \RuntimeException('This design is sold out and is no longer available to book.');
        }

        $existing = Booking::query()->where('viva_order_code', $pending->viva_order_code)->first();
        if ($existing) {
            return $existing;
        }

        $bookingEmail = mb_strtolower(trim((string) ($payload['email'] ?? '')));
        $bookingUser = User::query()->whereRaw('LOWER(email) = ?', [$bookingEmail])->first();
        if (! $bookingUser) {
            throw new \RuntimeException('Booking user not found.');
        }

        $bookingUser->syncPhoneNumber($payload['phone'] ?? null);

        $artistTimezone = $userDetail->timezone ?: 'UTC';
        $consultationRequired = (bool) ($payload['consultation_required'] ?? false);
        $consultationTiming = (string) ($payload['consultation_timing'] ?? 'combined');
        $consultDurationMinutes = (int) ($payload['consult_duration_minutes'] ?? 30);
        $tattooDurationMinutes = (int) ($payload['tattoo_duration_minutes'] ?? 120);

        $toUtcTime = function (string $date, string $time) use ($artistTimezone): string {
            return Carbon::createFromFormat('Y-m-d g:i A', $date.' '.$time, $artistTimezone)
                ->utc()
                ->format('H:i:s');
        };

        $bookingDate = (string) ($payload['tattoo_date'] ?? $payload['date'] ?? '');
        $bookingTime = (string) ($payload['tattoo_time'] ?? $payload['time'] ?? '');
        if ($bookingDate === '' || $bookingTime === '') {
            throw new \RuntimeException('Booking date/time is required.');
        }

        $startUtc = $toUtcTime($bookingDate, $bookingTime);
        $bookingStart = Carbon::createFromFormat('Y-m-d H:i:s', $bookingDate.' '.$startUtc, 'UTC');
        $bookingEndUtc = $bookingStart->copy()->addMinutes($tattooDurationMinutes)->format('H:i:s');

        $consultDate = null;
        $consultStartUtc = null;
        $consultEndUtc = null;
        if ($consultationRequired) {
            if ($consultationTiming === 'separate') {
                $consultDate = (string) ($payload['consultation_date'] ?? '');
                $consultTime = (string) ($payload['consultation_time'] ?? '');
                if ($consultDate !== '' && $consultTime !== '') {
                    $consultStartUtc = $toUtcTime($consultDate, $consultTime);
                    $consultStart = Carbon::createFromFormat('Y-m-d H:i:s', $consultDate.' '.$consultStartUtc, 'UTC');
                    $consultEndUtc = $consultStart->copy()->addMinutes($consultDurationMinutes)->format('H:i:s');
                }
            } else {
                $consultDate = $bookingDate;
                $consultStartUtc = $startUtc;
                $consultStart = Carbon::createFromFormat('Y-m-d H:i:s', $consultDate.' '.$consultStartUtc, 'UTC');
                $consultEndUtc = $consultStart->copy()->addMinutes($consultDurationMinutes)->format('H:i:s');
                $bookingEndUtc = $consultStart->copy()->addMinutes($consultDurationMinutes + $tattooDurationMinutes)->format('H:i:s');
            }
        }

        if ($this->managedBooking->artistLocalDateIsBlocked((int) $userDetail->user_id, $bookingDate)) {
            throw new \RuntimeException('The selected session date is no longer available.');
        }

        if ($consultationRequired && $consultationTiming === 'separate' && $consultDate !== ''
            && $this->managedBooking->artistLocalDateIsBlocked((int) $userDetail->user_id, $consultDate)) {
            throw new \RuntimeException('The selected consultation date is no longer available.');
        }

        $pricing = app(BookingCheckoutPricingService::class);
        $totals = $pricing->checkoutTotals($userDetail, (float) $design->min_price, $payload['phone'] ?? $bookingUser->phone_number);
        $depositAmount = (float) $totals['deposit'];
        $platformFee = (float) $totals['platform_fee'];
        $taxAmount = (float) $totals['tax_amount'];
        $totalPaid = (float) $totals['total_due'];

        $booking = Booking::create([
            'user_id' => $bookingUser->id,
            'artist_user_id' => $userDetail->user_id,
            'tattoo_id' => $design->id,
            'booking_type' => 'flash',
            'cancellation_window_hours' => CancellationService::hoursFromArtistWindow($userDetail->cancellation_window ?? '48h'),
            'booking_date' => $bookingDate,
            'start_time_utc' => $startUtc,
            'end_time_utc' => $bookingEndUtc,
            'timezone' => $artistTimezone,
            'has_consultation' => $consultationRequired,
            'consultation_date' => $consultDate,
            'consultation_start_time_utc' => $consultStartUtc,
            'consultation_end_time_utc' => $consultEndUtc,
            'consultation_timing_type' => $consultationRequired ? ($consultationTiming === 'separate' ? 'separate' : 'combined') : null,
            'status' => 'confirmed',
            'payment_provider' => 'viva_iris',
            'payment_intent_id' => null,
            'viva_order_code' => (int) $pending->viva_order_code,
            'viva_transaction_id' => $transactionId,
            'payment_status' => 'paid',
            'deposit_amount' => $depositAmount,
            'platform_fee' => $platformFee,
            'tax_amount' => $taxAmount,
            'tax_rate' => $totals['tax_rate'],
            'tax_country' => $totals['tax_country'],
            'tax_label' => $totals['tax_label'],
            'total_amount_paid' => $totalPaid,
            'currency' => 'EUR',
            'questions_answers' => $payload['questions_answers'] ?? [],
            'notes' => trim((string) ($payload['notes'] ?? '')),
        ]);

        if (! $booking->completion_code) {
            do {
                $code = strtoupper(Str::random(6));
            } while (Booking::query()->where('completion_code', $code)->exists());
            $booking->completion_code = $code;
            $booking->save();
        }

        $this->createGoogleCalendarEvent($booking);

        $this->sendEmails($booking, $bookingUser, $userDetail);

        return $booking;
    }

    private function sendEmails(Booking $booking, User $bookingUser, UserDetail $userDetail): void
    {
        $clientEmail = (string) ($bookingUser->email ?? '');
        $artistEmail = (string) ($userDetail->user->email ?? '');

        if ($clientEmail !== '') {
            try {
                Mail::to($clientEmail)->send(new BookingConfirmationMail($booking, false));
            } catch (\Throwable $e) {
                Log::error('Failed to send client booking confirmation email (Viva public)', [
                    'booking_id' => $booking->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        if ($artistEmail !== '') {
            try {
                Mail::to($artistEmail)->send(new BookingConfirmationMail($booking, true, []));
            } catch (\Throwable $e) {
                Log::error('Failed to send artist booking notification email (Viva public)', [
                    'booking_id' => $booking->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }

    private function createGoogleCalendarEvent(Booking $booking): void
    {
        try {
            $booking->loadMissing(['artist.userDetail']);
            $artistUserDetail = $booking->artist?->userDetail;
            if (! $artistUserDetail || ! $artistUserDetail->google_calendar_token) {
                return;
            }

            $consultationType = trim((string) ($payload['consultation_type'] ?? ''));
            $calendarResult = GoogleCalendarController::createCalendarEvent(
                $artistUserDetail,
                $booking,
                (bool) $booking->has_consultation,
                $consultationType !== '' ? $consultationType : null
            );

            if (is_array($calendarResult) && ! empty($calendarResult['event_id'])) {
                $booking->update([
                    'google_calendar_event_id' => $calendarResult['event_id'],
                    'google_meet_link' => $calendarResult['meet_link'] ?? null,
                ]);
                $booking->refresh();
            }
        } catch (\Throwable $e) {
            Log::error('Failed to create Google Calendar event (Viva public)', [
                'booking_id' => $booking->id,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
