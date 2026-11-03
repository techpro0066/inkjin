<?php

namespace App\Http\Controllers;

use App\Models\Placement;
use App\Models\QuestionSorting;
use App\Models\Studio;
use App\Models\StudioPortfolio;
use App\Models\StudioRequest;
use App\Models\Style;
use App\Models\User;
use App\Models\UserStudio;
use App\Services\PublicBookingEmailVerificationService;
use App\Support\PageLook;
use App\Support\QuestionAnswerPresenter;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;

class StudioRequestController extends Controller
{
    public function show(Request $request, string $userName): View
    {
        $studio = $this->resolvePublicStudio($userName);
        $ownerId = (int) $studio->user_id;

        $artists = $this->studioArtistsForPicker($studio);
        $questions = QuestionSorting::activeQuestionsPayloadForArtist($ownerId, 'custom');
        $questions = array_values(array_filter(
            $questions,
            static fn ($q) => ($q['question_type'] ?? '') !== 'artist'
        ));
        array_unshift($questions, $this->artistPickerQuestion($artists));

        $studioName = trim((string) ($studio->name ?? ''));
        if ($studioName === '') {
            $studioName = 'Studio';
        }

        $username = trim((string) ($studio->username ?? ''));
        $logo = trim((string) ($studio->logo_url ?? $studio->image_url ?? ''));
        $initials = strtoupper(substr(preg_replace('/\s+/', '', $studioName) ?: 'ST', 0, 2));

        $cityBits = array_filter([
            trim((string) ($studio->city ?? '')),
            trim((string) ($studio->country ?? '')),
        ]);
        $cityLabel = implode(', ', $cityBits);

        $hiddenStyleOptions = Style::query()
            ->active()
            ->where('appear_on_question', false)
            ->ordered()
            ->pluck('name')
            ->values()
            ->all();

        $hiddenPlacementOptions = Placement::query()
            ->active()
            ->where('appear_on_question', false)
            ->ordered()
            ->pluck('name')
            ->values()
            ->all();

        $studioProfileUrl = route('public.artist', ['username' => $username]);

        $studioPortfolioId = (int) $request->query('studio_portfolio', 0);
        $studioPortfolio = $studioPortfolioId > 0
            ? StudioPortfolio::query()
                ->whereKey($studioPortfolioId)
                ->where('studio_id', $studio->id)
                ->where('is_active', true)
                ->first()
            : null;

        $prefilledQuestionAnswers = $studioPortfolio
            ? $this->prefillAnswersFromStudioPortfolio($studioPortfolio, $questions, $artists)
            : [];

        return view('public.request-custom', [
            // Studio mode flags (request-custom.blade.php branches on these).
            'isStudioRequest' => true,
            'isManagedScheduling' => true,
            'isWaitlistJoin' => false,
            'guestSpotId' => null,
            'guestSpot' => null,
            'activeGuestSpots' => collect(),
            'studioChoices' => [],
            'studioLabel' => '',
            'userDetail' => null,
            'fallbackTattooSlug' => '',
            'prefilledQuestionAnswers' => $prefilledQuestionAnswers === []
                ? new \stdClass
                : $prefilledQuestionAnswers,
            'studioPortfolioId' => $studioPortfolio?->id,

            // Studio branding.
            'studio' => $studio,
            'studioName' => $studioName,
            'studioUsername' => $username,
            'studioInitials' => $initials,
            'studioLogoUrl' => $logo !== '' ? asset(ltrim($logo, '/')) : null,
            'studioCityLabel' => $cityLabel,
            'studioArtistCount' => $artists->count(),
            'studioProfileUrl' => $studioProfileUrl,
            'lookState' => PageLook::stateFromStudio($studio),

            // Shared request-custom view vars (mapped to studio values).
            'artistName' => $studioName,
            'artistUsername' => '',
            'artistProfileUrl' => $studioProfileUrl,

            // Questions: artist picker first (id studio_artist_pick, type select).
            'questions' => $questions,
            'requiredBookingQuestions' => $questions,
            'hasArtistQuestions' => true,
            'artists' => $artists->values()->all(),
            'hiddenStyleOptions' => $hiddenStyleOptions,
            'hiddenPlacementOptions' => $hiddenPlacementOptions,
        ]);
    }

