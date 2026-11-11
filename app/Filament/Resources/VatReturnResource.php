<?php

namespace App\Filament\Resources;

use App\Filament\Resources\VatReturnResource\Pages;
use App\Models\VatReturn;
use Filament\Actions\ViewAction;
use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class VatReturnResource extends BaseResource
{
    protected static ?string $model = VatReturn::class;

    protected static \BackedEnum|string|null $navigationIcon = 'heroicon-o-shield-check';

    protected static \UnitEnum|string|null $navigationGroup = 'Finance';

    protected static ?int $navigationSort = 10;

    public static function getNavigationLabel(): string
    {
        return __('finance.vat_returns.navigation');
    }

    public static function getModelLabel(): string
    {
        return __('finance.vat_returns.singular');
    }

    public static function getPluralModelLabel(): string
    {
        return __('finance.vat_returns.plural');
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema->schema([
            Section::make(__('finance.vat_returns.sections.summary'))
                ->columns(4)
                ->schema([
                    TextEntry::make('number')->label(__('finance.vat_returns.fields.number'))->weight('bold')->copyable(),
                    TextEntry::make('status')->label(__('finance.vat_returns.fields.status'))->badge()
                        ->formatStateUsing(fn (string $state): string => __('finance.vat_returns.statuses.'.$state))
                        ->color(fn (string $state): string => self::statusColor($state)),
                    TextEntry::make('period_from')->label(__('finance.vat_returns.fields.period_from'))->date(),
                    TextEntry::make('period_to')->label(__('finance.vat_returns.fields.period_to'))->date(),
                    TextEntry::make('snapshot_hash')->label(__('finance.vat_returns.fields.snapshot_hash'))->copyable()->columnSpan(2),
                    TextEntry::make('snapshot_version')->label(__('finance.vat_returns.fields.snapshot_version')),
                    TextEntry::make('warning_count')->label(__('finance.vat_returns.fields.warning_count')),
                ]),
            Section::make(__('finance.vat_returns.sections.boxes'))
                ->schema([
                    RepeatableEntry::make('snapshot.calculation.boxes')
                        ->hiddenLabel()
                        ->columns(4)
                        ->schema([
                            TextEntry::make('number')->label(__('finance.vat_returns.fields.box')),
                            TextEntry::make('hmrc_field')->label(__('finance.vat_returns.fields.hmrc_field'))->copyable(),
                            TextEntry::make('raw_gbp')->label(__('finance.vat_returns.fields.raw_gbp'))->money('GBP'),
                            TextEntry::make('submission_value')->label(__('finance.vat_returns.fields.submission_value')),
                        ]),
                ]),
            Section::make(__('finance.vat_returns.sections.sources'))
                ->columns(4)
                ->schema([
                    TextEntry::make('source_journal_count')->label(__('finance.vat_returns.fields.source_journal_count')),
                    TextEntry::make('source_line_count')->label(__('finance.vat_returns.fields.source_line_count')),
                    TextEntry::make('snapshot.profile.vat_scheme')->label(__('finance.vat_returns.fields.vat_scheme')),
                    TextEntry::make('snapshot.profile.vat_number_last_four')->label(__('finance.vat_returns.fields.vat_number_last_four'))->placeholder('—'),
                    TextEntry::make('snapshot.calculation.net_direction')->label(__('finance.vat_returns.fields.net_direction'))
                        ->formatStateUsing(fn (string $state): string => __('finance.vat_return_calculation.directions.'.$state)),
                    TextEntry::make('snapshot.calculation.signed_net_vat_gbp')->label(__('finance.vat_returns.fields.net_vat'))->money('GBP'),
                    IconEntry::make('snapshot.calculation.checks.vat_detail_reconciled')->label(__('finance.vat_returns.fields.vat_detail_reconciled'))->boolean(),
                    TextEntry::make('snapshot.profile.vat_number_sha256')->label(__('finance.vat_returns.fields.vat_number_hash'))->copyable()->columnSpanFull(),
                ]),
            Section::make(__('finance.vat_returns.sections.audit'))
                ->columns(3)
                ->schema([
                    TextEntry::make('createdBy.name')->label(__('finance.vat_returns.fields.created_by'))->placeholder('—'),
                    TextEntry::make('created_at')->label(__('finance.vat_returns.fields.created_at'))->dateTime(),
                    TextEntry::make('reviewedBy.name')->label(__('finance.vat_returns.fields.reviewed_by'))->placeholder('—'),
                    TextEntry::make('reviewed_at')->label(__('finance.vat_returns.fields.reviewed_at'))->dateTime()->placeholder('—'),
                    TextEntry::make('review_notes')->label(__('finance.vat_returns.fields.review_notes'))->placeholder('—')->columnSpan(2),
                    TextEntry::make('lockedBy.name')->label(__('finance.vat_returns.fields.locked_by'))->placeholder('—'),
                    TextEntry::make('locked_at')->label(__('finance.vat_returns.fields.locked_at'))->dateTime()->placeholder('—'),
                    TextEntry::make('lock_reason')->label(__('finance.vat_returns.fields.lock_reason'))->placeholder('—')->columnSpanFull(),
                    TextEntry::make('supersededBy.name')->label(__('finance.vat_returns.fields.superseded_by'))->placeholder('—'),
                    TextEntry::make('superseded_at')->label(__('finance.vat_returns.fields.superseded_at'))->dateTime()->placeholder('—'),
                    TextEntry::make('supersede_reason')->label(__('finance.vat_returns.fields.supersede_reason'))->placeholder('—')->columnSpanFull(),
                ]),
            Section::make(__('finance.vat_returns.sections.submission'))
                ->columns(3)
                ->visible(fn (VatReturn $record): bool => $record->hmrcSubmission !== null)
                ->schema([
                    TextEntry::make('hmrcSubmission.status')->label(__('finance.vat_returns.fields.submission_status'))->badge()
                        ->formatStateUsing(fn (string $state): string => __('finance.vat_returns.submission.statuses.'.$state)),
                    TextEntry::make('hmrcSubmission.submittedBy.name')->label(__('finance.vat_returns.fields.submitted_by'))->placeholder('—'),
                    TextEntry::make('hmrcSubmission.submitted_at')->label(__('finance.vat_returns.fields.submitted_at'))->dateTime()->placeholder('—'),
                    TextEntry::make('hmrcSubmission.processing_date')->label(__('finance.vat_returns.fields.processing_date'))->dateTime()->placeholder('—'),
                    TextEntry::make('hmrcSubmission.payment_indicator')->label(__('finance.vat_returns.fields.payment_indicator'))->placeholder('—'),
                    TextEntry::make('hmrcSubmission.payload_hash')->label(__('finance.vat_returns.fields.payload_hash'))->copyable()->columnSpanFull(),
                    TextEntry::make('hmrcSubmission.reconciliation_status')->label(__('finance.vat_returns.fields.reconciliation_status'))->badge()
                        ->formatStateUsing(fn (?string $state): string => $state
                            ? __('finance.vat_returns.submission.reconciliation_statuses.'.$state)
                            : '—'),
                    TextEntry::make('hmrcSubmission.reconciledBy.name')->label(__('finance.vat_returns.fields.reconciled_by'))->placeholder('—'),
                    TextEntry::make('hmrcSubmission.reconciled_at')->label(__('finance.vat_returns.fields.reconciled_at'))->dateTime()->placeholder('—'),
                    TextEntry::make('hmrcSubmission.hmrc_received_date')->label(__('finance.vat_returns.fields.hmrc_received_date'))->date()->placeholder('—'),
                    TextEntry::make('hmrcSubmission.reconciliation_count')->label(__('finance.vat_returns.fields.reconciliation_count')),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('number')->label(__('finance.vat_returns.fields.number'))->searchable()->sortable()->weight('bold'),
                Tables\Columns\TextColumn::make('period_from')->label(__('finance.vat_returns.fields.period_from'))->date()->sortable(),
                Tables\Columns\TextColumn::make('period_to')->label(__('finance.vat_returns.fields.period_to'))->date()->sortable(),
                Tables\Columns\TextColumn::make('status')->label(__('finance.vat_returns.fields.status'))->badge()
                    ->formatStateUsing(fn (string $state): string => __('finance.vat_returns.statuses.'.$state))
                    ->color(fn (string $state): string => self::statusColor($state)),
                Tables\Columns\TextColumn::make('source_journal_count')->label(__('finance.vat_returns.fields.source_journal_count')),
                Tables\Columns\TextColumn::make('warning_count')->label(__('finance.vat_returns.fields.warning_count')),
                Tables\Columns\TextColumn::make('created_at')->label(__('finance.vat_returns.fields.created_at'))->dateTime()->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')->options(collect(VatReturn::statuses())->mapWithKeys(
                    fn (string $status): array => [$status => __('finance.vat_returns.statuses.'.$status)],
                )->all()),
            ])
            ->actions([ViewAction::make()])
            ->defaultSort('period_to', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListVatReturns::route('/'),
            'view' => Pages\ViewVatReturn::route('/{record}'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->with(['createdBy', 'reviewedBy', 'lockedBy', 'supersededBy', 'hmrcSubmission.submittedBy', 'hmrcSubmission.reconciledBy']);
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

    private static function statusColor(string $status): string
    {
        return match ($status) {
            VatReturn::STATUS_DRAFT => 'gray',
            VatReturn::STATUS_REVIEWED => 'info',
            VatReturn::STATUS_LOCKED => 'success',
            VatReturn::STATUS_SUPERSEDED => 'warning',
            default => 'gray',
        };
    }
}
