<?php

namespace App\Filament\Pages;

use App\Actions\Finance\UpdateHmrcSandboxVrnAction;
use App\Models\Business;
use App\Models\HmrcConnection;
use App\Models\HmrcVatObligation;
use App\Models\User;
use App\Services\Hmrc\Exceptions\HmrcApiException;
use App\Services\Hmrc\Exceptions\HmrcFraudPreventionException;
use App\Services\Hmrc\Exceptions\HmrcOAuthException;
use App\Services\Hmrc\Exceptions\HmrcVatObligationSyncException;
use App\Services\Hmrc\Exceptions\HmrcVatReadException;
use App\Services\Hmrc\HmrcFraudPreventionClient;
use App\Services\Hmrc\HmrcFraudPreventionHeaders;
use App\Services\Hmrc\HmrcFraudPreventionValidationSummary;
use App\Services\Hmrc\HmrcOAuthService;
use App\Services\Hmrc\HmrcVatObligationSyncService;
use App\Services\Hmrc\HmrcVatReadService;
use DomainException;
use Filament\Actions\Action;
use Filament\Auth\MultiFactor\App\AppAuthentication;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Collection;
use InvalidArgumentException;

class HmrcConnectionPage extends BasePage
{
    /** @var array<string, mixed>|null */
    public ?array $fraudValidationSummary = null;

    /** @var array<string, mixed>|null */
    public ?array $vatReturnView = null;

    /** @var array<string, mixed>|null */
    public ?array $vatAccountView = null;

    protected string $view = 'filament.pages.hmrc-connection';

    protected static \BackedEnum|string|null $navigationIcon = 'heroicon-o-building-library';

    protected static \UnitEnum|string|null $navigationGroup = 'Finance';

    protected static ?int $navigationSort = 40;

    protected static ?string $slug = 'hmrc-connection';

    public static function getNavigationLabel(): string
    {
        return __('finance.hmrc_connection.navigation');
    }

    public function getTitle(): string
    {
        return __('finance.hmrc_connection.title');
    }

    /** @return array<string, mixed> */
    public function getConnectionSummary(): array
    {
        $connection = $this->connection();
        $status = $connection?->status ?? HmrcConnection::STATUS_DISCONNECTED;

        return [
            'environment' => (string) config('hmrc.environment', 'sandbox'),
            'status' => $status,
            'status_label' => __("finance.hmrc_connection.statuses.{$status}"),
            'scopes' => $connection?->scopes ?? [],
            'masked_vrn' => $this->maskedVrn($connection?->vrn),
            'expires_at' => $connection?->expires_at,
            'connected_at' => $connection?->connected_at,
            'refreshed_at' => $connection?->refreshed_at,
            'reconnect_reason' => $connection?->reconnect_reason,
        ];
    }

    public function refreshConnection(): void
    {
        [$business, $actor] = $this->managementContext();

        try {
            app(HmrcOAuthService::class)->refresh($business, $actor);
            Notification::make()->title(__('finance.hmrc_connection.notifications.refreshed'))->success()->send();
        } catch (HmrcOAuthException $exception) {
            Notification::make()->title($exception->getMessage())->danger()->send();
        }
    }

    public function disconnectConnection(): void
    {
        [$business, $actor] = $this->managementContext();
        app(HmrcOAuthService::class)->disconnect($business, $actor);
        Notification::make()
            ->title(__('finance.hmrc_connection.notifications.disconnected'))
            ->body(__('finance.hmrc_connection.help.external_revoke'))
            ->warning()
            ->send();
    }

    /** @param array{vrn?: string} $data */
    public function updateSandboxVrn(array $data): void
    {
        [$business, $actor] = $this->managementContext();

        try {
            app(UpdateHmrcSandboxVrnAction::class)->execute(
                $business,
                $actor,
                (string) ($data['vrn'] ?? ''),
            );

            Notification::make()
                ->title(__('finance.hmrc_connection.notifications.sandbox_vrn_updated'))
                ->success()
                ->send();
        } catch (DomainException $exception) {
            Notification::make()->title($exception->getMessage())->danger()->send();
        }
    }

