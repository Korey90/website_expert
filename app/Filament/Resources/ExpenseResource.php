<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ExpenseResource\Pages;
use App\Filament\Support\Currency as FilamentCurrency;
use App\Models\Client;
use App\Models\Expense;
use App\Models\ExpenseItem;
use App\Models\FinancialAttachment;
use App\Models\LedgerAccount;
use App\Models\Project;
use App\Models\PurchaseCreditNote;
use App\Models\Supplier;
use App\Models\TaxCode;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\RestoreAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Actions\ViewAction;
use Filament\Forms;
use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Validation\Rules\Unique;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;

class ExpenseResource extends BaseResource
{
    protected static ?string $model = Expense::class;

    protected static \BackedEnum|string|null $navigationIcon = 'heroicon-o-receipt-percent';

    protected static \UnitEnum|string|null $navigationGroup = 'Finance';

    protected static ?int $navigationSort = 5;

    public static function getNavigationLabel(): string
    {
        return __('finance.expenses.navigation');
    }

    public static function getModelLabel(): string
    {
        return __('finance.expenses.singular');
    }

    public static function getPluralModelLabel(): string
    {
        return __('finance.expenses.plural');
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->schema([
            Tabs::make('Tabs')
                ->columnSpanFull()
                ->tabs([
                    Tab::make(__('finance.expenses.sections.document'))
                        ->schema([
                            Section::make(__('finance.expenses.sections.document'))
                                ->columns(3)
                                ->schema([
                                    Forms\Components\Select::make('supplier_id')
                                        ->label(__('finance.expenses.fields.supplier'))
                                        ->options(fn (): array => Supplier::query()
                                            ->where('status', Supplier::STATUS_ACTIVE)
                                            ->orderBy('name')
                                            ->pluck('name', 'id')
                                            ->all())
                                        ->searchable()
                                        ->preload()
                                        ->nullable(),
                                    Forms\Components\TextInput::make('supplier_invoice_number')
                                        ->label(__('finance.expenses.fields.supplier_invoice_number'))
                                        ->maxLength(255)
                                        ->unique(
                                            table: Expense::class,
                                            column: 'supplier_invoice_number',
                                            ignoreRecord: true,
                                            modifyRuleUsing: fn (Unique $rule, Get $get): Unique => $rule->where(
                                                fn ($query) => $query
                                                    ->where('business_id', currentBusiness()?->id)
                                                    ->where('supplier_id', $get('supplier_id')),
                                            ),
                                        ),
                                    Forms\Components\Select::make('currency')
                                        ->label(__('finance.expenses.fields.currency'))
                                        ->options(fn (): array => FilamentCurrency::options())
                                        ->default('GBP')
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
                                    Forms\Components\DatePicker::make('issue_date')
                                        ->label(__('finance.expenses.fields.issue_date'))
                                        ->default(today())
                                        ->required(),
                                    Forms\Components\DatePicker::make('tax_point')
                                        ->label(__('finance.fields.tax_point'))
                                        ->default(today())
                                        ->required(),
                                    Forms\Components\DatePicker::make('due_date')->label(__('finance.expenses.fields.due_date')),
                                    Forms\Components\DatePicker::make('received_date')
                                        ->label(__('finance.expenses.fields.received_date'))
                                        ->default(today()),
                                    Forms\Components\Select::make('payment_method')
                                        ->label(__('finance.expenses.fields.payment_method'))
                                        ->options([
                                            'bank_transfer' => __('finance.expenses.payment_methods.bank_transfer'),
                                            'card' => __('finance.expenses.payment_methods.card'),
                                            'cash' => __('finance.expenses.payment_methods.cash'),
                                            'direct_debit' => __('finance.expenses.payment_methods.direct_debit'),
                                            'cheque' => __('finance.expenses.payment_methods.cheque'),
                                            'other' => __('finance.expenses.payment_methods.other'),
                                        ]),
                                    Forms\Components\Select::make('client_id')
                                        ->label(__('finance.expenses.fields.client'))
                                        ->options(fn (): array => Client::orderBy('company_name')->pluck('company_name', 'id')->all())
                                        ->searchable(),
                                    Forms\Components\Select::make('project_id')
                                        ->label(__('finance.expenses.fields.project'))
                                        ->options(fn (): array => Project::orderBy('title')->pluck('title', 'id')->all())
                                        ->searchable(),
                                ]),
                        ]),
                    Tab::make(__('finance.expenses.sections.exchange'))
                        ->schema([
                            Section::make(__('finance.expenses.sections.exchange'))
                                ->columns(3)
                                ->visible(fn (Get $get): bool => strtoupper((string) $get('currency')) !== 'GBP')
                                ->schema([
                                    Forms\Components\TextInput::make('gbp_exchange_rate')
                                        ->label(__('finance.fields.gbp_exchange_rate'))
                                        ->numeric()
                                        ->minValue(0.00000001)
                                        ->required(fn (Get $get): bool => strtoupper((string) $get('currency')) !== 'GBP'),
                                    Forms\Components\TextInput::make('exchange_rate_source')
                                        ->label(__('finance.fields.exchange_rate_source'))
                                        ->default('manual')
                                        ->maxLength(100)
                                        ->required(fn (Get $get): bool => strtoupper((string) $get('currency')) !== 'GBP'),
                                    Forms\Components\DatePicker::make('exchange_rate_date')
                                        ->label(__('finance.fields.exchange_rate_date'))
                                        ->default(today())
                                        ->required(fn (Get $get): bool => strtoupper((string) $get('currency')) !== 'GBP'),
                                ]),
                        ]),
                    Tab::make(__('finance.expenses.sections.items'))
                        ->schema([
                            Section::make(__('finance.expenses.sections.items'))
                                ->schema([
                                    Forms\Components\Repeater::make('items')
                                        ->label('')
                                        ->schema([
                                            Forms\Components\TextInput::make('description')
                                                ->label(__('finance.expenses.fields.description'))
                                                ->required()
                                                ->columnSpan(3),
                                            Forms\Components\DatePicker::make('supply_date')
                                                ->label(__('finance.fields.tax_point'))
                                                ->columnSpan(2),
                                            Forms\Components\Select::make('tax_code_id')
                                                ->label(__('finance.fields.tax_code'))
                                                ->options(fn (): array => TaxCode::effectiveOn(today())
                                                    ->orderBy('code')
                                                    ->get()
                                                    ->mapWithKeys(fn (TaxCode $taxCode): array => [
                                                        $taxCode->id => "{$taxCode->code} — {$taxCode->label} ({$taxCode->rate}%)",
                                                    ])->all())
                                                ->searchable()
                                                ->preload()
                                                ->required()
                                                ->columnSpan(2),
                                            Forms\Components\Select::make('category')
                                                ->label(__('finance.expenses.fields.category'))
                                                ->options(collect(config('finance.expense_categories', []))->mapWithKeys(fn (string $category): array => [
                                                    $category => __('finance.expense_categories.'.$category),
                                                ])->all())
                                                ->default('other')
                                                ->required()
                                                ->columnSpan(2),
                                            Forms\Components\Select::make('ledger_account_id')
                                                ->label(__('finance.expenses.fields.ledger_account'))
                                                ->options(fn (): array => LedgerAccount::activePostable()->where('type', 'expense')->orderBy('code')->get()->mapWithKeys(fn (LedgerAccount $account): array => [$account->id => "{$account->code} — {$account->name}"])->all())
                                                ->searchable()
                                                ->preload()
                                                ->columnSpan(2),
                                            Forms\Components\TextInput::make('quantity')
                                                ->label(__('finance.expenses.fields.quantity'))
                                                ->numeric()
                                                ->minValue(0.01)
                                                ->default(1)
                                                ->required(),
                                            Forms\Components\TextInput::make('unit_price')
                                                ->label(__('finance.expenses.fields.unit_price'))
                                                ->numeric()
                                                ->minValue(0)
                                                ->required()
                                                ->columnSpan(2),
                                            Forms\Components\Toggle::make('billable')
                                                ->label(__('finance.expenses.fields.billable'))
                                                ->live(),
                                            Forms\Components\Select::make('client_id')
                                                ->label(__('finance.expenses.fields.billable_client'))
                                                ->options(fn (): array => Client::orderBy('company_name')->pluck('company_name', 'id')->all())
                                                ->searchable()
                                                ->required(fn (Get $get): bool => (bool) $get('billable'))
                                                ->visible(fn (Get $get): bool => (bool) $get('billable'))
                                                ->columnSpan(2),
                                            Forms\Components\Select::make('project_id')
                                                ->label(__('finance.expenses.fields.project'))
                                                ->options(fn (): array => Project::orderBy('title')->pluck('title', 'id')->all())
                                                ->searchable()
                                                ->columnSpan(2),
                                        ])
                                        ->columns(10)
                                        ->defaultItems(1)
                                        ->minItems(1)
                                        ->required(),
                                ]),
                        ]),
                    Tab::make(__('finance.expenses.sections.purpose'))
                        ->schema([
                            Section::make(__('finance.expenses.sections.purpose'))
                                ->schema([
                                    Forms\Components\Textarea::make('business_purpose')
                                        ->label(__('finance.expenses.fields.business_purpose'))
                                        ->required()
                                        ->rows(3),
                                    Forms\Components\Textarea::make('notes')
                                        ->label(__('finance.expenses.fields.notes'))
                                        ->rows(3),
                                ]),
                        ]),
                    
                ]),

        ]);
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema->schema([
            Tabs::make('Tabs')
                ->columnSpanFull()
                ->tabs([
                    Tab::make(__('finance.expenses.sections.document'))
                        ->schema([
                            Section::make(__('finance.expenses.sections.document'))
                                ->columns(4)
                                ->schema([
                                    TextEntry::make('supplier.name')->label(__('finance.expenses.fields.supplier'))->placeholder('—')->weight('bold'),
                                    TextEntry::make('supplier_invoice_number')->label(__('finance.expenses.fields.supplier_invoice_number'))->copyable()->placeholder('—'),
                                    TextEntry::make('status')
                                        ->label(__('finance.expenses.fields.status'))
                                        ->badge()
                                        ->formatStateUsing(fn (string $state): string => __('finance.expenses.statuses.'.$state))
                                        ->color(fn (string $state): string => self::statusColor($state)),
                                    TextEntry::make('currency')->label(__('finance.expenses.fields.currency'))->badge(),
                                    TextEntry::make('issue_date')->label(__('finance.expenses.fields.issue_date'))->date('d M Y'),
                                    TextEntry::make('tax_point')->label(__('finance.fields.tax_point'))->date('d M Y'),
                                    TextEntry::make('due_date')->label(__('finance.expenses.fields.due_date'))->date('d M Y')->placeholder('—'),
                                    TextEntry::make('received_date')->label(__('finance.expenses.fields.received_date'))->date('d M Y')->placeholder('—'),
                                    TextEntry::make('business_purpose')->label(__('finance.expenses.fields.business_purpose'))->columnSpanFull(),
                                ]),
                        ]),
                    Tab::make(__('finance.expenses.sections.items') . ' & ' . __('finance.expenses.sections.totals'))
                        ->schema([
                            Section::make(__('finance.expenses.sections.items'))
                                ->schema([
                                    RepeatableEntry::make('items')
                                        ->hiddenLabel()
                                        ->columns(7)
                                        ->schema([
                                            TextEntry::make('description')->label(__('finance.expenses.fields.description'))->columnSpan(2),
                                            TextEntry::make('quantity')->label(__('finance.expenses.fields.quantity')),
                                            TextEntry::make('vat_rate_snapshot')->label(__('finance.expenses.fields.vat_rate'))->suffix('%'),
                                            TextEntry::make('net_amount')->label(__('finance.expenses.fields.net'))->formatStateUsing(fn ($state, ExpenseItem $record): string => FilamentCurrency::format($state, $record->expense?->currency)),
                                            TextEntry::make('vat_amount')->label(__('finance.expenses.fields.vat'))->formatStateUsing(fn ($state, ExpenseItem $record): string => FilamentCurrency::format($state, $record->expense?->currency)),
                                            IconEntry::make('billable')->label(__('finance.expenses.fields.billable'))->boolean(),
                                        ])->columnSpanFull(),
                                ]),
                            Section::make(__('finance.expenses.sections.totals'))
                                ->columns(4)
                                ->schema([
                                    TextEntry::make('subtotal')->label(__('finance.expenses.fields.net'))->formatStateUsing(fn ($state, Expense $record): string => FilamentCurrency::format($state, $record->currency)),
                                    TextEntry::make('vat_amount')->label(__('finance.expenses.fields.vat'))->formatStateUsing(fn ($state, Expense $record): string => FilamentCurrency::format($state, $record->currency)),
                                    TextEntry::make('total')->label(__('finance.expenses.fields.total'))->weight('bold')->formatStateUsing(fn ($state, Expense $record): string => FilamentCurrency::format($state, $record->currency)),
                                    TextEntry::make('amount_due')->label(__('finance.expenses.fields.amount_due'))->formatStateUsing(fn ($state, Expense $record): string => FilamentCurrency::format($state, $record->currency)),
                                ]),
                        ]),
                    Tab::make(__('finance.attachments.section'))
                        ->schema([
                            Section::make(__('finance.attachments.section'))
                                ->schema([
                                    RepeatableEntry::make('attachments')
                                        ->hiddenLabel()
                                        ->columns(4)
                                        ->schema([
                                            TextEntry::make('original_name')
                                                ->label(__('finance.attachments.fields.file'))
                                                ->icon('heroicon-o-paper-clip')
                                                ->url(fn (FinancialAttachment $record): ?string => $record->scan_status === FinancialAttachment::SCAN_CLEAN
                                                    ? route($record->isPreviewable() ? 'admin.finance.attachments.preview' : 'admin.finance.attachments.download', $record)
                                                    : null)
                                                ->openUrlInNewTab(),
                                            TextEntry::make('document_type')
                                                ->label(__('finance.attachments.fields.document_type'))
                                                ->formatStateUsing(fn (string $state): string => __('finance.attachments.document_types.'.$state)),
                                            TextEntry::make('size')
                                                ->label(__('finance.attachments.fields.size'))
                                                ->formatStateUsing(fn (int $state): string => number_format($state / 1024, 1).' KB'),
                                            TextEntry::make('scan_status')
                                                ->label(__('finance.attachments.fields.scan_status'))
                                                ->badge()
                                                ->formatStateUsing(fn (string $state): string => __('finance.attachments.scan_statuses.'.$state))
                                                ->color(fn (string $state): string => match ($state) {
                                                    FinancialAttachment::SCAN_CLEAN => 'success',
                                                    FinancialAttachment::SCAN_REJECTED => 'danger',
                                                    default => 'warning',
                                                }),
                                        ]),
                                ]),
                        ]),
                    Tab::make(__('finance.purchase_credit_notes.section'))
                        ->schema([
                            Section::make(__('finance.purchase_credit_notes.section'))
                                ->schema([
                                    RepeatableEntry::make('purchaseCreditNotes')
                                        ->hiddenLabel()
                                        ->columns(4)
                                        ->schema([
                                            TextEntry::make('number')
                                                ->label(__('finance.purchase_credit_notes.fields.number'))
                                                ->url(fn (PurchaseCreditNote $record): string => PurchaseCreditNoteResource::getUrl('view', ['record' => $record])),
                                            TextEntry::make('status')
                                                ->label(__('finance.purchase_credit_notes.fields.status'))
                                                ->badge()
                                                ->formatStateUsing(fn (string $state): string => __('finance.purchase_credit_notes.statuses.'.$state)),
                                            TextEntry::make('total')
                                                ->label(__('finance.purchase_credit_notes.fields.total'))
                                                ->formatStateUsing(fn ($state, PurchaseCreditNote $record): string => FilamentCurrency::format($state, $record->currency)),
                                            TextEntry::make('issue_date')->label(__('finance.purchase_credit_notes.fields.issue_date'))->date(),
                                        ]),
                                ]),
                        ]),
                ]),
                    

            
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('supplier.name')->label(__('finance.expenses.fields.supplier'))->searchable()->sortable()->placeholder('—'),
                Tables\Columns\TextColumn::make('supplier_invoice_number')->label(__('finance.expenses.fields.supplier_invoice_number'))->searchable()->sortable()->placeholder('—'),
                Tables\Columns\TextColumn::make('status')->label(__('finance.expenses.fields.status'))->badge()->formatStateUsing(fn (string $state): string => __('finance.expenses.statuses.'.$state))->color(fn (string $state): string => self::statusColor($state)),
                Tables\Columns\TextColumn::make('total')->label(__('finance.expenses.fields.total'))->money(fn (Expense $record): string => FilamentCurrency::tableCurrency($record))->sortable(),
                Tables\Columns\TextColumn::make('amount_due')->label(__('finance.expenses.fields.amount_due'))->money(fn (Expense $record): string => FilamentCurrency::tableCurrency($record))->sortable(),
                Tables\Columns\TextColumn::make('issue_date')->label(__('finance.expenses.fields.issue_date'))->date()->sortable(),
                Tables\Columns\TextColumn::make('due_date')->label(__('finance.expenses.fields.due_date'))->date()->sortable()->placeholder('—'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('supplier_id')->label(__('finance.expenses.fields.supplier'))->relationship('supplier', 'name')->searchable()->preload(),
                Tables\Filters\SelectFilter::make('status')->options(collect(Expense::statuses())->mapWithKeys(fn (string $status): array => [$status => __('finance.expenses.statuses.'.$status)])->all()),
                Tables\Filters\TrashedFilter::make(),
            ])
            ->actions([
                ViewAction::make(),
                EditAction::make(),
                DeleteAction::make(),
                RestoreAction::make(),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                    RestoreBulkAction::make(),
                ]),
            ])
            ->defaultSort('issue_date', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListExpenses::route('/'),
            'create' => Pages\CreateExpense::route('/create'),
            'view' => Pages\ViewExpense::route('/{record}'),
            'edit' => Pages\EditExpense::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->withTrashed()->with(['supplier', 'items.expense', 'items.taxCode', 'attachments', 'purchaseCreditNotes']);
    }

    private static function statusColor(string $status): string
    {
        return match ($status) {
            Expense::STATUS_DRAFT => 'gray',
            Expense::STATUS_APPROVED => 'info',
            Expense::STATUS_POSTED => 'primary',
            Expense::STATUS_PARTIALLY_PAID => 'warning',
            Expense::STATUS_PAID => 'success',
            Expense::STATUS_VOID => 'danger',
            default => 'gray',
        };
    }
}
