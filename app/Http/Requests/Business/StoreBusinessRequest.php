<?php

namespace App\Http\Requests\Business;

use Illuminate\Foundation\Http\FormRequest;

class StoreBusinessRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    public function rules(): array
    {
        return [
            'name'     => ['required', 'string', 'min:2', 'max:255'],
            'locale'   => ['nullable', 'string', 'in:en,pl,pt'],
            'timezone' => ['nullable', 'string', 'timezone'],
        ];
    }
}
