<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Question extends Model
{
    protected $fillable = [
        'user_id',
        'question',
        'description',
        'placeholder',
        'type',
        'form_context',
        'options',
        'max_images',
        'is_required',
    ];

    protected $casts = [
        'options' => 'array',
        'max_images' => 'integer',
        'is_required' => 'boolean',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function sorting(): HasOne
    {
        return $this->hasOne('App\\Models\\QuestionSorting', 'question_id');
    }
}
