<?php

namespace App\Filament\Widgets;

use App\Models\CalculatorString;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Forms;
use Filament\Schemas\Components\Section;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;

class CalculatorStringsTableWidget extends BaseWidget
{
    protected static bool $isDiscoverable = false;

    protected static ?string $heading = 'UI Strings — all text labels by language';
    protected int|string|array $columnSpan = 'full';

    private function stringForm(): array
    {
        return [
            Section::make('Key & Admin Info')
                ->columns(3)
                ->schema([
                    Forms\Components\TextInput::make('key')
                        ->required()
                        ->unique(CalculatorString::class, 'key', ignoreRecord: true)
                        ->maxLength(100)
                        ->helperText('Unique snake_case identifier')
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
                        ->label('Admin note')
                        ->helperText('Where this string appears — not shown to users')
                        ->maxLength(255)
                        ->columnSpanFull(),
                ]),
            Section::make('🇬🇧 English')
                ->schema([
                    Forms\Components\Textarea::make('value_en')
                        ->label('Value')
                        ->required()
                        ->rows(2)
                        ->autosize(),
                ]),
            Section::make('🇵🇱 Polish')
                ->schema([
                    Forms\Components\Textarea::make('value_pl')
                        ->label('Value')
                        ->rows(2)
                        ->autosize(),
                ]),
            Section::make('🇵🇹 Portuguese')
                ->schema([
                    Forms\Components\Textarea::make('value_pt')
                        ->label('Value')
                        ->rows(2)
                        ->autosize(),
                ]),
        ];
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(CalculatorString::query())
            ->defaultSort('sort_order')
            ->columns([
                Tables\Columns\TextColumn::make('group')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'header'       => 'info',
                        'navigation'   => 'warning',
                        'misc_labels'  => 'gray',
                        'result_page'  => 'success',
                        'contact_form' => 'danger',
                        default        => 'gray',
                    })
                    ->sortable(),
                Tables\Columns\TextColumn::make('key')
                    ->fontFamily('mono')
                    ->searchable()
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
            ->headerActions([
                Action::make('create')
                    ->label('Add String')
                    ->icon('heroicon-o-plus')
                    ->form($this->stringForm())
                    ->action(fn (array $data) => CalculatorString::create($data)),
            ])
            ->recordActions([
                Action::make('edit')
                    ->icon('heroicon-o-pencil')
                    ->label('Edit')
                    ->fillForm(fn ($record) => $record->toArray())
                    ->form($this->stringForm())
                    ->action(fn ($record, array $data) => $record->update($data)),
                DeleteAction::make(),
            ]);
    }
}
