<?php

namespace App\Console\Commands;

use App\Jobs\ScanFinancialAttachmentJob;
use App\Models\FinancialAttachment;
use Illuminate\Console\Command;

class ScanFinancialAttachments extends Command
{
    protected $signature = 'finance:scan-attachments
        {--id= : Scan one attachment ID}
        {--include-failed : Include failed scans}
        {--limit=100 : Maximum number of attachments to dispatch}';

    protected $description = 'Dispatch pending financial attachments for security scanning';

    public function handle(): int
    {
        $limit = filter_var($this->option('limit'), FILTER_VALIDATE_INT);

        if ($limit === false || $limit < 1 || $limit > 1000) {
            $this->error('The --limit option must be an integer between 1 and 1000.');

            return self::INVALID;
        }

        $id = $this->option('id');

        if ($id !== null && filter_var($id, FILTER_VALIDATE_INT) === false) {
            $this->error('The --id option must be a valid attachment ID.');

            return self::INVALID;
        }

        $statuses = [FinancialAttachment::SCAN_PENDING];

        if ((bool) $this->option('include-failed')) {
            $statuses[] = FinancialAttachment::SCAN_FAILED;
        }

        $attachments = FinancialAttachment::withoutGlobalScopes()
            ->whereIn('scan_status', $statuses)
            ->when($id !== null, fn ($query) => $query->whereKey((int) $id))
            ->orderBy('id')
            ->limit($limit)
            ->get();

        foreach ($attachments as $attachment) {
            $updates = ['scan_requested_at' => now()];

            if ($attachment->scan_status === FinancialAttachment::SCAN_FAILED) {
                $updates = [
                    ...$updates,
                    'scan_status' => FinancialAttachment::SCAN_PENDING,
                    'scan_error_code' => null,
                    'scanned_at' => null,
                ];
            }

            $attachment->forceFill($updates)->save();

            ScanFinancialAttachmentJob::dispatch($attachment->id)
                ->onQueue((string) config('finance.attachments.scanner.queue', 'default'));
        }

        $this->info("Dispatched {$attachments->count()} financial attachment scan(s).");

        return self::SUCCESS;
    }
}
