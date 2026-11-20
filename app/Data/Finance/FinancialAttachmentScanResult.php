<?php

namespace App\Data\Finance;

use App\Models\FinancialAttachment;

final readonly class FinancialAttachmentScanResult
{
    private function __construct(
        public string $status,
        public string $engine,
    ) {}

    public static function clean(string $engine): self
    {
        return new self(FinancialAttachment::SCAN_CLEAN, $engine);
    }

    public static function rejected(string $engine): self
    {
        return new self(FinancialAttachment::SCAN_REJECTED, $engine);
    }
}
