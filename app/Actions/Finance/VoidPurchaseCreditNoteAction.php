<?php

namespace App\Actions\Finance;

use App\Models\PurchaseCreditNote;
use App\Models\User;
use DomainException;
use Illuminate\Support\Facades\DB;

class VoidPurchaseCreditNoteAction
{
    public function execute(PurchaseCreditNote $creditNote, User $actor): PurchaseCreditNote
    {
        return DB::transaction(function () use ($creditNote, $actor): PurchaseCreditNote {
            $locked = PurchaseCreditNote::withoutGlobalScopes()->lockForUpdate()->findOrFail($creditNote->id);

            if (! $actor->businessMemberships()->where('business_id', $locked->business_id)->where('is_active', true)->exists()) {
                throw new DomainException('The actor does not belong to the purchase credit note business.');
            }

            if (! in_array($locked->status, [PurchaseCreditNote::STATUS_DRAFT, PurchaseCreditNote::STATUS_ISSUED], true)
                || (float) $locked->allocated_amount > 0
                || (float) $locked->refunded_amount > 0) {
                throw new DomainException('A settled purchase credit note cannot be voided.');
            }

            $locked->update([
                'status' => PurchaseCreditNote::STATUS_VOID,
                'voided_by' => $actor->id,
                'voided_at' => now(),
            ]);

            return $locked->fresh();
        });
    }
}
