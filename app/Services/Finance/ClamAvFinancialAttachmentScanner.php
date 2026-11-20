<?php

namespace App\Services\Finance;

use App\Contracts\Finance\FinancialAttachmentScanner;
use App\Data\Finance\FinancialAttachmentScanResult;
use App\Services\Finance\Exceptions\FinancialAttachmentScanException;
use Symfony\Component\Process\Exception\ProcessTimedOutException;
use Symfony\Component\Process\ExecutableFinder;
use Symfony\Component\Process\Process;
use Throwable;

class ClamAvFinancialAttachmentScanner implements FinancialAttachmentScanner
{
    public function engine(): string
    {
        return 'clamav';
    }

    public function scan(string $absolutePath): FinancialAttachmentScanResult
    {
        $binary = $this->resolveBinary();
        $command = [$binary, '--no-summary'];

        if ((bool) config('finance.attachments.scanner.fdpass', true)
            && str_contains(strtolower(basename($binary)), 'clamdscan')) {
            $command[] = '--fdpass';
        }

        $command[] = $absolutePath;
        $process = new Process($command);
        $process->disableOutput();
        $process->setTimeout(max(1, (int) config('finance.attachments.scanner.timeout_seconds', 120)));

        try {
            $process->run();
        } catch (ProcessTimedOutException $exception) {
            throw new FinancialAttachmentScanException('scanner_timeout', previous: $exception);
        } catch (Throwable $exception) {
            throw new FinancialAttachmentScanException('scanner_unavailable', previous: $exception);
        }

        return match ($process->getExitCode()) {
            0 => FinancialAttachmentScanResult::clean($this->engine()),
            1 => FinancialAttachmentScanResult::rejected($this->engine()),
            default => throw new FinancialAttachmentScanException('scanner_error'),
        };
    }

    private function resolveBinary(): string
    {
        $configured = trim((string) config('finance.attachments.scanner.binary', 'clamscan'));
        $binary = str_contains($configured, DIRECTORY_SEPARATOR)
            ? (is_executable($configured) ? $configured : null)
            : (new ExecutableFinder)->find($configured);

        if (! is_string($binary) || $binary === '') {
            throw new FinancialAttachmentScanException('scanner_unavailable');
        }

        return $binary;
    }
}
