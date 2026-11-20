<?php

namespace App\Contracts\Finance;

use App\Data\Finance\FinancialAttachmentScanResult;

interface FinancialAttachmentScanner
{
    public function engine(): string;

    public function scan(string $absolutePath): FinancialAttachmentScanResult;
}
