<?php

namespace App\Actions\Finance;

use App\Models\CreditNote;
use App\Models\User;
use DomainException;
use Illuminate\Support\Facades\DB;

class VoidCreditNoteAction
{
    public function execute(CreditNote $creditNote, User $actor): CreditNote
    {
        return DB::transaction(function () use ($creditNote, $actor): CreditNote {
            $locked = CreditNote::withoutGlobalScopes()->lockForUpdate()->findOrFail($creditNote->id);

            if (! in_array($locked->status, [CreditNote::STATUS_DRAFT, CreditNote::STATUS_ISSUED], true)) {
                throw new DomainException('An allocated or already void credit note cannot be voided.');
            }

            if ((float) $locked->allocated_amount > 0) {
                throw new DomainException('An allocated credit note cannot be voided.');
            }

            $locked->update([
                'status' => CreditNote::STATUS_VOID,
                'voided_by' => $actor->id,
                'voided_at' => now(),
            ]);

            return $locked->fresh();
        });
    }
}
