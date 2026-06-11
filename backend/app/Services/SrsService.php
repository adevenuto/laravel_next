<?php

namespace App\Services;

use App\Models\User;
use App\Models\UserSkillMastery;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * SM-2-lite. Just two outcomes per review (correct / wrong) and a single ease factor.
 * FSRS / quality grades are deliberately out of scope.
 */
class SrsService
{
    private const MIN_EASE = 1.3;

    private const MAX_INTERVAL_DAYS = 365;

    private const EASE_PENALTY_ON_FAIL = 0.2;

    /**
     * Record a review outcome for a user/skill pair and update the SRS state.
     * Creates the mastery row on first review.
     */
    public function recordReview(User $user, string $skillKey, bool $correct): UserSkillMastery
    {
        $mastery = UserSkillMastery::firstOrNew(
            ['user_id' => $user->id, 'skill_key' => $skillKey],
            ['tier' => 'recognize', 'srs_interval_days' => 1, 'srs_ease' => 2.5]
        );

        if ($correct) {
            $next = max(1, (int) round($mastery->srs_interval_days * $mastery->srs_ease));
            $mastery->srs_interval_days = min($next, self::MAX_INTERVAL_DAYS);
            $mastery->srs_due_at = Carbon::now()->addDays($mastery->srs_interval_days);
        } else {
            $mastery->srs_interval_days = 1;
            $mastery->srs_ease = max(self::MIN_EASE, $mastery->srs_ease - self::EASE_PENALTY_ON_FAIL);
            $mastery->srs_due_at = Carbon::now()->addDay();
        }

        $mastery->save();

        return $mastery;
    }

    /**
     * Skills the user owes a review on. Newest-due-first within the limit.
     *
     * @return Collection<int, UserSkillMastery>
     */
    public function dueSkills(User $user, int $limit = 8): Collection
    {
        return UserSkillMastery::where('user_id', $user->id)
            ->whereNotNull('srs_due_at')
            ->where('srs_due_at', '<=', Carbon::now())
            ->orderBy('srs_due_at')
            ->limit($limit)
            ->get();
    }
}
