<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class QuickContactRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name'         => ['required', 'string', 'max:255'],
            'email'        => ['nullable', 'email:rfc', 'max:255', 'required_without:phone'],
            'phone'        => ['nullable', 'string', 'max:50', 'required_without:email'],
            'message'      => ['required', 'string', 'min:10', 'max:5000'],
            'gdpr_consent' => ['required', 'accepted'],
            'service_slug' => ['nullable', 'string', 'max:100'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required'              => 'Full name is required.',
            'email.required_without'     => 'Please provide an email address or phone number.',
            'phone.required_without'     => 'Please provide an email address or phone number.',
            'email.email'                => 'Please enter a valid email address.',
            'message.required'           => 'Message is required.',
            'message.min'                => 'Message must be at least 10 characters.',
            'gdpr_consent.required'      => 'Please accept the privacy policy.',
            'gdpr_consent.accepted'      => 'Please accept the privacy policy.',
        ];
    }
}
