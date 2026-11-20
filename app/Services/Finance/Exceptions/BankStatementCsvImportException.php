<?php

namespace App\Services\Finance\Exceptions;

use DomainException;

final class BankStatementCsvImportException extends DomainException
{
    public function __construct(
        public readonly string $errorCode,
        string $message,
    ) {
        parent::__construct($message);
    }
}
