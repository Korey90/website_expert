<?php

namespace App\Services\Domain;

use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use RuntimeException;

/**
 * Low-level HTTP client for the Openprovider REST API.
 *
 * Handles authentication (Bearer token) with automatic token caching.
 * All methods throw RuntimeException on API or HTTP errors.
 *
 * @see https://docs.openprovider.com/
 */
class OpenProviderClient
{
    private const PROD_URL    = 'https://api.openprovider.eu/v1beta';
    private const SANDBOX_URL = 'http://api.sandbox.openprovider.nl:8480/v1beta';
    private const TOKEN_TTL   = 82_800; // 23 hours (tokens valid for ~24h)
    private const TOKEN_KEY   = 'openprovider_api_token';

    private readonly string $baseUrl;
    private readonly string $username;
    private readonly string $password;

    public function __construct()
    {
        $cfg = config('services.domain_registrar.openprovider');

        $this->username = $cfg['username'] ?? '';
        $this->password = $cfg['password'] ?? '';
        $this->baseUrl  = ($cfg['sandbox'] ?? true) ? self::SANDBOX_URL : self::PROD_URL;
    }

    // ── Public accessors ─────────────────────────────────────────────────────

    /** Returns a valid Bearer token (cached 23 h). Used for raw Http::pool() calls. */
    public function bearerToken(): string
    {
        return $this->token();
    }

    /** Returns the configured base URL (sandbox or production). */
    public function getBaseUrl(): string
    {
        return $this->baseUrl;
    }

    // ── Public HTTP methods ───────────────────────────────────────────────────

    public function get(string $path, array $query = []): array
    {
        $response = Http::timeout(30)->withToken($this->token())
            ->get("{$this->baseUrl}{$path}", $query);

        return $this->parse($response, "GET {$path}");
    }

    public function post(string $path, array $data): array
    {
        $response = Http::timeout(30)->withToken($this->token())
            ->post("{$this->baseUrl}{$path}", $data);

        return $this->parse($response, "POST {$path}");
    }

    public function put(string $path, array $data): array
    {
        $response = Http::timeout(30)->withToken($this->token())
            ->put("{$this->baseUrl}{$path}", $data);

        return $this->parse($response, "PUT {$path}");
    }

    // ── Auth ──────────────────────────────────────────────────────────────────

    /** Returns cached Bearer token, refreshing it if expired. */
    private function token(): string
    {
        return Cache::remember(self::TOKEN_KEY, self::TOKEN_TTL, function (): string {
            $response = Http::timeout(15)->post("{$this->baseUrl}/auth/login", [
                'username' => $this->username,
                'password' => $this->password,
                'ip'       => '0.0.0.0',
            ]);

            if (! $response->successful() || ($response->json('code') !== 0)) {
                throw new RuntimeException(
                    'Openprovider authentication failed: ' . $response->body()
                );
            }

            return $response->json('data.token');
        });
    }

    // ── Response handling ─────────────────────────────────────────────────────

    private function parse(\Illuminate\Http\Client\Response $response, string $context): array
    {
        if (! $response->successful()) {
            // Clear cached token on 401 so next request re-authenticates
            if ($response->status() === 401) {
                Cache::forget(self::TOKEN_KEY);
            }
            throw new RuntimeException(
                "Openprovider HTTP error on {$context} (status {$response->status()}): " . $response->body()
            );
        }

        $json = $response->json();
        $code = $json['code'] ?? -1;

        if ($code !== 0) {
            throw new RuntimeException(
                "Openprovider API error on {$context} (code {$code}): " . ($json['desc'] ?? 'Unknown error')
            );
        }

        return $json['data'] ?? [];
    }
}
