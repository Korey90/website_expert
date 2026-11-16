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
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class InvoiceResource extends BaseResource
{
    protected static ?string $model = Invoice::class;

    protected static ?string $recordTitleAttribute = 'number';

    protected static \BackedEnum|string|null $navigationIcon = 'heroicon-o-document-text';

    protected static \UnitEnum|string|null $navigationGroup = 'Finance';

    protected static ?int $navigationSort = 1;

    /** @return array<string> */
    public static function getGloballySearchableAttributes(): array
    {
        return ['number', 'client.company_name'];
    }

    public static function form(Schema $form): Schema
    {
        return $form->schema([
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
                    Forms\Components\TextInput::make('vat_rate')->numeric()->default(20)->suffix('%'),
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

            Section::make('Line Items')
                ->schema([
                    Forms\Components\Repeater::make('items')
                        ->relationship()
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
                                ->columnSpan(2),
                            Forms\Components\TextInput::make('quantity')->numeric()->default(1)->columnSpan(1),
                            Forms\Components\TextInput::make('unit_price')->numeric()->prefix(fn () => FilamentCurrency::symbol())->columnSpan(2),
                            Forms\Components\TextInput::make('amount')->numeric()->prefix(fn () => FilamentCurrency::symbol())->disabled()->columnSpan(2),
                        ])
                        ->columns(8)
                        ->defaultItems(1),
                ]),

            Section::make('Totals')
                ->columns(4)
                ->schema([
                    Forms\Components\TextInput::make('subtotal')->numeric()->prefix(fn () => FilamentCurrency::symbol())->disabled(),
                    Forms\Components\TextInput::make('discount_amount')->numeric()->prefix(fn () => FilamentCurrency::symbol())->default(0),
                    Forms\Components\TextInput::make('vat_amount')->numeric()->prefix(fn () => FilamentCurrency::symbol())->disabled(),
                    Forms\Components\TextInput::make('total')->numeric()->prefix(fn () => FilamentCurrency::symbol())->disabled(),
                    Forms\Components\TextInput::make('vat_amount_gbp')
                        ->label(__('finance.fields.vat_amount_gbp'))
                        ->numeric()
                        ->prefix('£')
                        ->disabled(),
                ]),

            Section::make(__('admin.migration.form.sections.notes_and_terms'))
                ->columns(2)
                ->columnSpanFull()
                ->schema([
                    Forms\Components\Textarea::make('notes')->rows(3),
                    Forms\Components\Textarea::make('terms')->rows(3)->default('Payment due within 30 days of invoice date.'),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('number')
                    ->label(__('admin.migration.sales_documents.columns.number'))
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),
                Tables\Columns\TextColumn::make('client.company_name')
                    ->label(__('admin.migration.sales_documents.columns.client'))
                    ->searchable()
                    ->visibleFrom('md'),
                Tables\Columns\TextColumn::make('status')
                    ->label(__('admin.pilot.common.status'))
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => __('admin.migration.sales_documents.statuses.'.$state))
                    ->color(fn ($state) => match ($state) {
                        'draft' => 'gray', 'sent' => 'info', 'partially_paid' => 'primary', 'paid' => 'success', 'overdue' => 'danger', 'cancelled' => 'warning', default => 'gray'
                    }),
                Tables\Columns\TextColumn::make('total')
                    ->label(__('admin.migration.sales_documents.columns.total'))
                    ->money(fn (Invoice $record) => FilamentCurrency::tableCurrency($record))
                    ->sortable(),
                Tables\Columns\TextColumn::make('amount_due')
                    ->label(__('admin.migration.sales_documents.columns.amount_due'))
                    ->money(fn (Invoice $record) => FilamentCurrency::tableCurrency($record))
                    ->sortable()
                    ->visibleFrom('lg'),
                Tables\Columns\TextColumn::make('due_date')
                    ->label(__('admin.migration.sales_documents.columns.due_date'))
                    ->date()
                    ->sortable()
                    ->visibleFrom('md'),
                Tables\Columns\IconColumn::make('sent_at')
                    ->label(__('admin.migration.sales_documents.columns.sent'))
                    ->boolean()
                    ->visibleFrom('xl')
                    ->toggleable(isToggledHiddenByDefault: true),
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
            ->recordUrl(fn (Invoice $record): string => static::getUrl('view', ['record' => $record]))
            ->emptyStateIcon('heroicon-o-document-text')
            ->emptyStateHeading(__('admin.migration.sales_documents.invoices.empty_heading'))
            ->emptyStateDescription(__('admin.migration.sales_documents.invoices.empty_description'))
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
