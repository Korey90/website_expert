<?php

namespace App\Filament\Resources\VatReturnResource\Pages;

use App\Actions\Finance\LockVatReturnAction;
use App\Actions\Finance\ReviewVatReturnAction;
use App\Actions\Finance\SupersedeVatReturnAction;
use App\Filament\Resources\VatReturnResource;
use App\Models\VatReturn;
use DomainException;
use Filament\Actions\Action;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;
use Illuminate\Support\Facades\Gate;

class ViewVatReturn extends ViewRecord
{
    protected static string $resource = VatReturnResource::class;

    protected function getHeaderActions(): array
    {
        /** @var VatReturn $return */
        $return = $this->record;

        return [
            Action::make('review')
                ->label(__('finance.vat_returns.actions.review'))
                ->icon('heroicon-o-check-badge')
                ->color('info')
                ->visible(fn (): bool => Gate::allows('review', $return))
                ->form([
                    Toggle::make('confirmed')
                        ->label(__('finance.vat_returns.fields.review_confirmation'))
                        ->helperText(__('finance.vat_returns.help.review_confirmation'))
                        ->accepted()
                        ->required(),
                    Textarea::make('notes')->label(__('finance.vat_returns.fields.review_notes'))->maxLength(2000),
                ])
                ->action(fn (array $data) => $this->runAction(
                    fn () => app(ReviewVatReturnAction::class)->execute($return, auth()->user(), (bool) ($data['confirmed'] ?? false), $data['notes'] ?? null),
                    __('finance.vat_returns.notifications.reviewed'),
                )),
            Action::make('lock')
                ->label(__('finance.vat_returns.actions.lock'))
                ->icon('heroicon-o-lock-closed')
                ->color('success')
                ->visible(fn (): bool => Gate::allows('lock', $return))
                ->requiresConfirmation()
                ->form([
                    Textarea::make('reason')->label(__('finance.vat_returns.fields.lock_reason'))->minLength(10)->maxLength(2000)->required(),
                ])
                ->action(fn (array $data) => $this->runAction(
                    fn () => app(LockVatReturnAction::class)->execute($return, auth()->user(), $data['reason']),
                    __('finance.vat_returns.notifications.locked'),
                )),
            Action::make('supersede')
                ->label(__('finance.vat_returns.actions.supersede'))
                ->icon('heroicon-o-arrow-path')
                ->color('warning')
                ->visible(fn (): bool => Gate::allows('supersede', $return))
                ->requiresConfirmation()
                ->form([
                    Textarea::make('reason')->label(__('finance.vat_returns.fields.supersede_reason'))->minLength(10)->maxLength(2000)->required(),
                ])
                ->action(fn (array $data) => $this->runAction(
                    fn () => app(SupersedeVatReturnAction::class)->execute($return, auth()->user(), $data['reason']),
                    __('finance.vat_returns.notifications.superseded'),
                )),
        ];
    }

    private function runAction(callable $action, string $successTitle): void
    {
        try {
            $action();
            Notification::make()->title($successTitle)->success()->send();
            $this->redirect(VatReturnResource::getUrl('view', ['record' => $this->record->id]));
        } catch (DomainException $exception) {
            Notification::make()->title($exception->getMessage())->danger()->send();
        }
    }
}
