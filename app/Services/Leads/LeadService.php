<?php

namespace App\Services\Leads;

use App\Actions\CreateLeadAction;
use App\Events\LeadAssigned;
use App\Events\LeadCaptured;
use App\Models\Business;
use App\Models\Lead;
use App\Models\LeadActivity;
use App\Models\LandingPage;
use App\Models\PipelineStage;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class LeadService
{
    public function __construct(
        private readonly LeadSourceService  $sourceService,
        private readonly LeadConsentService $consentService,
    ) {}

    // ─────────────────────────────────────────────────────────────────────────
    //  Landing Page → CRM orchestration
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Full pipeline: validate duplicate → create CRM → source + consent → events.
     *
     * @param  array  $validated   Sanitised data from StoreLandingPageLeadRequest
     * @param  array  $sourceData  {ip_hash, ip_masked, user_agent, device_type, country_code, page_url, referrer_url}
     * @param  array  $consentData {given, consent_text, consent_version, locale, source_url}
     * @param  LandingPage  $lp
     * @return array{status: 'created'|'duplicate', lead_id: int}
     */
    public function createFromLandingPage(
        array $validated,
        array $sourceData,
        array $consentData,
        LandingPage $lp,
    ): array {
        $business = $lp->business;

        // ── Layer 2 deduplication (fingerprint, 24h window) ───────────────────
        $fingerprint = $this->buildFingerprint($validated['email'], $lp->id);

        $existingLeadId = $this->checkDuplicate($fingerprint);
        if ($existingLeadId !== null) {
            $this->logDuplicateAttempt($fingerprint, $lp->id, $existingLeadId, $sourceData['ip_hash'] ?? null);
            return ['status' => 'duplicate', 'lead_id' => $existingLeadId];
        }

        // ── Wrap creation in a DB transaction ─────────────────────────────────
        $lead = DB::transaction(function () use ($validated, $lp, $business) {
            $leadData = array_merge($validated, [
                'source'                  => 'landing_page',
                'business_id'             => $business?->id,
                'landing_page_id'         => $lp->id,
                'landing_page_title'      => $lp->title,
                'lp_default_assignee_id'  => $lp->default_assignee_id,
                'notes'                   => $validated['message'] ?? $validated['notes'] ?? null,
            ]);

            return app(CreateLeadAction::class)->execute($leadData);
        });

        // ── Source attribution ────────────────────────────────────────────────
        $this->sourceService->record($lead, $business, array_merge($sourceData, [
            'type'            => 'landing_page',
            'landing_page_id' => $lp->id,
        ]));

        // ── GDPR consent ─────────────────────────────────────────────────────
        if (! empty($consentData['given'])) {
            $this->consentService->record($lead, $consentData);
        }

        // ── Cache fingerprint for 24h to prevent duplicates ──────────────────
        cache()->put("lp_dup_{$fingerprint}", $lead->id, now()->addHours(24));

        // ── Activity log — LP context ─────────────────────────────────────────
        LeadActivity::log(
            $lead->id,
            'lp_captured',
            "Lead captured from landing page: {$lp->title}",
            [
                'lp_id'     => $lp->id,
                'lp_title'  => $lp->title,
                'lp_slug'   => $lp->slug,
                'utm'       => array_filter([
                    'source'   => $validated['utm_source'] ?? null,
                    'medium'   => $validated['utm_medium'] ?? null,
                    'campaign' => $validated['utm_campaign'] ?? null,
                ]),
            ],
            null,
        );

        // ── Emit LeadCaptured — triggers all downstream listeners ────────────
        event(new LeadCaptured($lead->fresh(['client', 'stage', 'landingPage']), $lp));

        Log::channel('leads')->info('Lead created from LP', [
            'lead_id'   => $lead->id,
            'lp_id'     => $lp->id,
            'email'     => $lead->client?->primary_contact_email,
            'source'    => 'landing_page',
        ]);

        return ['status' => 'created', 'lead_id' => $lead->id];
    }

    // ─────────────────────────────────────────────────────────────────────────
    //  Unified creation from other sources (contact form, calculator, API)
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Unified lead creation from non-LP origin.
     * LP leads must go through createFromLandingPage() for dedup + events.
     */
    public function createFromSource(
        array $leadData,
        array $sourceData,
        array $consentData,
        ?Business $business,
    ): Lead {
        $leadData['business_id'] = $business?->id ?? null;

        /** @var Lead $lead */
        $lead = app(CreateLeadAction::class)->execute($leadData);

        if ($business || ! empty($sourceData['type'])) {
            $this->sourceService->record($lead, $business, $sourceData);
        }

        if (! empty($consentData['given'])) {
            $this->consentService->record($lead, $consentData);
        }

        return $lead;
    }

    // ─────────────────────────────────────────────────────────────────────────
    //  Assignment
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Assign a lead to a user and log the activity.
     */
    public function assign(Lead $lead, User $assignee, User $assignedBy): Lead
    {
        $lead->update(['assigned_to' => $assignee->id]);

        LeadActivity::log(
            $lead->id,
            'assigned',
            "Assigned to {$assignee->name}",
            ['assigned_by' => $assignedBy->name, 'assigned_to' => $assignee->name],
            $assignedBy->id,
        );

        event(new LeadAssigned($lead, $assignee));

        return $lead->fresh();
    }

    // ─────────────────────────────────────────────────────────────────────────
    //  Stage management
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Move a lead to a different pipeline stage and log the activity.
     */
    public function updateStage(Lead $lead, PipelineStage $stage, User $actor): Lead
    {
        $lead->update(['pipeline_stage_id' => $stage->id]);

        // Eloquent event on the model already triggers AutomationEventListener.
        // We just log the activity here.
        LeadActivity::log(
            $lead->id,
            'stage_moved',
            "Stage changed to {$stage->name}",
            ['stage' => $stage->name],
            $actor->id,
        );

        return $lead->fresh();
    }

    // ─────────────────────────────────────────────────────────────────────────
    //  Win / Loss
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Mark lead as won, auto-move to the won pipeline stage, update client status.
     */
    public function markWon(Lead $lead, User $actor): Lead
    {
        DB::transaction(function () use ($lead, $actor) {
            $lead->update(['won_at' => now()]);

            // Auto-move to the pipeline stage flagged as is_won = true
            $wonStage = PipelineStage::where('is_won', true)->first();

            if ($wonStage && $lead->pipeline_stage_id !== $wonStage->id) {
                // updateQuietly bypasses Eloquent events — prevents recursive stage_changed automation
                $lead->updateQuietly(['pipeline_stage_id' => $wonStage->id]);

                LeadActivity::log(
                    $lead->id,
                    'stage_moved',
                    "Stage auto-moved to {$wonStage->name} (won)",
                    ['stage' => $wonStage->name, 'auto' => true],
                    $actor->id,
                );
            }

            LeadActivity::log($lead->id, 'marked_won', 'Lead marked as won', [], $actor->id);

            // Promote client from prospect → active when first lead is won
            if ($lead->client && $lead->client->status === 'prospect') {
                $lead->client->updateQuietly(['status' => 'active']);
            }
        });

        // Dispatch won automation outside transaction (audit trail safe to miss if tx fails)
        \App\Jobs\ProcessAutomationJob::dispatch('lead.won', [
            'lead_id'   => $lead->id,
            'client_id' => $lead->client_id,
            'source'    => $lead->source,
        ]);

        return $lead->fresh();
    }

    /**
     * Mark lead as lost, auto-move to the lost pipeline stage.
     */
    public function markLost(Lead $lead, string $reason, User $actor): Lead
    {
        DB::transaction(function () use ($lead, $reason, $actor) {
            $lead->update(['lost_at' => now(), 'lost_reason' => $reason]);

            $lostStage = PipelineStage::where('is_lost', true)->first();

            if ($lostStage && $lead->pipeline_stage_id !== $lostStage->id) {
                $lead->updateQuietly(['pipeline_stage_id' => $lostStage->id]);

                LeadActivity::log(
                    $lead->id,
                    'stage_moved',
                    "Stage auto-moved to {$lostStage->name} (lost)",
                    ['stage' => $lostStage->name, 'auto' => true],
                    $actor->id,
                );
            }

            LeadActivity::log(
                $lead->id,
                'marked_lost',
                "Lead marked as lost: {$reason}",
                ['reason' => $reason],
                $actor->id,
            );
        });

        \App\Jobs\ProcessAutomationJob::dispatch('lead.lost', [
            'lead_id'      => $lead->id,
            'client_id'    => $lead->client_id,
            'lost_reason'  => $reason,
        ]);

        return $lead->fresh();
    }

    // ─────────────────────────────────────────────────────────────────────────
    //  Deduplication helpers
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Build a 24h deduplication fingerprint: MD5(normalised_email|lp_id|date).
     */
    private function buildFingerprint(string $email, int $landingPageId): string
    {
        return md5(strtolower(trim($email)) . '|' . $landingPageId . '|' . now()->toDateString());
    }

    /**
     * Check cache for duplicate fingerprint. Returns lead_id when duplicate found, null otherwise.
     */
    private function checkDuplicate(string $fingerprint): ?int
    {
        return cache()->get("lp_dup_{$fingerprint}");
    }

    /**
     * Log duplicated submission attempt (no PII — uses email hash, not raw email).
     */
    private function logDuplicateAttempt(
        string $fingerprint,
        int $landingPageId,
        ?int $originalLeadId,
        ?string $ipHash,
    ): void {
        Log::channel('leads')->info('Duplicate LP submission blocked', [
            'fingerprint'      => $fingerprint,
            'landing_page_id'  => $landingPageId,
            'original_lead_id' => $originalLeadId,
            'ip_hash'          => $ipHash,
        ]);
    }
}
