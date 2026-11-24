<?php

namespace App\Filament\Resources;

use App\Data\Finance\UniversalCreditPreflightResult;
use App\Filament\Resources\UniversalCreditAssessmentPeriodResource\Pages;
use App\Filament\Resources\UniversalCreditAssessmentPeriodResource\RelationManagers\EvidencePacksRelationManager;
use App\Filament\Resources\UniversalCreditAssessmentPeriodResource\RelationManagers\ManualEntriesRelationManager;
use App\Filament\Resources\UniversalCreditAssessmentPeriodResource\RelationManagers\ReportItemsRelationManager;
use App\Filament\Resources\UniversalCreditAssessmentPeriodResource\RelationManagers\SimplifiedExpensesRelationManager;
use App\Models\UniversalCreditAssessmentPeriod;
use App\Services\Finance\UniversalCreditPreflightService;
use Filament\Actions\ViewAction;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class UniversalCreditAssessmentPeriodResource extends BaseResource
{
    protected static ?string $model = UniversalCreditAssessmentPeriod::class;

    protected static \BackedEnum|string|null $navigationIcon = 'heroicon-o-document-chart-bar';

    protected static \UnitEnum|string|null $navigationGroup = 'Finance';

    protected static ?int $navigationSort = 12;

    /** @var array<string, UniversalCreditPreflightResult> */
    private static array $preflightCache = [];

    public static function getNavigationLabel(): string
    {
        return __('finance.universal_credit.ui.navigation');
    }

    public static function getModelLabel(): string
    {
        return __('finance.universal_credit.ui.period_singular');
    }

    public static function getPluralModelLabel(): string
    {
        return __('finance.universal_credit.ui.period_plural');
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema->schema([
            Section::make(__('finance.universal_credit.ui.sections.notice'))
                ->schema([
                    TextEntry::make('scope_notice')
                        ->hiddenLabel()
                        ->state(__('finance.universal_credit.ui.scope_notice')),
                ]),
            Section::make(__('finance.universal_credit.ui.sections.summary'))
                ->columns(4)
                ->schema([
                    TextEntry::make('number')->label(__('finance.universal_credit.ui.fields.number'))->weight('bold')->copyable(),
                    TextEntry::make('status')->label(__('finance.universal_credit.ui.fields.status'))->badge()
                        ->formatStateUsing(fn (string $state): string => __('finance.universal_credit.ui.statuses.'.$state))
                        ->color(fn (string $state): string => self::statusColor($state)),
                    TextEntry::make('date_from')->label(__('finance.universal_credit.ui.fields.date_from'))->date(),
                    TextEntry::make('date_to')->label(__('finance.universal_credit.ui.fields.date_to'))->date(),
                    TextEntry::make('income_total_pence')->label(__('finance.universal_credit.ui.fields.income_total'))
                        ->state(fn (UniversalCreditAssessmentPeriod $record): float => $record->income_total_pence / 100)->money('GBP'),
                    TextEntry::make('expense_total_pence')->label(__('finance.universal_credit.ui.fields.expense_total'))
                        ->state(fn (UniversalCreditAssessmentPeriod $record): float => $record->expense_total_pence / 100)->money('GBP'),
                    TextEntry::make('net_total_pence')->label(__('finance.universal_credit.ui.fields.net_total'))
                        ->state(fn (UniversalCreditAssessmentPeriod $record): float => $record->net_total_pence / 100)->money('GBP'),
                    TextEntry::make('preflight_status')->label(__('finance.universal_credit.ui.fields.preflight'))->badge()
                        ->state(fn (UniversalCreditAssessmentPeriod $record): string => self::preflight($record)->status())
                        ->formatStateUsing(fn (string $state): string => __('finance.universal_credit.ui.preflight_statuses.'.$state))
                        ->color(fn (string $state): string => match ($state) {
                            'green' => 'success', 'amber' => 'warning', 'red' => 'danger', default => 'gray',
                        }),
                    TextEntry::make('vat_reporting_method_snapshot')->label(__('finance.universal_credit.ui.fields.vat_method'))
                        ->formatStateUsing(fn (?string $state): string => $state
                            ? __('finance.universal_credit.ui.vat_methods.'.$state)
                            : __('finance.universal_credit.report.not_applicable')),
                    TextEntry::make('report_items_count')->label(__('finance.universal_credit.ui.fields.source_count')),
                    TextEntry::make('simplified_expenses_count')->label(__('finance.universal_credit.ui.fields.simplified_count')),
                    TextEntry::make('evidence_packs_count')->label(__('finance.universal_credit.ui.fields.pack_count')),
                ]),
            Section::make(__('finance.universal_credit.ui.sections.preflight'))
                ->columns(2)
                ->schema([
                    RepeatableEntry::make('preflight_errors')
                        ->label(__('finance.universal_credit.ui.fields.errors'))
                        ->state(fn (UniversalCreditAssessmentPeriod $record): array => self::formatPreflightIssues(self::preflight($record)->errors))
                        ->schema([
                            TextEntry::make('code')->hiddenLabel()
                                ->formatStateUsing(fn (string $state): string => __('finance.universal_credit.preflight.'.$state)),
                            TextEntry::make('context')->hiddenLabel()
                                ->placeholder('-'),
                        ]),
                    RepeatableEntry::make('preflight_warnings')
                        ->label(__('finance.universal_credit.ui.fields.warnings'))
                        ->state(fn (UniversalCreditAssessmentPeriod $record): array => self::formatPreflightIssues(self::preflight($record)->warnings))
                        ->schema([
                            TextEntry::make('code')->hiddenLabel()
                                ->formatStateUsing(fn (string $state): string => __('finance.universal_credit.preflight.'.$state)),
                            TextEntry::make('context')->hiddenLabel()
                                ->placeholder('-'),
                        ]),
                ]),
            Section::make(__('finance.universal_credit.ui.sections.audit'))
                ->columns(3)
                ->schema([
                    TextEntry::make('createdBy.name')->label(__('finance.universal_credit.ui.fields.created_by'))->placeholder('-'),
                    TextEntry::make('created_at')->label(__('finance.universal_credit.ui.fields.created_at'))->dateTime(),
                    TextEntry::make('source_snapshot_hash')->label(__('finance.universal_credit.ui.fields.calculation_hash'))->copyable()->placeholder('-'),
                    TextEntry::make('reviewedBy.name')->label(__('finance.universal_credit.ui.fields.reviewed_by'))->placeholder('-'),
                    TextEntry::make('reviewed_at')->label(__('finance.universal_credit.ui.fields.reviewed_at'))->dateTime()->placeholder('-'),
                    TextEntry::make('review_snapshot_hash')->label(__('finance.universal_credit.ui.fields.review_hash'))->copyable()->placeholder('-'),
                    TextEntry::make('lockedBy.name')->label(__('finance.universal_credit.ui.fields.locked_by'))->placeholder('-'),
                    TextEntry::make('locked_at')->label(__('finance.universal_credit.ui.fields.locked_at'))->dateTime()->placeholder('-'),
                    TextEntry::make('lock_reason')->label(__('finance.universal_credit.ui.fields.lock_reason'))->placeholder('-'),
                    TextEntry::make('reportedBy.name')->label(__('finance.universal_credit.ui.fields.reported_by'))->placeholder('-'),
                    TextEntry::make('reported_at')->label(__('finance.universal_credit.ui.fields.reported_at'))->dateTime()->placeholder('-'),
                    TextEntry::make('report_confirmation_reference')->label(__('finance.universal_credit.ui.fields.confirmation_reference'))->placeholder('-'),
                    TextEntry::make('supersededBy.name')->label(__('finance.universal_credit.ui.fields.superseded_by'))->placeholder('-'),
                    TextEntry::make('superseded_at')->label(__('finance.universal_credit.ui.fields.superseded_at'))->dateTime()->placeholder('-'),
                    TextEntry::make('supersede_reason')->label(__('finance.universal_credit.ui.fields.supersede_reason'))->placeholder('-'),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('number')->label(__('finance.universal_credit.ui.fields.number'))->searchable()->sortable()->weight('bold'),
                Tables\Columns\TextColumn::make('date_from')->label(__('finance.universal_credit.ui.fields.date_from'))->date()->sortable(),
                Tables\Columns\TextColumn::make('date_to')->label(__('finance.universal_credit.ui.fields.date_to'))->date()->sortable(),
                Tables\Columns\TextColumn::make('status')->label(__('finance.universal_credit.ui.fields.status'))->badge()
                    ->formatStateUsing(fn (string $state): string => __('finance.universal_credit.ui.statuses.'.$state))
                    ->color(fn (string $state): string => self::statusColor($state)),
                Tables\Columns\TextColumn::make('income_total_pence')->label(__('finance.universal_credit.ui.fields.income_total'))
                    ->formatStateUsing(fn (int $state): string => self::money($state))->alignEnd(),
                Tables\Columns\TextColumn::make('expense_total_pence')->label(__('finance.universal_credit.ui.fields.expense_total'))
                    ->formatStateUsing(fn (int $state): string => self::money($state))->alignEnd(),
                Tables\Columns\TextColumn::make('net_total_pence')->label(__('finance.universal_credit.ui.fields.net_total'))
                    ->formatStateUsing(fn (int $state): string => self::money($state))->alignEnd(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')->options(collect(UniversalCreditAssessmentPeriod::statuses())
                    ->mapWithKeys(fn (string $status): array => [$status => __('finance.universal_credit.ui.statuses.'.$status)])
                    ->all()),
            ])
            ->actions([ViewAction::make()])
            ->defaultSort('date_to', 'desc');
    }

    public static function getRelations(): array
    {
        return [
            ReportItemsRelationManager::class,
            ManualEntriesRelationManager::class,
            SimplifiedExpensesRelationManager::class,
            EvidencePacksRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListUniversalCreditAssessmentPeriods::route('/'),
            'view' => Pages\ViewUniversalCreditAssessmentPeriod::route('/{record}'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->with(['createdBy', 'reviewedBy', 'lockedBy', 'reportedBy', 'supersededBy'])
            ->withCount(['reportItems', 'simplifiedExpenses', 'evidencePacks']);
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canEdit(Model $record): bool
    {
        return false;
    }

    public static function canDelete(Model $record): bool
    {
        return false;
    }

    public static function statusColor(string $status): string
    {
        return match ($status) {
            UniversalCreditAssessmentPeriod::STATUS_DRAFT => 'gray',
            UniversalCreditAssessmentPeriod::STATUS_IN_REVIEW => 'info',
            UniversalCreditAssessmentPeriod::STATUS_LOCKED => 'success',
            UniversalCreditAssessmentPeriod::STATUS_REPORTED => 'primary',
            UniversalCreditAssessmentPeriod::STATUS_SUPERSEDED => 'warning',
            default => 'gray',
        };
    }

    public static function money(int $pence): string
    {
        return '£'.number_format($pence / 100, 2);
    }

    /**
     * @param  list<array{code: string, context: array<string, mixed>}>  $issues
     * @return list<array{code: string, context: string|null}>
     */
    private static function formatPreflightIssues(array $issues): array
    {
        return array_map(static function (array $issue): array {
            $context = $issue['context'] ?? [];

            return [
                'code' => (string) ($issue['code'] ?? ''),
                'context' => $context === []
                    ? null
                    : json_encode($context, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR),
            ];
        }, $issues);
    }

    private static function preflight(UniversalCreditAssessmentPeriod $period): UniversalCreditPreflightResult
    {
        $key = spl_object_id($period).'|'.$period->id.'|'.$period->updated_at?->getTimestamp();

        return self::$preflightCache[$key] ??= app(UniversalCreditPreflightService::class)->inspect($period);
    }
}
