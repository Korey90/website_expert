<?php

namespace App\Filament\Resources;

use App\Filament\Resources\VatAdjustmentResource\Pages;
use App\Filament\Support\Currency as FilamentCurrency;
use App\Models\FinancialAttachment;
use App\Models\JournalEntry;
use App\Models\LedgerAccount;
use App\Models\TaxCode;
use App\Models\VatAdjustment;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms;
use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class VatAdjustmentResource extends BaseResource
{
    protected static ?string $model = VatAdjustment::class;

    protected static \BackedEnum|string|null $navigationIcon = 'heroicon-o-adjustments-horizontal';

    protected static \UnitEnum|string|null $navigationGroup = 'Finance';

    protected static ?int $navigationSort = 9;

    public static function getNavigationLabel(): string
    {
        return __('finance.vat_adjustments.navigation');
    }

    public static function getModelLabel(): string
    {
        return __('finance.vat_adjustments.singular');
    }

    public static function getPluralModelLabel(): string
    {
        return __('finance.vat_adjustments.plural');
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->schema([
            Section::make(__('finance.vat_adjustments.sections.adjustment'))
                ->columns(3)
                ->schema([
                    Forms\Components\Select::make('adjustment_type')
                        ->label(__('finance.vat_adjustments.fields.adjustment_type'))
                        ->options(self::translatedOptions('types', VatAdjustment::types()))
                        ->default(VatAdjustment::TYPE_ACCOUNTING_ADJUSTMENT)
                        ->live()
                        ->required(),
                    Forms\Components\Select::make('vat_side')
                        ->label(__('finance.vat_adjustments.fields.vat_side'))
                        ->options(self::translatedOptions('sides', VatAdjustment::sides()))
                        ->live()
                        ->required(),
                    Forms\Components\Select::make('direction')
                        ->label(__('finance.vat_adjustments.fields.direction'))
                        ->options(self::translatedOptions('directions', VatAdjustment::directions()))
                        ->required(),
                    Forms\Components\TextInput::make('amount_gbp')
                        ->label(__('finance.vat_adjustments.fields.amount_gbp'))
                        ->numeric()
                        ->minValue(0.01)
                        ->prefix('£')
                        ->required(),
                    Forms\Components\DatePicker::make('effective_date')
                        ->label(__('finance.vat_adjustments.fields.effective_date'))
                        ->default(today())
                        ->required(),
                    Forms\Components\Select::make('tax_code_id')
                        ->label(__('finance.fields.tax_code'))
                        ->options(fn (): array => TaxCode::query()
                            ->where('is_active', true)
                            ->orderBy('code')
                            ->get()
                            ->mapWithKeys(fn (TaxCode $taxCode): array => [
                                $taxCode->id => $taxCode->code.' — '.$taxCode->label,
                            ])->all())
                        ->searchable()
                        ->preload()
                        ->required(),
                    Forms\Components\Select::make('control_ledger_account_id')
                        ->label(__('finance.vat_adjustments.fields.control_account'))
                        ->options(fn (Get $get): array => LedgerAccount::query()
                            ->activePostable()
                            ->where('subtype', $get('vat_side') === VatAdjustment::SIDE_INPUT ? 'tax_receivable' : 'tax_payable')
                            ->orderBy('code')
                            ->get()
                            ->mapWithKeys(fn (LedgerAccount $account): array => [$account->id => $account->code.' — '.$account->name])
                            ->all())
                        ->searchable()
                        ->preload()
                        ->required(),
                    Forms\Components\Select::make('contra_ledger_account_id')
                        ->label(__('finance.vat_adjustments.fields.contra_account'))
                        ->options(fn (): array => LedgerAccount::query()
                            ->activePostable()
                            ->whereNotIn('subtype', ['tax_payable', 'tax_receivable'])
                            ->orderBy('code')
                            ->get()
                            ->mapWithKeys(fn (LedgerAccount $account): array => [$account->id => $account->code.' — '.$account->name])
                            ->all())
                        ->searchable()
                        ->preload()
                        ->required(),
                    Forms\Components\Textarea::make('reason')
                        ->label(__('finance.vat_adjustments.fields.reason'))
                        ->helperText(__('finance.vat_adjustments.help.reason'))
                        ->minLength(10)
                        ->maxLength(2000)
                        ->rows(4)
                        ->required()
                        ->columnSpanFull(),
                ])->columnSpanFull(),

            Section::make(__('finance.vat_adjustments.sections.error_correction'))
                ->description(__('finance.vat_adjustments.help.error_correction'))
                ->columns(3)
                ->visible(fn (Get $get): bool => $get('adjustment_type') === VatAdjustment::TYPE_ERROR_CORRECTION)
                ->schema([
                    Forms\Components\Select::make('correction_method')
                        ->label(__('finance.vat_adjustments.fields.correction_method'))
                        ->options([
                            VatAdjustment::METHOD_CURRENT_RETURN => __('finance.vat_adjustments.methods.current_return'),
                            VatAdjustment::METHOD_SEPARATE_NOTIFICATION => __('finance.vat_adjustments.methods.separate_notification'),
                        ])
                        ->default(VatAdjustment::METHOD_CURRENT_RETURN)
                        ->live()
                        ->required(),
                    Forms\Components\DatePicker::make('discovered_on')
                        ->label(__('finance.vat_adjustments.fields.discovered_on'))
                        ->required(),
                    Forms\Components\Toggle::make('is_deliberate')
                        ->label(__('finance.vat_adjustments.fields.is_deliberate'))
                        ->helperText(__('finance.vat_adjustments.help.is_deliberate')),
                    Forms\Components\DatePicker::make('original_period_from')
                        ->label(__('finance.vat_adjustments.fields.original_period_from'))
                        ->required(),
                    Forms\Components\DatePicker::make('original_period_to')
                        ->label(__('finance.vat_adjustments.fields.original_period_to'))
                        ->required(),
                    Forms\Components\TextInput::make('hmrc_reference')
                        ->label(__('finance.vat_adjustments.fields.hmrc_reference'))
                        ->maxLength(255)
                        ->visible(fn (Get $get): bool => $get('correction_method') === VatAdjustment::METHOD_SEPARATE_NOTIFICATION),
                ]),
        ]);
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema->schema([
            Section::make(__('finance.vat_adjustments.sections.adjustment'))
                ->columns(4)
                ->schema([
                    TextEntry::make('number')->label(__('finance.vat_adjustments.fields.number'))->weight('bold')->copyable(),
                    TextEntry::make('status')->label(__('finance.vat_adjustments.fields.status'))->badge()
                        ->formatStateUsing(fn (string $state): string => __('finance.vat_adjustments.statuses.'.$state))
                        ->color(fn (string $state): string => self::statusColor($state)),
                    TextEntry::make('adjustment_type')->label(__('finance.vat_adjustments.fields.adjustment_type'))->formatStateUsing(fn (string $state): string => __('finance.vat_adjustments.types.'.$state)),
                    TextEntry::make('effective_date')->label(__('finance.vat_adjustments.fields.effective_date'))->date(),
                    TextEntry::make('vat_side')->label(__('finance.vat_adjustments.fields.vat_side'))->badge()->formatStateUsing(fn (string $state): string => __('finance.vat_adjustments.sides.'.$state)),
                    TextEntry::make('direction')->label(__('finance.vat_adjustments.fields.direction'))->badge()->formatStateUsing(fn (string $state): string => __('finance.vat_adjustments.directions.'.$state)),
                    TextEntry::make('amount_gbp')->label(__('finance.vat_adjustments.fields.amount_gbp'))->formatStateUsing(fn ($state): string => FilamentCurrency::format($state, 'GBP'))->weight('bold'),
                    TextEntry::make('taxCode.code')->label(__('finance.fields.tax_code')),
                    TextEntry::make('controlLedgerAccount.name')->label(__('finance.vat_adjustments.fields.control_account'))->formatStateUsing(fn ($state, VatAdjustment $record): string => $record->controlLedgerAccount->code.' — '.$state),
                    TextEntry::make('contraLedgerAccount.name')->label(__('finance.vat_adjustments.fields.contra_account'))->formatStateUsing(fn ($state, VatAdjustment $record): string => $record->contraLedgerAccount->code.' — '.$state),
                    TextEntry::make('reason')->label(__('finance.vat_adjustments.fields.reason'))->columnSpanFull(),
                ]),
            Section::make(__('finance.vat_adjustments.sections.error_correction'))
                ->columns(4)
                ->visible(fn (VatAdjustment $record): bool => $record->adjustment_type === VatAdjustment::TYPE_ERROR_CORRECTION)
                ->schema([
                    TextEntry::make('correction_method')->label(__('finance.vat_adjustments.fields.correction_method'))->formatStateUsing(fn (string $state): string => __('finance.vat_adjustments.methods.'.$state)),
                    TextEntry::make('discovered_on')->label(__('finance.vat_adjustments.fields.discovered_on'))->date(),
                    TextEntry::make('original_period_from')->label(__('finance.vat_adjustments.fields.original_period_from'))->date(),
                    TextEntry::make('original_period_to')->label(__('finance.vat_adjustments.fields.original_period_to'))->date(),
                    IconEntry::make('is_deliberate')->label(__('finance.vat_adjustments.fields.is_deliberate'))->boolean(),
                    TextEntry::make('hmrc_reference')->label(__('finance.vat_adjustments.fields.hmrc_reference'))->placeholder('—')->columnSpan(3),
                ]),
            Section::make(__('finance.attachments.section'))
                ->description(__('finance.vat_adjustments.help.evidence'))
                ->schema([
                    RepeatableEntry::make('attachments')
                        ->hiddenLabel()
                        ->columns(6)
                        ->schema([
                            TextEntry::make('original_name')
                                ->label(__('finance.attachments.fields.file'))
                                ->icon('heroicon-o-paper-clip')
                                ->url(fn (FinancialAttachment $record): ?string => $record->scan_status === FinancialAttachment::SCAN_CLEAN
                                    ? route($record->isPreviewable() ? 'admin.finance.attachments.preview' : 'admin.finance.attachments.download', $record)
                                    : null)
                                ->openUrlInNewTab(),
                            TextEntry::make('size')->label(__('finance.attachments.fields.size'))->formatStateUsing(fn (int $state): string => number_format($state / 1024, 1).' KB'),
                            TextEntry::make('sha256')->label(__('finance.vat_adjustments.fields.sha256'))->copyable()->limit(18),
                            TextEntry::make('scan_status')->label(__('finance.attachments.fields.scan_status'))->badge()
                                ->formatStateUsing(fn (string $state): string => __('finance.attachments.scan_statuses.'.$state))
                                ->color(fn (string $state): string => match ($state) {
                                    FinancialAttachment::SCAN_CLEAN => 'success',
                                    FinancialAttachment::SCAN_REJECTED, FinancialAttachment::SCAN_FAILED => 'danger',
                                    default => 'warning',
                                }),
                            TextEntry::make('scanned_at')
                                ->label(__('finance.attachments.fields.scanned_at'))
                                ->dateTime()
                                ->placeholder('-'),
                            TextEntry::make('scan_error_code')
                                ->label(__('finance.attachments.fields.scan_error'))
                                ->formatStateUsing(fn (string $state): string => __('finance.attachments.scan_errors.'.$state))
                                ->placeholder('-'),
                        ]),
                ]),
            Section::make(__('finance.vat_adjustments.sections.audit'))
                ->columns(3)
                ->schema([
                    TextEntry::make('createdBy.name')->label(__('finance.vat_adjustments.fields.created_by'))->placeholder('—'),
                    TextEntry::make('approvedBy.name')->label(__('finance.vat_adjustments.fields.approved_by'))->placeholder('—'),
                    TextEntry::make('approved_at')->label(__('finance.vat_adjustments.fields.approved_at'))->dateTime()->placeholder('—'),
                    TextEntry::make('postedBy.name')->label(__('finance.vat_adjustments.fields.posted_by'))->placeholder('—'),
                    TextEntry::make('posted_at')->label(__('finance.vat_adjustments.fields.posted_at'))->dateTime()->placeholder('—'),
                    RepeatableEntry::make('journalEntries')
                        ->label(__('finance.vat_adjustments.fields.journal'))
                        ->schema([
                            TextEntry::make('entry_number')
                                ->hiddenLabel()
                                ->url(fn (JournalEntry $record): string => JournalEntryResource::getUrl('view', ['record' => $record])),
                        ]),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('number')->label(__('finance.vat_adjustments.fields.number'))->searchable()->sortable()->weight('bold'),
                Tables\Columns\TextColumn::make('effective_date')->label(__('finance.vat_adjustments.fields.effective_date'))->date()->sortable(),
                Tables\Columns\TextColumn::make('adjustment_type')->label(__('finance.vat_adjustments.fields.adjustment_type'))->formatStateUsing(fn (string $state): string => __('finance.vat_adjustments.types.'.$state)),
                Tables\Columns\TextColumn::make('vat_side')->label(__('finance.vat_adjustments.fields.vat_side'))->badge()->formatStateUsing(fn (string $state): string => __('finance.vat_adjustments.sides.'.$state)),
                Tables\Columns\TextColumn::make('direction')->label(__('finance.vat_adjustments.fields.direction'))->badge()->formatStateUsing(fn (string $state): string => __('finance.vat_adjustments.directions.'.$state)),
                Tables\Columns\TextColumn::make('amount_gbp')->label(__('finance.vat_adjustments.fields.amount_gbp'))->money('GBP')->sortable(),
                Tables\Columns\TextColumn::make('status')->label(__('finance.vat_adjustments.fields.status'))->badge()->formatStateUsing(fn (string $state): string => __('finance.vat_adjustments.statuses.'.$state))->color(fn (string $state): string => self::statusColor($state)),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')->options(self::translatedOptions('statuses', VatAdjustment::statuses())),
                Tables\Filters\SelectFilter::make('adjustment_type')->options(self::translatedOptions('types', VatAdjustment::types())),
                Tables\Filters\SelectFilter::make('vat_side')->options(self::translatedOptions('sides', VatAdjustment::sides())),
            ])
            ->actions([ViewAction::make(), EditAction::make()])
            ->defaultSort('effective_date', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListVatAdjustments::route('/'),
            'create' => Pages\CreateVatAdjustment::route('/create'),
            'view' => Pages\ViewVatAdjustment::route('/{record}'),
            'edit' => Pages\EditVatAdjustment::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->with([
            'taxCode', 'controlLedgerAccount', 'contraLedgerAccount', 'attachments',
            'createdBy', 'approvedBy', 'postedBy', 'journalEntries',
        ]);
    }

    /** @param list<string> $values @return array<string, string> */
    private static function translatedOptions(string $group, array $values): array
    {
        return collect($values)->mapWithKeys(fn (string $value): array => [
            $value => __('finance.vat_adjustments.'.$group.'.'.$value),
        ])->all();
    }

    private static function statusColor(string $status): string
    {
        return match ($status) {
            VatAdjustment::STATUS_DRAFT => 'gray',
            VatAdjustment::STATUS_APPROVED => 'info',
            VatAdjustment::STATUS_POSTED => 'success',
            default => 'gray',
        };
    }
}
