<?php

namespace App\Actions\Finance;

use App\Models\BusinessUser;
use App\Models\Expense;
use App\Models\RecurringExpenseTemplate;
use App\Models\User;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use DomainException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Throwable;

class GenerateDueRecurringExpensesAction
{
    private const MAX_OCCURRENCES_PER_TEMPLATE = 120;

    public function __construct(private readonly CreateExpenseAction $createExpense) {}

    /**
     * @return array{generated: Collection<int, Expense>, failures: array<int, string>}
     */
    public function execute(?CarbonInterface $through = null): array
    {
        $through = CarbonImmutable::instance($through ?? today())->startOfDay();
        $generated = collect();
        $failures = [];
        $templateIds = RecurringExpenseTemplate::withoutGlobalScopes()
            ->where('status', RecurringExpenseTemplate::STATUS_ACTIVE)
            ->whereDate('next_run_date', '<=', $through)
            ->orderBy('id')
            ->pluck('id');

        foreach ($templateIds as $templateId) {
            try {
                $generated->push(...$this->generateTemplate((int) $templateId, $through));
            } catch (Throwable $exception) {
                report($exception);
                $failures[(int) $templateId] = $exception->getMessage();
            }
        }

        return ['generated' => $generated, 'failures' => $failures];
    }

    /** @return list<Expense> */
    private function generateTemplate(int $templateId, CarbonImmutable $through): array
    {
        return DB::transaction(function () use ($templateId, $through): array {
            $template = RecurringExpenseTemplate::withoutGlobalScopes()
                ->with(['items', 'business'])
                ->lockForUpdate()
                ->findOrFail($templateId);

            if ($template->status !== RecurringExpenseTemplate::STATUS_ACTIVE) {
                return [];
            }

            $actor = $this->generationActor($template);
            $occurrence = CarbonImmutable::parse($template->next_run_date);
            $generated = [];

            for ($iteration = 0; $iteration < self::MAX_OCCURRENCES_PER_TEMPLATE && $occurrence->lte($through); $iteration++) {
                if ($template->end_date && $occurrence->gt($template->end_date)) {
                    $template->update(['status' => RecurringExpenseTemplate::STATUS_COMPLETED]);

                    break;
                }

                $expense = Expense::withoutGlobalScopes()
                    ->where('recurring_expense_template_id', $template->id)
                    ->whereDate('recurrence_date', $occurrence)
                    ->first();

                if (! $expense) {
                    $expense = $this->createExpense->execute($template->business, $actor, [
                        'supplier_id' => $template->supplier_id,
                        'client_id' => $template->client_id,
                        'project_id' => $template->project_id,
                        'currency' => 'GBP',
                        'issue_date' => $occurrence->toDateString(),
                        'tax_point' => $occurrence->toDateString(),
                        'received_date' => $occurrence->toDateString(),
                        'due_date' => $occurrence->addDays($template->due_days)->toDateString(),
                        'payment_method' => $template->payment_method,
                        'business_purpose' => $template->business_purpose,
                        'notes' => $template->notes,
                        'items' => $template->items->map(fn ($item): array => [
                            'description' => $item->description,
                            'quantity' => $item->quantity,
                            'unit_price' => $item->unit_price,
                            'tax_code_id' => $item->tax_code_id,
                            'ledger_account_id' => $item->ledger_account_id,
                            'category' => $item->category,
                            'client_id' => $item->client_id,
                            'project_id' => $item->project_id,
                            'billable' => $item->billable,
                            'supply_date' => $occurrence->toDateString(),
                        ])->all(),
                    ]);
                    $expense->update([
                        'recurring_expense_template_id' => $template->id,
                        'recurrence_date' => $occurrence,
                    ]);
                    $generated[] = $expense->fresh(['items', 'recurringTemplate']);
                }

                $occurrence = $template->nextOccurrenceAfter($occurrence);
                $template->update([
                    'next_run_date' => $occurrence,
                    'last_generated_at' => now(),
                    'status' => $template->end_date && $occurrence->gt($template->end_date)
                        ? RecurringExpenseTemplate::STATUS_COMPLETED
                        : RecurringExpenseTemplate::STATUS_ACTIVE,
                ]);
            }

            return $generated;
        }, 3);
    }

    private function generationActor(RecurringExpenseTemplate $template): User
    {
        if ($template->created_by) {
            $creatorIsActive = BusinessUser::query()
                ->where('business_id', $template->business_id)
                ->where('user_id', $template->created_by)
                ->where('is_active', true)
                ->exists();

            if ($creatorIsActive) {
                return User::findOrFail($template->created_by);
            }
        }

        $membership = BusinessUser::query()
            ->where('business_id', $template->business_id)
            ->where('is_active', true)
            ->with('user')
            ->first();

        if (! $membership?->user) {
            throw new DomainException('No active business member can own the generated recurring expense.');
        }

        return $membership->user;
    }
}
