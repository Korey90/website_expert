<?php

namespace App\Filament\Resources\DomainOrderResource\Pages;

use App\Actions\Domain\GenerateDomainInvoiceAction;
use App\Actions\Domain\GenerateDomainQuoteAction;
use App\Filament\Resources\DomainOrderResource;
use App\Filament\Resources\InvoiceResource;
use App\Filament\Resources\QuoteResource;
use App\Models\Quote;
use App\Services\Domain\DomainOrderService;
use Filament\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;
use Illuminate\Support\Carbon;

class ViewDomainOrder extends ViewRecord
{
    protected static string $resource = DomainOrderResource::class;

    protected function getHeaderActions(): array
    {
        return [
            $this->generateInvoiceAction(),
            $this->generateQuoteAction(),
            $this->markAsRegisteringAction(),
            $this->markAsRegisteredAction(),
            $this->cancelOrderAction(),
            $this->addAdminNotesAction(),
        ];
    }

    private function generateInvoiceAction(): Action
    {
        return Action::make('generate_invoice')
            ->label('Generate Invoice')
            ->icon('heroicon-o-document-text')
            ->color('success')
            ->visible(fn () => in_array($this->record->status, ['paid', 'registering', 'completed'])
                && $this->record->invoices()->doesntExist())
            ->requiresConfirmation()
            ->modalHeading('Generate Invoice')
            ->modalDescription('This will create a draft invoice for this domain order. You can review and edit it before sending.')
            ->modalSubmitActionLabel('Generate')
            ->action(function () {
                $invoice = app(GenerateDomainInvoiceAction::class)->execute(
                    $this->record,
                    auth()->id()
                );

                Notification::make()
                    ->success()
                    ->title('Invoice generated')
                    ->body('Draft invoice ' . $invoice->number . ' created successfully.')
                    ->send();

                $this->redirect(InvoiceResource::getUrl('view', ['record' => $invoice->id]));
            });
    }

    private function generateQuoteAction(): Action
    {
        return Action::make('generate_quote')
            ->label('Generate Quote')
            ->icon('heroicon-o-clipboard-document-list')
            ->color('info')
            ->visible(fn () => in_array($this->record->status, ['paid', 'registering', 'completed'])
                && Quote::where('domain_order_id', $this->record->id)->doesntExist())
            ->requiresConfirmation()
            ->modalHeading('Generate Quote')
            ->modalDescription('This will create a draft quote for this domain order with the domain as a pre-filled line item.')
            ->modalSubmitActionLabel('Generate')
            ->action(function () {
                $quote = app(GenerateDomainQuoteAction::class)->execute(
                    $this->record,
                    auth()->id()
                );

                Notification::make()
                    ->success()
                    ->title('Quote generated')
                    ->body('Draft quote ' . $quote->number . ' created successfully.')
                    ->send();

                $this->redirect(QuoteResource::getUrl('edit', ['record' => $quote->id]));
            });
    }

    private function markAsRegisteringAction(): Action
    {
        return Action::make('mark_registering')
            ->label('Mark as Registering')
            ->icon('heroicon-o-arrow-path')
            ->color('info')
            ->visible(fn () => $this->record->status === 'paid')
            ->requiresConfirmation()
            ->modalHeading('Mark Order as Registering')
            ->modalDescription('This will set the order status to "Registering" indicating that the domain is being provisioned with the registrar.')
            ->modalSubmitActionLabel('Yes, Mark as Registering')
            ->action(function () {
                app(DomainOrderService::class)->markAsRegistering($this->record);

                Notification::make()
                    ->success()
                    ->title('Order status updated')
                    ->body('Domain order is now marked as registering.')
                    ->send();

                $this->redirect(DomainOrderResource::getUrl('view', ['record' => $this->record->id]));
            });
    }

