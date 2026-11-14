<?php

namespace App\Filament\Resources\VatReturnResource\Pages;

use App\Actions\Finance\LockVatReturnAction;
use App\Actions\Finance\ReconcileHmrcVatSubmissionAction;
use App\Actions\Finance\ReviewVatReturnAction;
use App\Actions\Finance\SubmitVatReturnAction;
use App\Actions\Finance\SupersedeVatReturnAction;
use App\Filament\Resources\VatReturnResource;
use App\Models\HmrcVatSubmission;
use App\Models\User;
use App\Models\VatReturn;
use App\Services\Hmrc\Exceptions\HmrcApiException;
use App\Services\Hmrc\Exceptions\HmrcFraudPreventionException;
use App\Services\Hmrc\Exceptions\HmrcOAuthException;
use App\Services\Hmrc\HmrcFraudPreventionHeaders;
use DomainException;
use Filament\Actions\Action;
use Filament\Auth\MultiFactor\App\AppAuthentication;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;
use Illuminate\Support\Facades\Gate;
use InvalidArgumentException;

class ViewVatReturn extends ViewRecord
{
    protected static string $resource = VatReturnResource::class;

    public function getSubheading(): ?string
    {
        return __('admin.migration.accounting.vat_returns.view_subheading');
    }

    protected function getHeaderActions(): array
    {
        /** @var VatReturn $return */
        $return = $this->record;

        return [
            Action::make('download_hmrc_receipt')
                ->label(__('finance.vat_returns.actions.download_hmrc_receipt'))
                ->icon('heroicon-o-document-arrow-down')
                ->color('gray')
                ->visible(fn (): bool => Gate::allows('downloadReceipt', $return))
                ->url(fn (): string => route('admin.finance.vat-returns.hmrc-receipt', ['vatReturn' => $return]))
                ->openUrlInNewTab(),
            Action::make('reconcile_hmrc')
                ->label(__('finance.vat_returns.actions.reconcile_hmrc'))
                ->icon('heroicon-o-arrow-path')
                ->color('warning')
                ->modalHeading(__('finance.vat_returns.submission.reconciliation_modal_title'))
                ->modalDescription(__('finance.vat_returns.submission.reconciliation_modal_description'))
                ->visible(fn (): bool => Gate::allows('reconcile', $return))
                ->form([
                    TextInput::make('totp')
                        ->label(__('finance.hmrc_connection.fraud_validation.totp'))
                        ->password()
                        ->required()
                        ->minLength(6)
                        ->maxLength(8),
                ])
                ->action(fn (array $data) => $this->reconcileWithHmrc($return, $data)),
            Action::make('submit_hmrc')
                ->label(__('finance.vat_returns.actions.submit_hmrc'))
                ->icon('heroicon-o-paper-airplane')
                ->color('danger')
                ->modalHeading(__('finance.vat_returns.submission.modal_title'))
                ->modalDescription(__('finance.vat_returns.submission.modal_description'))
                ->visible(fn (): bool => Gate::allows('submit', $return))
                ->form([
                    Toggle::make('declaration_confirmed')
                        ->label(__('finance.vat_returns.fields.submission_declaration'))
                        ->helperText(__('finance.vat_returns.help.submission_declaration'))
                        ->accepted()
                        ->required(),
                    TextInput::make('totp')
                        ->label(__('finance.hmrc_connection.fraud_validation.totp'))
                        ->password()
                        ->required()
                        ->minLength(6)
                        ->maxLength(8),
                ])
                ->action(fn (array $data) => $this->submitToHmrc($return, $data)),
            Action::make('download_accountant_pack')
                ->label(__('finance.accountant_pack.action'))
                ->icon('heroicon-o-archive-box-arrow-down')
                ->color('gray')
                ->visible(fn (): bool => $return->status === VatReturn::STATUS_LOCKED && Gate::allows('view', $return))
                ->url(fn (): string => route('admin.finance.vat-returns.accountant-pack', ['vatReturn' => $return])),
            Action::make('review')
                ->label(__('finance.vat_returns.actions.review'))
                ->icon('heroicon-o-check-badge')
                ->color('info')
                ->visible(fn (): bool => Gate::allows('review', $return))
                ->form([
                    Toggle::make('confirmed')
                        ->label(__('finance.vat_returns.fields.review_confirmation'))
                        ->helperText(__('finance.vat_returns.help.review_confirmation'))
                        ->accepted()
                        ->required(),
                    Textarea::make('notes')->label(__('finance.vat_returns.fields.review_notes'))->maxLength(2000),
                ])
                ->action(fn (array $data) => $this->runAction(
                    fn () => app(ReviewVatReturnAction::class)->execute($return, auth()->user(), (bool) ($data['confirmed'] ?? false), $data['notes'] ?? null),
                    __('finance.vat_returns.notifications.reviewed'),
                )),
            Action::make('lock')
                ->label(__('finance.vat_returns.actions.lock'))
                ->icon('heroicon-o-lock-closed')
                ->color('success')
                ->visible(fn (): bool => Gate::allows('lock', $return))
                ->requiresConfirmation()
                ->form([
                    Textarea::make('reason')->label(__('finance.vat_returns.fields.lock_reason'))->minLength(10)->maxLength(2000)->required(),
                ])
                ->action(fn (array $data) => $this->runAction(
                    fn () => app(LockVatReturnAction::class)->execute($return, auth()->user(), $data['reason']),
                    __('finance.vat_returns.notifications.locked'),
                )),
            Action::make('supersede')
                ->label(__('finance.vat_returns.actions.supersede'))
                ->icon('heroicon-o-arrow-path')
                ->color('warning')
                ->visible(fn (): bool => Gate::allows('supersede', $return))
                ->requiresConfirmation()
                ->form([
                    Textarea::make('reason')->label(__('finance.vat_returns.fields.supersede_reason'))->minLength(10)->maxLength(2000)->required(),
                ])
                ->action(fn (array $data) => $this->runAction(
                    fn () => app(SupersedeVatReturnAction::class)->execute($return, auth()->user(), $data['reason']),
                    __('finance.vat_returns.notifications.superseded'),
                )),
        ];
    }

