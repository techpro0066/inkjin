<?php

namespace App\Support;

use App\Models\UserDetail;

class OnboardingProgress
{
    public const TOTAL_STEPS = 6;

    /** @var array<int, string> */
    public const STEP_LABELS = [
        1 => 'Profile',
        2 => 'Styles & Social',
        3 => 'Studio',
        4 => 'Payments',
        5 => 'Calendar',
        6 => 'Payouts',
    ];

    /**
     * @return array{
     *     total_steps: int,
     *     completed_count: int,
     *     completed_steps: list<int>,
     *     current_step: int,
     *     current_step_label: string,
     *     progress_label: string,
     *     steps: list<array{step: int, label: string, completed: bool}>
     * }
     */
    public static function for(?UserDetail $detail): array
    {
        $completed = self::normalizeCompletedSteps($detail?->completed_steps ?? []);
        $currentStep = max(1, min(self::TOTAL_STEPS, (int) ($detail?->current_step ?? 1)));

        $steps = [];
        for ($step = 1; $step <= self::TOTAL_STEPS; $step++) {
            $steps[] = [
                'step' => $step,
                'label' => self::STEP_LABELS[$step],
                'completed' => in_array($step, $completed, true),
            ];
        }

        $completedCount = count($completed);

        return [
            'total_steps' => self::TOTAL_STEPS,
            'completed_count' => $completedCount,
            'completed_steps' => $completed,
            'current_step' => $currentStep,
            'current_step_label' => self::STEP_LABELS[$currentStep] ?? self::STEP_LABELS[1],
            'progress_label' => "{$completedCount}/".self::TOTAL_STEPS.' steps',
            'steps' => $steps,
        ];
    }

    /**
     * @param  mixed  $completedSteps
     * @return list<int>
     */
    private static function normalizeCompletedSteps($completedSteps): array
    {
        if (! is_array($completedSteps)) {
            return [];
        }

        $normalized = [];
        foreach ($completedSteps as $step) {
            $step = (int) $step;
            if ($step >= 1 && $step <= self::TOTAL_STEPS) {
                $normalized[] = $step;
            }
        }

        sort($normalized);

        return array_values(array_unique($normalized));
    }
}
