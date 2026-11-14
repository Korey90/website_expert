<?php

namespace App\Filament\Resources;

use App\Filament\Resources\JournalEntryResource\Pages;
use App\Models\JournalEntry;
use App\Models\JournalLine;
use App\Models\LedgerAccount;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class JournalEntryResource extends BaseResource
{
    protected static ?string $model = JournalEntry::class;

    protected static ?string $recordTitleAttribute = 'entry_number';

    protected static \BackedEnum|string|null $navigationIcon = 'heroicon-o-book-open';

    protected static \UnitEnum|string|null $navigationGroup = 'Finance';

    protected static ?int $navigationSort = 10;

    public static function getNavigationLabel(): string
    {
        return __('finance.journals.navigation');
    }

    public static function getModelLabel(): string
    {
        return __('finance.journals.singular');
    }

    public static function getPluralModelLabel(): string
    {
        return __('finance.journals.plural');
    }

    /** @return array<string> */
    public static function getGloballySearchableAttributes(): array
    {
        return ['entry_number', 'description'];
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema->schema([
            Section::make(__('finance.journals.sections.summary'))
                ->columns(4)
                ->schema([
                    TextEntry::make('entry_number')->label(__('finance.journals.fields.number'))->weight('bold')->copyable(),
                    TextEntry::make('entry_date')->label(__('finance.journals.fields.date'))->date('d M Y'),
                    TextEntry::make('status')->label(__('finance.journals.fields.status'))->badge()
                        ->formatStateUsing(fn (string $state): string => __('finance.journals.statuses.'.$state))
                        ->color(fn (string $state): string => self::statusColor($state)),
                    TextEntry::make('accountingPeriod.name')->label(__('finance.journals.fields.period')),
                    TextEntry::make('description')->label(__('finance.journals.fields.description'))->columnSpan(2),
                    TextEntry::make('currency')->label(__('finance.journals.fields.currency'))->badge(),
                    TextEntry::make('posted_at')->label(__('finance.journals.fields.posted_at'))->dateTime()->placeholder('—'),
                    TextEntry::make('createdBy.name')->label(__('finance.journals.fields.created_by'))->placeholder('—'),
                    TextEntry::make('postedBy.name')->label(__('finance.journals.fields.posted_by'))->placeholder('—'),
                    TextEntry::make('reversedBy.name')->label(__('finance.journals.fields.reversed_by'))->placeholder('—'),
                    TextEntry::make('reversed_at')->label(__('finance.journals.fields.reversed_at'))->dateTime()->placeholder('—'),
                    TextEntry::make('reversal_reason')->label(__('finance.journals.fields.reversal_reason'))->placeholder('—')->columnSpanFull(),
                ]),
            Section::make(__('finance.journals.sections.audit'))
                ->columns(3)
                ->collapsible()
                ->schema([
                    TextEntry::make('source_type')->label(__('finance.journals.fields.source_type'))
                        ->formatStateUsing(fn (?string $state): string => $state ? class_basename($state) : '—'),
                    TextEntry::make('source_id')->label(__('finance.journals.fields.source_id'))->placeholder('—'),
                    TextEntry::make('idempotency_key')->label(__('finance.journals.fields.idempotency_key'))->copyable(),
                    TextEntry::make('source_snapshot_hash')->label(__('finance.journals.fields.snapshot_hash'))->copyable()->columnSpanFull(),
                    TextEntry::make('reversalOf.entry_number')->label(__('finance.journals.fields.reversal_of'))->placeholder('—')
                        ->url(fn (JournalEntry $record): ?string => $record->reversal_of_id
                            ? self::getUrl('view', ['record' => $record->reversal_of_id])
                            : null),
                    TextEntry::make('reversal.entry_number')->label(__('finance.journals.fields.reversal'))->placeholder('—')
                        ->url(fn (JournalEntry $record): ?string => $record->reversal
                            ? self::getUrl('view', ['record' => $record->reversal])
                            : null),
                ]),
            Section::make(__('finance.journals.sections.lines'))
                ->schema([
                    RepeatableEntry::make('lines')
                        ->hiddenLabel()
                        ->columns(6)
                        ->schema([
                            TextEntry::make('ledger_account')->label(__('finance.journals.fields.account'))
                                ->state(fn (JournalLine $record): string => $record->ledgerAccount->code.' — '.$record->ledgerAccount->name)
                                ->columnSpan(2),
                            TextEntry::make('memo')->label(__('finance.journals.fields.memo'))->placeholder('—')->columnSpan(2),
                            TextEntry::make('debit_gbp')->label(__('finance.journals.fields.debit'))
                                ->formatStateUsing(fn ($state): string => '£'.number_format((float) $state, 2)),
                            TextEntry::make('credit_gbp')->label(__('finance.journals.fields.credit'))
                                ->formatStateUsing(fn ($state): string => '£'.number_format((float) $state, 2)),
                            TextEntry::make('taxCode.code')->label(__('finance.journals.fields.tax_code'))->placeholder('—'),
                            TextEntry::make('client.company_name')->label(__('finance.journals.fields.client'))->placeholder('—'),
                            TextEntry::make('supplier.name')->label(__('finance.journals.fields.supplier'))->placeholder('—'),
                            TextEntry::make('project.title')->label(__('finance.journals.fields.project'))->placeholder('—'),
                            TextEntry::make('original_currency')->label(__('finance.journals.fields.original_currency')),
                            TextEntry::make('original_amount')->label(__('finance.journals.fields.original_amount'))->placeholder('—'),
                            TextEntry::make('exchange_rate')->label(__('finance.journals.fields.exchange_rate'))->placeholder('—'),
                            TextEntry::make('dimensions')->label(__('finance.journals.fields.dimensions'))
                                ->formatStateUsing(fn (?array $state): string => $state ? json_encode($state, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) : '—')
                                ->columnSpan(3),
                        ])
                        ->columnSpanFull(),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('entry_number')->label(__('finance.journals.fields.number'))->searchable()->sortable()->weight('bold'),
                Tables\Columns\TextColumn::make('entry_date')->label(__('finance.journals.fields.date'))->date()->sortable()->visibleFrom('md'),
                Tables\Columns\TextColumn::make('accountingPeriod.name')->label(__('finance.journals.fields.period'))->sortable()->visibleFrom('lg'),
                Tables\Columns\TextColumn::make('description')->label(__('finance.journals.fields.description'))->searchable()->limit(50)->visibleFrom('md'),
                Tables\Columns\TextColumn::make('status')->label(__('finance.journals.fields.status'))->badge()
                    ->formatStateUsing(fn (string $state): string => __('finance.journals.statuses.'.$state))
                    ->color(fn (string $state): string => self::statusColor($state)),
                Tables\Columns\TextColumn::make('debit_total')->label(__('finance.journals.fields.debit'))->money('GBP')->sortable(),
                Tables\Columns\TextColumn::make('credit_total')->label(__('finance.journals.fields.credit'))->money('GBP')->sortable()->visibleFrom('lg')->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label(__('finance.journals.fields.status'))
                    ->options([
                        JournalEntry::STATUS_DRAFT => __('finance.journals.statuses.draft'),
                        JournalEntry::STATUS_POSTED => __('finance.journals.statuses.posted'),
                        JournalEntry::STATUS_REVERSED => __('finance.journals.statuses.reversed'),
                    ]),
                Tables\Filters\SelectFilter::make('accounting_period_id')
                    ->label(__('finance.journals.fields.period'))
                    ->relationship('accountingPeriod', 'name')
                    ->searchable()
                    ->preload(),
                Filter::make('entry_date')
                    ->form([
                        DatePicker::make('from')->label(__('finance.general_ledger.fields.date_from')),
                        DatePicker::make('to')->label(__('finance.general_ledger.fields.date_to')),
                    ])
                    ->query(fn (Builder $query, array $data): Builder => $query
                        ->when($data['from'] ?? null, fn (Builder $query, $date): Builder => $query->whereDate('entry_date', '>=', $date))
                        ->when($data['to'] ?? null, fn (Builder $query, $date): Builder => $query->whereDate('entry_date', '<=', $date))),
                Filter::make('ledger_account_id')
                    ->form([
                        Select::make('account_id')
                            ->label(__('finance.journals.fields.account'))
                            ->options(fn (): array => LedgerAccount::query()
                                ->orderBy('code')
                                ->get()
                                ->mapWithKeys(fn (LedgerAccount $account): array => [$account->id => $account->code.' — '.$account->name])
                                ->all())
                            ->searchable(),
                    ])
                    ->query(fn (Builder $query, array $data): Builder => $query
                        ->when($data['account_id'] ?? null, fn (Builder $query, $accountId): Builder => $query
                            ->whereHas('lines', fn (Builder $lines): Builder => $lines->where('ledger_account_id', $accountId)))),
            ])
            ->actions([ViewAction::make()])
            ->recordUrl(fn (JournalEntry $record): string => static::getUrl('view', ['record' => $record]))
            ->emptyStateIcon('heroicon-o-book-open')
            ->emptyStateHeading(__('admin.migration.accounting.journals.empty_heading'))
            ->emptyStateDescription(__('admin.migration.accounting.journals.empty_description'))
            ->defaultSort('entry_date', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListJournalEntries::route('/'),
            'view' => Pages\ViewJournalEntry::route('/{record}'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->with(['accountingPeriod', 'createdBy', 'postedBy', 'reversedBy', 'reversalOf', 'reversal'])
            ->withSum('lines as debit_total', 'debit_gbp')
            ->withSum('lines as credit_total', 'credit_gbp');
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
            JournalEntry::STATUS_DRAFT => 'gray',
            JournalEntry::STATUS_POSTED => 'success',
            JournalEntry::STATUS_REVERSED => 'warning',
            default => 'gray',
        };
    }
}
