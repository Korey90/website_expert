<?php

namespace App\Filament\Resources\UniversalCreditAssessmentPeriodResource\RelationManagers;

use App\Actions\Finance\CreateUniversalCreditManualEntryAction;
use App\Actions\Finance\VoidUniversalCreditManualEntryAction;
use App\Filament\Resources\UniversalCreditAssessmentPeriodResource;
use App\Models\UniversalCreditAssessmentPeriod;
use App\Models\UniversalCreditManualEntry;
use App\Models\UniversalCreditReportItem;
use DomainException;
use Filament\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Gate;

class ManualEntriesRelationManager extends RelationManager
{
    protected static string $relationship = 'manualEntries';

    public static function getTitle(Model $ownerRecord, string $pageClass): string
    {
        return __('finance.universal_credit.ui.relations.manual_entries');
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
                    ->formatStateUsing(fn (string $state): string => __('finance.universal_credit.values.direction.'.$state)),
                Tables\Columns\TextColumn::make('event_type')->label(__('finance.universal_credit.ui.fields.event_type'))
                    ->formatStateUsing(fn (string $state): string => __('finance.universal_credit.ui.event_types.'.$state)),
                Tables\Columns\TextColumn::make('description')->label(__('finance.universal_credit.ui.fields.description'))->limit(60)->wrap(),
                Tables\Columns\TextColumn::make('amount_gbp_pence')->label(__('finance.universal_credit.ui.fields.amount_gbp'))
                    ->formatStateUsing(fn (int $state): string => UniversalCreditAssessmentPeriodResource::money($state))->alignEnd(),
                Tables\Columns\TextColumn::make('status')->label(__('finance.universal_credit.ui.fields.status'))->badge()
                    ->formatStateUsing(fn (string $state): string => __('finance.universal_credit.ui.entry_statuses.'.$state))
                    ->color(fn (string $state): string => $state === UniversalCreditManualEntry::STATUS_ACTIVE ? 'success' : 'gray'),
            ])
            ->headerActions([
                Action::make('create_manual')
                    ->label(__('finance.universal_credit.ui.actions.add_manual_entry'))
                    ->icon('heroicon-o-plus')
                    ->visible(fn (): bool => $this->canManage())
                    ->form([
                        Select::make('direction')
                            ->label(__('finance.universal_credit.ui.fields.direction'))
                            ->options(collect(UniversalCreditReportItem::directions())->mapWithKeys(
                                fn (string $value): array => [$value => __('finance.universal_credit.values.direction.'.$value)]
                            )->all())->live()->required(),
                        Select::make('event_type')
                            ->label(__('finance.universal_credit.ui.fields.event_type'))
                            ->options(collect(UniversalCreditManualEntry::eventTypes())->mapWithKeys(
                                fn (string $value): array => [$value => __('finance.universal_credit.ui.event_types.'.$value)]
                            )->all())->required(),
                        Select::make('category')
                            ->label(__('finance.universal_credit.ui.fields.category'))
                            ->options(fn (Get $get): array => $this->categoryOptions((string) $get('direction')))
                            ->searchable()->required(),
                        DatePicker::make('cash_date')
                            ->label(__('finance.universal_credit.ui.fields.cash_date'))
                            ->minDate($this->getOwnerRecord()->date_from)
                            ->maxDate($this->getOwnerRecord()->date_to)
                            ->native(false)->required(),
                        TextInput::make('original_amount')
                            ->label(__('finance.universal_credit.ui.fields.original_amount'))->numeric()->minValue(0.01)->required(),
                        TextInput::make('original_currency')
                            ->label(__('finance.universal_credit.ui.fields.currency'))->default('GBP')->length(3)->live()->required(),
                        TextInput::make('gbp_exchange_rate')
                            ->label(__('finance.universal_credit.ui.fields.gbp_exchange_rate'))
                            ->visible(fn (Get $get): bool => strtoupper((string) $get('original_currency')) !== 'GBP')
                            ->required(fn (Get $get): bool => strtoupper((string) $get('original_currency')) !== 'GBP'),
                        TextInput::make('exchange_rate_source')
                            ->label(__('finance.universal_credit.ui.fields.exchange_rate_source'))
                            ->visible(fn (Get $get): bool => strtoupper((string) $get('original_currency')) !== 'GBP')
                            ->required(fn (Get $get): bool => strtoupper((string) $get('original_currency')) !== 'GBP'),
                        DatePicker::make('exchange_rate_date')
                            ->label(__('finance.universal_credit.ui.fields.exchange_rate_date'))
                            ->native(false)
                            ->visible(fn (Get $get): bool => strtoupper((string) $get('original_currency')) !== 'GBP')
                            ->required(fn (Get $get): bool => strtoupper((string) $get('original_currency')) !== 'GBP'),
                        Textarea::make('description')
                            ->label(__('finance.universal_credit.ui.fields.description'))->minLength(3)->maxLength(2000)->required(),
                        FileUpload::make('evidence')
                            ->label(__('finance.universal_credit.ui.fields.evidence_file'))->storeFiles(false)->maxSize(10240),
                        Textarea::make('evidence_missing_reason')
                            ->label(__('finance.universal_credit.ui.fields.evidence_missing_reason'))->maxLength(2000),
                    ])
                    ->action(function (array $data): void {
                        try {
                            $file = $data['evidence'] ?? null;
                            app(CreateUniversalCreditManualEntryAction::class)->execute(
                                $this->getOwnerRecord(),
                                auth()->user(),
                                $data,
                                $file instanceof UploadedFile ? $file : null,
                            );
                            Notification::make()->title(__('finance.universal_credit.ui.notifications.manual_created'))->success()->send();
                        } catch (DomainException $exception) {
                            Notification::make()->title($exception->getMessage())->danger()->send();
                        }
                    }),
            ])
            ->actions([
                Action::make('void')
                    ->label(__('finance.universal_credit.ui.actions.void'))
                    ->icon('heroicon-o-no-symbol')->color('warning')->requiresConfirmation()
                    ->visible(fn (UniversalCreditManualEntry $record): bool => $this->canManage()
                        && $record->status === UniversalCreditManualEntry::STATUS_ACTIVE)
                    ->form([
                        Textarea::make('reason')->label(__('finance.universal_credit.ui.fields.void_reason'))
                            ->minLength(3)->maxLength(2000)->required(),
                    ])
                    ->action(function (UniversalCreditManualEntry $record, array $data): void {
                        try {
                            app(VoidUniversalCreditManualEntryAction::class)->execute($record, auth()->user(), (string) $data['reason']);
                            Notification::make()->title(__('finance.universal_credit.ui.notifications.entry_voided'))->success()->send();
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
