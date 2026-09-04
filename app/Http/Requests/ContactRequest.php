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
            'contact'            => ['required', 'string', 'max:255', function ($attribute, $value, $fail) {
                $isEmail = filter_var($value, FILTER_VALIDATE_EMAIL) !== false;
                $digits  = preg_replace('/\D+/', '', $value);
                $isPhone = ! $isEmail && preg_match('/^[0-9+()\-\s]+$/', $value) === 1 && strlen($digits) >= 7;

                if (! $isEmail && ! $isPhone) {
                    $fail('Podaj prawidłowy adres e-mail lub numer telefonu.');
                }
            }],
            'project_type'       => ['nullable', 'string', 'max:100'],
            'message'            => ['required', 'string', 'min:10', 'max:5000'],
            'gdpr_consent'       => ['required', 'accepted'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required'         => 'Imię i nazwisko jest wymagane.',
            'contact.required'      => 'Podaj adres e-mail lub numer telefonu.',
            'message.required'      => 'Wiadomość jest wymagana.',
            'message.min'           => 'Wiadomość musi mieć co najmniej 10 znaków.',
            'gdpr_consent.required' => 'Zgoda na przetwarzanie danych jest wymagana.',
            'gdpr_consent.accepted' => 'Musisz wyrazić zgodę na przetwarzanie danych.',
        ];
    }
}
