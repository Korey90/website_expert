<?php

namespace App\Services\Calendar;

use App\Models\CalendarEvent;
use App\Models\Contract;
use App\Models\Invoice;
use App\Models\Lead;
use App\Models\Project;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * Aggregates calendar events from CalendarEvent model and virtual events
 * derived from date fields across Lead, Project, Invoice, Contract.
 */
class CalendarFeedService
{
    /**
     * Return all events for FullCalendar within the given range.
     * If no range given, defaults to current month ±1.
     */
    public function getEvents(?string $businessId, Carbon $from = null, Carbon $to = null): array
    {
        $from ??= now()->startOfMonth()->subMonth();
        $to   ??= now()->endOfMonth()->addMonth();

        $events = collect();

        // 1. CalendarEvent (manual / synced)
        $events = $events->merge($this->getCalendarEvents($businessId, $from, $to));

        // 2. Project deadlines
        $events = $events->merge($this->getProjectDeadlines($businessId, $from, $to));

        // 3. Invoice due dates
        $events = $events->merge($this->getInvoiceDueDates($businessId, $from, $to));

        // 4. Contract expirations
        $events = $events->merge($this->getContractExpirations($businessId, $from, $to));

        // 5. Lead close dates
        $events = $events->merge($this->getLeadCloseDates($businessId, $from, $to));

        return $events->values()->all();
    }

    // ── Private feed builders ─────────────────────────────────────────────

    private function getCalendarEvents(?string $businessId, Carbon $from, Carbon $to): Collection
    {
        return CalendarEvent::when($businessId, fn ($q) => $q->where('business_id', $businessId))
            ->whereBetween('starts_at', [$from, $to])
            ->get()
            ->map(fn (CalendarEvent $e) => $e->toCalendarArray());
    }

    private function getProjectDeadlines(?string $businessId, Carbon $from, Carbon $to): Collection
    {
        return Project::withoutTrashed()
            ->when($businessId, fn ($q) => $q->whereHas('client', fn ($c) => $c->where('business_id', $businessId)))
            ->whereNotNull('deadline')
            ->whereBetween('deadline', [$from->toDateString(), $to->toDateString()])
            ->whereNotIn('status', ['completed', 'cancelled'])
            ->get()
            ->map(fn (Project $p) => [
                'id'    => 'project-deadline-' . $p->id,
                'title' => '📁 ' . $p->title . ' — deadline',
                'start' => Carbon::parse($p->deadline)->toIso8601String(),
                'allDay'=> true,
                'color' => '#ef4444',
                'extendedProps' => [
                    'type'    => 'deadline',
                    'status'  => $p->status,
                    'editUrl' => route('filament.admin.resources.projects.edit', $p->id),
                    'virtual' => true,
                ],
            ]);
    }

    private function getInvoiceDueDates(?string $businessId, Carbon $from, Carbon $to): Collection
    {
        return Invoice::when($businessId, fn ($q) => $q->whereHas('client', fn ($c) => $c->where('business_id', $businessId)))
            ->whereNotNull('due_date')
            ->whereBetween('due_date', [$from->toDateString(), $to->toDateString()])
            ->whereNotIn('status', ['paid', 'cancelled'])
            ->get()
            ->map(fn (Invoice $i) => [
                'id'    => 'invoice-due-' . $i->id,
                'title' => '🧾 Invoice #' . ($i->number ?? $i->id) . ' due',
                'start' => Carbon::parse($i->due_date)->toIso8601String(),
                'allDay'=> true,
                'color' => '#f59e0b',
                'extendedProps' => [
                    'type'    => 'deadline',
                    'status'  => $i->status,
                    'editUrl' => route('filament.admin.resources.invoices.edit', $i->id),
                    'virtual' => true,
                ],
            ]);
    }

    private function getContractExpirations(?string $businessId, Carbon $from, Carbon $to): Collection
    {
        return Contract::when($businessId, fn ($q) => $q->whereHas('client', fn ($c) => $c->where('business_id', $businessId)))
            ->whereNotNull('expires_at')
            ->whereBetween('expires_at', [$from->toDateString(), $to->toDateString()])
            ->whereNotIn('status', ['expired', 'terminated'])
            ->get()
            ->map(fn (Contract $c) => [
                'id'    => 'contract-expiry-' . $c->id,
                'title' => '📄 Contract expires: ' . ($c->title ?? '#' . $c->id),
                'start' => Carbon::parse($c->expires_at)->toIso8601String(),
                'allDay'=> true,
                'color' => '#8b5cf6',
                'extendedProps' => [
                    'type'    => 'deadline',
                    'editUrl' => route('filament.admin.resources.contracts.edit', $c->id),
                    'virtual' => true,
                ],
            ]);
    }

    private function getLeadCloseDates(?string $businessId, Carbon $from, Carbon $to): Collection
    {
        return Lead::when($businessId, fn ($q) => $q->where('business_id', $businessId))
            ->whereNotNull('expected_close_date')
            ->whereBetween('expected_close_date', [$from->toDateString(), $to->toDateString()])
            ->whereNull('won_at')
            ->whereNull('lost_at')
            ->get()
            ->map(fn (Lead $l) => [
                'id'    => 'lead-close-' . $l->id,
                'title' => '🎯 ' . $l->title . ' — close',
                'start' => Carbon::parse($l->expected_close_date)->toIso8601String(),
                'allDay'=> true,
                'color' => '#10b981',
                'extendedProps' => [
                    'type'    => 'deadline',
                    'editUrl' => route('filament.admin.resources.leads.edit', $l->id),
                    'virtual' => true,
                ],
            ]);
    }
}
