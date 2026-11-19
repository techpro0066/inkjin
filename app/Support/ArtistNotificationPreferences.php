<?php

namespace App\Support;

use App\Models\User;

final class ArtistNotificationPreferences
{
    public const NEW_BOOKINGS = 'new_bookings';

    public const NEW_REQUESTS = 'new_requests';

    public const CONSENT_FORM_SIGNED = 'consent_form_signed';

    public const NEW_MESSAGES = 'new_messages';

    public const PAYOUTS_SENT = 'payouts_sent';

    public const SESSION_FEEDBACK = 'session_feedback';

    public const STUDIO_ANSWERS = 'studio_answers';

    public const KEYS = [
        self::NEW_BOOKINGS,
        self::NEW_REQUESTS,
        self::CONSENT_FORM_SIGNED,
        self::NEW_MESSAGES,
        self::PAYOUTS_SENT,
        self::SESSION_FEEDBACK,
        self::STUDIO_ANSWERS,
    ];

    public static function allows(?User $artist, string $key): bool
    {
        if (! $artist) {
            return true;
        }

        $disabled = $artist->userDetail?->not_allowed_notification;

        return ! is_array($disabled) || ! in_array($key, $disabled, true);
    }
}
