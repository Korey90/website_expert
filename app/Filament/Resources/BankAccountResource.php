<?php

namespace App\Filament\Resources;

use App\Filament\Resources\BankAccountResource\Pages;
use App\Filament\Support\Currency as FilamentCurrency;
use App\Models\BankAccount;
use App\Models\LedgerAccount;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class BankAccountResource extends BaseResource
{
    protected static ?string $model = BankAccount::class;

    protected static \BackedEnum|string|null $navigationIcon = 'heroicon-o-building-library';

    protected static \UnitEnum|string|null $navigationGroup = 'Finance';

    protected static ?int $navigationSort = 12;

    public static function getNavigationLabel(): string
    {
        return __('finance.bank_accounts.navigation');
    }

    public static function getModelLabel(): string
    {
        return __('finance.bank_accounts.singular');
    }

    public static function getPluralModelLabel(): string
    {
        return __('finance.bank_accounts.plural');
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->schema([
            Section::make(__('finance.bank_accounts.sections.identity'))
                ->columns(2)
                ->schema([
                    Forms\Components\TextInput::make('name')
                        ->label(__('finance.bank_accounts.fields.name'))
                        ->required()
                        ->maxLength(255),
                    Forms\Components\TextInput::make('bank_name')
                        ->label(__('finance.bank_accounts.fields.bank_name'))
                        ->required()
                        ->maxLength(255),
                    Forms\Components\TextInput::make('account_number')
                        ->label(__('finance.bank_accounts.fields.account_number'))
                        ->password()
                        ->revealable()
                        ->autocomplete('off')
                        ->required(fn (?BankAccount $record): bool => $record === null)
                        ->helperText(fn (?BankAccount $record): string => $record
                            ? __('finance.bank_accounts.hints.replace_account', ['masked' => $record->masked_account_number])
                            : __('finance.bank_accounts.hints.account_number')),
                    Forms\Components\TextInput::make('sort_code')
                        ->label(__('finance.bank_accounts.fields.sort_code'))
                        ->password()
                        ->revealable()
                        ->autocomplete('off')
                        ->required(fn (?BankAccount $record): bool => $record === null)
                        ->helperText(fn (?BankAccount $record): string => $record
                            ? __('finance.bank_accounts.hints.replace_sort_code', ['masked' => $record->masked_sort_code])
                            : __('finance.bank_accounts.hints.sort_code')),
                ]),
            Section::make(__('finance.bank_accounts.sections.accounting'))
                ->columns(3)
                ->schema([
                    Forms\Components\Select::make('ledger_account_id')
                        ->label(__('finance.bank_accounts.fields.ledger_account'))
                        ->options(fn (): array => LedgerAccount::activePostable()
                            ->where('type', 'asset')
                            ->where('subtype', 'cash')
                            ->orderBy('code')
                            ->get()
                            ->mapWithKeys(fn (LedgerAccount $account): array => [$account->id => $account->code.' — '.$account->name])
                            ->all())
                        ->searchable()
                        ->preload()
                        ->required(),
                    Forms\Components\Select::make('currency')
                        ->label(__('finance.bank_accounts.fields.currency'))
                        ->options(fn (): array => FilamentCurrency::options())
                        ->default('GBP')
                        ->required(),
                    Forms\Components\Select::make('status')
                        ->label(__('finance.bank_accounts.fields.status'))
                        ->options([
                            BankAccount::STATUS_ACTIVE => __('finance.bank_accounts.statuses.active'),
                            BankAccount::STATUS_ARCHIVED => __('finance.bank_accounts.statuses.archived'),
                        ])
                        ->default(BankAccount::STATUS_ACTIVE)
                        ->required(),
                    Forms\Components\TextInput::make('opening_balance')
                        ->label(__('finance.bank_accounts.fields.opening_balance'))
                        ->numeric()
                        ->default(0)
                        ->required(),
                    Forms\Components\DatePicker::make('opening_balance_date')
                        ->label(__('finance.bank_accounts.fields.opening_balance_date')),
                ]),
        ]);
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema->schema([
            Section::make(__('finance.bank_accounts.sections.identity'))
                ->columns(3)
                ->schema([
                    TextEntry::make('name')->label(__('finance.bank_accounts.fields.name'))->weight('bold'),
                    TextEntry::make('bank_name')->label(__('finance.bank_accounts.fields.bank_name')),
                    TextEntry::make('status')->label(__('finance.bank_accounts.fields.status'))->badge()
                        ->formatStateUsing(fn (string $state): string => __('finance.bank_accounts.statuses.'.$state))
                        ->color(fn (string $state): string => $state === BankAccount::STATUS_ACTIVE ? 'success' : 'gray'),
                    TextEntry::make('masked_account_number')->label(__('finance.bank_accounts.fields.account_number'))->fontFamily('mono'),
                    TextEntry::make('masked_sort_code')->label(__('finance.bank_accounts.fields.sort_code'))->fontFamily('mono'),
                    TextEntry::make('currency')->label(__('finance.bank_accounts.fields.currency'))->badge(),
                ]),
            Section::make(__('finance.bank_accounts.sections.accounting'))
                ->columns(3)
                ->schema([
                    TextEntry::make('ledgerAccount.code')->label(__('finance.bank_accounts.fields.ledger_account')),
                    TextEntry::make('opening_balance')->label(__('finance.bank_accounts.fields.opening_balance'))
                        ->formatStateUsing(fn ($state, BankAccount $record): string => FilamentCurrency::format($state, $record->currency)),
                    TextEntry::make('opening_balance_date')->label(__('finance.bank_accounts.fields.opening_balance_date'))->date()->placeholder('—'),
                    TextEntry::make('last_synced_at')->label(__('finance.bank_accounts.fields.last_synced_at'))->dateTime()->placeholder(__('finance.bank_accounts.never_synced')),
                    TextEntry::make('createdBy.name')->label(__('finance.bank_accounts.fields.created_by'))->placeholder('—'),
                    TextEntry::make('archivedBy.name')->label(__('finance.bank_accounts.fields.archived_by'))->placeholder('—'),
                    TextEntry::make('archived_at')->label(__('finance.bank_accounts.fields.archived_at'))->dateTime()->placeholder('—'),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')->label(__('finance.bank_accounts.fields.name'))->searchable()->sortable()->weight('bold'),
                Tables\Columns\TextColumn::make('bank_name')->label(__('finance.bank_accounts.fields.bank_name'))->searchable()->sortable(),
                Tables\Columns\TextColumn::make('masked_account_number')->label(__('finance.bank_accounts.fields.account_number'))->fontFamily('mono'),
                Tables\Columns\TextColumn::make('masked_sort_code')->label(__('finance.bank_accounts.fields.sort_code'))->fontFamily('mono'),
                Tables\Columns\TextColumn::make('currency')->label(__('finance.bank_accounts.fields.currency'))->badge()->sortable(),
                Tables\Columns\TextColumn::make('ledgerAccount.code')->label(__('finance.bank_accounts.fields.ledger_account'))->sortable(),
                Tables\Columns\TextColumn::make('status')->label(__('finance.bank_accounts.fields.status'))->badge()
                    ->formatStateUsing(fn (string $state): string => __('finance.bank_accounts.statuses.'.$state))
                    ->color(fn (string $state): string => $state === BankAccount::STATUS_ACTIVE ? 'success' : 'gray'),
                Tables\Columns\TextColumn::make('last_synced_at')->label(__('finance.bank_accounts.fields.last_synced_at'))->dateTime()->placeholder(__('finance.bank_accounts.never_synced'))->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')->options([
                    BankAccount::STATUS_ACTIVE => __('finance.bank_accounts.statuses.active'),
                    BankAccount::STATUS_ARCHIVED => __('finance.bank_accounts.statuses.archived'),
                ]),
                Tables\Filters\SelectFilter::make('currency')->options(fn (): array => FilamentCurrency::options()),
            ])
            ->actions([ViewAction::make(), EditAction::make()])
            ->defaultSort('name');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListBankAccounts::route('/'),
            'create' => Pages\CreateBankAccount::route('/create'),
            'view' => Pages\ViewBankAccount::route('/{record}'),
            'edit' => Pages\EditBankAccount::route('/{record}/edit'),
        ];
    }

    public static function canDelete(Model $record): bool
    {
        return false;
    }

    public static function canDeleteAny(): bool
    {
        return false;
    }
}
