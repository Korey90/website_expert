<?php

namespace App\Filament\Resources;

use App\Forms\Components\TinyEditor;
use App\Filament\Resources\SiteSectionResource\Pages;
use App\Models\SiteSection;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
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
                    Tabs::make('Translations')
                        ->tabs(
                            array_map(
                                fn (string $locale, string $label) => Tab::make($label)
                                    ->schema([
                                        Forms\Components\TextInput::make("title.{$locale}")
                                            ->label("Title ({$locale})")
                                            ->maxLength(255)
                                            ->columnSpanFull(),
                                        Forms\Components\TextInput::make("subtitle.{$locale}")
                                            ->label("Subtitle ({$locale})")
                                            ->maxLength(500)
                                            ->columnSpanFull(),
                                        TinyEditor::make("body.{$locale}")
                                            ->label("Body ({$locale})")
                                            ->columnSpanFull(),
                                        Forms\Components\TextInput::make("button_text.{$locale}")
                                            ->label("Button Text ({$locale})")
                                            ->maxLength(100),
                                    ]),
                                array_keys(config('languages')),
                                array_values(config('languages')),
                            )
                        )
                        ->columnSpanFull(),
                ]),

            Section::make('Call to Action')
                ->columns(2)
                ->collapsed()
                ->schema([
                    Forms\Components\TextInput::make('button_url')
                        ->label('Button URL (shared)')
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

            Section::make('About Us – Stats')
                ->description('Three stat boxes displayed below the body text.')
                ->columns(2)
                ->collapsed()
                ->visible(fn ($get) => $get('key') === 'about')
                ->schema([
                    Forms\Components\TextInput::make('extra.section_label_en')
                        ->label('Section Badge (EN)')
                        ->placeholder('About Us')
                        ->maxLength(40),
                    Forms\Components\TextInput::make('extra.section_label_pl')
                        ->label('Section Badge (PL)')
                        ->placeholder('O nas')
                        ->maxLength(40),
                    Forms\Components\Repeater::make('extra.stats')
                        ->label('Stats')
                        ->schema([
                            Forms\Components\TextInput::make('value')
                                ->label('Value (e.g. 200+)')
                                ->required()
                                ->maxLength(20),
                            Forms\Components\TextInput::make('label_en')
                                ->label('Label (EN)')
                                ->required()
                                ->maxLength(60),
                            Forms\Components\TextInput::make('label_pl')
                                ->label('Label (PL)')
                                ->required()
                                ->maxLength(60),
                        ])
                        ->columns(3)
                        ->defaultItems(3)
                        ->reorderable()
                        ->columnSpanFull(),
                ]),

            Section::make('About Us – Highlights')
                ->description('Four feature cards displayed on the right side of the section.')
                ->columns(1)
                ->collapsed()
                ->visible(fn ($get) => $get('key') === 'about')
                ->schema([
                    Forms\Components\Repeater::make('extra.highlights')
                        ->label('Highlight Cards')
                        ->schema([
                            Forms\Components\TextInput::make('title_en')
                                ->label('Title (EN)')
                                ->required()
                                ->maxLength(60),
                            Forms\Components\TextInput::make('title_pl')
                                ->label('Title (PL)')
                                ->required()
                                ->maxLength(60),
                            Forms\Components\Textarea::make('body_en')
                                ->label('Description (EN)')
                                ->required()
                                ->rows(2)
                                ->maxLength(200),
                            Forms\Components\Textarea::make('body_pl')
                                ->label('Description (PL)')
                                ->required()
                                ->rows(2)
                                ->maxLength(200),
                        ])
                        ->columns(2)
                        ->defaultItems(4)
                        ->reorderable()
                        ->columnSpanFull(),
                ]),

            Section::make('Portfolio – Projects')
                ->description('Portfolio case study cards.')
                ->columns(1)
                ->collapsed()
                ->visible(fn ($get) => $get('key') === 'portfolio')
                ->schema([
                    Forms\Components\TextInput::make('extra.section_label_en')
                        ->label('Section Badge (EN)')
                        ->placeholder('Portfolio')
                        ->maxLength(40),
                    Forms\Components\TextInput::make('extra.section_label_pl')
                        ->label('Section Badge (PL)')
                        ->placeholder('Portfolio')
                        ->maxLength(40),
                    Forms\Components\Repeater::make('extra.items')
                        ->label('Projects')
                        ->schema([
                            Forms\Components\TextInput::make('client')
                                ->label('Client Name')
                                ->maxLength(100),
                            Forms\Components\TextInput::make('image')
                                ->label('Image path (e.g. /images/portfolio/file.svg)')
                                ->maxLength(300)
                                ->placeholder('/images/portfolio/project.svg'),
                            Forms\Components\TextInput::make('link')
                                ->label('Detail page URL')
                                ->maxLength(200),
                            Forms\Components\TextInput::make('title_en')
                                ->label('Title (EN)')
                                ->required()
                                ->maxLength(120)
                                ->columnSpanFull(),
                            Forms\Components\TextInput::make('title_pl')
                                ->label('Title (PL)')
                                ->maxLength(120)
                                ->columnSpanFull(),
                            Forms\Components\TextInput::make('tag_en')
                                ->label('Category tag (EN)')
                                ->maxLength(60),
                            Forms\Components\TextInput::make('tag_pl')
                                ->label('Category tag (PL)')
                                ->maxLength(60),
                            Forms\Components\Textarea::make('desc_en')
                                ->label('Description (EN)')
                                ->rows(2)
                                ->maxLength(300)
                                ->columnSpanFull(),
                            Forms\Components\Textarea::make('desc_pl')
                                ->label('Description (PL)')
                                ->rows(2)
                                ->maxLength(300)
                                ->columnSpanFull(),
                            Forms\Components\TextInput::make('result_en')
                                ->label('Result / metric (EN)')
                                ->maxLength(100),
                            Forms\Components\TextInput::make('result_pl')
                                ->label('Result / metric (PL)')
                                ->maxLength(100),
                        ])
                        ->columns(3)
                        ->defaultItems(3)
                        ->reorderable()
                        ->columnSpanFull(),
                ]),

            Section::make('Services – Items')
                ->description('Service cards displayed in the grid.')
                ->columns(1)
                ->collapsed()
                ->visible(fn ($get) => $get('key') === 'services')
                ->schema([
                    Forms\Components\TextInput::make('extra.section_label_en')
                        ->label('Section Badge (EN)')
                        ->placeholder('Services')
                        ->maxLength(40),
                    Forms\Components\TextInput::make('extra.section_label_pl')
                        ->label('Section Badge (PL)')
                        ->placeholder('Oferta')
                        ->maxLength(40),
                    Forms\Components\Repeater::make('extra.services')
                        ->label('Service Cards')
                        ->schema([
                            Forms\Components\TextInput::make('icon')
                                ->label('Icon key')
                                ->placeholder('monitor | shopping-cart | code | search | bar-chart | settings | shield | pencil')
                                ->helperText('Identifier mapped to an SVG icon in the component.')
                                ->maxLength(40),
                            Forms\Components\TextInput::make('price_from')
                                ->label('Price from')
                                ->placeholder('£799')
                                ->maxLength(20),
                            Forms\Components\TextInput::make('link')
                                ->label('Link URL')
                                ->maxLength(200),
                            Forms\Components\TextInput::make('title_en')
                                ->label('Title (EN)')
                                ->required()
                                ->maxLength(80)
                                ->columnSpanFull(),
                            Forms\Components\TextInput::make('title_pl')
                                ->label('Title (PL)')
                                ->required()
                                ->maxLength(80)
                                ->columnSpanFull(),
                            Forms\Components\Textarea::make('description_en')
                                ->label('Description (EN)')
                                ->required()
                                ->rows(2)
                                ->maxLength(250)
                                ->columnSpanFull(),
                            Forms\Components\Textarea::make('description_pl')
                                ->label('Description (PL)')
                                ->rows(2)
                                ->maxLength(250)
                                ->columnSpanFull(),
                        ])
                        ->columns(3)
                        ->defaultItems(6)
                        ->reorderable()
                        ->columnSpanFull(),
                ]),

            Section::make('Trust Strip – Clients & Badges')
                ->description('Client logo placeholders and trust badge labels.')
                ->columns(2)
                ->collapsed()
                ->visible(fn ($get) => $get('key') === 'trust_strip')
                ->schema([
                    Forms\Components\TextInput::make('extra.section_label_en')
                        ->label('Section Badge (EN)')
                        ->placeholder('Trusted By')
                        ->maxLength(40),
                    Forms\Components\TextInput::make('extra.section_label_pl')
                        ->label('Section Badge (PL)')
                        ->placeholder('Zaufali nam')
                        ->maxLength(40),
                    Forms\Components\Repeater::make('extra.clients')
                        ->label('Client Names')
                        ->schema([
                            Forms\Components\TextInput::make('name')
                                ->label('Company Name')
                                ->required()
                                ->maxLength(80),
                        ])
                        ->defaultItems(6)
                        ->reorderable()
                        ->columnSpanFull(),
                    Forms\Components\Repeater::make('extra.badges')
                        ->label('Trust Badges')
                        ->schema([
                            Forms\Components\TextInput::make('text')
                                ->label('Badge Text (with emoji)'),
                        ])
                        ->defaultItems(4)
                        ->reorderable()
                        ->columnSpanFull(),
                ]),

            Section::make('Testimonials – Reviews')
                ->description('Customer reviews displayed in the carousel.')
                ->columns(1)
                ->collapsed()
                ->visible(fn ($get) => $get('key') === 'testimonials')
                ->schema([
                    Forms\Components\Repeater::make('extra.reviews')
                        ->label('Reviews')
                        ->schema([
                            Forms\Components\TextInput::make('name')
                                ->label('Reviewer Name')
                                ->required()
                                ->maxLength(80),
                            Forms\Components\TextInput::make('company')
                                ->label('Company')
                                ->maxLength(100),
                            Forms\Components\TextInput::make('rating')
                                ->label('Rating (1–5)')
                                ->numeric()
                                ->minValue(1)
                                ->maxValue(5)
                                ->default(5),
                            Forms\Components\Textarea::make('text_en')
                                ->label('Review Text (EN)')
                                ->required()
                                ->rows(3)
                                ->maxLength(400)
                                ->columnSpanFull(),
                            Forms\Components\Textarea::make('text_pl')
                                ->label('Review Text (PL)')
                                ->rows(3)
                                ->maxLength(400)
                                ->columnSpanFull(),
                        ])
                        ->columns(3)
                        ->defaultItems(4)
                        ->reorderable()
                        ->columnSpanFull(),
                ]),

            Section::make('Cost Calculator – Texts')
                ->description('Section header and all UI strings for the cost calculator wizard.')
                ->columns(2)
                ->collapsed()
                ->visible(fn ($get) => $get('key') === 'cost_calculator')
                ->schema([
                    Forms\Components\TextInput::make('extra.section_label_en')
                        ->label('Section Badge (EN)')
                        ->placeholder('Cost Calculator')
                        ->maxLength(40),
                    Forms\Components\TextInput::make('extra.section_label_pl')
                        ->label('Section Badge (PL)')
                        ->placeholder('Kalkulator kosztów')
                        ->maxLength(40),
                    Forms\Components\Repeater::make('extra.steps')
                        ->label('Step Questions & Hints (8 steps in order)')
                        ->schema([
                            Forms\Components\TextInput::make('question_en')
                                ->label('Question (EN)')
                                ->maxLength(120)
                                ->columnSpanFull(),
                            Forms\Components\TextInput::make('question_pl')
                                ->label('Question (PL)')
                                ->maxLength(120)
                                ->columnSpanFull(),
                            Forms\Components\TextInput::make('hint_en')
                                ->label('Hint text (EN)')
                                ->maxLength(200)
                                ->columnSpanFull(),
                            Forms\Components\TextInput::make('hint_pl')
                                ->label('Hint text (PL)')
                                ->maxLength(200)
                                ->columnSpanFull(),
                        ])
                        ->defaultItems(8)
                        ->maxItems(8)
                        ->reorderable(false)
                        ->columnSpanFull(),
                    Forms\Components\TextInput::make('extra.result_title_en')->label('Result title (EN)')->maxLength(80),
                    Forms\Components\TextInput::make('extra.result_title_pl')->label('Result title (PL)')->maxLength(80),
                    Forms\Components\TextInput::make('extra.result_subtitle_en')->label('Result subtitle (EN)')->maxLength(200)->columnSpanFull(),
                    Forms\Components\TextInput::make('extra.result_subtitle_pl')->label('Result subtitle (PL)')->maxLength(200)->columnSpanFull(),
                    Forms\Components\TextInput::make('extra.submit_btn_en')->label('Submit button (EN)')->maxLength(60),
                    Forms\Components\TextInput::make('extra.submit_btn_pl')->label('Submit button (PL)')->maxLength(60),
                    Forms\Components\TextInput::make('extra.success_msg_en')->label('Success message (EN)')->maxLength(150)->columnSpanFull(),
                    Forms\Components\TextInput::make('extra.success_msg_pl')->label('Success message (PL)')->maxLength(150)->columnSpanFull(),
                ]),

            Section::make('Navbar – Links & CTA')
                ->description('Navigation links and call-to-action button text.')
                ->columns(2)
                ->collapsed()
                ->visible(fn ($get) => $get('key') === 'navbar')
                ->schema([
                    Forms\Components\TextInput::make('extra.brand_name')
                        ->label('Brand Name')
                        ->maxLength(40),
                    Forms\Components\TextInput::make('extra.cta_href')
                        ->label('CTA Button URL')
                        ->placeholder('#kontakt')
                        ->maxLength(100),
                    Forms\Components\TextInput::make('extra.cta_text_en')
                        ->label('CTA Button Text (EN)')
                        ->placeholder('Free Quote')
                        ->maxLength(40),
                    Forms\Components\TextInput::make('extra.cta_text_pl')
                        ->label('CTA Button Text (PL)')
                        ->placeholder('Bezpłatna wycena')
                        ->maxLength(40),
                    Forms\Components\Repeater::make('extra.links')
                        ->label('Navigation Links (in order)')
                        ->schema([
                            Forms\Components\TextInput::make('href')
                                ->label('URL / hash')
                                ->placeholder('#oferta')
                                ->maxLength(100),
                            Forms\Components\TextInput::make('label_en')
                                ->label('Label (EN)')
                                ->maxLength(60),
                            Forms\Components\TextInput::make('label_pl')
                                ->label('Label (PL)')
                                ->maxLength(60),
                        ])
                        ->columns(3)
                        ->defaultItems(5)
                        ->reorderable()
                        ->columnSpanFull(),
                ]),

            Section::make('Contact – Info & Form Texts')
                ->description('Contact details, field labels, and all UI strings for the contact form.')
                ->columns(2)
                ->collapsed()
                ->visible(fn ($get) => $get('key') === 'contact')
                ->schema([
                    Forms\Components\TextInput::make('extra.section_label_en')->label('Section badge (EN)')->maxLength(40),
                    Forms\Components\TextInput::make('extra.section_label_pl')->label('Section badge (PL)')->maxLength(40),
                    Forms\Components\TextInput::make('extra.email')->label('Email address')->maxLength(120),
                    Forms\Components\TextInput::make('extra.phone')->label('Phone display text')->maxLength(40),
                    Forms\Components\TextInput::make('extra.phone_href')->label('Phone href (tel:...)')->maxLength(60),
                    Forms\Components\TextInput::make('extra.privacy_url')->label('Privacy Policy URL')->maxLength(200),
                    Forms\Components\TextInput::make('extra.submit_btn_en')->label('Submit button (EN)')->maxLength(60),
                    Forms\Components\TextInput::make('extra.submit_btn_pl')->label('Submit button (PL)')->maxLength(60),
                    Forms\Components\Textarea::make('extra.success_msg_en')->label('Success message (EN)')->rows(2)->maxLength(200)->columnSpanFull(),
                    Forms\Components\Textarea::make('extra.success_msg_pl')->label('Success message (PL)')->rows(2)->maxLength(200)->columnSpanFull(),
                    Forms\Components\Textarea::make('extra.error_msg_en')->label('Error message (EN)')->rows(2)->maxLength(200)->columnSpanFull(),
                    Forms\Components\Textarea::make('extra.error_msg_pl')->label('Error message (PL)')->rows(2)->maxLength(200)->columnSpanFull(),
                    Forms\Components\Textarea::make('extra.gdpr_text_en')->label('GDPR consent text (EN)')->rows(2)->maxLength(350)->columnSpanFull(),
                    Forms\Components\Textarea::make('extra.gdpr_text_pl')->label('GDPR consent text (PL)')->rows(2)->maxLength(350)->columnSpanFull(),
                    Forms\Components\TextInput::make('extra.gdpr_link_text_en')->label('Privacy link text (EN)')->placeholder('privacy policy')->maxLength(40),
                    Forms\Components\TextInput::make('extra.gdpr_link_text_pl')->label('Privacy link text (PL)')->placeholder('polityką prywatności')->maxLength(40),
                ]),

            Section::make('Footer – Content')
                ->description('Tagline, copyright, social links and navigation columns.')
                ->columns(2)
                ->collapsed()
                ->visible(fn ($get) => $get('key') === 'footer')
                ->schema([
                    Forms\Components\TextInput::make('extra.brand_name')->label('Brand Name')->maxLength(40)->columnSpanFull(),
                    Forms\Components\TextInput::make('extra.tagline_en')->label('Tagline (EN)')->maxLength(150),
                    Forms\Components\TextInput::make('extra.tagline_pl')->label('Tagline (PL)')->maxLength(150),
                    Forms\Components\TextInput::make('extra.copyright_en')->label('Copyright text (EN)')->maxLength(100),
                    Forms\Components\TextInput::make('extra.copyright_pl')->label('Copyright text (PL)')->maxLength(100),
                    Forms\Components\TextInput::make('extra.built_with_en')->label('"Built with" text (EN)')->maxLength(100),
                    Forms\Components\TextInput::make('extra.built_with_pl')->label('"Built with" text (PL)')->maxLength(100),
                    Forms\Components\Repeater::make('extra.social')
                        ->label('Social Links')
                        ->schema([
                            Forms\Components\TextInput::make('key')->label('Icon key (linkedin/facebook/instagram)')->maxLength(20),
                            Forms\Components\TextInput::make('url')->label('Profile URL')->maxLength(200),
                            Forms\Components\TextInput::make('label')->label('Aria Label')->maxLength(40),
                        ])
                        ->columns(3)
                        ->defaultItems(3)
                        ->reorderable()
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
                        ->columnSpanFull(),
                ]),

            Section::make('Extra Data (JSON)')
                ->collapsed()
                ->hidden(fn ($get) => in_array($get('key'), ['about', 'trust_strip', 'testimonials', 'services', 'portfolio', 'cost_calculator', 'navbar', 'contact', 'footer']))
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
                    ->getStateUsing(fn ($record) => $record->getTranslation('title', 'en'))
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
