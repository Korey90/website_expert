<?php

namespace App\Actions\Finance;

use App\Models\Client;
use App\Models\Expense;
use App\Models\ExpenseItem;
use App\Models\Project;
use App\Models\TaxCode;
use DomainException;
use Illuminate\Database\Eloquent\Model;

class SyncExpenseItemsAction
{
    /**
     * @param  list<array<string, mixed>>  $items
     */
    public function execute(Expense $expense, array $items): void
    {
        if ($expense->status !== Expense::STATUS_DRAFT) {
            throw new DomainException('Only a draft expense can have its items changed.');
        }

        if ($items === []) {
            throw new DomainException('An expense requires at least one item.');
        }

        ExpenseItem::withoutGlobalScopes()->where('expense_id', $expense->id)->delete();

        foreach ($items as $order => $itemData) {
            $quantity = round((float) ($itemData['quantity'] ?? 0), 2);
            $unitPrice = round((float) ($itemData['unit_price'] ?? -1), 2);
            $description = trim((string) ($itemData['description'] ?? ''));

            if ($description === '' || $quantity <= 0 || $unitPrice < 0) {
                throw new DomainException('Each expense item requires a description, positive quantity and non-negative unit price.');
            }

            $taxCodeId = $this->tenantModelId(TaxCode::class, $itemData['tax_code_id'] ?? null, $expense->business_id);
            $clientId = $this->tenantModelId(Client::class, $itemData['client_id'] ?? null, $expense->business_id);
            $projectId = $this->tenantModelId(Project::class, $itemData['project_id'] ?? null, $expense->business_id);
            $billable = (bool) ($itemData['billable'] ?? false);

            if ($billable && $clientId === null) {
                throw new DomainException('A billable expense item must be assigned to a client.');
            }

            ExpenseItem::withoutGlobalScopes()->create([
                'business_id' => $expense->business_id,
                'expense_id' => $expense->id,
                'tax_code_id' => $taxCodeId,
                'ledger_account_id' => $itemData['ledger_account_id'] ?? null,
                'client_id' => $clientId,
                'project_id' => $projectId,
                'description' => $description,
                'quantity' => $quantity,
                'unit_price' => $unitPrice,
                'supply_date' => $itemData['supply_date'] ?? $expense->tax_point,
                'billable' => $billable,
                'order' => $order,
            ]);
        }
    }

    /** @param  class-string<Model>  $model */
    private function tenantModelId(string $model, mixed $id, string $businessId): ?int
    {
        if (blank($id)) {
            return null;
        }

        $record = $model::withoutGlobalScopes()
            ->where('business_id', $businessId)
            ->find($id);

        if (! $record) {
            throw new DomainException('An expense reference does not belong to the current business.');
        }

        return (int) $record->getKey();
    }
}
