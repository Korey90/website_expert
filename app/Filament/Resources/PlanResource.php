<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PlanResource\Pages;
use App\Filament\Support\Currency as FilamentCurrency;
use App\Models\Plan;
use App\Models\PlanPrice;
use App\Services\Billing\PlanService;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms;
use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;

class PlanResource extends BaseResource
{
    protected static ?string $model = Plan::class;

    protected static \BackedEnum|string|null $navigationIcon = 'heroicon-o-tag';

    protected static \UnitEnum|string|null $navigationGroup = 'SaaS Billing';

    protected static ?int $navigationSort = 1;

    protected static ?string $label = 'Plan';

    protected static ?string $pluralLabel = 'Plans';

    // -------------------------------------------------------------------------
    // Form (Create / Edit)
    // -------------------------------------------------------------------------

    public static function form(Schema $schema): Schema
    {
        return $schema->schema([
            Section::make('Plan Identity')
                ->columns(2)
                ->schema([
                    Forms\Components\TextInput::make('slug')
                        ->label('Slug')
                        ->required()
                        ->maxLength(50)
                        ->unique(Plan::class, 'slug', ignoreRecord: true)
                        ->helperText('e.g. free, basic, pro, agency — used internally and in Stripe')
                        ->alphaDash(),

                    Forms\Components\TextInput::make('name')
                        ->label('Display Name')
                        ->required()
                        ->maxLength(100),

                    Forms\Components\Textarea::make('description')
                        ->label('Short Description')
                        ->maxLength(255)
                        ->columnSpanFull(),

                    Forms\Components\Toggle::make('is_active')
                        ->label('Visible to users')
                        ->default(true),

                    Forms\Components\TextInput::make('sort_order')
                        ->label('Sort Order')
                        ->numeric()
                        ->default(0)
                        ->minValue(0),
                ]),

            Section::make('Legacy GBP Pricing')
                ->description('Fallback values kept for existing installs. New checkout uses the price book below.')
                ->columns(2)
                ->collapsed()
                ->schema([
                    Forms\Components\TextInput::make('price_monthly')
                        ->label('Monthly Price (pence)')
                        ->numeric()
                        ->default(0)
                        ->minValue(0)
                        ->suffix('p'),

                    Forms\Components\TextInput::make('price_yearly')
                        ->label('Yearly Price (pence)')
                        ->numeric()
                        ->default(0)
                        ->minValue(0)
                        ->suffix('p'),

                    Forms\Components\TextInput::make('stripe_price_id_monthly')
                        ->label('Stripe Price ID (Monthly)')
                        ->nullable()
                        ->maxLength(255)
                        ->placeholder('price_xxxxx'),

                    Forms\Components\TextInput::make('stripe_price_id_yearly')
                        ->label('Stripe Price ID (Yearly)')
                        ->nullable()
                        ->maxLength(255)
                        ->placeholder('price_xxxxx'),
                ]),

            Section::make('Price Book')
                ->description('Create one monthly/yearly price per supported currency. Stripe subscriptions require a Price ID for each paid currency.')
                ->schema([
                    Forms\Components\Repeater::make('planPrices')
                        ->relationship()
                        ->schema([
                            Forms\Components\Select::make('currency')
                                ->options(fn () => FilamentCurrency::options())
                                ->required(),

                            Forms\Components\Select::make('interval')
                                ->options([
                                    PlanPrice::INTERVAL_MONTHLY => 'Monthly',
                                    PlanPrice::INTERVAL_YEARLY => 'Yearly',
                                ])
                                ->required(),

                            Forms\Components\TextInput::make('amount_minor')
                                ->label('Amount (minor units)')
                                ->numeric()
                                ->minValue(0)
                                ->required()
                                ->helperText('Example: 2900 = 29.00 for GBP/EUR/PLN.'),

                            Forms\Components\TextInput::make('stripe_price_id')
                                ->label('Stripe Price ID')
                                ->nullable()
                                ->maxLength(255)
                                ->placeholder('price_xxxxx'),

                            Forms\Components\Toggle::make('is_active')
                                ->label('Active')
                                ->default(true),
                        ])
                        ->columns(5)
                        ->defaultItems(0)
                        ->addActionLabel('Add price'),
                ]),

            Section::make('Limits')
                ->columns(2)
                ->schema([
                    Forms\Components\TextInput::make('max_landing_pages')
                        ->label('Max Landing Pages')
                        ->numeric()
                        ->nullable()
                        ->minValue(0)
                        ->helperText('Leave blank for unlimited'),

                    Forms\Components\TextInput::make('max_ai_per_month')
                        ->label('Max AI Generations / Month')
                        ->numeric()
                        ->nullable()
                        ->minValue(0)
                        ->helperText('Leave blank for unlimited'),
                ]),

            Section::make('Features')
                ->columns(3)
                ->schema([
                    Forms\Components\Toggle::make('multi_user')
                        ->label('Multi-user access')
                        ->default(false),

                    Forms\Components\Toggle::make('custom_domain')
                        ->label('Custom Domain')
                        ->default(false),

                    Forms\Components\Toggle::make('ab_testing')
                        ->label('A/B Testing')
                        ->default(false),

                    Forms\Components\TagsInput::make('features')
                        ->label('Feature Bullet Points')
                        ->helperText('Shown on the pricing page. Press Enter after each feature.')
                        ->columnSpanFull(),
                ]),
        ]);
    }

