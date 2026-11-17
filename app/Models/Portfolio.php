<?php

namespace App\Models;

use App\Models\Scopes\NotDeletedScope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Portfolio extends Model
{
    protected $fillable = [
        'user_id',
        'title',
        'description',
        'is_active',
        'is_deleted',
        'image',
        'primary_style',
        'other_styles',
        'placement',
        'size',
        'color',
        'tags',
        'sort_order',
        'instagram_media_id',
        'instagram_media_type',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'is_deleted' => 'boolean',
            'other_styles' => 'array',
            'tags' => 'array',
        ];
    }

    protected static function booted(): void
    {
        static::addGlobalScope(new NotDeletedScope);
    }

    /**
     * Display label for placement on public pages.
     */
    public function getPlacementLabelAttribute(): string
    {
        $value = trim((string) ($this->placement ?? ''));

        return $value !== '' ? $value : 'Anywhere';
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function scopeWithDeleted(Builder $query): Builder
    {
        return $query->withoutGlobalScope(NotDeletedScope::class);
    }

    public function scopeOnlyDeleted(Builder $query): Builder
    {
        return $query->withoutGlobalScope(NotDeletedScope::class)
            ->where($this->getTable().'.is_deleted', true);
    }

    /**
     * Custom requests that referenced this portfolio piece.
     */
    public function hasLinkedBookingsOrRequests(): bool
    {
        $id = (int) $this->id;
        if ($id <= 0) {
            return false;
        }

        return CustomRequest::query()
            ->where('artist_id', (int) $this->user_id)
            ->where(function ($q) use ($id) {
                $q->where('questions_answers->_portfolio_id', $id)
                    ->orWhere('questions_answers->_portfolio_id', (string) $id);
            })
            ->exists();
    }

    /**
     * Soft-delete: hide from dashboard / public, keep row for history.
     */
    public function markDeleted(): void
    {
        $this->forceFill([
            'is_active' => false,
            'is_deleted' => true,
        ])->save();
    }
}
