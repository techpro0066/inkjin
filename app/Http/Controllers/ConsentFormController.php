<?php

namespace App\Http\Controllers;

use App\Models\ConsentFormSetting;
use App\Services\ConsentFormQuestionService;
use App\Support\StripeConnectCountries;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ConsentFormController extends Controller
{
    public function __construct(
        private ConsentFormQuestionService $consentQuestions
    ) {}

    /**
     * Artist consent / content form settings.
     */
    public function index(): View
    {
        $user = Auth::user();
        $userDetail = $user?->userDetail;
        $defaultMarketCountry = ConsentFormSetting::defaultMarketForArtist($user, $userDetail);

        $settings = $this->settingsForArtist((int) $user->id, $defaultMarketCountry);
        $consentQuestions = $this->consentQuestions->listForArtist((int) $user->id);

        return view('artist.forms.consent', [
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

    /**
     * Persist artist consent form settings.
     */
    public function updateSettings(Request $request): JsonResponse
    {
        $userId = (int) Auth::id();
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
        ]);

        // Use raw input for question translations — validated() only keeps keys with
        // explicit rules (translations.en), which would drop studio-language text.
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
                // Cannot offer "both" without a real second language for this market.
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
                'send_automatically' => $request->boolean('send_automatically'),
            ]
        );

        $questions = $this->consentQuestions->syncForArtist($userId, $normalizedQuestions);

        return response()->json([
            'success' => true,
            'message' => 'Consent form saved.',
            'settings' => $settings->fresh()->toArtistArray(),
            'questions' => $questions->values(),
        ]);
    }

    private function settingsForArtist(int $userId, string $defaultMarket): ConsentFormSetting
    {
        $market = strtoupper($defaultMarket);
        $locale = ConsentFormSetting::localeForMarket($market);
        $existing = ConsentFormSetting::query()->where('user_id', $userId)->first();

        if ($existing) {
            // Auto-created rows keep created_at == updated_at until the artist saves.
            // Re-align those with the artist's country so older GR defaults correct themselves.
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
            'send_automatically' => false,
        ]);
    }
}
