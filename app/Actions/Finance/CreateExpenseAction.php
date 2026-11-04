<?php

namespace App\Actions\Finance;

use App\Models\Business;
use App\Models\Client;
use App\Models\Expense;
use App\Models\Project;
use App\Models\Supplier;
use App\Models\User;
use Carbon\CarbonImmutable;
use DomainException;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;

class CreateExpenseAction
{
    public function __construct(private readonly SyncExpenseItemsAction $syncItems) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function execute(Business $business, User $actor, array $data): Expense
    {
        $this->assertActorMembership($business->id, $actor);

        return DB::transaction(function () use ($business, $actor, $data): Expense {
            $issueDate = CarbonImmutable::parse($data['issue_date'] ?? now());
            $supplier = $this->supplier($business->id, $data['supplier_id'] ?? null);
            $currency = strtoupper((string) ($data['currency'] ?? $supplier?->default_currency ?? 'GBP'));
            $this->assertExchangeRate($currency, $data['gbp_exchange_rate'] ?? null);
            $dueDate = $data['due_date'] ?? $issueDate->addDays($supplier?->payment_terms_days ?? 30)->toDateString();
            $currencySnapshot = $currency === 'GBP'
                ? [
                    'gbp_exchange_rate' => 1,
                    'exchange_rate_source' => 'identity',
                    'exchange_rate_date' => $data['tax_point'] ?? $issueDate->toDateString(),
                ]
                : [];
            $clientId = $this->tenantReferenceId(Client::class, $data['client_id'] ?? null, $business->id);
            $projectId = $this->tenantReferenceId(Project::class, $data['project_id'] ?? null, $business->id);

            $expense = Expense::withoutGlobalScopes()->create([
                ...Arr::only($data, [
                    'supplier_invoice_number', 'gbp_exchange_rate',
                    'exchange_rate_source', 'exchange_rate_date', 'payment_method',
                    'business_purpose', 'notes', 'received_date',
                ]),
                ...$currencySnapshot,
                'business_id' => $business->id,
                'supplier_id' => $supplier?->id,
                'client_id' => $clientId,
                'project_id' => $projectId,
                'created_by' => $actor->id,
                'status' => Expense::STATUS_DRAFT,
                'currency' => $currency,
                'issue_date' => $issueDate,
                'tax_point' => $data['tax_point'] ?? $issueDate,
                'due_date' => $dueDate,
                'received_date' => $data['received_date'] ?? $issueDate,
            ]);

            $this->syncItems->execute($expense, $data['items'] ?? []);
            $expense->recalculate();

            return $expense->fresh(['items.taxCode', 'supplier']);
        });
    }

    private function supplier(string $businessId, mixed $supplierId): ?Supplier
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

        return $supplier;
    }

    private function assertActorMembership(string $businessId, User $actor): void
    {
        if (! $actor->businessMemberships()->where('business_id', $businessId)->where('is_active', true)->exists()) {
            throw new DomainException('The actor does not belong to the expense business.');
        }
    }

    private function assertExchangeRate(string $currency, mixed $rate): void
    {
        if ($currency !== 'GBP' && (float) $rate <= 0) {
            throw new DomainException('A positive GBP exchange rate is required for a foreign-currency expense.');
        }
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
