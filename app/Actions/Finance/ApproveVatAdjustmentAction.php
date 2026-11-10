<?php

namespace App\Actions\Finance;

use App\Models\FinancialAttachment;
use App\Models\User;
use App\Models\VatAdjustment;
use App\Services\Finance\VatAdjustmentPayloadValidator;
use App\Support\PermissionHelper;
use DomainException;
use Illuminate\Support\Facades\DB;

class ApproveVatAdjustmentAction
{
    public function __construct(private readonly VatAdjustmentPayloadValidator $validator) {}

    public function execute(VatAdjustment $adjustment, User $actor): VatAdjustment
    {
        return DB::transaction(function () use ($adjustment, $actor): VatAdjustment {
            $locked = VatAdjustment::withoutGlobalScopes()->lockForUpdate()->findOrFail($adjustment->id);
            $this->assertAuthorized($locked, $actor);
            $this->validator->validateStored($locked);

            $hasCleanEvidence = $locked->attachments()
                ->where('document_type', 'vat_adjustment_evidence')
                ->where('scan_status', FinancialAttachment::SCAN_CLEAN)
                ->exists();

            if (! $hasCleanEvidence) {
                throw new DomainException('A clean VAT adjustment evidence attachment is required before approval.');
            }

            $locked->update([
                'status' => VatAdjustment::STATUS_APPROVED,
                'approved_by' => $actor->id,
                'approved_at' => now(),
            ]);

            return $locked->fresh(['attachments']);
        });
    }

    private function assertAuthorized(VatAdjustment $adjustment, User $actor): void
    {
        if ($adjustment->status !== VatAdjustment::STATUS_DRAFT) {
            throw new DomainException('Only a draft VAT adjustment can be approved.');
        }

        if (! $actor->businessMemberships()->where('business_id', $adjustment->business_id)->where('is_active', true)->exists()) {
            throw new DomainException('The actor does not belong to the VAT adjustment business.');
        }

        if (! PermissionHelper::allows($actor, 'approve_finance')) {
            throw new DomainException('The actor cannot approve VAT adjustments.');
        }
    }
}
