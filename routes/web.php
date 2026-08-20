<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web routes
|--------------------------------------------------------------------------
|
| This application is an API. Keep the root response minimal and non-diagnostic.
| Health checks use Laravel's built-in /up endpoint (configured in bootstrap/app.php).
|
*/

Route::get('/', function () {
    return response()->json([
        'name' => 'Luvanex Realty API',
        'status' => 'ok',
    ]);
});
