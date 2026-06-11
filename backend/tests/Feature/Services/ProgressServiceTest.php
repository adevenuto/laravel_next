<?php

namespace Tests\Feature\Services;

use App\Models\Lesson;
use App\Models\Level;
use App\Models\Unit;
use App\Models\User;
use App\Models\UserCollection;
use App\Models\UserLessonProgress;
use App\Services\ProgressService;
use App\Services\RewardService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use RuntimeException;
use Tests\TestCase;

class ProgressServiceTest extends TestCase
{
    use RefreshDatabase;

    private ProgressService $progress;

    protected function setUp(): void
    {
        parent::setUp();
        $this->progress = new ProgressService(new RewardService);
    }

    public function test_first_lesson_of_first_unit_is_available_by_default(): void
    {
        $user = User::factory()->create();
        $unit = $this->buildUnit(unitOrder: 1, lessonCount: 2);

        $first = $unit->lessons()->orderBy('order')->first();

        $this->assertSame('available', $this->progress->unlockState($user, $first));
    }

    public function test_completing_a_lesson_creates_a_completed_progress_row(): void
    {
        $user = User::factory()->create();
        $unit = $this->buildUnit(lessonCount: 2);
        $first = $unit->lessons()->orderBy('order')->first();

        $result = $this->progress->complete($user, $first, score: 85);

        $this->assertSame('completed', $result->progress->status);
        $this->assertSame(85, $result->progress->best_score);
        $this->assertNotNull($result->progress->completed_at);
        // First-of-two: unit reward should NOT have fired.
        $this->assertNull($result->reward);
    }

    public function test_completing_a_non_last_lesson_does_not_return_a_reward(): void
    {
        $user = User::factory()->create();
        $unit = $this->buildUnit(lessonCount: 3);
        $first = $unit->lessons()->orderBy('order')->first();

        $result = $this->progress->complete($user, $first);

        $this->assertNull($result->reward);
    }

    public function test_idempotent_completion_returns_no_reward_on_second_call(): void
    {
        $user = User::factory()->create();
        $unit = $this->buildUnit(lessonCount: 1, badgeKey: 'the_alchemist');
        $only = $unit->lessons->first();

        $first = $this->progress->complete($user, $only);
        $second = $this->progress->complete($user, $only);

        $this->assertNotNull($first->reward);
        $this->assertTrue($first->reward['was_new']);
        $this->assertNull($second->reward);
    }

    public function test_completion_is_idempotent_and_does_not_double_grant_unit_reward(): void
    {
        $user = User::factory()->create();
        $unit = $this->buildUnit(lessonCount: 1, badgeKey: 'the_alchemist');
        $only = $unit->lessons->first();

        $this->progress->complete($user, $only);
        $this->progress->complete($user, $only);

        $this->assertSame(1, UserLessonProgress::where('user_id', $user->id)->count());
        $this->assertSame(1, UserCollection::where('user_id', $user->id)
            ->where('collectible_key', 'the_alchemist')->count());
        // The_alchemist's vocab bump is 400 — must only fire once.
        $this->assertSame(400, (int) $user->fresh()->vocab_counter);
    }

    public function test_completion_idempotently_keeps_best_score_high_water_mark(): void
    {
        $user = User::factory()->create();
        $unit = $this->buildUnit(lessonCount: 1);
        $only = $unit->lessons->first();

        $this->progress->complete($user, $only, score: 60);
        $this->progress->complete($user, $only, score: 95);
        $this->progress->complete($user, $only, score: 70);

        $this->assertSame(95, (int) UserLessonProgress::firstOrFail()->best_score);
    }

    public function test_next_lesson_unlocks_after_previous_completes(): void
    {
        $user = User::factory()->create();
        $unit = $this->buildUnit(lessonCount: 3);
        [$l1, $l2, $l3] = $unit->lessons()->orderBy('order')->get()->all();

        $this->assertSame('locked', $this->progress->unlockState($user, $l2));
        $this->assertSame('locked', $this->progress->unlockState($user, $l3));

        $this->progress->complete($user, $l1);

        $this->assertSame('completed', $this->progress->unlockState($user, $l1));
        $this->assertSame('available', $this->progress->unlockState($user, $l2));
        $this->assertSame('locked', $this->progress->unlockState($user, $l3));
    }

    public function test_first_lesson_of_next_unit_unlocks_when_previous_unit_completes(): void
    {
        $level = Level::factory()->create();
        $unit1 = $this->buildUnit(level: $level, unitOrder: 1, lessonCount: 2);
        $unit2 = $this->buildUnit(level: $level, unitOrder: 2, lessonCount: 2);
        $user = User::factory()->create();

        $unit2Lesson1 = $unit2->lessons()->orderBy('order')->first();
        $this->assertSame('locked', $this->progress->unlockState($user, $unit2Lesson1));

        foreach ($unit1->lessons()->orderBy('order')->get() as $lesson) {
            $this->progress->complete($user, $lesson);
        }

        $this->assertSame('available', $this->progress->unlockState($user, $unit2Lesson1));
    }

    public function test_completing_a_locked_lesson_throws(): void
    {
        $user = User::factory()->create();
        $unit = $this->buildUnit(lessonCount: 2);
        $second = $unit->lessons()->orderBy('order')->skip(1)->first();

        $this->expectException(RuntimeException::class);
        $this->progress->complete($user, $second);
    }

    public function test_unit_badge_fires_only_on_the_last_lesson(): void
    {
        $user = User::factory()->create();
        $unit = $this->buildUnit(lessonCount: 3, badgeKey: 'unit_test_badge');
        [$l1, $l2, $l3] = $unit->lessons()->orderBy('order')->get()->all();

        $this->progress->complete($user, $l1);
        $this->assertSame(0, UserCollection::where('user_id', $user->id)->count());

        $this->progress->complete($user, $l2);
        $this->assertSame(0, UserCollection::where('user_id', $user->id)->count());

        $this->progress->complete($user, $l3);
        $this->assertSame(1, UserCollection::where('user_id', $user->id)
            ->where('collectible_key', 'unit_test_badge')->count());
    }

    private function buildUnit(
        ?Level $level = null,
        int $unitOrder = 1,
        int $lessonCount = 1,
        string $badgeKey = 'test_badge',
    ): Unit {
        $level ??= Level::factory()->create();

        $unit = Unit::factory()->create([
            'level_id' => $level->id,
            'order' => $unitOrder,
            'reward_badge_key' => $badgeKey,
        ]);

        for ($i = 1; $i <= $lessonCount; $i++) {
            Lesson::factory()->create([
                'unit_id' => $unit->id,
                'order' => $i,
                'skill_key' => "test_skill_{$unit->id}_{$i}",
            ]);
        }

        return $unit->fresh('lessons');
    }
}
