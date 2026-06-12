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
                                                    self::fieldRow(
                                                        Forms\Components\TextInput::make("content.badge_{$code}")
                                                            ->label('Badge / Eyebrow')
                                                            ->maxLength(60)
                                                            ->nullable(),
                                                        'badge'
                                                    ),
                                                    self::fieldRow(
                                                        Forms\Components\TextInput::make("content.heading_{$code}")
                                                            ->label('Heading')
                                                            ->maxLength(160)
                                                            ->nullable(),
                                                        'heading'
                                                    ),
                                                    self::fieldRow(
                                                        Forms\Components\Textarea::make("content.subheading_{$code}")
                                                            ->label('Subheading')
                                                            ->rows(2)
                                                            ->nullable(),
                                                        'subheading'
                                                    ),
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
                                                    self::fieldRow(
                                                        Forms\Components\TextInput::make("content.section_label_{$code}")
                                                            ->label('Section Label (eyebrow)')
                                                            ->maxLength(60)
                                                            ->nullable(),
                                                        'section_label'
                                                    ),
                                                    self::fieldRow(
                                                        Forms\Components\TextInput::make("content.heading_{$code}")
                                                            ->label('Section Heading')
                                                            ->maxLength(160)
                                                            ->nullable(),
                                                        'heading'
                                                    ),
                                                    self::fieldRow(
                                                        Forms\Components\Textarea::make("content.subheading_{$code}")
                                                            ->label('Subheading')
                                                            ->rows(2)
                                                            ->nullable(),
                                                        'subheading'
                                                    ),
                                                ]),
                                                array_keys($locales), array_values($locales),
                                            )),
                                            Section::make('Feature Cards')
                                                ->headerActions([
                                                    Action::make('collapseAllFeatureCards')
                                                        ->label('')
                                                        ->icon('heroicon-o-chevron-double-up')
                                                        ->tooltip('Zwiń wszystkie')
                                                        ->color('gray')
                                                        ->size('sm')
                                                        ->alpineClickHandler(
                                                            '$dispatch(\'repeater-collapse\', ($el.closest(\'.fi-sc-section\')?.querySelector(\'.fi-fo-repeater[data-path]\')?.getAttribute(\'data-path\') ?? \'\'))'
                                                        ),
                                                    Action::make('expandAllFeatureCards')
                                                        ->label('')
                                                        ->icon('heroicon-o-chevron-double-down')
                                                        ->tooltip('Rozwiń wszystkie')
                                                        ->color('gray')
                                                        ->size('sm')
                                                        ->alpineClickHandler(
                                                            '$dispatch(\'repeater-expand\', ($el.closest(\'.fi-sc-section\')?.querySelector(\'.fi-fo-repeater[data-path]\')?.getAttribute(\'data-path\') ?? \'\'))'
                                                        ),
                                                    Action::make('translateFeatureCardsWithAI')
                                                        ->label('Translate with AI')
                                                        ->icon('heroicon-o-language')
                                                        ->color('info')
                                                        ->size('sm')
                                                        ->requiresConfirmation()
                                                        ->modalHeading('Translate Feature Cards with AI')
                                                        ->modalDescription('AI przetłumaczy pola title i desc wszystkich kart z języka polskiego na EN i PT. Istniejące EN/PT zostaną nadpisane.')
                                                        ->modalSubmitActionLabel('Generate')
                                                        ->action(function (Get $get, Set $set): void {
                                                            $items = (array) ($get('content.items') ?? []);
                                                            $hasPolish = false;
                                                            foreach ($items as $item) {
                                                                if (! empty($item['title_pl']) || ! empty($item['desc_pl'])) {
                                                                    $hasPolish = true;
                                                                    break;
                                                                }
                                                            }
                                                            if (! $hasPolish) {
                                                                Notification::make()
                                                                    ->title('Brak treści PL')
                                                                    ->body('Uzupełnij pola title lub desc po polsku w kartach przed tłumaczeniem.')
                                                                    ->warning()
                                                                    ->send();
                                                                return;
                                                            }
                                                            try {
                                                                $updated = app(ServicePageTranslationService::class)
                                                                    ->translateBlock('features_grid', ['items' => $items]);
                                                            } catch (LandingPageGenerationException $e) {
                                                                Notification::make()->title('Translation failed')->body($e->getMessage())->danger()->send();
                                                                return;
                                                            }
                                                            $set('content.items', $updated['items'] ?? $items);
                                                            Notification::make()
                                                                ->title('Cards translated')
                                                                ->body('EN i PT zaktualizowane. Przełącz zakładki, by sprawdzić, a następnie zapisz stronę.')
                                                                ->success()
                                                                ->send();
                                                        })
                                                        ->visible(fn (): bool => filled(config('services.openai.api_key'))),
                                                ])
                                                ->schema([
                                                    Forms\Components\Repeater::make('content.items')
                                                        ->hiddenLabel()
                                                        ->addActionLabel('Add Card')
                                                        ->defaultItems(0)
                                                        ->collapsible()
                                                        ->itemLabel(fn (array $state): string =>
                                                            $state['title_en'] ?? $state['title_pl'] ?? $state['title_pt'] ?? 'Card'
                                                        )
                                                        ->extraAttributes(fn ($component) => ['data-path' => $component->getStatePath()])
                                                        ->schema([
                                                            Forms\Components\TextInput::make('icon')
                                                                ->label('Icon (heroicon name)')
                                                                ->maxLength(60)
                                                                ->placeholder('eye')
                                                                ->helperText('Podaj nazwę heroicona, np: eye, home, check-circle, globe-alt, code-bracket')
                                                                ->nullable(),
                                                            Tabs::make('card_translations')->tabs(array_map(
                                                                fn ($code, $label) => Tab::make($label)->schema([
                                                                    Forms\Components\TextInput::make("title_{$code}")
                                                                        ->label('Title')
                                                                        ->maxLength(120)
                                                                        ->nullable(),
                                                                    Forms\Components\Textarea::make("desc_{$code}")
                                                                        ->label('Description')
                                                                        ->rows(2)
                                                                        ->nullable(),
                                                                ]),
                                                                array_keys($locales), array_values($locales),
                                                            )),
                                                        ])
                                                        ->columns(1),
                                                ]),
                                        ]),

                                    // --------------- PACKAGES ---------------
                                    Section::make('Packages / Pricing Cards')
                                        ->visible(fn (Get $get) => $get('type') === 'packages')
                                        ->headerActions([self::blockTranslateAction()])
                                        ->schema([
                                            Tabs::make('pkg_heading')->tabs(array_map(
                                                fn ($code, $label) => Tab::make($label)->schema([
                                                    self::fieldRow(
                                                        Forms\Components\TextInput::make("content.badge_{$code}")
                                                            ->label('Badge / Eyebrow')
                                                            ->maxLength(60)
                                                            ->nullable(),
                                                        'badge'
                                                    ),
                                                    self::fieldRow(
                                                        Forms\Components\TextInput::make("content.heading_{$code}")
                                                            ->label('Section Heading')
                                                            ->maxLength(160)
                                                            ->nullable(),
                                                        'heading'
                                                    ),
                                                    self::fieldRow(
                                                        Forms\Components\Textarea::make("content.subheading_{$code}")
                                                            ->label('Subheading')
                                                            ->rows(2)
                                                            ->nullable(),
                                                        'subheading'
                                                    ),
                                                ]),
                                                array_keys($locales), array_values($locales),
                                            )),
                                            Section::make('Packages')
                                                ->headerActions([
                                                    Action::make('collapseAllPackages')
                                                        ->label('')
                                                        ->icon('heroicon-o-chevron-double-up')
                                                        ->tooltip('Zwiń wszystkie')
                                                        ->color('gray')
                                                        ->size('sm')
                                                        ->alpineClickHandler(
                                                            '$dispatch(\'repeater-collapse\', ($el.closest(\'.fi-sc-section\')?.querySelector(\'.fi-fo-repeater[data-path]\')?.getAttribute(\'data-path\') ?? \'\'))'
                                                        ),
                                                    Action::make('expandAllPackages')
                                                        ->label('')
                                                        ->icon('heroicon-o-chevron-double-down')
                                                        ->tooltip('Rozwiń wszystkie')
                                                        ->color('gray')
                                                        ->size('sm')
                                                        ->alpineClickHandler(
                                                            '$dispatch(\'repeater-expand\', ($el.closest(\'.fi-sc-section\')?.querySelector(\'.fi-fo-repeater[data-path]\')?.getAttribute(\'data-path\') ?? \'\'))'
                                                        ),
                                                    Action::make('translatePackagesWithAI')
                                                        ->label('Translate with AI')
                                                        ->icon('heroicon-o-language')
                                                        ->color('info')
                                                        ->size('sm')
                                                        ->requiresConfirmation()
                                                        ->modalHeading('Translate Packages with AI')
                                                        ->modalDescription('AI przetłumaczy pola badge, name, desc, features i cta_label z języka polskiego na EN i PT. Istniejące EN/PT zostaną nadpisane.')
                                                        ->modalSubmitActionLabel('Generate')
                                                        ->action(function (Get $get, Set $set): void {
                                                            $packages = (array) ($get('content.packages') ?? []);
                                                            $hasPolish = false;
                                                            foreach ($packages as $pkg) {
                                                                foreach (['badge_pl', 'name_pl', 'desc_pl', 'features_pl', 'cta_label_pl'] as $f) {
                                                                    if (! empty($pkg[$f])) { $hasPolish = true; break 2; }
                                                                }
                                                            }
                                                            if (! $hasPolish) {
                                                                Notification::make()
                                                                    ->title('Brak treści PL')
                                                                    ->body('Uzupełnij pola po polsku w pakietach przed tłumaczeniem.')
                                                                    ->warning()->send();
                                                                return;
                                                            }
                                                            try {
                                                                $updated = app(ServicePageTranslationService::class)
                                                                    ->translateBlock('packages', ['packages' => $packages]);
                                                            } catch (LandingPageGenerationException $e) {
                                                                Notification::make()->title('Translation failed')->body($e->getMessage())->danger()->send();
                                                                return;
                                                            }
                                                            $set('content.packages', $updated['packages'] ?? $packages);
                                                            Notification::make()
                                                                ->title('Packages translated')
                                                                ->body('EN i PT zaktualizowane. Zapisz stronę.')
                                                                ->success()->send();
                                                        })
                                                        ->visible(fn (): bool => filled(config('services.openai.api_key'))),
                                                ])
                                                ->schema([
                                                    Forms\Components\Repeater::make('content.packages')
                                                        ->hiddenLabel()
                                                        ->addActionLabel('Add Package')
                                                        ->defaultItems(0)
                                                        ->collapsible()
                                                        ->itemLabel(fn (array $state): string =>
                                                            $state['name_en'] ?? $state['name_pl'] ?? $state['name_pt'] ?? 'Package'
                                                        )
                                                        ->extraAttributes(fn ($component) => ['data-path' => $component->getStatePath()])
                                                        ->schema([
                                                            Grid::make(3)->schema([
                                                                Forms\Components\Toggle::make('highlight')
                                                                    ->label('Highlight (popular)')
                                                                    ->default(false),
                                                                Forms\Components\TextInput::make('cta_url')
                                                                    ->label('CTA URL')
                                                                    ->maxLength(255)
                                                                    ->nullable(),
                                                            ]),
                                                            Grid::make(6)->columnSpanFull()->schema([
                                                                Forms\Components\TextInput::make('price')
                                                                    ->label('Price (display string)')
                                                                    ->maxLength(40)
                                                                    ->placeholder('£799')
                                                                    ->nullable()
                                                                    ->columnSpan(2),
                                                                Forms\Components\Select::make('settings.price_size')
                                                                    ->label('Size')
                                                                    ->options(['xs' => 'XS', 'sm' => 'SM', 'md' => 'MD', 'lg' => 'LG', 'xl' => 'XL', '2xl' => '2XL', '3xl' => '3XL', '4xl' => '4XL', '5xl' => '5XL', '6xl' => '6XL', '7xl' => '7XL'])
                                                                    ->default('md')
                                                                    ->columnSpan(1),
                                                                Forms\Components\Select::make('settings.price_color')
                                                                    ->label('Color')
                                                                    ->options(['neutral' => 'Default', 'brand' => 'Brand', 'white' => 'White', 'muted' => 'Muted'])
                                                                    ->default('brand')
                                                                    ->columnSpan(1),
                                                                Forms\Components\Select::make('settings.price_align')
                                                                    ->label('Align')
                                                                    ->options(['left' => 'Left', 'center' => 'Center', 'right' => 'Right'])
                                                                    ->default('left')
                                                                    ->columnSpan(1),
                                                                Forms\Components\Select::make('settings.price_font')
                                                                    ->label('Font')
                                                                    ->options(['sans' => 'Sans (Inter)', 'display' => 'Display (Syne)'])
                                                                    ->default('sans')
                                                                    ->columnSpan(1),
                                                            ]),
                                                            Tabs::make('pkg_item_translations')->tabs(array_map(
                                                                fn ($code, $label) => Tab::make($label)->schema([
                                                                    self::fieldRow(
                                                                        Forms\Components\TextInput::make("badge_{$code}")
                                                                            ->label('Badge')
                                                                            ->maxLength(40)
                                                                            ->nullable(),
                                                                        'badge'
                                                                    ),
                                                                    self::fieldRow(
                                                                        Forms\Components\TextInput::make("name_{$code}")
                                                                            ->label('Package Name')
                                                                            ->maxLength(80)
                                                                            ->nullable(),
                                                                        'name'
                                                                    ),
                                                                    self::fieldRow(
                                                                        Forms\Components\Textarea::make("desc_{$code}")
                                                                            ->label('Description')
                                                                            ->rows(2)
                                                                            ->nullable(),
                                                                        'desc'
                                                                    ),
                                                                    Forms\Components\Textarea::make("features_{$code}")
                                                                        ->label('Features — one per line')
                                                                        ->rows(4)
                                                                        ->nullable()
                                                                        ->helperText('One feature per line'),
                                                                    self::fieldRow(
                                                                        Forms\Components\TextInput::make("cta_label_{$code}")
                                                                            ->label('CTA Label')
                                                                            ->maxLength(80)
                                                                            ->nullable(),
                                                                        'cta_label'
                                                                    ),
                                                                ]),
                                                                array_keys($locales), array_values($locales),
                                                            )),
                                                        ])
                                                        ->columns(1),
                                                ]),
                                        ]),

                                    // --------------- PRICING TABLE ---------------
                                    Section::make('Pricing Table')
                                        ->visible(fn (Get $get) => $get('type') === 'pricing_table')
                                        ->headerActions([self::blockTranslateAction()])
                                        ->schema([
                                            Tabs::make('pt_heading')->tabs(array_map(
                                                fn ($code, $label) => Tab::make($label)->schema([
                                                    self::fieldRow(
                                                        Forms\Components\TextInput::make("content.heading_{$code}")
                                                            ->label('Section Heading')
                                                            ->maxLength(160)
                                                            ->nullable(),
                                                        'heading'
                                                    ),
                                                ]),
                                                array_keys($locales), array_values($locales),
                                            )),
                                            Section::make('Price Rows')
                                                ->headerActions([
                                                    Action::make('collapseAllPriceRows')
                                                        ->label('')
                                                        ->icon('heroicon-o-chevron-double-up')
                                                        ->tooltip('Zwiń wszystkie')
                                                        ->color('gray')
                                                        ->size('sm')
                                                        ->alpineClickHandler(
                                                            '$dispatch(\'repeater-collapse\', ($el.closest(\'.fi-sc-section\')?.querySelector(\'.fi-fo-repeater[data-path]\')?.getAttribute(\'data-path\') ?? \'\'))'
                                                        ),
                                                    Action::make('expandAllPriceRows')
                                                        ->label('')
                                                        ->icon('heroicon-o-chevron-double-down')
                                                        ->tooltip('Rozwiń wszystkie')
                                                        ->color('gray')
                                                        ->size('sm')
                                                        ->alpineClickHandler(
                                                            '$dispatch(\'repeater-expand\', ($el.closest(\'.fi-sc-section\')?.querySelector(\'.fi-fo-repeater[data-path]\')?.getAttribute(\'data-path\') ?? \'\'))'
                                                        ),
                                                    Action::make('translatePriceRowsWithAI')
                                                        ->label('Translate with AI')
                                                        ->icon('heroicon-o-language')
                                                        ->color('info')
                                                        ->size('sm')
                                                        ->requiresConfirmation()
                                                        ->modalHeading('Translate Price Rows with AI')
                                                        ->modalDescription('AI przetłumaczy pola label i note z języka polskiego na EN i PT.')
                                                        ->modalSubmitActionLabel('Generate')
                                                        ->action(function (Get $get, Set $set): void {
                                                            $rows = (array) ($get('content.rows') ?? []);
                                                            $hasPolish = false;
                                                            foreach ($rows as $row) {
                                                                if (! empty($row['label_pl']) || ! empty($row['note_pl'])) { $hasPolish = true; break; }
                                                            }
                                                            if (! $hasPolish) {
                                                                Notification::make()->title('Brak treści PL')->body('Uzupełnij pola po polsku przed tłumaczeniem.')->warning()->send();
                                                                return;
                                                            }
                                                            try {
                                                                $updated = app(ServicePageTranslationService::class)
                                                                    ->translateBlock('pricing_table', ['rows' => $rows]);
                                                            } catch (LandingPageGenerationException $e) {
                                                                Notification::make()->title('Translation failed')->body($e->getMessage())->danger()->send();
                                                                return;
                                                            }
                                                            $set('content.rows', $updated['rows'] ?? $rows);
                                                            Notification::make()->title('Rows translated')->body('EN i PT zaktualizowane. Zapisz stronę.')->success()->send();
                                                        })
                                                        ->visible(fn (): bool => filled(config('services.openai.api_key'))),
                                                ])
                                                ->schema([
                                                    Forms\Components\Repeater::make('content.rows')
                                                        ->hiddenLabel()
                                                        ->addActionLabel('Add Row')
                                                        ->defaultItems(0)
                                                        ->collapsible()
                                                        ->itemLabel(fn (array $state): string =>
                                                            $state['label_en'] ?? $state['label_pl'] ?? $state['label_pt'] ?? 'Row'
                                                        )
                                                        ->extraAttributes(fn ($component) => ['data-path' => $component->getStatePath()])
                                                        ->schema([
                                                            Forms\Components\TextInput::make('price')
                                                                ->label('Price (display string)')
                                                                ->maxLength(40)
                                                                ->nullable(),
                                                            Tabs::make('price_row_translations')->tabs(array_map(
                                                                fn ($code, $label) => Tab::make($label)->schema([
                                                                    Forms\Components\TextInput::make("label_{$code}")
                                                                        ->label('Label')
                                                                        ->maxLength(120)
                                                                        ->nullable(),
                                                                    Forms\Components\Textarea::make("note_{$code}")
                                                                        ->label('Note')
                                                                        ->rows(1)
                                                                        ->nullable(),
                                                                ]),
                                                                array_keys($locales), array_values($locales),
                                                            )),
                                                        ])
                                                        ->columns(1),
                                                ]),
                                        ]),

                                    // --------------- FAQ ---------------
                                    Section::make('FAQ')
                                        ->visible(fn (Get $get) => $get('type') === 'faq')
                                        ->headerActions([self::blockTranslateAction()])
                                        ->schema([
                                            Tabs::make('faq_heading')->tabs(array_map(
                                                fn ($code, $label) => Tab::make($label)->schema([
                                                    self::fieldRow(
                                                        Forms\Components\TextInput::make("content.heading_{$code}")
                                                            ->label('Section Heading')
                                                            ->maxLength(160)
                                                            ->nullable(),
                                                        'heading'
                                                    ),
                                                ]),
                                                array_keys($locales), array_values($locales),
                                            )),
                                            Section::make('FAQ Items')
                                                ->headerActions([
                                                    Action::make('collapseAllFaqItems')
                                                        ->label('')
                                                        ->icon('heroicon-o-chevron-double-up')
                                                        ->tooltip('Zwiń wszystkie')
                                                        ->color('gray')
                                                        ->size('sm')
                                                        ->alpineClickHandler(
                                                            '$dispatch(\'repeater-collapse\', ($el.closest(\'.fi-sc-section\')?.querySelector(\'.fi-fo-repeater[data-path]\')?.getAttribute(\'data-path\') ?? \'\'))'
                                                        ),
                                                    Action::make('expandAllFaqItems')
                                                        ->label('')
                                                        ->icon('heroicon-o-chevron-double-down')
                                                        ->tooltip('Rozwiń wszystkie')
                                                        ->color('gray')
                                                        ->size('sm')
                                                        ->alpineClickHandler(
                                                            '$dispatch(\'repeater-expand\', ($el.closest(\'.fi-sc-section\')?.querySelector(\'.fi-fo-repeater[data-path]\')?.getAttribute(\'data-path\') ?? \'\'))'
                                                        ),
                                                    Action::make('translateFaqWithAI')
                                                        ->label('Translate with AI')
                                                        ->icon('heroicon-o-language')
                                                        ->color('info')
                                                        ->size('sm')
                                                        ->requiresConfirmation()
                                                        ->modalHeading('Translate FAQ with AI')
                                                        ->modalDescription('AI przetłumaczy pytania i odpowiedzi z języka polskiego na EN i PT.')
                                                        ->modalSubmitActionLabel('Generate')
                                                        ->action(function (Get $get, Set $set): void {
                                                            $items = (array) ($get('content.items') ?? []);
                                                            $hasPolish = false;
                                                            foreach ($items as $item) {
                                                                if (! empty($item['q_pl']) || ! empty($item['a_pl'])) { $hasPolish = true; break; }
                                                            }
                                                            if (! $hasPolish) {
                                                                Notification::make()->title('Brak treści PL')->body('Uzupełnij pytania i odpowiedzi po polsku przed tłumaczeniem.')->warning()->send();
                                                                return;
                                                            }
                                                            try {
                                                                $updated = app(ServicePageTranslationService::class)
                                                                    ->translateBlock('faq', ['items' => $items]);
                                                            } catch (LandingPageGenerationException $e) {
                                                                Notification::make()->title('Translation failed')->body($e->getMessage())->danger()->send();
                                                                return;
                                                            }
                                                            $set('content.items', $updated['items'] ?? $items);
                                                            Notification::make()->title('FAQ translated')->body('EN i PT zaktualizowane. Zapisz stronę.')->success()->send();
                                                        })
                                                        ->visible(fn (): bool => filled(config('services.openai.api_key'))),
                                                ])
                                                ->schema([
                                                    Forms\Components\Repeater::make('content.items')
                                                        ->hiddenLabel()
                                                        ->addActionLabel('Add Question')
                                                        ->defaultItems(0)
                                                        ->collapsible()
                                                        ->itemLabel(fn (array $state): string =>
                                                            $state['q_en'] ?? $state['q_pl'] ?? $state['q_pt'] ?? 'Question'
                                                        )
                                                        ->extraAttributes(fn ($component) => ['data-path' => $component->getStatePath()])
                                                        ->schema([
                                                            Tabs::make('faq_item_translations')->tabs(array_map(
                                                                fn ($code, $label) => Tab::make($label)->schema([
                                                                    Forms\Components\TextInput::make("q_{$code}")
                                                                        ->label('Question')
                                                                        ->maxLength(255)
                                                                        ->nullable(),
                                                                    Forms\Components\Textarea::make("a_{$code}")
                                                                        ->label('Answer')
                                                                        ->rows(3)
                                                                        ->nullable(),
                                                                ]),
                                                                array_keys($locales), array_values($locales),
                                                            )),
                                                        ])
                                                        ->columns(1),
                                                ]),
                                        ]),

                                    // --------------- CTA BANNER ---------------
                                    Section::make('CTA Banner')
                                        ->visible(fn (Get $get) => $get('type') === 'cta_banner')
                                        ->headerActions([self::blockTranslateAction()])
                                        ->schema([
                                            Tabs::make('cta_translations')->tabs(array_map(
                                                fn ($code, $label) => Tab::make($label)->schema([
                                                    self::fieldRow(
                                                        Forms\Components\TextInput::make("content.heading_{$code}")
                                                            ->label('Heading')
                                                            ->maxLength(160)
                                                            ->nullable(),
                                                        'heading'
                                                    ),
                                                    self::fieldRow(
                                                        Forms\Components\Textarea::make("content.subheading_{$code}")
                                                            ->label('Subheading')
                                                            ->rows(2)
                                                            ->nullable(),
                                                        'subheading'
                                                    ),
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
                                                    self::fieldRow(
                                                        Forms\Components\TextInput::make("content.heading_{$code}")
                                                            ->label('Heading')
                                                            ->maxLength(160)
                                                            ->nullable(),
                                                        'heading'
                                                    ),
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
                                                    self::fieldRow(
                                                        Forms\Components\TextInput::make("content.heading_{$code}")
                                                            ->label('Section Heading')
                                                            ->maxLength(160)
                                                            ->nullable(),
                                                        'heading'
                                                    ),
                                                ]),
                                                array_keys($locales), array_values($locales),
                                            )),
                                            Section::make('Columns')
                                                ->headerActions([
                                                    Action::make('collapseAllCtColumns')
                                                        ->label('')
                                                        ->icon('heroicon-o-chevron-double-up')
                                                        ->tooltip('Zwiń wszystkie')
                                                        ->color('gray')
                                                        ->size('sm')
                                                        ->alpineClickHandler(
                                                            '$dispatch(\'repeater-collapse\', ($el.closest(\'.fi-sc-section\')?.querySelector(\'.fi-fo-repeater[data-path]\')?.getAttribute(\'data-path\') ?? \'\'))'
                                                        ),
                                                    Action::make('expandAllCtColumns')
                                                        ->label('')
                                                        ->icon('heroicon-o-chevron-double-down')
                                                        ->tooltip('Rozwiń wszystkie')
                                                        ->color('gray')
                                                        ->size('sm')
                                                        ->alpineClickHandler(
                                                            '$dispatch(\'repeater-expand\', ($el.closest(\'.fi-sc-section\')?.querySelector(\'.fi-fo-repeater[data-path]\')?.getAttribute(\'data-path\') ?? \'\'))'
                                                        ),
                                                    Action::make('translateCtColumnsWithAI')
                                                        ->label('Translate with AI')
                                                        ->icon('heroicon-o-language')
                                                        ->color('info')
                                                        ->size('sm')
                                                        ->requiresConfirmation()
                                                        ->modalHeading('Translate Columns with AI')
                                                        ->modalDescription('AI przetłumaczy etykiety kolumn z języka polskiego na EN i PT.')
                                                        ->modalSubmitActionLabel('Generate')
                                                        ->action(function (Get $get, Set $set): void {
                                                            $cols = (array) ($get('content.columns') ?? []);
                                                            $hasPolish = false;
                                                            foreach ($cols as $col) {
                                                                if (! empty($col['label_pl'])) { $hasPolish = true; break; }
                                                            }
                                                            if (! $hasPolish) {
                                                                Notification::make()->title('Brak treści PL')->body('Uzupełnij etykiety po polsku przed tłumaczeniem.')->warning()->send();
                                                                return;
                                                            }
                                                            try {
                                                                $updated = app(ServicePageTranslationService::class)
                                                                    ->translateBlock('comparison_table', ['columns' => $cols]);
                                                            } catch (LandingPageGenerationException $e) {
                                                                Notification::make()->title('Translation failed')->body($e->getMessage())->danger()->send();
                                                                return;
                                                            }
                                                            $set('content.columns', $updated['columns'] ?? $cols);
                                                            Notification::make()->title('Columns translated')->body('EN i PT zaktualizowane. Zapisz stronę.')->success()->send();
                                                        })
                                                        ->visible(fn (): bool => filled(config('services.openai.api_key'))),
                                                ])
                                                ->schema([
                                                    Forms\Components\Repeater::make('content.columns')
                                                        ->hiddenLabel()
                                                        ->addActionLabel('Add Column')
                                                        ->defaultItems(0)
                                                        ->collapsible()
                                                        ->itemLabel(fn (array $state): string =>
                                                            $state['label_en'] ?? $state['label_pl'] ?? $state['label_pt'] ?? 'Column'
                                                        )
                                                        ->extraAttributes(fn ($component) => ['data-path' => $component->getStatePath()])
                                                        ->schema([
                                                            Grid::make(2)->schema([
                                                                Forms\Components\Toggle::make('highlight')
                                                                    ->label('Highlight')
                                                                    ->default(false),
                                                                Forms\Components\TextInput::make('price')
                                                                    ->label('Price')
                                                                    ->maxLength(40)
                                                                    ->nullable(),
                                                            ]),
                                                            Tabs::make('ct_col_translations')->tabs(array_map(
                                                                fn ($code, $label) => Tab::make($label)->schema([
                                                                    Forms\Components\TextInput::make("label_{$code}")
                                                                        ->label('Label')
                                                                        ->maxLength(80)
                                                                        ->nullable(),
                                                                ]),
                                                                array_keys($locales), array_values($locales),
                                                            )),
                                                        ])
                                                        ->columns(1),
                                                ]),

                                            Section::make('Feature Rows')
                                                ->headerActions([
                                                    Action::make('collapseAllCtRows')
                                                        ->label('')
                                                        ->icon('heroicon-o-chevron-double-up')
                                                        ->tooltip('Zwiń wszystkie')
                                                        ->color('gray')
                                                        ->size('sm')
                                                        ->alpineClickHandler(
                                                            '$dispatch(\'repeater-collapse\', ($el.closest(\'.fi-sc-section\')?.querySelector(\'.fi-fo-repeater[data-path]\')?.getAttribute(\'data-path\') ?? \'\'))'
                                                        ),
                                                    Action::make('expandAllCtRows')
                                                        ->label('')
                                                        ->icon('heroicon-o-chevron-double-down')
                                                        ->tooltip('Rozwiń wszystkie')
                                                        ->color('gray')
                                                        ->size('sm')
                                                        ->alpineClickHandler(
                                                            '$dispatch(\'repeater-expand\', ($el.closest(\'.fi-sc-section\')?.querySelector(\'.fi-fo-repeater[data-path]\')?.getAttribute(\'data-path\') ?? \'\'))'
                                                        ),
                                                    Action::make('translateCtRowsWithAI')
                                                        ->label('Translate with AI')
                                                        ->icon('heroicon-o-language')
                                                        ->color('info')
                                                        ->size('sm')
                                                        ->requiresConfirmation()
                                                        ->modalHeading('Translate Feature Rows with AI')
                                                        ->modalDescription('AI przetłumaczy etykiety wierszy z języka polskiego na EN i PT.')
                                                        ->modalSubmitActionLabel('Generate')
                                                        ->action(function (Get $get, Set $set): void {
                                                            $rows = (array) ($get('content.rows') ?? []);
                                                            $hasPolish = false;
                                                            foreach ($rows as $row) {
                                                                if (! empty($row['label_pl'])) { $hasPolish = true; break; }
                                                            }
                                                            if (! $hasPolish) {
                                                                Notification::make()->title('Brak treści PL')->body('Uzupełnij etykiety po polsku przed tłumaczeniem.')->warning()->send();
                                                                return;
                                                            }
                                                            try {
                                                                $updated = app(ServicePageTranslationService::class)
                                                                    ->translateBlock('comparison_table', ['rows' => $rows]);
                                                            } catch (LandingPageGenerationException $e) {
                                                                Notification::make()->title('Translation failed')->body($e->getMessage())->danger()->send();
                                                                return;
                                                            }
                                                            $set('content.rows', $updated['rows'] ?? $rows);
                                                            Notification::make()->title('Rows translated')->body('EN i PT zaktualizowane. Zapisz stronę.')->success()->send();
                                                        })
                                                        ->visible(fn (): bool => filled(config('services.openai.api_key'))),
                                                ])
                                                ->schema([
                                                    Forms\Components\Repeater::make('content.rows')
                                                        ->hiddenLabel()
                                                        ->addActionLabel('Add Row')
                                                        ->defaultItems(0)
                                                        ->collapsible()
                                                        ->itemLabel(fn (array $state): string =>
                                                            $state['label_en'] ?? $state['label_pl'] ?? $state['label_pt'] ?? 'Row'
                                                        )
                                                        ->extraAttributes(fn ($component) => ['data-path' => $component->getStatePath()])
                                                        ->schema([
                                                            Tabs::make('ct_row_translations')->tabs(array_map(
                                                                fn ($code, $label) => Tab::make($label)->schema([
                                                                    Forms\Components\TextInput::make("label_{$code}")
                                                                        ->label('Row Label')
                                                                        ->maxLength(120)
                                                                        ->nullable(),
                                                                ]),
                                                                array_keys($locales), array_values($locales),
                                                            )),
                                                            Forms\Components\Textarea::make('values')
                                                                ->label('Values (JSON array, e.g. [true, false, "Custom"])')
                                                                ->rows(2)
                                                                ->nullable()
                                                                ->helperText('JSON array matching column count. true = ✓, false = ✗, string = custom text'),
                                                        ])
                                                        ->columns(1),
                                                ]),
                                        ]),

                                ]),
                        ]),
                ]),
        ]);
    }

    // -------------------------------------------------------------------------
    // Per-field typography row helper
    // -------------------------------------------------------------------------

    /**
     * Wraps a content field in a 5-column grid with inline size / color / align selects.
     * Settings keys: settings.{$key}_size, settings.{$key}_color, settings.{$key}_align
     *
     * @param  \Filament\Schemas\Components\Component  $field
     * @param  string  $settingsKey  e.g. "heading", "badge", "subheading"
     */
    private static function fieldRow(
        \Filament\Schemas\Components\Component $field,
        string $settingsKey
    ): Grid {
        return Grid::make(6)
            ->columnSpanFull()
            ->schema([
                $field->columnSpan(2),

                Forms\Components\Select::make("settings.{$settingsKey}_size")
                    ->label('Size')
                    ->options(['xs' => 'XS', 'sm' => 'SM', 'md' => 'MD', 'lg' => 'LG', 'xl' => 'XL', '2xl' => '2XL', '3xl' => '3XL', '4xl' => '4XL', '5xl' => '5XL', '6xl' => '6XL', '7xl' => '7XL'])
                    ->default('md')
                    ->columnSpan(1),

                Forms\Components\Select::make("settings.{$settingsKey}_color")
                    ->label('Color')
                    ->options([
                        'neutral' => 'Default',
                        'brand'   => 'Brand',
                        'white'   => 'White',
                        'muted'   => 'Muted',
                    ])
                    ->default('neutral')
                    ->columnSpan(1),

                Forms\Components\Select::make("settings.{$settingsKey}_align")
                    ->label('Align')
                    ->options(['left' => 'Left', 'center' => 'Center', 'right' => 'Right'])
                    ->default('left')
                    ->columnSpan(1),

                Forms\Components\Select::make("settings.{$settingsKey}_font")
                    ->label('Font')
                    ->options(['sans' => 'Sans (Inter)', 'display' => 'Display (Syne)'])
                    ->default('sans')
                    ->columnSpan(1),
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
