<?php

namespace App\Services;

use App\Models\ConsentFormQuestion;
use App\Models\User;
use App\Models\UserDetail;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ConsentFormQuestionService
{
    /**
     * Copy enabled admin/system templates into the artist's own questions (once).
     */
    public function seedForArtist(int $userId): bool
    {
        if ($userId === ConsentFormQuestion::SYSTEM_USER_ID) {
            return false;
        }

        $detail = UserDetail::query()->firstOrCreate(['user_id' => $userId]);
        if ($detail->consent_questions_seeded_at) {
            return false;
        }

        // Already has owned questions (legacy / partial) — mark seeded, don't re-copy.
        if (ConsentFormQuestion::query()->where('user_id', $userId)->exists()) {
            $detail->forceFill(['consent_questions_seeded_at' => now()])->save();

            return false;
        }

        $templates = ConsentFormQuestion::query()
            ->where('user_id', ConsentFormQuestion::SYSTEM_USER_ID)
            ->where('enabled', true)
            ->orderBy('order')
            ->orderBy('id')
            ->get();

        DB::transaction(function () use ($userId, $templates, $detail) {
            $order = 0;
            foreach ($templates as $template) {
                $order++;
                ConsentFormQuestion::query()->create([
                    'user_id' => $userId,
                    'question_type' => $template->question_type,
                    'translations' => is_array($template->translations) ? $template->translations : [],
                    'enabled' => true,
                    'order' => $order,
                ]);
            }

            $detail->forceFill(['consent_questions_seeded_at' => now()])->save();
        });

        return true;
    }

    /**
     * Seed every artist who has not received consent questions yet.
     */
    public function seedForAllArtistsMissing(): int
    {
        $artistIds = User::query()
            ->where('role', 'artist')
            ->where('id', '!=', ConsentFormQuestion::SYSTEM_USER_ID)
            ->pluck('id');

        $seeded = 0;
        foreach ($artistIds as $artistId) {
            try {
                if ($this->seedForArtist((int) $artistId)) {
                    $seeded++;
                }
            } catch (\Throwable $e) {
                Log::error('Failed to seed consent questions for artist', [
                    'user_id' => $artistId,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return $seeded;
    }

    /**
     * Artist-owned consent questions only.
     *
     * @return Collection<int, array{id: int, question_type: string, translations: array<string, string>, enabled: bool, is_system: bool, order: int}>
     */
    public function listForArtist(int $userId): Collection
    {
        $this->seedForArtist($userId);

        return ConsentFormQuestion::query()
            ->where('user_id', $userId)
            ->orderBy('order')
            ->orderBy('id')
            ->get()
            ->map(function (ConsentFormQuestion $question) {
                return [
                    'id' => (int) $question->id,
                    'question_type' => (string) $question->question_type,
                    'translations' => is_array($question->translations) ? $question->translations : [],
                    'enabled' => (bool) $question->enabled,
                    'is_system' => false,
                    'order' => (int) $question->order,
                ];
            })
            ->values();
    }

    /**
     * Replace the artist's question set to match the published draft list.
     *
     * @param  list<array{id?: int|string|null, question_type: string, translations: array<string, string>, enabled?: bool}>  $questions
     * @return Collection<int, array{id: int, question_type: string, translations: array<string, string>, enabled: bool, is_system: bool, order: int}>
     */
    public function syncForArtist(int $userId, array $questions): Collection
    {
        if ($userId === ConsentFormQuestion::SYSTEM_USER_ID) {
            return collect();
        }

        DB::transaction(function () use ($userId, $questions) {
            $existing = ConsentFormQuestion::query()
                ->where('user_id', $userId)
                ->get()
                ->keyBy('id');

            $keptIds = [];
            $order = 0;

            foreach (array_values($questions) as $item) {
                $order++;
                $type = (string) ($item['question_type'] ?? 'health');
                if (! in_array($type, ['health', 'risk', 'aftercare'], true)) {
                    $type = 'health';
                }

                $translations = $this->normalizeTranslations($item['translations'] ?? []);
                $enabled = array_key_exists('enabled', $item) ? (bool) $item['enabled'] : true;
                $rawId = $item['id'] ?? null;
                $numericId = is_numeric($rawId) ? (int) $rawId : null;

                if ($numericId && $existing->has($numericId)) {
                    /** @var ConsentFormQuestion $question */
                    $question = $existing->get($numericId);
                    $question->update([
                        'question_type' => $type,
                        'translations' => $translations,
                        'enabled' => $enabled,
                        'order' => $order,
                    ]);
                    $keptIds[] = $numericId;
                    continue;
                }

                $created = ConsentFormQuestion::query()->create([
                    'user_id' => $userId,
                    'question_type' => $type,
                    'translations' => $translations,
                    'enabled' => $enabled,
                    'order' => $order,
                ]);
                $keptIds[] = (int) $created->id;
            }

            ConsentFormQuestion::query()
                ->where('user_id', $userId)
                ->when(
                    count($keptIds) > 0,
                    fn ($q) => $q->whereNotIn('id', $keptIds),
                    fn ($q) => $q
                )
                ->delete();

            $detail = UserDetail::query()->firstOrCreate(['user_id' => $userId]);
            if (! $detail->consent_questions_seeded_at) {
                $detail->forceFill(['consent_questions_seeded_at' => now()])->save();
            }
        });

        return $this->listForArtist($userId);
    }

    /**
     * @param  mixed  $translations
     * @return array<string, string>
     */
    public function normalizeTranslations(mixed $translations): array
    {
        $normalized = [];
        foreach ((array) $translations as $locale => $text) {
            $locale = strtolower(trim((string) $locale));
            $text = trim((string) $text);
            if ($locale === '' || $text === '') {
                continue;
            }
            if (! preg_match('/^[a-z]{2}(-[a-z]{2})?$/', $locale)) {
                continue;
            }
            $normalized[$locale] = $text;
        }

        return $normalized;
    }

    /**
     * @param  list<int>  $orderedIds
     */
    public function reorderForArtist(int $userId, array $orderedIds): void
    {
        DB::transaction(function () use ($userId, $orderedIds) {
            foreach (array_values($orderedIds) as $index => $questionId) {
                ConsentFormQuestion::query()
                    ->where('user_id', $userId)
                    ->where('id', (int) $questionId)
                    ->update(['order' => $index + 1]);
            }
        });
    }

    public function nextOrderForUser(int $userId): int
    {
        return ((int) ConsentFormQuestion::query()->where('user_id', $userId)->max('order')) + 1;
    }
}
