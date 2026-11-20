<?php

namespace App\Filament\Resources\BankTransactionResource\Pages;

use App\Actions\Finance\CreateRecordFromBankTransactionAction;
use App\Actions\Finance\FindBankTransactionMatchesAction;
use App\Actions\Finance\LinkExistingExpenseToBankTransactionAction;
use App\Actions\Finance\ReconcileBankTransactionAction;
use App\Actions\Finance\UndoBankReconciliationAction;
use App\Data\Finance\BankTransactionMatchCandidate;
use App\Filament\Resources\BankTransactionResource;
use App\Filament\Support\Currency as FilamentCurrency;
use App\Models\BankReconciliation;
use App\Models\BankReconciliationAllocation;
use App\Models\BankTransaction;
use App\Models\Expense;
use App\Models\Invoice;
use App\Models\LedgerAccount;
use App\Models\Supplier;
use App\Models\TaxCode;
use App\Models\User;
use App\Support\PermissionHelper;
use DomainException;
use Filament\Actions\Action;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;
use Filament\Schemas\Components\Utilities\Set;

class ViewBankTransaction extends ViewRecord
{
    protected static string $resource = BankTransactionResource::class;

    /** @var array<string, BankTransactionMatchCandidate> */
    private array $candidateCache = [];

    protected function getHeaderActions(): array
    {
        /** @var BankTransaction $transaction */
        $transaction = $this->record;

        return [
            Action::make('create_customer_payment')
                ->label(__('finance.bank_reconciliation.actions.create_customer_payment'))
                ->icon('heroicon-o-banknotes')
                ->color('success')
                ->visible(fn (): bool => (float) $transaction->amount > 0
                    && $transaction->reconciliation_status === BankTransaction::STATUS_UNMATCHED
                    && PermissionHelper::allows(auth()->user(), 'manage_finance')
                    && PermissionHelper::allows(auth()->user(), 'post_finance'))
                ->modalHeading(__('finance.bank_reconciliation.create_record.customer_heading'))
                ->modalDescription(__('finance.bank_reconciliation.create_record.customer_description'))
                ->form([
                    Select::make('invoice_id')
                        ->label(__('finance.bank_reconciliation.create_record.invoice'))
                        ->options(fn (): array => $this->invoiceOptions($transaction))
                        ->searchable()
                        ->preload()
                        ->required(),
                    TextInput::make('source_amount')
                        ->label(__('finance.bank_reconciliation.create_record.source_amount'))
                        ->helperText(__('finance.bank_reconciliation.create_record.source_amount_help'))
                        ->numeric()
                        ->minValue(0.01)
                        ->step(0.01),
                    TextInput::make('gbp_exchange_rate')
                        ->label(__('finance.bank_reconciliation.create_record.gbp_exchange_rate'))
                        ->helperText(__('finance.bank_reconciliation.create_record.gbp_exchange_rate_help'))
                        ->numeric()
                        ->minValue(0.00000001)
                        ->step(0.00000001)
                        ->required(fn (): bool => strtoupper($transaction->currency) !== 'GBP')
                        ->visible(fn (): bool => strtoupper($transaction->currency) !== 'GBP'),
                    Textarea::make('notes')
                        ->label(__('finance.bank_reconciliation.fields.notes'))
                        ->maxLength(2000),
                ])
                ->action(fn (array $data) => $this->createRecord($transaction, $data)),
            Action::make('create_supplier_expense')
                ->label(__('finance.bank_reconciliation.actions.create_supplier_expense'))
                ->icon('heroicon-o-receipt-percent')
                ->color('warning')
                ->visible(fn (): bool => (float) $transaction->amount < 0
                    && $transaction->reconciliation_status === BankTransaction::STATUS_UNMATCHED
                    && PermissionHelper::allows(auth()->user(), 'manage_finance')
                    && PermissionHelper::allows(auth()->user(), 'post_finance'))
                ->modalHeading(__('finance.bank_reconciliation.create_record.expense_heading'))
                ->modalDescription(__('finance.bank_reconciliation.create_record.expense_description'))
                ->modalWidth('4xl')
                ->form([
                    Select::make('supplier_id')
                        ->label(__('finance.expenses.fields.supplier'))
                        ->options(fn (): array => Supplier::query()
                            ->where('status', Supplier::STATUS_ACTIVE)
                            ->orderBy('name')
                            ->pluck('name', 'id')
                            ->all())
                        ->searchable()
                        ->preload(),
                    TextInput::make('supplier_invoice_number')
                        ->label(__('finance.expenses.fields.supplier_invoice_number'))
                        ->maxLength(255),
                    Select::make('currency')
                        ->label(__('finance.expenses.fields.currency'))
                        ->options(fn (): array => strtoupper($transaction->currency) === 'GBP'
                            ? FilamentCurrency::options()
                            : array_intersect_key(FilamentCurrency::options(), [strtoupper($transaction->currency) => true]))
                        ->default(strtoupper($transaction->currency))
                        ->required(),
                    TextInput::make('source_amount')
                        ->label(__('finance.bank_reconciliation.create_record.expense_gross_amount'))
                        ->default(number_format(abs((float) $transaction->amount), 2, '.', ''))
                        ->numeric()
                        ->minValue(0.01)
                        ->step(0.01)
                        ->required(),
                    TextInput::make('gbp_exchange_rate')
                        ->label(__('finance.bank_reconciliation.create_record.gbp_exchange_rate'))
                        ->helperText(__('finance.bank_reconciliation.create_record.gbp_exchange_rate_help'))
                        ->numeric()
                        ->minValue(0.00000001)
                        ->step(0.00000001)
                        ->required(fn (): bool => strtoupper($transaction->currency) !== 'GBP')
                        ->visible(fn (): bool => strtoupper($transaction->currency) !== 'GBP'),
                    Select::make('tax_code_id')
                        ->label(__('finance.fields.tax_code'))
                        ->options(fn (): array => TaxCode::effectiveOn($transaction->booking_date)
                            ->orderBy('code')
                            ->get()
                            ->mapWithKeys(fn (TaxCode $taxCode): array => [
                                $taxCode->id => "{$taxCode->code} — {$taxCode->label} ({$taxCode->rate}%)",
                            ])->all())
                        ->searchable()
                        ->preload()
                        ->required(),
                    Select::make('ledger_account_id')
                        ->label(__('finance.expenses.fields.ledger_account'))
                        ->options(fn (): array => LedgerAccount::activePostable()
                            ->where('type', 'expense')
                            ->orderBy('code')
                            ->get()
                            ->mapWithKeys(fn (LedgerAccount $account): array => [
                                $account->id => "{$account->code} — {$account->name}",
                            ])->all())
                        ->searchable()
                        ->preload()
                        ->required(),
                    Select::make('category')
                        ->label(__('finance.expenses.fields.category'))
                        ->options(collect(config('finance.expense_categories', []))->mapWithKeys(
                            fn (string $category): array => [$category => __('finance.expense_categories.'.$category)],
                        )->all())
                        ->default('other')
                        ->required(),
                    TextInput::make('description')
                        ->label(__('finance.expenses.fields.description'))
                        ->default($transaction->counterparty ?: $transaction->reference)
                        ->maxLength(255)
                        ->required(),
                    Textarea::make('business_purpose')
                        ->label(__('finance.expenses.fields.business_purpose'))
                        ->maxLength(2000)
                        ->required(),
                    Textarea::make('notes')
                        ->label(__('finance.bank_reconciliation.fields.notes'))
                        ->maxLength(2000),
                ])
                ->action(fn (array $data) => $this->createRecord($transaction, $data)),
            Action::make('link_existing_expense')
                ->label(__('finance.bank_reconciliation.actions.link_existing_expense'))
                ->icon('heroicon-o-link')
                ->color('info')
                ->visible(fn (): bool => (float) $transaction->amount < 0
                    && in_array($transaction->reconciliation_status, [
                        BankTransaction::STATUS_UNMATCHED,
                        BankTransaction::STATUS_PARTIALLY_RECONCILED,
                    ], true)
                    && PermissionHelper::allows(auth()->user(), 'manage_finance')
                    && PermissionHelper::allows(auth()->user(), 'post_finance'))
                ->modalHeading(__('finance.bank_reconciliation.link_expense.heading'))
                ->modalDescription(__('finance.bank_reconciliation.link_expense.description'))
                ->form([
                    Select::make('expense_id')
                        ->label(__('finance.bank_reconciliation.link_expense.expense'))
                        ->options(fn (): array => $this->existingExpenseOptions($transaction))
                        ->helperText(__('finance.bank_reconciliation.link_expense.expense_help'))
                        ->searchable()
                        ->preload()
                        ->required(),
                    Textarea::make('notes')
                        ->label(__('finance.bank_reconciliation.fields.notes'))
                        ->maxLength(2000),
                ])
                ->action(fn (array $data) => $this->linkExistingExpense($transaction, $data)),
            Action::make('undo_reconciliation')
                ->label(__('finance.bank_reconciliation.actions.undo'))
                ->icon('heroicon-o-arrow-uturn-left')
                ->color('danger')
                ->visible(fn (): bool => PermissionHelper::allows(auth()->user(), 'manage_finance')
                    && BankReconciliation::withoutGlobalScopes()
                        ->where('business_id', $transaction->business_id)
                        ->where('bank_transaction_id', $transaction->id)
                        ->where('status', BankReconciliation::STATUS_ACTIVE)
                        ->exists())
                ->modalHeading(__('finance.bank_reconciliation.undo.heading'))
                ->modalDescription(__('finance.bank_reconciliation.undo.description'))
                ->form([
                    Select::make('bank_reconciliation_id')
                        ->label(__('finance.bank_reconciliation.undo.batch'))
                        ->options(fn (): array => $this->activeReconciliationOptions($transaction))
                        ->required(),
                    Textarea::make('reason')
                        ->label(__('finance.bank_reconciliation.fields.reversal_reason'))
                        ->helperText(__('finance.bank_reconciliation.undo.reason_help'))
                        ->minLength(1)
                        ->maxLength(2000)
                        ->required(),
                ])
                ->action(fn (array $data) => $this->undoReconciliation($transaction, $data)),
            Action::make('reconcile')
                ->label(__('finance.bank_reconciliation.actions.reconcile'))
                ->icon('heroicon-o-link')
                ->color('success')
                ->visible(fn (): bool => in_array($transaction->reconciliation_status, [
                    BankTransaction::STATUS_UNMATCHED,
                    BankTransaction::STATUS_PARTIALLY_RECONCILED,
                ], true) && PermissionHelper::allows(auth()->user(), 'manage_finance'))
                ->modalHeading(__('finance.bank_reconciliation.modal.heading'))
                ->modalDescription(__('finance.bank_reconciliation.modal.description'))
                ->modalWidth('5xl')
                ->form([
                    Repeater::make('allocations')
                        ->label(__('finance.bank_reconciliation.sections.allocations'))
                        ->schema([
                            Select::make('candidate_key')
                                ->label(__('finance.bank_reconciliation.fields.source'))
                                ->options(fn (): array => $this->candidateOptions($transaction))
                                ->disableOptionsWhenSelectedInSiblingRepeaterItems()
                                ->searchable()
                                ->live()
                                ->afterStateUpdated(function (?string $state, Set $set): void {
                                    $candidate = $state ? ($this->candidateCache[$state] ?? null) : null;
                                    $set('amount', $candidate ? number_format(abs((float) $candidate->comparableAmount), 2, '.', '') : null);
                                })
                                ->required(),
                            TextInput::make('amount')
                                ->label(__('finance.bank_reconciliation.fields.allocated_amount'))
                                ->numeric()
                                ->minValue(0.01)
                                ->step(0.01)
                                ->required(),
                        ])
                        ->columns(3)
                        ->minItems(1)
                        ->maxItems(50)
                        ->defaultItems(1)
                        ->required(),
                    Textarea::make('notes')
                        ->label(__('finance.bank_reconciliation.fields.notes'))
                        ->maxLength(2000),
                ])
                ->action(function (array $data) use ($transaction): void {
                    try {
                        $user = auth()->user();

                        if (! $user instanceof User) {
                            throw new DomainException('An authenticated user is required.');
                        }

                        app(ReconcileBankTransactionAction::class)->execute(
                            $transaction,
                            $user,
                            $data['allocations'] ?? [],
                            $data['notes'] ?? null,
                        );
                        Notification::make()
                            ->title(__('finance.bank_reconciliation.notifications.reconciled'))
                            ->success()
                            ->send();
                        $this->redirect(BankTransactionResource::getUrl('view', ['record' => $transaction->id]));
                    } catch (DomainException $exception) {
                        Notification::make()->title($exception->getMessage())->danger()->send();
                    }
                }),
        ];
    }

