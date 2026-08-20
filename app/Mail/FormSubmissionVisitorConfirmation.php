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

class FormSubmissionVisitorConfirmation extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    /** @var int */
    public $tries = 3;

    /** @var int */
    public $timeout = 90;

    public function __construct(public FormSubmission $submission) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            from: new Address(
                (string) config('mail.from.address'),
                (string) config('mail.from.name'),
            ),
            subject: 'Luvanex Realty — We received your enquiry',
        );
    }

    public function content(): Content
    {
        return new Content(
            html: 'emails.forms.visitor-confirmation',
            with: [
                'submission' => $this->submission,
                'inbox' => (string) config('mail.form_inbox'),
            ],
        );
    }
}
