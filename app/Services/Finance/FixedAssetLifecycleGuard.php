<?php

namespace App\Services\Finance;

use App\Models\Business;
use App\Models\CreditNote;
use App\Models\Expense;
use App\Models\ExpenseItem;
use App\Models\FinancialAttachment;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\JournalEntry;
use App\Models\PurchaseCreditNote;
use App\Models\User;
use App\Support\PermissionHelper;
use Carbon\CarbonImmutable;
use DomainException;
use Illuminate\Support\Facades\DB;

class FixedAssetLifecycleGuard
{
    public function assertActor(Business $business, User $actor): void
    {
        if (! $actor->businessMemberships()->where('business_id', $business->id)->where('is_active', true)->exists()) {
            throw new DomainException('The actor does not belong to the fixed asset business.');
        }

        if (! PermissionHelper::allows($actor, 'post_finance')) {
            throw new DomainException('The actor cannot post fixed asset transactions.');
        }
    }

    public function date(string $value, string $label): CarbonImmutable
    {
        $value = trim($value);

        try {
            $date = CarbonImmutable::createFromFormat('!Y-m-d', $value);
        } catch (\Throwable) {
            $date = null;
        }

        if (! $date || $date->format('Y-m-d') !== $value) {
            throw new DomainException("The {$label} must use a valid YYYY-MM-DD value.");
        }

        return $date;
    }

    public function assertExpenseSource(ExpenseItem $item, string $businessId): Expense
    {
        $item->loadMissing(['expense', 'taxCode', 'ledgerAccount']);
        $expense = $item->expense;

        if (! $expense || $item->business_id !== $businessId || $expense->business_id !== $businessId) {
            throw new DomainException('The expense item must belong to the fixed asset business.');
        }

        if (! in_array($expense->status, [Expense::STATUS_POSTED, Expense::STATUS_PARTIALLY_PAID, Expense::STATUS_PAID], true)) {
            throw new DomainException('The expense must be posted before its item can be capitalised.');
        }

        if (! $item->ledgerAccount || $item->ledgerAccount->business_id !== $businessId
            || ! $item->ledgerAccount->is_active || ! $item->ledgerAccount->is_postable) {
            throw new DomainException('The expense item requires an active postable source account.');
        }

        if (! $this->hasActivePostedJournal($expense)) {
            throw new DomainException('The expense does not have an active posted purchase journal.');
        }

        $hasCredit = DB::table('purchase_credit_note_items')
            ->join('purchase_credit_notes', 'purchase_credit_notes.id', '=', 'purchase_credit_note_items.purchase_credit_note_id')
            ->where('purchase_credit_note_items.expense_item_id', $item->id)
            ->where('purchase_credit_notes.status', '!=', PurchaseCreditNote::STATUS_VOID)
            ->exists();

        if ($hasCredit) {
            throw new DomainException('An expense item with an active purchase credit note cannot be capitalised.');
        }

        $hasCleanEvidence = FinancialAttachment::withoutGlobalScopes()
            ->where('business_id', $businessId)
            ->where('attachable_type', $expense->getMorphClass())
            ->where('attachable_id', $expense->id)
            ->where('scan_status', FinancialAttachment::SCAN_CLEAN)
            ->exists();

        if (! $hasCleanEvidence) {
            throw new DomainException('A clean scanned expense document is required before capitalisation.');
        }

        return $expense;
    }

    public function assertInvoiceSource(InvoiceItem $item, string $businessId): Invoice
    {
        $item->loadMissing('invoice');
        $invoice = $item->invoice;

        if (! $invoice || $item->business_id !== $businessId || $invoice->business_id !== $businessId) {
            throw new DomainException('The invoice item must belong to the fixed asset business.');
        }

        if (! in_array($invoice->status, ['sent', 'partially_paid', 'paid', 'overdue'], true)) {
            throw new DomainException('The invoice must be issued before its item can document a disposal.');
        }

        if (! $this->hasActivePostedJournal($invoice)) {
            throw new DomainException('The invoice does not have an active posted sales journal.');
        }

        $hasCredit = DB::table('credit_note_items')
            ->join('credit_notes', 'credit_notes.id', '=', 'credit_note_items.credit_note_id')
            ->where('credit_note_items.invoice_item_id', $item->id)
            ->where('credit_notes.status', '!=', CreditNote::STATUS_VOID)
            ->exists();

        if ($hasCredit) {
            throw new DomainException('An invoice item with an active credit note cannot document a disposal.');
        }

        return $invoice;
    }

    private function hasActivePostedJournal(Expense|Invoice $document): bool
    {
        return JournalEntry::withoutGlobalScopes()
            ->where('business_id', $document->business_id)
            ->where('source_type', $document->getMorphClass())
            ->where('source_id', $document->id)
            ->where('status', JournalEntry::STATUS_POSTED)
            ->whereNull('reversal_of_id')
            ->exists();
    }
}
