<?php

namespace App\Actions\Finance;

use App\Contracts\Finance\FinancialAttachmentScanner;
use App\Models\FinancialAttachment;
use App\Services\Finance\Exceptions\FinancialAttachmentScanException;
use Illuminate\Support\Facades\Storage;
use Throwable;

class ScanFinancialAttachmentAction
{
    public function __construct(private readonly FinancialAttachmentScanner $scanner) {}

    public function execute(int $attachmentId): ?FinancialAttachment
    {
        $attachment = FinancialAttachment::withoutGlobalScopes()->find($attachmentId);

        if (! $attachment || in_array($attachment->scan_status, [
            FinancialAttachment::SCAN_CLEAN,
            FinancialAttachment::SCAN_REJECTED,
        ], true)) {
            return $attachment;
        }

        $attachment->forceFill([
            'scan_status' => FinancialAttachment::SCAN_PENDING,
            'scan_attempts' => $attachment->scan_attempts + 1,
            'scan_engine' => $this->scanner->engine(),
            'scan_error_code' => null,
            'scan_requested_at' => $attachment->scan_requested_at ?: now(),
            'scanned_at' => null,
        ])->save();

        try {
            $absolutePath = $this->verifiedAbsolutePath($attachment);
            $result = $this->scanner->scan($absolutePath);
        } catch (FinancialAttachmentScanException $exception) {
            $attachment->forceFill([
                'scan_status' => $exception->retryable
                    ? FinancialAttachment::SCAN_PENDING
                    : FinancialAttachment::SCAN_FAILED,
                'scan_error_code' => $exception->errorCode,
                'scanned_at' => $exception->retryable ? null : now(),
            ])->save();

            if ($exception->retryable) {
                throw $exception;
            }

            return $attachment->fresh();
        } catch (Throwable $exception) {
            $attachment->forceFill([
                'scan_status' => FinancialAttachment::SCAN_PENDING,
                'scan_error_code' => 'scanner_error',
            ])->save();

            throw new FinancialAttachmentScanException('scanner_error', previous: $exception);
        }

        $attachment->forceFill([
            'scan_status' => $result->status,
            'scan_engine' => $result->engine,
            'scan_error_code' => null,
            'scanned_at' => now(),
        ])->save();

        return $attachment->fresh();
    }

    private function verifiedAbsolutePath(FinancialAttachment $attachment): string
    {
        $disk = config('filesystems.disks.'.$attachment->disk);

        if (! is_array($disk) || ($disk['driver'] ?? null) !== 'local') {
            throw new FinancialAttachmentScanException('unsupported_storage', retryable: false);
        }

        $relativePath = str_replace('\\', '/', $attachment->path);

        if ($relativePath === ''
            || str_starts_with($relativePath, '/')
            || preg_match('#(^|/)\.\.(/|$)#', $relativePath) === 1) {
            throw new FinancialAttachmentScanException('invalid_storage_path', retryable: false);
        }

        $filesystem = Storage::disk($attachment->disk);
        $root = realpath($filesystem->path(''));
        $candidate = $filesystem->path($relativePath);
        $absolutePath = realpath($candidate);

        if (! is_string($root)
            || ! is_string($absolutePath)
            || ! str_starts_with($absolutePath, $root.DIRECTORY_SEPARATOR)
            || ! is_file($absolutePath)
            || ! is_readable($absolutePath)) {
            throw new FinancialAttachmentScanException('file_unavailable');
        }

        clearstatcache(true, $absolutePath);
        $size = filesize($absolutePath);
        $sha256 = hash_file('sha256', $absolutePath);

        if ($size !== $attachment->size
            || ! is_string($sha256)
            || ! hash_equals($attachment->sha256, $sha256)) {
            throw new FinancialAttachmentScanException('integrity_mismatch', retryable: false);
        }

        return $absolutePath;
    }
}
