<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Administrator seeder credentials
    |--------------------------------------------------------------------------
    |
    | Sourced from the server .env (Forge: ADMIN_EMAIL, ADMIN_PASSWORD, ADMIN_NAME).
    | Application code must read these through config() so values survive
    | `php artisan config:cache`. Never put real credentials in this file.
    |
    */

    'name' => env('ADMIN_NAME', 'Luvanex Administrator'),

    'email' => env('ADMIN_EMAIL'),

    'password' => env('ADMIN_PASSWORD'),

];
