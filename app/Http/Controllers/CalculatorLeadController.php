<?php

namespace App\Http\Controllers;

use App\Actions\CreateLeadAction;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CalculatorLeadController extends Controller
{
    public function store(Request $request, CreateLeadAction $action): JsonResponse
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

        $lead = $action->execute([
            'email'           => $data['contactEmail'],
            'company'         => $data['companyName'] ?? null,
            'project_type'    => $projectType,
            'source'          => 'calculator',
            'notes'           => sprintf('Enquiry via cost calculator. Estimate: %s-%s', $estimateLow, $estimateHigh),
            'value'           => ($estimateLow !== null && $estimateHigh !== null)
                                    ? round(($estimateLow + $estimateHigh) / 2, 2)
                                    : $estimateLow,
            'calculator_data' => $data,
        ]);

        return response()->json(['message' => 'ok', 'lead_id' => $lead->id], 201);
    }
}
