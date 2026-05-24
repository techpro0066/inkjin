<?php

namespace App\Support;

use Carbon\Carbon;
use Illuminate\Validation\Validator;

class ArtistSessionSlots
{
    /**
     * @param  array<int, array<string, mixed>>  $slots
     * @return array<int, array{date: string, ranges: array<int, array{from: string, to: string}>}>
     */
    public static function normalize(array $slots): array
    {
        $normalized = [];

        foreach ($slots as $slot) {
            if (!is_array($slot)) {
                continue;
            }

            $date = trim((string) ($slot['date'] ?? ''));
            if ($date === '') {
                continue;
            }

            $ranges = [];
            foreach ((array) ($slot['ranges'] ?? []) as $range) {
                if (!is_array($range)) {
                    continue;
                }
                $from = self::normalizeTime((string) ($range['from'] ?? ''));
                $to = self::normalizeTime((string) ($range['to'] ?? ''));
                if ($from === '' || $to === '') {
                    continue;
                }
                $ranges[] = ['from' => $from, 'to' => $to];
            }

            if ($ranges !== []) {
                $normalized[] = ['date' => $date, 'ranges' => $ranges];
            }
        }

        return $normalized;
    }

    /**
     * @param  array<int, array<string, mixed>>  $slots
     */
    public static function validateCollection(Validator $validator, array $slots, string $prefix, string $label): void
    {
        $dates = [];

        foreach ($slots as $index => $slot) {
            $date = (string) ($slot['date'] ?? '');
            if ($date !== '') {
                if (isset($dates[$date])) {
                    $validator->errors()->add(
                        "{$prefix}.{$index}.date",
                        "{$label}: each date can only appear once."
                    );
                }
                $dates[$date] = true;
            }

            $ranges = is_array($slot['ranges'] ?? null) ? $slot['ranges'] : [];
            $parsed = [];

            foreach ($ranges as $rangeIndex => $range) {
                $from = (string) ($range['from'] ?? '');
                $to = (string) ($range['to'] ?? '');
                $start = self::timeToMinutes($from);
                $end = self::timeToMinutes($to);

                if ($start === null || $end === null || $start >= $end) {
                    $validator->errors()->add(
                        "{$prefix}.{$index}.ranges.{$rangeIndex}.from",
                        "{$label}: from time must be earlier than to time."
                    );
                    continue;
                }

                $parsed[] = ['start' => $start, 'end' => $end, 'rangeIndex' => $rangeIndex];
            }

            usort($parsed, fn (array $a, array $b) => $a['start'] <=> $b['start']);

            for ($i = 0; $i < count($parsed); $i++) {
                for ($j = $i + 1; $j < count($parsed); $j++) {
                    if ($parsed[$i]['start'] < $parsed[$j]['end'] && $parsed[$j]['start'] < $parsed[$i]['end']) {
                        $validator->errors()->add(
                            "{$prefix}.{$index}.ranges.{$parsed[$j]['rangeIndex']}.from",
                            "{$label}: time windows on the same date cannot overlap."
                        );
                        break 2;
                    }
                }
            }
        }
    }

    private static function normalizeTime(string $value): string
    {
        if ($value === '') {
            return '';
        }

        return strlen($value) >= 5 ? substr($value, 0, 5) : $value;
    }

    private static function timeToMinutes(string $value): ?int
    {
        if ($value === '' || !str_contains($value, ':')) {
            return null;
        }

        try {
            $time = Carbon::createFromFormat('H:i', strlen($value) === 5 ? $value : substr($value, 0, 5));
        } catch (\Throwable) {
            return null;
        }

        return $time->hour * 60 + $time->minute;
    }
}
