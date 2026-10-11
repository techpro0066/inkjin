<?php

namespace App\Support;

use App\Models\UserDetail;

class PageLook
{
    public const DEFAULT_SCHEME = '#1A1A2E,#3A3A5C,#ECEBF3';

    /** @var array<string, string> */
    public const LEGACY_COLOR_TO_SCHEME = [
        'default' => '#4B0F8F,#7B3FC4,#F3E8FF',
        'ocean' => '#1565C0,#42A5F5,#E3F0FC',
        'forest' => '#1B7F3B,#3FBF6A,#E4F5EA',
        'coral' => '#E64A19,#FF7043,#FDEBE4',
        'midnight' => '#1A1A2E,#3A3A5C,#ECEBF3',
        'rose' => '#AD1457,#F06292,#FCE4EC',
        'golden' => '#E07000,#FFB300,#FDF0DC',
    ];

    /** @var array<string, string> */
    public const SCHEME_TO_LEGACY = [
        '#4B0F8F,#7B3FC4,#F3E8FF' => 'default',
        '#1565C0,#42A5F5,#E3F0FC' => 'ocean',
        '#1B7F3B,#3FBF6A,#E4F5EA' => 'forest',
        '#E64A19,#FF7043,#FDEBE4' => 'coral',
        '#1A1A2E,#3A3A5C,#ECEBF3' => 'midnight',
        '#E07000,#FFB300,#FDF0DC' => 'golden',
        '#AD1457,#F06292,#FCE4EC' => 'rose',
    ];

    /**
     * BPLook-compatible state for the dashboard editor / public page.
     *
     * @return array{mode: string, scheme: string, banner: ?string, c: array<string, mixed>}
     */
    public static function stateFromUserDetail(UserDetail $detail): array
    {
        $scheme = trim((string) ($detail->page_look_scheme ?? ''));
        if ($scheme === '') {
            $legacy = (string) ($detail->personal_page_color ?? 'default');
            $scheme = self::LEGACY_COLOR_TO_SCHEME[$legacy] ?? self::DEFAULT_SCHEME;
        }

        $mode = ($detail->page_look_mode === 'custom') ? 'custom' : 'standard';

        $banner = self::publicUrl($detail->personal_page_background_image);
        $bgD = self::publicUrl($detail->page_bg_desktop);
        $bgM = self::publicUrl($detail->page_bg_mobile);

        return [
            'mode' => $mode,
            'scheme' => $scheme,
            'banner' => $banner,
            'c' => [
                'bgD' => $bgD,
                'bgM' => $bgM,
                'bgLum' => $detail->page_bg_lum !== null ? (float) $detail->page_bg_lum : 0.093,
                'bgAvg' => $detail->page_bg_avg ?: '#A32724',
                'box' => $detail->page_box_color ?: '#FFFFFF',
                'effect' => in_array($detail->page_box_effect, ['solid', 'see', 'glass'], true)
                    ? $detail->page_box_effect
                    : 'glass',
                'op' => max(5, min(95, (int) ($detail->page_box_opacity ?? 22))),
                'title' => in_array($detail->page_title_mode, ['auto', 'black', 'white', 'custom'], true)
                    ? $detail->page_title_mode
                    : 'auto',
                'titleC' => $detail->page_title_color ?: '#FFFFFF',
                'text' => in_array($detail->page_text_mode, ['auto', 'black', 'white'], true)
                    ? $detail->page_text_mode
                    : 'auto',
                'radius' => in_array($detail->page_box_radius, ['none', 's', 'm', 'l'], true)
                    ? $detail->page_box_radius
                    : 'l',
            ],
        ];
    }

    /**
     * Columns to persist from a BPLook state (relative storage paths already resolved).
     *
     * @param  array<string, mixed>  $state
     * @return array<string, mixed>
     */
    public static function columnsFromState(array $state, ?string $bannerPath, ?string $bgDPath, ?string $bgMPath): array
    {
        $scheme = trim((string) ($state['scheme'] ?? self::DEFAULT_SCHEME));
        if ($scheme === '') {
            $scheme = self::DEFAULT_SCHEME;
        }

        $c = is_array($state['c'] ?? null) ? $state['c'] : [];
        $mode = ($state['mode'] ?? 'standard') === 'custom' ? 'custom' : 'standard';
        $legacy = self::SCHEME_TO_LEGACY[$scheme] ?? 'default';

        $effect = in_array($c['effect'] ?? '', ['solid', 'see', 'glass'], true) ? $c['effect'] : 'glass';
        $title = in_array($c['title'] ?? '', ['auto', 'black', 'white', 'custom'], true) ? $c['title'] : 'auto';
        $text = in_array($c['text'] ?? '', ['auto', 'black', 'white'], true) ? $c['text'] : 'auto';
        $radius = in_array($c['radius'] ?? '', ['none', 's', 'm', 'l'], true) ? $c['radius'] : 'l';

        return [
            'page_look_mode' => $mode,
            'page_look_scheme' => $scheme,
            'personal_page_color' => $legacy,
            'personal_page_background_image' => $bannerPath,
            'page_bg_desktop' => $bgDPath,
            'page_bg_mobile' => $bgMPath,
            'page_bg_lum' => isset($c['bgLum']) ? round((float) $c['bgLum'], 5) : null,
            'page_bg_avg' => self::normalizeHex($c['bgAvg'] ?? null),
            'page_box_color' => self::normalizeHex($c['box'] ?? '#FFFFFF') ?: '#FFFFFF',
            'page_box_effect' => $effect,
            'page_box_opacity' => max(5, min(95, (int) ($c['op'] ?? 22))),
            'page_title_mode' => $title,
            'page_title_color' => self::normalizeHex($c['titleC'] ?? '#FFFFFF'),
            'page_text_mode' => $text,
            'page_box_radius' => $radius,
        ];
    }

    public static function publicUrl(?string $relativePath): ?string
    {
        $relativePath = trim((string) $relativePath);
        if ($relativePath === '') {
            return null;
        }
        if (str_starts_with($relativePath, 'http://') || str_starts_with($relativePath, 'https://') || str_starts_with($relativePath, 'data:')) {
            return $relativePath;
        }

        return asset(ltrim($relativePath, '/'));
    }

    public static function normalizeHex(mixed $value): ?string
    {
        $value = trim((string) $value);
        if ($value === '') {
            return null;
        }
        if (preg_match('/^#([0-9A-Fa-f]{3}|[0-9A-Fa-f]{6})$/', $value)) {
            return strtoupper(strlen($value) === 4
                ? '#'.$value[1].$value[1].$value[2].$value[2].$value[3].$value[3]
                : $value);
        }

        return null;
    }

    /**
     * Theme colors for the current public page (standard layout).
     *
     * @return array{primary: string, bg: string}
     */
    public static function themeColors(UserDetail $detail): array
    {
        $scheme = trim((string) ($detail->page_look_scheme ?? ''));
        if ($scheme === '') {
            $legacy = (string) ($detail->personal_page_color ?? 'default');
            $scheme = self::LEGACY_COLOR_TO_SCHEME[$legacy] ?? self::DEFAULT_SCHEME;
        }
        $parts = array_values(array_filter(array_map('trim', explode(',', $scheme))));
        $primary = $parts[0] ?? '#310F7A';
        $bg = $parts[1] ?? $primary;

        return [
            'primary' => $primary,
            'bg' => $bg,
        ];
    }
}
