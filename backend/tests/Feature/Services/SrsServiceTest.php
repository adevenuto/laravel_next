<?php

namespace Tests\Feature\Services;

use App\Models\User;
use App\Models\UserSkillMastery;
use App\Services\SrsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class SrsServiceTest extends TestCase
{
    use RefreshDatabase;

    private SrsService $srs;

    protected function setUp(): void
    {
        parent::setUp();
        $this->srs = new SrsService;
    }

    public function test_first_correct_review_creates_mastery_row_with_extended_interval(): void
    {
        $user = User::factory()->create();
        Carbon::setTestNow('2026-06-10 12:00:00');

        $mastery = $this->srs->recordReview($user, 'cognate_tion', correct: true);

        // Default interval (1) × default ease (2.5) → round(2.5) = 3 days.
        $this->assertSame(3, $mastery->srs_interval_days);
        $this->assertTrue($mastery->srs_due_at->equalTo(Carbon::parse('2026-06-13 12:00:00')));
        $this->assertSame(2.5, (float) $mastery->srs_ease);

        Carbon::setTestNow();
    }

    public function test_correct_review_compounds_interval_by_ease(): void
    {
        $user = User::factory()->create();
        UserSkillMastery::create([
            'user_id' => $user->id,
            'skill_key' => 'greetings',
            'tier' => 'recognize',
            'srs_interval_days' => 6,
            'srs_ease' => 2.0,
        ]);

        $mastery = $this->srs->recordReview($user, 'greetings', correct: true);

        // 6 × 2.0 = 12
        $this->assertSame(12, $mastery->srs_interval_days);
    }

    public function test_incorrect_review_resets_interval_to_one_day(): void
    {
        $user = User::factory()->create();
        UserSkillMastery::create([
            'user_id' => $user->id,
            'skill_key' => 'cognate_tion',
            'tier' => 'transform',
            'srs_interval_days' => 30,
            'srs_ease' => 2.3,
        ]);
        Carbon::setTestNow('2026-06-10 12:00:00');

        $mastery = $this->srs->recordReview($user, 'cognate_tion', correct: false);

        $this->assertSame(1, $mastery->srs_interval_days);
        $this->assertTrue($mastery->srs_due_at->equalTo(Carbon::parse('2026-06-11 12:00:00')));

        Carbon::setTestNow();
    }

    public function test_incorrect_review_reduces_ease_with_a_floor_of_1_3(): void
    {
        $user = User::factory()->create();
        UserSkillMastery::create([
            'user_id' => $user->id,
            'skill_key' => 'stress_rules',
            'tier' => 'recognize',
            'srs_interval_days' => 4,
            'srs_ease' => 1.4,
        ]);

        $mastery = $this->srs->recordReview($user, 'stress_rules', correct: false);
        $this->assertSame(1.3, (float) $mastery->srs_ease);

        // A second failure should not push it below the floor.
        $mastery = $this->srs->recordReview($user, 'stress_rules', correct: false);
        $this->assertSame(1.3, (float) $mastery->srs_ease);
    }

    public function test_due_skills_returns_only_currently_due_items_in_due_order(): void
    {
        $user = User::factory()->create();
        Carbon::setTestNow('2026-06-10 12:00:00');

        UserSkillMastery::create([
            'user_id' => $user->id,
            'skill_key' => 'overdue_a',
            'srs_due_at' => Carbon::parse('2026-06-08 09:00:00'),
            'srs_interval_days' => 1,
            'srs_ease' => 2.5,
            'tier' => 'recognize',
        ]);
        UserSkillMastery::create([
            'user_id' => $user->id,
            'skill_key' => 'overdue_b',
            'srs_due_at' => Carbon::parse('2026-06-09 09:00:00'),
            'srs_interval_days' => 1,
            'srs_ease' => 2.5,
            'tier' => 'recognize',
        ]);
        UserSkillMastery::create([
            'user_id' => $user->id,
            'skill_key' => 'future',
            'srs_due_at' => Carbon::parse('2026-06-20 09:00:00'),
            'srs_interval_days' => 1,
            'srs_ease' => 2.5,
            'tier' => 'recognize',
        ]);

        $due = $this->srs->dueSkills($user);

        $this->assertSame(['overdue_a', 'overdue_b'], $due->pluck('skill_key')->all());

        Carbon::setTestNow();
    }
}
