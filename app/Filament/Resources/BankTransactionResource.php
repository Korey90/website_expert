<?php

namespace App\Filament\Resources;

use App\Filament\Resources\BankTransactionResource\Pages;
use App\Models\BankReconciliation;
use App\Models\BankReconciliationAllocation;
use App\Models\BankTransaction;
use Filament\Actions\ViewAction;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class BankTransactionResource extends BaseResource
{
    protected static ?string $model = BankTransaction::class;

    protected static \BackedEnum|string|null $navigationIcon = 'heroicon-o-arrows-right-left';

    protected static \UnitEnum|string|null $navigationGroup = 'Finance';

    protected static ?int $navigationSort = 13;

    public static function getNavigationLabel(): string
    {
        return __('finance.bank_reconciliation.navigation');
    }

    public static function getModelLabel(): string
    {
        return __('finance.bank_reconciliation.singular');
    }

    public static function getPluralModelLabel(): string
    {
        return __('finance.bank_reconciliation.plural');
    }

    /** @return array<string> */
    public static function getGloballySearchableAttributes(): array
    {
        return ['reference', 'counterparty', 'bankAccount.name'];
    }

    public static function getGlobalSearchResultTitle(Model $record): string
    {
        return $record->reference ?: $record->counterparty ?: '#'.$record->getKey();
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema->schema([
            Section::make(__('finance.bank_reconciliation.sections.transaction'))
                ->columns(4)
                ->schema([
                    TextEntry::make('booking_date')->label(__('finance.bank_reconciliation.fields.booking_date'))->date('d M Y'),
                    TextEntry::make('value_date')->label(__('finance.bank_reconciliation.fields.value_date'))->date('d M Y')->placeholder('—'),
                    TextEntry::make('amount')->label(__('finance.bank_reconciliation.fields.amount'))
                        ->formatStateUsing(fn ($state, BankTransaction $record): string => number_format((float) $state, 2).' '.$record->currency)
                        ->weight('bold'),
                    TextEntry::make('allocated_amount')->label(__('finance.bank_reconciliation.fields.allocated_amount'))
                        ->state(fn (BankTransaction $record): string => number_format((float) $record->reconciliationAllocations
                            ->filter(fn (BankReconciliationAllocation $allocation): bool => $allocation->reconciliation?->status === BankReconciliation::STATUS_ACTIVE)
                            ->sum('amount'), 2).' '.$record->currency),
                    TextEntry::make('remaining_amount')->label(__('finance.bank_reconciliation.fields.remaining_amount'))
                        ->state(function (BankTransaction $record): string {
                            $allocated = (float) $record->reconciliationAllocations
                                ->filter(fn (BankReconciliationAllocation $allocation): bool => $allocation->reconciliation?->status === BankReconciliation::STATUS_ACTIVE)
                                ->sum('amount');

                            return number_format(max(0, abs((float) $record->amount) - $allocated), 2).' '.$record->currency;
                        }),
                    TextEntry::make('reconciliation_status')->label(__('finance.bank_reconciliation.fields.status'))->badge()
                        ->formatStateUsing(fn (string $state): string => __('finance.bank_reconciliation.statuses.'.$state))
                        ->color(fn (string $state): string => self::statusColor($state)),
                    TextEntry::make('bankAccount.name')->label(__('finance.bank_reconciliation.fields.account')),
                    TextEntry::make('counterparty')->label(__('finance.bank_reconciliation.fields.counterparty'))->placeholder('—'),
                    TextEntry::make('reference')->label(__('finance.bank_reconciliation.fields.reference'))->placeholder('—')->columnSpan(2),
                ]),
            Section::make(__('finance.bank_reconciliation.sections.allocations'))
                ->schema([
                    RepeatableEntry::make('reconciliationAllocations')
                        ->hiddenLabel()
                        ->columns(6)
                        ->schema([
                            TextEntry::make('event_type')->label(__('finance.bank_reconciliation.fields.event_type'))
                                ->formatStateUsing(fn (string $state): string => __('finance.bank_reconciliation.events.'.$state)),
                            TextEntry::make('candidate_key')->label(__('finance.bank_reconciliation.fields.source'))->columnSpan(2),
                            TextEntry::make('amount')->label(__('finance.bank_reconciliation.fields.allocated_amount'))
                                ->formatStateUsing(fn ($state, BankReconciliationAllocation $record): string => number_format((float) $state, 2).' '.$record->currency),
                            TextEntry::make('source_amount')->label(__('finance.bank_reconciliation.fields.source_amount'))
                                ->formatStateUsing(fn ($state, BankReconciliationAllocation $record): string => number_format((float) $state, 2).' '.$record->source_currency),
                            TextEntry::make('match_score')->label(__('finance.bank_reconciliation.fields.score'))
                                ->suffix('/100')->placeholder('—'),
                            TextEntry::make('reconciliation.status')->label(__('finance.bank_reconciliation.fields.batch_status'))->badge()
                                ->formatStateUsing(fn (string $state): string => __('finance.bank_reconciliation.reconciliation_statuses.'.$state))
                                ->color(fn (string $state): string => $state === BankReconciliation::STATUS_ACTIVE ? 'success' : 'gray'),
                            TextEntry::make('reconciliation.createdBy.name')->label(__('finance.bank_reconciliation.fields.reconciled_by'))->placeholder('—'),
                            TextEntry::make('reconciliation.reconciled_at')->label(__('finance.bank_reconciliation.fields.reconciled_at'))->dateTime(),
                            TextEntry::make('reconciliation.notes')->label(__('finance.bank_reconciliation.fields.notes'))->placeholder('—')->columnSpan(3),
                            TextEntry::make('reconciliation.reversedBy.name')->label(__('finance.bank_reconciliation.fields.reversed_by'))->placeholder('—'),
                            TextEntry::make('reconciliation.reversed_at')->label(__('finance.bank_reconciliation.fields.reversed_at'))->dateTime()->placeholder('—'),
                            TextEntry::make('reconciliation.reversal_reason')->label(__('finance.bank_reconciliation.fields.reversal_reason'))->placeholder('—')->columnSpan(4),
                        ])
                        ->columnSpanFull(),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('booking_date')->label(__('finance.bank_reconciliation.fields.booking_date'))->date()->sortable()->weight('bold'),
                Tables\Columns\TextColumn::make('bankAccount.name')->label(__('finance.bank_reconciliation.fields.account'))->searchable()->sortable()->visibleFrom('md'),
                Tables\Columns\TextColumn::make('counterparty')->label(__('finance.bank_reconciliation.fields.counterparty'))->searchable()->placeholder('—')->visibleFrom('md'),
                Tables\Columns\TextColumn::make('reference')->label(__('finance.bank_reconciliation.fields.reference'))->searchable()->limit(40)->placeholder('—')->visibleFrom('lg'),
                Tables\Columns\TextColumn::make('amount')->label(__('finance.bank_reconciliation.fields.amount'))
                    ->money(fn (BankTransaction $record): string => $record->currency)
                    ->color(fn ($state): string => (float) $state >= 0 ? 'success' : 'danger')
                    ->sortable(),
                Tables\Columns\TextColumn::make('reconciliation_status')->label(__('finance.bank_reconciliation.fields.status'))->badge()
                    ->formatStateUsing(fn (string $state): string => __('finance.bank_reconciliation.statuses.'.$state))
                    ->color(fn (string $state): string => self::statusColor($state))
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('bank_account_id')
                    ->label(__('finance.bank_reconciliation.fields.account'))
                    ->relationship('bankAccount', 'name')
                    ->searchable()
                    ->preload(),
                Tables\Filters\SelectFilter::make('reconciliation_status')
                    ->label(__('finance.bank_reconciliation.fields.status'))
                    ->options([
                        BankTransaction::STATUS_UNMATCHED => __('finance.bank_reconciliation.statuses.unmatched'),
                        BankTransaction::STATUS_PARTIALLY_RECONCILED => __('finance.bank_reconciliation.statuses.partially_reconciled'),
                        BankTransaction::STATUS_RECONCILED => __('finance.bank_reconciliation.statuses.reconciled'),
                    ]),
            ])
            ->actions([ViewAction::make()])
            ->recordUrl(fn (BankTransaction $record): string => static::getUrl('view', ['record' => $record]))
            ->emptyStateIcon('heroicon-o-arrows-right-left')
            ->emptyStateHeading(__('admin.migration.banking.transactions.empty_heading'))
            ->emptyStateDescription(__('admin.migration.banking.transactions.empty_description'))
            ->defaultSort('booking_date', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListBankTransactions::route('/'),
            'view' => Pages\ViewBankTransaction::route('/{record}'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->with([
            'bankAccount',
            'reconciliationAllocations.reconciliation.createdBy',
            'reconciliationAllocations.reconciliation.reversedBy',
        ]);
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canEdit(Model $record): bool
    {
        return false;
    }

    public static function canDelete(Model $record): bool
    {
        return false;
    }

    public static function canDeleteAny(): bool
    {
        return false;
    }

    private static function statusColor(string $status): string
    {
        return match ($status) {
            BankTransaction::STATUS_UNMATCHED => 'warning',
            BankTransaction::STATUS_PARTIALLY_RECONCILED => 'info',
            BankTransaction::STATUS_RECONCILED => 'success',
            default => 'gray',
        };
    }
}
