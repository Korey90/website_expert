<?php

namespace App\Filament\Resources;

use App\Filament\Resources\FixedAssetResource\Pages;
use App\Models\FixedAsset;
use App\Models\FixedAssetCategory;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Actions\RestoreAction;
use Filament\Actions\ViewAction;
use Filament\Forms;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class FixedAssetResource extends BaseResource
{
    protected static ?string $model = FixedAsset::class;

    protected static \BackedEnum|string|null $navigationIcon = 'heroicon-o-computer-desktop';

    protected static \UnitEnum|string|null $navigationGroup = 'Finance';

    protected static ?int $navigationSort = 10;

    public static function getNavigationLabel(): string
    {
        return __('finance.fixed_assets.navigation');
    }

    public static function getModelLabel(): string
    {
        return __('finance.fixed_assets.singular');
    }

    public static function getPluralModelLabel(): string
    {
        return __('finance.fixed_assets.plural');
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->schema([
            Section::make(__('finance.fixed_assets.sections.asset'))
                ->description(__('finance.fixed_assets.draft_notice'))
                ->columns(3)
                ->schema([
                    Forms\Components\Select::make('fixed_asset_category_id')
                        ->label(__('finance.fixed_assets.fields.category'))
                        ->options(fn (): array => FixedAssetCategory::query()
                            ->active()
                            ->orderBy('code')
                            ->get()
                            ->mapWithKeys(fn (FixedAssetCategory $category): array => [
                                $category->id => "{$category->code} - {$category->name}",
                            ])->all())
                        ->searchable()
                        ->preload()
                        ->live()
                        ->afterStateUpdated(function (mixed $state, Set $set): void {
                            $category = FixedAssetCategory::query()->active()->find($state);

                            if (! $category) {
                                return;
                            }

                            $set('depreciation_method', $category->default_depreciation_method);
                            $set('useful_life_months', $category->default_useful_life_months);
                            $set('residual_value_percent', $category->default_residual_value_percent);
                        })
                        ->required(),
                    Forms\Components\TextInput::make('name')
                        ->label(__('finance.fixed_assets.fields.name'))
                        ->required()
                        ->maxLength(255)
                        ->columnSpan(2),
                    Forms\Components\Textarea::make('description')
                        ->label(__('finance.fixed_assets.fields.description'))
                        ->maxLength(10000)
                        ->rows(3)
                        ->columnSpanFull(),
                    Forms\Components\TextInput::make('serial_number')
                        ->label(__('finance.fixed_assets.fields.serial_number'))
                        ->maxLength(100),
                    Forms\Components\TextInput::make('location')
                        ->label(__('finance.fixed_assets.fields.location'))
                        ->maxLength(150),
                ])->columnSpanFull(),
            Section::make(__('finance.fixed_assets.sections.depreciation'))
                ->description(__('finance.fixed_assets.depreciation_help'))
                ->columns(3)
                ->schema([
                    Forms\Components\Select::make('depreciation_method')
                        ->label(__('finance.fixed_assets.fields.depreciation_method'))
                        ->options(FixedAssetCategoryResource::methodOptions())
                        ->default(FixedAssetCategory::METHOD_STRAIGHT_LINE)
                        ->live()
                        ->required(),
                    Forms\Components\TextInput::make('useful_life_months')
                        ->label(__('finance.fixed_assets.fields.useful_life_months'))
                        ->numeric()
                        ->integer()
                        ->minValue(1)
                        ->maxValue(1200)
                        ->default(36)
                        ->required(fn (Get $get): bool => $get('depreciation_method') !== FixedAssetCategory::METHOD_NONE)
                        ->visible(fn (Get $get): bool => $get('depreciation_method') !== FixedAssetCategory::METHOD_NONE),
                    Forms\Components\TextInput::make('residual_value_percent')
                        ->label(__('finance.fixed_assets.fields.residual_value_percent'))
                        ->numeric()
                        ->minValue(0)
                        ->maxValue(100)
                        ->step('0.01')
                        ->suffix('%')
                        ->default(0)
                        ->required(),
                    Forms\Components\Textarea::make('notes')
                        ->label(__('finance.fixed_assets.fields.notes'))
                        ->maxLength(10000)
                        ->rows(3)
                        ->columnSpanFull(),
                ])->columnSpanFull(),
        ]);
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema->schema([
            Section::make(__('finance.fixed_assets.sections.asset'))
                ->description(__('finance.fixed_assets.draft_notice'))
                ->columns(3)
                ->schema([
                    TextEntry::make('number')->label(__('finance.fixed_assets.fields.number'))->weight('bold')->copyable(),
                    TextEntry::make('name')->label(__('finance.fixed_assets.fields.name'))->weight('bold'),
                    TextEntry::make('status')
                        ->label(__('finance.fixed_assets.fields.status'))
                        ->badge()
                        ->formatStateUsing(fn (string $state): string => __('finance.fixed_assets.statuses.'.$state))
                        ->color('gray'),
                    TextEntry::make('category.name')->label(__('finance.fixed_assets.fields.category')),
                    TextEntry::make('serial_number')->label(__('finance.fixed_assets.fields.serial_number'))->placeholder('-'),
                    TextEntry::make('location')->label(__('finance.fixed_assets.fields.location'))->placeholder('-'),
                    TextEntry::make('description')->label(__('finance.fixed_assets.fields.description'))->placeholder('-')->columnSpanFull(),
                ]),
            Section::make(__('finance.fixed_assets.sections.depreciation'))
                ->columns(3)
                ->schema([
                    TextEntry::make('depreciation_method')
                        ->label(__('finance.fixed_assets.fields.depreciation_method'))
                        ->formatStateUsing(fn (string $state): string => __('finance.fixed_asset_categories.methods.'.$state)),
                    TextEntry::make('useful_life_months')
                        ->label(__('finance.fixed_assets.fields.useful_life_months'))
                        ->placeholder('-'),
                    TextEntry::make('residual_value_percent')
                        ->label(__('finance.fixed_assets.fields.residual_value_percent'))
                        ->suffix('%'),
                    TextEntry::make('notes')->label(__('finance.fixed_assets.fields.notes'))->placeholder('-')->columnSpanFull(),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('number')
                    ->label(__('finance.fixed_assets.fields.number'))
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),
                Tables\Columns\TextColumn::make('name')
                    ->label(__('finance.fixed_assets.fields.name'))
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('category.name')
                    ->label(__('finance.fixed_assets.fields.category'))
                    ->sortable(),
                Tables\Columns\TextColumn::make('status')
                    ->label(__('finance.fixed_assets.fields.status'))
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => __('finance.fixed_assets.statuses.'.$state))
                    ->color('gray'),
                Tables\Columns\TextColumn::make('serial_number')
                    ->label(__('finance.fixed_assets.fields.serial_number'))
                    ->searchable()
                    ->placeholder('-'),
                Tables\Columns\TextColumn::make('location')
                    ->label(__('finance.fixed_assets.fields.location'))
                    ->searchable()
                    ->placeholder('-'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('fixed_asset_category_id')
                    ->label(__('finance.fixed_assets.fields.category'))
                    ->options(fn (): array => FixedAssetCategory::query()
                        ->orderBy('code')
                        ->get()
                        ->mapWithKeys(fn (FixedAssetCategory $category): array => [
                            $category->id => "{$category->code} - {$category->name}",
                        ])->all())
                    ->searchable()
                    ->preload(),
                Tables\Filters\SelectFilter::make('status')
                    ->label(__('finance.fixed_assets.fields.status'))
                    ->options([FixedAsset::STATUS_DRAFT => __('finance.fixed_assets.statuses.draft')]),
                Tables\Filters\TrashedFilter::make(),
            ])
            ->actions([
                ViewAction::make(),
                EditAction::make(),
                DeleteAction::make(),
                RestoreAction::make(),
            ])
            ->defaultSort('number', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListFixedAssets::route('/'),
            'create' => Pages\CreateFixedAsset::route('/create'),
            'view' => Pages\ViewFixedAsset::route('/{record}'),
            'edit' => Pages\EditFixedAsset::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->withTrashed()->with('category');
    }
}
