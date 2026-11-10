<?php

namespace App\Actions\Finance;

use App\Models\Business;
use App\Models\User;
use App\Models\VatAdjustment;
use App\Services\Finance\VatAdjustmentPayloadValidator;
use App\Support\PermissionHelper;
use Carbon\CarbonImmutable;
use DomainException;
use Illuminate\Support\Facades\DB;

class CreateVatAdjustmentAction
{
    public function __construct(
        private readonly GenerateDocumentNumberAction $generateDocumentNumber,
        private readonly VatAdjustmentPayloadValidator $validator,
    ) {}

    /** @param array<string, mixed> $data */
    public function execute(Business $business, User $actor, array $data): VatAdjustment
    {
        $this->assertAuthorized($business->id, $actor);
        $normalized = $this->validator->normalize($business, $data);

        return DB::transaction(function () use ($business, $actor, $normalized): VatAdjustment {
            Business::query()->lockForUpdate()->findOrFail($business->id);

            return VatAdjustment::withoutGlobalScopes()->create([
                ...$normalized,
                'business_id' => $business->id,
                'number' => $this->generateDocumentNumber->execute(
                    $business->id,
                    'vat_adjustment',
                    CarbonImmutable::parse($normalized['effective_date']),
                ),
                'status' => VatAdjustment::STATUS_DRAFT,
                'created_by' => $actor->id,
            ]);
        }, 3);
    }

    private function assertAuthorized(string $businessId, User $actor): void
    {
        if (! $actor->businessMemberships()->where('business_id', $businessId)->where('is_active', true)->exists()) {
            throw new DomainException('The actor does not belong to the VAT adjustment business.');
        }

        if (! PermissionHelper::allows($actor, 'manage_finance')) {
            throw new DomainException('The actor cannot manage VAT adjustments.');
        }
    }
}
