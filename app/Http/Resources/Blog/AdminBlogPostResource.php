<?php

namespace App\Http\Resources\Blog;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

/** @mixin \App\Models\BlogPost */
class AdminBlogPostResource extends JsonResource
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
            'previous_slug' => $this->previous_slug,
            'excerpt' => $this->excerpt,
            'content' => $this->content,
            'featured_image' => $this->featured_image,
            'featured_image_url' => $this->publicUrl($this->featured_image),
            'status' => $this->status,
            'published_at' => optional($this->published_at)?->toIso8601String(),
            'created_at' => optional($this->created_at)?->toIso8601String(),
            'updated_at' => optional($this->updated_at)?->toIso8601String(),
            'author' => $this->whenLoaded('author', fn () => [
                'id' => $this->author?->id,
                'name' => $this->author?->name,
                'email' => $this->author?->email,
            ]),
            'category_id' => $this->category_id,
            'category' => $this->whenLoaded('category', fn () => $this->category ? [
                'id' => $this->category->id,
                'name' => $this->category->name,
                'slug' => $this->category->slug,
            ] : null),
            'tags' => $this->whenLoaded('tags', fn () => $this->tags->map(fn ($tag) => [
                'id' => $tag->id,
                'name' => $tag->name,
                'slug' => $tag->slug,
            ])->values()),
            'tag_ids' => $this->whenLoaded('tags', fn () => $this->tags->pluck('id')->values()),
            'seo_title' => $this->seo_title,
            'seo_description' => $this->seo_description,
            'canonical_url' => $this->canonical_url,
            'og_title' => $this->og_title,
            'og_description' => $this->og_description,
            'og_image' => $this->og_image,
            'og_image_url' => $this->publicUrl($this->og_image),
            'robots_index' => (bool) $this->robots_index,
            'robots_follow' => (bool) $this->robots_follow,
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
