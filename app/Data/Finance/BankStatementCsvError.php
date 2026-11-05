<?php

namespace App\Data\Finance;

final readonly class BankStatementCsvError
{
    /** @param array<int, string> $messages */
    public function __construct(
        public int $rowNumber,
        public array $messages,
    ) {}
}
