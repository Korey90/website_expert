<?php

namespace App\Services\Finance;

use App\Models\AccountingProfile;
use App\Models\Business;
use App\Models\FinancialAttachment;
use App\Models\UniversalCreditAssessmentPeriod;
use App\Models\UniversalCreditEvidencePack;
use App\Models\UniversalCreditEvidencePackItem;
use Barryvdh\DomPDF\Facade\Pdf;
use DomainException;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Throwable;
use ZipArchive;

class UniversalCreditEvidencePackGenerator
{
    public function __construct(
        private readonly UniversalCreditCalculationHashService $hasher,
        private readonly UniversalCreditEvidenceInventoryBuilder $inventoryBuilder,
        private readonly UniversalCreditReportDocumentFactory $documentFactory,
        private readonly FinanceReportExportService $exporter,
    ) {}

    /**
     * @return array{disk: string, path: string, filename: string, zip_sha256: string, bytes: int, included_count: int, excluded_count: int, items: list<array<string, mixed>>}
     */
    public function generate(UniversalCreditEvidencePack $pack): array
    {
        if (! class_exists(ZipArchive::class)) {
            throw new DomainException(__('finance.universal_credit.pack.errors.zip_unavailable'));
        }

        $period = UniversalCreditAssessmentPeriod::withoutGlobalScopes()
            ->where('business_id', $pack->business_id)->findOrFail($pack->assessment_period_id);
        $snapshot = $period->review_snapshot;

        if (! is_array($snapshot)
            || ! hash_equals((string) $pack->snapshot_hash, (string) $period->review_snapshot_hash)
            || ! hash_equals((string) $period->review_snapshot_hash, $this->hasher->hash($snapshot))) {
            throw new DomainException(__('finance.universal_credit.pack.errors.snapshot_integrity'));
        }

        $business = Business::withoutGlobalScopes()->findOrFail($pack->business_id);
        $inventory = $this->inventoryBuilder->build($period, $snapshot);
        $token = (string) Str::uuid();
        $staging = storage_path('app/private/universal-credit-pack-staging-'.$token);
        $zipPath = storage_path('app/private/universal-credit-pack-'.$token.'.zip');
        $finalPath = trim((string) config('finance.universal_credit.evidence_pack.prefix'), '/').'/'.$pack->business_id.'/'.$pack->id.'/'.$token.'.zip';
        $diskName = (string) config('finance.universal_credit.evidence_pack.disk', 'financial');
        $stored = false;

        File::ensureDirectoryExists($staging);

        try {
            $items = $this->writeReports($staging, $pack, $period, $business, $snapshot);
            $items = [...$items, ...$this->copyEvidence($staging, $inventory)];
            $items = $this->writeManifest($staging, $pack, $period, $business, $snapshot, $items);
            $this->writeZip($zipPath, $items, $pack->snapshot_hash);

            $zipHash = hash_file('sha256', $zipPath);
            $zipBytes = File::size($zipPath);
            if (! is_string($zipHash)) {
                throw new DomainException(__('finance.universal_credit.pack.errors.zip_hash'));
            }

            $source = fopen($zipPath, 'rb');
            if (! is_resource($source)) {
                throw new DomainException(__('finance.universal_credit.pack.errors.storage_write'));
            }

            try {
                $stored = Storage::disk($diskName)->writeStream($finalPath, $source);
            } finally {
                fclose($source);
            }

            if (! $stored) {
                throw new DomainException(__('finance.universal_credit.pack.errors.storage_write'));
            }

            $this->verifyStoredZip($diskName, $finalPath, $zipHash, $zipBytes);
            $included = collect($items)->where('inclusion_status', UniversalCreditEvidencePackItem::INCLUDED)->count();
            $excluded = collect($items)->where('inclusion_status', UniversalCreditEvidencePackItem::EXCLUDED)->count();

            return [
                'disk' => $diskName,
                'path' => $finalPath,
                'filename' => 'universal-credit-evidence-'.$this->safeName($period->number).'-v'.$pack->version.'.zip',
                'zip_sha256' => $zipHash,
                'bytes' => $zipBytes,
                'included_count' => $included,
                'excluded_count' => $excluded,
                'items' => array_map(fn (array $item): array => $this->persistentItem($item), $items),
            ];
        } catch (Throwable $exception) {
            if ($stored || Storage::disk($diskName)->exists($finalPath)) {
                Storage::disk($diskName)->delete($finalPath);
            }

            throw $exception;
        } finally {
            File::deleteDirectory($staging);
            File::delete($zipPath);
        }
    }

