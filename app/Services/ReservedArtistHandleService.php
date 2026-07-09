<?php

namespace App\Services;

class ReservedArtistHandleService
{
    /** @var array<string, string>|null handle (lowercase) => email (lowercase) */
    private static ?array $handleToEmail = null;

    /** @var array<string, string>|null email (lowercase) => inkjin_handle (original casing) */
    private static ?array $emailToHandle = null;

    private function ensureLoaded(): void
    {
        if (self::$handleToEmail !== null) {
            return;
        }

        self::$handleToEmail = [];
        self::$emailToHandle = [];

        $path = base_path('artist-handles.json');
        if (! is_file($path)) {
            return;
        }

        $rows = json_decode((string) file_get_contents($path), true);
        if (! is_array($rows)) {
            return;
        }

        foreach ($rows as $row) {
            if (! is_array($row)) {
                continue;
            }

            $email = strtolower(trim((string) ($row['email'] ?? '')));
            $handle = trim((string) ($row['inkjin_handle'] ?? ''));
            $handleKey = strtolower($handle);

            if ($email === '' || $handleKey === '') {
                continue;
            }

            self::$handleToEmail[$handleKey] = $email;
            self::$emailToHandle[$email] = $handle;
        }
    }

    public function canUseUsername(string $userEmail, string $username): bool
    {
        $this->ensureLoaded();

        $email = strtolower(trim($userEmail));
        $handleKey = strtolower(trim($username));

        if ($handleKey === '') {
            return true;
        }

        $reservedForEmail = self::$handleToEmail[$handleKey] ?? null;

        if ($reservedForEmail === null) {
            return true;
        }

        return $reservedForEmail === $email;
    }

    public function reservedHandleForEmail(string $userEmail): ?string
    {
        $this->ensureLoaded();

        $email = strtolower(trim($userEmail));

        return self::$emailToHandle[$email] ?? null;
    }
}
