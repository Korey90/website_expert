<?php

namespace App\Http\Requests\LandingPage;

use App\Models\LandingPageSection;
use Illuminate\Foundation\Http\FormRequest;

class UpdateSectionRequest extends FormRequest
{
    public function authorize(): bool
    {
        /** @var LandingPageSection $section */
        $section = $this->route('section');
        return $this->user()?->can('update', $section->landingPage) ?? false;
    }

    public function rules(): array
    {
        return [
            'order'      => ['sometimes', 'nullable', 'integer', 'min:0'],
            'is_visible' => ['sometimes', 'boolean'],
            'content'    => ['sometimes', 'nullable', 'array'],
            'settings'   => ['sometimes', 'nullable', 'array'],

            'content.title'    => ['sometimes', 'nullable', 'string', 'max:255'],
            'content.subtitle' => ['sometimes', 'nullable', 'string', 'max:500'],
            'content.body'     => ['sometimes', 'nullable', 'string', 'max:50000'],
            'content.url'      => ['sometimes', 'nullable', 'url', 'max:2048'],
        ];
    }

    public function after(): array
    {
        return [
            function ($validator) {
                $content = $this->input('content');

                if ($content === null) {
                    return;
                }

                /** @var LandingPageSection $section */
                $section = $this->route('section');

                if ($section->type === 'video' && ! empty($content['url'])) {
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
