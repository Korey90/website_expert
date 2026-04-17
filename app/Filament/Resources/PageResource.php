<?php

namespace App\Filament\Resources;

use App\Forms\Components\TinyEditor;
use App\Filament\Resources\PageResource\Pages;
use App\Models\Page;
use Filament\Forms;
use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Schema;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Table;

class PageResource extends Resource
{
    protected static ?string $model = Page::class;
    protected static \BackedEnum|string|null $navigationIcon = 'heroicon-o-document';
    protected static \UnitEnum|string|null $navigationGroup = 'Marketing';
    protected static ?string $navigationLabel = 'CMS Pages';
    protected static ?int $navigationSort = 7;

    public static function infolist(Schema $schema): Schema
    {
        $locales = config('languages', ['en' => 'English', 'pl' => 'Polski']);

        return $schema->columns(1)->schema([

            Section::make('Page Settings')
                ->columns(2)
                ->schema([
                    TextEntry::make('title')
                        ->label('Title')
                        ->weight('bold')
                        ->size('lg')
                        ->columnSpanFull()
                        ->getStateUsing(fn ($record) => $record->getTranslation('title', 'en') ?: '—'),

                    TextEntry::make('status')
                        ->label('Status')
                        ->badge()
                        ->color(fn ($state) => match ($state) {
                            'published' => 'success',
                            default     => 'gray',
                        }),

                    TextEntry::make('slug')
                        ->label('URL slug')
                        ->copyable()
                        ->icon('heroicon-o-link')
                        ->getStateUsing(fn ($record) => '/p/' . $record->slug),

                    TextEntry::make('type')
                        ->label('Type')
                        ->badge()
                        ->color('info'),

                    TextEntry::make('sort_order')
                        ->label('Sort order')
                        ->placeholder('0'),

                    IconEntry::make('show_in_footer')
                        ->label('Show in footer')
                        ->boolean(),

                    TextEntry::make('published_at')
                        ->label('Published')
                        ->dateTime('d M Y, H:i')
                        ->placeholder('Not yet published'),

                    TextEntry::make('createdBy.name')
                        ->label('Created by')
                        ->placeholder('—')
                        ->icon('heroicon-o-user'),
                ]),

            Section::make('Document Metadata')
                ->description('Effective date and version for legal/policy documents.')
                ->columns(2)
                ->collapsed()
                ->schema([
                    TextEntry::make('effective_date')
                        ->label('Effective date')
                        ->date('d M Y')
                        ->placeholder('—'),

                    TextEntry::make('version')
                        ->label('Version')
                        ->badge()
                        ->color('gray')
                        ->placeholder('—'),

                    TextEntry::make('updated_at')
                        ->label('Last updated')
                        ->dateTime('d M Y, H:i')
                        ->since(),
                ]),

            Section::make('Content')
                ->collapsed()
                ->schema([
                    Tabs::make('Translations')
                        ->tabs(
                            array_map(
                                fn (string $locale, string $label) => Tab::make($label)
                                    ->schema([
                                        TextEntry::make("title.{$locale}")
                                            ->label("Title ({$locale})")
                                            ->getStateUsing(fn ($record) => $record->getTranslation('title', $locale) ?: '—'),

                                        TextEntry::make("content.{$locale}")
                                            ->label("Content ({$locale})")
                                            ->html()
                                            ->columnSpanFull()
                                            ->getStateUsing(fn ($record) => $record->getTranslation('content', $locale) ?: '<em class="text-gray-400">No content</em>'),

                                        TextEntry::make("meta_title.{$locale}")
                                            ->label("Meta title ({$locale})")
                                            ->placeholder('—')
                                            ->getStateUsing(fn ($record) => $record->getTranslation('meta_title', $locale) ?: null),

                                        TextEntry::make("meta_description.{$locale}")
                                            ->label("Meta description ({$locale})")
                                            ->placeholder('—')
                                            ->getStateUsing(fn ($record) => $record->getTranslation('meta_description', $locale) ?: null),
                                    ]),
                                array_keys($locales),
                                array_values($locales),
                            )
                        )
                        ->columnSpanFull(),
                ]),
        ]);
    }

