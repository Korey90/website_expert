<?php

namespace App\Filament\Resources\UniversalCreditAssessmentPeriodResource\RelationManagers;

use App\Actions\Finance\CreateUniversalCreditSimplifiedExpenseAction;
use App\Actions\Finance\VoidUniversalCreditSimplifiedExpenseAction;
use App\Filament\Resources\UniversalCreditAssessmentPeriodResource;
use App\Models\UniversalCreditAssessmentPeriod;
use App\Models\UniversalCreditSimplifiedExpense;
use DomainException;
use Filament\Actions\Action;
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

class SimplifiedExpensesRelationManager extends RelationManager
{
    protected static string $relationship = 'simplifiedExpenses';

    public static function getTitle(Model $ownerRecord, string $pageClass): string
    {
        return __('finance.universal_credit.ui.relations.simplified_expenses');
    }

    public function isReadOnly(): bool
    {
        return false;
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('type')->label(__('finance.universal_credit.ui.fields.simplified_type'))
                    ->formatStateUsing(fn (string $state): string => __('finance.universal_credit.values.simplified_type.'.$state)),
                Tables\Columns\TextColumn::make('description')->label(__('finance.universal_credit.ui.fields.description'))->limit(70)->wrap(),
                Tables\Columns\TextColumn::make('inputs')->label(__('finance.universal_credit.ui.fields.inputs'))
                    ->formatStateUsing(fn (array $state): string => collect($state)->map(
                        fn (mixed $value, string $key): string => $key.': '.$value
                    )->implode(', '))->wrap(),
                Tables\Columns\TextColumn::make('calculated_amount_pence')->label(__('finance.universal_credit.ui.fields.calculated_amount'))
                    ->formatStateUsing(fn (int $state): string => UniversalCreditAssessmentPeriodResource::money($state))->alignEnd(),
                Tables\Columns\TextColumn::make('status')->label(__('finance.universal_credit.ui.fields.status'))->badge()
                    ->formatStateUsing(fn (string $state): string => __('finance.universal_credit.values.simplified_status.'.$state))
                    ->color(fn (string $state): string => $state === UniversalCreditSimplifiedExpense::STATUS_ACTIVE ? 'success' : 'gray'),
            ])
            ->headerActions([
                Action::make('create_simplified')
                    ->label(__('finance.universal_credit.ui.actions.add_simplified_expense'))
                    ->icon('heroicon-o-plus')
                    ->visible(fn (): bool => $this->canManage())
                    ->form([
                        Select::make('type')
                            ->label(__('finance.universal_credit.ui.fields.simplified_type'))
                            ->options(collect(UniversalCreditSimplifiedExpense::types())->mapWithKeys(
                                fn (string $value): array => [$value => __('finance.universal_credit.values.simplified_type.'.$value)]
                            )->all())->live()->required(),
                        Select::make('vehicle_type')
                            ->label(__('finance.universal_credit.ui.fields.vehicle_type'))
                            ->options([
                                'car_van_or_other_motor_vehicle' => __('finance.universal_credit.ui.vehicle_types.car_van_or_other_motor_vehicle'),
                                'motorbike_or_scooter' => __('finance.universal_credit.ui.vehicle_types.motorbike_or_scooter'),
                            ])
                            ->visible(fn (Get $get): bool => $get('type') === UniversalCreditSimplifiedExpense::TYPE_VEHICLE_MILEAGE)
                            ->required(fn (Get $get): bool => $get('type') === UniversalCreditSimplifiedExpense::TYPE_VEHICLE_MILEAGE),
                        TextInput::make('miles')
                            ->label(__('finance.universal_credit.ui.fields.miles'))->numeric()->minValue(0.01)
                            ->visible(fn (Get $get): bool => $get('type') === UniversalCreditSimplifiedExpense::TYPE_VEHICLE_MILEAGE)
                            ->required(fn (Get $get): bool => $get('type') === UniversalCreditSimplifiedExpense::TYPE_VEHICLE_MILEAGE),
                        TextInput::make('hours')
                            ->label(__('finance.universal_credit.ui.fields.home_working_hours'))->integer()->minValue(1)
                            ->visible(fn (Get $get): bool => $get('type') === UniversalCreditSimplifiedExpense::TYPE_HOME_WORKING)
                            ->required(fn (Get $get): bool => $get('type') === UniversalCreditSimplifiedExpense::TYPE_HOME_WORKING),
                        TextInput::make('actual_cost_gbp')
                            ->label(__('finance.universal_credit.ui.fields.actual_cost'))->numeric()->minValue(0.01)
                            ->visible(fn (Get $get): bool => $get('type') === UniversalCreditSimplifiedExpense::TYPE_BUSINESS_PREMISES_LIVING)
                            ->required(fn (Get $get): bool => $get('type') === UniversalCreditSimplifiedExpense::TYPE_BUSINESS_PREMISES_LIVING),
                        Select::make('resident_band')
                            ->label(__('finance.universal_credit.ui.fields.resident_band'))
                            ->options([
                                'one_person' => __('finance.universal_credit.ui.resident_bands.one_person'),
                                'two_people' => __('finance.universal_credit.ui.resident_bands.two_people'),
                                'three_or_more_people' => __('finance.universal_credit.ui.resident_bands.three_or_more_people'),
                            ])
                            ->visible(fn (Get $get): bool => $get('type') === UniversalCreditSimplifiedExpense::TYPE_BUSINESS_PREMISES_LIVING)
                            ->required(fn (Get $get): bool => $get('type') === UniversalCreditSimplifiedExpense::TYPE_BUSINESS_PREMISES_LIVING),
                        Textarea::make('description')->label(__('finance.universal_credit.ui.fields.description'))->maxLength(2000),
                        FileUpload::make('evidence')
                            ->label(__('finance.universal_credit.ui.fields.evidence_file'))->storeFiles(false)->maxSize(10240),
                        Textarea::make('evidence_missing_reason')
                            ->label(__('finance.universal_credit.ui.fields.evidence_missing_reason'))->maxLength(2000),
                    ])
                    ->action(function (array $data): void {
                        try {
                            $type = (string) $data['type'];
                            $inputs = match ($type) {
                                UniversalCreditSimplifiedExpense::TYPE_VEHICLE_MILEAGE => [
                                    'vehicle_type' => $data['vehicle_type'] ?? null,
                                    'miles' => $data['miles'] ?? null,
                                ],
                                UniversalCreditSimplifiedExpense::TYPE_HOME_WORKING => ['hours' => $data['hours'] ?? null],
                                UniversalCreditSimplifiedExpense::TYPE_BUSINESS_PREMISES_LIVING => [
                                    'actual_cost_gbp' => $data['actual_cost_gbp'] ?? null,
                                    'resident_band' => $data['resident_band'] ?? null,
                                ],
                                default => [],
                            };
                            $file = $data['evidence'] ?? null;
                            app(CreateUniversalCreditSimplifiedExpenseAction::class)->execute(
                                $this->getOwnerRecord(),
                                auth()->user(),
                                $type,
                                $inputs,
                                $data['description'] ?? null,
                                $data['evidence_missing_reason'] ?? null,
                                $file instanceof UploadedFile ? $file : null,
                            );
                            Notification::make()->title(__('finance.universal_credit.ui.notifications.simplified_created'))->success()->send();
                        } catch (DomainException $exception) {
                            Notification::make()->title($exception->getMessage())->danger()->send();
                        }
                    }),
            ])
            ->actions([
                Action::make('void')
                    ->label(__('finance.universal_credit.ui.actions.void'))
                    ->icon('heroicon-o-no-symbol')->color('warning')->requiresConfirmation()
                    ->visible(fn (UniversalCreditSimplifiedExpense $record): bool => $this->canManage()
                        && $record->status === UniversalCreditSimplifiedExpense::STATUS_ACTIVE)
                    ->form([
                        Textarea::make('reason')->label(__('finance.universal_credit.ui.fields.void_reason'))
                            ->minLength(3)->maxLength(2000)->required(),
                    ])
                    ->action(function (UniversalCreditSimplifiedExpense $record, array $data): void {
                        try {
                            app(VoidUniversalCreditSimplifiedExpenseAction::class)->execute($record, auth()->user(), (string) $data['reason']);
                            Notification::make()->title(__('finance.universal_credit.ui.notifications.entry_voided'))->success()->send();
                        } catch (DomainException $exception) {
                            Notification::make()->title($exception->getMessage())->danger()->send();
                        }
                    }),
            ]);
    }

    private function canManage(): bool
    {
        return $this->getOwnerRecord()->status === UniversalCreditAssessmentPeriod::STATUS_DRAFT
            && Gate::allows('manage', $this->getOwnerRecord());
    }
}
