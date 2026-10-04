<?php

namespace Tests\Feature\Api\V1;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    /** Header yang membuat permintaan dianggap berasal dari frontend terdaftar. */
    private const FRONTEND = ['Referer' => 'http://localhost:3000'];

    public function test_admin_can_login_with_valid_credentials(): void
    {
        $user = User::factory()->create(['password' => 'rahasia-123']);

        $this->postJson('/api/v1/auth/login', [
            'email' => $user->email,
            'password' => 'rahasia-123',
        ], self::FRONTEND)
            ->assertOk()
            ->assertJsonPath('data.email', $user->email)
            ->assertJsonMissingPath('data.password');

        $this->assertAuthenticatedAs($user);
    }

    public function test_login_fails_with_wrong_password(): void
    {
        $user = User::factory()->create(['password' => 'rahasia-123']);

        $this->postJson('/api/v1/auth/login', [
            'email' => $user->email,
            'password' => 'salah',
        ], self::FRONTEND)
            ->assertStatus(422)
            ->assertJsonValidationErrors('email');

        $this->assertGuest();
    }

    public function test_login_requires_email_and_password(): void
    {
        $this->postJson('/api/v1/auth/login', [], self::FRONTEND)
            ->assertStatus(422)
            ->assertJsonValidationErrors(['email', 'password']);
    }

    public function test_login_is_rate_limited(): void
    {
        $user = User::factory()->create(['password' => 'rahasia-123']);

        foreach (range(1, 5) as $attempt) {
            $this->postJson('/api/v1/auth/login', [
                'email' => $user->email,
                'password' => 'salah',
            ], self::FRONTEND)->assertStatus(422);
        }

        $this->postJson('/api/v1/auth/login', [
            'email' => $user->email,
            'password' => 'salah',
        ], self::FRONTEND)->assertStatus(429);
    }

    public function test_login_must_come_from_the_registered_frontend(): void
    {
        $user = User::factory()->create(['password' => 'rahasia-123']);

        $this->postJson('/api/v1/auth/login', [
            'email' => $user->email,
            'password' => 'rahasia-123',
        ])->assertStatus(400);
    }

    public function test_me_returns_the_authenticated_admin(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->getJson('/api/v1/auth/me')
            ->assertOk()
            ->assertJsonPath('data.email', $user->email);
    }

    public function test_me_requires_authentication(): void
    {
        $this->getJson('/api/v1/auth/me')->assertUnauthorized();
    }

    public function test_admin_can_logout(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->postJson('/api/v1/auth/logout', [], self::FRONTEND)
            ->assertNoContent();

        $this->assertGuest('web');
    }

    public function test_logout_requires_authentication(): void
    {
        $this->postJson('/api/v1/auth/logout', [], self::FRONTEND)->assertUnauthorized();
    }
}
