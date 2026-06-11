<?php

namespace Tests\Feature;

use App\Models\Lesson;
use App\Models\User;
use App\Services\ProgressService;
use Database\Seeders\BadgeSeeder;
use Database\Seeders\ExerciseSeeder;
use Database\Seeders\LessonSeeder;
use Database\Seeders\LevelSeeder;
use Database\Seeders\UnitSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LevelMapTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed([BadgeSeeder::class, LevelSeeder::class, UnitSeeder::class, LessonSeeder::class, ExerciseSeeder::class]);
    }

    public function test_unauthenticated_request_returns_401(): void
    {
        $this->getJson('/api/levels/1/map')->assertStatus(401);
    }

    public function test_fresh_user_sees_only_first_lesson_available(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->getJson('/api/levels/1/map');

        $response->assertOk()
            ->assertJsonPath('data.level.number', 1)
            ->assertJsonPath('data.level.slug', 'level-1')
            ->assertJsonCount(5, 'data.units');

        // Lesson 1.1.1 should be the only available lesson for a brand-new user.
        $statuses = collect($response->json('data.units'))
            ->flatMap(fn ($u) => collect($u['lessons'])->map(fn ($l) => [
                'slug' => $l['slug'],
                'lock_state' => $l['lock_state'],
            ]));

        $this->assertSame('available', $statuses->firstWhere('slug', 'l-1-1-1-vowel-lock-in')['lock_state']);
        $this->assertSame('locked', $statuses->firstWhere('slug', 'l-1-1-2-consonant-essentials')['lock_state']);
        $this->assertSame('locked', $statuses->firstWhere('slug', 'l-1-2-1-hello-goodbye')['lock_state']);
    }

    public function test_completed_lesson_reflects_completed_state_and_best_score(): void
    {
        $user = User::factory()->create();
        $lesson = Lesson::where('slug', 'l-1-1-1-vowel-lock-in')->firstOrFail();
        app(ProgressService::class)->complete($user, $lesson, score: 88);

        $response = $this->actingAs($user)->getJson('/api/levels/1/map');

        $response->assertOk();
        $row = collect($response->json('data.units'))
            ->flatMap(fn ($u) => $u['lessons'])
            ->firstWhere('slug', 'l-1-1-1-vowel-lock-in');

        $this->assertSame('completed', $row['lock_state']);
        $this->assertSame(88, $row['best_score']);
    }

    public function test_completing_unit_1_1_unlocks_first_lesson_of_unit_1_2(): void
    {
        $user = User::factory()->create();
        $progress = app(ProgressService::class);

        foreach (['l-1-1-1-vowel-lock-in', 'l-1-1-2-consonant-essentials', 'l-1-1-3-stress-and-accents'] as $slug) {
            $progress->complete($user, Lesson::where('slug', $slug)->firstOrFail());
        }

        $response = $this->actingAs($user)->getJson('/api/levels/1/map');

        $row = collect($response->json('data.units'))
            ->flatMap(fn ($u) => $u['lessons'])
            ->firstWhere('slug', 'l-1-2-1-hello-goodbye');

        $this->assertSame('available', $row['lock_state']);
    }
}
