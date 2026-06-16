<?php

namespace App\Http\Controllers;

use App\Models\Lesson;
use App\Models\User;
use App\Models\UserBossAttempt;
use App\Models\UserCollection;
use App\Models\UserLessonProgress;
use App\Models\UserSkillMastery;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * DEV-UNLOCK: local/testing-only progress shortcuts for play-through testing.
 *
 * These endpoints exist only when APP_ENV ∈ {local, testing}; the route group
 * registration in routes/api.php is gated by `app()->environment(...)`.
 *
 * The toggle endpoint is idempotent in both directions: snapshot+complete when
 * there's no snapshot, restore+clear when there is one. Reset wipes everything
 * INCLUDING the snapshot so the user lands in normal-empty state.
 */
class DevProgressController extends Controller
{
    public function toggleCompleteAll(Request $request): JsonResponse
    {
        $user = $request->user();

        return $user->progress_snapshot === null
            ? $this->snapshotAndComplete($user)
            : $this->restoreFromSnapshot($user);
    }

    public function reset(Request $request): JsonResponse
    {
        $user = $request->user();

        DB::transaction(function () use ($user) {
            UserLessonProgress::where('user_id', $user->id)->delete();
            UserCollection::where('user_id', $user->id)->delete();
            UserSkillMastery::where('user_id', $user->id)->delete();
            UserBossAttempt::where('user_id', $user->id)->delete();

            $user->forceFill([
                'xp' => 0,
                'vocab_counter' => 0,
                'streak_count' => 0,
                'streak_last_active_date' => null,
                'progress_snapshot' => null,
            ])->save();
        });

        $fresh = $user->fresh();

        return response()->json([
            'state' => 'normal',
            'reset' => true,
            'xp_total' => (int) $fresh->xp,
            'vocab_total' => (int) $fresh->vocab_counter,
        ]);
    }

    private function snapshotAndComplete(User $user): JsonResponse
    {
        $lessonIds = Lesson::pluck('id');
        $now = now();

        DB::transaction(function () use ($user, $lessonIds, $now) {
            // Snapshot the user's current lesson progress so we can restore it.
            $snapshot = UserLessonProgress::where('user_id', $user->id)
                ->get(['lesson_id', 'status', 'best_score', 'completed_at'])
                ->map(fn ($row) => [
                    'lesson_id' => (int) $row->lesson_id,
                    'status' => $row->status,
                    'best_score' => $row->best_score !== null ? (int) $row->best_score : null,
                    'completed_at' => $row->completed_at?->toIso8601String(),
                ])
                ->values()
                ->all();

            $user->forceFill(['progress_snapshot' => $snapshot])->save();

            // Mark every lesson completed for navigability.
            foreach ($lessonIds as $lessonId) {
                UserLessonProgress::updateOrCreate(
                    ['user_id' => $user->id, 'lesson_id' => $lessonId],
                    ['status' => 'completed', 'completed_at' => $now]
                );
            }
        });

        $fresh = $user->fresh();

        return response()->json([
            'state' => 'unlocked',
            'completed_count' => $lessonIds->count(),
            'xp_total' => (int) $fresh->xp,
            'vocab_total' => (int) $fresh->vocab_counter,
        ]);
    }

    private function restoreFromSnapshot(User $user): JsonResponse
    {
        $snapshot = $user->progress_snapshot ?? [];

        DB::transaction(function () use ($user, $snapshot) {
            UserLessonProgress::where('user_id', $user->id)->delete();

            foreach ($snapshot as $row) {
                UserLessonProgress::create([
                    'user_id' => $user->id,
                    'lesson_id' => $row['lesson_id'],
                    'status' => $row['status'],
                    'best_score' => $row['best_score'] ?? null,
                    'completed_at' => $row['completed_at'] ?? null,
                ]);
            }

            $user->forceFill(['progress_snapshot' => null])->save();
        });

        $fresh = $user->fresh();

        return response()->json([
            'state' => 'normal',
            'restored_count' => count($snapshot),
            'xp_total' => (int) $fresh->xp,
            'vocab_total' => (int) $fresh->vocab_counter,
        ]);
    }
}
