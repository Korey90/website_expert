<?php

namespace App\Http\Requests\LandingPage;

use Illuminate\Foundation\Http\FormRequest;

class LeadCaptureRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Public form — no authentication required
        return true;
    }

    public function rules(): array
    {
        return [
            'name'    => ['nullable', 'string', 'max:255'],
            'email'   => ['required', 'string', 'max:255', 'email:rfc'],
            'phone'   => ['nullable', 'string', 'max:50', 'regex:/^[\+\d\s\-\(\)]{6,50}$/'],
            'company' => ['nullable', 'string', 'max:255'],
            'message' => ['nullable', 'string', 'max:2000'],
            // Honeypot — must be empty (bots fill everything)
            'website' => ['sometimes', 'size:0'],
        ];
    }

    public function messages(): array
    {
        return [
            'email.required' => __('validation.required', ['attribute' => 'email']),
            'email.email'    => __('validation.email', ['attribute' => 'email']),
            'website.size'   => '', // silent
        ];
    }
}
