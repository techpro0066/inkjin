<?php

namespace App\Support;

use App\Models\Availability;
use App\Models\UserDetail;
use App\Models\UserStudio;

final class ArtistStudioChoices
{
    /**
     * Active workplace studios for public booking studio pickers.
     * `id` is user_studios.id (same as availabilities.studio_ids).
     * Only studios that appear in weekly availability are returned.
     * Same-name studios at different locations stay distinct (`label` includes location).
     *
     * @return list<array{id: int|null, name: string, label: string, address: string, maps_link: string}>
     */
    public static function forArtist(int $artistUserId, UserDetail $userDetail): array
    {
        $choices = [];

        if (UserStudio::tableReady()) {
            $rows = UserStudio::activeLinksForUser($artistUserId);
            foreach ($rows as $row) {
                $name = trim((string) ($row->studio?->name ?? $row->studio_name ?? ''));
                $street = trim(trim((string) ($row->street_number ?? '')).' '.trim((string) ($row->street_name ?? '')));
                if ($street === '') {
                    $street = trim((string) ($row->studio_address ?? ''));
                }
                $city = trim((string) ($row->city ?? ''));
                $country = trim((string) ($row->country ?? ''));
                $cityLine = implode(', ', array_filter([
                    trim((string) ($row->postal_code ?? '')),
                    $city,
                    $country,
                ]));
                $address = implode(', ', array_filter([$street, $cityLine]));
                if ($name === '' && $address === '') {
                    continue;
                }
                $choices[] = [
                    'id' => (int) $row->id,
                    'name' => $name !== '' ? $name : 'Studio',
                    'street' => $street,
                    'address' => $address,
                    'city' => $city,
                    'country' => $country,
                    'maps_link' => trim((string) ($row->google_maps_link ?? '')),
                ];
            }
        }

        if ($choices === []) {
            $studioName = trim((string) ($userDetail->studio_name ?? ''));
            $addressLines = $userDetail->studioLocationLines();
            array_shift($addressLines);
            $address = implode(', ', array_filter($addressLines));
            $street = $addressLines[0] ?? '';
            if ($studioName !== '' || $address !== '') {
                $choices[] = [
                    'id' => null,
                    'name' => $studioName !== '' ? $studioName : 'Studio',
                    'street' => trim((string) $street),
                    'address' => $address,
                    'city' => trim((string) ($userDetail->city ?? '')),
                    'country' => trim((string) ($userDetail->country ?? '')),
                    'maps_link' => trim((string) ($userDetail->google_maps_link ?? '')),
                ];
            }
        }

        $choices = self::uniqueChoices($choices);
        $choices = self::withDistinctLabels($choices);

        return self::filterToStudiosWithAvailability($artistUserId, $choices);
    }

    /**
     * @param  list<array{id: int|null, name: string, address: string, maps_link?: string, city?: string, country?: string}>  $choices
     * @return list<array{id: int|null, name: string, address: string, maps_link?: string, city?: string, country?: string}>
     */
    private static function uniqueChoices(array $choices): array
    {
        $seen = [];
        $unique = [];
        foreach ($choices as $choice) {
            $id = $choice['id'] ?? null;
            $key = $id !== null
                ? 'id:'.(int) $id
                : 'na:'.mb_strtolower(trim((string) ($choice['name'] ?? ''))).'|'.mb_strtolower(trim((string) ($choice['address'] ?? '')));
            if (isset($seen[$key])) {
                continue;
            }
            $seen[$key] = true;
            $unique[] = $choice;
        }

        return $unique;
    }

    /**
     * When workplaces share a name, label adds city/country; if still ambiguous (same city),
     * street is included so different addresses stay distinct.
     *
     * @param  list<array{id: int|null, name: string, street?: string, address: string, maps_link?: string, city?: string, country?: string}>  $choices
     * @return list<array{id: int|null, name: string, label: string, address: string, maps_link: string, city?: string, country?: string, street?: string}>
     */
    private static function withDistinctLabels(array $choices): array
    {
        $labels = self::distinctLabelsForChoices($choices);

        $out = [];
        foreach ($choices as $i => $choice) {
            $name = trim((string) ($choice['name'] ?? ''));
            $out[] = [
                'id' => $choice['id'] ?? null,
                'name' => $name !== '' ? $name : 'Studio',
                'label' => $labels[$i] ?? ($name !== '' ? $name : 'Studio'),
                'address' => trim((string) ($choice['address'] ?? '')),
                'maps_link' => trim((string) ($choice['maps_link'] ?? '')),
                'city' => trim((string) ($choice['city'] ?? '')),
                'country' => trim((string) ($choice['country'] ?? '')),
                'street' => trim((string) ($choice['street'] ?? '')),
            ];
        }

        return $out;
    }

