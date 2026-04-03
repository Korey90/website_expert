<?php

namespace App\Jobs;

use App\Models\LeadSource;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class CleanLeadSourcePiiJob implements ShouldQueue
{
    use Queueable;

    /**
     * Remove raw IP addresses from lead_sources older than 30 days (GDPR compliance).
     * ip_hash is preserved for analytics purposes.
     */
    public function handle(): void
    {
        $days = config('leads.pii_retention_days', 30);

        LeadSource::where('created_at', '<', now()->subDays($days))
            ->whereNotNull('ip_address')
            ->update(['ip_address' => null]);
    }
}
