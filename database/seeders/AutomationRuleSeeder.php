<?php

namespace Database\Seeders;

use App\Models\AutomationRule;
use App\Models\EmailTemplate;
use App\Models\PipelineStage;
use Illuminate\Database\Seeder;

class AutomationRuleSeeder extends Seeder
{
    public function run(): void
    {
        $welcomeTpl       = EmailTemplate::where('slug', 'welcome_email')->value('id');
        $invoiceSentTpl   = EmailTemplate::where('slug', 'invoice_sent')->value('id');
        $invoiceOverdueTpl= EmailTemplate::where('slug', 'invoice_overdue')->value('id');
        $quoteSentTpl     = EmailTemplate::where('slug', 'quote_sent')->value('id');
        $launchedTpl      = EmailTemplate::where('slug', 'project_launched')->value('id');
        $phaseTpl         = EmailTemplate::where('slug', 'project_phase_complete')->value('id');
        $clientConfirmTpl = EmailTemplate::where('slug', 'lead_received_client')->value('id');
        $wonStageId       = PipelineStage::where('is_won', true)->value('id');

        $rules = [
            [
                'name'          => 'Send Welcome Email on Project Created',
                'trigger_event' => 'project.created',
                'conditions'    => [],
                'actions'       => [
                    ['type' => 'send_email', 'template_id' => $welcomeTpl, 'recipient' => 'client'],
                ],
                'delay_minutes' => 0,
                'is_active'     => true,
            ],
            [
                'name'          => 'Send Invoice Email When Invoice Sent',
                'trigger_event' => 'invoice.sent',
                'conditions'    => [],
                'actions'       => [
                    ['type' => 'send_email', 'template_id' => $invoiceSentTpl, 'recipient' => 'client'],
                ],
                'delay_minutes' => 0,
                'is_active'     => true,
            ],
            [
                'name'          => 'Overdue Invoice First Reminder (14 days)',
                'trigger_event' => 'invoice.overdue',
                'conditions'    => [
                    ['field' => 'days_overdue', 'operator' => '>=', 'value' => 14],
                ],
                'actions'       => [
                    ['type' => 'send_email', 'template_id' => $invoiceOverdueTpl, 'recipient' => 'client'],
                    ['type' => 'notify_team', 'message' => 'Invoice overdue 14+ days — chase required'],
                ],
                'delay_minutes' => 0,
                'is_active'     => true,
            ],
            [
                'name'          => 'Send Quote Email on Quote Issued',
                'trigger_event' => 'quote.sent',
                'conditions'    => [],
                'actions'       => [
                    ['type' => 'send_email', 'template_id' => $quoteSentTpl, 'recipient' => 'client'],
                ],
                'delay_minutes' => 0,
                'is_active'     => true,
            ],
            [
                'name'          => 'Quote Follow-Up Reminder (5 days no response)',
                'trigger_event' => 'quote.sent',
                'conditions'    => [
                    ['field' => 'days_since_sent', 'operator' => '>=', 'value' => 5],
                    ['field' => 'status', 'operator' => '=', 'value' => 'sent'],
                ],
                'actions'       => [
                    ['type' => 'notify_team', 'message' => 'Quote not responded to in 5 days — follow up recommended'],
                ],
                'delay_minutes' => 7200,
                'is_active'     => true,
            ],
            [
                'name'          => 'Notify Team on New Lead Created',
                'trigger_event' => 'lead.created',
                'conditions'    => [],
                'actions'       => [
                    [
                        'type'          => 'notify_team',
                        'title'         => 'New Lead: {{lead_title}}',
                        'message'       => 'New lead added to pipeline: {{lead_title}}',
                        'template_slug' => 'service_cta_admin_mail_notice',
                        'icon'          => 'heroicon-o-funnel',
                        'color'         => 'info',
                    ],
                    ['type' => 'send_email', 'template_id' => $clientConfirmTpl, 'recipient' => 'client'],
                    ['type' => 'assign_task', 'task_title' => 'Initial contact with new lead', 'due_days' => 1],
                ],
                'delay_minutes' => 0,
                'is_active'     => true,
            ],
            [
                'name'          => 'Send Launch Email When Project Completed',
                'trigger_event' => 'project.completed',
                'conditions'    => [],
                'actions'       => [
                    ['type' => 'send_email', 'template_id' => $launchedTpl, 'recipient' => 'client'],
                    ['type' => 'notify_team', 'message' => 'Project {{project_title}} has been marked as complete'],
                ],
                'delay_minutes' => 0,
                'is_active'     => true,
            ],
            [
                'name'          => 'Phase Completion Notification',
                'trigger_event' => 'project_phase.completed',
                'conditions'    => [],
                'actions'       => [
                    ['type' => 'send_email', 'template_id' => $phaseTpl, 'recipient' => 'client'],
                ],
                'delay_minutes' => 0,
                'is_active'     => false,
            ],
            [
                'name'          => 'Create Portal Account on Lead Won',
                'trigger_event' => 'lead.stage_changed',
                'conditions'    => [
                    ['field' => 'stage_id', 'operator' => '=', 'value' => $wonStageId],
                ],
                'actions'       => [
                    ['type' => 'create_portal_access'],
                ],
                'delay_minutes' => 0,
                'is_active'     => true,
            ],
        ];

        foreach ($rules as $data) {
            AutomationRule::updateOrCreate(['name' => $data['name']], $data);
        }
    }
}
