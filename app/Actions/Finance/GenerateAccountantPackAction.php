<?php

namespace App\Actions\Finance;

use App\Models\Business;
use App\Models\FinancialAttachment;
use App\Models\JournalEntry;
use App\Models\User;
use App\Models\VatReturn;
use App\Services\Finance\VatReturnSnapshotService;
use App\Support\PermissionHelper;
use Barryvdh\DomPDF\Facade\Pdf;
use DomainException;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Lang;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Throwable;
use ZipArchive;

class GenerateAccountantPackAction
{
    public function __construct(private readonly VatReturnSnapshotService $snapshotService) {}

    /** @return array{path: string, filename: string, sha256: string, bytes: int} */
    public function execute(VatReturn $return, User $actor): array
    {
        if (! class_exists(ZipArchive::class)) {
            throw new DomainException(__('finance.accountant_pack.errors.zip_unavailable'));
        }

        $locked = VatReturn::withoutGlobalScopes()->findOrFail($return->id);
        $this->assertAuthorized($locked, $actor);
        $snapshot = $locked->snapshot;

        if (! is_array($snapshot) || ! hash_equals($locked->snapshot_hash, $this->snapshotService->hash($snapshot))) {
            throw new DomainException(__('finance.accountant_pack.errors.snapshot_integrity'));
        }

        $business = Business::query()->findOrFail($locked->business_id);
        $journals = $this->sourceJournals($locked, $snapshot);
        $attachments = $this->sourceAttachments($locked, $journals);
        $generatedAt = now()->utc();
        $token = (string) Str::uuid();
        $root = storage_path('app/private/finance-packs');
        $staging = $root.DIRECTORY_SEPARATOR.'staging-'.$token;
        $zipPath = $root.DIRECTORY_SEPARATOR.'accountant-pack-'.$token.'.zip';
        $filename = 'accountant-pack-'.$this->safeName($locked->number).'-'.substr($locked->snapshot_hash, 0, 12).'.zip';

        File::ensureDirectoryExists($staging);

        try {
            $attachmentRows = $this->copyAttachments($staging, $attachments);
            $archiveFiles = $this->writeReports(
                $staging,
                $locked,
                $business,
                $actor,
                $generatedAt->toIso8601String(),
                $snapshot,
                $journals,
                $attachmentRows,
            );
            $manifestPath = $this->writeManifest(
                $staging,
                $locked,
                $business,
                $actor,
                $generatedAt->toIso8601String(),
                $archiveFiles,
            );
            $archiveFiles['manifest.sha256.json'] = $manifestPath;
            $this->writeZip($zipPath, $archiveFiles, $locked->snapshot_hash);
        } catch (Throwable $exception) {
            File::delete($zipPath);

            throw $exception;
        } finally {
            File::deleteDirectory($staging);
        }

        $hash = hash_file('sha256', $zipPath);

        if (! is_string($hash)) {
            File::delete($zipPath);

            throw new DomainException(__('finance.accountant_pack.errors.pack_hash'));
        }

        return [
            'path' => $zipPath,
            'filename' => $filename,
            'sha256' => $hash,
            'bytes' => File::size($zipPath),
        ];
    }

    private function assertAuthorized(VatReturn $return, User $actor): void
    {
        if ($return->status !== VatReturn::STATUS_LOCKED) {
            throw new DomainException(__('finance.accountant_pack.errors.locked_only'));
        }

        if (! $actor->businessMemberships()->where('business_id', $return->business_id)->where('is_active', true)->exists()) {
            throw new DomainException(__('finance.accountant_pack.errors.not_member'));
        }

        if (! PermissionHelper::allows($actor, 'view_finance')) {
            throw new DomainException(__('finance.accountant_pack.errors.cannot_export'));
        }
    }

    /** @param array<string, mixed> $snapshot @return Collection<int, JournalEntry> */
    private function sourceJournals(VatReturn $return, array $snapshot): Collection
    {
        $rows = data_get($snapshot, 'sources.rows', []);
        $journalIds = collect(is_array($rows) ? $rows : [])
            ->pluck('journal_entry_id')
            ->filter(fn (mixed $id): bool => is_int($id) || ctype_digit((string) $id))
            ->map(fn (mixed $id): int => (int) $id)
            ->unique()
            ->sort()
            ->values();
        $journals = JournalEntry::withoutGlobalScopes()
            ->where('business_id', $return->business_id)
            ->whereIn('id', $journalIds)
            ->orderBy('id')
            ->get();

        if ($journals->count() !== $journalIds->count()
            || $journalIds->count() !== $return->source_journal_count) {
            throw new DomainException(__('finance.accountant_pack.errors.source_integrity'));
        }

        return $journals;
    }

