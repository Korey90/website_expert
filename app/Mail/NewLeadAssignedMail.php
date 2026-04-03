<?php

namespace App\Mail;

use App\Models\Lead;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Sent to the assigned team member when a new lead from a landing page
 * is assigned to them (either directly or via LP default_assignee_id).
 *
 * Queued by NotifyLeadOwnerListener. Uses SerializesModels — safe for retries.
 */
class NewLeadAssignedMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly Lead $lead,
        public readonly User $assignee,
    ) {}

    public function envelope(): Envelope
    {
        $clientName = $this->lead->client?->primary_contact_name
            ?? $this->lead->client?->primary_contact_email
            ?? 'Unknown';

        return new Envelope(
            subject: "New lead assigned to you — {$clientName}",
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.new-lead-assigned',
        );
    }
}
