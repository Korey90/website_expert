<?php

namespace App\Http\Requests\Landing;

use Illuminate\Foundation\Http\FormRequest;

class StorePlLandingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name'              => ['required', 'string', 'max:255'],
            'email'             => ['required', 'email:rfc,dns', 'max:255'],
            'phone'             => ['nullable', 'string', 'max:50'],
            'company'           => ['nullable', 'string', 'max:255'],
            'current_website'   => ['nullable', 'url', 'max:500'],
            'message'           => ['required', 'string', 'min:5', 'max:3000'],
            'audience_type'     => ['nullable', 'string', 'in:english_clients,polish_clients,both,unknown'],
            'gdpr_consent'      => ['required', 'accepted'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required'           => 'Imię jest wymagane.',
            'email.required'          => 'Adres e-mail jest wymagany.',
            'email.email'             => 'Podaj prawidłowy adres e-mail.',
            'message.required'        => 'Wiadomość jest wymagana.',
            'message.min'             => 'Wiadomość musi mieć co najmniej 5 znaków.',
            'current_website.url'     => 'Podaj prawidłowy adres URL strony (np. https://twojafirma.co.uk).',
            'gdpr_consent.required'   => 'Zgoda na przetwarzanie danych jest wymagana.',
            'gdpr_consent.accepted'   => 'Musisz wyrazić zgodę na przetwarzanie danych.',
        ];
    }
}
