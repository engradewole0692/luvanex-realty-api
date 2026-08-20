<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\RateLimiter;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withHeader('Origin', 'http://localhost:8080');
        $this->withHeader('Referer', 'http://localhost:8080/admin/login');
    }

    public function test_login_succeeds_with_valid_administrator_credentials(): void
    {
        $admin = User::factory()->admin()->create([
            'email' => 'admin@example.com',
            'password' => 'SecretPass123!',
        ]);

        $response = $this->postJson('/api/v1/auth/login', [
            'email' => 'admin@example.com',
            'password' => 'SecretPass123!',
        ]);

        $response->assertOk()
            ->assertJsonPath('user.email', 'admin@example.com')
            ->assertJsonPath('user.is_admin', true)
            ->assertJsonMissingPath('user.password');

        $this->assertAuthenticatedAs($admin);
    }

    public function test_login_fails_with_invalid_credentials(): void
    {
        User::factory()->admin()->create([
            'email' => 'admin@example.com',
            'password' => 'SecretPass123!',
        ]);

        $response = $this->postJson('/api/v1/auth/login', [
            'email' => 'admin@example.com',
            'password' => 'wrong-password',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email']);

        $this->assertGuest();
    }

    public function test_login_validation_requires_email_and_password(): void
    {
        $response = $this->postJson('/api/v1/auth/login', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email', 'password']);
    }

    public function test_unauthenticated_request_to_auth_user_is_rejected(): void
    {
        $this->getJson('/api/v1/auth/user')
            ->assertUnauthorized();
    }

    public function test_authenticated_administrator_can_retrieve_profile(): void
    {
        $admin = User::factory()->admin()->create([
            'name' => 'Admin User',
            'email' => 'admin@example.com',
        ]);

        $this->actingAs($admin)
            ->getJson('/api/v1/auth/user')
            ->assertOk()
            ->assertJsonPath('user.name', 'Admin User')
            ->assertJsonPath('user.email', 'admin@example.com')
            ->assertJsonMissingPath('user.password');
    }

    public function test_logout_invalidates_authentication(): void
    {
        User::factory()->admin()->create([
            'email' => 'admin@example.com',
            'password' => 'SecretPass123!',
        ]);

        $this->postJson('/api/v1/auth/login', [
            'email' => 'admin@example.com',
            'password' => 'SecretPass123!',
        ])->assertOk();

        $this->assertAuthenticated('web');

        $this->postJson('/api/v1/auth/logout')
            ->assertOk()
            ->assertJsonPath('message', 'Logged out.');

        $this->assertGuest('web');
    }

    public function test_non_admin_users_cannot_access_admin_endpoints(): void
    {
        $user = User::factory()->create([
            'email' => 'member@example.com',
            'password' => 'SecretPass123!',
            'is_admin' => false,
            'role' => User::ROLE_USER,
        ]);

        $this->postJson('/api/v1/auth/login', [
            'email' => 'member@example.com',
            'password' => 'SecretPass123!',
        ])->assertStatus(422);

        $this->assertGuest();

        $this->actingAs($user)
            ->getJson('/api/v1/auth/user')
            ->assertForbidden();
    }

    public function test_login_throttling_works(): void
    {
        User::factory()->admin()->create([
            'email' => 'admin@example.com',
            'password' => 'SecretPass123!',
        ]);

        RateLimiter::clear(strtolower('admin@example.com').'|127.0.0.1');

        for ($i = 0; $i < 5; $i++) {
            $this->postJson('/api/v1/auth/login', [
                'email' => 'admin@example.com',
                'password' => 'wrong-password',
            ])->assertStatus(422);
        }

        $this->postJson('/api/v1/auth/login', [
            'email' => 'admin@example.com',
            'password' => 'wrong-password',
        ])->assertStatus(429);
    }

    public function test_csrf_endpoint_returns_success(): void
    {
        $this->get('/api/v1/auth/csrf')
            ->assertNoContent();
    }
}
