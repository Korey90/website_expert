<?php

namespace App\Http\Requests\Business;

use Illuminate\Foundation\Http\FormRequest;

class UpdateProfileRequest extends FormRequest
{
    public function authorize(): bool
    {
        $business = currentBusiness();

        if (! $business) {
            return false;
        }

        return $this->user()
            ->businessMemberships()
            ->where('business_id', $business->id)
            ->whereIn('role', ['owner', 'admin', 'member'])
            ->exists();
    }

    public function rules(): array
    {
        return [
            'tagline'                          => ['nullable', 'string', 'max:255'],
            'description'                      => ['nullable', 'string', 'max:2000'],
            'industry'                         => ['nullable', 'string', 'max:100'],
            'tone_of_voice'                    => ['nullable', 'string', 'in:professional,friendly,bold,minimalist'],
            'website_url'                      => ['nullable', 'url', 'max:500'],

            'services'                         => ['nullable', 'array', 'max:20'],
            'services.*'                       => ['string', 'max:100'],

            'target_audience'                  => ['nullable', 'array'],
            'target_audience.age_range'        => ['nullable', 'string', 'max:50'],
            'target_audience.gender'           => ['nullable', 'string', 'in:male,female,mixed'],
            'target_audience.interests'        => ['nullable', 'array'],
            'target_audience.interests.*'      => ['string', 'max:100'],

            'brand_colors'                     => ['nullable', 'array'],
            'brand_colors.primary'             => ['nullable', 'string', 'regex:/^#([0-9a-fA-F]{3}|[0-9a-fA-F]{6})$/'],
            'brand_colors.secondary'           => ['nullable', 'string', 'regex:/^#([0-9a-fA-F]{3}|[0-9a-fA-F]{6})$/'],
            'brand_colors.accent'              => ['nullable', 'string', 'regex:/^#([0-9a-fA-F]{3}|[0-9a-fA-F]{6})$/'],

            'fonts'                            => ['nullable', 'array'],
            'fonts.heading'                    => ['nullable', 'string', 'max:100'],
            'fonts.body'                       => ['nullable', 'string', 'max:100'],

            'social_links'                     => ['nullable', 'array'],
            'social_links.facebook'            => ['nullable', 'url'],
            'social_links.instagram'           => ['nullable', 'url'],
            'social_links.linkedin'            => ['nullable', 'url'],
            'social_links.twitter'             => ['nullable', 'url'],

            'seo_keywords'                     => ['nullable', 'array', 'max:20'],
            'seo_keywords.*'                   => ['string', 'max:100'],
        ];
    }
}
