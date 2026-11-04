<?php

namespace App\Filament\Resources;

use App\Filament\Resources\QuoteResource\Pages;
use App\Filament\Support\Currency as FilamentCurrency;
use App\Models\Client;
use App\Models\Quote;
use App\Models\TaxCode;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;

class QuoteResource extends BaseResource
{
    protected static ?string $model = Quote::class;

    protected static \BackedEnum|string|null $navigationIcon = 'heroicon-o-clipboard-document-list';

    protected static \UnitEnum|string|null $navigationGroup = 'Finance';

    protected static ?int $navigationSort = 2;

    public static function form(Schema $form): Schema
    {
        return $form->schema([
            Tabs::make('Quote')
                ->columnSpanFull()
                ->tabs([
                    Tab::make('Details')
                        ->icon('heroicon-o-identification')
                        ->schema([
                            Section::make('Quote Details')
                                ->description('Basic information identifying this quote and its client.')
                                ->columns(3)
                                ->schema([
                                    Forms\Components\TextInput::make('number')
                                        ->label('Quote No.')
                                        ->placeholder('Assigned when the quote is saved')
                                        ->disabled()
                                        ->dehydrated(false),
                                    Forms\Components\Select::make('client_id')
                                        ->label('Client')
                                        ->options(Client::withTrashed()->orderBy('company_name')->pluck('company_name', 'id'))
                                        ->searchable()
                                        ->preload()
                                        ->required(),
                                    Forms\Components\Select::make('status')
                                        ->options(['draft' => 'Draft', 'sent' => 'Sent', 'accepted' => 'Accepted', 'rejected' => 'Rejected', 'expired' => 'Expired'])
                                        ->default('draft')
                                        ->required(),
                                    Forms\Components\Select::make('domain_order_id')
                                        ->label('Domain order')
                                        ->relationship('domainOrder', 'full_domain')
                                        ->searchable()
                                        ->preload()
                                        ->nullable()
                                        ->placeholder('None'),
                                    Forms\Components\Select::make('currency')
                                        ->options(fn () => FilamentCurrency::options())
                                        ->default(fn () => FilamentCurrency::default())
                                        ->live()
                                        ->afterStateUpdated(function (?string $state, Set $set): void {
                                            if (strtoupper((string) $state) === 'GBP') {
                                                $set('gbp_exchange_rate', 1);
                                                $set('exchange_rate_source', 'identity');
                                                $set('exchange_rate_date', today()->toDateString());
                                            } else {
                                                $set('gbp_exchange_rate', null);
                                                $set('exchange_rate_source', 'manual');
                                            }
                                        })
                                        ->required(),
                                    Forms\Components\TextInput::make('vat_rate')
                                        ->label('VAT rate')
                                        ->numeric()
                                        ->default(20)
                                        ->suffix('%')
                                        ->live(onBlur: true)
                                        ->afterStateUpdated(fn (Get $get, Set $set) => static::recalculateTotals($get, $set, '')),
                                    Forms\Components\DatePicker::make('valid_until')
                                        ->label('Valid until')
                                        ->default(today()->addDays(30)),
                                    Forms\Components\TextInput::make('gbp_exchange_rate')
                                        ->label(__('finance.fields.gbp_exchange_rate'))
                                        ->numeric()
                                        ->minValue(0.00000001)
                                        ->helperText('GBP for one unit of the document currency.')
                                        ->required(fn (Get $get): bool => strtoupper((string) $get('currency')) !== 'GBP')
                                        ->visible(fn (Get $get): bool => strtoupper((string) $get('currency')) !== 'GBP'),
                                    Forms\Components\Select::make('exchange_rate_source')
                                        ->label(__('finance.fields.exchange_rate_source'))
                                        ->options(['manual' => 'Manual', 'bank_of_england' => 'Bank of England', 'other' => 'Other'])
                                        ->default('manual')
                                        ->required(fn (Get $get): bool => strtoupper((string) $get('currency')) !== 'GBP')
                                        ->visible(fn (Get $get): bool => strtoupper((string) $get('currency')) !== 'GBP'),
                                    Forms\Components\DatePicker::make('exchange_rate_date')
                                        ->label(__('finance.fields.exchange_rate_date'))
                                        ->default(today())
                                        ->required(fn (Get $get): bool => strtoupper((string) $get('currency')) !== 'GBP')
                                        ->visible(fn (Get $get): bool => strtoupper((string) $get('currency')) !== 'GBP'),
                                ]),
                        ]),

                    Tab::make('Items & Totals')
                        ->icon('heroicon-o-list-bullet')
                        ->schema([
                            Section::make('Line Items')
                                ->description('Add every product or service included in this quote — totals below update automatically.')
                                ->schema([
                                    Forms\Components\Repeater::make('items')
                                        ->relationship()
                                        ->label('')
                                        ->live()
                                        ->afterStateUpdated(fn (Get $get, Set $set) => static::recalculateTotals($get, $set, ''))
                                        ->schema([
                                            Forms\Components\TextInput::make('description')
                                                ->required()
                                                ->placeholder('e.g. Website design & development')
                                                ->columnSpanFull(),
                                            Forms\Components\Textarea::make('details')
                                                ->label('Additional details')
                                                ->placeholder('Optional extra info shown to the client')
                                                ->rows(1)
                                                ->autosize()
                                                ->columnSpanFull(),
                                            Forms\Components\DatePicker::make('supply_date')
                                                ->label(__('finance.fields.tax_point'))
                                                ->columnSpan(4),
                                            Forms\Components\Select::make('tax_code_id')
                                                ->label(__('finance.fields.tax_code'))
                                                ->options(fn () => TaxCode::effectiveOn(today())
                                                    ->orderBy('code')
                                                    ->get()
                                                    ->mapWithKeys(fn (TaxCode $taxCode): array => [
                                                        $taxCode->id => "{$taxCode->code} — {$taxCode->label} ({$taxCode->rate}%)",
                                                    ]))
                                                ->searchable()
                                                ->preload()
                                                ->live()
                                                ->afterStateUpdated(function ($state, Get $get, Set $set): void {
                                                    $rate = $state
                                                        ? TaxCode::find($state)?->rate
                                                        : null;

                                                    $set('vat_rate_snapshot', $rate);
                                                    static::recalculateTotals($get, $set, '../../');
                                                })
                                                ->columnSpan(4),
                                            Forms\Components\Hidden::make('vat_rate_snapshot'),
                                            Forms\Components\TextInput::make('quantity')
                                                ->numeric()
                                                ->default(1)
                                                ->minValue(0)
                                                ->live(onBlur: true)
                                                ->afterStateUpdated(function (Get $get, Set $set) {
                                                    $set('amount', round((float) ($get('quantity') ?? 0) * (float) ($get('unit_price') ?? 0), 2));
                                                    static::recalculateTotals($get, $set, '../../');
                                                })
                                                ->columnSpan(4),
                                            Forms\Components\TextInput::make('unit_price')
                                                ->label('Unit price')
                                                ->numeric()
                                                ->minValue(0)
                                                ->prefix(fn (Get $get) => FilamentCurrency::symbol($get('../../currency')))
                                                ->live(onBlur: true)
                                                ->afterStateUpdated(function (Get $get, Set $set) {
                                                    $set('amount', round((float) ($get('quantity') ?? 0) * (float) ($get('unit_price') ?? 0), 2));
                                                    static::recalculateTotals($get, $set, '../../');
                                                })
                                                ->columnSpan(4),
                                            Forms\Components\TextInput::make('amount')
                                                ->label('Line total')
                                                ->numeric()
                                                ->prefix(fn (Get $get) => FilamentCurrency::symbol($get('../../currency')))
                                                ->disabled()
                                                ->dehydrated(false)
                                                ->columnSpan(4),
                                        ])
                                        ->columns(12)
                                        ->defaultItems(1)
                                        ->addActionLabel('Add line item')
                                        ->reorderable('order')
                                        ->collapsible()
                                        ->cloneable()
                                        ->itemLabel(fn (array $state, Get $get): ?string => filled($state['description'] ?? null)
                                            ? Str::limit($state['description'], 50).' — '.FilamentCurrency::format(
                                                (float) ($state['quantity'] ?? 0) * (float) ($state['unit_price'] ?? 0),
                                                $get('currency')
                                            )
                                            : null),
                                ]),

                            Section::make('Totals')
                                ->description('Discount is applied before VAT. These figures are also recalculated when you save.')
                                ->icon('heroicon-o-calculator')
                                ->columns(4)
                                ->schema([
                                    Forms\Components\TextInput::make('subtotal')
                                        ->numeric()
                                        ->prefix(fn (Get $get) => FilamentCurrency::symbol($get('currency')))
                                        ->disabled()
                                        ->dehydrated(false),
                                    Forms\Components\TextInput::make('discount_amount')
                                        ->label('Discount')
                                        ->numeric()
                                        ->default(0)
                                        ->minValue(0)
                                        ->prefix(fn (Get $get) => FilamentCurrency::symbol($get('currency')))
                                        ->live(onBlur: true)
                                        ->afterStateUpdated(fn (Get $get, Set $set) => static::recalculateTotals($get, $set, '')),
                                    Forms\Components\TextInput::make('vat_amount')
                                        ->label('VAT amount')
                                        ->numeric()
                                        ->prefix(fn (Get $get) => FilamentCurrency::symbol($get('currency')))
                                        ->disabled()
                                        ->dehydrated(false),
                                    Forms\Components\TextInput::make('total')
                                        ->numeric()
                                        ->prefix(fn (Get $get) => FilamentCurrency::symbol($get('currency')))
                                        ->disabled()
                                        ->dehydrated(false),
                                    Forms\Components\TextInput::make('vat_amount_gbp')
                                        ->label(__('finance.fields.vat_amount_gbp'))
                                        ->numeric()
                                        ->prefix('£')
                                        ->disabled()
                                        ->dehydrated(false),
                                ]),
                        ]),

                    Tab::make('Notes & Terms')
                        ->icon('heroicon-o-chat-bubble-left-ellipsis')
                        ->schema([
                            Forms\Components\Textarea::make('notes')
                                ->rows(4)
                                ->placeholder('Internal notes or extra information for the client...')
                                ->columnSpanFull(),
                            Forms\Components\Textarea::make('terms')
                                ->rows(4)
                                ->columnSpanFull()
                                ->default('This quote is valid for 30 days from the date of issue.'),
                        ]),
                ]),
        ]);
    }

