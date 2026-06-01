<?php

namespace App\Jobs;

use App\Jobs\ProcessAutomationJob;
use App\Models\DomainRenewal;
use App\Notifications\DomainExpiryReminderNotification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;

/**
 * Sends a domain renewal reminder email to the client and marks the notification
 * flag on the DomainRenewal record to prevent duplicate sends.
 */
class SendDomainRenewalReminderJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    private const FLAG_MAP = [
        30 => 'notified_30d',
        14 => 'notified_14d',
        7  => 'notified_7d',
        1  => 'notified_1d',
    ];

    public function __construct(
        private readonly DomainRenewal $renewal,
        private readonly int           $daysUntilExpiry,
    ) {}

    public function handle(): void
    {
        $flag = self::FLAG_MAP[$this->daysUntilExpiry] ?? null;

        // Idempotency guard: skip if already sent for this threshold
        if ($flag && $this->renewal->$flag) {
            return;
        }

        $domain = $this->renewal->domain;
        if (! $domain) {
            Log::warning("SendDomainRenewalReminderJob: domain not found for renewal #{$this->renewal->id}");
            return;
        }

        // Resolve recipient email: client record > registrant contact
        $email = $domain->client?->primary_contact_email;

        if (! $email) {
            Log::warning("SendDomainRenewalReminderJob: no email for domain {$domain->full_domain}");
            return;
        }

        Notification::route('mail', $email)
            ->notify(new DomainExpiryReminderNotification($domain, $this->daysUntilExpiry));

        // Mark as notified to prevent duplicate sends
        if ($flag) {
            $this->renewal->update([$flag => true]);
        }

        // Fire automation trigger — allows building rules like "send email 30d before expiry"
        ProcessAutomationJob::dispatch('domain.expiry_reminder', [
            'domain_id'         => $domain->id,
            'domain_name'       => $domain->full_domain,
            'client_id'         => $domain->client_id,
            'business_id'       => $domain->business_id,
            'days_until_expiry' => $this->daysUntilExpiry,
            'due_date'          => $this->renewal->due_date?->toDateString(),
            'renewal_id'        => $this->renewal->id,
        ]);

        Log::info("SendDomainRenewalReminderJob: sent {$this->daysUntilExpiry}d reminder for {$domain->full_domain}");
    }
}
