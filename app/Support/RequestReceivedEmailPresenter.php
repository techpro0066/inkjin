<?php

namespace App\Support;

use App\Models\BookingRequest;
use App\Models\CustomRequest;
use App\Models\User;
use App\Models\UserDetail;

class RequestReceivedEmailPresenter
{
    /**
     * @return array<string, mixed>
     */
    public static function forCustomRequest(
        CustomRequest $customRequest,
        UserDetail $userDetail,
        string $accessUrl,
        ?User $client = null,
    ): array {
        $artist = self::artistCard($userDetail);
        $clientUser = $client ?: $customRequest->user;
        $clientFirst = self::firstName($clientUser, 'there');

        return array_merge($artist, [
            'clientFirstName' => $clientFirst,
            'accessUrl' => $accessUrl,
            'summaryRows' => self::customRequestSummaryRows($customRequest),
            'introText' => 'Thanks for your request. '.$artist['artistFirstName']
                .' will look at your idea and reply with a quote, or let you know if it isn’t a fit. This usually takes a few days.',
            'payNote' => 'You won’t pay anything until you accept a quote.',
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public static function forCustomRequestArtist(
        CustomRequest $customRequest,
        string $dashboardUrl,
    ): array {
        $customRequest->loadMissing(['user', 'artist']);

        $client = $customRequest->user;
        $artist = $customRequest->artist;

        $clientFirst = self::firstName($client, 'A client');
        $clientDisplay = self::clientDisplayName($client);
        $artistFirst = self::firstName($artist, 'there');
        $ideaLabel = self::ideaLabelFromAnswers($customRequest->questions_answers);

        return [
            'artistFirstName' => $artistFirst,
            'clientFirstName' => $clientFirst,
            'clientDisplayName' => $clientDisplay,
            'clientInitials' => self::initials($clientDisplay),
            'ideaLabel' => $ideaLabel,
            'summaryRows' => self::customRequestSummaryRows($customRequest),
            'dashboardUrl' => $dashboardUrl,
            'introText' => $clientFirst.' sent you a custom request.',
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public static function forBookingRequest(
        BookingRequest $bookingRequest,
        UserDetail $userDetail,
        string $accessUrl,
        ?User $client = null,
    ): array {
        $bookingRequest->loadMissing(['tattoo', 'user']);
        $artist = self::artistCard($userDetail);
        $clientUser = $client ?: $bookingRequest->user;
        $clientFirst = self::firstName($clientUser, 'there');

        $designTitle = trim((string) ($bookingRequest->tattoo?->title ?? 'Design'));
        $summary = [
            ['label' => 'Request', 'value' => $bookingRequest->referenceLabel()],
            ['label' => 'Design', 'value' => $designTitle !== '' ? $designTitle : 'Design'],
        ];

        $prefCount = 0;
        foreach ((array) ($bookingRequest->preferences ?? []) as $pref) {
            if (is_array($pref) && trim((string) ($pref['date'] ?? '')) !== '') {
                $prefCount++;
            }
        }
        if ($prefCount > 0) {
            $summary[] = [
                'label' => 'Preferred times',
                'value' => $prefCount === 1 ? '1 option' : $prefCount.' options',
            ];
        }

        return array_merge($artist, [
            'clientFirstName' => $clientFirst,
            'accessUrl' => $accessUrl,
            'summaryRows' => $summary,
            'introText' => 'Thanks for your request. '.$artist['artistFirstName']
                .' will look at your available times and reply soon. This usually takes a few days.',
            'payNote' => 'You won’t pay anything until you book.',
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public static function forQuote(
        CustomRequest $customRequest,
        string $accessUrl,
        string $askQuestionUrl,
    ): array {
        $customRequest->loadMissing(['user', 'artist.userDetail']);
        $userDetail = $customRequest->artist?->userDetail;
        if (! $userDetail) {
            $userDetail = new UserDetail;
        }

        $artist = self::artistCard($userDetail);
        $clientFirst = self::firstName($customRequest->user, 'there');
        $ideaLabel = self::ideaLabelFromAnswers($customRequest->questions_answers);
        $price = max(0, round((float) ($customRequest->estimated_price ?? 0), 2));
        $priceLabel = $customRequest->estimatedPriceLabel();

        $durationParts = [];
        $estimatedTime = trim((string) ($customRequest->estimated_time ?? ''));
        if ($estimatedTime !== '') {
            $durationParts[] = $estimatedTime;
        }
        $sessions = trim((string) ($customRequest->number_of_sessions ?? ''));
        if ($sessions !== '') {
            $durationParts[] = str_contains(strtolower($sessions), 'session')
                ? $sessions
                : ($sessions === '1' ? '1 session' : $sessions.' sessions');
        }
        $durationLabel = implode(' · ', $durationParts);

        $depositLabel = '—';
        try {
            if ($customRequest->artist?->userDetail && $price > 0.009) {
                $depositMeta = app(\App\Services\BookingCheckoutPricingService::class)
                    ->resolveDepositForTattoo($customRequest->artist->userDetail, $price);
                $deposit = max(0, round((float) ($depositMeta['deposit'] ?? 0), 2));
                $depositLabel = '€'.number_format($deposit, 2, '.', ',');
            }
        } catch (\Throwable) {
            $depositLabel = '—';
        }

        $message = trim((string) ($customRequest->message_for_client ?? ''));

        return array_merge($artist, [
            'clientFirstName' => $clientFirst,
            'ideaLabel' => $ideaLabel,
            'priceLabel' => $priceLabel,
            'priceAmount' => $price,
            'durationLabel' => $durationLabel,
            'depositLabel' => $depositLabel,
            'quoteMessage' => $message,
            'accessUrl' => $accessUrl,
            'askQuestionUrl' => $askQuestionUrl,
            'validityNote' => 'The quote is valid for 7 days. The deposit comes off your final price.',
        ]);
    }

    public static function ideaLabelFromAnswers(mixed $questionsAnswers): string
    {
        foreach (self::customSummaryFromAnswers($questionsAnswers) as $row) {
            if (($row['label'] ?? '') === 'Kind of tattoo') {
                return (string) $row['value'];
            }
        }

        return 'your custom tattoo request';
    }

    /**
     * @return array{
     *     artistName: string,
     *     artistFirstName: string,
     *     artistInitials: string,
     *     artistAvatarUrl: ?string,
     *     artistMeta: string
     * }
     */
    private static function artistCard(UserDetail $userDetail): array
    {
        $userDetail->loadMissing('user');
        $artistName = trim($userDetail->publicDisplayName()) ?: 'your artist';
        $artistFirst = trim((string) ($userDetail->user?->first_name ?? ''));
        if ($artistFirst === '') {
            $artistFirst = trim((string) strtok($artistName, ' ')) ?: $artistName;
        }

        $handle = trim((string) ($userDetail->user_name ?? ''));
        if ($handle !== '' && ! str_starts_with($handle, '@')) {
            $handle = '@'.$handle;
        }

        $studio = trim((string) $userDetail->studioNameWithCityCountry());
        if ($studio === '') {
            $studio = trim((string) $userDetail->resolvedStudioName());
        }

        $meta = trim(($handle !== '' ? $handle : '').($handle !== '' && $studio !== '' ? ' · ' : '').$studio);

        $avatarUrl = null;
        $avatarPath = trim((string) ($userDetail->avatar ?? ''));
        if ($avatarPath !== '') {
            $avatarUrl = str_starts_with($avatarPath, 'http://') || str_starts_with($avatarPath, 'https://')
                ? $avatarPath
                : asset(ltrim($avatarPath, '/'));
        }

        return [
            'artistName' => $artistName,
            'artistFirstName' => $artistFirst,
            'artistInitials' => $userDetail->publicDisplayInitials(),
            'artistAvatarUrl' => $avatarUrl,
            'artistMeta' => $meta,
        ];
    }

    /**
     * @return list<array{label: string, value: string}>
     */
    private static function customRequestSummaryRows(CustomRequest $customRequest): array
    {
        $summary = [
            ['label' => 'Request', 'value' => $customRequest->referenceLabel()],
        ];

        foreach (self::customSummaryFromAnswers($customRequest->questions_answers) as $row) {
            $summary[] = $row;
        }

        $imageCount = count($customRequest->artistPanelImageUrls());
        if ($imageCount < 1) {
            $imageCount = count(QuestionAnswerPresenter::allImageUrls($customRequest->questions_answers));
        }
        if ($imageCount > 0 && ! self::summaryHasLabel($summary, 'Reference images')) {
            $summary[] = [
                'label' => 'Reference images',
                'value' => QuestionAnswerPresenter::formatPhotoLabels($imageCount),
            ];
        }

        return $summary;
    }

    /**
     * @return list<array{label: string, value: string}>
     */
    private static function customSummaryFromAnswers(mixed $questionsAnswers): array
    {
        $wanted = [
            'kind' => 'Kind of tattoo',
            'style' => 'Style',
            'placement' => 'Where on the body',
            'size' => 'Size',
            'budget' => 'Budget',
            'references' => 'Reference images',
        ];
        $found = [];

        foreach (QuestionAnswerPresenter::rows($questionsAnswers) as $row) {
            $key = self::matchSummaryKey((string) ($row['question'] ?? ''), (string) ($row['type'] ?? ''));
            if ($key === null || isset($found[$key])) {
                continue;
            }

            $value = QuestionAnswerPresenter::formatAnswer($row['answer'] ?? null, (string) ($row['type'] ?? ''));
            if ($value === '' || $value === '—') {
                continue;
            }

            $found[$key] = [
                'label' => $wanted[$key],
                'value' => $value,
            ];
        }

        $ordered = [];
        foreach (array_keys($wanted) as $key) {
            if (isset($found[$key])) {
                $ordered[] = $found[$key];
            }
        }

        return $ordered;
    }

    private static function matchSummaryKey(string $question, string $type): ?string
    {
        $q = strtolower(trim($question));
        $type = strtolower(trim($type));

        if (in_array($type, ['image', 'reference_image'], true) || str_contains($q, 'reference')) {
            return 'references';
        }
        if (str_contains($q, 'kind of tattoo')) {
            return 'kind';
        }
        if (str_contains($q, 'what style') || $q === 'style' || (str_contains($q, 'style') && ! str_contains($q, 'lifestyle'))) {
            return 'style';
        }
        if (str_contains($q, 'where on') || (str_contains($q, 'placement') && ! str_contains($q, 'photo'))) {
            return 'placement';
        }
        if (str_contains($q, 'what size') || $q === 'size' || str_contains($q, 'how big')) {
            return 'size';
        }
        if (str_contains($q, 'budget')) {
            return 'budget';
        }

        return null;
    }

    private static function clientDisplayName(?User $user): string
    {
        $first = trim((string) ($user?->first_name ?? ''));
        $last = trim((string) ($user?->last_name ?? ''));

        if ($first === '') {
            return 'Client';
        }

        $display = ucfirst($first);
        if ($last !== '') {
            $display .= ' '.strtoupper(mb_substr($last, 0, 1)).'.';
        }

        return $display;
    }

    private static function initials(string $name): string
    {
        $parts = preg_split('/\s+/', trim($name)) ?: [];
        $initials = strtoupper(mb_substr($parts[0] ?? '', 0, 1).mb_substr($parts[1] ?? '', 0, 1));

        return $initials !== '' ? $initials : 'CL';
    }

    /**
     * @param  list<array{label: string, value: string}>  $summary
     */
    private static function summaryHasLabel(array $summary, string $label): bool
    {
        foreach ($summary as $row) {
            if (($row['label'] ?? '') === $label) {
                return true;
            }
        }

        return false;
    }

    private static function firstName(?User $user, string $fallback): string
    {
        $first = trim((string) ($user?->first_name ?? ''));

        return $first !== '' ? $first : $fallback;
    }
}