    public function submit(Request $request, PublicBookingEmailVerificationService $emailVerification): JsonResponse
    {
        $validated = $request->validate([
            'studio_username' => ['required', 'string', 'max:100'],
            'request_payload' => ['required', 'array'],
            'request_payload.email' => ['required', 'email'],
            'request_payload.name' => ['nullable', 'string', 'max:255'],
            'request_payload.phone' => ['nullable', 'string', 'max:50'],
            'request_payload.notes' => ['nullable', 'string', 'max:10000'],
            'request_payload.referral_source' => ['nullable', 'string', 'max:255'],
            'request_payload.questions_answers' => ['nullable', 'array'],
            'request_payload.assigned_artist_id' => ['nullable', 'integer', 'min:1'],
            'request_payload.artist_choice' => ['nullable', 'string', 'max:255'],
            'request_payload.preferences' => ['nullable', 'array'],
            'request_payload.preferred_days' => ['nullable', 'array'],
            'request_payload.how_much_flexible' => ['nullable', 'string', 'max:255'],
            'request_payload.avoid_dates' => ['nullable', 'string', 'max:500'],
            'request_payload.urgency' => ['nullable', 'string', 'max:255'],
            'request_payload.studio_portfolio_id' => ['nullable', 'integer', 'min:1'],
        ]);

        $studio = $this->resolvePublicStudio($validated['studio_username']);
        $payload = $validated['request_payload'];
        $email = mb_strtolower(trim((string) ($payload['email'] ?? '')));

        $verifiedEntry = $emailVerification->getVerified($email);
        if (! $verifiedEntry) {
            return response()->json(['message' => 'Please verify your email before submitting your request.'], 422);
        }

        $requestUser = User::query()->find((int) $verifiedEntry['user_id']);
        if (! $requestUser || mb_strtolower((string) $requestUser->email) !== $email) {
            return response()->json(['message' => 'User not found. Please verify email again.'], 422);
        }

        $name = trim((string) ($payload['name'] ?? ''));
        if ($name !== '') {
            $parts = preg_split('/\s+/', $name) ?: [];
            $requestUser->first_name = $parts[0] ?? $requestUser->first_name;
            $requestUser->last_name = count($parts) > 1
                ? implode(' ', array_slice($parts, 1))
                : ($requestUser->last_name ?: 'User');
            $requestUser->save();
        }

        $requestUser->syncPhoneNumber($payload['phone'] ?? null);
        $requestUser->syncHearAboutUs($payload['referral_source'] ?? null);

        $questionsAnswers = is_array($payload['questions_answers'] ?? null)
            ? $payload['questions_answers']
            : [];
        $phone = trim((string) ($payload['phone'] ?? ''));
        $referral = trim((string) ($payload['referral_source'] ?? ''));
        if ($phone !== '' || $referral !== '') {
            $questionsAnswers['_contact'] = array_filter([
                'phone' => $phone !== '' ? $phone : null,
                'referral_source' => $referral !== '' ? $referral : null,
            ]);
        }

        // Client artist pick is preference only — stored in questions_answers for the studio.
        // Do not write assigned_artist_id; studio assigns from the dashboard.
        $artistChoice = trim((string) ($payload['artist_choice'] ?? ''));
        if ($artistChoice === '') {
            $artistChoice = 'Any available artist';
        }
        $questionsAnswers['_artist_choice'] = $artistChoice;

        $referenceImages = $this->collectStudioRequestImages($questionsAnswers);

        $source = 'studio_page';
        $sourcePath = '/@'.$studio->username.'/request';
        $portfolioId = (int) ($payload['studio_portfolio_id'] ?? 0);
        if ($portfolioId > 0) {
            $fromPortfolio = StudioPortfolio::query()
                ->whereKey($portfolioId)
                ->where('studio_id', $studio->id)
                ->first();
            if ($fromPortfolio) {
                $source = 'studio_portfolio';
                $sourcePath = '/@'.$studio->username.'/request?studio_portfolio='.$fromPortfolio->id;
                $questionsAnswers['_studio_portfolio_id'] = $fromPortfolio->id;
                // Always keep the portfolio piece image even if the client also uploads more.
                $portfolioImage = $this->studioPortfolioImageUrl($fromPortfolio);
                if ($portfolioImage !== null) {
                    array_unshift($referenceImages, $portfolioImage);
                    $referenceImages = array_values(array_unique($referenceImages));
                }
            }
        }

        $studioRequest = StudioRequest::create([
            'studio_id' => $studio->id,
            'user_id' => $requestUser->id,
            'assigned_artist_id' => null,
            'status' => StudioRequest::STATUS_PENDING,
            'source' => $source,
            'source_path' => $sourcePath,
            'client_name' => $name !== '' ? $name : null,
            'client_email' => $email,
            'client_phone' => $phone !== '' ? $phone : null,
            'questions_answers' => $questionsAnswers,
            'reference_images' => $referenceImages !== [] ? $referenceImages : null,
            'anything_else_notes' => trim((string) ($payload['notes'] ?? '')) ?: null,
            'preferences' => is_array($payload['preferences'] ?? null) ? $payload['preferences'] : null,
            'preferred_days' => is_array($payload['preferred_days'] ?? null) ? $payload['preferred_days'] : null,
            'avoid_dates' => trim((string) ($payload['avoid_dates'] ?? '')) ?: null,
            'how_much_flexible' => trim((string) ($payload['how_much_flexible'] ?? '')) ?: null,
            'urgency' => trim((string) ($payload['urgency'] ?? '')) ?: null,
        ]);

        return response()->json([
            'saved' => true,
            'studio_request_id' => $studioRequest->id,
            'message' => 'Your request was sent to the studio.',
        ]);
    }

