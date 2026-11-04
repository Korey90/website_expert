<?php

namespace App\Actions\Finance;

use App\Models\CreditNote;
use App\Models\User;
use DomainException;
use Illuminate\Support\Facades\DB;

class IssueCreditNoteAction
{
    public function execute(CreditNote $creditNote, User $actor): CreditNote
    {
        return DB::transaction(function () use ($creditNote, $actor): CreditNote {
            $locked = CreditNote::withoutGlobalScopes()->lockForUpdate()->findOrFail($creditNote->id);

            if ($locked->status !== CreditNote::STATUS_DRAFT) {
                throw new DomainException('Only a draft credit note can be issued.');
            }

            if ((float) $locked->total <= 0 || ! $locked->items()->exists()) {
                throw new DomainException('A credit note must have a positive total and at least one item.');
            }

            $locked->update([
                'status' => CreditNote::STATUS_ISSUED,
                'issued_by' => $actor->id,
                'issued_at' => now(),
            ]);

            return $locked->fresh();
        });
    }
}
