<?php

namespace App\Filament\Resources;

use App\Exceptions\LandingPageGenerationException;
use App\Filament\Resources\ServicePageResource\Pages;
use App\Models\ServicePage;
use App\Models\ServicePageBlock;
use App\Services\ServicePage\ServicePageTranslationService;
use Filament\Actions\Action;
use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;

class ServicePageResource extends BaseResource
{
    protected static ?string $model = ServicePage::class;
    protected static \BackedEnum|string|null $navigationIcon  = 'heroicon-o-paint-brush';
    protected static \UnitEnum|string|null   $navigationGroup = 'Marketing';
    protected static ?string $navigationLabel = 'Service Pages';
    protected static ?string $label           = 'Service Page';
    protected static ?string $pluralLabel     = 'Service Pages';
    protected static ?int    $navigationSort  = 5;

    // -------------------------------------------------------------------------
    // Form
    // -------------------------------------------------------------------------

    public static function form(Schema $form): Schema
    {
        $locales = config('languages', ['en' => 'English', 'pl' => 'Polski', 'pt' => 'Português']);

        return $form->schema([
            Tabs::make('Service Page')
                ->columnSpanFull()
                ->tabs([

                    // ---------------------------------------------------------
                    // Tab: Settings
                    // ---------------------------------------------------------
                    Tab::make('Settings')
                        ->icon('heroicon-o-cog-6-tooth')
                        ->schema([
                            Section::make('Page Identity')
                                ->columns(2)
                                ->schema([
                                    Forms\Components\TextInput::make('slug')
                                        ->label('Slug (URL path)')
                                        ->required()
                                        ->maxLength(100)
                                        ->unique(ignoreRecord: true)
                                        ->helperText('e.g. "seo" → your-domain.com/seo')
                                        ->rules(['regex:/^[a-z0-9\-]+$/']),

                                    Forms\Components\TextInput::make('sort_order')
                                        ->label('Sort Order')
                                        ->numeric()
                                        ->default(0),

                                    Forms\Components\Toggle::make('is_published')
                                        ->label('Published (visible on site)')
                                        ->default(false),

                                    Forms\Components\Toggle::make('show_in_nav')
                                        ->label('Show in Navigation')
                                        ->default(false),
                                ]),

                            Section::make('Title & SEO')
                                ->columns(1)
                                ->headerActions([
                                    Action::make('translateTitleSeo')
                                        ->label('Translate with AI')
                                        ->icon('heroicon-o-language')
                                        ->color('info')
                                        ->size('sm')
                                        ->requiresConfirmation()
                                        ->modalHeading('Translate from Polish with AI')
                                        ->modalDescription('AI wygeneruje tłumaczenia EN i PT na podstawie polskich wartości Title, Nav Label, Meta Title i Meta Description. Istniejące EN/PT zostaną nadpisane.')
                                        ->modalSubmitActionLabel('Generate')
                                        ->action(function (Get $get, Set $set): void {
                                            $source = [
                                                'title'            => $get('title.pl') ?? '',
                                                'meta_title'       => $get('meta_title.pl') ?? '',
                                                'meta_description' => $get('meta_description.pl') ?? '',
                                                'nav_label'        => $get('nav_label.pl') ?? '',
                                            ];
                                            if (empty(array_filter($source))) {
                                                Notification::make()->title('Brak treści PL')->body('Uzupełnij pola w zakładce Polski przed tłumaczeniem.')->warning()->send();
                                                return;
                                            }
                                            try {
                                                $translations = app(ServicePageTranslationService::class)->translatePage($source);
                                            } catch (LandingPageGenerationException $e) {
                                                Notification::make()->title('Translation failed')->body($e->getMessage())->danger()->send();
                                                return;
                                            }
                                            foreach (['en', 'pt'] as $locale) {
                                                $set("title.{$locale}",            $translations[$locale]['title']);
                                                $set("meta_title.{$locale}",       $translations[$locale]['meta_title']);
                                                $set("meta_description.{$locale}", $translations[$locale]['meta_description']);
                                                $set("nav_label.{$locale}",        $translations[$locale]['nav_label']);
                                            }
                                            Notification::make()->title('Translations generated')->body('EN and PT fields updated. Save to persist.')->success()->send();
                                        })
                                        ->visible(fn (): bool => filled(config('services.openai.api_key'))),
                                ])
                                ->schema([
                                    Tabs::make('Translations')
                                        ->columnSpanFull()
                                        ->tabs(array_map(
                                            fn (string $code, string $label) => Tab::make($label)->schema([
                                                Forms\Components\TextInput::make("title.{$code}")
                                                    ->label("Page Title ({$code})")
                                                    ->maxLength(120)
                                                    ->nullable(),
                                                Forms\Components\TextInput::make("nav_label.{$code}")
                                                    ->label("Navigation Label ({$code})")
                                                    ->maxLength(60)
                                                    ->nullable()
                                                    ->helperText('Short label for nav menu. If empty, uses Page Title.'),
                                                Forms\Components\TextInput::make("meta_title.{$code}")
                                                    ->label("Meta Title ({$code})")
                                                    ->maxLength(70)
                                                    ->nullable(),
                                                Forms\Components\Textarea::make("meta_description.{$code}")
                                                    ->label("Meta Description ({$code})")
                                                    ->rows(2)
                                                    ->maxLength(160)
                                                    ->nullable(),
                                            ]),
                                            array_keys($locales),
                                            array_values($locales),
                                        )),
                                ]),
                        ]),

                    // ---------------------------------------------------------
                    // Tab: Blocks
                    // ---------------------------------------------------------
                    Tab::make('Blocks')
                        ->icon('heroicon-o-squares-2x2')
                        ->schema([
                            Forms\Components\Repeater::make('blocks')
                                ->label('Page Blocks')
                                ->columnSpanFull()
                                ->addActionLabel('Add Block')
                                ->collapsible()
                                ->cloneable()
                                ->dehydrated(false)
                                ->itemLabel(fn (array $state): string =>
                                    (ServicePageBlock::TYPES[$state['type'] ?? ''] ?? 'Block')
                                    . (isset($state['content']['heading_en']) && $state['content']['heading_en']
                                        ? ' — ' . str($state['content']['heading_en'])->limit(40)
                                        : '')
                                )
                                ->schema([
                                    Forms\Components\Hidden::make('block_id'),

                                    Grid::make(2)->schema([
                                        Forms\Components\Select::make('type')
                                            ->label('Block Type')
                                            ->options(ServicePageBlock::TYPES)
                                            ->required()
                                            ->live()
                                            ->default('hero'),

                                        Grid::make(2)->schema([
                                            Forms\Components\Toggle::make('is_active')
                                                ->label('Active')
                                                ->default(true)
                                                ->inline(false),

                                            Forms\Components\TextInput::make('sort_order')
                                                ->label('Order')
                                                ->numeric()
                                                ->default(0),
                                        ]),
                                    ]),

                                    // Settings (always visible)
                                    Section::make('Layout Settings')
                                        ->collapsed()
                                        ->columns(3)
                                        ->schema([
                                            Forms\Components\Select::make('settings.bg')
                                                ->label('Background')
                                                ->options([
                                                    'white' => 'White',
                                                    'gray'  => 'Light Gray',
                                                    'dark'  => 'Dark',
                                                    'brand' => 'Brand Accent',
                                                ])
                                                ->default('white'),

                                            Forms\Components\Select::make('settings.columns')
                                                ->label('Columns (grid blocks)')
                                                ->options(['2' => '2', '3' => '3', '4' => '4'])
                                                ->default('3')
                                                ->visible(fn (Get $get) => in_array($get('type'), ['features_grid', 'packages', 'comparison_table']))
                                                ->helperText('Applies to Features Grid, Packages, Comparison Table'),

                                            Forms\Components\Select::make('settings.layout')
                                                ->label('Layout (text block)')
                                                ->options(['full' => 'Full width', 'split' => '2-column split'])
                                                ->default('full')
                                                ->visible(fn (Get $get) => $get('type') === 'text_section')
                                                ->helperText('Applies to Text Section only'),
                                        ]),

                                    // --------------- HERO ---------------
                                    Section::make('Hero Content')
                                        ->visible(fn (Get $get) => $get('type') === 'hero')
                                        ->headerActions([self::blockTranslateAction()])
                                        ->schema([
                                            Tabs::make('hero_translations')->tabs(array_map(
                                                fn ($code, $label) => Tab::make($label)->schema([
                                                    Forms\Components\TextInput::make("content.badge_{$code}")
                                                        ->label('Badge / Eyebrow')
                                                        ->maxLength(60)
                                                        ->nullable(),
                                                    Forms\Components\TextInput::make("content.heading_{$code}")
                                                        ->label('Heading')
                                                        ->maxLength(160)
                                                        ->nullable(),
                                                    Forms\Components\Textarea::make("content.subheading_{$code}")
                                                        ->label('Subheading')
                                                        ->rows(2)
                                                        ->nullable(),
                                                    Forms\Components\TextInput::make("content.cta_label_{$code}")
                                                        ->label('CTA Button Label')
                                                        ->maxLength(80)
                                                        ->nullable(),
                                                ]),
                                                array_keys($locales), array_values($locales),
                                            )),
                                            Forms\Components\TextInput::make('content.cta_url')
                                                ->label('CTA URL')
                                                ->maxLength(255)
                                                ->nullable()
                                                ->helperText('e.g. /contact or #packages'),
                                            Forms\Components\FileUpload::make('content.image')
                                                ->label('Background / Hero Image')
                                                ->image()
                                                ->disk('public')
                                                ->directory('service-pages')
                                                ->nullable(),
                                        ]),

                                    // --------------- FEATURES GRID ---------------
                                    Section::make('Features Grid')
                                        ->visible(fn (Get $get) => $get('type') === 'features_grid')
                                        ->headerActions([self::blockTranslateAction()])
                                        ->schema([
                                            Tabs::make('fg_heading')->tabs(array_map(
                                                fn ($code, $label) => Tab::make($label)->schema([
                                                    Forms\Components\TextInput::make("content.section_label_{$code}")
                                                        ->label('Section Label (eyebrow)')
                                                        ->maxLength(60)
                                                        ->nullable(),
                                                    Forms\Components\TextInput::make("content.heading_{$code}")
                                                        ->label('Section Heading')
                                                        ->maxLength(160)
                                                        ->nullable(),
                                                    Forms\Components\Textarea::make("content.subheading_{$code}")
                                                        ->label('Subheading')
                                                        ->rows(2)
                                                        ->nullable(),
                                                ]),
                                                array_keys($locales), array_values($locales),
                                            )),
                                            Forms\Components\Repeater::make('content.items')
                                                ->label('Feature Cards')
                                                ->addActionLabel('Add Card')
                                                ->defaultItems(0)
                                                ->schema([
                                                    Forms\Components\TextInput::make('icon')
                                                        ->label('Icon (heroicon name)')
                                                        ->maxLength(60)
                                                        ->placeholder('eye')
                                                        ->helperText('Podaj nazwę heroicona, np: eye, home, check-circle, globe-alt, code-bracket')
                                                        ->nullable(),
                                                    ...array_map(fn ($code) => Forms\Components\TextInput::make("title_{$code}")
                                                        ->label("Title ({$code})")
                                                        ->maxLength(120)
                                                        ->nullable(), array_keys($locales)),
                                                    ...array_map(fn ($code) => Forms\Components\Textarea::make("desc_{$code}")
                                                        ->label("Description ({$code})")
                                                        ->rows(2)
                                                        ->nullable(), array_keys($locales)),
                                                ])
                                                ->columns(2),
                                        ]),

                                    // --------------- PACKAGES ---------------
                                    Section::make('Packages / Pricing Cards')
                                        ->visible(fn (Get $get) => $get('type') === 'packages')
                                        ->headerActions([self::blockTranslateAction()])
                                        ->schema([
                                            Tabs::make('pkg_heading')->tabs(array_map(
                                                fn ($code, $label) => Tab::make($label)->schema([
                                                    Forms\Components\TextInput::make("content.heading_{$code}")
                                                        ->label('Section Heading')
                                                        ->maxLength(160)
                                                        ->nullable(),
                                                    Forms\Components\Textarea::make("content.subheading_{$code}")
                                                        ->label('Subheading')
                                                        ->rows(2)
                                                        ->nullable(),
                                                ]),
                                                array_keys($locales), array_values($locales),
                                            )),
                                            Forms\Components\Repeater::make('content.packages')
                                                ->label('Packages')
                                                ->addActionLabel('Add Package')
                                                ->defaultItems(0)
                                                ->schema([
                                                    Forms\Components\Toggle::make('highlight')
                                                        ->label('Highlight (popular)')
                                                        ->default(false),
                                                    Forms\Components\TextInput::make('price')
                                                        ->label('Price (display string)')
                                                        ->maxLength(40)
                                                        ->placeholder('£799')
                                                        ->nullable(),
                                                    Forms\Components\TextInput::make('cta_url')
                                                        ->label('CTA URL')
                                                        ->maxLength(255)
                                                        ->nullable(),
                                                    ...array_map(fn ($code) => Forms\Components\TextInput::make("badge_{$code}")
                                                        ->label("Badge ({$code})")
                                                        ->maxLength(40)
                                                        ->nullable(), array_keys($locales)),
                                                    ...array_map(fn ($code) => Forms\Components\TextInput::make("name_{$code}")
                                                        ->label("Package Name ({$code})")
                                                        ->maxLength(80)
                                                        ->nullable(), array_keys($locales)),
                                                    ...array_map(fn ($code) => Forms\Components\Textarea::make("desc_{$code}")
                                                        ->label("Description ({$code})")
                                                        ->rows(2)
                                                        ->nullable(), array_keys($locales)),
                                                    ...array_map(fn ($code) => Forms\Components\Textarea::make("features_{$code}")
                                                        ->label("Features ({$code}) — one per line")
                                                        ->rows(4)
                                                        ->nullable()
                                                        ->helperText('One feature per line'), array_keys($locales)),
                                                    ...array_map(fn ($code) => Forms\Components\TextInput::make("cta_label_{$code}")
                                                        ->label("CTA Label ({$code})")
                                                        ->maxLength(80)
                                                        ->nullable(), array_keys($locales)),
                                                ])
                                                ->columns(2),
                                        ]),

                                    // --------------- PRICING TABLE ---------------
                                    Section::make('Pricing Table')
                                        ->visible(fn (Get $get) => $get('type') === 'pricing_table')
                                        ->headerActions([self::blockTranslateAction()])
                                        ->schema([
                                            Tabs::make('pt_heading')->tabs(array_map(
                                                fn ($code, $label) => Tab::make($label)->schema([
                                                    Forms\Components\TextInput::make("content.heading_{$code}")
                                                        ->label('Section Heading')
                                                        ->maxLength(160)
                                                        ->nullable(),
                                                ]),
                                                array_keys($locales), array_values($locales),
                                            )),
                                            Forms\Components\Repeater::make('content.rows')
                                                ->label('Price Rows')
                                                ->addActionLabel('Add Row')
                                                ->defaultItems(0)
                                                ->schema([
                                                    ...array_map(fn ($code) => Forms\Components\TextInput::make("label_{$code}")
                                                        ->label("Label ({$code})")
                                                        ->maxLength(120)
                                                        ->nullable(), array_keys($locales)),
                                                    Forms\Components\TextInput::make('price')
                                                        ->label('Price (display string)')
                                                        ->maxLength(40)
                                                        ->nullable(),
                                                    ...array_map(fn ($code) => Forms\Components\Textarea::make("note_{$code}")
                                                        ->label("Note ({$code})")
                                                        ->rows(1)
                                                        ->nullable(), array_keys($locales)),
                                                ])
                                                ->columns(2),
                                        ]),

                                    // --------------- FAQ ---------------
                                    Section::make('FAQ')
                                        ->visible(fn (Get $get) => $get('type') === 'faq')
                                        ->headerActions([self::blockTranslateAction()])
                                        ->schema([
                                            Tabs::make('faq_heading')->tabs(array_map(
                                                fn ($code, $label) => Tab::make($label)->schema([
                                                    Forms\Components\TextInput::make("content.heading_{$code}")
                                                        ->label('Section Heading')
                                                        ->maxLength(160)
                                                        ->nullable(),
                                                ]),
                                                array_keys($locales), array_values($locales),
                                            )),
                                            Forms\Components\Repeater::make('content.items')
                                                ->label('FAQ Items')
                                                ->addActionLabel('Add Question')
                                                ->defaultItems(0)
                                                ->schema([
                                                    ...array_map(fn ($code) => Forms\Components\TextInput::make("q_{$code}")
                                                        ->label("Question ({$code})")
                                                        ->maxLength(255)
                                                        ->nullable(), array_keys($locales)),
                                                    ...array_map(fn ($code) => Forms\Components\Textarea::make("a_{$code}")
                                                        ->label("Answer ({$code})")
                                                        ->rows(3)
                                                        ->nullable(), array_keys($locales)),
                                                ])
                                                ->columns(2),
                                        ]),

                                    // --------------- CTA BANNER ---------------
                                    Section::make('CTA Banner')
                                        ->visible(fn (Get $get) => $get('type') === 'cta_banner')
                                        ->headerActions([self::blockTranslateAction()])
                                        ->schema([
                                            Tabs::make('cta_translations')->tabs(array_map(
                                                fn ($code, $label) => Tab::make($label)->schema([
                                                    Forms\Components\TextInput::make("content.heading_{$code}")
                                                        ->label('Heading')
                                                        ->maxLength(160)
                                                        ->nullable(),
                                                    Forms\Components\Textarea::make("content.subheading_{$code}")
                                                        ->label('Subheading')
                                                        ->rows(2)
                                                        ->nullable(),
                                                    Forms\Components\TextInput::make("content.cta_label_{$code}")
                                                        ->label('Button Label')
                                                        ->maxLength(80)
                                                        ->nullable(),
                                                ]),
                                                array_keys($locales), array_values($locales),
                                            )),
                                            Forms\Components\TextInput::make('content.cta_url')
                                                ->label('CTA URL')
                                                ->maxLength(255)
                                                ->nullable(),
                                        ]),

                                    // --------------- TEXT SECTION ---------------
                                    Section::make('Text Section')
                                        ->visible(fn (Get $get) => $get('type') === 'text_section')
                                        ->headerActions([self::blockTranslateAction()])
                                        ->schema([
                                            Tabs::make('text_translations')->tabs(array_map(
                                                fn ($code, $label) => Tab::make($label)->schema([
                                                    Forms\Components\TextInput::make("content.heading_{$code}")
                                                        ->label('Heading')
                                                        ->maxLength(160)
                                                        ->nullable(),
                                                    Forms\Components\RichEditor::make("content.body_{$code}")
                                                        ->label('Body (Rich Text)')
                                                        ->nullable()
                                                        ->toolbarButtons([
                                                            'bold', 'italic', 'underline',
                                                            'h2', 'h3',
                                                            'bulletList', 'orderedList',
                                                            'link', 'blockquote',
                                                        ]),
                                                ]),
                                                array_keys($locales), array_values($locales),
                                            )),
                                        ]),

                                    // --------------- COMPARISON TABLE ---------------
                                    Section::make('Comparison Table')
                                        ->visible(fn (Get $get) => $get('type') === 'comparison_table')
                                        ->headerActions([self::blockTranslateAction()])
                                        ->schema([
                                            Tabs::make('ct_heading')->tabs(array_map(
                                                fn ($code, $label) => Tab::make($label)->schema([
                                                    Forms\Components\TextInput::make("content.heading_{$code}")
                                                        ->label('Section Heading')
                                                        ->maxLength(160)
                                                        ->nullable(),
                                                ]),
                                                array_keys($locales), array_values($locales),
                                            )),
                                            Forms\Components\Repeater::make('content.columns')
                                                ->label('Columns (e.g. plans or competitors)')
                                                ->addActionLabel('Add Column')
                                                ->defaultItems(0)
                                                ->schema([
                                                    Forms\Components\Toggle::make('highlight')
                                                        ->label('Highlight')
                                                        ->default(false),
                                                    Forms\Components\TextInput::make('price')
                                                        ->label('Price')
                                                        ->maxLength(40)
                                                        ->nullable(),
                                                    ...array_map(fn ($code) => Forms\Components\TextInput::make("label_{$code}")
                                                        ->label("Label ({$code})")
                                                        ->maxLength(80)
                                                        ->nullable(), array_keys($locales)),
                                                ])
                                                ->columns(2),
                                            Forms\Components\Repeater::make('content.rows')
                                                ->label('Feature Rows')
                                                ->addActionLabel('Add Row')
                                                ->defaultItems(0)
                                                ->schema([
                                                    ...array_map(fn ($code) => Forms\Components\TextInput::make("label_{$code}")
                                                        ->label("Row Label ({$code})")
                                                        ->maxLength(120)
                                                        ->nullable(), array_keys($locales)),
                                                    Forms\Components\Textarea::make('values')
                                                        ->label('Values (JSON array, e.g. [true, false, "Custom"])')
                                                        ->rows(2)
                                                        ->nullable()
                                                        ->helperText('JSON array matching column count. true = ✓, false = ✗, string = custom text'),
                                                ])
                                                ->columns(2),
                                        ]),

                                ]),
                        ]),
                ]),
        ]);
    }

