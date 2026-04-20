<?php

namespace App\Notifications;

use App\Models\Lead;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

/**
 * In-app database notification for admin/manager when a new lead
 * arrives from a non-landing-page source (service_cta, contact_form, etc.).
 *
 * Sent by NotifyOnLeadCreatedListener.
 */
class LeadFromSourceNotification extends Notification
{
    use Queueable;

    public function __construct(
        private readonly Lead $lead,
    ) {}

    public function via(mixed $notifiable): array
    {
        return ['database'];
    }

    public function toDatabase(mixed $notifiable): array
    {
        $client    = $this->lead->client;
        $name      = $client?->primary_contact_name ?? $client?->primary_contact_email ?? 'Unknown';
        $source    = $this->lead->source ?? 'unknown';
        $leadRoute = route('filament.admin.resources.leads.view', ['record' => $this->lead->id]);

        return [
            'lead_id'    => $this->lead->id,
            'title'      => __('notifications.lead_captured_title'),
            'body'       => __('notifications.lead_source_body', ['name' => $name, 'source' => $source]),
            'icon'       => 'heroicon-o-funnel',
            'icon_color' => 'info',
            'actions'    => [
                [
                    'label'  => __('notifications.view_lead'),
                    'url'    => $leadRoute,
                    'button' => true,
                ],
            ],
            'context' => [
                'source' => $source,
            ],
        ];
    }
}
