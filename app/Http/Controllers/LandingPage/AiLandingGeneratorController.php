<?php

namespace App\Http\Controllers\LandingPage;

use App\Data\LandingPage\GenerateLandingData;
use App\Data\LandingPage\RegenerateLandingSectionData;
use App\Data\LandingPage\SaveGeneratedLandingData;
use App\Exceptions\LandingPageGenerationException;
use App\Http\Controllers\Controller;
use App\Http\Requests\LandingPage\GenerateLandingRequest;
use App\Http\Requests\LandingPage\RegenerateLandingSectionRequest;
use App\Http\Requests\LandingPage\SaveGeneratedLandingRequest;
use App\Models\LandingPageGenerationVariant;
use App\Services\LandingPage\GenerateLandingService;
use Illuminate\Http\JsonResponse;

class AiLandingGeneratorController extends Controller
{
    public function __construct(
        private readonly GenerateLandingService $generateLandingService,
    ) {}

    public function generate(GenerateLandingRequest $request): JsonResponse
    {
        try {
            $variant = $this->generateLandingService->generate(
                currentBusiness(),
                $request->user(),
                GenerateLandingData::fromArray($request->validated()),
            );
        } catch (LandingPageGenerationException $exception) {
            return $this->errorResponse($exception);
        }

        return response()->json([
            'success' => true,
            'message' => __('landing_pages.ai.messages.generated'),
            'variant' => $variant,
        ]);
    }

    public function regenerateSection(
        RegenerateLandingSectionRequest $request,
        LandingPageGenerationVariant $variant,
    ): JsonResponse {
        try {
            $variant = $this->generateLandingService->regenerateSection(
                $variant,
                currentBusiness(),
                $request->user(),
                RegenerateLandingSectionData::fromArray($request->validated()),
            );
        } catch (LandingPageGenerationException $exception) {
            return $this->errorResponse($exception);
        }

        return response()->json([
            'success' => true,
            'message' => __('landing_pages.ai.messages.section_regenerated'),
            'variant' => $variant,
        ]);
    }

    public function save(
        SaveGeneratedLandingRequest $request,
        LandingPageGenerationVariant $variant,
    ): JsonResponse {
        try {
            $landingPage = $this->generateLandingService->saveAsLandingPage(
                $variant,
                currentBusiness(),
                $request->user(),
                SaveGeneratedLandingData::fromArray($request->validated()),
            );
        } catch (LandingPageGenerationException $exception) {
            return $this->errorResponse($exception);
        }

        return response()->json([
            'success' => true,
            'message' => __('landing_pages.ai.messages.saved'),
            'landing_page' => $landingPage->append(['public_url', 'conversion_rate', 'is_published']),
            'redirect_url' => route('landing-pages.edit', $landingPage),
        ]);
    }

    private function errorResponse(LandingPageGenerationException $exception): JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => $exception->getMessage(),
            'error_code' => $exception->errorCode(),
        ], $exception->status());
    }
}