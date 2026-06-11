<?php

namespace Tests\Feature;

use App\Models\Exercise;
use App\Models\Lesson;
use App\Models\User;
use App\Models\UserSkillMastery;
use Database\Seeders\BadgeSeeder;
use Database\Seeders\ExerciseSeeder;
use Database\Seeders\LessonSeeder;
use Database\Seeders\LevelSeeder;
use Database\Seeders\UnitSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExerciseAttemptTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed([BadgeSeeder::class, LevelSeeder::class, UnitSeeder::class, LessonSeeder::class, ExerciseSeeder::class]);
    }

    public function test_unauthenticated_request_returns_401(): void
    {
        $exercise = Exercise::first();
        $this->postJson("/api/exercises/{$exercise->id}/attempt", ['correct' => true])
            ->assertStatus(401);
    }

    public function test_attempt_on_locked_lesson_returns_403(): void
    {
        $user = User::factory()->create();
        $lockedLesson = Lesson::where('slug', 'l-1-1-2-consonant-essentials')->firstOrFail();
        $exercise = $lockedLesson->exercises()->first();

        $this->actingAs($user)
            ->postJson("/api/exercises/{$exercise->id}/attempt", ['correct' => true])
            ->assertStatus(403)
            ->assertExactJson(['error' => 'locked']);

        // No xp / mastery side-effects.
        $this->assertSame(0, (int) $user->fresh()->xp);
        $this->assertSame(0, UserSkillMastery::where('user_id', $user->id)->count());
    }

    public function test_correct_attempt_increments_xp_and_records_srs(): void
    {
        $user = User::factory()->create();
        $availableLesson = Lesson::where('slug', 'l-1-1-1-vowel-lock-in')->firstOrFail();
        $exercise = $availableLesson->exercises()->orderBy('order')->first();

        $response = $this->actingAs($user)
            ->postJson("/api/exercises/{$exercise->id}/attempt", ['correct' => true]);

        $response->assertOk()
            ->assertJsonPath('xp_earned', (int) $exercise->xp)
            ->assertJsonPath('xp_total', (int) $exercise->xp)
            ->assertJsonPath('mastery.skill_key', 'vowels')
            ->assertJsonStructure(['mastery' => ['skill_key', 'tier', 'srs_due_at', 'srs_interval_days']]);

        $this->assertSame((int) $exercise->xp, (int) $user->fresh()->xp);
        $this->assertSame(1, UserSkillMastery::where('user_id', $user->id)->where('skill_key', 'vowels')->count());
    }

    public function test_wrong_attempt_does_not_increment_xp_but_resets_srs_interval(): void
    {
        $user = User::factory()->create();
        $availableLesson = Lesson::where('slug', 'l-1-1-1-vowel-lock-in')->firstOrFail();
        $exercise = $availableLesson->exercises()->orderBy('order')->first();

        // Seed an existing mastery row to verify wrong-answer reset behavior.
        UserSkillMastery::create([
            'user_id' => $user->id,
            'skill_key' => 'vowels',
            'tier' => 'recognize',
            'srs_interval_days' => 30,
            'srs_ease' => 2.4,
        ]);

        $response = $this->actingAs($user)
            ->postJson("/api/exercises/{$exercise->id}/attempt", ['correct' => false]);

        $response->assertOk()->assertJsonPath('xp_earned', 0);
        $this->assertSame(0, (int) $user->fresh()->xp);

        $mastery = UserSkillMastery::where('user_id', $user->id)->where('skill_key', 'vowels')->first();
        $this->assertSame(1, (int) $mastery->srs_interval_days, 'Wrong answer must reset interval to 1.');
    }

    public function test_missing_correct_field_returns_422(): void
    {
        $user = User::factory()->create();
        $availableLesson = Lesson::where('slug', 'l-1-1-1-vowel-lock-in')->firstOrFail();
        $exercise = $availableLesson->exercises()->first();

        $this->actingAs($user)
            ->postJson("/api/exercises/{$exercise->id}/attempt", [])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['correct']);
    }
}
