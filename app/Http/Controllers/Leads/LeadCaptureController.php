<?php

namespace App\Http\Controllers\Leads;

use App\Http\Controllers\Controller;
use App\Http\Requests\Lead\StorePublicLeadRequest;
use App\Services\Leads\PublicLeadCaptureService;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;

class LeadCaptureController extends Controller
{
    public function __construct(
        private readonly PublicLeadCaptureService $publicLeadCaptureService,
    ) {}

    /**
     * Public endpoint used by landing page forms that submit to a generic path.
     */
    public function store(StorePublicLeadRequest $request): JsonResponse
    {
        try {
            $capture = $this->publicLeadCaptureService->captureBySlug(
                $request->string('landing_page_slug')->toString(),
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
                    'source_url'   => $request->header('Referer') ?? $request->url(),
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
        ], $capture['status'] === 'created' ? 201 : 200);
    }
}