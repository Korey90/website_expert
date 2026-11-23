<?php

namespace App\Filament\Resources\UniversalCreditAssessmentPeriodResource\RelationManagers;

use App\Actions\Finance\ClassifyUniversalCreditReportItemAction;
use App\Filament\Resources\UniversalCreditAssessmentPeriodResource;
use App\Models\UniversalCreditAssessmentPeriod;
use App\Models\UniversalCreditReportItem;
use App\Services\Finance\UniversalCreditEvidenceService;
use DomainException;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Gate;

class ReportItemsRelationManager extends RelationManager
{
    protected static string $relationship = 'reportItems';

    public static function getTitle(Model $ownerRecord, string $pageClass): string
    {
        return __('finance.universal_credit.ui.relations.report_items');
    }

    public function isReadOnly(): bool
    {
        return false;
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('cash_date')->label(__('finance.universal_credit.ui.fields.cash_date'))->date()->sortable(),
                Tables\Columns\TextColumn::make('direction')->label(__('finance.universal_credit.ui.fields.direction'))->badge()
                    ->formatStateUsing(fn (string $state): string => __('finance.universal_credit.values.direction.'.$state))
                    ->color(fn (string $state): string => $state === UniversalCreditReportItem::DIRECTION_INCOME ? 'success' : 'warning'),
                Tables\Columns\TextColumn::make('category')->label(__('finance.universal_credit.ui.fields.category'))
                    ->formatStateUsing(fn (string $state): string => __('finance.universal_credit.values.category.'.$state))->wrap(),
                Tables\Columns\TextColumn::make('classification_status')->label(__('finance.universal_credit.ui.fields.classification'))->badge()
                    ->formatStateUsing(fn (string $state): string => __('finance.universal_credit.values.classification.'.$state))
                    ->color(fn (string $state): string => match ($state) {
                        UniversalCreditReportItem::STATUS_INCLUDED => 'success',
                        UniversalCreditReportItem::STATUS_EXCLUDED => 'gray',
                        default => 'warning',
                    }),
                Tables\Columns\TextColumn::make('gross_amount_pence')->label(__('finance.universal_credit.ui.fields.gross_amount'))
                    ->formatStateUsing(fn (?int $state): string => $state === null ? '-' : UniversalCreditAssessmentPeriodResource::money($state))->alignEnd(),
                Tables\Columns\TextColumn::make('business_use_basis_points')->label(__('finance.universal_credit.ui.fields.business_use'))
                    ->formatStateUsing(fn (int $state): string => number_format($state / 100, 2).'%')->alignEnd(),
                Tables\Columns\TextColumn::make('reportable_amount_pence')->label(__('finance.universal_credit.ui.fields.reportable_amount'))
                    ->formatStateUsing(fn (?int $state): string => $state === null ? '-' : UniversalCreditAssessmentPeriodResource::money($state))->alignEnd(),
                Tables\Columns\TextColumn::make('evidence_state')->label(__('finance.universal_credit.ui.fields.evidence'))->badge()
                    ->getStateUsing(fn (UniversalCreditReportItem $record): string => (string) app(UniversalCreditEvidenceService::class)->forItem($record)['state'])
                    ->formatStateUsing(fn (string $state): string => __('finance.universal_credit.values.evidence.'.$state))
                    ->color(fn (string $state): string => in_array($state, ['clean_attachment', 'source_document', 'structured_record'], true) ? 'success' : 'warning'),
            ])
            ->actions([
                Action::make('classify')
                    ->label(__('finance.universal_credit.ui.actions.classify'))
                    ->icon('heroicon-o-check-circle')
                    ->visible(fn (UniversalCreditReportItem $record): bool => $this->canManage()
                        && $record->source_is_current)
                    ->fillForm(fn (UniversalCreditReportItem $record): array => [
                        'classification_status' => $record->classification_status === UniversalCreditReportItem::STATUS_REVIEW_REQUIRED
                            ? UniversalCreditReportItem::STATUS_INCLUDED
                            : $record->classification_status,
                        'category' => $record->category,
                        'business_use_percentage' => $record->business_use_basis_points / 100,
                        'business_use_reason' => $record->business_use_reason,
                        'classification_reason' => $record->classification_reason,
                        'calculation_rule' => $record->calculation_rule,
                    ])
                    ->form(fn (UniversalCreditReportItem $record): array => [
                        Select::make('classification_status')
                            ->label(__('finance.universal_credit.ui.fields.classification'))
                            ->options([
                                UniversalCreditReportItem::STATUS_INCLUDED => __('finance.universal_credit.values.classification.included'),
                                UniversalCreditReportItem::STATUS_EXCLUDED => __('finance.universal_credit.values.classification.excluded'),
                            ])->live()->required(),
                        Select::make('category')
                            ->label(__('finance.universal_credit.ui.fields.category'))
                            ->options($this->categoryOptions($record->direction))
                            ->required()
                            ->searchable(),
                        TextInput::make('business_use_percentage')
                            ->label(__('finance.universal_credit.ui.fields.business_use'))
                            ->numeric()->minValue(0.01)->maxValue(100)
                            ->visible(fn (Get $get): bool => $get('classification_status') === UniversalCreditReportItem::STATUS_INCLUDED)
                            ->required(fn (Get $get): bool => $get('classification_status') === UniversalCreditReportItem::STATUS_INCLUDED),
                        Textarea::make('business_use_reason')
                            ->label(__('finance.universal_credit.ui.fields.business_use_reason'))->maxLength(2000)
                            ->visible(fn (Get $get): bool => $get('classification_status') === UniversalCreditReportItem::STATUS_INCLUDED),
                        Textarea::make('classification_reason')
                            ->label(__('finance.universal_credit.ui.fields.classification_reason'))->maxLength(2000)
                            ->visible(fn (Get $get): bool => $get('classification_status') === UniversalCreditReportItem::STATUS_EXCLUDED)
                            ->required(fn (Get $get): bool => $get('classification_status') === UniversalCreditReportItem::STATUS_EXCLUDED),
                        Select::make('calculation_rule')
                            ->label(__('finance.universal_credit.ui.fields.calculation_rule'))
                            ->options([
                                UniversalCreditReportItem::RULE_BUSINESS_LOAN_INTEREST => __('finance.universal_credit.ui.calculation_rules.business_loan_interest'),
                            ]),
                    ])
                    ->action(function (UniversalCreditReportItem $record, array $data): void {
                        try {
                            app(ClassifyUniversalCreditReportItemAction::class)->execute(
                                $record,
                                auth()->user(),
                                (string) $data['classification_status'],
                                (string) $data['category'],
                                $data['business_use_percentage'] ?? 100,
                                $data['business_use_reason'] ?? null,
                                $data['classification_reason'] ?? null,
                                $data['calculation_rule'] ?? null,
                            );
                            Notification::make()->title(__('finance.universal_credit.ui.notifications.classified'))->success()->send();
                        } catch (DomainException $exception) {
                            Notification::make()->title($exception->getMessage())->danger()->send();
                        }
                    }),
            ])
            ->defaultSort('cash_date');
    }

    /** @return array<string, string> */
    private function categoryOptions(string $direction): array
    {
        $key = $direction === UniversalCreditReportItem::DIRECTION_INCOME ? 'income_categories' : 'expense_categories';
        $categories = data_get($this->getOwnerRecord()->policy_rules_snapshot, $key, []);

        return collect(is_array($categories) ? $categories : [])
            ->reject(fn (string $category): bool => $category === 'review_required')
            ->mapWithKeys(fn (string $category): array => [$category => __('finance.universal_credit.values.category.'.$category)])
            ->all();
    }

    private function canManage(): bool
    {
        return $this->getOwnerRecord()->status === UniversalCreditAssessmentPeriod::STATUS_DRAFT
            && Gate::allows('manage', $this->getOwnerRecord());
    }
}
