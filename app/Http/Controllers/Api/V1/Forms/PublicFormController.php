<?php

namespace App\Http\Controllers\Api\V1\Forms;

use App\Http\Controllers\Controller;
use App\Http\Requests\Forms\SubmitWebsiteFormRequest;
use App\Mail\FormSubmissionAdminNotification;
use App\Mail\FormSubmissionVisitorConfirmation;
use App\Models\FormSubmission;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Mail;

class PublicFormController extends Controller
{
    public function store(SubmitWebsiteFormRequest $request, string $formType): JsonResponse
    {
        $data = $request->validated();

        // Silent honeypot success — do not create a submission or send email.
        if (filled($data['company_website'] ?? null)) {
            return response()->json([
                'message' => 'Thank you. Your enquiry has been received.',
            ]);
        }

        unset($data['company_website'], $data['form_type']);

        $name = trim($data['first_name'].' '.$data['last_name']);
        $subject = $data['topic'] ?? match ($formType) {
            FormSubmission::TYPE_INVESTOR => 'Investor enquiry',
            FormSubmission::TYPE_MORTGAGE => 'Mortgage Programme',
            default => null,
        };

        $payload = array_filter([
            'first_name' => $data['first_name'],
            'last_name' => $data['last_name'],
            'referral' => $data['referral'] ?? null,
            'budget' => $data['budget'] ?? null,
            'strategy' => $data['strategy'] ?? null,
            'timeline' => $data['timeline'] ?? null,
            'topic' => $data['topic'] ?? null,
        ], fn ($value) => $value !== null && $value !== '');

        $submission = FormSubmission::query()->create([
            'form_type' => $formType,
            'name' => $name,
            'email' => $data['email'],
            'phone' => $data['phone'] ?? null,
            'company' => null,
            'country' => $data['country'],
            'subject' => $subject,
            'message' => $data['message'],
            'payload' => $payload,
            'status' => FormSubmission::STATUS_NEW,
            'submitted_at' => now(),
        ]);

        $inbox = (string) config('mail.form_inbox');

        Mail::to($inbox)->queue(new FormSubmissionAdminNotification($submission));
        Mail::to($submission->email)->queue(new FormSubmissionVisitorConfirmation($submission));

        return response()->json([
            'message' => 'Thank you. Your enquiry has been received.',
        ], 201);
    }
}
