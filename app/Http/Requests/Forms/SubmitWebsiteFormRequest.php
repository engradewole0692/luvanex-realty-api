<?php

namespace App\Http\Requests\Forms;

use App\Models\FormSubmission;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SubmitWebsiteFormRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'form_type' => $this->route('formType'),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $rules = [
            'form_type' => ['required', Rule::in(FormSubmission::TYPES)],
            'first_name' => ['required', 'string', 'max:100'],
            'last_name' => ['required', 'string', 'max:100'],
            'email' => ['required', 'email:rfc', 'max:255'],
            'phone' => ['nullable', 'string', 'max:40'],
            'country' => ['required', 'string', 'max:120'],
            'message' => ['required', 'string', 'max:4000'],
            'topic' => ['nullable', 'string', 'max:180'],
            'referral' => ['nullable', 'string', 'max:255'],
            'budget' => ['nullable', 'string', 'max:80'],
            'strategy' => ['nullable', 'string', 'max:120'],
            'timeline' => ['nullable', 'string', 'max:80'],
            // Honeypot — bots fill this; humans leave it empty.
            'company_website' => ['nullable', 'string', 'max:255'],
        ];

        if ($this->route('formType') === FormSubmission::TYPE_CONTACT) {
            $rules['topic'] = ['required', 'string', 'max:180'];
        }

        return $rules;
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'first_name.required' => 'Please enter your first name.',
            'last_name.required' => 'Please enter your last name.',
            'email.required' => 'Please enter a valid email address.',
            'email.email' => 'Please enter a valid email address.',
            'country.required' => 'Please select your country of residence.',
            'message.required' => 'Please enter a message.',
            'topic.required' => 'Please select how we can help.',
        ];
    }
}
