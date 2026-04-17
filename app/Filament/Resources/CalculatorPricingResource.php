<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CalculatorPricingResource\Pages;
use App\Models\CalculatorPricing;
use Filament\Forms;
use Filament\Schemas\Schema;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Table;

class CalculatorPricingResource extends Resource
{
    protected static ?string $model = CalculatorPricing::class;
    protected static \BackedEnum|string|null $navigationIcon = 'heroicon-o-calculator';
    protected static \UnitEnum|string|null $navigationGroup = 'Settings';
    protected static ?string $navigationLabel = 'Calculator Pricing';
    protected static ?int $navigationSort = 7;
    protected static bool $shouldRegisterNavigation = false;

    public static function form(Schema $form): Schema
    {
        return $form->schema([
            Forms\Components\Select::make('category')
                ->options([
                    'project_type' => 'Project Type',
                    'design'       => 'Design',
                    'cms'          => 'CMS',
                    'integrations' => 'Integrations',
                    'seo_package'  => 'SEO Package',
                    'deadline'     => 'Deadline',
                    'hosting'      => 'Hosting',
                ])
                ->required(),
            Forms\Components\TextInput::make('key')
                ->required()
                ->maxLength(100)
                ->helperText('Unique within category (e.g. wizytowka, landing, none, basic)'),
            Forms\Components\TextInput::make('icon')
                ->maxLength(20)
                ->placeholder('🌐')
                ->helperText('Emoji icon displayed next to the option'),
            Forms\Components\TextInput::make('label')
                ->label('Label (EN)')
                ->required()
                ->maxLength(255),
            Forms\Components\TextInput::make('label_pl')
                ->label('Label (PL)')
                ->maxLength(255),
            Forms\Components\TextInput::make('label_pt')
                ->label('Label (PT)')
                ->maxLength(255),
            Forms\Components\Textarea::make('description')
                ->label('Description (EN)')
                ->rows(2),
            Forms\Components\Textarea::make('desc_pl')
                ->label('Description (PL)')
                ->rows(2),
            Forms\Components\Textarea::make('desc_pt')
                ->label('Description (PT)')
                ->rows(2),
            Forms\Components\TextInput::make('base_cost')
                ->label('Base Cost / Fixed Cost (one-time)')
                ->numeric()
                ->prefix('£')
                ->default(0)
                ->helperText('For project types: base price. For add-ons: fixed cost. For design/deadline: leave 0.'),
            Forms\Components\TextInput::make('multiplier')
                ->label('Multiplier')
                ->numeric()
                ->default(1.000)
                ->step(0.001)
                ->helperText('For design and deadline categories: price multiplier (e.g. 1.5 = ×1.5 of base price)'),
            Forms\Components\TextInput::make('monthly_cost')
                ->label('Monthly Cost')
                ->numeric()
                ->prefix('£')
                ->default(0),
            Forms\Components\TextInput::make('cost_formula')
                ->label('Dynamic Formula')
                ->maxLength(500)
                ->helperText('e.g. pages > 5 ? (pages-5)*80 : 0'),
            Forms\Components\Select::make('currency')
                ->options(['GBP' => '£ GBP', 'EUR' => '€ EUR'])
                ->default('GBP'),
            Forms\Components\TextInput::make('sort_order')
                ->numeric()
                ->default(0),
            Forms\Components\Toggle::make('is_active')
                ->default(true),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('category')->badge()->sortable(),
                Tables\Columns\TextColumn::make('key')->searchable(),
                Tables\Columns\TextColumn::make('icon')->label('Icon'),
                Tables\Columns\TextColumn::make('label')->searchable()->label('Label (EN)'),
                Tables\Columns\TextColumn::make('base_cost')->money('GBP')->label('Base Cost'),
                Tables\Columns\TextColumn::make('multiplier')->label('Multiplier'),
                Tables\Columns\TextColumn::make('monthly_cost')->money('GBP'),
                Tables\Columns\IconColumn::make('is_active')->boolean(),
                Tables\Columns\TextColumn::make('sort_order')->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('category')
                    ->options([
                        'project_type' => 'Project Type',
                        'design'       => 'Design',
                        'cms'          => 'CMS',
                        'integrations' => 'Integrations',
                        'seo_package'  => 'SEO Package',
                        'deadline'     => 'Deadline',
                        'hosting'      => 'Hosting',
                    ]),
            ])
            ->actions([ViewAction::make(), EditAction::make(), DeleteAction::make()])
            ->reorderable('sort_order')
            ->defaultSort('sort_order');
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListCalculatorPricings::route('/'),
            'create' => Pages\CreateCalculatorPricing::route('/create'),
            'view'   => Pages\ViewCalculatorPricing::route('/{record}'),
            'edit'   => Pages\EditCalculatorPricing::route('/{record}/edit'),
        ];
    }
}
