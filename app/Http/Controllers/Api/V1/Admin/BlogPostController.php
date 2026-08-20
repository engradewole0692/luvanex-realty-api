<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Blog\StoreBlogPostRequest;
use App\Http\Requests\Admin\Blog\UpdateBlogPostRequest;
use App\Http\Resources\Blog\AdminBlogPostResource;
use App\Models\BlogPost;
use App\Support\HtmlSanitizer;
use App\Support\SlugGenerator;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Carbon;

class BlogPostController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $query = BlogPost::query()
            ->with(['author:id,name,email', 'category:id,name,slug', 'tags:id,name,slug'])
            ->latest('updated_at');

        if ($status = $request->string('status')->toString()) {
            $query->where('status', $status);
        }

        if ($search = $request->string('search')->toString()) {
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                    ->orWhere('slug', 'like', "%{$search}%");
            });
        }

        $perPage = min(max((int) $request->input('per_page', 15), 1), 50);

        return AdminBlogPostResource::collection(
            $query->paginate($perPage)->withQueryString()
        );
    }

    public function store(StoreBlogPostRequest $request): JsonResponse
    {
        $data = $request->validated();
        $status = $data['status'] ?? BlogPost::STATUS_DRAFT;

        $slugSource = $data['slug'] ?? $data['title'];
        $slug = SlugGenerator::unique(BlogPost::class, $slugSource);

        $post = BlogPost::query()->create([
            'author_id' => $request->user()->id,
            'category_id' => $data['category_id'] ?? null,
            'title' => $data['title'],
            'slug' => $slug,
            'excerpt' => $data['excerpt'] ?? null,
            'content' => HtmlSanitizer::clean($data['content']),
            'featured_image' => $data['featured_image'] ?? null,
            'status' => $status,
            'published_at' => $status === BlogPost::STATUS_PUBLISHED
                ? ($data['published_at'] ?? now())
                : ($data['published_at'] ?? null),
            'seo_title' => $data['seo_title'] ?? null,
            'seo_description' => $data['seo_description'] ?? null,
            'canonical_url' => $data['canonical_url'] ?? null,
            'og_title' => $data['og_title'] ?? null,
            'og_description' => $data['og_description'] ?? null,
            'og_image' => $data['og_image'] ?? null,
            'robots_index' => $data['robots_index'] ?? true,
            'robots_follow' => $data['robots_follow'] ?? true,
        ]);

        if (! empty($data['tag_ids'])) {
            $post->tags()->sync($data['tag_ids']);
        }

        $post->load(['author:id,name,email', 'category:id,name,slug', 'tags:id,name,slug']);

        return (new AdminBlogPostResource($post))
            ->response()
            ->setStatusCode(201);
    }

    public function show(BlogPost $post): AdminBlogPostResource
    {
        $post->load(['author:id,name,email', 'category:id,name,slug', 'tags:id,name,slug']);

        return new AdminBlogPostResource($post);
    }

    public function update(UpdateBlogPostRequest $request, BlogPost $post): AdminBlogPostResource
    {
        $data = $request->validated();

        if (array_key_exists('title', $data) || array_key_exists('slug', $data)) {
            $slugSource = $data['slug'] ?? ($data['title'] ?? $post->title);
            $newSlug = SlugGenerator::unique(BlogPost::class, $slugSource, $post->id);

            if ($newSlug !== $post->slug && $post->status === BlogPost::STATUS_PUBLISHED) {
                $data['previous_slug'] = $post->slug;
            }

            $data['slug'] = $newSlug;
        }

        if (array_key_exists('content', $data)) {
            $data['content'] = HtmlSanitizer::clean($data['content']);
        }

        if (($data['status'] ?? null) === BlogPost::STATUS_PUBLISHED && ! $post->published_at) {
            $data['published_at'] = $data['published_at'] ?? now();
        }

        if (array_key_exists('published_at', $data) && $data['published_at']) {
            $data['published_at'] = Carbon::parse($data['published_at']);
        }

        $tagIds = $data['tag_ids'] ?? null;
        unset($data['tag_ids']);

        $post->fill($data);
        $post->save();

        if (is_array($tagIds)) {
            $post->tags()->sync($tagIds);
        }

        $post->load(['author:id,name,email', 'category:id,name,slug', 'tags:id,name,slug']);

        return new AdminBlogPostResource($post);
    }

    public function destroy(BlogPost $post): JsonResponse
    {
        $post->tags()->detach();
        $post->delete();

        return response()->json(['message' => 'Deleted.']);
    }

    public function publish(BlogPost $post): AdminBlogPostResource
    {
        $post->status = BlogPost::STATUS_PUBLISHED;
        $post->published_at = $post->published_at ?? now();
        $post->save();

        $post->load(['author:id,name,email', 'category:id,name,slug', 'tags:id,name,slug']);

        return new AdminBlogPostResource($post);
    }

    public function unpublish(BlogPost $post): AdminBlogPostResource
    {
        $post->status = BlogPost::STATUS_DRAFT;
        $post->save();

        $post->load(['author:id,name,email', 'category:id,name,slug', 'tags:id,name,slug']);

        return new AdminBlogPostResource($post);
    }
}
