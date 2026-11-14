<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PurchaseCreditNoteResource\Pages;
use App\Filament\Support\Currency as FilamentCurrency;
use App\Models\PurchaseCreditNote;
use App\Models\PurchaseCreditNoteItem;
use Filament\Actions\ViewAction;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class PurchaseCreditNoteResource extends BaseResource
{
    protected static ?string $model = PurchaseCreditNote::class;

    protected static ?string $recordTitleAttribute = 'number';

    protected static \BackedEnum|string|null $navigationIcon = 'heroicon-o-arrow-uturn-left';

    protected static \UnitEnum|string|null $navigationGroup = 'Finance';

    protected static ?int $navigationSort = 6;

    public static function getNavigationLabel(): string
    {
        return __('finance.purchase_credit_notes.navigation');
    }

    public static function getModelLabel(): string
    {
        return __('finance.purchase_credit_notes.singular');
    }

    public static function getPluralModelLabel(): string
    {
        return __('finance.purchase_credit_notes.plural');
    }

    /** @return array<string> */
    public static function getGloballySearchableAttributes(): array
    {
        return ['number', 'supplier.name', 'expense.supplier_invoice_number'];
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema->schema([
            Section::make(__('finance.purchase_credit_notes.sections.details'))
                ->columns(4)
                ->schema([
                    TextEntry::make('number')->label(__('finance.purchase_credit_notes.fields.number'))->weight('bold')->copyable(),
                    TextEntry::make('status')->label(__('finance.purchase_credit_notes.fields.status'))->badge()->formatStateUsing(fn (string $state): string => __('finance.purchase_credit_notes.statuses.'.$state))->color(fn (string $state): string => self::statusColor($state)),
                    TextEntry::make('expense.supplier_invoice_number')->label(__('finance.purchase_credit_notes.fields.expense'))->url(fn (PurchaseCreditNote $record): string => ExpenseResource::getUrl('view', ['record' => $record->expense_id])),
                    TextEntry::make('supplier.name')->label(__('finance.purchase_credit_notes.fields.supplier')),
                    TextEntry::make('issue_date')->label(__('finance.purchase_credit_notes.fields.issue_date'))->date(),
                    TextEntry::make('tax_point')->label(__('finance.fields.tax_point'))->date(),
                    TextEntry::make('currency')->label(__('finance.purchase_credit_notes.fields.currency'))->badge(),
                    TextEntry::make('reason')->label(__('finance.purchase_credit_notes.fields.reason'))->columnSpanFull(),
                ]),
            Section::make(__('finance.purchase_credit_notes.sections.items'))
                ->schema([
                    RepeatableEntry::make('items')
                        ->hiddenLabel()
                        ->columns(6)
                        ->schema([
                            TextEntry::make('description')->label(__('finance.purchase_credit_notes.fields.description'))->columnSpan(2),
                            TextEntry::make('quantity')->label(__('finance.purchase_credit_notes.fields.quantity')),
                            TextEntry::make('vat_rate_snapshot')->label(__('finance.purchase_credit_notes.fields.vat_rate'))->suffix('%'),
                            TextEntry::make('net_amount')->label(__('finance.purchase_credit_notes.fields.net'))->formatStateUsing(fn ($state, PurchaseCreditNoteItem $record): string => FilamentCurrency::format($state, $record->purchaseCreditNote?->currency)),
                            TextEntry::make('gross_amount')->label(__('finance.purchase_credit_notes.fields.gross'))->formatStateUsing(fn ($state, PurchaseCreditNoteItem $record): string => FilamentCurrency::format($state, $record->purchaseCreditNote?->currency)),
                        ]),
                ]),
            Section::make(__('finance.purchase_credit_notes.sections.settlement'))
                ->columns(5)
                ->schema([
                    TextEntry::make('total')->label(__('finance.purchase_credit_notes.fields.total'))->formatStateUsing(fn ($state, PurchaseCreditNote $record): string => FilamentCurrency::format($state, $record->currency))->weight('bold'),
                    TextEntry::make('allocated_amount')->label(__('finance.purchase_credit_notes.fields.allocated'))->formatStateUsing(fn ($state, PurchaseCreditNote $record): string => FilamentCurrency::format($state, $record->currency)),
                    TextEntry::make('refunded_amount')->label(__('finance.purchase_credit_notes.fields.refunded'))->formatStateUsing(fn ($state, PurchaseCreditNote $record): string => FilamentCurrency::format($state, $record->currency)),
                    TextEntry::make('remaining_amount')->label(__('finance.purchase_credit_notes.fields.remaining'))->state(fn (PurchaseCreditNote $record): float => $record->remainingAmount())->formatStateUsing(fn ($state, PurchaseCreditNote $record): string => FilamentCurrency::format($state, $record->currency)),
                    TextEntry::make('refunds_count')->label(__('finance.purchase_credit_notes.fields.refunds'))->state(fn (PurchaseCreditNote $record): int => $record->refunds->count()),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('number')->label(__('finance.purchase_credit_notes.fields.number'))->searchable()->sortable()->weight('bold'),
                Tables\Columns\TextColumn::make('supplier.name')->label(__('finance.purchase_credit_notes.fields.supplier'))->searchable()->sortable()->visibleFrom('md'),
                Tables\Columns\TextColumn::make('expense.supplier_invoice_number')->label(__('finance.purchase_credit_notes.fields.expense'))->searchable()->visibleFrom('lg'),
                Tables\Columns\TextColumn::make('status')->label(__('finance.purchase_credit_notes.fields.status'))->badge()->formatStateUsing(fn (string $state): string => __('finance.purchase_credit_notes.statuses.'.$state))->color(fn (string $state): string => self::statusColor($state)),
                Tables\Columns\TextColumn::make('total')->label(__('finance.purchase_credit_notes.fields.total'))->money(fn (PurchaseCreditNote $record): string => FilamentCurrency::tableCurrency($record))->sortable(),
                Tables\Columns\TextColumn::make('issue_date')->label(__('finance.purchase_credit_notes.fields.issue_date'))->date()->sortable()->visibleFrom('md'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')->options(collect(PurchaseCreditNote::statuses())->mapWithKeys(fn (string $status): array => [$status => __('finance.purchase_credit_notes.statuses.'.$status)])->all()),
                Tables\Filters\SelectFilter::make('supplier_id')->relationship('supplier', 'name')->searchable()->preload(),
            ])
            ->actions([ViewAction::make()])
            ->recordUrl(fn (PurchaseCreditNote $record): string => static::getUrl('view', ['record' => $record]))
            ->emptyStateIcon('heroicon-o-arrow-uturn-left')
            ->emptyStateHeading(__('admin.migration.procurement.purchase_credit_notes.empty_heading'))
            ->emptyStateDescription(__('admin.migration.procurement.purchase_credit_notes.empty_description'))
            ->defaultSort('issue_date', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPurchaseCreditNotes::route('/'),
            'view' => Pages\ViewPurchaseCreditNote::route('/{record}'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->with(['expense', 'supplier', 'items.purchaseCreditNote', 'refunds']);
    }

    private static function statusColor(string $status): string
    {
        return match ($status) {
            PurchaseCreditNote::STATUS_DRAFT => 'gray',
            PurchaseCreditNote::STATUS_ISSUED => 'info',
            PurchaseCreditNote::STATUS_PARTIALLY_SETTLED => 'warning',
            PurchaseCreditNote::STATUS_SETTLED => 'success',
            PurchaseCreditNote::STATUS_VOID => 'danger',
            default => 'gray',
        };
    }
}
