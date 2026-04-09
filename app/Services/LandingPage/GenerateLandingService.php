<?php

namespace App\Services\LandingPage;

use App\Data\LandingPage\GenerateLandingData;
use App\Data\LandingPage\RegenerateLandingSectionData;
use App\Data\LandingPage\SaveGeneratedLandingData;
use App\Exceptions\LandingPageGenerationException;
use App\Models\Business;
use App\Models\LandingPage;
use App\Models\LandingPageAiGeneration;
use App\Models\LandingPageGenerationVariant;
use App\Models\User;
use App\Services\Business\BusinessProfileService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class GenerateLandingService
{
    public function __construct(
        private readonly BusinessProfileService $businessProfileService,
        private readonly LandingPageService $landingPageService,
        private readonly OpenAiLandingPromptBuilder $promptBuilder,
        private readonly OpenAiLandingClient $openAiClient,
        private readonly LandingPageJsonNormalizer $normalizer,
        private readonly LandingPageJsonSchemaValidator $validator,
    ) {}

    public function generate(Business $business, User $user, GenerateLandingData $data): LandingPageGenerationVariant
    {
        $context = $this->buildContext($business);

        $generation = LandingPageAiGeneration::create([
            'business_id' => $business->id,
            'user_id' => $user->id,
            'status' => LandingPageAiGeneration::STATUS_PENDING,
            'source' => 'business_profile_prompt',
            'input_payload' => [
                'context' => $context,
                'request' => $data->toArray(),
            ],
        ]);

        $startedAt = microtime(true);
        $normalized = null;

        try {
            $result = $this->openAiClient->generateStructuredLanding([
                'system_prompt' => $this->promptBuilder->buildSystemPrompt($context),
                'user_prompt' => $this->promptBuilder->buildUserPrompt($data->toArray()),
            ]);

            $normalized = $this->normalizer->normalize($result['content']);
            $this->validator->validateDraft($normalized);

            $variant = DB::transaction(function () use ($business, $generation, $normalized, $user) {
                return LandingPageGenerationVariant::create([
                    'business_id' => $business->id,
                    'generation_id' => $generation->id,
                    'user_id' => $user->id,
                    'title' => $normalized['title'],
                    'slug_suggestion' => $normalized['slug_suggestion'],
                    'language' => $normalized['language'],
                    'template_key' => $normalized['template_key'],
                    'meta' => $normalized['meta'],
                    'sections' => $normalized['sections'],
                    'expires_at' => now()->addDays(7),
                ]);
            });

            $this->markGenerationSuccess($generation, $result, $normalized, $startedAt);

            return $variant->refresh();
        } catch (LandingPageGenerationException $exception) {
            $this->markGenerationFailed($generation, $exception, $startedAt, $normalized);
            throw $exception;
        } catch (\Throwable $exception) {
            report($exception);
            $wrapped = new LandingPageGenerationException(
                __('landing_pages.ai.errors.generation_failed'),
                'generation_failed',
                500,
                $exception,
            );
            $this->markGenerationFailed($generation, $wrapped, $startedAt, $normalized);
            throw $wrapped;
        }
    }

    public function regenerateSection(
        LandingPageGenerationVariant $variant,
        Business $business,
        User $user,
        RegenerateLandingSectionData $data,
    ): LandingPageGenerationVariant {
        $this->assertVariantOwnership($variant, $business);
        $this->assertVariantIsEditable($variant);

        $context = $this->buildContext($business);

        $generation = LandingPageAiGeneration::create([
            'business_id' => $business->id,
            'user_id' => $user->id,
            'status' => LandingPageAiGeneration::STATUS_PENDING,
            'source' => 'regenerate_section',
            'input_payload' => [
                'context' => $context,
                'request' => $data->toArray(),
                'variant_id' => $variant->id,
                'sections' => $variant->sections,
            ],
        ]);

        $startedAt = microtime(true);

        try {
            $result = $this->openAiClient->regenerateSection([
                'system_prompt' => $this->promptBuilder->buildSystemPrompt($context),
                'user_prompt' => $this->promptBuilder->buildSectionRegenerationPrompt(
                    $context,
                    $data->sectionType,
                    $variant->sections,
                    $data->toArray(),
                ),
            ]);

            $sectionPayload = $result['content']['section'] ?? $result['content'];
            $normalizedSection = $this->normalizer->normalizeSection($sectionPayload);
            $this->validator->validateSection(
                $normalizedSection['type'],
                $normalizedSection['content'],
                $normalizedSection['settings'],
            );

            $sections = $this->replaceSection($variant->sections ?? [], $normalizedSection);

            $variant->update([
                'sections' => $sections,
            ]);

            $this->markGenerationSuccess($generation, $result, ['section' => $normalizedSection], $startedAt);

            return $variant->refresh();
        } catch (LandingPageGenerationException $exception) {
            $this->markGenerationFailed($generation, $exception, $startedAt);
            throw $exception;
        } catch (\Throwable $exception) {
            report($exception);
            $wrapped = new LandingPageGenerationException(
                __('landing_pages.ai.errors.section_regeneration_failed'),
                'section_regeneration_failed',
                500,
                $exception,
            );
            $this->markGenerationFailed($generation, $wrapped, $startedAt);
            throw $wrapped;
        }
    }

    public function saveAsLandingPage(
        LandingPageGenerationVariant $variant,
        Business $business,
        User $user,
        SaveGeneratedLandingData $data,
    ): LandingPage {
        $this->assertVariantOwnership($variant, $business);
        $this->assertVariantIsEditable($variant);

        $payload = [
            'title' => $data->title ?? $variant->title,
            'slug_suggestion' => $data->slug ?? $variant->slug_suggestion,
            'language' => $data->language ?? $variant->language,
            'template_key' => $data->templateKey ?? $variant->template_key,
            'meta' => array_merge($variant->meta ?? [], $data->meta ?? []),
            'sections' => $data->sections ?? $variant->sections,
        ];

        $this->validator->validateDraft($payload);

        $landingPage = $this->landingPageService->createFromGeneratedVariant(
            $business,
            $variant,
            $payload,
        );

        DB::transaction(function () use ($variant, $landingPage) {
            $variant->update(['is_saved' => true]);
            $variant->generation()->update(['landing_page_id' => $landingPage->id]);
        });

        Log::info('Landing page saved from AI variant.', [
            'variant_id' => $variant->id,
            'landing_page_id' => $landingPage->id,
        ]);

        return $landingPage;
    }

    private function buildContext(Business $business): array
    {
        $profile = $this->businessProfileService->getOrCreate($business);

        return [
            'business' => [
                'id' => $business->id,
                'name' => $business->name,
                'locale' => $business->locale,
            ],
            'profile' => $this->businessProfileService->getAiContext($business),
            'profile_completion' => $this->businessProfileService->completion($profile),
            'website_url' => $profile->website_url,
            'seo_keywords' => $profile->seo_keywords ?? [],
        ];
    }

    private function assertVariantOwnership(LandingPageGenerationVariant $variant, Business $business): void
    {
        if ($variant->business_id !== $business->id) {
            throw new LandingPageGenerationException(
                __('landing_pages.ai.errors.variant_not_found'),
                'variant_not_found',
                404,
            );
        }
    }

    private function assertVariantIsEditable(LandingPageGenerationVariant $variant): void
    {
        if ($variant->is_saved) {
            throw new LandingPageGenerationException(
                __('landing_pages.ai.errors.variant_already_saved'),
                'variant_already_saved',
                422,
            );
        }

        if ($variant->expires_at && $variant->expires_at->isPast()) {
            throw new LandingPageGenerationException(
                __('landing_pages.ai.errors.variant_expired'),
                'variant_expired',
                410,
            );
        }
    }

    /**
     * @param  list<array<string, mixed>>  $sections
     * @param  array<string, mixed>  $replacement
     * @return list<array<string, mixed>>
     */
    private function replaceSection(array $sections, array $replacement): array
    {
        $replaced = false;

        foreach ($sections as $index => $section) {
            if (($section['type'] ?? null) === $replacement['type']) {
                $sections[$index] = $replacement;
                $replaced = true;
                break;
            }
        }

        if (! $replaced) {
            $sections[] = $replacement;
        }

        return array_values($sections);
    }

    /**
     * @param  array<string, mixed>  $result
     * @param  array<string, mixed>  $normalized
     */
    private function markGenerationSuccess(
        LandingPageAiGeneration $generation,
        array $result,
        array $normalized,
        float $startedAt,
    ): void {
        $generation->update([
            'status' => LandingPageAiGeneration::STATUS_SUCCEEDED,
            'model' => $result['model'] ?? config('services.openai.model'),
            'normalized_payload' => $normalized,
            'tokens_input' => $result['tokens_input'] ?? null,
            'tokens_output' => $result['tokens_output'] ?? null,
            'duration_ms' => (int) round((microtime(true) - $startedAt) * 1000),
            'error_code' => null,
            'error_message' => null,
        ]);
    }

    private function markGenerationFailed(
        LandingPageAiGeneration $generation,
        LandingPageGenerationException $exception,
        float $startedAt,
        ?array $normalized = null,
    ): void {
        $generation->update([
            'status' => LandingPageAiGeneration::STATUS_FAILED,
            'error_code' => $exception->errorCode(),
            'error_message' => $exception->getMessage(),
            'duration_ms' => (int) round((microtime(true) - $startedAt) * 1000),
            'normalized_payload' => $normalized,
        ]);
    }
}