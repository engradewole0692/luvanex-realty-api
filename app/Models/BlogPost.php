<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

#[Fillable([
    'author_id',
    'category_id',
    'title',
    'slug',
    'previous_slug',
    'excerpt',
    'content',
    'featured_image',
    'status',
    'published_at',
    'seo_title',
    'seo_description',
    'canonical_url',
    'og_title',
    'og_description',
    'og_image',
    'robots_index',
    'robots_follow',
])]
class BlogPost extends Model
{
    /** @use HasFactory<\Database\Factories\BlogPostFactory> */
    use HasFactory;

    public const STATUS_DRAFT = 'draft';

    public const STATUS_PUBLISHED = 'published';

    protected function casts(): array
    {
        return [
            'published_at' => 'datetime',
            'robots_index' => 'boolean',
            'robots_follow' => 'boolean',
        ];
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'author_id');
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(BlogCategory::class, 'category_id');
    }

    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(BlogTag::class, 'blog_post_tag');
    }

    public function isPublished(): bool
    {
        return $this->status === self::STATUS_PUBLISHED
            && $this->published_at !== null
            && $this->published_at->lte(now());
    }

    public function scopePublished(Builder $query): Builder
    {
        return $query
            ->where('status', self::STATUS_PUBLISHED)
            ->whereNotNull('published_at')
            ->where('published_at', '<=', now());
    }
}
