<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ContactRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name'               => ['required', 'string', 'max:255'],
            'email'              => ['required', 'email:rfc,dns', 'max:255'],
            'phone'              => ['nullable', 'string', 'max:50'],
            'company'            => ['nullable', 'string', 'max:255'],
            'nip'                => ['nullable', 'string', 'max:50'],
            'project_type'       => ['nullable', 'string', 'max:100'],
            'contact_preference' => ['nullable', 'string', 'max:50'],
            'message'            => ['required', 'string', 'min:10', 'max:5000'],
            'gdpr_consent'       => ['required', 'accepted'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required'         => 'Imię i nazwisko jest wymagane.',
            'email.required'        => 'Adres e-mail jest wymagany.',
            'email.email'           => 'Podaj prawidłowy adres e-mail.',
            'message.required'      => 'Wiadomość jest wymagana.',
            'message.min'           => 'Wiadomość musi mieć co najmniej 10 znaków.',
            'gdpr_consent.required' => 'Zgoda na przetwarzanie danych jest wymagana.',
            'gdpr_consent.accepted' => 'Musisz wyrazić zgodę na przetwarzanie danych.',
        ];
    }
}
