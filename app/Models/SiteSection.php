<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SiteSection extends Model
{
    use HasFactory;

    protected $fillable = [
        'key', 'label', 'title', 'subtitle', 'body',
        'button_text', 'button_url', 'image_path',
        'extra', 'is_active', 'sort_order',
    ];

    protected $casts = [
        'extra'     => 'array',
        'is_active' => 'boolean',
    ];
}
