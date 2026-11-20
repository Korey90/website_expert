<?php

namespace App\Services\Finance\Exceptions;

use RuntimeException;
use Throwable;

class FinancialAttachmentScanException extends RuntimeException
{
    public function __construct(
        public readonly string $errorCode,
        public readonly bool $retryable = true,
        ?Throwable $previous = null,
    ) {
        parent::__construct('The financial attachment security scan could not be completed.', 0, $previous);
    }
}
