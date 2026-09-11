<?php

namespace App\Http\Controllers;

use App\Models\ConsentFormQuestion;
use App\Models\ConsentFormQuestionSorting;
use App\Services\ConsentFormQuestionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class ArtistConsentFormQuestionController extends Controller
{
    public function __construct(
        private ConsentFormQuestionService $consentQuestions
    ) {}

    public function store(Request $request): JsonResponse
    {
        $userId = (int) Auth::id();
        $validated = $this->validateQuestion($request);

        $question = ConsentFormQuestion::query()->create([
            'user_id' => $userId,
            'question_type' => $validated['question_type'],
            'translations' => $validated['translations'],
            'enabled' => true,
        ]);

        $nextOrder = (int) ConsentFormQuestionSorting::query()
            ->where('user_id', $userId)
            ->max('order');

        ConsentFormQuestionSorting::query()->create([
            'user_id' => $userId,
            'consent_form_question_id' => $question->id,
            'order' => $nextOrder + 1,
            'is_active' => $validated['enabled'] ?? true,
        ]);

        return response()->json([
            'success' => true,
            'question' => $this->serializeArtistQuestion($question, $validated['enabled'] ?? true, false),
        ]);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $userId = (int) Auth::id();
        $question = ConsentFormQuestion::query()
            ->where('user_id', $userId)
            ->findOrFail($id);

        $validated = $this->validateQuestion($request);

        $question->update([
            'question_type' => $validated['question_type'],
            'translations' => $validated['translations'],
        ]);

        $sorting = ConsentFormQuestionSorting::query()
            ->where('user_id', $userId)
            ->where('consent_form_question_id', $question->id)
            ->first();

        if ($sorting && array_key_exists('enabled', $validated)) {
            $sorting->update(['is_active' => (bool) $validated['enabled']]);
        }

        return response()->json([
            'success' => true,
            'question' => $this->serializeArtistQuestion(
                $question->fresh(),
                $sorting ? (bool) $sorting->is_active : true,
                false
            ),
        ]);
    }

    public function updateStatus(Request $request, int $id): JsonResponse
    {
        $userId = (int) Auth::id();
        $validated = $request->validate([
            'enabled' => ['required', 'boolean'],
        ]);

        $sorting = ConsentFormQuestionSorting::query()
            ->where('user_id', $userId)
            ->where('consent_form_question_id', $id)
            ->firstOrFail();

        $question = $sorting->question;
        if (! $question) {
            abort(404);
        }

        // System questions disabled by admin are not toggleable.
        if ($question->isSystemQuestion() && ! $question->enabled) {
            return response()->json([
                'success' => false,
                'message' => 'This system question is disabled by admin.',
            ], 422);
        }

        $sorting->update(['is_active' => $validated['enabled']]);

        return response()->json([
            'success' => true,
            'question' => $this->serializeArtistQuestion(
                $question,
                (bool) $sorting->is_active,
                $question->isSystemQuestion()
            ),
        ]);
    }

    public function reorder(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'ordered_ids' => ['required', 'array', 'min:1'],
            'ordered_ids.*' => ['integer'],
        ]);

        $userId = (int) Auth::id();
        $ownedIds = ConsentFormQuestionSorting::query()
            ->where('user_id', $userId)
            ->whereIn('consent_form_question_id', $validated['ordered_ids'])
            ->pluck('consent_form_question_id')
            ->map(fn ($id) => (int) $id)
            ->all();

        $orderedIds = array_values(array_filter(
            array_map('intval', $validated['ordered_ids']),
            fn (int $id) => in_array($id, $ownedIds, true)
        ));

        $this->consentQuestions->reorderForArtist($userId, $orderedIds);

        return response()->json(['success' => true]);
    }

    public function destroy(int $id): JsonResponse
    {
        $userId = (int) Auth::id();
        $question = ConsentFormQuestion::query()
            ->where('user_id', $userId)
            ->findOrFail($id);

        ConsentFormQuestionSorting::query()
            ->where('user_id', $userId)
            ->where('consent_form_question_id', $question->id)
            ->delete();

        $question->delete();

        return response()->json(['success' => true]);
    }

    /**
     * @return array{question_type: string, translations: array<string, string>, enabled?: bool}
     */
    private function validateQuestion(Request $request): array
    {
        $request->validate([
            'question_type' => ['required', Rule::in(['health', 'risk', 'aftercare'])],
            'translations' => ['required', 'array'],
            'translations.en' => ['required', 'string'],
            'enabled' => ['sometimes', 'boolean'],
        ]);

        // Use raw input for locales — validated() only keeps keys with explicit rules
        // (e.g. translations.en), which would drop studio-language translations.
        $translations = [];
        foreach ((array) $request->input('translations', []) as $locale => $text) {
            $locale = strtolower(trim((string) $locale));
            $text = trim((string) $text);
            if ($locale === '' || $text === '') {
                continue;
            }
            if (! preg_match('/^[a-z]{2}(-[a-z]{2})?$/', $locale)) {
                continue;
            }
            $translations[$locale] = $text;
        }

        if (! isset($translations['en']) || $translations['en'] === '') {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'translations.en' => ['English question is required.'],
            ]);
        }

        $result = [
            'question_type' => (string) $request->input('question_type'),
            'translations' => $translations,
        ];

        if ($request->exists('enabled')) {
            $result['enabled'] = $request->boolean('enabled');
        }

        return $result;
    }

    /**
     * @param  array<string, string>  $translations
     * @return array{id: int, question_type: string, translations: array<string, string>, enabled: bool, is_system: bool}
     */
    private function serializeArtistQuestion(ConsentFormQuestion $question, bool $enabled, bool $isSystem): array
    {
        return [
            'id' => (int) $question->id,
            'question_type' => (string) $question->question_type,
            'translations' => is_array($question->translations) ? $question->translations : [],
            'enabled' => $enabled,
            'is_system' => $isSystem,
        ];
    }
}
