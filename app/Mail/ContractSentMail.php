<?php

namespace App\Mail;

use App\Models\Contract;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ContractSentMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public readonly Contract $contract) {}

    public function envelope(): Envelope
    {
        $businessName = config('app.name', 'Website Expert');

        return new Envelope(
            subject: "Twój kontrakt jest gotowy — {$this->contract->number} | {$businessName}",
        );
    }

    public function content(): Content
    {
        return new Content(view: 'emails.contract-sent');
    }
}
