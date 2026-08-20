<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Blog\StoreBlogTagRequest;
use App\Http\Requests\Admin\Blog\UpdateBlogTagRequest;
use App\Http\Resources\Blog\BlogTagResource;
use App\Models\BlogTag;
use App\Support\SlugGenerator;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class BlogTagController extends Controller
{
    public function index(): AnonymousResourceCollection
    {
        return BlogTagResource::collection(
            BlogTag::query()->withCount('posts')->orderBy('name')->get()
        );
    }

    public function store(StoreBlogTagRequest $request): JsonResponse
    {
        $data = $request->validated();
        $slug = SlugGenerator::unique(BlogTag::class, $data['slug'] ?? $data['name']);

        $tag = BlogTag::query()->create([
            'name' => $data['name'],
            'slug' => $slug,
        ]);

        return (new BlogTagResource($tag->loadCount('posts')))
            ->response()
            ->setStatusCode(201);
    }

    public function update(UpdateBlogTagRequest $request, BlogTag $tag): BlogTagResource
    {
        $data = $request->validated();

        if (array_key_exists('name', $data) || array_key_exists('slug', $data)) {
            $data['slug'] = SlugGenerator::unique(
                BlogTag::class,
                $data['slug'] ?? ($data['name'] ?? $tag->name),
                $tag->id
            );
        }

        $tag->fill($data)->save();

        return new BlogTagResource($tag->loadCount('posts'));
    }

    public function destroy(BlogTag $tag): JsonResponse
    {
        $tag->posts()->detach();
        $tag->delete();

        return response()->json(['message' => 'Deleted.']);
    }
}
