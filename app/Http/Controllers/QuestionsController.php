<?php

namespace App\Http\Controllers;

use App\Models\Question;
use App\Models\QuestionSorting;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class QuestionsController extends Controller
{
    /**
     * Artist: list own questions (legacy shape for DataTables view).
     */
    public function index()
    {
        $userId = Auth::id();

        $systemSortingRows = QuestionSorting::query()
            ->where('user_id', 1)
            ->where('is_active', true)
            ->orderBy('order')
            ->get(['question_id', 'order']);

        $systemQuestionIds = $systemSortingRows
            ->pluck('question_id')
            ->map(fn ($id) => (int) $id)
            ->values()
            ->all();

        if (! empty($systemQuestionIds)) {
            $existingSystemIdsForUser = QuestionSorting::query()
                ->where('user_id', $userId)
                ->whereIn('question_id', $systemQuestionIds)
                ->pluck('question_id')
                ->map(fn ($id) => (int) $id)
                ->all();

            $missingSystemRows = $systemSortingRows->filter(function ($row) use ($existingSystemIdsForUser) {
                return ! in_array((int) $row->question_id, $existingSystemIdsForUser, true);
            });

            if ($missingSystemRows->isNotEmpty()) {
                $now = now();
                QuestionSorting::insert(
                    $missingSystemRows->map(function ($row) use ($userId, $now) {
                        return [
                            'user_id' => $userId,
                            'question_id' => (int) $row->question_id,
                            'order' => (int) $row->order,
                            'is_active' => true,
                            'created_at' => $now,
                            'updated_at' => $now,
                        ];
                    })->values()->all()
                );
            }
        }

        $active_system_question_ids = $systemQuestionIds;

        $system_questions_ids = QuestionSorting::query()
            ->where('user_id', 1)
            ->pluck('question_id')
            ->map(fn ($id) => (int) $id)
            ->values()
            ->all();

        $userSortingRows = QuestionSorting::query()
            ->where('user_id', $userId)
            ->orderBy('order')
            ->get(['question_id', 'order', 'is_active']);

        $questionMap = Question::query()
            ->whereIn('id', $userSortingRows->pluck('question_id'))
            ->get()
            ->keyBy('id');

        $orderedQuestions = $userSortingRows
            ->map(function ($sortingRow) use ($questionMap, $system_questions_ids, $active_system_question_ids) {
                $question = $questionMap->get((int) $sortingRow->question_id);
                if (! $question) {
                    return null;
                }

                $isSystemQuestion = in_array((int) $question->id, $system_questions_ids, true);
                $isActiveAtSystemLevel = in_array((int) $question->id, $active_system_question_ids, true);
                if ($isSystemQuestion && ! $isActiveAtSystemLevel) {
                    return null;
                }

                $question->setAttribute('order', (int) $sortingRow->order);
                $question->setAttribute('is_active', (bool) $sortingRow->is_active);

                return $question;
            })
            ->filter()
            ->values();

        $default_questions = $orderedQuestions
            ->where('form_context', 'default')
            ->values();

        $custom_questions = $orderedQuestions
            ->where('form_context', 'custom')
            ->values();

        return view('artist.forms.index', [
            'default_questions' => $default_questions,
            'custom_questions' => $custom_questions,
            'system_questions_ids' => $system_questions_ids,
        ]);
    }

    /**
     * Create a question (artist booking questions or admin form templates).
     */
    public function store(Request $request): JsonResponse
    {
        $user = $request->user();

        $rawType = (string) $request->input('type', '');
        $normalizedType = match ($rawType) {
            'free', 'text' => 'input',
            'images' => 'image',
            default => $rawType,
        };

        $formContext = $request->input('form_context');
        if ($formContext === null || $formContext === '') {
            $formContext = $user->role === 'artist' ? 'custom' : 'default';
        }

        $isActive = $request->has('is_active')
            ? $request->boolean('is_active')
            : ($request->input('status') === 'active' || $request->input('status') === null);

        $isRequired = $request->has('is_required')
            ? $request->boolean('is_required')
            : true;

        $payload = [
            'question' => $request->input('question'),
            'description' => $request->input('description'),
            'placeholder' => $request->input('placeholder'),
            'type' => $normalizedType,
            'form_context' => $formContext,
            'is_active' => $isActive,
            'is_required' => $isRequired,
            'options' => $request->input('options'),
            'max_images' => $request->input('max_images'),
        ];

        $validator = Validator::make($payload, [
            'question' => ['required', 'string', 'max:10000'],
            'description' => ['nullable', 'string', 'max:10000'],
            'placeholder' => ['nullable', 'string', 'max:10000'],
            'type' => ['required', Rule::in(['input', 'textarea', 'toggle', 'select', 'image', 'radio'])],
            'form_context' => ['required', 'string', 'max:255'],
            'is_active' => ['required', 'boolean'],
            'is_required' => ['required', 'boolean'],
            'options' => ['nullable', 'array'],
            'options.*' => ['nullable', 'string', 'max:500'],
            'max_images' => ['nullable', 'integer', 'min:1', 'max:255'],
        ]);

        $validator->after(function ($validator) use ($normalizedType, $request) {
            if (! in_array($normalizedType, ['select', 'radio'], true)) {
                return;
            }

            $rawOptions = $request->input('options', []);
            if (! is_array($rawOptions)) {
                $rawOptions = [];
            }

            // First two option inputs are required for select/radio.
            for ($i = 0; $i < 2; $i++) {
                $value = $rawOptions[$i] ?? null;
                if (! is_string($value) || trim($value) === '') {
                    $validator->errors()->add("options.$i", 'This field is required.');
                }
            }

            // Any additional option row that user keeps is also required.
            foreach ($rawOptions as $idx => $value) {
                if ($idx < 2) {
                    continue;
                }
                if (! is_string($value) || trim($value) === '') {
                    $validator->errors()->add("options.$idx", 'This field is required.');
                }
            }
        });

        $data = $validator->validate();

        $options = null;
        if (in_array($data['type'], ['select', 'radio'], true)) {
            $opts = [];
            foreach (($data['options'] ?? []) as $value) {
                if (is_string($value)) {
                    $opts[] = trim($value);
                }
            }
            $options = $opts;
        }

        $maxImages = $data['type'] === 'image'
            ? ($data['max_images'] ?? 5)
            : null;

        $question = DB::transaction(function () use ($data, $options, $maxImages) {
            $question = Question::create([
                'user_id' => Auth::id(),
                'question' => $data['question'],
                'description' => $data['description'] ?? null,
                'placeholder' => $data['placeholder'] ?? null,
                'type' => $data['type'],
                'form_context' => $data['form_context'],
                'options' => $options,
                'max_images' => $maxImages,
                'is_required' => $data['is_required'],
            ]);

            QuestionSorting::create([
                'user_id' => Auth::id(),
                'question_id' => $question->id,
                'order' => $question->id,
                'is_active' => $data['is_active'],
            ]);

            return $question;
        });

        return response()->json([
            'success' => true,
            'message' => 'Question saved.',
            'question' => $question->fresh()->load('sorting'),
        ], 201);
    }

    public function update(Request $request, string $id): JsonResponse
    {
        $question = Question::query()
            ->where('user_id', Auth::id())
            ->whereKey($id)
            ->firstOrFail();

        $rawType = (string) $request->input('type', '');
        $normalizedType = match ($rawType) {
            'free', 'text' => 'input',
            'images' => 'image',
            default => $rawType,
        };

        $payload = [
            'question' => $request->input('question'),
            'description' => $request->input('description'),
            'placeholder' => $request->input('placeholder'),
            'type' => $normalizedType,
            'form_context' => $request->input('form_context', $question->form_context),
            'is_active' => $request->has('is_active')
                ? $request->boolean('is_active')
                : $question->is_active,
            'is_required' => $request->has('is_required')
                ? $request->boolean('is_required')
                : $question->is_required,
            'options' => $request->input('options'),
            'max_images' => $request->input('max_images'),
        ];

        $validator = Validator::make($payload, [
            'question' => ['required', 'string', 'max:10000'],
            'description' => ['nullable', 'string', 'max:10000'],
            'placeholder' => ['nullable', 'string', 'max:10000'],
            'type' => ['required', Rule::in(['input', 'textarea', 'toggle', 'select', 'image', 'radio'])],
            'form_context' => ['required', 'string', 'max:255'],
            'is_active' => ['required', 'boolean'],
            'is_required' => ['required', 'boolean'],
            'options' => ['nullable', 'array'],
            'options.*' => ['nullable', 'string', 'max:500'],
            'max_images' => ['nullable', 'integer', 'min:1', 'max:255'],
        ]);

        $validator->after(function ($validator) use ($normalizedType, $request) {
            if (! in_array($normalizedType, ['select', 'radio'], true)) {
                return;
            }

            $rawOptions = $request->input('options', []);
            if (! is_array($rawOptions)) {
                $rawOptions = [];
            }

            for ($i = 0; $i < 2; $i++) {
                $value = $rawOptions[$i] ?? null;
                if (! is_string($value) || trim($value) === '') {
                    $validator->errors()->add("options.$i", 'This field is required.');
                }
            }

            foreach ($rawOptions as $idx => $value) {
                if ($idx < 2) {
                    continue;
                }
                if (! is_string($value) || trim($value) === '') {
                    $validator->errors()->add("options.$idx", 'This field is required.');
                }
            }
        });

        $data = $validator->validate();

        $options = null;
        if (in_array($data['type'], ['select', 'radio'], true)) {
            $opts = [];
            foreach (($data['options'] ?? []) as $value) {
                if (is_string($value)) {
                    $opts[] = trim($value);
                }
            }
            $options = $opts;
        }

        $maxImages = $data['type'] === 'image'
            ? ($data['max_images'] ?? 5)
            : null;

        DB::transaction(function () use ($question, $data, $options, $maxImages) {
            $question->update([
                'question' => $data['question'],
                'description' => $data['description'] ?? null,
                'placeholder' => $data['placeholder'] ?? null,
                'type' => $data['type'],
                'form_context' => $data['form_context'],
                'options' => $options,
                'max_images' => $maxImages,
                'is_required' => $data['is_required'],
            ]);

            QuestionSorting::updateOrCreate(
                ['user_id' => Auth::id(), 'question_id' => $question->id],
                [
                    'order' => optional($question->sorting)->order ?? $question->id,
                    'is_active' => $data['is_active'],
                ]
            );
        });

        return response()->json([
            'success' => true,
            'message' => 'Question updated.',
            'question' => $question->fresh()->load('sorting'),
        ]);
    }

    public function destroy(Request $request, string $id): JsonResponse
    {
        $question = Question::query()
            ->where('user_id', Auth::id())
            ->whereKey($id)
            ->firstOrFail();

        $question->delete();

        return response()->json([
            'success' => true,
            'message' => 'Question deleted.',
        ]);
    }

    public function updateSystemQuestionStatus(Request $request, string $id): JsonResponse
    {
        $data = $request->validate([
            'is_active' => ['required', 'boolean'],
        ]);

        $question = Question::query()->whereKey($id)->firstOrFail();

        $isSystemQuestion = QuestionSorting::query()
            ->where('user_id', 1)
            ->where('question_id', $question->id)
            ->exists();

        if (! $isSystemQuestion) {
            return response()->json([
                'success' => false,
                'message' => 'Question is not a system question.',
            ], 422);
        }

        $userId = Auth::id();
        $existingUserOrder = QuestionSorting::query()
            ->where('user_id', $userId)
            ->where('question_id', $question->id)
            ->value('order');

        $systemOrder = QuestionSorting::query()
            ->where('user_id', 1)
            ->where('question_id', $question->id)
            ->value('order');

        QuestionSorting::updateOrCreate(
            ['user_id' => $userId, 'question_id' => $question->id],
            [
                'order' => $existingUserOrder ?? $systemOrder ?? $question->id,
                'is_active' => $data['is_active'],
            ]
        );

        return response()->json([
            'success' => true,
            'message' => 'Question status updated.',
        ]);
    }

    /**
     * Persist question order after drag-drop sorting.
     */
    public function reorder(Request $request): JsonResponse
    {
        $data = $request->validate([
            'form_context' => ['required', 'string', 'max:255'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.id' => ['required', 'integer', 'distinct'],
            'items.*.order' => ['required', 'integer', 'min:1'],
        ]);

        $userId = Auth::id();
        $items = collect($data['items']);
        $ids = $items->pluck('id')->all();

        $userSortingCount = QuestionSorting::query()
            ->where('user_id', $userId)
            ->whereIn('question_id', $ids)
            ->count();

        $contextMatchCount = Question::query()
            ->whereIn('id', $ids)
            ->where('form_context', $data['form_context'])
            ->count();

        if ($userSortingCount !== count($ids) || $contextMatchCount !== count($ids)) {
            return response()->json([
                'success' => false,
                'message' => 'Some questions were not found for this form.',
            ], 422);
        }

        DB::transaction(function () use ($items, $userId, $data) {
            foreach ($items as $item) {
                $question = Question::query()
                    ->where('form_context', $data['form_context'])
                    ->whereKey($item['id'])
                    ->first();

                if (! $question) {
                    continue;
                }

                QuestionSorting::updateOrCreate(
                    ['user_id' => $userId, 'question_id' => $question->id],
                    [
                        'order' => $item['order'],
                        'is_active' => QuestionSorting::query()
                            ->where('user_id', $userId)
                            ->where('question_id', $question->id)
                            ->value('is_active') ?? true,
                    ]
                );
            }
        });

        return response()->json([
            'success' => true,
            'message' => 'Question order updated.',
        ]);
    }
}
