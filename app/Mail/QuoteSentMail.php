<?php

namespace App\Mail;

use App\Models\Quote;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class QuoteSentMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public readonly Quote $quote) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Your Quote ' . $this->quote->number . ' from WebsiteExpert',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.quote-sent',
        );
    }
}
