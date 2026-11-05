<?php

namespace App\Filament\Resources\BankTransactionResource\Pages;

use App\Actions\Finance\FindBankTransactionMatchesAction;
use App\Actions\Finance\ReconcileBankTransactionAction;
use App\Data\Finance\BankTransactionMatchCandidate;
use App\Filament\Resources\BankTransactionResource;
use App\Models\BankTransaction;
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
