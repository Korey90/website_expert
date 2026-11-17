<?php

namespace App\Filament\Resources;

use App\Filament\Resources\InvoiceResource\Pages;
use App\Filament\Support\Currency as FilamentCurrency;
use App\Models\Client;
use App\Models\Invoice;
use App\Models\Project;
use App\Models\TaxCode;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Components\Wizard;
use Filament\Schemas\Components\Wizard\Step;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\HtmlString;

class InvoiceResource extends BaseResource
{
    protected static ?string $model = Invoice::class;

    protected static \BackedEnum|string|null $navigationIcon = 'heroicon-o-document-text';

    protected static \UnitEnum|string|null $navigationGroup = 'Finance';

    protected static ?int $navigationSort = 1;

    public static function form(Schema $form): Schema
    {
        return $form->schema([
            Wizard::make([
                Step::make('Invoice Details')
                    ->schema([
                        Section::make('Invoice Details')
                            ->columns(3)
                            ->schema([
                                Forms\Components\TextInput::make('number')
                                    ->label('Invoice No.')
                                    ->placeholder('Assigned when the invoice is saved')
                                    ->disabled()
                                    ->dehydrated(false),
                                Forms\Components\Select::make('client_id')
                                    ->label('Client')
                                    ->options(Client::withTrashed()->pluck('company_name', 'id'))
                                    ->searchable()
                                    ->required(),
                                Forms\Components\Select::make('project_id')
                                    ->label('Project')
                                    ->options(Project::withTrashed()->pluck('title', 'id'))
                                    ->searchable(),
                                Forms\Components\Select::make('domain_order_id')
                                    ->label('Domain order')
                                    ->relationship('domainOrder', 'full_domain')
                                    ->searchable()
                                    ->preload()
                                    ->nullable()
                                    ->placeholder('None'),
                                Forms\Components\Select::make('status')
                                    ->options(['draft' => 'Draft', 'sent' => 'Sent', 'partially_paid' => 'Partially Paid', 'paid' => 'Paid', 'overdue' => 'Overdue', 'cancelled' => 'Cancelled'])
                                    ->default('draft')
                                    ->disabled()
                                    ->dehydrated()
                                    ->required(),
                                Forms\Components\Select::make('currency')
                                    ->options(fn () => FilamentCurrency::options())
                                    ->default(fn () => FilamentCurrency::default())
                                    ->live()
                                    ->afterStateUpdated(function (?string $state, Get $get, Set $set): void {
                                        if (strtoupper((string) $state) === 'GBP') {
                                            $set('gbp_exchange_rate', 1);
                                            $set('exchange_rate_source', 'identity');
                                            $set('exchange_rate_date', today()->toDateString());
                                        } else {
                                            $set('gbp_exchange_rate', null);
                                            $set('exchange_rate_source', 'manual');
                                        }

                                        static::recalculateTotals($get, $set, '');
                                    })
                                    ->required(),
                                Forms\Components\TextInput::make('vat_rate')
                                    ->numeric()
                                    ->default(20)
                                    ->suffix('%')
                                    ->live(onBlur: true)
                                    ->afterStateUpdated(fn (Get $get, Set $set) => static::recalculateTotals($get, $set, '')),
                                Forms\Components\DatePicker::make('issue_date')->default(today())->required(),
                                Forms\Components\DatePicker::make('tax_point')
                                    ->label(__('finance.fields.tax_point'))
                                    ->default(today())
                                    ->required(),
                                Forms\Components\DatePicker::make('due_date')->default(today()->addDays(30))->required(),
                                Forms\Components\TextInput::make('gbp_exchange_rate')
                                    ->label(__('finance.fields.gbp_exchange_rate'))
                                    ->numeric()
                                    ->minValue(0.00000001)
                                    ->live(onBlur: true)
                                    ->afterStateUpdated(fn (Get $get, Set $set) => static::recalculateTotals($get, $set, ''))
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
                Step::make('Line Items')
                    ->schema([
                        Section::make('Line Items')
                            ->schema([
                                Forms\Components\Repeater::make('items')
                                    ->relationship()
                                    ->live()
                                    ->afterStateUpdated(fn (Get $get, Set $set) => static::recalculateTotals($get, $set, ''))
                                    ->schema([
                                        Forms\Components\TextInput::make('description')->required()->columnSpan(3),
                                        Forms\Components\DatePicker::make('supply_date')
                                            ->label(__('finance.fields.tax_point'))
                                            ->columnSpan(2),
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
                                            ->columnSpan(2),
                                        Forms\Components\Hidden::make('vat_rate_snapshot'),
                                        Forms\Components\TextInput::make('quantity')
                                            ->numeric()
                                            ->default(1)
                                            ->minValue(0)
                                            ->live(onBlur: true)
                                            ->afterStateUpdated(function (Get $get, Set $set): void {
                                                $set('amount', round((float) ($get('quantity') ?? 0) * (float) ($get('unit_price') ?? 0), 2));
                                                static::recalculateTotals($get, $set, '../../');
                                            })
                                            ->columnSpan(1),
                                        Forms\Components\TextInput::make('unit_price')
                                            ->numeric()
                                            ->minValue(0)
                                            ->prefix(fn (Get $get) => FilamentCurrency::symbol($get('../../currency')))
                                            ->live(onBlur: true)
                                            ->afterStateUpdated(function (Get $get, Set $set): void {
                                                $set('amount', round((float) ($get('quantity') ?? 0) * (float) ($get('unit_price') ?? 0), 2));
                                                static::recalculateTotals($get, $set, '../../');
                                            })
                                            ->columnSpan(2),
                                        Forms\Components\TextInput::make('amount')
                                            ->numeric()
                                            ->prefix(fn (Get $get) => FilamentCurrency::symbol($get('../../currency')))
                                            ->disabled()
                                            ->dehydrated(false)
                                            ->columnSpan(2),
                                    ])
                                    ->columns(8)
                                    ->defaultItems(1),
                            ]),
                    ]),
                Step::make('Totals')
                    ->schema([
                        Section::make('Totals')
                            ->columns(4)
                            ->schema([
                                Forms\Components\TextInput::make('subtotal')->numeric()->prefix(fn (Get $get) => FilamentCurrency::symbol($get('currency')))->disabled()->dehydrated(false),
                                Forms\Components\TextInput::make('discount_amount')
                                    ->numeric()
                                    ->minValue(0)
                                    ->prefix(fn (Get $get) => FilamentCurrency::symbol($get('currency')))
                                    ->default(0)
                                    ->live(onBlur: true)
                                    ->afterStateUpdated(fn (Get $get, Set $set) => static::recalculateTotals($get, $set, '')),
                                Forms\Components\TextInput::make('vat_amount')->numeric()->prefix(fn (Get $get) => FilamentCurrency::symbol($get('currency')))->disabled()->dehydrated(false),
                                Forms\Components\TextInput::make('total')->numeric()->prefix(fn (Get $get) => FilamentCurrency::symbol($get('currency')))->disabled()->dehydrated(false),
                                Forms\Components\TextInput::make('vat_amount_gbp')
                                    ->label(__('finance.fields.vat_amount_gbp'))
                                    ->numeric()
                                    ->prefix('£')
                                    ->disabled()
                                    ->dehydrated(false),
                            ]),

                        Forms\Components\Textarea::make('notes')->rows(3)->columnSpanFull(),
                        Forms\Components\Textarea::make('terms')->rows(3)->columnSpanFull()->default('Payment due within 30 days of invoice date.'),
                    ]),
            ])->columnSpanFull(),

        ]);
    }

    /**
     * Recompute the totals preview from the current, unsaved form state.
     * Use an empty prefix for root fields and "../../" inside a repeater row.
     */
    protected static function recalculateTotals(Get $get, Set $set, string $prefix): void
    {
        $items = collect($get($prefix.'items') ?? []);
        $taxCodes = TaxCode::query()
            ->whereIn('id', $items->pluck('tax_code_id')->filter()->unique())
            ->get()
            ->keyBy('id');
        $subtotal = round(
            $items->sum(fn (array $item): float => (float) ($item['quantity'] ?? 0) * (float) ($item['unit_price'] ?? 0)
            ),
            2
        );
        $discount = min(max((float) ($get($prefix.'discount_amount') ?? 0), 0), $subtotal);
        $fallbackVatRate = (float) ($get($prefix.'vat_rate') ?? 0);
        $taxableSubtotal = max(0, $subtotal - $discount);
        $vatAmount = round($items->sum(function (array $item) use ($subtotal, $taxableSubtotal, $fallbackVatRate, $taxCodes): float {
            $lineAmount = (float) ($item['quantity'] ?? 0) * (float) ($item['unit_price'] ?? 0);
            $discountedLineAmount = $subtotal > 0
                ? $lineAmount * ($taxableSubtotal / $subtotal)
                : 0;
            $taxCode = filled($item['tax_code_id'] ?? null)
                ? $taxCodes->get($item['tax_code_id'])
                : null;

            if ($taxCode?->type === 'reverse_charge') {
                return 0;
            }

            $vatRate = filled($item['tax_code_id'] ?? null)
                ? (float) ($item['vat_rate_snapshot'] ?? $taxCode?->rate ?? 0)
                : $fallbackVatRate;

            return round($discountedLineAmount * ($vatRate / 100), 2);
        }), 2);
        $currency = strtoupper((string) ($get($prefix.'currency') ?? 'GBP'));
        $gbpExchangeRate = $currency === 'GBP'
            ? 1.0
            : (float) ($get($prefix.'gbp_exchange_rate') ?? 0);

        $set($prefix.'subtotal', $subtotal);
        $set($prefix.'vat_amount', $vatAmount);
        $set($prefix.'total', round($subtotal - $discount + $vatAmount, 2));
        $set($prefix.'vat_amount_gbp', $gbpExchangeRate > 0
            ? round($vatAmount * $gbpExchangeRate, 2)
            : null);
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
                        'draft' => 'gray', 'sent' => 'info', 'partially_paid' => 'primary', 'paid' => 'success', 'overdue' => 'danger', 'cancelled' => 'warning', default => 'gray'
                    }),
                Tables\Columns\TextColumn::make('total')->money(fn (Invoice $record) => FilamentCurrency::tableCurrency($record))->sortable(),
                Tables\Columns\TextColumn::make('amount_due')->label('Due')->money(fn (Invoice $record) => FilamentCurrency::tableCurrency($record))->sortable(),
                Tables\Columns\TextColumn::make('due_date')->date()->sortable(),
                Tables\Columns\IconColumn::make('sent_at')->label('Sent')->boolean(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('client_id')
                    ->label('Client')
                    ->relationship('client', 'company_name')
                    ->searchable()
                    ->preload(),
                Tables\Filters\SelectFilter::make('status')
                    ->options(['draft' => 'Draft', 'sent' => 'Sent', 'partially_paid' => 'Partially Paid', 'paid' => 'Paid', 'overdue' => 'Overdue']),
                Tables\Filters\TrashedFilter::make(),
            ])
            ->actions([
                ViewAction::make(),
                EditAction::make()->visible(fn (Invoice $record): bool => ! $record->journalEntries()->exists()),
                Action::make('pdf')
                    ->label('PDF')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->url(fn (Invoice $record) => route('invoice.pdf', $record))
                    ->openUrlInNewTab(),
                DeleteAction::make()->visible(fn (Invoice $record): bool => ! $record->journalEntries()->exists()),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListInvoices::route('/'),
            'create' => Pages\CreateInvoice::route('/create'),
            'view' => Pages\ViewInvoice::route('/{record}'),
            'edit' => Pages\EditInvoice::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->withTrashed();
    }
}
