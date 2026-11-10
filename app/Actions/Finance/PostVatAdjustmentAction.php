<?php

namespace App\Actions\Finance;

use App\Models\Business;
use App\Models\FinancialAttachment;
use App\Models\User;
use App\Models\VatAdjustment;
use App\Services\Finance\VatAdjustmentPayloadValidator;
use App\Support\PermissionHelper;
use DomainException;
use Illuminate\Support\Facades\DB;

class PostVatAdjustmentAction
{
    public function __construct(
        private readonly VatAdjustmentPayloadValidator $validator,
        private readonly PostJournalEntryAction $postJournal,
    ) {}

    public function execute(VatAdjustment $adjustment, User $actor): VatAdjustment
    {
        return DB::transaction(function () use ($adjustment, $actor): VatAdjustment {
            Business::query()->lockForUpdate()->findOrFail($adjustment->business_id);
            $locked = VatAdjustment::withoutGlobalScopes()->lockForUpdate()->findOrFail($adjustment->id);

            if (! $actor->businessMemberships()->where('business_id', $locked->business_id)->where('is_active', true)->exists()) {
                throw new DomainException('The actor does not belong to the VAT adjustment business.');
            }

            if (! PermissionHelper::allows($actor, 'post_finance')) {
                throw new DomainException('The actor cannot post VAT adjustments.');
            }

            if ($locked->status === VatAdjustment::STATUS_POSTED) {
                return $locked->fresh(['journalEntries.lines', 'attachments']);
            }

            if ($locked->status !== VatAdjustment::STATUS_APPROVED) {
                throw new DomainException('Only an approved VAT adjustment can be posted.');
            }

            $this->validator->validateStored($locked);
            $attachments = $locked->attachments()
                ->where('document_type', 'vat_adjustment_evidence')
                ->where('scan_status', FinancialAttachment::SCAN_CLEAN)
                ->orderBy('id')
                ->get();

            if ($attachments->isEmpty()) {
                throw new DomainException('The approved VAT adjustment no longer has clean evidence.');
            }

            $business = Business::query()->findOrFail($locked->business_id);
            $this->postJournal->execute($business, $actor, $this->journalPayload($locked, $attachments->map(fn (FinancialAttachment $attachment): array => [
                'id' => $attachment->id,
                'sha256' => $attachment->sha256,
                'document_type' => $attachment->document_type,
                'original_name' => $attachment->original_name,
            ])->all()));

            $locked->update([
                'status' => VatAdjustment::STATUS_POSTED,
                'posted_by' => $actor->id,
                'posted_at' => now(),
            ]);

            return $locked->fresh(['journalEntries.lines', 'attachments']);
        }, 3);
    }

    /** @param list<array<string, mixed>> $evidence */
    private function journalPayload(VatAdjustment $adjustment, array $evidence): array
    {
        $vatIsDebit = ($adjustment->vat_side === VatAdjustment::SIDE_OUTPUT && $adjustment->direction === VatAdjustment::DIRECTION_DECREASE)
            || ($adjustment->vat_side === VatAdjustment::SIDE_INPUT && $adjustment->direction === VatAdjustment::DIRECTION_INCREASE);
        $amount = (string) $adjustment->amount_gbp;
        $zero = '0.00';

        return [
            'entry_date' => $adjustment->effective_date->toDateString(),
            'description' => "VAT adjustment {$adjustment->number}",
            'idempotency_key' => "vat-adjustment:{$adjustment->id}:post",
            'source' => $adjustment,
            'source_snapshot' => [
                'number' => $adjustment->number,
                'adjustment_type' => $adjustment->adjustment_type,
                'vat_side' => $adjustment->vat_side,
                'direction' => $adjustment->direction,
                'correction_method' => $adjustment->correction_method,
                'effective_date' => $adjustment->effective_date->toDateString(),
                'discovered_on' => $adjustment->discovered_on?->toDateString(),
                'original_period_from' => $adjustment->original_period_from?->toDateString(),
                'original_period_to' => $adjustment->original_period_to?->toDateString(),
                'amount_gbp' => $amount,
                'is_deliberate' => $adjustment->is_deliberate,
                'reason' => $adjustment->reason,
                'hmrc_reference' => $adjustment->hmrc_reference,
                'tax_code_id' => $adjustment->tax_code_id,
                'control_ledger_account_id' => $adjustment->control_ledger_account_id,
                'contra_ledger_account_id' => $adjustment->contra_ledger_account_id,
                'evidence' => $evidence,
            ],
            'lines' => [
                [
                    'ledger_account_id' => $adjustment->control_ledger_account_id,
                    'tax_code_id' => $adjustment->tax_code_id,
                    'debit_gbp' => $vatIsDebit ? $amount : $zero,
                    'credit_gbp' => $vatIsDebit ? $zero : $amount,
                    'memo' => "{$adjustment->number} VAT control",
                    'dimensions' => ['vat_adjustment' => $adjustment->number, 'vat_side' => $adjustment->vat_side],
                ],
                [
                    'ledger_account_id' => $adjustment->contra_ledger_account_id,
                    'debit_gbp' => $vatIsDebit ? $zero : $amount,
                    'credit_gbp' => $vatIsDebit ? $amount : $zero,
                    'memo' => "{$adjustment->number} contra",
                    'dimensions' => ['vat_adjustment' => $adjustment->number],
                ],
            ],
        ];
    }
}
