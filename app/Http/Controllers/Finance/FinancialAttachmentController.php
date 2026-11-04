<?php

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Controller;
use App\Models\FinancialAttachment;
use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class FinancialAttachmentController extends Controller
{
    public function preview(FinancialAttachment $attachment): StreamedResponse
    {
        Gate::authorize('view', $attachment);
        $disk = $this->verifiedDisk($attachment);

        abort_unless($attachment->isPreviewable(), 415, 'This attachment type cannot be previewed.');

        return $disk->response(
            $attachment->path,
            $attachment->original_name,
            $this->securityHeaders(),
            'inline',
        );
    }

    public function download(FinancialAttachment $attachment): StreamedResponse
    {
        Gate::authorize('view', $attachment);
        $disk = $this->verifiedDisk($attachment);

        return $disk->download(
            $attachment->path,
            $attachment->original_name,
            $this->securityHeaders(),
        );
    }

    private function verifiedDisk(FinancialAttachment $attachment): FilesystemAdapter
    {
        abort_unless(
            $attachment->scan_status === FinancialAttachment::SCAN_CLEAN,
            423,
            'The attachment is not available until its security scan passes.',
        );

        $disk = Storage::disk($attachment->disk);
        abort_unless($disk->exists($attachment->path), 404);

        $stream = $disk->readStream($attachment->path);
        abort_unless(is_resource($stream), 404);

        try {
            $context = hash_init('sha256');
            hash_update_stream($context, $stream);
            $actualHash = hash_final($context);
        } finally {
            fclose($stream);
        }

        abort_unless(
            hash_equals($attachment->sha256, $actualHash),
            409,
            'The attachment failed its integrity check.',
        );

        return $disk;
    }

    /** @return array<string, string> */
    private function securityHeaders(): array
    {
        return [
            'Cache-Control' => 'private, no-store, max-age=0',
            'Content-Security-Policy' => "default-src 'none'; img-src 'self' data:; style-src 'unsafe-inline'; sandbox",
            'X-Content-Type-Options' => 'nosniff',
        ];
    }
}
