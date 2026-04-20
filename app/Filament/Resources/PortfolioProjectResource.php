<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PortfolioProjectResource\Pages;
use App\Models\PortfolioProject;
use Filament\Forms;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Schema;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Illuminate\Support\Facades\Storage;

class PortfolioProjectResource extends BaseResource
{
    protected static ?string $model = PortfolioProject::class;
    protected static \BackedEnum|string|null $navigationIcon = 'heroicon-o-photo';
    protected static \UnitEnum|string|null $navigationGroup = 'Marketing';
    protected static ?string $navigationLabel = 'Portfolio Projects';
    protected static ?string $label = 'Portfolio Project';
    protected static ?string $pluralLabel = 'Portfolio Projects';
    protected static ?int $navigationSort = 5;
    // Form
    // -------------------------------------------------------------------------

    public static function form(Schema $form): Schema
    {
        $locales = config('languages', ['en' => 'English', 'pl' => 'Polski', 'pt' => 'Português']);

        $tabSchemas = [];
        foreach ($locales as $code => $label) {
            $tabSchemas[] = Tab::make($label)
                ->schema([
                    Forms\Components\TextInput::make("title.{$code}")
                        ->label('Title')
                        ->maxLength(255)
                        ->nullable(),

                    Forms\Components\TextInput::make("tag.{$code}")
                        ->label('Tag / Category')
                        ->maxLength(100)
                        ->nullable(),

                    Forms\Components\Textarea::make("description.{$code}")
                        ->label('Description')
                        ->rows(4)
                        ->nullable(),

                    Forms\Components\Textarea::make("result.{$code}")
                        ->label('Result / Outcome')
                        ->rows(3)
                        ->nullable(),
                ]);
        }

        return $form->schema([
            Tabs::make('Portfolio Project')
                ->columnSpanFull()
                ->tabs([
                    Tab::make('Content')
                        ->icon('heroicon-o-language')
                        ->schema([
                            Tabs::make('Translations')
                                ->columnSpanFull()
                                ->tabs($tabSchemas),
                        ]),

                    Tab::make('Media & Links')
                        ->icon('heroicon-o-link')
                        ->schema([
                            Forms\Components\TextInput::make('client_name')
                                ->label('Client Name')
                                ->maxLength(255)
                                ->nullable(),

                            Forms\Components\TextInput::make('slug')
                                ->label('Slug (URL key)')
                                ->helperText('Auto-generated from client name. Used in /portfolio/{slug}.')
                                ->maxLength(255)
                                ->unique(ignoreRecord: true)
                                ->nullable(),

                            Forms\Components\FileUpload::make('image_path')
                                ->label('Project Image')
                                ->image()
                                ->disk('public')
                                ->directory('portfolio')
                                ->nullable(),

                            Forms\Components\TextInput::make('link')
                                ->label('Project URL')
                                ->type('text')
                                ->rules(['nullable', 'string', 'max:512', 'regex:/^(https?:\/\/|\/)/'])
                                ->maxLength(512)
                                ->helperText('Relative path (e.g. /portfolio/slug) or full URL (https://…)')
                                ->nullable(),

                            Forms\Components\TagsInput::make('tags')
                                ->label('Tags')
                                ->separator(',')
                                ->nullable(),
                        ]),

                    Tab::make('Settings')
                        ->icon('heroicon-o-cog-6-tooth')
                        ->schema([
                            Forms\Components\Toggle::make('is_featured')
                                ->label('Featured (show on homepage)')
                                ->inline(false)
                                ->default(false),

                            Forms\Components\Toggle::make('is_active')
                                ->label('Active')
                                ->inline(false)
                                ->default(true),

                            Forms\Components\TextInput::make('sort_order')
                                ->label('Sort Order')
                                ->integer()
                                ->default(0)
                                ->minValue(0),
                        ]),
                ]),
        ]);
    }

    // -------------------------------------------------------------------------
    // Table
    // -------------------------------------------------------------------------

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('sort_order')
            ->columns([
                Tables\Columns\TextColumn::make('image_path')
                    ->label('Image')
                    ->html()
                    ->getStateUsing(function (PortfolioProject $record): string {
                        $path = $record->image_path;
                        if (! $path) {
                            return '<div class="w-12 h-12 rounded bg-gray-100 dark:bg-gray-800"></div>';
                        }
                        $url = (str_starts_with($path, '/') || str_starts_with($path, 'http'))
                            ? $path
                            : Storage::disk('public')->url($path);
                        return "<img src=\"{$url}\" class=\"w-12 h-12 object-cover rounded\" loading=\"lazy\" />";
                    }),

                Tables\Columns\TextColumn::make('title_en_display')
                    ->label('Title (EN)')
                    ->getStateUsing(fn (PortfolioProject $record): string => $record->getTranslation('title', 'en') ?? '')
                    ->searchable(query: function ($query, string $value) {
                        $query->whereRaw("JSON_UNQUOTE(JSON_EXTRACT(title, '$.en')) LIKE ?", ["%{$value}%"]);
                    })
                    ->limit(40),

                Tables\Columns\TextColumn::make('client_name')
                    ->label('Client')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('tag.en')
                    ->label('Tag')
                    ->badge()
                    ->color('info'),

                Tables\Columns\IconColumn::make('is_featured')
                    ->label('Featured')
                    ->boolean()
                    ->sortable(),

                Tables\Columns\IconColumn::make('is_active')
                    ->label('Active')
                    ->boolean()
                    ->sortable(),

                Tables\Columns\TextColumn::make('sort_order')
                    ->label('#')
                    ->sortable()
                    ->alignCenter(),
            ])
            ->filters([
                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Active'),

                Tables\Filters\TernaryFilter::make('is_featured')
                    ->label('Featured'),
            ])
            ->actions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->bulkActions([
                DeleteBulkAction::make(),
            ])
            ->reorderable('sort_order');
    }

    // -------------------------------------------------------------------------
    // Pages
    // -------------------------------------------------------------------------

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListPortfolioProjects::route('/'),
            'create' => Pages\CreatePortfolioProject::route('/create'),
            'edit'   => Pages\EditPortfolioProject::route('/{record}/edit'),
        ];
    }
}
