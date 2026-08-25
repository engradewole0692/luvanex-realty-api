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
     * Credentials MUST come from config('admin.*'), which is sourced from
     * ADMIN_EMAIL / ADMIN_PASSWORD / ADMIN_NAME in the server environment.
     * Production refuses missing or unsafe passwords.
     * Never commit real production credentials.
     */
    public function run(): void
    {
        $email = strtolower($this->stringConfig('admin.email'));
        $name = $this->stringConfig('admin.name') ?: 'Luvanex Administrator';
        $password = $this->normalizedPassword(config('admin.password'));

        if (app()->environment('production')) {
            if ($email === '' || $password === null || $this->isUnsafeProductionPassword($password)) {
                throw new RuntimeException(
                    'Refusing to seed administrator in production without a strong ADMIN_PASSWORD. '.
                    'Set ADMIN_EMAIL and ADMIN_PASSWORD in the Forge environment, then run '.
                    '`php artisan config:cache` before seeding.'
                );
            }
        } else {
            $email = $email !== '' ? $email : 'admin@luvanexrealty.com';
            $password ??= 'ChangeMeSecurely!';
        }

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

    protected function stringConfig(string $key): string
    {
        $value = config($key);

        return is_string($value) ? trim($value) : '';
    }

    /**
     * Persist the password humans type, not incidental .env whitespace.
     * Login does not trim passwords; the seeder must hash the trimmed secret.
     */
    protected function normalizedPassword(mixed $password): ?string
    {
        if (! is_string($password)) {
            return null;
        }

        $normalized = trim($password);

        return $normalized !== '' ? $normalized : null;
    }

    protected function isUnsafeProductionPassword(string $password): bool
    {
        $normalized = trim($password);

        if ($normalized === '' || strlen($normalized) < 12) {
            return true;
        }

        return in_array($normalized, [
            'ChangeMeSecurely!',
            'password',
            'password123',
            'admin',
            'admin123',
        ], true);
    }
}
