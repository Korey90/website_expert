<?php

namespace App\Actions\Finance;

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

class UpdateRecurringExpenseTemplateAction
{
    public function __construct(private readonly SyncRecurringExpenseTemplateItemsAction $syncItems) {}

    /** @param array<string, mixed> $data */
    public function execute(RecurringExpenseTemplate $template, User $actor, array $data): RecurringExpenseTemplate
    {
        return DB::transaction(function () use ($template, $actor, $data): RecurringExpenseTemplate {
            $locked = RecurringExpenseTemplate::withoutGlobalScopes()->lockForUpdate()->findOrFail($template->id);
            $this->assertActorMembership($locked->business_id, $actor);
            $this->validateSchedule($locked, $data);

            $locked->update([
                ...Arr::only($data, [
                    'name', 'status', 'frequency', 'interval', 'start_date', 'end_date',
                    'next_run_date', 'due_days', 'payment_method', 'business_purpose', 'notes',
                ]),
                'supplier_id' => $this->tenantModelId(Supplier::class, $data['supplier_id'] ?? $locked->supplier_id, $locked->business_id),
                'client_id' => $this->tenantModelId(Client::class, $data['client_id'] ?? $locked->client_id, $locked->business_id),
                'project_id' => $this->tenantModelId(Project::class, $data['project_id'] ?? $locked->project_id, $locked->business_id),
                'currency' => 'GBP',
                'interval' => max(1, (int) ($data['interval'] ?? $locked->interval)),
                'due_days' => max(0, (int) ($data['due_days'] ?? $locked->due_days)),
            ]);

            if (array_key_exists('items', $data)) {
                $this->syncItems->execute($locked, $data['items']);
            }

            return $locked->fresh(['items.taxCode', 'supplier']);
        });
    }

    /** @param array<string, mixed> $data */
    private function validateSchedule(RecurringExpenseTemplate $template, array $data): void
    {
        $frequency = $data['frequency'] ?? $template->frequency;
        $startDate = CarbonImmutable::parse($data['start_date'] ?? $template->start_date);
        $endDate = $data['end_date'] ?? $template->end_date;
        $nextRunDate = CarbonImmutable::parse($data['next_run_date'] ?? $template->next_run_date);

        if (blank($data['name'] ?? $template->name) || blank($data['business_purpose'] ?? $template->business_purpose)) {
            throw new DomainException('A recurring template requires a name and business purpose.');
        }

        if (! in_array($frequency, RecurringExpenseTemplate::frequencies(), true)) {
            throw new DomainException('Unsupported recurring expense frequency.');
        }

        if ($endDate && CarbonImmutable::parse($endDate)->lt($startDate)) {
            throw new DomainException('The recurring expense end date cannot precede its start date.');
        }

        if ($nextRunDate->lt($startDate)) {
            throw new DomainException('The next run date cannot precede the template start date.');
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
