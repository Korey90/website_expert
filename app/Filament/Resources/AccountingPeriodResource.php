<?php

namespace App\Filament\Resources;

use App\Filament\Resources\AccountingPeriodResource\Pages;
use App\Models\AccountingPeriod;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;

class AccountingPeriodResource extends BaseResource
{
    protected static ?string $model = AccountingPeriod::class;

    protected static \BackedEnum|string|null $navigationIcon = 'heroicon-o-calendar-days';

    protected static \UnitEnum|string|null $navigationGroup = 'Finance';

    protected static ?int $navigationSort = 9;

    public static function getNavigationLabel(): string
    {
        return __('finance.accounting_periods.navigation');
    }

    public static function getModelLabel(): string
    {
        return __('finance.accounting_periods.singular');
    }

    public static function getPluralModelLabel(): string
    {
        return __('finance.accounting_periods.plural');
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->schema([
            Section::make(__('finance.accounting_periods.section'))
                ->columns(2)
                ->schema([
                    Forms\Components\TextInput::make('name')->label(__('finance.accounting_periods.fields.name'))->required()->maxLength(255),
                    Forms\Components\TextInput::make('financial_year')->label(__('finance.accounting_periods.fields.financial_year'))->numeric()->minValue(1900)->maxValue(9999)->required(),
                    Forms\Components\TextInput::make('period_number')->label(__('finance.accounting_periods.fields.period_number'))->numeric()->minValue(1)->maxValue(999),
                    Forms\Components\DatePicker::make('start_date')->label(__('finance.accounting_periods.fields.start_date'))->required(),
                    Forms\Components\DatePicker::make('end_date')->label(__('finance.accounting_periods.fields.end_date'))->afterOrEqual('start_date')->required(),
                ]),
        ]);
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema->schema([
            Section::make(__('finance.accounting_periods.section'))
                ->columns(3)
                ->schema([
                    TextEntry::make('name')->label(__('finance.accounting_periods.fields.name'))->weight('bold'),
                    TextEntry::make('financial_year')->label(__('finance.accounting_periods.fields.financial_year')),
                    TextEntry::make('period_number')->label(__('finance.accounting_periods.fields.period_number'))->placeholder('—'),
                    TextEntry::make('start_date')->label(__('finance.accounting_periods.fields.start_date'))->date(),
                    TextEntry::make('end_date')->label(__('finance.accounting_periods.fields.end_date'))->date(),
                    TextEntry::make('status')->label(__('finance.accounting_periods.fields.status'))->badge()->formatStateUsing(fn (string $state): string => __('finance.accounting_periods.statuses.'.$state))->color(fn (string $state): string => self::statusColor($state)),
                    TextEntry::make('closed_at')->label(__('finance.accounting_periods.fields.closed_at'))->dateTime()->placeholder('—'),
                    TextEntry::make('locked_at')->label(__('finance.accounting_periods.fields.locked_at'))->dateTime()->placeholder('—'),
                    TextEntry::make('transition_history')->label(__('finance.accounting_periods.fields.transitions'))->formatStateUsing(fn (?array $state): string => (string) count($state ?? [])),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')->label(__('finance.accounting_periods.fields.name'))->searchable()->sortable()->weight('bold'),
                Tables\Columns\TextColumn::make('financial_year')->label(__('finance.accounting_periods.fields.financial_year'))->sortable(),
                Tables\Columns\TextColumn::make('period_number')->label(__('finance.accounting_periods.fields.period_number'))->placeholder('—')->sortable(),
                Tables\Columns\TextColumn::make('start_date')->label(__('finance.accounting_periods.fields.start_date'))->date()->sortable(),
                Tables\Columns\TextColumn::make('end_date')->label(__('finance.accounting_periods.fields.end_date'))->date()->sortable(),
                Tables\Columns\TextColumn::make('status')->label(__('finance.accounting_periods.fields.status'))->badge()->formatStateUsing(fn (string $state): string => __('finance.accounting_periods.statuses.'.$state))->color(fn (string $state): string => self::statusColor($state)),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')->options(collect(AccountingPeriod::statuses())->mapWithKeys(fn (string $status): array => [$status => __('finance.accounting_periods.statuses.'.$status)])->all()),
            ])
            ->actions([
                ViewAction::make(),
                EditAction::make()->visible(fn (AccountingPeriod $record): bool => $record->status === AccountingPeriod::STATUS_OPEN),
            ])
            ->defaultSort('start_date', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListAccountingPeriods::route('/'),
            'create' => Pages\CreateAccountingPeriod::route('/create'),
            'view' => Pages\ViewAccountingPeriod::route('/{record}'),
            'edit' => Pages\EditAccountingPeriod::route('/{record}/edit'),
        ];
    }

    private static function statusColor(string $status): string
    {
        return match ($status) {
            AccountingPeriod::STATUS_OPEN => 'success',
            AccountingPeriod::STATUS_CLOSING => 'warning',
            AccountingPeriod::STATUS_CLOSED => 'info',
            AccountingPeriod::STATUS_LOCKED => 'danger',
            default => 'gray',
        };
    }
}
