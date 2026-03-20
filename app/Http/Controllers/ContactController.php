<?php

namespace App\Http\Controllers;

use App\Http\Requests\ContactRequest;
use App\Mail\NewLeadMail;
use App\Models\Client;
use App\Models\Lead;
use App\Models\PipelineStage;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Mail;

class ContactController extends Controller
{
    public function store(ContactRequest $request): JsonResponse
    {
        $data = $request->validated();

        // Find or create a Client record keyed by email
        $client = Client::firstOrCreate(
            ['primary_contact_email' => $data['email']],
            [
                'company_name'          => $data['company'] ?? $data['name'],
                'primary_contact_name'  => $data['name'],
                'primary_contact_phone' => $data['phone'] ?? null,
                'vat_number'            => $data['nip'] ?? null,
                'status'                => 'lead',
                'source'                => 'contact_form',
                'country'               => 'GB',
                'currency'              => 'GBP',
            ]
        );

        // First pipeline stage (new-lead)
        $stage = PipelineStage::orderBy('order')->first();

        $title = trim(
            ($data['name'] ?? 'Unknown') . ' — ' . ($data['project_type'] ?? 'Enquiry')
        );

        $lead = Lead::create([
            'title'              => $title,
            'client_id'          => $client->id,
            'pipeline_stage_id'  => $stage?->id,
            'source'             => 'contact_form',
            'notes'              => $data['message'],
            'calculator_data'    => $data,
        ]);

        // Notify admin
        $adminEmail = config('mail.admin_address', 'admin@websiteexpert.co.uk');
        Mail::to($adminEmail)->queue(new NewLeadMail($data, $lead->id));

        return response()->json(['message' => 'ok'], 201);
    }
}
