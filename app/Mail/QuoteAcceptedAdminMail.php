<?php

namespace App\Mail;

use App\Models\Quote;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class QuoteAcceptedAdminMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly Quote $quote,
        public readonly User  $recipient,
    ) {}

    public function envelope(): Envelope
    {
        $client = $this->quote->client;
        $name   = $client?->company_name ?? $client?->primary_contact_name ?? 'Unknown';

        return new Envelope(
            subject: "✅ Wycena zaakceptowana: {$name} — {$this->quote->number}",
        );
    }

    public function content(): Content
    {
        return new Content(view: 'emails.quote-accepted-admin');
    }
}
