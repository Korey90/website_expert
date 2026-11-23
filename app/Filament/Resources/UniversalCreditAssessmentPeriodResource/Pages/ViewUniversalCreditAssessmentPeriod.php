<?php

namespace App\Filament\Resources\UniversalCreditAssessmentPeriodResource\Pages;

use App\Actions\Finance\CalculateUniversalCreditAssessmentPeriodAction;
use App\Actions\Finance\CollectUniversalCreditSourcesAction;
use App\Actions\Finance\LockUniversalCreditAssessmentPeriodAction;
use App\Actions\Finance\MarkUniversalCreditAssessmentPeriodReportedAction;
use App\Actions\Finance\RequestUniversalCreditEvidencePackAction;
use App\Actions\Finance\ReviewUniversalCreditAssessmentPeriodAction;
use App\Actions\Finance\SupersedeUniversalCreditAssessmentPeriodAction;
use App\Filament\Resources\UniversalCreditAssessmentPeriodResource;
use App\Models\UniversalCreditAssessmentPeriod;
use DomainException;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Gate;

class ViewUniversalCreditAssessmentPeriod extends ViewRecord
{
    protected static string $resource = UniversalCreditAssessmentPeriodResource::class;

    protected function getHeaderActions(): array
    {
        /** @var UniversalCreditAssessmentPeriod $period */
        $period = $this->record;

        return [
            Action::make('collect')
                ->label(__('finance.universal_credit.ui.actions.collect'))
                ->icon('heroicon-o-arrow-path')
                ->visible(fn (): bool => $this->canManage($period) && $period->status === UniversalCreditAssessmentPeriod::STATUS_DRAFT)
                ->action(function () use ($period): void {
                    $this->runAction(function () use ($period): string {
                        $result = app(CollectUniversalCreditSourcesAction::class)->execute($period, auth()->user());

                        return __('finance.universal_credit.ui.notifications.sources_collected', [
                            'created' => $result->created,
                            'updated' => $result->updated,
                            'unchanged' => $result->unchanged,
                        ]);
                    });
                }),
            Action::make('calculate')
                ->label(__('finance.universal_credit.ui.actions.calculate'))
                ->icon('heroicon-o-calculator')
                ->color('info')
                ->visible(fn (): bool => $this->canManage($period) && $period->status === UniversalCreditAssessmentPeriod::STATUS_DRAFT)
                ->action(fn () => $this->runAction(function () use ($period): string {
                    app(CalculateUniversalCreditAssessmentPeriodAction::class)->execute($period, auth()->user());

                    return __('finance.universal_credit.ui.notifications.calculated');
                })),
            Action::make('review')
                ->label(__('finance.universal_credit.ui.actions.review'))
                ->icon('heroicon-o-check-badge')
                ->color('info')
                ->visible(fn (): bool => $this->canManage($period) && $period->status === UniversalCreditAssessmentPeriod::STATUS_DRAFT)
                ->form([
                    Toggle::make('confirmed')
                        ->label(__('finance.universal_credit.ui.fields.review_confirmation'))
                        ->accepted()
                        ->required(),
                    Toggle::make('warnings_confirmed')
                        ->label(__('finance.universal_credit.ui.fields.warnings_confirmation'))
                        ->helperText(__('finance.universal_credit.ui.help.warnings_confirmation')),
                    Textarea::make('notes')->label(__('finance.universal_credit.ui.fields.review_notes'))->maxLength(2000),
                ])
                ->action(fn (array $data) => $this->runAction(function () use ($period, $data): string {
                    app(ReviewUniversalCreditAssessmentPeriodAction::class)->execute(
                        $period,
                        auth()->user(),
                        (bool) ($data['confirmed'] ?? false),
                        (bool) ($data['warnings_confirmed'] ?? false),
                        $data['notes'] ?? null,
                    );

                    return __('finance.universal_credit.ui.notifications.reviewed');
                })),
            Action::make('lock')
                ->label(__('finance.universal_credit.ui.actions.lock'))
                ->icon('heroicon-o-lock-closed')
                ->color('success')
                ->visible(fn (): bool => $this->canManage($period) && $period->status === UniversalCreditAssessmentPeriod::STATUS_IN_REVIEW)
                ->requiresConfirmation()
                ->form([
                    Textarea::make('reason')
                        ->label(__('finance.universal_credit.ui.fields.lock_reason'))
                        ->minLength(10)
                        ->maxLength(2000)
                        ->required(),
                ])
                ->action(fn (array $data) => $this->runAction(function () use ($period, $data): string {
                    app(LockUniversalCreditAssessmentPeriodAction::class)->execute($period, auth()->user(), (string) $data['reason']);

                    return __('finance.universal_credit.ui.notifications.locked');
                })),
            Action::make('generate_pack')
                ->label(__('finance.universal_credit.ui.actions.generate_pack'))
                ->icon('heroicon-o-archive-box')
                ->color('success')
                ->visible(fn (): bool => Gate::allows('export', $period)
                    && in_array($period->status, [
                        UniversalCreditAssessmentPeriod::STATUS_LOCKED,
                        UniversalCreditAssessmentPeriod::STATUS_REPORTED,
                    ], true))
                ->action(fn () => $this->runAction(function () use ($period): string {
                    app(RequestUniversalCreditEvidencePackAction::class)->execute($period, auth()->user(), app()->getLocale());

                    return __('finance.universal_credit.ui.notifications.pack_requested');
                })),
            Action::make('mark_reported')
                ->label(__('finance.universal_credit.ui.actions.mark_reported'))
                ->icon('heroicon-o-paper-airplane')
                ->color('primary')
                ->visible(fn (): bool => $this->canManage($period) && $period->status === UniversalCreditAssessmentPeriod::STATUS_LOCKED)
                ->modalDescription(__('finance.universal_credit.ui.help.mark_reported'))
                ->form([
                    Toggle::make('confirmed')
                        ->label(__('finance.universal_credit.ui.fields.report_confirmation'))
                        ->accepted()
                        ->required(),
                    TextInput::make('confirmation_reference')
                        ->label(__('finance.universal_credit.ui.fields.confirmation_reference'))
                        ->maxLength(150),
                    FileUpload::make('confirmation')
                        ->label(__('finance.universal_credit.ui.fields.confirmation_file'))
                        ->storeFiles(false)
                        ->maxSize(10240),
                ])
                ->action(fn (array $data) => $this->runAction(function () use ($period, $data): string {
                    if (! (bool) ($data['confirmed'] ?? false)) {
                        throw new DomainException(__('finance.universal_credit.ui.errors.report_confirmation_required'));
                    }

                    $file = $data['confirmation'] ?? null;
                    app(MarkUniversalCreditAssessmentPeriodReportedAction::class)->execute(
                        $period,
                        auth()->user(),
                        $data['confirmation_reference'] ?? null,
                        $file instanceof UploadedFile ? $file : null,
                    );

                    return __('finance.universal_credit.ui.notifications.reported');
                })),
            Action::make('supersede')
                ->label(__('finance.universal_credit.ui.actions.supersede'))
                ->icon('heroicon-o-no-symbol')
                ->color('warning')
                ->visible(fn (): bool => $this->canManage($period) && $period->status !== UniversalCreditAssessmentPeriod::STATUS_SUPERSEDED)
                ->requiresConfirmation()
                ->form([
                    Textarea::make('reason')
                        ->label(__('finance.universal_credit.ui.fields.supersede_reason'))
                        ->minLength(10)
                        ->maxLength(2000)
                        ->required(),
                ])
                ->action(fn (array $data) => $this->runAction(function () use ($period, $data): string {
                    app(SupersedeUniversalCreditAssessmentPeriodAction::class)->execute($period, auth()->user(), (string) $data['reason']);

                    return __('finance.universal_credit.ui.notifications.superseded');
                })),
            ActionGroup::make([
                $this->exportAction($period, 'csv'),
                $this->exportAction($period, 'xlsx'),
                $this->exportAction($period, 'pdf'),
            ])->label(__('finance.universal_credit.ui.actions.export'))
                ->icon('heroicon-o-arrow-down-tray')
                ->visible(fn (): bool => Gate::allows('export', $period)),
        ];
    }

    private function exportAction(UniversalCreditAssessmentPeriod $period, string $format): Action
    {
        return Action::make('export_'.$format)
            ->label(__('finance.universal_credit.ui.actions.export_format', ['format' => strtoupper($format)]))
            ->url(fn (): string => route('admin.finance.universal-credit.assessment-periods.report', [
                'assessmentPeriod' => $period,
                'format' => $format,
            ]));
    }

    private function canManage(UniversalCreditAssessmentPeriod $period): bool
    {
        return Gate::allows('manage', $period);
    }

    private function runAction(callable $action): void
    {
        try {
            $title = $action();
            Notification::make()->title($title)->success()->send();
            $this->redirect(UniversalCreditAssessmentPeriodResource::getUrl('view', ['record' => $this->record->id]));
        } catch (DomainException $exception) {
            Notification::make()->title($exception->getMessage())->danger()->send();
        }
    }
}