    private function resolvePublicStudio(string $usernameKey): Studio
    {
        $key = strtolower(trim($usernameKey));
        $studio = Studio::query()->whereRaw('LOWER(username) = ?', [$key])->first();

        if (
            ! $studio
            || trim((string) ($studio->username ?? '')) === ''
            || ! $studio->user
            || $studio->user->role !== 'studio'
            || $studio->user->on_boarding !== 'yes'
        ) {
            abort(404, 'Studio not found');
        }

        return $studio;
    }

    /**
     * @return \Illuminate\Support\Collection<int, array{user_id:int,name:string,username:string}>
     */
    private function studioArtistsForPicker(Studio $studio)
    {
        if (! UserStudio::tableReady()) {
            return collect();
        }

        $query = UserStudio::query()
            ->with(['user.userDetail'])
            ->where('studio_id', $studio->id)
            ->where('status', UserStudio::STATUS_ACTIVE);

        if (Schema::hasColumn('user_studios', 'show_on_page')) {
            $query->where(function ($q) {
                $q->whereNull('show_on_page')->orWhere('show_on_page', true);
            });
        }

        return $query->orderBy('id')->get()->map(function (UserStudio $link) {
            $artistUser = $link->user;
            $detail = $artistUser?->userDetail;
            if (! $detail || ! $artistUser || $artistUser->role !== 'artist' || $artistUser->on_boarding !== 'yes') {
                return null;
            }

            $name = $detail->publicDisplayName();
            if ($name === '' || $name === 'Artist') {
                $fallback = trim(($artistUser->first_name ?? '').' '.($artistUser->last_name ?? ''));
                $name = $fallback !== '' ? $fallback : (string) ($detail->user_name ?? 'Artist');
            }

            return [
                'user_id' => (int) $artistUser->id,
                'name' => $name,
                'username' => trim((string) ($detail->user_name ?? '')),
            ];
        })->filter()->values();
    }

    /**
     * @param  \Illuminate\Support\Collection<int, array{user_id:int,name:string,username:string}>  $artists
     * @return array<string, mixed>
     */
    private function artistPickerQuestion($artists): array
    {
        $options = ['Any available artist'];
        foreach ($artists as $artist) {
            $options[] = $artist['name'];
        }

        return [
            'id' => 'studio_artist_pick',
            'question' => 'Which artist?',
            'description' => 'Pick an artist, or let the studio choose the best fit for your idea.',
            'placeholder' => '',
            'type' => 'select',
            'question_type' => 'artist',
            'is_required' => true,
            'is_active' => true,
            'options' => $options,
        ];
    }

