<?php

namespace App\Services\Hmrc;

use App\Models\Business;
use App\Models\HmrcConnection;
use App\Models\User;
use App\Services\Hmrc\Exceptions\HmrcOAuthException;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Response;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Throwable;

class HmrcOAuthService
{
    private const SESSION_KEY = 'hmrc.oauth.pending';

    public function authorizationUrl(Request $request, Business $business, User $actor): string
    {
        $configuration = $this->configuration();
        $state = bin2hex(random_bytes(32));
        $verifier = $this->base64UrlEncode(random_bytes(64));
        $challenge = $this->base64UrlEncode(hash('sha256', $verifier, true));

        $request->session()->put(self::SESSION_KEY, [
            'state_hash' => hash('sha256', $state),
            'code_verifier' => Crypt::encryptString($verifier),
            'business_id' => $business->getKey(),
            'user_id' => $actor->getAuthIdentifier(),
            'environment' => $configuration['environment'],
            'created_at' => now()->getTimestamp(),
        ]);

        return $configuration['authorize_url'].'?'.http_build_query([
            'response_type' => 'code',
            'client_id' => $configuration['client_id'],
            'scope' => implode(' ', $this->configuredScopes()),
            'state' => $state,
            'redirect_uri' => $configuration['redirect_uri'],
            'code_challenge' => $challenge,
            'code_challenge_method' => 'S256',
        ], '', '&', PHP_QUERY_RFC3986);
    }

    public function completeAuthorization(Request $request, Business $business, User $actor): HmrcConnection
    {
        $configuration = $this->configuration();
        $pending = $request->session()->pull(self::SESSION_KEY);
        $state = $request->query('state');

        if (! $this->validPendingState($pending, $state, $business, $actor, $configuration['environment'])) {
            throw HmrcOAuthException::invalidState();
        }

        if (filled($request->query('error'))) {
            throw HmrcOAuthException::denied((string) $request->query('error_code', 'ACCESS_DENIED'));
        }

        $code = $request->query('code');
        if (! is_string($code) || $code === '' || strlen($code) > 2048) {
            throw HmrcOAuthException::invalidState();
        }

        try {
            $verifier = Crypt::decryptString((string) $pending['code_verifier']);
        } catch (Throwable) {
            throw HmrcOAuthException::invalidState();
        }

        $tokens = $this->requestTokens($configuration, [
            'grant_type' => 'authorization_code',
            'redirect_uri' => $configuration['redirect_uri'],
            'code' => $code,
            'code_verifier' => $verifier,
        ]);

        return DB::transaction(function () use ($business, $actor, $configuration, $tokens): HmrcConnection {
            $connection = HmrcConnection::withoutGlobalScopes()->updateOrCreate(
                [
                    'business_id' => $business->getKey(),
                    'environment' => $configuration['environment'],
                ],
                [
                    'scopes' => $tokens['scopes'],
                    'access_token' => $tokens['access_token'],
                    'refresh_token' => $tokens['refresh_token'],
                    'vrn' => $this->businessVrn($business),
                    'expires_at' => now()->addSeconds($tokens['expires_in']),
                    'status' => HmrcConnection::STATUS_CONNECTED,
                    'connected_at' => now(),
                    'connected_by' => $actor->getAuthIdentifier(),
                    'refreshed_at' => null,
                    'refreshed_by' => null,
                    'disconnected_at' => null,
                    'disconnected_by' => null,
                    'reconnect_reason' => null,
                ],
            );

            return $connection->fresh();
        });
    }

    public function refresh(Business $business, User $actor): HmrcConnection
    {
        $configuration = $this->configuration();

        $result = DB::transaction(function () use ($business, $actor, $configuration): HmrcConnection|HmrcOAuthException {
            $connection = HmrcConnection::withoutGlobalScopes()
                ->where('business_id', $business->getKey())
                ->where('environment', $configuration['environment'])
                ->lockForUpdate()
                ->first();

            if (! $connection || ! filled($connection->refresh_token)) {
                throw HmrcOAuthException::reconnectRequired();
            }

            try {
                $tokens = $this->requestTokens($configuration, [
                    'grant_type' => 'refresh_token',
                    'refresh_token' => $connection->refresh_token,
                ]);
            } catch (HmrcOAuthException $exception) {
                if ($exception->errorCode === 'INVALID_GRANT') {
                    $this->markReconnectRequired($connection, $exception->errorCode);

                    return $exception;
                }

                throw $exception;
            }

            $connection->forceFill([
                'scopes' => $tokens['scopes'],
                'access_token' => $tokens['access_token'],
                'refresh_token' => $tokens['refresh_token'],
                'expires_at' => now()->addSeconds($tokens['expires_in']),
                'status' => HmrcConnection::STATUS_CONNECTED,
                'refreshed_at' => now(),
                'refreshed_by' => $actor->getAuthIdentifier(),
                'reconnect_reason' => null,
            ])->save();

            return $connection->fresh();
        }, 3);

        if ($result instanceof HmrcOAuthException) {
            throw $result;
        }

        return $result;
    }

    public function disconnect(Business $business, User $actor): ?HmrcConnection
    {
        $environment = $this->environment();

        return DB::transaction(function () use ($business, $actor, $environment): ?HmrcConnection {
            $connection = HmrcConnection::withoutGlobalScopes()
                ->where('business_id', $business->getKey())
                ->where('environment', $environment)
                ->lockForUpdate()
                ->first();

            if (! $connection) {
                return null;
            }

            $connection->forceFill([
                'access_token' => null,
                'refresh_token' => null,
                'expires_at' => null,
                'status' => HmrcConnection::STATUS_DISCONNECTED,
                'disconnected_at' => now(),
                'disconnected_by' => $actor->getAuthIdentifier(),
                'reconnect_reason' => 'Disconnected locally; revoke HMRC authority in Manage authorised applications if required.',
            ])->save();

            return $connection->fresh();
        });
    }

