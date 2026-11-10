<?php

namespace App\Services\Finance;

use App\Models\Client;
use App\Models\CreditNote;
use App\Models\Expense;
use App\Models\Invoice;
use App\Models\JournalEntry;
use App\Models\JournalLine;
use App\Models\PurchaseCreditNote;
use App\Models\Supplier;
use App\Models\TaxCode;
use App\Models\VatAdjustment;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;
use InvalidArgumentException;
use Throwable;

class VatDetailService
{
    private const TAX_RECEIVABLE = 'tax_receivable';

    private const TAX_PAYABLE = 'tax_payable';

    /**
     * @return array{date_from: string, date_to: string, rows: Collection<int, object>, tax_codes: Collection<int, object>, totals: object}
     */
    public function report(
        string $businessId,
        CarbonInterface|string $dateFrom,
        CarbonInterface|string $dateTo,
    ): array {
        $from = $this->date($dateFrom, 'start');
        $to = $this->date($dateTo, 'end');

        if ($from->gt($to)) {
            throw new InvalidArgumentException('The VAT detail start date must not be after the end date.');
        }

        $lines = $this->reportLines($businessId, $from, $to);
        $entries = $this->resolveEntries($businessId, $lines);
        $documents = $this->documents($businessId, $entries);
        $taxCodes = $this->taxCodes($businessId, $lines);
        $clients = $this->clients($businessId, $lines, $documents);
        $suppliers = $this->suppliers($businessId, $lines, $documents);

        $rows = $lines
            ->groupBy(fn (object $line): string => $line->journal_entry_id.':'.($line->tax_code_id ?? 'unknown'))
            ->map(fn (Collection $group): object => $this->row(
                $group,
                $entries,
                $documents,
                $taxCodes,
                $clients,
                $suppliers,
            ))
            ->sortBy([
                ['tax_point', 'asc'],
                ['journal_entry_id', 'asc'],
                ['tax_code', 'asc'],
            ])
            ->values();

        return [
            'date_from' => $from->toDateString(),
            'date_to' => $to->toDateString(),
            'rows' => $rows,
            'tax_codes' => $this->taxCodeSummaries($rows),
            'totals' => $this->totals($rows, $lines),
        ];
    }

    private function date(CarbonInterface|string $date, string $boundary): CarbonImmutable
    {
        if ($date instanceof CarbonInterface) {
            return CarbonImmutable::instance($date)->startOfDay();
        }

        try {
            $parsed = CarbonImmutable::createFromFormat('!Y-m-d', $date);
        } catch (Throwable) {
            $parsed = null;
        }

        if (! $parsed || $parsed->toDateString() !== $date) {
            throw new InvalidArgumentException("The VAT detail {$boundary} date must use the YYYY-MM-DD format.");
        }

        return $parsed;
    }

    /** @return Collection<int, object> */
    private function reportLines(string $businessId, CarbonImmutable $from, CarbonImmutable $to): Collection
    {
        return JournalLine::withoutGlobalScopes()
            ->selectRaw(<<<'SQL'
                journal_lines.id,
                journal_lines.journal_entry_id,
                journal_lines.ledger_account_id,
                journal_lines.tax_code_id,
                journal_lines.client_id,
                journal_lines.supplier_id,
                journal_lines.debit_gbp,
                journal_lines.credit_gbp,
                journal_lines.original_currency,
                journal_lines.memo,
                journal_entries.reversal_of_id,
                journal_entries.entry_date,
                journal_entries.entry_number,
                journal_entries.description,
                journal_entries.source_type,
                journal_entries.source_id,
                journal_entries.source_snapshot_hash,
                ledger_accounts.type AS account_type,
                ledger_accounts.subtype AS account_subtype
                SQL)
            ->join('journal_entries', 'journal_entries.id', '=', 'journal_lines.journal_entry_id')
            ->join('ledger_accounts', 'ledger_accounts.id', '=', 'journal_lines.ledger_account_id')
            ->where('journal_lines.business_id', $businessId)
            ->where('journal_entries.business_id', $businessId)
            ->where('ledger_accounts.business_id', $businessId)
            ->whereIn('journal_entries.status', [JournalEntry::STATUS_POSTED, JournalEntry::STATUS_REVERSED])
            ->whereDate('journal_entries.entry_date', '>=', $from->toDateString())
            ->whereDate('journal_entries.entry_date', '<=', $to->toDateString())
            ->where(function ($query): void {
                $query->whereNotNull('journal_lines.tax_code_id')
                    ->orWhereIn('ledger_accounts.subtype', [self::TAX_RECEIVABLE, self::TAX_PAYABLE]);
            })
            ->orderBy('journal_entries.entry_date')
            ->orderBy('journal_entries.id')
            ->orderBy('journal_lines.line_number')
            ->get();
    }