    /**
     * Prefill intake answers from a studio portfolio piece (mirrors artist portfolio prefill).
     *
     * @param  array<int, array<string, mixed>>  $questions
     * @param  \Illuminate\Support\Collection<int, array{user_id:int,name:string,username:string}>  $artists
     * @return array<string, mixed>
     */
    private function prefillAnswersFromStudioPortfolio(
        StudioPortfolio $portfolio,
        array $questions,
        $artists
    ): array {
        $imageRaw = trim((string) ($portfolio->image ?? ''));
        $imageUrl = $imageRaw !== ''
            ? (str_starts_with($imageRaw, 'http://') || str_starts_with($imageRaw, 'https://')
                ? $imageRaw
                : asset(ltrim($imageRaw, '/')))
            : null;

        $stylesJoined = collect([(string) ($portfolio->primary_style ?? '')])
            ->merge(is_array($portfolio->other_styles) ? $portfolio->other_styles : [])
            ->map(fn ($style) => trim((string) $style))
            ->filter()
            ->unique()
            ->values()
            ->implode(', ');

        $colorLabel = match ((string) ($portfolio->color ?? '')) {
            'color' => 'Full Color',
            'black-grey' => 'Black & Grey',
            'both' => 'Black & Color',
            default => trim((string) ($portfolio->color ?? '')),
        };

        $valuesByQuestionType = [
            'description' => trim((string) ($portfolio->description ?? '')),
            'style' => $stylesJoined,
            'color' => $colorLabel,
            'placement' => trim((string) ($portfolio->placement ?? '')),
            'size' => '',
            'reference_image' => $imageUrl ? [$imageUrl] : null,
            'placement_photo' => null,
            'coverup_photo' => null,
            'other' => null,
        ];

        $seenQuestionTypes = [];
        $prefilled = [];

        foreach ($questions as $question) {
            if (! is_array($question) || empty($question['id'])) {
                continue;
            }

            $questionType = (string) ($question['question_type'] ?? 'other');
            if ($questionType === '' || $questionType === 'artist' || isset($seenQuestionTypes[$questionType])) {
                continue;
            }

            $raw = $valuesByQuestionType[$questionType] ?? null;
            $answer = $this->adaptStudioPortfolioPrefillToQuestionWidget(
                (string) ($question['type'] ?? 'input'),
                $questionType,
                $raw,
                $portfolio,
                is_array($question['options'] ?? null) ? $question['options'] : []
            );

            $seenQuestionTypes[$questionType] = true;

            if ($answer === null || $answer === '' || $answer === []) {
                continue;
            }

            $prefilled[(string) $question['id']] = $answer;
        }

        // Prefill artist picker when the piece is attributed to a linked artist.
        $artistUserId = (int) ($portfolio->artist_user_id ?? 0);
        if ($artistUserId > 0 && ($portfolio->attribution ?? '') === StudioPortfolio::ATTRIBUTION_ARTIST) {
            $match = $artists->firstWhere('user_id', $artistUserId);
            if (is_array($match) && trim((string) ($match['name'] ?? '')) !== '') {
                $prefilled['studio_artist_pick'] = (string) $match['name'];
            }
        }

        return $prefilled;
    }

