<?php

namespace App\Exceptions;

use RuntimeException;

class LandingPageGenerationException extends RuntimeException
{
    public function __construct(
        string $message,
        private readonly string $errorCode = 'generation_failed',
        private readonly int $status = 422,
        ?\Throwable $previous = null,
    ) {
        parent::__construct($message, 0, $previous);
    }

    public function errorCode(): string
    {
        return $this->errorCode;
    }

    public function status(): int
    {
        return $this->status;
    }
}