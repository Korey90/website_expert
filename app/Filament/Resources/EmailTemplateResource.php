<?php

namespace App\Filament\Resources;

use App\Filament\Resources\EmailTemplateResource\Pages;
use App\Models\EmailTemplate;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms;
use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Html;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use App\Forms\Components\TinyEditor;
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

    public static function infolist(Schema $schema): Schema
    {
        $langTab = fn (string $code, string $flag) => Tab::make($flag)
            ->schema([
                TextEntry::make("subject.{$code}")
                    ->label('Subject Line')
                    ->copyable()
                    ->copyMessage('Subject copied!')
                    ->icon('heroicon-m-envelope')
                    ->weight('bold')
                    ->columnSpanFull(),

                Html::make(fn ($record) =>
                    '<div style="border-radius:10px;overflow:hidden;border:1px solid #e2e8f0;background:#f1f5f9;">'
                    . '<div style="background:#f8fafc;padding:8px 16px;border-bottom:1px solid #e2e8f0;font-size:12px;color:#64748b;font-family:monospace;display:flex;align-items:center;gap:8px;">'
                    . '<span style="width:10px;height:10px;border-radius:50%;background:#ef4444;display:inline-block;"></span>'
                    . '<span style="width:10px;height:10px;border-radius:50%;background:#f59e0b;display:inline-block;margin:0 2px;"></span>'
                    . '<span style="width:10px;height:10px;border-radius:50%;background:#10b981;display:inline-block;"></span>'
                    . '<span style="margin-left:8px;">Email Preview – ' . strtoupper($code) . '</span>'
                    . '<a href="' . route('admin.email-preview', [$record->id, $code]) . '" target="_blank" style="margin-left:auto;color:#4F46E5;text-decoration:none;font-size:11px;font-family:sans-serif;">&#8599; open full page</a>'
                    . '</div>'
                    . '<iframe src="' . route('admin.email-preview', [$record->id, $code]) . '" loading="lazy" style="width:100%;height:560px;border:none;display:block;"></iframe>'
                    . '</div>'
                )->columnSpanFull(),

                TextEntry::make("body_text.{$code}")
                    ->label('Plain Text Fallback')
                    ->wrap()
                    ->fontFamily('mono')
                    ->columnSpanFull()
                    ->placeholder('— not set —'),
            ]);

        return $schema->schema([
            Section::make()
                ->columns(4)
                ->schema([
                    TextEntry::make('name')
                        ->label('Template Name')
                        ->weight('bold')
                        ->size('lg')
                        ->columnSpan(2),

                    TextEntry::make('slug')
                        ->label('Identifier (Slug)')
                        ->badge()
                        ->color('gray')
                        ->copyable()
                        ->copyMessage('Slug copied!'),

                    IconEntry::make('is_active')
                        ->label('Status')
                        ->boolean()
                        ->trueIcon('heroicon-o-check-circle')
                        ->falseIcon('heroicon-o-x-circle')
                        ->trueColor('success')
                        ->falseColor('danger'),

                    TextEntry::make('variables')
                        ->label('Available Variables')
                        ->badge()
                        ->color('info')
                        ->columnSpanFull()
                        ->placeholder('No variables defined'),

                    TextEntry::make('updated_at')
                        ->label('Last Updated')
                        ->dateTime('d M Y, H:i')
                        ->since(),

                    TextEntry::make('created_at')
                        ->label('Created At')
                        ->dateTime('d M Y, H:i'),
                ]),

            Tabs::make('Languages')
                ->columnSpanFull()
                ->tabs([
                    $langTab('en', '🇬🇧 English'),
                    $langTab('pl', '🇵🇱 Polish'),
                    $langTab('pt', '🇵🇹 Portuguese'),
                ]),
        ]);
    }

    public static function form(Schema $form): Schema
    {
        $langTab = fn (string $code, string $label) => Tab::make($label)->schema([
            Forms\Components\TextInput::make("subject.{$code}")
                ->label('Email Subject')
                ->maxLength(255)
                ->helperText('Supports variables: {{client_name}}, {{project_title}}, {{invoice_number}}, etc.'),
            TinyEditor::make("body_html.{$code}")
                ->label('HTML Body')
                ->columnSpanFull(),
            Forms\Components\Textarea::make("body_text.{$code}")
                ->label('Plain Text Fallback')
                ->rows(8)
                ->columnSpanFull()
                ->helperText('Fallback for email clients that do not support HTML.'),
        ]);

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
                    Forms\Components\Toggle::make('is_active')
                        ->label('Active')
                        ->default(true),
                    Forms\Components\TagsInput::make('variables')
                        ->label('Available Variables')
                        ->helperText('List variables this template uses, e.g. client_name')
                        ->placeholder('Add variable name'),
                ]),

            Tabs::make('Languages')
                ->columnSpanFull()
                ->tabs([
                    $langTab('en', '🇬🇧 English'),
                    $langTab('pl', '🇵🇱 Polish'),
                    $langTab('pt', '🇵🇹 Portuguese'),
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
                Tables\Columns\TextColumn::make('subject.en')
                    ->label('Subject (EN)')
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
                ViewAction::make(),
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
            'view'   => Pages\ViewEmailTemplate::route('/{record}'),
            'edit'   => Pages\EditEmailTemplate::route('/{record}/edit'),
        ];
    }
}

