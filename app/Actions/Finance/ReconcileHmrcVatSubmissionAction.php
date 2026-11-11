<?php

namespace App\Actions\Finance;

use App\Models\Business;
use App\Models\HmrcConnection;
use App\Models\HmrcVatObligation;
use App\Models\HmrcVatSubmission;
use App\Models\User;
use App\Models\VatReturn;
use App\Services\Finance\VatReturnSnapshotService;
use App\Services\Hmrc\Contracts\HmrcClientInterface;
use App\Services\Hmrc\Data\HmrcApiResponse;
use App\Services\Hmrc\Exceptions\HmrcApiException;
use App\Services\Hmrc\HmrcFraudPreventionHeaders;
use App\Services\Hmrc\HmrcOAuthService;
use App\Services\Hmrc\HmrcVatReturnPayload;
use App\Services\Hmrc\HmrcVatSubmissionAuditService;
use App\Support\PermissionHelper;
use Carbon\CarbonImmutable;
use DateTimeImmutable;
use DomainException;
use Illuminate\Support\Facades\DB;
use Throwable;

final class ReconcileHmrcVatSubmissionAction
{
    public function __construct(
        private readonly HmrcClientInterface $client,
        private readonly HmrcOAuthService $oauth,
        private readonly HmrcFraudPreventionHeaders $fraudHeaders,
        private readonly VatReturnSnapshotService $snapshotService,
        private readonly HmrcVatReturnPayload $payloadService,
        private readonly HmrcVatSubmissionAuditService $audit,
    ) {}

    /**
     * @param  array{type?: string, timestamp?: string, unique_reference?: string}  $multiFactor
     * @param  array<string, string>  $fraudPreventionHeaders
     */
    public function execute(
        VatReturn $return,
        User $actor,
        array $multiFactor,
        array $fraudPreventionHeaders,
        ?string $obligationsTestScenario = null,
        ?string $returnTestScenario = null,
    ): HmrcVatSubmission {
        $this->fraudHeaders->assertComplete($fraudPreventionHeaders);
        $this->validateMultiFactor($multiFactor);
        $this->assertAuthorized($return, $actor);
        $business = Business::query()->findOrFail($return->business_id);
        $connection = $this->connection($business);

        if ($connection->expires_at?->lessThanOrEqualTo(now()->addMinute())) {
            $connection = $this->oauth->refresh($business, $actor);
        }

        $context = $this->context($return, $actor, $connection);

        try {
            $obligationsResponse = $this->client->retrieveVatObligations(
                (string) $context['connection']->access_token,
                (string) $context['connection']->vrn,
                [
                    'from' => $context['return']->period_from->format('Y-m-d'),
                    'to' => $context['return']->period_to->format('Y-m-d'),
                ],
                $fraudPreventionHeaders,
                $obligationsTestScenario,
            );
            $remoteObligation = $this->remoteObligation(
                $obligationsResponse,
                $context['obligation'],
                $context['return'],
            );

            if ($remoteObligation['status'] === HmrcVatObligation::STATUS_OPEN) {
                return $this->finish(
                    $context,
                    $actor,
                    $remoteObligation,
                    HmrcVatSubmission::RECONCILIATION_PENDING_OPEN,
                    $this->correlationId($obligationsResponse->correlationId),
                    $this->correlationId($obligationsResponse->correlationId),
                );
            }

            $returnResponse = $this->client->viewVatReturn(
                (string) $context['connection']->access_token,
                (string) $context['connection']->vrn,
                (string) $context['obligation']->period_key,
                $fraudPreventionHeaders,
                $returnTestScenario,
            );
            $remoteValues = $this->payloadService->remoteValues(
                $returnResponse->data,
                (string) $context['obligation']->period_key,
            );
            $expectedValues = array_intersect_key(
                $context['payload'],
                array_flip(HmrcVatReturnPayload::FIELDS),
            );
            $reconciliationStatus = $this->payloadService->valuesMatch($expectedValues, $remoteValues)
                ? HmrcVatSubmission::RECONCILIATION_MATCHED
                : HmrcVatSubmission::RECONCILIATION_CONFLICT;

            return $this->finish(
                $context,
                $actor,
                $remoteObligation,
                $reconciliationStatus,
                $this->correlationId($obligationsResponse->correlationId),
                $this->correlationId($returnResponse->correlationId)
                    ?? $this->correlationId($obligationsResponse->correlationId),
            );
        } catch (Throwable $exception) {
            $this->markCheckFailed($context['submission'], $actor, $exception);

            throw $exception;
        }
    }

