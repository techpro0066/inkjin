<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class QuestionSorting extends Model
{
    protected $table = 'question_sorting';

    protected $fillable = [
        'user_id',
        'question_id',
        'order',
        'is_active',
    ];

    protected $casts = [
        'order' => 'integer',
        'is_active' => 'boolean',
    ];

    public function question(): BelongsTo
    {
        return $this->belongsTo(Question::class, 'question_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Active booking questions for a public flow, filtered by form context.
     *
     * @return list<array<string, mixed>>
     */
    public static function activeQuestionsPayloadForArtist(int $userId, string $formContext): array
    {
        return self::query()
            ->where('user_id', $userId)
            ->where('is_active', true)
            ->whereHas('question', fn ($query) => $query->where('form_context', $formContext))
            ->with('question')
            ->orderBy('order')
            ->get()
            ->map(function (self $sorting) {
                $question = $sorting->question;
                if (! $question) {
                    return null;
                }

                return [
                    'id' => $sorting->question_id,
                    'question' => $question->question,
                    'description' => $question->description,
                    'placeholder' => $question->placeholder,
                    'type' => $question->type,
                    'is_required' => $question->is_required,
                    'is_active' => $sorting->is_active,
                    'options' => $question->options,
                ];
            })
            ->filter()
            ->values()
            ->all();
    }
}

