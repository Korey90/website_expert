<?php

namespace App\Actions\Finance;

use App\Models\PurchaseCreditNote;
use App\Models\User;
use DomainException;
use Illuminate\Support\Facades\DB;

class IssuePurchaseCreditNoteAction
{
    public function execute(PurchaseCreditNote $creditNote, User $actor): PurchaseCreditNote
    {
        return DB::transaction(function () use ($creditNote, $actor): PurchaseCreditNote {
            $locked = PurchaseCreditNote::withoutGlobalScopes()->lockForUpdate()->findOrFail($creditNote->id);
            $this->assertActorMembership($actor, $locked->business_id);

            if ($locked->status !== PurchaseCreditNote::STATUS_DRAFT) {
                throw new DomainException('Only a draft purchase credit note can be issued.');
            }

            if ((float) $locked->total <= 0 || ! $locked->items()->exists()) {
                throw new DomainException('A purchase credit note must have a positive total and at least one item.');
            }

            $locked->update([
                'status' => PurchaseCreditNote::STATUS_ISSUED,
                'issued_by' => $actor->id,
                'issued_at' => now(),
            ]);

            return $locked->fresh();
        });
    }

    private function assertActorMembership(User $actor, string $businessId): void
    {
        if (! $actor->businessMemberships()->where('business_id', $businessId)->where('is_active', true)->exists()) {
            throw new DomainException('The actor does not belong to the purchase credit note business.');
        }
    }
}
