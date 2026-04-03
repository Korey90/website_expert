<?php

namespace App\Notifications;

use App\Models\LandingPage;
use App\Models\Lead;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

/**
 * In-app database notification shown in the Filament notification bell.
 *
 * Sent by NotifyLeadOwnerListener when a new lead is captured from a landing page.
 */
class LeadCapturedNotification extends Notification
{
    use Queueable;

    public function __construct(
        private readonly Lead $lead,
        private readonly LandingPage $landingPage,
    ) {}

    public function via(mixed $notifiable): array
    {
        return ['database'];
    }

    public function toDatabase(mixed $notifiable): array
    {
        $client    = $this->lead->client;
        $name      = $client?->primary_contact_name ?? $client?->primary_contact_email ?? 'Unknown';
        $lpTitle   = $this->landingPage->title;
        $leadRoute = route('filament.admin.resources.leads.view', ['record' => $this->lead->id]);

        return [
            'lead_id'          => $this->lead->id,
            'landing_page_id'  => $this->landingPage->id,
            'title'            => __('notifications.lead_captured_title'),
            'body'             => __('notifications.lead_captured_body', ['name' => $name, 'lp' => $lpTitle]),
            'icon'             => 'heroicon-o-funnel',
            'icon_color'       => 'info',
            'actions'          => [
                [
                    'label'  => __('notifications.view_lead'),
                    'url'    => $leadRoute,
                    'button' => true,
                ],
            ],
            // Metadata for deduplication check in NotifyLeadOwnerListener
            'context'          => [
                'source'      => 'landing_page',
                'utm_source'  => $this->lead->utm_source,
                'utm_medium'  => $this->lead->utm_medium,
                'utm_campaign'=> $this->lead->utm_campaign,
            ],
        ];
    }
}
