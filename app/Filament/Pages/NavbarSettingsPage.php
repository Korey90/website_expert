<?php

namespace App\Filament\Pages;

use App\Models\NavItem;
use App\Models\Setting;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;

class NavbarSettingsPage extends BasePage implements HasTable
{
    use InteractsWithTable;

    protected string $view = 'filament.pages.navbar-settings';

    protected static ?string $slug = 'navbar';

    protected static \BackedEnum|string|null $navigationIcon  = 'heroicon-o-bars-3';
    protected static \UnitEnum|string|null   $navigationGroup = 'Marketing';
    protected static ?string $navigationLabel = 'Navigation Menu';
    protected static ?int    $navigationSort  = 0;

    /** @var array<string, mixed>|null */
    public ?array $data = [];

    public function mount(): void
    {
        $this->form->fill([
            'brand_name'          => Setting::get('nav_brand_name',          'WebsiteExpert'),
            'cta_href'            => Setting::get('nav_cta_href',            '#contact'),
            'cta_text_pl'         => Setting::get('nav_cta_text_pl',         'Bezpłatna wycena'),
            'cta_text_en'         => Setting::get('nav_cta_text_en',         'Free Quote'),
            'cta_text_pt'         => Setting::get('nav_cta_text_pt',         'Orçamento Gratuito'),
            'show_cta_button'     => (bool) Setting::get('nav_show_cta_button',     true),
            'show_lang_switcher'  => (bool) Setting::get('nav_show_lang_switcher',  true),
            'show_theme_toggle'   => (bool) Setting::get('nav_show_theme_toggle',   true),
            'show_client_portal'  => (bool) Setting::get('nav_show_client_portal',  true),
        ]);
    }

    // -------------------------------------------------------------------------
    // Settings form
    // -------------------------------------------------------------------------

    public function form(Schema $form): Schema
    {
        return $form
            ->schema([
                Section::make('Brand')
                    ->columns(2)
                    ->schema([
                        Forms\Components\TextInput::make('brand_name')
                            ->label('Brand Name')
                            ->placeholder('WebsiteExpert')
                            ->maxLength(60)
                            ->columnSpanFull(),
                    ]),

                Section::make('Visibility')
                    ->description('Show or hide individual navbar elements.')
                    ->columns(3)
                    ->schema([
                        Forms\Components\Toggle::make('show_lang_switcher')
                            ->label('Language Switcher')
                            ->default(true)
                            ->inline(false),
                        Forms\Components\Toggle::make('show_theme_toggle')
                            ->label('Dark/Light Mode Toggle')
                            ->default(true)
                            ->inline(false),
                        Forms\Components\Toggle::make('show_client_portal')
                            ->label('Client Portal Button')
                            ->default(true)
                            ->inline(false),
                    ]),

                Section::make('CTA Button')
                    ->description('Call-to-action button displayed on the right side of the navbar.')
                    ->columns(2)
                    ->headerActions([
                        \Filament\Actions\Action::make('toggle_cta_button')
                            ->label(fn () => ($this->data['show_cta_button'] ?? true) ? 'Enabled' : 'Disabled')
                            ->icon(fn () => ($this->data['show_cta_button'] ?? true) ? 'heroicon-o-eye' : 'heroicon-o-eye-slash')
                            ->color(fn () => ($this->data['show_cta_button'] ?? true) ? 'success' : 'gray')
                            ->size(\Filament\Support\Enums\Size::Small)
                            ->action(function () {
                                $this->data['show_cta_button'] = !($this->data['show_cta_button'] ?? true);
                            }),
                    ])
                    ->schema([
                        Forms\Components\Hidden::make('show_cta_button'),
                        Forms\Components\TextInput::make('cta_href')
                            ->label('CTA URL / hash')
                            ->placeholder('#contact')
                            ->maxLength(200),
                        Forms\Components\TextInput::make('cta_text_pl')
                            ->label('Button text (PL)')
                            ->placeholder('Bezpłatna wycena')
                            ->maxLength(60),
                        Forms\Components\TextInput::make('cta_text_en')
                            ->label('Button text (EN)')
                            ->placeholder('Free Quote')
                            ->maxLength(60),
                        Forms\Components\TextInput::make('cta_text_pt')
                            ->label('Button text (PT)')
                            ->placeholder('Orçamento Gratuito')
                            ->maxLength(60),
                    ]),
            ])
            ->statePath('data');
    }

