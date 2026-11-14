<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ArticleResource\Pages;
use App\Models\Article;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;

class ArticleResource extends BaseResource
{
    protected static ?string $model = Article::class;

    protected static ?string $recordTitleAttribute = 'slug';

    protected static \BackedEnum|string|null $navigationIcon = 'heroicon-o-newspaper';

    protected static \UnitEnum|string|null $navigationGroup = 'Marketing';

    protected static ?string $navigationLabel = 'Blog Articles';

    protected static ?string $label = 'Article';

    protected static ?string $pluralLabel = 'Articles';

    protected static ?int $navigationSort = 8;

    /** @return array<string> */
    public static function getGloballySearchableAttributes(): array
    {
        return ['slug', 'category'];
    }

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

                    Forms\Components\Textarea::make("excerpt.{$code}")
                        ->label('Excerpt')
                        ->rows(2)
                        ->maxLength(255)
                        ->nullable()
                        ->helperText('Shown on the blog listing and as a meta description fallback.'),

                    Forms\Components\RichEditor::make("content.{$code}")
                        ->label('Content (Rich Text)')
                        ->nullable()
                        ->toolbarButtons([
                            'bold', 'italic', 'underline', 'strike',
                            'h2', 'h3',
                            'bulletList', 'orderedList',
                            'link', 'blockquote',
                        ])
                        ->columnSpanFull(),

                    Forms\Components\TextInput::make("meta_title.{$code}")
                        ->label('Meta Title (SEO)')
                        ->maxLength(70)
                        ->nullable(),

                    Forms\Components\Textarea::make("meta_description.{$code}")
                        ->label('Meta Description (SEO)')
                        ->rows(2)
                        ->maxLength(160)
                        ->nullable(),
                ]);
        }

        return $form->schema([
            Tabs::make('Article')
                ->columnSpanFull()
                ->tabs([
                    Tab::make('Content')
                        ->icon('heroicon-o-language')
                        ->schema([
                            Tabs::make('Translations')
                                ->columnSpanFull()
                                ->tabs($tabSchemas),
                        ]),

                    Tab::make('Settings')
                        ->icon('heroicon-o-cog-6-tooth')
                        ->schema([
                            Forms\Components\TextInput::make('slug')
                                ->label('Slug')
                                ->helperText('Auto-generated from English title on creation.')
                                ->maxLength(100)
                                ->unique(ignoreRecord: true)
                                ->nullable(),

                            Forms\Components\Select::make('category')
                                ->label('Category')
                                ->options([
                                    'pricing' => 'Pricing',
                                    'seo' => 'SEO',
                                    'pl-business' => 'Polish businesses in UK',
                                    'comparison' => 'Comparison / How-to',
                                ])
                                ->searchable()
                                ->nullable(),

                            Forms\Components\FileUpload::make('featured_image_path')
                                ->label('Featured Image')
                                ->image()
                                ->disk('public')
                                ->directory('articles')
                                ->imagePreviewHeight('200')
                                ->nullable(),

                            Forms\Components\Select::make('status')
                                ->label('Status')
                                ->options(['draft' => 'Draft', 'published' => 'Published'])
                                ->default('draft')
                                ->required()
                                ->native(false),

                            Forms\Components\DateTimePicker::make('published_at')
                                ->label('Published at')
                                ->nullable(),
                        ]),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('published_at', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('title_en_display')
                    ->label('Title (EN)')
                    ->getStateUsing(fn (Article $article): string => $article->getTranslation('title', 'en') ?? '')
                    ->searchable(query: function ($query, string $value) {
                        $query->whereRaw("JSON_UNQUOTE(JSON_EXTRACT(title, '$.en')) LIKE ?", ["%{$value}%"]);
                    })
                    ->limit(50)
                    ->weight('bold'),

                Tables\Columns\TextColumn::make('category')
                    ->label('Category')
                    ->badge()
                    ->color('gray')
                    ->visibleFrom('md'),

                Tables\Columns\TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->color(fn (string $state): string => $state === 'published' ? 'success' : 'gray'),

                Tables\Columns\TextColumn::make('published_at')
                    ->label('Published')
                    ->dateTime('d M Y')
                    ->placeholder('—')
                    ->sortable()
                    ->visibleFrom('md'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options(['draft' => 'Draft', 'published' => 'Published']),
            ])
            ->actions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->bulkActions([
                DeleteBulkAction::make(),
            ])
            ->recordUrl(fn (Article $record): ?string => static::canEdit($record)
                ? static::getUrl('edit', ['record' => $record])
                : null)
            ->emptyStateIcon('heroicon-o-newspaper')
            ->emptyStateHeading(__('admin.migration.marketing.articles.empty_heading'))
            ->emptyStateDescription(__('admin.migration.marketing.articles.empty_description'));
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListArticles::route('/'),
            'create' => Pages\CreateArticle::route('/create'),
            'edit' => Pages\EditArticle::route('/{record}/edit'),
        ];
    }
}
