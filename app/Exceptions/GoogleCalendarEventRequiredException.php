<?php

namespace App\Exceptions;

class GoogleCalendarEventRequiredException extends \RuntimeException
{
    public static function notConnected(): self
    {
        return new self(
            'This artist uses auto scheduling, but Google Calendar is not connected. Booking cannot be completed right now. Please try again later or contact the artist.'
        );
    }

    public static function createFailed(): self
    {
        return new self(
            'We could not add this appointment to the artist\'s Google Calendar. The booking was not confirmed and any payment will be refunded. Please try again or choose another time.'
        );
    }
}
