<?php

namespace App\Data\Finance;

final readonly class BankStatementCsvParseResult
{
    /**
     * @param  array<int, string>  $headers
     * @param  array<int, BankStatementCsvRow>  $rows
     * @param  array<int, BankStatementCsvError>  $errors
     * @param  array<string, string|null>  $resolvedMapping
     */
    public function __construct(
        public string $fileHash,
        public array $headers,
        public array $rows,
        public array $errors,
        public int $totalRows,
        public array $resolvedMapping = [],
        public ?string $profile = null,
    ) {}

    public function validRows(): int
    {
        return count($this->rows);
    }

    public function failedRows(): int
    {
        return count($this->errors);
    }
}
