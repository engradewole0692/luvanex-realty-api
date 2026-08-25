<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Database\Seeders\AdminUserSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use RuntimeException;
use Tests\TestCase;

class AdminUserSeederTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'admin.name' => 'Luvanex Administrator',
            'admin.email' => null,
            'admin.password' => null,
        ]);
    }

    public function test_seeder_creates_administrator_from_configuration(): void
    {
        config([
            'admin.name' => 'Configured Admin',
            'admin.email' => 'admin@example.com',
            'admin.password' => 'ValidStrongPass123!',
        ]);

        $this->seedWithoutPrompt();

        $user = User::query()->where('email', 'admin@example.com')->first();

        $this->assertNotNull($user);
        $this->assertSame('Configured Admin', $user->name);
        $this->assertTrue($user->is_admin);
        $this->assertSame(User::ROLE_ADMIN, $user->role);
        $this->assertTrue(Hash::check('ValidStrongPass123!', $user->password));
        $this->assertNotSame('ValidStrongPass123!', $user->getAttributes()['password']);
        $this->assertArrayNotHasKey('password', $user->toArray());
    }

    public function test_seeder_updates_existing_administrator_from_configuration(): void
    {
        User::factory()->admin()->create([
            'email' => 'admin@example.com',
            'name' => 'Old Name',
            'password' => 'OldPassword123!',
        ]);

        config([
            'admin.email' => 'admin@example.com',
            'admin.name' => 'Updated Admin',
            'admin.password' => 'NewStrongPass123!',
        ]);

        $this->seedWithoutPrompt();

        $user = User::query()->where('email', 'admin@example.com')->first();

        $this->assertSame(1, User::query()->where('email', 'admin@example.com')->count());
        $this->assertSame('Updated Admin', $user->name);
        $this->assertTrue(Hash::check('NewStrongPass123!', $user->password));
        $this->assertFalse(Hash::check('OldPassword123!', $user->password));
    }

    public function test_local_seeder_uses_development_defaults_when_config_is_empty(): void
    {
        $this->assertFalse($this->app->environment('production'));

        $this->seedWithoutPrompt();

        $user = User::query()->where('email', 'admin@luvanexrealty.com')->first();

        $this->assertNotNull($user);
        $this->assertTrue(Hash::check('ChangeMeSecurely!', $user->password));
        $this->assertArrayNotHasKey('password', $user->toArray());
    }

    public function test_production_refuses_missing_credentials(): void
    {
        $this->app->instance('env', 'production');

        try {
            $this->seedWithoutPrompt();
            $this->fail('Expected the production seeder to refuse missing credentials.');
        } catch (RuntimeException $e) {
            $this->assertStringContainsString('ADMIN_PASSWORD', $e->getMessage());
            $this->assertStringNotContainsString('ChangeMeSecurely!', $e->getMessage());
            $this->assertSame(0, User::query()->count());
        }
    }

    public function test_production_refuses_missing_email_even_when_password_is_set(): void
    {
        $this->app->instance('env', 'production');
        config([
            'admin.email' => '',
            'admin.password' => 'ValidStrongPass123!',
        ]);

        $this->expectException(RuntimeException::class);

        $this->seedWithoutPrompt();
    }

    public function test_production_refuses_default_password_and_does_not_expose_it(): void
    {
        $this->app->instance('env', 'production');
        config([
            'admin.email' => 'admin@example.com',
            'admin.password' => 'ChangeMeSecurely!',
        ]);

        try {
            $this->seedWithoutPrompt();
            $this->fail('Expected the production seeder to refuse the default password.');
        } catch (RuntimeException $e) {
            $this->assertStringContainsString('strong ADMIN_PASSWORD', $e->getMessage());
            $this->assertStringNotContainsString('ChangeMeSecurely!', $e->getMessage());
            $this->assertStringNotContainsString('admin@example.com', $e->getMessage());
            $this->assertSame(0, User::query()->count());
        }
    }

    public function test_production_creates_administrator_with_valid_configuration(): void
    {
        $this->app->instance('env', 'production');
        config([
            'admin.name' => 'Production Admin',
            'admin.email' => 'admin@example.com',
            'admin.password' => 'ValidStrongPass123!',
        ]);

        $this->seedWithoutPrompt();

        $user = User::query()->where('email', 'admin@example.com')->first();

        $this->assertNotNull($user);
        $this->assertSame('Production Admin', $user->name);
        $this->assertTrue($user->isAdministrator());
        $this->assertTrue(Hash::check('ValidStrongPass123!', $user->password));
        $this->assertArrayNotHasKey('password', $user->toArray());
    }

    public function test_seeder_trims_email_and_password_from_configuration(): void
    {
        config([
            'admin.name' => 'Whitespace Admin',
            'admin.email' => '  Admin@Example.com  ',
            'admin.password' => '  ValidStrongPass123!  ',
        ]);

        $this->seedWithoutPrompt();

        $user = User::query()->where('email', 'admin@example.com')->first();

        $this->assertNotNull($user);
        $this->assertSame('admin@example.com', $user->email);
        $this->assertTrue(Hash::check('ValidStrongPass123!', $user->password));
        $this->assertFalse(Hash::check('  ValidStrongPass123!  ', $user->password));
    }

    public function test_seeder_reads_config_even_when_environment_variables_differ(): void
    {
        putenv('ADMIN_EMAIL=env-only@example.com');
        $_ENV['ADMIN_EMAIL'] = 'env-only@example.com';

        config([
            'admin.name' => 'Config Admin',
            'admin.email' => 'config-admin@example.com',
            'admin.password' => 'ConfigStrongPass123!',
        ]);

        $this->seedWithoutPrompt();

        $this->assertTrue(User::query()->where('email', 'config-admin@example.com')->exists());
        $this->assertFalse(User::query()->where('email', 'env-only@example.com')->exists());

        putenv('ADMIN_EMAIL');
        unset($_ENV['ADMIN_EMAIL']);
    }

    public function test_production_refuses_short_password_without_exposing_it(): void
    {
        $this->app->instance('env', 'production');
        $short = 'Short1!';
        config([
            'admin.email' => 'admin@example.com',
            'admin.password' => $short,
        ]);

        try {
            $this->seedWithoutPrompt();
            $this->fail('Expected the production seeder to refuse a short password.');
        } catch (RuntimeException $e) {
            $this->assertStringContainsString('strong ADMIN_PASSWORD', $e->getMessage());
            $this->assertStringNotContainsString($short, $e->getMessage());
            $this->assertSame(0, User::query()->count());
        }
    }

    public function test_admin_configuration_keys_exist(): void
    {
        $this->assertIsArray(config('admin'));
        $this->assertArrayHasKey('email', config('admin'));
        $this->assertArrayHasKey('password', config('admin'));
        $this->assertArrayHasKey('name', config('admin'));
    }

    protected function seedWithoutPrompt(): void
    {
        $this->app->make(AdminUserSeeder::class)->run();
    }
}
