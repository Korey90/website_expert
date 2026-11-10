<?php

namespace App\Actions\Finance;

use App\Models\Business;
use App\Models\User;
use App\Models\VatAdjustment;
use App\Services\Finance\VatAdjustmentPayloadValidator;
use App\Support\PermissionHelper;
use DomainException;
use Illuminate\Support\Facades\DB;

class UpdateVatAdjustmentAction
{
    public function __construct(private readonly VatAdjustmentPayloadValidator $validator) {}

    /** @param array<string, mixed> $data */
    public function execute(VatAdjustment $adjustment, User $actor, array $data): VatAdjustment
    {
        return DB::transaction(function () use ($adjustment, $actor, $data): VatAdjustment {
            $locked = VatAdjustment::withoutGlobalScopes()->lockForUpdate()->findOrFail($adjustment->id);
            $this->assertAuthorized($locked, $actor);
            $business = Business::query()->findOrFail($locked->business_id);
            $locked->update($this->validator->normalize($business, $data));

            return $locked->fresh();
        });
    }

    private function assertAuthorized(VatAdjustment $adjustment, User $actor): void
    {
        if ($adjustment->status !== VatAdjustment::STATUS_DRAFT) {
            throw new DomainException('Only a draft VAT adjustment can be updated.');
        }

        if (! $actor->businessMemberships()->where('business_id', $adjustment->business_id)->where('is_active', true)->exists()) {
            throw new DomainException('The actor does not belong to the VAT adjustment business.');
        }

        if (! PermissionHelper::allows($actor, 'manage_finance')) {
            throw new DomainException('The actor cannot manage VAT adjustments.');
        }
    }
}
