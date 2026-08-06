<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SmartPricingSize extends Model
{
    protected $fillable = [
        'user_id',
        'kind',
        'size_min',
        'size_max',
        'min_price',
        'max_price',
        'sessions',
        'duration',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'size_min' => 'float',
            'size_max' => 'float',
            'min_price' => 'float',
            'max_price' => 'float',
            'duration' => 'float',
            'sort_order' => 'integer',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('sort_order')->orderBy('id');
    }
}
