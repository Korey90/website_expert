<?php

namespace App\Filament\Resources;

use App\Filament\Resources\SupplierResource\Pages;
use App\Filament\Support\Currency as FilamentCurrency;
use App\Models\LedgerAccount;
use App\Models\Supplier;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Actions\ViewAction;
use Filament\Forms;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class SupplierResource extends BaseResource
{
    protected static ?string $model = Supplier::class;

    protected static \BackedEnum|string|null $navigationIcon = 'heroicon-o-truck';

    protected static \UnitEnum|string|null $navigationGroup = 'Finance';

    protected static ?int $navigationSort = 4;

    public static function getNavigationLabel(): string
    {
        return __('finance.suppliers.navigation');
    }

    public static function getModelLabel(): string
    {
        return __('finance.suppliers.singular');
    }

    public static function getPluralModelLabel(): string
    {
        return __('finance.suppliers.plural');
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->schema([
            Section::make(__('finance.suppliers.sections.company'))
                ->columns(2)
                ->schema([
                    Forms\Components\TextInput::make('name')
                        ->label(__('finance.suppliers.fields.name'))
                        ->required()
                        ->maxLength(255),
                    Forms\Components\TextInput::make('trading_name')
                        ->label(__('finance.suppliers.fields.trading_name'))
                        ->maxLength(255),
                    Forms\Components\TextInput::make('company_number')
                        ->label(__('finance.suppliers.fields.company_number'))
                        ->maxLength(20),
                    Forms\Components\TextInput::make('vat_number')
                        ->label(__('finance.suppliers.fields.vat_number'))
                        ->maxLength(30),
                    Forms\Components\Select::make('status')
                        ->label(__('finance.suppliers.fields.status'))
                        ->options([
                            Supplier::STATUS_ACTIVE => __('finance.suppliers.statuses.active'),
                            Supplier::STATUS_ARCHIVED => __('finance.suppliers.statuses.archived'),
                        ])
                        ->default(Supplier::STATUS_ACTIVE)
                        ->required(),
                ]),

            Section::make(__('finance.suppliers.sections.contact'))
                ->columns(3)
                ->schema([
                    Forms\Components\TextInput::make('contact_name')->label(__('finance.suppliers.fields.contact_name'))->maxLength(255),
                    Forms\Components\TextInput::make('contact_email')->label(__('finance.suppliers.fields.contact_email'))->email()->maxLength(255),
                    Forms\Components\TextInput::make('contact_phone')->label(__('finance.suppliers.fields.contact_phone'))->tel()->maxLength(50),
                ]),

            Section::make(__('finance.suppliers.sections.address'))
                ->columns(2)
                ->schema([
                    Forms\Components\TextInput::make('address_line1')->label(__('finance.suppliers.fields.address_line1'))->maxLength(255),
                    Forms\Components\TextInput::make('address_line2')->label(__('finance.suppliers.fields.address_line2'))->maxLength(255),
                    Forms\Components\TextInput::make('city')->label(__('finance.suppliers.fields.city'))->maxLength(100),
                    Forms\Components\TextInput::make('county')->label(__('finance.suppliers.fields.county'))->maxLength(100),
                    Forms\Components\TextInput::make('postcode')->label(__('finance.suppliers.fields.postcode'))->maxLength(20),
                    Forms\Components\Select::make('country')
                        ->label(__('finance.suppliers.fields.country'))
                        ->options(self::countryOptions())
                        ->default('GB')
                        ->searchable()
                        ->required(),
                ]),

            Section::make(__('finance.suppliers.sections.payment'))
                ->columns(2)
                ->schema([
                    Forms\Components\Select::make('default_currency')
                        ->label(__('finance.suppliers.fields.default_currency'))
                        ->options(fn (): array => FilamentCurrency::options())
                        ->default('GBP')
                        ->required(),
                    Forms\Components\TextInput::make('payment_terms_days')
                        ->label(__('finance.suppliers.fields.payment_terms_days'))
                        ->numeric()
                        ->minValue(0)
                        ->maxValue(365)
                        ->default(30)
                        ->suffix(__('finance.suppliers.fields.days'))
                        ->required(),
                    Forms\Components\Select::make('default_ledger_account_id')
                        ->label(__('finance.suppliers.fields.default_ledger_account'))
                        ->options(fn (): array => LedgerAccount::activePostable()->where('type', 'expense')->orderBy('code')->get()->mapWithKeys(fn (LedgerAccount $account): array => [$account->id => "{$account->code} — {$account->name}"])->all())
                        ->searchable()
                        ->preload(),
                    Forms\Components\Textarea::make('notes')
                        ->label(__('finance.suppliers.fields.notes'))
                        ->rows(3)
                        ->columnSpanFull(),
                ]),
        ]);
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema->schema([
            Section::make(__('finance.suppliers.sections.company'))
                ->columns(3)
                ->schema([
                    TextEntry::make('name')->label(__('finance.suppliers.fields.name'))->weight('bold')->size('lg'),
                    TextEntry::make('trading_name')->label(__('finance.suppliers.fields.trading_name'))->placeholder('—'),
                    TextEntry::make('status')
                        ->label(__('finance.suppliers.fields.status'))
                        ->badge()
                        ->formatStateUsing(fn (string $state): string => __('finance.suppliers.statuses.'.$state))
                        ->color(fn (string $state): string => $state === Supplier::STATUS_ACTIVE ? 'success' : 'gray'),
                    TextEntry::make('company_number')->label(__('finance.suppliers.fields.company_number'))->placeholder('—'),
                    TextEntry::make('vat_number')->label(__('finance.suppliers.fields.vat_number'))->placeholder('—'),
                ]),
            Section::make(__('finance.suppliers.sections.contact'))
                ->columns(3)
                ->schema([
                    TextEntry::make('contact_name')->label(__('finance.suppliers.fields.contact_name'))->placeholder('—'),
                    TextEntry::make('contact_email')->label(__('finance.suppliers.fields.contact_email'))->copyable()->placeholder('—'),
                    TextEntry::make('contact_phone')->label(__('finance.suppliers.fields.contact_phone'))->copyable()->placeholder('—'),
                ]),
            Section::make(__('finance.suppliers.sections.address'))
                ->columns(3)
                ->collapsed()
                ->schema([
                    TextEntry::make('address_line1')->label(__('finance.suppliers.fields.address_line1'))->placeholder('—'),
                    TextEntry::make('address_line2')->label(__('finance.suppliers.fields.address_line2'))->placeholder('—'),
                    TextEntry::make('city')->label(__('finance.suppliers.fields.city'))->placeholder('—'),
                    TextEntry::make('county')->label(__('finance.suppliers.fields.county'))->placeholder('—'),
                    TextEntry::make('postcode')->label(__('finance.suppliers.fields.postcode'))->placeholder('—'),
                    TextEntry::make('country')->label(__('finance.suppliers.fields.country'))->placeholder('—'),
                ]),
            Section::make(__('finance.suppliers.sections.payment'))
                ->columns(2)
                ->schema([
                    TextEntry::make('default_currency')->label(__('finance.suppliers.fields.default_currency'))->badge(),
                    TextEntry::make('payment_terms_days')
                        ->label(__('finance.suppliers.fields.payment_terms_days'))
                        ->suffix(' '.__('finance.suppliers.fields.days')),
                    TextEntry::make('defaultLedgerAccount.code')
                        ->label(__('finance.suppliers.fields.default_ledger_account'))
                        ->placeholder('—'),
                    TextEntry::make('notes')->label(__('finance.suppliers.fields.notes'))->placeholder('—')->columnSpanFull(),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')->label(__('finance.suppliers.fields.name'))->searchable()->sortable()->weight('bold'),
                Tables\Columns\TextColumn::make('contact_name')->label(__('finance.suppliers.fields.contact_name'))->searchable()->placeholder('—'),
                Tables\Columns\TextColumn::make('contact_email')->label(__('finance.suppliers.fields.contact_email'))->searchable()->copyable()->placeholder('—'),
                Tables\Columns\TextColumn::make('vat_number')->label(__('finance.suppliers.fields.vat_number'))->searchable()->placeholder('—'),
                Tables\Columns\TextColumn::make('default_currency')->label(__('finance.suppliers.fields.default_currency'))->badge(),
                Tables\Columns\TextColumn::make('status')
                    ->label(__('finance.suppliers.fields.status'))
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => __('finance.suppliers.statuses.'.$state))
                    ->color(fn (string $state): string => $state === Supplier::STATUS_ACTIVE ? 'success' : 'gray'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')->options([
                    Supplier::STATUS_ACTIVE => __('finance.suppliers.statuses.active'),
                    Supplier::STATUS_ARCHIVED => __('finance.suppliers.statuses.archived'),
                ]),
                Tables\Filters\TrashedFilter::make(),
            ])
            ->actions([
                ViewAction::make(),
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                    RestoreBulkAction::make(),
                ]),
            ])
            ->defaultSort('name');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListSuppliers::route('/'),
            'create' => Pages\CreateSupplier::route('/create'),
            'view' => Pages\ViewSupplier::route('/{record}'),
            'edit' => Pages\EditSupplier::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->withTrashed();
    }

    /** @return array<string, string> */
    private static function countryOptions(): array
    {
        return [
            'GB' => 'United Kingdom',
            'IE' => 'Ireland',
            'PL' => 'Poland',
            'PT' => 'Portugal',
            'DE' => 'Germany',
            'FR' => 'France',
            'ES' => 'Spain',
            'US' => 'United States',
        ];
    }
}
