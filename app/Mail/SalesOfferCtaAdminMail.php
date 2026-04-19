<?php

namespace App\Mail;

use App\Models\SalesOffer;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class SalesOfferCtaAdminMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly SalesOffer $offer,
        public readonly User $recipient,
    ) {}

    public function envelope(): Envelope
    {
        $client = $this->offer->lead?->client;
        $name   = $client?->company_name ?? $client?->primary_contact_name ?? 'Unknown';

        return new Envelope(
            subject: "🔥 Klient zainteresowany ofertą: {$name} — {$this->offer->title}",
        );
    }

    public function content(): Content
    {
        return new Content(view: 'emails.sales-offer-cta-admin');
    }
}
