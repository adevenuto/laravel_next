<?php

namespace App\Http\Resources;

use App\Models\Lesson;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Shape contract for GET /api/lessons/{slug}.
 * Only emitted for lessons the user has unlocked (controller enforces the gate).
 *
 * @property Lesson $resource
 */
class LessonPlayResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $lesson = $this->resource;

        return [
            'lesson' => [
                'id' => $lesson->id,
                'slug' => $lesson->slug,
                'title' => $lesson->title,
                'order' => (int) $lesson->order,
                'skill_key' => $lesson->skill_key,
                'teach_screens' => $lesson->teach_screens ?? [],
                'unit' => [
                    'slug' => $lesson->unit->slug,
                    'title' => $lesson->unit->title,
                ],
            ],
            'exercises' => $lesson->exercises->map(fn ($ex) => [
                'id' => $ex->id,
                'component' => $ex->component,
                'payload' => $ex->payload,
                'order' => (int) $ex->order,
                'xp' => (int) $ex->xp,
            ])->all(),
        ];
    }
}
