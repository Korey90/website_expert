<?php

namespace App\Filament\Resources;

use App\Filament\Resources\EmailTemplateResource\Pages;
use App\Models\EmailTemplate;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class EmailTemplateResource extends Resource
{
    protected static ?string $model = EmailTemplate::class;
    protected static \BackedEnum|string|null $navigationIcon = 'heroicon-o-envelope';
    protected static \UnitEnum|string|null $navigationGroup = 'Marketing';
    protected static ?string $navigationLabel = 'Email Templates';
    protected static ?int $navigationSort = 1;

    public static function form(Schema $form): Schema
    {
        return $form->schema([
            Section::make('Template Details')
                ->columns(2)
                ->schema([
                    Forms\Components\TextInput::make('name')
                        ->label('Template Name')
                        ->required()
                        ->maxLength(255),
                    Forms\Components\TextInput::make('slug')
                        ->label('Slug (identifier)')
                        ->required()
                        ->unique(ignoreRecord: true)
                        ->maxLength(100)
                        ->helperText('Used in automation rules, e.g. welcome_email'),
                    Forms\Components\TextInput::make('subject')
                        ->label('Email Subject')
                        ->required()
                        ->maxLength(255)
                        ->columnSpanFull()
                        ->helperText('Supports variables: {{client_name}}, {{project_title}}, {{invoice_number}}, etc.'),
                    Forms\Components\Toggle::make('is_active')
                        ->label('Active')
                        ->default(true),
                    Forms\Components\TagsInput::make('variables')
                        ->label('Available Variables')
                        ->helperText('List variables this template uses, e.g. client_name')
                        ->placeholder('Add variable name'),
                ]),

            Section::make('HTML Content')
                ->schema([
                    Forms\Components\RichEditor::make('body_html')
                        ->label('HTML Body')
                        ->toolbarButtons([
                            'bold', 'italic', 'underline', 'strike',
                            'link', 'orderedList', 'bulletList',
                            'h2', 'h3', 'blockquote', 'codeBlock',
                        ])
                        ->columnSpanFull(),
                ]),

            Section::make('Plain Text Fallback')
                ->collapsed()
                ->schema([
                    Forms\Components\Textarea::make('body_text')
                        ->label('Plain Text Body')
                        ->rows(10)
                        ->columnSpanFull()
                        ->helperText('Fallback for email clients that do not support HTML.'),
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
                Tables\Columns\TextColumn::make('slug')
                    ->searchable()
                    ->copyable()
                    ->badge()
                    ->color('gray'),
                Tables\Columns\TextColumn::make('subject')
                    ->searchable()
                    ->limit(50),
                Tables\Columns\IconColumn::make('is_active')
                    ->label('Active')
                    ->boolean(),
                Tables\Columns\TextColumn::make('updated_at')
                    ->label('Last Updated')
                    ->date()
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Status')
                    ->trueLabel('Active only')
                    ->falseLabel('Inactive only'),
            ])
            ->actions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->defaultSort('name');
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListEmailTemplates::route('/'),
            'create' => Pages\CreateEmailTemplate::route('/create'),
            'edit'   => Pages\EditEmailTemplate::route('/{record}/edit'),
        ];
    }
}

