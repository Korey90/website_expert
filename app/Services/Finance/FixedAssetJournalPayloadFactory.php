<?php

namespace App\Services\Finance;

use App\Models\FixedAssetTransaction;
use App\Models\LedgerAccount;
use DomainException;

class FixedAssetJournalPayloadFactory
{
    public function __construct(private readonly FixedAssetCostBasisService $money) {}

    /** @return array<string, mixed> */
    public function forCostTransaction(FixedAssetTransaction $transaction): array
    {
        $transaction->loadMissing(['fixedAsset.category', 'expenseItem']);
        $asset = $transaction->fixedAsset;
        $category = $asset?->category;
        $item = $transaction->expenseItem;

        if (! $asset || ! $category || ! $item || ! $item->ledger_account_id) {
            throw new DomainException('The fixed asset cost transaction is missing its source or ledger mapping.');
        }

        $amount = $this->positiveAmount($transaction->amount_gbp, 'fixed asset cost');
        $memo = $transaction->type === FixedAssetTransaction::TYPE_ACQUISITION
            ? "Fixed asset acquisition {$asset->number}"
            : "Fixed asset improvement {$asset->number}";

        return [
            'entry_date' => $transaction->event_date->toDateString(),
            'description' => $memo,
            'idempotency_key' => "fixed_asset_transaction:{$transaction->id}:post",
            'source' => $transaction,
            'source_snapshot' => $this->snapshot($transaction),
            'lines' => [
                $this->line($category->asset_account_id, $amount, '0.00', $memo, $transaction),
                $this->line($item->ledger_account_id, '0.00', $amount, "Reclassify expense item {$item->id}", $transaction),
            ],
        ];
    }

    /** @return array<string, mixed> */
    public function forDisposal(FixedAssetTransaction $transaction): array
    {
        $transaction->loadMissing(['fixedAsset.category', 'invoiceItem']);
        $asset = $transaction->fixedAsset;
        $category = $asset?->category;

        if (! $asset || ! $category || ! $transaction->invoiceItem) {
            throw new DomainException('The fixed asset disposal is missing its source or ledger mapping.');
        }

        $cost = $this->money->toMinorUnits($transaction->cost_basis_gbp, 'historical cost');
        $accumulated = $this->money->toMinorUnits($transaction->accumulated_depreciation_gbp, 'accumulated depreciation');
        $proceeds = $this->money->toMinorUnits($transaction->proceeds_gbp, 'disposal proceeds');
        $gainLoss = $this->signedMinorUnits($transaction->gain_loss_gbp, 'disposal gain or loss');

        if ($cost <= 0 || $accumulated > $cost || $proceeds <= 0) {
            throw new DomainException('The disposal requires positive proceeds and a valid historical cost snapshot.');
        }

        $memo = "Fixed asset disposal {$asset->number}";
        $lines = [
            $this->line($this->accountId($transaction->business_id, '4100'), $this->money->fromMinorUnits($proceeds), '0.00', 'Remove invoice sales income', $transaction),
        ];

        if ($accumulated > 0) {
            $lines[] = $this->line($category->accumulated_depreciation_account_id, $this->money->fromMinorUnits($accumulated), '0.00', 'Remove accumulated depreciation', $transaction);
        }

        if ($gainLoss < 0) {
            $lines[] = $this->line($this->accountId($transaction->business_id, '7900'), $this->money->fromMinorUnits(abs($gainLoss)), '0.00', 'Loss on fixed asset disposal', $transaction);
        }

        $lines[] = $this->line($category->asset_account_id, '0.00', $this->money->fromMinorUnits($cost), 'Remove historical fixed asset cost', $transaction);

        if ($gainLoss > 0) {
            $lines[] = $this->line($this->accountId($transaction->business_id, '4200'), '0.00', $this->money->fromMinorUnits($gainLoss), 'Gain on fixed asset disposal', $transaction);
        }

        return [
            'entry_date' => $transaction->event_date->toDateString(),
            'description' => $memo,
            'idempotency_key' => "fixed_asset_transaction:{$transaction->id}:post",
            'source' => $transaction,
            'source_snapshot' => $this->snapshot($transaction),
            'lines' => $lines,
        ];
    }

    /** @return array<string, mixed> */
    private function line(int $accountId, string $debit, string $credit, string $memo, FixedAssetTransaction $transaction): array
    {
        return [
            'ledger_account_id' => $accountId,
            'debit_gbp' => $debit,
            'credit_gbp' => $credit,
            'original_currency' => 'GBP',
            'original_amount' => $debit !== '0.00' ? $debit : $credit,
            'exchange_rate' => '1.00000000',
            'memo' => $memo,
            'dimensions' => [
                'fixed_asset_id' => $transaction->fixed_asset_id,
                'fixed_asset_transaction_id' => $transaction->id,
                'fixed_asset_transaction_type' => $transaction->type,
            ],
        ];
    }

    private function accountId(string $businessId, string $code): int
    {
        $account = LedgerAccount::withoutGlobalScopes()
            ->where('business_id', $businessId)
            ->where('code', $code)
            ->activePostable()
            ->first();

        if (! $account) {
            throw new DomainException("Required ledger account [{$code}] is unavailable.");
        }

        return $account->id;
    }

    private function positiveAmount(mixed $amount, string $label): string
    {
        $minor = $this->money->toMinorUnits($amount, $label);

        if ($minor <= 0) {
            throw new DomainException("The {$label} must be positive.");
        }

        return $this->money->fromMinorUnits($minor);
    }

    private function signedMinorUnits(mixed $amount, string $label): int
    {
        $value = trim((string) $amount);
        $negative = str_starts_with($value, '-');
        $absolute = $negative ? substr($value, 1) : $value;
        $minor = $this->money->toMinorUnits($absolute, $label);

        return $negative ? -$minor : $minor;
    }

    /** @return array<string, mixed> */
    private function snapshot(FixedAssetTransaction $transaction): array
    {
        return [
            'id' => $transaction->id,
            'fixed_asset_id' => $transaction->fixed_asset_id,
            'type' => $transaction->type,
            'event_date' => $transaction->event_date->toDateString(),
            'expense_item_id' => $transaction->expense_item_id,
            'invoice_item_id' => $transaction->invoice_item_id,
            'amount_gbp' => $transaction->amount_gbp,
            'cost_basis_gbp' => $transaction->cost_basis_gbp,
            'accumulated_depreciation_gbp' => $transaction->accumulated_depreciation_gbp,
            'net_book_value_gbp' => $transaction->net_book_value_gbp,
            'proceeds_gbp' => $transaction->proceeds_gbp,
            'gain_loss_gbp' => $transaction->gain_loss_gbp,
        ];
    }
}
