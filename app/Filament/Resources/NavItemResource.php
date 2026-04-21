<?php

namespace App\Filament\Resources;

use App\Filament\Resources\NavItemResource\Pages;
use App\Models\NavItem;
use Filament\Forms;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Actions\EditAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;

class NavItemResource extends BaseResource
{
    protected static ?string $model = NavItem::class;
    protected static \BackedEnum|string|null $navigationIcon = 'heroicon-o-bars-3';
    protected static \UnitEnum|string|null $navigationGroup = 'Marketing';
    protected static ?string $navigationLabel = 'Navigation Menu';
    protected static ?string $label = 'Menu Item';
    protected static ?string $pluralLabel = 'Navigation Menu';
    protected static ?int $navigationSort = 0;
    protected static bool $shouldRegisterNavigation = false;

    // -------------------------------------------------------------------------
    // Form
    // -------------------------------------------------------------------------

    public static function form(Schema $form): Schema
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

        return $form->schema([
            Section::make('Link')
                ->columns(2)
                ->schema([
                    Forms\Components\TextInput::make('href')
                        ->label('URL / hash')
                        ->placeholder('#about')
                        ->required()
                        ->maxLength(200)
                        ->helperText('Anchor (e.g. #about), internal path (/portfolio) or full URL (https://…)'),

                    Forms\Components\TextInput::make('section_key')
                        ->label('Section Key')
                        ->placeholder('about')
                        ->maxLength(100)
                        ->nullable()
                        ->helperText('DOM id of the target section (without #). Used to highlight active link on scroll. Leave empty for external links.'),

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
        ]);
    }

    // -------------------------------------------------------------------------
    // Table
    // -------------------------------------------------------------------------

    public static function table(Table $table): Table
    {
        return $table
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
                    })
                    ->searchable(query: fn ($query, string $value) => $query->whereRaw(
                        "JSON_UNQUOTE(JSON_EXTRACT(label, '$.pl')) LIKE ?",
                        ["%{$value}%"]
                    )),

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
            ->actions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->bulkActions([
                DeleteBulkAction::make(),
            ])
            ->emptyStateHeading('No menu items yet')
            ->emptyStateDescription('Add your first navigation link using the button above.');
    }

    // -------------------------------------------------------------------------
    // Pages
    // -------------------------------------------------------------------------

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListNavItems::route('/'),
            'create' => Pages\CreateNavItem::route('/create'),
            'edit'   => Pages\EditNavItem::route('/{record}/edit'),
        ];
    }
}