    /** @param Collection<int, JournalEntry> $journals @return Collection<int, FinancialAttachment> */
    private function sourceAttachments(VatReturn $return, Collection $journals): Collection
    {
        $groups = $journals
            ->filter(fn (JournalEntry $journal): bool => filled($journal->source_type) && filled($journal->source_id))
            ->groupBy('source_type')
            ->map(fn (Collection $items): array => $items->pluck('source_id')->map(fn (mixed $id): int => (int) $id)->unique()->all());

        if ($groups->isEmpty()) {
            return collect();
        }

        return FinancialAttachment::withoutGlobalScopes()
            ->whereNull('deleted_at')
            ->where('business_id', $return->business_id)
            ->where(function (Builder $query) use ($groups): void {
                foreach ($groups as $type => $ids) {
                    $query->orWhere(function (Builder $source) use ($type, $ids): void {
                        $source->where('attachable_type', $type)->whereIn('attachable_id', $ids);
                    });
                }
            })
            ->orderBy('id')
            ->get();
    }

    /**
     * @param  Collection<int, FinancialAttachment>  $attachments
     * @return list<array<string, int|string|null>>
     */
    private function copyAttachments(string $staging, Collection $attachments): array
    {
        $maximumCount = (int) config('finance.accountant_pack.max_attachments', 100);
        $maximumBytes = (int) config('finance.accountant_pack.max_attachment_bytes', 100 * 1024 * 1024);

        if ($attachments->count() > $maximumCount) {
            throw new DomainException(__('finance.accountant_pack.errors.attachment_count'));
        }

        $rows = [];
        $includedBytes = 0;

        foreach ($attachments as $attachment) {
            $packagePath = null;
            $inclusion = 'excluded_'.$attachment->scan_status;

            if ($attachment->scan_status === FinancialAttachment::SCAN_CLEAN) {
                $packagePath = 'attachments/'.$attachment->id.'-'.$this->safeName($attachment->original_name);
                $destination = $staging.DIRECTORY_SEPARATOR.str_replace('/', DIRECTORY_SEPARATOR, $packagePath);
                $includedBytes += $this->copyVerifiedAttachment($attachment, $destination);
                $inclusion = 'included';

                if ($includedBytes > $maximumBytes) {
                    throw new DomainException(__('finance.accountant_pack.errors.attachment_size'));
                }
            }

            $rows[] = [
                'attachment_id' => $attachment->id,
                'source_type' => class_basename($attachment->attachable_type),
                'source_id' => $attachment->attachable_id,
                'original_name' => $attachment->original_name,
                'document_type' => $attachment->document_type,
                'mime_type' => $attachment->mime_type,
                'bytes' => $attachment->size,
                'sha256' => $attachment->sha256,
                'scan_status' => $attachment->scan_status,
                'inclusion_status' => $inclusion,
                'package_path' => $packagePath,
            ];
        }

        return $rows;
    }

    private function copyVerifiedAttachment(FinancialAttachment $attachment, string $destination): int
    {
        $disk = Storage::disk($attachment->disk);

        if (! $disk->exists($attachment->path)) {
            throw new DomainException(__('finance.accountant_pack.errors.attachment_missing', ['id' => $attachment->id]));
        }

        $source = $disk->readStream($attachment->path);

        if (! is_resource($source)) {
            throw new DomainException(__('finance.accountant_pack.errors.attachment_missing', ['id' => $attachment->id]));
        }

        File::ensureDirectoryExists(dirname($destination));
        $target = fopen($destination, 'wb');

        if (! is_resource($target)) {
            fclose($source);

            throw new DomainException(__('finance.accountant_pack.errors.attachment_copy', ['id' => $attachment->id]));
        }

        $hash = hash_init('sha256');

        try {
            while (! feof($source)) {
                $chunk = fread($source, 1024 * 1024);

                if ($chunk === false || fwrite($target, $chunk) === false) {
                    throw new DomainException(__('finance.accountant_pack.errors.attachment_copy', ['id' => $attachment->id]));
                }

                hash_update($hash, $chunk);
            }
        } finally {
            fclose($source);
            fclose($target);
        }

        $actualHash = hash_final($hash);
        $actualBytes = File::size($destination);

        if (! hash_equals($attachment->sha256, $actualHash) || $actualBytes !== $attachment->size) {
            throw new DomainException(__('finance.accountant_pack.errors.attachment_integrity', ['id' => $attachment->id]));
        }

        return $actualBytes;
    }

