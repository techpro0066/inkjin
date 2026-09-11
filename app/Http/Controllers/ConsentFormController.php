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
        $defaultMarketCountry = strtoupper((string) ($userDetail?->payout_bank_country ?? ''));
        if ($defaultMarketCountry === '' || ! StripeConnectCountries::isRegistrationCountry($defaultMarketCountry)) {
            $defaultMarketCountry = 'GR';
        }

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

        $validated = $request->validate([
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
        ]);

        $market = strtoupper($validated['studio_market']);
        $marketLocale = ConsentFormSetting::localeForMarket($market);
        $mode = $validated['language_mode'];

        $formLanguage = strtolower($validated['form_language']);
        $otherLanguage = isset($validated['other_language'])
            ? strtolower((string) $validated['other_language'])
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

        $allowYounger = (bool) $validated['allow_younger'];
        $ageAllow = $allowYounger
            ? (int) ($validated['age_allow'] ?? 16)
            : null;

        $settings = ConsentFormSetting::query()->updateOrCreate(
            ['user_id' => $userId],
            [
                'studio_market' => $market,
                'registration_number' => $validated['registration_number'] ?: null,
                'allow_younger' => $allowYounger,
                'age_allow' => $ageAllow,
                'language_mode' => $mode,
                'form_language' => $formLanguage,
                'other_language' => $otherLanguage,
                'ask_photo' => (bool) $validated['ask_photo'],
                'send_automatically' => (bool) $validated['send_automatically'],
            ]
        );

        return response()->json([
            'success' => true,
            'settings' => $settings->fresh()->toArtistArray(),
        ]);
    }

    private function settingsForArtist(int $userId, string $defaultMarket): ConsentFormSetting
    {
        $existing = ConsentFormSetting::query()->where('user_id', $userId)->first();
        if ($existing) {
            return $existing;
        }

        $market = strtoupper($defaultMarket);
        $locale = ConsentFormSetting::localeForMarket($market);

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
