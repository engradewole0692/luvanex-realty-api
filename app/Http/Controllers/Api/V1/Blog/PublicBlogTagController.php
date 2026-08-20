<?php

namespace App\Http\Controllers\Api\V1\Blog;

use App\Http\Controllers\Controller;
use App\Http\Resources\Blog\BlogTagResource;
use App\Models\BlogTag;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class PublicBlogTagController extends Controller
{
    public function index(): AnonymousResourceCollection
    {
        $tags = BlogTag::query()
            ->withCount(['posts' => fn ($q) => $q->published()])
            ->orderBy('name')
            ->get();

        return BlogTagResource::collection($tags);
    }
}
