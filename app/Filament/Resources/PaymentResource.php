<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PaymentResource\Pages;
use App\Models\Invoice;
use App\Models\Payment;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\ViewAction;

class PaymentResource extends BaseResource
{
    protected static ?string $model = Payment::class;
    protected static \BackedEnum|string|null $navigationIcon  = 'heroicon-o-credit-card';
    protected static \UnitEnum|string|null   $navigationGroup = 'Finance';
    protected static ?string $navigationLabel = 'Payments';
    protected static ?int    $navigationSort  = 5;

    public static function form(Schema $form): Schema
    {
        return $form->schema([
            Section::make('Payment Details')
                ->columns(2)
                ->schema([
                    Forms\Components\Select::make('invoice_id')
                        ->label('Invoice')
                        ->options(Invoice::all()->pluck('number', 'id'))
                        ->searchable()
                        ->required(),

                    Forms\Components\Select::make('method')
                        ->options([
                            'stripe'        => 'Stripe',
                            'payu'          => 'PayU',
                            'bank_transfer' => 'Bank Transfer',
                            'cash'          => 'Cash',
                            'cheque'        => 'Cheque',
                            'other'         => 'Other',
                        ])
                        ->required(),

                    Forms\Components\TextInput::make('amount')
                        ->numeric()
                        ->prefix('£')
                        ->required(),

                    Forms\Components\Select::make('currency')
                        ->options([
                            'GBP' => 'GBP',
                            'EUR' => 'EUR',
                            'USD' => 'USD',
                            'PLN' => 'PLN',
                        ])
                        ->default('GBP')
                        ->required(),

                    Forms\Components\Select::make('status')
                        ->options([
                            'pending'   => 'Pending',
                            'completed' => 'Completed',
                            'failed'    => 'Failed',
                            'refunded'  => 'Refunded',
                        ])
                        ->default('completed')
                        ->required(),

                    Forms\Components\DateTimePicker::make('paid_at')
                        ->label('Paid at')
                        ->native(false),

                    Forms\Components\TextInput::make('reference')
                        ->label('Reference / Transaction ID')
                        ->columnSpan(2),

                    Forms\Components\TextInput::make('stripe_payment_intent_id')
                        ->label('Stripe Payment Intent ID')
                        ->columnSpan(2),

                    Forms\Components\Textarea::make('notes')
                        ->rows(3)
                        ->columnSpan(2),
                ]),
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

                Tables\Columns\BadgeColumn::make('method')
                    ->colors([
                        'primary' => 'stripe',
                        'warning' => 'payu',
                        'info'    => 'bank_transfer',
                        'success' => 'cash',
                        'gray'    => ['cheque', 'other'],
                    ])
                    ->formatStateUsing(fn (string $state) => match ($state) {
                        'stripe'        => 'Stripe',
                        'payu'          => 'PayU',
                        'bank_transfer' => 'Bank Transfer',
                        'cash'          => 'Cash',
                        'cheque'        => 'Cheque',
                        default         => ucfirst($state),
                    }),

                Tables\Columns\BadgeColumn::make('status')
                    ->colors([
                        'success' => 'completed',
                        'warning' => 'pending',
                        'danger'  => 'failed',
                        'gray'    => 'refunded',
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
                        'stripe'        => 'Stripe',
                        'payu'          => 'PayU',
                        'bank_transfer' => 'Bank Transfer',
                        'cash'          => 'Cash',
                        'cheque'        => 'Cheque',
                        'other'         => 'Other',
                    ]),
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'pending'   => 'Pending',
                        'completed' => 'Completed',
                        'failed'    => 'Failed',
                        'refunded'  => 'Refunded',
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
            'index'  => Pages\ListPayments::route('/'),
            'create' => Pages\CreatePayment::route('/create'),
            'view'   => Pages\ViewPayment::route('/{record}'),
            'edit'   => Pages\EditPayment::route('/{record}/edit'),
        ];
    }
}