    /** @return Collection<int, object> */
    private function resolveEntries(string $businessId, Collection $lines): Collection
    {
        $originals = JournalEntry::withoutGlobalScopes()
            ->where('business_id', $businessId)
            ->whereIn('id', $lines->pluck('reversal_of_id')->filter()->unique())
            ->get(['id', 'source_type', 'source_id'])
            ->keyBy('id');

        return $lines
            ->unique('journal_entry_id')
            ->mapWithKeys(function (object $line) use ($originals): array {
                $original = $line->reversal_of_id ? $originals->get($line->reversal_of_id) : null;

                return [$line->journal_entry_id => (object) [
                    'journal_entry_id' => (int) $line->journal_entry_id,
                    'source_type' => $original?->source_type ?? $line->source_type,
                    'source_id' => $original?->source_id ?? $line->source_id,
                    'is_reversal' => $line->reversal_of_id !== null,
                ]];
            });
    }

    /** @return Collection<string, object> */
    private function documents(string $businessId, Collection $entries): Collection
    {
        $documents = collect();

        Invoice::withoutGlobalScopes()
            ->withTrashed()
            ->where('business_id', $businessId)
            ->whereIn('id', $this->sourceIds($entries, Invoice::class))
            ->get(['id', 'client_id', 'number', 'currency'])
            ->each(fn (Invoice $invoice) => $documents->put(
                $this->sourceKey(Invoice::class, $invoice->id),
                (object) [
                    'source_key' => $this->sourceKey(Invoice::class, $invoice->id),
                    'kind' => 'sales_invoice',
                    'side' => 'sales',
                    'reference' => $invoice->number,
                    'counterparty_id' => $invoice->client_id,
                    'currency' => strtoupper((string) $invoice->currency),
                ],
            ));

        CreditNote::withoutGlobalScopes()
            ->withTrashed()
            ->where('business_id', $businessId)
            ->whereIn('id', $this->sourceIds($entries, CreditNote::class))
            ->get(['id', 'client_id', 'number', 'currency'])
            ->each(fn (CreditNote $creditNote) => $documents->put(
                $this->sourceKey(CreditNote::class, $creditNote->id),
                (object) [
                    'source_key' => $this->sourceKey(CreditNote::class, $creditNote->id),
                    'kind' => 'sales_credit_note',
                    'side' => 'sales',
                    'reference' => $creditNote->number,
                    'counterparty_id' => $creditNote->client_id,
                    'currency' => strtoupper((string) $creditNote->currency),
                ],
            ));

        Expense::withoutGlobalScopes()
            ->withTrashed()
            ->where('business_id', $businessId)
            ->whereIn('id', $this->sourceIds($entries, Expense::class))
            ->get(['id', 'supplier_id', 'supplier_invoice_number', 'currency'])
            ->each(fn (Expense $expense) => $documents->put(
                $this->sourceKey(Expense::class, $expense->id),
                (object) [
                    'source_key' => $this->sourceKey(Expense::class, $expense->id),
                    'kind' => 'purchase_invoice',
                    'side' => 'purchases',
                    'reference' => $expense->supplier_invoice_number ?: 'Expense #'.$expense->id,
                    'counterparty_id' => $expense->supplier_id,
                    'currency' => strtoupper((string) $expense->currency),
                ],
            ));

        PurchaseCreditNote::withoutGlobalScopes()
            ->withTrashed()
            ->where('business_id', $businessId)
            ->whereIn('id', $this->sourceIds($entries, PurchaseCreditNote::class))
            ->get(['id', 'supplier_id', 'number', 'currency'])
            ->each(fn (PurchaseCreditNote $creditNote) => $documents->put(
                $this->sourceKey(PurchaseCreditNote::class, $creditNote->id),
                (object) [
                    'source_key' => $this->sourceKey(PurchaseCreditNote::class, $creditNote->id),
                    'kind' => 'purchase_credit_note',
                    'side' => 'purchases',
                    'reference' => $creditNote->number,
                    'counterparty_id' => $creditNote->supplier_id,
                    'currency' => strtoupper((string) $creditNote->currency),
                ],
            ));

        VatAdjustment::withoutGlobalScopes()
            ->where('business_id', $businessId)
            ->whereIn('id', $this->sourceIds($entries, VatAdjustment::class))
            ->get(['id', 'number', 'vat_side', 'adjustment_type', 'correction_method', 'is_deliberate'])
            ->each(fn (VatAdjustment $adjustment) => $documents->put(
                $this->sourceKey(VatAdjustment::class, $adjustment->id),
                (object) [
                    'source_key' => $this->sourceKey(VatAdjustment::class, $adjustment->id),
                    'kind' => 'vat_adjustment',
                    'side' => $adjustment->vat_side === VatAdjustment::SIDE_OUTPUT ? 'sales' : 'purchases',
                    'reference' => $adjustment->number,
                    'counterparty_id' => null,
                    'currency' => 'GBP',
                    'is_adjustment' => true,
                    'vat_adjustment_id' => $adjustment->id,
                    'vat_adjustment_type' => $adjustment->adjustment_type,
                    'vat_correction_method' => $adjustment->correction_method,
                    'vat_error_is_deliberate' => $adjustment->is_deliberate,
                    'is_vat_return_excluded' => $adjustment->correction_method === VatAdjustment::METHOD_SEPARATE_NOTIFICATION,
                ],
            ));

        return $documents;
    }