    /** @param array{declaration_confirmed?: bool, totp?: string} $data */
    private function submitToHmrc(VatReturn $return, array $data): void
    {
        $actor = auth()->user();
        $multiFactor = $this->verifiedMultiFactor($actor, $data['totp'] ?? null);

        if ($multiFactor === null) {
            return;
        }

        try {
            $headers = app(HmrcFraudPreventionHeaders::class)->build(request(), $actor, $multiFactor)->headers;
            app(SubmitVatReturnAction::class)->execute(
                $return,
                $actor,
                (bool) ($data['declaration_confirmed'] ?? false),
                $multiFactor,
                $headers,
                config('hmrc.environment') === 'sandbox' ? config('hmrc.sandbox.submission_test_scenario') : null,
            );
            Notification::make()->title(__('finance.vat_returns.notifications.submitted'))->success()->send();
            $this->redirect(VatReturnResource::getUrl('view', ['record' => $return->id]));
        } catch (DomainException|HmrcApiException|HmrcFraudPreventionException|HmrcOAuthException|InvalidArgumentException $exception) {
            Notification::make()->title($exception->getMessage())->danger()->send();
        }
    }

    /** @param array{totp?: string} $data */
    private function reconcileWithHmrc(VatReturn $return, array $data): void
    {
        $actor = auth()->user();
        $multiFactor = $this->verifiedMultiFactor($actor, $data['totp'] ?? null);

        if ($multiFactor === null) {
            return;
        }

        try {
            $headers = app(HmrcFraudPreventionHeaders::class)->build(request(), $actor, $multiFactor)->headers;
            $submission = app(ReconcileHmrcVatSubmissionAction::class)->execute(
                $return,
                $actor,
                $multiFactor,
                $headers,
                config('hmrc.environment') === 'sandbox' ? config('hmrc.sandbox.obligations_test_scenario') : null,
                config('hmrc.environment') === 'sandbox' ? config('hmrc.sandbox.return_test_scenario') : null,
            );
            $notification = match ($submission->reconciliation_status) {
                HmrcVatSubmission::RECONCILIATION_MATCHED => 'reconciliation_matched',
                HmrcVatSubmission::RECONCILIATION_CONFLICT => 'reconciliation_conflict',
                default => 'reconciliation_pending',
            };
            $notice = Notification::make()->title(__("finance.vat_returns.notifications.{$notification}"));

            if ($submission->reconciliation_status === HmrcVatSubmission::RECONCILIATION_MATCHED) {
                $notice->success();
            } else {
                $notice->warning();
            }

            $notice->send();
            $this->redirect(VatReturnResource::getUrl('view', ['record' => $return->id]));
        } catch (DomainException|HmrcApiException|HmrcFraudPreventionException|HmrcOAuthException|InvalidArgumentException $exception) {
            Notification::make()->title($exception->getMessage())->danger()->send();
        }
    }

    /**
     * @return array{type: string, timestamp: string, unique_reference: string}|null
     */
    private function verifiedMultiFactor(?User $actor, mixed $code): ?array
    {
        $secret = $actor?->getAppAuthenticationSecret();

        if (! is_string($secret) || $secret === '') {
            Notification::make()->title(__('finance.hmrc_connection.notifications.mfa_required'))->danger()->send();

            return null;
        }

        if (! is_string($code) || ! AppAuthentication::make()->verifyCode($code, $secret, true)) {
            Notification::make()->title(__('finance.hmrc_connection.notifications.mfa_invalid'))->danger()->send();

            return null;
        }

        return [
            'type' => 'TOTP',
            'timestamp' => now()->utc()->format('Y-m-d\TH:i:s\Z'),
            'unique_reference' => hash_hmac('sha256', 'filament-totp:'.$secret, (string) config('app.key')),
        ];
    }

    private function runAction(callable $action, string $successTitle): void
    {
        try {
            $action();
            Notification::make()->title($successTitle)->success()->send();
            $this->redirect(VatReturnResource::getUrl('view', ['record' => $this->record->id]));
        } catch (DomainException $exception) {
            Notification::make()->title($exception->getMessage())->danger()->send();
        }
    }
}
