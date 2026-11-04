<?php

namespace App\Filament\Resources\PurchaseCreditNoteResource\Pages;

use App\Actions\Finance\AllocatePurchaseCreditNoteAction;
use App\Actions\Finance\IssuePurchaseCreditNoteAction;
use App\Actions\Finance\RecordPurchaseCreditRefundAction;
use App\Actions\Finance\VoidPurchaseCreditNoteAction;
use App\Filament\Resources\PurchaseCreditNoteResource;
use App\Models\PurchaseCreditNote;
use DomainException;
use Filament\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;
use Illuminate\Support\Facades\Gate;

class ViewPurchaseCreditNote extends ViewRecord
{
    protected static string $resource = PurchaseCreditNoteResource::class;

    protected function getHeaderActions(): array
    {
        /** @var PurchaseCreditNote $creditNote */
        $creditNote = $this->record;

        return [
            Action::make('issue')
                ->label(__('finance.purchase_credit_notes.actions.issue'))
                ->icon('heroicon-o-check-circle')
                ->color('info')
                ->visible(fn (): bool => Gate::allows('issue', $creditNote))
                ->requiresConfirmation()
                ->action(fn () => $this->runAction(
                    fn () => app(IssuePurchaseCreditNoteAction::class)->execute($creditNote, auth()->user()),
                    __('finance.purchase_credit_notes.notifications.issued'),
                )),
            Action::make('allocate')
                ->label(__('finance.purchase_credit_notes.actions.allocate'))
                ->icon('heroicon-o-scale')
                ->visible(fn (): bool => Gate::allows('settle', $creditNote) && (float) $creditNote->expense->amount_due > 0)
                ->form([
                    TextInput::make('amount')
                        ->label(__('finance.purchase_credit_notes.fields.allocation_amount'))
                        ->numeric()
                        ->minValue(0.01)
                        ->maxValue(fn (): float => min($creditNote->remainingAmount(), (float) $creditNote->expense->amount_due))
                        ->required(),
                ])
                ->action(fn (array $data) => $this->runAction(
                    fn () => app(AllocatePurchaseCreditNoteAction::class)->execute($creditNote, auth()->user(), (float) $data['amount']),
                    __('finance.purchase_credit_notes.notifications.allocated'),
                )),
            Action::make('refund')
                ->label(__('finance.purchase_credit_notes.actions.refund'))
                ->icon('heroicon-o-banknotes')
                ->color('success')
                ->visible(fn (): bool => Gate::allows('settle', $creditNote) && (float) $creditNote->expense->amount_paid > 0)
                ->form([
                    TextInput::make('amount')->label(__('finance.purchase_credit_notes.fields.refund_amount'))->numeric()->minValue(0.01)->maxValue(fn (): float => $creditNote->remainingAmount())->required(),
                    Select::make('method')->label(__('finance.purchase_credit_notes.fields.refund_method'))->options([
                        'bank_transfer' => __('finance.expenses.payment_methods.bank_transfer'),
                        'card' => __('finance.expenses.payment_methods.card'),
                        'cash' => __('finance.expenses.payment_methods.cash'),
                        'cheque' => __('finance.expenses.payment_methods.cheque'),
                        'other' => __('finance.expenses.payment_methods.other'),
                    ])->required(),
                    TextInput::make('reference')->label(__('finance.purchase_credit_notes.fields.reference'))->maxLength(255),
                    DatePicker::make('received_at')->label(__('finance.purchase_credit_notes.fields.received_at'))->default(today())->required(),
                    Textarea::make('notes')->label(__('finance.purchase_credit_notes.fields.notes')),
                ])
                ->action(fn (array $data) => $this->runAction(
                    fn () => app(RecordPurchaseCreditRefundAction::class)->execute($creditNote, auth()->user(), $data),
                    __('finance.purchase_credit_notes.notifications.refunded'),
                )),
            Action::make('void')
                ->label(__('finance.purchase_credit_notes.actions.void'))
                ->icon('heroicon-o-no-symbol')
                ->color('danger')
                ->visible(fn (): bool => Gate::allows('void', $creditNote))
                ->requiresConfirmation()
                ->action(fn () => $this->runAction(
                    fn () => app(VoidPurchaseCreditNoteAction::class)->execute($creditNote, auth()->user()),
                    __('finance.purchase_credit_notes.notifications.voided'),
                )),
        ];
    }

    private function runAction(callable $action, string $successTitle): void
    {
        try {
            $action();
            Notification::make()->title($successTitle)->success()->send();
            $this->redirect(PurchaseCreditNoteResource::getUrl('view', ['record' => $this->record->id]));
        } catch (DomainException $exception) {
            Notification::make()->title($exception->getMessage())->danger()->send();
        }
    }
}
