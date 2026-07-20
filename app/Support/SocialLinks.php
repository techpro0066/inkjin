<?php

namespace App\Support;

use Illuminate\Validation\ValidationException;

class SocialLinks
{
    /** @var array<string, array{label: string, example: string, hosts: list<string>}> */
    private const PLATFORMS = [
        'instagram' => [
            'label' => 'Instagram',
            'example' => 'https://www.instagram.com/yourhandle',
            'hosts' => ['instagram.com', 'www.instagram.com'],
        ],
        'tiktok' => [
            'label' => 'TikTok',
            'example' => 'https://www.tiktok.com/@yourhandle',
            'hosts' => ['tiktok.com', 'www.tiktok.com'],
        ],
        'youtube' => [
            'label' => 'YouTube',
            'example' => 'https://www.youtube.com/@yourchannel',
            'hosts' => ['youtube.com', 'www.youtube.com', 'm.youtube.com', 'youtu.be'],
        ],
        'facebook' => [
            'label' => 'Facebook',
            'example' => 'https://www.facebook.com/yourpage',
            'hosts' => ['facebook.com', 'www.facebook.com', 'm.facebook.com', 'web.facebook.com', 'fb.com', 'www.fb.com'],
        ],
    ];

    /**
     * @param  array<string, mixed>  $input
     * @return array<string, string>
     */
    public static function normalize(array $input): array
    {
        $errors = [];
        $normalized = [];

        $website = isset($input['website']) ? trim((string) $input['website']) : '';
        if ($website !== '') {
            if (! preg_match('#^https?://#i', $website)) {
                $errors['social_links.website'] = ['Website must start with http:// or https://'];
            } elseif (filter_var($website, FILTER_VALIDATE_URL) === false) {
                $errors['social_links.website'] = ['Please enter a valid website URL.'];
            } else {
                $normalized['website'] = self::canonicalizeHttpUrl($website);
            }
        }

        foreach (self::PLATFORMS as $key => $config) {
            $raw = isset($input[$key]) ? trim((string) $input[$key]) : '';
            if ($raw === '') {
                continue;
            }

            $url = self::normalizePlatformValue($key, $raw);
            if ($url === null || ! self::matchesPlatform($key, $url)) {
                $errors["social_links.{$key}"] = [
                    "Please enter a valid {$config['label']} URL (e.g. {$config['example']}).",
                ];

                continue;
            }

            $normalized[$key] = $url;
        }

        if ($errors !== []) {
            throw ValidationException::withMessages($errors);
        }

        return $normalized;
    }

    private static function normalizePlatformValue(string $platform, string $raw): ?string
    {
        $raw = trim($raw);
        if ($raw === '') {
            return null;
        }

        if (preg_match('#^https?://#i', $raw)) {
            if (filter_var($raw, FILTER_VALIDATE_URL) === false) {
                return null;
            }

            $scheme = parse_url($raw, PHP_URL_SCHEME);
            if (! in_array(strtolower((string) $scheme), ['http', 'https'], true)) {
                return null;
            }

            return self::canonicalizeHttpUrl($raw);
        }

        if ($platform === 'facebook') {
            return null;
        }

        $handle = ltrim($raw, '@');
        $handle = trim($handle);
        if ($handle === '' || preg_match('/\s/', $handle) || ! preg_match('/^[a-zA-Z0-9._-]+$/', $handle)) {
            return null;
        }

        return match ($platform) {
            'instagram' => 'https://www.instagram.com/'.rawurlencode($handle),
            'tiktok' => 'https://www.tiktok.com/@'.rawurlencode($handle),
            'youtube' => 'https://www.youtube.com/@'.rawurlencode($handle),
            default => null,
        };
    }

    private static function matchesPlatform(string $platform, string $url): bool
    {
        $host = parse_url($url, PHP_URL_HOST);
        if (! is_string($host) || $host === '') {
            return false;
        }

        $allowed = self::PLATFORMS[$platform]['hosts'] ?? [];

        return self::hostMatches($host, $allowed);
    }

    /**
     * @param  list<string>  $allowedHosts
     */
    private static function hostMatches(string $host, array $allowedHosts): bool
    {
        $host = strtolower($host);

        foreach ($allowedHosts as $allowedHost) {
            $allowedHost = strtolower($allowedHost);
            if ($host === $allowedHost) {
                return true;
            }
        }

        return false;
    }

    private static function canonicalizeHttpUrl(string $url): string
    {
        $parts = parse_url($url);
        if ($parts === false || empty($parts['host'])) {
            return $url;
        }

        $scheme = strtolower((string) ($parts['scheme'] ?? 'https'));
        $host = strtolower((string) $parts['host']);
        $path = $parts['path'] ?? '';
        $query = isset($parts['query']) ? '?'.$parts['query'] : '';
        $fragment = isset($parts['fragment']) ? '#'.$parts['fragment'] : '';

        return $scheme.'://'.$host.$path.$query.$fragment;
    }
}
