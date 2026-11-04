<?php

namespace App\Actions\Finance;

use App\Models\Business;
use App\Models\CreditNote;
use App\Models\User;
use DomainException;
use Illuminate\Support\Facades\DB;

class IssueCreditNoteAction
{
    public function __construct(private readonly PostSalesCreditNoteAction $postSalesCreditNote) {}

    public function execute(CreditNote $creditNote, User $actor): CreditNote
    {
        return DB::transaction(function () use ($creditNote, $actor): CreditNote {
            Business::query()->lockForUpdate()->findOrFail($creditNote->business_id);
            $locked = CreditNote::withoutGlobalScopes()->lockForUpdate()->findOrFail($creditNote->id);

            if (! in_array($locked->status, [CreditNote::STATUS_DRAFT, CreditNote::STATUS_ISSUED], true)) {
                throw new DomainException('Only a draft credit note can be issued.');
            }

            if ($locked->status === CreditNote::STATUS_DRAFT && ((float) $locked->total <= 0 || ! $locked->items()->exists())) {
                throw new DomainException('A credit note must have a positive total and at least one item.');
            }

            if ($locked->status === CreditNote::STATUS_DRAFT) {
                $locked->update([
                    'status' => CreditNote::STATUS_ISSUED,
                    'issued_by' => $actor->id,
                    'issued_at' => now(),
                ]);
            }

            $this->postSalesCreditNote->execute($locked, $actor);

            return $locked->fresh();
        }, 3);
    }
}