    /**
     * @return array{
     *     return: VatReturn,
     *     submission: HmrcVatSubmission,
     *     obligation: HmrcVatObligation,
     *     connection: HmrcConnection,
     *     payload: array<string, bool|int|float|string>
     * }
     */
    private function context(VatReturn $return, User $actor, HmrcConnection $connection): array
    {
        return DB::transaction(function () use ($return, $actor, $connection): array {
            $lockedReturn = VatReturn::withoutGlobalScopes()->lockForUpdate()->findOrFail($return->id);
            $this->assertAuthorized($lockedReturn, $actor);
            $submission = HmrcVatSubmission::withoutGlobalScopes()
                ->where('business_id', $lockedReturn->business_id)
                ->where('vat_return_id', $lockedReturn->id)
                ->where('environment', $connection->environment)
                ->lockForUpdate()
                ->first();

            if (! $submission || ! $submission->canReconcile()) {
                throw new DomainException(__('finance.vat_returns.errors.reconciliation_unknown_only'));
            }

            $obligation = HmrcVatObligation::withoutGlobalScopes()
                ->whereKey($submission->hmrc_vat_obligation_id)
                ->where('business_id', $lockedReturn->business_id)
                ->where('environment', $submission->environment)
                ->lockForUpdate()
                ->first();
            $lockedConnection = HmrcConnection::withoutGlobalScopes()
                ->whereKey($connection->id)
                ->where('business_id', $lockedReturn->business_id)
                ->where('environment', $submission->environment)
                ->lockForUpdate()
                ->first();

            if (! $obligation || ! $lockedConnection) {
                throw new DomainException(__('finance.vat_returns.errors.reconciliation_context'));
            }

            $this->assertConnection($lockedConnection, $lockedReturn->business_id);

            if (! hash_equals($lockedReturn->snapshot_hash, $submission->snapshot_hash)
                || ! hash_equals($lockedReturn->snapshot_hash, $this->snapshotService->hash($lockedReturn->snapshot))) {
                throw new DomainException(__('finance.vat_returns.errors.submission_snapshot'));
            }

            $payload = $this->payloadService->build($lockedReturn, (string) $obligation->period_key);

            if (! hash_equals($submission->payload_hash, $this->payloadService->hash($payload))) {
                throw new DomainException(__('finance.vat_returns.errors.reconciliation_payload'));
            }

            return [
                'return' => $lockedReturn,
                'submission' => $submission,
                'obligation' => $obligation,
                'connection' => $lockedConnection,
                'payload' => $payload,
            ];
        }, 3);
    }

    /**
     * @return array{start_date: string, end_date: string, due_date: string, status: string, received_date: ?string}
     */
    private function remoteObligation(
        HmrcApiResponse $response,
        HmrcVatObligation $expected,
        VatReturn $return,
    ): array {
        $items = $response->data['obligations'] ?? null;

        if (! is_array($items) || ! array_is_list($items)) {
            throw new DomainException(__('finance.vat_returns.errors.reconciliation_response'));
        }

        $matches = [];
        $seen = [];

        foreach ($items as $item) {
            if (! is_array($item)) {
                throw new DomainException(__('finance.vat_returns.errors.reconciliation_response'));
            }

            $periodKey = $item['periodKey'] ?? null;
            $start = $item['start'] ?? null;
            $end = $item['end'] ?? null;
            $due = $item['due'] ?? null;
            $status = $item['status'] ?? null;
            $received = $item['received'] ?? null;

            if (! is_string($periodKey) || preg_match('/^[A-Za-z0-9#]{1,18}$/', $periodKey) !== 1
                || isset($seen[$periodKey]) || ! $this->isDate($start) || ! $this->isDate($end)
                || ! $this->isDate($due) || $start > $end || $end > $due
                || ! in_array($status, ['O', 'F'], true)
                || ($status === 'F' && ! $this->isDate($received))
                || ($received !== null && ! $this->isDate($received))) {
                throw new DomainException(__('finance.vat_returns.errors.reconciliation_response'));
            }

            $seen[$periodKey] = true;

            if ($periodKey === (string) $expected->period_key
                && $start === $return->period_from->format('Y-m-d')
                && $end === $return->period_to->format('Y-m-d')) {
                $matches[] = [
                    'start_date' => $start,
                    'end_date' => $end,
                    'due_date' => $due,
                    'status' => $status === 'O' ? HmrcVatObligation::STATUS_OPEN : HmrcVatObligation::STATUS_FULFILLED,
                    'received_date' => $status === 'F' ? $received : null,
                ];
            }
        }

        if (count($matches) !== 1) {
            throw new DomainException(__('finance.vat_returns.errors.reconciliation_obligation'));
        }

        return $matches[0];
    }

