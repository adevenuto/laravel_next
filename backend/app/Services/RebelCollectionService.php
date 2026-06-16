<?php

namespace App\Services;

use App\Models\User;
use App\Models\UserCollection;

class RebelCollectionService
{
    /**
     * Capture rebel-word keys into the user's collection. Idempotent — already-collected
     * keys are skipped silently. Returns the keys that were newly inserted.
     *
     * @param  array<int, string>  $keys
     * @return array<int, string>
     */
    public function capture(User $user, array $keys): array
    {
        $newlyCaptured = [];

        foreach ($keys as $key) {
            $row = UserCollection::firstOrCreate(
                [
                    'user_id' => $user->id,
                    'collectible_type' => 'rebel_word',
                    'collectible_key' => $key,
                ],
                ['earned_at' => now()]
            );

            if ($row->wasRecentlyCreated) {
                $newlyCaptured[] = $key;
            }
        }

        return $newlyCaptured;
    }
}
