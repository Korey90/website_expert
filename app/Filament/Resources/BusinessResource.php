<?php

namespace App\Filament\Resources;

use App\Filament\Resources\BusinessResource\Pages;
use App\Models\Business;
use App\Services\Billing\PlanService;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms;
use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;

class BusinessResource extends Resource
{
    protected static ?string $model = Business::class;
    protected static \BackedEnum|string|null $navigationIcon = 'heroicon-o-building-storefront';
    protected static \UnitEnum|string|null $navigationGroup = 'SaaS Billing';
    protected static ?int $navigationSort = 10;
    protected static ?string $label = 'Business';
    protected static ?string $pluralLabel = 'Businesses';

    // -------------------------------------------------------------------------
    // Form (Edit)
    // -------------------------------------------------------------------------

    public static function form(Schema $schema): Schema
    {
        return $schema->schema([
            Section::make('Business Details')
                ->columns(2)
                ->schema([
                    Forms\Components\TextInput::make('name')
                        ->label('Business Name')
                        ->required()
                        ->maxLength(255),

                    Forms\Components\TextInput::make('slug')
                        ->label('Slug')
                        ->required()
                        ->maxLength(255),

                    Forms\Components\Select::make('locale')
                        ->label('Locale')
                        ->options(['en' => 'English', 'pl' => 'Polski', 'pt' => 'Português'])
                        ->default('en'),

                    Forms\Components\Select::make('timezone')
                        ->label('Timezone')
                        ->options(
                            collect(\DateTimeZone::listIdentifiers())->mapWithKeys(fn ($tz) => [$tz => $tz])->toArray()
                        )
                        ->searchable()
                        ->default('UTC'),

                    Forms\Components\Toggle::make('is_active')
                        ->label('Account Active'),
                ]),

            Section::make('Plan & Billing')
                ->columns(2)
                ->schema([
                    Forms\Components\Select::make('plan')
                        ->label('Plan')
                        ->options(
                            collect(PlanService::PLANS)
                                ->mapWithKeys(fn ($v, $k) => [$k => $v['name']])
                                ->toArray()
                        )
                        ->default('free')
                        ->required(),

                    Forms\Components\DateTimePicker::make('trial_ends_at')
                        ->label('Trial Ends At')
                        ->nullable(),

                    Forms\Components\TextInput::make('stripe_customer_id')
                        ->label('Stripe Customer ID')
                        ->nullable()
                        ->maxLength(255),

                    Forms\Components\TextInput::make('stripe_subscription_id')
                        ->label('Stripe Subscription ID')
                        ->nullable()
                        ->maxLength(255),

                    Forms\Components\Select::make('stripe_subscription_status')
                        ->label('Stripe Subscription Status')
                        ->options([
                            'active'    => 'Active',
                            'trialing'  => 'Trialing',
                            'past_due'  => 'Past Due',
                            'canceled'  => 'Canceled',
                            'ended'     => 'Ended',
                        ])
                        ->nullable(),
                ]),
        ]);
    }

    // -------------------------------------------------------------------------
    // Infolist (View)
    // -------------------------------------------------------------------------

    public static function infolist(Schema $schema): Schema
    {
        return $schema->schema([
            Section::make('Business Details')
                ->columns(3)
                ->schema([
                    TextEntry::make('name')
                        ->label('Name')
                        ->weight('bold')
                        ->size('lg')
                        ->columnSpan(2),

                    IconEntry::make('is_active')
                        ->label('Active')
                        ->boolean(),

                    TextEntry::make('slug')
                        ->label('Slug'),

                    TextEntry::make('locale')
                        ->label('Locale')
                        ->badge()
                        ->color('gray'),

                    TextEntry::make('timezone')
                        ->label('Timezone'),
                ]),

            Section::make('Plan & Billing')
                ->columns(2)
                ->schema([
                    TextEntry::make('plan')
                        ->label('Plan')
                        ->badge()
                        ->color(fn ($state) => match ($state) {
                            'free'   => 'gray',
                            'pro'    => 'info',
                            'agency' => 'success',
                            default  => 'gray',
                        })
                        ->formatStateUsing(fn ($state) => PlanService::PLANS[$state]['name'] ?? ucfirst((string) $state)),

                    TextEntry::make('stripe_subscription_status')
                        ->label('Stripe Status')
                        ->badge()
                        ->color(fn ($state) => match ($state) {
                            'active'            => 'success',
                            'trialing'          => 'info',
                            'past_due'          => 'warning',
                            'canceled', 'ended' => 'danger',
                            default             => 'gray',
                        })
                        ->formatStateUsing(fn ($state) => $state ? ucfirst((string) $state) : 'None'),

                    TextEntry::make('stripe_customer_id')
                        ->label('Stripe Customer ID')
                        ->placeholder('—')
                        ->copyable(),

                    TextEntry::make('stripe_subscription_id')
                        ->label('Stripe Subscription ID')
                        ->placeholder('—')
                        ->copyable(),

                    TextEntry::make('trial_ends_at')
                        ->label('Trial Ends At')
                        ->dateTime()
                        ->placeholder('No trial'),
                ]),

            Section::make('Meta')
                ->columns(2)
                ->schema([
                    TextEntry::make('created_at')
                        ->label('Created')
                        ->dateTime(),

                    TextEntry::make('updated_at')
                        ->label('Updated')
                        ->dateTime(),
                ]),
        ]);
    }

    // -------------------------------------------------------------------------
    // Table — zarządzanie tenantami (bez duplikowania danych Stripe)
    // -------------------------------------------------------------------------

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Business')
                    ->description(fn ($record) => $record->slug)
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('locale')
                    ->label('Locale')
                    ->badge()
                    ->color('gray')
                    ->sortable(),

                Tables\Columns\TextColumn::make('plan')
                    ->label('Plan')
                    ->badge()
                    ->color(fn ($state) => match ($state) {
                        'free'   => 'gray',
                        'basic'  => 'warning',
                        'pro'    => 'info',
                        'agency' => 'success',
                        default  => 'gray',
                    })
                    ->formatStateUsing(fn ($state) => PlanService::PLANS[$state]['name'] ?? ucfirst((string) $state))
                    ->sortable(),

                Tables\Columns\TextColumn::make('landingPages_count')
                    ->label('LPs')
                    ->counts('landingPages')
                    ->sortable(),

                Tables\Columns\IconColumn::make('is_active')
                    ->label('Active')
                    ->boolean()
                    ->sortable(),

                Tables\Columns\TextColumn::make('trial_ends_at')
                    ->label('Trial Ends')
                    ->date()
                    ->placeholder('—')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Registered')
                    ->date()
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('plan')
                    ->options(
                        collect(PlanService::PLANS)
                            ->mapWithKeys(fn ($v, $k) => [$k => $v['name']])
                            ->toArray()
                    ),

                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Account Active'),

                Tables\Filters\SelectFilter::make('locale')
                    ->options(['en' => 'English', 'pl' => 'Polski', 'pt' => 'Português']),
            ])
            ->actions([
                ViewAction::make(),
                EditAction::make(),
            ]);
    }

    // -------------------------------------------------------------------------
    // Pages
    // -------------------------------------------------------------------------

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListBusinesses::route('/'),
            'view'   => Pages\ViewBusiness::route('/{record}'),
            'edit'   => Pages\EditBusiness::route('/{record}/edit'),
        ];
    }
}