    /**
     * @param array{
     *     return: VatReturn,
     *     submission: HmrcVatSubmission,
     *     obligation: HmrcVatObligation,
     *     connection: HmrcConnection,
     *     payload: array<string, bool|int|float|string>
     * } $context
     * @param  array{start_date: string, end_date: string, due_date: string, status: string, received_date: ?string}  $remoteObligation
     */
    private function finish(
        array $context,
        User $actor,
        array $remoteObligation,
        string $reconciliationStatus,
        ?string $obligationCorrelationId,
        ?string $reconciliationCorrelationId,
    ): HmrcVatSubmission {
        return DB::transaction(function () use (
            $context,
            $actor,
            $remoteObligation,
            $reconciliationStatus,
            $obligationCorrelationId,
            $reconciliationCorrelationId,
        ): HmrcVatSubmission {
            $submission = HmrcVatSubmission::withoutGlobalScopes()->lockForUpdate()
                ->findOrFail($context['submission']->id);

            if (! $submission->canReconcile()) {
                throw new DomainException(__('finance.vat_returns.errors.reconciliation_unknown_only'));
            }

            $obligation = HmrcVatObligation::withoutGlobalScopes()->lockForUpdate()
                ->findOrFail($context['obligation']->id);
            $statusChanged = $obligation->status !== $remoteObligation['status'];
            $checkedAt = now();
            $obligation->forceFill([
                ...$remoteObligation,
                'last_seen_at' => $checkedAt,
                'status_changed_at' => $statusChanged ? $checkedAt : $obligation->status_changed_at,
                'last_synced_by' => $actor->id,
                'last_correlation_id' => $obligationCorrelationId,
            ])->save();

            $submission->update([
                'status' => match ($reconciliationStatus) {
                    HmrcVatSubmission::RECONCILIATION_MATCHED => HmrcVatSubmission::STATUS_SUBMITTED,
                    HmrcVatSubmission::RECONCILIATION_CONFLICT => HmrcVatSubmission::STATUS_OUTCOME_CONFLICT,
                    default => HmrcVatSubmission::STATUS_OUTCOME_UNKNOWN,
                },
                'reconciliation_status' => $reconciliationStatus,
                'reconciliation_count' => $submission->reconciliation_count + 1,
                'reconciled_at' => $checkedAt,
                'reconciled_by' => $actor->id,
                'hmrc_received_date' => $remoteObligation['received_date'],
                'reconciliation_correlation_id' => $reconciliationCorrelationId,
                'reconciliation_error_code' => null,
                'reconciliation_error_status' => null,
            ]);
            $eventType = match ($reconciliationStatus) {
                HmrcVatSubmission::RECONCILIATION_MATCHED => HmrcVatSubmissionAuditService::EVENT_RECONCILIATION_MATCHED,
                HmrcVatSubmission::RECONCILIATION_CONFLICT => HmrcVatSubmissionAuditService::EVENT_RECONCILIATION_CONFLICT,
                default => HmrcVatSubmissionAuditService::EVENT_RECONCILIATION_PENDING,
            };
            $this->audit->record(
                $submission,
                $eventType,
                $submission->status,
                $actor,
                [
                    'reconciliation_status' => $reconciliationStatus,
                    'hmrc_received_date' => $remoteObligation['received_date'],
                    'obligation_correlation_id' => $obligationCorrelationId,
                    'return_correlation_id' => $reconciliationCorrelationId,
                ],
            );

            HmrcConnection::withoutGlobalScopes()->whereKey($context['connection']->id)
                ->where('business_id', $submission->business_id)
                ->update(['last_successful_call_at' => $checkedAt]);

            return $submission->fresh(['submittedBy', 'reconciledBy']);
        }, 3);
    }