    /**
     * @param  list<mixed>  $options
     */
    private function adaptStudioPortfolioPrefillToQuestionWidget(
        string $widgetType,
        string $questionType,
        mixed $raw,
        StudioPortfolio $portfolio,
        array $options = []
    ): mixed {
        $imageQuestionTypes = ['reference_image', 'placement_photo', 'coverup_photo'];

        if (in_array($questionType, $imageQuestionTypes, true) || $widgetType === 'image') {
            if ($widgetType !== 'image' || $questionType !== 'reference_image') {
                return null;
            }

            if (is_array($raw)) {
                return array_values(array_filter($raw, fn ($url) => filled($url)));
            }

            return filled($raw) ? [(string) $raw] : null;
        }

        $optionStrings = collect($options)
            ->map(fn ($option) => trim((string) $option))
            ->filter()
            ->values()
            ->all();

        if ($questionType === 'style' && in_array($widgetType, ['style', 'radio', 'select'], true)) {
            $primary = trim((string) ($portfolio->primary_style ?? ''));

            return $this->matchPrefillOption($primary, $optionStrings) ?? ($primary !== '' ? $primary : null);
        }

        if ($questionType === 'placement' && in_array($widgetType, ['placement', 'radio', 'select'], true)) {
            $placement = trim((string) ($portfolio->placement ?? ''));

            return $this->matchPrefillOption($placement, $optionStrings) ?? ($placement !== '' ? $placement : null);
        }

        if ($questionType === 'color') {
            $candidates = array_values(array_filter([
                is_string($raw) ? trim($raw) : null,
                match ((string) ($portfolio->color ?? '')) {
                    'color' => 'Color',
                    'black-grey' => 'Black & Grey',
                    'both' => 'Both',
                    default => null,
                },
                match ((string) ($portfolio->color ?? '')) {
                    'color' => 'Full Color',
                    'black-grey' => 'Black & Grey',
                    'both' => 'Black & Color',
                    default => null,
                },
                trim((string) ($portfolio->color ?? '')),
            ]));

            if (in_array($widgetType, ['radio', 'select'], true) && $optionStrings !== []) {
                foreach ($candidates as $candidate) {
                    $matched = $this->matchPrefillOption($candidate, $optionStrings);
                    if ($matched !== null) {
                        return $matched;
                    }
                }
            }

            return $candidates[0] ?? null;
        }

        if (! is_string($raw)) {
            return null;
        }

        $text = trim($raw);

        return $text !== '' ? $text : null;
    }

    /**
     * @param  list<string>  $options
     */
    private function matchPrefillOption(string $value, array $options): ?string
    {
        $value = trim($value);
        if ($value === '' || $options === []) {
            return null;
        }

        foreach ($options as $option) {
            if (strcasecmp($option, $value) === 0) {
                return $option;
            }
        }

        return null;
    }

    /**
     * Collect every image URL from intake answers (portfolio prefill + client uploads).
     *
     * @param  array<string, mixed>  $questionsAnswers
     * @return list<string>
     */
    private function collectStudioRequestImages(array $questionsAnswers): array
    {
        $images = [];

        foreach (QuestionAnswerPresenter::allImageUrls($questionsAnswers) as $url) {
            $path = trim((string) $url);
            if ($path === '') {
                continue;
            }
            $images[] = str_starts_with($path, 'http') ? $path : asset(ltrim($path, '/'));
        }

        // Fallback for older / oddly shaped answer payloads.
        foreach ($questionsAnswers as $key => $value) {
            if (is_string($key) && str_starts_with($key, '_')) {
                continue;
            }
            if (! is_array($value)) {
                continue;
            }
            $type = strtolower((string) ($value['question_type'] ?? $value['type'] ?? ''));
            $widget = strtolower((string) ($value['type'] ?? ''));
            $isImageType = in_array($type, ['reference_image', 'placement_photo', 'coverup_photo', 'images', 'image'], true)
                || in_array($widget, ['reference_image', 'images', 'image'], true)
                || str_contains(strtolower((string) ($value['question'] ?? '')), 'reference');

            $candidates = $value['images'] ?? $value['urls'] ?? null;
            if ($candidates === null && array_key_exists('answer', $value)) {
                $candidates = $value['answer'];
            } elseif ($candidates === null && array_key_exists('value', $value)) {
                $candidates = $value['value'];
            }
            if (! $isImageType && $candidates === null) {
                continue;
            }
            if (is_string($candidates) && trim($candidates) !== '') {
                $candidates = [trim($candidates)];
            }
            if (! is_array($candidates)) {
                continue;
            }
            foreach ($candidates as $url) {
                $path = is_string($url) ? trim($url) : '';
                if ($path === '' || ! QuestionAnswerPresenter::isImageUrl($path)) {
                    continue;
                }
                $images[] = str_starts_with($path, 'http') ? $path : asset(ltrim($path, '/'));
            }
        }

        return array_values(array_unique($images));
    }

    private function studioPortfolioImageUrl(StudioPortfolio $portfolio): ?string
    {
        $imageRaw = trim((string) ($portfolio->image ?? ''));
        if ($imageRaw === '') {
            return null;
        }

        return str_starts_with($imageRaw, 'http://') || str_starts_with($imageRaw, 'https://')
            ? $imageRaw
            : asset(ltrim($imageRaw, '/'));
    }
}
