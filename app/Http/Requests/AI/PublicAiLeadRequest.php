<?php

namespace App\Http\Requests\AI;

use Illuminate\Foundation\Http\FormRequest;

final class PublicAiLeadRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'min:2', 'max:120'],
            'email' => ['required', 'email:rfc', 'max:254'],
            'company' => ['nullable', 'string', 'max:120'],
            'project' => ['required', 'string', 'min:10', 'max:1000'],
            'consent' => ['required', 'accepted'],
            'locale' => ['required', 'in:en,pl,pt'],
            'recaptcha_token' => ['nullable', 'string', 'max:4096'],
            'website' => ['nullable', 'max:0'], // Honeypot.
        ];
    }
}