    /** @return array<int, string> */
    private function invoiceOptions(BankTransaction $transaction): array
    {
        $bankCurrency = strtoupper($transaction->currency);

        return Invoice::query()
            ->with('client')
            ->whereIn('status', ['sent', 'partially_paid', 'overdue'])
            ->where('amount_due', '>', 0)
            ->when($bankCurrency !== 'GBP', fn ($query) => $query->where('currency', $bankCurrency))
            ->orderBy('due_date')
            ->get()
            ->mapWithKeys(fn (Invoice $invoice): array => [
                $invoice->id => __('finance.bank_reconciliation.create_record.invoice_option', [
                    'number' => $invoice->number,
                    'client' => $invoice->client?->company_name ?: '—',
                    'amount' => number_format((float) $invoice->amount_due, 2),
                    'currency' => $invoice->currency,
                ]),
            ])
            ->all();
    }

    /** @param array<string, mixed> $data */
    private function createRecord(BankTransaction $transaction, array $data): void
    {
        try {
            $user = auth()->user();

            if (! $user instanceof User) {
                throw new DomainException('An authenticated user is required.');
            }

            app(CreateRecordFromBankTransactionAction::class)->execute($transaction, $user, $data);
            Notification::make()
                ->title(__('finance.bank_reconciliation.notifications.record_created'))
                ->success()
                ->send();
            $this->redirect(BankTransactionResource::getUrl('view', ['record' => $transaction->id]));
        } catch (DomainException $exception) {
            Notification::make()->title($exception->getMessage())->danger()->send();
        }
    }