    /**
     * @param  list<array{name?: string, street?: string, address?: string, city?: string, country?: string}>  $choices
     * @return list<string>
     */
    private static function distinctLabelsForChoices(array $choices): array
    {
        $names = [];
        $cityLabels = [];
        $streetLabels = [];

        foreach ($choices as $i => $choice) {
            $name = trim((string) ($choice['name'] ?? ''));
            if ($name === '') {
                $name = 'Studio';
            }
            $names[$i] = $name;
            $cityPart = self::cityCountryLabel($choice);
            $street = self::streetLabel($choice);

            $cityLabels[$i] = $cityPart !== '' ? $name.' · '.$cityPart : $name;
            if ($street !== '' && $cityPart !== '') {
                $streetLabels[$i] = $name.' · '.$street.', '.$cityPart;
            } elseif ($street !== '') {
                $streetLabels[$i] = $name.' · '.$street;
            } else {
                $streetLabels[$i] = $cityLabels[$i];
            }
        }

        $nameCounts = [];
        foreach ($names as $name) {
            $key = mb_strtolower($name);
            $nameCounts[$key] = ($nameCounts[$key] ?? 0) + 1;
        }

        $labels = [];
        foreach ($names as $i => $name) {
            $labels[$i] = ($nameCounts[mb_strtolower($name)] ?? 0) > 1
                ? $cityLabels[$i]
                : $name;
        }

        $labelCounts = [];
        foreach ($labels as $label) {
            $key = mb_strtolower($label);
            $labelCounts[$key] = ($labelCounts[$key] ?? 0) + 1;
        }

        foreach ($labels as $i => $label) {
            if (($labelCounts[mb_strtolower($label)] ?? 0) > 1) {
                $labels[$i] = $streetLabels[$i];
            }
        }

        return $labels;
    }

    /**
     * @param  array{city?: string, country?: string, address?: string}  $choice
     */
    private static function cityCountryLabel(array $choice): string
    {
        $city = trim((string) ($choice['city'] ?? ''));
        $country = trim((string) ($choice['country'] ?? ''));
        $fromParts = implode(', ', array_filter([$city, $country]));
        if ($fromParts !== '') {
            return $fromParts;
        }

        $address = trim((string) ($choice['address'] ?? ''));
        if ($address === '') {
            return '';
        }

        $parts = array_values(array_filter(array_map('trim', explode(',', $address))));
        if (count($parts) >= 2) {
            return implode(', ', array_slice($parts, -2));
        }

        return $address;
    }

    /**
     * @param  array{street?: string, address?: string}  $choice
     */
    private static function streetLabel(array $choice): string
    {
        $street = trim((string) ($choice['street'] ?? ''));
        if ($street !== '') {
            return $street;
        }

        $address = trim((string) ($choice['address'] ?? ''));
        if ($address === '') {
            return '';
        }

        $parts = array_values(array_filter(array_map('trim', explode(',', $address))));

        return $parts[0] ?? '';
    }

    /**
     * Keep only studios that have weekly hours.
     * Empty/null studio_ids on an availability row = all active studios are bookable (legacy).
     *
     * @param  list<array{id: int|null, name: string, address: string, maps_link: string}>  $choices
     * @return list<array{id: int|null, name: string, address: string, maps_link: string}>
     */
    private static function filterToStudiosWithAvailability(int $artistUserId, array $choices): array
    {
        if ($choices === []) {
            return [];
        }

        $rows = Availability::query()
            ->where('user_id', $artistUserId)
            ->get(['studio_ids']);

        if ($rows->isEmpty()) {
            return [];
        }

        $bookableIds = [];
        $coversAll = false;
        foreach ($rows as $row) {
            $ids = is_array($row->studio_ids) ? $row->studio_ids : [];
            $normalized = array_values(array_unique(array_filter(array_map(
                static fn ($id) => ((int) $id) > 0 ? (int) $id : null,
                $ids
            ))));
            if ($normalized === []) {
                $coversAll = true;
                break;
            }
            foreach ($normalized as $id) {
                $bookableIds[$id] = true;
            }
        }

        if ($coversAll) {
            return $choices;
        }

        if ($bookableIds === []) {
            return [];
        }

        return array_values(array_filter(
            $choices,
            static function (array $choice) use ($bookableIds): bool {
                $id = $choice['id'] ?? null;
                if ($id === null) {
                    // Legacy nameless-id studio: keep only if somehow bookable globally (handled above).
                    return false;
                }

                return isset($bookableIds[(int) $id]);
            }
        ));
    }

