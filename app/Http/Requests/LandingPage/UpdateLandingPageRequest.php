<?php

namespace App\Http\Requests\LandingPage;

use App\Models\LandingPage;
use App\Services\LandingPage\LandingPageSlugService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateLandingPageRequest extends FormRequest
{
    public function authorize(): bool
    {
        $page = $this->route('landingPage');
        return $this->user()?->can('update', $page) ?? false;
    }

    public function rules(): array
    {
        /** @var LandingPage $page */
        $page        = $this->route('landingPage');
        $business    = currentBusiness();
        $slugService = app(LandingPageSlugService::class);

        return [
            'title' => ['sometimes', 'required', 'string', 'max:255'],

            'slug' => [
                'sometimes',
                'nullable',
                'string',
                'max:255',
                'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/',
                function (string $attribute, mixed $value, $fail) use ($business, $slugService, $page) {
                    if ($value && $value !== $page->slug && ! $slugService->validate($value, $business, $page)) {
                        $fail(__('landing_pages.validation.slug_taken'));
                    }
                },
            ],

            'description'      => ['sometimes', 'nullable', 'string', 'max:1000'],
            'language'         => ['sometimes', 'nullable', 'string', Rule::in(['en', 'pl', 'pt'])],
            'conversion_goal'  => ['sometimes', 'nullable', 'string', Rule::in(array_keys(config('landing_pages.conversion_goals', [])))],
            'meta_title'       => ['sometimes', 'nullable', 'string', 'max:255'],
            'meta_description' => ['sometimes', 'nullable', 'string', 'max:500'],
            'custom_css'       => ['sometimes', 'nullable', 'string', 'max:10000'],
            'settings'         => ['sometimes', 'nullable', 'array'],
        ];
    }
}