    public static function form(Schema $form): Schema
    {
        $locales = config('languages', ['en' => 'English', 'pl' => 'Polski']);

        return $form->schema([

            Section::make('Page Settings')
                ->columns(2)
                ->schema([
                    Forms\Components\TextInput::make('slug')
                        ->required()
                        ->unique(ignoreRecord: true)
                        ->maxLength(255)
                        ->helperText('URL: /p/{slug}'),
                    Forms\Components\Select::make('type')
                        ->options([
                            'page'            => 'Page',
                            'policy'          => 'Privacy Policy',
                            'terms'           => 'Terms & Conditions',
                            'cookie_policy'   => 'Cookie Policy',
                            'accessibility'   => 'Accessibility Statement',
                            'other'           => 'Other',
                        ])
                        ->default('page')
                        ->required()
                        ->live(),
                    Forms\Components\Select::make('status')
                        ->options(['draft' => 'Draft', 'published' => 'Published'])
                        ->default('draft')
                        ->required(),
                    Forms\Components\TextInput::make('sort_order')->numeric()->default(0),
                    Forms\Components\Toggle::make('show_in_footer')
                        ->label('Show in footer navigation')
                        ->default(false)
                        ->columnSpanFull(),
                ]),

            Section::make('Document Metadata')
                ->description('Effective date and version override per-document. You can also set these globally in Settings → Legal & Company.')
                ->columns(2)
                ->collapsed()
                ->visible(fn (\Filament\Schemas\Components\Utilities\Get $get) => $get('type') !== 'page')
                ->schema([
                    Forms\Components\DatePicker::make('effective_date')
                        ->label('Effective date')
                        ->displayFormat('d M Y')
                        ->helperText('Override the global effective date for this document'),
                    Forms\Components\TextInput::make('version')
                        ->label('Document version')
                        ->placeholder('e.g. 1.0, 2025-01')
                        ->maxLength(20),
                ]),

            Section::make('Content')
                ->schema([
                    Tabs::make('Translations')
                        ->tabs(
                            array_map(
                                fn (string $locale, string $label) => Tab::make($label)
                                    ->schema([
                                        Forms\Components\TextInput::make("title.{$locale}")
                                            ->label("Title ({$locale})")
                                            ->required()
                                            ->maxLength(255),
                                        TinyEditor::make("content.{$locale}")
                                            ->label("Content ({$locale})"),
                                        Forms\Components\TextInput::make("meta_title.{$locale}")
                                            ->label("Meta title ({$locale})")
                                            ->maxLength(100),
                                        Forms\Components\Textarea::make("meta_description.{$locale}")
                                            ->label("Meta description ({$locale})")
                                            ->rows(2)
                                            ->maxLength(300),
                                    ]),
                                array_keys($locales),
                                array_values($locales),
                            )
                        )
                        ->columnSpanFull(),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('title')
                    ->getStateUsing(fn ($record) => $record->getTranslation('title', 'en'))
                    ->searchable(query: fn ($query, $value) => $query->whereRaw("JSON_UNQUOTE(JSON_EXTRACT(title, '$.en')) LIKE ?", ["%{$value}%"]))
                    ->sortable(),
                Tables\Columns\TextColumn::make('slug')->searchable(),
                Tables\Columns\TextColumn::make('type')->badge(),
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn ($state) => match ($state) {
                        'published' => 'success',
                        default     => 'gray',
                    }),
                Tables\Columns\IconColumn::make('show_in_footer')->boolean(),
                Tables\Columns\TextColumn::make('effective_date')
                    ->label('Effective')
                    ->date('d M Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('version')
                    ->badge()
                    ->color('gray')
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('updated_at')->date()->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('type')
                    ->options(['page' => 'Page', 'policy' => 'Policy', 'terms' => 'Terms', 'cookie_policy' => 'Cookies', 'accessibility' => 'Accessibility', 'other' => 'Other']),
                Tables\Filters\SelectFilter::make('status')
                    ->options(['draft' => 'Draft', 'published' => 'Published']),
                Tables\Filters\TrashedFilter::make(),
            ])
            ->actions([ViewAction::make(), EditAction::make(), DeleteAction::make()])
            ->reorderable('sort_order')
            ->defaultSort('sort_order');
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListPages::route('/'),
            'create' => Pages\CreatePage::route('/create'),
            'view'   => Pages\ViewPage::route('/{record}'),
            'edit'   => Pages\EditPage::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): \Illuminate\Database\Eloquent\Builder
    {
        return parent::getEloquentQuery()->withTrashed();
    }

}