<?php

namespace App\Console\Commands;

use App\Jobs\ProcessAutomationJob;
use App\Models\Lead;
use Illuminate\Console\Command;

/**
 * Finds leads that haven't been updated in N+ days (default 7) and are still
 * open (not won or lost). Dispatches the `lead.inactive` trigger so that
 * matching AutomationRules can send reminders or escalate.
 *
 * Usage: php artisan leads:check-stale [--days=7]
 */
class CheckStaleLeads extends Command
{
    protected $signature = 'leads:check-stale {--days=7 : Number of days without activity before a lead is considered stale}';
    protected $description = 'Dispatch lead.inactive automation events for leads idle for N days';

    public function handle(): int
    {
        $days  = (int) $this->option('days');
        $cutoff = now()->subDays($days);

        $leads = Lead::withoutTrashed()
            ->whereNull('won_at')
            ->whereNull('lost_at')
            ->where('updated_at', '<', $cutoff)
            ->with('client')
            ->get();

        if ($leads->isEmpty()) {
            $this->info("No stale leads found (threshold: {$days} day(s)).");
            return self::SUCCESS;
        }

        foreach ($leads as $lead) {
            ProcessAutomationJob::dispatch('lead.inactive', [
                'lead_id'       => $lead->id,
                'client_id'     => $lead->client_id,
                'days_inactive' => (int) $cutoff->diffInDays($lead->updated_at),
            ]);
        }

        $this->info("Dispatched lead.inactive events for {$leads->count()} stale lead(s).");

        return self::SUCCESS;
    }
}