    /**
     * @param  array<string, mixed>  $snapshot
     * @param  Collection<int, JournalEntry>  $journals
     * @param  list<array<string, int|string|null>>  $attachmentRows
     * @return array<string, string>
     */
    private function writeReports(
        string $staging,
        VatReturn $return,
        Business $business,
        User $actor,
        string $generatedAt,
        array $snapshot,
        Collection $journals,
        array $attachmentRows,
    ): array {
        $files = [];
        $summaryRows = $this->summaryRows($return, $business, $actor, $generatedAt, $snapshot);
        $boxRows = collect(data_get($snapshot, 'calculation.boxes', []))->map(fn (array $box): array => [
            $box['number'] ?? null,
            $box['hmrc_field'] ?? null,
            $box['raw_gbp'] ?? null,
            $box['submission_value'] ?? null,
            ($box['is_derived'] ?? false) ? 'yes' : 'no',
        ])->all();
        $detailRows = collect(data_get($snapshot, 'sources.rows', []))->map(fn (array $row): array => [
            $row['tax_point'] ?? null,
            $row['kind'] ?? null,
            $row['reference'] ?? null,
            $row['journal_entry_id'] ?? null,
            implode('|', $row['journal_line_ids'] ?? []),
            $row['journal_snapshot_hash'] ?? null,
            $row['tax_code'] ?? null,
            $row['tax_code_type'] ?? null,
            $row['tax_rate'] ?? null,
            $row['vat_return_mapping'] ?? null,
            $row['net_sales_gbp'] ?? null,
            $row['net_purchases_gbp'] ?? null,
            $row['output_vat_gbp'] ?? null,
            $row['input_vat_gbp'] ?? null,
        ])->all();
        $taxCodeRows = collect(data_get($snapshot, 'sources.tax_codes', []))->map(fn (array $row): array => [
            $row['tax_code_id'] ?? null,
            $row['code'] ?? null,
            $row['type'] ?? null,
            $row['rate'] ?? null,
            $row['vat_return_mapping'] ?? null,
            ($row['is_active'] ?? false) ? 'yes' : 'no',
            $row['net_sales_gbp'] ?? null,
            $row['net_purchases_gbp'] ?? null,
            $row['output_vat_gbp'] ?? null,
            $row['input_vat_gbp'] ?? null,
        ])->all();
        $journalRows = $journals->map(fn (JournalEntry $journal): array => [
            $journal->id,
            $journal->entry_number,
            $journal->entry_date->toDateString(),
            $journal->status,
            $journal->description,
            class_basename((string) $journal->source_type),
            $journal->source_id,
            $journal->source_snapshot_hash,
        ])->all();
        $attachmentDataRows = array_map(fn (array $row): array => array_values($row), $attachmentRows);

        $readme = $this->label('readme', [
            'business' => $business->name,
            'number' => $return->number,
            'from' => $return->period_from->toDateString(),
            'to' => $return->period_to->toDateString(),
            'snapshot_hash' => $return->snapshot_hash,
            'generated_at' => $generatedAt,
        ]);
        $files['README.txt'] = $this->put($staging, 'README.txt', $readme.PHP_EOL);

        $pdfPath = $staging.DIRECTORY_SEPARATOR.'vat-return-summary.pdf';
        File::put($pdfPath, Pdf::loadView('pdf.accountant-pack', [
            'return' => $return,
            'business' => $business,
            'actor' => $actor,
            'generatedAt' => $generatedAt,
            'snapshot' => $snapshot,
        ])->setPaper('a4')->output());

        if (File::size($pdfPath) < 1000 || file_get_contents($pdfPath, false, null, 0, 5) !== '%PDF-') {
            throw new DomainException(__('finance.accountant_pack.errors.pdf'));
        }

        $files['vat-return-summary.pdf'] = $pdfPath;
        $files['vat-return-boxes.csv'] = $this->writeCsv($staging, 'vat-return-boxes.csv', [
            $this->labels(['box', 'hmrc_field', 'raw_gbp', 'review_value', 'derived']),
            ...$boxRows,
        ]);
        $files['vat-detail.csv'] = $this->writeCsv($staging, 'vat-detail.csv', [
            $this->labels(['tax_point', 'document_type', 'reference', 'journal_id', 'journal_line_ids', 'journal_hash', 'tax_code', 'tax_code_type', 'tax_rate', 'vat_mapping', 'net_sales', 'net_purchases', 'output_vat', 'input_vat']),
            ...$detailRows,
        ]);
        $files['source-journals.csv'] = $this->writeCsv($staging, 'source-journals.csv', [
            $this->labels(['journal_id', 'entry_number', 'entry_date', 'status', 'description', 'source_type', 'source_id', 'journal_hash']),
            ...$journalRows,
        ]);
        $files['attachments-manifest.csv'] = $this->writeCsv($staging, 'attachments-manifest.csv', [
            $this->labels(['attachment_id', 'source_type', 'source_id', 'original_name', 'document_type', 'mime_type', 'bytes', 'sha256', 'scan_status', 'inclusion_status', 'package_path']),
            ...$attachmentDataRows,
        ]);

        $xlsxPath = $staging.DIRECTORY_SEPARATOR.'accountant-pack.xlsx';
        $this->writeSpreadsheet($xlsxPath, [
            $this->label('sheet_summary') => [$this->labels(['field', 'value']), ...$summaryRows],
            $this->label('sheet_boxes') => [$this->labels(['box', 'hmrc_field', 'raw_gbp', 'review_value', 'derived']), ...$boxRows],
            $this->label('sheet_vat_detail') => [$this->labels(['tax_point', 'document_type', 'reference', 'journal_id', 'journal_line_ids', 'journal_hash', 'tax_code', 'tax_code_type', 'tax_rate', 'vat_mapping', 'net_sales', 'net_purchases', 'output_vat', 'input_vat']), ...$detailRows],
            $this->label('sheet_tax_codes') => [$this->labels(['tax_code_id', 'tax_code', 'tax_code_type', 'tax_rate', 'vat_mapping', 'active', 'net_sales', 'net_purchases', 'output_vat', 'input_vat']), ...$taxCodeRows],
            $this->label('sheet_journals') => [$this->labels(['journal_id', 'entry_number', 'entry_date', 'status', 'description', 'source_type', 'source_id', 'journal_hash']), ...$journalRows],
            $this->label('sheet_attachments') => [$this->labels(['attachment_id', 'source_type', 'source_id', 'original_name', 'document_type', 'mime_type', 'bytes', 'sha256', 'scan_status', 'inclusion_status', 'package_path']), ...$attachmentDataRows],
        ]);
        $files['accountant-pack.xlsx'] = $xlsxPath;

        foreach ($attachmentRows as $row) {
            if ($row['inclusion_status'] === 'included' && is_string($row['package_path'])) {
                $files[$row['package_path']] = $staging.DIRECTORY_SEPARATOR.str_replace('/', DIRECTORY_SEPARATOR, $row['package_path']);
            }
        }

        ksort($files);

        return $files;
    }

