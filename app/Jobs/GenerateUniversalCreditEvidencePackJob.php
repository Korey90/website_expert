<?php

namespace App\Jobs;

use App\Models\UniversalCreditEvidencePack;
use App\Models\UniversalCreditEvidencePackItem;
use App\Services\Finance\UniversalCreditEvidencePackGenerator;
use Illuminate\Contracts\Cache\Repository;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Throwable;

class GenerateUniversalCreditEvidencePackJob implements ShouldBeUnique, ShouldQueue
{
    use Queueable;

    public int $timeout = 300;

    public int $uniqueFor = 1800;

    public function __construct(public readonly int $packId) {}

    public function handle(UniversalCreditEvidencePackGenerator $generator): void
    {
        $pack = DB::transaction(function (): ?UniversalCreditEvidencePack {
            $locked = UniversalCreditEvidencePack::withoutGlobalScopes()->lockForUpdate()->find($this->packId);
            if (! $locked || in_array($locked->status, [
                UniversalCreditEvidencePack::STATUS_COMPLETED,
                UniversalCreditEvidencePack::STATUS_EXPIRED,
            ], true)) {
                return null;
            }

            if (! in_array($locked->status, [
                UniversalCreditEvidencePack::STATUS_PENDING,
                UniversalCreditEvidencePack::STATUS_PROCESSING,
            ], true)) {
                return null;
            }

            $values = [
                'attempt_count' => $locked->attempt_count + 1,
                'last_error_code' => null,
                'failed_at' => null,
            ];
            if ($locked->status === UniversalCreditEvidencePack::STATUS_PENDING) {
                $values['status'] = UniversalCreditEvidencePack::STATUS_PROCESSING;
                $values['started_at'] = now();
            }
            $locked->update($values);

            return $locked->fresh();
        }, 3);

        if (! $pack) {
            return;
        }

        $previousLocale = app()->getLocale();
        $result = null;

        try {
            app()->setLocale($pack->locale);
            $result = $generator->generate($pack);

            DB::transaction(function () use ($pack, $result): void {
                $locked = UniversalCreditEvidencePack::withoutGlobalScopes()
                    ->where('business_id', $pack->business_id)->whereKey($pack->id)
                    ->lockForUpdate()->firstOrFail();

                if ($locked->status !== UniversalCreditEvidencePack::STATUS_PROCESSING) {
                    throw new \DomainException(__('finance.universal_credit.pack.errors.invalid_transition'));
                }

                foreach ($result['items'] as $item) {
                    UniversalCreditEvidencePackItem::withoutGlobalScopes()->create([
                        'business_id' => $locked->business_id,
                        'evidence_pack_id' => $locked->id,
                        ...$item,
                    ]);
                }

                $locked->update([
                    'status' => UniversalCreditEvidencePack::STATUS_COMPLETED,
                    'disk' => $result['disk'],
                    'path' => $result['path'],
                    'filename' => $result['filename'],
                    'zip_sha256' => $result['zip_sha256'],
                    'bytes' => $result['bytes'],
                    'included_count' => $result['included_count'],
                    'excluded_count' => $result['excluded_count'],
                    'generated_at' => now(),
                    'expires_at' => now()->addDays((int) config('finance.universal_credit.evidence_pack.expiry_days', 7)),
                    'last_error_code' => null,
                    'failed_at' => null,
                ]);
            }, 3);
        } catch (Throwable $exception) {
            if (is_array($result) && filled($result['disk'] ?? null) && filled($result['path'] ?? null)) {
                Storage::disk($result['disk'])->delete($result['path']);
            }

            UniversalCreditEvidencePack::withoutGlobalScopes()
                ->whereKey($pack->id)
                ->where('status', UniversalCreditEvidencePack::STATUS_PROCESSING)
                ->update(['last_error_code' => $exception instanceof \DomainException ? 'build_validation_failed' : 'build_failed']);

            throw $exception;
        } finally {
            app()->setLocale($previousLocale);
        }
    }

    public function tries(): int
    {
        return max(1, (int) config('finance.universal_credit.evidence_pack.tries', 3));
    }

    /** @return list<int> */
    public function backoff(): array
    {
        return array_values(array_map(
            'intval',
            (array) config('finance.universal_credit.evidence_pack.backoff_seconds', [60, 300, 900]),
        ));
    }

    public function uniqueId(): string
    {
        return (string) $this->packId;
    }

    public function uniqueVia(): Repository
    {
        return Cache::store('database');
    }

    public function failed(?Throwable $exception): void
    {
        $pack = UniversalCreditEvidencePack::withoutGlobalScopes()->find($this->packId);
        if (! $pack || $pack->status !== UniversalCreditEvidencePack::STATUS_PROCESSING) {
            return;
        }

        $pack->update([
            'status' => UniversalCreditEvidencePack::STATUS_FAILED,
            'last_error_code' => $pack->last_error_code ?: ($exception instanceof \DomainException ? 'build_validation_failed' : 'build_failed'),
            'failed_at' => now(),
        ]);
    }
}