    // -------------------------------------------------------------------------
    // AI Translate Action — reused by all block sections
    // -------------------------------------------------------------------------

    private static function blockTranslateAction(): Action
    {
        return Action::make('translateBlockWithAI')
            ->label('Translate with AI')
            ->icon('heroicon-o-language')
            ->color('info')
            ->size('sm')
            ->requiresConfirmation()
            ->modalHeading('Translate block from Polish with AI')
            ->modalDescription('AI wygeneruje tłumaczenia EN i PT dla wszystkich pól tego bloku na podstawie wartości po polsku (_pl). Istniejące EN/PT zostaną nadpisane.')
            ->modalSubmitActionLabel('Generate')
            ->action(function (Get $get, Set $set): void {
                $type    = (string) ($get('type') ?? '');
                $content = (array)  ($get('content') ?? []);

                // Check we have at least one Polish field
                $hasPolish = false;
                array_walk_recursive($content, function ($v, $k) use (&$hasPolish) {
                    if (str_ends_with((string) $k, '_pl') && is_string($v) && $v !== '') {
                        $hasPolish = true;
                    }
                });

                if (! $hasPolish) {
                    Notification::make()
                        ->title('Brak treści PL')
                        ->body('Uzupełnij pola po polsku (_pl) w tym bloku przed tłumaczeniem.')
                        ->warning()
                        ->send();
                    return;
                }

                try {
                    $updated = app(ServicePageTranslationService::class)->translateBlock($type, $content);
                } catch (LandingPageGenerationException $e) {
                    Notification::make()->title('Translation failed')->body($e->getMessage())->danger()->send();
                    return;
                }

                $set('content', $updated);

                Notification::make()
                    ->title('Block translated')
                    ->body('EN and PT fields updated. Switch tabs to review, then save the page.')
                    ->success()
                    ->send();
            })
            ->visible(fn (): bool => filled(config('services.openai.api_key')));
    }

