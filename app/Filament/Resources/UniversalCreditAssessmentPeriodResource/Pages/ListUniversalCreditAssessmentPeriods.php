<?php

namespace App\Filament\Resources\UniversalCreditAssessmentPeriodResource\Pages;

use App\Actions\Finance\CreateUniversalCreditAssessmentPeriodAction;
use App\Actions\Finance\UpdateUniversalCreditProfileAction;
use App\Filament\Resources\UniversalCreditAssessmentPeriodResource;
use App\Models\AccountingProfile;
use App\Models\UniversalCreditProfile;
use App\Models\User;
use App\Support\PermissionHelper;
use DomainException;
use Filament\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;

class ListUniversalCreditAssessmentPeriods extends ListRecords
{
    protected static string $resource = UniversalCreditAssessmentPeriodResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('configure_profile')
                ->label(__('finance.universal_credit.ui.actions.configure_profile'))
                ->icon('heroicon-o-cog-6-tooth')
                ->color('gray')
                ->visible(fn (): bool => $this->canManageProfile())
                ->fillForm(function (): array {
                    $profile = UniversalCreditProfile::withoutGlobalScopes()
                        ->where('business_id', currentBusiness()?->id)
                        ->first();

                    return [
                        'is_enabled' => (bool) $profile?->is_enabled,
                        'vat_reporting_method' => $profile?->vat_reporting_method,
                        'vat_method_effective_from' => $profile?->vat_method_effective_from?->toDateString(),
                        'default_assessment_period_start_day' => $profile?->default_assessment_period_start_day,
                    ];
                })
                ->form([
                    Toggle::make('is_enabled')
                        ->label(__('finance.universal_credit.ui.fields.enabled'))
                        ->helperText(__('finance.universal_credit.ui.help.enabled')),
                    Select::make('vat_reporting_method')
                        ->label(__('finance.universal_credit.ui.fields.vat_method'))
                        ->options([
                            UniversalCreditProfile::VAT_INCLUSIVE => __('finance.universal_credit.ui.vat_methods.inclusive'),
                            UniversalCreditProfile::VAT_EXCLUSIVE => __('finance.universal_credit.ui.vat_methods.exclusive'),
                        ])
                        ->visible(fn (): bool => $this->isVatRegistered()),
                    DatePicker::make('vat_method_effective_from')
                        ->label(__('finance.universal_credit.ui.fields.vat_effective_from'))
                        ->native(false)
                        ->visible(fn (): bool => $this->isVatRegistered()),
                    TextInput::make('default_assessment_period_start_day')
                        ->label(__('finance.universal_credit.ui.fields.default_start_day'))
                        ->helperText(__('finance.universal_credit.ui.help.default_start_day'))
                        ->numeric()
                        ->minValue(1)
                        ->maxValue(31),
                ])
                ->action(fn (array $data) => $this->runAction(
                    fn () => app(UpdateUniversalCreditProfileAction::class)->execute(
                        currentBusiness(),
                        auth()->user(),
                        $data,
                    ),
                    __('finance.universal_credit.ui.notifications.profile_saved'),
                )),
            Action::make('create_period')
                ->label(__('finance.universal_credit.ui.actions.create_period'))
                ->icon('heroicon-o-plus')
                ->visible(fn (): bool => auth()->user() instanceof User
                    && PermissionHelper::allows(auth()->user(), 'manage_universal_credit_reporting'))
                ->form([
                    DatePicker::make('date_from')
                        ->label(__('finance.universal_credit.ui.fields.date_from'))
                        ->required()
                        ->native(false),
                    DatePicker::make('date_to')
                        ->label(__('finance.universal_credit.ui.fields.date_to'))
                        ->required()
                        ->native(false),
                ])
                ->action(function (array $data): void {
                    try {
                        $period = app(CreateUniversalCreditAssessmentPeriodAction::class)->execute(
                            currentBusiness(),
                            auth()->user(),
                            (string) $data['date_from'],
                            (string) $data['date_to'],
                        );
                        Notification::make()->title(__('finance.universal_credit.ui.notifications.period_created'))->success()->send();
                        $this->redirect(UniversalCreditAssessmentPeriodResource::getUrl('view', ['record' => $period]));
                    } catch (DomainException $exception) {
                        Notification::make()->title($exception->getMessage())->danger()->send();
                    }
                }),
        ];
    }

    private function canManageProfile(): bool
    {
        $user = auth()->user();
        $business = currentBusiness();

        return $user instanceof User
            && $business !== null
            && PermissionHelper::allows($user, 'manage_universal_credit_reporting')
            && $user->businessMemberships()
                ->where('business_id', $business->id)
                ->where('is_active', true)
                ->whereIn('role', ['owner', 'admin'])
                ->exists();
    }

    private function isVatRegistered(): bool
    {
        return (bool) AccountingProfile::withoutGlobalScopes()
            ->where('business_id', currentBusiness()?->id)
            ->value('vat_registered');
    }

    private function runAction(callable $action, string $success): void
    {
        try {
            $action();
            Notification::make()->title($success)->success()->send();
            $this->redirect(UniversalCreditAssessmentPeriodResource::getUrl());
        } catch (DomainException $exception) {
            Notification::make()->title($exception->getMessage())->danger()->send();
        }
    }
}
