<?php

namespace App\Console\Commands;

use App\Actions\Finance\GenerateDueRecurringExpensesAction;
use Carbon\CarbonImmutable;
use Illuminate\Console\Command;
use Throwable;

class GenerateRecurringExpenses extends Command
{
    protected $signature = 'finance:generate-recurring-expenses {--date= : Generate occurrences due through YYYY-MM-DD}';

    protected $description = 'Generate draft expenses from active recurring expense templates';

    public function handle(GenerateDueRecurringExpensesAction $action): int
    {
        try {
            $through = $this->option('date')
                ? CarbonImmutable::createFromFormat('Y-m-d', (string) $this->option('date'))->startOfDay()
                : today();
        } catch (Throwable) {
            $this->error('The --date option must use YYYY-MM-DD.');

            return self::INVALID;
        }

        $result = $action->execute($through);
        $this->info("Generated {$result['generated']->count()} recurring expense draft(s).");

        foreach ($result['failures'] as $templateId => $message) {
            $this->error("Template {$templateId}: {$message}");
        }

        return $result['failures'] === [] ? self::SUCCESS : self::FAILURE;
    }
}
