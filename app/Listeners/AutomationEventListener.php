<?php

namespace App\Listeners;

use App\Jobs\ProcessAutomationJob;
use App\Models\Invoice;
use App\Models\Lead;
use App\Models\Project;
use App\Models\Quote;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Support\Facades\Log;

/**
 * Observes Eloquent model events (created / updated) and dispatches
 * ProcessAutomationJob for each matching trigger event.
 *
 * Register in AppServiceProvider::boot() or EventServiceProvider::subscribe().
 */
class AutomationEventListener
{
    public function subscribe(Dispatcher $events): void
    {
        // Lead created
        $events->listen('eloquent.created: ' . Lead::class, [self::class, 'onLeadCreated']);

        // Lead status / stage changed
        $events->listen('eloquent.updated: ' . Lead::class, [self::class, 'onLeadUpdated']);

        // Project created
        $events->listen('eloquent.created: ' . Project::class, [self::class, 'onProjectCreated']);

        // Project status changed
        $events->listen('eloquent.updated: ' . Project::class, [self::class, 'onProjectUpdated']);

        // Invoice status changed
        $events->listen('eloquent.updated: ' . Invoice::class, [self::class, 'onInvoiceUpdated']);

        // Quote status changed
        $events->listen('eloquent.updated: ' . Quote::class, [self::class, 'onQuoteUpdated']);
    }

    public function onLeadCreated(Lead $lead): void
    {
        $this->dispatch('lead.created', [
            'lead_id'   => $lead->id,
            'client_id' => $lead->client_id,
            'source'    => $lead->source,
            'status'    => $lead->status,
        ]);
    }

    public function onLeadUpdated(Lead $lead): void
    {
        if ($lead->wasChanged('status')) {
            $this->dispatch('lead.status_changed', [
                'lead_id'    => $lead->id,
                'client_id'  => $lead->client_id,
                'old_status' => $lead->getOriginal('status'),
                'status'     => $lead->status,
            ]);
        }

        if ($lead->wasChanged('stage')) {
            $this->dispatch('lead.stage_changed', [
                'lead_id'   => $lead->id,
                'client_id' => $lead->client_id,
                'old_stage' => $lead->getOriginal('stage'),
                'stage'     => $lead->stage,
            ]);
        }
    }

    public function onProjectCreated(Project $project): void
    {
        $this->dispatch('project.created', [
            'project_id' => $project->id,
            'client_id'  => $project->client_id,
            'status'     => $project->status,
        ]);
    }

    public function onProjectUpdated(Project $project): void
    {
        if ($project->wasChanged('status')) {
            $oldStatus = $project->getOriginal('status');
            $this->dispatch('project.status_changed', [
                'project_id' => $project->id,
                'client_id'  => $project->client_id,
                'old_status' => $oldStatus,
                'status'     => $project->status,
            ]);
        }
    }

    public function onInvoiceUpdated(Invoice $invoice): void
    {
        if ($invoice->wasChanged('status')) {
            $newStatus = $invoice->status;

            if ($newStatus === 'sent') {
                $this->dispatch('invoice.sent', [
                    'invoice_id' => $invoice->id,
                    'client_id'  => $invoice->client_id,
                    'old_status' => $invoice->getOriginal('status'),
                    'status'     => $newStatus,
                ]);
            } elseif ($newStatus === 'paid') {
                $this->dispatch('invoice.paid', [
                    'invoice_id' => $invoice->id,
                    'client_id'  => $invoice->client_id,
                    'old_status' => $invoice->getOriginal('status'),
                    'status'     => $newStatus,
                ]);
            }
        }
    }

    public function onQuoteUpdated(Quote $quote): void
    {
        if ($quote->wasChanged('status')) {
            $newStatus = $quote->status;

            if ($newStatus === 'sent') {
                $this->dispatch('quote.sent', [
                    'quote_id'   => $quote->id,
                    'client_id'  => $quote->client_id,
                    'old_status' => $quote->getOriginal('status'),
                    'status'     => $newStatus,
                ]);
            } elseif ($newStatus === 'accepted') {
                $this->dispatch('quote.accepted', [
                    'quote_id'   => $quote->id,
                    'client_id'  => $quote->client_id,
                    'old_status' => $quote->getOriginal('status'),
                    'status'     => $newStatus,
                ]);
            }
        }
    }

    private function dispatch(string $triggerEvent, array $context): void
    {
        ProcessAutomationJob::dispatch($triggerEvent, $context);
    }
}