    /** @return array<int, string> */
    private function existingExpenseOptions(BankTransaction $transaction): array
    {
        $remaining = $this->remainingBankAmount($transaction);

        if ($remaining <= 0) {
            return [];
        }

        $statuses = [
            Expense::STATUS_APPROVED,
            Expense::STATUS_POSTED,
            Expense::STATUS_PARTIALLY_PAID,
        ];

        if (PermissionHelper::allows(auth()->user(), 'approve_finance')) {
            $statuses[] = Expense::STATUS_DRAFT;
        }

        return Expense::query()
            ->with('supplier')
            ->where('business_id', $transaction->business_id)
            ->whereIn('status', $statuses)
            ->where('currency', strtoupper((string) $transaction->currency))
            ->where('amount_due', '>=', $remaining)
            ->orderBy('due_date')
            ->orderBy('id')
            ->get()
            ->mapWithKeys(fn (Expense $expense): array => [
                $expense->id => __('finance.bank_reconciliation.link_expense.option', [
                    'number' => $expense->supplier_invoice_number ?: "#{$expense->id}",
                    'supplier' => $expense->supplier?->name ?: __('finance.bank_reconciliation.link_expense.no_supplier'),
                    'amount' => number_format((float) $expense->amount_due, 2),
                    'currency' => $expense->currency,
                    'status' => __('finance.expenses.statuses.'.$expense->status),
                ]),
            ])
            ->all();
    }

