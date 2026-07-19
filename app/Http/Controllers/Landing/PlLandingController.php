<?php

namespace App\Http\Controllers\Landing;

use App\Http\Controllers\Controller;
use App\Http\Requests\Landing\StorePlLandingRequest;
use App\Mail\LeadEnquiryConfirmationMail;
use App\Jobs\ScheduleLandingLeadFollowUpJob;
use App\Models\Business;
use App\Models\SiteSection;
use App\Services\Leads\LeadService;
use App\Services\Leads\LeadConsentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Mail;
use Inertia\Inertia;
use Inertia\Response;

class PlLandingController extends Controller
{
    public function __construct(
        private readonly LeadService        $leadService,
        private readonly LeadConsentService $consentService,
    ) {}

    public function show(): Response
    {
        App::setLocale('pl');

        $footer = ($s = SiteSection::where('key', 'footer')->where('is_active', true)->first())
            ? ['extra' => $s->extra]
            : null;

        return Inertia::render('Landing/PlStronyInternetowe', [
            'footer' => $footer,
        ]);
    }

    /**
     * Handle Polish LP lead form submission.
     * Rate-limited at route level: throttle:5,60
     */
    public function submit(StorePlLandingRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $business  = Business::first(); // single-tenant: one business

        $sourceData = [
            'type'              => 'landing_page',
            'ip_address'        => $request->ip(),
            'user_agent'        => $request->userAgent() ?? '',
            'referrer_url'      => $request->header('Referer'),
            'page_url'          => $request->header('Referer') ?? $request->url(),
            'utm_source'        => $request->query('utm_source') ?? $request->input('utm_source'),
            'utm_medium'        => $request->query('utm_medium') ?? $request->input('utm_medium'),
            'utm_campaign'      => $request->query('utm_campaign') ?? $request->input('utm_campaign'),
            'utm_content'       => $request->query('utm_content') ?? $request->input('utm_content'),
            'utm_term'          => $request->query('utm_term') ?? $request->input('utm_term'),
            'gclid'             => $request->query('gclid') ?? $request->input('gclid'),
            'gbraid'            => $request->query('gbraid') ?? $request->input('gbraid'),
            'wbraid'            => $request->query('wbraid') ?? $request->input('wbraid'),
            'device_type'       => $this->detectDevice($request),
        ];

        $consentData = [
            'given'           => true,
            'consent_text'    => $this->consentService->getConsentTextForLocale('pl'),
            'consent_version' => config('leads.consent_version', '1.0'),
            'source_url'      => '/pl/strony-internetowe-uk',
            'ip_address'      => $request->ip(),
            'locale'          => 'pl',
        ];

        $leadData = array_merge($validated, [
            'source'            => 'google_ads',
            'source_type'       => 'landing_page',
            'landing_page_slug' => 'strony-internetowe-uk',
            'locale'            => 'pl',
            'audience'          => 'polish_business_uk',
            'notes'             => $validated['message'],
            'form_data'         => array_filter([
                'name'             => $validated['name'],
                'email'            => $validated['email'],
                'phone'            => $validated['phone'] ?? null,
                'company'          => $validated['company'] ?? null,
                'current_website'  => $validated['current_website'] ?? null,
                'message'          => $validated['message'],
                'audience_type'    => $validated['audience_type'] ?? null,
                'landing_page'     => 'strony-internetowe-uk',
                'locale'           => 'pl',
            ], fn ($v) => $v !== null && $v !== ''),
            'project_type'      => 'Website',
            // Pass tracking fields through to CreateLeadAction
            'gclid'             => $sourceData['gclid'],
            'gbraid'            => $sourceData['gbraid'],
            'wbraid'            => $sourceData['wbraid'],
            'device_type'       => $sourceData['device_type'],
            'utm_source'        => $sourceData['utm_source'],
            'utm_medium'        => $sourceData['utm_medium'],
            'utm_campaign'      => $sourceData['utm_campaign'],
            'utm_content'       => $sourceData['utm_content'],
            'utm_term'          => $sourceData['utm_term'],
        ]);

        $lead = $this->leadService->createFromSource(
            $leadData,
            $sourceData,
            $consentData,
            $business,
        );

        // Send confirmation email to the prospect (queued)
        $clientEmail = $lead->client?->primary_contact_email;
        if ($clientEmail) {
            Mail::to($clientEmail)->queue(
                new LeadEnquiryConfirmationMail($lead->load('contact'), 'pl')
            );
        }

        // Schedule 24h + 72h follow-up tasks
        ScheduleLandingLeadFollowUpJob::dispatch($lead->id, 'pl')
            ->onQueue('default');

        return response()->json([
            'status'  => 'created',
            'lead_id' => $lead->id,
        ]);
    }

    private function detectDevice(Request $request): string
    {
        $ua = strtolower($request->userAgent() ?? '');
        if (str_contains($ua, 'mobi') || str_contains($ua, 'android') || str_contains($ua, 'iphone')) {
            return 'mobile';
        }
        if (str_contains($ua, 'tablet') || str_contains($ua, 'ipad')) {
            return 'tablet';
        }
        return 'desktop';
    }
}
