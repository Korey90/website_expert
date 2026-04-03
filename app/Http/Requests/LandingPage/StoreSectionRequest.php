<?php

namespace App\Http\Requests\LandingPage;

use App\Models\LandingPage;
use App\Models\LandingPageSection;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreSectionRequest extends FormRequest
{
    public function authorize(): bool
    {
        /** @var LandingPage $page */
        $page = $this->route('landingPage');
        return $this->user()?->can('update', $page) ?? false;
    }

    public function rules(): array
    {
        $allowedTypes = array_keys(config('landing_pages.section_types', []));

        return [
            'type'       => ['required', 'string', Rule::in($allowedTypes)],
            'order'      => ['nullable', 'integer', 'min:0'],
            'is_visible' => ['sometimes', 'boolean'],
            'content'    => ['required', 'array'],
            'settings'   => ['nullable', 'array'],

            // Type-specific content sub-rules (validated in service too)
            'content.title'    => ['nullable', 'string', 'max:255'],
            'content.subtitle' => ['nullable', 'string', 'max:500'],
            'content.body'     => ['nullable', 'string', 'max:50000'],
            'content.url'      => ['nullable', 'url', 'max:2048'],
        ];
    }

    public function after(): array
    {
        return [
            function ($validator) {
                $type    = $this->input('type');
                $content = $this->input('content', []);

                if ($type === 'form') {
                    /** @var LandingPage $page */
                    $page      = $this->route('landingPage');
                    $maxSections = config('landing_pages.max_sections_per_page', 20);

                    if ($page->sections()->count() >= $maxSections) {
                        $validator->errors()->add('type', __('landing_pages.validation.max_sections_reached', [
                            'max' => $maxSections,
                        ]));
                    }
                }

                if ($type === 'video' && ! empty($content['url'])) {
                    $allowedDomains = config('landing_pages.allowed_video_domains', []);
                    $host           = parse_url($content['url'], PHP_URL_HOST);

                    if (! in_array($host, $allowedDomains, true)) {
                        $validator->errors()->add('content.url', __('landing_pages.validation.invalid_video_domain'));
                    }
                }
            },
        ];
    }
}
