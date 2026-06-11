<?php

namespace App\Http\Controllers;

use App\Http\Resources\LessonPlayResource;
use App\Models\Lesson;
use App\Services\ProgressService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class LessonController extends Controller
{
    public function __construct(private readonly ProgressService $progress) {}

    public function show(Request $request, Lesson $lesson): LessonPlayResource|JsonResponse
    {
        $state = $this->progress->unlockState($request->user(), $lesson);

        if ($state === 'locked') {
            // 403 + no payload — server is the source of truth (§9). Don't leak
            // teach_screens or exercise payloads for content the user hasn't earned.
            return response()->json(['error' => 'locked'], 403);
        }

        $lesson->load(['exercises' => fn ($q) => $q->orderBy('order'), 'unit']);

        return new LessonPlayResource($lesson);
    }
}
