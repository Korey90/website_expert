<?php

namespace App\Services\Finance;

use App\Jobs\ScanFinancialAttachmentJob;
use App\Models\FinancialAttachment;
use App\Models\User;
use DomainException;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\File\File;
use Throwable;

class FinancialAttachmentService
{
    public function store(
        Model $attachable,
        UploadedFile $file,
        User $actor,
        string $documentType,
    ): FinancialAttachment {
        if (! $file->isValid()) {
            throw new DomainException('The uploaded attachment is invalid.');
        }

        return $this->storeFile(
            $attachable,
            $file,
            basename($file->getClientOriginalName()),
            $actor,
            $documentType,
        );
    }

    public function storePath(
        Model $attachable,
        string $path,
        string $originalName,
        User $actor,
        string $documentType,
    ): FinancialAttachment {
        if (! is_file($path) || ! is_readable($path)) {
            throw new DomainException('The attachment source file is not readable.');
        }

        return $this->storeFile(
            $attachable,
            new File($path),
            basename(str_replace('\\', '/', $originalName)),
            $actor,
            $documentType,
        );
    }

    private function storeFile(
        Model $attachable,
        File $file,
        string $originalName,
        User $actor,
        string $documentType,
    ): FinancialAttachment {
        $businessId = $this->businessId($attachable);
        $this->assertActorMembership($actor, $businessId);
        $this->assertValidFile($file, $documentType);

        $realPath = $file->getRealPath();
        $sha256 = is_string($realPath) ? hash_file('sha256', $realPath) : false;

        if (! is_string($sha256)) {
            throw new DomainException('The attachment hash could not be calculated.');
        }

        $existing = FinancialAttachment::withoutGlobalScopes()
            ->where('business_id', $businessId)
            ->where('attachable_type', $attachable->getMorphClass())
            ->where('attachable_id', $attachable->getKey())
            ->where('sha256', $sha256)
            ->first();

        if ($existing) {
            throw new DomainException('This file is already attached to the financial record.');
        }

        $disk = (string) config('finance.attachments.disk', 'financial');
        $extension = strtolower($file->guessExtension() ?: pathinfo($originalName, PATHINFO_EXTENSION) ?: 'bin');
        $directory = implode('/', [
            $businessId,
            Str::kebab(class_basename($attachable)),
            (string) $attachable->getKey(),
        ]);
        $storedName = Str::uuid().'.'.$extension;
        $path = Storage::disk($disk)->putFileAs($directory, $file, $storedName);

        if (! is_string($path)) {
            throw new DomainException('The attachment could not be stored.');
        }

        try {
            $attachment = FinancialAttachment::withoutGlobalScopes()->create([
                'business_id' => $businessId,
                'attachable_type' => $attachable->getMorphClass(),
                'attachable_id' => $attachable->getKey(),
                'uploaded_by' => $actor->id,
                'document_type' => $documentType,
                'disk' => $disk,
                'path' => $path,
                'original_name' => $originalName !== '' ? $originalName : 'document.'.$extension,
                'mime_type' => (string) $file->getMimeType(),
                'size' => $file->getSize(),
                'sha256' => $sha256,
                'scan_status' => FinancialAttachment::SCAN_PENDING,
                'scan_requested_at' => now(),
                'retention_until' => today()->addYears((int) config('finance.attachments.retention_years', 6)),
            ]);
        } catch (Throwable $exception) {
            Storage::disk($disk)->delete($path);

            throw $exception;
        }

        ScanFinancialAttachmentJob::dispatch($attachment->id)
            ->onQueue((string) config('finance.attachments.scanner.queue', 'default'))
            ->afterCommit();

        return $attachment;
    }

    private function businessId(Model $attachable): string
    {
        $businessId = $attachable->getAttribute('business_id');

        if (! is_string($businessId) || $businessId === '' || ! $attachable->exists) {
            throw new DomainException('A persisted tenant-owned financial record is required.');
        }

        return $businessId;
    }

    private function assertActorMembership(User $actor, string $businessId): void
    {
        if (! $actor->businessMemberships()->where('business_id', $businessId)->where('is_active', true)->exists()) {
            throw new DomainException('The actor does not belong to the attachment business.');
        }
    }

    private function assertValidFile(File $file, string $documentType): void
    {
        if (! in_array($documentType, config('finance.attachments.document_types', []), true)) {
            throw new DomainException('Unsupported financial document type.');
        }

        if (($file->getSize() ?: 0) > (int) config('finance.attachments.max_bytes')) {
            throw new DomainException('The attachment exceeds the maximum allowed size.');
        }

        if (! in_array($file->getMimeType(), config('finance.attachments.allowed_mime_types', []), true)) {
            throw new DomainException('Unsupported financial attachment MIME type.');
        }
    }
}
