<?php

namespace App\Services\Hmrc;

use App\Models\Business;
use App\Models\HmrcSandboxEvidenceEvent;
use App\Models\User;
use DomainException;
use Illuminate\Support\Facades\DB;

final class HmrcSandboxEvidenceRecorder
{
    public const OPERATION_FPH_VALIDATION = 'fph_validation';

    public const OPERATION_FPH_FEEDBACK = 'fph_feedback';

    public const OPERATION_OBLIGATIONS = 'obligations';

    public const OPERATION_VAT_RETURN = 'vat_return';

    public const OPERATION_LIABILITIES = 'liabilities';

    public const OPERATION_PAYMENTS = 'payments';

    private const OPERATIONS = [
        self::OPERATION_FPH_VALIDATION => [
            'outcomes' => ['valid', 'invalid'],
            'summary' => ['code', 'spec_version', 'errors', 'warnings'],
        ],
        self::OPERATION_FPH_FEEDBACK => [
            'outcomes' => ['succeeded'],
            'summary' => ['request_count'],
        ],
        self::OPERATION_OBLIGATIONS => [
            'outcomes' => ['succeeded'],
            'summary' => ['created', 'updated', 'unchanged', 'total'],
        ],
        self::OPERATION_VAT_RETURN => [
            'outcomes' => ['succeeded'],
            'summary' => ['box_count'],
        ],
        self::OPERATION_LIABILITIES => [
            'outcomes' => ['succeeded'],
            'summary' => ['item_count'],
        ],
        self::OPERATION_PAYMENTS => [
            'outcomes' => ['succeeded'],
            'summary' => ['item_count'],
        ],
    ];

    /** @param array<string, scalar|null> $summary */
    public function record(
        Business $business,
        User $actor,
        string $operation,
        string $outcome,
        ?string $correlationId,
        array $summary,
    ): HmrcSandboxEvidenceEvent {
        $this->assertInput($business, $actor, $operation, $outcome, $correlationId, $summary);

        return DB::transaction(function () use ($business, $actor, $operation, $outcome, $correlationId, $summary): HmrcSandboxEvidenceEvent {
            Business::query()->whereKey($business->getKey())->lockForUpdate()->firstOrFail();
            $previous = HmrcSandboxEvidenceEvent::withoutGlobalScopes()
                ->where('business_id', $business->getKey())
                ->where('environment', 'sandbox')
                ->latest('sequence')
                ->first();
            $occurredAt = now()->utc()->startOfSecond();
            $attributes = [
                'business_id' => (string) $business->getKey(),
                'environment' => 'sandbox',
                'sequence' => ($previous?->sequence ?? 0) + 1,
                'operation' => $operation,
                'outcome' => $outcome,
                'actor_id' => $actor->getAuthIdentifier(),
                'occurred_at' => $occurredAt,
                'correlation_id' => $correlationId,
                'summary' => $summary,
                'previous_hash' => $previous?->event_hash,
            ];
            $attributes['event_hash'] = $this->hash($attributes);

            return HmrcSandboxEvidenceEvent::withoutGlobalScopes()->create($attributes);
        }, 3);
    }

    /** @return array{count: int, head_hash: string|null} */
    public function verify(Business $business): array
    {
        $events = HmrcSandboxEvidenceEvent::withoutGlobalScopes()
            ->where('business_id', $business->getKey())
            ->where('environment', 'sandbox')
            ->orderBy('sequence')
            ->get();
        $previousHash = null;

        foreach ($events as $index => $event) {
            $attributes = [
                'business_id' => $event->business_id,
                'environment' => $event->environment,
                'sequence' => $event->sequence,
                'operation' => $event->operation,
                'outcome' => $event->outcome,
                'actor_id' => $event->actor_id,
                'occurred_at' => $event->occurred_at,
                'correlation_id' => $event->correlation_id,
                'summary' => $event->summary,
                'previous_hash' => $event->previous_hash,
            ];

            if ($event->sequence !== $index + 1
                || $event->previous_hash !== $previousHash
                || ! hash_equals($event->event_hash, $this->hash($attributes))) {
                throw new DomainException('HMRC sandbox evidence integrity check failed.');
            }

            $previousHash = $event->event_hash;
        }

        return ['count' => $events->count(), 'head_hash' => $previousHash];
    }

    /** @param array<string, scalar|null> $summary */
    private function assertInput(
        Business $business,
        User $actor,
        string $operation,
        string $outcome,
        ?string $correlationId,
        array $summary,
    ): void {
        if (config('hmrc.environment') !== 'sandbox') {
            throw new DomainException('HMRC sandbox evidence can only be recorded in sandbox mode.');
        }

        $definition = self::OPERATIONS[$operation] ?? null;
        $activeMembership = $actor->businessMemberships()
            ->where('business_id', $business->getKey())
            ->where('is_active', true)
            ->exists();

        if (! $definition || ! in_array($outcome, $definition['outcomes'], true) || ! $activeMembership) {
            throw new DomainException('Invalid HMRC sandbox evidence event.');
        }

        if ($correlationId !== null && preg_match('/^[A-Za-z0-9._:-]{1,100}$/', $correlationId) !== 1) {
            throw new DomainException('Invalid HMRC sandbox evidence correlation ID.');
        }

        $keys = array_keys($summary);
        sort($keys);
        $allowed = $definition['summary'];
        sort($allowed);

        if ($keys !== $allowed) {
            throw new DomainException('Invalid HMRC sandbox evidence summary.');
        }

        foreach ($summary as $value) {
            if (is_int($value) && $value >= 0) {
                continue;
            }

            if ($value === null || (is_string($value) && preg_match('/^[A-Za-z0-9._-]{1,80}$/', $value) === 1)) {
                continue;
            }

            throw new DomainException('Invalid HMRC sandbox evidence summary.');
        }
    }

    /** @param array<string, mixed> $attributes */
    private function hash(array $attributes): string
    {
        $occurredAt = $attributes['occurred_at'];
        $attributes['occurred_at'] = $occurredAt instanceof \DateTimeInterface
            ? $occurredAt->setTimezone(new \DateTimeZone('UTC'))->format('Y-m-d\TH:i:s\Z')
            : (string) $occurredAt;

        return hash('sha256', json_encode(
            $this->canonicalize($attributes),
            JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE,
        ));
    }

    private function canonicalize(mixed $value): mixed
    {
        if (! is_array($value)) {
            return $value;
        }

        if (! array_is_list($value)) {
            ksort($value);
        }

        foreach ($value as $key => $item) {
            $value[$key] = $this->canonicalize($item);
        }

        return $value;
    }
}
