<?php

namespace App\Services\Finance;

use App\Models\Client;
use App\Models\CreditNote;
use App\Models\Expense;
use App\Models\ExpensePayment;
use App\Models\Invoice;
use App\Models\JournalEntry;
use App\Models\JournalLine;
use App\Models\Payment;
use App\Models\PurchaseCreditNote;
use App\Models\PurchaseCreditRefund;
use App\Models\Supplier;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;
use InvalidArgumentException;
use Throwable;

class AgedReceivablesPayablesService
{
    private const BUCKETS = ['current', 'one_to_thirty', 'thirty_one_to_sixty', 'sixty_one_to_ninety', 'ninety_one_plus'];

    /**
     * @return array{as_of: string, buckets: list<string>, sections: Collection<int, object>}
     */
    public function report(string $businessId, CarbonInterface|string $asOf): array
    {
        $date = $this->date($asOf);
        $lines = $this->controlLines($businessId, $date);
        $resolved = $this->resolveSources($businessId, $lines);
        $documents = $this->documentSources($businessId, $resolved);
        $invoices = $this->invoices($businessId, $documents);
        $expenses = $this->expenses($businessId, $documents);
        $clients = $this->clients($businessId, $lines, $invoices);
        $suppliers = $this->suppliers($businessId, $lines, $expenses);
        $groups = ['receivables' => [], 'payables' => []];

        foreach ($resolved as $item) {
            $line = $item->line;
            $side = $line->account_subtype === 'accounts_receivable' ? 'receivables' : 'payables';
            $amount = $this->linePennies($line, $side);
            $sourceKey = $this->sourceKey($item->source_type, $item->source_id);
            $document = $documents->get($sourceKey);
            $metadata = $document?->kind === 'invoice'
                ? $invoices->get($document->id)
                : ($document?->kind === 'expense' ? $expenses->get($document->id) : null);

            if (! $metadata || $metadata->side !== $side) {
                $metadata = $this->adjustmentMetadata($line, $side, $clients, $suppliers);
                $groupKey = 'adjustment:'.($line->reversal_of_id ?: $line->journal_entry_id).':'.($metadata->counterparty_id ?? 'none');
            } else {
                $metadata = clone $metadata;
                $metadata->counterparty = $side === 'receivables'
                    ? $clients->get($metadata->counterparty_id, __('finance.aged_receivables_payables.unknown_client'))
                    : $suppliers->get($metadata->counterparty_id, __('finance.aged_receivables_payables.unknown_supplier'));
                $groupKey = $metadata->kind.':'.$metadata->id;
            }

            if (! isset($groups[$side][$groupKey])) {
                $groups[$side][$groupKey] = [
                    'metadata' => $metadata,
                    'balance' => 0,
                    'journals' => [],
                ];
            }

            $groups[$side][$groupKey]['balance'] += $amount;
            $groups[$side][$groupKey]['journals'][(int) $line->journal_entry_id] = true;
        }

        $sections = collect(['receivables', 'payables'])->map(function (string $side) use ($groups, $lines, $date): object {
            $rows = collect($groups[$side])
                ->filter(fn (array $group): bool => $group['balance'] !== 0)
                ->map(fn (array $group): object => $this->row($group, $date))
                ->sortBy(fn (object $row): string => sprintf(
                    '%d|%05d|%s|%s',
                    $row->is_adjustment ? 1 : 0,
                    99999 - $row->days_overdue,
                    mb_strtolower($row->counterparty),
                    mb_strtolower($row->reference),
                ))
                ->values();
            $sectionLines = $lines->where('account_subtype', $side === 'receivables' ? 'accounts_receivable' : 'accounts_payable');

            return (object) [
                'key' => $side,
                'rows' => $rows,
                'totals' => $this->totals($rows, $sectionLines, $side),
            ];
        });

        return [
            'as_of' => $date->toDateString(),
            'buckets' => self::BUCKETS,
            'sections' => $sections,
        ];
    }

