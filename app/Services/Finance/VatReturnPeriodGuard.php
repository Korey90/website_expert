<?php

namespace App\Services\Finance;

use App\Models\VatReturn;
use DomainException;

class VatReturnPeriodGuard
{
    public function assertWritable(string $businessId, string $entryDate): void
    {
        $lockedReturn = VatReturn::withoutGlobalScopes()
            ->where('business_id', $businessId)
            ->where('status', VatReturn::STATUS_LOCKED)
            ->whereDate('period_from', '<=', $entryDate)
            ->whereDate('period_to', '>=', $entryDate)
            ->first(['number', 'period_from', 'period_to']);

        if ($lockedReturn) {
            throw new DomainException(__('finance.vat_returns.errors.period_locked', [
                'number' => $lockedReturn->number,
                'date' => $entryDate,
            ]));
        }
    }
}
