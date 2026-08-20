<?php

namespace App\Http\Requests\Admin\Blog;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateBlogTagRequest extends FormRequest
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
        $id = $this->route('tag');

        return [
            'name' => ['sometimes', 'required', 'string', 'max:80'],
            'slug' => [
                'sometimes',
                'nullable',
                'string',
                'max:80',
                'alpha_dash',
                Rule::unique('blog_tags', 'slug')->ignore($id),
            ],
        ];
    }
}
