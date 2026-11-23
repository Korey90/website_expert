<?php

namespace App\Actions\Finance;

use App\Models\Business;
use App\Models\FixedAsset;
use App\Models\FixedAssetDepreciationEntry;
use App\Models\User;
use App\Services\Finance\FixedAssetCostBasisService;
use App\Services\Finance\FixedAssetDepreciationJournalPayloadFactory;
use App\Services\Finance\FixedAssetLifecycleGuard;
use DomainException;
use Illuminate\Support\Facades\DB;

class PostFixedAssetDepreciationAction
{
    public function __construct(
        private readonly FixedAssetLifecycleGuard $guard,
        private readonly FixedAssetCostBasisService $money,
        private readonly FixedAssetDepreciationJournalPayloadFactory $payloads,
        private readonly PostJournalEntryAction $postJournal,
    ) {}

    public function execute(FixedAssetDepreciationEntry $entry, User $actor): FixedAssetDepreciationEntry
    {
        return DB::transaction(function () use ($entry, $actor): FixedAssetDepreciationEntry {
            $locked = FixedAssetDepreciationEntry::withoutGlobalScopes()->lockForUpdate()->findOrFail($entry->id);
            $asset = FixedAsset::withoutGlobalScopes()->lockForUpdate()->findOrFail($locked->fixed_asset_id);
            $business = Business::query()->lockForUpdate()->findOrFail($locked->business_id);
            $this->guard->assertActor($business, $actor);

            if ($locked->status === FixedAssetDepreciationEntry::STATUS_POSTED) {
                return $locked->load('journalEntry.lines');
            }

            if ($locked->status !== FixedAssetDepreciationEntry::STATUS_PLANNED) {
                throw new DomainException('Only a planned depreciation entry can be posted.');
            }

            if ($asset->status !== FixedAsset::STATUS_ACTIVE) {
                throw new DomainException('Depreciation can only be posted for an active fixed asset.');
            }

            if ($locked->posting_date->isFuture()) {
                throw new DomainException('A future depreciation period cannot be posted.');
            }

            $earlier = FixedAssetDepreciationEntry::withoutGlobalScopes()
                ->where('fixed_asset_id', $asset->id)
                ->where('status', FixedAssetDepreciationEntry::STATUS_PLANNED)
                ->where(function ($query) use ($locked): void {
                    $query->whereDate('period_end', '<', $locked->period_end)
                        ->orWhere(function ($sameDate) use ($locked): void {
                            $sameDate->whereDate('period_end', $locked->period_end)->where('id', '<', $locked->id);
                        });
                })->exists();

            if ($earlier) {
                throw new DomainException('Post the earlier depreciation period first.');
            }

            $currentAccumulated = $this->money->toMinorUnits($asset->accumulated_depreciation_gbp ?? '0.00');
            $expectedAccumulated = $this->money->toMinorUnits($locked->opening_accumulated_depreciation_gbp);
            if ($currentAccumulated !== $expectedAccumulated) {
                throw new DomainException('The depreciation schedule is stale and must be regenerated.');
            }

            $journal = $this->postJournal->execute($business, $actor, $this->payloads->forEntry($locked));
            $locked->update([
                'status' => FixedAssetDepreciationEntry::STATUS_POSTED,
                'journal_entry_id' => $journal->id,
                'posted_by' => $actor->id,
                'posted_at' => now(),
            ]);
            DB::table('fixed_assets')->where('id', $asset->id)->update([
                'accumulated_depreciation_gbp' => $locked->closing_accumulated_depreciation_gbp,
                'updated_by' => $actor->id,
                'updated_at' => now(),
            ]);

            return $locked->fresh(['journalEntry.lines']);
        }, 3);
    }
}
