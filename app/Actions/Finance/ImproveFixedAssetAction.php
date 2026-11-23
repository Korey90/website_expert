<?php

namespace App\Actions\Finance;

use App\Models\Business;
use App\Models\ExpenseItem;
use App\Models\FixedAsset;
use App\Models\FixedAssetDepreciationEntry;
use App\Models\FixedAssetTransaction;
use App\Models\User;
use App\Services\Finance\FixedAssetCostBasisService;
use App\Services\Finance\FixedAssetJournalPayloadFactory;
use App\Services\Finance\FixedAssetLifecycleGuard;
use DomainException;
use Illuminate\Support\Facades\DB;

class ImproveFixedAssetAction
{
    public function __construct(
        private readonly FixedAssetLifecycleGuard $guard,
        private readonly FixedAssetCostBasisService $costBasis,
        private readonly FixedAssetJournalPayloadFactory $payloads,
        private readonly PostJournalEntryAction $postJournal,
    ) {}

    public function execute(FixedAsset $asset, ExpenseItem $expenseItem, User $actor, string $eventDate): FixedAsset
    {
        return DB::transaction(function () use ($asset, $expenseItem, $actor, $eventDate): FixedAsset {
            $locked = FixedAsset::withoutGlobalScopes()->lockForUpdate()->findOrFail($asset->id);
            $business = Business::query()->findOrFail($locked->business_id);
            $this->guard->assertActor($business, $actor);
            $date = $this->guard->date($eventDate, 'improvement date');
            $source = ExpenseItem::withoutGlobalScopes()->lockForUpdate()->findOrFail($expenseItem->id);
            $existing = FixedAssetTransaction::withoutGlobalScopes()->where('expense_item_id', $source->id)->first();

            if ($existing) {
                if ($existing->fixed_asset_id === $locked->id
                    && $existing->type === FixedAssetTransaction::TYPE_IMPROVEMENT
                    && $existing->status === FixedAssetTransaction::STATUS_POSTED
                    && $existing->event_date->isSameDay($date)) {
                    return $locked->fresh(['category', 'transactions.journalEntry']);
                }

                throw new DomainException('This expense item is already linked to a fixed asset transaction.');
            }

            if ($locked->status !== FixedAsset::STATUS_ACTIVE) {
                throw new DomainException('Only an active fixed asset can receive an improvement.');
            }

            if (! $locked->category?->is_active) {
                throw new DomainException('The fixed asset category must remain active.');
            }

            $expense = $this->guard->assertExpenseSource($source, $locked->business_id);
            $taxPoint = $expense->tax_point ?? $expense->issue_date;
            if (! $taxPoint || $date->lt($taxPoint) || ($locked->in_service_date && $date->lt($locked->in_service_date))) {
                throw new DomainException('The improvement date cannot predate its expense tax point or the asset in-service date.');
            }

            if (FixedAssetDepreciationEntry::withoutGlobalScopes()
                ->where('fixed_asset_id', $locked->id)
                ->where('status', FixedAssetDepreciationEntry::STATUS_POSTED)
                ->whereDate('period_end', '>=', $date)
                ->exists()) {
                throw new DomainException('Reverse depreciation covering or following the improvement date first.');
            }

            FixedAssetDepreciationEntry::withoutGlobalScopes()
                ->where('fixed_asset_id', $locked->id)
                ->where('status', FixedAssetDepreciationEntry::STATUS_PLANNED)
                ->get()
                ->each->delete();

            $amount = $this->costBasis->forExpenseItem($source);
            $transaction = FixedAssetTransaction::withoutGlobalScopes()->create([
                'business_id' => $locked->business_id,
                'fixed_asset_id' => $locked->id,
                'type' => FixedAssetTransaction::TYPE_IMPROVEMENT,
                'status' => FixedAssetTransaction::STATUS_PREPARED,
                'event_date' => $date->toDateString(),
                'expense_item_id' => $source->id,
                'amount_gbp' => $amount,
                'created_by' => $actor->id,
            ]);
            $journal = $this->postJournal->execute($business, $actor, $this->payloads->forCostTransaction($transaction));
            $transaction->update(['status' => FixedAssetTransaction::STATUS_POSTED, 'journal_entry_id' => $journal->id]);

            $newCost = $this->costBasis->fromMinorUnits(
                $this->costBasis->toMinorUnits($locked->acquisition_cost_gbp, 'asset acquisition cost')
                + $this->costBasis->toMinorUnits($amount, 'improvement cost'),
            );
            DB::table('fixed_assets')->where('id', $locked->id)->update([
                'acquisition_cost_gbp' => $newCost,
                'updated_by' => $actor->id,
                'updated_at' => now(),
            ]);

            return $locked->fresh(['category', 'transactions.journalEntry']);
        }, 3);
    }
}
