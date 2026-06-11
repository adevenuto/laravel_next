<?php

namespace App\Http\Controllers;

use App\Http\Resources\LevelMapResource;
use App\Models\Level;
use Illuminate\Http\Resources\Json\JsonResource;

class LevelMapController extends Controller
{
    public function show(Level $level): JsonResource
    {
        $level->load(['units.lessons' => fn ($q) => $q->orderBy('order'), 'units' => fn ($q) => $q->orderBy('order')]);

        return new LevelMapResource($level);
    }
}
