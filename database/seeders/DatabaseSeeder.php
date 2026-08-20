<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     *
     * Production: only AdminUserSeeder (requires secure ADMIN_* env vars).
     * Local/staging: also seeds sample blog content via BlogSeeder.
     */
    public function run(): void
    {
        $this->call([
            AdminUserSeeder::class,
        ]);

        if (! app()->environment('production')) {
            $this->call([
                BlogSeeder::class,
            ]);
        }
    }
}