    /** @return Collection<int, int> */
    private function sourceIds(Collection $entries, string $sourceType): Collection
    {
        return $entries
            ->where('source_type', $sourceType)
            ->pluck('source_id')
            ->filter()
            ->unique()
            ->map(fn (mixed $id): int => (int) $id);
    }

    /** @return Collection<int, object> */
    private function taxCodes(string $businessId, Collection $lines): Collection
    {
        return TaxCode::withoutGlobalScopes()
            ->where('business_id', $businessId)
            ->whereIn('id', $lines->pluck('tax_code_id')->filter()->unique())
            ->get()
            ->mapWithKeys(fn (TaxCode $taxCode): array => [$taxCode->id => (object) [
                'id' => $taxCode->id,
                'code' => $taxCode->code,
                'label' => $taxCode->getTranslation('label', app()->getLocale()),
                'type' => $taxCode->type,
                'rate' => $taxCode->rate !== null ? (float) $taxCode->rate : null,
                'vat_return_mapping' => $taxCode->vat_return_mapping ?? [],
                'mapping_boxes' => $this->mappingBoxes($taxCode->vat_return_mapping ?? []),
                'is_active' => (bool) $taxCode->is_active,
            ]]);
    }

    /** @return Collection<int, string> */
    private function clients(string $businessId, Collection $lines, Collection $documents): Collection
    {
        $ids = $lines->pluck('client_id')
            ->merge($documents->where('side', 'sales')->pluck('counterparty_id'))
            ->filter()
            ->unique();

        return Client::withoutGlobalScopes()
            ->withTrashed()
            ->where('business_id', $businessId)
            ->whereIn('id', $ids)
            ->get(['id', 'company_name', 'trading_name'])
            ->mapWithKeys(fn (Client $client): array => [
                $client->id => $client->company_name ?: ($client->trading_name ?: 'Client #'.$client->id),
            ]);
    }

    /** @return Collection<int, string> */
    private function suppliers(string $businessId, Collection $lines, Collection $documents): Collection
    {
        $ids = $lines->pluck('supplier_id')
            ->merge($documents->where('side', 'purchases')->pluck('counterparty_id'))
            ->filter()
            ->unique();

        return Supplier::withoutGlobalScopes()
            ->withTrashed()
            ->where('business_id', $businessId)
            ->whereIn('id', $ids)
            ->get(['id', 'name', 'trading_name'])
            ->mapWithKeys(fn (Supplier $supplier): array => [
                $supplier->id => $supplier->name ?: ($supplier->trading_name ?: 'Supplier #'.$supplier->id),
            ]);
    }

