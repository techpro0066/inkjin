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
     *
     * @return list<array{id: int|null, name: string, address: string, maps_link: string}>
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
                $cityLine = implode(', ', array_filter([
                    trim((string) ($row->postal_code ?? '')),
                    trim((string) ($row->city ?? '')),
                    trim((string) ($row->country ?? '')),
                ]));
                $address = implode(', ', array_filter([$street, $cityLine]));
                if ($name === '' && $address === '') {
                    continue;
                }
                $choices[] = [
                    'id' => (int) $row->id,
                    'name' => $name !== '' ? $name : 'Studio',
                    'address' => $address,
                    'maps_link' => trim((string) ($row->google_maps_link ?? '')),
                ];
            }
        }

        if ($choices === []) {
            $studioName = trim((string) ($userDetail->studio_name ?? ''));
            $addressLines = $userDetail->studioLocationLines();
            array_shift($addressLines);
            $address = implode(', ', array_filter($addressLines));
            if ($studioName !== '' || $address !== '') {
                $choices[] = [
                    'id' => null,
                    'name' => $studioName !== '' ? $studioName : 'Studio',
                    'address' => $address,
                    'maps_link' => trim((string) ($userDetail->google_maps_link ?? '')),
                ];
            }
        }

        $seen = [];
        $unique = [];
        foreach ($choices as $choice) {
            $key = mb_strtolower($choice['name']);
            if (isset($seen[$key])) {
                continue;
            }
            $seen[$key] = true;
            $unique[] = $choice;
        }

        return self::filterToStudiosWithAvailability($artistUserId, $unique);
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
     * @param  array<string, mixed>  $payload
     */
    public static function resolveStudiosTableIdFromPayload(int $artistUserId, array $payload): ?int
    {
        $resolved = self::resolveStudiosTableId($artistUserId, $payload['studio_id'] ?? null);
        if ($resolved) {
            return $resolved;
        }

        if (! UserStudio::tableReady()) {
            return null;
        }

        $rows = UserStudio::activeLinksForUser($artistUserId);
        if ($rows->count() === 1) {
            $studioId = $rows->first()?->studio_id;

            return $studioId ? (int) $studioId : null;
        }

        $answers = is_array($payload['questions_answers'] ?? null) ? $payload['questions_answers'] : [];
        $selectedName = '';
        if (isset($answers['__studio__'])) {
            $raw = $answers['__studio__'];
            if (is_array($raw) && array_key_exists('answer', $raw)) {
                $selectedName = trim((string) $raw['answer']);
            } else {
                $selectedName = trim((string) $raw);
            }
        }
        if ($selectedName === '') {
            return null;
        }

        foreach ($rows as $row) {
            $name = trim((string) ($row->studio?->name ?? $row->studio_name ?? ''));
            if ($name !== '' && mb_strtolower($name) === mb_strtolower($selectedName) && $row->studio_id) {
                return (int) $row->studio_id;
            }
        }

        return null;
    }

    /**
     * Prepend mandatory studio picker when the artist has 2+ active studios.
     *
     * @param  list<array<string, mixed>>  $questions
     * @param  list<array{id: int|null, name: string, address: string, maps_link: string}>  $studioChoices
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

        array_unshift($questions, [
            'id' => '__studio__',
            'question' => 'Where do you want to get tattooed?',
            'description' => $artistDisplay.' works at more than one studio. Pick where you want your session.',
            'placeholder' => '',
            'type' => 'studio',
            'question_type' => 'studio',
            'is_required' => true,
            'is_active' => true,
            'options' => array_column($studioChoices, 'name'),
            'studio_options' => $studioChoices,
        ]);

        return $questions;
    }
}
