<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StudioRequest extends Model
{
    public const STATUS_PENDING = 'pending';

    /** Offered to an artist; waiting for quote (accept) or send-back. No CustomRequest yet. */
    public const STATUS_ASSIGNED = 'assigned';

    /** Artist sent the offer back; studio can reassign. */
    public const STATUS_SENT_BACK = 'sent_back';

    public const STATUS_DECLINED = 'declined';

    public const STATUS_CANCELLED = 'cancelled';

    public const STATUS_MOVED_TO_CUSTOM = 'moved_to_custom';

    public const STATUS_MOVED_TO_BOOKING = 'moved_to_booking';

    public function isAwaitingArtistDecision(): bool
    {
        return $this->status === self::STATUS_ASSIGNED && empty($this->custom_request_id);
    }

    public function canStudioAssign(): bool
    {
        if (! empty($this->custom_request_id)) {
            return false;
        }

        return in_array($this->status, [
            self::STATUS_PENDING,
            self::STATUS_ASSIGNED,
            self::STATUS_SENT_BACK,
        ], true);
    }

    public function canStudioWithdraw(): bool
    {
        if (! empty($this->custom_request_id)) {
            return false;
        }

        // Only while an offer is out to an artist. Pending = studio hasn't assigned yet.
        return $this->status === self::STATUS_ASSIGNED;
    }

    protected $fillable = [
        'studio_id',
        'user_id',
        'assigned_artist_id',
        'custom_request_id',
        'booking_id',
        'status',
        'source',
        'source_path',
        'client_name',
        'client_email',
        'client_phone',
        'questions_answers',
        'reference_images',
        'preferences',
        'preferred_days',
        'avoid_dates',
        'how_much_flexible',
        'urgency',
        'anything_else_notes',
        'reason_decline',
        'send_back_history',
    ];

    protected function casts(): array
    {
        return [
            'questions_answers' => 'array',
            'reference_images' => 'array',
            'preferences' => 'array',
            'preferred_days' => 'array',
            'send_back_history' => 'array',
        ];
    }

    /**
     * Append a send-back / withdraw entry to the JSON history log.
     *
     * @param  'artist_send_back'|'studio_withdraw'  $action
     * @return list<array{action:string,artist_id:?int,artist_name:?string,actor_name:string,reason:?string,at:string}>
     */
    public function appendSendBackHistory(
        string $action,
        ?string $reason = null,
        ?int $artistId = null,
        ?string $artistName = null,
        ?string $actorName = null,
    ): array {
        $history = is_array($this->send_back_history) ? array_values($this->send_back_history) : [];
        $label = $actorName !== null && trim($actorName) !== ''
            ? trim($actorName)
            : ($artistName !== null && trim($artistName) !== '' ? trim($artistName) : 'Studio');

        $history[] = [
            'action' => $action,
            'artist_id' => $artistId,
            'artist_name' => $artistName !== null && trim($artistName) !== '' ? trim($artistName) : null,
            'actor_name' => $label,
            'reason' => $reason !== null && trim($reason) !== '' ? trim($reason) : null,
            'at' => now()->toIso8601String(),
        ];
        $this->send_back_history = $history;

        return $history;
    }

    /**
     * Latest artist who sent this request back (from history), if any.
     */
    public function latestSendBackArtistName(): ?string
    {
        $history = is_array($this->send_back_history) ? $this->send_back_history : [];
        for ($i = count($history) - 1; $i >= 0; $i--) {
            $entry = $history[$i] ?? null;
            if (! is_array($entry)) {
                continue;
            }
            $action = (string) ($entry['action'] ?? 'artist_send_back');
            if ($action !== 'artist_send_back') {
                continue;
            }
            $name = trim((string) ($entry['artist_name'] ?? $entry['actor_name'] ?? ''));

            return $name !== '' ? $name : null;
        }

        return null;
    }

    public function studio(): BelongsTo
    {
        return $this->belongsTo(Studio::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function assignedArtist(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_artist_id');
    }

    public function customRequest(): BelongsTo
    {
        return $this->belongsTo(CustomRequest::class);
    }

    public function booking(): BelongsTo
    {
        return $this->belongsTo(Booking::class);
    }

    /**
     * Same shape as CustomRequest::availabilityStructured() for the artist quote modal.
     *
     * @return array{
     *     preferredDates: list<array{preference:int,date:string,dateLabel:string,times:list<string>}>,
     *     preferredDays: list<string>,
     *     flexibility: string,
     *     urgency: string,
     *     avoidDates: string,
     *     sessionGap: string
     * }
     */
    public function availabilityStructured(): array
    {
        $preferredDates = [];
        $preferences = is_array($this->preferences) ? $this->preferences : [];
        foreach ($preferences as $index => $pref) {
            if (! is_array($pref)) {
                continue;
            }
            $date = trim((string) ($pref['date'] ?? ''));
            if ($date === '') {
                continue;
            }
            try {
                $dateLabel = Carbon::parse($date)->format('l, M j, Y');
            } catch (\Throwable) {
                $dateLabel = $date;
            }
            $preferredDates[] = [
                'preference' => (int) ($pref['preference'] ?? ($index + 1)),
                'date' => $date,
                'dateLabel' => $dateLabel,
                'times' => array_values(array_filter((array) ($pref['times_of_day'] ?? []))),
            ];
        }

        return [
            'preferredDates' => $preferredDates,
            'preferredDays' => array_values(array_filter((array) ($this->preferred_days ?? []))),
            'flexibility' => trim((string) ($this->how_much_flexible ?? '')),
            'urgency' => trim((string) ($this->urgency ?? '')),
            'avoidDates' => trim((string) ($this->avoid_dates ?? '')),
            'sessionGap' => '',
        ];
    }
}
