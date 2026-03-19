<?php

namespace Database\Seeders;

use App\Models\SiteSection;
use Illuminate\Database\Seeder;

class SiteSectionSeeder extends Seeder
{
    public function run(): void
    {
        $sections = [
            // -------------------------------------------------------
            // Hero Section
            // -------------------------------------------------------
            [
                'key'         => 'hero',
                'label'       => 'Hero Section',
                'title'       => 'We Build Websites That Win Clients',
                'subtitle'    => 'Bespoke web design and development for UK businesses. From stunning brochure sites to powerful web applications — built to perform, built to last.',
                'button_text' => 'Get a Free Quote',
                'button_url'  => '#calculator',
                'image_path'  => null,
                'extra'       => [
                    'secondary_button_text' => 'View Our Work',
                    'secondary_button_url'  => '#portfolio',
                    'badge_text'            => '5-star rated on Google',
                    'stats'                 => [
                        ['value' => '200+', 'label' => 'Websites Delivered'],
                        ['value' => '98%',  'label' => 'Client Satisfaction'],
                        ['value' => '10+',  'label' => 'Years Experience'],
                    ],
                ],
                'is_active'   => true,
                'sort_order'  => 1,
            ],
            // -------------------------------------------------------
            // Trust Strip (logos / social proof)
            // -------------------------------------------------------
            [
                'key'         => 'trust_strip',
                'label'       => 'Trust Strip / Logos',
                'title'       => 'Trusted by UK Businesses',
                'subtitle'    => null,
                'body'        => null,
                'button_text' => null,
                'button_url'  => null,
                'image_path'  => null,
                'extra'       => [
                    'badges' => [
                        ['text' => '⭐ 5.0 Google Reviews'],
                        ['text' => '🔒 GDPR Compliant'],
                        ['text' => '🇬🇧 UK-Based Team'],
                        ['text' => '⚡ PageSpeed 95+'],
                    ],
                    'clients' => [
                        'Hargreaves Solicitors',
                        'NTS Direct',
                        'Oakfield Dental',
                        'Pinnacle Recruitment',
                        'Coastal Escapes',
                        'Bloom & Grow',
                    ],
                ],
                'is_active'   => true,
                'sort_order'  => 2,
            ],
            // -------------------------------------------------------
            // Services Section
            // -------------------------------------------------------
            [
                'key'         => 'services',
                'label'       => 'Services Section',
                'title'       => 'Everything You Need to Succeed Online',
                'subtitle'    => 'From your first website to a full digital transformation — we have the expertise to make it happen.',
                'body'        => null,
                'button_text' => 'See All Services',
                'button_url'  => '/services',
                'image_path'  => null,
                'extra'       => [
                    'services' => [
                        [
                            'icon'        => 'monitor',
                            'title'       => 'Brochure Websites',
                            'description' => 'Professional, mobile-first websites that create the right first impression and generate enquiries.',
                            'price_from'  => '£799',
                            'link'        => '/services/brochure-websites',
                        ],
                        [
                            'icon'        => 'shopping-cart',
                            'title'       => 'E-Commerce Stores',
                            'description' => 'Sell online with confidence. WooCommerce and headless solutions tailored to your products.',
                            'price_from'  => '£2,999',
                            'link'        => '/services/ecommerce',
                        ],
                        [
                            'icon'        => 'code',
                            'title'       => 'Web Applications',
                            'description' => 'Bespoke Laravel and React applications. Customer portals, SaaS platforms, booking systems.',
                            'price_from'  => '£5,999',
                            'link'        => '/services/web-applications',
                        ],
                        [
                            'icon'        => 'search',
                            'title'       => 'SEO & Digital Marketing',
                            'description' => 'Rank higher, attract more visitors, and convert them into paying customers.',
                            'price_from'  => '£499',
                            'link'        => '/services/seo',
                        ],
                        [
                            'icon'        => 'bar-chart',
                            'title'       => 'Google Ads (PPC)',
                            'description' => 'Targeted pay-per-click campaigns that deliver measurable ROI from day one.',
                            'price_from'  => '£399/mo',
                            'link'        => '/services/google-ads',
                        ],
                        [
                            'icon'        => 'settings',
                            'title'       => 'Website Maintenance',
                            'description' => 'Keep your site fast, secure, and up to date with our monthly care plans.',
                            'price_from'  => '£149/mo',
                            'link'        => '/services/maintenance',
                        ],
                    ],
                ],
                'is_active'   => true,
                'sort_order'  => 3,
            ],
            // -------------------------------------------------------
            // About Section
            // -------------------------------------------------------
            [
                'key'         => 'about',
                'label'       => 'About Section',
                'title'       => 'A Digital Agency That Treats You Like a Partner',
                'subtitle'    => 'Based in Manchester. Working with businesses across the UK.',
                'body'        => '<p>We\'re a small, dedicated team of developers, designers, and digital marketers who genuinely care about your results. No account managers passing your project around — you work directly with the people building your website.</p><p>Since 2014, we\'ve delivered over 200 projects for businesses ranging from sole traders to multi-site enterprises. We\'re proud of every one of them.</p><p>We don\'t do cookie-cutter. Every project starts with a proper brief, a proper plan, and a commitment to delivering something you\'ll be proud of.</p>',
                'button_text' => 'Our Story',
                'button_url'  => '/about',
                'image_path'  => null,
                'extra'       => [
                    'founded'      => '2014',
                    'team_size'    => '8',
                    'projects'     => '200+',
                    'location'     => 'Manchester, UK',
                    'highlights'   => [
                        'Direct access to your developer — no middlemen',
                        'UK-based team, UK business hours',
                        'Fixed-price quotes — no hidden costs',
                        'Ongoing support after launch',
                    ],
                ],
                'is_active'   => true,
                'sort_order'  => 4,
            ],
            // -------------------------------------------------------
            // Process Section
            // -------------------------------------------------------
            [
                'key'         => 'process',
                'label'       => 'How It Works / Process',
                'title'       => 'Simple Process. Brilliant Results.',
                'subtitle'    => 'We\'ve refined our process over 10 years to make working with us as smooth as possible.',
                'body'        => null,
                'button_text' => null,
                'button_url'  => null,
                'image_path'  => null,
                'extra'       => [
                    'steps' => [
                        ['number' => '01', 'title' => 'Discovery Call',      'description' => 'We learn about your business, goals, and budget. We\'ll tell you honestly what\'s possible and what it will cost.'],
                        ['number' => '02', 'title' => 'Quote & Brief',        'description' => 'You receive a detailed, fixed-price quote within 48 hours. Once approved, we create a full project brief.'],
                        ['number' => '03', 'title' => 'Design',               'description' => 'We design your website in Figma. You see exactly what you\'re getting before a line of code is written.'],
                        ['number' => '04', 'title' => 'Build & Test',         'description' => 'We build your site, test it across all devices and browsers, and run performance checks.'],
                        ['number' => '05', 'title' => 'Launch & Handover',    'description' => 'We handle the go-live, provide training, and give you everything you need to manage your site.'],
                        ['number' => '06', 'title' => 'Ongoing Support',      'description' => 'We\'re not gone after launch. Monthly maintenance plans, updates, and SEO available.'],
                    ],
                ],
                'is_active'   => true,
                'sort_order'  => 5,
            ],
            // -------------------------------------------------------
            // Portfolio / Case Studies Teaser
            // -------------------------------------------------------
            [
                'key'         => 'portfolio',
                'label'       => 'Portfolio Section',
                'title'       => 'Recent Work We\'re Proud Of',
                'subtitle'    => 'Every project is different. Here are a few recent examples.',
                'body'        => null,
                'button_text' => 'View All Projects',
                'button_url'  => '/portfolio',
                'image_path'  => null,
                'extra'       => [
                    'items' => [
                        [
                            'client'      => 'Hargreaves Solicitors',
                            'title'       => 'Solicitors Website Redesign',
                            'tags'        => ['Laravel', 'Responsive', 'WCAG AA'],
                            'result'      => '+40% contact form conversions',
                        ],
                        [
                            'client'      => 'NTS Direct',
                            'title'       => 'B2B E-Commerce Platform',
                            'tags'        => ['WooCommerce', 'B2B Portal', '3,500 SKUs'],
                            'result'      => '£80k online sales in first month',
                        ],
                        [
                            'client'      => 'Oakfield Dental',
                            'title'       => 'Dental Practice Website',
                            'tags'        => ['Brochure', 'Booking Integration', 'CQC'],
                            'result'      => '60% increase in new patient enquiries',
                        ],
                    ],
                ],
                'is_active'   => true,
                'sort_order'  => 6,
            ],
            // -------------------------------------------------------
            // Testimonials
            // -------------------------------------------------------
            [
                'key'         => 'testimonials',
                'label'       => 'Testimonials Section',
                'title'       => 'What Our Clients Say',
                'subtitle'    => null,
                'body'        => null,
                'button_text' => null,
                'button_url'  => null,
                'image_path'  => null,
                'extra'       => [
                    'reviews' => [
                        [
                            'name'    => 'Robert Hargreaves',
                            'company' => 'Hargreaves Solicitors',
                            'rating'  => 5,
                            'text'    => 'WebsiteExpert delivered exactly what we needed — a clean, professional site that our clients trust. The team were communicative throughout and delivered on time and on budget. Highly recommended.',
                        ],
                        [
                            'name'    => 'Lisa Thornton',
                            'company' => 'NTS Direct',
                            'rating'  => 5,
                            'text'    => 'Our new e-commerce platform has transformed our business. The B2B trade portal alone saved our sales team hours every week. The team understood our industry and delivered a product that really works.',
                        ],
                        [
                            'name'    => 'Dr Priya Patel',
                            'company' => 'Oakfield Dental',
                            'rating'  => 5,
                            'text'    => 'From the initial call to launch, everything was smooth and professional. Our new website has already brought us several new patients. Very happy with the service.',
                        ],
                        [
                            'name'    => 'Daniel Walsh',
                            'company' => 'Pinnacle Recruitment',
                            'rating'  => 5,
                            'text'    => 'We were referred to WebsiteExpert by another client and we\'re so glad we made the call. They\'re building our custom job board and the quality has been exceptional throughout.',
                        ],
                    ],
                ],
                'is_active'   => true,
                'sort_order'  => 7,
            ],
            // -------------------------------------------------------
            // CTA Banner
            // -------------------------------------------------------
            [
                'key'         => 'cta_banner',
                'label'       => 'CTA Banner',
                'title'       => 'Ready to Get Started?',
                'subtitle'    => 'Get a free, no-obligation quote in 48 hours. No jargon. No pressure. Just honest advice.',
                'body'        => null,
                'button_text' => 'Get My Free Quote',
                'button_url'  => '#calculator',
                'image_path'  => null,
                'extra'       => [
                    'secondary_button_text' => 'Book a Discovery Call',
                    'secondary_button_url'  => '/contact',
                    'phone'                 => '0161 000 0000',
                    'email'                 => 'hello@websiteexpert.co.uk',
                ],
                'is_active'   => true,
                'sort_order'  => 8,
            ],
            // -------------------------------------------------------
            // FAQ Section
            // -------------------------------------------------------
            [
                'key'         => 'faq',
                'label'       => 'FAQ Section',
                'title'       => 'Frequently Asked Questions',
                'subtitle'    => 'Can\'t find what you\'re looking for? Just ask us.',
                'body'        => null,
                'button_text' => 'Contact Us',
                'button_url'  => '/contact',
                'image_path'  => null,
                'extra'       => [
                    'items' => [
                        [
                            'question' => 'How long does a website take?',
                            'answer'   => 'A standard brochure website typically takes 3–5 weeks from kick-off to launch. E-commerce and web applications vary — we\'ll give you a clear timeline in your project brief.',
                        ],
                        [
                            'question' => 'Do you work with clients outside Manchester?',
                            'answer'   => 'Yes! The vast majority of our work is done remotely. We work with clients all across the UK. We\'re always happy to arrange a video call.',
                        ],
                        [
                            'question' => 'What do I need to provide?',
                            'answer'   => 'Typically: your logo (or brief for a new one), any text/content for the site, and any specific photos. We can help with content and photography if needed.',
                        ],
                        [
                            'question' => 'Will my website work on mobile?',
                            'answer'   => 'Absolutely. Every website we build is designed mobile-first and tested on a wide range of devices and screen sizes.',
                        ],
                        [
                            'question' => 'Do you offer payment plans?',
                            'answer'   => 'Yes. For larger projects we\'re happy to discuss staged payments. We typically take a 40–50% deposit, with the balance on delivery or split across milestones.',
                        ],
                        [
                            'question' => 'Will I be able to update my website myself?',
                            'answer'   => 'Yes. We build with a CMS (WordPress or our own Filament-based system) and provide training and a user guide so you can manage your content confidently.',
                        ],
                    ],
                ],
                'is_active'   => true,
                'sort_order'  => 9,
            ],
        ];

        foreach ($sections as $data) {
            SiteSection::firstOrCreate(['key' => $data['key']], $data);
        }
    }
}
