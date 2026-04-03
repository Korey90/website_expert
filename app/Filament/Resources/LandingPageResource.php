<?php

namespace App\Filament\Resources;

use App\Filament\Resources\LandingPageResource\Pages;
use App\Models\LandingPage;
use Filament\Forms;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class LandingPageResource extends Resource
{
    protected static ?string $model = LandingPage::class;
    protected static \BackedEnum|string|null $navigationIcon = 'heroicon-o-globe-alt';
    protected static \UnitEnum|string|null $navigationGroup = 'Marketing';
    protected static ?int $navigationSort = 1;
    protected static ?string $label = 'Landing Page';
    protected static ?string $pluralLabel = 'Landing Pages';

    // -------------------------------------------------------------------------
    // Form
    // -------------------------------------------------------------------------

    public static function form(Schema $form): Schema
    {
        return $form->schema([
            Section::make('Details')
                ->columns(2)
                ->schema([
                    Forms\Components\TextInput::make('title')
                        ->required()
                        ->maxLength(255)
                        ->columnSpanFull(),

                    Forms\Components\TextInput::make('slug')
                        ->prefix('lp/')
                        ->helperText('Leave blank to auto-generate from title.')
                        ->maxLength(255)
                        ->regex('/^[a-z0-9]+(?:-[a-z0-9]+)*$/')
                        ->nullable(),

                    Forms\Components\Select::make('language')
                        ->options(['en' => 'English', 'pl' => 'Polski', 'pt' => 'Português'])
                        ->default('en')
                        ->required(),

                    Forms\Components\Select::make('status')
                        ->options([
                            LandingPage::STATUS_DRAFT     => 'Draft',
                            LandingPage::STATUS_PUBLISHED => 'Published',
                            LandingPage::STATUS_ARCHIVED  => 'Archived',
                        ])
                        ->default(LandingPage::STATUS_DRAFT)
                        ->required(),

                    Forms\Components\Select::make('template_key')
                        ->label('Template')
                        ->options(collect(config('landing_pages.templates', []))
                            ->mapWithKeys(fn ($t, $k) => [$k => $t['label']]))
                        ->nullable(),

                    Forms\Components\Select::make('conversion_goal')
                        ->label('Conversion Goal')
                        ->options(config('landing_pages.conversion_goals', []))
                        ->nullable(),

                    Forms\Components\Textarea::make('description')
                        ->rows(2)
                        ->maxLength(1000)
                        ->columnSpanFull(),
                ]),

            Section::make('SEO')
                ->collapsed()
                ->columns(1)
                ->schema([
                    Forms\Components\TextInput::make('meta_title')->maxLength(255),
                    Forms\Components\Textarea::make('meta_description')->rows(2)->maxLength(500),
                ]),

            Section::make('Custom CSS')
                ->collapsed()
                ->schema([
                    Forms\Components\Textarea::make('custom_css')->rows(6)->maxLength(10000)->columnSpanFull(),
                ]),
        ]);
    }

    // -------------------------------------------------------------------------
    // Table
    // -------------------------------------------------------------------------

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('title')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('slug')
                    ->prefix('lp/')
                    ->searchable(),

                Tables\Columns\BadgeColumn::make('status')
                    ->colors([
                        'gray'    => LandingPage::STATUS_DRAFT,
                        'success' => LandingPage::STATUS_PUBLISHED,
                        'warning' => LandingPage::STATUS_ARCHIVED,
                    ])
                    ->sortable(),

                Tables\Columns\TextColumn::make('language')
                    ->badge()
                    ->sortable(),

                Tables\Columns\TextColumn::make('views_count')
                    ->label('Views')
                    ->sortable(),

                Tables\Columns\TextColumn::make('conversions_count')
                    ->label('Leads')
                    ->sortable(),

                Tables\Columns\TextColumn::make('published_at')
                    ->label('Published')
                    ->dateTime('d M Y')
                    ->sortable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime('d M Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        LandingPage::STATUS_DRAFT     => 'Draft',
                        LandingPage::STATUS_PUBLISHED => 'Published',
                        LandingPage::STATUS_ARCHIVED  => 'Archived',
                    ]),

                Tables\Filters\SelectFilter::make('language')
                    ->options(['en' => 'English', 'pl' => 'Polski', 'pt' => 'Português']),
            ])
            ->actions([
                ViewAction::make(),
                EditAction::make(),
            ])
            ->bulkActions([
                DeleteBulkAction::make(),
            ])
            ->defaultSort('created_at', 'desc');
    }

    // -------------------------------------------------------------------------
    // Query scope — limit to current tenant
    // -------------------------------------------------------------------------

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->when(currentBusiness(), fn ($q, $b) => $q->where('business_id', $b->id));
    }

    // -------------------------------------------------------------------------
    // Pages
    // -------------------------------------------------------------------------

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListLandingPages::route('/'),
            'create' => Pages\CreateLandingPage::route('/create'),
            'view'   => Pages\ViewLandingPage::route('/{record}'),
            'edit'   => Pages\EditLandingPage::route('/{record}/edit'),
        ];
    }
}
