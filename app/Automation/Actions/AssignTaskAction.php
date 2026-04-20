<?php

namespace App\Automation\Actions;

use App\Automation\ActionSkippedException;
use App\Models\Lead;
use App\Models\LeadActivity;

/**
 * Creates an activity/task reminder entry on the Lead.
 *
 * Action config keys:
 *   task_title  (string, required) — task title, supports {{vars}}
 *   due_days    (int, optional)    — days from now until due; default 1
 *   notes       (string, optional) — additional notes
 */
class AssignTaskAction extends BaseAutomationAction
{
    public function execute(array $action, array $context, string $triggerEvent): void
    {
        $leadId = $context['lead_id'] ?? null;

        if (! $leadId) {
            throw new ActionSkippedException('assign_task requires lead_id in context');
        }

        $lead = Lead::find($leadId);

        if (! $lead) {
            throw new ActionSkippedException("Lead #{$leadId} not found");
        }

        $vars     = array_merge($context, $this->buildTemplateVars($context));
        $title    = $this->interpolate($action['task_title'] ?? 'Follow up', $vars);
        $dueDays  = (int) ($action['due_days'] ?? 1);
        $notes    = $this->interpolate($action['notes'] ?? '', $vars);
        $dueDate  = now()->addDays($dueDays)->toDateString();

        LeadActivity::log(
            $lead->id,
            'task_created',
            "Task created by automation: {$title}",
            [
                'task_title'  => $title,
                'due_date'    => $dueDate,
                'notes'       => $notes,
                'source'      => 'automation',
                'trigger'     => $triggerEvent,
            ],
            null,
        );
    }
}
