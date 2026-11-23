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

        $structured = match ($item->source_type) {
            (new Payment)->getMorphClass() => Payment::withoutGlobalScopes()
                ->where('business_id', $item->business_id)->whereKey($item->source_id)->exists(),
            (new ExpensePayment)->getMorphClass() => ExpensePayment::withoutGlobalScopes()
                ->where('business_id', $item->business_id)->whereKey($item->source_id)->exists(),
            (new PurchaseCreditRefund)->getMorphClass() => PurchaseCreditRefund::withoutGlobalScopes()
                ->where('business_id', $item->business_id)->whereKey($item->source_id)->exists(),
            default => false,
        };

        return $structured
            ? ['state' => 'structured_record', 'attachment_ids' => [], 'attachment_hashes' => [], 'scan_statuses' => []]
            : $this->missing('source_not_retained');
    }

    /** @return array<string, mixed> */
    public function forSimplified(UniversalCreditSimplifiedExpense $expense): array
    {
        return $this->fromAttachable($expense, $expense->evidence_missing_reason);
    }

    /** @return array<string, mixed> */
    private function fromAttachable(Model $model, ?string $missingReason, string $cleanState = 'clean_attachment'): array
    {
        $attachments = $model->attachments()->withoutGlobalScopes()
            ->where('business_id', $model->business_id)
            ->orderBy('id')
            ->get(['id', 'sha256', 'scan_status']);
        $clean = $attachments->where('scan_status', FinancialAttachment::SCAN_CLEAN);

        if ($clean->isNotEmpty()) {
            return [
                'state' => $cleanState,
                'attachment_ids' => $clean->pluck('id')->values()->all(),
                'attachment_hashes' => $clean->pluck('sha256')->values()->all(),
                'scan_statuses' => $attachments->pluck('scan_status')->values()->all(),
            ];
        }

        if ($attachments->isNotEmpty()) {
            return [
                'state' => 'attachment_not_clean',
                'attachment_ids' => $attachments->pluck('id')->values()->all(),
                'attachment_hashes' => $attachments->pluck('sha256')->values()->all(),
                'scan_statuses' => $attachments->pluck('scan_status')->values()->all(),
            ];
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
