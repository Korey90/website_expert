<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Contract extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'number', 'title', 'client_id', 'project_id', 'quote_id', 'contract_template_id', 'created_by',
        'status', 'currency', 'value', 'terms', 'notes', 'file_path',
        'starts_at', 'expires_at', 'sent_at', 'signed_at',
        'signature_data', 'signer_ip', 'signer_name',
    ];

    protected $casts = [
        'value'     => 'decimal:2',
        'starts_at' => 'date',
        'expires_at' => 'date',
        'sent_at'   => 'datetime',
        'signed_at' => 'datetime',
    ];

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class)->withTrashed();
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class)->withTrashed();
    }

    public function quote(): BelongsTo
    {
        return $this->belongsTo(Quote::class)->withTrashed();
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function contractTemplate(): BelongsTo
    {
        return $this->belongsTo(ContractTemplate::class);
    }
    public function statusColor(): string
    {
        return match ($this->status) {
            'draft'     => 'gray',
            'sent'      => 'info',
            'signed'    => 'success',
            'expired'   => 'warning',
            'cancelled' => 'danger',
            default     => 'gray',
        };
    }

    public static function nextNumber(): string
    {
        $count = self::withTrashed()->whereYear('created_at', date('Y'))->count() + 1;
        return 'CNT-' . date('Y') . '-' . str_pad($count, 3, '0', STR_PAD_LEFT);
    }
}
