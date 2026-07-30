<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;

class ClaudeDesignAnalysisService
{
    public const COLOR_OPTIONS = ['color', 'black-grey', 'both'];

    public function isConfigured(): bool
    {
        return filled(config('services.claude.api_key'));
    }

    /**
     * Analyze a tattoo design image and suggest listing fields.
     *
     * @param  array<int, string>  $allowedStyles
     * @param  array<int, string>  $fieldsNeeded  Subset of: title, description, primary_style, other_styles, suggested_placements, color, tags
     * @param  array<int, string>  $allowedPlacements
     * @return array{
     *     title?: string,
     *     description?: string,
     *     primary_style?: string,
     *     other_styles?: array<int, string>,
     *     suggested_placements?: array<int, string>,
     *     color?: string,
     *     tags?: array<int, string>
     * }
     */
    public function suggestFields(
        string $imageBinary,
        string $mediaType,
        array $allowedStyles,
        array $fieldsNeeded,
        array $allowedPlacements = []
    ): array {
        if (! $this->isConfigured()) {
            throw new RuntimeException('Claude API is not configured.');
        }

        $fieldsNeeded = array_values(array_intersect(
            ['title', 'description', 'primary_style', 'other_styles', 'suggested_placements', 'color', 'tags'],
            $fieldsNeeded
        ));

        if ($fieldsNeeded === []) {
            return [];
        }

        [$imageBinary, $mediaType] = $this->normalizeImage($imageBinary, $mediaType);

        $prompt = $this->buildPrompt($allowedStyles, $allowedPlacements, $fieldsNeeded);

        $response = Http::withHeaders([
            'x-api-key' => (string) config('services.claude.api_key'),
            'anthropic-version' => (string) config('services.claude.version', '2023-06-01'),
            'content-type' => 'application/json',
        ])
            ->timeout((int) config('services.claude.timeout', 60))
            ->post((string) config('services.claude.base_url', 'https://api.anthropic.com/v1/messages'), [
                'model' => (string) config('services.claude.model', 'claude-sonnet-5'),
                'max_tokens' => 1024,
                'messages' => [[
                    'role' => 'user',
                    'content' => [
                        [
                            'type' => 'image',
                            'source' => [
                                'type' => 'base64',
                                'media_type' => $mediaType,
                                'data' => base64_encode($imageBinary),
                            ],
                        ],
                        [
                            'type' => 'text',
                            'text' => $prompt,
                        ],
                    ],
                ]],
            ]);

        if (! $response->successful()) {
            Log::warning('Claude design analysis failed', [
                'status' => $response->status(),
                'body' => $response->json() ?: $response->body(),
            ]);

            throw new RuntimeException('Unable to analyze the design image right now.');
        }

        $text = $this->extractTextContent($response->json());
        $parsed = $this->parseJsonObject($text);

        return $this->normalizeSuggestions($parsed, $allowedStyles, $allowedPlacements, $fieldsNeeded);
    }

