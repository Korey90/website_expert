<?php

namespace App\Filament\Resources\ExpenseResource\Pages;

use App\Actions\Finance\ApproveExpenseAction;
use App\Actions\Finance\CreatePurchaseCreditNoteAction;
use App\Actions\Finance\PostExpenseAction;
use App\Actions\Finance\RecordExpensePaymentAction;
use App\Actions\Finance\VoidExpenseAction;
use App\Filament\Resources\ExpenseResource;
use App\Filament\Resources\PurchaseCreditNoteResource;
use App\Filament\Support\Currency as FilamentCurrency;
use App\Models\Expense;
use App\Models\FinancialAttachment;
use App\Models\PurchaseCreditNote;
use App\Services\Finance\FinancialAttachmentService;
use App\Support\PermissionHelper;
use DomainException;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Gate;

class ViewExpense extends ViewRecord
{
    protected static string $resource = ExpenseResource::class;

    protected function getHeaderActions(): array
    {
        /** @var Expense $expense */
        $expense = $this->record;

        return [
            Action::make('create_purchase_credit_note')
                ->label(__('finance.purchase_credit_notes.actions.create'))
                ->icon('heroicon-o-arrow-uturn-left')
                ->color('warning')
                ->visible(fn (): bool => Gate::allows('create', PurchaseCreditNote::class)
                    && $expense->supplier_id !== null
                    && in_array($expense->status, [
                        Expense::STATUS_APPROVED,
                        Expense::STATUS_POSTED,
                        Expense::STATUS_PARTIALLY_PAID,
                        Expense::STATUS_PAID,
                    ], true))
                ->form([
                    Textarea::make('reason')
                        ->label(__('finance.purchase_credit_notes.fields.reason'))
                        ->required()
                        ->maxLength(2000),
                    DatePicker::make('issue_date')
                        ->label(__('finance.purchase_credit_notes.fields.issue_date'))
                        ->default(today())
                        ->required(),
                    DatePicker::make('tax_point')
                        ->label(__('finance.fields.tax_point'))
                        ->default($expense->tax_point),
                    Repeater::make('items')
                        ->label(__('finance.purchase_credit_notes.sections.items'))
                        ->schema([
                            Select::make('expense_item_id')
                                ->label(__('finance.purchase_credit_notes.fields.expense_item'))
                                ->options($expense->items->mapWithKeys(fn ($item): array => [
                                    $item->id => $item->description.' — '.FilamentCurrency::format($item->gross_amount, $expense->currency),
                                ])->all())
                                ->disableOptionsWhenSelectedInSiblingRepeaterItems()
                                ->required(),
                            TextInput::make('quantity')
                                ->label(__('finance.purchase_credit_notes.fields.quantity'))
                                ->numeric()
                                ->minValue(0.01)
                                ->required(),
                        ])
                        ->columns(2)
                        ->minItems(1)
                        ->defaultItems(1)
                        ->required(),
                ])
                ->action(function (array $data) use ($expense): void {
                    try {
                        $creditNote = app(CreatePurchaseCreditNoteAction::class)->execute($expense, auth()->user(), $data);
                        Notification::make()->title(__('finance.purchase_credit_notes.notifications.created'))->success()->send();
                        $this->redirect(PurchaseCreditNoteResource::getUrl('view', ['record' => $creditNote]));
                    } catch (DomainException $exception) {
                        Notification::make()->title($exception->getMessage())->danger()->send();
                    }
                }),
            Action::make('upload_attachment')
                ->label(__('finance.attachments.actions.upload'))
                ->icon('heroicon-o-paper-clip')
                ->visible(fn (): bool => Gate::allows('create', FinancialAttachment::class))
                ->form([
                    Select::make('document_type')
                        ->label(__('finance.attachments.fields.document_type'))
                        ->options(collect(config('finance.attachments.document_types', []))
                            ->mapWithKeys(fn (string $type): array => [
                                $type => __('finance.attachments.document_types.'.$type),
                            ])->all())
                        ->required(),
                    FileUpload::make('file')
                        ->label(__('finance.attachments.fields.file'))
                        ->acceptedFileTypes(config('finance.attachments.allowed_mime_types', []))
                        ->maxSize((int) ceil((int) config('finance.attachments.max_bytes', 10485760) / 1024))
                        ->storeFiles(false)
                        ->required(),
                ])
                ->action(function (array $data) use ($expense): void {
                    if (! ($data['file'] ?? null) instanceof UploadedFile) {
                        throw new DomainException('A valid attachment file is required.');
                    }

                    $this->runWorkflowAction(
                        fn () => app(FinancialAttachmentService::class)->store(
                            $expense,
                            $data['file'],
                            auth()->user(),
                            $data['document_type'],
                        ),
                        __('finance.attachments.notifications.uploaded'),
                    );
                }),
            Action::make('approve')
                ->label(__('finance.expenses.actions.approve'))
                ->icon('heroicon-o-check-circle')
                ->color('info')
                ->visible(fn (): bool => Gate::allows('approve', $expense))
                ->requiresConfirmation()
                ->action(fn () => $this->runWorkflowAction(
                    fn () => app(ApproveExpenseAction::class)->execute($expense, auth()->user()),
                    __('finance.expenses.notifications.approved'),
                )),
            Action::make('post')
                ->label(__('finance.expenses.actions.post'))
                ->icon('heroicon-o-lock-closed')
                ->color('primary')
                ->visible(fn (): bool => Gate::allows('post', $expense))
                ->requiresConfirmation()
                ->action(fn () => $this->runWorkflowAction(
                    fn () => app(PostExpenseAction::class)->execute($expense, auth()->user()),
                    __('finance.expenses.notifications.posted'),
                )),
            Action::make('record_payment')
                ->label('Record payment')
                ->icon('heroicon-o-banknotes')
                ->color('success')
                ->visible(fn (): bool => in_array($expense->status, [Expense::STATUS_POSTED, Expense::STATUS_PARTIALLY_PAID], true)
                    && (float) $expense->amount_due > 0
                    && PermissionHelper::allows(auth()->user(), 'post_finance'))
                ->form([
                    TextInput::make('amount')
                        ->numeric()
                        ->minValue(0.01)
                        ->maxValue((float) $expense->amount_due)
                        ->default((float) $expense->amount_due)
                        ->required(),
                    Select::make('method')
                        ->options([
                            'bank_transfer' => __('finance.expenses.payment_methods.bank_transfer'),
                            'card' => __('finance.expenses.payment_methods.card'),
                            'cash' => __('finance.expenses.payment_methods.cash'),
                            'direct_debit' => __('finance.expenses.payment_methods.direct_debit'),
                            'cheque' => __('finance.expenses.payment_methods.cheque'),
                            'other' => __('finance.expenses.payment_methods.other'),
                        ])
                        ->default('bank_transfer')
                        ->required(),
                    TextInput::make('reference')->label('Payment reference'),
                    DatePicker::make('paid_at')->default(today())->required(),
                    Textarea::make('notes')->label(__('finance.expenses.fields.notes')),
                ])
                ->action(fn (array $data) => $this->runWorkflowAction(
                    fn () => app(RecordExpensePaymentAction::class)->execute($expense, auth()->user(), $data),
                    'Supplier payment recorded and posted.',
                )),
            Action::make('void')
                ->label(__('finance.expenses.actions.void'))
                ->icon('heroicon-o-no-symbol')
                ->color('danger')
                ->visible(fn (): bool => Gate::allows('void', $expense))
                ->form([
                    Textarea::make('reason')
                        ->label(__('finance.expenses.fields.void_reason'))
                        ->required()
                        ->maxLength(2000),
                ])
                ->action(fn (array $data) => $this->runWorkflowAction(
                    fn () => app(VoidExpenseAction::class)->execute($expense, auth()->user(), $data['reason']),
                    __('finance.expenses.notifications.voided'),
                )),
            EditAction::make(),
        ];
    }

    private function runWorkflowAction(callable $action, string $successTitle): void
    {
        try {
            $action();
            Notification::make()->title($successTitle)->success()->send();
            $this->redirect(ExpenseResource::getUrl('view', ['record' => $this->record->id]));
        } catch (DomainException $exception) {
            Notification::make()->title($exception->getMessage())->danger()->send();
        }
    }
}
