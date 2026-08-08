<?php

namespace App\Services;

use App\Models\Portfolio;
use App\Models\Style;
use App\Models\User;
use App\Models\UserDetail;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class InstagramPortfolioImportService
{
    public const MAX_ITEMS = 30;

    public const SESSION_QUEUE_KEY = 'instagram_portfolio_import_queue';

    public function __construct(
        private readonly ClaudeDesignAnalysisService $claude,
    ) {}

    /**
     * Always fetches the latest 30 Instagram picture posts for the user.
     * Existing media ids are skipped; only new ones among those latest 30 are imported.
     *
     * @return array{imported: int, skipped: int, message: string}
     */
    public function importLatestForUser(User $user): array
    {
        $prepared = $this->prepareImportCandidates($user);
        if (($prepared['error'] ?? null) !== null) {
            return [
                'imported' => 0,
                'skipped' => 0,
                'message' => (string) $prepared['error'],
            ];
        }

        $candidates = $prepared['to_import'];
        $skipped = (int) $prepared['skipped'];

        if ($candidates === []) {
            return [
                'imported' => 0,
                'skipped' => $skipped,
                'message' => $skipped > 0
                    ? 'No new Instagram images were imported (already imported or unavailable).'
                    : 'No Instagram images were found to import.',
            ];
        }

        $styles = Style::active()->ordered()->pluck('name')->values()->all();
        $imported = 0;

        foreach ($candidates as $candidate) {
            try {
                $portfolio = $this->createPortfolioFromImage(
                    $user,
                    (string) $candidate['id'],
                    (string) ($candidate['media_type'] ?? 'IMAGE'),
                    (string) $candidate['media_url'],
                    (string) ($candidate['caption'] ?? ''),
                    $styles
                );

                if ($portfolio) {
                    $imported++;
                } else {
                    $skipped++;
                }
            } catch (\Throwable $e) {
                Log::warning('Instagram portfolio import item failed', [
                    'user_id' => $user->id,
                    'media_id' => $candidate['id'] ?? null,
                    'message' => $e->getMessage(),
                ]);
                $skipped++;
            }
        }

        if ($imported === 0) {
            return [
                'imported' => 0,
                'skipped' => $skipped,
                'message' => $skipped > 0
                    ? 'No new Instagram images were imported (already imported or unavailable).'
                    : 'No Instagram images were imported.',
            ];
        }

        return [
            'imported' => $imported,
            'skipped' => $skipped,
            'message' => $imported === 1
                ? '1 Instagram image was added to your portfolio.'
                : $imported.' Instagram images were added to your portfolio.',
        ];
    }

    /**
     * Fetch latest candidates, filter existing, and store the import queue in session.
     *
     * @return array{ok: bool, total: int, skipped: int, message: string, error?: string}
     */
    public function beginAjaxImport(User $user): array
    {
        $prepared = $this->prepareImportCandidates($user);
        if (($prepared['error'] ?? null) !== null) {
            session()->forget(self::SESSION_QUEUE_KEY);

            return [
                'ok' => false,
                'total' => 0,
                'skipped' => 0,
                'message' => (string) $prepared['error'],
                'error' => (string) $prepared['error'],
            ];
        }

        $toImport = $prepared['to_import'];
        $skipped = (int) $prepared['skipped'];
        $total = count($toImport);

        if ($total === 0) {
            session()->forget(self::SESSION_QUEUE_KEY);

            return [
                'ok' => true,
                'total' => 0,
                'skipped' => $skipped,
                'message' => $skipped > 0
                    ? 'No new Instagram images to import — latest posts are already in your portfolio.'
                    : 'No Instagram images were found to import.',
            ];
        }

        session([self::SESSION_QUEUE_KEY => [
            'user_id' => $user->id,
            'queue' => array_values($toImport),
            'total' => $total,
            'processed' => 0,
            'imported' => 0,
            'failed' => 0,
            'skipped' => $skipped,
        ]]);

        return [
            'ok' => true,
            'total' => $total,
            'skipped' => $skipped,
            'message' => 'Importing '.$total.' Instagram '.($total === 1 ? 'image' : 'images').'…',
        ];
    }

    /**
     * Import the next queued Instagram image for this user.
     *
     * @return array{
     *   ok: bool,
     *   done: bool,
     *   current: int,
     *   total: int,
     *   imported: int,
     *   failed: int,
     *   portfolio?: Portfolio,
     *   message: string,
     *   error?: string
     * }
     */
    public function importNextAjax(User $user): array
    {
        $state = session(self::SESSION_QUEUE_KEY);
        if (! is_array($state) || (int) ($state['user_id'] ?? 0) !== (int) $user->id) {
            return [
                'ok' => false,
                'done' => true,
                'current' => 0,
                'total' => 0,
                'imported' => 0,
                'failed' => 0,
                'message' => 'Import session expired. Please try Refresh again.',
                'error' => 'Import session expired. Please try Refresh again.',
            ];
        }

        $queue = is_array($state['queue'] ?? null) ? $state['queue'] : [];
        $total = (int) ($state['total'] ?? 0);
        $processed = (int) ($state['processed'] ?? 0);
        $imported = (int) ($state['imported'] ?? 0);
        $failed = (int) ($state['failed'] ?? 0);

        if ($queue === []) {
            session()->forget(self::SESSION_QUEUE_KEY);
            $message = $imported === 0
                ? 'No new Instagram images were imported.'
                : ($imported === 1
                    ? '1 Instagram image was added to your portfolio.'
                    : $imported.' Instagram images were added to your portfolio.');

            return [
                'ok' => true,
                'done' => true,
                'current' => $total,
                'total' => $total,
                'imported' => $imported,
                'failed' => $failed,
                'message' => $message,
            ];
        }

        $candidate = array_shift($queue);
        $processed++;
        $portfolio = null;
        $itemError = null;

        if (! is_array($candidate) || blank($candidate['id'] ?? null) || blank($candidate['media_url'] ?? null)) {
            $failed++;
            $itemError = 'Invalid Instagram media item.';
        } else {
            $styles = Style::active()->ordered()->pluck('name')->values()->all();
            try {
                $portfolio = $this->createPortfolioFromImage(
                    $user,
                    (string) $candidate['id'],
                    (string) ($candidate['media_type'] ?? 'IMAGE'),
                    (string) $candidate['media_url'],
                    (string) ($candidate['caption'] ?? ''),
                    $styles
                );
                if ($portfolio) {
                    $imported++;
                } else {
                    $failed++;
                    $itemError = 'Could not create portfolio item.';
                }
            } catch (\Throwable $e) {
                Log::warning('Instagram AJAX import item failed', [
                    'user_id' => $user->id,
                    'media_id' => $candidate['id'] ?? null,
                    'message' => $e->getMessage(),
                ]);
                $failed++;
                $itemError = 'Failed to import this image.';
            }
        }

        $done = $queue === [];
        $state['queue'] = $queue;
        $state['processed'] = $processed;
        $state['imported'] = $imported;
        $state['failed'] = $failed;

        if ($done) {
            session()->forget(self::SESSION_QUEUE_KEY);
        } else {
            session([self::SESSION_QUEUE_KEY => $state]);
        }

        $message = $done
            ? ($imported === 0
                ? 'No new Instagram images were imported.'
                : ($imported === 1
                    ? '1 Instagram image was added to your portfolio.'
                    : $imported.' Instagram images were added to your portfolio.'))
            : 'Importing image '.$processed.' of '.$total.'…';

        $result = [
            'ok' => $itemError === null,
            'done' => $done,
            'current' => $processed,
            'total' => $total,
            'imported' => $imported,
            'failed' => $failed,
            'message' => $message,
        ];

        if ($portfolio instanceof Portfolio) {
            $result['portfolio'] = $portfolio;
        }
        if ($itemError !== null) {
            $result['error'] = $itemError;
        }

        return $result;
    }

    /**
     * @return array{to_import: list<array{id: string, media_type: string, media_url: string, caption?: string}>, skipped: int, error?: string}
     */
    private function prepareImportCandidates(User $user): array
    {
        $detail = $user->userDetail;
        if (! $detail instanceof UserDetail || blank($detail->instagram_access_token)) {
            return [
                'to_import' => [],
                'skipped' => 0,
                'error' => 'Instagram is not connected.',
            ];
        }

        $token = (string) $detail->instagram_access_token;
        $candidates = $this->fetchLatestImageCandidates($token, self::MAX_ITEMS);

        if ($candidates === []) {
            return [
                'to_import' => [],
                'skipped' => 0,
            ];
        }

        $existingIds = Portfolio::query()
            ->where('user_id', $user->id)
            ->whereNotNull('instagram_media_id')
            ->pluck('instagram_media_id')
            ->all();
        $existingLookup = array_fill_keys($existingIds, true);

        $toImport = [];
        $skipped = 0;

        foreach ($candidates as $candidate) {
            $mediaId = (string) ($candidate['id'] ?? '');
            if ($mediaId === '' || blank($candidate['media_url'] ?? null)) {
                $skipped++;

                continue;
            }

            if (isset($existingLookup[$mediaId])) {
                $skipped++;

                continue;
            }

            $toImport[] = $candidate;
        }

        // Fetch is newest-first; import oldest → newest among this batch.
        $toImport = array_values(array_reverse($toImport));

        return [
            'to_import' => $toImport,
            'skipped' => $skipped,
        ];
    }

    /**
     * Latest picture posts only (IMAGE posts + first IMAGE child of CAROUSEL_ALBUM).
     * Skips VIDEO/REELS. Returns at most $limit candidates, newest first.
     *
     * @return list<array{id: string, media_type: string, media_url: string, caption?: string}>
     */
    private function fetchLatestImageCandidates(string $accessToken, int $limit): array
    {
        $limit = max(1, min(self::MAX_ITEMS, $limit));
        $out = [];
        $url = 'https://graph.instagram.com/v21.0/me/media';
        $params = [
            'fields' => 'id,caption,media_type,media_url,timestamp,children{id,media_type,media_url}',
            'limit' => 50,
            'access_token' => $accessToken,
        ];
        $pages = 0;

        while ($url !== null && count($out) < $limit && $pages < 5) {
            $pages++;
            $response = $pages === 1
                ? Http::timeout(30)->get($url, $params)
                : Http::timeout(30)->get($url);

            if (! $response->successful()) {
                Log::warning('Instagram media list failed', ['body' => $response->body()]);

                break;
            }

            $data = $response->json('data');
            if (! is_array($data)) {
                break;
            }

            foreach ($data as $item) {
                if (! is_array($item)) {
                    continue;
                }

                $candidate = $this->normalizeImageCandidate($item);
                if ($candidate === null) {
                    continue;
                }

                $out[] = $candidate;
                if (count($out) >= $limit) {
                    break 2;
                }
            }

            $next = $response->json('paging.next');
            $url = is_string($next) && $next !== '' ? $next : null;
        }

        return $out;
    }

    /**
     * @param  array<string, mixed>  $item
     * @return array{id: string, media_type: string, media_url: string, caption?: string}|null
     */
    private function normalizeImageCandidate(array $item): ?array
    {
        $type = strtoupper((string) ($item['media_type'] ?? ''));
        $id = (string) ($item['id'] ?? '');
        $caption = isset($item['caption']) ? (string) $item['caption'] : '';

        if ($type === 'IMAGE') {
            $url = (string) ($item['media_url'] ?? '');
            if ($id === '' || $url === '') {
                return null;
            }

            return [
                'id' => $id,
                'media_type' => 'IMAGE',
                'media_url' => $url,
                'caption' => $caption,
            ];
        }

        if ($type !== 'CAROUSEL_ALBUM') {
            return null;
        }

        $children = $item['children']['data'] ?? [];
        if (! is_array($children)) {
            return null;
        }

        foreach ($children as $child) {
            if (! is_array($child)) {
                continue;
            }
            if (strtoupper((string) ($child['media_type'] ?? '')) !== 'IMAGE') {
                continue;
            }

            $url = (string) ($child['media_url'] ?? '');
            $childId = (string) ($child['id'] ?? '');
            if ($url === '' || $childId === '') {
                continue;
            }

            return [
                'id' => $childId,
                'media_type' => 'IMAGE',
                'media_url' => $url,
                'caption' => $caption,
            ];
        }

        return null;
    }

    /**
     * @param  list<string>  $styles
     */
    private function createPortfolioFromImage(
        User $user,
        string $mediaId,
        string $mediaType,
        string $imageUrl,
        string $caption,
        array $styles
    ): ?Portfolio {
        $exists = Portfolio::query()
            ->where('user_id', $user->id)
            ->where('instagram_media_id', $mediaId)
            ->exists();
        if ($exists) {
            return null;
        }

        $download = Http::timeout(45)->withOptions(['allow_redirects' => true])->get($imageUrl);
        if (! $download->successful()) {
            throw new \RuntimeException('Failed to download Instagram image.');
        }

        $binary = $download->body();
        if ($binary === '') {
            throw new \RuntimeException('Downloaded Instagram image was empty.');
        }

        $contentType = $download->header('Content-Type');
        if (is_array($contentType)) {
            $contentType = $contentType[0] ?? 'image/jpeg';
        }
        $mime = strtolower(trim(explode(';', (string) ($contentType ?: 'image/jpeg'))[0]));
        if (! in_array($mime, ['image/jpeg', 'image/jpg', 'image/png', 'image/webp'], true)) {
            $mime = 'image/jpeg';
        }

        $suggestions = [];
        if ($this->claude->isConfigured()) {
            try {
                $suggestions = $this->claude->suggestFields(
                    $binary,
                    $mime,
                    $styles,
                    ['title', 'description', 'primary_style', 'other_styles', 'color', 'tags']
                );
            } catch (\Throwable $e) {
                Log::warning('Claude failed for Instagram import; using fallbacks', [
                    'user_id' => $user->id,
                    'message' => $e->getMessage(),
                ]);
            }
        }

        $title = trim((string) ($suggestions['title'] ?? ''));
        if ($title === '' && $caption !== '') {
            $title = Str::limit(preg_replace('/\s+/', ' ', $caption) ?? $caption, 80, '');
        }
        if ($title === '') {
            $title = 'Instagram work';
        }

        $primary = (string) ($suggestions['primary_style'] ?? '');
        if ($primary === '' || ! in_array($primary, $styles, true)) {
            $primary = $styles[0] ?? 'Tattoo';
        }

        $other = array_values(array_filter(
            is_array($suggestions['other_styles'] ?? null) ? $suggestions['other_styles'] : [],
            fn ($style) => is_string($style) && in_array($style, $styles, true) && $style !== $primary
        ));
        $other = array_slice(array_values(array_unique($other)), 0, 2);

        $color = (string) ($suggestions['color'] ?? 'both');
        if (! in_array($color, ['color', 'black-grey', 'both'], true)) {
            $color = 'both';
        }

        $tags = array_values(array_filter(array_map(
            fn ($tag) => is_string($tag) ? trim($tag) : '',
            is_array($suggestions['tags'] ?? null) ? $suggestions['tags'] : []
        )));
        $tags = array_slice(array_values(array_unique($tags)), 0, 30);

        $description = trim((string) ($suggestions['description'] ?? ''));
        if ($description === '' && $caption !== '') {
            $description = Str::limit($caption, 2000, '');
        }

        $ext = match ($mime) {
            'image/png' => 'png',
            'image/webp' => 'webp',
            default => 'jpg',
        };

        $destination = public_path('uploads/portfolios');
        if (! File::exists($destination)) {
            File::makeDirectory($destination, 0755, true);
        }

        $filename = time().'_'.uniqid('ig_', true).'.'.$ext;
        $relative = 'uploads/portfolios/'.$filename;
        File::put(public_path($relative), $binary);

        return Portfolio::create([
            'user_id' => $user->id,
            'title' => Str::limit($title, 255, ''),
            'description' => $description !== '' ? Str::limit($description, 2000, '') : null,
            'is_active' => true,
            'image' => $relative,
            'primary_style' => $primary,
            'other_styles' => $other,
            'color' => $color,
            'tags' => $tags,
            'instagram_media_id' => $mediaId,
            'instagram_media_type' => $mediaType,
        ]);
    }
}
