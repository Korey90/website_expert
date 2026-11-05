<?php

namespace App\Filament\Resources\PaymentResource\Pages;

use App\Actions\Finance\RefundInvoicePaymentAction;
use App\Filament\Resources\PaymentResource;
use App\Models\Payment;
use App\Support\PermissionHelper;
use DomainException;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;

class ViewPayment extends ViewRecord
{
    protected static string $resource = PaymentResource::class;

    protected function getHeaderActions(): array
    {
        /** @var Payment $payment */
        $payment = $this->record;

        return [
            Action::make('refund')
                ->label('Refund payment')
                ->icon('heroicon-o-arrow-uturn-left')
                ->color('warning')
                ->visible(fn (): bool => $payment->status === 'completed'
                    && $payment->journalEntries()->exists()
                    && PermissionHelper::allows(auth()->user(), 'post_finance'))
                ->requiresConfirmation()
                ->action(function () use ($payment): void {
                    try {
                        app(RefundInvoicePaymentAction::class)->execute($payment, auth()->user());
                        Notification::make()->title('Payment refunded and posted.')->success()->send();
                        $this->redirect(PaymentResource::getUrl('view', ['record' => $payment->id]));
                    } catch (DomainException $exception) {
                        Notification::make()->title($exception->getMessage())->danger()->send();
                    }
                }),
            EditAction::make()->visible(fn (): bool => ! $payment->journalEntries()->exists()),
            DeleteAction::make()->visible(fn (): bool => ! $payment->journalEntries()->exists()),
        ];
    }
}