    /**
     * @param  array<string, mixed>  $snapshot
     * @return list<array<string, mixed>>
     */
    private function writeReports(
        string $staging,
        UniversalCreditEvidencePack $pack,
        UniversalCreditAssessmentPeriod $period,
        Business $business,
        array $snapshot,
    ): array {
        $report = data_get($snapshot, 'report');
        if (! is_array($report)) {
            throw new DomainException(__('finance.universal_credit.pack.errors.snapshot_integrity'));
        }

        $document = $this->documentFactory->make($business, $report);
        $files = [];
        foreach (['pdf', 'xlsx', 'csv'] as $format) {
            $files[] = $this->put(
                $staging,
                '01-summary/universal-credit-summary.'.$format,
                $this->responseContent($this->exporter->download($document, $format)),
                'report',
            );
        }

        $reconciliationDocument = $document;
        $reconciliationDocument['title'] = __('finance.universal_credit.report.reconciliation');
        $reconciliationDocument['filename'] = 'bank-and-cash-reconciliation';
        $tables = $document['tables'];
        $reconciliationTable = end($tables);
        if (! is_array($reconciliationTable)) {
            throw new DomainException(__('finance.universal_credit.pack.errors.snapshot_integrity'));
        }
        $reconciliationDocument['tables'] = [$reconciliationTable];
        $files[] = $this->put(
            $staging,
            '02-details/bank-and-cash-reconciliation.xlsx',
            $this->responseContent($this->exporter->download($reconciliationDocument, 'xlsx')),
            'report',
        );

        $details = collect((array) ($report['details'] ?? []));
        $files[] = $this->writeCsv($staging, '02-details/income-detail.csv', $this->detailRows(
            $details->where('direction', 'income')->where('classification_status', 'included')->values()->all(),
        ));
        $files[] = $this->writeCsv($staging, '02-details/expense-detail.csv', $this->detailRows(
            $details->where('direction', 'expense')->where('classification_status', 'included')->values()->all(),
        ));
        $excluded = $details->where('classification_status', 'excluded')->values()->all();
        $files[] = $this->writeCsv($staging, '02-details/excluded-and-private.csv', $this->detailRows($excluded));
        $files[] = $this->writeCsv($staging, '02-details/missing-evidence-and-warnings.csv', $this->warningRows($snapshot, $details->all()));
        $files[] = $this->writeCsv($staging, '04-audit/source-inventory.csv', $this->detailRows($details->all()));
        $files[] = $this->writeCsv($staging, '04-audit/exclusions.csv', $this->detailRows($excluded));

        $profileName = AccountingProfile::withoutGlobalScopes()->where('business_id', $business->id)->value('legal_name');
        $businessName = filled($profileName) ? $profileName : $business->name;
        $metadata = [
            __('finance.exports.business') => $businessName,
            __('finance.universal_credit.report.period_number') => $period->number,
            __('finance.exports.period') => $period->date_from->toDateString().' - '.$period->date_to->toDateString(),
            __('finance.universal_credit.report.snapshot_hash') => $period->review_snapshot_hash,
        ];
        $files[] = $this->put($staging, '01-summary/review-and-declaration.pdf', Pdf::loadView(
            'pdf.universal-credit-pack-document',
            [
                'title' => __('finance.universal_credit.pack.review_title'),
                'notice' => __('finance.universal_credit.pack.not_submission'),
                'metadata' => $metadata,
                'sections' => [[
                    'title' => __('finance.universal_credit.pack.review_audit'),
                    'rows' => [
                        [__('finance.universal_credit.pack.reviewed_at'), $period->reviewed_at?->toIso8601String()],
                        [__('finance.universal_credit.pack.locked_at'), $period->locked_at?->toIso8601String()],
                        [__('finance.universal_credit.pack.warning_count'), (string) $period->preflight_warning_count],
                        [__('finance.universal_credit.pack.lock_reason'), $period->lock_reason],
                    ],
                ]],
            ],
        )->setPaper('a4')->output(), 'report');
        $files[] = $this->put($staging, '02-details/calculation-policy.pdf', Pdf::loadView(
            'pdf.universal-credit-pack-document',
            [
                'title' => __('finance.universal_credit.pack.policy_title'),
                'notice' => __('finance.universal_credit.pack.policy_notice'),
                'metadata' => $metadata,
                'sections' => [[
                    'title' => __('finance.universal_credit.pack.policy_snapshot'),
                    'text' => json_encode($period->policy_rules_snapshot, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR),
                ]],
            ],
        )->setPaper('a4')->output(), 'report');

        $readme = implode(PHP_EOL, [
            __('finance.universal_credit.pack.readme_title'),
            __('finance.universal_credit.pack.not_submission'),
            __('finance.exports.business').': '.$businessName,
            __('finance.exports.period').': '.$period->date_from->toDateString().' - '.$period->date_to->toDateString(),
            __('finance.universal_credit.report.snapshot_hash').': '.$period->review_snapshot_hash,
        ]).PHP_EOL;
        $files[] = $this->put($staging, 'README.txt', $readme, 'metadata');
        $files[] = $this->put($staging, '04-audit/generation-metadata.json', json_encode([
            'schema_version' => 1,
            'pack_id' => $pack->id,
            'pack_version' => $pack->version,
            'assessment_period_id' => $period->id,
            'assessment_period_number' => $period->number,
            'review_snapshot_hash' => $period->review_snapshot_hash,
            'generated_at' => now()->utc()->toIso8601String(),
            'locale' => $pack->locale,
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR).PHP_EOL, 'metadata');

        return $files;
    }

    /**
     * @param  list<array<string, mixed>>  $inventory
     * @return list<array<string, mixed>>
     */
    private function copyEvidence(string $staging, array $inventory): array
    {
        $maximumCount = (int) config('finance.universal_credit.evidence_pack.max_attachments', 100);
        $maximumBytes = (int) config('finance.universal_credit.evidence_pack.max_attachment_bytes', 100 * 1024 * 1024);
        $attachmentCount = collect($inventory)->whereNotNull('attachment_id')->count();
        if ($attachmentCount > $maximumCount) {
            throw new DomainException(__('finance.universal_credit.pack.errors.attachment_count'));
        }

        $bytes = 0;
        foreach ($inventory as &$item) {
            if ($item['inclusion_status'] !== UniversalCreditEvidencePackItem::INCLUDED) {
                continue;
            }

            /** @var FinancialAttachment|null $attachment */
            $attachment = $item['attachment'];
            if (! $attachment) {
                throw new DomainException(__('finance.universal_credit.pack.errors.attachment_record_missing', ['id' => $item['attachment_id']]));
            }

            $destination = $this->localPath($staging, $item['logical_path']);
            $actual = $this->copyVerifiedAttachment($attachment, $destination);
            $item['bytes'] = $actual['bytes'];
            $item['sha256'] = $actual['sha256'];
            $item['_local_path'] = $destination;
            $bytes += $actual['bytes'];

            if ($bytes > $maximumBytes) {
                throw new DomainException(__('finance.universal_credit.pack.errors.attachment_size'));
            }
        }
        unset($item);

        return $inventory;
    }

    /** @return array{bytes: int, sha256: string} */
    private function copyVerifiedAttachment(FinancialAttachment $attachment, string $destination): array
    {
        if ($attachment->scan_status !== FinancialAttachment::SCAN_CLEAN) {
            throw new DomainException(__('finance.universal_credit.pack.errors.attachment_not_clean', ['id' => $attachment->id]));
        }

        $disk = Storage::disk($attachment->disk);
        if (! $disk->exists($attachment->path)) {
            throw new DomainException(__('finance.universal_credit.pack.errors.attachment_file_missing', ['id' => $attachment->id]));
        }

        $source = $disk->readStream($attachment->path);
        if (! is_resource($source)) {
            throw new DomainException(__('finance.universal_credit.pack.errors.attachment_file_missing', ['id' => $attachment->id]));
        }

        File::ensureDirectoryExists(dirname($destination));
        $target = fopen($destination, 'wb');
        if (! is_resource($target)) {
            fclose($source);
            throw new DomainException(__('finance.universal_credit.pack.errors.attachment_copy', ['id' => $attachment->id]));
        }

        $hash = hash_init('sha256');
        try {
            while (! feof($source)) {
                $chunk = fread($source, 1024 * 1024);
                if ($chunk === false || ($chunk !== '' && fwrite($target, $chunk) === false)) {
                    throw new DomainException(__('finance.universal_credit.pack.errors.attachment_copy', ['id' => $attachment->id]));
                }
                hash_update($hash, $chunk);
            }
        } finally {
            fclose($source);
            fclose($target);
        }

        $actualHash = hash_final($hash);
        $actualBytes = File::size($destination);
        if (! hash_equals((string) $attachment->sha256, $actualHash) || $attachment->size !== $actualBytes) {
            throw new DomainException(__('finance.universal_credit.pack.errors.attachment_integrity', ['id' => $attachment->id]));
        }

        return ['bytes' => $actualBytes, 'sha256' => $actualHash];
    }

    /**
     * @param  array<string, mixed>  $snapshot
     * @param  list<array<string, mixed>>  $items
     * @return list<array<string, mixed>>
     */
    private function writeManifest(
        string $staging,
        UniversalCreditEvidencePack $pack,
        UniversalCreditAssessmentPeriod $period,
        Business $business,
        array $snapshot,
        array $items,
    ): array {
        $manifest = [
            'schema_version' => 1,
            'classification' => 'UNIVERSAL CREDIT EVIDENCE - NOT SUBMISSION CONFIRMATION',
            'business' => ['id' => $business->id, 'name' => $business->name],
            'assessment_period' => [
                'id' => $period->id,
                'number' => $period->number,
                'date_from' => $period->date_from->toDateString(),
                'date_to' => $period->date_to->toDateString(),
                'review_snapshot_hash' => $period->review_snapshot_hash,
            ],
            'pack' => ['id' => $pack->id, 'version' => $pack->version, 'locale' => $pack->locale],
            'preflight' => data_get($snapshot, 'preflight'),
            'manifest_scope' => 'All ZIP entries except manifest.sha256.json',
            'files' => collect($items)
                ->where('inclusion_status', UniversalCreditEvidencePackItem::INCLUDED)
                ->map(fn (array $item): array => [
                    'path' => $item['logical_path'],
                    'type' => $item['item_type'],
                    'bytes' => $item['bytes'],
                    'sha256' => $item['sha256'],
                ])->values()->all(),
            'excluded' => collect($items)
                ->where('inclusion_status', UniversalCreditEvidencePackItem::EXCLUDED)
                ->map(fn (array $item): array => [
                    'logical_path' => $item['logical_path'],
                    'type' => $item['item_type'],
                    'source_type' => class_basename((string) $item['source_type']),
                    'source_id' => $item['source_id'],
                    'attachment_id' => $item['attachment_id'],
                    'reason' => $item['reason'],
                    'expected_sha256' => $item['sha256'],
                ])->values()->all(),
        ];
        $items[] = $this->put(
            $staging,
            'manifest.sha256.json',
            json_encode($manifest, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR).PHP_EOL,
            'manifest',
        );

        return $items;
    }

    /** @param list<array<string, mixed>> $items */
    private function writeZip(string $zipPath, array $items, string $snapshotHash): void
    {
        $zip = new ZipArchive;
        if ($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            throw new DomainException(__('finance.universal_credit.pack.errors.zip_create'));
        }

        $closed = false;
        try {
            foreach ($items as $item) {
                if ($item['inclusion_status'] !== UniversalCreditEvidencePackItem::INCLUDED) {
                    continue;
                }
                $this->assertSafePath($item['logical_path']);
                if (! $zip->addFile($item['_local_path'], $item['logical_path'])) {
                    throw new DomainException(__('finance.universal_credit.pack.errors.zip_entry', ['file' => $item['logical_path']]));
                }
            }

            $zip->setArchiveComment('Universal Credit review snapshot SHA-256: '.$snapshotHash);
        } finally {
            $closed = $zip->close();
        }

        if (! $closed) {
            throw new DomainException(__('finance.universal_credit.pack.errors.zip_create'));
        }
    }

    /** @return array<string, mixed> */
    private function put(string $staging, string $logicalPath, string $contents, string $type): array
    {
        $path = $this->localPath($staging, $logicalPath);
        File::ensureDirectoryExists(dirname($path));
        File::put($path, $contents);
        $hash = hash_file('sha256', $path);
        if (! is_string($hash)) {
            throw new DomainException(__('finance.universal_credit.pack.errors.report_write'));
        }

        return [
            'logical_path' => $logicalPath,
            'item_type' => $type,
            'source_type' => null,
            'source_id' => null,
            'attachment_id' => null,
            'inclusion_status' => UniversalCreditEvidencePackItem::INCLUDED,
            'reason' => null,
            'bytes' => File::size($path),
            'sha256' => $hash,
            '_local_path' => $path,
        ];
    }

    /** @param list<array<string, mixed>> $rows */
    private function writeCsv(string $staging, string $logicalPath, array $rows): array
    {
        $path = $this->localPath($staging, $logicalPath);
        File::ensureDirectoryExists(dirname($path));
        $stream = fopen($path, 'wb');
        if (! is_resource($stream)) {
            throw new DomainException(__('finance.universal_credit.pack.errors.report_write'));
        }

        try {
            if (fwrite($stream, "\xEF\xBB\xBF") === false) {
                throw new DomainException(__('finance.universal_credit.pack.errors.report_write'));
            }
            foreach ($rows as $row) {
                if (fputcsv($stream, array_map($this->safeTabularValue(...), $row)) === false) {
                    throw new DomainException(__('finance.universal_credit.pack.errors.report_write'));
                }
            }
        } finally {
            fclose($stream);
        }

        $hash = hash_file('sha256', $path);
        if (! is_string($hash)) {
            throw new DomainException(__('finance.universal_credit.pack.errors.report_write'));
        }

        return [
            'logical_path' => $logicalPath,
            'item_type' => 'report',
            'source_type' => null,
            'source_id' => null,
            'attachment_id' => null,
            'inclusion_status' => UniversalCreditEvidencePackItem::INCLUDED,
            'reason' => null,
            'bytes' => File::size($path),
            'sha256' => $hash,
            '_local_path' => $path,
        ];
    }

    /** @param list<array<string, mixed>> $rows @return list<array<int, mixed>> */
    private function detailRows(array $rows): array
    {
        $output = [[
            'id', 'cash_date', 'direction', 'category', 'classification_status', 'source_type',
            'source_reference', 'original_amount', 'original_currency', 'gross_amount_pence',
            'vat_amount_pence', 'business_use_basis_points', 'reportable_amount_pence',
            'exclusion_reason', 'evidence_state', 'reconciliation_state',
        ]];

        foreach ($rows as $row) {
            $output[] = [
                $row['id'] ?? null, $row['cash_date'] ?? null, $row['direction'] ?? null,
                $row['category'] ?? null, $row['classification_status'] ?? null, $row['source_type'] ?? null,
                $row['source_reference'] ?? null, $row['original_amount'] ?? null, $row['original_currency'] ?? null,
                $row['gross_amount_pence'] ?? null, $row['vat_amount_pence'] ?? null,
                $row['business_use_basis_points'] ?? null, $row['reportable_amount_pence'] ?? null,
                $row['exclusion_reason'] ?? null, data_get($row, 'evidence.state'), data_get($row, 'reconciliation.state'),
            ];
        }

        return $output;
    }

    /** @param list<array<string, mixed>> $details @return list<array<int, mixed>> */
    private function warningRows(array $snapshot, array $details): array
    {
        $rows = [['kind', 'code', 'item_id', 'context']];
        foreach ((array) data_get($snapshot, 'preflight.warnings', []) as $warning) {
            $rows[] = ['preflight_warning', $warning['code'] ?? null, data_get($warning, 'context.item_id'), $warning['context'] ?? []];
        }
        foreach ($details as $detail) {
            $state = (string) data_get($detail, 'evidence.state', 'missing');
            if (! in_array($state, ['clean_attachment', 'source_document', 'structured_record'], true)) {
                $rows[] = ['evidence', $state, $detail['id'] ?? null, data_get($detail, 'evidence.reason')];
            }
        }

        return $rows;
    }

    private function responseContent(Response $response): string
    {
        if ($response instanceof StreamedResponse) {
            ob_start();
            $response->sendContent();

            return (string) ob_get_clean();
        }

        return (string) $response->getContent();
    }

    private function verifyStoredZip(string $diskName, string $path, string $expectedHash, int $expectedBytes): void
    {
        $disk = Storage::disk($diskName);
        $stream = $disk->readStream($path);
        if (! is_resource($stream)) {
            throw new DomainException(__('finance.universal_credit.pack.errors.storage_write'));
        }

        try {
            $hash = hash_init('sha256');
            $bytes = hash_update_stream($hash, $stream);
            $actualHash = hash_final($hash);
        } finally {
            fclose($stream);
        }

        if ($bytes !== $expectedBytes || ! hash_equals($expectedHash, $actualHash)) {
            throw new DomainException(__('finance.universal_credit.pack.errors.storage_integrity'));
        }
    }

    /** @return array<string, mixed> */
    private function persistentItem(array $item): array
    {
        unset($item['_local_path'], $item['attachment']);

        return $item;
    }

    private function localPath(string $staging, string $logicalPath): string
    {
        $this->assertSafePath($logicalPath);

        return $staging.DIRECTORY_SEPARATOR.str_replace('/', DIRECTORY_SEPARATOR, $logicalPath);
    }

    private function assertSafePath(string $path): void
    {
        if ($path === '' || str_contains($path, '\\') || str_starts_with($path, '/')
            || preg_match('/^[A-Za-z]:/', $path) === 1
            || in_array('..', explode('/', $path), true)) {
            throw new DomainException(__('finance.universal_credit.pack.errors.unsafe_path'));
        }
    }

    private function safeTabularValue(mixed $value): mixed
    {
        if ($value === null || is_int($value) || is_float($value)) {
            return $value;
        }
        if (is_bool($value)) {
            return $value ? 'yes' : 'no';
        }
        if (is_array($value)) {
            $value = json_encode($value, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
        }
        $value = str_replace(["\r\n", "\r"], "\n", (string) $value);

        return preg_match('/^[=+\-@]/u', $value) === 1 ? "'".$value : $value;
    }

    private function safeName(string $name): string
    {
        $name = Str::ascii($name);
        $name = preg_replace('/[^A-Za-z0-9._-]+/', '-', $name) ?? '';

        return trim($name, '.-_') ?: 'period';
    }
}
