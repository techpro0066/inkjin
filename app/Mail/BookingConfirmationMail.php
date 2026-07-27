<?php

namespace App\Mail;

use App\Services\ArtistPayoutService;
use Illuminate\Support\Facades\URL;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class BookingConfirmationMail extends Mailable
{
    use Queueable, SerializesModels;

    public $booking;
    public $isArtistEmail;
    public $questions;

    /**
     * Create a new message instance.
     */
    public function __construct($booking, $isArtistEmail = false, $questions = [])
    {
        $this->booking = $booking;
        $this->isArtistEmail = $isArtistEmail;
        $this->questions = $questions;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        $title = $this->booking->displayTitle();
        $subject = $this->isArtistEmail
            ? 'New Booking Notification - '.$title
            : 'Booking Confirmation - '.$title;

        return new Envelope(
            subject: $subject,
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        $view = $this->isArtistEmail
            ? 'emails.booking-confirmation-artist'
            : 'emails.booking-confirmation-user';

        return new Content(
            view: $view,
            with: $this->getEmailData(),
        );
    }

    /**
     * Get email data for the view
     */
    private function getEmailData(): array
    {
        $booking = $this->booking;
        $artist = $booking->artist;
        $customer = $booking->user;

        // Format booking date and time
        $bookingDate = \Carbon\Carbon::parse($booking->booking_date)->format('l, F j, Y');
        $startTime = \Carbon\Carbon::createFromFormat('H:i:s', $booking->start_time_utc)
            ->setTimezone($booking->timezone)
            ->format('g:i A');
        $endTime = \Carbon\Carbon::createFromFormat('H:i:s', $booking->end_time_utc)
            ->setTimezone($booking->timezone)
            ->format('g:i A');
        $bookingTime = $startTime.' - '.$endTime;

        // Calculate duration
        $start = \Carbon\Carbon::createFromFormat('H:i:s', $booking->start_time_utc);
        $end = \Carbon\Carbon::createFromFormat('H:i:s', $booking->end_time_utc);
        $duration = $start->diffInHours($end);

        // Currency symbol
        $currencySymbols = [
            'USD' => '$',
            'EUR' => '€',
            'GBP' => '£',
            'AED' => 'AED ',
            'SAR' => 'SAR ',
            'INR' => '₹',
            'JPY' => '¥',
            'CAD' => 'C$',
            'AUD' => 'A$',
        ];
        $currencySymbol = $currencySymbols[strtoupper($booking->currency)] ?? $booking->currency.' ';

        $baseData = [
            'bookingId' => $booking->id,
            'completionCode' => (string) ($booking->completion_code ?? ''),
            'tattooTitle' => $booking->displayTitle(),
            'bookingDate' => $bookingDate,
            'bookingTime' => $bookingTime,
            'duration' => $duration,
            'currencySymbol' => $currencySymbol,
            'meetLink' => $booking->google_meet_link,
            'meetingTime' => $bookingDate.' at '.$startTime,
        ];

        if ($this->isArtistEmail) {
            // Artist email — tax is platform-collected and excluded from artist amount
            return array_merge($baseData, [
                'artistName' => ucfirst($artist->first_name).' '.ucfirst($artist->last_name),
                'customerName' => ucfirst($customer->first_name).' '.ucfirst($customer->last_name),
                'amountReceived' => $this->resolveArtistAmountReceived($booking, $artist),
                'questionsAnswers' => $booking->questions_answers ?? [],
                'questions' => $this->questions,
            ]);
        }

        $seeBookingUrl = null;
        if ($customer) {
            $seeBookingUrl = URL::temporarySignedRoute(
                'user.post-booking.access',
                now()->addDays(14),
                ['user' => $customer->id, 'booking' => $booking->id]
            );
        }

        // Customer email — full amount paid (includes fee + tax)
        return array_merge($baseData, [
            'userName' => ucfirst($customer->first_name).' '.ucfirst($customer->last_name),
            'artistName' => $artist?->userDetail?->publicDisplayName()
                ?: trim(ucfirst((string) $artist->first_name).' '.ucfirst((string) $artist->last_name)),
            'totalAmount' => $booking->total_amount_paid,
            'seeBookingUrl' => $seeBookingUrl,
        ]);
    }

    /**
     * Artist receives deposit (minus any artist-paid booking fee). Tax is not included.
     */
    private function resolveArtistAmountReceived($booking, $artist): float
    {
        $userDetail = $artist?->userDetail;
        if ($userDetail) {
            return app(ArtistPayoutService::class)->computeArtistPayoutAmount($booking, $userDetail);
        }

        return max(0, round((float) ($booking->deposit_amount ?? 0), 2));
    }

    /**
     * Get the attachments for the message.
     *
     * @return array<int, \Illuminate\Mail\Mailables\Attachment>
     */
    public function attachments(): array
    {
        return [];
    }
}