    private function date(CarbonInterface|string $asOf): CarbonImmutable
    {
        if ($asOf instanceof CarbonInterface) {
            return CarbonImmutable::instance($asOf)->startOfDay();
        }

        try {
            $date = CarbonImmutable::createFromFormat('!Y-m-d', $asOf);
        } catch (Throwable) {
            $date = null;
        }

        if (! $date || $date->toDateString() !== $asOf) {
            throw new InvalidArgumentException('The ageing date must use the YYYY-MM-DD format.');
        }

        return $date;
    }

    /** @return Collection<int, object> */
    private function controlLines(string $businessId, CarbonImmutable $asOf): Collection
    {
        return JournalLine::withoutGlobalScopes()
            ->selectRaw(<<<'SQL'
                journal_lines.id,
                journal_lines.journal_entry_id,
                journal_lines.ledger_account_id,
                journal_lines.client_id,
                journal_lines.supplier_id,
                journal_lines.debit_gbp,
                journal_lines.credit_gbp,
                journal_lines.original_currency,
                journal_entries.reversal_of_id,
                journal_entries.entry_date,
                journal_entries.entry_number,
                journal_entries.description,
                journal_entries.source_type,
                journal_entries.source_id,
                ledger_accounts.subtype AS account_subtype
                SQL)
            ->join('journal_entries', 'journal_entries.id', '=', 'journal_lines.journal_entry_id')
            ->join('ledger_accounts', 'ledger_accounts.id', '=', 'journal_lines.ledger_account_id')
            ->where('journal_lines.business_id', $businessId)
            ->where('journal_entries.business_id', $businessId)
            ->where('ledger_accounts.business_id', $businessId)
            ->whereIn('ledger_accounts.subtype', ['accounts_receivable', 'accounts_payable'])
            ->whereIn('journal_entries.status', [JournalEntry::STATUS_POSTED, JournalEntry::STATUS_REVERSED])
            ->whereDate('journal_entries.entry_date', '<=', $asOf->toDateString())
            ->orderBy('journal_entries.entry_date')
            ->orderBy('journal_entries.id')
            ->orderBy('journal_lines.line_number')
            ->get();
    }

    /** @return Collection<int, object> */
    private function resolveSources(string $businessId, Collection $lines): Collection
    {
        $originals = JournalEntry::withoutGlobalScopes()
            ->where('business_id', $businessId)
            ->whereIn('id', $lines->pluck('reversal_of_id')->filter()->unique())
            ->get(['id', 'source_type', 'source_id'])
            ->keyBy('id');

        return $lines->map(function (object $line) use ($originals): object {
            $original = $line->reversal_of_id ? $originals->get($line->reversal_of_id) : null;

            return (object) [
                'line' => $line,
                'source_type' => $original?->source_type ?? $line->source_type,
                'source_id' => $original?->source_id ?? $line->source_id,
            ];
        });
    }

    /** @return Collection<string, object> */
    private function documentSources(string $businessId, Collection $resolved): Collection
    {
        $documents = collect();

        foreach ($resolved as $item) {
            if ($item->source_type === Invoice::class && $item->source_id) {
                $documents->put($this->sourceKey($item->source_type, $item->source_id), (object) ['kind' => 'invoice', 'id' => (int) $item->source_id]);
            } elseif ($item->source_type === Expense::class && $item->source_id) {
                $documents->put($this->sourceKey($item->source_type, $item->source_id), (object) ['kind' => 'expense', 'id' => (int) $item->source_id]);
            }
        }

        $this->mapRelatedSources($documents, $resolved, Payment::class, $businessId, 'invoice_id', 'invoice');
        $this->mapRelatedSources($documents, $resolved, CreditNote::class, $businessId, 'invoice_id', 'invoice');
        $this->mapRelatedSources($documents, $resolved, ExpensePayment::class, $businessId, 'expense_id', 'expense');
        $this->mapRelatedSources($documents, $resolved, PurchaseCreditNote::class, $businessId, 'expense_id', 'expense');
        $this->mapRelatedSources($documents, $resolved, PurchaseCreditRefund::class, $businessId, 'expense_id', 'expense');

        return $documents;
    }

