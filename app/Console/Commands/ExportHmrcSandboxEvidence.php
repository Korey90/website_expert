<?php

namespace App\Console\Commands;

use App\Models\Business;
use App\Services\Hmrc\HmrcSandboxEvidenceExporter;
use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;

class ExportHmrcSandboxEvidence extends Command
{
    protected $signature = 'hmrc:export-sandbox-evidence
        {business : Exact business ULID or slug}
        {--output=storage/app/private/hmrc-evidence : Relative or absolute output directory}';

    protected $description = 'Export a redacted, integrity-checked HMRC sandbox evidence document';

    public function handle(HmrcSandboxEvidenceExporter $exporter, Filesystem $files): int
    {
        if (config('hmrc.environment') !== 'sandbox') {
            $this->error('Sandbox evidence can only be exported while HMRC_ENVIRONMENT=sandbox.');

            return self::FAILURE;
        }

        $reference = (string) $this->argument('business');
        $business = Business::query()
            ->where(fn ($query) => $query->whereKey($reference)->orWhere('slug', $reference))
            ->first();

        if (! $business) {
            $this->error('Business not found.');

            return self::FAILURE;
        }

        $document = $exporter->build($business);
        $output = (string) $this->option('output');
        $directory = $this->isAbsolutePath($output) ? $output : base_path($output);
        $files->ensureDirectoryExists($directory);
        $timestamp = now()->utc()->format('Ymd-His');
        $filename = "hmrc-sandbox-evidence-{$timestamp}.json";
        $path = $directory.DIRECTORY_SEPARATOR.$filename;
        $json = json_encode($document, JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE).PHP_EOL;
        $fileHash = hash('sha256', $json);
        $files->put($path, $json, true);
        $files->put($path.'.sha256', $fileHash.'  '.$filename.PHP_EOL, true);

        $this->info('HMRC sandbox evidence exported.');
        $this->line('Document: '.$path);
        $this->line('File SHA-256: '.$fileHash);
        $this->line('Payload SHA-256: '.$document['payload_sha256']);
        $this->line('Evidence events: '.$document['integrity']['evidence_event_count']);
        $this->line('Submission chains verified: '.$document['integrity']['submission_chains_verified']);

        return self::SUCCESS;
    }

    private function isAbsolutePath(string $path): bool
    {
        return str_starts_with($path, DIRECTORY_SEPARATOR)
            || preg_match('/^[A-Za-z]:[\\\\\/]/', $path) === 1;
    }
}