    private function markCheckFailed(HmrcVatSubmission $submission, User $actor, Throwable $exception): void
    {
        DB::transaction(function () use ($submission, $actor, $exception): void {
            $record = HmrcVatSubmission::withoutGlobalScopes()->lockForUpdate()->find($submission->id);

            if (! $record || ! $record->canReconcile()) {
                return;
            }

            $record->update([
                'reconciliation_status' => HmrcVatSubmission::RECONCILIATION_CHECK_FAILED,
                'reconciliation_count' => $record->reconciliation_count + 1,
                'reconciled_at' => now(),
                'reconciled_by' => $actor->id,
                'reconciliation_correlation_id' => $exception instanceof HmrcApiException
                    ? $this->correlationId($exception->correlationId)
                    : null,
                'reconciliation_error_code' => $exception instanceof HmrcApiException
                    ? mb_substr($exception->errorCode, 0, 100)
                    : 'INVALID_RECONCILIATION_RESPONSE',
                'reconciliation_error_status' => $exception instanceof HmrcApiException ? $exception->status : null,
            ]);
            $this->audit->record(
                $record,
                HmrcVatSubmissionAuditService::EVENT_RECONCILIATION_FAILED,
                $record->status,
                $actor,
                [
                    'error_code' => $record->reconciliation_error_code,
                    'error_status' => $record->reconciliation_error_status,
                    'correlation_id' => $record->reconciliation_correlation_id,
                ],
            );
        }, 3);
    }

    private function connection(Business $business): HmrcConnection
    {
        $connection = HmrcConnection::withoutGlobalScopes()
            ->where('business_id', $business->id)
            ->where('environment', config('hmrc.environment', 'sandbox'))
            ->first();
        $this->assertConnection($connection, $business->id);

        return $connection;
    }

    private function assertConnection(?HmrcConnection $connection, string $businessId): void
    {
        if (! $connection || $connection->business_id !== $businessId || ! $connection->isConnected()
            || preg_match('/^\d{9}$/', (string) $connection->vrn) !== 1
            || ! in_array('read:vat', $connection->scopes ?? [], true)) {
            throw new DomainException(__('finance.vat_returns.errors.reconciliation_connection'));
        }
    }

    private function assertAuthorized(VatReturn $return, User $actor): void
    {
        if (! $actor->businessMemberships()->where('business_id', $return->business_id)->where('is_active', true)->exists()) {
            throw new DomainException(__('finance.vat_returns.errors.not_member'));
        }

        if (! PermissionHelper::allows($actor, 'submit_tax_returns')) {
            throw new DomainException(__('finance.vat_returns.errors.cannot_submit'));
        }
    }

    private function validateMultiFactor(array $multiFactor): void
    {
        $type = $multiFactor['type'] ?? null;
        $timestamp = $multiFactor['timestamp'] ?? null;
        $reference = $multiFactor['unique_reference'] ?? null;

        try {
            $verifiedAt = is_string($timestamp) ? CarbonImmutable::parse($timestamp)->utc() : null;
        } catch (Throwable) {
            $verifiedAt = null;
        }

        if ($type !== 'TOTP' || ! $verifiedAt || $verifiedAt->lt(now()->utc()->subMinutes(2))
            || $verifiedAt->gt(now()->utc()->addSeconds(30))
            || ! is_string($reference) || preg_match('/^[a-f0-9]{64}$/', $reference) !== 1) {
            throw new DomainException(__('finance.vat_returns.errors.submission_mfa'));
        }
    }

    private function isDate(mixed $value): bool
    {
        if (! is_string($value)) {
            return false;
        }

        $date = DateTimeImmutable::createFromFormat('!Y-m-d', $value);

        return $date !== false && $date->format('Y-m-d') === $value;
    }

    private function correlationId(?string $value): ?string
    {
        return is_string($value) && preg_match('/^[A-Za-z0-9._:-]{1,100}$/', $value) === 1 ? $value : null;
    }
}
