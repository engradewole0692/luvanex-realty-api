<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Models\BlogPost;
use App\Models\FormSubmission;
use Illuminate\Http\JsonResponse;

class DashboardStatsController extends Controller
{
    public function __invoke(): JsonResponse
    {
        return response()->json([
            'data' => [
                'new_form_submissions' => FormSubmission::query()
                    ->status(FormSubmission::STATUS_NEW)
                    ->count(),
                'total_form_submissions' => FormSubmission::query()->count(),
                'total_blog_posts' => BlogPost::query()->count(),
                'published_posts' => BlogPost::query()
                    ->where('status', BlogPost::STATUS_PUBLISHED)
                    ->count(),
                'draft_posts' => BlogPost::query()
                    ->where('status', BlogPost::STATUS_DRAFT)
                    ->count(),
            ],
        ]);
    }
}
