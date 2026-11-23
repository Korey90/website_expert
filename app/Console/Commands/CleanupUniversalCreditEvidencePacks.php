<?php

namespace App\Console\Commands;

use App\Models\UniversalCreditEvidencePack;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

class CleanupUniversalCreditEvidencePacks extends Command
{
    protected $signature = 'finance:cleanup-universal-credit-packs {--limit=500 : Maximum packs to clean in one run}';

    protected $description = 'Delete expired Universal Credit ZIP files while preserving pack and manifest audit records';

    public function handle(): int
    {
        $limit = max(1, min(5000, (int) $this->option('limit')));
        $ids = UniversalCreditEvidencePack::withoutGlobalScopes()
            ->where('status', UniversalCreditEvidencePack::STATUS_COMPLETED)
            ->whereNotNull('expires_at')
            ->where('expires_at', '<=', now())
            ->orderBy('id')
            ->limit($limit)
            ->pluck('id');
        $cleaned = 0;

        foreach ($ids as $id) {
            DB::transaction(function () use ($id, &$cleaned): void {
                $pack = UniversalCreditEvidencePack::withoutGlobalScopes()->lockForUpdate()->find($id);
                if (! $pack
                    || $pack->status !== UniversalCreditEvidencePack::STATUS_COMPLETED
                    || $pack->expires_at?->isFuture()) {
                    return;
                }

                if (filled($pack->disk) && filled($pack->path)) {
                    $disk = Storage::disk($pack->disk);
                    if ($disk->exists($pack->path) && ! $disk->delete($pack->path)) {
                        throw new RuntimeException('Unable to delete expired Universal Credit evidence pack.');
                    }
                }

                $pack->update([
                    'status' => UniversalCreditEvidencePack::STATUS_EXPIRED,
                    'file_deleted_at' => now(),
                ]);
                $cleaned++;
            }, 3);
        }

        $this->info("Expired Universal Credit evidence packs cleaned: {$cleaned}.");

        return self::SUCCESS;
    }
}
