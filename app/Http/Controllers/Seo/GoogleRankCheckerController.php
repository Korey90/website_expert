<?php

namespace App\Http\Controllers\Seo;

use App\Actions\Seo\CheckGoogleRankingAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Seo\CheckGoogleRankingRequest;
use App\Models\SiteSection;
use App\Services\RecaptchaService;
use Illuminate\Http\JsonResponse;
use Inertia\Inertia;
use Inertia\Response;

class GoogleRankCheckerController extends Controller
{
    public function __construct(
        private readonly CheckGoogleRankingAction $checkRanking,
        private readonly RecaptchaService $recaptcha,
    ) {}

    public function show(): Response
    {
        $footer = ($s = SiteSection::where('key', 'footer')->where('is_active', true)->first())
            ? ['extra' => $s->extra]
            : null;

        return Inertia::render('Tools/GoogleRankChecker', [
            'footer' => $footer,
            'locations' => array_column(config('seo.rank_checker.locations', []), 'name'),
            'defaults' => [
                'location' => config('seo.rank_checker.default_location'),
                'device' => config('seo.rank_checker.default_device'),
            ],
        ]);
    }

    /**
     * Public JSON endpoint. Rate-limited at route level: throttle:10,60
     */
    public function check(CheckGoogleRankingRequest $request): JsonResponse
    {
        if (! $this->recaptcha->verify($request->input('recaptcha_token'), 'google_rank_checker')) {
            return response()->json([
                'status' => 'failed',
                'message' => __('rank_checker.errors.recaptcha_failed'),
            ], 422);
        }

        $validated = $request->validated();

        $result = $this->checkRanking->execute(
            keyword: $validated['keyword'],
            rawDomain: $validated['website'],
            location: $validated['location'],
            device: $validated['device'],
            language: $validated['language'] ?? 'en',
            businessId: null,
            ipHash: hash('sha256', $request->ip() ?? ''),
        );

        return response()->json($result->toArray());
    }
}
