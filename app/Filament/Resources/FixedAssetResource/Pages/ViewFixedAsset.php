<?php

namespace App\Filament\Resources\FixedAssetResource\Pages;

use App\Actions\Finance\AcquireFixedAssetAction;
use App\Actions\Finance\DisposeFixedAssetAction;
use App\Actions\Finance\ImproveFixedAssetAction;
use App\Actions\Finance\ReverseFixedAssetTransactionAction;
use App\Filament\Resources\FixedAssetResource;
use App\Models\CreditNote;
use App\Models\Expense;
use App\Models\ExpenseItem;
use App\Models\FinancialAttachment;
use App\Models\FixedAsset;
use App\Models\FixedAssetTransaction;
use App\Models\InvoiceItem;
use App\Models\JournalEntry;
use App\Models\PurchaseCreditNote;
use App\Services\Finance\FixedAssetCostBasisService;
use App\Support\PermissionHelper;
use DomainException;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Actions\RestoreAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;
use Filament\Schemas\Components\Utilities\Get;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Gate;

class ViewFixedAsset extends ViewRecord
{
    protected static string $resource = FixedAssetResource::class;

    protected function getHeaderActions(): array
    {
        /** @var FixedAsset $asset */
        $asset = $this->record;

        return [
            Action::make('acquire')
                ->label(__('finance.fixed_assets.actions.acquire'))
                ->icon('heroicon-o-arrow-down-tray')
                ->color('success')
                ->visible(fn (): bool => $asset->status === FixedAsset::STATUS_DRAFT && $this->canPost())
                ->form($this->costSourceForm(__('finance.fixed_assets.fields.in_service_date')))
                ->action(function (array $data) use ($asset): void {
                    $item = ExpenseItem::query()->findOrFail((int) $data['expense_item_id']);
                    $this->runWorkflow(
                        fn () => app(AcquireFixedAssetAction::class)->execute($asset, $item, auth()->user(), $data['event_date']),
                        __('finance.fixed_assets.notifications.acquired'),
                    );
                }),
            Action::make('improve')
                ->label(__('finance.fixed_assets.actions.improve'))
                ->icon('heroicon-o-plus-circle')
                ->color('info')
                ->visible(fn (): bool => $asset->status === FixedAsset::STATUS_ACTIVE && $this->canPost())
                ->form($this->costSourceForm(__('finance.fixed_assets.fields.improvement_date')))
                ->action(function (array $data) use ($asset): void {
                    $item = ExpenseItem::query()->findOrFail((int) $data['expense_item_id']);
                    $this->runWorkflow(
                        fn () => app(ImproveFixedAssetAction::class)->execute($asset, $item, auth()->user(), $data['event_date']),
                        __('finance.fixed_assets.notifications.improved'),
                    );
                }),
            Action::make('dispose')
                ->label(__('finance.fixed_assets.actions.dispose'))
                ->icon('heroicon-o-arrow-up-tray')
                ->color('warning')
                ->visible(fn (): bool => $asset->status === FixedAsset::STATUS_ACTIVE && $this->canPost())
                ->form([
                    Select::make('invoice_item_id')
                        ->label(__('finance.fixed_assets.fields.invoice_item'))
                        ->options(fn (): array => $this->invoiceItemOptions())
                        ->searchable()->preload()->live()->required(),
                    DatePicker::make('event_date')
                        ->label(__('finance.fixed_assets.fields.disposal_date'))
                        ->default(today())->required(),
                    Placeholder::make('journal_preview')
                        ->label(__('finance.fixed_assets.journal_preview.title'))
                        ->content(fn (Get $get): string => $this->disposalPreview((int) $get('invoice_item_id'))),
                ])
                ->requiresConfirmation()
                ->action(function (array $data) use ($asset): void {
                    $item = InvoiceItem::query()->findOrFail((int) $data['invoice_item_id']);
                    $this->runWorkflow(
                        fn () => app(DisposeFixedAssetAction::class)->execute($asset, $item, auth()->user(), $data['event_date']),
                        __('finance.fixed_assets.notifications.disposed'),
                    );
                }),
            Action::make('reverse_latest')
                ->label(__('finance.fixed_assets.actions.reverse_latest'))
                ->icon('heroicon-o-arrow-uturn-left')
                ->color('danger')
                ->visible(fn (): bool => $this->latestPostedTransaction() !== null && $this->canPost())
                ->form([
                    Textarea::make('reason')
                        ->label(__('finance.fixed_assets.fields.reversal_reason'))
                        ->required()->maxLength(2000),
                    DatePicker::make('reversal_date')
                        ->label(__('finance.fixed_assets.fields.reversal_date'))
                        ->default(today())->required(),
                ])
                ->requiresConfirmation()
                ->action(function (array $data): void {
                    $transaction = $this->latestPostedTransaction();
                    if (! $transaction) {
                        Notification::make()->title(__('finance.fixed_assets.notifications.no_transaction'))->danger()->send();

                        return;
                    }

                    $this->runWorkflow(
                        fn () => app(ReverseFixedAssetTransactionAction::class)->execute(
                            $transaction,
                            auth()->user(),
                            $data['reason'],
                            $data['reversal_date'],
                        ),
                        __('finance.fixed_assets.notifications.reversed'),
                    );
                }),
            EditAction::make()->visible(fn (): bool => Gate::allows('update', $asset)),
            DeleteAction::make(),
            RestoreAction::make(),
        ];
    }

