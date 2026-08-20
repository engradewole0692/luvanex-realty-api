<?php

namespace App\Http\Resources\Blog;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

/** @mixin \App\Models\BlogPost */
class PublicBlogPostResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'slug' => $this->slug,
            'excerpt' => $this->excerpt,
            'content' => $this->when($request->route('slug') !== null, $this->content),
            'featured_image' => $this->publicUrl($this->featured_image),
            'published_at' => optional($this->published_at)?->toIso8601String(),
            'updated_at' => optional($this->updated_at)?->toIso8601String(),
            'author' => $this->whenLoaded('author', fn () => [
                'name' => $this->author?->name,
            ]),
            'category' => $this->whenLoaded('category', fn () => $this->category ? [
                'name' => $this->category->name,
                'slug' => $this->category->slug,
            ] : null),
            'tags' => $this->whenLoaded('tags', fn () => $this->tags->map(fn ($tag) => [
                'name' => $tag->name,
                'slug' => $tag->slug,
            ])->values()),
            'seo' => [
                'title' => $this->seo_title ?: $this->title,
                'description' => $this->seo_description ?: $this->excerpt,
                'canonical_url' => $this->canonical_url,
                'og_title' => $this->og_title ?: ($this->seo_title ?: $this->title),
                'og_description' => $this->og_description ?: ($this->seo_description ?: $this->excerpt),
                'og_image' => $this->publicUrl($this->og_image ?: $this->featured_image),
                'robots_index' => (bool) $this->robots_index,
                'robots_follow' => (bool) $this->robots_follow,
            ],
        ];
    }

    protected function publicUrl(?string $path): ?string
    {
        if (! $path) {
            return null;
        }

        if (str_starts_with($path, 'http://') || str_starts_with($path, 'https://')) {
            return $path;
        }

        return Storage::disk('public')->url($path);
    }
}
