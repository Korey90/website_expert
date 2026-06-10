<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Translatable\HasTranslations;

class ServicePage extends Model
{
    use HasFactory, HasTranslations;

    public array $translatable = ['title', 'meta_title', 'meta_description', 'nav_label'];

    protected $fillable = [
        'slug',
        'title',
        'meta_title',
        'meta_description',
        'nav_label',
        'is_published',
        'show_in_nav',
        'sort_order',
    ];

    protected $casts = [
        'is_published' => 'boolean',
        'show_in_nav'  => 'boolean',
        'sort_order'   => 'integer',
    ];

    public function blocks(): HasMany
    {
        return $this->hasMany(ServicePageBlock::class)->orderBy('sort_order');
    }

    public function activeBlocks(): HasMany
    {
        return $this->hasMany(ServicePageBlock::class)
            ->where('is_active', true)
            ->orderBy('sort_order');
    }
}
