<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PipelineStage extends Model
{
    use HasFactory;

    protected $fillable = [
        'name', 'slug', 'color', 'description', 'checklist', 'order', 'is_won', 'is_lost',
    ];

    protected $casts = [
        'checklist' => 'array',
        'is_won'    => 'boolean',
        'is_lost'   => 'boolean',
    ];

    public function leads(): HasMany
    {
        return $this->hasMany(Lead::class, 'pipeline_stage_id');
    }
}
