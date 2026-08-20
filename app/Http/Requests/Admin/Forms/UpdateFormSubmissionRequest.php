<?php

namespace App\Http\Requests\Admin\Forms;

use App\Models\FormSubmission;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateFormSubmissionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'status' => ['sometimes', 'required', Rule::in(FormSubmission::STATUSES)],
            'admin_notes' => ['sometimes', 'nullable', 'string', 'max:5000'],
        ];
    }
}
