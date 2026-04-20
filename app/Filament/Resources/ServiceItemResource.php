<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ServiceItemResource\Pages;
use App\Models\ServiceItem;
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

class ServiceItemResource extends BaseResource
{
    protected static ?string $model = ServiceItem::class;
    protected static \BackedEnum|string|null $navigationIcon = 'heroicon-o-wrench-screwdriver';
    protected static \UnitEnum|string|null $navigationGroup = 'Marketing';
    protected static ?string $navigationLabel = 'Services';
    protected static ?string $label = 'Service';
    protected static ?string $pluralLabel = 'Services';
    protected static ?int $navigationSort = 4;

    // -------------------------------------------------------------------------
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
                        ->maxLength(120)
                        ->nullable(),

                    Forms\Components\TextInput::make("badge_text.{$code}")
                        ->label('Badge / Eyebrow')
                        ->maxLength(60)
                        ->nullable()
                        ->helperText('Short label above the title, e.g. "Most Popular"'),

                    Forms\Components\Textarea::make("description.{$code}")
                        ->label('Short Description')
                        ->rows(3)
                        ->nullable()
                        ->helperText('Used on listing cards and meta fallback.'),

                    Forms\Components\RichEditor::make("body.{$code}")
                        ->label('Full Body (Rich Text)')
                        ->nullable()
                        ->toolbarButtons([
                            'bold', 'italic', 'underline', 'strike',
                            'h2', 'h3',
                            'bulletList', 'orderedList',
                            'link', 'blockquote',
                        ])
                        ->helperText('Main content shown on the service detail page.'),

                    Forms\Components\TextInput::make("cta_label.{$code}")
                        ->label('CTA Button Label')
                        ->maxLength(80)
                        ->nullable()
                        ->helperText('e.g. "Get a Free Quote" — leave empty to use default'),

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

        $iconOptions = collect([
            'bar-chart', 'code', 'file-text', 'monitor', 'pencil',
            'search', 'settings', 'shield', 'shopping-cart', 'zap',
        ])->mapWithKeys(fn ($v) => [$v => $v])->all();

        return $form->schema([
            Tabs::make('Service Item')
                ->columnSpanFull()
                ->tabs([
                    Tab::make('Content')
                        ->icon('heroicon-o-language')
                        ->schema([
                            Tabs::make('Translations')
                                ->columnSpanFull()
                                ->tabs($tabSchemas),
                        ]),

                    Tab::make('Media')
                        ->icon('heroicon-o-photo')
                        ->schema([
                            Forms\Components\FileUpload::make('image_path')
                                ->label('Service Image / Mockup')
                                ->image()
                                ->disk('public')
                                ->directory('services')
                                ->imagePreviewHeight('200')
                                ->nullable(),
                        ]),

                    Tab::make('Features')
                        ->icon('heroicon-o-check-badge')
                        ->schema([
                            Forms\Components\Repeater::make('features')
                                ->label('Feature / Benefit Items')
                                ->columnSpanFull()
                                ->addActionLabel('Add Feature')
                                ->schema([
                                    Forms\Components\TextInput::make('text_en')
                                        ->label('English')
                                        ->maxLength(200)
                                        ->nullable(),
                                    Forms\Components\TextInput::make('text_pl')
                                        ->label('Polski')
                                        ->maxLength(200)
                                        ->nullable(),
                                    Forms\Components\TextInput::make('text_pt')
                                        ->label('Português')
                                        ->maxLength(200)
                                        ->nullable(),
                                ])
                                ->defaultItems(0)
                                ->nullable(),
                        ]),

                    Tab::make('FAQ')
                        ->icon('heroicon-o-question-mark-circle')
                        ->schema([
                            Forms\Components\Repeater::make('faq')
                                ->label('FAQ Items')
                                ->columnSpanFull()
                                ->addActionLabel('Add FAQ')
                                ->schema([
                                    Forms\Components\TextInput::make('q_en')->label('Question EN')->maxLength(255)->nullable(),
                                    Forms\Components\TextInput::make('q_pl')->label('Question PL')->maxLength(255)->nullable(),
                                    Forms\Components\TextInput::make('q_pt')->label('Question PT')->maxLength(255)->nullable(),
                                    Forms\Components\Textarea::make('a_en')->label('Answer EN')->rows(3)->nullable(),
                                    Forms\Components\Textarea::make('a_pl')->label('Answer PL')->rows(3)->nullable(),
                                    Forms\Components\Textarea::make('a_pt')->label('Answer PT')->rows(3)->nullable(),
                                ])
                                ->defaultItems(0)
                                ->nullable(),
                        ]),

                    Tab::make('Settings')
                        ->icon('heroicon-o-cog-6-tooth')
                        ->schema([
                            Forms\Components\Select::make('icon')
                                ->label('Icon')
                                ->options($iconOptions)
                                ->default('settings')
                                ->searchable()
                                ->required(),

                            Forms\Components\TextInput::make('price_from')
                                ->label('Price From')
                                ->maxLength(30)
                                ->nullable()
                                ->helperText('e.g. £799 or £149/mo'),

                            Forms\Components\TextInput::make('link')
                                ->label('Link URL')
                                ->type('text')
                                ->rules(['nullable', 'string', 'max:255', 'regex:/^(https?:\/\/|\/)/'])
                                ->maxLength(255)
                                ->helperText('Relative path (e.g. /services/seo) or full URL.')
                                ->nullable(),

                            Forms\Components\TextInput::make('slug')
                                ->label('Slug')
                                ->helperText('Auto-generated from English title on creation.')
                                ->maxLength(100)
                                ->unique(ignoreRecord: true)
                                ->nullable(),

                            Forms\Components\TextInput::make('cta_url')
                                ->label('Custom CTA URL')
                                ->type('text')
                                ->rules(['nullable', 'string', 'max:255'])
                                ->maxLength(255)
                                ->helperText('Override CTA destination, e.g. /contact or /order. Leave empty to use default.')
                                ->nullable(),

                            Forms\Components\Toggle::make('is_featured')
                                ->label('Featured (show on homepage)')
                                ->inline(false)
                                ->default(true),

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
                Tables\Columns\TextColumn::make('icon')
                    ->label('Icon')
                    ->badge()
                    ->color('gray'),

                Tables\Columns\TextColumn::make('title_en_display')
                    ->label('Title (EN)')
                    ->getStateUsing(fn (ServiceItem $item): string => $item->getTranslation('title', 'en') ?? '')
                    ->searchable(query: function ($query, string $value) {
                        $query->whereRaw("JSON_UNQUOTE(JSON_EXTRACT(title, '$.en')) LIKE ?", ["%{$value}%"]);
                    })
                    ->limit(45),

                Tables\Columns\TextColumn::make('price_from')
                    ->label('Price From')
                    ->sortable(),

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
            'index'  => Pages\ListServiceItems::route('/'),
            'create' => Pages\CreateServiceItem::route('/create'),
            'edit'   => Pages\EditServiceItem::route('/{record}/edit'),
        ];
    }
}
