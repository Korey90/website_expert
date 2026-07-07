<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SecurityWebhookRequest extends FormRequest
{
    public function authorize(): bool
    {
        $secret = config('services.security.webhook_secret');

        if (! $secret) {
            return false;
        }

        return hash_equals($secret, (string) $this->header('X-Security-Secret', ''));
    }

    public function rules(): array
    {
        return [
            'ip'       => ['required', 'ip'],
            'jail'     => ['required', 'string', 'max:64'],
            'action'   => ['required', 'in:banned,unbanned'],
            'failures' => ['nullable', 'integer', 'min:0', 'max:9999'],
            'country'  => ['nullable', 'string', 'max:64'],
            'city'     => ['nullable', 'string', 'max:64'],
            'isp'      => ['nullable', 'string', 'max:128'],
        ];
    }
}
