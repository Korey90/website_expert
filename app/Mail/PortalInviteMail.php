<?php

namespace App\Mail;

use App\Models\EmailTemplate;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class PortalInviteMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly string $clientName,
        public readonly string $loginEmail,
        public readonly string $plainPassword,
        public readonly string $loginUrl,
        public readonly string $companyName,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: $this->resolveSubject(),
        );
    }

    public function content(): Content
    {
        [$subject, $body] = $this->resolveContent();

        return new Content(
            view: 'emails.client-email',
            with: [
                'emailSubject' => $subject,
                'emailBody'    => $body,
            ],
        );
    }

    public function attachments(): array
    {
        return [];
    }

    // ── Helpers ──────────────────────────────────────────────────────────────

    private function vars(): array
    {
        return [
            '{{client_name}}'    => $this->clientName,
            '{{login_email}}'    => $this->loginEmail,
            '{{plain_password}}' => $this->plainPassword,
            '{{portal_url}}'     => $this->loginUrl,
            '{{company_name}}'   => $this->companyName,
        ];
    }

    private function resolveSubject(): string
    {
        $template = EmailTemplate::where('slug', 'portal_invite')->first();
        if ($template) {
            $localized = $template->getForLocale('pl');
            return str_replace(array_keys($this->vars()), array_values($this->vars()), $localized['subject']);
        }

        return 'Twój dostęp do portalu klienta – ' . $this->companyName;
    }

    /** @return array{0: string, 1: string} [subject, body_html] */
    private function resolveContent(): array
    {
        $template = EmailTemplate::where('slug', 'portal_invite')->first();
        if ($template) {
            $localized = $template->getForLocale('pl');
            $vars      = $this->vars();
            $subject   = str_replace(array_keys($vars), array_values($vars), $localized['subject']);
            $body      = str_replace(array_keys($vars), array_values($vars), $localized['body_html']);
            return [$subject, $body];
        }

        // Fallback if template is missing from DB
        $subject = 'Twój dostęp do portalu klienta – ' . $this->companyName;
        return [$subject, $this->buildFallbackBody()];
    }

    private function buildFallbackBody(): string
    {
        $name    = e($this->clientName);
        $email   = e($this->loginEmail);
        $pass    = e($this->plainPassword);
        $url     = e($this->loginUrl);
        $company = e($this->companyName);

        return <<<HTML
<p>Szanowny/a <strong>{$name}</strong>,</p>
<p>Przygotowaliśmy dla Ciebie dostęp do <strong>portalu klienta {$company}</strong>.</p>
<p>E-mail: <strong>{$email}</strong><br>Hasło: <code>{$pass}</code></p>
<div style="text-align:center;margin:28px 0;">
  <a href="{$url}" style="display:inline-block;background:#4F46E5;color:#fff;text-decoration:none;padding:14px 36px;border-radius:8px;font-weight:700;">Zaloguj się do portalu</a>
</div>
HTML;
    }
}
