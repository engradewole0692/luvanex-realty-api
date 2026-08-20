<?php

namespace App\Support;

use Illuminate\Support\Str;

class SlugGenerator
{
    /**
     * Generate a unique slug for the given model class.
     *
     * @param  class-string<\Illuminate\Database\Eloquent\Model>  $modelClass
     */
    public static function unique(string $modelClass, string $value, ?int $ignoreId = null, string $column = 'slug'): string
    {
        $base = Str::slug($value);
        if ($base === '') {
            $base = 'item';
        }

        $slug = $base;
        $counter = 2;

        while (
            $modelClass::query()
                ->when($ignoreId, fn ($q) => $q->where('id', '!=', $ignoreId))
                ->where($column, $slug)
                ->exists()
        ) {
            $slug = $base.'-'.$counter;
            $counter++;
        }

        return $slug;
    }
}
