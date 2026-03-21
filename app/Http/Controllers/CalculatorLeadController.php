<?php

namespace App\Http\Controllers;

use App\Mail\NewLeadMail;
use App\Models\Client;
use App\Models\Lead;
use App\Models\LeadActivity;
use App\Models\PipelineStage;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;

class CalculatorLeadController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'contactEmail'  => ['required', 'email:rfc', 'max:255'],
            'companyName'   => ['nullable', 'string', 'max:255'],
            'projectType'   => ['nullable', 'string', 'max:100'],
            'pages'         => ['nullable', 'integer', 'min:1', 'max:500'],
            'design'        => ['nullable', 'string', 'max:100'],
            'cms'           => ['nullable', 'string', 'max:100'],
            'integrations'  => ['nullable', 'array'],
            'integrations.*'=> ['string', 'max:100'],
            'seoPackage'    => ['nullable', 'string', 'max:100'],
            'deadline'      => ['nullable', 'string', 'max:100'],
            'hosting'       => ['nullable', 'string', 'max:100'],
            'estimateLow'   => ['nullable', 'numeric'],
            'estimateHigh'  => ['nullable', 'numeric'],
        ]);

        $client = Client::firstOrCreate(
            ['primary_contact_email' => $data['contactEmail']],
            [
                'company_name'          => $data['companyName'] ?? $data['contactEmail'],
                'primary_contact_name'  => $data['companyName'] ?? $data['contactEmail'],
                'status'                => 'prospect',
                'source'                => 'website',
                'country'               => 'GB',
                'currency'              => 'GBP',
            ]
        );

        $stage = PipelineStage::orderBy('order')->first();

        $projectType = $data['projectType'] ?? 'enquiry';
        $title = trim(($data['companyName'] ?? $data['contactEmail']) . ' — ' . $projectType . ' (calculator)');

        $lead = Lead::create([
            'title'             => $title,
            'client_id'         => $client->id,
            'pipeline_stage_id' => $stage?->id,
            'source'            => 'calculator',
            'notes'             => "Enquiry via cost calculator. Estimate: £{$data['estimateLow']}–£{$data['estimateHigh']}",
            'value'             => isset($data['estimateLow']) ? round(($data['estimateLow'] + ($data['estimateHigh'] ?? $data['estimateLow'])) / 2, 2) : null,
            'calculator_data'   => $data,
        ]);

        LeadActivity::log($lead->id, 'created', 'Lead created via cost calculator', [
            'email'        => $data['contactEmail'],
            'project_type' => $projectType,
            'estimate_low' => $data['estimateLow'] ?? null,
            'estimate_high'=> $data['estimateHigh'] ?? null,
            'source'       => 'calculator',
        ], null);

        $adminEmail = config('mail.admin_address', 'admin@websiteexpert.co.uk');
        Mail::to($adminEmail)->queue(new NewLeadMail(
            array_merge($data, ['name' => $data['companyName'] ?? $data['contactEmail'], 'email' => $data['contactEmail'], 'message' => "Via calculator. Type: {$projectType}"]),
            $lead->id,
        ));

        return response()->json(['message' => 'ok', 'lead_id' => $lead->id], 201);
    }
}
