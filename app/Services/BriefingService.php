<?php

namespace App\Services;

use App\Models\Briefing;
use App\Models\BriefingTemplate;
use App\Models\Lead;
use App\Models\LeadActivity;
use App\Models\User;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class BriefingService
{
    /**
     * Create a new Briefing from a template and link it to a lead.
     */
    public function createFromTemplate(
        Lead             $lead,
        BriefingTemplate $template,
        User             $conductor,
    ): Briefing {
        $briefing = Briefing::create([
            'business_id'          => $lead->business_id,
            'lead_id'              => $lead->id,
            'briefing_template_id' => $template->id,
            'conducted_by'         => $conductor->id,
            'title'                => $lead->title . ' — ' . $template->title . ' — ' . now()->format('d/m/Y'),
            'type'                 => $template->type,
            'language'             => $template->language,
            'status'               => 'draft',
            'answers'              => [],
        ]);

        LeadActivity::log(
            leadId:      $lead->id,
            type:        'briefing_started',
            description: "Briefing started: {$template->title}",
            metadata:    ['briefing_id' => $briefing->id],
            userId:      $conductor->id,
        );

        return $briefing;
    }

    /**
     * Patch answers on an active briefing.
     * Uses array_merge per section so we don't overwrite other sections.
     */
    public function saveAnswers(Briefing $briefing, array $answers): void
    {
        $current = $briefing->answers ?? [];

        foreach ($answers as $sectionKey => $sectionAnswers) {
            $current[$sectionKey] = array_merge(
                $current[$sectionKey] ?? [],
                $sectionAnswers
            );
        }

        $briefing->update([
            'answers'    => $current,
            'autosave_at' => now(),
            'status'     => $briefing->status === 'draft' ? 'in_progress' : $briefing->status,
        ]);
    }

    /**
     * Save answers and mark the briefing as completed.
     *
     * @throws ValidationException when required questions are missing
     */
    public function complete(Briefing $briefing, array $answers, ?string $notes = null): Briefing
    {
        $this->saveAnswers($briefing, $answers);

        $missing = $briefing->fresh()->missingRequiredKeys();

        if (!empty($missing)) {
            throw ValidationException::withMessages([
                'answers' => 'Required fields are missing: ' . implode(', ', $missing),
            ]);
        }

        $briefing->update([
            'status'       => 'completed',
            'completed_at' => now(),
            'notes'        => $notes ?? $briefing->notes,
        ]);

        LeadActivity::log(
            leadId:      $briefing->lead_id,
            type:        'briefing_completed',
            description: "Briefing completed: {$briefing->title}",
            metadata:    ['briefing_id' => $briefing->id],
        );

        return $briefing->fresh();
    }

    /**
     * Cancel an in-progress or draft briefing.
     */
    public function cancel(Briefing $briefing): void
    {
        $briefing->update(['status' => 'cancelled']);

        LeadActivity::log(
            leadId:      $briefing->lead_id,
            type:        'briefing_cancelled',
            description: "Briefing cancelled: {$briefing->title}",
            metadata:    ['briefing_id' => $briefing->id],
        );
    }

    /**
     * Generate a client token and return the shareable URL.
     * Only callable when the client portal is active.
     */
    public function shareWithClient(Briefing $briefing): string
    {
        $token = Str::random(64);

        $briefing->update(['client_token' => $token]);

        LeadActivity::log(
            leadId:      $briefing->lead_id,
            type:        'briefing_shared_with_client',
            description: "Briefing shared with client: {$briefing->title}",
            metadata:    ['briefing_id' => $briefing->id],
        );

        return route('client.briefings.show', ['token' => $token]);
    }

    /**
     * Handle answers submitted by the client through the portal.
     * Moves status to in_progress; admin still needs to call complete().
     */
    public function submitByClient(Briefing $briefing, array $answers): Briefing
    {
        $this->saveAnswers($briefing, $answers);

        $briefing->update([
            'client_submitted_at' => now(),
            'status'              => 'in_progress',
        ]);

        LeadActivity::log(
            leadId:      $briefing->lead_id,
            type:        'briefing_submitted_by_client',
            description: "Client submitted briefing answers: {$briefing->title}",
            metadata:    ['briefing_id' => $briefing->id],
        );

        return $briefing->fresh();
    }
}
