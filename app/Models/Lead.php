<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class Lead extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'title', 'client_id', 'contact_id', 'pipeline_stage_id',
        'assigned_to', 'value', 'currency', 'source',
        'calculator_data', 'notes', 'expected_close_date',
        'won_at', 'lost_at', 'lost_reason',
        'budget_min', 'budget_max',
    ];

    protected $casts = [
        'calculator_data'      => 'array',
        'value'                => 'decimal:2',
        'budget_min'           => 'decimal:2',
        'budget_max'           => 'decimal:2',
        'expected_close_date'  => 'date',
        'won_at'               => 'datetime',
        'lost_at'              => 'datetime',
    ];

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class)->withTrashed();
    }

    public function contact(): BelongsTo
    {
        return $this->belongsTo(Contact::class)->withTrashed();
    }

    public function stage(): BelongsTo
    {
        return $this->belongsTo(PipelineStage::class, 'pipeline_stage_id');
    }

    public function assignedTo(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function project(): HasOne
    {
        return $this->hasOne(Project::class);
    }

    public function activities(): HasMany
    {
        return $this->hasMany(LeadActivity::class)->latest();
    }

    public function notes(): HasMany
    {
        return $this->hasMany(LeadNote::class)->latest();
    }
}
