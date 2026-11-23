<?php

namespace App\Actions\Finance;

use App\Models\Business;
use App\Models\FixedAsset;
use App\Models\FixedAssetDepreciationEntry;
use App\Models\FixedAssetTransaction;
use App\Models\JournalEntry;
use App\Models\User;
use App\Services\Finance\FixedAssetCostBasisService;
use App\Services\Finance\FixedAssetLifecycleGuard;
use DomainException;
use Illuminate\Support\Facades\DB;

class ReverseFixedAssetTransactionAction
{
    public function __construct(
        private readonly FixedAssetLifecycleGuard $guard,
        private readonly FixedAssetCostBasisService $money,
        private readonly ReverseJournalEntryAction $reverseJournal,
    ) {}

    public function execute(
        FixedAssetTransaction $transaction,
        User $actor,
        string $reason,
        ?string $reversalDate = null,
    ): FixedAsset {
        return DB::transaction(function () use ($transaction, $actor, $reason, $reversalDate): FixedAsset {
            $locked = FixedAssetTransaction::withoutGlobalScopes()->lockForUpdate()->findOrFail($transaction->id);
            $asset = FixedAsset::withoutGlobalScopes()->lockForUpdate()->findOrFail($locked->fixed_asset_id);
            $business = Business::query()->findOrFail($locked->business_id);
            $this->guard->assertActor($business, $actor);
            $reason = trim($reason);

            if ($reason === '' || mb_strlen($reason) > 2000) {
                throw new DomainException('A reversal reason between 1 and 2000 characters is required.');
            }

            if ($locked->status === FixedAssetTransaction::STATUS_REVERSED) {
                if ($locked->reversal_reason !== $reason) {
                    throw new DomainException('The fixed asset transaction was already reversed for a different reason.');
                }

                return $asset->fresh(['category', 'transactions.journalEntry', 'transactions.reversalJournalEntry']);
            }

            if ($locked->status !== FixedAssetTransaction::STATUS_POSTED || ! $locked->journal_entry_id) {
                throw new DomainException('Only a posted fixed asset transaction can be reversed.');
            }

            $hasLaterTransaction = FixedAssetTransaction::withoutGlobalScopes()
                ->where('fixed_asset_id', $asset->id)
                ->where('status', FixedAssetTransaction::STATUS_POSTED)
                ->where(function ($query) use ($locked): void {
                    $query->whereDate('event_date', '>', $locked->event_date)
                        ->orWhere(function ($sameDate) use ($locked): void {
                            $sameDate->whereDate('event_date', $locked->event_date)->where('id', '>', $locked->id);
                        });
                })
                ->exists();

            if ($hasLaterTransaction) {
                throw new DomainException('Reverse the later fixed asset transaction first.');
            }

            $dependentDepreciation = FixedAssetDepreciationEntry::withoutGlobalScopes()
                ->where('fixed_asset_id', $asset->id)
                ->where('status', FixedAssetDepreciationEntry::STATUS_POSTED)
                ->when(
                    $locked->type === FixedAssetTransaction::TYPE_IMPROVEMENT,
                    fn ($query) => $query->whereDate('period_end', '>=', $locked->event_date),
                )
                ->when(
                    $locked->type === FixedAssetTransaction::TYPE_DISPOSAL,
                    fn ($query) => $query->whereRaw('1 = 0'),
                )
                ->exists();

            if ($dependentDepreciation) {
                throw new DomainException('Reverse dependent fixed asset depreciation entries first.');
            }

            FixedAssetDepreciationEntry::withoutGlobalScopes()
                ->where('fixed_asset_id', $asset->id)
                ->where('status', FixedAssetDepreciationEntry::STATUS_PLANNED)
                ->get()
                ->each->delete();

            $journal = JournalEntry::withoutGlobalScopes()->findOrFail($locked->journal_entry_id);
            $reversal = $this->reverseJournal->execute($journal, $actor, $reason, $reversalDate);
            DB::table('fixed_asset_transactions')->where('id', $locked->id)->update([
                'status' => FixedAssetTransaction::STATUS_REVERSED,
                'reversal_journal_entry_id' => $reversal->id,
                'reversed_by' => $actor->id,
                'reversed_at' => now(),
                'reversal_reason' => $reason,
                'updated_at' => now(),
            ]);

            $remaining = FixedAssetTransaction::withoutGlobalScopes()
                ->where('fixed_asset_id', $asset->id)
                ->where('status', FixedAssetTransaction::STATUS_POSTED)
                ->orderBy('event_date')->orderBy('id')->get();
            $acquisition = $remaining->firstWhere('type', FixedAssetTransaction::TYPE_ACQUISITION);

            if (! $acquisition) {
                $this->restoreDraft($asset, $actor);
            } else {
                $cost = $remaining
                    ->whereIn('type', [FixedAssetTransaction::TYPE_ACQUISITION, FixedAssetTransaction::TYPE_IMPROVEMENT])
                    ->sum(fn (FixedAssetTransaction $item): int => $this->money->toMinorUnits($item->amount_gbp));
                DB::table('fixed_assets')->where('id', $asset->id)->update([
                    'status' => FixedAsset::STATUS_ACTIVE,
                    'acquisition_cost_gbp' => $this->money->fromMinorUnits($cost),
                    'disposed_at' => null,
                    'disposal_proceeds_gbp' => null,
                    'disposal_gain_loss_gbp' => null,
                    'disposed_by' => null,
                    'disposal_recorded_at' => null,
                    'updated_by' => $actor->id,
                    'updated_at' => now(),
                ]);
            }

            return $asset->fresh(['category', 'transactions.journalEntry', 'transactions.reversalJournalEntry']);
        }, 3);
    }

    private function restoreDraft(FixedAsset $asset, User $actor): void
    {
        DB::table('fixed_assets')->where('id', $asset->id)->update([
            'status' => FixedAsset::STATUS_DRAFT,
            'acquisition_date' => null,
            'in_service_date' => null,
            'acquisition_cost_gbp' => null,
            'accumulated_depreciation_gbp' => '0.00',
            'disposed_at' => null,
            'disposal_proceeds_gbp' => null,
            'disposal_gain_loss_gbp' => null,
            'activated_by' => null,
            'activated_at' => null,
            'disposed_by' => null,
            'disposal_recorded_at' => null,
            'updated_by' => $actor->id,
            'updated_at' => now(),
        ]);
    }
}
