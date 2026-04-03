<?php

namespace App\Services\Business;

use App\Events\BusinessProfileUpdated;
use App\Models\Business;
use App\Models\BusinessProfile;

class BusinessProfileService
{
    /**
     * Update brand profile fields.
     */
    public function update(BusinessProfile $profile, array $data): BusinessProfile
    {
        $allowed = [
            'tagline', 'description', 'industry', 'tone_of_voice',
            'target_audience', 'services', 'brand_colors', 'fonts',
            'website_url', 'social_links', 'seo_keywords',
        ];

        $updateData = array_intersect_key($data, array_flip($allowed));

        // Sanitise brand_colors — ensure primary is a valid hex
        if (isset($updateData['brand_colors']['primary'])) {
            $primary = $updateData['brand_colors']['primary'];
            if (! preg_match('/^#([0-9a-fA-F]{3}|[0-9a-fA-F]{6})$/', $primary)) {
                unset($updateData['brand_colors']['primary']);
            }
        }

        // Invalidate AI context cache so LP Generator will regenerate it
        $updateData['ai_context_updated_at'] = null;
        $updateData['ai_context_cache']      = null;

        $profile->update($updateData);

        event(new BusinessProfileUpdated($profile));

        return $profile->fresh();
    }

    /**
     * Retrieve the profile, creating an empty one if it does not yet exist.
     */
    public function getOrCreate(Business $business): BusinessProfile
    {
        return BusinessProfile::firstOrCreate(
            ['business_id' => $business->id]
        );
    }

    /**
     * Return an array suitable for passing to OpenAI as context.
     * Reserved for LP Generator (v1.1).
     */
    public function getAiContext(Business $business): array
    {
        $profile = $this->getOrCreate($business);

        return [
            'brand_name'      => $business->name,
            'tagline'         => $profile->tagline,
            'industry'        => $profile->industry,
            'tone_of_voice'   => $profile->tone_of_voice ?? 'professional',
            'target_audience' => $profile->target_audience ?? [],
            'services'        => $profile->services ?? [],
            'primary_color'   => $profile->getPrimaryColorAttribute(),
            'language'        => $business->locale,
        ];
    }

    /**
     * Check if the minimum fields needed for LP generation are filled in.
     */
    public function isComplete(BusinessProfile $profile): bool
    {
        return $profile->isComplete();
    }

    /**
     * Return a completion percentage and list of missing fields.
     */
    public function completion(BusinessProfile $profile): array
    {
        $checks = [
            'tagline'      => filled($profile->tagline),
            'industry'     => filled($profile->industry),
            'tone_of_voice' => filled($profile->tone_of_voice),
            'services'     => ! empty($profile->services),
            'description'  => filled($profile->description),
            'brand_colors' => ! empty($profile->brand_colors),
        ];

        $total   = count($checks);
        $passed  = count(array_filter($checks));
        $missing = array_keys(array_filter($checks, fn ($v) => ! $v));

        return [
            'complete'    => $passed === $total,
            'percentage'  => (int) round(($passed / $total) * 100),
            'missing'     => $missing,
        ];
    }
}
