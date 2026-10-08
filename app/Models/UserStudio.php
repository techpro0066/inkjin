<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Schema;

class UserStudio extends Model
{
    public const STATUS_INACTIVE = 0;

    public const STATUS_ACTIVE = 1;

    public const STATUS_JOIN_PENDING = 2;

    public const STATUS_SPLIT_PROPOSED = 3;

    public const CONNECTION_NOT_ON_BOOKPAY = 'not_on_bookpay';

    public const CONNECTION_ON_BOOKPAY = 'on_bookpay';

    public const CONNECTION_NOT_CONNECTED = 'not_connected';

    public const RELATIONSHIP_TYPES = [
        'co_owner',
        'resident',
        'collective_member',
        'apprentice',
        'other',
    ];

    protected $table = 'user_studios';

    protected $fillable = [
        'user_id',
        'studio_id',
        'status',
        'connection_type',
        'relationship',
        'workspace_type',
        'studio_name',
        'studio_address',
        'street_name',
        'street_number',
        'city',
        'state',
        'postal_code',
        'country',
        'google_maps_link',
        'payout',
        'revenue_split',
        'proposed_revenue_split',
    ];

    protected $casts = [
        'status' => 'integer',
        'payout' => 'boolean',
        'revenue_split' => 'integer',
        'proposed_revenue_split' => 'integer',
    ];

