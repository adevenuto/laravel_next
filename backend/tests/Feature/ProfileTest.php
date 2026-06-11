<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProfileTest extends TestCase
{
    use RefreshDatabase;

    public function test_unauthenticated_request_returns_401(): void
    {
        $this->postJson('/api/profile/onboarding', [
            'display_name' => 'Tester',
            'hometown' => 'Brooklyn',
        ])->assertStatus(401);
    }

    public function test_sets_display_name_and_hometown_and_returns_me_resource(): void
    {
        $user = User::factory()->create([
            'display_name' => null,
            'hometown' => null,
        ]);

        $response = $this->actingAs($user)->postJson('/api/profile/onboarding', [
            'display_name' => 'Ana',
            'hometown' => 'Guadalajara',
        ]);

        $response->assertOk()
            ->assertJsonPath('data.display_name', 'Ana')
            ->assertJsonPath('data.hometown', 'Guadalajara')
            ->assertJsonStructure([
                'data' => ['id', 'first_name', 'last_name', 'email', 'display_name', 'hometown', 'xp', 'vocab_counter', 'streak', 'badges', 'feature_flags'],
            ]);

        $this->assertSame('Ana', $user->fresh()->display_name);
        $this->assertSame('Guadalajara', $user->fresh()->hometown);
    }

    public function test_overwrites_existing_values_on_subsequent_call(): void
    {
        $user = User::factory()->create([
            'display_name' => 'Old',
            'hometown' => 'Oldtown',
        ]);

        $this->actingAs($user)->postJson('/api/profile/onboarding', [
            'display_name' => 'New',
            'hometown' => 'Newtown',
        ])->assertOk();

        $this->assertSame('New', $user->fresh()->display_name);
        $this->assertSame('Newtown', $user->fresh()->hometown);
    }

    public function test_missing_fields_return_422(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->postJson('/api/profile/onboarding', [])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['display_name', 'hometown']);
    }

    public function test_values_over_120_characters_are_rejected(): void
    {
        $user = User::factory()->create();
        $tooLong = str_repeat('a', 121);

        $this->actingAs($user)
            ->postJson('/api/profile/onboarding', [
                'display_name' => $tooLong,
                'hometown' => 'OK',
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['display_name']);
    }
}
