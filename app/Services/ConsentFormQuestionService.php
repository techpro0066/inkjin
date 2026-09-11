<?php

namespace App\Services;

use App\Models\ConsentFormQuestion;
use App\Models\ConsentFormQuestionSorting;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class ConsentFormQuestionService
{
    /**
     * Ensure the artist has sorting rows for all active system consent questions.
     */
    public function syncForArtist(int $userId): void
    {
        if ($userId === ConsentFormQuestion::SYSTEM_USER_ID) {
            return;
        }

        $systemQuestions = ConsentFormQuestion::query()
            ->where('user_id', ConsentFormQuestion::SYSTEM_USER_ID)
            ->where('enabled', true)
            ->orderByRaw("CASE question_type WHEN 'health' THEN 1 WHEN 'risk' THEN 2 WHEN 'aftercare' THEN 3 ELSE 4 END")
            ->orderBy('id')
            ->get(['id']);

        if ($systemQuestions->isEmpty()) {
            return;
        }

        $existingIds = ConsentFormQuestionSorting::query()
            ->where('user_id', $userId)
            ->whereIn('consent_form_question_id', $systemQuestions->pluck('id'))
            ->pluck('consent_form_question_id')
            ->map(fn ($id) => (int) $id)
            ->all();

        $missing = $systemQuestions->filter(
            fn (ConsentFormQuestion $q) => ! in_array((int) $q->id, $existingIds, true)
        );

        if ($missing->isEmpty()) {
            return;
        }

        $nextOrder = (int) ConsentFormQuestionSorting::query()
            ->where('user_id', $userId)
            ->max('order');

        $now = now();
        $rows = [];
        foreach ($missing as $question) {
            $nextOrder++;
            $rows[] = [
                'user_id' => $userId,
                'consent_form_question_id' => (int) $question->id,
                'order' => $nextOrder,
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        ConsentFormQuestionSorting::query()->insert($rows);
    }

    /**
     * Artist-visible consent questions with per-artist order/active.
     *
     * @return Collection<int, array{id: int, question_type: string, translations: array<string, string>, enabled: bool, is_system: bool, order: int}>
     */
    public function listForArtist(int $userId): Collection
    {
        $this->syncForArtist($userId);

        $systemIds = ConsentFormQuestion::query()
            ->where('user_id', ConsentFormQuestion::SYSTEM_USER_ID)
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->all();

        $activeSystemIds = ConsentFormQuestion::query()
            ->where('user_id', ConsentFormQuestion::SYSTEM_USER_ID)
            ->where('enabled', true)
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->all();

        $sortings = ConsentFormQuestionSorting::query()
            ->where('user_id', $userId)
            ->with('question')
            ->orderBy('order')
            ->orderBy('id')
            ->get();

        return $sortings
            ->map(function (ConsentFormQuestionSorting $sorting) use ($systemIds, $activeSystemIds) {
                $question = $sorting->question;
                if (! $question) {
                    return null;
                }

                $isSystem = in_array((int) $question->id, $systemIds, true);
                if ($isSystem && ! in_array((int) $question->id, $activeSystemIds, true)) {
                    return null;
                }

                $translations = is_array($question->translations) ? $question->translations : [];

                return [
                    'id' => (int) $question->id,
                    'question_type' => (string) $question->question_type,
                    'translations' => $translations,
                    'enabled' => (bool) $sorting->is_active,
                    'is_system' => $isSystem,
                    'order' => (int) $sorting->order,
                ];
            })
            ->filter()
            ->values();
    }

    /**
     * @param  list<int>  $orderedIds
     */
    public function reorderForArtist(int $userId, array $orderedIds): void
    {
        DB::transaction(function () use ($userId, $orderedIds) {
            foreach (array_values($orderedIds) as $index => $questionId) {
                ConsentFormQuestionSorting::query()
                    ->where('user_id', $userId)
                    ->where('consent_form_question_id', (int) $questionId)
                    ->update(['order' => $index + 1]);
            }
        });
    }
}
