<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class Placement extends Model
{
    protected $fillable = [
        'name',
        'status',
        'sort_order',
        'appear_on_question',
    ];

    protected $casts = [
        'sort_order' => 'integer',
        'appear_on_question' => 'boolean',
    ];

    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('sort_order')->orderBy('id');
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', 'active');
    }

    public function isActive(): bool
    {
        return $this->status === 'active';
    }
}
