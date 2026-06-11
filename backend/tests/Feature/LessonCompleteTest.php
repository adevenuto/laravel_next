<?php

namespace Tests\Feature;

use App\Models\Lesson;
use App\Models\User;
use App\Models\UserCollection;
use App\Services\ProgressService;
use Database\Seeders\BadgeSeeder;
use Database\Seeders\ExerciseSeeder;
use Database\Seeders\LessonSeeder;
use Database\Seeders\LevelSeeder;
use Database\Seeders\UnitSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LessonCompleteTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed([BadgeSeeder::class, LevelSeeder::class, UnitSeeder::class, LessonSeeder::class, ExerciseSeeder::class]);
    }

    public function test_unauthenticated_request_returns_401(): void
    {
        $this->postJson('/api/lessons/l-1-1-1-vowel-lock-in/complete')->assertStatus(401);
    }

    public function test_completing_a_locked_lesson_returns_403(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->postJson('/api/lessons/l-1-1-2-consonant-essentials/complete')
            ->assertStatus(403)
            ->assertExactJson(['error' => 'locked']);
    }

    public function test_completing_last_lesson_of_unit_1_1_fires_the_decoder_reward(): void
    {
        $user = User::factory()->create();
        $progress = app(ProgressService::class);

        // Complete the first two lessons of Unit 1.1 via the service so the third is unlocked.
        $progress->complete($user, Lesson::where('slug', 'l-1-1-1-vowel-lock-in')->firstOrFail());
        $progress->complete($user, Lesson::where('slug', 'l-1-1-2-consonant-essentials')->firstOrFail());

        $response = $this->actingAs($user)
            ->postJson('/api/lessons/l-1-1-3-stress-and-accents/complete', ['score' => 90]);

        $response->assertOk()
            ->assertJsonPath('progress.status', 'completed')
            ->assertJsonPath('progress.best_score', 90)
            ->assertJsonPath('reward.was_new', true)
            ->assertJsonPath('reward.badge_key', 'the_decoder')
            ->assertJsonPath('reward.feature_flag', 'pronunciation_hints')
            ->assertJsonPath('reward.vocab_delta', 0)
            ->assertJsonPath('next_lesson_slug', 'l-1-2-1-hello-goodbye');

        // Badge actually persisted.
        $this->assertSame(1, UserCollection::where('user_id', $user->id)
            ->where('collectible_key', 'the_decoder')->count());
    }

    public function test_second_completion_is_idempotent_and_does_not_re_grant_reward(): void
    {
        $user = User::factory()->create();
        $progress = app(ProgressService::class);
        $progress->complete($user, Lesson::where('slug', 'l-1-1-1-vowel-lock-in')->firstOrFail());
        $progress->complete($user, Lesson::where('slug', 'l-1-1-2-consonant-essentials')->firstOrFail());

        // First call grants the reward.
        $this->actingAs($user)
            ->postJson('/api/lessons/l-1-1-3-stress-and-accents/complete')
            ->assertOk()
            ->assertJsonPath('reward.was_new', true);

        // Second call: lesson is already completed → no reward emitted.
        $this->actingAs($user)
            ->postJson('/api/lessons/l-1-1-3-stress-and-accents/complete')
            ->assertOk()
            ->assertJsonPath('reward', null)
            ->assertJsonPath('progress.status', 'completed');

        // Badge still exactly once in user_collections.
        $this->assertSame(1, UserCollection::where('user_id', $user->id)
            ->where('collectible_key', 'the_decoder')->count());
    }
}