    private function mapRelatedSources(
        Collection $documents,
        Collection $resolved,
        string $model,
        string $businessId,
        string $foreignKey,
        string $kind,
    ): void {
        $ids = $resolved->where('source_type', $model)->pluck('source_id')->filter()->unique();

        $model::withoutGlobalScopes()
            ->where('business_id', $businessId)
            ->whereIn('id', $ids)
            ->get(['id', $foreignKey])
            ->each(function (object $source) use ($documents, $model, $foreignKey, $kind): void {
                $documents->put($this->sourceKey($model, $source->id), (object) [
                    'kind' => $kind,
                    'id' => (int) $source->{$foreignKey},
                ]);
            });
    }

    /** @return Collection<int, object> */
    private function invoices(string $businessId, Collection $documents): Collection
    {
        return Invoice::withoutGlobalScopes()
            ->withTrashed()
            ->where('business_id', $businessId)
            ->whereIn('id', $documents->where('kind', 'invoice')->pluck('id')->unique())
            ->get(['id', 'client_id', 'number', 'issue_date', 'due_date', 'currency'])
            ->mapWithKeys(fn (Invoice $invoice): array => [$invoice->id => (object) [
                'side' => 'receivables',
                'kind' => 'invoice',
                'id' => $invoice->id,
                'counterparty_id' => $invoice->client_id,
                'reference' => $invoice->number,
                'issue_date' => $invoice->issue_date->toDateString(),
                'due_date' => $invoice->due_date->toDateString(),
                'currency' => strtoupper((string) $invoice->currency),
                'is_adjustment' => false,
            ]]);
    }

    /** @return Collection<int, object> */
    private function expenses(string $businessId, Collection $documents): Collection
    {
        return Expense::withoutGlobalScopes()
            ->withTrashed()
            ->where('business_id', $businessId)
            ->whereIn('id', $documents->where('kind', 'expense')->pluck('id')->unique())
            ->get(['id', 'supplier_id', 'supplier_invoice_number', 'issue_date', 'due_date', 'currency'])
            ->mapWithKeys(fn (Expense $expense): array => [$expense->id => (object) [
                'side' => 'payables',
                'kind' => 'expense',
                'id' => $expense->id,
                'counterparty_id' => $expense->supplier_id,
                'reference' => $expense->supplier_invoice_number ?: 'Expense #'.$expense->id,
                'issue_date' => $expense->issue_date->toDateString(),
                'due_date' => ($expense->due_date ?? $expense->issue_date)->toDateString(),
                'currency' => strtoupper((string) $expense->currency),
                'is_adjustment' => false,
            ]]);
    }

