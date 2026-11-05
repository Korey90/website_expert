<?php

namespace App\Data\Finance;

final readonly class BankStatementImportResult
{
    public function __construct(
        public bool $dryRun,
        public BankStatementCsvParseResult $parseResult,
        public int $importedRows,
        public int $duplicateRows,
        public ?int $importId = null,
    ) {}
}
