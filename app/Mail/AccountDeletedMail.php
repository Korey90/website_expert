<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class AccountDeletedMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly string $name,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Your account has been deleted — ' . config('app.name'),
        );
    }

    public function content(): Content
    {
        $body = view('emails.partials.account-deleted-body', ['name' => $this->name])->render();

        return new Content(
            view: 'emails.client-email',
            with: [
                'emailSubject' => 'Your account has been deleted',
                'emailBody'    => $body,
            ],
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
