<?php

namespace App\Services\AI;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

final class PublicAiBotGuard
{
    /**
     * Fail-closed reCAPTCHA v3 verification for paid public AI endpoints.
     * The existing generic RecaptchaService is intentionally NOT reused:
     * it is permissive when Google is unavailable or unconfigured.
     */
    public function verify(Request $request, string $action): void
    {
        if (! config('ai_assistant.recaptcha_required')) {
            return; // Explicitly disabled by the operator, intended for local development only.
        }

        $secret = (string) config('services.recaptcha.secret_key');
        $token = (string) $request->input('recaptcha_token', '');

        if ($secret === '' || $token === '' || mb_strlen($token) > 4096) {
            throw new AiUnavailable('bot_protection_unconfigured');
        }

        try {
            $response = Http::asForm()
                ->acceptJson()
                ->withOptions(['allow_redirects' => false])
                ->connectTimeout(3)->timeout(6)
                ->post('https://www.google.com/recaptcha/api/siteverify', [
                    'secret' => $secret,
                    'response' => $token,
                ]);
        } catch (\Throwable) {
            throw new AiUnavailable('bot_check_failed');
        }

        if (! $response->successful()) {
            throw new AiUnavailable('bot_check_failed');
        }

        $data = $response->json();
        if (! is_array($data)
            || ($data['success'] ?? false) !== true
            || ($data['action'] ?? null) !== $action
            || ! is_numeric($data['score'] ?? null)
            || (float) $data['score'] < 0.5
            || ! is_string($data['hostname'] ?? null)
            || strtolower($data['hostname']) !== strtolower($request->getHost())) {
            throw new AiUnavailable('bot_rejected');
        }
    }
}
