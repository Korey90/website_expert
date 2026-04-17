<?php

namespace App\Filament\Resources;

use App\Filament\Resources\AutomationTriggerResource\Pages;
use App\Models\AutomationTrigger;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms;
use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\KeyValueEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;

class AutomationTriggerResource extends Resource
{
    protected static ?string $model = AutomationTrigger::class;
    protected static \BackedEnum|string|null $navigationIcon = 'heroicon-o-bolt';
    protected static \UnitEnum|string|null $navigationGroup = 'Automation';
    protected static ?string $navigationLabel = 'Automation Triggers';
    protected static ?int $navigationSort = 2;

    // ── Form ─────────────────────────────────────────────────────────────────

    public static function form(Schema $form): Schema
    {
        return $form->schema([
            Section::make('Trigger Definition')
                ->columns(2)
                ->schema([
                    Forms\Components\TextInput::make('key')
                        ->label('Trigger Key')
                        ->placeholder('my.custom.trigger')
                        ->helperText('Unique identifier used in code. Use dot notation, e.g. lead.created')
                        ->required()
                        ->unique(ignoreRecord: true)
                        ->maxLength(100)
                        ->disabledOn('edit')
                        ->dehydrated(),
                    Forms\Components\TextInput::make('label')
                        ->label('Display Name')
                        ->placeholder('Lead Created')
                        ->required()
                        ->maxLength(255),
                    Forms\Components\Select::make('group')
                        ->label('Group')
                        ->options([
                            'Leads'     => 'Leads',
                            'Projects'  => 'Projects',
                            'Invoices'  => 'Invoices',
                            'Quotes'    => 'Quotes',
                            'Contracts' => 'Contracts',
                            'Custom'    => 'Custom',
                        ])
                        ->createOptionForm([
                            Forms\Components\TextInput::make('label')->required(),
                        ])
                        ->nullable()
                        ->searchable(),
                    Forms\Components\Toggle::make('is_active')
                        ->label('Active')
                        ->default(true)
                        ->inline(false),
                    Forms\Components\Textarea::make('description')
                        ->label('Description')
                        ->placeholder('Describe when this trigger fires and what it does.')
                        ->rows(3)
                        ->columnSpanFull(),
                ]),

            Section::make('Available Variables')
                ->description('Variables available in SMS/Email templates when this trigger fires. Use {variable_name} syntax in templates.')
                ->schema([
                    Forms\Components\Repeater::make('variables')
                        ->schema([
                            Forms\Components\TextInput::make('name')
                                ->label('Variable Name')
                                ->placeholder('client_name')
                                ->helperText('No curly braces — just the key')
                                ->required(),
                            Forms\Components\TextInput::make('description')
                                ->label('Description')
                                ->placeholder('The contact\'s full name')
                                ->required(),
                        ])
                        ->columns(2)
                        ->addActionLabel('Add Variable')
                        ->defaultItems(0)
                        ->collapsible(),
                ])
                ->collapsible(),
        ]);
    }

    // ── Infolist ─────────────────────────────────────────────────────────────

    public static function infolist(Schema $schema): Schema
    {
        return $schema->schema([
            Section::make()
                ->columns(3)
                ->schema([
                    TextEntry::make('key')->badge()->color('gray')->copyable(),
                    TextEntry::make('label')->weight('bold'),
                    TextEntry::make('group')->badge(),
                    IconEntry::make('is_system')->label('System (built-in)')->boolean(),
                    IconEntry::make('is_active')->label('Active')->boolean(),
                    TextEntry::make('description')->columnSpanFull()->placeholder('—'),
                ]),
            Section::make('Available Variables')
                ->schema([
                    KeyValueEntry::make('variables')
                        ->label('')
                        ->getStateUsing(fn ($record) =>
                            collect($record->variables ?? [])
                                ->mapWithKeys(fn ($v) => ['{' . ($v['name'] ?? '') . '}' => $v['description'] ?? ''])
                                ->toArray()
                        ),
                ])
                ->collapsible(),
        ]);
    }

    // ── Table ─────────────────────────────────────────────────────────────────

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('key')
                    ->badge()
                    ->color('gray')
                    ->searchable()
                    ->copyable(),
                Tables\Columns\TextColumn::make('label')
                    ->searchable(),
                Tables\Columns\TextColumn::make('group')
                    ->badge()
                    ->placeholder('—'),
                Tables\Columns\IconColumn::make('is_system')
                    ->label('System')
                    ->boolean()
                    ->trueIcon('heroicon-o-lock-closed')
                    ->falseIcon('heroicon-o-pencil-square'),
                Tables\Columns\IconColumn::make('is_active')
                    ->label('Active')
                    ->boolean(),
                Tables\Columns\TextColumn::make('variables_count')
                    ->label('Variables')
                    ->getStateUsing(fn ($record) => count($record->variables ?? []))
                    ->badge()
                    ->color('gray'),
            ])
            ->defaultSort('group')
            ->groups(['group'])
            ->filters([
                Tables\Filters\TernaryFilter::make('is_system')->label('System triggers'),
                Tables\Filters\TernaryFilter::make('is_active')->label('Active'),
            ])
            ->actions([
                ViewAction::make(),
                EditAction::make()
                    ->visible(fn ($record) => ! $record->is_system),
                EditAction::make('edit_system')
                    ->label('Edit Labels')
                    ->visible(fn ($record) => $record->is_system),
                DeleteAction::make()
                    ->visible(fn ($record) => ! $record->is_system),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListAutomationTriggers::route('/'),
            'create' => Pages\CreateAutomationTrigger::route('/create'),
            'view'   => Pages\ViewAutomationTrigger::route('/{record}'),
            'edit'   => Pages\EditAutomationTrigger::route('/{record}/edit'),
        ];
    }
}
