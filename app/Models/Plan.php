<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Plan extends Model
{
    protected $fillable = [
        'slug',
        'name',
        'status',
        'audience',
        'visibility',
        'badge',
        'prices',
        'commission_rate',
        'client_booking_fee',
        'included_artist_count',
        'extra_artist_monthly_price',
        'trial_days',
        'trial_fallback_plan',
        'signup_cutoff_date',
        'cutoff_replacement_plan',
        'layout',
        'tagline',
        'price_note',
        'list_title',
        'included_items',
        'button_label',
        'button_note',
        'terms_intro',
        'terms',
        'terms_note',
        'trial_button_label',
        'features',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'prices' => 'array',
            'client_booking_fee' => 'boolean',
            'included_artist_count' => 'integer',
            'extra_artist_monthly_price' => 'decimal:2',
            'trial_days' => 'integer',
            'signup_cutoff_date' => 'date:Y-m-d',
            'included_items' => 'array',
            'terms' => 'array',
            'features' => 'array',
            'sort_order' => 'integer',
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function toEditorArray(): array
    {
        $terms = array_map(
            static fn (array $term): string => trim((string) ($term['label'] ?? '')).' | '.trim((string) ($term['value'] ?? '')),
            $this->terms ?? []
        );

        return [
            'id' => $this->id,
            'slug' => $this->slug,
            'name' => $this->name,
            'status' => $this->status,
            'audience' => $this->audience,
            'visibility' => $this->visibility,
            'badge' => $this->badge ?? '',
            'prices' => $this->prices ?? ['EUR' => 0],
            'commission' => (float) $this->commission_rate,
            'bookingFee' => $this->client_booking_fee ? '1' : '0',
            'includedArtists' => $this->included_artist_count,
            'extraArtist' => $this->extra_artist_monthly_price === null ? null : (float) $this->extra_artist_monthly_price,
            'trialDays' => $this->trial_days,
            'trialFallback' => $this->trial_fallback_plan ?? '',
            'cutoffDate' => $this->signup_cutoff_date?->format('Y-m-d') ?? '',
            'afterCutoff' => $this->cutoff_replacement_plan ?? '',
            'layout' => $this->layout,
            'tagline' => $this->tagline ?? '',
            'priceNote' => $this->price_note ?? '',
            'listTitle' => $this->list_title ?? '',
            'includedItems' => implode("\n", $this->included_items ?? []),
            'buttonLabel' => $this->button_label ?? '',
            'buttonNote' => $this->button_note ?? '',
            'termsIntro' => $this->terms_intro ?? '',
            'termsList' => implode("\n", $terms),
            'termsNote' => $this->terms_note ?? '',
            'trialButton' => $this->trial_button_label ?? '',
            'features' => $this->features ?? [],
            'count' => 0,
        ];
    }
}
