<?php

namespace App\Services\Hmrc;

use App\Models\Business;
use App\Models\HmrcConnection;
use App\Models\HmrcVatObligation;
use App\Models\User;
use App\Services\Hmrc\Contracts\HmrcClientInterface;
use App\Services\Hmrc\Data\HmrcApiResponse;
use App\Services\Hmrc\Exceptions\HmrcApiException;
use App\Services\Hmrc\Exceptions\HmrcVatReadException;
use DateTimeImmutable;

final class HmrcVatReadService
{
    private const RETURN_FIELDS = [
        1 => 'vatDueSales',
        2 => 'vatDueAcquisitions',
        3 => 'totalVatDue',
        4 => 'vatReclaimedCurrPeriod',
        5 => 'netVatDue',
        6 => 'totalValueSalesExVAT',
        7 => 'totalValuePurchasesExVAT',
        8 => 'totalValueGoodsSuppliedExVAT',
        9 => 'totalAcquisitionsExVAT',
    ];

    public function __construct(
        private readonly HmrcClientInterface $client,
        private readonly HmrcOAuthService $oauth,
    ) {}

    /**
     * @param  array<string, string>  $fraudPreventionHeaders
     * @return array{boxes: array<int, int|float>, fetched_at: string, correlation_id: ?string}
     */
    public function viewReturn(
        Business $business,
        User $actor,
        HmrcVatObligation $obligation,
        array $fraudPreventionHeaders,
        ?string $testScenario = null,
    ): array {
        $this->assertAuthorized($business, $actor);
        $connection = $this->connection($business);
        $obligation = $this->canonicalObligation($business, $connection, $obligation);
        $connection = $this->refreshIfNeeded($business, $actor, $connection);
        $this->assertReadScope($connection);

        try {
            $response = $this->client->viewVatReturn(
                (string) $connection->access_token,
                (string) $connection->vrn,
                (string) $obligation->period_key,
                $fraudPreventionHeaders,
                $testScenario,
            );
        } catch (HmrcApiException $exception) {
            if ($exception->status === 404) {
                throw HmrcVatReadException::returnNotFound();
            }

            throw $exception;
        }
        $boxes = $this->normalizeReturn($response, (string) $obligation->period_key);
        $fetchedAt = now();
        $this->markSuccessfulCall($business, $connection, $fetchedAt);

        return [
            'boxes' => $boxes,
            'fetched_at' => $fetchedAt->toIso8601String(),
            'correlation_id' => $this->correlationId($response->correlationId),
        ];
    }

    /**
     * @param  array<string, string>  $fraudPreventionHeaders
     * @return array{
     *     liabilities: list<array{from: string, to: string, type: string, original_amount: int|float, outstanding_amount: int|float|null, due: string|null}>,
     *     payments: list<array{amount: int|float, received: string|null}>,
     *     fetched_at: string,
     *     liabilities_correlation_id: ?string,
     *     payments_correlation_id: ?string
     * }
     */
    public function retrieveAccount(
        Business $business,
        User $actor,
        string $from,
        string $to,
        array $fraudPreventionHeaders,
        ?string $liabilitiesTestScenario = null,
        ?string $paymentsTestScenario = null,
    ): array {
        $this->assertAuthorized($business, $actor);
        $this->assertAccountDateRange($from, $to);
        $connection = $this->refreshIfNeeded($business, $actor, $this->connection($business));
        $this->assertReadScope($connection);

        $liabilitiesResponse = $this->client->retrieveVatLiabilities(
            (string) $connection->access_token,
            (string) $connection->vrn,
            $from,
            $to,
            $fraudPreventionHeaders,
            $liabilitiesTestScenario,
        );
        $liabilities = $this->normalizeLiabilities($liabilitiesResponse);

        $paymentsResponse = $this->client->retrieveVatPayments(
            (string) $connection->access_token,
            (string) $connection->vrn,
            $from,
            $to,
            $fraudPreventionHeaders,
            $paymentsTestScenario,
        );
        $payments = $this->normalizePayments($paymentsResponse);
        $fetchedAt = now();
        $this->markSuccessfulCall($business, $connection, $fetchedAt);

        return [
            'liabilities' => $liabilities,
            'payments' => $payments,
            'fetched_at' => $fetchedAt->toIso8601String(),
            'liabilities_correlation_id' => $this->correlationId($liabilitiesResponse->correlationId),
            'payments_correlation_id' => $this->correlationId($paymentsResponse->correlationId),
        ];
    }

    private function connection(Business $business): HmrcConnection
    {
        $connection = HmrcConnection::withoutGlobalScopes()
            ->where('business_id', $business->getKey())
            ->where('environment', config('hmrc.environment', 'sandbox'))
            ->first();

        if (! $connection || ! $connection->isConnected() || preg_match('/^\d{9}$/', (string) $connection->vrn) !== 1) {
            throw HmrcVatReadException::connectionRequired();
        }

        return $connection;
    }

    private function refreshIfNeeded(Business $business, User $actor, HmrcConnection $connection): HmrcConnection
    {
        if ($connection->expires_at?->lessThanOrEqualTo(now()->addMinute())) {
            return $this->oauth->refresh($business, $actor);
        }

        return $connection;
    }

