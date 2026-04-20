<?php

namespace App\Listeners;

use App\Events\LeadAssigned;
use App\Events\LeadCaptured;
use App\Jobs\ProcessAutomationJob;
use App\Models\Contract;
use App\Models\Invoice;
use App\Models\Lead;
use App\Models\Project;
use App\Models\Quote;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Support\Facades\Log;

/**
 * Observes Eloquent model events and custom app events, then dispatches
 * ProcessAutomationJob for each matching trigger event.
 *
 * Registered as event subscriber in AppServiceProvider::boot().
 */
class AutomationEventListener
{
    public function subscribe(Dispatcher $events): void
    {
        // ── Lead Eloquent events ────────────────────────────────────────────
        $events->listen('eloquent.created: ' . Lead::class, [self::class, 'onLeadCreated']);
        $events->listen('eloquent.updated: ' . Lead::class, [self::class, 'onLeadUpdated']);

        // ── Lead app events (dispatched by LeadService) ────────────────────
        // NOTE: LP leads skip onLeadCreated → automation dispatched here with richer context
        $events->listen(LeadCaptured::class, [self::class, 'onLeadCaptured']);
        $events->listen(LeadAssigned::class, [self::class, 'onLeadAssigned']);

        // ── Project events ─────────────────────────────────────────────────
        $events->listen('eloquent.created: ' . Project::class, [self::class, 'onProjectCreated']);
        $events->listen('eloquent.updated: ' . Project::class, [self::class, 'onProjectUpdated']);

        // ── Invoice events ─────────────────────────────────────────────────
        $events->listen('eloquent.updated: ' . Invoice::class, [self::class, 'onInvoiceUpdated']);

        // ── Quote events ───────────────────────────────────────────────────
        $events->listen('eloquent.updated: ' . Quote::class, [self::class, 'onQuoteUpdated']);

        // ── Contract events ────────────────────────────────────────────────
        $events->listen('eloquent.created: ' . Contract::class, [self::class, 'onContractCreated']);
        $events->listen('eloquent.updated: ' . Contract::class, [self::class, 'onContractUpdated']);
    }

    // ─────────────────────────────────────────────────────────────────────────
    //  Lead handlers
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Eloquent lead.created — fires for ALL leads.
     * LP leads are handled by onLeadCaptured() which carries LP context.
     * Skip them here to prevent double automation dispatch.
     */
    public function onLeadCreated(Lead $lead): void
    {
        // LP leads: richer event (LeadCaptured) handles automation dispatch
        if ($lead->source === 'landing_page') {
            return;
        }

        $baseContext = [
            'lead_id'     => $lead->id,
            'client_id'   => $lead->client_id,
            'business_id' => $lead->business_id,
            'source'      => $lead->source,
            'locale'      => app()->getLocale(),
        ];

        // Generic trigger for all lead sources
        $this->dispatch('lead.created', $baseContext);

        // Source-specific triggers for granular automation rules
        if ($lead->source === 'service_cta') {
            $this->dispatch('lead.service_cta', $baseContext);
        } elseif ($lead->source === 'contact_form') {
            $this->dispatch('lead.contact_form', $baseContext);
        }
    }

    /**
     * App event fired by LeadService::createFromLandingPage().
     * Carries full LP + UTM context — dispatches lead.created with extra attribution.
     */
    public function onLeadCaptured(LeadCaptured $event): void
    {
        $lead = $event->lead;
        $lp   = $event->landingPage;

        $this->dispatch('lead.created', [
            'lead_id'         => $lead->id,
            'client_id'       => $lead->client_id,
            'business_id'     => $lead->business_id,
            'source'          => 'landing_page',
            'landing_page_id' => $lp->id,
            'assigned_to'     => $lead->assigned_to,
            'utm_source'      => $lead->utm_source,
            'utm_medium'      => $lead->utm_medium,
            'utm_campaign'    => $lead->utm_campaign,
            'locale'          => app()->getLocale(),
        ]);
    }

    /**
     * App event fired by LeadService::assign().
     */
    public function onLeadAssigned(LeadAssigned $event): void
    {
        $this->dispatch('lead.assigned', [
            'lead_id'     => $event->lead->id,
            'client_id'   => $event->lead->client_id,
            'business_id' => $event->lead->business_id,
            'assignee_id' => $event->assignee->id,
        ]);
    }

    /**
     * Eloquent lead.updated — handles stage change, won, lost.
     */
    public function onLeadUpdated(Lead $lead): void
    {
        if ($lead->wasChanged('pipeline_stage_id')) {
            $this->dispatch('lead.stage_changed', [
                'lead_id'      => $lead->id,
                'client_id'    => $lead->client_id,
                'business_id'  => $lead->business_id,
                'stage_id'     => $lead->pipeline_stage_id,
                'old_stage_id' => $lead->getOriginal('pipeline_stage_id'),
            ]);
        }

        // won_at / lost_at dispatched directly by LeadService::markWon/markLost
        // via ProcessAutomationJob to avoid re-dispatch from updateQuietly stage moves
    }

    // ─────────────────────────────────────────────────────────────────────────
    //  Project handlers
    // ─────────────────────────────────────────────────────────────────────────

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

    // ─────────────────────────────────────────────────────────────────────────
    //  Invoice handlers
    // ─────────────────────────────────────────────────────────────────────────

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

    // ─────────────────────────────────────────────────────────────────────────
    //  Quote handlers
    // ─────────────────────────────────────────────────────────────────────────

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

    // ─────────────────────────────────────────────────────────────────────────
    //  Contract handlers
    // ─────────────────────────────────────────────────────────────────────────

    public function onContractCreated(Contract $contract): void
    {
        $this->dispatch('contract.created', [
            'contract_id' => $contract->id,
            'client_id'   => $contract->client_id,
            'status'      => $contract->status,
        ]);
    }

    public function onContractUpdated(Contract $contract): void
    {
        if ($contract->wasChanged('status')) {
            $newStatus = $contract->status;

            if ($newStatus === 'sent') {
                $this->dispatch('contract.sent', [
                    'contract_id' => $contract->id,
                    'client_id'   => $contract->client_id,
                    'old_status'  => $contract->getOriginal('status'),
                    'status'      => $newStatus,
                ]);
            } elseif ($newStatus === 'signed') {
                $this->dispatch('contract.signed', [
                    'contract_id' => $contract->id,
                    'client_id'   => $contract->client_id,
                    'old_status'  => $contract->getOriginal('status'),
                    'status'      => $newStatus,
                ]);
            } elseif ($newStatus === 'expired') {
                $this->dispatch('contract.expired', [
                    'contract_id' => $contract->id,
                    'client_id'   => $contract->client_id,
                    'old_status'  => $contract->getOriginal('status'),
                    'status'      => $newStatus,
                ]);
            }
        }
    }

    // ─────────────────────────────────────────────────────────────────────────

    private function dispatch(string $triggerEvent, array $context): void
    {
        ProcessAutomationJob::dispatch($triggerEvent, $context);
    }
}

