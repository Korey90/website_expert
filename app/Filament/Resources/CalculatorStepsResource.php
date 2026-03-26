<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CalculatorStepsResource\Pages;
use App\Models\CalculatorStep;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Schema;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class CalculatorStepsResource extends Resource
{
    protected static ?string $model = CalculatorStep::class;
    protected static \BackedEnum|string|null $navigationIcon = 'heroicon-o-queue-list';
    protected static \UnitEnum|string|null $navigationGroup = 'Settings';
    protected static ?string $navigationLabel = 'Calculator Steps';
    protected static ?int $navigationSort = 4;
    protected static bool $shouldRegisterNavigation = false;

    public static function form(Schema $form): Schema
    {
        return $form->schema([
            Section::make('Step settings')
                ->columns(3)
                ->schema([
                    Forms\Components\TextInput::make('step_number')
                        ->label('Step #')
                        ->numeric()
                        ->required()
                        ->minValue(1)
                        ->maxValue(20)
                        ->columnSpan(1),
                    Forms\Components\TextInput::make('sort_order')
                        ->numeric()
                        ->default(0)
                        ->columnSpan(1),
                    Forms\Components\Toggle::make('is_active')
                        ->default(true)
                        ->columnSpan(1),
                ]),

            Tabs::make('Translations')
                ->tabs([
                    Tab::make('🇬🇧 English')
                        ->schema([
                            Forms\Components\Textarea::make('question_en')
                                ->label('Question (EN)')
                                ->required()
                                ->rows(2)
                                ->autosize(),
                            Forms\Components\Textarea::make('hint_en')
                                ->label('Hint / subtitle (EN)')
                                ->rows(2)
                                ->autosize(),
                        ]),
                    Tab::make('🇵🇱 Polski')
                        ->schema([
                            Forms\Components\Textarea::make('question_pl')
                                ->label('Question (PL)')
                                ->rows(2)
                                ->autosize(),
                            Forms\Components\Textarea::make('hint_pl')
                                ->label('Hint / subtitle (PL)')
                                ->rows(2)
                                ->autosize(),
                        ]),
                    Tab::make('🇵🇹 Português')
                        ->schema([
                            Forms\Components\Textarea::make('question_pt')
                                ->label('Question (PT)')
                                ->rows(2)
                                ->autosize(),
                            Forms\Components\Textarea::make('hint_pt')
                                ->label('Hint / subtitle (PT)')
                                ->rows(2)
                                ->autosize(),
                        ]),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('step_number')
                    ->label('Step')
                    ->sortable()
                    ->badge()
                    ->color('primary'),
                Tables\Columns\TextColumn::make('question_en')
                    ->label('Question (EN)')
                    ->limit(60)
                    ->searchable(),
                Tables\Columns\TextColumn::make('question_pl')
                    ->label('Question (PL)')
                    ->limit(50)
                    ->toggleable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('question_pt')
                    ->label('Question (PT)')
                    ->limit(50)
                    ->toggleable()
                    ->searchable(),
                Tables\Columns\IconColumn::make('is_active')
                    ->boolean()
                    ->label('Active'),
                Tables\Columns\TextColumn::make('sort_order')
                    ->sortable()
                    ->label('Order'),
            ])
            ->defaultSort('sort_order')
            ->reorderable('sort_order')
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListCalculatorSteps::route('/'),
            'create' => Pages\CreateCalculatorStep::route('/create'),
            'edit'   => Pages\EditCalculatorStep::route('/{record}/edit'),
        ];
    }
}
