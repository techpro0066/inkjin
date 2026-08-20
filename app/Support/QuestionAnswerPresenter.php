<?php

namespace App\Support;

class QuestionAnswerPresenter
{
    /**
     * Normalize stored questions_answers into a list of display rows.
     *
     * @param  mixed  $questionsAnswers
     * @return array<int, array{question: string, type: string, answer: mixed, images: array<int, string>}>
     */
    public static function rows(mixed $questionsAnswers): array
    {
        if (! is_array($questionsAnswers)) {
            return [];
        }

        $rows = [];

        foreach ($questionsAnswers as $key => $entry) {
            if ($key === '_contact') {
                continue;
            }

            if (is_array($entry) && array_key_exists('question', $entry)) {
                $question = trim((string) ($entry['question'] ?? ''));
                $type = strtolower(trim((string) ($entry['type'] ?? '')));
                $answer = $entry['answer'] ?? null;
                if ($question === '' && ($answer === null || $answer === '')) {
                    continue;
                }
                $rows[] = [
                    'question' => $question !== '' ? $question : ('Question #'.(string) $key),
                    'type' => $type,
                    'answer' => $answer,
                    'images' => self::imageUrls($answer, $type),
                ];
                continue;
            }

            // Legacy / keyed payloads without structured fields.
            $fallbackQuestion = is_string($key) && ! ctype_digit($key)
                ? $key
                : ('Question #'.(string) $key);
            $rows[] = [
                'question' => $fallbackQuestion,
                'type' => '',
                'answer' => $entry,
                'images' => self::imageUrls($entry, ''),
            ];
        }

        return $rows;
    }

    /**
     * @return array<int, string>
     */
    public static function imageUrls(mixed $answer, string $type = ''): array
    {
        $urls = [];

        if (is_array($answer)) {
            foreach ($answer as $item) {
                if (self::isImageUrl($item)) {
                    $urls[] = trim((string) $item);
                }
            }

            return $urls;
        }

        if (self::isImageUrl($answer) || $type === 'image') {
            $single = trim((string) ($answer ?? ''));
            if (self::isImageUrl($single)) {
                $urls[] = $single;
            }
        }

        return $urls;
    }

    public static function formatAnswer(mixed $answer, string $type = ''): string
    {
        $images = self::imageUrls($answer, $type);
        if ($images !== []) {
            $count = count($images);
            $labels = [];
            for ($i = 1; $i <= $count; $i++) {
                $labels[] = 'Photo '.$i;
            }

            return implode(', ', $labels);
        }

        if (is_bool($answer)) {
            return $answer ? 'Yes' : 'No';
        }

        if (is_array($answer)) {
            return implode(', ', array_map(static fn ($item) => (string) $item, $answer));
        }

        $text = trim((string) ($answer ?? ''));

        return $text !== '' ? $text : '—';
    }

    private static function isImageUrl(mixed $value): bool
    {
        $text = trim((string) ($value ?? ''));
        if ($text === '') {
            return false;
        }

        return (bool) preg_match('#^https?://#i', $text)
            || str_contains($text, '/uploads/')
            || str_starts_with($text, 'uploads/');
    }
}
