<?php

namespace Tests\Unit;

use App\Models\UserDetail;
use App\Support\OnboardingProgress;
use Tests\TestCase;

class OnboardingProgressTest extends TestCase
{
    public function test_builds_progress_from_user_detail(): void
    {
        $detail = new UserDetail([
            'current_step' => 4,
            'completed_steps' => [1, 2, 3],
        ]);

        $progress = OnboardingProgress::for($detail);

        $this->assertSame(3, $progress['completed_count']);
        $this->assertSame('3/6 steps', $progress['progress_label']);
        $this->assertSame(4, $progress['current_step']);
        $this->assertSame('Preferences', $progress['current_step_label']);
        $this->assertTrue($progress['steps'][0]['completed']);
        $this->assertFalse($progress['steps'][5]['completed']);
    }

    public function test_defaults_when_no_detail_exists(): void
    {
        $progress = OnboardingProgress::for(null);

        $this->assertSame(0, $progress['completed_count']);
        $this->assertSame('0/6 steps', $progress['progress_label']);
        $this->assertSame(1, $progress['current_step']);
        $this->assertSame('Profile', $progress['current_step_label']);
    }
}
