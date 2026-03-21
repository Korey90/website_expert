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
    protected static ?int $navigationSort = 2;

    public static function form(Schema $form): Schema
    {
        return $form->schema([
            Forms\Components\Select::make('category')
                ->options(['project_type' => 'Project Type', 'cms' => 'CMS', 'pages_addon' => 'Pages Add-on', 'hosting' => 'Hosting', 'extra' => 'Extra'])
                ->required(),
            Forms\Components\TextInput::make('key')->required()->unique(ignoreRecord: true)->maxLength(100),
            Forms\Components\TextInput::make('label')->required()->maxLength(255),
            Forms\Components\Textarea::make('description')->rows(2),
            Forms\Components\TextInput::make('base_cost')->label('Base Cost (one-time)')->numeric()->prefix('£')->default(0),
            Forms\Components\TextInput::make('monthly_cost')->label('Monthly Cost')->numeric()->prefix('£')->default(0),
            Forms\Components\TextInput::make('cost_formula')->label('Dynamic Formula')->maxLength(500)->helperText('e.g. pages > 5 ? (pages-5)*80 : 0'),
            Forms\Components\Select::make('currency')->options(['GBP' => '£ GBP', 'EUR' => '€ EUR'])->default('GBP'),
            Forms\Components\TextInput::make('sort_order')->numeric()->default(0),
            Forms\Components\Toggle::make('is_active')->default(true),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('category')->badge()->sortable(),
                Tables\Columns\TextColumn::make('key')->searchable(),
                Tables\Columns\TextColumn::make('label')->searchable(),
                Tables\Columns\TextColumn::make('base_cost')->money('GBP'),
                Tables\Columns\TextColumn::make('monthly_cost')->money('GBP'),
                Tables\Columns\IconColumn::make('is_active')->boolean(),
                Tables\Columns\TextColumn::make('sort_order')->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('category')
                    ->options(['project_type' => 'Project Type', 'cms' => 'CMS', 'pages_addon' => 'Pages', 'hosting' => 'Hosting', 'extra' => 'Extra']),
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
