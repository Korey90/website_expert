<?php

namespace App\Services\Hmrc;

use App\Models\HmrcVatSubmission;
use App\Models\HmrcVatSubmissionEvent;
use App\Models\User;
use DomainException;
use Illuminate\Support\Facades\DB;

final class HmrcVatSubmissionAuditService
{
    public const EVENT_PREPARED = 'prepared';

    public const EVENT_SUBMITTED = 'submitted';

    public const EVENT_OUTCOME_UNKNOWN = 'outcome_unknown';

    public const EVENT_RECONCILIATION_PENDING = 'reconciliation_pending';

    public const EVENT_RECONCILIATION_MATCHED = 'reconciliation_matched';

    public const EVENT_RECONCILIATION_CONFLICT = 'reconciliation_conflict';

    public const EVENT_RECONCILIATION_FAILED = 'reconciliation_failed';

    public const EVENT_RECEIPT_GENERATED = 'receipt_generated';

    private const EVENT_TYPES = [
        self::EVENT_PREPARED,
        self::EVENT_SUBMITTED,
        self::EVENT_OUTCOME_UNKNOWN,
        self::EVENT_RECONCILIATION_PENDING,
        self::EVENT_RECONCILIATION_MATCHED,
        self::EVENT_RECONCILIATION_CONFLICT,
        self::EVENT_RECONCILIATION_FAILED,
        self::EVENT_RECEIPT_GENERATED,
    ];

    /** @param array<string, mixed> $metadata */
    public function record(
        HmrcVatSubmission $submission,
        string $eventType,
        string $status,
        ?User $actor,
        array $metadata = [],
    ): HmrcVatSubmissionEvent {
        if (! in_array($eventType, self::EVENT_TYPES, true) || trim($status) === '' || mb_strlen($status) > 30) {
            throw new DomainException(__('finance.vat_returns.errors.audit_event'));
        }

        $metadata = $this->sanitize($metadata);

        return DB::transaction(function () use ($submission, $eventType, $status, $actor, $metadata): HmrcVatSubmissionEvent {
            $locked = HmrcVatSubmission::withoutGlobalScopes()->lockForUpdate()->findOrFail($submission->id);
            $previous = HmrcVatSubmissionEvent::withoutGlobalScopes()
                ->where('hmrc_vat_submission_id', $locked->id)
                ->latest('sequence')
                ->first();
            $sequence = ($previous?->sequence ?? 0) + 1;
            $occurredAt = now()->utc()->startOfSecond();
            $attributes = [
                'business_id' => $locked->business_id,
                'hmrc_vat_submission_id' => $locked->id,
                'sequence' => $sequence,
                'event_type' => $eventType,
                'status' => $status,
                'actor_id' => $actor?->id,
                'occurred_at' => $occurredAt,
                'metadata' => $metadata,
                'previous_hash' => $previous?->event_hash,
            ];
            $attributes['event_hash'] = $this->hash($attributes);

            return HmrcVatSubmissionEvent::withoutGlobalScopes()->create($attributes);
        }, 3);
    }

    /** @return array{count: int, head_hash: string} */
    public function verify(HmrcVatSubmission $submission): array
    {
        $events = HmrcVatSubmissionEvent::withoutGlobalScopes()
            ->where('business_id', $submission->business_id)
            ->where('hmrc_vat_submission_id', $submission->id)
            ->orderBy('sequence')
            ->get();

        if ($events->isEmpty()) {
            throw new DomainException(__('finance.vat_returns.errors.audit_integrity'));
        }

        $previousHash = null;

        foreach ($events as $index => $event) {
            $expectedSequence = $index + 1;
            $attributes = [
                'business_id' => $event->business_id,
                'hmrc_vat_submission_id' => $event->hmrc_vat_submission_id,
                'sequence' => $event->sequence,
                'event_type' => $event->event_type,
                'status' => $event->status,
                'actor_id' => $event->actor_id,
                'occurred_at' => $event->occurred_at,
                'metadata' => $event->metadata,
                'previous_hash' => $event->previous_hash,
            ];

            if ($event->sequence !== $expectedSequence
                || $event->previous_hash !== $previousHash
                || ! hash_equals($event->event_hash, $this->hash($attributes))) {
                throw new DomainException(__('finance.vat_returns.errors.audit_integrity'));
            }

            $previousHash = $event->event_hash;
        }

        return ['count' => $events->count(), 'head_hash' => (string) $previousHash];
    }

    /** @param array<string, mixed> $attributes */
    private function hash(array $attributes): string
    {
        $occurredAt = $attributes['occurred_at'];
        $attributes['occurred_at'] = $occurredAt instanceof \DateTimeInterface
            ? $occurredAt->setTimezone(new \DateTimeZone('UTC'))->format('Y-m-d\TH:i:s\Z')
            : (string) $occurredAt;
        $canonical = $this->canonicalize($attributes);

        return hash('sha256', json_encode($canonical, JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
    }

    /** @param array<string, mixed> $metadata
     * @return array<string, mixed>
     */
    private function sanitize(array $metadata): array
    {
        $sanitizeValue = function (mixed $value) use (&$sanitizeValue): mixed {
            if (is_array($value)) {
                if (count($value) > 30) {
                    throw new DomainException(__('finance.vat_returns.errors.audit_event'));
                }

                return array_map($sanitizeValue, $value);
            }

            if ($value === null || is_bool($value) || is_int($value) || is_float($value)) {
                return $value;
            }

            if (! is_string($value) || mb_strlen($value) > 255 || preg_match('/[\x00-\x1F\x7F]/', $value) === 1) {
                throw new DomainException(__('finance.vat_returns.errors.audit_event'));
            }

            return $value;
        };

        return $sanitizeValue($metadata);
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
