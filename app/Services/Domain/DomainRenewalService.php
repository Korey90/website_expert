<?php

namespace App\Services\Domain;

use App\Jobs\SendDomainRenewalReminderJob;
use App\Models\Domain;
use App\Models\DomainRenewal;
use Illuminate\Support\Facades\Log;

/**
 * Service responsible for domain renewal lifecycle:
 *   - scanning pending renewals and dispatching reminder jobs
 *   - marking overdue renewals
 *   - creating renewal records after registration / renewal completion
 *
 * The CheckDomainExpiryJob delegates to this service so the logic is
 * testable and reusable from admin actions or artisan commands.
 */
class DomainRenewalService
{
    private const THRESHOLDS = [
        30 => 'notified_30d',
        14 => 'notified_14d',
        7 => 'notified_7d',
        1 => 'notified_1d',
    ];

    public function __construct(private readonly DomainPricingService $pricing) {}

    /**
     * Scan pending renewals due within the next 31 days and dispatch a
     * SendDomainRenewalReminderJob for every threshold that has not yet
     * been notified (idempotent — job itself checks the flag again).
     *
     * @return int number of reminder jobs dispatched
     */
    public function sendReminders(): int
    {
        $dispatched = 0;

        DomainRenewal::pending()
            ->upcoming(31)
            ->with('domain.client')
            ->each(function (DomainRenewal $renewal) use (&$dispatched): void {
                $daysLeft = (int) now()->startOfDay()->diffInDays(
                    $renewal->due_date->startOfDay(),
                    absolute: true,
                );

                foreach (self::THRESHOLDS as $threshold => $flag) {
                    if ($daysLeft <= $threshold && ! $renewal->$flag) {
                        SendDomainRenewalReminderJob::dispatch($renewal, $threshold);
                        $dispatched++;
                        break; // one threshold per run per renewal
                    }
                }
            });

        Log::info("DomainRenewalService: {$dispatched} renewal reminder(s) dispatched.");

        return $dispatched;
    }

    /**
     * Mark all pending renewals whose due_date has passed as overdue.
     *
     * @return int number of records updated
     */
    public function markOverdue(): int
    {
        $count = DomainRenewal::pending()
            ->where('due_date', '<', now()->startOfDay())
            ->update(['status' => 'overdue']);

        if ($count > 0) {
            Log::info("DomainRenewalService: {$count} renewal(s) marked as overdue.");
        }

        return $count;
    }

    /**
     * Create a pending renewal record for a domain.
     * Called after registration or renewal completion.
     */
    public function createRenewal(Domain $domain, int $years = 1): DomainRenewal
    {
        $currency = $this->pricing->resolveCurrency($domain->domainOrder?->currency);
        $snapshot = $this->pricing->getPriceForTld($domain->tld, $currency);
        $currency = $snapshot?->currency ?? $currency;
        $price = $this->pricing->calculateRetailPrice($domain->tld, $years, 'renew', $currency) ?? 0.00;

        return DomainRenewal::create([
            'domain_id' => $domain->id,
            'due_date' => $domain->expires_at,
            'years' => $years,
            'status' => 'pending',
            'retail_price' => $price,
            'currency' => $currency,
        ]);
    }
}
