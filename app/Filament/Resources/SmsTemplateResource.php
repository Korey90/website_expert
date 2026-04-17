<?php

namespace App\Filament\Resources;

use App\Filament\Resources\SmsTemplateResource\Pages;
use App\Models\SmsTemplate;
use Filament\Forms;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Tables\Table;

class SmsTemplateResource extends Resource
{
    protected static ?string $model = SmsTemplate::class;
    protected static \BackedEnum|string|null $navigationIcon  = 'heroicon-o-chat-bubble-left-ellipsis';
    protected static \UnitEnum|string|null   $navigationGroup = 'Marketing';
    protected static ?string $navigationLabel = 'SMS Templates';
    protected static ?int    $navigationSort  = 3;

    public static function form(Schema $form): Schema
    {
        $vars = collect(SmsTemplate::availableVariables())
            ->map(fn ($desc, $key) => "{{" . $key . "}} — {$desc}")
            ->implode("\n");

        return $form->schema([
            Section::make('Template Details')
                ->schema([
                    Forms\Components\TextInput::make('name')
                        ->required()
                        ->maxLength(255)
                        ->placeholder('e.g. New Lead Welcome'),

                    Forms\Components\Toggle::make('is_active')
                        ->default(true)
                        ->label('Active'),

                    Forms\Components\Textarea::make('description')
                        ->rows(2)
                        ->placeholder('Internal notes about when to use this template...')
                        ->columnSpanFull(),
                ])
                ->columns(2),

            Section::make('Message Content')
                ->description('Use {{variable}} placeholders. Max 160 chars = 1 SMS segment.')
                ->schema([
                    Forms\Components\Textarea::make('content')
                        ->required()
                        ->rows(5)
                        ->maxLength(1600)
                        ->placeholder("Hi {{client_name}}, thanks for reaching out to {{company_name}}. We'll be in touch shortly.")
                        ->helperText("Available variables:\n{$vars}")
                        ->columnSpanFull(),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('content')
                    ->limit(80)
                    ->tooltip(fn ($record) => $record->content)
                    ->label('Content preview'),

                Tables\Columns\TextColumn::make('content')
                    ->label('Chars')
                    ->formatStateUsing(fn ($state) => strlen($state))
                    ->sortable(false),

                Tables\Columns\IconColumn::make('is_active')
                    ->boolean()
                    ->label('Active'),

                Tables\Columns\TextColumn::make('updated_at')
                    ->dateTime('d M Y')
                    ->sortable()
                    ->label('Updated'),
            ])
            ->actions([EditAction::make(), DeleteAction::make()])
            ->defaultSort('name');
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListSmsTemplates::route('/'),
            'create' => Pages\CreateSmsTemplate::route('/create'),
            'edit'   => Pages\EditSmsTemplate::route('/{record}/edit'),
        ];
    }
}
