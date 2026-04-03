<?php

namespace App\Http\Controllers\LandingPage;

use App\Http\Controllers\Controller;
use App\Http\Requests\LandingPage\LeadCaptureRequest;
use App\Services\Leads\PublicLeadCaptureService;
use App\Services\LandingPage\PublicLandingPageService;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Inertia\Inertia;
use Inertia\Response;

class PublicLandingPageController extends Controller
{
    public function __construct(
        private readonly PublicLeadCaptureService $publicLeadCaptureService,
        private readonly PublicLandingPageService $publicLandingPageService,
    ) {}

    /**
     * Render the public landing page.
     */
    public function show(string $slug): Response|\Symfony\Component\HttpFoundation\Response
    {
        $page = $this->publicLandingPageService->resolvePublishedBySlug($slug);

        $page->increment('views_count');

        return Inertia::render('LandingPage/Show', [
            'landingPage' => $page->append(['conversion_rate']),
            'sections'    => $page->sections,
        ]);
    }

    /**
     * Handle lead form submission from a public landing page.
     *
     * Rate-limited at route level: throttle:3,60
     */
    public function submit(LeadCaptureRequest $request, string $slug): JsonResponse
    {
        try {
            $capture = $this->publicLeadCaptureService->captureBySlug(
                $slug,
                $request->validated(),
                [
                    'consent'      => $request->boolean('consent', false),
                    'ip_address'   => $request->ip(),
                    'user_agent'   => $request->userAgent() ?? '',
                    'referrer_url' => $request->header('Referer'),
                    'page_url'     => $request->header('Referer') ?? $request->url(),
                    'utm_source'   => $request->query('utm_source'),
                    'utm_medium'   => $request->query('utm_medium'),
                    'utm_campaign' => $request->query('utm_campaign'),
                    'utm_content'  => $request->query('utm_content'),
                    'utm_term'     => $request->query('utm_term'),
                    'source_url'   => $request->url(),
                    'locale'       => app()->getLocale(),
                ],
            );
        } catch (ModelNotFoundException) {
            return response()->json([
                'success' => false,
                'message' => __('landing_pages.errors.page_not_published'),
            ], 404);
        } catch (\Throwable $e) {
            report($e);
            return response()->json([
                'success' => false,
                'message' => __('landing_pages.errors.submission_failed'),
            ], 500);
        }

        return response()->json([
            'success'      => true,
            'status'       => $capture['status'],
            'lead_id'      => $capture['lead_id'],
            'message'      => $capture['message'],
            'redirect_url' => $capture['redirect_url'],
        ]);
    }
}
