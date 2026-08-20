<?php

use App\Http\Controllers\Api\V1\Admin\BlogCategoryController as AdminBlogCategoryController;
use App\Http\Controllers\Api\V1\Admin\BlogMediaController;
use App\Http\Controllers\Api\V1\Admin\BlogPostController as AdminBlogPostController;
use App\Http\Controllers\Api\V1\Admin\BlogTagController as AdminBlogTagController;
use App\Http\Controllers\Api\V1\Admin\DashboardStatsController;
use App\Http\Controllers\Api\V1\Admin\FormSubmissionController;
use App\Http\Controllers\Api\V1\Auth\AuthController;
use App\Http\Controllers\Api\V1\Blog\PublicBlogCategoryController;
use App\Http\Controllers\Api\V1\Blog\PublicBlogPostController;
use App\Http\Controllers\Api\V1\Blog\PublicBlogTagController;
use App\Http\Controllers\Api\V1\Forms\PublicFormController;
use App\Models\FormSubmission;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {
    Route::prefix('auth')->group(function () {
        Route::get('/csrf', [AuthController::class, 'csrf']);
        Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:login');

        Route::middleware(['auth:sanctum', 'admin'])->group(function () {
            Route::get('/user', [AuthController::class, 'user']);
            Route::post('/logout', [AuthController::class, 'logout']);
        });
    });

    // Public blog
    Route::prefix('blog')->group(function () {
        Route::get('/posts', [PublicBlogPostController::class, 'index']);
        Route::get('/posts/{slug}', [PublicBlogPostController::class, 'show']);
        Route::get('/categories', [PublicBlogCategoryController::class, 'index']);
        Route::get('/tags', [PublicBlogTagController::class, 'index']);
    });

    // Public website forms (only types that exist in the React app)
    Route::prefix('forms')->middleware('throttle:forms')->group(function () {
        Route::post('/{formType}', [PublicFormController::class, 'store'])
            ->whereIn('formType', FormSubmission::TYPES);
    });

    // Admin
    Route::middleware(['auth:sanctum', 'admin'])->prefix('admin')->group(function () {
        Route::get('/dashboard/stats', DashboardStatsController::class);

        Route::middleware('throttle:admin-writes')->prefix('blog')->group(function () {
            Route::get('/posts', [AdminBlogPostController::class, 'index'])->withoutMiddleware('throttle:admin-writes');
            Route::post('/posts', [AdminBlogPostController::class, 'store']);
            Route::get('/posts/{post}', [AdminBlogPostController::class, 'show'])->withoutMiddleware('throttle:admin-writes');
            Route::put('/posts/{post}', [AdminBlogPostController::class, 'update']);
            Route::delete('/posts/{post}', [AdminBlogPostController::class, 'destroy']);
            Route::post('/posts/{post}/publish', [AdminBlogPostController::class, 'publish']);
            Route::post('/posts/{post}/unpublish', [AdminBlogPostController::class, 'unpublish']);

            Route::get('/categories', [AdminBlogCategoryController::class, 'index'])->withoutMiddleware('throttle:admin-writes');
            Route::post('/categories', [AdminBlogCategoryController::class, 'store']);
            Route::put('/categories/{category}', [AdminBlogCategoryController::class, 'update']);
            Route::delete('/categories/{category}', [AdminBlogCategoryController::class, 'destroy']);

            Route::get('/tags', [AdminBlogTagController::class, 'index'])->withoutMiddleware('throttle:admin-writes');
            Route::post('/tags', [AdminBlogTagController::class, 'store']);
            Route::put('/tags/{tag}', [AdminBlogTagController::class, 'update']);
            Route::delete('/tags/{tag}', [AdminBlogTagController::class, 'destroy']);

            Route::post('/media', [BlogMediaController::class, 'store']);
        });

        Route::prefix('forms/submissions')->group(function () {
            Route::get('/', [FormSubmissionController::class, 'index']);
            Route::get('/{submission}', [FormSubmissionController::class, 'show']);
            Route::patch('/{submission}', [FormSubmissionController::class, 'update'])
                ->middleware('throttle:admin-writes');
        });
    });
});
