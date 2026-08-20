<?php

namespace App\Http\Controllers\Api\V1\Blog;

use App\Http\Controllers\Controller;
use App\Http\Resources\Blog\BlogCategoryResource;
use App\Models\BlogCategory;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class PublicBlogCategoryController extends Controller
{
    public function index(): AnonymousResourceCollection
    {
        $categories = BlogCategory::query()
            ->withCount(['posts' => fn ($q) => $q->published()])
            ->orderBy('name')
            ->get();

        return BlogCategoryResource::collection($categories);
    }
}
