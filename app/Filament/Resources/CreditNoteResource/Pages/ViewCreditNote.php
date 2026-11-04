<?php

namespace App\Filament\Resources\CreditNoteResource\Pages;

use App\Actions\Finance\AllocateCreditNoteAction;
use App\Actions\Finance\IssueCreditNoteAction;
use App\Actions\Finance\VoidCreditNoteAction;
use App\Filament\Resources\CreditNoteResource;
use App\Filament\Resources\InvoiceResource;
use App\Models\CreditNote;
use DomainException;
use Filament\Actions\Action;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;

class ViewCreditNote extends ViewRecord
{
    protected static string $resource = CreditNoteResource::class;

    protected function getHeaderActions(): array
    {
        /** @var CreditNote $creditNote */
        $creditNote = $this->record;

        return [
            Action::make('issue')
                ->label(__('finance.credit_notes.actions.issue'))
                ->icon('heroicon-o-paper-airplane')
                ->color('primary')
                ->visible(fn (): bool => $creditNote->status === CreditNote::STATUS_DRAFT && $this->canManage())
                ->requiresConfirmation()
                ->action(function () use ($creditNote): void {
                    $this->runLifecycleAction(
                        fn () => app(IssueCreditNoteAction::class)->execute($creditNote, auth()->user()),
                        __('finance.credit_notes.notifications.issued'),
                    );
                }),

            Action::make('allocate')
                ->label(__('finance.credit_notes.actions.allocate'))
                ->icon('heroicon-o-banknotes')
                ->color('success')
                ->visible(fn (): bool => in_array($creditNote->status, [CreditNote::STATUS_ISSUED, CreditNote::STATUS_PARTIALLY_ALLOCATED], true) && $this->canManage())
                ->form([
                    TextInput::make('amount')
                        ->label(__('finance.credit_notes.fields.allocation_amount'))
                        ->numeric()
                        ->minValue(0.01)
                        ->maxValue(fn (): float => round((float) $creditNote->total - (float) $creditNote->allocated_amount, 2))
                        ->default(fn (): float => round((float) $creditNote->total - (float) $creditNote->allocated_amount, 2))
                        ->required(),
                ])
                ->action(function (array $data) use ($creditNote): void {
                    $this->runLifecycleAction(
                        fn () => app(AllocateCreditNoteAction::class)->execute($creditNote, auth()->user(), (float) $data['amount']),
                        __('finance.credit_notes.notifications.allocated'),
                    );
                }),

            Action::make('void')
                ->label(__('finance.credit_notes.actions.void'))
                ->icon('heroicon-o-no-symbol')
                ->color('danger')
                ->visible(fn (): bool => in_array($creditNote->status, [CreditNote::STATUS_DRAFT, CreditNote::STATUS_ISSUED], true) && $this->canManage())
                ->requiresConfirmation()
                ->action(function () use ($creditNote): void {
                    $this->runLifecycleAction(
                        fn () => app(VoidCreditNoteAction::class)->execute($creditNote, auth()->user()),
                        __('finance.credit_notes.notifications.voided'),
                    );
                }),

            Action::make('pdf')
                ->label(__('finance.credit_notes.actions.pdf'))
                ->icon('heroicon-o-arrow-down-tray')
                ->color('gray')
                ->url(fn (): string => route('credit-note.pdf', $creditNote))
                ->openUrlInNewTab(),
        ];
    }

    private function canManage(): bool
    {
        return InvoiceResource::canEdit($this->record->invoice);
    }

    private function runLifecycleAction(callable $action, string $successTitle): void
    {
        try {
            $action();

            Notification::make()->title($successTitle)->success()->send();
            $this->redirect(CreditNoteResource::getUrl('view', ['record' => $this->record->id]));
        } catch (DomainException $exception) {
            Notification::make()->title($exception->getMessage())->danger()->send();
        }
    }
}
