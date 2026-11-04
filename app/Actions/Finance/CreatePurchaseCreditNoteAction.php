<?php

namespace App\Actions\Finance;

use App\Models\Expense;
use App\Models\ExpenseItem;
use App\Models\PurchaseCreditNote;
use App\Models\PurchaseCreditNoteItem;
use App\Models\User;
use Carbon\CarbonImmutable;
use DomainException;
use Illuminate\Support\Facades\DB;

class CreatePurchaseCreditNoteAction
{
    public function __construct(private readonly GenerateDocumentNumberAction $generateDocumentNumber) {}

    /**
     * @param  array{reason: string, issue_date?: string, tax_point?: string, items: list<array{expense_item_id: int, quantity: float|int|string}>}  $data
     */
    public function execute(Expense $expense, User $actor, array $data): PurchaseCreditNote
    {
        return DB::transaction(function () use ($expense, $actor, $data): PurchaseCreditNote {
            $lockedExpense = Expense::withoutGlobalScopes()->lockForUpdate()->findOrFail($expense->id);
            $this->assertActorMembership($actor, $lockedExpense->business_id);

            if (! in_array($lockedExpense->status, [
                Expense::STATUS_APPROVED,
                Expense::STATUS_POSTED,
                Expense::STATUS_PARTIALLY_PAID,
                Expense::STATUS_PAID,
            ], true)) {
                throw new DomainException('Purchase credit notes require an approved expense.');
            }

            if (! $lockedExpense->supplier_id) {
                throw new DomainException('A supplier is required for a purchase credit note.');
            }

            if (blank($data['reason'] ?? null) || empty($data['items'])) {
                throw new DomainException('A reason and at least one purchase credit item are required.');
            }

            $issueDate = $data['issue_date'] ?? now()->toDateString();
            $creditNote = PurchaseCreditNote::withoutGlobalScopes()->create([
                'business_id' => $lockedExpense->business_id,
                'number' => $this->generateDocumentNumber->execute(
                    $lockedExpense->business_id,
                    'purchase_credit_note',
                    CarbonImmutable::parse($issueDate),
                ),
                'expense_id' => $lockedExpense->id,
                'supplier_id' => $lockedExpense->supplier_id,
                'created_by' => $actor->id,
                'status' => PurchaseCreditNote::STATUS_DRAFT,
                'currency' => $lockedExpense->currency,
                'reason' => trim($data['reason']),
                'issue_date' => $issueDate,
                'tax_point' => $data['tax_point'] ?? $lockedExpense->tax_point ?? $lockedExpense->issue_date,
                'gbp_exchange_rate' => $lockedExpense->gbp_exchange_rate,
                'exchange_rate_source' => $lockedExpense->exchange_rate_source,
                'exchange_rate_date' => $lockedExpense->exchange_rate_date,
            ]);

            $seenItems = [];

            foreach ($data['items'] as $order => $requestedItem) {
                $expenseItemId = (int) $requestedItem['expense_item_id'];

                if (isset($seenItems[$expenseItemId])) {
                    throw new DomainException('An expense item may only appear once on a purchase credit note.');
                }

                $seenItems[$expenseItemId] = true;
                $expenseItem = ExpenseItem::withoutGlobalScopes()
                    ->where('expense_id', $lockedExpense->id)
                    ->lockForUpdate()
                    ->find($expenseItemId);

                if (! $expenseItem) {
                    throw new DomainException('A purchase credit item does not belong to the expense.');
                }

                $quantity = round((float) $requestedItem['quantity'], 2);
                $originalQuantity = (float) $expenseItem->quantity;
                $alreadyCredited = (float) DB::table('purchase_credit_note_items')
                    ->join('purchase_credit_notes', 'purchase_credit_notes.id', '=', 'purchase_credit_note_items.purchase_credit_note_id')
                    ->where('purchase_credit_note_items.expense_item_id', $expenseItem->id)
                    ->where('purchase_credit_notes.status', '!=', PurchaseCreditNote::STATUS_VOID)
                    ->where('purchase_credit_notes.id', '!=', $creditNote->id)
                    ->sum('purchase_credit_note_items.quantity');

                if ($quantity <= 0 || $quantity > round($originalQuantity - $alreadyCredited, 2)) {
                    throw new DomainException('Credited quantity exceeds the remaining expense item quantity.');
                }

                if ($expenseItem->net_amount === null || $expenseItem->vat_amount === null || $expenseItem->gross_amount === null) {
                    throw new DomainException('The expense must be recalculated before a purchase credit note is created.');
                }

                $ratio = $quantity / $originalQuantity;
                PurchaseCreditNoteItem::withoutGlobalScopes()->create([
                    'business_id' => $lockedExpense->business_id,
                    'purchase_credit_note_id' => $creditNote->id,
                    'expense_item_id' => $expenseItem->id,
                    'tax_code_id' => $expenseItem->tax_code_id,
                    'description' => $expenseItem->description,
                    'quantity' => $quantity,
                    'unit_price' => $expenseItem->unit_price,
                    'supply_date' => $expenseItem->supply_date,
                    'net_amount' => $this->proportion($expenseItem->net_amount, $ratio),
                    'net_amount_gbp' => $this->proportion($expenseItem->net_amount_gbp, $ratio),
                    'vat_rate_snapshot' => $expenseItem->vat_rate_snapshot,
                    'vat_amount' => $this->proportion($expenseItem->vat_amount, $ratio),
                    'vat_amount_gbp' => $this->proportion($expenseItem->vat_amount_gbp, $ratio),
                    'gross_amount' => $this->proportion($expenseItem->gross_amount, $ratio),
                    'gross_amount_gbp' => $this->proportion($expenseItem->gross_amount_gbp, $ratio),
                    'order' => $order,
                ]);
            }

            $items = PurchaseCreditNoteItem::withoutGlobalScopes()
                ->where('purchase_credit_note_id', $creditNote->id)
                ->get();
            $creditNote->update([
                'subtotal' => $items->sum('net_amount'),
                'vat_amount' => $items->sum('vat_amount'),
                'total' => $items->sum('gross_amount'),
                'subtotal_gbp' => $this->sumNullable($items->pluck('net_amount_gbp')->all()),
                'vat_amount_gbp' => $this->sumNullable($items->pluck('vat_amount_gbp')->all()),
                'total_gbp' => $this->sumNullable($items->pluck('gross_amount_gbp')->all()),
            ]);

            return $creditNote->fresh(['items', 'expense', 'supplier']);
        });
    }

    private function assertActorMembership(User $actor, string $businessId): void
    {
        if (! $actor->businessMemberships()->where('business_id', $businessId)->where('is_active', true)->exists()) {
            throw new DomainException('The actor does not belong to the purchase credit note business.');
        }
    }

    private function proportion(string|float|int|null $amount, float $ratio): ?float
    {
        return $amount !== null ? round((float) $amount * $ratio, 2) : null;
    }

    /** @param  list<string|float|int|null>  $amounts */
    private function sumNullable(array $amounts): ?float
    {
        if (collect($amounts)->contains(null)) {
            return null;
        }

        return round((float) array_sum($amounts), 2);
    }
}
