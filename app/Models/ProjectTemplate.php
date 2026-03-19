<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProjectTemplate extends Model
{
    use HasFactory;

    protected $fillable = [
        'name', 'service_type', 'description', 'phases', 'is_active',
    ];

    protected $casts = [
        'phases'    => 'array',
        'is_active' => 'boolean',
    ];
}
