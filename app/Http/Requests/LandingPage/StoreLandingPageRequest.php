<?php

namespace App\Http\Requests\LandingPage;

use App\Models\LandingPage;
use App\Services\LandingPage\LandingPageSlugService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreLandingPageRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', LandingPage::class) ?? false;
    }

    public function rules(): array
    {
        $business    = currentBusiness();
        $slugService = app(LandingPageSlugService::class);

        return [
            'title' => ['required', 'string', 'max:255'],

            'slug' => [
                'nullable',
                'string',
                'max:255',
                'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/',
                function (string $attribute, mixed $value, $fail) use ($business, $slugService) {
                    if ($value && ! $slugService->validate($value, $business)) {
                        $fail(__('landing_pages.validation.slug_taken'));
                    }
                },
            ],

            'description'      => ['nullable', 'string', 'max:1000'],
            'language'         => ['nullable', 'string', Rule::in(['en', 'pl', 'pt'])],
            'template_key'     => ['nullable', 'string', Rule::in(array_keys(config('landing_pages.templates', [])))],
            'conversion_goal'  => ['nullable', 'string', Rule::in(array_keys(config('landing_pages.conversion_goals', [])))],
            'meta_title'       => ['nullable', 'string', 'max:255'],
            'meta_description' => ['nullable', 'string', 'max:500'],
            'custom_css'       => ['nullable', 'string', 'max:10000'],
            'settings'         => ['nullable', 'array'],
            'ai_generated'     => ['sometimes', 'boolean'],
        ];
    }
}
