<?php

namespace App\Http\Requests\Landing;

use Illuminate\Foundation\Http\FormRequest;

class StoreEnLandingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name'            => ['required', 'string', 'max:255'],
            'email'           => ['required', 'email:rfc,dns', 'max:255'],
            'phone'           => ['nullable', 'string', 'max:50'],
            'current_website' => ['nullable', 'url', 'max:500'],
            'message'         => ['required', 'string', 'min:5', 'max:3000'],
            'gdpr_consent'    => ['required', 'accepted'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required'           => 'Your name is required.',
            'email.required'          => 'An email address is required.',
            'email.email'             => 'Please enter a valid email address.',
            'message.required'        => 'Please briefly describe what you need.',
            'message.min'             => 'The message must be at least 5 characters.',
            'current_website.url'     => 'Please enter a valid URL (e.g. https://yourcompany.co.uk).',
            'gdpr_consent.required'   => 'Please accept the privacy policy to proceed.',
            'gdpr_consent.accepted'   => 'Please accept the privacy policy to proceed.',
        ];
    }
}
