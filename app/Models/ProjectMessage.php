<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class ProjectMessage extends Model
{
    use HasFactory;

    protected $fillable = [
        'project_id', 'sender_type', 'sender_id', 'content', 'attachments', 'read_at',
    ];

    protected $casts = [
        'attachments' => 'array',
        'read_at'     => 'datetime',
    ];

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class)->withTrashed();
    }

    public function sender(): MorphTo
    {
        return $this->morphTo();
    }
}
