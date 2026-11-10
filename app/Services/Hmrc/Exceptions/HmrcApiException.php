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
        $errorCode = (string) data_get($response->json(), 'code', 'HMRC_REQUEST_FAILED');
        $correlationId = $response->header('X-CorrelationId');

        return new self(
            "HMRC API request failed [{$errorCode}] (HTTP {$status}).",
            $errorCode,
            $status,
            is_string($correlationId) && $correlationId !== '' ? $correlationId : null,
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
}
