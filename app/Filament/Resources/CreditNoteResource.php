<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CreditNoteResource\Pages;
use App\Filament\Support\Currency as FilamentCurrency;
use App\Models\CreditNote;
use App\Models\CreditNoteItem;
use Filament\Actions\Action;
use Filament\Actions\ViewAction;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class CreditNoteResource extends BaseResource
{
    protected static ?string $model = CreditNote::class;

    protected static \BackedEnum|string|null $navigationIcon = 'heroicon-o-receipt-refund';

    protected static \UnitEnum|string|null $navigationGroup = 'Finance';

    protected static ?int $navigationSort = 3;

    public static function getNavigationLabel(): string
    {
        return __('finance.credit_notes.navigation');
    }

    public static function getModelLabel(): string
    {
        return __('finance.credit_notes.singular');
    }

    public static function getPluralModelLabel(): string
    {
        return __('finance.credit_notes.plural');
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema->schema([
            Section::make(__('finance.credit_notes.sections.details'))
                ->columns(4)
                ->schema([
                    TextEntry::make('number')->label(__('finance.credit_notes.fields.number'))->weight('bold')->copyable(),
                    TextEntry::make('status')
                        ->label(__('finance.credit_notes.fields.status'))
                        ->badge()
                        ->formatStateUsing(fn (string $state): string => __('finance.credit_notes.statuses.'.$state))
                        ->color(fn (string $state): string => self::statusColor($state)),
                    TextEntry::make('invoice.number')
                        ->label(__('finance.credit_notes.fields.invoice'))
                        ->url(fn (CreditNote $record): string => InvoiceResource::getUrl('view', ['record' => $record->invoice_id])),
                    TextEntry::make('client.company_name')->label(__('finance.credit_notes.fields.client')),
                    TextEntry::make('issue_date')->label(__('finance.credit_notes.fields.issue_date'))->date('d M Y'),
                    TextEntry::make('tax_point')->label(__('finance.fields.tax_point'))->date('d M Y'),
                    TextEntry::make('currency')->label(__('finance.credit_notes.fields.currency'))->badge(),
                    TextEntry::make('createdBy.name')->label(__('finance.credit_notes.fields.created_by')),
                    TextEntry::make('reason')->label(__('finance.credit_notes.fields.reason'))->columnSpanFull(),
                ]),

            Section::make(__('finance.credit_notes.sections.items'))
                ->schema([
                    RepeatableEntry::make('items')
                        ->hiddenLabel()
                        ->columns(6)
                        ->schema([
                            TextEntry::make('description')->label(__('finance.credit_notes.fields.description'))->columnSpan(2),
                            TextEntry::make('quantity')->label(__('finance.credit_notes.fields.quantity')),
                            TextEntry::make('vat_rate_snapshot')
                                ->label(__('finance.credit_notes.fields.vat_rate'))
                                ->formatStateUsing(fn ($state): string => number_format((float) $state, 2).'%'),
                            TextEntry::make('net_amount')
                                ->label(__('finance.credit_notes.fields.net'))
                                ->formatStateUsing(fn ($state, CreditNoteItem $record): string => FilamentCurrency::format($state, $record->creditNote?->currency)),
                            TextEntry::make('vat_amount')
                                ->label(__('finance.credit_notes.fields.vat'))
                                ->formatStateUsing(fn ($state, CreditNoteItem $record): string => FilamentCurrency::format($state, $record->creditNote?->currency)),
                        ])
                        ->columnSpanFull(),
                ]),

            Section::make(__('finance.credit_notes.sections.totals'))
                ->columns(4)
                ->schema([
                    TextEntry::make('subtotal')
                        ->label(__('finance.credit_notes.fields.net'))
                        ->formatStateUsing(fn ($state, CreditNote $record): string => FilamentCurrency::format($state, $record->currency)),
                    TextEntry::make('vat_amount')
                        ->label(__('finance.credit_notes.fields.vat'))
                        ->formatStateUsing(fn ($state, CreditNote $record): string => FilamentCurrency::format($state, $record->currency)),
                    TextEntry::make('total')
                        ->label(__('finance.credit_notes.fields.total'))
                        ->weight('bold')
                        ->formatStateUsing(fn ($state, CreditNote $record): string => FilamentCurrency::format($state, $record->currency)),
                    TextEntry::make('allocated_amount')
                        ->label(__('finance.credit_notes.fields.allocated'))
                        ->formatStateUsing(fn ($state, CreditNote $record): string => FilamentCurrency::format($state, $record->currency)),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('number')->label(__('finance.credit_notes.fields.number'))->searchable()->sortable()->weight('bold'),
                Tables\Columns\TextColumn::make('invoice.number')->label(__('finance.credit_notes.fields.invoice'))->searchable()->sortable(),
                Tables\Columns\TextColumn::make('client.company_name')->label(__('finance.credit_notes.fields.client'))->searchable()->sortable(),
                Tables\Columns\TextColumn::make('status')
                    ->label(__('finance.credit_notes.fields.status'))
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => __('finance.credit_notes.statuses.'.$state))
                    ->color(fn (string $state): string => self::statusColor($state)),
                Tables\Columns\TextColumn::make('total')->label(__('finance.credit_notes.fields.total'))->money(fn (CreditNote $record): string => FilamentCurrency::tableCurrency($record))->sortable(),
                Tables\Columns\TextColumn::make('allocated_amount')->label(__('finance.credit_notes.fields.allocated'))->money(fn (CreditNote $record): string => FilamentCurrency::tableCurrency($record))->sortable(),
                Tables\Columns\TextColumn::make('issue_date')->label(__('finance.credit_notes.fields.issue_date'))->date()->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('client_id')
                    ->label(__('finance.credit_notes.fields.client'))
                    ->relationship('client', 'company_name')
                    ->searchable()
                    ->preload(),
                Tables\Filters\SelectFilter::make('status')
                    ->options(collect(CreditNote::statuses())->mapWithKeys(
                        fn (string $status): array => [$status => __('finance.credit_notes.statuses.'.$status)]
                    )->all()),
            ])
            ->actions([
                ViewAction::make(),
                Action::make('pdf')
                    ->label('PDF')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->url(fn (CreditNote $record): string => route('credit-note.pdf', $record))
                    ->openUrlInNewTab(),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListCreditNotes::route('/'),
            'view' => Pages\ViewCreditNote::route('/{record}'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->with(['invoice', 'client', 'items.creditNote', 'items.taxCode']);
    }

    public static function canCreate(): bool
    {
        return false;
    }

    private static function statusColor(string $status): string
    {
        return match ($status) {
            CreditNote::STATUS_DRAFT => 'gray',
            CreditNote::STATUS_ISSUED => 'info',
            CreditNote::STATUS_PARTIALLY_ALLOCATED => 'warning',
            CreditNote::STATUS_ALLOCATED => 'success',
            CreditNote::STATUS_VOID => 'danger',
            default => 'gray',
        };
    }
}
