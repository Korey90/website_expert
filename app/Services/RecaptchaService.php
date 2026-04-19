<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class RecaptchaService
{
    private const VERIFY_URL = 'https://www.google.com/recaptcha/api/siteverify';

    /** Minimum score to accept (0.0 – 1.0). Below this = bot. */
    private const MIN_SCORE = 0.5;

    /**
     * Verify a reCAPTCHA v3 token.
     * Returns true if the token is valid and score is acceptable.
     * Returns true (permissive) when reCAPTCHA is not configured — dev/staging fallback.
     */
    public function verify(?string $token, string $action = ''): bool
    {
        $secretKey = config('services.recaptcha.secret_key');

        // If not configured, skip verification (dev/staging without keys)
        if (empty($secretKey)) {
            return true;
        }

        if (empty($token)) {
            return false;
        }

        try {
            $response = Http::asForm()->post(self::VERIFY_URL, [
                'secret'   => $secretKey,
                'response' => $token,
            ]);

            if (! $response->successful()) {
                Log::warning('reCAPTCHA HTTP error', ['status' => $response->status()]);
                return false;
            }

            $body = $response->json();

            if (! ($body['success'] ?? false)) {
                return false;
            }

            if (isset($body['score']) && $body['score'] < self::MIN_SCORE) {
                Log::info('reCAPTCHA low score', ['score' => $body['score'], 'action' => $action]);
                return false;
            }

            return true;
        } catch (\Throwable $e) {
            Log::error('reCAPTCHA verification exception', ['error' => $e->getMessage()]);
            // Fail open — don't block legitimate users on service errors
            return true;
        }
    }
}
