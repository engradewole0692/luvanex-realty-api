<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['name', 'slug', 'description'])]
class BlogCategory extends Model
{
    /** @use HasFactory<\Database\Factories\BlogCategoryFactory> */
    use HasFactory;

    public function posts(): HasMany
    {
        return $this->hasMany(BlogPost::class, 'category_id');
    }
}