    private function markAsRegisteredAction(): Action
    {
        return Action::make('mark_registered')
            ->label('Mark as Registered')
            ->icon('heroicon-o-check-circle')
            ->color('success')
            ->visible(fn () => in_array($this->record->status, ['paid', 'registering']))
            ->modalHeading('Mark Domain as Registered')
            ->modalDescription('Enter the registration details to complete this order and create the domain record.')
            ->form([
                TextInput::make('provider_domain_id')
                    ->label('Provider Domain ID')
                    ->default(fn () => 'manual-' . now()->format('YmdHis'))
                    ->required()
                    ->maxLength(255),

                TextInput::make('provider')
                    ->label('Provider')
                    ->default(fn () => $this->record->provider ?? 'manual')
                    ->required()
                    ->maxLength(100),

                DatePicker::make('registered_at')
                    ->label('Registered At')
                    ->default(fn () => now()->toDateString())
                    ->required()
                    ->native(false),

                DatePicker::make('expires_at')
                    ->label('Expires At')
                    ->default(fn () => now()->addYears((int) $this->record->years)->toDateString())
                    ->required()
                    ->native(false),
            ])
            ->modalSubmitActionLabel('Complete Registration')
            ->action(function (array $data) {
                $order = $this->record;

                if ($order->provider !== $data['provider']) {
                    $order->update(['provider' => $data['provider']]);
                }

                app(DomainOrderService::class)->completeOrder(
                    $order,
                    $data['provider_domain_id'],
                    Carbon::parse($data['registered_at']),
                    Carbon::parse($data['expires_at']),
                );

                Notification::make()
                    ->success()
                    ->title('Domain registered!')
                    ->body("Order completed. Domain record created for {$order->full_domain}.")
                    ->send();

                $this->redirect(DomainOrderResource::getUrl('view', ['record' => $this->record->id]));
            });
    }

    private function cancelOrderAction(): Action
    {
        return Action::make('cancel_order')
            ->label('Cancel Order')
            ->icon('heroicon-o-x-circle')
            ->color('danger')
            ->visible(fn () => in_array($this->record->status, ['pending_payment', 'paid', 'registering']))
            ->requiresConfirmation()
            ->modalHeading('Cancel Domain Order')
            ->modalDescription('Are you sure you want to cancel this order? This action cannot be undone.')
            ->form([
                Textarea::make('reason')
                    ->label('Reason for Cancellation')
                    ->rows(3)
                    ->placeholder('Optional — internal note about why this order was cancelled.'),
            ])
            ->modalSubmitActionLabel('Yes, Cancel Order')
            ->action(function (array $data) {
                $order = $this->record;

                app(DomainOrderService::class)->cancelOrder($order);

                if (! empty($data['reason'])) {
                    $existing = $order->admin_notes ?? '';
                    $order->update([
                        'admin_notes' => trim($existing . "\n\nCancellation reason: " . $data['reason']),
                    ]);
                }

                Notification::make()
                    ->success()
                    ->title('Order cancelled')
                    ->body("Domain order for {$order->full_domain} has been cancelled.")
                    ->send();

                $this->redirect(DomainOrderResource::getUrl('view', ['record' => $this->record->id]));
            });
    }

    private function addAdminNotesAction(): Action
    {
        return Action::make('add_notes')
            ->label('Admin Notes')
            ->icon('heroicon-o-pencil-square')
            ->color('gray')
            ->modalHeading('Update Admin Notes')
            ->form([
                Textarea::make('admin_notes')
                    ->label('Admin Notes')
                    ->default(fn () => $this->record->admin_notes ?? '')
                    ->rows(6),
            ])
            ->modalSubmitActionLabel('Save Notes')
            ->action(function (array $data) {
                $this->record->update(['admin_notes' => $data['admin_notes']]);

                Notification::make()
                    ->success()
                    ->title('Notes saved')
                    ->send();

                $this->redirect(DomainOrderResource::getUrl('view', ['record' => $this->record->id]));
            });
    }
}
