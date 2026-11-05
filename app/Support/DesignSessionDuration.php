<?php

namespace App\Support;

/**
 * Maps artist-design session_duration tokens (e.g. "30min", "2h", "half-day")
 * to minutes and human labels. Tokens are stored as strings on artist_designs.
 */
final class DesignSessionDuration
{
    /** @var list<string> */
    public const TOKENS = [
        '30min',
        '1h',
        '2h',
        '3h',
        '4h',
        '5h',
        '6h',
        '7h',
        '8h',
        'half-day',
        'full-day',
    ];

    public static function minutes(?string $token, int $default = 120): int
    {
        $token = strtolower(trim((string) $token));

        $mapped = match ($token) {
            '30min' => 30,
            '1h' => 60,
            '2h' => 120,
            '3h' => 180,
            '4h' => 240,
            '5h' => 300,
            '6h' => 360,
            '7h' => 420,
            '8h' => 480,
            'half-day' => 240,
            'full-day' => 480,
            default => null,
        };

        if ($mapped !== null) {
            return $mapped;
        }

        return self::parseLegacy($token, $default);
    }

    public static function label(?string $token): string
    {
        $token = strtolower(trim((string) $token));

        return match ($token) {
            '30min' => '30 min',
            '1h' => '1 hour',
            '2h' => '2 hours',
            '3h' => '3 hours',
            '4h' => '4 hours',
            '5h' => '5 hours',
            '6h' => '6 hours',
            '7h' => '7 hours',
            '8h' => '8 hours',
            'half-day' => 'Half day',
            'full-day' => 'Full day',
            '' => '—',
            default => self::formatMinutes(self::minutes($token)),
        };
    }

    public static function formatMinutes(int $minutes): string
    {
        $mins = max(0, $minutes);
        $hours = intdiv($mins, 60);
        $remainder = $mins % 60;

        if ($hours > 0 && $remainder > 0) {
            return $hours.'h '.$remainder.'m';
        }
        if ($hours === 1 && $remainder === 0) {
            return '1 hour';
        }
        if ($hours > 1 && $remainder === 0) {
            return $hours.' hours';
        }
        if ($remainder > 0) {
            return $remainder.' min';
        }

        return '—';
    }

    private static function parseLegacy(string $token, int $default): int
    {
        if ($token === '') {
            return max(15, $default);
        }

        if (is_numeric($token) && (float) $token > 0) {
            // Legacy numeric values were stored as hours.
            return max(15, (int) round((float) $token * 60));
        }

        if (preg_match('/^(\d+)\s*min(?:ute)?s?$/i', $token, $match)) {
            return max(15, (int) $match[1]);
        }

        if (preg_match('/^(\d+(?:\.\d+)?)\s*h(?:ours?)?$/i', $token, $match)) {
            return max(15, (int) round((float) $match[1] * 60));
        }

        return max(15, $default);
    }
}
