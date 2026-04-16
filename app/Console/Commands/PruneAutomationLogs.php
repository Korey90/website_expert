<?php

namespace App\Console\Commands;

use App\Models\AutomationLog;
use App\Models\Setting;
use Illuminate\Console\Command;

class PruneAutomationLogs extends Command
{
    protected $signature   = 'automation:prune-logs {--days= : Override retention days from settings}';
    protected $description = 'Delete automation logs older than the configured retention period.';

    public function handle(): int
    {
        $days = (int) ($this->option('days')
            ?? Setting::where('key', 'automation_log_retention_days')->value('value')
            ?? 90);

        if ($days <= 0) {
            $this->warn("Retention days is {$days} — skipping prune.");
            return self::SUCCESS;
        }

        $deleted = AutomationLog::olderThanDays($days)->delete();

        $this->info("Pruned {$deleted} automation log(s) older than {$days} day(s).");

        return self::SUCCESS;
    }
}
