<?php

namespace App\Actions\Domain;

use App\Jobs\SendDomainRenewalReminderJob;
use App\Models\DomainRenewal;

class SendRenewalReminderAction
{
    private const VALID_THRESHOLDS = [30, 14, 7, 1];

    /**
     * Dispatch a renewal reminder job for a specific DomainRenewal.
     * Useful for manual admin-triggered reminders regardless of the
     * automatic schedule.
     *
     * @throws \InvalidArgumentException for unrecognised threshold
     */
    public function execute(DomainRenewal $renewal, int $daysUntilExpiry = 30): void
    {
        if (! in_array($daysUntilExpiry, self::VALID_THRESHOLDS, true)) {
            throw new \InvalidArgumentException(
                "Invalid threshold: {$daysUntilExpiry}. Valid values: "
                . implode(', ', self::VALID_THRESHOLDS) . '.'
            );
        }

        SendDomainRenewalReminderJob::dispatch($renewal, $daysUntilExpiry);
    }
}
