<?php

namespace App\Http\Controllers;

use App\Models\Lesson;
use App\Models\Unit;
use App\Services\ProgressService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use RuntimeException;

class LessonCompleteController extends Controller
{
    public function __construct(private readonly ProgressService $progress) {}

    public function store(Request $request, Lesson $lesson): JsonResponse
    {
        $data = $request->validate([
            'score' => 'nullable|integer|min:0',
        ]);

        $user = $request->user();

        // Belt-and-suspenders: ProgressService::complete throws when locked, but
        // returning 403 directly avoids surfacing the service exception as a 500.
        if ($this->progress->unlockState($user, $lesson) === 'locked') {
            return response()->json(['error' => 'locked'], 403);
        }

        try {
            $result = $this->progress->complete($user, $lesson, $data['score'] ?? null);
        } catch (RuntimeException) {
            return response()->json(['error' => 'locked'], 403);
        }

        return response()->json([
            'progress' => [
                'status' => $result->progress->status,
                'best_score' => $result->progress->best_score !== null
                    ? (int) $result->progress->best_score : null,
                'completed_at' => $result->progress->completed_at?->toIso8601String(),
            ],
            'reward' => $result->reward,
            'next_lesson_slug' => $this->nextLessonSlug($lesson),
        ]);
    }

    private function nextLessonSlug(Lesson $lesson): ?string
    {
        $next = Lesson::where('unit_id', $lesson->unit_id)
            ->where('order', '>', $lesson->order)
            ->orderBy('order')
            ->first();

        if ($next) {
            return $next->slug;
        }

        $nextUnit = Unit::where('level_id', $lesson->unit->level_id)
            ->where('order', '>', $lesson->unit->order)
            ->orderBy('order')
            ->first();

        if (! $nextUnit) {
            return null;
        }

        return Lesson::where('unit_id', $nextUnit->id)
            ->orderBy('order')
            ->value('slug');
    }
}
