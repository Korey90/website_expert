<?php

namespace App\Services\Hmrc;

use App\Models\Business;
use App\Models\HmrcConnection;
use App\Models\HmrcVatObligation;
use App\Models\User;
use App\Services\Hmrc\Contracts\HmrcClientInterface;
use App\Services\Hmrc\Data\HmrcApiResponse;
use App\Services\Hmrc\Exceptions\HmrcVatObligationSyncException;
use DateTimeImmutable;
use Illuminate\Support\Facades\DB;

class HmrcVatObligationSyncService
{
    public function __construct(
        private readonly HmrcClientInterface $client,
        private readonly HmrcOAuthService $oauth,
    ) {}

    /**
     * @param  array{from?: string, to?: string, status?: 'O'|'F'}  $filters
     * @param  array<string, string>  $fraudPreventionHeaders
     * @return array{created: int, updated: int, unchanged: int, total: int, correlation_id: ?string}
     */
    public function sync(
        Business $business,
        User $actor,
        array $filters,
        array $fraudPreventionHeaders,
        ?string $testScenario = null,
    ): array {
        $this->assertAuthorized($business, $actor);
        $connection = $this->connection($business);

        if ($connection->expires_at?->lessThanOrEqualTo(now()->addMinute())) {
            $connection = $this->oauth->refresh($business, $actor);
        }

        if (! in_array('read:vat', $connection->scopes ?? [], true)) {
            throw HmrcVatObligationSyncException::readScopeRequired();
        }

        $response = $this->client->retrieveVatObligations(
            (string) $connection->access_token,
            (string) $connection->vrn,
            $filters,
            $fraudPreventionHeaders,
            $testScenario,
        );
        $obligations = $this->normalize($response);
        $syncedAt = now();
        $correlationId = $this->correlationId($response->correlationId);

        $result = DB::transaction(function () use (
            $business,
            $actor,
            $connection,
            $obligations,
            $syncedAt,
            $correlationId,
        ): array {
            $created = 0;
            $updated = 0;
            $unchanged = 0;

            foreach ($obligations as $item) {
                $obligation = HmrcVatObligation::withoutGlobalScopes()
                    ->where('business_id', $business->getKey())
                    ->where('environment', $connection->environment)
                    ->where('period_key', $item['period_key'])
                    ->lockForUpdate()
                    ->first();

                if (! $obligation) {
                    HmrcVatObligation::withoutGlobalScopes()->create([
                        'business_id' => $business->getKey(),
                        'environment' => $connection->environment,
                        ...$item,
                        'first_seen_at' => $syncedAt,
                        'last_seen_at' => $syncedAt,
                        'status_changed_at' => $syncedAt,
                        'last_synced_by' => $actor->getAuthIdentifier(),
                        'last_correlation_id' => $correlationId,
                    ]);
                    $created++;

                    continue;
                }

                $businessFields = [
                    'start_date' => $item['start_date'],
                    'end_date' => $item['end_date'],
                    'due_date' => $item['due_date'],
                    'status' => $item['status'],
                    'received_date' => $item['received_date'],
                ];
                $changed = collect($businessFields)->contains(
                    fn (mixed $value, string $field): bool => $this->comparable($obligation->{$field}) !== $value,
                );
                $statusChanged = $obligation->status !== $item['status'];

                $obligation->forceFill([
                    ...$businessFields,
                    'last_seen_at' => $syncedAt,
                    'status_changed_at' => $statusChanged ? $syncedAt : $obligation->status_changed_at,
                    'last_synced_by' => $actor->getAuthIdentifier(),
                    'last_correlation_id' => $correlationId,
                ])->save();

                $changed ? $updated++ : $unchanged++;
            }

            HmrcConnection::withoutGlobalScopes()
                ->whereKey($connection->getKey())
                ->where('business_id', $business->getKey())
                ->update(['last_successful_call_at' => $syncedAt]);

            return compact('created', 'updated', 'unchanged');
        }, 3);

        return [
            ...$result,
            'total' => count($obligations),
            'correlation_id' => $correlationId,
        ];
    }

    private function connection(Business $business): HmrcConnection
    {
        $connection = HmrcConnection::withoutGlobalScopes()
            ->where('business_id', $business->getKey())
            ->where('environment', config('hmrc.environment', 'sandbox'))
            ->first();

        if (! $connection || ! $connection->isConnected() || ! preg_match('/^\d{9}$/', (string) $connection->vrn)) {
            throw HmrcVatObligationSyncException::connectionRequired();
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
            throw HmrcVatObligationSyncException::unauthorized();
        }
    }

    /**
     * @return list<array{period_key: string, start_date: string, end_date: string, due_date: string, status: string, received_date: ?string}>
     */
    private function normalize(HmrcApiResponse $response): array
    {
        $raw = $response->data['obligations'] ?? null;

        if (! is_array($raw) || ! array_is_list($raw)) {
            throw HmrcVatObligationSyncException::invalidResponse();
        }

        $normalized = [];
        $keys = [];

        foreach ($raw as $item) {
            if (! is_array($item)) {
                throw HmrcVatObligationSyncException::invalidResponse();
            }

            $periodKey = $item['periodKey'] ?? null;
            $start = $item['start'] ?? null;
            $end = $item['end'] ?? null;
            $due = $item['due'] ?? null;
            $status = $item['status'] ?? null;
            $received = $item['received'] ?? null;

            if (! is_string($periodKey) || preg_match('/^[A-Za-z0-9#]{1,18}$/', $periodKey) !== 1
                || isset($keys[$periodKey])
                || ! $this->isDate($start) || ! $this->isDate($end) || ! $this->isDate($due)
                || $start > $end || $end > $due
                || ! in_array($status, ['O', 'F'], true)
                || ($status === 'F' && ! $this->isDate($received))
                || ($received !== null && ! $this->isDate($received))) {
                throw HmrcVatObligationSyncException::invalidResponse();
            }

            $keys[$periodKey] = true;
            $normalized[] = [
                'period_key' => $periodKey,
                'start_date' => $start,
                'end_date' => $end,
                'due_date' => $due,
                'status' => $status === 'O' ? HmrcVatObligation::STATUS_OPEN : HmrcVatObligation::STATUS_FULFILLED,
                'received_date' => $status === 'F' ? $received : null,
            ];
        }

        return $normalized;
    }

    private function isDate(mixed $value): bool
    {
        if (! is_string($value)) {
            return false;
        }

        $date = DateTimeImmutable::createFromFormat('!Y-m-d', $value);

        return $date !== false && $date->format('Y-m-d') === $value;
    }

    private function comparable(mixed $value): mixed
    {
        return $value instanceof \DateTimeInterface ? $value->format('Y-m-d') : $value;
    }

    private function correlationId(?string $value): ?string
    {
        return is_string($value) && preg_match('/^[A-Za-z0-9._:-]{1,100}$/', $value) === 1
            ? $value
            : null;
    }
}
