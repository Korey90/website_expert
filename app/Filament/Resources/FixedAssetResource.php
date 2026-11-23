<?php

namespace App\Filament\Resources;

use App\Filament\Resources\FixedAssetResource\Pages;
use App\Models\FixedAsset;
use App\Models\FixedAssetCategory;
use App\Models\FixedAssetDepreciationEntry;
use App\Models\FixedAssetTransaction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Actions\RestoreAction;
use Filament\Actions\ViewAction;
use Filament\Forms;
use Filament\Infolists\Components\RepeatableEntry;
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
                            $set('depreciation_rate_percent', $category->default_depreciation_rate_percent);
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
                    Forms\Components\TextInput::make('depreciation_rate_percent')
                        ->label(__('finance.fixed_assets.fields.depreciation_rate_percent'))
                        ->numeric()
                        ->minValue(0.01)
                        ->maxValue(100)
                        ->step('0.01')
                        ->suffix('%')
                        ->required(fn (Get $get): bool => $get('depreciation_method') === FixedAssetCategory::METHOD_REDUCING_BALANCE)
                        ->visible(fn (Get $get): bool => $get('depreciation_method') === FixedAssetCategory::METHOD_REDUCING_BALANCE),
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
                        ->color(fn (string $state): string => match ($state) {
                            FixedAsset::STATUS_ACTIVE => 'success',
                            FixedAsset::STATUS_DISPOSED => 'warning',
                            default => 'gray',
                        }),
                    TextEntry::make('category.name')->label(__('finance.fixed_assets.fields.category')),
                    TextEntry::make('serial_number')->label(__('finance.fixed_assets.fields.serial_number'))->placeholder('-'),
                    TextEntry::make('location')->label(__('finance.fixed_assets.fields.location'))->placeholder('-'),
                    TextEntry::make('description')->label(__('finance.fixed_assets.fields.description'))->placeholder('-')->columnSpanFull(),
                ]),
            Section::make(__('finance.fixed_assets.sections.lifecycle'))
                ->columns(3)
                ->schema([
                    TextEntry::make('acquisition_date')->label(__('finance.fixed_assets.fields.acquisition_date'))->date()->placeholder('-'),
                    TextEntry::make('in_service_date')->label(__('finance.fixed_assets.fields.in_service_date'))->date()->placeholder('-'),
                    TextEntry::make('acquisition_cost_gbp')->label(__('finance.fixed_assets.fields.acquisition_cost_gbp'))->money('GBP')->placeholder('-'),
                    TextEntry::make('accumulated_depreciation_gbp')->label(__('finance.fixed_assets.fields.accumulated_depreciation_gbp'))->money('GBP')->placeholder('-'),
                    TextEntry::make('net_book_value_gbp')
                        ->label(__('finance.fixed_assets.fields.net_book_value_gbp'))
                        ->state(fn (FixedAsset $record): float => max(0, (float) ($record->acquisition_cost_gbp ?? 0) - (float) ($record->accumulated_depreciation_gbp ?? 0)))
                        ->money('GBP'),
                    TextEntry::make('disposed_at')->label(__('finance.fixed_assets.fields.disposal_date'))->date()->placeholder('-'),
                    TextEntry::make('disposal_proceeds_gbp')->label(__('finance.fixed_assets.fields.disposal_proceeds_gbp'))->money('GBP')->placeholder('-'),
                    TextEntry::make('disposal_gain_loss_gbp')->label(__('finance.fixed_assets.fields.disposal_gain_loss_gbp'))->money('GBP')->placeholder('-'),
                ]),
            Section::make(__('finance.fixed_assets.sections.transactions'))
                ->schema([
                    RepeatableEntry::make('transactions')
                        ->hiddenLabel()
                        ->schema([
                            TextEntry::make('event_date')->label(__('finance.fixed_assets.fields.event_date'))->date(),
                            TextEntry::make('type')
                                ->label(__('finance.fixed_assets.fields.transaction_type'))
                                ->badge()
                                ->formatStateUsing(fn (string $state): string => __('finance.fixed_assets.transaction_types.'.$state)),
                            TextEntry::make('status')
                                ->label(__('finance.fixed_assets.fields.transaction_status'))
                                ->badge()
                                ->formatStateUsing(fn (string $state): string => __('finance.fixed_assets.transaction_statuses.'.$state))
                                ->color(fn (string $state): string => match ($state) {
                                    FixedAssetTransaction::STATUS_POSTED => 'success',
                                    FixedAssetTransaction::STATUS_REVERSED => 'danger',
                                    default => 'gray',
                                }),
                            TextEntry::make('amount_gbp')->label(__('finance.fixed_assets.fields.amount_gbp'))->money('GBP'),
                            TextEntry::make('journalEntry.entry_number')->label(__('finance.fixed_assets.fields.journal'))->placeholder('-')->copyable(),
                            TextEntry::make('reversalJournalEntry.entry_number')->label(__('finance.fixed_assets.fields.reversal_journal'))->placeholder('-')->copyable(),
                            TextEntry::make('reversal_reason')->label(__('finance.fixed_assets.fields.reversal_reason'))->placeholder('-')->columnSpanFull(),
                        ])->columns(3),
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
                    TextEntry::make('depreciation_rate_percent')
                        ->label(__('finance.fixed_assets.fields.depreciation_rate_percent'))
                        ->suffix('%')
                        ->placeholder('-'),
                    TextEntry::make('notes')->label(__('finance.fixed_assets.fields.notes'))->placeholder('-')->columnSpanFull(),
                ]),
            Section::make(__('finance.fixed_assets.sections.depreciation_schedule'))
                ->description(__('finance.fixed_assets.depreciation_schedule_help'))
                ->schema([
                    RepeatableEntry::make('depreciationEntries')
                        ->hiddenLabel()
                        ->schema([
                            TextEntry::make('period_start')->label(__('finance.fixed_assets.fields.period_start'))->date(),
                            TextEntry::make('period_end')->label(__('finance.fixed_assets.fields.period_end'))->date(),
                            TextEntry::make('status')
                                ->label(__('finance.fixed_assets.fields.depreciation_status'))
                                ->badge()
                                ->formatStateUsing(fn (string $state): string => __('finance.fixed_assets.depreciation_statuses.'.$state))
                                ->color(fn (string $state): string => match ($state) {
                                    FixedAssetDepreciationEntry::STATUS_POSTED => 'success',
                                    FixedAssetDepreciationEntry::STATUS_REVERSED => 'danger',
                                    default => 'gray',
                                }),
                            TextEntry::make('amount_gbp')->label(__('finance.fixed_assets.fields.depreciation_amount_gbp'))->money('GBP'),
                            TextEntry::make('closing_net_book_value_gbp')->label(__('finance.fixed_assets.fields.closing_net_book_value_gbp'))->money('GBP'),
                            TextEntry::make('journalEntry.entry_number')->label(__('finance.fixed_assets.fields.journal'))->placeholder('-')->copyable(),
                            TextEntry::make('reversalJournalEntry.entry_number')->label(__('finance.fixed_assets.fields.reversal_journal'))->placeholder('-')->copyable(),
                            TextEntry::make('reversal_reason')->label(__('finance.fixed_assets.fields.reversal_reason'))->placeholder('-')->columnSpanFull(),
                        ])->columns(4),
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
                    ->color(fn (string $state): string => match ($state) {
                        FixedAsset::STATUS_ACTIVE => 'success',
                        FixedAsset::STATUS_DISPOSED => 'warning',
                        default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('acquisition_cost_gbp')
                    ->label(__('finance.fixed_assets.fields.acquisition_cost_gbp'))
                    ->money('GBP')
                    ->placeholder('-')
                    ->sortable(),
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
                    ->options(collect(FixedAsset::statuses())->mapWithKeys(fn (string $status): array => [
                        $status => __('finance.fixed_assets.statuses.'.$status),
                    ])->all()),
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
        return parent::getEloquentQuery()->withTrashed()->with([
            'category.assetAccount',
            'transactions.journalEntry',
            'transactions.reversalJournalEntry',
            'depreciationEntries.journalEntry',
            'depreciationEntries.reversalJournalEntry',
        ]);
    }
}
