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
use App\Services\Hmrc\Exceptions\HmrcApiException;
use App\Services\Hmrc\HmrcFraudPreventionHeaders;
use App\Services\Hmrc\HmrcOAuthService;
use App\Services\Hmrc\HmrcVatReturnPayload;
use App\Support\PermissionHelper;
use Carbon\CarbonImmutable;
use DomainException;
use Illuminate\Support\Facades\DB;
use Throwable;

final class SubmitVatReturnAction
{
    public function __construct(
        private readonly HmrcClientInterface $client,
        private readonly HmrcOAuthService $oauth,
        private readonly HmrcFraudPreventionHeaders $fraudHeaders,
        private readonly VatReturnSnapshotService $snapshotService,
        private readonly HmrcVatReturnPayload $payloadService,
    ) {}

    /**
     * @param  array<string, string>  $fraudPreventionHeaders
     * @param  array{type?: string, timestamp?: string, unique_reference?: string}  $multiFactor
     */
    public function execute(
        VatReturn $return,
        User $actor,
        bool $declarationConfirmed,
        array $multiFactor,
        array $fraudPreventionHeaders,
        ?string $testScenario = null,
    ): HmrcVatSubmission {
        if (! $declarationConfirmed) {
            throw new DomainException(__('finance.vat_returns.errors.submission_declaration'));
        }

        $this->fraudHeaders->assertComplete($fraudPreventionHeaders);
        $mfa = $this->validateMultiFactor($multiFactor);
        $business = Business::query()->findOrFail($return->business_id);
        $this->assertAuthorized($return, $actor);
        $connection = $this->connection($business);

        if ($connection->expires_at?->lessThanOrEqualTo(now()->addMinute())) {
            $connection = $this->oauth->refresh($business, $actor);
        }

        $prepared = DB::transaction(function () use ($return, $actor, $connection, $mfa): array {
            Business::query()->lockForUpdate()->findOrFail($return->business_id);
            $lockedReturn = VatReturn::withoutGlobalScopes()->lockForUpdate()->findOrFail($return->id);
            $this->assertAuthorized($lockedReturn, $actor);
            $lockedConnection = HmrcConnection::withoutGlobalScopes()->lockForUpdate()->findOrFail($connection->id);
            $this->assertConnection($lockedConnection, $lockedReturn->business_id);

            if (HmrcVatSubmission::withoutGlobalScopes()
                ->where('business_id', $lockedReturn->business_id)
                ->where('vat_return_id', $lockedReturn->id)
                ->where('environment', $lockedConnection->environment)
                ->exists()) {
                throw new DomainException(__('finance.vat_returns.errors.submission_exists'));
            }

            if (! hash_equals($lockedReturn->snapshot_hash, $this->snapshotService->hash($lockedReturn->snapshot))) {
                throw new DomainException(__('finance.vat_returns.errors.submission_snapshot'));
            }

            $obligation = HmrcVatObligation::withoutGlobalScopes()
                ->where('business_id', $lockedReturn->business_id)
                ->where('environment', $lockedConnection->environment)
                ->where('status', HmrcVatObligation::STATUS_OPEN)
                ->whereDate('start_date', $lockedReturn->period_from)
                ->whereDate('end_date', $lockedReturn->period_to)
                ->lockForUpdate()
                ->first();

            if (! $obligation) {
                throw new DomainException(__('finance.vat_returns.errors.submission_obligation'));
            }

            $payload = $this->payloadService->build($lockedReturn, (string) $obligation->period_key);
            $payloadHash = $this->payloadService->hash($payload);
            $declaredAt = now();
            $submission = HmrcVatSubmission::withoutGlobalScopes()->create([
                'business_id' => $lockedReturn->business_id,
                'vat_return_id' => $lockedReturn->id,
                'hmrc_vat_obligation_id' => $obligation->id,
                'environment' => $lockedConnection->environment,
                'status' => HmrcVatSubmission::STATUS_PREPARED,
                'snapshot_hash' => $lockedReturn->snapshot_hash,
                'payload_hash' => $payloadHash,
                'declaration_version' => HmrcVatSubmission::DECLARATION_VERSION,
                'declared_at' => $declaredAt,
                'mfa_type' => $mfa['type'],
                'mfa_verified_at' => $mfa['verified_at'],
                'mfa_reference_hash' => hash('sha256', $mfa['unique_reference']),
                'submitted_by' => $actor->id,
            ]);

            return compact('submission', 'payload', 'lockedConnection');
        }, 3);

        /** @var HmrcVatSubmission $submission */
        $submission = $prepared['submission'];
        /** @var HmrcConnection $lockedConnection */
        $lockedConnection = $prepared['lockedConnection'];

        try {
            $response = $this->client->submitVatReturn(
                (string) $lockedConnection->access_token,
                (string) $lockedConnection->vrn,
                $prepared['payload'],
                $fraudPreventionHeaders,
                $testScenario,
            );

            if ($response->status !== 201) {
                throw new DomainException(__('finance.vat_returns.errors.submission_response'));
            }

            $receipt = $this->receipt($response->data);

            return DB::transaction(function () use ($submission, $lockedConnection, $response, $receipt): HmrcVatSubmission {
                $record = HmrcVatSubmission::withoutGlobalScopes()->lockForUpdate()->findOrFail($submission->id);
                $record->update([
                    'status' => HmrcVatSubmission::STATUS_SUBMITTED,
                    'submitted_at' => now(),
                    'processing_date' => $receipt['processing_date'],
                    'form_bundle_number' => $receipt['form_bundle_number'],
                    'payment_indicator' => $receipt['payment_indicator'],
                    'charge_reference' => $receipt['charge_reference'],
                    'correlation_id' => $this->correlationId($response->correlationId),
                ]);
                HmrcConnection::withoutGlobalScopes()->whereKey($lockedConnection->id)
                    ->where('business_id', $record->business_id)
                    ->update(['last_successful_call_at' => now()]);

                return $record->fresh(['submittedBy']);
            }, 3);
        } catch (Throwable $exception) {
            $this->markOutcomeUnknown($submission, $exception);

            throw $exception;
        }
    }

