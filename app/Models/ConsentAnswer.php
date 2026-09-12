<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ConsentAnswer extends Model
{
    public const STATUS_PENDING = 'pending';

    public const STATUS_SENT = 'sent';

    public const STATUS_COMPLETED = 'completed';

    public const STATUS_CANCELLED = 'cancelled';

    public const STATUS_EXPIRED = 'expired';

    protected $fillable = [
        'booking_id',
        'artist_user_id',
        'client_user_id',
        'token',
        'status',
        'send_at',
        'sent_at',
        'completed_at',
        'age_verified_by_artist',
        'full_name',
        'date_of_birth',
        'phone',
        'emergency_contact',
        'guardian_name',
        'guardian_relationship',
        'guardian_id_reference',
        'guardian_signature',
        'accepted_risks',
        'accepted_health_consent',
        'accepted_aftercare',
        'accepted_data_notice',
        'accepted_photo',
        'typed_signature',
        'form_language',
        'answers',
    ];

    protected $casts = [
        'send_at' => 'datetime',
        'sent_at' => 'datetime',
        'completed_at' => 'datetime',
        'age_verified_by_artist' => 'boolean',
        'date_of_birth' => 'date',
        'accepted_risks' => 'boolean',
        'accepted_health_consent' => 'boolean',
        'accepted_aftercare' => 'boolean',
        'accepted_data_notice' => 'boolean',
        'accepted_photo' => 'boolean',
        'answers' => 'array',
    ];

    public function booking(): BelongsTo
    {
        return $this->belongsTo(Booking::class);
    }

    public function artist(): BelongsTo
    {
        return $this->belongsTo(User::class, 'artist_user_id');
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(User::class, 'client_user_id');
    }

    public function isCompleted(): bool
    {
        return $this->status === self::STATUS_COMPLETED;
    }

    public function isOpenForClient(): bool
    {
        return in_array($this->status, [self::STATUS_PENDING, self::STATUS_SENT], true);
    }

    public function publicUrl(): string
    {
        return route('public.consent', ['token' => $this->token]);
    }

    /**
     * Payload for artist booking “View detail” UI.
     *
     * @return array<string, mixed>
     */
    public function toArtistDetailArray(): array
    {
        $answers = is_array($this->answers) ? $this->answers : [];

        return [
            'status' => $this->status,
            'completed_at' => $this->completed_at?->timezone(config('app.timezone'))->format('M j, Y · g:i A'),
            'age_verified_by_artist' => (bool) $this->age_verified_by_artist,
            'full_name' => $this->full_name,
            'date_of_birth' => $this->date_of_birth?->format('M j, Y'),
            'phone' => $this->phone,
            'emergency_contact' => $this->emergency_contact,
            'guardian_name' => $this->guardian_name,
            'guardian_relationship' => $this->guardian_relationship,
            'guardian_id_reference' => $this->guardian_id_reference,
            'guardian_signature' => $this->guardian_signature,
            'accepted_risks' => (bool) $this->accepted_risks,
            'accepted_health_consent' => (bool) $this->accepted_health_consent,
            'accepted_aftercare' => (bool) $this->accepted_aftercare,
            'accepted_data_notice' => (bool) $this->accepted_data_notice,
            'accepted_photo' => (bool) $this->accepted_photo,
            'typed_signature' => $this->typed_signature,
            'form_language' => $this->form_language,
            'health' => array_values(is_array($answers['health'] ?? null) ? $answers['health'] : []),
            'risk' => array_values(is_array($answers['risk'] ?? null) ? $answers['risk'] : []),
            'aftercare' => array_values(is_array($answers['aftercare'] ?? null) ? $answers['aftercare'] : []),
            'other_health_note' => trim((string) ($answers['other_health_note'] ?? '')),
        ];
    }
}
