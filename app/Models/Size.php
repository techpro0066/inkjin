<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class Size extends Model
{
    protected $fillable = [
        'label',
        'cm_min',
        'cm_max',
        'in_min',
        'in_max',
        'status',
        'sort_order',
    ];

    protected $casts = [
        'cm_min' => 'float',
        'cm_max' => 'float',
        'in_min' => 'float',
        'in_max' => 'float',
        'sort_order' => 'integer',
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

    public function cmRangeLabel(): string
    {
        return $this->formatRange($this->cm_min, $this->cm_max, 'cm');
    }

    public function inRangeLabel(): string
    {
        return $this->formatRange($this->in_min, $this->in_max, 'in');
    }

    /**
     * Pill / picker label for booking questions, e.g. "Tiny (<5 cm)".
     */
    public function pillLabel(string $unit = 'cm'): string
    {
        $range = $unit === 'in' ? $this->inRangeLabel() : $this->cmRangeLabel();

        return $this->label.' ('.$range.')';
    }

    private function formatRange(?float $min, ?float $max, string $unit): string
    {
        // Open lower bound: Tiny (<5 cm)
        if ($min === null && $max !== null) {
            return '<'.$this->formatNumber($max).' '.$unit;
        }

        // Open upper bound: Extra Large (35+ cm)
        if ($min !== null && $max === null) {
            return $this->formatNumber($min).'+ '.$unit;
        }

        // Closed range: Small (5–10 cm)
        if ($min !== null && $max !== null) {
            return $this->formatNumber($min).'–'.$this->formatNumber($max).' '.$unit;
        }

        return '—';
    }

    private function formatNumber(float $value): string
    {
        return rtrim(rtrim(number_format($value, 2, '.', ''), '0'), '.');
    }
}
