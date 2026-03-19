<?php

namespace App\Filament\Resources;

use App\Filament\Resources\SiteSectionResource\Pages;
use App\Models\SiteSection;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class SiteSectionResource extends Resource
{
    protected static ?string $model = SiteSection::class;
    protected static \BackedEnum|string|null $navigationIcon = 'heroicon-o-squares-2x2';
    protected static \UnitEnum|string|null $navigationGroup = 'Marketing';
    protected static ?string $navigationLabel = 'Front-end Sections';
    protected static ?int $navigationSort = 2;

    public static function form(Schema $form): Schema
    {
        return $form->schema([
            Section::make('Section Identity')
                ->columns(2)
                ->schema([
                    Forms\Components\TextInput::make('key')
                        ->label('Section Key')
                        ->required()
                        ->unique(ignoreRecord: true)
                        ->maxLength(100)
                        ->helperText('Identifier used in React components, e.g. hero, about, services, cta'),
                    Forms\Components\TextInput::make('label')
                        ->label('Display Name')
                        ->required()
                        ->maxLength(255),
                    Forms\Components\Toggle::make('is_active')
                        ->label('Visible on site')
                        ->default(true),
                    Forms\Components\TextInput::make('sort_order')
                        ->numeric()
                        ->default(0)
                        ->label('Order'),
                ]),

            Section::make('Content')
                ->columns(2)
                ->schema([
                    Forms\Components\TextInput::make('title')
                        ->label('Heading / Title')
                        ->maxLength(255)
                        ->columnSpanFull(),
                    Forms\Components\TextInput::make('subtitle')
                        ->label('Subtitle / Tagline')
                        ->maxLength(255)
                        ->columnSpanFull(),
                    Forms\Components\RichEditor::make('body')
                        ->label('Body Content')
                        ->toolbarButtons(['bold', 'italic', 'underline', 'link', 'orderedList', 'unorderedList', 'h2', 'h3', 'blockquote'])
                        ->columnSpanFull(),
                ]),

            Section::make('Call to Action')
                ->columns(2)
                ->collapsed()
                ->schema([
                    Forms\Components\TextInput::make('button_text')
                        ->label('Button Label')
                        ->maxLength(100),
                    Forms\Components\TextInput::make('button_url')
                        ->label('Button URL')
                        ->maxLength(500)
                        ->url(),
                ]),

            Section::make('Media')
                ->columns(1)
                ->collapsed()
                ->schema([
                    Forms\Components\FileUpload::make('image_path')
                        ->label('Section Image')
                        ->image()
                        ->directory('site-sections')
                        ->imageResizeMode('cover')
                        ->imageCropAspectRatio('16:9'),
                ]),

            Section::make('Extra Data (JSON)')
                ->collapsed()
                ->schema([
                    Forms\Components\KeyValue::make('extra')
                        ->label('Custom Key-Value Pairs')
                        ->helperText('Additional data passed to the React component')
                        ->columnSpanFull(),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('sort_order')
                    ->label('#')
                    ->sortable(),
                Tables\Columns\TextColumn::make('key')
                    ->badge()
                    ->color('gray')
                    ->copyable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('label')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('title')
                    ->label('Heading')
                    ->limit(40),
                Tables\Columns\IconColumn::make('is_active')
                    ->label('Active')
                    ->boolean(),
                Tables\Columns\TextColumn::make('updated_at')
                    ->label('Updated')
                    ->date()
                    ->sortable(),
            ])
            ->defaultSort('sort_order')
            ->reorderable('sort_order')
            ->actions([EditAction::make(), DeleteAction::make()])
            ->filters([
                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Status')
                    ->trueLabel('Visible')
                    ->falseLabel('Hidden'),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListSiteSections::route('/'),
            'create' => Pages\CreateSiteSection::route('/create'),
            'edit'   => Pages\EditSiteSection::route('/{record}/edit'),
        ];
    }
}
