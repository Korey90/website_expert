<?php

namespace App\Actions\Finance;

use App\Models\BankAccount;
use App\Models\Business;
use App\Models\User;
use App\Services\Finance\BankAccountDataNormalizer;
use App\Support\PermissionHelper;
use DomainException;
use Illuminate\Support\Facades\DB;

class CreateBankAccountAction
{
    public function __construct(private readonly BankAccountDataNormalizer $normalizer) {}

    /** @param array<string, mixed> $data */
    public function execute(Business $business, User $actor, array $data): BankAccount
    {
        $this->assertAuthorized($business->id, $actor);

        return DB::transaction(function () use ($business, $actor, $data): BankAccount {
            Business::query()->lockForUpdate()->findOrFail($business->id);
            $attributes = $this->normalizer->normalize($business, $data);

            return BankAccount::withoutGlobalScopes()->create([
                ...$attributes,
                'created_by' => $actor->id,
                'archived_by' => $attributes['status'] === BankAccount::STATUS_ARCHIVED ? $actor->id : null,
                'archived_at' => $attributes['status'] === BankAccount::STATUS_ARCHIVED ? now() : null,
            ]);
        });
    }

    private function assertAuthorized(string $businessId, User $actor): void
    {
        if (! $actor->businessMemberships()->where('business_id', $businessId)->where('is_active', true)->exists()) {
            throw new DomainException('The actor does not belong to the bank account business.');
        }

        if (! PermissionHelper::allows($actor, 'manage_finance')) {
            throw new DomainException('The actor cannot manage bank accounts.');
        }
    }
}
