<?php

namespace App\Services\Hmrc;

use App\Models\Business;
use App\Models\HmrcConnection;
use App\Models\HmrcSandboxEvidenceEvent;
use App\Models\HmrcVatObligation;
use App\Models\HmrcVatSubmission;

final class HmrcSandboxEvidenceExporter
{
    public function __construct(
        private readonly HmrcSandboxEvidenceRecorder $evidence,
        private readonly HmrcVatSubmissionAuditService $submissionAudit,
    ) {}

    /** @return array<string, mixed> */
    public function build(Business $business): array
    {
        $integrity = $this->evidence->verify($business);
        $connection = HmrcConnection::withoutGlobalScopes()
            ->where('business_id', $business->getKey())
            ->where('environment', 'sandbox')
            ->first();
        $events = HmrcSandboxEvidenceEvent::withoutGlobalScopes()
            ->where('business_id', $business->getKey())
            ->where('environment', 'sandbox')
            ->orderBy('sequence')
            ->get()
            ->map(fn (HmrcSandboxEvidenceEvent $event): array => [
                'sequence' => $event->sequence,
                'operation' => $event->operation,
                'outcome' => $event->outcome,
                'actor_reference' => $this->reference('actor', $event->actor_id),
                'occurred_at' => $event->occurred_at->utc()->format('Y-m-d\TH:i:s\Z'),
                'correlation_id' => $event->correlation_id,
                'summary' => $event->summary,
                'previous_hash' => $event->previous_hash,
                'event_hash' => $event->event_hash,
            ])
            ->all();
        $submissions = HmrcVatSubmission::withoutGlobalScopes()
            ->where('business_id', $business->getKey())
            ->where('environment', 'sandbox')
            ->orderBy('id')
            ->get()
            ->map(function (HmrcVatSubmission $submission): array {
                $audit = $this->submissionAudit->verify($submission);

                return [
                    'submission_reference' => $this->reference('submission', $submission->getKey()),
                    'status' => $submission->status,
                    'declared_at' => $submission->declared_at?->utc()->format('Y-m-d\TH:i:s\Z'),
                    'submitted_at' => $submission->submitted_at?->utc()->format('Y-m-d\TH:i:s\Z'),
                    'processing_date' => $submission->processing_date?->utc()->format('Y-m-d\TH:i:s\Z'),
                    'payment_indicator' => $submission->payment_indicator,
                    'correlation_id' => $submission->correlation_id,
                    'snapshot_hash' => $submission->snapshot_hash,
                    'payload_hash' => $submission->payload_hash,
                    'declaration_version' => $submission->declaration_version,
                    'audit_event_count' => $audit['count'],
                    'audit_head_hash' => $audit['head_hash'],
                ];
            })
            ->all();
        $obligationCounts = HmrcVatObligation::withoutGlobalScopes()
            ->where('business_id', $business->getKey())
            ->where('environment', 'sandbox')
            ->selectRaw('status, COUNT(*) as aggregate')
            ->groupBy('status')
            ->pluck('aggregate', 'status');

        $document = [
            'schema' => 'website-expert-ni-hmrc-sandbox-evidence-v1',
            'generated_at' => now()->utc()->format('Y-m-d\TH:i:s\Z'),
            'application' => [
                'name' => 'Website Expert NI',
                'environment' => 'sandbox',
                'product_id' => (string) config('hmrc.fraud_prevention.product_id'),
                'product_version' => (string) config('hmrc.fraud_prevention.product_version'),
            ],
            'business' => [
                'name' => $business->name,
                'reference' => $this->reference('business', $business->getKey()),
            ],
            'connection' => [
                'status' => $connection?->status ?? HmrcConnection::STATUS_DISCONNECTED,
                'scopes' => collect($connection?->scopes ?? [])->sort()->values()->all(),
                'vrn_present' => filled($connection?->vrn),
                'connected_at' => $connection?->connected_at?->utc()->format('Y-m-d\TH:i:s\Z'),
                'last_successful_call_at' => $connection?->last_successful_call_at?->utc()->format('Y-m-d\TH:i:s\Z'),
            ],
            'obligations' => [
                'open_count' => (int) ($obligationCounts[HmrcVatObligation::STATUS_OPEN] ?? 0),
                'fulfilled_count' => (int) ($obligationCounts[HmrcVatObligation::STATUS_FULFILLED] ?? 0),
            ],
            'evidence_events' => $events,
            'submissions' => $submissions,
            'integrity' => [
                'evidence_event_count' => $integrity['count'],
                'evidence_head_hash' => $integrity['head_hash'],
                'submission_chains_verified' => count($submissions),
            ],
        ];
        $document['payload_sha256'] = hash('sha256', json_encode(
            $this->canonicalize($document),
            JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE,
        ));

        return $document;
    }

    private function reference(string $type, mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        return hash_hmac('sha256', $type.':'.(string) $value, (string) config('app.key'));
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
