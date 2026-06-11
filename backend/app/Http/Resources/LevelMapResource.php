<?php

namespace App\Http\Resources;

use App\Models\Lesson;
use App\Models\Level;
use App\Models\User;
use App\Models\UserLessonProgress;
use App\Services\ProgressService;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Shape contract for GET /api/levels/{level:number}/map.
 * Per-lesson lock_state computed server-side via ProgressService — never trust the client.
 *
 * @property Level $resource
 */
class LevelMapResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $level = $this->resource;
        $user = $request->user();

        $progress = $this->progressByLessonId($user, $level);

        return [
            'level' => [
                'id' => $level->id,
                'number' => $level->number,
                'slug' => $level->slug,
                'title' => $level->title,
                'promise_text' => $level->promise_text,
            ],
            'units' => $level->units->map(function ($unit) use ($user, $progress) {
                return [
                    'id' => $unit->id,
                    'slug' => $unit->slug,
                    'title' => $unit->title,
                    'tagline' => $unit->tagline,
                    'order' => (int) $unit->order,
                    'reward_badge_key' => $unit->reward_badge_key,
                    'reward_feature_key' => $unit->reward_feature_key,
                    'lessons' => $unit->lessons->map(fn ($lesson) => $this->lessonRow($lesson, $user, $progress))->all(),
                ];
            })->all(),
        ];
    }

    private function lessonRow(Lesson $lesson, User $user, array $progress): array
    {
        $row = $progress[$lesson->id] ?? null;

        return [
            'id' => $lesson->id,
            'slug' => $lesson->slug,
            'title' => $lesson->title,
            'order' => (int) $lesson->order,
            'skill_key' => $lesson->skill_key,
            'lock_state' => app(ProgressService::class)->unlockState($user, $lesson),
            'best_score' => $row?->best_score !== null ? (int) $row->best_score : null,
            'completed_at' => $row?->completed_at?->toIso8601String(),
        ];
    }

    /** @return array<int, UserLessonProgress> */
    private function progressByLessonId(User $user, Level $level): array
    {
        $lessonIds = $level->units->flatMap(fn ($u) => $u->lessons->pluck('id'))->all();

        return UserLessonProgress::where('user_id', $user->id)
            ->whereIn('lesson_id', $lessonIds)
            ->get()
            ->keyBy('lesson_id')
            ->all();
    }
}
