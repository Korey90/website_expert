<?php

namespace Database\Seeders;

use App\Models\AutomationTrigger;
use Illuminate\Database\Seeder;

class AutomationTriggerSeeder extends Seeder
{
    public function run(): void
    {
        $triggers = [
            // ── Leads ─────────────────────────────────────────────────────────
            [
                'key'         => 'lead.created',
                'label'       => 'Lead Created',
                'group'       => 'Leads',
                'description' => 'Fires when any new lead is created in the system.',
                'variables'   => [
                    ['name' => 'lead_id',      'description' => 'Lead ID'],
                    ['name' => 'lead_title',   'description' => 'Lead title'],
                    ['name' => 'client_name',  'description' => 'Contact name'],
                    ['name' => 'company_name', 'description' => 'Company name'],
                    ['name' => 'stage_name',   'description' => 'Pipeline stage'],
                    ['name' => 'source',       'description' => 'Lead source (contact_form, service_cta, landing_page…)'],
                ],
            ],
            [
                'key'         => 'lead.stage_changed',
                'label'       => 'Lead Stage Changed',
                'group'       => 'Leads',
                'description' => 'Fires when a lead is moved to a different pipeline stage.',
                'variables'   => [
                    ['name' => 'lead_id',      'description' => 'Lead ID'],
                    ['name' => 'stage_id',     'description' => 'New stage ID'],
                    ['name' => 'old_stage_id', 'description' => 'Previous stage ID'],
                    ['name' => 'client_name',  'description' => 'Contact name'],
                ],
            ],
            [
                'key'         => 'lead.assigned',
                'label'       => 'Lead Assigned',
                'group'       => 'Leads',
                'description' => 'Fires when a lead is assigned to a team member.',
                'variables'   => [
                    ['name' => 'lead_id',     'description' => 'Lead ID'],
                    ['name' => 'assignee_id', 'description' => 'Assigned user ID'],
                ],
            ],
            [
                'key'         => 'lead.service_cta',
                'label'       => 'Lead: Service CTA Form',
                'group'       => 'Leads',
                'description' => 'Fires when a lead submits the inline CTA form on a service page.',
                'variables'   => [
                    ['name' => 'lead_id',     'description' => 'Lead ID'],
                    ['name' => 'source',      'description' => 'Always "service_cta"'],
                    ['name' => 'client_name', 'description' => 'Contact name from form'],
                ],
            ],
            [
                'key'         => 'lead.contact_form',
                'label'       => 'Lead: Contact Form',
                'group'       => 'Leads',
                'description' => 'Fires when a lead submits the main contact form.',
                'variables'   => [
                    ['name' => 'lead_id',     'description' => 'Lead ID'],
                    ['name' => 'source',      'description' => 'Always "contact_form"'],
                    ['name' => 'client_name', 'description' => 'Contact name from form'],
                ],
            ],
            // ── Projects ──────────────────────────────────────────────────────
            [
                'key'         => 'project.created',
                'label'       => 'Project Created',
                'group'       => 'Projects',
                'variables'   => [
                    ['name' => 'project_id',   'description' => 'Project ID'],
                    ['name' => 'project_name', 'description' => 'Project name'],
                    ['name' => 'client_name',  'description' => 'Client name'],
                    ['name' => 'status',       'description' => 'Initial status'],
                ],
            ],
            [
                'key'         => 'project.status_changed',
                'label'       => 'Project Status Changed',
                'group'       => 'Projects',
                'variables'   => [
                    ['name' => 'project_id',   'description' => 'Project ID'],
                    ['name' => 'status',       'description' => 'New status'],
                    ['name' => 'old_status',   'description' => 'Previous status'],
                ],
            ],
            [
                'key'         => 'project.completed',
                'label'       => 'Project Completed',
                'group'       => 'Projects',
                'variables'   => [
                    ['name' => 'project_id',   'description' => 'Project ID'],
                    ['name' => 'project_name', 'description' => 'Project name'],
                    ['name' => 'client_name',  'description' => 'Client name'],
                ],
            ],
            // ── Invoices ──────────────────────────────────────────────────────
            [
                'key'         => 'invoice.sent',
                'label'       => 'Invoice Sent',
                'group'       => 'Invoices',
                'variables'   => [
                    ['name' => 'invoice_id',     'description' => 'Invoice ID'],
                    ['name' => 'invoice_number', 'description' => 'Invoice number'],
                    ['name' => 'client_name',    'description' => 'Client name'],
                ],
            ],
            [
                'key'         => 'invoice.overdue',
                'label'       => 'Invoice Overdue',
                'group'       => 'Invoices',
                'variables'   => [
                    ['name' => 'invoice_id',     'description' => 'Invoice ID'],
                    ['name' => 'invoice_number', 'description' => 'Invoice number'],
                    ['name' => 'client_name',    'description' => 'Client name'],
                ],
            ],
            [
                'key'         => 'invoice.paid',
                'label'       => 'Invoice Paid',
                'group'       => 'Invoices',
                'variables'   => [
                    ['name' => 'invoice_id',     'description' => 'Invoice ID'],
                    ['name' => 'invoice_number', 'description' => 'Invoice number'],
                    ['name' => 'client_name',    'description' => 'Client name'],
                ],
            ],
            // ── Quotes ────────────────────────────────────────────────────────
            [
                'key'   => 'quote.sent',
                'label' => 'Quote Sent',
                'group' => 'Quotes',
                'variables' => [
                    ['name' => 'quote_id',    'description' => 'Quote ID'],
                    ['name' => 'client_name', 'description' => 'Client name'],
                ],
            ],
            [
                'key'   => 'quote.accepted',
                'label' => 'Quote Accepted',
                'group' => 'Quotes',
                'variables' => [
                    ['name' => 'quote_id',    'description' => 'Quote ID'],
                    ['name' => 'client_name', 'description' => 'Client name'],
                ],
            ],
            // ── Contracts ─────────────────────────────────────────────────────
            [
                'key'   => 'contract.created',
                'label' => 'Contract Created',
                'group' => 'Contracts',
                'variables' => [['name' => 'contract_id', 'description' => 'Contract ID']],
            ],
            [
                'key'   => 'contract.sent',
                'label' => 'Contract Sent',
                'group' => 'Contracts',
                'variables' => [['name' => 'contract_id', 'description' => 'Contract ID']],
            ],
            [
                'key'   => 'contract.signed',
                'label' => 'Contract Signed',
                'group' => 'Contracts',
                'variables' => [
                    ['name' => 'contract_id', 'description' => 'Contract ID'],
                    ['name' => 'client_name', 'description' => 'Client name'],
                ],
            ],
            [
                'key'   => 'contract.expired',
                'label' => 'Contract Expired',
                'group' => 'Contracts',
                'variables' => [['name' => 'contract_id', 'description' => 'Contract ID']],
            ],
        ];

        foreach ($triggers as $data) {
            AutomationTrigger::updateOrCreate(
                ['key' => $data['key']],
                array_merge($data, ['is_system' => true, 'is_active' => true]),
            );
        }
    }
}