    private function assertAuthorized(Business $business, User $actor): void
    {
        $authorized = $actor->businessMemberships()
            ->where('business_id', $business->getKey())
            ->where('is_active', true)
            ->whereIn('role', ['owner', 'admin'])
            ->exists();

        if (! $authorized) {
            throw HmrcVatReadException::unauthorized();
        }
    }

    private function assertReadScope(HmrcConnection $connection): void
    {
        if (! in_array('read:vat', $connection->scopes ?? [], true)) {
            throw HmrcVatReadException::readScopeRequired();
        }
    }

    private function canonicalObligation(
        Business $business,
        HmrcConnection $connection,
        HmrcVatObligation $obligation,
    ): HmrcVatObligation {
        $canonical = HmrcVatObligation::withoutGlobalScopes()
            ->whereKey($obligation->getKey())
            ->where('business_id', $business->getKey())
            ->where('environment', $connection->environment)
            ->where('status', HmrcVatObligation::STATUS_FULFILLED)
            ->first();

        if (! $canonical || preg_match('/^[A-Za-z0-9#]{1,18}$/', (string) $canonical->period_key) !== 1) {
            throw HmrcVatReadException::obligationRequired();
        }

        return $canonical;
    }

    /** @return array<int, int|float> */
    private function normalizeReturn(HmrcApiResponse $response, string $expectedPeriodKey): array
    {
        if (($response->data['periodKey'] ?? null) !== $expectedPeriodKey) {
            throw HmrcVatReadException::invalidReturnResponse();
        }

        $boxes = [];

        foreach (self::RETURN_FIELDS as $box => $field) {
            $value = $response->data[$field] ?? null;

            if (! $this->isAmount($value) || ($box >= 6 && ! is_int($value))) {
                throw HmrcVatReadException::invalidReturnResponse();
            }

            $boxes[$box] = $value;
        }

        return $boxes;
    }

    /** @return list<array{from: string, to: string, type: string, original_amount: int|float, outstanding_amount: int|float|null, due: string|null}> */
    private function normalizeLiabilities(HmrcApiResponse $response): array
    {
        $items = $response->data['liabilities'] ?? null;

        if (! is_array($items) || ! array_is_list($items)) {
            throw HmrcVatReadException::invalidLiabilitiesResponse();
        }

        return array_map(function (mixed $item): array {
            $taxPeriod = is_array($item) ? ($item['taxPeriod'] ?? null) : null;
            $from = is_array($taxPeriod) ? ($taxPeriod['from'] ?? null) : null;
            $to = is_array($taxPeriod) ? ($taxPeriod['to'] ?? null) : null;
            $type = is_array($item) ? ($item['type'] ?? null) : null;
            $original = is_array($item) ? ($item['originalAmount'] ?? null) : null;
            $outstanding = is_array($item) ? ($item['outstandingAmount'] ?? null) : null;
            $due = is_array($item) ? ($item['due'] ?? null) : null;

            if (! $this->isDate($from) || ! $this->isDate($to) || $from > $to
                || ! is_string($type) || trim($type) === '' || mb_strlen($type) > 100
                || ! $this->isAmount($original)
                || ($outstanding !== null && ! $this->isAmount($outstanding))
                || ($due !== null && ! $this->isDate($due))) {
                throw HmrcVatReadException::invalidLiabilitiesResponse();
            }

            return [
                'from' => $from,
                'to' => $to,
                'type' => trim($type),
                'original_amount' => $original,
                'outstanding_amount' => $outstanding,
                'due' => $due,
            ];
        }, $items);
    }

    /** @return list<array{amount: int|float, received: string|null}> */
    private function normalizePayments(HmrcApiResponse $response): array
    {
        $items = $response->data['payments'] ?? null;

        if (! is_array($items) || ! array_is_list($items)) {
            throw HmrcVatReadException::invalidPaymentsResponse();
        }

        return array_map(function (mixed $item): array {
            $amount = is_array($item) ? ($item['amount'] ?? null) : null;
            $received = is_array($item) ? ($item['received'] ?? null) : null;

            if (! $this->isAmount($amount) || ($received !== null && ! $this->isDate($received))) {
                throw HmrcVatReadException::invalidPaymentsResponse();
            }

            return ['amount' => $amount, 'received' => $received];
        }, $items);
    }

    private function isAmount(mixed $value): bool
    {
        return (is_int($value) || is_float($value))
            && is_finite((float) $value)
            && abs((float) $value) <= 99999999999999.99;
    }

    private function isDate(mixed $value): bool
    {
        if (! is_string($value)) {
            return false;
        }

        $date = DateTimeImmutable::createFromFormat('!Y-m-d', $value);

        return $date !== false && $date->format('Y-m-d') === $value;
    }

    private function assertAccountDateRange(string $from, string $to): void
    {
        if (! $this->isDate($from) || ! $this->isDate($to)
            || $from < '2017-12-01' || $from > $to || $to > today()->toDateString()) {
            throw HmrcVatReadException::invalidAccountDateRange();
        }
    }

    private function markSuccessfulCall(Business $business, HmrcConnection $connection, mixed $at): void
    {
        HmrcConnection::withoutGlobalScopes()
            ->whereKey($connection->getKey())
            ->where('business_id', $business->getKey())
            ->where('environment', $connection->environment)
            ->update(['last_successful_call_at' => $at]);
    }

    private function correlationId(?string $value): ?string
    {
        return is_string($value) && preg_match('/^[A-Za-z0-9._:-]{1,100}$/', $value) === 1
            ? $value
            : null;
    }
}
