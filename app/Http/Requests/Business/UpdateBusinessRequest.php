<?php

namespace App\Http\Requests\Business;

use Illuminate\Foundation\Http\FormRequest;

class UpdateBusinessRequest extends FormRequest
{
    public function authorize(): bool
    {
        $business = currentBusiness();

        if (! $business) {
            return false;
        }

        return $this->user()
            ->businessMemberships()
            ->where('business_id', $business->id)
            ->whereIn('role', ['owner', 'admin'])
            ->exists();
    }

    public function rules(): array
    {
        return [
            'name'          => ['nullable', 'string', 'min:2', 'max:255'],
            'locale'        => ['nullable', 'string', 'in:en,pl,pt'],
            'timezone'      => ['nullable', 'string', 'timezone'],
            'primary_color' => ['nullable', 'string', 'regex:/^#([0-9a-fA-F]{3}|[0-9a-fA-F]{6})$/'],
            'logo'          => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
        ];
    }
}
