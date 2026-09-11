<?php

namespace App\Services;

use App\Mail\TattooAftercareMail;
use App\Models\Booking;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class BookingAftercareService
{
    public const SECTION_KEYS = [
        'first_24_hours',
        'keeping_it_clean',
        'moisturizing',
        'next_2_4_weeks',
    ];

    public const TEXT_KEYS = [
        'what_is_normal',
        'when_to_get_help',
        'once_healed',
        'questions',
    ];

    /**
     * Send aftercare email once when a booking is marked completed (if auto-send is on).
     */
    public function sendForCompletedBooking(Booking $booking): bool
    {
        if ((string) $booking->status !== 'completed') {
            return false;
        }

        if ($booking->aftercare_sent_at) {
            return false;
        }

        $booking->loadMissing(['user', 'artist.userDetail']);
        $client = $booking->user;
        $email = trim((string) ($client?->email ?? ''));
        if (! $client || $email === '') {
            return false;
        }

        $artistDetail = $booking->artist?->userDetail;
        if (! $artistDetail || ! $artistDetail->aftercare_send_automatically) {
            return false;
        }

        $claimed = false;
        try {
            $claimed = DB::transaction(function () use ($booking) {
                $locked = Booking::query()
                    ->whereKey($booking->id)
                    ->where('status', 'completed')
                    ->whereNull('aftercare_sent_at')
                    ->lockForUpdate()
                    ->first();

                if (! $locked) {
                    return false;
                }

                $locked->update(['aftercare_sent_at' => now()]);

                return true;
            });
        } catch (\Throwable $e) {
            Log::error('Failed to claim aftercare email send', [
                'booking_id' => $booking->id,
                'error' => $e->getMessage(),
            ]);

            return false;
        }

        if (! $claimed) {
            return false;
        }

        try {
            if ($artistDetail && $booking->artist) {
                $artistDetail->setRelation('user', $booking->artist);
            }

            $content = $this->resolveContent(
                is_array($artistDetail->aftercare_content) ? $artistDetail->aftercare_content : null
            );

            Mail::to($email)->send(new TattooAftercareMail(
                $booking,
                $client,
                $artistDetail,
                $content['sections'],
                $content['text_blocks']
            ));

            return true;
        } catch (\Throwable $e) {
            Booking::query()->whereKey($booking->id)->update(['aftercare_sent_at' => null]);
            Log::error('Failed to send tattoo aftercare email', [
                'booking_id' => $booking->id,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * @param  array<string, mixed>|null  $stored
     * @return array{sections: list<array{key: string, title: string, items: list<string>}>, text_blocks: array<string, array{title: string, body: string}>}
     */
    public function resolveContent(?array $stored): array
    {
        $defaults = [
            'sections' => $this->defaultSections(),
            'text_blocks' => $this->defaultTextBlocks(),
        ];

        if ($stored === null) {
            return $defaults;
        }

        $storedSections = is_array($stored['sections'] ?? null) ? $stored['sections'] : [];
        $storedTexts = is_array($stored['text_blocks'] ?? null) ? $stored['text_blocks'] : [];

        $sections = [];
        foreach ($defaults['sections'] as $section) {
            $key = $section['key'];
            $items = $storedSections[$key] ?? null;
            if (is_array($items)) {
                $section['items'] = $this->cleanItems($items);
            }
            $sections[] = $section;
        }

        $textBlocks = [];
        foreach ($defaults['text_blocks'] as $key => $block) {
            if (array_key_exists($key, $storedTexts)) {
                $block['body'] = trim((string) $storedTexts[$key]);
            }
            $textBlocks[$key] = $block;
        }

        return [
            'sections' => $sections,
            'text_blocks' => $textBlocks,
        ];
    }

    /**
     * @param  array<string, mixed>  $sections
     * @param  array<string, mixed>  $textBlocks
     * @return array{sections: array<string, list<string>>, text_blocks: array<string, string>}
     */
    public function normalizeContent(array $sections, array $textBlocks): array
    {
        $normalizedSections = [];
        foreach (self::SECTION_KEYS as $key) {
            $items = is_array($sections[$key] ?? null) ? $sections[$key] : [];
            $normalizedSections[$key] = $this->cleanItems($items);
        }

        $normalizedTexts = [];
        foreach (self::TEXT_KEYS as $key) {
            $normalizedTexts[$key] = trim((string) ($textBlocks[$key] ?? ''));
        }

        return [
            'sections' => $normalizedSections,
            'text_blocks' => $normalizedTexts,
        ];
    }

    /**
     * @param  array<int, mixed>  $items
     * @return list<string>
     */
    private function cleanItems(array $items): array
    {
        return array_values(array_filter(array_map(
            fn ($item) => trim((string) $item),
            $items
        ), fn (string $item) => $item !== ''));
    }

    /**
     * @return list<array{key: string, title: string, items: list<string>}>
     */
    public function defaultSections(): array
    {
        return [
            [
                'key' => 'first_24_hours',
                'title' => 'First 24 hours',
                'items' => [
                    'Always wash your hands before touching your new tattoo.',
                    'Follow the instructions your artist gave you for removing your bandage or protective film.',
                    'Once uncovered, gently wash the tattoo with lukewarm water and a mild, fragrance-free soap.',
                    'Pat it dry with a clean paper towel. Don\'t rub or scrub it.',
                ],
            ],
            [
                'key' => 'keeping_it_clean',
                'title' => 'Keeping it clean',
                'items' => [
                    'Gently wash your tattoo 1–2 times a day, or as directed by your artist.',
                    'Keep the area clean and dry.',
                    'Don\'t pick, scratch, or peel any scabs or flaking skin. Let them come off naturally.',
                    'Avoid tight or dirty clothing rubbing against the tattoo.',
                ],
            ],
            [
                'key' => 'moisturizing',
                'title' => 'Moisturizing',
                'items' => [
                    'Once your artist recommends it, apply a very thin layer of fragrance-free tattoo aftercare product or moisturiser.',
                    'Don\'t over-moisturize.',
                    'Your tattoo shouldn\'t be covered in a thick or greasy layer.',
                ],
            ],
            [
                'key' => 'next_2_4_weeks',
                'title' => 'For the next 2–4 weeks',
                'items' => [
                    'Avoid soaking the tattoo while it\'s healing. That means no swimming pools, hot tubs, baths, saunas, or prolonged soaking. Showers are fine.',
                    'Avoid direct sunlight and tanning.',
                    'Avoid activities that cause excessive rubbing, stretching, sweating, or contamination of the tattoo while it is healing.',
                ],
            ],
        ];
    }

    /**
     * @return array<string, array{title: string, body: string}>
     */
    public function defaultTextBlocks(): array
    {
        return [
            'what_is_normal' => [
                'title' => 'What is normal',
                'body' => 'Some redness, tenderness, mild swelling, itching, flaking, and small amounts of clear fluid or ink can occur during normal healing, particularly during the first few days.',
            ],
            'when_to_get_help' => [
                'title' => 'When to get help',
                'body' => 'If you develop worsening or spreading redness, increasing pain or swelling, significant warmth, pus or unusual discharge, red streaks, fever, or other symptoms that concern you, seek medical advice promptly. Don\'t rely on your tattoo artist to diagnose or treat a possible infection.',
            ],
            'once_healed' => [
                'title' => 'Once healed',
                'body' => 'Protect your tattoo from the sun. Using a broad-spectrum sunscreen on healed skin can help preserve the tattoo over time.',
            ],
            'questions' => [
                'title' => 'Questions?',
                'body' => 'If you\'re unsure about anything during the healing process, contact your artist. We\'re happy to help with normal aftercare questions.',
            ],
        ];
    }
}