    // -------------------------------------------------------------------------
    // Table
    // -------------------------------------------------------------------------

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('slug')
                    ->label('URL Slug')
                    ->searchable()
                    ->sortable()
                    ->weight('bold')
                    ->prefix('/'),

                Tables\Columns\TextColumn::make('title')
                    ->label('Title (EN)')
                    ->getStateUsing(fn ($record) => $record->getTranslation('title', 'en', false) ?: '—')
                    ->searchable(query: fn ($query, $search) =>
                        $query->whereRaw("JSON_EXTRACT(title, '$.en') LIKE ?", ["%{$search}%"])
                    ),

                Tables\Columns\IconColumn::make('is_published')
                    ->label('Published')
                    ->boolean(),

                Tables\Columns\IconColumn::make('show_in_nav')
                    ->label('In Nav')
                    ->boolean(),

                Tables\Columns\TextColumn::make('blocks_count')
                    ->label('Blocks')
                    ->counts('blocks')
                    ->badge()
                    ->color('gray'),

                Tables\Columns\TextColumn::make('sort_order')
                    ->label('Order')
                    ->sortable(),

                Tables\Columns\TextColumn::make('updated_at')
                    ->label('Updated')
                    ->date()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\TernaryFilter::make('is_published')->label('Published'),
                Tables\Filters\TernaryFilter::make('show_in_nav')->label('In Navigation'),
            ])
            ->actions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('sort_order');
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListServicePages::route('/'),
            'create' => Pages\CreateServicePage::route('/create'),
            'edit'   => Pages\EditServicePage::route('/{record}/edit'),
        ];
    }
}
