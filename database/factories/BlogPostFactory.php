<?php

namespace Database\Factories;

use App\Models\BlogCategory;
use App\Models\BlogPost;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<BlogPost>
 */
class BlogPostFactory extends Factory
{
    protected $model = BlogPost::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $title = fake()->sentence(6);

        return [
            'author_id' => User::factory()->admin(),
            'category_id' => BlogCategory::factory(),
            'title' => $title,
            'slug' => Str::slug($title).'-'.fake()->unique()->numerify('###'),
            'excerpt' => fake()->paragraph(),
            'content' => '<p>'.fake()->paragraphs(3, true).'</p>',
            'featured_image' => null,
            'status' => BlogPost::STATUS_DRAFT,
            'published_at' => null,
            'seo_title' => null,
            'seo_description' => null,
            'canonical_url' => null,
            'og_title' => null,
            'og_description' => null,
            'og_image' => null,
            'robots_index' => true,
            'robots_follow' => true,
        ];
    }

    public function published(): static
    {
        return $this->state(fn () => [
            'status' => BlogPost::STATUS_PUBLISHED,
            'published_at' => now()->subDay(),
        ]);
    }
}
