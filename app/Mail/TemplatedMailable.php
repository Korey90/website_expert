<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Generic mailable that renders content from an EmailTemplate database record.
 *
 * Usage:
 *   Mail::to($email)->queue(new TemplatedMailable($subject, $bodyHtml));
 */
class TemplatedMailable extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        private readonly string $renderedSubject,
        private readonly string $renderedBody,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: $this->renderedSubject);
    }

    public function content(): Content
    {
        return new Content(htmlString: $this->renderedBody);
    }
}
