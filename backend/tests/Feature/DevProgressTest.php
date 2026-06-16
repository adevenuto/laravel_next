<?php

namespace Tests\Feature;

use App\Models\Lesson;
use App\Models\User;
use App\Models\UserCollection;
use App\Models\UserLessonProgress;
use App\Models\UserSkillMastery;
use Database\Seeders\BadgeSeeder;
use Database\Seeders\ExerciseSeeder;
use Database\Seeders\LessonSeeder;
use Database\Seeders\LevelSeeder;
use Database\Seeders\UnitSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class DevProgressTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed([
            BadgeSeeder::class,
            LevelSeeder::class,
            UnitSeeder::class,
            LessonSeeder::class,
            ExerciseSeeder::class,
        ]);
    }

    public function test_first_toggle_snapshots_existing_progress_and_marks_all_complete(): void
    {
        $user = User::factory()->create();
        $someLesson = Lesson::orderBy('id')->first();
        UserLessonProgress::create([
            'user_id' => $user->id,
            'lesson_id' => $someLesson->id,
            'status' => 'completed',
            'best_score' => 87,
            'completed_at' => now(),
        ]);

        $response = $this->actingAs($user)
            ->postJson('/api/dev/progress/toggle-complete-all');

        $response->assertOk()
            ->assertJsonPath('state', 'unlocked')
            ->assertJsonPath('completed_count', Lesson::count());

        $fresh = $user->fresh();
        $this->assertNotNull($fresh->progress_snapshot);
        $this->assertCount(1, $fresh->progress_snapshot, 'Snapshot must record the pre-toggle row.');
        $this->assertSame(87, $fresh->progress_snapshot[0]['best_score']);

        $this->assertSame(
            Lesson::count(),
            UserLessonProgress::where('user_id', $user->id)->count(),
            'Every lesson must be marked completed.'
        );
    }

    public function test_second_toggle_restores_exactly_the_snapshotted_progress(): void
    {
        $user = User::factory()->create();
        $someLesson = Lesson::orderBy('id')->first();
        UserLessonProgress::create([
            'user_id' => $user->id,
            'lesson_id' => $someLesson->id,
            'status' => 'completed',
            'best_score' => 87,
            'completed_at' => now(),
        ]);

        // First toggle → unlock.
        $this->actingAs($user)->postJson('/api/dev/progress/toggle-complete-all')->assertOk();

        // Second toggle → restore.
        $response = $this->actingAs($user)
            ->postJson('/api/dev/progress/toggle-complete-all');

        $response->assertOk()->assertJsonPath('state', 'normal')->assertJsonPath('restored_count', 1);

        $rows = UserLessonProgress::where('user_id', $user->id)->get();
        $this->assertCount(1, $rows, 'Only the snapshotted row should remain.');
        $this->assertSame($someLesson->id, (int) $rows->first()->lesson_id);
        $this->assertSame(87, (int) $rows->first()->best_score);
        $this->assertNull($user->fresh()->progress_snapshot, 'Snapshot must clear after restore.');
    }

    public function test_toggle_round_trip_from_empty_progress_lands_back_empty(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->postJson('/api/dev/progress/toggle-complete-all')->assertOk();
        $this->actingAs($user)->postJson('/api/dev/progress/toggle-complete-all')->assertOk();

        $this->assertSame(0, UserLessonProgress::where('user_id', $user->id)->count());
        $this->assertNull($user->fresh()->progress_snapshot);
    }

    public function test_toggle_unlock_does_not_fire_unit_rewards(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->postJson('/api/dev/progress/toggle-complete-all')->assertOk();

        $this->assertSame(
            0,
            UserCollection::where('user_id', $user->id)
                ->where('collectible_type', 'badge')
                ->count(),
            'Direct DB write must not trigger ProgressService reward grants.'
        );
        $this->assertSame(0, (int) $user->fresh()->vocab_counter);
    }

    public function test_reset_wipes_progress_and_clears_snapshot_and_zeroes_counters(): void
    {
        $user = User::factory()->create([
            'xp' => 500,
            'vocab_counter' => 400,
            'streak_count' => 7,
        ]);

        UserLessonProgress::create([
            'user_id' => $user->id,
            'lesson_id' => Lesson::first()->id,
            'status' => 'completed',
            'completed_at' => now(),
        ]);
        UserCollection::create([
            'user_id' => $user->id,
            'collectible_type' => 'rebel_word',
            'collectible_key' => 'traduccion',
            'earned_at' => now(),
        ]);
        UserSkillMastery::create([
            'user_id' => $user->id,
            'skill_key' => 'vowels',
            'tier' => 'recognize',
        ]);

        // Put the user in the unlocked state first.
        $this->actingAs($user)->postJson('/api/dev/progress/toggle-complete-all')->assertOk();
        $this->assertNotNull($user->fresh()->progress_snapshot);

        $response = $this->actingAs($user)->postJson('/api/dev/progress/reset');

        $response->assertOk()->assertJsonPath('reset', true)->assertJsonPath('state', 'normal');

        $fresh = $user->fresh();
        $this->assertSame(0, (int) $fresh->xp);
        $this->assertSame(0, (int) $fresh->vocab_counter);
        $this->assertSame(0, (int) $fresh->streak_count);
        $this->assertNull($fresh->streak_last_active_date);
        $this->assertNull($fresh->progress_snapshot, 'Reset must clear the snapshot column.');
        $this->assertSame(0, UserLessonProgress::where('user_id', $user->id)->count());
        $this->assertSame(0, UserCollection::where('user_id', $user->id)->count());
        $this->assertSame(0, UserSkillMastery::where('user_id', $user->id)->count());
    }

    public function test_reset_preserves_display_name_and_hometown(): void
    {
        $user = User::factory()->create([
            'display_name' => 'Anthony',
            'hometown' => 'New Orleans',
        ]);

        $this->actingAs($user)->postJson('/api/dev/progress/reset')->assertOk();

        $fresh = $user->fresh();
        $this->assertSame('Anthony', $fresh->display_name);
        $this->assertSame('New Orleans', $fresh->hometown);
    }

    public function test_dev_routes_require_auth(): void
    {
        $this->postJson('/api/dev/progress/toggle-complete-all')->assertStatus(401);
        $this->postJson('/api/dev/progress/reset')->assertStatus(401);
    }

    public function test_dev_routes_are_registered_in_testing_env(): void
    {
        $names = collect(Route::getRoutes())->map(fn ($r) => $r->uri())->toArray();
        $this->assertContains('api/dev/progress/toggle-complete-all', $names);
        $this->assertContains('api/dev/progress/reset', $names);
    }

    public function test_me_payload_reports_dev_snapshot_present_correctly(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->getJson('/api/me')
            ->assertOk()
            ->assertJsonPath('data.dev_snapshot_present', false);

        $this->actingAs($user)->postJson('/api/dev/progress/toggle-complete-all')->assertOk();

        $this->actingAs($user)
            ->getJson('/api/me')
            ->assertOk()
            ->assertJsonPath('data.dev_snapshot_present', true);
    }

    public function test_level_map_lock_states_restore_correctly_after_toggle_round_trip(): void
    {
        $user = User::factory()->create();

        // Before any toggle: only Lesson 1.1.1 is available, the rest locked.
        $beforeMap = $this->actingAs($user)->getJson('/api/levels/1/map')->json('data.units');
        $this->assertSame('available', $beforeMap[0]['lessons'][0]['lock_state']);
        $this->assertSame('locked', $beforeMap[0]['lessons'][1]['lock_state']);

        // Unlock.
        $this->actingAs($user)->postJson('/api/dev/progress/toggle-complete-all')->assertOk();
        $unlockedMap = $this->actingAs($user)->getJson('/api/levels/1/map')->json('data.units');
        // Every lesson now reads as completed (and therefore navigable).
        foreach ($unlockedMap as $unit) {
            foreach ($unit['lessons'] as $lesson) {
                $this->assertSame('completed', $lesson['lock_state']);
            }
        }

        // Restore — the lock pattern must come back exactly.
        $this->actingAs($user)->postJson('/api/dev/progress/toggle-complete-all')->assertOk();
        $afterMap = $this->actingAs($user)->getJson('/api/levels/1/map')->json('data.units');
        $this->assertSame('available', $afterMap[0]['lessons'][0]['lock_state']);
        $this->assertSame('locked', $afterMap[0]['lessons'][1]['lock_state']);
        $this->assertSame('locked', $afterMap[1]['lessons'][0]['lock_state']);
    }
}
