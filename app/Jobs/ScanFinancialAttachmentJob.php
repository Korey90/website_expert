<?php

namespace App\Jobs;

use App\Actions\Finance\ScanFinancialAttachmentAction;
use App\Models\FinancialAttachment;
use Illuminate\Contracts\Cache\Repository;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Cache;
use Throwable;

class ScanFinancialAttachmentJob implements ShouldBeUnique, ShouldQueue
{
    use Queueable;

    public int $timeout = 135;

    public int $uniqueFor = 3600;

    public function __construct(public readonly int $attachmentId) {}

    public function handle(ScanFinancialAttachmentAction $action): void
    {
        $action->execute($this->attachmentId);
    }

    public function tries(): int
    {
        return max(1, (int) config('finance.attachments.scanner.tries', 3));
    }

    /** @return list<int> */
    public function backoff(): array
    {
        return array_values(array_map(
            'intval',
            (array) config('finance.attachments.scanner.backoff_seconds', [60, 300, 900]),
        ));
    }

    public function uniqueId(): string
    {
        return (string) $this->attachmentId;
    }

    public function uniqueVia(): Repository
    {
        return Cache::store('database');
    }

    public function failed(?Throwable $exception): void
    {
        $attachment = FinancialAttachment::withoutGlobalScopes()->find($this->attachmentId);

        if (! $attachment || $attachment->scan_status !== FinancialAttachment::SCAN_PENDING) {
            return;
        }

        $attachment->forceFill([
            'scan_status' => FinancialAttachment::SCAN_FAILED,
            'scan_error_code' => $attachment->scan_error_code ?: 'scan_failed',
            'scanned_at' => now(),
        ])->save();
    }
}
