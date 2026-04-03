<?php

namespace App\Http\Requests\LandingPage;

use App\Models\LandingPage;
use App\Models\LandingPageGenerationVariant;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class RegenerateLandingSectionRequest extends FormRequest
{
    public function authorize(): bool
    {
        /** @var LandingPageGenerationVariant|null $variant */
        $variant = $this->route('variant');
        $business = currentBusiness();

        return ($this->user()?->can('generateAi', LandingPage::class) ?? false)
            && $variant !== null
            && $business !== null
            && $variant->business_id === $business->id;
    }

    public function rules(): array
    {
        return [
            'section_type' => ['required', 'string', Rule::in(array_keys(config('landing_pages.section_types', [])))],
            'instruction' => ['nullable', 'string', 'max:2000'],
        ];
    }
}