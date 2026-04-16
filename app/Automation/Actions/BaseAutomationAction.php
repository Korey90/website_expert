<?php

namespace App\Automation\Actions;

use App\Automation\AutomationActionContract;
use App\Models\Client;
use App\Models\Invoice;
use App\Models\Lead;
use App\Models\Project;
use App\Models\Quote;

abstract class BaseAutomationAction implements AutomationActionContract
{
    protected function resolveClient(array $context): ?Client
    {
        if (isset($context['client_id'])) {
            return Client::find($context['client_id']);
        }
        if (isset($context['lead_id'])) {
            return Lead::find($context['lead_id'])?->client;
        }
        if (isset($context['project_id'])) {
            return Project::find($context['project_id'])?->client;
        }
        if (isset($context['invoice_id'])) {
            return Invoice::find($context['invoice_id'])?->client;
        }
        if (isset($context['quote_id'])) {
            return Quote::find($context['quote_id'])?->client;
        }

        return null;
    }

    protected function resolveClientEmail(array $context): ?string
    {
        if (isset($context['client_id'])) {
            return Client::find($context['client_id'])?->primary_contact_email;
        }
        if (isset($context['lead_id'])) {
            $lead = Lead::find($context['lead_id']);
            return $lead?->client?->primary_contact_email ?? $lead?->email;
        }
        if (isset($context['project_id'])) {
            return Project::find($context['project_id'])?->client?->primary_contact_email;
        }
        if (isset($context['invoice_id'])) {
            return Invoice::find($context['invoice_id'])?->client?->primary_contact_email;
        }
        if (isset($context['quote_id'])) {
            return Quote::find($context['quote_id'])?->client?->primary_contact_email;
        }

        return null;
    }

    protected function resolveClientPhone(array $context): ?string
    {
        if (isset($context['client_id'])) {
            return Client::find($context['client_id'])?->primary_contact_phone;
        }
        if (isset($context['lead_id'])) {
            $lead = Lead::find($context['lead_id']);
            return $lead?->client?->primary_contact_phone;
        }
        if (isset($context['project_id'])) {
            return Project::find($context['project_id'])?->client?->primary_contact_phone;
        }
        if (isset($context['invoice_id'])) {
            return Invoice::find($context['invoice_id'])?->client?->primary_contact_phone;
        }
        if (isset($context['quote_id'])) {
            return Quote::find($context['quote_id'])?->client?->primary_contact_phone;
        }

        return null;
    }

    protected function resolveRecipientEmail(string $recipient, array $context): ?string
    {
        return match ($recipient) {
            'client' => $this->resolveClientEmail($context),
            'admin'  => config('mail.admin_address', 'admin@websiteexpert.co.uk'),
            default  => filter_var($recipient, FILTER_VALIDATE_EMAIL) ? $recipient : null,
        };
    }

    protected function resolveRecipientPhone(string $recipient, array $context): ?string
    {
        return match ($recipient) {
            'client' => $this->resolveClientPhone($context),
            default  => preg_match('/^\+?[\d\s\-()]{7,}$/', $recipient) ? $recipient : null,
        };
    }

    protected function buildTemplateVars(array $context): array
    {
        $vars = ['today' => now()->format('d M Y')];

        if (isset($context['lead_id'])) {
            $lead = Lead::with(['client', 'stage', 'assignedTo'])->find($context['lead_id']);
            if ($lead) {
                $vars['lead_title']    = $lead->title ?? '';
                $vars['client_name']   = $lead->client?->primary_contact_name ?? '';
                $vars['company_name']  = $lead->client?->company_name ?? '';
                $vars['stage_name']    = $lead->stage?->name ?? '';
                $vars['assigned_name'] = $lead->assignedTo?->name ?? '';
                $vars['project_name']  = $lead->project?->name ?? '';
            }
        }

        if (isset($context['project_id'])) {
            $project = Project::with(['client', 'assignedTo'])->find($context['project_id']);
            if ($project) {
                $vars['project_name']  = $project->name ?? '';
                $vars['client_name']   = $vars['client_name']  ?? ($project->client?->primary_contact_name ?? '');
                $vars['company_name']  = $vars['company_name'] ?? ($project->client?->company_name ?? '');
                $vars['assigned_name'] = $vars['assigned_name'] ?? ($project->assignedTo?->name ?? '');
            }
        }

        if (isset($context['invoice_id'])) {
            $invoice = Invoice::with('client')->find($context['invoice_id']);
            if ($invoice) {
                $vars['invoice_number'] = $invoice->invoice_number ?? "#{$invoice->id}";
                $vars['client_name']    = $vars['client_name']  ?? ($invoice->client?->primary_contact_name ?? '');
                $vars['company_name']   = $vars['company_name'] ?? ($invoice->client?->company_name ?? '');
            }
        }

        return $vars;
    }

    protected function interpolate(string $template, array $vars): string
    {
        foreach ($vars as $key => $value) {
            $template = str_replace('{' . $key . '}', (string) ($value ?? ''), $template);
        }

        return $template;
    }

    protected function resolveMailable(string|null $template, array $context): ?object
    {
        return match ($template) {
            'invoice_sent'   => isset($context['invoice_id'])
                ? new \App\Mail\InvoiceSentMail(\App\Models\Invoice::findOrFail($context['invoice_id']))
                : null,
            'quote_sent'     => isset($context['quote_id'])
                ? new \App\Mail\QuoteSentMail(\App\Models\Quote::findOrFail($context['quote_id']))
                : null,
            'project_status' => isset($context['project_id'])
                ? new \App\Mail\ProjectStatusMail(
                    \App\Models\Project::findOrFail($context['project_id']),
                    $context['old_status'] ?? 'unknown',
                )
                : null,
            'new_lead'       => isset($context['lead_id'])
                ? new \App\Mail\NewLeadMail($context, $context['lead_id'])
                : null,
            default => null,
        };
    }
}
