<?php

namespace App\Http\Controllers;

use App\Models\Exercise;
use App\Services\ProgressService;
use App\Services\RebelCollectionService;
use App\Services\SrsService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ExerciseAttemptController extends Controller
{
    public function __construct(
        private readonly ProgressService $progress,
        private readonly SrsService $srs,
        private readonly RebelCollectionService $rebels,
    ) {}

    public function store(Request $request, Exercise $exercise): JsonResponse
    {
        $data = $request->validate([
            'correct' => 'required|boolean',
            'score' => 'nullable|integer|min:0',
            'meta' => 'nullable|array',
            'meta.rebels_captured' => 'nullable|array|max:10',
            'meta.rebels_captured.*' => 'string|max:64',
            'meta.vocab_delta' => 'nullable|integer|min:0|max:10',
        ]);

        $user = $request->user();
        $lesson = $exercise->lesson()->with('unit')->firstOrFail();

        // Server-side enforcement: an attempt against a locked lesson's exercise
        // must not leak XP or SRS state. §9 "server is source of truth."
        if ($this->progress->unlockState($user, $lesson) === 'locked') {
            return response()->json(['error' => 'locked'], 403);
        }

        $correct = (bool) $data['correct'];
        $rebelsRequested = (array) ($data['meta']['rebels_captured'] ?? []);
        $vocabDelta = (int) ($data['meta']['vocab_delta'] ?? 0);

        [$mastery, $rebelsCaptured] = DB::transaction(function () use ($user, $lesson, $exercise, $correct, $rebelsRequested, $vocabDelta) {
            if ($correct) {
                $user->increment('xp', (int) $exercise->xp);
            }

            if ($vocabDelta > 0) {
                $user->increment('vocab_counter', $vocabDelta);
            }

            $captured = $rebelsRequested ? $this->rebels->capture($user, $rebelsRequested) : [];

            return [$this->srs->recordReview($user, $lesson->skill_key, $correct), $captured];
        });

        $fresh = $user->fresh();

        return response()->json([
            'xp_earned' => $correct ? (int) $exercise->xp : 0,
            'xp_total' => (int) $fresh->xp,
            'vocab_total' => (int) $fresh->vocab_counter,
            'rebels_captured' => $rebelsCaptured,
            'mastery' => [
                'skill_key' => $mastery->skill_key,
                'tier' => $mastery->tier,
                'srs_due_at' => $mastery->srs_due_at?->toIso8601String(),
                'srs_interval_days' => (int) $mastery->srs_interval_days,
            ],
        ]);
    }
}
