<?php

namespace App\Actions\Finance;

use App\Models\BankAccount;
use App\Models\Business;
use App\Models\User;
use App\Services\Finance\BankAccountDataNormalizer;
use App\Support\PermissionHelper;
use DomainException;
use Illuminate\Support\Facades\DB;

class UpdateBankAccountAction
{
    public function __construct(private readonly BankAccountDataNormalizer $normalizer) {}

    /** @param array<string, mixed> $data */
    public function execute(BankAccount $account, User $actor, array $data): BankAccount
    {
        return DB::transaction(function () use ($account, $actor, $data): BankAccount {
            $locked = BankAccount::withoutGlobalScopes()->lockForUpdate()->findOrFail($account->id);
            $this->assertAuthorized($locked, $actor);
            $business = Business::query()->lockForUpdate()->findOrFail($locked->business_id);
            $attributes = $this->normalizer->normalize($business, $data, $locked);
            $wasArchived = $locked->status === BankAccount::STATUS_ARCHIVED;
            $willBeArchived = $attributes['status'] === BankAccount::STATUS_ARCHIVED;

            $locked->update([
                ...$attributes,
                'archived_by' => $willBeArchived ? ($wasArchived ? $locked->archived_by : $actor->id) : null,
                'archived_at' => $willBeArchived ? ($locked->archived_at ?? now()) : null,
            ]);

            return $locked->fresh(['ledgerAccount', 'createdBy', 'archivedBy']);
        });
    }

    private function assertAuthorized(BankAccount $account, User $actor): void
    {
        if (! $actor->businessMemberships()->where('business_id', $account->business_id)->where('is_active', true)->exists()) {
            throw new DomainException('The actor does not belong to the bank account business.');
        }

        if (! PermissionHelper::allows($actor, 'manage_finance')) {
            throw new DomainException('The actor cannot manage bank accounts.');
        }
    }
}
