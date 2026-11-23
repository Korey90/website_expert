<?php

namespace App\Actions\Finance;

use App\Models\Business;
use App\Models\FixedAsset;
use App\Models\FixedAssetDepreciationEntry;
use App\Models\JournalEntry;
use App\Models\User;
use App\Services\Finance\FixedAssetLifecycleGuard;
use DomainException;
use Illuminate\Support\Facades\DB;

class ReverseFixedAssetDepreciationAction
{
    public function __construct(
        private readonly FixedAssetLifecycleGuard $guard,
        private readonly ReverseJournalEntryAction $reverseJournal,
    ) {}

    public function execute(
        FixedAssetDepreciationEntry $entry,
        User $actor,
        string $reason,
        ?string $reversalDate = null,
    ): FixedAssetDepreciationEntry {
        return DB::transaction(function () use ($entry, $actor, $reason, $reversalDate): FixedAssetDepreciationEntry {
            $locked = FixedAssetDepreciationEntry::withoutGlobalScopes()->lockForUpdate()->findOrFail($entry->id);
            $asset = FixedAsset::withoutGlobalScopes()->lockForUpdate()->findOrFail($locked->fixed_asset_id);
            $business = Business::query()->lockForUpdate()->findOrFail($locked->business_id);
            $this->guard->assertActor($business, $actor);
            $reason = trim($reason);

            if ($reason === '' || mb_strlen($reason) > 2000) {
                throw new DomainException('A reversal reason between 1 and 2000 characters is required.');
            }

            if ($locked->status === FixedAssetDepreciationEntry::STATUS_REVERSED) {
                if ($locked->reversal_reason !== $reason) {
                    throw new DomainException('The depreciation entry was already reversed for a different reason.');
                }

                return $locked->load(['journalEntry', 'reversalJournalEntry']);
            }

            if ($asset->status === FixedAsset::STATUS_DISPOSED) {
                throw new DomainException('Reverse the fixed asset disposal before reversing depreciation.');
            }

            if ($locked->status !== FixedAssetDepreciationEntry::STATUS_POSTED || ! $locked->journal_entry_id) {
                throw new DomainException('Only a posted depreciation entry can be reversed.');
            }

            $latest = FixedAssetDepreciationEntry::withoutGlobalScopes()
                ->where('fixed_asset_id', $asset->id)
                ->where('status', FixedAssetDepreciationEntry::STATUS_POSTED)
                ->orderByDesc('period_end')->orderByDesc('id')->first();
            if (! $latest?->is($locked)) {
                throw new DomainException('Reverse the later depreciation period first.');
            }

            $journal = JournalEntry::withoutGlobalScopes()->findOrFail($locked->journal_entry_id);
            $reversal = $this->reverseJournal->execute($journal, $actor, $reason, $reversalDate);
            DB::table('fixed_asset_depreciation_entries')->where('id', $locked->id)->update([
                'status' => FixedAssetDepreciationEntry::STATUS_REVERSED,
                'reversal_journal_entry_id' => $reversal->id,
                'reversed_by' => $actor->id,
                'reversed_at' => now(),
                'reversal_reason' => $reason,
                'updated_at' => now(),
            ]);
            FixedAssetDepreciationEntry::withoutGlobalScopes()
                ->where('fixed_asset_id', $asset->id)
                ->where('status', FixedAssetDepreciationEntry::STATUS_PLANNED)
                ->get()->each->delete();
            DB::table('fixed_assets')->where('id', $asset->id)->update([
                'accumulated_depreciation_gbp' => $locked->opening_accumulated_depreciation_gbp,
                'updated_by' => $actor->id,
                'updated_at' => now(),
            ]);

            return $locked->fresh(['journalEntry', 'reversalJournalEntry']);
        }, 3);
    }
}
