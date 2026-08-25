<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Database\Seeders\AdminUserSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
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

        config([
            'admin.email' => null,
            'admin.password' => null,
            'admin.name' => 'Luvanex Administrator',
        ]);
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

    public function test_csrf_endpoint_returns_token_for_json_clients(): void
    {
        $response = $this->getJson('/api/v1/auth/csrf');

        $response->assertOk()
            ->assertJsonStructure(['csrf_token']);

        $this->assertNotEmpty($response->json('csrf_token'));
    }

    public function test_sanctum_csrf_cookie_endpoint_returns_success(): void
    {
        $this->getJson('/sanctum/csrf-cookie')
            ->assertNoContent();
    }

    public function test_stateful_api_cookies_honor_configured_same_site(): void
    {
        config([
            'session.same_site' => 'none',
            'session.secure' => true,
        ]);

        $response = $this->getJson('/api/v1/auth/csrf');

        $cookie = collect($response->headers->getCookies())
            ->first(fn ($cookie) => $cookie->getName() === 'XSRF-TOKEN');

        $this->assertNotNull($cookie);
        $this->assertSame('none', $cookie->getSameSite());
    }

    public function test_spa_login_initializes_csrf_then_authenticates(): void
    {
        $admin = User::factory()->admin()->create([
            'email' => 'admin@example.com',
            'password' => 'SecretPass123!',
        ]);

        $this->getJson('/sanctum/csrf-cookie')->assertNoContent();

        $token = $this->getJson('/api/v1/auth/csrf')->json('csrf_token');

        $this->withHeader('X-CSRF-TOKEN', $token)
            ->postJson('/api/v1/auth/login', [
                'email' => 'admin@example.com',
                'password' => 'SecretPass123!',
            ])
            ->assertOk()
            ->assertJsonPath('user.email', 'admin@example.com');

        $this->assertAuthenticatedAs($admin);
    }

    public function test_seeded_admin_config_credentials_can_authenticate(): void
    {
        config([
            'admin.name' => 'Luvanex Administrator',
            'admin.email' => 'admin@example.com',
            'admin.password' => 'ValidStrongPass123!',
        ]);

        $this->seed(AdminUserSeeder::class);

        $user = User::query()->where('email', 'admin@example.com')->firstOrFail();

        $this->assertTrue(Hash::check(config('admin.password'), $user->password));

        $this->postJson('/api/v1/auth/login', [
            'email' => 'Admin@Example.com',
            'password' => config('admin.password'),
        ])
            ->assertOk()
            ->assertJsonPath('user.email', 'admin@example.com')
            ->assertJsonPath('user.is_admin', true);

        $this->assertAuthenticatedAs($user);
    }

    public function test_configured_admin_email_can_authenticate_even_if_admin_flags_are_missing(): void
    {
        config([
            'admin.email' => 'admin@example.com',
            'admin.password' => 'ValidStrongPass123!',
        ]);

        $user = User::factory()->create([
            'email' => 'admin@example.com',
            'password' => 'ValidStrongPass123!',
            'is_admin' => false,
            'role' => User::ROLE_USER,
        ]);

        $this->assertTrue(Hash::check(config('admin.password'), $user->password));
        $this->assertFalse($user->is_admin);
        $this->assertSame(User::ROLE_USER, $user->role);
        $this->assertTrue($user->isAdministrator());

        $this->postJson('/api/v1/auth/login', [
            'email' => 'admin@example.com',
            'password' => config('admin.password'),
        ])
            ->assertOk()
            ->assertJsonPath('user.email', 'admin@example.com')
            ->assertJsonPath('user.is_admin', true);

        $this->assertAuthenticatedAs($user);
    }

    public function test_login_endpoint_matches_the_spa_authentication_flow(): void
    {
        $password = 'ValidStrongPass123!';

        $admin = User::factory()->admin()->create([
            'email' => 'admin@example.com',
            'password' => $password,
        ]);

        $this->assertSame('web', config('auth.defaults.guard'));
        $this->assertTrue(Hash::check($password, $admin->getAuthPassword()));
        $this->assertTrue($admin->isAdministrator());

        $this->assertTrue(Auth::guard('web')->attempt([
            'email' => 'admin@example.com',
            'password' => $password,
        ]));
        $this->assertAuthenticatedAs($admin, 'web');
        Auth::guard('web')->logout();
        $this->assertGuest('web');

        $this->getJson('/sanctum/csrf-cookie')->assertNoContent();
        $token = $this->getJson('/api/v1/auth/csrf')->json('csrf_token');

        $response = $this->withHeader('X-CSRF-TOKEN', $token)
            ->postJson('/api/v1/auth/login', [
                'email' => 'admin@example.com',
                'password' => $password,
            ]);

        $response->assertOk()
            ->assertJsonPath('message', 'Authenticated.')
            ->assertJsonPath('user.id', $admin->id)
            ->assertJsonPath('user.email', 'admin@example.com')
            ->assertJsonPath('user.role', User::ROLE_ADMIN)
            ->assertJsonPath('user.is_admin', true)
            ->assertJsonMissingPath('user.data')
            ->assertJsonMissingPath('user.password');

        $this->assertAuthenticatedAs($admin, 'web');
        $this->assertTrue(Auth::guard('web')->user()->isAdministrator());

        $this->getJson('/api/v1/auth/user')
            ->assertOk()
            ->assertJsonPath('user.id', $admin->id)
            ->assertJsonPath('user.email', 'admin@example.com')
            ->assertJsonPath('user.is_admin', true)
            ->assertJsonMissingPath('user.data');
    }

    public function test_configured_admin_can_login_when_env_password_has_incidental_whitespace(): void
    {
        $plain = 'ValidStrongPass123!';
        $stored = $plain."\n";

        config([
            'admin.email' => 'admin@example.com',
            'admin.password' => $stored,
        ]);

        $user = User::factory()->admin()->create([
            'email' => 'admin@example.com',
            'password' => $stored,
        ]);

        $this->assertTrue(Hash::check($stored, $user->getAuthPassword()));
        $this->assertFalse(Hash::check($plain, $user->getAuthPassword()));
        $this->assertFalse(Auth::guard('web')->attempt([
            'email' => 'admin@example.com',
            'password' => $plain,
        ]));
        $this->assertGuest('web');

        $this->postJson('/api/v1/auth/login', [
            'email' => 'admin@example.com',
            'password' => $plain,
        ])
            ->assertOk()
            ->assertJsonPath('user.id', $user->id)
            ->assertJsonPath('user.is_admin', true);

        $this->assertAuthenticatedAs($user, 'web');
    }

    public function test_login_rejects_wrong_password_for_configured_admin(): void
    {
        config([
            'admin.email' => 'admin@example.com',
            'admin.password' => 'ValidStrongPass123!',
        ]);

        User::factory()->admin()->create([
            'email' => 'admin@example.com',
            'password' => 'ValidStrongPass123!',
        ]);

        $this->postJson('/api/v1/auth/login', [
            'email' => 'admin@example.com',
            'password' => 'DefinitelyWrongPass123!',
        ])
            ->assertStatus(422)
            ->assertJsonPath('message', 'These credentials do not match our records.');

        $this->assertGuest('web');
    }
}
