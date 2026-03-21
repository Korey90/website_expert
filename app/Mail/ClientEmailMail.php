<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ClientEmailMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly string $emailSubject,
        public readonly string $emailBody,
        public readonly array  $attachmentPaths = [],
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: $this->emailSubject);
    }

    public function content(): Content
    {
        return new Content(view: 'emails.client-email');
    }

    public function attachments(): array
    {
        return collect($this->attachmentPaths)
            ->map(fn (string $path) => Attachment::fromPath($path)->as(basename($path)))
            ->all();
    }
}
