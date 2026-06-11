<?php

namespace Tests\Feature\Services;

use App\Models\Level;
use App\Models\Unit;
use App\Models\User;
use App\Models\UserCollection;
use App\Services\RewardService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RewardServiceTest extends TestCase
{
    use RefreshDatabase;

    private RewardService $rewards;

    protected function setUp(): void
    {
        parent::setUp();
        $this->rewards = new RewardService;
    }

    public function test_granting_a_unit_reward_inserts_a_badge_collection_row(): void
    {
        $user = User::factory()->create();
        $unit = $this->makeUnit(badgeKey: 'the_decoder', featureKey: 'pronunciation_hints');

        $result = $this->rewards->grantUnitReward($user, $unit);

        $this->assertTrue($result['was_new']);
        $this->assertSame('the_decoder', $result['badge_key']);
        $this->assertSame('pronunciation_hints', $result['feature_flag']);
        $this->assertSame(0, $result['vocab_delta']);
        $this->assertDatabaseHas('user_collections', [
            'user_id' => $user->id,
            'collectible_type' => 'badge',
            'collectible_key' => 'the_decoder',
        ]);
    }

    public function test_granting_a_unit_reward_is_idempotent(): void
    {
        $user = User::factory()->create();
        $unit = $this->makeUnit(badgeKey: 'the_decoder');

        $first = $this->rewards->grantUnitReward($user, $unit);
        $second = $this->rewards->grantUnitReward($user, $unit);

        $this->assertTrue($first['was_new']);
        $this->assertFalse($second['was_new']);
        $this->assertSame(1, UserCollection::where('user_id', $user->id)->count());
    }

    public function test_alchemist_unit_bumps_vocab_counter_by_400_once(): void
    {
        $user = User::factory()->create();
        $unit = $this->makeUnit(badgeKey: 'the_alchemist');

        $first = $this->rewards->grantUnitReward($user, $unit);
        $second = $this->rewards->grantUnitReward($user, $unit);

        $this->assertSame(400, $first['vocab_delta']);
        $this->assertSame(0, $second['vocab_delta']);
        $this->assertSame(400, (int) $user->fresh()->vocab_counter);
    }

    public function test_non_alchemist_unit_does_not_bump_vocab(): void
    {
        $user = User::factory()->create();
        $unit = $this->makeUnit(badgeKey: 'first_contact');

        $result = $this->rewards->grantUnitReward($user, $unit);

        $this->assertSame(0, $result['vocab_delta']);
        $this->assertSame(0, (int) $user->fresh()->vocab_counter);
    }

    public function test_flags_for_returns_feature_keys_for_earned_units(): void
    {
        $user = User::factory()->create();
        $decoder = $this->makeUnit(badgeKey: 'the_decoder', featureKey: 'pronunciation_hints');
        $unsinkable = $this->makeUnit(badgeKey: 'unsinkable', featureKey: 'como_se_dice_button');
        $alchemist = $this->makeUnit(badgeKey: 'the_alchemist', featureKey: null);

        $this->rewards->grantUnitReward($user, $decoder);
        $this->rewards->grantUnitReward($user, $alchemist);

        $flags = $this->rewards->flagsFor($user);

        $this->assertArrayHasKey('pronunciation_hints', $flags);
        $this->assertTrue($flags['pronunciation_hints']);
        $this->assertArrayNotHasKey('como_se_dice_button', $flags);

        $this->rewards->grantUnitReward($user, $unsinkable);
        $flags = $this->rewards->flagsFor($user);
        $this->assertTrue($flags['como_se_dice_button']);
    }

    public function test_flags_for_returns_empty_when_user_has_no_badges(): void
    {
        $user = User::factory()->create();

        $this->assertSame([], $this->rewards->flagsFor($user));
    }

    private function makeUnit(string $badgeKey, ?string $featureKey = null): Unit
    {
        return Unit::factory()->create([
            'level_id' => Level::factory(),
            'reward_badge_key' => $badgeKey,
            'reward_feature_key' => $featureKey,
        ]);
    }
}
