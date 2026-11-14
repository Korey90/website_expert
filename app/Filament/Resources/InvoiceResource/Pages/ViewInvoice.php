<?php

namespace App\Filament\Resources\InvoiceResource\Pages;

use App\Actions\Finance\CreateCreditNoteAction;
use App\Actions\Finance\IssueInvoiceAction;
use App\Actions\Finance\RecordInvoicePaymentAction;
use App\Filament\Resources\CreditNoteResource;
use App\Filament\Resources\InvoiceResource;
use App\Mail\InvoiceSentMail;
use App\Models\CreditNote;
use App\Models\InvoiceItem;
use App\Support\PermissionHelper;
use DomainException;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;
use Illuminate\Support\Facades\Mail;

class ViewInvoice extends ViewRecord
{
    protected static string $resource = InvoiceResource::class;

    protected string $view = 'filament.resources.invoice-resource.pages.view-invoice';

    public function getSubheading(): ?string
    {
        return __('admin.migration.sales_documents.invoices.view_subheading');
    }

    protected function getHeaderActions(): array
    {
        $invoice = $this->record;
        $creditableItems = $invoice->items
            ->map(function (InvoiceItem $item): ?array {
                $alreadyCredited = (float) $item->creditNoteItems()
                    ->whereHas('creditNote', fn ($query) => $query->where('status', '!=', CreditNote::STATUS_VOID))
                    ->sum('quantity');
                $remaining = round((float) $item->quantity - $alreadyCredited, 2);

                if ($remaining <= 0) {
                    return null;
                }

                return [
                    'id' => $item->id,
                    'label' => "{$item->description} ({$remaining} remaining)",
                    'quantity' => $remaining,
                ];
            })
            ->filter()
            ->values();

        return [
            Action::make('send_invoice')
                ->label('Send Invoice')
                ->icon('heroicon-o-paper-airplane')
                ->color('primary')
                ->visible(fn () => $invoice->status === 'draft' && PermissionHelper::allows(auth()->user(), 'post_finance'))
                ->requiresConfirmation()
                ->modalHeading('Send Invoice to Client')
                ->modalDescription('This will mark the invoice as Sent and email the client.')
                ->modalSubmitActionLabel('Yes, Send Invoice')
                ->action(function () use ($invoice): void {
                    try {
                        $issued = app(IssueInvoiceAction::class)->execute($invoice, auth()->user());

                        if ($issued->client?->primary_contact_email) {
                            Mail::to($issued->client->primary_contact_email)
                                ->send(new InvoiceSentMail($issued));
                        }

                        Notification::make()
                            ->title('Invoice sent and posted!')
                            ->success()
                            ->send();
                    } catch (DomainException $exception) {
                        Notification::make()->title($exception->getMessage())->danger()->send();
                    }
                }),

            Action::make('mark_paid')
                ->label('Mark as Paid')
                ->icon('heroicon-o-check-badge')
                ->color('success')
                ->visible(fn () => in_array($invoice->status, ['sent', 'partially_paid', 'overdue'])
                    && PermissionHelper::allows(auth()->user(), 'post_finance'))
                ->modalHeading('Mark Invoice as Paid')
                ->modalDescription('This records the outstanding payment and posts it to the ledger.')
                ->modalSubmitActionLabel('Yes, Mark as Paid')
                ->form([
                    Select::make('method')
                        ->options([
                            'bank_transfer' => 'Bank transfer',
                            'stripe' => 'Stripe',
                            'payu' => 'PayU',
                            'cash' => 'Cash',
                            'cheque' => 'Cheque',
                            'other' => 'Other',
                        ])
                        ->default('bank_transfer')
                        ->required(),
                    TextInput::make('reference')->label('Payment reference'),
                    DatePicker::make('paid_at')->default(today())->required(),
                    TextInput::make('gbp_exchange_rate')
                        ->label('Payment GBP exchange rate')
                        ->numeric()
                        ->minValue(0.00000001)
                        ->default($invoice->gbp_exchange_rate)
                        ->helperText('GBP value of one unit of the invoice currency at the payment date.')
                        ->visible(strtoupper((string) $invoice->currency) !== 'GBP')
                        ->required(strtoupper((string) $invoice->currency) !== 'GBP'),
                    TextInput::make('exchange_rate_source')
                        ->label('Exchange-rate source')
                        ->default('manual')
                        ->visible(strtoupper((string) $invoice->currency) !== 'GBP')
                        ->required(strtoupper((string) $invoice->currency) !== 'GBP'),
                    DatePicker::make('exchange_rate_date')
                        ->label('Exchange-rate date')
                        ->default(today())
                        ->visible(strtoupper((string) $invoice->currency) !== 'GBP')
                        ->required(strtoupper((string) $invoice->currency) !== 'GBP'),
                ])
                ->action(function (array $data) use ($invoice): void {
                    try {
                        app(RecordInvoicePaymentAction::class)->execute($invoice, auth()->user(), [
                            ...$data,
                            'amount' => $invoice->amount_due,
                        ]);

                        Notification::make()->title('Invoice payment recorded and posted!')->success()->send();
                        $this->redirect(InvoiceResource::getUrl('view', ['record' => $invoice->id]));
                    } catch (DomainException $exception) {
                        Notification::make()->title($exception->getMessage())->danger()->send();
                    }
                }),

            Action::make('create_credit_note')
                ->label(__('finance.credit_notes.actions.create'))
                ->icon('heroicon-o-receipt-refund')
                ->color('warning')
                ->visible(fn (): bool => in_array($invoice->status, ['sent', 'partially_paid', 'paid', 'overdue'], true)
                    && $creditableItems->isNotEmpty()
                    && InvoiceResource::canEdit($invoice))
                ->form([
                    Textarea::make('reason')
                        ->label(__('finance.credit_notes.fields.reason'))
                        ->rows(3)
                        ->required(),
                    DatePicker::make('issue_date')
                        ->label(__('finance.credit_notes.fields.issue_date'))
                        ->default(today())
                        ->required(),
                    DatePicker::make('tax_point')
                        ->label(__('finance.fields.tax_point'))
                        ->default($invoice->tax_point ?? $invoice->issue_date)
                        ->required(),
                    Repeater::make('items')
                        ->label(__('finance.credit_notes.sections.items'))
                        ->schema([
                            Select::make('invoice_item_id')
                                ->label(__('finance.credit_notes.fields.invoice_item'))
                                ->options($creditableItems->pluck('label', 'id')->all())
                                ->disableOptionsWhenSelectedInSiblingRepeaterItems()
                                ->required(),
                            TextInput::make('quantity')
                                ->label(__('finance.credit_notes.fields.quantity'))
                                ->numeric()
                                ->minValue(0.01)
                                ->required(),
                        ])
                        ->columns(2)
                        ->default($creditableItems->map(fn (array $item): array => [
                            'invoice_item_id' => $item['id'],
                            'quantity' => $item['quantity'],
                        ])->all())
                        ->minItems(1)
                        ->required(),
                ])
                ->action(function (array $data) use ($invoice): void {
                    try {
                        $creditNote = app(CreateCreditNoteAction::class)->execute($invoice, auth()->user(), $data);

                        Notification::make()
                            ->title(__('finance.credit_notes.notifications.created'))
                            ->success()
                            ->send();

                        $this->redirect(CreditNoteResource::getUrl('view', ['record' => $creditNote->id]));
                    } catch (DomainException $exception) {
                        Notification::make()->title($exception->getMessage())->danger()->send();
                    }
                }),

            Action::make('pdf')
                ->label('Download PDF')
                ->icon('heroicon-o-arrow-down-tray')
                ->color('gray')
                ->url(fn () => route('invoice.pdf', $invoice))
                ->openUrlInNewTab(),

            EditAction::make()->visible(fn (): bool => ! $invoice->journalEntries()->exists()),
            DeleteAction::make()->visible(fn (): bool => ! $invoice->journalEntries()->exists()),
            ForceDeleteAction::make()->visible(fn (): bool => ! $invoice->journalEntries()->exists()),
            RestoreAction::make(),
        ];
    }
}
