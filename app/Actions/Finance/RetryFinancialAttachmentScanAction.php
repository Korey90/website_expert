<?php

namespace App\Actions\Finance;

use App\Jobs\ScanFinancialAttachmentJob;
use App\Models\FinancialAttachment;
use App\Models\User;
use App\Support\PermissionHelper;
use DomainException;

class RetryFinancialAttachmentScanAction
{
    public function execute(FinancialAttachment $attachment, User $actor): FinancialAttachment
    {
        if (! PermissionHelper::allows($actor, 'manage_finance')
            || ! $actor->businessMemberships()
                ->where('business_id', $attachment->business_id)
                ->where('is_active', true)
                ->exists()) {
            throw new DomainException('The actor cannot retry this attachment security scan.');
        }

        if (! in_array($attachment->scan_status, [
            FinancialAttachment::SCAN_PENDING,
            FinancialAttachment::SCAN_FAILED,
        ], true)) {
            throw new DomainException('Only a pending or failed attachment scan can be retried.');
        }

        $attachment->forceFill([
            'scan_status' => FinancialAttachment::SCAN_PENDING,
            'scan_error_code' => null,
            'scan_requested_at' => now(),
            'scanned_at' => null,
        ])->save();

        ScanFinancialAttachmentJob::dispatch($attachment->id)
            ->onQueue((string) config('finance.attachments.scanner.queue', 'default'))
            ->afterCommit();

        return $attachment->fresh();
    }
}
