<?php

namespace App\Http\Controllers;

use App\Services\Leads\LeadService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CalculatorLeadController extends Controller
{
    public function store(Request $request, LeadService $leadService): JsonResponse
    {
        $data = $request->validate([
            'contactEmail'   => ['required', 'email:rfc', 'max:255'],
            'companyName'    => ['nullable', 'string', 'max:255'],
            'projectType'    => ['nullable', 'string', 'max:100'],
            'pages'          => ['nullable', 'integer', 'min:1', 'max:500'],
            'design'         => ['nullable', 'string', 'max:100'],
            'cms'            => ['nullable', 'string', 'max:100'],
            'integrations'   => ['nullable', 'array'],
            'integrations.*' => ['string', 'max:100'],
            'seoPackage'     => ['nullable', 'string', 'max:100'],
            'deadline'       => ['nullable', 'string', 'max:100'],
            'hosting'        => ['nullable', 'string', 'max:100'],
            'estimateLow'    => ['nullable', 'numeric'],
            'estimateHigh'   => ['nullable', 'numeric'],
        ]);

        $estimateLow  = $data['estimateLow'] ?? null;
        $estimateHigh = $data['estimateHigh'] ?? $estimateLow;
        $projectType  = $data['projectType'] ?? 'enquiry';

        $lead = $leadService->createFromSource(
            leadData: [
                'email'           => $data['contactEmail'],
                'company'         => $data['companyName'] ?? null,
                'project_type'    => $projectType,
                'source'          => 'calculator',
                'notes'           => sprintf('Enquiry via cost calculator. Estimate: %s-%s', $estimateLow, $estimateHigh),
                'value'           => ($estimateLow !== null && $estimateHigh !== null)
                                        ? round(($estimateLow + $estimateHigh) / 2, 2)
                                        : $estimateLow,
                'calculator_data' => $data,
            ],
            sourceData: [
                'type'         => 'calculator',
                'referrer_url' => $request->header('Referer'),
                'page_url'     => $request->header('Origin'),
                'ip_address'   => $request->ip(),
                'user_agent'   => $request->userAgent(),
                'utm_source'   => $request->query('utm_source'),
                'utm_medium'   => $request->query('utm_medium'),
                'utm_campaign' => $request->query('utm_campaign'),
            ],
            consentData: [
                'given'        => false,
                'consent_text' => null,
                'source_url'   => $request->header('Referer'),
                'ip_address'   => $request->ip(),
                'locale'       => app()->getLocale(),
            ],
            business: currentBusiness(),
        );

        return response()->json(['message' => 'ok', 'lead_id' => $lead->id], 201);
    }
}
