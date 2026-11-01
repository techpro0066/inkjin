<?php

namespace App\Http\Controllers\Studio;

use App\Http\Controllers\Controller;
use App\Models\ConsentFormSetting;
use App\Models\Question;
use App\Models\QuestionSorting;
use App\Models\Studio;
use App\Services\ConsentFormQuestionService;
use App\Support\StripeConnectCountries;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ClientFlowController extends Controller
{
    /** Same context as artist Custom request — system templates live here. */
    public const FORM_CONTEXT = 'custom';

    public function __construct(
        private ConsentFormQuestionService $consentQuestions
    ) {}

    public function intake(Request $request): View
    {
        $user = $request->user();
        $userId = (int) $user->id;

        $this->cleanupLegacyStudioClones($userId);
        $this->ensureSystemCustomQuestionsLinked($userId);

        $studio = Studio::query()->where('user_id', $userId)->first();
        $studioName = trim((string) ($studio?->name ?? ''));
        if ($studioName === '') {
            $studioName = trim(($user->first_name ?? '').' '.($user->last_name ?? ''));
        }
        if ($studioName === '') {
            $studioName = 'your studio';
        }

        [$questions, $systemQuestionIds] = $this->studioQuestionsForUser($userId);

        return view('studio.client-flow.intake', [
            'studio' => $studio,
            'studioName' => $studioName,
            'studioEmail' => $studio?->email ?? $user->email,
            'studioInitials' => strtoupper(substr(preg_replace('/\s+/', '', $studioName) ?: 'ST', 0, 2)),
            'ownerAvatarUrl' => ($imagePath = trim((string) ($studio?->image_url ?? $studio?->logo_url ?? ''))) !== ''
                ? asset(ltrim($imagePath, '/'))
                : null,
            'profileComplete' => (string) ($user->on_boarding ?? '') === 'yes',
            'activeNav' => 'client-flow',
            'clientFlowTab' => 'intake',
            'questions' => $questions,
            'system_questions_ids' => $systemQuestionIds,
        ]);
    }

    public function consent(Request $request): View
    {
        $user = $request->user();
        $userId = (int) $user->id;
        $studio = Studio::query()->where('user_id', $userId)->firstOrFail();

        $studioName = trim((string) ($studio->name ?? ''));
        if ($studioName === '') {
            $studioName = trim(($user->first_name ?? '').' '.($user->last_name ?? ''));
        }
        if ($studioName === '') {
            $studioName = 'your studio';
        }

        $defaultMarketCountry = $this->defaultMarketForStudio($studio, $user);
        $settings = $this->settingsForOwner($userId, $defaultMarketCountry);
        $consentQuestions = $this->consentQuestions->listForArtist($userId);

        return view('studio.client-flow.consent', [
            'studio' => $studio,
            'studioName' => $studioName,
            'studioEmail' => $studio->email ?? $user->email,
            'studioInitials' => strtoupper(substr(preg_replace('/\s+/', '', $studioName) ?: 'ST', 0, 2)),
            'ownerAvatarUrl' => ($imagePath = trim((string) ($studio->image_url ?? $studio->logo_url ?? ''))) !== ''
                ? asset(ltrim($imagePath, '/'))
                : null,
            'profileComplete' => (string) ($user->on_boarding ?? '') === 'yes',
            'activeNav' => 'client-flow',
            'clientFlowTab' => 'consent',
            'useStudioConsentForm' => (bool) $studio->use_studio_consent_form,
            'allowArtistOwnConsent' => (bool) $studio->allow_artist_own_consent,
            'registrationCountries' => StripeConnectCountries::registrationCountriesForSelect(),
            'defaultMarketCountry' => $settings->studio_market ?: $defaultMarketCountry,
            'consentSettings' => $settings->toArtistArray(),
            'consentQuestions' => $consentQuestions,
            'consentQuestionsByType' => [
                'health' => $consentQuestions->where('question_type', 'health')->values(),
                'risk' => $consentQuestions->where('question_type', 'risk')->values(),
                'aftercare' => $consentQuestions->where('question_type', 'aftercare')->values(),
            ],
        ]);
    }

    public function updateConsentPolicy(Request $request): JsonResponse
    {
        $user = $request->user();
        $studio = Studio::query()->where('user_id', (int) $user->id)->firstOrFail();

        $validated = $request->validate([
            'use_studio_consent_form' => ['required', 'boolean'],
            'allow_artist_own_consent' => ['required', 'boolean'],
        ]);

        $studio->forceFill([
            'use_studio_consent_form' => $validated['use_studio_consent_form'],
            'allow_artist_own_consent' => $validated['allow_artist_own_consent'],
        ])->save();

        return response()->json([
            'success' => true,
            'message' => 'Consent policy saved.',
            'use_studio_consent_form' => (bool) $studio->use_studio_consent_form,
            'allow_artist_own_consent' => (bool) $studio->allow_artist_own_consent,
        ]);
    }

    public function updateConsentSettings(Request $request): JsonResponse
    {
        $userId = (int) $request->user()->id;
        $studio = Studio::query()->where('user_id', $userId)->firstOrFail();

        $marketCodes = collect(StripeConnectCountries::registrationCountriesForSelect())
            ->pluck('code')
            ->map(fn ($code) => strtoupper((string) $code))
            ->all();

        $request->validate([
            'studio_market' => ['required', 'string', 'size:2', Rule::in($marketCodes)],
            'registration_number' => ['nullable', 'string', 'max:255'],
            'allow_younger' => ['required', 'boolean'],
            'age_allow' => ['nullable', 'integer', 'min:1', 'max:17'],
            'language_mode' => ['required', Rule::in([
                ConsentFormSetting::LANGUAGE_MODE_SINGLE,
                ConsentFormSetting::LANGUAGE_MODE_BOTH,
            ])],
            'form_language' => ['required', 'string', 'max:8'],
            'other_language' => ['nullable', 'string', 'max:8'],
            'ask_photo' => ['required', 'boolean'],
            'send_automatically' => ['required', 'boolean'],
            'questions' => ['required', 'array'],
            'questions.*.id' => ['nullable'],
            'questions.*.question_type' => ['required', Rule::in(['health', 'risk', 'aftercare'])],
            'questions.*.translations' => ['required', 'array'],
            'questions.*.translations.en' => ['required', 'string'],
            'questions.*.enabled' => ['required', 'boolean'],
            'use_studio_consent_form' => ['sometimes', 'boolean'],
            'allow_artist_own_consent' => ['sometimes', 'boolean'],
        ]);

        $normalizedQuestions = [];
        foreach (array_values((array) $request->input('questions', [])) as $index => $question) {
            $question = is_array($question) ? $question : [];
            $translations = $this->consentQuestions->normalizeTranslations($question['translations'] ?? []);
            if (! isset($translations['en']) || $translations['en'] === '') {
                throw \Illuminate\Validation\ValidationException::withMessages([
                    "questions.$index.translations.en" => ['English question is required.'],
                ]);
            }
            $type = (string) ($question['question_type'] ?? 'health');
            if (! in_array($type, ['health', 'risk', 'aftercare'], true)) {
                $type = 'health';
            }
            $normalizedQuestions[] = [
                'id' => $question['id'] ?? null,
                'question_type' => $type,
                'translations' => $translations,
                'enabled' => filter_var($question['enabled'] ?? true, FILTER_VALIDATE_BOOLEAN),
            ];
        }

        $market = strtoupper((string) $request->input('studio_market'));
        $marketLocale = ConsentFormSetting::localeForMarket($market);
        $mode = (string) $request->input('language_mode');

        $formLanguage = strtolower((string) $request->input('form_language'));
        $otherLanguage = $request->filled('other_language')
            ? strtolower((string) $request->input('other_language'))
            : null;

        if ($mode === ConsentFormSetting::LANGUAGE_MODE_BOTH) {
            $otherLanguage = $otherLanguage ?: ($marketLocale !== 'en' ? $marketLocale : null);
            if (! $otherLanguage || $otherLanguage === 'en') {
                $mode = ConsentFormSetting::LANGUAGE_MODE_SINGLE;
                $otherLanguage = null;
                $formLanguage = $formLanguage ?: 'en';
            } else {
                $formLanguage = 'en';
            }
        } else {
            $otherLanguage = null;
            if ($formLanguage === '') {
                $formLanguage = 'en';
            }
        }

        $allowYounger = $request->boolean('allow_younger');
        $ageAllow = $allowYounger
            ? (int) ($request->input('age_allow') ?? 16)
            : null;

        $settings = ConsentFormSetting::query()->updateOrCreate(
            ['user_id' => $userId],
            [
                'studio_market' => $market,
                'registration_number' => $request->input('registration_number') ?: null,
                'allow_younger' => $allowYounger,
                'age_allow' => $ageAllow,
                'language_mode' => $mode,
                'form_language' => $formLanguage,
                'other_language' => $otherLanguage,
                'ask_photo' => $request->boolean('ask_photo'),
                'health_split_consent' => true,
                'retention_notice_enabled' => true,
                'send_automatically' => $request->boolean('send_automatically'),
            ]
        );

        if ($request->exists('use_studio_consent_form') || $request->exists('allow_artist_own_consent')) {
            $studio->forceFill([
                'use_studio_consent_form' => $request->exists('use_studio_consent_form')
                    ? $request->boolean('use_studio_consent_form')
                    : (bool) $studio->use_studio_consent_form,
                'allow_artist_own_consent' => $request->exists('allow_artist_own_consent')
                    ? $request->boolean('allow_artist_own_consent')
                    : (bool) $studio->allow_artist_own_consent,
            ])->save();
        }

        $questions = $this->consentQuestions->syncForArtist($userId, $normalizedQuestions);

        return response()->json([
            'success' => true,
            'message' => 'Consent form saved.',
            'settings' => $settings->fresh()->toArtistArray(),
            'questions' => $questions->values(),
            'use_studio_consent_form' => (bool) $studio->fresh()->use_studio_consent_form,
            'allow_artist_own_consent' => (bool) $studio->fresh()->allow_artist_own_consent,
        ]);
    }

    private function defaultMarketForStudio(Studio $studio, $user): string
    {
        foreach ([
            $studio->country,
            $user?->country_user_belongs_in,
            $user?->userDetail?->payout_bank_country,
            $user?->userDetail?->country,
        ] as $candidate) {
            $code = ConsentFormSetting::normalizeMarketCountryCode((string) ($candidate ?? ''));
            if ($code !== null) {
                return $code;
            }
        }

        return 'GR';
    }

    private function settingsForOwner(int $userId, string $defaultMarket): ConsentFormSetting
    {
        $market = strtoupper($defaultMarket);
        $locale = ConsentFormSetting::localeForMarket($market);
        $existing = ConsentFormSetting::query()->where('user_id', $userId)->first();

        if ($existing) {
            $untouched = $existing->created_at
                && $existing->updated_at
                && $existing->created_at->equalTo($existing->updated_at);
            if ($untouched && strtoupper((string) $existing->studio_market) !== $market) {
                $existing->forceFill([
                    'studio_market' => $market,
                    'other_language' => $locale !== 'en' ? $locale : null,
                ])->save();
            }

            return $existing->fresh() ?? $existing;
        }

        return ConsentFormSetting::query()->create([
            'user_id' => $userId,
            'studio_market' => $market,
            'registration_number' => null,
            'allow_younger' => false,
            'age_allow' => null,
            'language_mode' => ConsentFormSetting::LANGUAGE_MODE_SINGLE,
            'form_language' => 'en',
            'other_language' => $locale !== 'en' ? $locale : null,
            'ask_photo' => true,
            'health_split_consent' => true,
            'retention_notice_enabled' => true,
            'send_automatically' => false,
        ]);
    }

    /**
     * @return array{0: \Illuminate\Support\Collection<int, Question>, 1: list<int>}
     */
    private function studioQuestionsForUser(int $userId): array
    {
        $activeSystemQuestionIds = QuestionSorting::query()
            ->where('user_id', 1)
            ->where('is_active', true)
            ->pluck('question_id')
            ->map(fn ($id) => (int) $id)
            ->all();

        $systemQuestionIds = QuestionSorting::query()
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
            ->where('form_context', self::FORM_CONTEXT)
            ->get()
            ->keyBy('id');

        $questions = $userSortingRows
            ->map(function ($sortingRow) use ($questionMap, $systemQuestionIds, $activeSystemQuestionIds, $userId) {
                $question = $questionMap->get((int) $sortingRow->question_id);
                if (! $question) {
                    return null;
                }

                $isSystemQuestion = in_array((int) $question->id, $systemQuestionIds, true);
                $isActiveAtSystemLevel = in_array((int) $question->id, $activeSystemQuestionIds, true);
                if ($isSystemQuestion && ! $isActiveAtSystemLevel) {
                    return null;
                }

                $question->setAttribute('order', (int) $sortingRow->order);
                $question->setAttribute('is_active', (bool) $sortingRow->is_active);

                if ($question->type === 'sizes') {
                    $question->setAttribute('options', QuestionSorting::sizeOptionsForArtist($userId));
                }

                return $question;
            })
            ->filter()
            ->values();

        return [$questions, $systemQuestionIds];
    }

    /**
     * Remove owned clones from the first studio intake implementation.
     */
    private function cleanupLegacyStudioClones(int $userId): void
    {
        try {
            $cloneIds = Question::query()
                ->where('user_id', $userId)
                ->where('form_context', 'studio')
                ->pluck('id');

            if ($cloneIds->isEmpty()) {
                return;
            }

            Question::query()->whereIn('id', $cloneIds)->delete();
        } catch (\Throwable $e) {
            Log::error('Failed to clean legacy studio intake clones', [
                'user_id' => $userId,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Link admin custom-request system questions via question_sorting (artist parity).
     */
    private function ensureSystemCustomQuestionsLinked(int $userId): void
    {
        try {
            $systemCustomIds = Question::query()
                ->where('user_id', 1)
                ->where('form_context', self::FORM_CONTEXT)
                ->pluck('id')
                ->map(fn ($id) => (int) $id)
                ->all();

            if ($systemCustomIds === []) {
                return;
            }

            $systemSortingRows = QuestionSorting::query()
                ->where('user_id', 1)
                ->where('is_active', true)
                ->whereIn('question_id', $systemCustomIds)
                ->orderBy('order')
                ->get(['question_id', 'order']);

            if ($systemSortingRows->isEmpty()) {
                return;
            }

            $existing = QuestionSorting::query()
                ->where('user_id', $userId)
                ->whereIn('question_id', $systemSortingRows->pluck('question_id'))
                ->pluck('question_id')
                ->map(fn ($id) => (int) $id)
                ->all();

            $missing = $systemSortingRows->filter(function ($row) use ($existing) {
                return ! in_array((int) $row->question_id, $existing, true);
            });

            if ($missing->isEmpty()) {
                return;
            }

            $now = now();
            QuestionSorting::insert(
                $missing->map(function ($row) use ($userId, $now) {
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
        } catch (\Throwable $e) {
            Log::error('Failed to link studio system intake questions', [
                'user_id' => $userId,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
