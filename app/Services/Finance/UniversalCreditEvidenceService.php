<?php

namespace App\Services\Finance;

use App\Models\BankStatementImport;
use App\Models\BankTransaction;
use App\Models\ExpensePayment;
use App\Models\FinancialAttachment;
use App\Models\Payment;
use App\Models\PurchaseCreditRefund;
use App\Models\UniversalCreditManualEntry;
use App\Models\UniversalCreditReportItem;
use App\Models\UniversalCreditSimplifiedExpense;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

class UniversalCreditEvidenceService
{
    /** @return array<string, mixed> */
    public function forItem(UniversalCreditReportItem $item): array
    {
        if ($item->source_type === (new UniversalCreditManualEntry)->getMorphClass()) {
            $entry = UniversalCreditManualEntry::withoutGlobalScopes()
                ->where('business_id', $item->business_id)
                ->find($item->source_id);

            return $entry ? $this->fromAttachable($entry, $entry->evidence_missing_reason) : $this->missing('source_not_retained');
        }

        if ($item->source_type === (new BankTransaction)->getMorphClass()) {
            $importId = data_get($item->source_context, 'bank_statement_import_id');
            $import = $importId === null ? null : BankStatementImport::withoutGlobalScopes()
                ->where('business_id', $item->business_id)
                ->find($importId);

            return $import ? $this->fromAttachable($import, null, 'source_document') : $this->missing('source_not_retained');
        }

        if ($item->source_type === (new Payment)->getMorphClass()) {
            $payment = Payment::withoutGlobalScopes()->with('invoice')
                ->where('business_id', $item->business_id)->find($item->source_id);

            return $payment
                ? $this->fromStructuredRecords(collect([$payment, $payment->invoice]))
                : $this->missing('source_not_retained');
        }

        if ($item->source_type === (new ExpensePayment)->getMorphClass()) {
            $payment = ExpensePayment::withoutGlobalScopes()->with('expense')
                ->where('business_id', $item->business_id)->find($item->source_id);

            return $payment
                ? $this->fromStructuredRecords(collect([$payment, $payment->expense]))
                : $this->missing('source_not_retained');
        }

        if ($item->source_type === (new PurchaseCreditRefund)->getMorphClass()) {
            $refund = PurchaseCreditRefund::withoutGlobalScopes()->with(['purchaseCreditNote', 'expense'])
                ->where('business_id', $item->business_id)->find($item->source_id);

            return $refund
                ? $this->fromStructuredRecords(collect([$refund, $refund->purchaseCreditNote, $refund->expense]))
                : $this->missing('source_not_retained');
        }

        return $this->missing('source_not_retained');
    }

    /** @return array<string, mixed> */
    public function forSimplified(UniversalCreditSimplifiedExpense $expense): array
    {
        return $this->fromAttachable($expense, $expense->evidence_missing_reason);
    }

    /** @return array<string, mixed> */
    private function fromAttachable(Model $model, ?string $missingReason, string $cleanState = 'clean_attachment'): array
    {
        return $this->fromAttachables(collect([$model]), $missingReason, $cleanState);
    }

    /** @param Collection<int, Model|null> $models @return array<string, mixed> */
    private function fromStructuredRecords(Collection $models): array
    {
        return $this->fromAttachables($models, null, 'structured_record');
    }

    /** @param Collection<int, Model|null> $models @return array<string, mixed> */
    private function fromAttachables(
        Collection $models,
        ?string $missingReason,
        string $cleanState,
    ): array {
        $models = $models->filter(fn (?Model $model): bool => $model !== null && $model->exists)->values();
        $businessId = $models->first()?->getAttribute('business_id');
        $groups = $models
            ->filter(fn (Model $model): bool => $model->getAttribute('business_id') === $businessId)
            ->groupBy(fn (Model $model): string => $model->getMorphClass())
            ->map(fn (Collection $group): array => $group->map(fn (Model $model): mixed => $model->getKey())->all());
        $attachments = FinancialAttachment::withoutGlobalScopes()
            ->where('business_id', $businessId)
            ->whereNull('deleted_at')
            ->where(function ($query) use ($groups): void {
                foreach ($groups as $type => $ids) {
                    $query->orWhere(function ($source) use ($type, $ids): void {
                        $source->where('attachable_type', $type)->whereIn('attachable_id', $ids);
                    });
                }
            })
            ->orderBy('id')
            ->get(['id', 'sha256', 'scan_status']);
        $clean = $attachments->where('scan_status', FinancialAttachment::SCAN_CLEAN);

        if ($clean->isNotEmpty()) {
            return [
                'state' => $cleanState,
                'attachment_ids' => $attachments->pluck('id')->values()->all(),
                'attachment_hashes' => $attachments->pluck('sha256')->values()->all(),
                'scan_statuses' => $attachments->pluck('scan_status')->values()->all(),
            ];
        }

        if ($attachments->isNotEmpty()) {
            return [
                'state' => $cleanState === 'structured_record' ? $cleanState : 'attachment_not_clean',
                'attachment_ids' => $attachments->pluck('id')->values()->all(),
                'attachment_hashes' => $attachments->pluck('sha256')->values()->all(),
                'scan_statuses' => $attachments->pluck('scan_status')->values()->all(),
            ];
        }

        if ($cleanState === 'structured_record') {
            return ['state' => $cleanState, 'attachment_ids' => [], 'attachment_hashes' => [], 'scan_statuses' => []];
        }

        return filled($missingReason)
            ? ['state' => 'missing_with_reason', 'reason' => $missingReason, 'attachment_ids' => [], 'attachment_hashes' => [], 'scan_statuses' => []]
            : $this->missing('missing');
    }

    /** @return array<string, mixed> */
    private function missing(string $state): array
    {
        return ['state' => $state, 'attachment_ids' => [], 'attachment_hashes' => [], 'scan_statuses' => []];
    }
}
