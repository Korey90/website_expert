<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LandingPageSection extends Model
{
    use HasFactory;

    public const TYPES = [
        'hero',
        'features',
        'testimonials',
        'cta',
        'form',
        'faq',
        'text',
        'video',
    ];

    protected $fillable = [
        'landing_page_id',
        'type',
        'order',
        'content',
        'settings',
        'is_visible',
    ];

    protected function casts(): array
    {
        return [
            'content'    => 'array',
            'settings'   => 'array',
            'is_visible' => 'boolean',
            'order'      => 'integer',
        ];
    }

    // -------------------------------------------------------------------------
    // Relations
    // -------------------------------------------------------------------------

    public function landingPage(): BelongsTo
    {
        return $this->belongsTo(LandingPage::class);
    }

    // -------------------------------------------------------------------------
    // Scopes
    // -------------------------------------------------------------------------

    public function scopeVisible($query)
    {
        return $query->where('is_visible', true);
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('order');
    }

    // -------------------------------------------------------------------------
    // Methods
    // -------------------------------------------------------------------------

    public function getDefaultContent(string $type): array
    {
        return match ($type) {
            'hero' => [
                'headline'    => 'Your Headline Here',
                'subheadline' => 'A compelling subheadline that explains your value.',
                'cta_text'    => 'Get Started',
                'cta_url'     => '#form',
                'image_path'  => null,
            ],
            'features' => [
                'headline' => 'Why Choose Us?',
                'items'    => [
                    ['icon' => 'check', 'title' => 'Fast Delivery', 'description' => 'We deliver in 7 business days.'],
                    ['icon' => 'star',  'title' => 'Quality Guarantee', 'description' => 'Or your money back.'],
                    ['icon' => 'shield', 'title' => 'Trusted by 50+ businesses', 'description' => 'Join our happy clients.'],
                ],
            ],
            'testimonials' => [
                'headline' => 'What Our Clients Say',
                'items'    => [
                    ['author' => 'John Smith', 'company' => 'Acme Corp', 'text' => 'Excellent service!', 'rating' => 5, 'avatar_path' => null],
                ],
            ],
            'cta' => [
                'headline'    => 'Ready to Grow?',
                'subheadline' => 'Join 50+ businesses that trust us.',
                'cta_text'    => "Let's Talk",
                'cta_url'     => '#form',
            ],
            'form' => [
                'headline'        => 'Get in Touch',
                'subheadline'     => 'We respond within 24 hours.',
                'fields'          => ['name', 'email', 'phone', 'message'],
                'required'        => ['name', 'email'],
                'cta_text'        => 'Send Message',
                'success_message' => 'Thank you! We will contact you shortly.',
                'redirect_url'    => null,
            ],
            'faq' => [
                'headline' => 'Frequently Asked Questions',
                'items'    => [
                    ['question' => 'How much does it cost?', 'answer' => 'Pricing is tailored to your needs.'],
                ],
            ],
            'text' => [
                'headline' => 'About Us',
                'html'     => '<p>Tell your story here.</p>',
            ],
            'video' => [
                'headline'        => 'See How We Work',
                'video_url'       => '',
                'autoplay'        => false,
                'thumbnail_path'  => null,
            ],
            default => [],
        };
    }
}
