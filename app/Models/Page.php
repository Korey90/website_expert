<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Translatable\HasTranslations;

class Page extends Model
{
    use HasFactory, SoftDeletes, HasTranslations;

    public array $translatable = ['title', 'content', 'meta_title', 'meta_description'];

    protected $fillable = [
        'title', 'slug', 'content', 'meta_title', 'meta_description',
        'status', 'type', 'show_in_footer', 'sort_order', 'created_by', 'published_at',
    ];

    protected $casts = [
        'show_in_footer' => 'boolean',
        'published_at'   => 'datetime',
    ];

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}