    /**
     * @param  array<int, string>  $allowedStyles
     * @param  array<int, string>  $allowedPlacements
     * @param  array<int, string>  $fieldsNeeded
     */
    private function buildPrompt(array $allowedStyles, array $allowedPlacements, array $fieldsNeeded): string
    {
        $stylesList = $allowedStyles === []
            ? '(no styles available)'
            : implode(', ', array_map(fn ($s) => '"'.$s.'"', $allowedStyles));

        $placementsList = $allowedPlacements === []
            ? '(no placements available)'
            : implode(', ', array_map(fn ($s) => '"'.$s.'"', $allowedPlacements));

        $fieldsList = implode(', ', $fieldsNeeded);

        return <<<PROMPT
You are helping a tattoo artist fill listing fields for a portfolio piece or available design image.

Return ONLY a single JSON object (no markdown, no commentary) with keys among: {$fieldsList}.
Only include keys that were requested.

Rules:
- title: short catchy tattoo title (max 80 chars).
- description: 1-2 sentences for clients (max 255 chars).
- primary_style: MUST be exactly one value from this list: {$stylesList}
- other_styles: array of up to 2 styles from the same list, different from primary_style. Prefer complementary styles. Use [] if none fit.
- suggested_placements: array of up to 3 body placements from this list: {$placementsList}. Pick the most likely places this design would be tattooed. Prefer concrete body areas; avoid vague options like "Not Sure" when better options fit. Use [] if none fit.
- color: exactly one of "color", "black-grey", or "both" (black-grey for monochrome/greywash; color for full color; both if mixed or ambiguous).
- tags: array of 3-8 short lowercase search tags (single words or short phrases, no #).

If the image is not a tattoo design, still make best-effort guesses based on what you see.
PROMPT;
    }

    /**
     * @param  array<string, mixed>|null  $payload
     */
    private function extractTextContent(?array $payload): string
    {
        $chunks = $payload['content'] ?? [];
        if (! is_array($chunks)) {
            return '';
        }

        $parts = [];
        foreach ($chunks as $chunk) {
            if (($chunk['type'] ?? null) === 'text' && isset($chunk['text'])) {
                $parts[] = (string) $chunk['text'];
            }
        }

        return trim(implode("\n", $parts));
    }

    /**
     * @return array<string, mixed>
     */
    private function parseJsonObject(string $text): array
    {
        $text = trim($text);
        if ($text === '') {
            return [];
        }

        $decoded = json_decode($text, true);
        if (is_array($decoded)) {
            return $decoded;
        }

        if (preg_match('/\{.*\}/s', $text, $matches)) {
            $decoded = json_decode($matches[0], true);
            if (is_array($decoded)) {
                return $decoded;
            }
        }

        Log::warning('Claude design analysis returned non-JSON', ['text' => mb_substr($text, 0, 500)]);

        return [];
    }

    /**
     * @param  array<string, mixed>  $parsed
     * @param  array<int, string>  $allowedStyles
     * @param  array<int, string>  $allowedPlacements
     * @param  array<int, string>  $fieldsNeeded
     * @return array<string, mixed>
     */
    private function normalizeSuggestions(
        array $parsed,
        array $allowedStyles,
        array $allowedPlacements,
        array $fieldsNeeded
    ): array {
        $out = [];
        $styleLookup = [];
        foreach ($allowedStyles as $style) {
            $styleLookup[mb_strtolower(trim($style))] = $style;
        }
        $placementLookup = [];
        foreach ($allowedPlacements as $placement) {
            $placementLookup[mb_strtolower(trim($placement))] = $placement;
        }

        if (in_array('title', $fieldsNeeded, true) && isset($parsed['title'])) {
            $title = trim((string) $parsed['title']);
            if ($title !== '') {
                $out['title'] = mb_substr($title, 0, 255);
            }
        }

        if (in_array('description', $fieldsNeeded, true) && isset($parsed['description'])) {
            $description = trim((string) $parsed['description']);
            if ($description !== '') {
                $out['description'] = mb_substr($description, 0, 255);
            }
        }

        $primary = null;
        if (in_array('primary_style', $fieldsNeeded, true) && isset($parsed['primary_style'])) {
            $primary = $this->matchCatalogValue((string) $parsed['primary_style'], $styleLookup);
            if ($primary !== null) {
                $out['primary_style'] = $primary;
            }
        }

        if (in_array('other_styles', $fieldsNeeded, true)) {
            $others = $parsed['other_styles'] ?? [];
            if (! is_array($others)) {
                $others = [];
            }
            $normalized = [];
            foreach ($others as $style) {
                $matched = $this->matchCatalogValue((string) $style, $styleLookup);
                if ($matched === null) {
                    continue;
                }
                if ($primary !== null && $matched === $primary) {
                    continue;
                }
                if (in_array($matched, $normalized, true)) {
                    continue;
                }
                $normalized[] = $matched;
                if (count($normalized) >= 2) {
                    break;
                }
            }
            $out['other_styles'] = $normalized;
        }

        if (in_array('suggested_placements', $fieldsNeeded, true)) {
            $placements = $parsed['suggested_placements'] ?? ($parsed['placements'] ?? []);
            if (! is_array($placements)) {
                $placements = [];
            }
            $normalizedPlacements = [];
            foreach ($placements as $placement) {
                $matched = $this->matchCatalogValue((string) $placement, $placementLookup);
                if ($matched === null) {
                    continue;
                }
                if (in_array($matched, $normalizedPlacements, true)) {
                    continue;
                }
                $normalizedPlacements[] = $matched;
                if (count($normalizedPlacements) >= 3) {
                    break;
                }
            }
            $out['suggested_placements'] = $normalizedPlacements;
        }

        if (in_array('color', $fieldsNeeded, true) && isset($parsed['color'])) {
            $color = $this->normalizeColor((string) $parsed['color']);
            if ($color !== null) {
                $out['color'] = $color;
            }
        }

        if (in_array('tags', $fieldsNeeded, true) && isset($parsed['tags'])) {
            $tags = $parsed['tags'];
            if (! is_array($tags)) {
                $tags = preg_split('/[,|]+/', (string) $tags) ?: [];
            }
            $normalizedTags = [];
            foreach ($tags as $tag) {
                $tag = trim(preg_replace('/\s+/', ' ', (string) $tag) ?? '');
                $tag = ltrim($tag, '#');
                if ($tag === '') {
                    continue;
                }
                $tag = mb_substr($tag, 0, 64);
                $key = mb_strtolower($tag);
                if (isset($normalizedTags[$key])) {
                    continue;
                }
                $normalizedTags[$key] = $tag;
                if (count($normalizedTags) >= 30) {
                    break;
                }
            }
            if ($normalizedTags !== []) {
                $out['tags'] = array_values($normalizedTags);
            }
        }

        return $out;
    }

    /**
     * @param  array<string, string>  $lookup
     */
    private function matchCatalogValue(string $value, array $lookup): ?string
    {
        $key = mb_strtolower(trim($value));
        if ($key === '') {
            return null;
        }

        return $lookup[$key] ?? null;
    }

    private function normalizeColor(string $value): ?string
    {
        $value = mb_strtolower(trim($value));
        $value = str_replace(['_', ' '], '-', $value);

        $aliases = [
            'color' => 'color',
            'coloured' => 'color',
            'colored' => 'color',
            'full-color' => 'color',
            'black-grey' => 'black-grey',
            'black-gray' => 'black-grey',
            'black-&-grey' => 'black-grey',
            'black-and-grey' => 'black-grey',
            'black-and-gray' => 'black-grey',
            'b&g' => 'black-grey',
            'monochrome' => 'black-grey',
            'both' => 'both',
            'mixed' => 'both',
        ];

        $normalized = $aliases[$value] ?? $value;

        return in_array($normalized, self::COLOR_OPTIONS, true) ? $normalized : null;
    }

    /**
     * @return array{0: string, 1: string}
     */
    private function normalizeImage(string $imageBinary, string $mediaType): array
    {
        $mediaType = strtolower(trim($mediaType));
        if (! in_array($mediaType, ['image/jpeg', 'image/png', 'image/webp', 'image/gif'], true)) {
            $mediaType = 'image/jpeg';
        }

        if (! function_exists('imagecreatefromstring')) {
            return [$imageBinary, $mediaType];
        }

        $src = @imagecreatefromstring($imageBinary);
        if ($src === false) {
            return [$imageBinary, $mediaType];
        }

        $width = imagesx($src);
        $height = imagesy($src);
        $maxSide = 1280;

        if ($width > $maxSide || $height > $maxSide) {
            $scale = min($maxSide / max(1, $width), $maxSide / max(1, $height));
            $newW = max(1, (int) round($width * $scale));
            $newH = max(1, (int) round($height * $scale));
            $dst = imagecreatetruecolor($newW, $newH);
            if ($dst !== false) {
                imagealphablending($dst, true);
                imagesavealpha($dst, true);
                imagecopyresampled($dst, $src, 0, 0, 0, 0, $newW, $newH, $width, $height);
                imagedestroy($src);
                $src = $dst;
            }
        }

        ob_start();
        imagejpeg($src, null, 85);
        $out = (string) ob_get_clean();
        imagedestroy($src);

        return [$out !== '' ? $out : $imageBinary, 'image/jpeg'];
    }
}
