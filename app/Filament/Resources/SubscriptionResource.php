<?php

namespace App\Filament\Resources;

use App\Filament\Resources\SubscriptionResource\Pages;
use App\Models\Business;
use App\Services\Billing\PlanService;
use Filament\Actions\Action;
use Filament\Actions\ViewAction;
use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class SubscriptionResource extends BaseResource
{
    protected static ?string $model = Business::class;
    protected static \BackedEnum|string|null $navigationIcon = 'heroicon-o-credit-card';
    protected static \UnitEnum|string|null $navigationGroup = 'SaaS Billing';
    protected static ?int $navigationSort = 3;
    protected static ?string $label = 'Subscription';
    protected static ?string $pluralLabel = 'Stripe Subscriptions';

    // Show only businesses that have ever had a Stripe subscription
    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->whereNotNull('stripe_subscription_status');
    }

    // -------------------------------------------------------------------------
    // Infolist (View — pełne dane Stripe)
    // -------------------------------------------------------------------------

    public static function infolist(Schema $schema): Schema
    {
        return $schema->schema([
            Section::make('Stripe Subscription')
                ->columns(2)
                ->schema([
                    TextEntry::make('stripe_subscription_status')
                        ->label('Stripe Status')
                        ->badge()
                        ->color(fn ($state) => match ($state) {
                            'active'            => 'success',
                            'trialing'          => 'info',
                            'past_due'          => 'warning',
                            'canceled', 'ended' => 'danger',
                            default             => 'gray',
                        }),

                    TextEntry::make('plan')
                        ->label('Plan')
                        ->badge()
                        ->color(fn ($state) => match ($state) {
                            'free'   => 'gray',
                            'basic'  => 'warning',
                            'pro'    => 'info',
                            'agency' => 'success',
                            default  => 'gray',
                        })
                        ->formatStateUsing(fn ($state) => PlanService::PLANS[$state]['name'] ?? ucfirst((string) $state)),

                    TextEntry::make('stripe_subscription_id')
                        ->label('Stripe Subscription ID')
                        ->placeholder('—')
                        ->copyable()
                        ->columnSpanFull(),

                    TextEntry::make('stripe_customer_id')
                        ->label('Stripe Customer ID')
                        ->placeholder('—')
                        ->copyable(),

                    TextEntry::make('trial_ends_at')
                        ->label('Trial Ends At')
                        ->dateTime()
                        ->placeholder('No trial'),
                ]),

            Section::make('Tenant')
                ->columns(3)
                ->schema([
                    TextEntry::make('name')
                        ->label('Business Name')
                        ->weight('bold'),

                    TextEntry::make('slug')
                        ->label('Slug'),

                    IconEntry::make('is_active')
                        ->label('Account Active')
                        ->boolean(),

                    TextEntry::make('created_at')
                        ->label('Registered')
                        ->dateTime(),

                    TextEntry::make('updated_at')
                        ->label('Last Updated')
                        ->dateTime(),
                ]),
        ]);
    }

    // -------------------------------------------------------------------------
    // Table — wyłącznie dane billingowe
    // -------------------------------------------------------------------------

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('updated_at', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Business')
                    ->description(fn ($record) => $record->slug)
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('stripe_subscription_status')
                    ->label('Stripe Status')
                    ->badge()
                    ->color(fn ($state) => match ($state) {
                        'active'            => 'success',
                        'trialing'          => 'info',
                        'past_due'          => 'warning',
                        'canceled', 'ended' => 'danger',
                        default             => 'gray',
                    })
                    ->formatStateUsing(fn ($state) => $state ? ucfirst(str_replace('_', ' ', (string) $state)) : 'None')
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

                Tables\Columns\TextColumn::make('stripe_subscription_id')
                    ->label('Stripe Sub ID')
                    ->searchable()
                    ->placeholder('—')
                    ->copyable()
                    ->limit(20)
                    ->tooltip(fn ($record) => $record->stripe_subscription_id),

                Tables\Columns\TextColumn::make('stripe_customer_id')
                    ->label('Stripe Customer ID')
                    ->searchable()
                    ->placeholder('—')
                    ->copyable()
                    ->limit(20)
                    ->tooltip(fn ($record) => $record->stripe_customer_id)
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('trial_ends_at')
                    ->label('Trial Ends')
                    ->date()
                    ->placeholder('—')
                    ->sortable(),

                Tables\Columns\TextColumn::make('updated_at')
                    ->label('Last Event')
                    ->date()
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('stripe_subscription_status')
                    ->label('Stripe Status')
                    ->options([
                        'active'    => 'Active',
                        'trialing'  => 'Trialing',
                        'past_due'  => 'Past Due',
                        'canceled'  => 'Canceled',
                        'ended'     => 'Ended',
                    ]),

                Tables\Filters\SelectFilter::make('plan')
                    ->options(
                        collect(PlanService::PLANS)
                            ->mapWithKeys(fn ($v, $k) => [$k => $v['name']])
                            ->toArray()
                    ),
            ])
            ->actions([
                ViewAction::make(),

                Action::make('stripe_dashboard')
                    ->label('Stripe')
                    ->icon('heroicon-o-arrow-top-right-on-square')
                    ->color('gray')
                    ->url(fn ($record) => $record->stripe_customer_id
                        ? "https://dashboard.stripe.com/customers/{$record->stripe_customer_id}"
                        : null)
                    ->openUrlInNewTab()
                    ->visible(fn ($record) => (bool) $record->stripe_customer_id),
            ])
            ->bulkActions([]);
    }

    // -------------------------------------------------------------------------
    // Pages
    // -------------------------------------------------------------------------

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListSubscriptions::route('/'),
            'view'  => Pages\ViewSubscription::route('/{record}'),
        ];
    }

    // -------------------------------------------------------------------------
    // Access
    // -------------------------------------------------------------------------

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canDelete(\Illuminate\Database\Eloquent\Model $record): bool
    {
        return false;
    }
}
