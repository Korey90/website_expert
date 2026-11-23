<?php

namespace App\Models;

use App\Traits\BelongsToTenant;
use DomainException;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UniversalCreditEvidencePackItem extends Model
{
    use BelongsToTenant, HasFactory;

    public const INCLUDED = 'included';

    public const EXCLUDED = 'excluded';

    protected $fillable = [
        'business_id', 'evidence_pack_id', 'logical_path', 'item_type',
        'source_type', 'source_id', 'attachment_id', 'inclusion_status',
        'reason', 'bytes', 'sha256',
    ];

    protected function casts(): array
    {
        return ['bytes' => 'integer'];
    }

    protected static function booted(): void
    {
        static::creating(function (self $item): void {
            $pack = UniversalCreditEvidencePack::withoutGlobalScopes()->find($item->evidence_pack_id);

            if (! $pack
                || $pack->business_id !== $item->business_id
                || $pack->status !== UniversalCreditEvidencePack::STATUS_PROCESSING
                || ! in_array($item->inclusion_status, [self::INCLUDED, self::EXCLUDED], true)
                || ($item->inclusion_status === self::INCLUDED
                    && ($item->bytes === null || preg_match('/^[a-f0-9]{64}$/', (string) $item->sha256) !== 1))
                || ($item->inclusion_status === self::EXCLUDED && blank($item->reason))) {
                throw new DomainException(__('finance.universal_credit.pack.errors.invalid_manifest_item'));
            }
        });

        static::updating(function (): void {
            throw new DomainException(__('finance.universal_credit.pack.errors.manifest_immutable'));
        });

        static::deleting(function (): void {
            throw new DomainException(__('finance.universal_credit.pack.errors.manifest_immutable'));
        });
    }

    public function evidencePack(): BelongsTo
    {
        return $this->belongsTo(UniversalCreditEvidencePack::class, 'evidence_pack_id');
    }

    public function attachment(): BelongsTo
    {
        return $this->belongsTo(FinancialAttachment::class);
    }
}
