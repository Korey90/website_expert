<?php

namespace App\Filament\Resources\AccountingPeriodResource\Pages;

use App\Actions\Finance\TransitionAccountingPeriodAction;
use App\Filament\Resources\AccountingPeriodResource;
use App\Models\AccountingPeriod;
use DomainException;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;
use Illuminate\Support\Facades\Gate;

class ViewAccountingPeriod extends ViewRecord
{
    protected static string $resource = AccountingPeriodResource::class;

    public function getSubheading(): ?string
    {
        return __('admin.migration.accounting.periods.view_subheading');
    }

    protected function getHeaderActions(): array
    {
        /** @var AccountingPeriod $period */
        $period = $this->record;

        return [
            Action::make('start_closing')
                ->label(__('finance.accounting_periods.actions.start_closing'))
                ->color('warning')
                ->requiresConfirmation()
                ->visible(fn (): bool => $period->status === AccountingPeriod::STATUS_OPEN && Gate::allows('transition', $period))
                ->action(fn () => $this->performPeriodTransition(AccountingPeriod::STATUS_CLOSING)),
            Action::make('close')
                ->label(__('finance.accounting_periods.actions.close'))
                ->color('info')
                ->form([Textarea::make('reason')->label(__('finance.accounting_periods.fields.close_notes'))->maxLength(2000)])
                ->visible(fn (): bool => $period->status === AccountingPeriod::STATUS_CLOSING && Gate::allows('transition', $period))
                ->action(fn (array $data) => $this->performPeriodTransition(AccountingPeriod::STATUS_CLOSED, $data['reason'] ?? null)),
            Action::make('reopen')
                ->label(__('finance.accounting_periods.actions.reopen'))
                ->color('success')
                ->form([Textarea::make('reason')->label(__('finance.accounting_periods.fields.reopen_reason'))->required()->maxLength(2000)])
                ->visible(fn (): bool => in_array($period->status, [AccountingPeriod::STATUS_CLOSING, AccountingPeriod::STATUS_CLOSED], true) && Gate::allows('transition', $period))
                ->action(fn (array $data) => $this->performPeriodTransition(AccountingPeriod::STATUS_OPEN, $data['reason'])),
            Action::make('lock')
                ->label(__('finance.accounting_periods.actions.lock'))
                ->color('danger')
                ->form([Textarea::make('reason')->label(__('finance.accounting_periods.fields.lock_reason'))->required()->maxLength(2000)])
                ->visible(fn (): bool => $period->status === AccountingPeriod::STATUS_CLOSED && Gate::allows('transition', $period))
                ->action(fn (array $data) => $this->performPeriodTransition(AccountingPeriod::STATUS_LOCKED, $data['reason'])),
            EditAction::make()->visible(fn (): bool => $period->status === AccountingPeriod::STATUS_OPEN),
        ];
    }

    private function performPeriodTransition(string $status, ?string $reason = null): void
    {
        try {
            app(TransitionAccountingPeriodAction::class)->execute($this->record, auth()->user(), $status, $reason);
            Notification::make()->title(__('finance.accounting_periods.notifications.updated'))->success()->send();
            $this->redirect(AccountingPeriodResource::getUrl('view', ['record' => $this->record]));
        } catch (DomainException $exception) {
            Notification::make()->title($exception->getMessage())->danger()->send();
        }
    }
}
