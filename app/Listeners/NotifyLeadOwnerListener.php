<?php

namespace App\Listeners;

use App\Events\LeadCaptured;
use App\Mail\NewLeadAssignedMail;
use App\Models\User;
use App\Notifications\LeadCapturedNotification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

/**
 * Sends in-app database notifications (and queued email) whenever a lead
 * is captured from a landing page.
 *
 * Retry-safe: uses the lead_id as a unique lock key — reprocessing the same
 * LeadCaptured event will not duplicate notifications because
 * DatabaseNotification rows are checked for existence before inserting.
 *
 * Queued: runs on the 'notifications' queue — isolated from automation queue.
 */
class NotifyLeadOwnerListener implements ShouldQueue
{
    use InteractsWithQueue;

    public string $queue = 'notifications';
    public int    $tries = 3;
    public array  $backoff = [30, 60, 120];

    /**
     * Provide a unique ID so that retries of the same event never run twice
     * simultaneously (prevents duplicate notification rows on retry).
     */
    public function uniqueId(LeadCaptured $event): string
    {
        return 'notify-lead-captured-' . $event->lead->id;
    }

    public function handle(LeadCaptured $event): void
    {
        $lead = $event->lead;
        $lp   = $event->landingPage;

        // ── Determine recipients ──────────────────────────────────────────
        $recipients = $this->resolveRecipients($lead);

        if ($recipients->isEmpty()) {
            Log::channel('leads')->warning('NotifyLeadOwnerListener: no recipients found', [
                'lead_id'     => $lead->id,
                'business_id' => $lead->business_id,
            ]);
            return;
        }

        // ── Send in-app + email notification ─────────────────────────────
        foreach ($recipients as $user) {
            // Guard: idempotent — don't re-notify if already notified for this lead
            if ($this->alreadyNotified($user, $lead->id)) {
                continue;
            }

            // Database notification (shown in Filament notification bell)
            $user->notify(new LeadCapturedNotification($lead, $lp));

            // Queued email to assigned user only (not all admins)
            if ($lead->assigned_to === $user->id) {
                Mail::to($user->email)->queue(new NewLeadAssignedMail($lead, $user));
            }
        }

        // ── Activity log ─────────────────────────────────────────────────
        \App\Models\LeadActivity::log(
            $lead->id,
            'notification_sent',
            'Owner notified of new lead',
            ['recipients' => $recipients->pluck('email')->toArray()],
            null,
        );
    }

    /**
     * Resolve who should receive the notification.
     * Priority: assigned user → all admins/managers of the business.
     *
     * @return \Illuminate\Database\Eloquent\Collection<User>
     */
    private function resolveRecipients(\App\Models\Lead $lead): \Illuminate\Database\Eloquent\Collection
    {
        // Assigned user gets notified first and exclusively
        if ($lead->assigned_to) {
            $assignee = User::find($lead->assigned_to);
            return $assignee
                ? User::whereKey($assignee->id)->get()
                : User::whereHas('roles', fn ($q) => $q->whereIn('name', ['admin', 'manager']))
                      ->limit(5)
                      ->get();
        }

        // No assignee → notify all active admins/managers for this business
        return User::whereHas('businesses', function ($q) use ($lead) {
            $q->where('businesses.id', $lead->business_id);
        })
        ->whereHas('roles', fn ($q) => $q->whereIn('name', ['admin', 'manager']))
        ->where('is_active', true)
        ->limit(10)
        ->get();
    }

    /**
     * Check if this user was already notified about this lead (idempotency guard).
     */
    private function alreadyNotified(User $user, int $leadId): bool
    {
        return $user->notifications()
            ->where('type', LeadCapturedNotification::class)
            ->whereJsonContains('data->lead_id', $leadId)
            ->exists();
    }
}