    private function assertAuthorized(VatReturn $return, User $actor): void
    {
        if ($return->status !== VatReturn::STATUS_LOCKED) {
            throw new DomainException(__('finance.vat_returns.errors.submission_locked_only'));
        }

        if (! $actor->businessMemberships()->where('business_id', $return->business_id)->where('is_active', true)->exists()) {
            throw new DomainException(__('finance.vat_returns.errors.not_member'));
        }

        if (! PermissionHelper::allows($actor, 'submit_tax_returns')) {
            throw new DomainException(__('finance.vat_returns.errors.cannot_submit'));
        }
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
            || ! in_array('write:vat', $connection->scopes ?? [], true)) {
            throw new DomainException(__('finance.vat_returns.errors.submission_connection'));
        }
    }

    /** @return array{type: string, verified_at: CarbonImmutable, unique_reference: string} */
    private function validateMultiFactor(array $multiFactor): array
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

        return ['type' => $type, 'verified_at' => $verifiedAt, 'unique_reference' => $reference];
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array{processing_date: CarbonImmutable, form_bundle_number: string, payment_indicator: ?string, charge_reference: ?string}
     */
    private function receipt(array $data): array
    {
        try {
            $processingDateValue = $data['processingDate'] ?? null;
            $processingDate = is_string($processingDateValue)
                && preg_match('/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}(?:\.\d{1,9})?(?:Z|[+-]\d{2}:?\d{2})$/', $processingDateValue) === 1
                ? CarbonImmutable::parse($processingDateValue)->utc()
                : null;
        } catch (Throwable) {
            $processingDate = null;
        }

        $bundle = $data['formBundleNumber'] ?? null;
        $payment = $data['paymentIndicator'] ?? null;
        $charge = $data['chargeRefNumber'] ?? null;

        if (! $processingDate || ! is_string($bundle) || preg_match('/^\d{12}$/', $bundle) !== 1
            || ($payment !== null && ! in_array($payment, ['DD', 'BANK'], true))
            || ($charge !== null && (! is_string($charge) || mb_strlen($charge) < 1 || mb_strlen($charge) > 16
                || preg_match('/[\x00-\x1F\x7F]/', $charge) === 1))) {
            throw new DomainException(__('finance.vat_returns.errors.submission_response'));
        }

        return [
            'processing_date' => $processingDate,
            'form_bundle_number' => $bundle,
            'payment_indicator' => $payment,
            'charge_reference' => $charge,
        ];
    }

    private function markOutcomeUnknown(HmrcVatSubmission $submission, Throwable $exception): void
    {
        HmrcVatSubmission::withoutGlobalScopes()->whereKey($submission->id)->update([
            'status' => HmrcVatSubmission::STATUS_OUTCOME_UNKNOWN,
            'failure_code' => $exception instanceof HmrcApiException ? mb_substr($exception->errorCode, 0, 100) : 'INVALID_RESPONSE',
            'failure_status' => $exception instanceof HmrcApiException ? $exception->status : null,
            'correlation_id' => $exception instanceof HmrcApiException ? $this->correlationId($exception->correlationId) : null,
        ]);
    }

    private function correlationId(?string $value): ?string
    {
        return is_string($value) && preg_match('/^[A-Za-z0-9._:-]{1,100}$/', $value) === 1 ? $value : null;
    }
}
