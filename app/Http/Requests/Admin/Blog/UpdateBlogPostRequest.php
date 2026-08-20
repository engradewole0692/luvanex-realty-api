<?php

namespace App\Http\Requests\Admin\Blog;

use App\Models\BlogPost;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateBlogPostRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isAdministrator() === true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $postId = $this->route('post');

        return [
            'title' => ['sometimes', 'required', 'string', 'max:255'],
            'slug' => [
                'sometimes',
                'nullable',
                'string',
                'max:255',
                'alpha_dash',
                Rule::unique('blog_posts', 'slug')->ignore($postId),
            ],
            'excerpt' => ['nullable', 'string', 'max:2000'],
            'content' => ['sometimes', 'required', 'string'],
            'featured_image' => ['nullable', 'string', 'max:500'],
            'category_id' => ['nullable', 'integer', 'exists:blog_categories,id'],
            'tag_ids' => ['nullable', 'array'],
            'tag_ids.*' => ['integer', 'exists:blog_tags,id'],
            'status' => ['nullable', Rule::in([BlogPost::STATUS_DRAFT, BlogPost::STATUS_PUBLISHED])],
            'published_at' => ['nullable', 'date'],
            'seo_title' => ['nullable', 'string', 'max:255'],
            'seo_description' => ['nullable', 'string', 'max:500'],
            'canonical_url' => ['nullable', 'url', 'max:500'],
            'og_title' => ['nullable', 'string', 'max:255'],
            'og_description' => ['nullable', 'string', 'max:500'],
            'og_image' => ['nullable', 'string', 'max:500'],
            'robots_index' => ['nullable', 'boolean'],
            'robots_follow' => ['nullable', 'boolean'],
        ];
    }
}
