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

            if ($locked->status !== PurchaseCreditNote::STATUS_DRAFT) {
                throw new DomainException('An issued purchase credit note must be reversed and cannot be voided.');
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
