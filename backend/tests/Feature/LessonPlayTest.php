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

class LessonPlayTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed([BadgeSeeder::class, LevelSeeder::class, UnitSeeder::class, LessonSeeder::class, ExerciseSeeder::class]);
    }

    public function test_unauthenticated_request_returns_401(): void
    {
        $this->getJson('/api/lessons/l-1-1-1-vowel-lock-in')->assertStatus(401);
    }

    public function test_locked_lesson_returns_403_and_does_not_leak_payload(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->getJson('/api/lessons/l-1-1-2-consonant-essentials'); // locked behind 1.1.1

        $response->assertStatus(403)->assertExactJson(['error' => 'locked']);
        // Critical: no teach_screens or exercises field present.
        $this->assertNull($response->json('lesson'));
        $this->assertNull($response->json('exercises'));
    }

    public function test_available_lesson_returns_teach_screens_and_ordered_exercises(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->getJson('/api/lessons/l-1-1-1-vowel-lock-in');

        $response->assertOk()
            ->assertJsonPath('data.lesson.slug', 'l-1-1-1-vowel-lock-in')
            ->assertJsonPath('data.lesson.skill_key', 'vowels')
            ->assertJsonStructure([
                'data' => [
                    'lesson' => ['id', 'slug', 'title', 'order', 'skill_key', 'teach_screens', 'unit'],
                    'exercises' => [['id', 'component', 'payload', 'order', 'xp']],
                ],
            ]);

        $orders = collect($response->json('data.exercises'))->pluck('order')->all();
        $this->assertSame([1, 2, 3], $orders);

        $components = collect($response->json('data.exercises'))->pluck('component')->all();
        $this->assertSame(['EarTraining', 'MinimalPairs', 'ShadowRecord'], $components);
    }

    public function test_completed_lesson_remains_accessible(): void
    {
        $user = User::factory()->create();
        $lesson = Lesson::where('slug', 'l-1-1-1-vowel-lock-in')->firstOrFail();
        app(ProgressService::class)->complete($user, $lesson);

        $this->actingAs($user)
            ->getJson('/api/lessons/l-1-1-1-vowel-lock-in')
            ->assertOk()
            ->assertJsonPath('data.lesson.slug', 'l-1-1-1-vowel-lock-in');
    }
}