    /** @param array{totp?: string} $data */
    public function validateFraudPreventionHeaders(array $data): void
    {
        [, $actor] = $this->managementContext();
        $multiFactor = $this->verifiedMultiFactor($actor, $data['totp'] ?? null);

        if ($multiFactor === null) {
            return;
        }

        try {
            $builder = app(HmrcFraudPreventionHeaders::class);
            $result = $builder->build(request(), $actor, $multiFactor);
            $response = app(HmrcFraudPreventionClient::class)->validate($result->headers);

            $this->fraudValidationSummary = app(HmrcFraudPreventionValidationSummary::class)
                ->fromResponse($response);
            $code = $this->fraudValidationSummary['code'];
            $errors = $this->fraudValidationSummary['errors'];
            $warnings = $this->fraudValidationSummary['warnings'];
            $isValid = $this->fraudValidationSummary['valid'];

            if ($isValid) {
                Notification::make()
                    ->title(__('finance.hmrc_connection.notifications.fraud_validated'))
                    ->success()
                    ->send();
            } else {
                Notification::make()
                    ->title(__('finance.hmrc_connection.notifications.fraud_invalid'))
                    ->body(__('finance.hmrc_connection.notifications.fraud_invalid_details', [
                        'code' => $code ?? 'UNKNOWN',
                        'errors' => $errors,
                        'warnings' => $warnings,
                    ]))
                    ->danger()
                    ->send();
            }
        } catch (HmrcFraudPreventionException $exception) {
            Notification::make()->title($exception->getMessage())->danger()->send();
        }
    }

    /** @param array{from?: string, to?: string, status?: string|null, totp?: string} $data */
    public function syncVatObligations(array $data): void
    {
        [$business, $actor] = $this->managementContext();
        $multiFactor = $this->verifiedMultiFactor($actor, $data['totp'] ?? null);

        if ($multiFactor === null) {
            return;
        }

        try {
            $headers = app(HmrcFraudPreventionHeaders::class)->build(request(), $actor, $multiFactor)->headers;
            $filters = [
                'from' => (string) ($data['from'] ?? ''),
                'to' => (string) ($data['to'] ?? ''),
            ];

            if (in_array($data['status'] ?? null, ['O', 'F'], true)) {
                $filters['status'] = $data['status'];
            }

            $result = app(HmrcVatObligationSyncService::class)->sync(
                $business,
                $actor,
                $filters,
                $headers,
                config('hmrc.environment') === 'sandbox'
                    ? config('hmrc.sandbox.obligations_test_scenario')
                    : null,
            );

            Notification::make()
                ->title(__('finance.hmrc_connection.notifications.obligations_synced'))
                ->body(__('finance.hmrc_connection.obligations.sync_result', $result))
                ->success()
                ->send();
        } catch (HmrcApiException|HmrcFraudPreventionException|HmrcOAuthException|HmrcVatObligationSyncException|InvalidArgumentException $exception) {
            Notification::make()->title($exception->getMessage())->danger()->send();
        }
    }

    /** @return Collection<int, HmrcVatObligation> */
    public function getVatObligations(): Collection
    {
        return HmrcVatObligation::query()
            ->where('environment', config('hmrc.environment', 'sandbox'))
            ->latest('start_date')
            ->limit(24)
            ->get();
    }

    /** @return array<string, string> */
    public function getFulfilledObligationOptions(): array
    {
        return HmrcVatObligation::query()
            ->where('environment', config('hmrc.environment', 'sandbox'))
            ->where('status', HmrcVatObligation::STATUS_FULFILLED)
            ->latest('start_date')
            ->limit(24)
            ->get()
            ->mapWithKeys(fn (HmrcVatObligation $obligation): array => [
                (string) $obligation->getKey() => $obligation->start_date->format('Y-m-d').' – '.$obligation->end_date->format('Y-m-d'),
            ])
            ->all();
    }

    /** @param array{obligation_id?: string|int, totp?: string} $data */
    public function viewVatReturn(array $data): void
    {
        [$business, $actor] = $this->managementContext();
        $multiFactor = $this->verifiedMultiFactor($actor, $data['totp'] ?? null);

        if ($multiFactor === null) {
            return;
        }

        try {
            $obligation = HmrcVatObligation::query()
                ->where('environment', config('hmrc.environment', 'sandbox'))
                ->where('status', HmrcVatObligation::STATUS_FULFILLED)
                ->whereKey($data['obligation_id'] ?? null)
                ->first();

            if (! $obligation) {
                throw HmrcVatReadException::obligationRequired();
            }

            $headers = app(HmrcFraudPreventionHeaders::class)->build(request(), $actor, $multiFactor)->headers;
            $result = app(HmrcVatReadService::class)->viewReturn(
                $business,
                $actor,
                $obligation,
                $headers,
                config('hmrc.environment') === 'sandbox'
                    ? config('hmrc.sandbox.return_test_scenario')
                    : null,
            );

            $this->vatReturnView = [
                ...$result,
                'period' => $obligation->start_date->format('Y-m-d').' – '.$obligation->end_date->format('Y-m-d'),
            ];

            Notification::make()->title(__('finance.hmrc_connection.notifications.return_retrieved'))->success()->send();
        } catch (HmrcApiException|HmrcFraudPreventionException|HmrcOAuthException|HmrcVatReadException|InvalidArgumentException $exception) {
            Notification::make()->title($exception->getMessage())->danger()->send();
        }
    }

