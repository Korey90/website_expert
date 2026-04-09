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
            // UTM parameters from FormSection.jsx POST body
            'utm_source'        => ['nullable', 'string', 'max:255'],
            'utm_medium'        => ['nullable', 'string', 'max:255'],
            'utm_campaign'      => ['nullable', 'string', 'max:255'],
            'utm_content'       => ['nullable', 'string', 'max:255'],
            'utm_term'          => ['nullable', 'string', 'max:255'],
        ]);
    }

    public function messages(): array
    {
        return array_merge(parent::messages(), [
            'landing_page_slug.required' => __('validation.required', ['attribute' => 'landing_page_slug']),
        ]);
    }
}