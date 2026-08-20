<?php

namespace App\Http\Controllers\Api\V1\Blog;

use App\Http\Controllers\Controller;
use App\Http\Resources\Blog\PublicBlogPostResource;
use App\Models\BlogPost;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class PublicBlogPostController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $query = BlogPost::query()
            ->published()
            ->with(['author:id,name', 'category:id,name,slug', 'tags:id,name,slug'])
            ->latest('published_at');

        if ($category = $request->string('category')->toString()) {
            $query->whereHas('category', fn ($q) => $q->where('slug', $category));
        }

        if ($tag = $request->string('tag')->toString()) {
            $query->whereHas('tags', fn ($q) => $q->where('slug', $tag));
        }

        if ($search = $request->string('search')->toString()) {
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                    ->orWhere('excerpt', 'like', "%{$search}%");
            });
        }

        $perPage = min(max((int) $request->input('per_page', 12), 1), 50);

        return PublicBlogPostResource::collection(
            $query->paginate($perPage)->withQueryString()
        );
    }

    public function show(string $slug): PublicBlogPostResource
    {
        $post = BlogPost::query()
            ->published()
            ->with(['author:id,name', 'category:id,name,slug', 'tags:id,name,slug'])
            ->where('slug', $slug)
            ->firstOrFail();

        return new PublicBlogPostResource($post);
    }
}
