<?php

namespace App\Filament\Resources;

use App\Filament\Resources\LedgerAccountResource\Pages;
use App\Models\LedgerAccount;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Validation\Rules\Unique;

class LedgerAccountResource extends BaseResource
{
    protected static ?string $model = LedgerAccount::class;

    protected static \BackedEnum|string|null $navigationIcon = 'heroicon-o-book-open';

    protected static \UnitEnum|string|null $navigationGroup = 'Finance';

    protected static ?int $navigationSort = 8;

    public static function getNavigationLabel(): string
    {
        return __('finance.ledger_accounts.navigation');
    }

    public static function getModelLabel(): string
    {
        return __('finance.ledger_accounts.singular');
    }

    public static function getPluralModelLabel(): string
    {
        return __('finance.ledger_accounts.plural');
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->schema([
            Section::make(__('finance.ledger_accounts.section'))
                ->columns(3)
                ->schema([
                    Forms\Components\TextInput::make('code')
                        ->label(__('finance.ledger_accounts.fields.code'))
                        ->required()
                        ->maxLength(32)
                        ->unique(ignoreRecord: true, modifyRuleUsing: fn (Unique $rule): Unique => $rule->where('business_id', currentBusiness()?->id))
                        ->disabled(fn (?LedgerAccount $record): bool => (bool) $record?->is_system),
                    Forms\Components\TextInput::make('name.en')->label(__('finance.ledger_accounts.fields.name_en'))->required()->maxLength(255),
                    Forms\Components\TextInput::make('name.pl')->label(__('finance.ledger_accounts.fields.name_pl'))->maxLength(255),
                    Forms\Components\TextInput::make('name.pt')->label(__('finance.ledger_accounts.fields.name_pt'))->maxLength(255),
                    Forms\Components\Select::make('type')
                        ->label(__('finance.ledger_accounts.fields.type'))
                        ->options(collect(LedgerAccount::TYPES)->mapWithKeys(fn (string $type): array => [$type => __('finance.ledger_accounts.types.'.$type)])->all())
                        ->required()
                        ->disabled(fn (?LedgerAccount $record): bool => (bool) $record?->is_system),
                    Forms\Components\TextInput::make('subtype')->label(__('finance.ledger_accounts.fields.subtype'))->maxLength(50),
                    Forms\Components\Select::make('normal_balance')
                        ->label(__('finance.ledger_accounts.fields.normal_balance'))
                        ->options(collect(LedgerAccount::NORMAL_BALANCES)->mapWithKeys(fn (string $balance): array => [$balance => __('finance.ledger_accounts.normal_balances.'.$balance)])->all())
                        ->required(),
                    Forms\Components\Select::make('parent_id')
                        ->label(__('finance.ledger_accounts.fields.parent'))
                        ->options(fn (): array => LedgerAccount::where('is_postable', false)->orderBy('code')->get()->mapWithKeys(fn (LedgerAccount $account): array => [$account->id => "{$account->code} — {$account->name}"])->all())
                        ->searchable(),
                    Forms\Components\Toggle::make('is_postable')->label(__('finance.ledger_accounts.fields.is_postable'))->default(true),
                    Forms\Components\Toggle::make('is_active')->label(__('finance.ledger_accounts.fields.is_active'))->default(true),
                ])->columnSpanFull(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('code')->label(__('finance.ledger_accounts.fields.code'))->searchable()->sortable()->weight('bold'),
                Tables\Columns\TextColumn::make('name')->label(__('finance.ledger_accounts.fields.name'))->searchable()->sortable(),
                Tables\Columns\TextColumn::make('type')->label(__('finance.ledger_accounts.fields.type'))->badge()->formatStateUsing(fn (string $state): string => __('finance.ledger_accounts.types.'.$state)),
                Tables\Columns\TextColumn::make('parent.code')->label(__('finance.ledger_accounts.fields.parent'))->placeholder('—'),
                Tables\Columns\IconColumn::make('is_postable')->label(__('finance.ledger_accounts.fields.is_postable'))->boolean(),
                Tables\Columns\IconColumn::make('is_active')->label(__('finance.ledger_accounts.fields.is_active'))->boolean(),
                Tables\Columns\IconColumn::make('is_system')->label(__('finance.ledger_accounts.fields.is_system'))->boolean(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('type')->options(collect(LedgerAccount::TYPES)->mapWithKeys(fn (string $type): array => [$type => __('finance.ledger_accounts.types.'.$type)])->all()),
                Tables\Filters\TernaryFilter::make('is_active')->label(__('finance.ledger_accounts.fields.is_active')),
            ])
            ->actions([EditAction::make(), DeleteAction::make()])
            ->bulkActions([BulkActionGroup::make([DeleteBulkAction::make()])])
            ->defaultSort('code');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListLedgerAccounts::route('/'),
            'create' => Pages\CreateLedgerAccount::route('/create'),
            'edit' => Pages\EditLedgerAccount::route('/{record}/edit'),
        ];
    }
}
