<?php

namespace App\Filament\Resources;

use App\Filament\Resources\DomainOrderResource\Pages;
use App\Filament\Support\Currency as FilamentCurrency;
use App\Filament\Support\FilamentPermissionRegistry;
use App\Models\DomainOrder;
use App\Scopes\BusinessScope;
use App\Support\PermissionHelper;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\ViewAction;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class DomainOrderResource extends BaseResource
{
    protected static ?string $model = DomainOrder::class;

    protected static \BackedEnum|string|null $navigationIcon = 'heroicon-o-shopping-cart';

    protected static \UnitEnum|string|null $navigationGroup = 'Domains';

    protected static ?string $navigationLabel = 'Domain Orders';

    protected static ?int $navigationSort = 2;

    public static function infolist(Schema $schema): Schema
    {
        return $schema->schema([
            Section::make('Order Details')
                ->columns(3)
                ->schema([
                    TextEntry::make('full_domain')
                        ->label('Domain')
                        ->weight('bold')
                        ->size('lg')
                        ->columnSpan(2),

                    TextEntry::make('status')
                        ->label('Status')
                        ->badge()
                        ->color(fn ($state) => match ($state) {
                            'pending_payment' => 'warning',
                            'paid' => 'info',
                            'registering' => 'info',
                            'completed' => 'success',
                            'failed' => 'danger',
                            'cancelled' => 'gray',
                            default => 'gray',
                        }),

                    TextEntry::make('action')
                        ->label('Action')
                        ->badge()
                        ->color(fn ($state) => match ($state) {
                            'register' => 'primary',
                            'renew' => 'info',
                            'transfer' => 'warning',
                            default => 'gray',
                        }),

                    TextEntry::make('years')
                        ->label('Years'),

                    TextEntry::make('provider')
                        ->label('Provider')
                        ->placeholder('—'),

                    TextEntry::make('retail_price')
                        ->label('Retail Price')
                        ->money(fn ($record) => FilamentCurrency::tableCurrency($record)),

                    TextEntry::make('wholesale_price')
                        ->label('Wholesale Price')
                        ->money(fn ($record) => FilamentCurrency::tableCurrency($record))
                        ->placeholder('—'),

                    TextEntry::make('currency')
                        ->label('Currency')
                        ->badge()
                        ->color('gray'),

                    TextEntry::make('stripe_payment_intent_id')
                        ->label('Stripe Payment Intent')
                        ->copyable()
                        ->placeholder('—'),

                    TextEntry::make('created_at')
                        ->label('Created')
                        ->dateTime('d M Y, H:i'),

                    TextEntry::make('completed_at')
                        ->label('Completed')
                        ->dateTime('d M Y, H:i')
                        ->placeholder('—'),
                ]),

            Section::make('Client')
                ->columns(2)
                ->schema([
                    TextEntry::make('client.company_name')
                        ->label('Company')
                        ->placeholder('—'),

                    TextEntry::make('client.primary_contact_email')
                        ->label('Email')
                        ->copyable()
                        ->placeholder('—'),

                    TextEntry::make('createdBy.name')
                        ->label('Created By')
                        ->placeholder('—'),

                    TextEntry::make('business.company_name')
                        ->label('Business (Tenant)')
                        ->placeholder('—'),
                ]),

            Section::make('Registered Domain')
                ->columns(3)
                ->schema([
                    TextEntry::make('domain.status')
                        ->label('Domain Status')
                        ->badge()
                        ->color(fn ($state) => match ($state) {
                            'active' => 'success',
                            'pending' => 'warning',
                            'expired' => 'danger',
                            'transferred' => 'info',
                            'cancelled' => 'gray',
                            default => 'gray',
                        })
                        ->placeholder('Not yet registered'),

                    TextEntry::make('domain.registered_at')
                        ->label('Registered')
                        ->date()
                        ->placeholder('—'),

                    TextEntry::make('domain.expires_at')
                        ->label('Expires')
                        ->date()
                        ->placeholder('—'),

                    TextEntry::make('domain.provider_domain_id')
                        ->label('Provider ID')
                        ->copyable()
                        ->placeholder('—'),

                    TextEntry::make('domain.auto_renew')
                        ->label('Auto-Renew')
                        ->badge()
                        ->formatStateUsing(fn ($state) => $state ? 'Yes' : 'No')
                        ->color(fn ($state) => $state ? 'success' : 'warning')
                        ->placeholder('—'),
                ]),

            Section::make('Notes')
                ->columns(2)
                ->schema([
                    TextEntry::make('notes')
                        ->label('Customer Notes')
                        ->prose()
                        ->placeholder('—')
                        ->columnSpanFull(),

                    TextEntry::make('admin_notes')
                        ->label('Admin Notes')
                        ->prose()
                        ->placeholder('—')
                        ->columnSpanFull(),
                ]),

            Section::make('Event Log')
                ->collapsed()
                ->schema([
                    RepeatableEntry::make('events')
                        ->label('')
                        ->schema([
                            TextEntry::make('type')
                                ->label('Type')
                                ->badge()
                                ->color('info'),

                            TextEntry::make('description')
                                ->label('Description'),

                            TextEntry::make('user.name')
                                ->label('By')
                                ->placeholder('System'),

                            TextEntry::make('created_at')
                                ->label('Date')
                                ->dateTime('d M Y, H:i'),
                        ])
                        ->columns(4)
                        ->columnSpanFull(),
                ]),

            Section::make('Linked Documents')
                ->collapsed()
                ->schema([
                    RepeatableEntry::make('invoices')
                        ->label('Invoices')
                        ->schema([
                            TextEntry::make('number')
                                ->label('Invoice No.')
                                ->weight('bold')
                                ->url(fn ($record) => $record
                                    ? InvoiceResource::getUrl('view', ['record' => $record->id])
                                    : null),
                            TextEntry::make('status')
                                ->label('Status')
                                ->badge()
                                ->color(fn ($state) => match ($state) {
                                    'draft' => 'gray',
                                    'sent' => 'info',
                                    'partially_paid' => 'warning',
                                    'paid' => 'success',
                                    'overdue' => 'danger',
                                    'cancelled' => 'danger',
                                    default => 'gray',
                                }),
                            TextEntry::make('total')
                                ->label('Total')
                                ->money(fn ($record) => FilamentCurrency::tableCurrency($record)),
                            TextEntry::make('due_date')
                                ->label('Due')
                                ->date(),
                        ])
                        ->columns(4)
                        ->columnSpanFull(),

                    RepeatableEntry::make('quotes')
                        ->label('Quotes')
                        ->schema([
                            TextEntry::make('number')
                                ->label('Quote No.')
                                ->weight('bold')
                                ->url(fn ($record) => $record
                                    ? QuoteResource::getUrl('view', ['record' => $record->id])
                                    : null),
                            TextEntry::make('status')
                                ->label('Status')
                                ->badge()
                                ->color(fn ($state) => match ($state) {
                                    'draft' => 'gray',
                                    'sent' => 'info',
                                    'accepted' => 'success',
                                    'rejected' => 'danger',
                                    'expired' => 'warning',
                                    default => 'gray',
                                }),
                            TextEntry::make('total')
                                ->label('Total')
                                ->money(fn ($record) => FilamentCurrency::tableCurrency($record)),
                            TextEntry::make('valid_until')
                                ->label('Valid Until')
                                ->date(),
                        ])
                        ->columns(4)
                        ->columnSpanFull(),

                    RepeatableEntry::make('projects')
                        ->label('Projects')
                        ->schema([
                            TextEntry::make('title')
                                ->label('Title')
                                ->weight('bold')
                                ->url(fn ($record) => $record
                                    ? ProjectResource::getUrl('view', ['record' => $record->id])
                                    : null),
                            TextEntry::make('status')
                                ->label('Status')
                                ->badge()
                                ->color(fn ($state) => match ($state) {
                                    'draft' => 'gray',
                                    'active' => 'success',
                                    'on_hold' => 'warning',
                                    'completed' => 'info',
                                    'cancelled' => 'danger',
                                    default => 'gray',
                                }),
                            TextEntry::make('service_type')
                                ->label('Service')
                                ->placeholder('—'),
                            TextEntry::make('deadline')
                                ->label('Deadline')
                                ->date()
                                ->placeholder('—'),
                        ])
                        ->columns(4)
                        ->columnSpanFull(),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('full_domain')
                    ->label('Domain')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),

                Tables\Columns\TextColumn::make('client.company_name')
                    ->label('Client')
                    ->searchable()
                    ->sortable()
                    ->placeholder('—'),

                Tables\Columns\TextColumn::make('action')
                    ->label('Action')
                    ->badge()
                    ->color(fn ($state) => match ($state) {
                        'register' => 'primary',
                        'renew' => 'info',
                        'transfer' => 'warning',
                        default => 'gray',
                    }),

                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn ($state) => match ($state) {
                        'pending_payment' => 'warning',
                        'paid' => 'info',
                        'registering' => 'info',
                        'completed' => 'success',
                        'failed' => 'danger',
                        'cancelled' => 'gray',
                        default => 'gray',
                    }),

                Tables\Columns\TextColumn::make('retail_price')
                    ->label('Price')
                    ->money(fn ($record) => FilamentCurrency::tableCurrency($record))
                    ->sortable(),

                Tables\Columns\TextColumn::make('years')
                    ->label('Yrs')
                    ->sortable(),

                Tables\Columns\TextColumn::make('business.company_name')
                    ->label('Business')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Created')
                    ->date()
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'pending_payment' => 'Pending Payment',
                        'paid' => 'Paid',
                        'registering' => 'Registering',
                        'completed' => 'Completed',
                        'failed' => 'Failed',
                        'cancelled' => 'Cancelled',
                    ]),

                Tables\Filters\SelectFilter::make('action')
                    ->options([
                        'register' => 'Register',
                        'renew' => 'Renew',
                        'transfer' => 'Transfer',
                    ]),
            ])
            ->actions([
                ViewAction::make(),
            ])
            ->bulkActions([
                BulkActionGroup::make([]),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListDomainOrders::route('/'),
            'view' => Pages\ViewDomainOrder::route('/{record}'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        $user = auth()->user();

        if (PermissionHelper::allows($user, FilamentPermissionRegistry::panelAccessPermission())) {
            return parent::getEloquentQuery()
                ->withoutGlobalScope(BusinessScope::class);
        }

        return parent::getEloquentQuery();
    }
}
