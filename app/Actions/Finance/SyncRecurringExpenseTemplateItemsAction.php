<?php

namespace App\Actions\Finance;

use App\Models\Client;
use App\Models\LedgerAccount;
use App\Models\Project;
use App\Models\RecurringExpenseTemplate;
use App\Models\RecurringExpenseTemplateItem;
use App\Models\TaxCode;
use DomainException;
use Illuminate\Database\Eloquent\Model;

class SyncRecurringExpenseTemplateItemsAction
{
    /** @param list<array<string, mixed>> $items */
    public function execute(RecurringExpenseTemplate $template, array $items): void
    {
        if ($items === []) {
            throw new DomainException('A recurring expense template requires at least one item.');
        }

        RecurringExpenseTemplateItem::withoutGlobalScopes()
            ->where('recurring_expense_template_id', $template->id)
            ->delete();

        foreach ($items as $order => $item) {
            $description = trim((string) ($item['description'] ?? ''));
            $quantity = round((float) ($item['quantity'] ?? 0), 2);
            $unitPrice = round((float) ($item['unit_price'] ?? -1), 2);
            $taxCodeId = $this->tenantModelId(TaxCode::class, $item['tax_code_id'] ?? null, $template->business_id);
            $category = in_array($item['category'] ?? null, config('finance.expense_categories', []), true)
                ? $item['category']
                : 'other';
            $ledgerAccountId = $this->resolveLedgerAccountId($template, $item['ledger_account_id'] ?? null, $category);
            $clientId = $this->tenantModelId(Client::class, $item['client_id'] ?? null, $template->business_id);
            $projectId = $this->tenantModelId(Project::class, $item['project_id'] ?? null, $template->business_id);
            $billable = (bool) ($item['billable'] ?? false);

            if ($description === '' || $quantity <= 0 || $unitPrice < 0) {
                throw new DomainException('Each recurring item requires a description, positive quantity and non-negative unit price.');
            }

            if ($billable && $clientId === null) {
                throw new DomainException('A billable recurring item must be assigned to a client.');
            }

            RecurringExpenseTemplateItem::withoutGlobalScopes()->create([
                'business_id' => $template->business_id,
                'recurring_expense_template_id' => $template->id,
                'tax_code_id' => $taxCodeId,
                'ledger_account_id' => $ledgerAccountId,
                'category' => $category,
                'client_id' => $clientId,
                'project_id' => $projectId,
                'description' => $description,
                'quantity' => $quantity,
                'unit_price' => $unitPrice,
                'billable' => $billable,
                'order' => $order,
            ]);
        }
    }

    /** @param class-string<Model> $model */
    private function tenantModelId(string $model, mixed $id, string $businessId): ?int
    {
        if (blank($id)) {
            return null;
        }

        $record = $model::withoutGlobalScopes()->where('business_id', $businessId)->find($id);

        if (! $record || ($record instanceof LedgerAccount && (! $record->is_active || ! $record->is_postable))) {
            throw new DomainException('A recurring expense reference does not belong to the current business.');
        }

        return (int) $record->getKey();
    }

    private function resolveLedgerAccountId(RecurringExpenseTemplate $template, mixed $providedId, string $category): ?int
    {
        $candidateId = $providedId ?: $template->supplier?->default_ledger_account_id;

        if ($candidateId) {
            return $this->tenantModelId(LedgerAccount::class, $candidateId, $template->business_id);
        }

        return LedgerAccount::withoutGlobalScopes()
            ->where('business_id', $template->business_id)
            ->activePostable()
            ->where('type', 'expense')
            ->where('subtype', $category)
            ->value('id');
    }
}
