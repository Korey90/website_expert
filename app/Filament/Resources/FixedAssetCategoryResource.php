<?php

namespace App\Filament\Resources;

use App\Filament\Resources\FixedAssetCategoryResource\Pages;
use App\Models\FixedAssetCategory;
use App\Models\LedgerAccount;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Validation\Rules\Unique;

class FixedAssetCategoryResource extends BaseResource
{
    protected static ?string $model = FixedAssetCategory::class;

    protected static \BackedEnum|string|null $navigationIcon = 'heroicon-o-tag';

    protected static \UnitEnum|string|null $navigationGroup = 'Finance';

    protected static ?int $navigationSort = 11;

    public static function getNavigationLabel(): string
    {
        return __('finance.fixed_asset_categories.navigation');
    }

    public static function getModelLabel(): string
    {
        return __('finance.fixed_asset_categories.singular');
    }

    public static function getPluralModelLabel(): string
    {
        return __('finance.fixed_asset_categories.plural');
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->schema([
            Section::make(__('finance.fixed_asset_categories.sections.identity'))
                ->columns(3)
                ->schema([
                    Forms\Components\TextInput::make('code')
                        ->label(__('finance.fixed_asset_categories.fields.code'))
                        ->required()
                        ->maxLength(32)
                        ->unique(
                            ignoreRecord: true,
                            modifyRuleUsing: fn (Unique $rule): Unique => $rule->where('business_id', currentBusiness()?->id),
                        ),
                    Forms\Components\TextInput::make('name.en')
                        ->label(__('finance.fixed_asset_categories.fields.name_en'))
                        ->required()
                        ->maxLength(255),
                    Forms\Components\TextInput::make('name.pl')
                        ->label(__('finance.fixed_asset_categories.fields.name_pl'))
                        ->maxLength(255),
                    Forms\Components\Toggle::make('is_active')
                        ->label(__('finance.fixed_asset_categories.fields.is_active'))
                        ->default(true),
                ])->columnSpanFull(),
            Section::make(__('finance.fixed_asset_categories.sections.accounts'))
                ->description(__('finance.fixed_asset_categories.accounts_help'))
                ->columns(3)
                ->schema([
                    static::accountSelect('asset_account_id', 'asset', 'fixed_asset'),
                    static::accountSelect('accumulated_depreciation_account_id', 'asset', 'contra_asset'),
                    static::accountSelect('depreciation_expense_account_id', 'expense', 'depreciation'),
                ])->columnSpanFull(),
            Section::make(__('finance.fixed_asset_categories.sections.depreciation'))
                ->description(__('finance.fixed_asset_categories.depreciation_help'))
                ->columns(3)
                ->schema([
                    Forms\Components\Select::make('default_depreciation_method')
                        ->label(__('finance.fixed_asset_categories.fields.default_depreciation_method'))
                        ->options(static::methodOptions())
                        ->default(FixedAssetCategory::METHOD_STRAIGHT_LINE)
                        ->live()
                        ->required(),
                    Forms\Components\TextInput::make('default_useful_life_months')
                        ->label(__('finance.fixed_asset_categories.fields.default_useful_life_months'))
                        ->numeric()
                        ->integer()
                        ->minValue(1)
                        ->maxValue(1200)
                        ->default(36)
                        ->required(fn (Get $get): bool => $get('default_depreciation_method') !== FixedAssetCategory::METHOD_NONE)
                        ->visible(fn (Get $get): bool => $get('default_depreciation_method') !== FixedAssetCategory::METHOD_NONE),
                    Forms\Components\TextInput::make('default_residual_value_percent')
                        ->label(__('finance.fixed_asset_categories.fields.default_residual_value_percent'))
                        ->numeric()
                        ->minValue(0)
                        ->maxValue(100)
                        ->step('0.01')
                        ->suffix('%')
                        ->default(0)
                        ->required(),
                ])->columnSpanFull(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('code')
                    ->label(__('finance.fixed_asset_categories.fields.code'))
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),
                Tables\Columns\TextColumn::make('name')
                    ->label(__('finance.fixed_asset_categories.fields.name'))
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('default_depreciation_method')
                    ->label(__('finance.fixed_asset_categories.fields.default_depreciation_method'))
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => __('finance.fixed_asset_categories.methods.'.$state)),
                Tables\Columns\TextColumn::make('default_useful_life_months')
                    ->label(__('finance.fixed_asset_categories.fields.default_useful_life_months'))
                    ->placeholder('-')
                    ->sortable(),
                Tables\Columns\IconColumn::make('is_active')
                    ->label(__('finance.fixed_asset_categories.fields.is_active'))
                    ->boolean(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('default_depreciation_method')
                    ->label(__('finance.fixed_asset_categories.fields.default_depreciation_method'))
                    ->options(static::methodOptions()),
                Tables\Filters\TernaryFilter::make('is_active')
                    ->label(__('finance.fixed_asset_categories.fields.is_active')),
            ])
            ->actions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->defaultSort('code');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListFixedAssetCategories::route('/'),
            'create' => Pages\CreateFixedAssetCategory::route('/create'),
            'edit' => Pages\EditFixedAssetCategory::route('/{record}/edit'),
        ];
    }

    /** @return array<string, string> */
    public static function methodOptions(): array
    {
        return collect(FixedAssetCategory::DEPRECIATION_METHODS)
            ->mapWithKeys(fn (string $method): array => [$method => __('finance.fixed_asset_categories.methods.'.$method)])
            ->all();
    }

    private static function accountSelect(string $field, string $type, string $subtype): Forms\Components\Select
    {
        return Forms\Components\Select::make($field)
            ->label(__('finance.fixed_asset_categories.fields.'.$field))
            ->options(fn (): array => LedgerAccount::query()
                ->activePostable()
                ->where('type', $type)
                ->where('subtype', $subtype)
                ->orderBy('code')
                ->get()
                ->mapWithKeys(fn (LedgerAccount $account): array => [$account->id => "{$account->code} - {$account->name}"])
                ->all())
            ->searchable()
            ->preload()
            ->required();
    }
}
