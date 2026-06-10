<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ServicePageBlock extends Model
{
    use HasFactory;

    public const TYPES = [
        'hero'             => 'Hero',
        'features_grid'    => 'Features Grid',
        'packages'         => 'Packages / Pricing Cards',
        'pricing_table'    => 'Pricing Table',
        'faq'              => 'FAQ',
        'cta_banner'       => 'CTA Banner',
        'text_section'     => 'Text Section',
        'comparison_table' => 'Comparison Table',
    ];

    protected $fillable = [
        'service_page_id',
        'type',
        'sort_order',
        'content',
        'settings',
        'is_active',
    ];

    protected $casts = [
        'content'   => 'array',
        'settings'  => 'array',
        'is_active' => 'boolean',
        'sort_order' => 'integer',
    ];

    public function servicePage(): BelongsTo
    {
        return $this->belongsTo(ServicePage::class);
    }
}