    /** @return array{environment: string, authorize_url: string, token_url: string, client_id: string, client_secret: string, redirect_uri: string} */
    private function configuration(): array
    {
        $environment = $this->environment();
        $configuration = (array) config("hmrc.{$environment}", []);

        foreach (['authorize_url', 'token_url', 'client_id', 'client_secret', 'redirect_uri'] as $key) {
            if (! is_string($configuration[$key] ?? null) || trim($configuration[$key]) === '') {
                throw HmrcOAuthException::configuration();
            }
        }

        return [
            'environment' => $environment,
            'authorize_url' => rtrim($configuration['authorize_url'], '/'),
            'token_url' => $configuration['token_url'],
            'client_id' => $configuration['client_id'],
            'client_secret' => $configuration['client_secret'],
            'redirect_uri' => $configuration['redirect_uri'],
        ];
    }

    private function environment(): string
    {
        $environment = (string) config('hmrc.environment', 'sandbox');

        if (! in_array($environment, ['sandbox', 'production'], true)) {
            throw HmrcOAuthException::configuration();
        }

        return $environment;
    }

    /**
     * @param  array{environment: string, authorize_url: string, token_url: string, client_id: string, client_secret: string, redirect_uri: string}  $configuration
     * @param  array<string, string>  $grant
     * @return array{access_token: string, refresh_token: string, expires_in: int, scopes: array<int, string>}
     */
    private function requestTokens(array $configuration, array $grant): array
    {
        try {
            $response = Http::asForm()
                ->timeout(max(1, (int) config('hmrc.timeout', 20)))
                ->post($configuration['token_url'], [
                    'client_id' => $configuration['client_id'],
                    'client_secret' => $configuration['client_secret'],
                    ...$grant,
                ]);
        } catch (ConnectionException) {
            throw HmrcOAuthException::unreachable();
        }

        if (! $response->successful()) {
            throw HmrcOAuthException::fromResponse($response);
        }

        return $this->validatedTokens($response);
    }

    /** @return array{access_token: string, refresh_token: string, expires_in: int, scopes: array<int, string>} */
    private function validatedTokens(Response $response): array
    {
        $accessToken = $response->json('access_token');
        $refreshToken = $response->json('refresh_token');
        $expiresIn = filter_var($response->json('expires_in'), FILTER_VALIDATE_INT);
        $tokenType = strtolower((string) $response->json('token_type', 'bearer'));

        if (! is_string($accessToken) || $accessToken === ''
            || ! is_string($refreshToken) || $refreshToken === ''
            || $expiresIn === false || $expiresIn < 1
            || $tokenType !== 'bearer') {
            throw HmrcOAuthException::invalidResponse();
        }

        $scope = $response->json('scope');
        $scopes = is_string($scope) && trim($scope) !== ''
            ? preg_split('/\s+/', trim($scope))
            : $this->configuredScopes();

        return [
            'access_token' => $accessToken,
            'refresh_token' => $refreshToken,
            'expires_in' => $expiresIn,
            'scopes' => array_values(array_unique(array_filter($scopes))),
        ];
    }

    /** @return array<int, string> */
    private function configuredScopes(): array
    {
        return array_values(array_filter(
            (array) config('hmrc.scopes', []),
            fn (mixed $scope): bool => is_string($scope) && $scope !== '',
        ));
    }

    private function validPendingState(
        mixed $pending,
        mixed $state,
        Business $business,
        User $actor,
        string $environment,
    ): bool {
        if (! is_array($pending) || ! is_string($state) || $state === '' || strlen($state) > 255) {
            return false;
        }

        $createdAt = filter_var($pending['created_at'] ?? null, FILTER_VALIDATE_INT);
        $ttl = max(60, (int) config('hmrc.oauth_state_ttl', 600));

        return is_string($pending['state_hash'] ?? null)
            && hash_equals($pending['state_hash'], hash('sha256', $state))
            && ($pending['business_id'] ?? null) === $business->getKey()
            && ($pending['user_id'] ?? null) === $actor->getAuthIdentifier()
            && ($pending['environment'] ?? null) === $environment
            && $createdAt !== false
            && $createdAt >= now()->subSeconds($ttl)->getTimestamp()
            && $createdAt <= now()->getTimestamp();
    }

    private function businessVrn(Business $business): ?string
    {
        $vatNumber = $business->accountingProfile()->withoutGlobalScopes()->value('vat_number');
        $digits = preg_replace('/\D+/', '', (string) $vatNumber);

        return is_string($digits) && strlen($digits) >= 9 ? substr($digits, 0, 9) : null;
    }

    private function markReconnectRequired(HmrcConnection $connection, string $reason): void
    {
        $connection->forceFill([
            'access_token' => null,
            'refresh_token' => null,
            'expires_at' => null,
            'status' => HmrcConnection::STATUS_RECONNECT_REQUIRED,
            'reconnect_reason' => $reason,
        ])->save();
    }

    private function base64UrlEncode(string $value): string
    {
        return rtrim(strtr(base64_encode($value), '+/', '-_'), '=');
    }
}
