<?php

namespace App\Filament\Pages;

use App\Models\Business;
use App\Models\HmrcConnection;
use App\Models\User;
use App\Services\Hmrc\Exceptions\HmrcFraudPreventionException;
use App\Services\Hmrc\Exceptions\HmrcOAuthException;
use App\Services\Hmrc\HmrcFraudPreventionClient;
use App\Services\Hmrc\HmrcFraudPreventionHeaders;
use App\Services\Hmrc\HmrcOAuthService;
use Filament\Actions\Action;
use Filament\Auth\MultiFactor\App\AppAuthentication;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;

class HmrcConnectionPage extends BasePage
{
    /** @var array<string, int|string|null>|null */
    public ?array $fraudValidationSummary = null;

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

    /** @param array{totp?: string} $data */
    public function validateFraudPreventionHeaders(array $data): void
    {
        [, $actor] = $this->managementContext();
        $secret = $actor->getAppAuthenticationSecret();

        if (! is_string($secret) || $secret === '') {
            Notification::make()->title(__('finance.hmrc_connection.notifications.mfa_required'))->danger()->send();

            return;
        }

        $code = (string) ($data['totp'] ?? '');
        if (! AppAuthentication::make()->verifyCode($code, $secret, true)) {
            Notification::make()->title(__('finance.hmrc_connection.notifications.mfa_invalid'))->danger()->send();

            return;
        }

        $multiFactor = [
            'type' => 'TOTP',
            'timestamp' => now()->utc()->format('Y-m-d\TH:i:s\Z'),
            'unique_reference' => hash_hmac('sha256', 'filament-totp:'.$secret, (string) config('app.key')),
        ];

        try {
            $builder = app(HmrcFraudPreventionHeaders::class);
            $result = $builder->build(request(), $actor, $multiFactor);
            $builder->assertComplete($result->headers);
            $response = app(HmrcFraudPreventionClient::class)->validate($result->headers);

            $headers = is_array($response->data['headers'] ?? null) ? $response->data['headers'] : [];
            $this->fraudValidationSummary = [
                'code' => $this->safeValidationCode($response->data['code'] ?? 'CHECKED'),
                'spec_version' => $this->safeValidationCode($response->data['specVersion'] ?? null),
                'errors' => $this->countValidationItems($headers, 'errors'),
                'warnings' => $this->countValidationItems($headers, 'warnings'),
                'checked_at' => now()->toIso8601String(),
                'correlation_id' => $response->correlationId,
            ];

            Notification::make()
                ->title(__('finance.hmrc_connection.notifications.fraud_validated'))
                ->success()
                ->send();
        } catch (HmrcFraudPreventionException $exception) {
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

    /** @param array<int, mixed> $headers */
    private function countValidationItems(array $headers, string $key): int
    {
        return array_sum(array_map(
            fn (mixed $header): int => is_array($header) && is_array($header[$key] ?? null)
                ? count($header[$key])
                : 0,
            $headers,
        ));
    }

    private function safeValidationCode(mixed $value): ?string
    {
        if (! is_string($value) || preg_match('/^[A-Za-z0-9._-]{1,80}$/', $value) !== 1) {
            return null;
        }

        return $value;
    }
}