    private function row(
        Collection $group,
        Collection $entries,
        Collection $documents,
        Collection $taxCodes,
        Collection $clients,
        Collection $suppliers,
    ): object {
        $first = $group->first();
        $entry = $entries->get($first->journal_entry_id);
        $sourceKey = $this->sourceKey($entry?->source_type, $entry?->source_id);
        $document = $documents->get($sourceKey);
        $outputVat = $group
            ->where('account_subtype', self::TAX_PAYABLE)
            ->sum(fn (object $line): int => $this->creditMinusDebit($line));
        $inputVat = $group
            ->where('account_subtype', self::TAX_RECEIVABLE)
            ->sum(fn (object $line): int => $this->debitMinusCredit($line));
        $netDebit = $group
            ->reject(fn (object $line): bool => in_array($line->account_subtype, [self::TAX_RECEIVABLE, self::TAX_PAYABLE], true))
            ->sum(fn (object $line): int => $this->debitMinusCredit($line));
        $side = $document?->side ?? $this->inferredSide($entry?->source_type, $outputVat, $inputVat, $netDebit);
        $taxCode = $taxCodes->get($first->tax_code_id) ?? $this->unknownTaxCode();
        $counterpartyId = $document?->counterparty_id
            ?? ($side === 'sales' ? $group->pluck('client_id')->filter()->first() : $group->pluck('supplier_id')->filter()->first());
        $counterparty = $side === 'sales'
            ? $clients->get($counterpartyId, __('finance.vat_detail.unknown_client'))
            : $suppliers->get($counterpartyId, __('finance.vat_detail.unknown_supplier'));

        return (object) [
            'journal_entry_id' => (int) $first->journal_entry_id,
            'journal_line_ids' => $group->pluck('id')->map(fn (mixed $id): int => (int) $id)->sort()->values()->all(),
            'journal_snapshot_hash' => $first->source_snapshot_hash,
            'entry_number' => $first->entry_number,
            'tax_point' => (string) $first->entry_date,
            'kind' => $document?->kind ?? 'adjustment',
            'side' => $side,
            'reference' => $document?->reference ?? ($first->entry_number ?: 'Journal #'.$first->journal_entry_id),
            'description' => $first->description,
            'counterparty_id' => $counterpartyId,
            'counterparty' => $counterparty,
            'currency' => $document?->currency ?? strtoupper((string) ($group->pluck('original_currency')->filter()->first() ?: 'GBP')),
            'tax_code_id' => $taxCode->id,
            'tax_code' => $taxCode->code,
            'tax_code_label' => $taxCode->label,
            'tax_code_type' => $taxCode->type,
            'tax_rate' => $taxCode->rate,
            'vat_return_mapping' => $taxCode->vat_return_mapping,
            'mapping_boxes' => $taxCode->mapping_boxes,
            'tax_code_is_active' => $taxCode->is_active,
            'net_sales_gbp' => $this->penniesToMoney($side === 'sales' ? -$netDebit : 0),
            'net_purchases_gbp' => $this->penniesToMoney($side === 'purchases' ? $netDebit : 0),
            'output_vat_gbp' => $this->penniesToMoney($outputVat),
            'input_vat_gbp' => $this->penniesToMoney($inputVat),
            'is_reversal' => (bool) $entry?->is_reversal,
            'is_adjustment' => $document?->is_adjustment ?? $document === null,
            'vat_adjustment_id' => $document?->vat_adjustment_id ?? null,
            'vat_adjustment_type' => $document?->vat_adjustment_type ?? null,
            'vat_correction_method' => $document?->vat_correction_method ?? null,
            'vat_error_is_deliberate' => $document?->vat_error_is_deliberate ?? false,
            'is_vat_return_excluded' => $document?->is_vat_return_excluded ?? false,
            'source_key' => $document?->source_key,
            'line_count' => $group->count(),
        ];
    }

    private function inferredSide(?string $sourceType, int $outputVat, int $inputVat, int $netDebit): string
    {
        if (in_array($sourceType, [Invoice::class, CreditNote::class], true)) {
            return 'sales';
        }

        if (in_array($sourceType, [Expense::class, PurchaseCreditNote::class], true)) {
            return 'purchases';
        }

        if ($outputVat !== 0 && $inputVat === 0) {
            return 'sales';
        }

        if ($inputVat !== 0 && $outputVat === 0) {
            return 'purchases';
        }

        return $netDebit >= 0 ? 'purchases' : 'sales';
    }

    /** @return Collection<int, object> */
    private function taxCodeSummaries(Collection $rows): Collection
    {
        return $rows
            ->groupBy(fn (object $row): string => (string) ($row->tax_code_id ?? 'unknown'))
            ->map(function (Collection $taxRows): object {
                $first = $taxRows->first();

                return (object) [
                    'tax_code_id' => $first->tax_code_id,
                    'code' => $first->tax_code,
                    'label' => $first->tax_code_label,
                    'type' => $first->tax_code_type,
                    'rate' => $first->tax_rate,
                    'vat_return_mapping' => $first->vat_return_mapping,
                    'mapping_boxes' => $first->mapping_boxes,
                    'is_active' => $first->tax_code_is_active,
                    'net_sales_gbp' => $this->sumMoney($taxRows, 'net_sales_gbp'),
                    'net_purchases_gbp' => $this->sumMoney($taxRows, 'net_purchases_gbp'),
                    'output_vat_gbp' => $this->sumMoney($taxRows, 'output_vat_gbp'),
                    'input_vat_gbp' => $this->sumMoney($taxRows, 'input_vat_gbp'),
                    'journal_count' => $taxRows->pluck('journal_entry_id')->unique()->count(),
                    'document_count' => $taxRows->where('is_adjustment', false)->pluck('source_key')->filter()->unique()->count(),
                ];
            })
            ->sortBy('code')
            ->values();
    }

