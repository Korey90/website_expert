<?php

namespace App\Services\LandingPage;

use App\Actions\CreateLeadAction;
use App\Events\LeadCaptured;
use App\Models\LandingPage;
use App\Models\Lead;
use DomainException;
use Illuminate\Http\Request;

class LeadCaptureService
{
    public function __construct(
        private readonly CreateLeadAction $createLeadAction,
    ) {}

    /**
     * Capture a lead submission from a public landing page form.
     *
     * @throws DomainException when the page is not published.
     */
    public function capture(LandingPage $page, array $formData, Request $request): Lead
    {
        if ($page->status !== LandingPage::STATUS_PUBLISHED) {
            throw new DomainException(__('landing_pages.errors.page_not_published'));
        }

        $lead = $this->createLeadAction->execute([
            'email'           => $formData['email'],
            'name'            => $formData['name'] ?? null,
            'company'         => $formData['company'] ?? null,
            'phone'           => $formData['phone'] ?? null,
            'notes'           => $formData['message'] ?? null,
            'source'          => 'landing_page',
            'landing_page_id' => $page->id,
            'utm_source'      => $request->query('utm_source'),
            'utm_medium'      => $request->query('utm_medium'),
            'utm_campaign'    => $request->query('utm_campaign'),
            'utm_content'     => $request->query('utm_content'),
            'utm_term'        => $request->query('utm_term'),
        ]);

        $page->increment('conversions_count');

        LeadCaptured::dispatch($lead, $page);

        return $lead;
    }

    /**
     * Increment the page's view counter atomically.
     */
    public function trackView(LandingPage $page): void
    {
        $page->increment('views_count');
    }
}
