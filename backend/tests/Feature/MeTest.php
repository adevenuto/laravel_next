<?php

namespace Tests\Feature;

use App\Models\Unit;
use App\Models\User;
use App\Models\UserCollection;
use App\Services\RewardService;
use Database\Seeders\BadgeSeeder;
use Database\Seeders\ExerciseSeeder;
use Database\Seeders\LessonSeeder;
use Database\Seeders\LevelSeeder;
use Database\Seeders\UnitSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MeTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed([BadgeSeeder::class, LevelSeeder::class, UnitSeeder::class, LessonSeeder::class, ExerciseSeeder::class]);
    }

    public function test_unauthenticated_request_returns_401(): void
    {
        $this->getJson('/api/me')->assertStatus(401);
    }

    public function test_me_returns_the_section_7_shape(): void
    {
        $user = User::factory()->create([
            'display_name' => 'Tester',
            'hometown' => 'Brooklyn',
            'xp' => 75,
            'vocab_counter' => 12,
        ]);

        $response = $this->actingAs($user)->getJson('/api/me');

        $response->assertOk()->assertJsonStructure([
            'data' => [
                'id', 'first_name', 'last_name', 'email',
                'display_name', 'hometown', 'xp', 'vocab_counter',
                'streak' => ['count', 'last_active_date'],
                'badges', 'feature_flags',
            ],
        ]);
        $response->assertJsonPath('data.xp', 75);
        $response->assertJsonPath('data.vocab_counter', 12);
        $response->assertJsonPath('data.display_name', 'Tester');
        $response->assertJsonPath('data.hometown', 'Brooklyn');
    }

    public function test_fresh_user_has_no_badges_and_no_flags(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->getJson('/api/me');

        $response->assertOk();
        $this->assertSame([], $response->json('data.badges'));
        $this->assertSame([], $response->json('data.feature_flags'));
    }

    public function test_pronunciation_hints_flag_is_true_after_the_decoder_is_granted(): void
    {
        $user = User::factory()->create();
        $unit = Unit::where('slug', 'unit-1-1-vowels')->firstOrFail();

        app(RewardService::class)->grantUnitReward($user, $unit);

        $response = $this->actingAs($user)->getJson('/api/me');

        $response->assertOk();
        $response->assertJsonPath('data.feature_flags.pronunciation_hints', true);
        $badges = $response->json('data.badges');
        $this->assertCount(1, $badges);
        $this->assertSame('the_decoder', $badges[0]['key']);
        $this->assertSame('The Decoder', $badges[0]['title']);

        // Sanity: the badge row should be the one we just granted.
        $this->assertSame(1, UserCollection::where('user_id', $user->id)->count());
    }
}
