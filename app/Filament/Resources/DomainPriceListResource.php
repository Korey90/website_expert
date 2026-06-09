<?php

namespace App\Filament\Resources;

use App\Filament\Resources\DomainPriceListResource\Pages;
use App\Models\DomainPriceList;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\BulkActionGroup;
use Filament\Forms;
use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;

class DomainPriceListResource extends BaseResource
{
    protected static ?string $model = DomainPriceList::class;
    protected static \BackedEnum|string|null $navigationIcon  = 'heroicon-o-tag';
    protected static \UnitEnum|string|null   $navigationGroup = 'Domains';
    protected static ?string $navigationLabel = 'TLD Price List';
    protected static ?int    $navigationSort  = 1;

    public static function form(Schema $form): Schema
    {
        return $form->schema([
            Section::make('TLD Pricing')
                ->columns(2)
                ->schema([
                    Forms\Components\TextInput::make('tld')
                        ->label('TLD (e.g. .co.uk)')
                        ->required()
                        ->maxLength(20)
                        ->unique(ignoreRecord: true),

                    Forms\Components\Select::make('currency')
                        ->options(['GBP' => '£ GBP', 'EUR' => '€ EUR', 'USD' => '$ USD'])
                        ->default('GBP')
                        ->required(),

                    Forms\Components\TextInput::make('margin_percent')
                        ->label('Margin (%)')
                        ->numeric()
                        ->step('0.5')
                        ->minValue(0)
                        ->maxValue(1000)
                        ->default(50)
                        ->suffix('%')
                        ->helperText('Markup over wholesale. Leave 0 to use the global default from Integration Settings.'),

                    Forms\Components\Toggle::make('is_active')
                        ->label('Active')
                        ->default(true),
                ]),

            Section::make('Retail Prices (per year, charged to customers)')
                ->description('Prices customers pay. Recalculated automatically when syncing from Openprovider.')
                ->columns(3)
                ->schema([
                    Forms\Components\TextInput::make('register_price')
                        ->label('Register /yr')
                        ->numeric()
                        ->prefix('£')
                        ->step('0.01')
                        ->required(),

                    Forms\Components\TextInput::make('renew_price')
                        ->label('Renewal /yr')
                        ->numeric()
                        ->prefix('£')
                        ->step('0.01')
                        ->required(),

                    Forms\Components\TextInput::make('transfer_price')
                        ->label('Transfer')
                        ->numeric()
                        ->prefix('£')
                        ->step('0.01')
                        ->nullable(),
                ]),

            Section::make('Wholesale Prices (Openprovider, per year)')
                ->description('Populated automatically by the "Sync from Openprovider" action. Do not edit manually unless needed.')
                ->columns(3)
                ->collapsed()
                ->schema([
                    Forms\Components\TextInput::make('wholesale_register')
                        ->label('Wholesale Register /yr')
                        ->numeric()
                        ->prefix('£')
                        ->step('0.01')
                        ->nullable(),

                    Forms\Components\TextInput::make('wholesale_renew')
                        ->label('Wholesale Renewal /yr')
                        ->numeric()
                        ->prefix('£')
                        ->step('0.01')
                        ->nullable(),

                    Forms\Components\TextInput::make('wholesale_transfer')
                        ->label('Wholesale Transfer')
                        ->numeric()
                        ->prefix('£')
                        ->step('0.01')
                        ->nullable(),
                ]),

            Section::make('Notes')
                ->collapsed()
                ->schema([
                    Forms\Components\Textarea::make('notes')
                        ->label('')
                        ->rows(3)
                        ->columnSpanFull(),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('tld')
                    ->label('TLD')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),

                Tables\Columns\TextColumn::make('register_price')
                    ->label('Register /yr (retail)')
                    ->money('GBP')
                    ->sortable(),

                Tables\Columns\TextColumn::make('renew_price')
                    ->label('Renew /yr (retail)')
                    ->money('GBP')
                    ->sortable(),

                Tables\Columns\TextColumn::make('transfer_price')
                    ->label('Transfer (retail)')
                    ->money('GBP')
                    ->placeholder('—')
                    ->sortable(),

                Tables\Columns\TextColumn::make('wholesale_register')
                    ->label('Register /yr (wholesale)')
                    ->money('GBP')
                    ->placeholder('—')
                    ->sortable()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('wholesale_renew')
                    ->label('Renew /yr (wholesale)')
                    ->money('GBP')
                    ->placeholder('—')
                    ->sortable()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('wholesale_transfer')
                    ->label('Transfer (wholesale)')
                    ->money('GBP')
                    ->placeholder('—')
                    ->toggleable(),

                Tables\Columns\TextColumn::make('margin_percent')
                    ->label('Margin')
                    ->formatStateUsing(fn ($state) => $state . '%')
                    ->badge()
                    ->color('info')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('currency')
                    ->badge()
                    ->color('gray'),

                Tables\Columns\IconColumn::make('is_active')
                    ->label('Active')
                    ->boolean(),

                Tables\Columns\TextColumn::make('updated_at')
                    ->label('Updated')
                    ->date()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Active'),
            ])
            ->actions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('tld');
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListDomainPriceLists::route('/'),
            'create' => Pages\CreateDomainPriceList::route('/create'),
            'edit'   => Pages\EditDomainPriceList::route('/{record}/edit'),
        ];
    }
}
