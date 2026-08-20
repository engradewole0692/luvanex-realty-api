<?php

namespace App\Http\Requests\Admin\Blog;

use Illuminate\Foundation\Http\FormRequest;

class UploadBlogImageRequest extends FormRequest
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
        return [
            'image' => [
                'required',
                'file',
                'image',
                'mimes:jpeg,jpg,png,webp',
                'extensions:jpeg,jpg,png,webp',
                'max:5120', // 5MB
            ],
        ];
    }
}
