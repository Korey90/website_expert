<?php

namespace App\Actions;

use App\Mail\NewLeadMail;
use App\Models\Client;
use App\Models\Lead;
use App\Models\LeadActivity;
use App\Models\PipelineStage;
use Illuminate\Support\Facades\Mail;

class CreateLeadAction
{
    /**
     * Create a lead from any inbound source (contact form, calculator, API, etc.)
     *
     * @param  array{
     *     email: string,
     *     name?: string|null,
     *     company?: string|null,
     *     phone?: string|null,
     *     nip?: string|null,
     *     project_type?: string|null,
     *     source: string,
     *     notes?: string|null,
     *     value?: float|null,
     *     calculator_data?: array|null,
     * } $data
     */
    public function execute(array $data): Lead
    {
        $client = Client::firstOrCreate(
            ['primary_contact_email' => $data['email']],
            [
                'company_name'          => $data['company'] ?? $data['name'] ?? $data['email'],
                'primary_contact_name'  => $data['name'] ?? $data['company'] ?? $data['email'],
                'primary_contact_phone' => $data['phone'] ?? null,
                'vat_number'            => $data['nip'] ?? null,
                'status'                => 'prospect',
                'source'                => 'website',
                'country'               => 'GB',
                'currency'              => 'GBP',
            ]
        );

        $stage = PipelineStage::orderBy('order')->first();

        $projectType = $data['project_type'] ?? 'Enquiry';
        $title = trim(
            ($data['company'] ?? $data['name'] ?? $data['email']) . ' — ' . $projectType
            . ($data['source'] !== 'contact_form' ? ' (' . $data['source'] . ')' : '')
        );

        $lead = Lead::create([
            'title'             => $title,
            'client_id'         => $client->id,
            'pipeline_stage_id' => $stage?->id,
            'source'            => $data['source'],
            'notes'             => $data['notes'] ?? null,
            'value'             => $data['value'] ?? null,
            'calculator_data'   => $data['calculator_data'] ?? null,
        ]);

        LeadActivity::log($lead->id, 'created', 'Lead created via ' . $data['source'], [
            'name'         => $data['name'] ?? $data['company'] ?? null,
            'email'        => $data['email'],
            'project_type' => $projectType,
            'source'       => $data['source'],
        ], null);

        $adminEmail = config('mail.admin_address', 'admin@websiteexpert.co.uk');
        Mail::to($adminEmail)->queue(new NewLeadMail(
            array_merge($data, [
                'name'    => $data['name'] ?? $data['company'] ?? $data['email'],
                'email'   => $data['email'],
                'message' => $data['notes'] ?? '',
            ]),
            $lead->id,
        ));

        return $lead;
    }
}