    /** @return list<mixed> */
    private function costSourceForm(string $dateLabel): array
    {
        return [
            Select::make('expense_item_id')
                ->label(__('finance.fixed_assets.fields.expense_item'))
                ->options(fn (): array => $this->expenseItemOptions())
                ->searchable()->preload()->live()->required(),
            DatePicker::make('event_date')->label($dateLabel)->default(today())->required(),
            Placeholder::make('journal_preview')
                ->label(__('finance.fixed_assets.journal_preview.title'))
                ->content(fn (Get $get): string => $this->costPreview((int) $get('expense_item_id'))),
        ];
    }

    /** @return array<int, string> */
    private function expenseItemOptions(): array
    {
        return ExpenseItem::query()
            ->with(['expense.supplier', 'taxCode', 'ledgerAccount'])
            ->whereDoesntHave('fixedAssetTransactions')
            ->whereDoesntHave('purchaseCreditNoteItems.purchaseCreditNote', fn (Builder $query): Builder => $query
                ->where('status', '!=', PurchaseCreditNote::STATUS_VOID))
            ->whereHas('expense', function (Builder $query): void {
                $query->whereIn('status', [Expense::STATUS_POSTED, Expense::STATUS_PARTIALLY_PAID, Expense::STATUS_PAID])
                    ->whereHas('journalEntries', fn (Builder $journal): Builder => $journal
                        ->where('status', JournalEntry::STATUS_POSTED)->whereNull('reversal_of_id'))
                    ->whereHas('attachments', fn (Builder $attachment): Builder => $attachment
                        ->where('scan_status', FinancialAttachment::SCAN_CLEAN));
            })
            ->orderByDesc('id')
            ->get()
            ->mapWithKeys(function (ExpenseItem $item): array {
                try {
                    $amount = app(FixedAssetCostBasisService::class)->forExpenseItem($item);
                } catch (DomainException) {
                    return [];
                }

                $reference = $item->expense?->supplier_invoice_number ?: '#'.$item->expense_id;

                return [$item->id => "{$reference} - {$item->description} - £{$amount}"];
            })->all();
    }

    /** @return array<int, string> */
    private function invoiceItemOptions(): array
    {
        return InvoiceItem::query()
            ->with('invoice')
            ->whereDoesntHave('fixedAssetTransactions')
            ->whereDoesntHave('creditNoteItems.creditNote', fn (Builder $query): Builder => $query
                ->where('status', '!=', CreditNote::STATUS_VOID))
            ->whereHas('invoice', fn (Builder $query): Builder => $query
                ->whereIn('status', ['sent', 'partially_paid', 'paid', 'overdue'])
                ->whereHas('journalEntries', fn (Builder $journal): Builder => $journal
                    ->where('status', JournalEntry::STATUS_POSTED)->whereNull('reversal_of_id')))
            ->orderByDesc('id')
            ->get()
            ->mapWithKeys(function (InvoiceItem $item): array {
                try {
                    $amount = app(FixedAssetCostBasisService::class)->forInvoiceItem($item);
                } catch (DomainException) {
                    return [];
                }

                return [$item->id => "{$item->invoice?->number} - {$item->description} - £{$amount}"];
            })->all();
    }

    private function costPreview(int $itemId): string
    {
        $item = $itemId > 0 ? ExpenseItem::query()->with('ledgerAccount')->find($itemId) : null;
        if (! $item) {
            return __('finance.fixed_assets.journal_preview.select_source');
        }

        try {
            $amount = app(FixedAssetCostBasisService::class)->forExpenseItem($item);
        } catch (DomainException $exception) {
            return $exception->getMessage();
        }

        return __('finance.fixed_assets.journal_preview.cost', [
            'asset' => $this->record->category?->assetAccount?->code,
            'expense' => $item->ledgerAccount?->code,
            'amount' => $amount,
        ]);
    }

    private function disposalPreview(int $itemId): string
    {
        $item = $itemId > 0 ? InvoiceItem::query()->find($itemId) : null;
        if (! $item) {
            return __('finance.fixed_assets.journal_preview.select_source');
        }

        try {
            $proceeds = app(FixedAssetCostBasisService::class)->forInvoiceItem($item);
        } catch (DomainException $exception) {
            return $exception->getMessage();
        }

        return __('finance.fixed_assets.journal_preview.disposal', [
            'proceeds' => $proceeds,
            'cost' => $this->record->acquisition_cost_gbp,
        ]);
    }

    private function latestPostedTransaction(): ?FixedAssetTransaction
    {
        return FixedAssetTransaction::query()
            ->where('fixed_asset_id', $this->record->id)
            ->where('status', FixedAssetTransaction::STATUS_POSTED)
            ->orderByDesc('event_date')->orderByDesc('id')->first();
    }

    private function canPost(): bool
    {
        return PermissionHelper::allows(auth()->user(), 'post_finance');
    }

    private function runWorkflow(callable $workflow, string $successTitle): void
    {
        try {
            $workflow();
            Notification::make()->title($successTitle)->success()->send();
            $this->redirect(FixedAssetResource::getUrl('view', ['record' => $this->record->id]));
        } catch (DomainException $exception) {
            Notification::make()->title($exception->getMessage())->danger()->send();
        }
    }
}
