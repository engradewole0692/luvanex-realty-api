<?php

namespace App\Http\Resources\Forms;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Models\FormSubmission */
class FormSubmissionResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'form_type' => $this->form_type,
            'name' => $this->name,
            'email' => $this->email,
            'phone' => $this->phone,
            'company' => $this->company,
            'country' => $this->country,
            'subject' => $this->subject,
            'message' => $this->message,
            'payload' => $this->payload,
            'status' => $this->status,
            'admin_notes' => $this->admin_notes,
            'submitted_at' => optional($this->submitted_at)?->toIso8601String(),
            'created_at' => optional($this->created_at)?->toIso8601String(),
            'updated_at' => optional($this->updated_at)?->toIso8601String(),
        ];
    }
}
