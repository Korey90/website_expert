<?php

namespace App\Actions\Finance;

use App\Models\Client;
use App\Models\Expense;
use App\Models\Project;
use App\Models\Supplier;
use App\Models\User;
use DomainException;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;

class UpdateExpenseAction
{
    public function __construct(private readonly SyncExpenseItemsAction $syncItems) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function execute(Expense $expense, User $actor, array $data): Expense
    {
        return DB::transaction(function () use ($expense, $actor, $data): Expense {
            $locked = Expense::withoutGlobalScopes()->lockForUpdate()->findOrFail($expense->id);
            $this->assertEditable($locked, $actor);
            $currency = strtoupper((string) ($data['currency'] ?? $locked->currency));

            if ($currency !== 'GBP' && (float) ($data['gbp_exchange_rate'] ?? $locked->gbp_exchange_rate) <= 0) {
                throw new DomainException('A positive GBP exchange rate is required for a foreign-currency expense.');
            }

            $supplierId = array_key_exists('supplier_id', $data)
                ? $this->supplierId($locked->business_id, $data['supplier_id'])
                : $locked->supplier_id;
            $currencySnapshot = $currency === 'GBP'
                ? [
                    'gbp_exchange_rate' => 1,
                    'exchange_rate_source' => 'identity',
                    'exchange_rate_date' => $data['tax_point'] ?? $locked->tax_point,
                ]
                : [];
            $clientId = array_key_exists('client_id', $data)
                ? $this->tenantReferenceId(Client::class, $data['client_id'], $locked->business_id)
                : $locked->client_id;
            $projectId = array_key_exists('project_id', $data)
                ? $this->tenantReferenceId(Project::class, $data['project_id'], $locked->business_id)
                : $locked->project_id;

            $locked->update([
                ...Arr::only($data, [
                    'supplier_invoice_number', 'gbp_exchange_rate',
                    'exchange_rate_source', 'exchange_rate_date', 'issue_date', 'tax_point',
                    'due_date', 'received_date', 'payment_method', 'business_purpose', 'notes',
                ]),
                ...$currencySnapshot,
                'supplier_id' => $supplierId,
                'client_id' => $clientId,
                'project_id' => $projectId,
                'currency' => $currency,
            ]);

            if (array_key_exists('items', $data)) {
                $this->syncItems->execute($locked, $data['items']);
            }

            $locked->recalculate();

            return $locked->fresh(['items.taxCode', 'supplier']);
        });
    }

    private function assertEditable(Expense $expense, User $actor): void
    {
        if ($expense->status !== Expense::STATUS_DRAFT) {
            throw new DomainException('Only a draft expense can be updated.');
        }

        if (! $actor->businessMemberships()->where('business_id', $expense->business_id)->where('is_active', true)->exists()) {
            throw new DomainException('The actor does not belong to the expense business.');
        }
    }

    private function supplierId(string $businessId, mixed $supplierId): ?int
    {
        if (blank($supplierId)) {
            return null;
        }

        $supplier = Supplier::withoutGlobalScopes()
            ->where('business_id', $businessId)
            ->find($supplierId);

        if (! $supplier) {
            throw new DomainException('The supplier does not belong to the current business.');
        }

        return (int) $supplier->id;
    }

    /** @param  class-string<Model>  $model */
    private function tenantReferenceId(string $model, mixed $id, string $businessId): ?int
    {
        if (blank($id)) {
            return null;
        }

        $record = $model::withoutGlobalScopes()->where('business_id', $businessId)->find($id);

        if (! $record) {
            throw new DomainException('An expense reference does not belong to the current business.');
        }

        return (int) $record->getKey();
    }
}