    /**
     * Resolve `studios.id` for a booking.
     * Picker choice ids from forArtist() are user_studios.id; single studio auto-picks that workplace.
     */
    public static function resolveStudiosTableId(int $artistUserId, mixed $choiceId = null): ?int
    {
        if (! UserStudio::tableReady()) {
            return null;
        }

        $rows = UserStudio::activeLinksForUser($artistUserId);
        if ($rows->isEmpty()) {
            return null;
        }

        $choiceId = $choiceId !== null && $choiceId !== '' ? (int) $choiceId : 0;

        if ($rows->count() === 1) {
            $studioId = $rows->first()?->studio_id;

            return $studioId ? (int) $studioId : null;
        }

        if ($choiceId <= 0) {
            return null;
        }

        $byUserStudio = $rows->firstWhere('id', $choiceId);
        if ($byUserStudio && $byUserStudio->studio_id) {
            return (int) $byUserStudio->studio_id;
        }

        $byStudio = $rows->first(static fn ($row) => (int) ($row->studio_id ?? 0) === $choiceId);
        if ($byStudio && $byStudio->studio_id) {
            return (int) $byStudio->studio_id;
        }

        return null;
    }

    /**
     * user_studios.id the client picked (works for Not-on-Bookpay workplaces with no studios.id).
     *
     * @param  array<string, mixed>  $payload
     */
    public static function resolveUserStudioLinkIdFromPayload(int $artistUserId, array $payload): ?int
    {
        if (! UserStudio::tableReady()) {
            return null;
        }

        $rows = UserStudio::activeLinksForUser($artistUserId);
        if ($rows->isEmpty()) {
            return null;
        }

        if ($rows->count() === 1) {
            return (int) $rows->first()->id;
        }

        $choiceId = 0;
        if (isset($payload['studio_id']) && is_numeric($payload['studio_id'])) {
            $choiceId = (int) $payload['studio_id'];
        }

        $answers = is_array($payload['questions_answers'] ?? null) ? $payload['questions_answers'] : [];
        $selectedLabel = '';
        if (isset($answers['__studio__'])) {
            $raw = $answers['__studio__'];
            if (is_array($raw)) {
                if ($choiceId <= 0 && isset($raw['studio_id']) && is_numeric($raw['studio_id']) && (int) $raw['studio_id'] > 0) {
                    $choiceId = (int) $raw['studio_id'];
                }
                $selectedLabel = array_key_exists('answer', $raw)
                    ? trim((string) $raw['answer'])
                    : '';
            } else {
                $selectedLabel = trim((string) $raw);
            }
        }
        if ($choiceId <= 0 && isset($answers['__studio_id__']) && is_numeric($answers['__studio_id__'])) {
            $choiceId = (int) $answers['__studio_id__'];
        }

        if ($choiceId > 0) {
            $byLink = $rows->firstWhere('id', $choiceId);
            if ($byLink) {
                return (int) $byLink->id;
            }
            $byStudio = $rows->first(static fn ($row) => (int) ($row->studio_id ?? 0) === $choiceId);
            if ($byStudio) {
                return (int) $byStudio->id;
            }
        }

        if ($selectedLabel === '') {
            return null;
        }

        $built = [];
        foreach ($rows as $row) {
            $name = trim((string) ($row->studio?->name ?? $row->studio_name ?? ''));
            if ($name === '') {
                $name = 'Studio';
            }
            $city = trim((string) ($row->city ?? ''));
            $country = trim((string) ($row->country ?? ''));
            $street = trim(trim((string) ($row->street_number ?? '')).' '.trim((string) ($row->street_name ?? '')));
            if ($street === '') {
                $street = trim((string) ($row->studio_address ?? ''));
            }
            $address = implode(', ', array_filter([
                $street,
                implode(', ', array_filter([
                    trim((string) ($row->postal_code ?? '')),
                    $city,
                    $country,
                ])),
            ]));
            $built[] = [
                'row' => $row,
                'name' => $name,
                'street' => $street,
                'address' => $address,
                'city' => $city,
                'country' => $country,
            ];
        }

        $labels = self::distinctLabelsForChoices($built);
        $labelMatches = [];
        $nameMatches = [];
        foreach ($built as $i => $item) {
            $label = $labels[$i] ?? $item['name'];
            if (mb_strtolower($label) === mb_strtolower($selectedLabel)) {
                $labelMatches[] = $item['row'];
            } elseif (mb_strtolower($item['name']) === mb_strtolower($selectedLabel)) {
                $nameMatches[] = $item['row'];
            }
        }

        $pick = count($labelMatches) === 1
            ? $labelMatches[0]
            : (count($labelMatches) === 0 && count($nameMatches) === 1 ? $nameMatches[0] : null);

        return $pick ? (int) $pick->id : null;
    }

