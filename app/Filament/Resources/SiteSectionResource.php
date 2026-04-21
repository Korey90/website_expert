<?php

namespace App\Filament\Resources;

use App\Forms\Components\TinyEditor;
use App\Filament\Resources\SiteSectionResource\Pages;
use App\Models\SiteSection;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms;
use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Schema;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class SiteSectionResource extends BaseResource
{
    protected static ?string $model = SiteSection::class;
    protected static \BackedEnum|string|null $navigationIcon = 'heroicon-o-squares-2x2';
    protected static \UnitEnum|string|null $navigationGroup = 'Marketing';
    protected static ?string $navigationLabel = 'Front-end Sections';
    protected static ?int $navigationSort = 6;

    public static function infolist(Schema $schema): Schema
    {
        $locales = config('languages', ['en' => 'English', 'pl' => 'Polski']);

        return $schema->columns(1)->schema([

            Section::make('Section Identity')
                ->columns(2)
                ->schema([
                    TextEntry::make('label')
                        ->label('Display Name')
                        ->weight('bold')
                        ->size('lg')
                        ->columnSpanFull(),

                    TextEntry::make('key')
                        ->label('Section Key')
                        ->badge()
                        ->color('gray')
                        ->copyable()
                        ->icon('heroicon-o-code-bracket'),

                    TextEntry::make('sort_order')
                        ->label('Order'),

                    IconEntry::make('is_active')
                        ->label('Visible on site')
                        ->boolean()
                        ->trueColor('success')
                        ->falseColor('danger'),

                    TextEntry::make('button_url')
                        ->label('Button URL (shared)')
                        ->icon('heroicon-o-link')
                        ->placeholder('—')
                        ->url(fn ($state) => $state ?: null)
                        ->openUrlInNewTab(),

                    TextEntry::make('image_path')
                        ->label('Section Image')
                        ->placeholder('No image')
                        ->icon('heroicon-o-photo')
                        ->columnSpanFull(),

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
                                            ->placeholder('—')
                                            ->getStateUsing(fn ($record) => $record->getTranslation('title', $locale) ?: null),

                                        TextEntry::make("subtitle.{$locale}")
                                            ->label("Subtitle ({$locale})")
                                            ->placeholder('—')
                                            ->getStateUsing(fn ($record) => $record->getTranslation('subtitle', $locale) ?: null),

                                        TextEntry::make("button_text.{$locale}")
                                            ->label("Button Text ({$locale})")
                                            ->placeholder('—')
                                            ->getStateUsing(fn ($record) => $record->getTranslation('button_text', $locale) ?: null),

                                        TextEntry::make("body.{$locale}")
                                            ->label("Body ({$locale})")
                                            ->html()
                                            ->columnSpanFull()
                                            ->placeholder('No content')
                                            ->getStateUsing(fn ($record) => $record->getTranslation('body', $locale) ?: null),
                                    ]),
                                array_keys($locales),
                                array_values($locales),
                            )
                        )
                        ->columnSpanFull(),
                ]),

            Section::make('Extra Data (JSON)')
                ->description('Raw structured data passed to the React component.')
                ->collapsed()
                ->schema([
                    TextEntry::make('extra')
                        ->label('')
                        ->columnSpanFull()
                        ->fontFamily('mono')
                        ->getStateUsing(fn ($record) => $record->extra
                            ? json_encode($record->extra, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
                            : '—'),
                ]),
        ]);
    }

    public static function form(Schema $form): Schema
    {
        return $form->schema([

            // ── 1. IDENTITY ───────────────────────────────────────────────
            Section::make('Section Identity')
                ->icon('heroicon-o-identification')
                ->columns(4)
                ->collapsible()
                ->collapsed()
                ->schema([
                    Forms\Components\TextInput::make('key')
                        ->label('Section Key')
                        ->required()
                        ->unique(ignoreRecord: true)
                        ->maxLength(100)
                        ->prefixIcon('heroicon-o-code-bracket')
                        ->helperText('Used in React components, e.g. hero, about, cta')
                        ->columnSpan(2),
                    Forms\Components\TextInput::make('label')
                        ->label('Display Name')
                        ->required()
                        ->maxLength(255)
                        ->prefixIcon('heroicon-o-tag')
                        ->columnSpan(2),
                    Forms\Components\Toggle::make('is_active')
                        ->label('Visible on site')
                        ->default(true)
                        ->inline(false)
                        ->columnSpan(1),
                    Forms\Components\TextInput::make('sort_order')
                        ->label('Display Order')
                        ->numeric()
                        ->default(0)
                        ->prefixIcon('heroicon-o-arrows-up-down')
                        ->columnSpan(1),
                ]),

            // ── 2. CONTENT TRANSLATIONS ───────────────────────────────────
            Section::make('Content & Translations')
                ->icon('heroicon-o-document-text')
                ->columns(1)
                ->collapsible()
                ->collapsed()
                ->schema([
                    Tabs::make('Translations')
                        ->tabs(
                            array_map(
                                fn (string $locale, string $label) => Tab::make($label)
                                    ->schema([
                                        Forms\Components\TextInput::make("title.{$locale}")
                                            ->label('Title')
                                            ->maxLength(255)
                                            ->columnSpanFull(),
                                        Forms\Components\TextInput::make("subtitle.{$locale}")
                                            ->label('Subtitle')
                                            ->maxLength(500)
                                            ->columnSpanFull(),
                                        TinyEditor::make("body.{$locale}")
                                            ->label('Body')
                                            ->columnSpanFull(),
                                        Forms\Components\TextInput::make("button_text.{$locale}")
                                            ->label('Button Text')
                                            ->prefixIcon('heroicon-o-cursor-arrow-rays')
                                            ->maxLength(100),
                                    ]),
                                array_keys(config('languages')),
                                array_values(config('languages')),
                            )
                        )
                        ->columnSpanFull(),
                ]),

            // ── 3. PRESENTATION (CTA + MEDIA) ─────────────────────────────
            Section::make('Presentation')
                ->icon('heroicon-o-photo')
                ->description('Shared button URL and section image.')
                ->columns(2)
                ->collapsed()
                ->schema([
                    Forms\Components\TextInput::make('button_url')
                        ->label('Button URL')
                        ->prefixIcon('heroicon-o-link')
                        ->placeholder('#calculate or /contact')
                        ->maxLength(500),
                    Forms\Components\FileUpload::make('image_path')
                        ->label('Section Image')
                        ->image()
                        ->directory('site-sections')
                        ->imageResizeMode('cover')
                        ->imageCropAspectRatio('16:9'),
                ]),

            // ── 4. ABOUT US ───────────────────────────────────────────────
            Section::make('About Us')
                ->icon('heroicon-o-user-group')
                ->description('Section badge, stat boxes and highlight cards.')
                ->columns(1)
                ->collapsed()
                ->visible(fn ($record) => ($record?->key ?? '') === 'about')
                ->schema([
                    Tabs::make('Section Badge')
                        ->tabs([
                            Tab::make('English')->schema([
                                Forms\Components\TextInput::make('extra.section_label_en')
                                    ->label('Section badge')->placeholder('About Us')->maxLength(40),
                            ]),
                            Tab::make('Polski')->schema([
                                Forms\Components\TextInput::make('extra.section_label_pl')
                                    ->label('Etykieta sekcji')->placeholder('O nas')->maxLength(40),
                            ]),
                        ]),
                    Forms\Components\Repeater::make('extra.stats')
                        ->label('Stat Boxes')
                        ->helperText('Three key numbers shown below the main text.')
                        ->schema([
                            Forms\Components\TextInput::make('value')
                                ->label('Value')->placeholder('200+')->required()->maxLength(20),
                            Forms\Components\TextInput::make('label_en')
                                ->label('Label (EN)')->required()->maxLength(60),
                            Forms\Components\TextInput::make('label_pl')
                                ->label('Label (PL)')->required()->maxLength(60),
                        ])
                        ->columns(3)
                        ->defaultItems(3)
                        ->reorderable()
                        ->collapsible()
                        ->collapsed()
                        ->columnSpanFull(),
                    Forms\Components\Repeater::make('extra.highlights')
                        ->label('Highlight Cards')
                        ->helperText('Feature cards shown on the right side of the section.')
                        ->schema([
                            Forms\Components\TextInput::make('title_en')->label('Title (EN)')->required()->maxLength(60),
                            Forms\Components\TextInput::make('title_pl')->label('Title (PL)')->required()->maxLength(60),
                            Forms\Components\Textarea::make('body_en')->label('Description (EN)')->required()->rows(2)->maxLength(200),
                            Forms\Components\Textarea::make('body_pl')->label('Description (PL)')->required()->rows(2)->maxLength(200),
                        ])
                        ->columns(2)
                        ->defaultItems(4)
                        ->reorderable()
                        ->collapsible()
                        ->collapsed()
                        ->columnSpanFull(),
                ]),

            // ── 5. PORTFOLIO ──────────────────────────────────────────────
            Section::make('Portfolio')
                ->icon('heroicon-o-squares-2x2')
                ->description('Section badge. Cards are managed in Marketing → Portfolio Projects.')
                ->columns(1)
                ->collapsed()
                ->visible(fn ($record) => ($record?->key ?? '') === 'portfolio')
                ->schema([
                    Forms\Components\Placeholder::make('portfolio_items_notice')
                        ->label('')
                        ->content('Project cards are managed in the dedicated Portfolio Projects resource. Changes there are reflected automatically on the homepage.'),
                    Tabs::make('Section Badge')
                        ->tabs([
                            Tab::make('English')->schema([
                                Forms\Components\TextInput::make('extra.section_label_en')
                                    ->label('Section badge')->placeholder('Portfolio')->maxLength(40),
                            ]),
                            Tab::make('Polski')->schema([
                                Forms\Components\TextInput::make('extra.section_label_pl')
                                    ->label('Etykieta sekcji')->placeholder('Portfolio')->maxLength(40),
                            ]),
                        ]),
                ]),

            // ── 6. SERVICES ───────────────────────────────────────────────
            Section::make('Services')
                ->icon('heroicon-o-briefcase')
                ->description('Section badge. Cards are managed in Marketing → Services.')
                ->columns(1)
                ->collapsed()
                ->visible(fn ($record) => ($record?->key ?? '') === 'services')
                ->schema([
                    Forms\Components\Placeholder::make('services_items_notice')
                        ->label('')
                        ->content('Service cards are managed in the dedicated Services resource. Changes there are reflected automatically on the homepage.'),
                    Tabs::make('Section Badge')
                        ->tabs([
                            Tab::make('English')->schema([
                                Forms\Components\TextInput::make('extra.section_label_en')
                                    ->label('Section badge')->placeholder('Services')->maxLength(40),
                            ]),
                            Tab::make('Polski')->schema([
                                Forms\Components\TextInput::make('extra.section_label_pl')
                                    ->label('Etykieta sekcji')->placeholder('Oferta')->maxLength(40),
                            ]),
                        ]),
                ]),

            // ── 7. TRUST STRIP ────────────────────────────────────────────
            Section::make('Trust Strip')
                ->icon('heroicon-o-star')
                ->description('Client logo placeholders and trust badge labels.')
                ->columns(1)
                ->collapsed()
                ->visible(fn ($record) => ($record?->key ?? '') === 'trust_strip')
                ->schema([
                    Tabs::make('Section Badge')
                        ->tabs([
                            Tab::make('English')->schema([
                                Forms\Components\TextInput::make('extra.section_label_en')
                                    ->label('Section badge')->placeholder('Trusted By')->maxLength(40),
                            ]),
                            Tab::make('Polski')->schema([
                                Forms\Components\TextInput::make('extra.section_label_pl')
                                    ->label('Etykieta sekcji')->placeholder('Zaufali nam')->maxLength(40),
                            ]),
                        ]),
                    Forms\Components\Repeater::make('extra.clients')
                        ->label('Client Names')
                        ->schema([
                            Forms\Components\TextInput::make('name')->label('Company name')->required()->maxLength(80),
                        ])
                        ->defaultItems(6)
                        ->reorderable()
                        ->collapsible()
                        ->collapsed()
                        ->columnSpanFull(),
                    Forms\Components\Repeater::make('extra.badges')
                        ->label('Trust Badges')
                        ->schema([
                            Forms\Components\TextInput::make('text')->label('Badge text (with emoji)'),
                        ])
                        ->defaultItems(4)
                        ->reorderable()
                        ->collapsible()
                        ->collapsed()
                        ->columnSpanFull(),
                ]),

            // ── 8. TESTIMONIALS ───────────────────────────────────────────
            Section::make('Testimonials')
                ->icon('heroicon-o-chat-bubble-left-right')
                ->description('Customer reviews displayed in the carousel.')
                ->columns(1)
                ->collapsed()
                ->visible(fn ($record) => ($record?->key ?? '') === 'testimonials')
                ->schema([
                    Forms\Components\Repeater::make('extra.reviews')
                        ->label('Reviews')
                        ->schema([
                            Forms\Components\TextInput::make('name')
                                ->label('Reviewer name')->required()->maxLength(80),
                            Forms\Components\TextInput::make('company')
                                ->label('Company')->maxLength(100),
                            Forms\Components\TextInput::make('rating')
                                ->label('Rating (1–5)')->numeric()->minValue(1)->maxValue(5)->default(5),
                            Forms\Components\Textarea::make('text_en')
                                ->label('Review text (EN)')->required()->rows(3)->maxLength(400)->columnSpanFull(),
                            Forms\Components\Textarea::make('text_pl')
                                ->label('Review text (PL)')->rows(3)->maxLength(400)->columnSpanFull(),
                        ])
                        ->columns(3)
                        ->defaultItems(4)
                        ->reorderable()
                        ->collapsible()
                        ->collapsed()
                        ->columnSpanFull(),
                ]),

            // ── 9. COST CALCULATOR ────────────────────────────────────────
            Section::make('Cost Calculator')
                ->icon('heroicon-o-calculator')
                ->description('Section header and all UI strings for the cost calculator wizard.')
                ->columns(1)
                ->collapsed()
                ->visible(fn ($record) => ($record?->key ?? '') === 'cost_calculator')
                ->schema([
                    Tabs::make('UI Strings')
                        ->tabs([
                            Tab::make('English')
                                ->columns(2)
                                ->schema([
                                    Forms\Components\TextInput::make('extra.section_label_en')
                                        ->label('Section badge')->placeholder('Cost Calculator')->maxLength(40),
                                    Forms\Components\TextInput::make('extra.submit_btn_en')
                                        ->label('Submit button')->placeholder('Calculate Quote 🚀')->maxLength(60),
                                    Forms\Components\TextInput::make('extra.result_title_en')
                                        ->label('Result title')->maxLength(80),
                                    Forms\Components\TextInput::make('extra.result_subtitle_en')
                                        ->label('Result subtitle')->maxLength(200)->columnSpanFull(),
                                    Forms\Components\TextInput::make('extra.success_msg_en')
                                        ->label('Success message')->maxLength(150)->columnSpanFull(),
                                ]),
                            Tab::make('Polski')
                                ->columns(2)
                                ->schema([
                                    Forms\Components\TextInput::make('extra.section_label_pl')
                                        ->label('Etykieta sekcji')->placeholder('Kalkulator kosztów')->maxLength(40),
                                    Forms\Components\TextInput::make('extra.submit_btn_pl')
                                        ->label('Przycisk wyślij')->placeholder('Oblicz wycenę 🚀')->maxLength(60),
                                    Forms\Components\TextInput::make('extra.result_title_pl')
                                        ->label('Tytuł wyniku')->maxLength(80),
                                    Forms\Components\TextInput::make('extra.result_subtitle_pl')
                                        ->label('Podtytuł wyniku')->maxLength(200)->columnSpanFull(),
                                    Forms\Components\TextInput::make('extra.success_msg_pl')
                                        ->label('Komunikat sukcesu')->maxLength(150)->columnSpanFull(),
                                ]),
                        ]),
                    Forms\Components\Repeater::make('extra.steps')
                        ->label('Step Questions & Hints')
                        ->helperText('8 steps in order. Question shown as the step heading, hint shown below options.')
                        ->schema([
                            Forms\Components\TextInput::make('question_en')->label('Question (EN)')->maxLength(120)->columnSpanFull(),
                            Forms\Components\TextInput::make('question_pl')->label('Question (PL)')->maxLength(120)->columnSpanFull(),
                            Forms\Components\TextInput::make('hint_en')->label('Hint (EN)')->maxLength(200)->columnSpanFull(),
                            Forms\Components\TextInput::make('hint_pl')->label('Hint (PL)')->maxLength(200)->columnSpanFull(),
                        ])
                        ->defaultItems(8)
                        ->maxItems(8)
                        ->reorderable(false)
                        ->collapsible()
                        ->collapsed()
                        ->columnSpanFull(),
                ]),

            // ── 10. CONTACT ───────────────────────────────────────────────
            Section::make('Contact – Info & Form Texts')
                ->icon('heroicon-o-envelope')
                ->description('Contact details, field labels, and all UI strings for the contact form.')
                ->columns(1)
                ->collapsed()
                ->visible(fn ($record) => ($record?->key ?? '') === 'contact')
                ->schema([
                    Section::make('Contact Details')
                        ->description('Shared across all languages.')
                        ->icon('heroicon-o-phone')
                        ->columns(2)
                        ->schema([
                            Forms\Components\TextInput::make('extra.email')
                                ->label('Email address')
                                ->email()
                                ->prefixIcon('heroicon-o-envelope')
                                ->maxLength(120)
                                ->columnSpanFull(),
                            Forms\Components\TextInput::make('extra.phone')
                                ->label('Phone (display)')
                                ->prefixIcon('heroicon-o-phone')
                                ->placeholder('+44 28 0000 0000')
                                ->maxLength(40),
                            Forms\Components\TextInput::make('extra.phone_href')
                                ->label('Phone href')
                                ->prefixIcon('heroicon-o-link')
                                ->placeholder('tel:+442800000000')
                                ->maxLength(60),
                            Forms\Components\TextInput::make('extra.privacy_url')
                                ->label('Privacy Policy URL')
                                ->prefixIcon('heroicon-o-shield-check')
                                ->placeholder('/privacy-policy')
                                ->maxLength(200)
                                ->columnSpanFull(),
                        ]),
                    Tabs::make('Language strings')
                        ->columnSpanFull()
                        ->tabs([
                            Tab::make('English')
                                ->icon('heroicon-o-language')
                                ->schema([
                                    Forms\Components\TextInput::make('extra.section_label_en')
                                        ->label('Section badge')->placeholder('Contact')->maxLength(40),
                                    Forms\Components\TextInput::make('extra.submit_btn_en')
                                        ->label('Submit button')->placeholder('Send message')->maxLength(60),
                                    Forms\Components\Textarea::make('extra.success_msg_en')
                                        ->label('Success message')->rows(2)->maxLength(200),
                                    Forms\Components\Textarea::make('extra.error_msg_en')
                                        ->label('Error message')->rows(2)->maxLength(200),
                                    Forms\Components\Textarea::make('extra.gdpr_text_en')
                                        ->label('GDPR consent text')->rows(3)->maxLength(350),
                                    Forms\Components\TextInput::make('extra.gdpr_link_text_en')
                                        ->label('Privacy link label')->placeholder('privacy policy')->maxLength(40),
                                ]),
                            Tab::make('Polski')
                                ->icon('heroicon-o-language')
                                ->schema([
                                    Forms\Components\TextInput::make('extra.section_label_pl')
                                        ->label('Etykieta sekcji')->placeholder('Kontakt')->maxLength(40),
                                    Forms\Components\TextInput::make('extra.submit_btn_pl')
                                        ->label('Przycisk wyślij')->placeholder('Wyślij wiadomość')->maxLength(60),
                                    Forms\Components\Textarea::make('extra.success_msg_pl')
                                        ->label('Komunikat sukcesu')->rows(2)->maxLength(200),
                                    Forms\Components\Textarea::make('extra.error_msg_pl')
                                        ->label('Komunikat błędu')->rows(2)->maxLength(200),
                                    Forms\Components\Textarea::make('extra.gdpr_text_pl')
                                        ->label('Tekst zgody RODO')->rows(3)->maxLength(350),
                                    Forms\Components\TextInput::make('extra.gdpr_link_text_pl')
                                        ->label('Etykieta linku prywatności')->placeholder('polityką prywatności')->maxLength(40),
                                ]),
                            Tab::make('Português')
                                ->icon('heroicon-o-language')
                                ->schema([
                                    Forms\Components\TextInput::make('extra.section_label_pt')
                                        ->label('Etiqueta da secção')->placeholder('Contacto')->maxLength(40),
                                    Forms\Components\TextInput::make('extra.submit_btn_pt')
                                        ->label('Botão enviar')->placeholder('Enviar mensagem')->maxLength(60),
                                    Forms\Components\Textarea::make('extra.success_msg_pt')
                                        ->label('Mensagem de sucesso')->rows(2)->maxLength(200),
                                    Forms\Components\Textarea::make('extra.error_msg_pt')
                                        ->label('Mensagem de erro')->rows(2)->maxLength(200),
                                    Forms\Components\Textarea::make('extra.gdpr_text_pt')
                                        ->label('Texto de consentimento RGPD')->rows(3)->maxLength(350),
                                    Forms\Components\TextInput::make('extra.gdpr_link_text_pt')
                                        ->label('Etiqueta do link de privacidade')->placeholder('política de privacidade')->maxLength(40),
                                ]),
                        ]),
                ]),

            // ── 11. FOOTER ────────────────────────────────────────────────
            Section::make('Footer')
                ->icon('heroicon-o-rectangle-stack')
                ->description('Brand, tagline, copyright, social links and navigation columns.')
                ->columns(1)
                ->collapsed()
                ->visible(fn ($record) => ($record?->key ?? '') === 'footer')
                ->schema([
                    Forms\Components\TextInput::make('extra.brand_name')
                        ->label('Brand name')
                        ->prefixIcon('heroicon-o-building-office')
                        ->maxLength(40),
                    Tabs::make('Text Strings')
                        ->tabs([
                            Tab::make('English')
                                ->columns(2)
                                ->schema([
                                    Forms\Components\TextInput::make('extra.tagline_en')->label('Tagline')->maxLength(150),
                                    Forms\Components\TextInput::make('extra.copyright_en')->label('Copyright text')->maxLength(100),
                                    Forms\Components\TextInput::make('extra.built_with_en')->label('"Built with" text')->maxLength(100),
                                ]),
                            Tab::make('Polski')
                                ->columns(2)
                                ->schema([
                                    Forms\Components\TextInput::make('extra.tagline_pl')->label('Tagline')->maxLength(150),
                                    Forms\Components\TextInput::make('extra.copyright_pl')->label('Copyright text')->maxLength(100),
                                    Forms\Components\TextInput::make('extra.built_with_pl')->label('"Built with" text')->maxLength(100),
                                ]),
                        ]),
                    Forms\Components\Repeater::make('extra.social')
                        ->label('Social Links')
                        ->schema([
                            Forms\Components\TextInput::make('key')->label('Icon key')->placeholder('linkedin / facebook / instagram')->maxLength(20),
                            Forms\Components\TextInput::make('url')->label('Profile URL')->maxLength(200),
                            Forms\Components\TextInput::make('label')->label('Aria Label')->maxLength(40),
                        ])
                        ->columns(3)
                        ->defaultItems(3)
                        ->reorderable()
                        ->collapsible()
                        ->collapsed()
                        ->columnSpanFull(),
                    Forms\Components\Repeater::make('extra.nav_groups')
                        ->label('Footer Navigation Columns')
                        ->schema([
                            Forms\Components\TextInput::make('title_en')->label('Column title (EN)')->maxLength(40),
                            Forms\Components\TextInput::make('title_pl')->label('Column title (PL)')->maxLength(40),
                            Forms\Components\Repeater::make('links')
                                ->label('Links')
                                ->schema([
                                    Forms\Components\TextInput::make('href')->label('URL / hash')->maxLength(200),
                                    Forms\Components\TextInput::make('label_en')->label('Label (EN)')->maxLength(60),
                                    Forms\Components\TextInput::make('label_pl')->label('Label (PL)')->maxLength(60),
                                ])
                                ->columns(3)
                                ->defaultItems(3)
                                ->reorderable()
                                ->columnSpanFull(),
                        ])
                        ->columns(2)
                        ->defaultItems(3)
                        ->reorderable()
                        ->collapsible()
                        ->collapsed()
                        ->columnSpanFull(),
                ]),

            // ── 12. RAW EXTRA (fallback for sections without dedicated UI) ─
            Section::make('Extra Data (JSON)')
                ->icon('heroicon-o-code-bracket')
                ->description('Raw key-value pairs passed to the React component. Only visible for sections without a dedicated editor above.')
                ->collapsed()
                ->hidden(fn ($record) => in_array($record?->key ?? '', ['about', 'trust_strip', 'testimonials', 'services', 'portfolio', 'cost_calculator', 'contact', 'footer']))
                ->schema([
                    Forms\Components\KeyValue::make('extra_kv')
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
                    ->getStateUsing(fn ($record) => $record->getTranslation('title', 'en'))
                    ->limit(40),
                Tables\Columns\ToggleColumn::make('is_active')
                    ->label('Active'),
                Tables\Columns\TextColumn::make('updated_at')
                    ->label('Updated')
                    ->date()
                    ->sortable(),
            ])
            ->defaultSort('sort_order')
            ->reorderable('sort_order')
            ->actions([ViewAction::make(), EditAction::make(), DeleteAction::make()])
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
            'view'   => Pages\ViewSiteSection::route('/{record}'),
            'edit'   => Pages\EditSiteSection::route('/{record}/edit'),
        ];
    }
}
