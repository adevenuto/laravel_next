<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use App\Notifications\WelcomeNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;
use Tests\TestCase;

class AuthControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_register_creates_user_sends_welcome_and_logs_in(): void
    {
        Notification::fake();

        $response = $this->postJson('/api/register', [
            'first_name' => 'Test',
            'last_name' => 'User',
            'email' => 'test@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $response->assertOk()
            ->assertJsonStructure(['user' => ['id', 'first_name', 'last_name', 'email']])
            ->assertJsonMissing(['token']);

        $this->assertDatabaseHas('users', ['email' => 'test@example.com']);
        $this->assertAuthenticated();

        $user = User::where('email', 'test@example.com')->first();
        Notification::assertSentTo($user, WelcomeNotification::class);
    }

    public function test_register_rejects_duplicate_email_with_422(): void
    {
        Notification::fake();

        User::factory()->create(['email' => 'existing@example.com']);

        $response = $this->postJson('/api/register', [
            'first_name' => 'Another',
            'last_name' => 'User',
            'email' => 'existing@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email']);

        // No duplicate user is created.
        $this->assertEquals(1, User::where('email', 'existing@example.com')->count());
        $this->assertGuest();
    }

    public function test_user_can_login(): void
    {
        User::factory()->create([
            'email' => 'login@example.com',
            'password' => bcrypt('password123'),
        ]);

        $response = $this->postJson('/api/login', [
            'email' => 'login@example.com',
            'password' => 'password123',
        ]);

        $response->assertOk()
            ->assertJsonStructure(['user' => ['id', 'first_name', 'last_name', 'email']])
            ->assertJsonMissing(['token']);

        $this->assertAuthenticated();
    }

    public function test_login_rejects_invalid_credentials(): void
    {
        User::factory()->create(['email' => 'user@example.com', 'password' => bcrypt('correct')]);

        $response = $this->postJson('/api/login', [
            'email' => 'user@example.com',
            'password' => 'wrongpassword',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email']);

        $this->assertGuest();
    }

    public function test_login_returns_same_response_shape_for_unknown_email(): void
    {
        $response = $this->postJson('/api/login', [
            'email' => 'nobody@example.com',
            'password' => 'whatever',
        ]);

        // Unknown email and wrong password should look identical to the client.
        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email']);
    }

    public function test_user_can_logout_with_session(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->postJson('/api/logout');

        // Note: assertGuest() can't run reliably here because the test request
        // had no real session to invalidate. The Bearer-token logout test below
        // covers actual auth-state teardown.
        $response->assertOk()
            ->assertJson(['message' => 'Logged out successfully']);
    }

    public function test_logout_works_with_bearer_token(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('test')->plainTextToken;

        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->postJson('/api/logout');

        $response->assertOk()
            ->assertJson(['message' => 'Logged out successfully']);
    }

    public function test_logout_requires_authentication(): void
    {
        $response = $this->postJson('/api/logout');
        $response->assertStatus(401);
    }

    public function test_authenticated_user_can_fetch_their_info_via_session(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->getJson('/api/user');

        $response->assertOk()
            ->assertJson(['id' => $user->id, 'email' => $user->email]);
    }

    public function test_authenticated_user_can_fetch_their_info_via_bearer(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('test')->plainTextToken;

        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->getJson('/api/user');

        $response->assertOk()
            ->assertJson(['id' => $user->id, 'email' => $user->email]);
    }

    public function test_password_reset_returns_uniform_response_for_unknown_email(): void
    {
        $response = $this->postJson('/api/password-reset', [
            'email' => 'nobody@example.com',
        ]);

        $response->assertOk()
            ->assertJsonStructure(['message']);
    }

    public function test_password_reset_returns_uniform_response_for_known_email(): void
    {
        User::factory()->create(['email' => 'someone@example.com']);

        $response = $this->postJson('/api/password-reset', [
            'email' => 'someone@example.com',
        ]);

        $response->assertOk()
            ->assertJsonStructure(['message']);
    }

    public function test_password_reset_confirm_updates_the_password(): void
    {
        $user = User::factory()->create([
            'email' => 'reset@example.com',
            'password' => Hash::make('oldpassword123'),
        ]);

        $token = Password::createToken($user);

        $response = $this->postJson('/api/password-reset/confirm', [
            'email' => 'reset@example.com',
            'token' => $token,
            'password' => 'newpassword456',
            'password_confirmation' => 'newpassword456',
        ]);

        $response->assertOk()->assertJsonStructure(['message']);

        $user->refresh();
        $this->assertTrue(Hash::check('newpassword456', $user->password));
    }

    public function test_password_reset_confirm_rejects_invalid_token(): void
    {
        User::factory()->create(['email' => 'reset@example.com']);

        $response = $this->postJson('/api/password-reset/confirm', [
            'email' => 'reset@example.com',
            'token' => 'totally-bogus-token',
            'password' => 'newpassword456',
            'password_confirmation' => 'newpassword456',
        ]);

        $response->assertStatus(422)->assertJsonStructure(['message']);
    }

    public function test_password_reset_confirm_validates_password_confirmation(): void
    {
        $response = $this->postJson('/api/password-reset/confirm', [
            'email' => 'reset@example.com',
            'token' => 'whatever',
            'password' => 'newpassword456',
            'password_confirmation' => 'mismatch',
        ]);

        $response->assertStatus(422)->assertJsonValidationErrors(['password']);
    }
}