    /**
     * studios.id from a client payload when the workplace is linked to a Bookpay studio.
     *
     * @param  array<string, mixed>  $payload
     */
    public static function resolveStudiosTableIdFromPayload(int $artistUserId, array $payload): ?int
    {
        $linkId = self::resolveUserStudioLinkIdFromPayload($artistUserId, $payload);
        if ($linkId) {
            $rows = UserStudio::activeLinksForUser($artistUserId);
            $row = $rows->firstWhere('id', $linkId);
            if ($row && $row->studio_id) {
                return (int) $row->studio_id;
            }
        }

        return self::resolveStudiosTableId($artistUserId, $payload['studio_id'] ?? null);
    }

    /**
     * user_studios.id selected on a managed booking request (consultation_details / answers).
     */
    public static function resolveUserStudioLinkIdFromBookingRequest(\App\Models\BookingRequest $bookingRequest): ?int
    {
        $details = $bookingRequest->consultationDetailsArray() ?? [];
        if (isset($details['user_studio_id']) && is_numeric($details['user_studio_id']) && (int) $details['user_studio_id'] > 0) {
            $rows = UserStudio::activeLinksForUser((int) $bookingRequest->artist_id);
            $byLink = $rows->firstWhere('id', (int) $details['user_studio_id']);
            if ($byLink) {
                return (int) $byLink->id;
            }
        }

        $payload = [
            'studio_id' => $details['studio_id'] ?? null,
            'questions_answers' => is_array($bookingRequest->questions_answers) ? $bookingRequest->questions_answers : [],
        ];

        return self::resolveUserStudioLinkIdFromPayload((int) $bookingRequest->artist_id, $payload);
    }

    /**
     * studios.id selected on a managed booking request (consultation_details / answers).
     */
    public static function resolveStudiosTableIdFromBookingRequest(\App\Models\BookingRequest $bookingRequest): ?int
    {
        $linkId = self::resolveUserStudioLinkIdFromBookingRequest($bookingRequest);
        if ($linkId) {
            $row = UserStudio::activeLinksForUser((int) $bookingRequest->artist_id)->firstWhere('id', $linkId);
            if ($row && $row->studio_id) {
                return (int) $row->studio_id;
            }
        }

        $details = $bookingRequest->consultationDetailsArray() ?? [];
        $fromDetails = $details['studio_id'] ?? null;
        if ($fromDetails) {
            $resolved = self::resolveStudiosTableId((int) $bookingRequest->artist_id, $fromDetails);
            if ($resolved) {
                return $resolved;
            }
            if (is_numeric($fromDetails) && (int) $fromDetails > 0) {
                return (int) $fromDetails;
            }
        }

        return self::resolveStudiosTableIdFromPayload((int) $bookingRequest->artist_id, [
            'questions_answers' => is_array($bookingRequest->questions_answers) ? $bookingRequest->questions_answers : [],
        ]);
    }

    /**
     * Prepend mandatory studio picker when the artist has 2+ active studios.
     *
     * @param  list<array<string, mixed>>  $questions
     * @param  list<array{id: int|null, name: string, label?: string, address: string, maps_link: string}>  $studioChoices
     * @return list<array<string, mixed>>
     */
    public static function prependQuestionIfNeeded(array $questions, array $studioChoices, string $artistDisplay): array
    {
        if (count($studioChoices) <= 1) {
            return $questions;
        }

        $questions = array_values(array_filter(
            $questions,
            static function (array $q): bool {
                $qt = strtolower(trim((string) ($q['question_type'] ?? '')));
                $type = strtolower(trim((string) ($q['type'] ?? '')));
                $title = strtolower(trim((string) ($q['question'] ?? '')));

                return $qt !== 'studio'
                    && $type !== 'studio'
                    && ! str_contains($title, 'where do you want to get tattooed');
            }
        ));

        $optionLabels = array_map(
            static fn (array $c): string => trim((string) ($c['label'] ?? $c['name'] ?? 'Studio')),
            $studioChoices
        );

        array_unshift($questions, [
            'id' => '__studio__',
            'question' => 'Where do you want to get tattooed?',
            'description' => $artistDisplay.' works at more than one studio. Pick where you want your session.',
            'placeholder' => '',
            'type' => 'studio',
            'question_type' => 'studio',
            'is_required' => true,
            'is_active' => true,
            'options' => $optionLabels,
            'studio_options' => $studioChoices,
        ]);

        return $questions;
    }
}