    public static function tableReady(): bool
    {
        return Schema::hasTable('user_studios');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function studio(): BelongsTo
    {
        return $this->belongsTo(Studio::class);
    }

    public function scopeActive($query)
    {
        return $query->where('status', self::STATUS_ACTIVE);
    }

    public function scopeForPayout($query)
    {
        return $query->where('payout', true)->whereNotNull('studio_id');
    }

    public static function activeForUser(int $userId): ?self
    {
        if (! self::tableReady()) {
            return null;
        }

        return static::query()
            ->where('user_id', $userId)
            ->active()
            ->orderByDesc('id')
            ->first();
    }

    /**
     * @return \Illuminate\Database\Eloquent\Collection<int, self>
     */
    public static function activeLinksForUser(int $userId)
    {
        if (! self::tableReady()) {
            return static::query()->whereRaw('0 = 1')->get();
        }

        // Oldest first so previous studios appear above newly added ones (09c6).
        return static::query()
            ->with('studio')
            ->where('user_id', $userId)
            ->where('status', self::STATUS_ACTIVE)
            ->orderBy('id')
            ->get();
    }

    public static function pendingJoinForUser(int $userId): ?self
    {
        return self::pendingJoinsForUser($userId)->first();
    }

    /**
     * @return \Illuminate\Database\Eloquent\Collection<int, self>
     */
    public static function pendingJoinsForUser(int $userId)
    {
        if (! self::tableReady()) {
            return static::query()->whereRaw('0 = 1')->get();
        }

        return static::query()
            ->with('studio')
            ->where('user_id', $userId)
            ->where('status', self::STATUS_JOIN_PENDING)
            ->orderBy('id')
            ->get();
    }

    public static function splitProposedForUser(int $userId): ?self
    {
        if (! self::tableReady()) {
            return null;
        }

        return static::query()
            ->with('studio')
            ->where('user_id', $userId)
            ->where('status', self::STATUS_SPLIT_PROPOSED)
            ->orderByDesc('id')
            ->first();
    }

    /**
     * Inactive workplaces the artist left — excludes studios they still work at.
     *
     * @return \Illuminate\Database\Eloquent\Collection<int, self>
     */
    public static function pastLinksForUser(int $userId)
    {
        if (! self::tableReady()) {
            return static::query()->whereRaw('0 = 1')->get();
        }

        $actives = self::activeLinksForUser($userId);
        $activeStudioIds = $actives
            ->pluck('studio_id')
            ->filter(fn ($id) => ! empty($id))
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values()
            ->all();
        $activeNames = $actives
            ->map(function (self $link) {
                $name = trim((string) ($link->studio?->name ?: $link->studio_name ?: ''));

                return $name !== '' ? mb_strtolower($name) : null;
            })
            ->filter()
            ->unique()
            ->values()
            ->all();

        return static::query()
            ->with('studio')
            ->where('user_id', $userId)
            ->where('status', self::STATUS_INACTIVE)
            ->orderByDesc('updated_at')
            ->orderByDesc('id')
            ->get()
            ->reject(function (self $link) use ($activeStudioIds, $activeNames) {
                if ($link->studio_id && in_array((int) $link->studio_id, $activeStudioIds, true)) {
                    return true;
                }

                $name = trim((string) ($link->studio?->name ?: $link->studio_name ?: ''));
                if ($name !== '' && in_array(mb_strtolower($name), $activeNames, true)) {
                    return true;
                }

                return false;
            })
            ->values();
    }

    public static function payoutLinkForUser(int $userId): ?self
    {
        if (! self::tableReady()) {
            return null;
        }

        return static::query()
            ->where('user_id', $userId)
            ->active()
            ->forPayout()
            ->orderByDesc('id')
            ->first();
    }

    /**
     * Prefer reactivating an inactive / pending join row instead of inserting a duplicate.
     */
    public static function findReusableForUser(int $userId, ?int $fromLinkId = null, ?int $studioId = null): ?self
    {
        if (! self::tableReady()) {
            return null;
        }

        if ($fromLinkId) {
            $byId = static::query()
                ->where('user_id', $userId)
                ->where('id', $fromLinkId)
                ->whereIn('status', [self::STATUS_INACTIVE, self::STATUS_JOIN_PENDING])
                ->first();
            if ($byId) {
                if ($studioId) {
                    // Bookpay rejoin: only reuse when the past row is for this studio.
                    if ($byId->studio_id && (int) $byId->studio_id === (int) $studioId) {
                        return $byId;
                    }
                } else {
                    return $byId;
                }
            }
        }

        if ($studioId) {
            return static::query()
                ->where('user_id', $userId)
                ->where('studio_id', $studioId)
                ->whereIn('status', [self::STATUS_INACTIVE, self::STATUS_JOIN_PENDING])
                ->orderByDesc('id')
                ->first();
        }

        return null;
    }

    /**
     * Upsert the artist's active workplace row from onboarding / settings studio step.
     *
     * @param  array{
     *   studio_name?: ?string,
     *   studio_address?: ?string,
     *   street_name?: ?string,
     *   street_number?: ?string,
     *   city?: ?string,
     *   state?: ?string,
     *   postal_code?: ?string,
     *   country?: ?string,
     *   google_maps_link?: ?string,
     *   workspace_type?: ?string,
     *   relationship?: ?string|null,
     * }  $data
     */
    public static function syncWorkplace(int $userId, array $data, bool $clearRelationship = false, bool $forceNew = false): ?self
    {
        if (! self::tableReady()) {
            return null;
        }

        $row = ($forceNew ? null : self::activeForUser($userId)) ?? new static([
            'user_id' => $userId,
            'status' => self::STATUS_ACTIVE,
            'connection_type' => self::CONNECTION_NOT_ON_BOOKPAY,
            'payout' => false,
        ]);

        foreach ([
            'studio_name',
            'studio_address',
            'street_name',
            'street_number',
            'city',
            'state',
            'postal_code',
            'country',
            'google_maps_link',
            'workspace_type',
        ] as $field) {
            if (array_key_exists($field, $data)) {
                $row->{$field} = self::nullableString($data[$field] ?? null);
            }
        }

        if ($clearRelationship) {
            $row->relationship = null;
        } elseif (array_key_exists('relationship', $data)) {
            $row->relationship = self::validRelationship($data['relationship'] ?? null);
        }

        // Workplace address without a Bookpay studio link.
        if (empty($row->studio_id)) {
            $row->connection_type = self::CONNECTION_NOT_ON_BOOKPAY;
        }

        $row->status = self::STATUS_ACTIVE;
        $row->save();

        return $row;
    }

    /**
     * Update an existing (usually inactive) workplace row in place.
     *
     * @param  array{
     *   studio_name?: ?string,
     *   studio_address?: ?string,
     *   street_name?: ?string,
     *   street_number?: ?string,
     *   city?: ?string,
     *   state?: ?string,
     *   postal_code?: ?string,
     *   country?: ?string,
     *   google_maps_link?: ?string,
     *   workspace_type?: ?string,
     *   relationship?: ?string
     * }  $data
     */
    public static function syncWorkplaceOnto(self $row, array $data, bool $clearRelationship = false): self
    {
        foreach ([
            'studio_name',
            'studio_address',
            'street_name',
            'street_number',
            'city',
            'state',
            'postal_code',
            'country',
            'google_maps_link',
            'workspace_type',
        ] as $field) {
            if (array_key_exists($field, $data)) {
                $row->{$field} = self::nullableString($data[$field] ?? null);
            }
        }

        if ($clearRelationship) {
            $row->relationship = null;
        } elseif (array_key_exists('relationship', $data)) {
            $row->relationship = self::validRelationship($data['relationship'] ?? null);
        }

        if (empty($row->studio_id)) {
            $row->connection_type = self::CONNECTION_NOT_ON_BOOKPAY;
        }

        $row->status = self::STATUS_ACTIVE;
        $row->save();

        return $row;
    }

    /**
     * Link a Bookpay/stub studio for payout (artist chose studio_account).
     *
     * @param  array{revenue_split?: mixed, relationship?: mixed}  $validated
     */
    public static function syncStudioPayout(int $userId, Studio $studio, array $validated = [], bool $forceNew = false): ?self
    {
        if (! self::tableReady()) {
            return null;
        }

        $row = ($forceNew ? null : self::activeForUser($userId)) ?? new static([
            'user_id' => $userId,
            'status' => self::STATUS_ACTIVE,
            'connection_type' => self::CONNECTION_NOT_CONNECTED,
        ]);

        // Only one active payout link per artist.
        static::query()
            ->where('user_id', $userId)
            ->where('payout', true)
            ->when($row->exists, fn ($q) => $q->where('id', '!=', $row->id))
            ->update(['payout' => false]);

        $row->studio_id = $studio->id;
        $row->payout = true;
        $row->status = self::STATUS_ACTIVE;
        $row->connection_type = ! empty($studio->user_id)
            ? self::CONNECTION_ON_BOOKPAY
            : self::CONNECTION_NOT_CONNECTED;

        if (self::nullableString($row->studio_name) === null) {
            $row->studio_name = self::nullableString($studio->name) ?? 'Studio';
        }

        if (array_key_exists('revenue_split', $validated) && $validated['revenue_split'] !== null && $validated['revenue_split'] !== '') {
            $row->revenue_split = (int) $validated['revenue_split'];
        } elseif (array_key_exists('studio_revenue_artist_percent', $validated)
            && $validated['studio_revenue_artist_percent'] !== null
            && $validated['studio_revenue_artist_percent'] !== '') {
            $row->revenue_split = (int) $validated['studio_revenue_artist_percent'];
        }

        $relationship = $validated['relationship'] ?? $validated['studio_relationship_type'] ?? null;
        if (! empty($relationship)) {
            $row->relationship = self::validRelationship((string) $relationship);
        }

        $row->save();

        return $row;
    }

    /**
     * Artist switched to artist payout — keep workplace row, clear studio payout link.
     */
    public static function clearStudioPayout(int $userId): void
    {
        if (! self::tableReady()) {
            return;
        }

        static::query()
            ->where('user_id', $userId)
            ->where(function ($q) {
                $q->where('payout', true)->orWhereNotNull('studio_id');
            })
            ->update([
                'payout' => false,
                'studio_id' => null,
                'connection_type' => self::CONNECTION_NOT_ON_BOOKPAY,
                'updated_at' => now(),
            ]);
    }

    public static function applySplitAndRelationship(int $userId, array $validated): ?self
    {
        if (! self::tableReady()) {
            return null;
        }

        $row = self::payoutLinkForUser($userId) ?? self::activeForUser($userId);
        if (! $row) {
            return null;
        }

        if (array_key_exists('studio_revenue_artist_percent', $validated)
            && $validated['studio_revenue_artist_percent'] !== null
            && $validated['studio_revenue_artist_percent'] !== '') {
            $row->revenue_split = (int) $validated['studio_revenue_artist_percent'];
        }

        if (! empty($validated['studio_relationship_type'])) {
            $row->relationship = self::validRelationship((string) $validated['studio_relationship_type']);
        }

        $row->save();

        return $row;
    }

    private static function nullableString(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $trimmed = trim((string) $value);

        return $trimmed === '' ? null : $trimmed;
    }

    private static function validRelationship(?string $value): ?string
    {
        return $value && in_array($value, self::RELATIONSHIP_TYPES, true) ? $value : null;
    }
}