    public function save(): void
    {
        $data = $this->form->getState();

        Setting::set('nav_brand_name',         $data['brand_name']         ?? 'WebsiteExpert', 'navbar');
        Setting::set('nav_cta_href',           $data['cta_href']           ?? '#contact',       'navbar');
        Setting::set('nav_cta_text_pl',        $data['cta_text_pl']        ?? '',               'navbar');
        Setting::set('nav_cta_text_en',        $data['cta_text_en']        ?? '',               'navbar');
        Setting::set('nav_cta_text_pt',        $data['cta_text_pt']        ?? '',               'navbar');
        Setting::set('nav_show_cta_button',    $data['show_cta_button']    ?? true,             'navbar');
        Setting::set('nav_show_lang_switcher', $data['show_lang_switcher'] ?? true,             'navbar');
        Setting::set('nav_show_theme_toggle',  $data['show_theme_toggle']  ?? true,             'navbar');
        Setting::set('nav_show_client_portal', $data['show_client_portal'] ?? true,             'navbar');

        Notification::make()
            ->title('Navbar settings saved')
            ->success()
            ->send();
    }

    // -------------------------------------------------------------------------
    // Nav Items table
    // -------------------------------------------------------------------------

    public function table(Table $table): Table
    {
        $locales = config('languages', ['pl' => 'Polski', 'en' => 'English', 'pt' => 'Português']);

        $labelTabs = [];
        foreach ($locales as $code => $langLabel) {
            $labelTabs[] = Tab::make($langLabel)
                ->schema([
                    Forms\Components\TextInput::make("label.{$code}")
                        ->label("Label ({$code})")
                        ->maxLength(60)
                        ->nullable(),
                ]);
        }

        $itemForm = [
            Section::make('Link')
                ->columns(2)
                ->schema([
                    Forms\Components\TextInput::make('href')
                        ->label('URL / hash')
                        ->placeholder('#about')
                        ->required()
                        ->maxLength(200)
                        ->helperText('Anchor (e.g. #about), internal path (/portfolio) or full URL'),

                    Forms\Components\Select::make('section_key')
                        ->label('Section Key')
                        ->placeholder('— none —')
                        ->nullable()
                        ->searchable()
                        ->options(function () {
                            return \App\Models\SiteSection::orderBy('sort_order')
                                ->pluck('key', 'key')
                                ->toArray();
                        })
                        ->helperText('DOM id of the section. Used for active-link highlighting on scroll.'),

                    Forms\Components\Toggle::make('is_active')
                        ->label('Visible in menu')
                        ->default(true)
                        ->inline(false),

                    Forms\Components\Toggle::make('open_in_new_tab')
                        ->label('Open in new tab')
                        ->default(false)
                        ->inline(false),
                ]),

            Section::make('Labels')
                ->schema([
                    Tabs::make('Translations')
                        ->tabs($labelTabs)
                        ->columnSpanFull(),
                ]),
        ];

        return $table
            ->query(NavItem::query())
            ->defaultSort('sort_order')
            ->reorderable('sort_order')
            ->columns([
                Tables\Columns\TextColumn::make('sort_order')
                    ->label('#')
                    ->sortable()
                    ->width('50px'),

                Tables\Columns\TextColumn::make('label_display')
                    ->label('Label')
                    ->getStateUsing(function (NavItem $record): string {
                        $locale = app()->getLocale();
                        return $record->getTranslation('label', $locale)
                            ?: $record->getTranslation('label', 'en')
                            ?: '—';
                    }),

                Tables\Columns\TextColumn::make('href')
                    ->label('URL')
                    ->limit(40),

                Tables\Columns\BadgeColumn::make('section_key')
                    ->label('Section Key')
                    ->color('info')
                    ->placeholder('—'),

                Tables\Columns\IconColumn::make('open_in_new_tab')
                    ->label('New Tab')
                    ->boolean(),

                Tables\Columns\ToggleColumn::make('is_active')
                    ->label('Active'),
            ])
            ->headerActions([
                CreateAction::make()
                    ->model(NavItem::class)
                    ->form($itemForm)
                    ->mutateFormDataUsing(fn (array $data) => array_merge(
                        $data,
                        ['sort_order' => (NavItem::max('sort_order') ?? 0) + 1]
                    ))
                    ->label('Add menu item'),
            ])
            ->actions([
                EditAction::make()
                    ->form($itemForm),
                DeleteAction::make(),
            ])
            ->bulkActions([
                DeleteBulkAction::make(),
            ])
            ->emptyStateHeading('No menu items yet')
            ->emptyStateDescription('Add your first navigation link using the button above.');
    }
}

