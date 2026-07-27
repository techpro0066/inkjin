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
        $styleOptions = self::catalogOptionsForQuestionType(Style::class);
        $placementOptions = self::catalogOptionsForQuestionType(Placement::class);

        return self::query()
            ->where('user_id', $userId)
            ->where('is_active', true)
            ->whereHas('question', fn ($query) => $query->where('form_context', $formContext))
            ->with('question')
            ->orderBy('order')
            ->get()
            ->map(function (self $sorting) use ($styleOptions, $placementOptions) {
                $question = $sorting->question;
                if (! $question) {
                    return null;
                }

                $options = match ($question->type) {
                    'style' => ! empty($styleOptions) ? $styleOptions : $question->options,
                    'placement' => ! empty($placementOptions) ? $placementOptions : $question->options,
                    default => $question->options,
                };

                return [
                    'id' => $sorting->question_id,
                    'question' => $question->question,
                    'description' => $question->description,
                    'placeholder' => $question->placeholder,
                    'type' => $question->type,
                    'is_required' => $question->is_required,
                    'is_active' => $sorting->is_active,
                    'options' => $options,
                ];
            })
            ->filter()
            ->values()
            ->all();
    }

    /**
     * Visible catalog options for style/placement questions, with trailing "Other"
     * when active hidden (appear_on_question = false) entries exist.
     *
     * @param  class-string<Style|Placement>  $modelClass
     * @return list<string>
     */
    private static function catalogOptionsForQuestionType(string $modelClass): array
    {
        $activeQuery = $modelClass::query()->active();

        $options = (clone $activeQuery)
            ->where('appear_on_question', true)
            ->ordered()
            ->pluck('name')
            ->values()
            ->all();

        $hasHiddenActive = (clone $activeQuery)
            ->where('appear_on_question', false)
            ->exists();

        if ($hasHiddenActive && ! in_array('Other', $options, true)) {
            $options[] = 'Other';
        }

        return $options;
    }
}

