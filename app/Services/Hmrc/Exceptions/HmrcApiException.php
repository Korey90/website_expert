<?php

namespace App\Services\Hmrc\Exceptions;

use Illuminate\Http\Client\Response;
use RuntimeException;
use Throwable;

class HmrcApiException extends RuntimeException
{
    public function __construct(
        string $message,
        public readonly string $errorCode,
        public readonly int $status,
        public readonly ?string $correlationId = null,
        ?Throwable $previous = null,
    ) {
        parent::__construct($message, $status, $previous);
    }

    public static function fromResponse(Response $response): self
    {
        $status = $response->status();
        $topLevelCode = self::safeCode(data_get($response->json(), 'code'));
        $nestedCode = self::safeCode(data_get($response->json(), 'errors.0.code'));
        $errorCode = $topLevelCode === 'BUSINESS_ERROR' && $nestedCode !== null
            ? $nestedCode
            : ($topLevelCode ?? 'HMRC_REQUEST_FAILED');
        $correlationId = $response->header('X-CorrelationId');

        return new self(
            "HMRC API request failed [{$errorCode}] (HTTP {$status}).",
            $errorCode,
            $status,
            is_string($correlationId) && preg_match('/^[A-Za-z0-9._:-]{1,100}$/', $correlationId) === 1
                ? $correlationId
                : null,
        );
    }

    public static function transport(Throwable $previous): self
    {
        return new self(
            'HMRC API is currently unreachable.',
            'HMRC_UNREACHABLE',
            503,
            null,
            $previous,
        );
    }

    private static function safeCode(mixed $value): ?string
    {
        if (! is_string($value)) {
            return null;
        }

        $value = strtoupper(trim($value));

        return preg_match('/^[A-Z0-9_-]{1,100}$/', $value) === 1 ? $value : null;
    }
}
