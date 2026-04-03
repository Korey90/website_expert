<?php

namespace App\Http\Requests\LandingPage;

use App\Models\LandingPage;
use App\Models\LandingPageGenerationVariant;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SaveGeneratedLandingRequest extends FormRequest
{
    public function authorize(): bool
    {
        /** @var LandingPageGenerationVariant|null $variant */
        $variant = $this->route('variant');
        $business = currentBusiness();

        return ($this->user()?->can('create', LandingPage::class) ?? false)
            && $variant !== null
            && $business !== null
            && $variant->business_id === $business->id;
    }

    public function rules(): array
    {
        return [
            'title' => ['nullable', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:100', 'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/'],
            'language' => ['nullable', 'string', Rule::in(['pl', 'en', 'pt'])],
            'template_key' => ['nullable', 'string', Rule::in(array_keys(config('landing_pages.templates', [])))],
            'meta' => ['nullable', 'array'],
            'meta.meta_title' => ['nullable', 'string', 'max:160'],
            'meta.meta_description' => ['nullable', 'string', 'max:320'],
            'meta.conversion_goal' => ['nullable', 'string', Rule::in(array_keys(config('landing_pages.conversion_goals', [])))],
            'sections' => ['nullable', 'array', 'min:2', 'max:' . (int) config('landing_pages.max_sections_per_page', 20)],
            'sections.*.type' => ['required_with:sections', 'string', Rule::in(array_keys(config('landing_pages.section_types', [])))],
            'sections.*.content' => ['required_with:sections', 'array'],
            'sections.*.settings' => ['nullable', 'array'],
        ];
    }
}