<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use RuntimeException;

class AdminUserSeeder extends Seeder
{
    /**
     * Seed the default administrator account.
     *
     * Credentials MUST come from ADMIN_EMAIL / ADMIN_PASSWORD.
     * Production refuses the local default password.
     * Never commit real production credentials.
     */
    public function run(): void
    {
        $email = (string) env('ADMIN_EMAIL', 'admin@luvanexrealty.com');
        $name = (string) env('ADMIN_NAME', 'Luvanex Administrator');
        $password = env('ADMIN_PASSWORD');

        if (app()->environment('production')) {
            if (! filled($password) || $password === 'ChangeMeSecurely!') {
                throw new RuntimeException(
                    'Refusing to seed administrator in production without a strong ADMIN_PASSWORD. '.
                    'Set ADMIN_EMAIL and ADMIN_PASSWORD in the Forge environment before seeding.'
                );
            }
        }

        $password ??= 'ChangeMeSecurely!';

        User::query()->updateOrCreate(
            ['email' => $email],
            [
                'name' => $name,
                'password' => $password,
                'role' => User::ROLE_ADMIN,
                'is_admin' => true,
                'email_verified_at' => now(),
            ],
        );
    }
}
