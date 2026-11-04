<?php

namespace App\Actions\Finance;

use App\Models\CreditNote;
use App\Models\Invoice;
use App\Models\User;
use DomainException;
use Illuminate\Support\Facades\DB;

class AllocateCreditNoteAction
{
    public function execute(CreditNote $creditNote, User $actor, float $amount): CreditNote
    {
        return DB::transaction(function () use ($creditNote, $actor, $amount): CreditNote {
            $locked = CreditNote::withoutGlobalScopes()->lockForUpdate()->findOrFail($creditNote->id);

            if (! in_array($locked->status, [CreditNote::STATUS_ISSUED, CreditNote::STATUS_PARTIALLY_ALLOCATED], true)) {
                throw new DomainException('Only an issued credit note can be allocated.');
            }

            $amount = round($amount, 2);
            $newAllocated = round((float) $locked->allocated_amount + $amount, 2);

            if ($amount <= 0 || $newAllocated > (float) $locked->total) {
                throw new DomainException('Allocation exceeds the remaining credit note balance.');
            }

            $fullyAllocated = $newAllocated === round((float) $locked->total, 2);
            $locked->update([
                'allocated_amount' => $newAllocated,
                'allocated_by' => $actor->id,
                'status' => $fullyAllocated ? CreditNote::STATUS_ALLOCATED : CreditNote::STATUS_PARTIALLY_ALLOCATED,
                'allocated_at' => $fullyAllocated ? now() : null,
            ]);

            $invoice = Invoice::withoutGlobalScopes()->lockForUpdate()->findOrFail($locked->invoice_id);
            $invoice->update([
                'amount_due' => max(0, round((float) $invoice->amount_due - $amount, 2)),
            ]);

            return $locked->fresh();
        });
    }
}