    private function totals(Collection $rows, Collection $lines): object
    {
        $outputVat = $this->sumMoneyPennies($rows, 'output_vat_gbp');
        $inputVat = $this->sumMoneyPennies($rows, 'input_vat_gbp');
        $controlOutput = $lines
            ->where('account_subtype', self::TAX_PAYABLE)
            ->sum(fn (object $line): int => $this->creditMinusDebit($line));
        $controlInput = $lines
            ->where('account_subtype', self::TAX_RECEIVABLE)
            ->sum(fn (object $line): int => $this->debitMinusCredit($line));
        $outputDifference = $outputVat - $controlOutput;
        $inputDifference = $inputVat - $controlInput;

        return (object) [
            'net_sales_gbp' => $this->sumMoney($rows, 'net_sales_gbp'),
            'net_purchases_gbp' => $this->sumMoney($rows, 'net_purchases_gbp'),
            'output_vat_gbp' => $this->penniesToMoney($outputVat),
            'input_vat_gbp' => $this->penniesToMoney($inputVat),
            'net_vat_gbp' => $this->penniesToMoney($outputVat - $inputVat),
            'output_control_gbp' => $this->penniesToMoney($controlOutput),
            'input_control_gbp' => $this->penniesToMoney($controlInput),
            'output_difference_gbp' => $this->penniesToMoney($outputDifference),
            'input_difference_gbp' => $this->penniesToMoney($inputDifference),
            'is_reconciled' => $outputDifference === 0 && $inputDifference === 0,
            'row_count' => $rows->count(),
            'journal_count' => $rows->pluck('journal_entry_id')->unique()->count(),
            'document_count' => $rows->where('is_adjustment', false)->pluck('source_key')->filter()->unique()->count(),
            'adjustment_count' => $rows->where('is_adjustment', true)->count(),
            'unknown_tax_code_count' => $rows->whereNull('tax_code_id')->count(),
            'tax_code_count' => $rows->pluck('tax_code_id')->filter()->unique()->count(),
        ];
    }

    /** @param array<string, mixed> $mapping @return list<int> */
    private function mappingBoxes(array $mapping): array
    {
        return collect($mapping)
            ->flatten()
            ->filter(fn (mixed $box): bool => is_numeric($box))
            ->map(fn (mixed $box): int => (int) $box)
            ->unique()
            ->sort()
            ->values()
            ->all();
    }

    private function unknownTaxCode(): object
    {
        return (object) [
            'id' => null,
            'code' => 'UNKNOWN',
            'label' => __('finance.vat_detail.unknown_tax_code'),
            'type' => 'unknown',
            'rate' => null,
            'vat_return_mapping' => [],
            'mapping_boxes' => [],
            'is_active' => false,
        ];
    }

    private function sourceKey(?string $type, mixed $id): string
    {
        return ($type ?? 'none').':'.($id ?? 'none');
    }

    private function debitMinusCredit(object $line): int
    {
        return $this->moneyToPennies($line->debit_gbp) - $this->moneyToPennies($line->credit_gbp);
    }

    private function creditMinusDebit(object $line): int
    {
        return $this->moneyToPennies($line->credit_gbp) - $this->moneyToPennies($line->debit_gbp);
    }

    private function sumMoney(Collection $rows, string $field): float
    {
        return $this->penniesToMoney($this->sumMoneyPennies($rows, $field));
    }

    private function sumMoneyPennies(Collection $rows, string $field): int
    {
        return $rows->sum(fn (object $row): int => $this->moneyToPennies($row->{$field}));
    }

    private function moneyToPennies(mixed $amount): int
    {
        $value = trim((string) ($amount ?? '0'));
        $negative = str_starts_with($value, '-');
        $unsigned = ltrim($value, '+-');

        if (! preg_match('/^\d+(?:\.\d{1,2})?$/', $unsigned)) {
            throw new InvalidArgumentException('A VAT detail amount must have at most two fraction digits.');
        }

        [$whole, $fraction] = array_pad(explode('.', $unsigned, 2), 2, '');
        $pennies = ((int) $whole * 100) + (int) str_pad($fraction, 2, '0');

        return $negative ? -$pennies : $pennies;
    }

    private function penniesToMoney(int $pennies): float
    {
        return round($pennies / 100, 2);
    }
}
