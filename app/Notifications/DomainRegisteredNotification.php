<?php

namespace App\Notifications;

use App\Models\Domain;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Email sent to the client when their domain has been successfully registered.
 * Use via anonymous routing: Notification::route('mail', $email)->notify(new self($domain))
 */
class DomainRegisteredNotification extends Notification
{
    use Queueable;

    public function __construct(private readonly Domain $domain) {}

    public function via(mixed $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(mixed $notifiable): MailMessage
    {
        $domain     = $this->domain->full_domain;
        $expiresAt  = $this->domain->expires_at?->format('d M Y') ?? 'TBC';
        $portalUrl  = route('portal.domains.show', $this->domain->id);

        return (new MailMessage)
            ->subject("Your domain {$domain} is now registered! 🎉")
            ->greeting("Great news!")
            ->line("Your domain **{$domain}** has been successfully registered.")
            ->line("**Registered on:** " . ($this->domain->registered_at?->format('d M Y') ?? 'Today'))
            ->line("**Expires on:** {$expiresAt}")
            ->line("WHOIS privacy is enabled — your personal details are protected.")
            ->action('Manage Your Domain', $portalUrl)
            ->line("We'll send you renewal reminders before your domain expires so you never lose it.")
            ->salutation("The WebsiteExpert Team");
    }
}
