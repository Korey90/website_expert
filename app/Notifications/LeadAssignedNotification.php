<?php

namespace App\Notifications;

use App\Models\Lead;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

/**
 * In-app database notification sent when a lead is manually assigned to a user.
 *
 * Dispatched from LeadService::assign() via the LeadAssigned event
 * (handled by AutomationEventListener — send_email action can also trigger this).
 */
class LeadAssignedNotification extends Notification
{
    use Queueable;

    public function __construct(
        private readonly Lead $lead,
        private readonly User $assignedBy,
    ) {}

    public function via(mixed $notifiable): array
    {
        return ['database'];
    }

    public function toDatabase(mixed $notifiable): array
    {
        $leadRoute = route('filament.admin.resources.leads.view', ['record' => $this->lead->id]);

        return [
            'lead_id'       => $this->lead->id,
            'assigned_by'   => $this->assignedBy->name,
            'title'         => __('notifications.lead_assigned_title'),
            'body'          => __('notifications.lead_assigned_body', [
                'title'  => $this->lead->title,
                'by'     => $this->assignedBy->name,
            ]),
            'icon'          => 'heroicon-o-user-plus',
            'icon_color'    => 'warning',
            'actions'       => [
                [
                    'label'  => __('notifications.view_lead'),
                    'url'    => $leadRoute,
                    'button' => true,
                ],
            ],
        ];
    }
}
