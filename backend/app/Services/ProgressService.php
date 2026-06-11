<?php

namespace App\Services;

use App\Models\Lesson;
use App\Models\Unit;
use App\Models\User;
use App\Models\UserLessonProgress;
use App\Services\Dto\LessonCompletion;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class ProgressService
{
    public function __construct(private readonly RewardService $rewards) {}

    /**
     * Compute the unlock state of a lesson for a user, on demand. This is the source
     * of truth — never trust a client-side claim.
     *
     * Rules:
     *  - 'completed' if a progress row exists with status 'completed'.
     *  - 'available' for the first lesson of the first unit, always.
     *  - 'available' for any lesson whose immediate predecessor (same unit, order - 1)
     *    is completed.
     *  - 'available' for the first lesson of unit N (N > 1) when every lesson of
     *    unit N - 1 is completed.
     *  - 'locked' otherwise.
     */
    public function unlockState(User $user, Lesson $lesson): string
    {
        $progress = UserLessonProgress::where('user_id', $user->id)
            ->where('lesson_id', $lesson->id)
            ->first();

        if ($progress && $progress->status === 'completed') {
            return 'completed';
        }

        if ($this->isLessonAvailable($user, $lesson)) {
            return 'available';
        }

        return 'locked';
    }

    /**
     * Mark a lesson complete for a user. Idempotent: calling twice does not double-fire
     * rewards. Transactional. Returns the persisted progress row + the unit reward
     * descriptor (if this completion triggered one and it was new).
     */
    public function complete(User $user, Lesson $lesson, ?int $score = null): LessonCompletion
    {
        return DB::transaction(function () use ($user, $lesson, $score): LessonCompletion {
            $progress = UserLessonProgress::where('user_id', $user->id)
                ->where('lesson_id', $lesson->id)
                ->first();

            if ($progress && $progress->status === 'completed') {
                if ($score !== null && ($progress->best_score === null || $score > $progress->best_score)) {
                    $progress->update(['best_score' => $score]);
                }

                return new LessonCompletion($progress, null);
            }

            if (! $this->isLessonAvailable($user, $lesson)) {
                throw new RuntimeException("Lesson [{$lesson->slug}] is not available for this user.");
            }

            $progress = UserLessonProgress::updateOrCreate(
                ['user_id' => $user->id, 'lesson_id' => $lesson->id],
                [
                    'status' => 'completed',
                    'best_score' => $score,
                    'completed_at' => now(),
                ]
            );

            $reward = null;
            if ($this->isLastLessonOfUnit($lesson)) {
                $reward = $this->rewards->grantUnitReward($user, $lesson->unit);
            }

            return new LessonCompletion($progress, $reward);
        });
    }

    private function isLessonAvailable(User $user, Lesson $lesson): bool
    {
        if ($lesson->order > 1) {
            $previous = Lesson::where('unit_id', $lesson->unit_id)
                ->where('order', $lesson->order - 1)
                ->first();

            return $previous !== null && $this->isCompleted($user, $previous);
        }

        $unit = $lesson->unit;
        if ($unit === null) {
            return false;
        }

        if ($unit->order <= 1) {
            return true;
        }

        $previousUnit = Unit::where('level_id', $unit->level_id)
            ->where('order', $unit->order - 1)
            ->first();

        if ($previousUnit === null) {
            return true;
        }

        return $this->allLessonsCompleted($user, $previousUnit);
    }

    private function isCompleted(User $user, Lesson $lesson): bool
    {
        return UserLessonProgress::where('user_id', $user->id)
            ->where('lesson_id', $lesson->id)
            ->where('status', 'completed')
            ->exists();
    }

    private function isLastLessonOfUnit(Lesson $lesson): bool
    {
        $max = Lesson::where('unit_id', $lesson->unit_id)->max('order');

        return $lesson->order === $max;
    }

    private function allLessonsCompleted(User $user, Unit $unit): bool
    {
        $total = Lesson::where('unit_id', $unit->id)->count();
        if ($total === 0) {
            return true;
        }

        $done = UserLessonProgress::where('user_id', $user->id)
            ->whereIn('lesson_id', Lesson::where('unit_id', $unit->id)->pluck('id'))
            ->where('status', 'completed')
            ->count();

        return $done === $total;
    }
}
