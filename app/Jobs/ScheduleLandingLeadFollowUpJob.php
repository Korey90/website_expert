<?php

namespace App\Jobs;

use App\Models\Lead;
use App\Models\LeadActivity;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

/**
 * Schedules two follow-up task activities for a static landing page lead:
 *  - 24h reminder: initial contact
 *  - 72h reminder: second attempt if no response
 *
 * Uses LeadActivity (type=task_created) consistent with the automation system.
 * Safe to retry: checks for existing task activity by due date before creating.
 */
class ScheduleLandingLeadFollowUpJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int   $tries   = 3;
    public array $backoff = [30, 60, 120];

    public function __construct(
        private readonly int    $leadId,
        private readonly string $locale = 'en',
    ) {}

    public function handle(): void
    {
        $lead = Lead::find($this->leadId);

        if (! $lead) {
            return; // Lead may have been soft-deleted — skip silently
        }

        $this->createFollowUpTask(
            $lead,
            $this->locale === 'pl'
                ? 'Skontaktuj się z nowym leadem z landing page'
                : 'Follow up with new landing page lead',
            1,
        );

        $this->createFollowUpTask(
            $lead,
            $this->locale === 'pl'
                ? 'Drugie przypomnienie — brak odpowiedzi z landing page'
                : 'Second reminder — no response from landing page lead',
            3,
        );
    }

    private function createFollowUpTask(Lead $lead, string $title, int $dueDays): void
    {
        $dueDate = now()->addDays($dueDays)->toDateString();

        // Idempotent: don't duplicate if task already exists for this lead + due date
        $exists = LeadActivity::where('lead_id', $lead->id)
            ->where('type', 'task_created')
            ->whereJsonContains('metadata->due_date', $dueDate)
            ->exists();

        if ($exists) {
            return;
        }

        LeadActivity::log(
            $lead->id,
            'task_created',
            "Task: {$title}",
            [
                'task_title'     => $title,
                'due_date'       => $dueDate,
                'notes'          => '',
                'source'         => 'landing_page_follow_up',
                'landing_page'   => $lead->landing_page_slug,
                'locale'         => $this->locale,
            ],
            null,
        );
    }
}
