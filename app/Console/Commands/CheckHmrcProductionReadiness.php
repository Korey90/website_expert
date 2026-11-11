<?php

namespace App\Console\Commands;

use App\Services\Hmrc\HmrcProductionReadinessService;
use Illuminate\Console\Command;

class CheckHmrcProductionReadiness extends Command
{
    protected $signature = 'hmrc:production-readiness {--json : Emit machine-readable JSON}';

    protected $description = 'Check the fail-closed HMRC production access and submission gates';

    public function handle(HmrcProductionReadinessService $readiness): int
    {
        $result = $readiness->assess();

        if ($this->option('json')) {
            $this->line(json_encode($result, JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES));
        } else {
            $this->table(
                ['Check', 'Status', 'Requirement'],
                array_map(fn (array $check): array => [
                    $check['id'],
                    $check['passed'] ? 'PASS' : 'FAIL',
                    $check['detail'],
                ], $result['checks']),
            );
            $this->newLine();
            $this->line($result['ready'] ? 'HMRC production readiness: READY' : 'HMRC production readiness: NOT READY');
        }

        return $result['ready'] ? self::SUCCESS : self::FAILURE;
    }
}
