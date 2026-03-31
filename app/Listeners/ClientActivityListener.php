<?php

namespace App\Listeners;

use App\Models\ClientActivity;
use App\Models\Contract;
use App\Models\Invoice;
use App\Models\Lead;
use App\Models\Project;
use App\Models\Quote;
use Illuminate\Contracts\Events\Dispatcher;

class ClientActivityListener
{
    public function subscribe(Dispatcher $events): void
    {
        $events->listen('eloquent.created: ' . Lead::class,     [self::class, 'onLeadCreated']);
        $events->listen('eloquent.created: ' . Project::class,  [self::class, 'onProjectCreated']);
        $events->listen('eloquent.updated: ' . Project::class,  [self::class, 'onProjectUpdated']);
        $events->listen('eloquent.updated: ' . Invoice::class,  [self::class, 'onInvoiceUpdated']);
        $events->listen('eloquent.updated: ' . Quote::class,    [self::class, 'onQuoteUpdated']);
        $events->listen('eloquent.updated: ' . Contract::class, [self::class, 'onContractUpdated']);
    }

    public function onLeadCreated(Lead $lead): void
    {
        if (! $lead->client_id) {
            return;
        }

        ClientActivity::create([
            'client_id'  => $lead->client_id,
            'event_type' => 'lead.created',
            'title'      => 'New lead received',
            'description' => $lead->title,
            'metadata'   => ['lead_id' => $lead->id, 'source' => $lead->source],
        ]);
    }

    public function onProjectCreated(Project $project): void
    {
        ClientActivity::create([
            'client_id'  => $project->client_id,
            'event_type' => 'project.created',
            'title'      => 'Project started',
            'description' => $project->title,
            'metadata'   => ['project_id' => $project->id, 'status' => $project->status],
        ]);
    }

    public function onProjectUpdated(Project $project): void
    {
        if (! $project->wasChanged('status')) {
            return;
        }

        ClientActivity::create([
            'client_id'  => $project->client_id,
            'event_type' => 'project.status_changed',
            'title'      => 'Project status updated',
            'description' => "{$project->title} → {$project->status}",
            'metadata'   => [
                'project_id' => $project->id,
                'old_status' => $project->getOriginal('status'),
                'status'     => $project->status,
            ],
        ]);
    }

    public function onInvoiceUpdated(Invoice $invoice): void
    {
        if (! $invoice->wasChanged('status')) {
            return;
        }

        $newStatus = $invoice->status;

        if (! in_array($newStatus, ['sent', 'paid'], true)) {
            return;
        }

        $titles = ['sent' => 'Invoice sent', 'paid' => 'Invoice paid'];

        ClientActivity::create([
            'client_id'  => $invoice->client_id,
            'event_type' => "invoice.{$newStatus}",
            'title'      => $titles[$newStatus],
            'description' => $invoice->number,
            'metadata'   => [
                'invoice_id' => $invoice->id,
                'total'      => $invoice->total,
            ],
        ]);
    }

    public function onQuoteUpdated(Quote $quote): void
    {
        if (! $quote->wasChanged('status')) {
            return;
        }

        $newStatus = $quote->status;

        if (! in_array($newStatus, ['sent', 'accepted'], true)) {
            return;
        }

        $titles = ['sent' => 'Quote sent', 'accepted' => 'Quote accepted'];

        ClientActivity::create([
            'client_id'  => $quote->client_id,
            'event_type' => "quote.{$newStatus}",
            'title'      => $titles[$newStatus],
            'description' => $quote->number,
            'metadata'   => [
                'quote_id' => $quote->id,
                'total'    => $quote->total,
            ],
        ]);
    }

    public function onContractUpdated(Contract $contract): void
    {
        if (! $contract->wasChanged('status')) {
            return;
        }

        $newStatus = $contract->status;

        if ($newStatus !== 'signed') {
            return;
        }

        ClientActivity::create([
            'client_id'  => $contract->client_id,
            'event_type' => 'contract.signed',
            'title'      => 'Contract signed',
            'description' => $contract->title ?? $contract->number,
            'metadata'   => [
                'contract_id' => $contract->id,
                'signed_at'   => $contract->signed_at,
            ],
        ]);
    }
}
