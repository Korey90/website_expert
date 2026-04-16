<?php

namespace App\Actions;

use App\Models\Client;
use App\Models\Contact;
use App\Models\Lead;
use App\Models\LeadActivity;
use App\Models\PipelineStage;

class CreateLeadAction
{
    /**
     * Create a lead from any inbound source (contact form, calculator, LP, API, etc.)
     *
     * @param  array{
     *     email: string,
     *     name?: string|null,
     *     first_name?: string|null,
     *     last_name?: string|null,
     *     company?: string|null,
     *     phone?: string|null,
     *     nip?: string|null,
     *     project_type?: string|null,
     *     source: string,
     *     notes?: string|null,
     *     value?: float|null,
     *     calculator_data?: array|null,
     *     form_data?: array|null,
     *     business_id?: string|null,
     *     landing_page_id?: int|null,
     *     landing_page_title?: string|null,
     *     assigned_to?: int|null,
     *     utm_source?: string|null,
     *     utm_medium?: string|null,
     *     utm_campaign?: string|null,
     *     utm_content?: string|null,
     *     utm_term?: string|null,
     * } $data
     */
    public function execute(array $data): Lead
    {
        $businessId = $data['business_id'] ?? null;

        // ── Client: firstOrCreate scoped per business (multi-tenant safe) ──────────
        $client = $this->findOrCreateClient($data, $businessId);

        // ── Contact: create from name fields if provided ──────────────────────────
        $contact = $this->findOrCreateContact($data, $client);

        // ── Pipeline stage: first stage for the business (order=1, "New Lead") ────
        $stage = PipelineStage::orderBy('order')->first();

        // Auto-create a default stage when none exist (guards EC-07: empty pipeline)
        if (! $stage) {
            $stage = PipelineStage::create([
                'name'     => 'New Lead',
                'slug'     => 'new-lead',
                'color'    => '#6B7280',
                'order'    => 1,
                'is_won'   => false,
                'is_lost'  => false,
            ]);
        }

        // ── Lead title ────────────────────────────────────────────────────────────
        $projectType = $data['project_type'] ?? 'Enquiry';
        $nameOrEmail = $data['company'] ?? $data['name'] ?? $data['first_name'] ?? $data['email'];
        $sourceLabel = $data['source'] !== 'contact_form' ? ' (' . $data['source'] . ')' : '';

        if (! empty($data['landing_page_title'])) {
            $title = trim("{$nameOrEmail} — {$data['landing_page_title']}{$sourceLabel}");
        } else {
            $title = trim("{$nameOrEmail} — {$projectType}{$sourceLabel}");
        }

        // ── Assigned-to priority: explicit → LP default_assignee ─────────────────
        $assignedTo = $data['assigned_to'] ?? $data['lp_default_assignee_id'] ?? null;

        // ── Lead creation ─────────────────────────────────────────────────────────
        $lead = Lead::create([
            'title'             => $title,
            'client_id'         => $client->id,
            'contact_id'        => $contact?->id,
            'pipeline_stage_id' => $stage->id,
            'assigned_to'       => $assignedTo,
            'source'            => $data['source'],
            'notes'             => $data['notes'] ?? null,
            'value'             => $data['value'] ?? null,
            'calculator_data'   => $data['calculator_data'] ?? null,
            'form_data'         => $data['form_data'] ?? null,
            'budget_min'        => $data['budget_min'] ?? null,
            'budget_max'        => $data['budget_max'] ?? null,
            'business_id'       => $businessId,
            'landing_page_id'   => $data['landing_page_id'] ?? null,
            'utm_source'        => $data['utm_source'] ?? null,
            'utm_medium'        => $data['utm_medium'] ?? null,
            'utm_campaign'      => $data['utm_campaign'] ?? null,
            'utm_content'       => $data['utm_content'] ?? null,
            'utm_term'          => $data['utm_term'] ?? null,
        ]);

        // ── Activity log ──────────────────────────────────────────────────────────
        LeadActivity::log($lead->id, 'created', 'Lead created via ' . $data['source'], [
            'name'            => $nameOrEmail,
            'email'           => $data['email'],
            'project_type'    => $projectType,
            'source'          => $data['source'],
            'contact_created' => $contact !== null,
            'client_existing' => $client->wasRecentlyCreated === false,
            'landing_page_id' => $data['landing_page_id'] ?? null,
        ], null);

        return $lead;
    }

    // ── Private helpers ───────────────────────────────────────────────────────────

    /**
     * Find or create Client scoped to business_id (prevents cross-tenant collisions).
     */
    private function findOrCreateClient(array $data, ?string $businessId): Client
    {
        $query = Client::where('primary_contact_email', $data['email']);

        if ($businessId) {
            $query->where('business_id', $businessId);
        }

        $existing = $query->first();

        if ($existing) {
            return $existing;
        }

        return Client::create([
            'business_id'           => $businessId,
            'company_name'          => $data['company'] ?? $data['name'] ?? $data['first_name'] ?? $data['email'],
            'primary_contact_name'  => $this->buildDisplayName($data),
            'primary_contact_email' => $data['email'],
            'primary_contact_phone' => $data['phone'] ?? null,
            'vat_number'            => $data['nip'] ?? null,
            'status'                => 'prospect',
            'source'                => 'website',
            'country'               => 'GB',
            'currency'              => 'GBP',
        ]);
    }

    /**
     * Create a Contact record linked to the Client (if name data is available).
     * Skips creation when Contact with this email already exists for the Client.
     */
    private function findOrCreateContact(array $data, Client $client): ?Contact
    {
        [$firstName, $lastName] = $this->splitName($data);

        if (! $firstName) {
            return null;
        }

        $existing = Contact::where('client_id', $client->id)
            ->where('email', $data['email'])
            ->first();

        if ($existing) {
            return $existing;
        }

        return Contact::create([
            'client_id'  => $client->id,
            'first_name' => $firstName,
            'last_name'  => $lastName ?? '',
            'email'      => $data['email'],
            'phone'      => $data['phone'] ?? null,
            'is_primary' => true,
        ]);
    }

    /**
     * Split name data into [first_name, last_name] pair.
     * Priority: explicit first_name/last_name → split from name → email fallback.
     */
    private function splitName(array $data): array
    {
        if (! empty($data['first_name'])) {
            return [$data['first_name'], $data['last_name'] ?? null];
        }

        if (! empty($data['name'])) {
            $parts = explode(' ', trim($data['name']), 2);
            return [
                $parts[0],
                $parts[1] ?? null,
            ];
        }

        if (! empty($data['company'])) {
            return [$data['company'], null];
        }

        return [null, null];
    }

    /**
     * Build a human-readable display name for the Client record.
     */
    private function buildDisplayName(array $data): string
    {
        if (! empty($data['first_name'])) {
            return trim($data['first_name'] . ' ' . ($data['last_name'] ?? ''));
        }

        return $data['name'] ?? $data['company'] ?? $data['email'];
    }
}

