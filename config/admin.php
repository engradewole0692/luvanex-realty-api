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

    /*
    | Temporary production HTTP diagnostics. Logs email, password LENGTHS,
    | attempt result, and request origin — never the password, hash, session,
    | or CSRF token. Set PORTAL_AUTH_DIAG=true on Forge for one login, then
    | set it back to false and config:cache.
    */
    'auth_diag' => filter_var(env('PORTAL_AUTH_DIAG', false), FILTER_VALIDATE_BOOLEAN),

];
