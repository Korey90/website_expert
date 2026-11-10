<?php

namespace App\Services\Hmrc;

use App\Services\Hmrc\Exceptions\HmrcFraudPreventionException;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;

class HmrcApplicationTokenService
{
    public function issue(): string
    {
        $environment = (string) config('hmrc.environment', 'sandbox');
        $configuration = (array) config("hmrc.{$environment}", []);
        $tokenUrl = $configuration['token_url'] ?? null;
        $clientId = $configuration['client_id'] ?? null;
        $clientSecret = $configuration['client_secret'] ?? null;

        if (! in_array($environment, ['sandbox', 'production'], true)
            || ! is_string($tokenUrl) || trim($tokenUrl) === ''
            || ! is_string($clientId) || trim($clientId) === ''
            || ! is_string($clientSecret) || trim($clientSecret) === '') {
            throw HmrcFraudPreventionException::configuration();
        }

        try {
            $response = Http::asForm()
                ->timeout(max(1, (int) config('hmrc.timeout', 20)))
                ->post($tokenUrl, [
                    'client_id' => $clientId,
                    'client_secret' => $clientSecret,
                    'grant_type' => 'client_credentials',
                ]);
        } catch (ConnectionException) {
            throw HmrcFraudPreventionException::unreachable();
        }

        if (! $response->successful()) {
            throw HmrcFraudPreventionException::fromResponse($response);
        }

        $token = $response->json('access_token');
        $type = strtolower((string) $response->json('token_type'));
        $expiresIn = filter_var($response->json('expires_in'), FILTER_VALIDATE_INT);

        if (! is_string($token) || $token === '' || $type !== 'bearer' || $expiresIn === false || $expiresIn < 1) {
            throw HmrcFraudPreventionException::invalidTokenResponse();
        }

        return $token;
    }
}
