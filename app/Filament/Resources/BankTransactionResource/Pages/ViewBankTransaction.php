<?php

namespace App\Filament\Resources\BankTransactionResource\Pages;

use App\Actions\Finance\CreateRecordFromBankTransactionAction;
use App\Actions\Finance\FindBankTransactionMatchesAction;
use App\Actions\Finance\ReconcileBankTransactionAction;
use App\Data\Finance\BankTransactionMatchCandidate;
use App\Filament\Resources\BankTransactionResource;
use App\Filament\Support\Currency as FilamentCurrency;
use App\Models\BankTransaction;
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