    /** @param array<string, mixed> $snapshot @return list<array{string, mixed}> */
    private function summaryRows(VatReturn $return, Business $business, User $actor, string $generatedAt, array $snapshot): array
    {
        return [
            [$this->label('business'), $business->name],
            [$this->label('return_number'), $return->number],
            [$this->label('status'), $return->status],
            [$this->label('period_from'), $return->period_from->toDateString()],
            [$this->label('period_to'), $return->period_to->toDateString()],
            [$this->label('snapshot_hash'), $return->snapshot_hash],
            [$this->label('generated_at'), $generatedAt],
            [$this->label('generated_by'), $actor->name],
            [$this->label('vat_scheme'), data_get($snapshot, 'profile.vat_scheme')],
            [$this->label('vat_last_four'), data_get($snapshot, 'profile.vat_number_last_four')],
            [$this->label('source_journals'), $return->source_journal_count],
            [$this->label('source_lines'), $return->source_line_count],
            [$this->label('warnings'), $return->warning_count],
        ];
    }

    /** @param array<string, string> $archiveFiles */
    private function writeManifest(
        string $staging,
        VatReturn $return,
        Business $business,
        User $actor,
        string $generatedAt,
        array $archiveFiles,
    ): string {
        $entries = [];

        foreach ($archiveFiles as $name => $path) {
            $hash = hash_file('sha256', $path);

            if (! is_string($hash)) {
                throw new DomainException(__('finance.accountant_pack.errors.file_hash', ['file' => $name]));
            }

            $entries[] = ['name' => $name, 'bytes' => File::size($path), 'sha256' => $hash];
        }

        usort($entries, fn (array $left, array $right): int => $left['name'] <=> $right['name']);

        return $this->put($staging, 'manifest.sha256.json', json_encode([
            'schema_version' => 1,
            'classification' => 'INTERNAL ACCOUNTANT REVIEW / NOT SUBMITTED TO HMRC',
            'business' => ['id' => $business->id, 'name' => $business->name],
            'vat_return' => [
                'id' => $return->id,
                'number' => $return->number,
                'period_from' => $return->period_from->toDateString(),
                'period_to' => $return->period_to->toDateString(),
                'snapshot_hash' => $return->snapshot_hash,
            ],
            'generated_at' => $generatedAt,
            'generated_by' => ['id' => $actor->id, 'name' => $actor->name],
            'manifest_scope' => 'All ZIP entries except manifest.sha256.json',
            'files' => $entries,
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR).PHP_EOL);
    }

    /** @param array<string, string> $files */
    private function writeZip(string $path, array $files, string $snapshotHash): void
    {
        $zip = new ZipArchive;

        if ($zip->open($path, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            throw new DomainException(__('finance.accountant_pack.errors.zip_create'));
        }

        try {
            foreach ($files as $name => $file) {
                if (! $zip->addFile($file, str_replace('\\', '/', $name))) {
                    throw new DomainException(__('finance.accountant_pack.errors.zip_entry', ['file' => $name]));
                }
            }

            $zip->setArchiveComment('VAT Return snapshot SHA-256: '.$snapshotHash);
        } finally {
            $zip->close();
        }
    }

    /** @param list<list<mixed>> $rows */
    private function writeCsv(string $staging, string $name, array $rows): string
    {
        $path = $staging.DIRECTORY_SEPARATOR.$name;
        $stream = fopen($path, 'wb');

        if (! is_resource($stream)) {
            throw new DomainException(__('finance.accountant_pack.errors.report_write', ['file' => $name]));
        }

        try {
            fwrite($stream, "\xEF\xBB\xBF");

            foreach ($rows as $row) {
                fputcsv($stream, array_map(fn (mixed $value): mixed => $this->safeTabularValue($value), $row));
            }
        } finally {
            fclose($stream);
        }

        return $path;
    }

    /** @param array<string, list<list<mixed>>> $sheets */
    private function writeSpreadsheet(string $path, array $sheets): void
    {
        $spreadsheet = new Spreadsheet;

        foreach (array_values($sheets) as $sheetIndex => $rows) {
            $name = array_keys($sheets)[$sheetIndex];
            $sheet = $sheetIndex === 0 ? $spreadsheet->getActiveSheet() : $spreadsheet->createSheet();
            $sheet->setTitle(mb_substr($name, 0, 31));

            foreach ($rows as $rowIndex => $row) {
                foreach (array_values($row) as $columnIndex => $value) {
                    $cell = Coordinate::stringFromColumnIndex($columnIndex + 1).($rowIndex + 1);

                    if (is_int($value) || is_float($value)) {
                        $sheet->setCellValue($cell, $value);
                    } else {
                        $sheet->setCellValueExplicit($cell, (string) $this->safeTabularValue($value), DataType::TYPE_STRING);
                    }
                }
            }

            if ($rows !== []) {
                $sheet->getStyle('A1:'.$sheet->getHighestColumn().'1')->getFont()->setBold(true);
                for ($column = 1; $column <= Coordinate::columnIndexFromString($sheet->getHighestColumn()); $column++) {
                    $sheet->getColumnDimension(Coordinate::stringFromColumnIndex($column))->setAutoSize(true);
                }
            }
        }

        (new Xlsx($spreadsheet))->save($path);
        $spreadsheet->disconnectWorksheets();
    }

    private function put(string $staging, string $name, string $contents): string
    {
        $path = $staging.DIRECTORY_SEPARATOR.$name;
        File::put($path, $contents);

        return $path;
    }

    /** @param list<string> $keys @return list<string> */
    private function labels(array $keys): array
    {
        return array_map(fn (string $key): string => $this->label($key), $keys);
    }

    /** @param array<string, mixed> $replace */
    private function label(string $key, array $replace = []): string
    {
        return Lang::get('finance.accountant_pack.export.'.$key, $replace, 'en');
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
        $name = basename(str_replace('\\', '/', $name));
        $name = Str::ascii($name);
        $name = preg_replace('/[^A-Za-z0-9._-]+/', '-', $name) ?? '';
        $name = trim($name, '.-_');

        return $name !== '' ? mb_substr($name, 0, 150) : 'file';
    }
}
