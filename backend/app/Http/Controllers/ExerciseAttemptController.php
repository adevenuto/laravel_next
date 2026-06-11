<?php

namespace App\Http\Controllers;

use App\Models\Exercise;
use App\Services\ProgressService;
use App\Services\SrsService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ExerciseAttemptController extends Controller
{
    public function __construct(
        private readonly ProgressService $progress,
        private readonly SrsService $srs,
    ) {}

    public function store(Request $request, Exercise $exercise): JsonResponse
    {
        $data = $request->validate([
            'correct' => 'required|boolean',
            'score' => 'nullable|integer|min:0',
            'meta' => 'nullable|array',
        ]);

        $user = $request->user();
        $lesson = $exercise->lesson()->with('unit')->firstOrFail();

        // Server-side enforcement: an attempt against a locked lesson's exercise
        // must not leak XP or SRS state. §9 "server is source of truth."
        if ($this->progress->unlockState($user, $lesson) === 'locked') {
            return response()->json(['error' => 'locked'], 403);
        }

        $correct = (bool) $data['correct'];

        $mastery = DB::transaction(function () use ($user, $lesson, $exercise, $correct) {
            if ($correct) {
                $user->increment('xp', (int) $exercise->xp);
            }

            return $this->srs->recordReview($user, $lesson->skill_key, $correct);
        });

        return response()->json([
            'xp_earned' => $correct ? (int) $exercise->xp : 0,
            'xp_total' => (int) $user->fresh()->xp,
            'mastery' => [
                'skill_key' => $mastery->skill_key,
                'tier' => $mastery->tier,
                'srs_due_at' => $mastery->srs_due_at?->toIso8601String(),
                'srs_interval_days' => (int) $mastery->srs_interval_days,
            ],
        ]);
    }
}