    /** @param array<string, mixed> $data */
    private function linkExistingExpense(BankTransaction $transaction, array $data): void
    {
        try {
            $user = auth()->user();

            if (! $user instanceof User) {
                throw new DomainException('An authenticated user is required.');
            }

            $expense = Expense::withoutGlobalScopes()
                ->where('business_id', $transaction->business_id)
                ->find((int) ($data['expense_id'] ?? 0));

            if (! $expense) {
                throw new DomainException('The selected expense does not belong to the bank transaction business.');
            }

            app(LinkExistingExpenseToBankTransactionAction::class)->execute(
                $transaction,
                $expense,
                $user,
                $data['notes'] ?? null,
            );
            Notification::make()
                ->title(__('finance.bank_reconciliation.notifications.expense_linked'))
                ->success()
                ->send();
            $this->redirect(BankTransactionResource::getUrl('view', ['record' => $transaction->id]));
        } catch (DomainException $exception) {
            Notification::make()->title($exception->getMessage())->danger()->send();
        }
    }

    private function remainingBankAmount(BankTransaction $transaction): float
    {
        $allocated = (float) BankReconciliationAllocation::withoutGlobalScopes()
            ->where('business_id', $transaction->business_id)
            ->where('bank_transaction_id', $transaction->id)
            ->whereHas('reconciliation', fn ($query) => $query
                ->withoutGlobalScopes()
                ->where('status', BankReconciliation::STATUS_ACTIVE))
            ->sum('amount');

        return max(0, round(abs((float) $transaction->amount) - $allocated, 2));
    }

