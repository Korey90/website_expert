<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EmailTemplate extends Model
{
    use HasFactory;

    protected $fillable = [
        'name', 'slug', 'subject', 'body_html', 'body_text', 'variables', 'is_active',
    ];

    protected $casts = [
        'subject'   => 'array',
        'body_html' => 'array',
        'body_text' => 'array',
        'variables' => 'array',
        'is_active' => 'boolean',
    ];

    /**
     * Resolve subject/body for a given locale, falling back to English.
     */
    public function getForLocale(string $locale = 'en'): array
    {
        $subject   = $this->subject   ?? [];
        $body_html = $this->body_html ?? [];
        $body_text = $this->body_text ?? [];

        return [
            'subject'   => $subject[$locale]   ?? $subject['en']   ?? '',
            'body_html' => $body_html[$locale] ?? $body_html['en'] ?? '',
            'body_text' => $body_text[$locale] ?? $body_text['en'] ?? '',
        ];
    }
}
