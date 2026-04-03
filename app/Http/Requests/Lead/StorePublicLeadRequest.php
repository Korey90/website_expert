<?php

namespace App\Http\Requests\Lead;

use App\Http\Requests\LandingPage\LeadCaptureRequest;

class StorePublicLeadRequest extends LeadCaptureRequest
{
    public function rules(): array
    {
        return array_merge(parent::rules(), [
            'landing_page_slug' => ['required', 'string', 'max:255'],
            'consent'           => ['nullable', 'boolean'],
        ]);
    }

    public function messages(): array
    {
        return array_merge(parent::messages(), [
            'landing_page_slug.required' => __('validation.required', ['attribute' => 'landing_page_slug']),
        ]);
    }
}