    // -------------------------------------------------------------------------
    // Infolist (View)
    // -------------------------------------------------------------------------

    public static function infolist(Schema $schema): Schema
    {
        return $schema->schema([
            Section::make('Plan Details')
                ->columns(3)
                ->schema([
                    TextEntry::make('name')
                        ->label('Name')
                        ->weight('bold')
                        ->size('lg'),

                    TextEntry::make('slug')
                        ->label('Slug')
                        ->badge()
                        ->color('gray'),

                    IconEntry::make('is_active')
                        ->label('Active')
                        ->boolean(),

                    TextEntry::make('description')
                        ->label('Description')
                        ->columnSpanFull()
                        ->placeholder('—'),
                ]),

            Section::make('Pricing')
                ->columns(1)
                ->schema([
                    RepeatableEntry::make('planPrices')
                        ->label('Price Book')
                        ->schema([
                            TextEntry::make('currency')
                                ->badge()
                                ->color('gray'),

                            TextEntry::make('interval')
                                ->badge()
                                ->color('info'),

                            TextEntry::make('amount_minor')
                                ->label('Amount')
                                ->formatStateUsing(fn ($state, $record) => self::formatMinorAmount((int) $state, $record?->currency)),

                            TextEntry::make('stripe_price_id')
                                ->label('Stripe Price ID')
                                ->placeholder('—')
                                ->copyable(),

                            IconEntry::make('is_active')
                                ->label('Active')
                                ->boolean(),
                        ])
                        ->columns(5)
                        ->placeholder('No plan prices configured.'),
                ]),

            Section::make('Limits & Features')
                ->columns(3)
                ->schema([
                    TextEntry::make('max_landing_pages')
                        ->label('Landing Pages')
                        ->formatStateUsing(fn ($state) => $state === null ? 'Unlimited' : $state),

                    TextEntry::make('max_ai_per_month')
                        ->label('AI Generations / Month')
                        ->formatStateUsing(fn ($state) => $state === null ? 'Unlimited' : $state),

                    IconEntry::make('multi_user')
                        ->label('Multi-user')
                        ->boolean(),

                    IconEntry::make('custom_domain')
                        ->label('Custom Domain')
                        ->boolean(),

                    IconEntry::make('ab_testing')
                        ->label('A/B Testing')
                        ->boolean(),

                    TextEntry::make('features')
                        ->label('Feature Bullets')
                        ->listWithLineBreaks()
                        ->placeholder('—')
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
                    ->sortable(),

                Tables\Columns\TextColumn::make('name')
                    ->label('Plan')
                    ->description(fn ($record) => $record->description)
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('slug')
                    ->label('Slug')
                    ->badge()
                    ->color('gray'),

                Tables\Columns\TextColumn::make('monthly_price')
                    ->label('Monthly')
                    ->state(fn (Plan $record) => self::formatPlanPrice($record, PlanPrice::INTERVAL_MONTHLY)),

                Tables\Columns\TextColumn::make('max_landing_pages')
                    ->label('Max LPs')
                    ->formatStateUsing(fn ($state) => $state === null ? '∞' : $state)
                    ->alignCenter(),

                Tables\Columns\TextColumn::make('max_ai_per_month')
                    ->label('Max AI/mo')
                    ->formatStateUsing(fn ($state) => $state === null ? '∞' : $state)
                    ->alignCenter(),

                Tables\Columns\IconColumn::make('multi_user')
                    ->label('Multi-user')
                    ->boolean()
                    ->alignCenter(),

                Tables\Columns\IconColumn::make('custom_domain')
                    ->label('Custom Domain')
                    ->boolean()
                    ->alignCenter(),

                Tables\Columns\IconColumn::make('ab_testing')
                    ->label('A/B Test')
                    ->boolean()
                    ->alignCenter(),

                Tables\Columns\IconColumn::make('is_active')
                    ->label('Active')
                    ->boolean()
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Active'),
            ])
            ->actions([
                ViewAction::make(),
                EditAction::make(),
                DeleteAction::make(),
            ]);
    }

    // -------------------------------------------------------------------------
    // Pages
    // -------------------------------------------------------------------------

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPlans::route('/'),
            'create' => Pages\CreatePlan::route('/create'),
            'view' => Pages\ViewPlan::route('/{record}'),
            'edit' => Pages\EditPlan::route('/{record}/edit'),
        ];
    }

    private static function formatPlanPrice(Plan $plan, string $interval): string
    {
        $price = PlanService::priceForPlan($plan, FilamentCurrency::default(), $interval);
        $currency = $price?->currency ?? FilamentCurrency::default();
        $amount = $price?->amount_minor ?? (
            $interval === PlanPrice::INTERVAL_MONTHLY
                ? (int) $plan->price_monthly
                : (int) $plan->price_yearly
        );

        return self::formatMinorAmount($amount, $currency);
    }

    private static function formatMinorAmount(int $amount, ?string $currency = null): string
    {
        $currency = $currency ?: FilamentCurrency::default();

        if ($amount <= 0) {
            return 'Free';
        }

        $minorUnit = (int) (config("currencies.supported.{$currency}.minor_unit", 100));

        return FilamentCurrency::format($amount / max(1, $minorUnit), $currency);
    }
}
