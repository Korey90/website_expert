<?php

namespace App\Mail;

use App\Models\Project;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ProjectStatusMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly Project $project,
        public readonly string $oldStatus,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Project Update: ' . $this->project->title . ' — ' . ucfirst($this->project->status),
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.project-status',
        );
    }
}
