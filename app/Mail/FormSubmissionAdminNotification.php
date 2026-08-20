<?php

namespace App\Mail;

use App\Models\FormSubmission;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class FormSubmissionAdminNotification extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    /** @var int */
    public $tries = 3;

    /** @var int */
    public $timeout = 90;

    public function __construct(public FormSubmission $submission) {}

    public function envelope(): Envelope
    {
        $labels = [
            FormSubmission::TYPE_CONTACT => 'Contact Form Submission',
            FormSubmission::TYPE_INVESTOR => 'Investor Enquiry',
            FormSubmission::TYPE_MORTGAGE => 'Mortgage Program Enquiry',
        ];

        $label = $labels[$this->submission->form_type] ?? 'Form Submission';

        return new Envelope(
            from: new Address(
                (string) config('mail.from.address'),
                (string) config('mail.from.name'),
            ),
            replyTo: [
                new Address($this->submission->email, $this->submission->name),
            ],
            subject: "Luvanex Realty — {$label}",
        );
    }

    public function content(): Content
    {
        return new Content(
            html: 'emails.forms.admin-notification',
            with: [
                'submission' => $this->submission,
                'payload' => $this->submission->payload ?? [],
            ],
        );
    }
}
