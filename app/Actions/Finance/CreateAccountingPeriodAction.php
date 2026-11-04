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

class CreateAccountingPeriodAction
{
    public function __construct(private readonly AccountingPeriodGuard $guard) {}

    /** @param array<string, mixed> $data */
    public function execute(Business $business, User $actor, array $data): AccountingPeriod
    {
        $this->assertMembership($business->id, $actor);

        if (! PermissionHelper::allows($actor, 'manage_finance')) {
            throw new DomainException('The actor cannot manage accounting periods.');
        }

        if (blank($data['name'] ?? null) || (int) ($data['financial_year'] ?? 0) < 1900 || blank($data['start_date'] ?? null) || blank($data['end_date'] ?? null)) {
            throw new DomainException('An accounting period requires a name, financial year and date range.');
        }

        return DB::transaction(function () use ($business, $actor, $data): AccountingPeriod {
            Business::query()->lockForUpdate()->findOrFail($business->id);
            $this->guard->assertNoOverlap($business->id, (string) $data['start_date'], (string) $data['end_date']);

            return AccountingPeriod::withoutGlobalScopes()->create([
                ...Arr::only($data, ['name', 'financial_year', 'period_number', 'start_date', 'end_date']),
                'business_id' => $business->id,
                'status' => AccountingPeriod::STATUS_OPEN,
                'created_by' => $actor->id,
                'transition_history' => [],
            ]);
        });
    }

    private function assertMembership(string $businessId, User $actor): void
    {
        if (! $actor->businessMemberships()->where('business_id', $businessId)->where('is_active', true)->exists()) {
            throw new DomainException('The actor does not belong to the accounting period business.');
        }
    }
}
