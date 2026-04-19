<?php

namespace App\Mail;

use App\Models\SalesOffer;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class SalesOfferMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public readonly SalesOffer $offer) {}

    public function envelope(): Envelope
    {
        $businessName = $this->offer->business?->name ?? 'Website Expert';

        return new Envelope(
            subject: "Oferta: {$this->offer->title} — {$businessName}",
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.sales-offer',
        );
    }
}
