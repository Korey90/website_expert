<?php

namespace App\Filament\Resources;

use App\Filament\Resources\RecurringExpenseTemplateResource\Pages;
use App\Models\Client;
use App\Models\LedgerAccount;
use App\Models\Project;
use App\Models\RecurringExpenseTemplate;
use App\Models\Supplier;
use App\Models\TaxCode;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\RestoreAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Forms;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class RecurringExpenseTemplateResource extends BaseResource
{
    protected static ?string $model = RecurringExpenseTemplate::class;

    protected static ?string $recordTitleAttribute = 'name';

    protected static \BackedEnum|string|null $navigationIcon = 'heroicon-o-arrow-path';

    protected static \UnitEnum|string|null $navigationGroup = 'Finance';

    protected static ?int $navigationSort = 7;

    public static function getNavigationLabel(): string
    {
        return __('finance.recurring_expenses.navigation');
    }

    public static function getModelLabel(): string
    {
        return __('finance.recurring_expenses.singular');
    }

    public static function getPluralModelLabel(): string
    {
        return __('finance.recurring_expenses.plural');
    }

    /** @return array<string> */
    public static function getGloballySearchableAttributes(): array
    {
        return ['name', 'supplier.name', 'business_purpose'];
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->schema([
            Section::make(__('finance.recurring_expenses.sections.schedule'))
                ->columns(4)
                ->schema([
                    Forms\Components\TextInput::make('name')->label(__('finance.recurring_expenses.fields.name'))->required()->maxLength(255)->columnSpan(2),
                    Forms\Components\Select::make('status')->label(__('finance.recurring_expenses.fields.status'))->options(collect(RecurringExpenseTemplate::statuses())->mapWithKeys(fn (string $status): array => [$status => __('finance.recurring_expenses.statuses.'.$status)])->all())->default(RecurringExpenseTemplate::STATUS_ACTIVE)->required(),
                    Forms\Components\Select::make('frequency')->label(__('finance.recurring_expenses.fields.frequency'))->options(collect(RecurringExpenseTemplate::frequencies())->mapWithKeys(fn (string $frequency): array => [$frequency => __('finance.recurring_expenses.frequencies.'.$frequency)])->all())->default(RecurringExpenseTemplate::FREQUENCY_MONTHLY)->required(),
                    Forms\Components\TextInput::make('interval')->label(__('finance.recurring_expenses.fields.interval'))->numeric()->minValue(1)->default(1)->required(),
                    Forms\Components\DatePicker::make('start_date')->label(__('finance.recurring_expenses.fields.start_date'))->default(today())->required(),
                    Forms\Components\DatePicker::make('next_run_date')->label(__('finance.recurring_expenses.fields.next_run_date'))->default(today())->required(),
                    Forms\Components\DatePicker::make('end_date')->label(__('finance.recurring_expenses.fields.end_date'))->afterOrEqual('start_date'),
                ]),
            Section::make(__('finance.recurring_expenses.sections.details'))
                ->columns(3)
                ->schema([
                    Forms\Components\Select::make('supplier_id')->label(__('finance.expenses.fields.supplier'))->options(fn (): array => Supplier::where('status', Supplier::STATUS_ACTIVE)->orderBy('name')->pluck('name', 'id')->all())->searchable()->preload(),
                    Forms\Components\Select::make('client_id')->label(__('finance.expenses.fields.client'))->options(fn (): array => Client::orderBy('company_name')->pluck('company_name', 'id')->all())->searchable(),
                    Forms\Components\Select::make('project_id')->label(__('finance.expenses.fields.project'))->options(fn (): array => Project::orderBy('title')->pluck('title', 'id')->all())->searchable(),
                    Forms\Components\TextInput::make('due_days')->label(__('finance.recurring_expenses.fields.due_days'))->numeric()->minValue(0)->default(30)->required(),
                    Forms\Components\Select::make('payment_method')->label(__('finance.expenses.fields.payment_method'))->options([
                        'bank_transfer' => __('finance.expenses.payment_methods.bank_transfer'), 'card' => __('finance.expenses.payment_methods.card'),
                        'cash' => __('finance.expenses.payment_methods.cash'), 'direct_debit' => __('finance.expenses.payment_methods.direct_debit'),
                        'cheque' => __('finance.expenses.payment_methods.cheque'), 'other' => __('finance.expenses.payment_methods.other'),
                    ]),
                    Forms\Components\TextInput::make('currency')->label(__('finance.expenses.fields.currency'))->default('GBP')->disabled()->dehydrated(false),
                    Forms\Components\Textarea::make('business_purpose')->label(__('finance.expenses.fields.business_purpose'))->required()->columnSpanFull(),
                    Forms\Components\Textarea::make('notes')->label(__('finance.expenses.fields.notes'))->columnSpanFull(),
                ]),
            Section::make(__('finance.recurring_expenses.sections.items'))
                ->schema([
                    Forms\Components\Repeater::make('items')
                        ->schema([
                            Forms\Components\TextInput::make('description')->label(__('finance.expenses.fields.description'))->required()->columnSpan(3),
                            Forms\Components\Select::make('tax_code_id')->label(__('finance.fields.tax_code'))->options(fn (): array => TaxCode::effectiveOn(today())->orderBy('code')->get()->mapWithKeys(fn (TaxCode $taxCode): array => [$taxCode->id => "{$taxCode->code} — {$taxCode->label} ({$taxCode->rate}%)"])->all())->searchable()->required()->columnSpan(2),
                            Forms\Components\Select::make('category')->label(__('finance.expenses.fields.category'))->options(collect(config('finance.expense_categories', []))->mapWithKeys(fn (string $category): array => [$category => __('finance.expense_categories.'.$category)])->all())->default('other')->required()->columnSpan(2),
                            Forms\Components\Select::make('ledger_account_id')->label(__('finance.expenses.fields.ledger_account'))->options(fn (): array => LedgerAccount::activePostable()->where('type', 'expense')->orderBy('code')->get()->mapWithKeys(fn (LedgerAccount $account): array => [$account->id => "{$account->code} — {$account->name}"])->all())->searchable()->preload()->columnSpan(2),
                            Forms\Components\TextInput::make('quantity')->label(__('finance.expenses.fields.quantity'))->numeric()->minValue(0.01)->default(1)->required(),
                            Forms\Components\TextInput::make('unit_price')->label(__('finance.expenses.fields.unit_price'))->numeric()->minValue(0)->required()->columnSpan(2),
                            Forms\Components\Toggle::make('billable')->label(__('finance.expenses.fields.billable'))->live(),
                            Forms\Components\Select::make('client_id')->label(__('finance.expenses.fields.billable_client'))->options(fn (): array => Client::orderBy('company_name')->pluck('company_name', 'id')->all())->searchable()->required(fn (Get $get): bool => (bool) $get('billable'))->visible(fn (Get $get): bool => (bool) $get('billable'))->columnSpan(2),
                            Forms\Components\Select::make('project_id')->label(__('finance.expenses.fields.project'))->options(fn (): array => Project::orderBy('title')->pluck('title', 'id')->all())->searchable()->columnSpan(2),
                        ])
                        ->columns(10)
                        ->defaultItems(1)
                        ->minItems(1)
                        ->required(),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')->label(__('finance.recurring_expenses.fields.name'))->searchable()->sortable()->weight('bold'),
                Tables\Columns\TextColumn::make('supplier.name')->label(__('finance.expenses.fields.supplier'))->placeholder('—')->visibleFrom('md'),
                Tables\Columns\TextColumn::make('status')->label(__('finance.recurring_expenses.fields.status'))->badge()->formatStateUsing(fn (string $state): string => __('finance.recurring_expenses.statuses.'.$state)),
                Tables\Columns\TextColumn::make('frequency')->label(__('finance.recurring_expenses.fields.frequency'))->formatStateUsing(fn (string $state): string => __('finance.recurring_expenses.frequencies.'.$state))->visibleFrom('md'),
                Tables\Columns\TextColumn::make('next_run_date')->label(__('finance.recurring_expenses.fields.next_run_date'))->date()->sortable(),
                Tables\Columns\TextColumn::make('generated_expenses_count')->label(__('finance.recurring_expenses.fields.generated'))->counts('generatedExpenses')->visibleFrom('lg')->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')->options(collect(RecurringExpenseTemplate::statuses())->mapWithKeys(fn (string $status): array => [$status => __('finance.recurring_expenses.statuses.'.$status)])->all()),
                Tables\Filters\TrashedFilter::make(),
            ])
            ->actions([EditAction::make(), DeleteAction::make(), RestoreAction::make()])
            ->bulkActions([BulkActionGroup::make([DeleteBulkAction::make(), RestoreBulkAction::make()])])
            ->recordUrl(fn (RecurringExpenseTemplate $record): ?string => static::canEdit($record)
                ? static::getUrl('edit', ['record' => $record])
                : null)
            ->emptyStateIcon('heroicon-o-arrow-path')
            ->emptyStateHeading(__('admin.migration.procurement.recurring_expenses.empty_heading'))
            ->emptyStateDescription(__('admin.migration.procurement.recurring_expenses.empty_description'))
            ->defaultSort('next_run_date');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListRecurringExpenseTemplates::route('/'),
            'create' => Pages\CreateRecurringExpenseTemplate::route('/create'),
            'edit' => Pages\EditRecurringExpenseTemplate::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->withTrashed()->with(['supplier', 'items'])->withCount('generatedExpenses');
    }
}
