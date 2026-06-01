<?php

namespace App\Jobs;

use App\Services\Domain\DomainRenewalService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

/**
 * Scheduled daily. Delegates renewal reminder dispatching and overdue
 * marking to DomainRenewalService.
 *
 * Reminder thresholds: 30, 14, 7, 1 day(s) before expiry.
 */
class CheckDomainExpiryJob implements ShouldQueue
{
    use Queueable;

    public function handle(DomainRenewalService $service): void
    {
        $service->sendReminders();
        $service->markOverdue();
    }
}
