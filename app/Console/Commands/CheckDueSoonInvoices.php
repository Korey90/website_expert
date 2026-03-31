<?php

namespace App\Console\Commands;

use App\Jobs\ProcessAutomationJob;
use App\Models\Invoice;
use Illuminate\Console\Command;

/**
 * Finds unpaid invoices whose due date is within N days (default 3) and
 * dispatches the `invoice.due_soon` automation trigger so that matching rules
 * can send reminder emails to clients.
 *
 * Usage: php artisan invoices:check-due-soon [--days=3]
 */
class CheckDueSoonInvoices extends Command
{
    protected $signature = 'invoices:check-due-soon {--days=3 : Days before due date to send a reminder}';
    protected $description = 'Dispatch invoice.due_soon automation events for invoices due within N days';

    public function handle(): int
    {
        $days = (int) $this->option('days');

        $invoices = Invoice::withoutTrashed()
            ->whereIn('status', ['sent', 'partially_paid'])
            ->whereBetween('due_date', [now()->toDateString(), now()->addDays($days)->toDateString()])
            ->with('client')
            ->get();

        if ($invoices->isEmpty()) {
            $this->info("No invoices due within {$days} day(s).");
            return self::SUCCESS;
        }

        foreach ($invoices as $invoice) {
            ProcessAutomationJob::dispatch('invoice.due_soon', [
                'invoice_id' => $invoice->id,
                'client_id'  => $invoice->client_id,
                'due_date'   => $invoice->due_date->toDateString(),
                'total'      => (float) $invoice->total,
                'amount_due' => (float) $invoice->amount_due,
            ]);
        }

        $this->info("Dispatched invoice.due_soon events for {$invoices->count()} invoice(s).");

        return self::SUCCESS;
    }
}