    /** @return array<int, string> */
    private function activeReconciliationOptions(BankTransaction $transaction): array
    {
        return BankReconciliation::withoutGlobalScopes()
            ->with('createdBy')
            ->where('business_id', $transaction->business_id)
            ->where('bank_transaction_id', $transaction->id)
            ->where('status', BankReconciliation::STATUS_ACTIVE)
            ->orderByDesc('reconciled_at')
            ->get()
            ->mapWithKeys(fn (BankReconciliation $reconciliation): array => [
                $reconciliation->id => __('finance.bank_reconciliation.undo.batch_option', [
                    'id' => $reconciliation->id,
                    'amount' => number_format((float) $reconciliation->total_amount, 2),
                    'currency' => $reconciliation->currency,
                    'date' => $reconciliation->reconciled_at->format('Y-m-d H:i'),
                    'user' => $reconciliation->createdBy?->name ?: '—',
                ]),
            ])
            ->all();
    }

    /** @param array<string, mixed> $data */
    private function undoReconciliation(BankTransaction $transaction, array $data): void
    {
        try {
            $user = auth()->user();

            if (! $user instanceof User) {
                throw new DomainException('An authenticated user is required.');
            }

            $reconciliation = BankReconciliation::withoutGlobalScopes()
                ->where('business_id', $transaction->business_id)
                ->where('bank_transaction_id', $transaction->id)
                ->find((int) ($data['bank_reconciliation_id'] ?? 0));

            if (! $reconciliation) {
                throw new DomainException('The selected reconciliation does not belong to this bank transaction.');
            }

            app(UndoBankReconciliationAction::class)->execute(
                $reconciliation,
                $user,
                (string) ($data['reason'] ?? ''),
            );
            Notification::make()
                ->title(__('finance.bank_reconciliation.notifications.undone'))
                ->success()
                ->send();
            $this->redirect(BankTransactionResource::getUrl('view', ['record' => $transaction->id]));
        } catch (DomainException $exception) {
            Notification::make()->title($exception->getMessage())->danger()->send();
        }
    }

    /** @return array<string, string> */
    private function candidateOptions(BankTransaction $transaction): array
    {
        $user = auth()->user();

        if (! $user instanceof User) {
            return [];
        }

        try {
            $candidates = app(FindBankTransactionMatchesAction::class)->execute($transaction->fresh(), $user);
        } catch (DomainException) {
            return [];
        }

        $options = [];
        $this->candidateCache = [];

        foreach ($candidates as $candidate) {
            $this->candidateCache[$candidate->candidateKey] = $candidate;
            $options[$candidate->candidateKey] = __('finance.bank_reconciliation.candidate_label', [
                'event' => __('finance.bank_reconciliation.events.'.$candidate->eventType),
                'amount' => number_format(abs((float) $candidate->comparableAmount), 2),
                'currency' => $candidate->comparableCurrency,
                'date' => $candidate->date,
                'party' => $candidate->counterparty ?: '—',
                'score' => $candidate->score,
            ]);
        }

        return $options;
    }
}
