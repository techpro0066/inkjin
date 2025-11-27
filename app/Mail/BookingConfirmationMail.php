<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
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
        $subject = $this->isArtistEmail 
            ? 'New Booking Notification - ' . $this->booking->tattoo->title
            : 'Booking Confirmation - ' . $this->booking->tattoo->title;
            
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
        $tattoo = $booking->tattoo;
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
        $bookingTime = $startTime . ' - ' . $endTime;
        
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
        $currencySymbol = $currencySymbols[strtoupper($booking->currency)] ?? $booking->currency . ' ';
        
        $baseData = [
            'bookingId' => $booking->id,
            'tattooTitle' => $tattoo->title,
            'bookingDate' => $bookingDate,
            'bookingTime' => $bookingTime,
            'duration' => $duration,
            'currencySymbol' => $currencySymbol,
        ];
        
        if ($this->isArtistEmail) {
            // Artist email data
            return array_merge($baseData, [
                'artistName' => $artist->name,
                'customerName' => $customer->name,
                'customerEmail' => $customer->email,
                'amountReceived' => $booking->total_amount_paid - $booking->platform_fee, // Amount after platform fee
                'questionsAnswers' => $booking->questions_answers ?? [],
                'questions' => $this->questions,
            ]);
        } else {
            // Customer email data
            return array_merge($baseData, [
                'userName' => $customer->name,
                'artistName' => $artist->name,
                'totalAmount' => $booking->total_amount_paid,
            ]);
        }
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
