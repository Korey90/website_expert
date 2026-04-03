<?php

namespace App\Http\Requests\Lead;

use Illuminate\Foundation\Http\FormRequest;

class ApiLeadRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Auth is handled by ApiTokenAuthentication middleware
        return true;
    }

    public function rules(): array
    {
        return [
            'email'        => ['required', 'email:rfc,dns', 'max:255'],
            'name'         => ['required', 'string', 'min:2', 'max:255'],
            'phone'        => ['nullable', 'string', 'max:30', 'regex:/^[\+\d\s\(\)\-]+$/'],
            'company'      => ['nullable', 'string', 'max:255'],
            'message'      => ['nullable', 'string', 'max:2000'],
            'source_page'  => ['nullable', 'url', 'max:2000'],
            'utm_source'   => ['nullable', 'string', 'max:100'],
            'utm_medium'   => ['nullable', 'string', 'max:100'],
            'utm_campaign' => ['nullable', 'string', 'max:100'],
            'metadata'     => ['nullable', 'array'],
            'metadata.*'   => ['string', 'max:500'],
        ];
    }

    public function messages(): array
    {
        return [
            'email.required'     => 'The email address is required.',
            'email.email'        => 'Please provide a valid email address.',
            'name.required'      => 'The name field is required.',
            'name.min'           => 'Name must be at least 2 characters.',
            'phone.regex'        => 'Phone number format is invalid.',
        ];
    }
}
