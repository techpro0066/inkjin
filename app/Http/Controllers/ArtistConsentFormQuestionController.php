<?php

namespace App\Http\Controllers;

use App\Models\ConsentFormQuestion;
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
            'enabled' => $validated['enabled'] ?? true,
            'order' => $this->consentQuestions->nextOrderForUser($userId),
        ]);

        return response()->json([
            'success' => true,
            'question' => $this->serializeArtistQuestion($question),
        ]);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $userId = (int) Auth::id();
        $question = ConsentFormQuestion::query()
            ->where('user_id', $userId)
            ->findOrFail($id);

        $validated = $this->validateQuestion($request);

        $payload = [
            'question_type' => $validated['question_type'],
            'translations' => $validated['translations'],
        ];
        if (array_key_exists('enabled', $validated)) {
            $payload['enabled'] = (bool) $validated['enabled'];
        }

        $question->update($payload);

        return response()->json([
            'success' => true,
            'question' => $this->serializeArtistQuestion($question->fresh()),
        ]);
    }

    public function updateStatus(Request $request, int $id): JsonResponse
    {
        $userId = (int) Auth::id();
        $validated = $request->validate([
            'enabled' => ['required', 'boolean'],
        ]);

        $question = ConsentFormQuestion::query()
            ->where('user_id', $userId)
            ->findOrFail($id);

        $question->update(['enabled' => $validated['enabled']]);

        return response()->json([
            'success' => true,
            'question' => $this->serializeArtistQuestion($question->fresh()),
        ]);
    }

    public function reorder(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'ordered_ids' => ['required', 'array', 'min:1'],
            'ordered_ids.*' => ['integer'],
        ]);

        $userId = (int) Auth::id();
        $ownedIds = ConsentFormQuestion::query()
            ->where('user_id', $userId)
            ->whereIn('id', $validated['ordered_ids'])
            ->pluck('id')
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
     * @return array{id: int, question_type: string, translations: array<string, string>, enabled: bool, is_system: bool, order: int}
     */
    private function serializeArtistQuestion(ConsentFormQuestion $question): array
    {
        return [
            'id' => (int) $question->id,
            'question_type' => (string) $question->question_type,
            'translations' => is_array($question->translations) ? $question->translations : [],
            'enabled' => (bool) $question->enabled,
            'is_system' => false,
            'order' => (int) $question->order,
        ];
    }
}
