<?php

namespace App\Services\Finance;

use App\Models\AccountingPeriod;
use Carbon\CarbonImmutable;
use DomainException;

class AccountingPeriodGuard
{
    public function assertNoOverlap(string $businessId, string $startDate, string $endDate, ?int $exceptId = null): void
    {
        $start = CarbonImmutable::parse($startDate)->startOfDay();
        $end = CarbonImmutable::parse($endDate)->startOfDay();

        if ($end->lt($start)) {
            throw new DomainException('The accounting period end date cannot precede its start date.');
        }

        $overlap = AccountingPeriod::withoutGlobalScopes()
            ->where('business_id', $businessId)
            ->when($exceptId, fn ($query) => $query->whereKeyNot($exceptId))
            ->whereDate('start_date', '<=', $end->toDateString())
            ->whereDate('end_date', '>=', $start->toDateString())
            ->exists();

        if ($overlap) {
            throw new DomainException('The accounting period overlaps an existing period.');
        }
    }

    public function assertDateIsWritable(string $businessId, string $date): void
    {
        $period = AccountingPeriod::withoutGlobalScopes()
            ->where('business_id', $businessId)
            ->containing($date)
            ->first();

        if ($period && ! $period->isWritable()) {
            throw new DomainException("Accounting period [{$period->name}] is {$period->status}.");
        }
    }
}
