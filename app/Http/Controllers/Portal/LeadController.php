<?php

namespace App\Http\Controllers\Portal;

use App\Models\Lead;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class LeadController extends BasePortalController
{
    public function show(Lead $lead): Response|RedirectResponse
    {
        $client   = $this->clientForUser();
        $business = currentBusiness();

        if (! $business) {
            return $this->redirectWithoutWorkspace('Workspace access is required to view captured leads.');
        }

        if ($lead->business_id !== $business->id) {
            abort(403);
        }

        $lead->load([
            'client:id,company_name,primary_contact_name,primary_contact_email,primary_contact_phone',
            'landingPage:id,title,slug',
            'activities' => fn ($q) => $q->orderByDesc('created_at')->limit(20),
        ]);

        $utmParams = collect([
            'utm_source'   => $lead->utm_source,
            'utm_medium'   => $lead->utm_medium,
            'utm_campaign' => $lead->utm_campaign,
            'utm_content'  => $lead->utm_content,
            'utm_term'     => $lead->utm_term,
        ])->filter()->all();

        return Inertia::render('Portal/Leads/Show', [
            'client' => $client?->only('id', 'company_name'),
            'lead'   => [
                'id'           => $lead->id,
                'title'        => $lead->title,
                'source'       => $lead->source,
                'value'        => $lead->value,
                'currency'     => $lead->currency,
                'notes'        => $lead->notes,
                'form_data'    => $lead->form_data ?? [],
                'utm'          => $utmParams,
                'created_at'   => $lead->created_at?->format('d M Y H:i'),
                'landing_page' => $lead->landingPage
                    ? ['id' => $lead->landingPage->id, 'title' => $lead->landingPage->title, 'slug' => $lead->landingPage->slug]
                    : null,
                'activities'   => $lead->activities->map(fn ($a) => [
                    'id'         => $a->id,
                    'type'       => $a->type,
                    'notes'      => $a->notes,
                    'metadata'   => $a->metadata ?? [],
                    'created_at' => $a->created_at?->format('d M Y H:i'),
                ]),
                'contact' => [
                    'name'  => $lead->client?->primary_contact_name ?? $lead->client?->company_name,
                    'email' => $lead->client?->primary_contact_email,
                    'phone' => $lead->client?->primary_contact_phone,
                ],
            ],
        ]);
    }
}
