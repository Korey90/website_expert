<?php

namespace App\Filament\Resources\InvoiceResource\Pages;

use App\Filament\Resources\InvoiceResource;
use App\Mail\InvoiceSentMail;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;
use Illuminate\Support\Facades\Mail;

class ViewInvoice extends ViewRecord
{
    protected static string $resource = InvoiceResource::class;

    protected string $view = 'filament.resources.invoice-resource.pages.view-invoice';

    protected function getHeaderActions(): array
    {
        $invoice = $this->record;

        return [
            Action::make('send_invoice')
                ->label('Send Invoice')
                ->icon('heroicon-o-paper-airplane')
                ->color('primary')
                ->visible(fn () => $invoice->status === 'draft')
                ->requiresConfirmation()
                ->modalHeading('Send Invoice to Client')
                ->modalDescription('This will mark the invoice as Sent and email the client.')
                ->modalSubmitActionLabel('Yes, Send Invoice')
                ->action(function () use ($invoice) {
                    $invoice->update([
                        'status'  => 'sent',
                        'sent_at' => now(),
                    ]);

                    if ($invoice->client?->primary_contact_email) {
                        Mail::to($invoice->client->primary_contact_email)
                            ->send(new InvoiceSentMail($invoice));
                    }

                    Notification::make()
                        ->title('Invoice sent!')
                        ->success()
                        ->send();
                }),

            Action::make('mark_paid')
                ->label('Mark as Paid')
                ->icon('heroicon-o-check-badge')
                ->color('success')
                ->visible(fn () => in_array($invoice->status, ['sent', 'partially_paid', 'overdue']))
                ->requiresConfirmation()
                ->modalHeading('Mark Invoice as Paid')
                ->modalDescription('This will set the invoice status to Paid and record the payment date as today.')
                ->modalSubmitActionLabel('Yes, Mark as Paid')
                ->action(function () use ($invoice) {
                    $invoice->update([
                        'status'      => 'paid',
                        'paid_at'     => now(),
                        'amount_paid' => $invoice->total,
                        'amount_due'  => 0,
                    ]);

                    Notification::make()
                        ->title('Invoice marked as paid!')
                        ->success()
                        ->send();
                }),

            Action::make('pdf')
                ->label('Download PDF')
                ->icon('heroicon-o-arrow-down-tray')
                ->color('gray')
                ->url(fn () => route('invoice.pdf', $invoice))
                ->openUrlInNewTab(),

            EditAction::make(),
            DeleteAction::make(),
            ForceDeleteAction::make(),
            RestoreAction::make(),
        ];
    }
}
