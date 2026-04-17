<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CalculatorStringsResource\Pages;
use App\Models\CalculatorString;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class CalculatorStringsResource extends Resource
{
    protected static ?string $model = CalculatorString::class;
    protected static \BackedEnum|string|null $navigationIcon = 'heroicon-o-language';
    protected static \UnitEnum|string|null $navigationGroup = 'Settings';
    protected static ?string $navigationLabel = 'Calculator Strings';
    protected static ?int $navigationSort = 8;
    protected static bool $shouldRegisterNavigation = false;

    public static function form(Schema $form): Schema
    {
        return $form->schema([
            Section::make('Key & Admin Info')
                ->columns(3)
                ->schema([
                    Forms\Components\TextInput::make('key')
                        ->required()
                        ->unique(ignoreRecord: true)
                        ->maxLength(100)
                        ->helperText('Unique identifier used in code (snake_case)')
                        ->columnSpan(1),
                    Forms\Components\Select::make('group')
                        ->options([
                            'header'       => 'Header',
                            'navigation'   => 'Navigation',
                            'misc_labels'  => 'Misc Labels',
                            'result_page'  => 'Result Page',
                            'contact_form' => 'Contact Form',
                        ])
                        ->required()
                        ->columnSpan(1),
                    Forms\Components\TextInput::make('sort_order')
                        ->numeric()
                        ->default(0)
                        ->columnSpan(1),
                    Forms\Components\TextInput::make('note')
                        ->helperText('Visible only in admin — describes where this string appears')
                        ->maxLength(255)
                        ->columnSpanFull(),
                ]),

            Section::make('English (EN)')
                ->schema([
                    Forms\Components\Textarea::make('value_en')
                        ->label('Value')
                        ->required()
                        ->rows(2)
                        ->autosize(),
                ]),

            Section::make('Polish (PL)')
                ->schema([
                    Forms\Components\Textarea::make('value_pl')
                        ->label('Value')
                        ->rows(2)
                        ->autosize(),
                ]),

            Section::make('Portuguese (PT)')
                ->schema([
                    Forms\Components\Textarea::make('value_pt')
                        ->label('Value')
                        ->rows(2)
                        ->autosize(),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('group')
                    ->badge()
                    ->color(fn(string $state) => match ($state) {
                        'header'       => 'info',
                        'navigation'   => 'warning',
                        'misc_labels'  => 'gray',
                        'result_page'  => 'success',
                        'contact_form' => 'danger',
                        default        => 'gray',
                    })
                    ->sortable(),
                Tables\Columns\TextColumn::make('key')
                    ->searchable()
                    ->fontFamily('mono')
                    ->sortable(),
                Tables\Columns\TextColumn::make('value_en')
                    ->label('EN')
                    ->limit(50)
                    ->searchable(),
                Tables\Columns\TextColumn::make('value_pl')
                    ->label('PL')
                    ->limit(40)
                    ->toggleable(),
                Tables\Columns\TextColumn::make('value_pt')
                    ->label('PT')
                    ->limit(40)
                    ->toggleable(),
                Tables\Columns\TextColumn::make('sort_order')
                    ->sortable()
                    ->label('Order'),
            ])
            ->defaultSort('sort_order')
            ->filters([
                Tables\Filters\SelectFilter::make('group')
                    ->options([
                        'header'       => 'Header',
                        'navigation'   => 'Navigation',
                        'misc_labels'  => 'Misc Labels',
                        'result_page'  => 'Result Page',
                        'contact_form' => 'Contact Form',
                    ]),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListCalculatorStrings::route('/'),
            'create' => Pages\CreateCalculatorString::route('/create'),
            'edit'   => Pages\EditCalculatorString::route('/{record}/edit'),
        ];
    }
}
