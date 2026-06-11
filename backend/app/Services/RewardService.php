<?php

namespace App\Services;

use App\Models\Unit;
use App\Models\User;
use App\Models\UserCollection;
use Illuminate\Support\Facades\DB;

class RewardService
{
    /**
     * Per-unit vocabulary counter bumps. Keyed by the unit's reward_badge_key so the
     * map survives unit slug churn. Add new units as Levels 2+ get authored.
     */
    private const VOCAB_BUMPS = [
        'the_alchemist' => 400,
    ];

    /**
     * Grant the reward for completing a unit: badge + (optional) vocab bump.
     * Idempotent — calling twice is a no-op. Transactional.
     *
     * Returns a small descriptor so the caller (ProgressService / API) can tell the
     * client what just fired, for animations / toasts.
     *
     * @return array{was_new: bool, badge_key: string, vocab_delta: int, feature_flag: ?string}
     */
    public function grantUnitReward(User $user, Unit $unit): array
    {
        return DB::transaction(function () use ($user, $unit): array {
            $existing = UserCollection::where('user_id', $user->id)
                ->where('collectible_type', 'badge')
                ->where('collectible_key', $unit->reward_badge_key)
                ->first();

            if ($existing) {
                return [
                    'was_new' => false,
                    'badge_key' => $unit->reward_badge_key,
                    'vocab_delta' => 0,
                    'feature_flag' => $unit->reward_feature_key,
                ];
            }

            UserCollection::create([
                'user_id' => $user->id,
                'collectible_type' => 'badge',
                'collectible_key' => $unit->reward_badge_key,
                'earned_at' => now(),
            ]);

            $vocabDelta = self::VOCAB_BUMPS[$unit->reward_badge_key] ?? 0;
            if ($vocabDelta > 0) {
                $user->increment('vocab_counter', $vocabDelta);
            }

            return [
                'was_new' => true,
                'badge_key' => $unit->reward_badge_key,
                'vocab_delta' => $vocabDelta,
                'feature_flag' => $unit->reward_feature_key,
            ];
        });
    }

    /**
     * Derive the set of active feature flags for a user, based on the units they've
     * earned badges for. Source of truth is `units.reward_feature_key`.
     *
     * @return array<string, bool>
     */
    public function flagsFor(User $user): array
    {
        $earnedBadgeKeys = UserCollection::where('user_id', $user->id)
            ->where('collectible_type', 'badge')
            ->pluck('collectible_key');

        $flags = Unit::whereIn('reward_badge_key', $earnedBadgeKeys)
            ->whereNotNull('reward_feature_key')
            ->pluck('reward_feature_key')
            ->all();

        return array_fill_keys($flags, true);
    }
}
