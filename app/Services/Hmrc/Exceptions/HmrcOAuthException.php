<?php

namespace App\Services\Hmrc\Exceptions;

use Illuminate\Http\Client\Response;
use RuntimeException;

class HmrcOAuthException extends RuntimeException
{
    public function __construct(
        string $message,
        public readonly string $errorCode,
        public readonly int $status,
    ) {
        parent::__construct($message, $status);
    }

    public static function configuration(): self
    {
        return new self(
            'HMRC OAuth is not configured for the selected environment.',
            'HMRC_OAUTH_NOT_CONFIGURED',
            503,
        );
    }

    public static function invalidState(): self
    {
        return new self(
            'The HMRC authorisation session is invalid or has expired. Start the connection again.',
            'HMRC_OAUTH_INVALID_STATE',
            422,
        );
    }

    public static function denied(string $errorCode = 'ACCESS_DENIED'): self
    {
        return new self(
            'HMRC authorisation was not granted.',
            self::safeCode($errorCode, 'ACCESS_DENIED'),
            422,
        );
    }

    public static function fromResponse(Response $response): self
    {
        $status = $response->status();
        $errorCode = self::safeCode((string) data_get($response->json(), 'error', 'HMRC_OAUTH_FAILED'));

        return new self(
            "HMRC OAuth request failed [{$errorCode}] (HTTP {$status}).",
            $errorCode,
            $status,
        );
    }

    public static function invalidResponse(): self
    {
        return new self(
            'HMRC returned an invalid OAuth response.',
            'HMRC_OAUTH_INVALID_RESPONSE',
            502,
        );
    }

    public static function unreachable(): self
    {
        return new self(
            'HMRC OAuth is currently unreachable.',
            'HMRC_OAUTH_UNREACHABLE',
            503,
        );
    }

    public static function reconnectRequired(string $reason = 'HMRC_RECONNECT_REQUIRED'): self
    {
        return new self(
            'The HMRC connection must be authorised again.',
            self::safeCode($reason, 'HMRC_RECONNECT_REQUIRED'),
            409,
        );
    }

    private static function safeCode(string $value, string $fallback = 'HMRC_OAUTH_FAILED'): string
    {
        $value = strtoupper(trim($value));

        return preg_match('/^[A-Z0-9_-]{1,80}$/', $value) === 1 ? $value : $fallback;
    }
}