    /**
     * Recompute subtotal/VAT/total previews from the in-progress form state (before save).
     * $prefix must resolve from the calling field's own container up to the form's root
     * (e.g. '' when called from a root-level field, '../../' from within a repeater row).
     */
    protected static function recalculateTotals(Get $get, Set $set, string $prefix): void
    {
        $subtotal = round(
            collect($get($prefix.'items') ?? [])
                ->sum(fn (array $item) => (float) ($item['quantity'] ?? 0) * (float) ($item['unit_price'] ?? 0)),
            2
        );
        $discount = (float) ($get($prefix.'discount_amount') ?? 0);
        $fallbackVatRate = (float) ($get($prefix.'vat_rate') ?? 0);
        $taxableSubtotal = max(0, $subtotal - $discount);
        $vatAmount = round(collect($get($prefix.'items') ?? [])->sum(function (array $item) use ($subtotal, $taxableSubtotal, $fallbackVatRate): float {
            $lineAmount = (float) ($item['quantity'] ?? 0) * (float) ($item['unit_price'] ?? 0);
            $discountedLineAmount = $subtotal > 0
                ? $lineAmount * ($taxableSubtotal / $subtotal)
                : 0;
            $vatRate = (float) ($item['vat_rate_snapshot'] ?? $fallbackVatRate);

            return round($discountedLineAmount * ($vatRate / 100), 2);
        }), 2);

        $set($prefix.'subtotal', $subtotal);
        $set($prefix.'vat_amount', $vatAmount);
        $set($prefix.'total', round($subtotal - $discount + $vatAmount, 2));
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('number')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('client.company_name')->label('Client')->searchable(),
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn ($state) => match ($state) {
                        'draft' => 'gray', 'sent' => 'info', 'accepted' => 'success', 'rejected' => 'danger', 'expired' => 'warning', default => 'gray'
                    }),
                Tables\Columns\TextColumn::make('total')->money(fn (Quote $record) => FilamentCurrency::tableCurrency($record))->sortable(),
                Tables\Columns\TextColumn::make('valid_until')->date()->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('client_id')
                    ->label('Client')
                    ->relationship('client', 'company_name')
                    ->searchable()
                    ->preload(),
                Tables\Filters\SelectFilter::make('status')
                    ->options(['draft' => 'Draft', 'sent' => 'Sent', 'accepted' => 'Accepted', 'rejected' => 'Rejected']),
                Tables\Filters\TrashedFilter::make(),
            ])
            ->actions([ViewAction::make(), EditAction::make(), DeleteAction::make()])
            ->defaultSort('created_at', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListQuotes::route('/'),
            'create' => Pages\CreateQuote::route('/create'),
            'view' => Pages\ViewQuote::route('/{record}'),
            'edit' => Pages\EditQuote::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->withTrashed();
    }
}
