<?php

namespace App\Data\Finance;

final readonly class BankStatementCsvRow
{
    public function __construct(
        public int $rowNumber,
        public string $bookingDate,
        public ?string $valueDate,
        public string $amount,
        public string $currency,
        public ?string $reference,
        public ?string $counterparty,
        public ?string $providerId,
        public string $sourceHash,
    ) {}
}
