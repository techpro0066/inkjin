<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Facades\Log;

/**
 * Blocks Gmail addresses that look mass-generated via heavy dot insertion
 * (e.g. y.u.mat.o.mer.o.54@gmail.com) — a common bot signup pattern.
 */
class NotBotGmailPattern implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_string($value) || ! self::matches($value)) {
            return;
        }

        Log::info('Blocked bot-looking Gmail signup pattern', [
            'email' => strtolower(trim($value)),
            'ip' => request()?->ip(),
        ]);

        $fail('This email address looks unusual. Please use a normal email address, or contact support if this is a mistake.');
    }

    /**
     * Whether an email matches the heavy dotted-Gmail bot pattern.
     */
    public static function matches(string $email): bool
    {
        $email = strtolower(trim($email));
        if ($email === '' || ! str_contains($email, '@')) {
            return false;
        }

        [$local, $domain] = explode('@', $email, 2);

        if (! in_array($domain, ['gmail.com', 'googlemail.com'], true)) {
            return false;
        }

        $dotCount = substr_count($local, '.');
        $letters = preg_replace('/[.\d]/', '', $local) ?? '';
        $letterCount = strlen($letters);

        if ($letterCount === 0) {
            return false;
        }

        $dotDensity = $dotCount / $letterCount;

        return $dotCount >= 4 && $dotDensity > 0.3;
    }
}
