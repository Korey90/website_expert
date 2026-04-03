<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Lead\ApiLeadRequest;
use App\Services\Leads\LeadService;

class LeadCaptureController extends Controller
{
    public function __construct(private readonly LeadService $leadService) {}

    /**
     * POST /api/v1/leads
     * Capture a lead from an external source (Zapier, Make.com, custom forms).
     * Requires: Authorization: Bearer {api_token}
     */
    public function store(ApiLeadRequest $request)
    {
        /** @var \App\Models\Business $business */
        $business = $request->attributes->get('api_business');

        $notes = $request->message;
        if ($request->metadata) {
            $metaStr = collect($request->metadata)
                ->map(fn ($v, $k) => "{$k}: {$v}")
                ->implode(', ');
            $notes = $notes ? "{$notes}\n\nMetadata: {$metaStr}" : "Metadata: {$metaStr}";
        }

        $lead = $this->leadService->createFromSource(
            leadData: [
                'email'       => $request->email,
                'name'        => $request->name,
                'phone'       => $request->phone,
                'company'     => $request->company,
                'source'      => 'api',
                'notes'       => $notes,
                'business_id' => $business->id,
            ],
            sourceData: [
                'type'         => 'api',
                'page_url'     => $request->source_page,
                'ip_address'   => $request->ip(),
                'user_agent'   => $request->userAgent(),
                'utm_source'   => $request->utm_source,
                'utm_medium'   => $request->utm_medium,
                'utm_campaign' => $request->utm_campaign,
            ],
            consentData: [
                'given'           => true,
                'consent_text'    => 'Consent collected and managed by API caller',
                'consent_version' => config('leads.consent_version', '1.0'),
                'ip_address'      => $request->ip(),
                'locale'          => 'en',
            ],
            business: $business,
        );

        return response()->json([
            'success'  => true,
            'lead_id'  => $lead->id,
            'message'  => 'Lead captured successfully',
        ], 201);
    }
}
