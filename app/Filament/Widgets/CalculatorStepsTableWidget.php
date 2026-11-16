<?php

namespace App\Filament\Widgets;

use App\Models\CalculatorStep;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Forms;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;

class CalculatorStepsTableWidget extends BaseWidget
{
    protected static bool $isDiscoverable = false;

    protected static ?string $heading = 'Steps — questions & hints per step';
    protected int|string|array $columnSpan = 'full';

    private function stepForm(): array
    {
        return [
            Section::make('Step Info')
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
                                ->label('Question')
                                ->required()
                                ->rows(2)
                                ->autosize(),
                            Forms\Components\Textarea::make('hint_en')
                                ->label('Hint / subtitle')
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
                                ->label('Hint (PL)')
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
                                ->label('Hint (PT)')
                                ->rows(2)
                                ->autosize(),
                        ]),
                ]),
        ];
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(CalculatorStep::query())
            ->defaultSort('sort_order')
            ->reorderable('sort_order')
            ->columns([
                Tables\Columns\TextColumn::make('step_number')
                    ->label('Step')
                    ->badge()
                    ->color('primary')
                    ->sortable(),
                Tables\Columns\TextColumn::make('question_en')
                    ->label('EN')
                    ->limit(60)
                    ->searchable(),
                Tables\Columns\TextColumn::make('question_pl')
                    ->label('PL')
                    ->limit(50)
                    ->toggleable(),
                Tables\Columns\TextColumn::make('question_pt')
                    ->label('PT')
                    ->limit(50)
                    ->toggleable(),
                Tables\Columns\IconColumn::make('is_active')
                    ->boolean()
                    ->label('Active'),
                Tables\Columns\TextColumn::make('sort_order')
                    ->sortable()
                    ->label('Order'),
            ])
            ->headerActions([
                Action::make('create')
                    ->label('Add Step')
                    ->icon('heroicon-o-plus')
                    ->form($this->stepForm())
                    ->action(fn (array $data) => CalculatorStep::create($data)),
            ])
            ->recordActions([
                Action::make('edit')
                    ->icon('heroicon-o-pencil')
                    ->label('Edit')
                    ->fillForm(fn ($record) => $record->toArray())
                    ->form($this->stepForm())
                    ->action(fn ($record, array $data) => $record->update($data)),
                DeleteAction::make(),
            ]);
    }
}
