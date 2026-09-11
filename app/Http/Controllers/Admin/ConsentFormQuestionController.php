<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ConsentFormQuestion;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ConsentFormQuestionController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        $validated = $this->validateQuestion($request);

        $question = ConsentFormQuestion::query()->create([
            'user_id' => ConsentFormQuestion::SYSTEM_USER_ID,
            'question_type' => $validated['question_type'],
            'translations' => $validated['translations'],
            'enabled' => $validated['enabled'] ?? true,
        ]);

        return response()->json([
            'success' => true,
            'question' => $this->serialize($question),
        ]);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $question = ConsentFormQuestion::query()
            ->where('user_id', ConsentFormQuestion::SYSTEM_USER_ID)
            ->findOrFail($id);

        $validated = $this->validateQuestion($request);

        $question->update([
            'question_type' => $validated['question_type'],
            'translations' => $validated['translations'],
            'enabled' => $validated['enabled'] ?? $question->enabled,
        ]);

        return response()->json([
            'success' => true,
            'question' => $this->serialize($question->fresh()),
        ]);
    }

    public function destroy(int $id): JsonResponse
    {
        $question = ConsentFormQuestion::query()
            ->where('user_id', ConsentFormQuestion::SYSTEM_USER_ID)
            ->findOrFail($id);

        $question->delete();

        return response()->json(['success' => true]);
    }

    public function updateStatus(Request $request, int $id): JsonResponse
    {
        $question = ConsentFormQuestion::query()
            ->where('user_id', ConsentFormQuestion::SYSTEM_USER_ID)
            ->findOrFail($id);

        $validated = $request->validate([
            'enabled' => ['required', 'boolean'],
        ]);

        $question->update([
            'enabled' => $validated['enabled'],
        ]);

        return response()->json([
            'success' => true,
            'question' => $this->serialize($question->fresh()),
        ]);
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
        // (e.g. translations.en), which would drop other-language translations.
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
     * @return array{id: int, question_type: string, translations: array<string, string>, enabled: bool}
     */
    private function serialize(ConsentFormQuestion $question): array
    {
        return [
            'id' => $question->id,
            'question_type' => $question->question_type,
            'translations' => is_array($question->translations) ? $question->translations : [],
            'enabled' => (bool) $question->enabled,
        ];
    }
}
