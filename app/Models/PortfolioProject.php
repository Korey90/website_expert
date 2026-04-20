<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;
use Spatie\Translatable\HasTranslations;

class PortfolioProject extends Model
{
    use HasFactory, SoftDeletes, HasTranslations;

    public array $translatable = ['title', 'tag', 'description', 'result'];

    protected $fillable = [
        'title',
        'tag',
        'description',
        'result',
        'client_name',
        'slug',
        'image_path',
        'link',
        'tags',
        'is_featured',
        'sort_order',
        'is_active',
    ];

    protected $casts = [
        'tags'        => 'array',
        'is_featured' => 'boolean',
        'is_active'   => 'boolean',
    ];

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeFeatured(Builder $query): Builder
    {
        return $query->where('is_featured', true);
    }

    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('sort_order')->orderBy('id');
    }

    protected static function booted(): void
    {
        static::creating(function (self $project) {
            if (empty($project->slug) && ! empty($project->client_name)) {
                $project->slug = Str::slug($project->client_name);
            }
        });
    }

}
