<?php

namespace App\Automation\Actions;

use App\Automation\ActionSkippedException;
use App\Models\CalendarEvent;
use App\Models\Contract;
use App\Models\Invoice;
use App\Models\Lead;
use App\Models\Project;
use App\Models\Quote;
use App\Services\Calendar\GoogleCalendarService;

/**
 * Creates a CalendarEvent entry linked (via morph) to the triggering model.
 *
 * Action config keys:
 *   title            (string, required)     — event title, supports {{vars}}
 *   event_type       (string, required)     — meeting|call|deadline|reminder|task
 *   offset_days      (int, optional)        — days from now; default 0
 *   all_day          (bool, optional)       — default true
 *   duration_minutes (int, optional)        — only when all_day=false; default 60
 *   description      (string, optional)     — supports {{vars}}
 *   sync_to_google   (bool, optional)       — push to Google Calendar; default false
 */
class CreateCalendarEventAction extends BaseAutomationAction
{
    public function execute(array $action, array $context, string $triggerEvent): void
    {
        $businessId = $context['business_id'] ?? null;

        if (! $businessId) {
            throw new ActionSkippedException('create_calendar_event requires business_id in context');
        }

        $vars  = array_merge($context, $this->buildTemplateVars($context));
        $title = trim($this->interpolate($action['title'] ?? '', $vars));

        if ($title === '') {
            throw new ActionSkippedException('create_calendar_event: title is required');
        }

        $type = $action['event_type'] ?? null;
        if (! in_array($type, CalendarEvent::TYPES, true)) {
            throw new ActionSkippedException("create_calendar_event: invalid event_type [{$type}]");
        }

        $allDay          = (bool) ($action['all_day'] ?? true);
        $offsetDays      = max(0, (int) ($action['offset_days'] ?? 0));
        $durationMinutes = max(15, (int) ($action['duration_minutes'] ?? 60));
        $description     = $this->interpolate($action['description'] ?? '', $vars);

        $startsAt = now()->addDays($offsetDays);
        $endsAt   = $allDay ? null : $startsAt->copy()->addMinutes($durationMinutes);

        if ($allDay) {
            $startsAt->startOfDay();
        }

        // ── Resolve morph relation ────────────────────────────────────────
        [$relatedType, $relatedId, $userId] = $this->resolveRelated($context);

        // ── Create event ──────────────────────────────────────────────────
        $event = CalendarEvent::create([
            'business_id'  => $businessId,
            'user_id'      => $userId,
            'title'        => $title,
            'description'  => $description ?: null,
            'type'         => $type,
            'status'       => 'scheduled',
            'all_day'      => $allDay,
            'starts_at'    => $startsAt,
            'ends_at'      => $endsAt,
            'related_type' => $relatedType,
            'related_id'   => $relatedId,
        ]);

        // ── Optional Google sync ──────────────────────────────────────────
        if (! empty($action['sync_to_google']) && $userId) {
            try {
                app(GoogleCalendarService::class)->pushEvent($event, $userId, $businessId);
            } catch (\Throwable $e) {
                // Non-fatal — event was created, sync just failed
                \Illuminate\Support\Facades\Log::warning(
                    "create_calendar_event: Google sync failed for event #{$event->id}: " . $e->getMessage()
                );
            }
        }
    }

    /**
     * Returns [relatedType, relatedId, userId] from context.
     * Priority: lead > project > invoice > quote > contract.
     */
    private function resolveRelated(array $context): array
    {
        if (! empty($context['lead_id'])) {
            $lead = Lead::find($context['lead_id']);
            return [Lead::class, $context['lead_id'], $lead?->assigned_to];
        }

        if (! empty($context['project_id'])) {
            $project = Project::find($context['project_id']);
            return [Project::class, $context['project_id'], $project?->assigned_to];
        }

        if (! empty($context['invoice_id'])) {
            return [Invoice::class, $context['invoice_id'], null];
        }

        if (! empty($context['quote_id'])) {
            return [Quote::class, $context['quote_id'], null];
        }

        if (! empty($context['contract_id'])) {
            return [Contract::class, $context['contract_id'], null];
        }

        return [null, null, null];
    }
}
