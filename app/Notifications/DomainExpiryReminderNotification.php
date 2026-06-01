<?php

namespace App\Notifications;

use App\Models\Domain;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Email reminder sent to the client before their domain expires.
 * Sent at 30, 14, 7, and 1 day(s) before the expiry date.
 * Use via anonymous routing: Notification::route('mail', $email)->notify(new self($domain, $days))
 */
class DomainExpiryReminderNotification extends Notification
{
    use Queueable;

    public function __construct(
        private readonly Domain $domain,
        private readonly int    $daysUntilExpiry,
    ) {}

    public function via(mixed $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(mixed $notifiable): MailMessage
    {
        $domain    = $this->domain->full_domain;
        $days      = $this->daysUntilExpiry;
        $expiresAt = $this->domain->expires_at?->format('d M Y') ?? 'soon';
        $portalUrl = route('portal.domains.show', $this->domain->id);
        $renewUrl  = route('portal.domains.order') . '?domain=' . urlencode($this->domain->name)
                     . '&tld=' . urlencode($this->domain->tld) . '&action=renew';

        $urgency = match (true) {
            $days <= 1  => '⚠️ URGENT: ',
            $days <= 7  => '⚠️ ',
            default     => '',
        };

        return (new MailMessage)
            ->subject("{$urgency}Your domain {$domain} expires in {$days} day" . ($days !== 1 ? 's' : ''))
            ->greeting("Renewal reminder")
            ->line("Your domain **{$domain}** will expire on **{$expiresAt}** — that's in **{$days} day" . ($days !== 1 ? 's' : '') . "**.")
            ->when($days <= 7, fn (MailMessage $m) =>
                $m->line("⚠️ If this domain expires, it may become available for anyone to register.")
            )
            ->action('Renew Your Domain Now', $renewUrl)
            ->line("Alternatively, you can manage all your domains from your portal:")
            ->action('View My Domains', $portalUrl)
            ->line("If you no longer need this domain, you can ignore this email.")
            ->salutation("The WebsiteExpert Team");
    }
}
