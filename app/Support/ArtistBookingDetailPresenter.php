<?php

namespace App\Support;

use App\Models\Booking;
use App\Models\UserDetail;
use App\Services\ArtistPayoutService;
use Carbon\Carbon;

class ArtistBookingDetailPresenter
{
    /**
     * Dataset used by the artist booking detail modal (matches booking-table-row data-* keys).
     *
     * @return array<string, mixed>
     */
    public static function forModal(Booking $booking, ?UserDetail $artistUserDetail = null): array
    {
        $booking->loadMissing(['user.userDetail', 'tattoo', 'studio']);

        $bt = $booking->booking_time;
        $clientName = trim(($booking->user?->first_name ?? '').' '.($booking->user?->last_name ?? ''));
        $clientName = $clientName !== '' ? $clientName : 'Client #'.($booking->user_id ?? '');

        $details = $booking->custom_tattoo_details ?? null;
        $customTitle = is_array($details) ? ($details['title'] ?? null) : null;
        $serviceTitle = ($booking->tattoo && filled($booking->tattoo->title))
            ? $booking->tattoo->title
            : (filled($customTitle) ? (string) $customTitle : ucfirst((string) $booking->booking_type).' booking');

        $startEnd = '—';
        $duration = '—';
        if ($bt !== null && ! empty($bt['start_datetime']) && ! empty($bt['end_datetime'])) {
            $startLocal = Carbon::parse($bt['start_datetime']);
            $endLocal = Carbon::parse($bt['end_datetime']);
            $startEnd = $startLocal->format('H:i').' – '.$endLocal->format('H:i');
        } elseif ($bt !== null && isset($bt['start'], $bt['end'])) {
            $startEnd = "{$bt['start']} – {$bt['end']}";
        }

        if ($bt !== null && isset($bt['duration_minutes'])) {
            $mins = (int) $bt['duration_minutes'];
            $hours = intdiv(max(0, $mins), 60);
            $tail = max(0, $mins) % 60;
            if ($mins < 60) {
                $duration = "{$mins} min";
            } else {
                $duration = $tail ? "{$hours}h {$tail}m" : "{$hours}h";
            }
        }

        $stKey = strtolower((string) ($booking->status ?? ''));
        $label = ucfirst(str_replace('_', ' ', $stKey ?: 'unknown'));

        $designImage = asset('design/images/icons/avatar.jpg');
        if ($booking->tattoo && ! empty($booking->tattoo->image)) {
            $imgRaw = (string) $booking->tattoo->image;
            $designImage = str_starts_with($imgRaw, 'http://') || str_starts_with($imgRaw, 'https://')
                ? $imgRaw
                : asset(ltrim($imgRaw, '/'));
        }

        $clientEmail = (string) ($booking->user?->email ?? '');
        $clientPhone = trim((string) ($booking->user?->phone_number ?? ''));
        if ($clientPhone === '') {
            $clientPhone = trim((string) ($booking->user?->userDetail?->mobile_number ?? ''));
        }

        $questionsAnswersData = is_array($booking->questions_answers ?? null) ? $booking->questions_answers : [];
        $balanceLabel = $booking->estimatedBalanceLabel();
        $balanceDue = $booking->remainingBalanceAmount();
        $balanceDueRounded = round($balanceDue, 2);
        $balanceDueLabel = fmod($balanceDueRounded, 1.0) === 0.0
            ? '€'.(string) (int) $balanceDueRounded
            : '€'.number_format($balanceDueRounded, 2, '.', '');

        $moneyBreakdown = app(ArtistPayoutService::class)
            ->bookingDetailsMoneyBreakdown($booking, $artistUserDetail);

        return [
            'booking_id' => (int) $booking->id,
            'booking_ref' => $booking->referenceLabel(),
            'client_name' => $clientName,
            'client_email' => $clientEmail,
            'client_phone' => $clientPhone,
            'service' => $serviceTitle,
            'date_display' => $booking->booking_date?->format('l, F j, Y') ?? '—',
            'time_range' => $startEnd,
            'duration' => $duration,
            'timezone' => $booking->timezone ?: 'UTC',
            'status_label' => $label,
            'booking_type' => ucfirst((string) ($booking->booking_type ?? '')),
            'deposit' => '€'.number_format((float) ($booking->deposit_amount ?? 0), 2),
            'balance' => $balanceLabel,
            'questions' => $questionsAnswersData,
            'design_image' => $designImage,
            'booking_status' => $stKey,
            'full_amount_paid' => $booking->full_amount_paid ? '1' : '0',
            'balance_due_label' => $balanceDueLabel,
            'aftercare_sent' => $booking->aftercare_sent_at ? '1' : '0',
            'money' => $moneyBreakdown,
            'manage_url' => route('artist.bookings.index', ['booking' => $booking->id]),
        ];
    }
}
