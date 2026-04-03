<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Landing Page Templates
    |--------------------------------------------------------------------------
    | Each template defines a key, label, description and the initial section
    | types to create when a user picks this template.
    */
    'templates' => [
        'lead_magnet' => [
            'key'         => 'lead_magnet',
            'label'       => 'Lead Magnet',
            'description' => 'Capture leads with a compelling offer (free consultation, ebook, audit…)',
            'icon'        => 'heroicon-o-gift',
            'sections'    => ['hero', 'features', 'testimonials', 'cta', 'form'],
        ],
        'services' => [
            'key'         => 'services',
            'label'       => 'Services',
            'description' => 'Showcase your services and encourage visitors to get in touch.',
            'icon'        => 'heroicon-o-briefcase',
            'sections'    => ['hero', 'features', 'cta', 'faq', 'form'],
        ],
        'portfolio' => [
            'key'         => 'portfolio',
            'label'       => 'Portfolio',
            'description' => 'Show your work and testimonials to build trust.',
            'icon'        => 'heroicon-o-photo',
            'sections'    => ['hero', 'testimonials', 'cta', 'form'],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Conversion Goals
    |--------------------------------------------------------------------------
    */
    'conversion_goals' => [
        'book_call' => 'Book a Call',
        'download'  => 'Download a Resource',
        'purchase'  => 'Purchase',
        'contact'   => 'Contact Enquiry',
    ],

    /*
    |--------------------------------------------------------------------------
    | Section Type definitions with labels and icons for the UI
    |--------------------------------------------------------------------------
    */
    'section_types' => [
        'hero'         => ['label' => 'Hero',         'icon' => 'heroicon-o-star'],
        'features'     => ['label' => 'Features',     'icon' => 'heroicon-o-check-badge'],
        'testimonials' => ['label' => 'Testimonials', 'icon' => 'heroicon-o-chat-bubble-left'],
        'cta'          => ['label' => 'Call to Action','icon' => 'heroicon-o-cursor-arrow-rays'],
        'form'         => ['label' => 'Lead Form',    'icon' => 'heroicon-o-envelope'],
        'faq'          => ['label' => 'FAQ',           'icon' => 'heroicon-o-question-mark-circle'],
        'text'         => ['label' => 'Text Block',   'icon' => 'heroicon-o-document-text'],
        'video'        => ['label' => 'Video',         'icon' => 'heroicon-o-play-circle'],
    ],

    /*
    |--------------------------------------------------------------------------
    | Slug blacklist — these slugs are reserved for the platform
    |--------------------------------------------------------------------------
    */
    'slug_blacklist' => [
        'admin', 'api', 'lp', 'dashboard', 'login', 'register',
        'onboarding', 'business', 'landing-pages', 'portal',
        'contact', 'calculator', 'p',
    ],

    /*
    |--------------------------------------------------------------------------
    | Allowed video domains for type='video' sections
    |--------------------------------------------------------------------------
    */
    'allowed_video_domains' => [
        'youtube.com', 'www.youtube.com', 'youtu.be',
        'vimeo.com', 'www.vimeo.com',
    ],

    /*
    |--------------------------------------------------------------------------
    | Public runtime cache
    |--------------------------------------------------------------------------
    */
    'public_cache' => [
        'enabled' => env('LANDING_PAGES_PUBLIC_CACHE', true),
        'ttl_seconds' => env('LANDING_PAGES_PUBLIC_CACHE_TTL', 300),
    ],

    /*
    |--------------------------------------------------------------------------
    | Limits
    |--------------------------------------------------------------------------
    */
    'max_sections_per_page' => 20,

];
