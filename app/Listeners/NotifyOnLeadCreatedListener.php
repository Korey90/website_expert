<?php

namespace App\Listeners;

use App\Mail\TemplatedMailable;
use App\Models\EmailTemplate;
use App\Models\Lead;
use App\Models\LeadActivity;
use App\Models\User;
use App\Notifications\LeadFromSourceNotification;
use Filament\Notifications\Events\DatabaseNotificationsSent;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

/**
 * Notifies admins/managers when a new lead arrives from a non-LP source
 * (service_cta, contact_form, api, etc.).
 *
 * Triggered by the Eloquent `created` event on Lead model.
 * LP leads are skipped — they are handled by NotifyLeadOwnerListener via LeadCaptured.
 *
 * Uses EmailTemplate from DB (by slug) to send the notification email.
 * Idempotent: checks for existing database notification before sending.
 */
class NotifyOnLeadCreatedListener implements ShouldQueue
{
    use InteractsWithQueue;

    public string $queue = 'notifications';
    public int    $tries = 3;
    public array  $backoff = [30, 60, 120];

    /** Template slugs per source. Falls back to the generic slug. */
    private const TEMPLATE_SLUGS = [
        'service_cta'  => 'service_cta_admin_mail_notice',
        'contact_form' => 'contact_form_admin_notice',
    ];

    private const FALLBACK_SLUG = 'service_cta_admin_mail_notice';

    public function handle(Lead $lead): void
    {
        // LP leads are handled by NotifyLeadOwnerListener via LeadCaptured event
        if ($lead->source === 'landing_page') {
            return;
        }

        $lead->loadMissing(['client']);

        $recipients = $this->resolveRecipients($lead);

        if ($recipients->isEmpty()) {
            Log::channel('leads')->warning('NotifyOnLeadCreatedListener: no recipients found', [
                'lead_id' => $lead->id,
                'source'  => $lead->source,
            ]);
            return;
        }

        $template = $this->resolveTemplate($lead->source);
        $vars     = $this->buildVars($lead);

        foreach ($recipients as $user) {
            if ($this->alreadyNotified($user, $lead->id)) {
                continue;
            }

            // In-app bell notification
            $user->notify(new LeadFromSourceNotification($lead));
            DatabaseNotificationsSent::dispatch($user);

            // Email from EmailTemplate in DB
            if ($template) {
                $locale  = app()->getLocale();
                $content = $template->getForLocale($locale);

                $subject = $this->interpolate($content['subject'] ?? '', $vars);
                $body    = $this->interpolate($content['body_html'] ?? '', $vars);

                if ($subject && $body) {
                    // Already inside a queued job — send synchronously, no double-hop
                    Mail::to($user->email)->send(new TemplatedMailable($subject, $body));
                }
            }
        }

        try {
            LeadActivity::log(
                $lead->id,
                'notification_sent',
                'Admin notified of new lead',
                [
                    'source'     => $lead->source,
                    'recipients' => $recipients->pluck('email')->toArray(),
                ],
                null,
            );
        } catch (\Throwable $e) {
            // Lead may have been deleted between serialization and processing — skip logging
            Log::channel('leads')->warning('NotifyOnLeadCreatedListener: failed to log activity', [
                'lead_id' => $lead->id,
                'error'   => $e->getMessage(),
            ]);
        }

        Log::channel('leads')->info('NotifyOnLeadCreatedListener: admins notified', [
            'lead_id'    => $lead->id,
            'source'     => $lead->source,
            'recipients' => $recipients->count(),
        ]);
    }

    // ─────────────────────────────────────────────────────────────────────────

    private function resolveRecipients(Lead $lead): \Illuminate\Database\Eloquent\Collection
    {
        if ($lead->assigned_to) {
            $assignee = User::find($lead->assigned_to);
            return $assignee
                ? User::whereKey($assignee->id)->get()
                : $this->fallbackAdmins($lead->business_id);
        }

        return $this->fallbackAdmins($lead->business_id);
    }

    private function fallbackAdmins(string|int|null $businessId): \Illuminate\Database\Eloquent\Collection
    {
        $query = User::whereHas('roles', fn ($q) => $q->whereIn('name', ['admin', 'manager', 'super_admin']))
            ->where('is_active', true)
            ->limit(10);

        if ($businessId) {
            $query->whereHas('businesses', fn ($q) => $q->where('businesses.id', $businessId));
        }

        return $query->get();
    }

    private function resolveTemplate(string $source): ?EmailTemplate
    {
        $slug = self::TEMPLATE_SLUGS[$source] ?? self::FALLBACK_SLUG;

        return EmailTemplate::where('slug', $slug)
            ->where('is_active', true)
            ->first()
            ?? EmailTemplate::where('slug', self::FALLBACK_SLUG)
                ->where('is_active', true)
                ->first();
    }

    private function buildVars(Lead $lead): array
    {
        $client = $lead->client;

        return [
            '{{client_name}}' => $client?->primary_contact_name ?? $client?->primary_contact_email ?? 'Unknown',
            '{{lead_name}}'   => $lead->title ?? 'New Lead',
            '{{lead_source}}' => $lead->source ?? '',
            '{{lead_id}}'     => (string) $lead->id,
            '{{lead_url}}'    => route('filament.admin.resources.leads.view', ['record' => $lead->id]),
        ];
    }

    private function interpolate(string $template, array $vars): string
    {
        return str_replace(array_keys($vars), array_values($vars), $template);
    }

    private function alreadyNotified(User $user, int $leadId): bool
    {
        return $user->notifications()
            ->where('type', LeadFromSourceNotification::class)
            ->whereJsonContains('data->lead_id', $leadId)
            ->exists();
    }
}
