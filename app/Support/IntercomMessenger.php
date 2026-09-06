<?php

namespace App\Support;

use App\Models\User;
use Illuminate\Support\Facades\Auth;

class IntercomMessenger
{
    /**
     * Settings for booting the Intercom Messenger on the artist dashboard.
     *
     * @return array<string, mixed>|null
     */
    public static function artistBootSettings(?User $user = null): ?array
    {
        $appId = trim((string) config('services.intercom.app_id', ''));
        if ($appId === '') {
            return null;
        }

        $user ??= Auth::user();
        if (! $user || $user->role !== 'artist') {
            return null;
        }

        $user->loadMissing('userDetail');

        $username = trim((string) ($user->userDetail?->user_name ?? ''));
        $userId = $username !== '' ? $username : (string) $user->id;
        $name = trim((string) ($user->name ?: trim(($user->first_name ?? '').' '.($user->last_name ?? ''))));
        $email = trim((string) ($user->email ?? ''));

        $settings = [
            'api_base' => (string) config('services.intercom.api_base', 'https://api-iam.intercom.io'),
            'app_id' => $appId,
            'hide_default_launcher' => true,
            'user_id' => $userId,
            'name' => $name !== '' ? $name : $userId,
            'email' => $email,
        ];

        $userHash = self::userHash($userId);
        if ($userHash !== null) {
            $settings['user_hash'] = $userHash;
        }

        return $settings;
    }

    public static function userHash(string $userId): ?string
    {
        $secret = trim((string) config('services.intercom.identity_secret', ''));
        if ($secret === '' || $userId === '') {
            return null;
        }

        return hash_hmac('sha256', $userId, $secret);
    }
}
