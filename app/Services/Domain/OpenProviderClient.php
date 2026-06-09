<?php

namespace App\Services\Domain;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use RuntimeException;

/**
 * Low-level HTTP client for the Openprovider REST API.
 *
 * Handles authentication (Bearer token) with automatic token caching.
 * All methods throw RuntimeException on API or HTTP errors.
 *
 * HTTP traffic logging: set OP_HTTP_LOG=true in .env (or pass in shell)
 * to dump every request/response to STDERR.
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
    private readonly bool   $logHttp;

    public function __construct()
    {
        $cfg = config('services.domain_registrar.openprovider');

        $this->username = $cfg['username'] ?? '';
        $this->password = $cfg['password'] ?? '';
        $this->baseUrl  = ($cfg['sandbox'] ?? true) ? self::SANDBOX_URL : self::PROD_URL;
        $this->logHttp  = filter_var(env('OP_HTTP_LOG', false), FILTER_VALIDATE_BOOLEAN);
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
        $response = $this->http()->withToken($this->token())
            ->get("{$this->baseUrl}{$path}", $query);

        return $this->parse($response, "GET {$path}");
    }

    public function post(string $path, array $data, int $timeout = 30): array
    {
        $response = $this->http($timeout)->withToken($this->token())
            ->post("{$this->baseUrl}{$path}", $data);

        return $this->parse($response, "POST {$path}");
    }

    public function put(string $path, array $data): array
    {
        $response = $this->http()->withToken($this->token())
            ->put("{$this->baseUrl}{$path}", $data);

        return $this->parse($response, "PUT {$path}");
    }

    // ── Auth ──────────────────────────────────────────────────────────────────

    /** Returns cached Bearer token, refreshing it if expired. */
    private function token(): string
    {
        return Cache::remember(self::TOKEN_KEY, self::TOKEN_TTL, function (): string {
            $response = $this->http(15)->post("{$this->baseUrl}/auth/login", [
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

    // ── HTTP factory ─────────────────────────────────────────────────────────

    /** Returns a configured PendingRequest, with log middleware when OP_HTTP_LOG=true. */
    private function http(int $timeout = 30): PendingRequest
    {
        $pending = Http::timeout($timeout);

        if ($this->logHttp) {
            $pending = $pending->withMiddleware($this->buildLogMiddleware());
        }

        return $pending;
    }

    // ── HTTP logging middleware ───────────────────────────────────────────────

    /**
     * Guzzle middleware that dumps every request and response to STDERR.
     * Activated by OP_HTTP_LOG=true environment variable.
     * Passwords and Bearer tokens are masked in the output.
     */
    private function buildLogMiddleware(): callable
    {
        return function (callable $handler): callable {
            return function (RequestInterface $request, array $options) use ($handler) {
                $method = $request->getMethod();
                $uri    = (string) $request->getUri();

                // Read and rewind request body
                $rawBody = (string) $request->getBody();
                $request->getBody()->rewind();

                // Mask sensitive fields before logging
                $displayBody = preg_replace(
                    ['/"password"\s*:\s*"[^"]*"/', '/"token"\s*:\s*"[A-Za-z0-9._\-]{20,}"/'],
                    ['"password":"[HIDDEN]"', '"token":"[MASKED]"'],
                    $rawBody
                ) ?? $rawBody;

                // Pretty-print JSON if possible
                $decoded = json_decode($displayBody, true);
                if ($decoded !== null) {
                    $displayBody = json_encode($decoded, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
                }

                fwrite(STDERR, "\n" . str_repeat('─', 60) . "\n");
                fwrite(STDERR, "▶  {$method} {$uri}\n");
                if ($displayBody !== '' && $displayBody !== '[]' && $displayBody !== '{}') {
                    fwrite(STDERR, "   Request body:\n");
                    foreach (explode("\n", (string) $displayBody) as $line) {
                        fwrite(STDERR, "   {$line}\n");
                    }
                }

                return $handler($request, $options)->then(
                    function (ResponseInterface $response) {
                        $status = $response->getStatusCode();

                        // Read and rewind response body
                        $rawBody = (string) $response->getBody();
                        $response->getBody()->rewind();

                        // Pretty-print JSON, truncate if huge
                        $decoded = json_decode($rawBody, true);
                        if ($decoded !== null) {
                            // Mask token value in auth responses
                            array_walk_recursive($decoded, function (mixed &$val, string|int $key): void {
                                if ($key === 'token' && is_string($val) && strlen($val) > 20) {
                                    $val = substr($val, 0, 8) . '...[MASKED]';
                                }
                            });
                            $displayBody = json_encode($decoded, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
                        } else {
                            $displayBody = $rawBody;
                        }

                        $truncated = '';
                        if (strlen((string) $displayBody) > 3000) {
                            $displayBody = substr((string) $displayBody, 0, 3000);
                            $truncated   = "\n   ...[truncated]";
                        }

                        fwrite(STDERR, "◀  HTTP {$status}\n");
                        fwrite(STDERR, "   Response body:\n");
                        foreach (explode("\n", (string) $displayBody) as $line) {
                            fwrite(STDERR, "   {$line}\n");
                        }
                        fwrite(STDERR, $truncated . "\n");

                        return $response;
                    }
                );
            };
        };
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
