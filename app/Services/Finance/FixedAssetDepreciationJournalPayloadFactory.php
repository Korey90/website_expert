<?php

namespace App\Services\Finance;

use App\Models\FixedAssetDepreciationEntry;
use DomainException;

class FixedAssetDepreciationJournalPayloadFactory
{
    /** @return array<string, mixed> */
    public function forEntry(FixedAssetDepreciationEntry $entry): array
    {
        $entry->loadMissing('fixedAsset.category');
        $asset = $entry->fixedAsset;
        $category = $asset?->category;

        if (! $asset || ! $category) {
            throw new DomainException('The depreciation entry is missing its fixed asset category.');
        }

        $memo = "Book depreciation {$asset->number} to {$entry->period_end->toDateString()}";
        $dimensions = [
            'fixed_asset_id' => $asset->id,
            'fixed_asset_depreciation_entry_id' => $entry->id,
            'depreciation_method' => $entry->method_snapshot,
        ];

        return [
            'entry_date' => $entry->posting_date->toDateString(),
            'description' => $memo,
            'idempotency_key' => "fixed_asset_depreciation:{$entry->id}:post",
            'source' => $entry,
            'source_snapshot' => [
                'id' => $entry->id,
                'fixed_asset_id' => $entry->fixed_asset_id,
                'period_start' => $entry->period_start->toDateString(),
                'period_end' => $entry->period_end->toDateString(),
                'method' => $entry->method_snapshot,
                'rate_percent' => $entry->rate_percent_snapshot,
                'amount_gbp' => $entry->amount_gbp,
                'opening_net_book_value_gbp' => $entry->opening_net_book_value_gbp,
                'closing_net_book_value_gbp' => $entry->closing_net_book_value_gbp,
            ],
            'lines' => [
                [
                    'ledger_account_id' => $category->depreciation_expense_account_id,
                    'debit_gbp' => $entry->amount_gbp,
                    'credit_gbp' => '0.00',
                    'original_currency' => 'GBP',
                    'original_amount' => $entry->amount_gbp,
                    'exchange_rate' => '1.00000000',
                    'memo' => $memo,
                    'dimensions' => $dimensions,
                ],
                [
                    'ledger_account_id' => $category->accumulated_depreciation_account_id,
                    'debit_gbp' => '0.00',
                    'credit_gbp' => $entry->amount_gbp,
                    'original_currency' => 'GBP',
                    'original_amount' => $entry->amount_gbp,
                    'exchange_rate' => '1.00000000',
                    'memo' => $memo,
                    'dimensions' => $dimensions,
                ],
            ],
        ];
    }
}
