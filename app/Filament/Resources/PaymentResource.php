<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PaymentResource\Pages;
use App\Filament\Support\Currency as FilamentCurrency;
use App\Models\Invoice;
use App\Models\Payment;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\ViewAction;
use Filament\Forms;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;

class PaymentResource extends BaseResource
{
    protected static ?string $model = Payment::class;

    protected static \BackedEnum|string|null $navigationIcon = 'heroicon-o-credit-card';

    protected static \UnitEnum|string|null $navigationGroup = 'Finance';

    protected static ?string $navigationLabel = 'Payments';

    protected static ?int $navigationSort = 8;

    public static function form(Schema $form): Schema
    {
        return $form->schema([
            Section::make('Payment Details')
                ->columns(6)
                ->schema([
                    Forms\Components\Select::make('invoice_id')
                        ->label('Invoice')
                        ->options(Invoice::query()
                            ->whereIn('status', ['sent', 'partially_paid', 'overdue'])
                            ->where('amount_due', '>', 0)
                            ->pluck('number', 'id'))
                        ->searchable()
                        ->live()
                        ->afterStateUpdated(function (mixed $state, Set $set): void {
                            $invoice = $state ? Invoice::find($state) : null;

                            $set('currency', $invoice?->currency ?? FilamentCurrency::default());
                            $set('amount', $invoice?->amount_due);
                            $set('gbp_exchange_rate', $invoice?->gbp_exchange_rate);
                            $set('exchange_rate_source', $invoice && strtoupper((string) $invoice->currency) !== 'GBP' ? 'manual' : 'identity');
                            $set('exchange_rate_date', today()->toDateString());
                        })
                        ->required()
                        ->columnSpan(2),

                    Forms\Components\Select::make('method')
                        ->options([
                            'stripe' => 'Stripe',
                            'payu' => 'PayU',
                            'bank_transfer' => 'Bank Transfer',
                            'cash' => 'Cash',
                            'cheque' => 'Cheque',
                            'other' => 'Other',
                        ])
                        ->required()
                        ->columnSpan(2),

                    Forms\Components\TextInput::make('amount')
                        ->numeric()
                        ->prefix(fn () => FilamentCurrency::symbol())
                        ->required()
                        ->columnSpan(2),

                    Forms\Components\Select::make('currency')
                        ->options(fn () => FilamentCurrency::options())
                        ->default(fn () => FilamentCurrency::default())
                        ->disabled()
                        ->dehydrated()
                        ->required()
                        ->columnSpan(2),

                    Forms\Components\TextInput::make('gbp_exchange_rate')
                        ->label('Payment GBP exchange rate')
                        ->numeric()
                        ->minValue(0.00000001)
                        ->helperText('GBP value of one unit of the invoice currency at the payment date.')
                        ->visible(fn (Get $get): bool => strtoupper((string) $get('currency')) !== 'GBP')
                        ->required(fn (Get $get): bool => strtoupper((string) $get('currency')) !== 'GBP')
                        ->columnSpan(2),

                    Forms\Components\TextInput::make('exchange_rate_source')
                        ->label('Exchange-rate source')
                        ->default('manual')
                        ->visible(fn (Get $get): bool => strtoupper((string) $get('currency')) !== 'GBP')
                        ->required(fn (Get $get): bool => strtoupper((string) $get('currency')) !== 'GBP')
                        ->columnSpan(2),

                    Forms\Components\DatePicker::make('exchange_rate_date')
                        ->label('Exchange-rate date')
                        ->default(today())
                        ->visible(fn (Get $get): bool => strtoupper((string) $get('currency')) !== 'GBP')
                        ->required(fn (Get $get): bool => strtoupper((string) $get('currency')) !== 'GBP')
                        ->columnSpan(2),

                    Forms\Components\Select::make('status')
                        ->options([
                            'pending' => 'Pending',
                            'completed' => 'Completed',
                            'failed' => 'Failed',
                            'refunded' => 'Refunded',
                        ])
                        ->default('completed')
                        ->disabled()
                        ->dehydrated()
                        ->required()
                        ->columnSpan(2),

                    Forms\Components\DateTimePicker::make('paid_at')
                        ->label('Paid at')
                        ->default(now())
                        ->native(false)
                        ->columnSpan(2),

                    Forms\Components\TextInput::make('reference')
                        ->label('Reference / Transaction ID')
                        ->columnSpan(3),

                    Forms\Components\TextInput::make('stripe_payment_intent_id')
                        ->label('Stripe Payment Intent ID')
                        ->columnSpan(3),

                    Forms\Components\Textarea::make('notes')
                        ->rows(3)
                        ->columnSpan(3),
                ])->columnSpanFull(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('invoice.number')
                    ->label('Invoice')
                    ->searchable()
                    ->sortable()
                    ->url(fn (Payment $r) => $r->invoice_id
                        ? route('filament.admin.resources.invoices.view', $r->invoice_id)
                        : null),

                Tables\Columns\TextColumn::make('invoice.client.company_name')
                    ->label('Client')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('amount')
                    ->money(fn (Payment $r) => $r->currency ?? 'GBP')
                    ->sortable(),

                Tables\Columns\TextColumn::make('amount_gbp')
                    ->label('Cash value (GBP)')
                    ->money('GBP')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('realised_fx_gbp')
                    ->label('Realised FX (GBP)')
                    ->money('GBP')
                    ->color(fn (mixed $state): string => (float) $state > 0 ? 'success' : ((float) $state < 0 ? 'danger' : 'gray'))
                    ->sortable()
                    ->toggleable(),

                Tables\Columns\BadgeColumn::make('method')
                    ->colors([
                        'primary' => 'stripe',
                        'warning' => 'payu',
                        'info' => 'bank_transfer',
                        'success' => 'cash',
                        'gray' => ['cheque', 'other'],
                    ])
                    ->formatStateUsing(fn (string $state) => match ($state) {
                        'stripe' => 'Stripe',
                        'payu' => 'PayU',
                        'bank_transfer' => 'Bank Transfer',
                        'cash' => 'Cash',
                        'cheque' => 'Cheque',
                        default => ucfirst($state),
                    }),

                Tables\Columns\BadgeColumn::make('status')
                    ->colors([
                        'success' => 'completed',
                        'warning' => 'pending',
                        'danger' => 'failed',
                        'gray' => 'refunded',
                    ])
                    ->formatStateUsing(fn (string $state) => ucfirst($state)),

                Tables\Columns\TextColumn::make('paid_at')
                    ->label('Paid at')
                    ->dateTime('d M Y, H:i')
                    ->sortable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Created')
                    ->dateTime('d M Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('method')
                    ->options([
                        'stripe' => 'Stripe',
                        'payu' => 'PayU',
                        'bank_transfer' => 'Bank Transfer',
                        'cash' => 'Cash',
                        'cheque' => 'Cheque',
                        'other' => 'Other',
                    ]),
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'pending' => 'Pending',
                        'completed' => 'Completed',
                        'failed' => 'Failed',
                        'refunded' => 'Refunded',
                    ]),
            ])
            ->headerActions([])
            ->actions([
                ViewAction::make(),
                DeleteAction::make(),
            ])
            ->bulkActions([
                DeleteBulkAction::make(),
            ]);
    }

    public static function getRelationManagers(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPayments::route('/'),
            'create' => Pages\CreatePayment::route('/create'),
            'view' => Pages\ViewPayment::route('/{record}'),
            'edit' => Pages\EditPayment::route('/{record}/edit'),
        ];
    }
}
