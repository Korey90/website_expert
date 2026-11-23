<?php

namespace App\Actions\Finance;

use App\Models\Business;
use App\Models\FixedAsset;
use App\Models\FixedAssetTransaction;
use App\Models\InvoiceItem;
use App\Models\User;
use App\Services\Finance\FixedAssetCostBasisService;
use App\Services\Finance\FixedAssetJournalPayloadFactory;
use App\Services\Finance\FixedAssetLifecycleGuard;
use DomainException;
use Illuminate\Support\Facades\DB;

class DisposeFixedAssetAction
{
    public function __construct(
        private readonly FixedAssetLifecycleGuard $guard,
        private readonly FixedAssetCostBasisService $money,
        private readonly FixedAssetJournalPayloadFactory $payloads,
        private readonly PostJournalEntryAction $postJournal,
    ) {}

    public function execute(FixedAsset $asset, InvoiceItem $invoiceItem, User $actor, string $eventDate): FixedAsset
    {
        return DB::transaction(function () use ($asset, $invoiceItem, $actor, $eventDate): FixedAsset {
            $locked = FixedAsset::withoutGlobalScopes()->lockForUpdate()->findOrFail($asset->id);
            $business = Business::query()->findOrFail($locked->business_id);
            $this->guard->assertActor($business, $actor);
            $date = $this->guard->date($eventDate, 'disposal date');
            $source = InvoiceItem::withoutGlobalScopes()->lockForUpdate()->findOrFail($invoiceItem->id);
            $existing = FixedAssetTransaction::withoutGlobalScopes()->where('invoice_item_id', $source->id)->first();

            if ($existing) {
                if ($existing->fixed_asset_id === $locked->id
                    && $existing->type === FixedAssetTransaction::TYPE_DISPOSAL
                    && $existing->status === FixedAssetTransaction::STATUS_POSTED
                    && $existing->event_date->isSameDay($date)) {
                    return $locked->fresh(['category', 'transactions.journalEntry']);
                }

                throw new DomainException('This invoice item is already linked to a fixed asset disposal.');
            }

            if ($locked->status !== FixedAsset::STATUS_ACTIVE) {
                throw new DomainException('Only an active fixed asset can be disposed.');
            }

            $invoice = $this->guard->assertInvoiceSource($source, $locked->business_id);
            $taxPoint = $invoice->tax_point ?? $invoice->issue_date;
            if (! $taxPoint || ! $date->isSameDay($taxPoint)) {
                throw new DomainException('The disposal date must equal the invoice tax point.');
            }

            $cost = $this->money->toMinorUnits($locked->acquisition_cost_gbp, 'asset acquisition cost');
            $accumulated = $this->money->toMinorUnits($locked->accumulated_depreciation_gbp ?? '0.00', 'accumulated depreciation');
            if ($cost <= 0 || $accumulated > $cost) {
                throw new DomainException('The fixed asset has an invalid cost or accumulated depreciation balance.');
            }

            $proceeds = $this->money->toMinorUnits($this->money->forInvoiceItem($source), 'disposal proceeds');
            $netBookValue = $cost - $accumulated;
            $gainLoss = $proceeds - $netBookValue;
            $transaction = FixedAssetTransaction::withoutGlobalScopes()->create([
                'business_id' => $locked->business_id,
                'fixed_asset_id' => $locked->id,
                'type' => FixedAssetTransaction::TYPE_DISPOSAL,
                'status' => FixedAssetTransaction::STATUS_PREPARED,
                'event_date' => $date->toDateString(),
                'invoice_item_id' => $source->id,
                'amount_gbp' => $this->money->fromMinorUnits($proceeds),
                'cost_basis_gbp' => $this->money->fromMinorUnits($cost),
                'accumulated_depreciation_gbp' => $this->money->fromMinorUnits($accumulated),
                'net_book_value_gbp' => $this->money->fromMinorUnits($netBookValue),
                'proceeds_gbp' => $this->money->fromMinorUnits($proceeds),
                'gain_loss_gbp' => $this->money->fromMinorUnits($gainLoss),
                'created_by' => $actor->id,
            ]);
            $journal = $this->postJournal->execute($business, $actor, $this->payloads->forDisposal($transaction));
            $transaction->update(['status' => FixedAssetTransaction::STATUS_POSTED, 'journal_entry_id' => $journal->id]);

            DB::table('fixed_assets')->where('id', $locked->id)->update([
                'status' => FixedAsset::STATUS_DISPOSED,
                'disposed_at' => $date->toDateString(),
                'disposal_proceeds_gbp' => $this->money->fromMinorUnits($proceeds),
                'disposal_gain_loss_gbp' => $this->money->fromMinorUnits($gainLoss),
                'disposed_by' => $actor->id,
                'disposal_recorded_at' => now(),
                'updated_by' => $actor->id,
                'updated_at' => now(),
            ]);

            return $locked->fresh(['category', 'transactions.journalEntry']);
        }, 3);
    }
}