    /** @return Collection<int, string> */
    private function clients(string $businessId, Collection $lines, Collection $invoices): Collection
    {
        $ids = $lines->pluck('client_id')->merge($invoices->pluck('counterparty_id'))->filter()->unique();

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
    private function suppliers(string $businessId, Collection $lines, Collection $expenses): Collection
    {
        $ids = $lines->pluck('supplier_id')->merge($expenses->pluck('counterparty_id'))->filter()->unique();

        return Supplier::withoutGlobalScopes()
            ->withTrashed()
            ->where('business_id', $businessId)
            ->whereIn('id', $ids)
            ->get(['id', 'name', 'trading_name'])
            ->mapWithKeys(fn (Supplier $supplier): array => [
                $supplier->id => $supplier->name ?: ($supplier->trading_name ?: 'Supplier #'.$supplier->id),
            ]);
    }

    private function adjustmentMetadata(object $line, string $side, Collection $clients, Collection $suppliers): object
    {
        $counterpartyId = $side === 'receivables' ? $line->client_id : $line->supplier_id;
        $counterparty = $side === 'receivables'
            ? $clients->get($counterpartyId, __('finance.aged_receivables_payables.unknown_client'))
            : $suppliers->get($counterpartyId, __('finance.aged_receivables_payables.unknown_supplier'));

        return (object) [
            'side' => $side,
            'kind' => 'adjustment',
            'id' => null,
            'counterparty_id' => $counterpartyId,
            'counterparty' => $counterparty,
            'reference' => $line->entry_number ?: 'Journal #'.$line->journal_entry_id,
            'issue_date' => (string) $line->entry_date,
            'due_date' => (string) $line->entry_date,
            'currency' => strtoupper((string) ($line->original_currency ?: 'GBP')),
            'is_adjustment' => true,
        ];
    }

    /** @param array{metadata: object, balance: int, journals: array<int, bool>} $group */
    private function row(array $group, CarbonImmutable $asOf): object
    {
        $metadata = $group['metadata'];
        $dueDate = CarbonImmutable::parse($metadata->due_date);
        $daysOverdue = $dueDate->lt($asOf) ? (int) $dueDate->diffInDays($asOf) : 0;

        return (object) [
            'document_id' => $metadata->id,
            'kind' => $metadata->kind,
            'counterparty_id' => $metadata->counterparty_id,
            'counterparty' => $metadata->counterparty ?? null,
            'reference' => $metadata->reference,
            'issue_date' => $metadata->issue_date,
            'due_date' => $metadata->due_date,
            'currency' => $metadata->currency,
            'days_overdue' => $daysOverdue,
            'bucket' => $this->bucket($daysOverdue),
            'balance_gbp' => $this->penniesToMoney($group['balance']),
            'is_adjustment' => $metadata->is_adjustment,
            'journal_count' => count($group['journals']),
        ];
    }

    private function bucket(int $daysOverdue): string
    {
        return match (true) {
            $daysOverdue === 0 => 'current',
            $daysOverdue <= 30 => 'one_to_thirty',
            $daysOverdue <= 60 => 'thirty_one_to_sixty',
            $daysOverdue <= 90 => 'sixty_one_to_ninety',
            default => 'ninety_one_plus',
        };
    }

    private function totals(Collection $rows, Collection $lines, string $side): object
    {
        $bucketPennies = array_fill_keys(self::BUCKETS, 0);
        foreach ($rows as $row) {
            $bucketPennies[$row->bucket] += $this->moneyToPennies($row->balance_gbp);
        }

        $rowPennies = array_sum($bucketPennies);
        $controlPennies = $lines->sum(fn (object $line): int => $this->linePennies($line, $side));
        $difference = $rowPennies - $controlPennies;
        $totals = [
            'total_gbp' => $this->penniesToMoney($rowPennies),
            'control_balance_gbp' => $this->penniesToMoney($controlPennies),
            'difference_gbp' => $this->penniesToMoney($difference),
            'is_reconciled' => $difference === 0,
            'document_count' => $rows->where('is_adjustment', false)->count(),
            'adjustment_count' => $rows->where('is_adjustment', true)->count(),
            'counterparty_count' => $rows->pluck('counterparty_id')->filter()->unique()->count(),
            'control_account_count' => $lines->pluck('ledger_account_id')->unique()->count(),
            'journal_count' => $lines->pluck('journal_entry_id')->unique()->count(),
        ];

        foreach ($bucketPennies as $bucket => $pennies) {
            $totals[$bucket.'_gbp'] = $this->penniesToMoney($pennies);
        }

        return (object) $totals;
    }

    private function linePennies(object $line, string $side): int
    {
        $debit = $this->moneyToPennies($line->debit_gbp);
        $credit = $this->moneyToPennies($line->credit_gbp);

        return $side === 'receivables' ? $debit - $credit : $credit - $debit;
    }

    private function sourceKey(?string $type, mixed $id): string
    {
        return ($type ?? 'none').':'.($id ?? 'none');
    }

    private function moneyToPennies(mixed $amount): int
    {
        $value = trim((string) ($amount ?? '0'));
        $negative = str_starts_with($value, '-');
        $unsigned = ltrim($value, '+-');

        if (! preg_match('/^\d+(?:\.\d{1,2})?$/', $unsigned)) {
            throw new InvalidArgumentException('An ageing amount must have at most two fraction digits.');
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
