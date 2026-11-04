<?php

namespace App\Services\Finance;

use App\Models\Expense;
use App\Models\ExpenseItem;
use App\Models\PurchaseCreditNote;
use App\Models\PurchaseCreditNoteItem;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;
use InvalidArgumentException;

class ExpenseReportService
{
    /** @var list<string> */
    public const GROUPS = ['category', 'supplier', 'project', 'tax_code'];

    /**
     * @return array{rows: Collection<int, object>, totals: object}
     */
    public function report(string $businessId, string $dateFrom, string $dateTo, string $groupBy): array
    {
        if (! in_array($groupBy, self::GROUPS, true)) {
            throw new InvalidArgumentException("Unsupported expense report group [{$groupBy}].");
        }

        $from = CarbonImmutable::parse($dateFrom)->startOfDay();
        $to = CarbonImmutable::parse($dateTo)->endOfDay();

        if ($to->lt($from)) {
            throw new InvalidArgumentException('The report end date cannot precede its start date.');
        }

        $entries = collect();
        $expenseItems = ExpenseItem::withoutGlobalScopes()
            ->where('business_id', $businessId)
            ->whereHas('expense', fn ($query) => $query
                ->whereIn('status', [
                    Expense::STATUS_APPROVED,
                    Expense::STATUS_POSTED,
                    Expense::STATUS_PARTIALLY_PAID,
                    Expense::STATUS_PAID,
                ])
                ->whereDate('issue_date', '>=', $from->toDateString())
                ->whereDate('issue_date', '<=', $to->toDateString()))
            ->with(['expense.supplier', 'expense.project', 'project', 'taxCode'])
            ->get();

        foreach ($expenseItems as $item) {
            $entries->push($this->entry(
                $groupBy,
                $item,
                (float) ($item->net_amount_gbp ?? $item->net_amount),
                (float) ($item->vat_amount_gbp ?? $item->vat_amount),
                (float) ($item->gross_amount_gbp ?? $item->gross_amount),
                'expense:'.$item->expense_id,
            ));
        }

        $creditItems = PurchaseCreditNoteItem::withoutGlobalScopes()
            ->where('business_id', $businessId)
            ->whereHas('purchaseCreditNote', fn ($query) => $query
                ->whereIn('status', [
                    PurchaseCreditNote::STATUS_ISSUED,
                    PurchaseCreditNote::STATUS_PARTIALLY_SETTLED,
                    PurchaseCreditNote::STATUS_SETTLED,
                ])
                ->whereDate('issue_date', '>=', $from->toDateString())
                ->whereDate('issue_date', '<=', $to->toDateString()))
            ->with([
                'purchaseCreditNote.supplier',
                'purchaseCreditNote.expense.project',
                'expenseItem.project',
                'expenseItem.taxCode',
            ])
            ->get();

        foreach ($creditItems as $item) {
            $entries->push($this->creditEntry($groupBy, $item));
        }

        $rows = $entries
            ->groupBy('key')
            ->map(function (Collection $group): object {
                $first = $group->first();

                return (object) [
                    'key' => $first->key,
                    'label' => $first->label,
                    'net_gbp' => round($group->sum('net_gbp'), 2),
                    'vat_gbp' => round($group->sum('vat_gbp'), 2),
                    'gross_gbp' => round($group->sum('gross_gbp'), 2),
                    'document_count' => $group->pluck('document')->unique()->count(),
                ];
            })
            ->sortBy('label', SORT_NATURAL | SORT_FLAG_CASE)
            ->values();

        return [
            'rows' => $rows,
            'totals' => (object) [
                'net_gbp' => round($rows->sum('net_gbp'), 2),
                'vat_gbp' => round($rows->sum('vat_gbp'), 2),
                'gross_gbp' => round($rows->sum('gross_gbp'), 2),
                'document_count' => $entries->pluck('document')->unique()->count(),
            ],
        ];
    }

    private function creditEntry(string $groupBy, PurchaseCreditNoteItem $item): object
    {
        $source = $item->expenseItem;
        $expense = $item->purchaseCreditNote->expense;
        [$key, $label] = $this->groupIdentity(
            $groupBy,
            $source?->category ?? 'other',
            $item->purchaseCreditNote->supplier_id,
            $item->purchaseCreditNote->supplier?->name,
            $source?->project_id ?? $expense?->project_id,
            $source?->project?->title ?? $expense?->project?->title,
            $source?->tax_code_id ?? $item->tax_code_id,
            $source?->taxCode?->code,
        );

        return (object) [
            'key' => $key,
            'label' => $label,
            'net_gbp' => -1 * (float) ($item->net_amount_gbp ?? $item->net_amount),
            'vat_gbp' => -1 * (float) ($item->vat_amount_gbp ?? $item->vat_amount),
            'gross_gbp' => -1 * (float) ($item->gross_amount_gbp ?? $item->gross_amount),
            'document' => 'purchase_credit:'.$item->purchase_credit_note_id,
        ];
    }

    private function entry(
        string $groupBy,
        ExpenseItem $item,
        float $net,
        float $vat,
        float $gross,
        string $document,
    ): object {
        [$key, $label] = $this->groupIdentity(
            $groupBy,
            $item->category,
            $item->expense->supplier_id,
            $item->expense->supplier?->name,
            $item->project_id ?? $item->expense->project_id,
            $item->project?->title ?? $item->expense->project?->title,
            $item->tax_code_id,
            $item->taxCode?->code,
        );

        return (object) [
            'key' => $key,
            'label' => $label,
            'net_gbp' => $net,
            'vat_gbp' => $vat,
            'gross_gbp' => $gross,
            'document' => $document,
        ];
    }

    /** @return array{string, string} */
    private function groupIdentity(
        string $groupBy,
        ?string $category,
        ?int $supplierId,
        ?string $supplierName,
        ?int $projectId,
        ?string $projectTitle,
        ?int $taxCodeId,
        ?string $taxCode,
    ): array {
        return match ($groupBy) {
            'category' => ['category:'.($category ?: 'other'), __('finance.expense_categories.'.($category ?: 'other'))],
            'supplier' => ['supplier:'.($supplierId ?: 'none'), $supplierName ?: __('finance.expense_report.uncategorised_supplier')],
            'project' => ['project:'.($projectId ?: 'none'), $projectTitle ?: __('finance.expense_report.no_project')],
            'tax_code' => ['tax_code:'.($taxCodeId ?: 'none'), $taxCode ?: __('finance.expense_report.no_tax_code')],
        };
    }
}
