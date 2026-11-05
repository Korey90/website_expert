<?php

namespace App\Data\Finance;

final readonly class BankTransactionMatchCandidate
{
    /**
     * @param  class-string  $sourceType
     * @param  array{amount: int, date: int, reference: int, counterparty: int}  $scoreBreakdown
     * @param  list<string>  $reasons
     */
    public function __construct(
        public string $candidateKey,
        public string $sourceType,
        public int $sourceId,
        public string $eventType,
        public string $direction,
        public string $date,
        public string $comparableAmount,
        public string $comparableCurrency,
        public string $originalAmount,
        public string $originalCurrency,
        public string $amountDifference,
        public int $dateDifferenceDays,
        public ?string $reference,
        public ?string $counterparty,
        public int $score,
        public string $confidence,
        public array $scoreBreakdown,
        public array $reasons,
    ) {}
}
