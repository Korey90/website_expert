<?php

namespace App\Filament\Widgets;

use App\Filament\Pages\CalculatorAdminPage;
use App\Filament\Support\Currency as FilamentCurrency;
use App\Models\CalculatorPricing;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Forms;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;

class CalculatorPricingTableWidget extends BaseWidget
{
    protected static bool $isDiscoverable = false;

    protected static ?string $heading = 'Pricing — options, costs & multipliers';

    protected int|string|array $columnSpan = 'full';

    private function pricingForm(): array
    {
        return [
            Section::make('Basic Info')
                ->columns(3)
                ->schema([
                    Forms\Components\Select::make('category')
                        ->options([
                            'project_type' => 'Project Type',
                            'pages_addon' => 'Pages Add-on',
                            'design' => 'Design',
                            'cms' => 'CMS',
                            'integrations' => 'Integrations',
                            'seo_package' => 'SEO Package',
                            'deadline' => 'Deadline',
                            'hosting' => 'Hosting',
                        ])
                        ->required()
                        ->columnSpan(1),
                    Forms\Components\TextInput::make('key')
                        ->required()
                        ->maxLength(100)
                        ->helperText('Unique within category (snake_case)')
                        ->columnSpan(1),
                    Forms\Components\TextInput::make('icon')
                        ->maxLength(20)
                        ->placeholder('🌐')
                        ->columnSpan(1),
                    Forms\Components\TextInput::make('sort_order')
                        ->numeric()
                        ->default(0)
                        ->columnSpan(1),
                    Forms\Components\Toggle::make('is_active')
                        ->default(true)
                        ->columnSpan(1),
                    Forms\Components\Select::make('currency')
                        ->options(fn () => FilamentCurrency::options())
                        ->default(fn () => FilamentCurrency::default())
                        ->columnSpan(1),
                ]),

            Tabs::make('Labels & Descriptions')
                ->tabs([
                    Tab::make('🇬🇧 English')
                        ->schema([
                            Forms\Components\TextInput::make('label')
                                ->label('Label')
                                ->required(),
                            Forms\Components\Textarea::make('description')
                                ->label('Description')
                                ->rows(2),
                        ]),
                    Tab::make('🇵🇱 Polski')
                        ->schema([
                            Forms\Components\TextInput::make('label_pl')
                                ->label('Label'),
                            Forms\Components\Textarea::make('desc_pl')
                                ->label('Description')
                                ->rows(2),
                        ]),
                    Tab::make('🇵🇹 Português')
                        ->schema([
                            Forms\Components\TextInput::make('label_pt')
                                ->label('Label'),
                            Forms\Components\Textarea::make('desc_pt')
                                ->label('Description')
                                ->rows(2),
                        ]),
                ]),

            Section::make('Pricing')
                ->columns(3)
                ->schema([
                    Forms\Components\TextInput::make('base_cost')
                        ->label('Base / Fixed Cost')
                        ->numeric()
                        ->prefix(fn (Get $get) => FilamentCurrency::symbol($get('currency')))
                        ->default(0)
                        ->helperText('One-time cost. For design/deadline: leave 0.')
                        ->columnSpan(1),
                    Forms\Components\TextInput::make('multiplier')
                        ->label('Multiplier')
                        ->numeric()
                        ->default(1.000)
                        ->step(0.001)
                        ->helperText('e.g. 1.5 = ×1.5 of base (only for design/deadline)')
                        ->columnSpan(1),
                    Forms\Components\TextInput::make('monthly_cost')
                        ->label('Monthly Cost')
                        ->numeric()
                        ->prefix(fn (Get $get) => FilamentCurrency::symbol($get('currency')))
                        ->default(0)
                        ->columnSpan(1),
                    Forms\Components\TextInput::make('cost_formula')
                        ->label('Dynamic Formula')
                        ->maxLength(500)
                        ->helperText('e.g. pages > 5 ? (pages-5)*80 : 0')
                        ->columnSpanFull(),
                ]),
        ];
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(CalculatorPricing::query())
            ->defaultSort('sort_order')
            ->reorderable('sort_order')
            ->columns([
                Tables\Columns\TextColumn::make('category')
                    ->badge()
                    ->sortable()
                    ->visibleFrom('md'),
                Tables\Columns\TextColumn::make('key')
                    ->fontFamily('mono')
                    ->searchable()
                    ->visibleFrom('md'),
                Tables\Columns\TextColumn::make('currency')
                    ->badge()
                    ->sortable()
                    ->visibleFrom('lg'),
                Tables\Columns\TextColumn::make('icon')
                    ->label('Icon')
                    ->visibleFrom('xl'),
                Tables\Columns\TextColumn::make('label')
                    ->label('Label (EN)')
                    ->searchable()
                    ->weight('bold'),
                Tables\Columns\TextColumn::make('label_pl')
                    ->label('PL')
                    ->toggleable()
                    ->visibleFrom('lg'),
                Tables\Columns\TextColumn::make('label_pt')
                    ->label('PT')
                    ->toggleable()
                    ->visibleFrom('xl'),
                Tables\Columns\TextColumn::make('base_cost')
                    ->money(fn (CalculatorPricing $record) => FilamentCurrency::tableCurrency($record))
                    ->label('Base Cost'),
                Tables\Columns\TextColumn::make('multiplier')
                    ->label('×')
                    ->visibleFrom('lg'),
                Tables\Columns\IconColumn::make('is_active')
                    ->boolean()
                    ->label('Active'),
                Tables\Columns\TextColumn::make('sort_order')
                    ->sortable()
                    ->label('Order')
                    ->visibleFrom('xl'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('category')
                    ->options([
                        'project_type' => 'Project Type',
                        'pages_addon' => 'Pages Add-on',
                        'design' => 'Design',
                        'cms' => 'CMS',
                        'integrations' => 'Integrations',
                        'seo_package' => 'SEO Package',
                        'deadline' => 'Deadline',
                        'hosting' => 'Hosting',
                    ]),
            ])
            ->headerActions([
                Action::make('create')
                    ->label('Add Pricing Option')
                    ->icon('heroicon-o-plus')
                    ->form($this->pricingForm())
                    ->visible(fn (): bool => CalculatorAdminPage::canAccess())
                    ->action(function (array $data): void {
                        abort_unless(CalculatorAdminPage::canAccess(), 403);
                        CalculatorPricing::create($data);
                    }),
            ])
            ->recordActions([
                Action::make('edit')
                    ->icon('heroicon-o-pencil')
                    ->label('Edit')
                    ->fillForm(fn ($record) => $record->toArray())
                    ->form($this->pricingForm())
                    ->visible(fn (): bool => CalculatorAdminPage::canAccess())
                    ->action(function ($record, array $data): void {
                        abort_unless(CalculatorAdminPage::canAccess(), 403);
                        $record->update($data);
                    }),
                DeleteAction::make()
                    ->visible(fn (): bool => CalculatorAdminPage::canAccess()),
            ])
            ->emptyStateIcon('heroicon-o-calculator')
            ->emptyStateHeading(__('admin.migration.administration.calculator.pricing.empty_heading'))
            ->emptyStateDescription(__('admin.migration.administration.calculator.pricing.empty_description'));
    }
}
