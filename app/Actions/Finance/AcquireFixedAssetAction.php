<?php

namespace App\Actions\Finance;

use App\Models\Business;
use App\Models\ExpenseItem;
use App\Models\FixedAsset;
use App\Models\FixedAssetCategory;
use App\Models\FixedAssetTransaction;
use App\Models\User;
use App\Services\Finance\FixedAssetCostBasisService;
use App\Services\Finance\FixedAssetJournalPayloadFactory;
use App\Services\Finance\FixedAssetLifecycleGuard;
use DomainException;
use Illuminate\Support\Facades\DB;

class AcquireFixedAssetAction
{
    public function __construct(
        private readonly FixedAssetLifecycleGuard $guard,
        private readonly FixedAssetCostBasisService $costBasis,
        private readonly FixedAssetJournalPayloadFactory $payloads,
        private readonly PostJournalEntryAction $postJournal,
    ) {}

    public function execute(FixedAsset $asset, ExpenseItem $expenseItem, User $actor, string $inServiceDate): FixedAsset
    {
        return DB::transaction(function () use ($asset, $expenseItem, $actor, $inServiceDate): FixedAsset {
            $locked = FixedAsset::withoutGlobalScopes()->lockForUpdate()->findOrFail($asset->id);
            $business = Business::query()->findOrFail($locked->business_id);
            $this->guard->assertActor($business, $actor);
            $date = $this->guard->date($inServiceDate, 'in-service date');
            $source = ExpenseItem::withoutGlobalScopes()->lockForUpdate()->findOrFail($expenseItem->id);

            $existing = FixedAssetTransaction::withoutGlobalScopes()
                ->where('fixed_asset_id', $locked->id)
                ->where('type', FixedAssetTransaction::TYPE_ACQUISITION)
                ->whereIn('status', [
                    FixedAssetTransaction::STATUS_PREPARED,
                    FixedAssetTransaction::STATUS_POSTED,
                ])
                ->first();

            if ($existing) {
                if ($existing->status === FixedAssetTransaction::STATUS_POSTED
                    && $existing->expense_item_id === $source->id
                    && $existing->event_date->isSameDay($date)) {
                    return $locked->fresh(['category', 'transactions.journalEntry']);
                }

                throw new DomainException('This fixed asset already has an acquisition transaction.');
            }

            if ($locked->status !== FixedAsset::STATUS_DRAFT) {
                throw new DomainException('Only a draft fixed asset can be acquired and activated.');
            }

            $category = FixedAssetCategory::withoutGlobalScopes()->lockForUpdate()->find($locked->fixed_asset_category_id);
            if (! $category || $category->business_id !== $locked->business_id || ! $category->is_active) {
                throw new DomainException('The fixed asset requires an active category from the same business.');
            }

            $expense = $this->guard->assertExpenseSource($source, $locked->business_id);
            $taxPoint = $expense->tax_point ?? $expense->issue_date;
            if (! $taxPoint || $date->lt($taxPoint)) {
                throw new DomainException('The in-service date cannot predate the expense tax point.');
            }

            $this->assertUnusedSource($source, $locked);
            $amount = $this->costBasis->forExpenseItem($source);
            $transaction = FixedAssetTransaction::withoutGlobalScopes()->create([
                'business_id' => $locked->business_id,
                'fixed_asset_id' => $locked->id,
                'type' => FixedAssetTransaction::TYPE_ACQUISITION,
                'status' => FixedAssetTransaction::STATUS_PREPARED,
                'event_date' => $date->toDateString(),
                'expense_item_id' => $source->id,
                'amount_gbp' => $amount,
                'created_by' => $actor->id,
            ]);

            $journal = $this->postJournal->execute($business, $actor, $this->payloads->forCostTransaction($transaction));
            $transaction->update(['status' => FixedAssetTransaction::STATUS_POSTED, 'journal_entry_id' => $journal->id]);

            DB::table('fixed_assets')->where('id', $locked->id)->update([
                'status' => FixedAsset::STATUS_ACTIVE,
                'acquisition_date' => $taxPoint->toDateString(),
                'in_service_date' => $date->toDateString(),
                'acquisition_cost_gbp' => $amount,
                'accumulated_depreciation_gbp' => '0.00',
                'activated_by' => $actor->id,
                'activated_at' => now(),
                'updated_by' => $actor->id,
                'updated_at' => now(),
            ]);

            return $locked->fresh(['category', 'transactions.journalEntry']);
        }, 3);
    }

    private function assertUnusedSource(ExpenseItem $source, FixedAsset $asset): void
    {
        $existing = FixedAssetTransaction::withoutGlobalScopes()->where('expense_item_id', $source->id)->first();

        if ($existing) {
            throw new DomainException('This expense item is already linked to a fixed asset transaction.');
        }
    }
}
