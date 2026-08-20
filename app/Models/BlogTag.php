<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

#[Fillable(['name', 'slug'])]
class BlogTag extends Model
{
    /** @use HasFactory<\Database\Factories\BlogTagFactory> */
    use HasFactory;

    public function posts(): BelongsToMany
    {
        return $this->belongsToMany(BlogPost::class, 'blog_post_tag');
    }
}
