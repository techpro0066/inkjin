<?php

namespace App\Services;

use App\Models\Studio;
use App\Models\User;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class MailcoachSubscriberService
{
    public const TAG_ARTIST = 'artist';

    public const TAG_USER = 'user';

    public const TAG_STUDIO = 'studio';

    public function isConfigured(): bool
    {
        return filled(config('services.mailcoach.list_uuid'))
            && filled(config('services.mailcoach.api_token'))
            && ($this->apiBaseUrl() !== null);
    }

    public function queueSubscribeUser(User $user, string $tag): void
    {
        $email = trim((string) $user->email);
        if ($email === '' || ! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            Log::warning('Mailcoach skip: invalid user email', [
                'user_id' => $user->id,
                'tag' => $tag,
            ]);

            return;
        }

        $this->queueSubscribe([
            'email' => $email,
            'first_name' => trim((string) ($user->first_name ?? '')) ?: null,
            'last_name' => trim((string) ($user->last_name ?? '')) ?: null,
            'tag' => $tag,
        ]);
    }

    public function queueSubscribeStudio(Studio $studio): void
    {
        $email = trim((string) ($studio->email ?? ''));
        if ($email === '' || ! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            Log::warning('Mailcoach skip: invalid studio email', [
                'studio_id' => $studio->id,
            ]);

            return;
        }

        [$firstName, $lastName] = $this->splitName((string) ($studio->name ?? ''));

        $this->queueSubscribe([
            'email' => $email,
            'first_name' => $firstName,
            'last_name' => $lastName,
            'tag' => self::TAG_STUDIO,
        ]);
    }

    /**
     * @param  array{email: string, first_name?: ?string, last_name?: ?string, tag: string}  $payload
     */
    public function queueSubscribe(array $payload): void
    {
        if (! $this->isConfigured()) {
            Log::warning('Mailcoach skip: not configured', [
                'email' => $payload['email'] ?? null,
                'tag' => $payload['tag'] ?? null,
                'has_list_uuid' => filled(config('services.mailcoach.list_uuid')),
                'has_token' => filled(config('services.mailcoach.api_token')),
                'api_base' => $this->apiBaseUrl(),
            ]);

            return;
        }

        // Run inline so local XAMPP redirects cannot skip afterResponse callbacks.
        try {
            $ok = $this->subscribe(
                (string) $payload['email'],
                $payload['first_name'] ?? null,
                $payload['last_name'] ?? null,
                (string) $payload['tag']
            );

            if ($ok) {
                Log::info('Mailcoach subscribe ok', [
                    'email' => $payload['email'] ?? null,
                    'tag' => $payload['tag'] ?? null,
                ]);
            }
        } catch (\Throwable $e) {
            Log::warning('Mailcoach subscribe failed', [
                'email' => $payload['email'] ?? null,
                'tag' => $payload['tag'] ?? null,
                'message' => $e->getMessage(),
            ]);
        }
    }

    public function subscribe(string $email, ?string $firstName, ?string $lastName, string $tag): bool
    {
        if (! $this->isConfigured()) {
            return false;
        }

        $email = trim($email);
        if ($email === '' || ! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return false;
        }

        $listUuid = (string) config('services.mailcoach.list_uuid');
        $base = $this->apiBaseUrl();
        if ($base === null) {
            return false;
        }

        $url = $base.'/email-lists/'.$listUuid.'/subscribers';
        $body = [
            'email' => $email,
            'tags' => [trim($tag)],
            'skip_confirmation' => true,
        ];

        if (filled($firstName)) {
            $body['first_name'] = trim((string) $firstName);
        }
        if (filled($lastName)) {
            $body['last_name'] = trim((string) $lastName);
        }

        $response = Http::timeout(20)
            ->withToken((string) config('services.mailcoach.api_token'))
            ->acceptJson()
            ->asJson()
            ->post($url, $body);

        if (! $response->successful()) {
            Log::warning('Mailcoach subscribe rejected', [
                'email' => $email,
                'tag' => $tag,
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            return false;
        }

        return true;
    }

    private function apiBaseUrl(): ?string
    {
        $apiUrl = trim((string) config('services.mailcoach.api_url'));
        if ($apiUrl !== '') {
            $apiUrl = rtrim($apiUrl, '/');
            if (! preg_match('#^https?://#i', $apiUrl)) {
                $apiUrl = 'https://'.$apiUrl;
            }

            if (! str_ends_with(strtolower($apiUrl), '/api')) {
                $apiUrl .= '/api';
            }

            return $apiUrl;
        }

        $domain = trim((string) config('services.mailcoach.api_domain'));
        if ($domain === '') {
            return null;
        }

        $domain = preg_replace('#^https?://#i', '', $domain) ?? $domain;
        $domain = rtrim($domain, '/');

        return 'https://'.$domain.'/api';
    }

    /**
     * @return array{0: ?string, 1: ?string}
     */
    private function splitName(string $name): array
    {
        $name = trim(preg_replace('/\s+/', ' ', $name) ?? $name);
        if ($name === '') {
            return [null, null];
        }

        $parts = explode(' ', $name, 2);

        return [
            $parts[0] !== '' ? $parts[0] : null,
            isset($parts[1]) && $parts[1] !== '' ? $parts[1] : null,
        ];
    }
}
