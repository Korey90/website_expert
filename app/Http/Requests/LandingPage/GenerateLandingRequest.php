<?php

namespace App\Http\Requests\LandingPage;

use App\Models\LandingPage;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class GenerateLandingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('generateAi', LandingPage::class) ?? false;
    }

    public function rules(): array
    {
        return [
            'goal' => ['required', 'string', Rule::in(array_keys(config('landing_pages.conversion_goals', [])))],
            'description' => ['nullable', 'string', 'max:5000'],
            'campaign_name' => ['nullable', 'string', 'max:255'],
            'target_audience_override' => ['nullable', 'string', 'max:1000'],
            'offer_summary' => ['nullable', 'string', 'max:2000'],
            'preferred_language' => ['nullable', 'string', Rule::in(['pl', 'en', 'pt'])],
            'template_key' => ['nullable', 'string', Rule::in(array_keys(config('landing_pages.templates', [])))],
            'include_sections' => ['nullable', 'array', 'max:8'],
            'include_sections.*' => ['string', Rule::in(array_keys(config('landing_pages.section_types', [])))],
        ];
    }
}