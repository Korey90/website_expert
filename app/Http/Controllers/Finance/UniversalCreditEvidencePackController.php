<?php

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Controller;
use App\Models\UniversalCreditEvidencePack;
use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class UniversalCreditEvidencePackController extends Controller
{
    public function __invoke(UniversalCreditEvidencePack $evidencePack): StreamedResponse
    {
        Gate::authorize('download', $evidencePack);

        abort_unless($evidencePack->status === UniversalCreditEvidencePack::STATUS_COMPLETED, 409);
        abort_if($evidencePack->expires_at === null || $evidencePack->expires_at->isPast(), 410);
        abort_unless(filled($evidencePack->disk) && filled($evidencePack->path), 404);
        $disk = $this->verifiedDisk($evidencePack);

        return $disk->download($evidencePack->path, $evidencePack->filename, [
            'Cache-Control' => 'private, no-store, max-age=0',
            'Pragma' => 'no-cache',
            'X-Content-Type-Options' => 'nosniff',
            'X-Universal-Credit-Pack-SHA256' => $evidencePack->zip_sha256,
            'X-Universal-Credit-Snapshot-SHA256' => $evidencePack->snapshot_hash,
        ]);
    }

    private function verifiedDisk(UniversalCreditEvidencePack $pack): FilesystemAdapter
    {
        $disk = Storage::disk($pack->disk);
        abort_unless($disk->exists($pack->path), 404);
        $stream = $disk->readStream($pack->path);
        abort_unless(is_resource($stream), 404);

        try {
            $hash = hash_init('sha256');
            $bytes = hash_update_stream($hash, $stream);
            $actualHash = hash_final($hash);
        } finally {
            fclose($stream);
        }

        abort_unless(
            $bytes === $pack->bytes && hash_equals((string) $pack->zip_sha256, $actualHash),
            409,
            __('finance.universal_credit.pack.errors.download_integrity'),
        );

        return $disk;
    }
}
