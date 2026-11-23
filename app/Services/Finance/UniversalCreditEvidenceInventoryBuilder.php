<?php

namespace App\Services\Finance;

use App\Models\FinancialAttachment;
use App\Models\Invoice;
use App\Models\UniversalCreditAssessmentPeriod;
use App\Models\UniversalCreditEvidencePackItem;
use App\Models\UniversalCreditReportItem;
use DomainException;
use Illuminate\Support\Str;

class UniversalCreditEvidenceInventoryBuilder
{
    /**
     * @param  array<string, mixed>  $snapshot
     * @return list<array<string, mixed>>
     */
    public function build(UniversalCreditAssessmentPeriod $period, array $snapshot): array
    {
        $inventory = [];
        $seenAttachments = [];
        $reportItems = UniversalCreditReportItem::withoutGlobalScopes()
            ->where('business_id', $period->business_id)
            ->where('assessment_period_id', $period->id)
            ->get()->keyBy('id');

        foreach ((array) data_get($snapshot, 'report.details', []) as $row) {
            if (! is_array($row)) {
                continue;
            }

            $reportItem = $reportItems->get($row['id'] ?? null);
            if (! $reportItem) {
                throw new DomainException(__('finance.universal_credit.pack.errors.source_integrity'));
            }

            $evidence = is_array($row['evidence'] ?? null) ? $row['evidence'] : [];
            $base = $this->itemDirectory($row);
            $inventory = [
                ...$inventory,
                ...$this->evidenceRows(
                    period: $period,
                    evidence: $evidence,
                    base: $base,
                    sourceType: $reportItem->source_type,
                    sourceId: (int) $reportItem->source_id,
                    fallbackName: 'item-'.$reportItem->id,
                    seenAttachments: $seenAttachments,
                ),
            ];
        }

        foreach ((array) data_get($snapshot, 'report.simplified_expenses', []) as $row) {
            if (! is_array($row)) {
                continue;
            }

            $inventory = [
                ...$inventory,
                ...$this->evidenceRows(
                    period: $period,
                    evidence: is_array($row['evidence'] ?? null) ? $row['evidence'] : [],
                    base: '03-evidence/simplified-expenses',
                    sourceType: 'UniversalCreditSimplifiedExpense',
                    sourceId: (int) ($row['id'] ?? 0),
                    fallbackName: 'simplified-'.((int) ($row['id'] ?? 0)),
                    seenAttachments: $seenAttachments,
                ),
            ];
        }

        return $inventory;
    }

    /**
     * @param  array<string, mixed>  $evidence
     * @param  array<int, true>  $seenAttachments
     * @return list<array<string, mixed>>
     */
    private function evidenceRows(
        UniversalCreditAssessmentPeriod $period,
        array $evidence,
        string $base,
        string $sourceType,
        int $sourceId,
        string $fallbackName,
        array &$seenAttachments,
    ): array {
        $ids = array_values(array_filter(array_map('intval', (array) ($evidence['attachment_ids'] ?? []))));
        $hashes = array_values((array) ($evidence['attachment_hashes'] ?? []));
        $state = (string) ($evidence['state'] ?? 'missing');

        if ($ids === []) {
            if (in_array($state, ['structured_record'], true)) {
                return [];
            }

            return [[
                'logical_path' => $base.'/'.$this->safeName($fallbackName),
                'item_type' => 'evidence',
                'source_type' => $sourceType,
                'source_id' => $sourceId,
                'attachment_id' => null,
                'inclusion_status' => UniversalCreditEvidencePackItem::EXCLUDED,
                'reason' => $state !== '' ? $state : 'missing',
                'bytes' => null,
                'sha256' => null,
                'attachment' => null,
            ]];
        }

        $rows = [];
        foreach ($ids as $index => $id) {
            $expectedHash = isset($hashes[$index]) ? (string) $hashes[$index] : null;

            if (isset($seenAttachments[$id])) {
                continue;
            }
            $seenAttachments[$id] = true;

            $attachment = FinancialAttachment::withoutGlobalScopes()
                ->where('business_id', $period->business_id)
                ->whereNull('deleted_at')
                ->find($id);

            if (! $attachment) {
                if ($expectedHash !== null) {
                    throw new DomainException(__('finance.universal_credit.pack.errors.attachment_record_missing', ['id' => $id]));
                }

                $rows[] = $this->excluded($base.'/'.$id.'-missing', $sourceType, $sourceId, $id, 'attachment_not_retained');

                continue;
            }

            if ($expectedHash !== null && ! hash_equals($expectedHash, (string) $attachment->sha256)) {
                throw new DomainException(__('finance.universal_credit.pack.errors.attachment_snapshot_mismatch', ['id' => $id]));
            }

            $path = $this->attachmentDirectory($base, $attachment).'/'.$attachment->id.'-'.$this->safeName($attachment->original_name);
            $attachmentSourceType = $attachment->attachable_type;
            $attachmentSourceId = (int) $attachment->attachable_id;
            if ($attachment->scan_status !== FinancialAttachment::SCAN_CLEAN) {
                $rows[] = $this->excluded(
                    $path,
                    $attachmentSourceType,
                    $attachmentSourceId,
                    $attachment->id,
                    'scan_'.$attachment->scan_status,
                    $attachment,
                );

                continue;
            }

            $rows[] = [
                'logical_path' => $path,
                'item_type' => 'evidence',
                'source_type' => $attachmentSourceType,
                'source_id' => $attachmentSourceId,
                'attachment_id' => $attachment->id,
                'inclusion_status' => UniversalCreditEvidencePackItem::INCLUDED,
                'reason' => null,
                'bytes' => $attachment->size,
                'sha256' => $attachment->sha256,
                'attachment' => $attachment,
            ];
        }

        return $rows;
    }

    /** @param array<string, mixed> $row */
    private function itemDirectory(array $row): string
    {
        if (($row['source_type'] ?? null) === 'BankTransaction') {
            return '03-evidence/banking/statements';
        }

        if (($row['direction'] ?? null) === 'income') {
            return '03-evidence/income/other';
        }

        return '03-evidence/expenses/receipts-and-invoices';
    }

    private function attachmentDirectory(string $fallback, FinancialAttachment $attachment): string
    {
        if ($attachment->attachable_type === (new Invoice)->getMorphClass()) {
            return '03-evidence/income/invoices';
        }

        return $fallback;
    }

    /** @return array<string, mixed> */
    private function excluded(
        string $path,
        string $sourceType,
        int $sourceId,
        ?int $attachmentId,
        string $reason,
        ?FinancialAttachment $attachment = null,
    ): array {
        return [
            'logical_path' => $path,
            'item_type' => 'evidence',
            'source_type' => $sourceType,
            'source_id' => $sourceId,
            'attachment_id' => $attachmentId,
            'inclusion_status' => UniversalCreditEvidencePackItem::EXCLUDED,
            'reason' => $reason,
            'bytes' => $attachment?->size,
            'sha256' => $attachment?->sha256,
            'attachment' => null,
        ];
    }

    private function safeName(string $name): string
    {
        $name = basename(str_replace('\\', '/', $name));
        $name = Str::ascii($name);
        $name = preg_replace('/[^A-Za-z0-9._-]+/', '-', $name) ?? '';
        $name = trim($name, '.-_');

        return $name !== '' ? mb_substr($name, 0, 150) : 'file';
    }
}
