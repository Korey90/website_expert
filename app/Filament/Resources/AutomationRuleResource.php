<?php

namespace App\Filament\Resources;

use App\Filament\Resources\AutomationRuleResource\Pages;
use App\Models\AutomationRule;
use Filament\Forms;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Tables\Table;

class AutomationRuleResource extends Resource
{
    protected static ?string $model = AutomationRule::class;
    protected static \BackedEnum|string|null $navigationIcon = 'heroicon-o-bolt';
    protected static \UnitEnum|string|null $navigationGroup = 'Settings';
    protected static ?string $navigationLabel = 'Automation Rules';
    protected static ?int $navigationSort = 4;

    // Available trigger events
    const TRIGGERS = [
        'lead.created'            => 'Lead Created',
        'lead.stage_changed'      => 'Lead Stage Changed',
        'project.created'         => 'Project Created',
        'project.status_changed'  => 'Project Status Changed',
        'invoice.sent'            => 'Invoice Sent',
        'invoice.overdue'         => 'Invoice Overdue',
        'invoice.paid'            => 'Invoice Paid',
        'quote.sent'              => 'Quote Sent',
        'quote.accepted'          => 'Quote Accepted',
    ];

    public static function form(Schema $form): Schema
    {
        return $form->schema([
            Forms\Components\TextInput::make('name')->required()->maxLength(255),
            Forms\Components\Select::make('trigger_event')
                ->options(self::TRIGGERS)
                ->required(),
            Forms\Components\TextInput::make('delay_minutes')->numeric()->default(0)->helperText('0 = immediate'),
            Forms\Components\Toggle::make('is_active')->default(true),

            Section::make('Actions')
                ->schema([
                    Forms\Components\Repeater::make('actions')
                        ->schema([
                            Forms\Components\Select::make('type')
                                ->options(['send_email' => 'Send Email', 'send_sms' => 'Send SMS', 'notify_admin' => 'Notify Admin'])
                                ->required()
                                ->reactive(),
                            Forms\Components\Select::make('to')
                                ->options(['client' => 'Client', 'admin' => 'Admin', 'assigned_user' => 'Assigned User'])
                                ->default('client'),
                            Forms\Components\TextInput::make('template_slug')
                                ->label('Email Template Slug')
                                ->visible(fn (Get $get) => $get('type') === 'send_email'),
                            Forms\Components\TextInput::make('message')
                                ->label('SMS Message')
                                ->visible(fn (Get $get) => $get('type') === 'send_sms'),
                        ])
                        ->columns(2)
                        ->defaultItems(1),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')->searchable(),
                Tables\Columns\TextColumn::make('trigger_event')
                    ->formatStateUsing(fn ($s) => self::TRIGGERS[$s] ?? $s)
                    ->badge(),
                Tables\Columns\TextColumn::make('delay_minutes')->suffix(' min'),
                Tables\Columns\IconColumn::make('is_active')->boolean(),
            ])
            ->actions([EditAction::make(), DeleteAction::make()])
            ->defaultSort('name');
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListAutomationRules::route('/'),
            'create' => Pages\CreateAutomationRule::route('/create'),
            'edit'   => Pages\EditAutomationRule::route('/{record}/edit'),
        ];
    }
}

