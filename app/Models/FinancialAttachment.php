<?php

namespace App\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class FinancialAttachment extends Model
{
    use BelongsToTenant, HasFactory, SoftDeletes;

    public const SCAN_PENDING = 'pending';

    public const SCAN_CLEAN = 'clean';

    public const SCAN_REJECTED = 'rejected';

    public const SCAN_FAILED = 'failed';

    protected $fillable = [
        'business_id', 'attachable_type', 'attachable_id', 'uploaded_by',
        'document_type', 'disk', 'path', 'original_name', 'mime_type', 'size',
        'sha256', 'scan_status', 'scan_attempts', 'scan_engine', 'scan_error_code',
        'scan_requested_at', 'scanned_at', 'retention_until',
    ];

    protected function casts(): array
    {
        return [
            'size' => 'integer',
            'scan_attempts' => 'integer',
            'scan_requested_at' => 'datetime',
            'scanned_at' => 'datetime',
            'retention_until' => 'date',
        ];
    }

    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class);
    }

    public function attachable(): MorphTo
    {
        return $this->morphTo();
    }

    public function uploadedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    public function isPreviewable(): bool
    {
        return $this->mime_type === 'application/pdf'
            || str_starts_with($this->mime_type, 'image/');
    }
}
