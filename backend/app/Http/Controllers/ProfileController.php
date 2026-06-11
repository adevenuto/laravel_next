<?php

namespace App\Http\Controllers;

use App\Http\Resources\MeResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProfileController extends Controller
{
    /**
     * Set / update the display_name + hometown pair used by SlotFill exercises
     * and dialogue scenes (boss level inserts the user's hometown).
     *
     * The build doc names this `/api/profile/onboarding` since the same call
     * powers both first-time onboarding and any later profile edit.
     */
    public function onboarding(Request $request): JsonResource
    {
        $data = $request->validate([
            'display_name' => 'required|string|max:120',
            'hometown' => 'required|string|max:120',
        ]);

        $user = $request->user();
        $user->update($data);

        return new MeResource($user->fresh());
    }
}
