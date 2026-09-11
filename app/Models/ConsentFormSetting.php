<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ConsentFormSetting extends Model
{
    public const LANGUAGE_MODE_SINGLE = 'single';

    public const LANGUAGE_MODE_BOTH = 'both';

    /** ISO country → consent locale (same map as consent-form-settings.js). */
    public const MARKET_LOCALE = [
        'AT' => 'de', 'BE' => 'nl', 'BG' => 'bg', 'HR' => 'hr', 'CY' => 'el', 'CZ' => 'cs',
        'DK' => 'da', 'EE' => 'et', 'FI' => 'fi', 'FR' => 'fr', 'DE' => 'de', 'GR' => 'el',
        'HU' => 'hu', 'IE' => 'en', 'IT' => 'it', 'LV' => 'lv', 'LT' => 'lt', 'LU' => 'fr',
        'MT' => 'en', 'NL' => 'nl', 'NO' => 'no', 'PL' => 'pl', 'PT' => 'pt', 'RO' => 'ro',
        'SK' => 'sk', 'SI' => 'sl', 'ES' => 'es', 'SE' => 'sv', 'CH' => 'de', 'GB' => 'en',
        'US' => 'en', 'CA' => 'en', 'AU' => 'en', 'NZ' => 'en', 'SG' => 'en',
    ];

    protected $fillable = [
        'user_id',
        'studio_market',
        'registration_number',
        'allow_younger',
        'age_allow',
        'language_mode',
        'form_language',
        'other_language',
        'ask_photo',
        'send_automatically',
    ];

    protected $casts = [
        'allow_younger' => 'boolean',
        'ask_photo' => 'boolean',
        'send_automatically' => 'boolean',
        'age_allow' => 'integer',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public static function localeForMarket(string $countryCode): string
    {
        $code = strtoupper(trim($countryCode));

        return self::MARKET_LOCALE[$code] ?? 'en';
    }

    /**
     * Language(s) the client form should expose.
     *
     * @return list<string>
     */
    public function clientLanguages(): array
    {
        if ($this->language_mode === self::LANGUAGE_MODE_BOTH) {
            $second = $this->other_language ?: self::localeForMarket((string) $this->studio_market);
            $langs = ['en'];
            if ($second && $second !== 'en') {
                $langs[] = $second;
            }

            return $langs;
        }

        $single = $this->form_language ?: 'en';

        return [$single];
    }

    public function toArtistArray(): array
    {
        return [
            'id' => $this->id,
            'studio_market' => strtoupper((string) $this->studio_market),
            'registration_number' => $this->registration_number,
            'allow_younger' => (bool) $this->allow_younger,
            'age_allow' => $this->age_allow !== null ? (int) $this->age_allow : null,
            'language_mode' => $this->language_mode === self::LANGUAGE_MODE_BOTH
                ? self::LANGUAGE_MODE_BOTH
                : self::LANGUAGE_MODE_SINGLE,
            'form_language' => $this->form_language ?: 'en',
            'other_language' => $this->other_language,
            'ask_photo' => (bool) $this->ask_photo,
            'send_automatically' => (bool) $this->send_automatically,
            'client_languages' => $this->clientLanguages(),
        ];
    }
}
