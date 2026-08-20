<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Blog\StoreBlogCategoryRequest;
use App\Http\Requests\Admin\Blog\UpdateBlogCategoryRequest;
use App\Http\Resources\Blog\BlogCategoryResource;
use App\Models\BlogCategory;
use App\Support\SlugGenerator;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class BlogCategoryController extends Controller
{
    public function index(): AnonymousResourceCollection
    {
        return BlogCategoryResource::collection(
            BlogCategory::query()->withCount('posts')->orderBy('name')->get()
        );
    }

    public function store(StoreBlogCategoryRequest $request): JsonResponse
    {
        $data = $request->validated();
        $slug = SlugGenerator::unique(BlogCategory::class, $data['slug'] ?? $data['name']);

        $category = BlogCategory::query()->create([
            'name' => $data['name'],
            'slug' => $slug,
            'description' => $data['description'] ?? null,
        ]);

        return (new BlogCategoryResource($category->loadCount('posts')))
            ->response()
            ->setStatusCode(201);
    }

    public function update(UpdateBlogCategoryRequest $request, BlogCategory $category): BlogCategoryResource
    {
        $data = $request->validated();

        if (array_key_exists('name', $data) || array_key_exists('slug', $data)) {
            $data['slug'] = SlugGenerator::unique(
                BlogCategory::class,
                $data['slug'] ?? ($data['name'] ?? $category->name),
                $category->id
            );
        }

        $category->fill($data)->save();

        return new BlogCategoryResource($category->loadCount('posts'));
    }

    public function destroy(BlogCategory $category): JsonResponse
    {
        if ($category->posts()->exists()) {
            return response()->json([
                'message' => 'Cannot delete a category that still has posts. Reassign or delete those posts first.',
            ], 422);
        }

        $category->delete();

        return response()->json(['message' => 'Deleted.']);
    }
}
