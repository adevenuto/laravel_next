<?php

namespace App\Http\Resources;

use App\Models\Badge;
use App\Models\User;
use App\Models\UserCollection;
use App\Services\RewardService;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Shape contract for GET /api/me (§7 of the build doc).
 *
 * @property User $resource
 */
class MeResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $user = $this->resource;

        return [
            'id' => $user->id,
            'first_name' => $user->first_name,
            'last_name' => $user->last_name,
            'email' => $user->email,
            'display_name' => $user->display_name,
            'hometown' => $user->hometown,
            'xp' => (int) $user->xp,
            'vocab_counter' => (int) $user->vocab_counter,
            'streak' => [
                'count' => (int) $user->streak_count,
                'last_active_date' => $user->streak_last_active_date?->toDateString(),
            ],
            'badges' => $this->badges($user),
            'feature_flags' => $this->flags($user),
            'dev_snapshot_present' => $user->progress_snapshot !== null,
        ];
    }

    /** @return list<array<string, mixed>> */
    private function badges(User $user): array
    {
        $earned = UserCollection::where('user_id', $user->id)
            ->where('collectible_type', 'badge')
            ->get(['collectible_key', 'earned_at']);

        if ($earned->isEmpty()) {
            return [];
        }

        $badges = Badge::whereIn('key', $earned->pluck('collectible_key'))->get()->keyBy('key');

        return $earned->map(function ($row) use ($badges) {
            $badge = $badges->get($row->collectible_key);
            if (! $badge) {
                return;
            }

            return [
                'key' => $badge->key,
                'title' => $badge->title,
                'description' => $badge->description,
                'icon' => $badge->icon,
                'earned_at' => $row->earned_at?->toIso8601String(),
            ];
        })->filter()->values()->all();
    }

    /** @return array<string, bool> */
    private function flags(User $user): array
    {
        return app(RewardService::class)->flagsFor($user);
    }
}
