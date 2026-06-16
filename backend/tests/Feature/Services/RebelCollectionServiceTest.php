<?php

namespace Tests\Feature\Services;

use App\Models\User;
use App\Models\UserCollection;
use App\Services\RebelCollectionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RebelCollectionServiceTest extends TestCase
{
    use RefreshDatabase;

    private RebelCollectionService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new RebelCollectionService;
    }

    public function test_capture_inserts_new_rebel_rows(): void
    {
        $user = User::factory()->create();

        $captured = $this->service->capture($user, ['traduccion', 'explicacion', 'vacaciones']);

        $this->assertSame(['traduccion', 'explicacion', 'vacaciones'], $captured);
        $this->assertSame(
            3,
            UserCollection::where('user_id', $user->id)
                ->where('collectible_type', 'rebel_word')
                ->count()
        );
    }

    public function test_capture_is_idempotent_for_already_collected_rebels(): void
    {
        $user = User::factory()->create();

        $this->service->capture($user, ['traduccion']);
        $second = $this->service->capture($user, ['traduccion']);

        $this->assertSame([], $second, 'Already-collected keys must be skipped.');
        $this->assertSame(
            1,
            UserCollection::where('user_id', $user->id)
                ->where('collectible_type', 'rebel_word')
                ->count()
        );
    }

    public function test_capture_returns_only_newly_inserted_in_a_mixed_call(): void
    {
        $user = User::factory()->create();

        $this->service->capture($user, ['traduccion']);
        $captured = $this->service->capture($user, ['traduccion', 'explicacion', 'vacaciones']);

        $this->assertSame(['explicacion', 'vacaciones'], $captured);
        $this->assertSame(
            3,
            UserCollection::where('user_id', $user->id)
                ->where('collectible_type', 'rebel_word')
                ->count()
        );
    }

    public function test_capture_with_empty_array_is_a_noop(): void
    {
        $user = User::factory()->create();

        $captured = $this->service->capture($user, []);

        $this->assertSame([], $captured);
        $this->assertSame(0, UserCollection::where('user_id', $user->id)->count());
    }
}
