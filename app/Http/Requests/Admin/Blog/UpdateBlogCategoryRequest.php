<?php

namespace App\Http\Requests\Admin\Blog;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateBlogCategoryRequest extends FormRequest
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
        $id = $this->route('category');

        return [
            'name' => ['sometimes', 'required', 'string', 'max:120'],
            'slug' => [
                'sometimes',
                'nullable',
                'string',
                'max:120',
                'alpha_dash',
                Rule::unique('blog_categories', 'slug')->ignore($id),
            ],
            'description' => ['nullable', 'string', 'max:1000'],
        ];
    }
}
