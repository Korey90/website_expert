<?php

namespace App\Mail;

use App\Models\Lead;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class LeadEnquiryConfirmationMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly Lead   $lead,
        public readonly string $mailLocale = 'pl',
        public readonly string $senderPhone = '',
    ) {}

    public function envelope(): Envelope
    {
        $subject = $this->mailLocale === 'pl'
            ? 'Dziękujemy za zapytanie — Website Expert'
            : 'Thank you for your enquiry — Website Expert';

        return new Envelope(subject: $subject);
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.lead-enquiry-confirmation',
            with: [
                'lead'        => $this->lead,
                'locale'      => $this->mailLocale,
                'senderPhone' => $this->senderPhone ?: env('CONTACT_PHONE', ''),
            ],
        );
    }
}
