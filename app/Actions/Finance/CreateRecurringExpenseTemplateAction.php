<?php

namespace App\Actions\Finance;

use App\Models\Business;
use App\Models\Client;
use App\Models\Project;
use App\Models\RecurringExpenseTemplate;
use App\Models\Supplier;
use App\Models\User;
use Carbon\CarbonImmutable;
use DomainException;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;

class CreateRecurringExpenseTemplateAction
{
    public function __construct(private readonly SyncRecurringExpenseTemplateItemsAction $syncItems) {}

    /** @param array<string, mixed> $data */
    public function execute(Business $business, User $actor, array $data): RecurringExpenseTemplate
    {
        $this->assertActorMembership($business->id, $actor);

        return DB::transaction(function () use ($business, $actor, $data): RecurringExpenseTemplate {
            $this->validateSchedule($data);
            $startDate = CarbonImmutable::parse($data['start_date']);
            $template = RecurringExpenseTemplate::withoutGlobalScopes()->create([
                ...Arr::only($data, [
                    'name', 'frequency', 'interval', 'end_date', 'due_days',
                    'payment_method', 'business_purpose', 'notes',
                ]),
                'business_id' => $business->id,
                'supplier_id' => $this->tenantModelId(Supplier::class, $data['supplier_id'] ?? null, $business->id),
                'client_id' => $this->tenantModelId(Client::class, $data['client_id'] ?? null, $business->id),
                'project_id' => $this->tenantModelId(Project::class, $data['project_id'] ?? null, $business->id),
                'created_by' => $actor->id,
                'status' => $data['status'] ?? RecurringExpenseTemplate::STATUS_ACTIVE,
                'currency' => 'GBP',
                'start_date' => $startDate,
                'next_run_date' => $data['next_run_date'] ?? $startDate,
                'interval' => max(1, (int) ($data['interval'] ?? 1)),
                'due_days' => max(0, (int) ($data['due_days'] ?? 30)),
            ]);

            $this->syncItems->execute($template, $data['items'] ?? []);

            return $template->fresh(['items.taxCode', 'supplier']);
        });
    }

    /** @param array<string, mixed> $data */
    private function validateSchedule(array $data): void
    {
        if (blank($data['name'] ?? null) || blank($data['business_purpose'] ?? null)) {
            throw new DomainException('A recurring template requires a name and business purpose.');
        }

        if (! in_array($data['frequency'] ?? null, RecurringExpenseTemplate::frequencies(), true)) {
            throw new DomainException('Unsupported recurring expense frequency.');
        }

        if (blank($data['start_date'] ?? null)) {
            throw new DomainException('A recurring expense start date is required.');
        }

        if (! blank($data['end_date'] ?? null)
            && CarbonImmutable::parse($data['end_date'])->lt(CarbonImmutable::parse($data['start_date']))) {
            throw new DomainException('The recurring expense end date cannot precede its start date.');
        }
    }

    private function assertActorMembership(string $businessId, User $actor): void
    {
        if (! $actor->businessMemberships()->where('business_id', $businessId)->where('is_active', true)->exists()) {
            throw new DomainException('The actor does not belong to the recurring expense business.');
        }
    }

    /** @param class-string<Model> $model */
    private function tenantModelId(string $model, mixed $id, string $businessId): ?int
    {
        if (blank($id)) {
            return null;
        }

        $record = $model::withoutGlobalScopes()->where('business_id', $businessId)->find($id);

        if (! $record) {
            throw new DomainException('A recurring expense reference does not belong to the current business.');
        }

        return (int) $record->getKey();
    }
}
