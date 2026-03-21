<?php

namespace Database\Seeders;

use App\Models\PipelineStage;
use Illuminate\Database\Seeder;

class PipelineStageChecklistSeeder extends Seeder
{
    public function run(): void
    {
        $defaults = [
            'New Lead' => [
                ['label' => 'Record contact details',    'condition' => 'has_client'],
                ['label' => 'Confirm budget range',      'condition' => 'has_value'],
                ['label' => 'Identify project type',     'condition' => 'has_calculator_data'],
                ['label' => 'Add to CRM & assign owner', 'condition' => 'has_assignee'],
            ],
            'Contacted' => [
                ['label' => 'Send introductory email',         'condition' => 'email_sent'],
                ['label' => 'Schedule discovery call',         'condition' => null],
                ['label' => 'Qualify requirements & goals',    'condition' => null],
                ['label' => 'Confirm decision-maker contact',  'condition' => 'has_contact'],
            ],
            'Proposal Sent' => [
                ['label' => 'Prepare custom proposal',    'condition' => null],
                ['label' => 'Review pricing with team',   'condition' => null],
                ['label' => 'Send proposal to client',    'condition' => 'email_sent'],
                ['label' => 'Follow up after 3 days',     'condition' => null],
            ],
            'Negotiation' => [
                ['label' => 'Discuss revisions to proposal', 'condition' => null],
                ['label' => 'Confirm project timeline',      'condition' => 'has_expected_close'],
                ['label' => 'Agree on payment terms',        'condition' => null],
                ['label' => 'Get signed contract / deposit', 'condition' => null],
            ],
            'Won' => [
                ['label' => 'Convert lead to project',     'condition' => 'has_project'],
                ['label' => 'Schedule kickoff call',        'condition' => null],
                ['label' => 'Send onboarding materials',    'condition' => 'email_sent'],
            ],
            'Lost' => [
                ['label' => 'Log reason for losing',                   'condition' => null],
                ['label' => 'Send polite closing email',               'condition' => 'email_sent'],
                ['label' => 'Add to re-engagement list for future',    'condition' => null],
            ],
        ];

        foreach ($defaults as $stageName => $items) {
            PipelineStage::where('name', $stageName)->update(['checklist' => $items]);
        }
    }
}
