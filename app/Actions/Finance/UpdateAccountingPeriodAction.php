<?php

namespace App\Actions\Finance;

use App\Models\AccountingPeriod;
use App\Models\Business;
use App\Models\User;
use App\Services\Finance\AccountingPeriodGuard;
use App\Support\PermissionHelper;
use DomainException;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;

class UpdateAccountingPeriodAction
{
    public function __construct(private readonly AccountingPeriodGuard $guard) {}

    /** @param array<string, mixed> $data */
    public function execute(AccountingPeriod $period, User $actor, array $data): AccountingPeriod
    {
        return DB::transaction(function () use ($period, $actor, $data): AccountingPeriod {
            $locked = AccountingPeriod::withoutGlobalScopes()->lockForUpdate()->findOrFail($period->id);
            $this->assertEditable($locked, $actor);
            Business::query()->lockForUpdate()->findOrFail($locked->business_id);
            $startDate = (string) ($data['start_date'] ?? $locked->start_date->toDateString());
            $endDate = (string) ($data['end_date'] ?? $locked->end_date->toDateString());
            $name = (string) ($data['name'] ?? $locked->name);
            $financialYear = (int) ($data['financial_year'] ?? $locked->financial_year);

            if (blank($name) || $financialYear < 1900) {
                throw new DomainException('An accounting period requires a name and valid financial year.');
            }

            $this->guard->assertNoOverlap($locked->business_id, $startDate, $endDate, $locked->id);
            $locked->update(Arr::only($data, ['name', 'financial_year', 'period_number', 'start_date', 'end_date']));

            return $locked->fresh();
        });
    }

    private function assertEditable(AccountingPeriod $period, User $actor): void
    {
        if (! $actor->businessMemberships()->where('business_id', $period->business_id)->where('is_active', true)->exists()) {
            throw new DomainException('The actor does not belong to the accounting period business.');
        }

        if (! PermissionHelper::allows($actor, 'manage_finance')) {
            throw new DomainException('The actor cannot manage accounting periods.');
        }

        if ($period->status !== AccountingPeriod::STATUS_OPEN) {
            throw new DomainException('Only an open accounting period can be edited.');
        }
    }
}
