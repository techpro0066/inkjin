<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class BookingCancellationMail extends Mailable
{
    use Queueable, SerializesModels;

    public $booking;
    public $isArtistEmail;
    public $cancellationType;
    public $isNoShow;

    /**
     * Create a new message instance.
     */
    public function __construct($booking, $isArtistEmail = false, $cancellationType = 'client', $isNoShow = false)
    {
        $this->booking = $booking;
        $this->isArtistEmail = $isArtistEmail;
        $this->cancellationType = $cancellationType;
        $this->isNoShow = $isNoShow;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        $subject = $this->isNoShow
            ? 'Booking Marked as No-Show - ' . ($this->booking->tattoo->title ?? 'Booking')
            : ($this->cancellationType === 'artist'
                ? 'Booking Cancelled by Artist - ' . ($this->booking->tattoo->title ?? 'Booking')
                : 'Booking Cancelled - ' . ($this->booking->tattoo->title ?? 'Booking'));

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
            ? 'emails.booking-cancellation-artist'
            : 'emails.booking-cancellation-user';

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
        $tattoo = $booking->tattoo;
        $artist = $booking->artist;
        $customer = $booking->user;

        // Format booking date and time
        $bookingDate = \Carbon\Carbon::parse($booking->booking_date)->format('l, F j, Y');
        $startTime = \Carbon\Carbon::createFromFormat('H:i:s', $booking->start_time_utc)
            ->setTimezone($booking->timezone ?? 'UTC')
            ->format('g:i A');
        $endTime = \Carbon\Carbon::createFromFormat('H:i:s', $booking->end_time_utc)
            ->setTimezone($booking->timezone ?? 'UTC')
            ->format('g:i A');
        $bookingTime = $startTime . ' - ' . $endTime;

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
        $currencySymbol = $currencySymbols[strtoupper($booking->currency ?? 'USD')] ?? ($booking->currency ?? 'USD') . ' ';

        // Determine refund status
        $hasRefund = $booking->refund_amount > 0;
        $isPartialRefund = $booking->refund_status === 'partial' || ($hasRefund && $booking->refund_amount < $booking->total_amount_paid);
        $isFullRefund = $hasRefund && $booking->refund_amount == $booking->total_amount_paid;
        $depositForfeited = $booking->deposit_forfeited > 0;

        return [
            'bookingId' => $booking->id,
            'tattooTitle' => $tattoo->title ?? 'Custom Tattoo',
            'artistName' => $artist->name ?? 'Artist',
            'customerName' => $customer->name ?? 'Customer',
            'customerEmail' => $customer->email ?? '',
            'bookingDate' => $bookingDate,
            'bookingTime' => $bookingTime,
            'cancellationReason' => $booking->cancellation_reason,
            'cancellationType' => $this->cancellationType,
            'isNoShow' => $this->isNoShow,
            'cancelledAt' => $booking->cancelled_at ? $booking->cancelled_at->format('l, F j, Y g:i A') : '',
            'refundAmount' => $booking->refund_amount ?? 0,
            'depositForfeited' => $booking->deposit_forfeited ?? 0,
            'totalAmountPaid' => $booking->total_amount_paid ?? 0,
            'currencySymbol' => $currencySymbol,
            'hasRefund' => $hasRefund,
            'isPartialRefund' => $isPartialRefund,
            'isFullRefund' => $isFullRefund,
            'refundStatus' => $booking->refund_status ?? 'completed',
            'refundReason' => $booking->refund_reason ?? '',
        ];
    }

    /**
     * Get the attachments for the message.
     */
    public function attachments(): array
    {
        return [];
    }
}