    /** @param array{from?: string, to?: string, totp?: string} $data */
    public function retrieveVatAccount(array $data): void
    {
        [$business, $actor] = $this->managementContext();
        $multiFactor = $this->verifiedMultiFactor($actor, $data['totp'] ?? null);

        if ($multiFactor === null) {
            return;
        }

        try {
            $headers = app(HmrcFraudPreventionHeaders::class)->build(request(), $actor, $multiFactor)->headers;
            $this->vatAccountView = app(HmrcVatReadService::class)->retrieveAccount(
                $business,
                $actor,
                (string) ($data['from'] ?? ''),
                (string) ($data['to'] ?? ''),
                $headers,
                config('hmrc.environment') === 'sandbox'
                    ? config('hmrc.sandbox.liabilities_test_scenario')
                    : null,
                config('hmrc.environment') === 'sandbox'
                    ? config('hmrc.sandbox.payments_test_scenario')
                    : null,
            );

            Notification::make()->title(__('finance.hmrc_connection.notifications.account_retrieved'))->success()->send();
        } catch (HmrcApiException|HmrcFraudPreventionException|HmrcOAuthException|HmrcVatReadException|InvalidArgumentException $exception) {
            Notification::make()->title($exception->getMessage())->danger()->send();
        }
    }

    protected function getHeaderActions(): array
    {
        $connection = $this->connection();
        $canManage = $this->canManageConnection();

        return [
            Action::make('connect')
                ->label(__('finance.hmrc_connection.actions.connect'))
                ->icon('heroicon-o-link')
                ->color('primary')
                ->url(route('admin.hmrc.oauth.connect'))
                ->visible($canManage && (! $connection || $connection->status !== HmrcConnection::STATUS_CONNECTED)),
            Action::make('refresh')
                ->label(__('finance.hmrc_connection.actions.refresh'))
                ->icon('heroicon-o-arrow-path')
                ->action('refreshConnection')
                ->visible($canManage && $connection?->status === HmrcConnection::STATUS_CONNECTED),
            Action::make('disconnect')
                ->label(__('finance.hmrc_connection.actions.disconnect'))
                ->icon('heroicon-o-x-circle')
                ->color('danger')
                ->requiresConfirmation()
                ->modalDescription(__('finance.hmrc_connection.help.disconnect'))
                ->action('disconnectConnection')
                ->visible($canManage && $connection && $connection->status !== HmrcConnection::STATUS_DISCONNECTED),
            Action::make('set_sandbox_vrn')
                ->label(__('finance.hmrc_connection.actions.set_sandbox_vrn'))
                ->icon('heroicon-o-identification')
                ->color('warning')
                ->modalHeading(__('finance.hmrc_connection.sandbox_vrn.modal_title'))
                ->modalDescription(__('finance.hmrc_connection.sandbox_vrn.modal_description'))
                ->form([
                    TextInput::make('vrn')
                        ->label(__('finance.hmrc_connection.sandbox_vrn.field'))
                        ->helperText(__('finance.hmrc_connection.sandbox_vrn.help'))
                        ->placeholder('123456789')
                        ->required()
                        ->regex('/^(?:GB)?[0-9\s-]{9,20}$/'),
                ])
                ->action(fn (array $data) => $this->updateSandboxVrn($data))
                ->visible($canManage && config('hmrc.environment') === 'sandbox' && $connection !== null),
            Action::make('validate_fraud_headers')
                ->label(__('finance.hmrc_connection.actions.validate_fraud_headers'))
                ->icon('heroicon-o-shield-check')
                ->color('info')
                ->modalHeading(__('finance.hmrc_connection.fraud_validation.modal_title'))
                ->modalDescription(__('finance.hmrc_connection.fraud_validation.modal_description'))
                ->form([
                    TextInput::make('totp')
                        ->label(__('finance.hmrc_connection.fraud_validation.totp'))
                        ->password()
                        ->required()
                        ->minLength(6)
                        ->maxLength(8),
                ])
                ->action(fn (array $data) => $this->validateFraudPreventionHeaders($data))
                ->visible($canManage && config('hmrc.environment') === 'sandbox'),
            Action::make('sync_vat_obligations')
                ->label(__('finance.hmrc_connection.actions.sync_vat_obligations'))
                ->icon('heroicon-o-arrow-down-tray')
                ->color('success')
                ->modalHeading(__('finance.hmrc_connection.obligations.modal_title'))
                ->modalDescription(__('finance.hmrc_connection.obligations.modal_description'))
                ->form([
                    DatePicker::make('from')
                        ->label(__('finance.hmrc_connection.obligations.from'))
                        ->default(now()->subDays(365)->toDateString())
                        ->required(),
                    DatePicker::make('to')
                        ->label(__('finance.hmrc_connection.obligations.to'))
                        ->default(now()->toDateString())
                        ->required(),
                    Select::make('status')
                        ->label(__('finance.hmrc_connection.obligations.status_filter'))
                        ->options([
                            'O' => __('finance.hmrc_connection.obligations.statuses.open'),
                            'F' => __('finance.hmrc_connection.obligations.statuses.fulfilled'),
                        ])
                        ->placeholder(__('finance.hmrc_connection.obligations.all_statuses')),
                    TextInput::make('totp')
                        ->label(__('finance.hmrc_connection.fraud_validation.totp'))
                        ->password()
                        ->required()
                        ->minLength(6)
                        ->maxLength(8),
                ])
                ->action(fn (array $data) => $this->syncVatObligations($data))
                ->visible($canManage && $connection?->status === HmrcConnection::STATUS_CONNECTED),
            Action::make('view_vat_return')
                ->label(__('finance.hmrc_connection.actions.view_vat_return'))
                ->icon('heroicon-o-document-magnifying-glass')
                ->color('info')
                ->modalHeading(__('finance.hmrc_connection.read.return_modal_title'))
                ->modalDescription(__('finance.hmrc_connection.read.return_modal_description'))
                ->form([
                    Select::make('obligation_id')
                        ->label(__('finance.hmrc_connection.read.period'))
                        ->options(fn (): array => $this->getFulfilledObligationOptions())
                        ->required(),
                    TextInput::make('totp')
                        ->label(__('finance.hmrc_connection.fraud_validation.totp'))
                        ->password()
                        ->required()
                        ->minLength(6)
                        ->maxLength(8),
                ])
                ->action(fn (array $data) => $this->viewVatReturn($data))
                ->visible($canManage && $connection?->status === HmrcConnection::STATUS_CONNECTED && $this->getFulfilledObligationOptions() !== []),
            Action::make('retrieve_vat_account')
                ->label(__('finance.hmrc_connection.actions.retrieve_vat_account'))
                ->icon('heroicon-o-banknotes')
                ->color('info')
                ->modalHeading(__('finance.hmrc_connection.read.account_modal_title'))
                ->modalDescription(__('finance.hmrc_connection.read.account_modal_description'))
                ->form([
                    DatePicker::make('from')
                        ->label(__('finance.hmrc_connection.obligations.from'))
                        ->default(now()->subDays(365)->toDateString())
                        ->required(),
                    DatePicker::make('to')
                        ->label(__('finance.hmrc_connection.obligations.to'))
                        ->default(now()->toDateString())
                        ->required(),
                    TextInput::make('totp')
                        ->label(__('finance.hmrc_connection.fraud_validation.totp'))
                        ->password()
                        ->required()
                        ->minLength(6)
                        ->maxLength(8),
                ])
                ->action(fn (array $data) => $this->retrieveVatAccount($data))
                ->visible($canManage && $connection?->status === HmrcConnection::STATUS_CONNECTED),
        ];
    }

    private function connection(): ?HmrcConnection
    {
        return HmrcConnection::query()
            ->where('environment', config('hmrc.environment', 'sandbox'))
            ->first();
    }

    private function canManageConnection(): bool
    {
        $business = currentBusiness();
        $actor = auth()->user();

        return $business !== null && $actor !== null && $actor->businessMemberships()
            ->where('business_id', $business->getKey())
            ->where('is_active', true)
            ->whereIn('role', ['owner', 'admin'])
            ->exists();
    }

    /** @return array{Business, User} */
    private function managementContext(): array
    {
        $business = currentBusiness();
        $actor = auth()->user();

        abort_unless($business instanceof Business && $actor instanceof User && $this->canManageConnection(), 403);

        return [$business, $actor];
    }

    private function maskedVrn(?string $vrn): ?string
    {
        return filled($vrn) ? '•••••'.substr($vrn, -4) : null;
    }

    /**
     * @return array{type: string, timestamp: string, unique_reference: string}|null
     */
    private function verifiedMultiFactor(User $actor, mixed $code): ?array
    {
        $secret = $actor->getAppAuthenticationSecret();

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
}
