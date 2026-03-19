<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AutomationRule extends Model
{
    use HasFactory;

    protected $fillable = [
        'name', 'trigger_event', 'conditions', 'actions', 'delay_minutes', 'is_active',
    ];

    protected $casts = [
        'conditions'     => 'array',
        'actions'        => 'array',
        'delay_minutes'  => 'integer',
        'is_active'      => 'boolean',
    ];
}
