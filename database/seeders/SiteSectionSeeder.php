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
                'title'       => ['en' => 'We Build Websites That Win Clients',       'pl' => 'Tworzymy strony internetowe, które zdobywają klientów'],
                'subtitle'    => ['en' => 'Bespoke web design and development for UK businesses. From stunning brochure sites to powerful web applications — built to perform, built to last.',
                                  'pl' => 'Dedykowane projektowanie i tworzenie stron dla firm z UK. Od efektownych stron wizytówkowych po rozbudowane aplikacje internetowe — zbudowane, by działać i trwać.'],
                'body'        => null,
                'button_text' => ['en' => 'Get a Free Quote', 'pl' => 'Darmowa wycena'],
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
                'title'       => ['en' => 'Trusted by UK Businesses', 'pl' => 'Zaufali nam przedsiębiorcy z całego UK'],
                'subtitle'    => null,
                'body'        => null,
                'button_text' => null,
                'button_url'  => null,
                'image_path'  => null,
                'extra'       => [
                    'section_label_en' => 'Trusted By',
                    'section_label_pl' => 'Zaufali nam',
                    'badges' => [
                        ['text' => '⭐ 5.0 Google Reviews'],
                        ['text' => '🔒 GDPR Compliant'],
                        ['text' => '🇬🇧 UK-Based Team'],
                        ['text' => '⚡ PageSpeed 95+'],
                    ],
                    'clients' => [
                        ['name' => 'Hargreaves Solicitors'],
                        ['name' => 'NTS Direct'],
                        ['name' => 'Oakfield Dental'],
                        ['name' => 'Pinnacle Recruitment'],
                        ['name' => 'Coastal Escapes'],
                        ['name' => 'Bloom & Grow'],
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
                'title'       => ['en' => 'Everything You Need to Succeed Online',    'pl' => 'Wszystko, czego potrzebujesz do sukcesu online'],
                'subtitle'    => ['en' => 'From your first website to a full digital transformation — we have the expertise to make it happen.',
                                  'pl' => 'Od pierwszej strony internetowej po pełną transformację cyfrową — mamy wiedzę i doświadczenie, by to zrealizować.'],
                'body'        => null,
                'button_text' => ['en' => 'See All Services', 'pl' => 'Zobacz wszystkie usługi'],
                'button_url'  => '/services',
                'image_path'  => null,
                'extra'       => [
                    'section_label_en' => 'Services',
                    'section_label_pl' => 'Oferta',
                    'services' => [
                        [
                            'icon'           => 'monitor',
                            'title_en'       => 'Brochure Websites',
                            'title_pl'       => 'Strony wizytówkowe',
                            'description_en' => 'Professional, mobile-first websites that create the right first impression and generate enquiries.',
                            'description_pl' => 'Profesjonalne strony firmowe mobile-first, które budują zaufanie i generują zapytania.',
                            'price_from'     => '£799',
                            'link'           => '/services/brochure-websites',
                        ],
                        [
                            'icon'           => 'shopping-cart',
                            'title_en'       => 'E-Commerce Stores',
                            'title_pl'       => 'Sklepy e-commerce',
                            'description_en' => 'Sell online with confidence. WooCommerce and headless solutions tailored to your products.',
                            'description_pl' => 'Szybkie, bezpieczne sklepy online z integracją płatności i systemu zamówień.',
                            'price_from'     => '£2,999',
                            'link'           => '/services/ecommerce',
                        ],
                        [
                            'icon'           => 'code',
                            'title_en'       => 'Web Applications',
                            'title_pl'       => 'Aplikacje internetowe',
                            'description_en' => 'Bespoke Laravel and React applications. Customer portals, SaaS platforms, booking systems.',
                            'description_pl' => 'Dedykowane aplikacje Laravel i React. Portale klientów, platformy SaaS, systemy rezerwacji.',
                            'price_from'     => '£5,999',
                            'link'           => '/services/web-applications',
                        ],
                        [
                            'icon'           => 'search',
                            'title_en'       => 'SEO & Digital Marketing',
                            'title_pl'       => 'SEO i Marketing Cyfrowy',
                            'description_en' => 'Rank higher, attract more visitors, and convert them into paying customers.',
                            'description_pl' => 'Zaistniej w Google. Audyty SEO, optymalizacja on-page i strategia contentu.',
                            'price_from'     => '£499',
                            'link'           => '/services/seo',
                        ],
                        [
                            'icon'           => 'bar-chart',
                            'title_en'       => 'Google Ads (PPC)',
                            'title_pl'       => 'Google Ads (PPC)',
                            'description_en' => 'Targeted pay-per-click campaigns that deliver measurable ROI from day one.',
                            'description_pl' => 'Kampanie płatne z realnym ROI – konfiguracja, optymalizacja i raportowanie.',
                            'price_from'     => '£399/mo',
                            'link'           => '/services/google-ads',
                        ],
                        [
                            'icon'           => 'settings',
                            'title_en'       => 'Website Maintenance',
                            'title_pl'       => 'Opieka nad stroną',
                            'description_en' => 'Keep your site fast, secure, and up to date with our monthly care plans.',
                            'description_pl' => 'Szybki hosting z SSL, backupami i opieką techniczną w pakiecie.',
                            'price_from'     => '£149/mo',
                            'link'           => '/services/maintenance',
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
                'title'       => ['en' => 'A Digital Agency That Treats You Like a Partner',        'pl' => 'Agencja cyfrowa, która traktuje Cię jak partnera'],
                'subtitle'    => ['en' => 'Based in Manchester. Working with businesses across the UK.', 'pl' => 'Siedziba w Manchesterze. Współpracujemy z firmami w całym UK.'],
                'body'        => [
                    'en' => '<p>We\'re a small, dedicated team of developers, designers, and digital marketers who genuinely care about your results. No account managers passing your project around — you work directly with the people building your website.</p><p>Since 2014, we\'ve delivered over 200 projects for businesses ranging from sole traders to multi-site enterprises. We\'re proud of every one of them.</p><p>We don\'t do cookie-cutter. Every project starts with a proper brief, a proper plan, and a commitment to delivering something you\'ll be proud of.</p>',
                    'pl' => '<p>Jesteśmy małym, zaangażowanym zespołem programistów, projektantów i marketerów, którym naprawdę zależy na Twoich wynikach. Żadnych pośredników — pracujesz bezpośrednio z ludźmi tworzącymi Twoją stronę.</p><p>Od 2014 roku zrealizowaliśmy ponad 200 projektów dla firm — od jednoosobowych działalności po rozbudowane przedsiębiorstwa. Jesteśmy z każdego z nich dumni.</p><p>Nie robimy szablonowych rozwiązań. Każdy projekt zaczyna się od porządnego briefu, planu i zaangażowania, aby dostarczyć coś, z czego będziesz dumny.</p>',
                ],
                'button_text' => ['en' => 'Our Story', 'pl' => 'Nasza historia'],
                'button_url'  => '/about',
                'image_path'  => null,
                'extra'       => [
                    'location'          => 'Manchester, UK',
                    'section_label_en'  => 'About Us',
                    'section_label_pl'  => 'O nas',
                    'stats'      => [
                        ['value' => '200+', 'label_en' => 'Projects Delivered',  'label_pl' => 'Zrealizowanych projektów'],
                        ['value' => '98%',  'label_en' => 'Client Satisfaction', 'label_pl' => 'Zadowolonych klientów'],
                        ['value' => '10+',  'label_en' => 'Years Experience',    'label_pl' => 'Lat doświadczenia'],
                    ],
                    'highlights' => [
                        [
                            'title_en' => 'Speed',
                            'title_pl' => 'Szybkość',
                            'body_en'  => 'Delivered in 2–6 weeks. Deadlines are not suggestions.',
                            'body_pl'  => 'Realizacje w 2–6 tygodniach. Terminy to nie sugestie.',
                        ],
                        [
                            'title_en' => 'Security',
                            'title_pl' => 'Bezpieczeństwo',
                            'body_en'  => 'Audits, SSL, GDPR – your data and your clients\' data are protected.',
                            'body_pl'  => 'Audyty, SSL, GDPR – Twoje dane i dane klientów są chronione.',
                        ],
                        [
                            'title_en' => 'Results',
                            'title_pl' => 'Wyniki',
                            'body_en'  => 'Conversion optimisation and SEO built in from day one.',
                            'body_pl'  => 'Optymalizacja konwersji i SEO wbudowane od pierwszego dnia.',
                        ],
                        [
                            'title_en' => 'Partnership',
                            'title_pl' => 'Partnerstwo',
                            'body_en'  => 'We don\'t disappear after launch. We\'re your tech partner.',
                            'body_pl'  => 'Nie znikamy po wdrożeniu. Jesteśmy Twoim tech-partnerem.',
                        ],
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
                'title'       => ['en' => 'Simple Process. Brilliant Results.',                                                   'pl' => 'Prosty proces. Doskonałe efekty.'],
                'subtitle'    => ['en' => 'We\'ve refined our process over 10 years to make working with us as smooth as possible.', 'pl' => 'Przez 10 lat doskonaliliśmy nasz proces, by współpraca z nami przebiegała jak najsprawniej.'],
                'body'        => null,
                'button_text' => null,
                'button_url'  => null,
                'image_path'  => null,
                'extra'       => [
                    'steps' => [
                        ['number' => '01', 'title' => 'Discovery Call',    'description' => 'We learn about your business, goals, and budget. We\'ll tell you honestly what\'s possible and what it will cost.'],
                        ['number' => '02', 'title' => 'Quote & Brief',     'description' => 'You receive a detailed, fixed-price quote within 48 hours. Once approved, we create a full project brief.'],
                        ['number' => '03', 'title' => 'Design',            'description' => 'We design your website in Figma. You see exactly what you\'re getting before a line of code is written.'],
                        ['number' => '04', 'title' => 'Build & Test',      'description' => 'We build your site, test it across all devices and browsers, and run performance checks.'],
                        ['number' => '05', 'title' => 'Launch & Handover', 'description' => 'We handle the go-live, provide training, and give you everything you need to manage your site.'],
                        ['number' => '06', 'title' => 'Ongoing Support',   'description' => 'We\'re not gone after launch. Monthly maintenance plans, updates, and SEO available.'],
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
                'title'       => ['en' => 'Recent Work We\'re Proud Of',                         'pl' => 'Ostatnie projekty, z których jesteśmy dumni'],
                'subtitle'    => ['en' => 'Every project is different. Here are a few recent examples.', 'pl' => 'Każdy projekt jest inny. Oto kilka ostatnich realizacji.'],
                'body'        => null,
                'button_text' => ['en' => 'View All Projects', 'pl' => 'Zobacz wszystkie projekty'],
                'button_url'  => '/portfolio',
                'image_path'  => null,
                'extra'       => [
                    'section_label_en' => 'Portfolio',
                    'section_label_pl' => 'Portfolio',
                    'items' => [
                        [
                            'client'    => 'Hargreaves Solicitors',
                            'title_en'  => 'Solicitors Website Redesign',
                            'title_pl'  => 'Redesign strony kancelarii prawnej',
                            'tag_en'    => 'Brochure Website',
                            'tag_pl'    => 'Strona wizytówkowa',
                            'desc_en'   => 'WCAG AA-compliant redesign for a Manchester solicitors firm. New case studies section, live chat integration.',
                            'desc_pl'   => 'Redesign strony dla kancelarii z Manchesteru zgodny z WCAG AA. Sekcja case studies, integracja live chat.',
                            'result_en' => '+40% contact form conversions',
                            'result_pl' => '+40% konwersji formularza kontaktowego',
                            'tags'      => ['Laravel', 'Responsive', 'WCAG AA'],
                            'image'     => '/images/portfolio/hargreaves-solicitors.svg',
                            'link'      => '/portfolio/hargreaves-solicitors',
                        ],
                        [
                            'client'    => 'NTS Direct',
                            'title_en'  => 'B2B E-Commerce Platform',
                            'title_pl'  => 'Platforma e-commerce B2B',
                            'tag_en'    => 'E-Commerce',
                            'tag_pl'    => 'E-Commerce',
                            'desc_en'   => 'Full B2B trade portal with 3,500+ SKUs, tiered pricing, custom quote builder and ERP integration.',
                            'desc_pl'   => 'Portal handlowy B2B z 3 500+ produktami, cenami poziomowymi, konfiguratorem wyceny i integracją ERP.',
                            'result_en' => '£80k online sales in first month',
                            'result_pl' => '80 tys. £ sprzedaży online w pierwszym miesiącu',
                            'tags'      => ['WooCommerce', 'B2B Portal', '3,500 SKUs'],
                            'image'     => '/images/portfolio/nts-direct.svg',
                            'link'      => '/portfolio/nts-direct',
                        ],
                        [
                            'client'    => 'Oakfield Dental',
                            'title_en'  => 'Dental Practice Website',
                            'title_pl'  => 'Strona kliniki dentystycznej',
                            'tag_en'    => 'Healthcare Website',
                            'tag_pl'    => 'Strona medyczna',
                            'desc_en'   => 'CQC-compliant dental practice website with online booking, patient portal and Google Reviews integration.',
                            'desc_pl'   => 'Strona gabinetu dentystycznego zgodna z CQC, rezerwacja online, portal pacjenta, integracja Google Reviews.',
                            'result_en' => '60% more new patient enquiries',
                            'result_pl' => '60% więcej zgłoszeń nowych pacjentów',
                            'tags'      => ['Brochure', 'Booking Integration', 'CQC'],
                            'image'     => '/images/portfolio/oakfield-dental.svg',
                            'link'      => '/portfolio/oakfield-dental',
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
                'title'       => ['en' => 'What Our Clients Say', 'pl' => 'Co mówią nasi klienci'],
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
                            'text_en' => 'WebsiteExpert delivered exactly what we needed — a clean, professional site that our clients trust. The team were communicative throughout and delivered on time and on budget. Highly recommended.',
                            'text_pl' => 'WebsiteExpert dostarczył dokładnie to, czego potrzebowaliśmy — czystą, profesjonalną stronę, której nasi klienci ufają. Zespół komunikował się przez cały czas i dostarczył projekt na czas i w budżecie. Gorąco polecam.',
                        ],
                        [
                            'name'    => 'Lisa Thornton',
                            'company' => 'NTS Direct',
                            'rating'  => 5,
                            'text_en' => 'Our new e-commerce platform has transformed our business. The B2B trade portal alone saved our sales team hours every week. The team understood our industry and delivered a product that really works.',
                            'text_pl' => 'Nasza nowa platforma e-commerce całkowicie przekształciła nasz biznes. Sam portal handlowy oszczędził naszemu zespołowi sprzedaży godziny tygodniowo. Zespół rozumiał naszą branżę i dostarczył produkt, który naprawdę działa.',
                        ],
                        [
                            'name'    => 'Dr Priya Patel',
                            'company' => 'Oakfield Dental',
                            'rating'  => 5,
                            'text_en' => 'From the initial call to launch, everything was smooth and professional. Our new website has already brought us several new patients. Very happy with the service.',
                            'text_pl' => 'Od pierwszej rozmowy do uruchomienia wszystko przebiegało sprawnie i profesjonalnie. Nasza nowa strona już przyciągnęła kilku nowych pacjentów. Bardzo zadowolona z usługi.',
                        ],
                        [
                            'name'    => 'Daniel Walsh',
                            'company' => 'Pinnacle Recruitment',
                            'rating'  => 5,
                            'text_en' => 'We were referred to WebsiteExpert by another client and we\'re so glad we made the call. They\'re building our custom job board and the quality has been exceptional throughout.',
                            'text_pl' => 'Zostaliśmy poleceni do WebsiteExpert przez innego klienta i bardzo cieszymy się, że zadzwoniliśmy. Budują dla nas niestandardową tablicę ogłoszeń o pracę i jakość przez cały czas jest wyjątkowa.',
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
                'title'       => ['en' => 'Ready to Get Started?',                                                          'pl' => 'Gotowy, aby zacząć?'],
                'subtitle'    => ['en' => 'Get a free, no-obligation quote in 48 hours. No jargon. No pressure. Just honest advice.', 'pl' => 'Bezpłatna wycena bez zobowiązań w 48 godziny. Bez żargonu. Bez nacisków. Tylko uczciwe porady.'],
                'body'        => null,
                'button_text' => ['en' => 'Get My Free Quote', 'pl' => 'Chcę bezpłatną wycenę'],
                'button_url'  => '#calculator',
                'image_path'  => null,
                'extra'       => [
                    'secondary_button_text_en' => 'Book a Discovery Call',
                    'secondary_button_text_pl' => 'Umów rozmowę',
                    'secondary_button_url'     => '/contact',
                    'phone'                    => '0161 000 0000',
                    'email'                    => 'hello@websiteexpert.co.uk',
                ],
                'is_active'   => true,
                'sort_order'  => 8,
            ],
            // -------------------------------------------------------
            // Cost Calculator Section
            // -------------------------------------------------------
            [
                'key'         => 'cost_calculator',
                'label'       => 'Cost Calculator Section',
                'title'       => ['en' => 'How Much Will Your Project Cost?', 'pl' => 'Ile będzie kosztował Twój projekt?'],
                'subtitle'    => ['en' => 'Answer a few questions and get an instant quote estimate. No registration required.', 'pl' => 'Odpowiedz na kilka pytań i otrzymaj wstępną wycenę. Szybko, bez rejestracji.'],
                'body'        => null,
                'button_text' => null,
                'button_url'  => null,
                'image_path'  => null,
                'extra'       => [
                    'section_label_en' => 'Cost Calculator',
                    'section_label_pl' => 'Kalkulator kosztów',
                    'steps' => [
                        ['question_en' => 'What type of project do you need?',       'question_pl' => 'Jakiego projektu potrzebujesz?',           'hint_en' => 'Choose the project type that best describes your needs.',            'hint_pl' => 'Wybierz typ projektu, który najlepiej opisuje Twoje potrzeby.'],
                        ['question_en' => 'How many pages do you need?',              'question_pl' => 'Ile podstron?',                            'hint_en' => 'E.g. Home, About, Services, Blog, Contact.',                         'hint_pl' => 'Np. Strona główna, O nas, Usługi, Blog, Kontakt.'],
                        ['question_en' => 'What level of design do you need?',        'question_pl' => 'Jaki poziom designu?',                     'hint_en' => 'Custom design means a unique graphic project from scratch.',         'hint_pl' => 'Custom design to indywidualny projekt graficzny od zera.'],
                        ['question_en' => 'Do you need a content management system?', 'question_pl' => 'Czy potrzebujesz systemu CMS?',            'hint_en' => 'A CMS lets you edit content without a developer.',                   'hint_pl' => 'CMS pozwala samodzielnie edytować treści bez programisty.'],
                        ['question_en' => 'Which integrations do you need?',          'question_pl' => 'Jakie integracje?',                        'hint_en' => 'You can choose multiple options or skip this step.',                 'hint_pl' => 'Możesz wybrać wiele opcji lub pominąć ten krok.'],
                        ['question_en' => 'Do you want an SEO package?',              'question_pl' => 'Pakiet SEO?',                              'hint_en' => 'Search engine optimisation — more organic traffic.',                 'hint_pl' => 'Optymalizacja pod wyszukiwarki – więcej ruchu organicznego.'],
                        ['question_en' => 'When do you need the project?',            'question_pl' => 'Kiedy potrzebujesz projektu?',             'hint_en' => 'Faster timelines require additional resources.',                     'hint_pl' => 'Szybsze terminy wymagają dodatkowych zasobów.'],
                        ['question_en' => 'Hosting & maintenance?',                   'question_pl' => 'Hosting i utrzymanie?',                    'hint_en' => 'Hosting prices shown as an annual add-on.',                         'hint_pl' => 'Ceny hostingu podane jako dopłata roczna.'],
                    ],
                    'result_title_en'        => 'Your Estimated Quote',
                    'result_title_pl'        => 'Twoja szacowana wycena',
                    'result_subtitle_en'     => 'Estimate based on the information you provided.',
                    'result_subtitle_pl'     => 'Wycena orientacyjna na podstawie podanych informacji.',
                    'result_cost_label_en'   => 'Estimated project cost',
                    'result_cost_label_pl'   => 'Szacowany koszt projektu',
                    'hosting_addon_label_en' => '+ hosting',
                    'hosting_addon_label_pl' => '+ hosting',
                    'per_year_en'            => '/year',
                    'per_year_pl'            => '/rok',
                    'contact_title_en'       => "Enter your details and we'll send you a detailed quote.",
                    'contact_title_pl'       => 'Podaj swoje dane, żebyśmy mogli wysłać Ci szczegółową ofertę.',
                    'name_placeholder_en'    => 'Your name / company',
                    'name_placeholder_pl'    => 'Twoje imię / firma',
                    'email_placeholder_en'   => 'Your email',
                    'email_placeholder_pl'   => 'Twój email',
                    'submit_btn_en'          => 'Send enquiry 🚀',
                    'submit_btn_pl'          => 'Wyślij zapytanie 🚀',
                    'success_msg_en'         => "✓ Done! We'll get back to you within 1 business day.",
                    'success_msg_pl'         => '✓ Gotowe! Odezwiemy się w ciągu 24h roboczych.',
                    'sent_to_en'             => 'Sent to:',
                    'sent_to_pl'             => 'Na adres:',
                    'restart_en'             => 'Start over',
                    'restart_pl'             => 'Zacznij od nowa',
                    'step_label_en'          => 'Step',
                    'step_label_pl'          => 'Krok',
                    'step_of_en'             => 'of',
                    'step_of_pl'             => 'z',
                    'pages_addon_en'         => 'Each page above 5: +£80',
                    'pages_addon_pl'         => 'Każda strona powyżej 5: +£80',
                    'nav_next_en'            => 'Next →',
                    'nav_next_pl'            => 'Dalej →',
                    'nav_back_en'            => '← Back',
                    'nav_back_pl'            => '← Wstecz',
                    'nav_skip_en'            => 'Skip →',
                    'nav_skip_pl'            => 'Pomiń →',
                    'nav_calc_en'            => 'Calculate Quote 🚀',
                    'nav_calc_pl'            => 'Oblicz wycenę 🚀',
                    'integrations_chip_en'   => 'integrations',
                    'integrations_chip_pl'   => 'integracje',
                    'pages_chip_en'          => 'pages',
                    'pages_chip_pl'          => 'podstron',
                ],
                'is_active'   => true,
                'sort_order'  => 10,
            ],
            // -------------------------------------------------------
            // FAQ Section
            // -------------------------------------------------------
            [
                'key'         => 'faq',
                'label'       => 'FAQ Section',
                'title'       => ['en' => 'Frequently Asked Questions',                'pl' => 'Najczęściej zadawane pytania'],
                'subtitle'    => ['en' => 'Can\'t find what you\'re looking for? Just ask us.', 'pl' => 'Nie możesz znaleźć odpowiedzi? Po prostu zapytaj.'],
                'body'        => null,
                'button_text' => ['en' => 'Contact Us', 'pl' => 'Skontaktuj się z nami'],
                'button_url'  => '/contact',
                'image_path'  => null,
                'extra'       => [
                    'items' => [
                        [
                            'question' => 'How long does a website take?',
                            'answer'   => 'A standard brochure website typically takes 3–5 weeks from kick-off to launch. E-commerce and web applications vary — we\'ll give you a clear timeline in your project brief.',
                        ],
                        [
                            'question' => 'How much does a website cost?',
                            'answer'   => 'Brochure websites start from £799. E-commerce from £2,999. We always provide a fixed-price quote — no surprises.',
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
            // -------------------------------------------------------
            // Navbar
            // -------------------------------------------------------
            [
                'key'         => 'navbar',
                'label'       => 'Navigation Bar',
                'title'       => ['en' => 'Navigation', 'pl' => 'Nawigacja'],
                'subtitle'    => null,
                'body'        => null,
                'button_text' => null,
                'button_url'  => null,
                'image_path'  => null,
                'extra'       => [
                    'brand_name'  => 'WebsiteExpert',
                    'cta_text_en' => 'Free Quote',
                    'cta_text_pl' => 'Bezpłatna wycena',
                    'cta_href'    => '#kontakt',
                    'links'       => [
                        ['label_en' => 'About Us',        'label_pl' => 'O nas',      'href' => '#o-nas'],
                        ['label_en' => 'Services',        'label_pl' => 'Oferta',     'href' => '#oferta'],
                        ['label_en' => 'Portfolio',       'label_pl' => 'Portfolio',  'href' => '#portfolio'],
                        ['label_en' => 'Cost Calculator', 'label_pl' => 'Kalkulator', 'href' => '#kalkulator'],
                        ['label_en' => 'Contact',         'label_pl' => 'Kontakt',    'href' => '#kontakt'],
                    ],
                ],
                'is_active'   => true,
                'sort_order'  => 0,
            ],
            // -------------------------------------------------------
            // Contact Section
            // -------------------------------------------------------
            [
                'key'         => 'contact',
                'label'       => 'Contact Section',
                'title'       => ['en' => "Let's talk about your project",                                                    'pl' => 'Porozmawiajmy o Twoim projekcie'],
                'subtitle'    => ['en' => "Get in touch and we'll reply within 24 business hours. First 30 min of consultation is free.",
                                  'pl' => 'Napisz do nas, a odezwiemy się w ciągu 24 godzin roboczych. Pierwsze 30 minut konsultacji jest bezpłatne.'],
                'body'        => null,
                'button_text' => null,
                'button_url'  => null,
                'image_path'  => null,
                'extra'       => [
                    'section_label_en'       => 'Contact',
                    'section_label_pl'       => 'Kontakt',
                    'email'                  => 'hello@websiteexpert.co.uk',
                    'phone'                  => '+44 000 000 000',
                    'phone_href'             => 'tel:+44000000000',
                    'privacy_url'            => '/privacy-policy',
                    'submit_btn_en'          => 'Send message',
                    'submit_btn_pl'          => 'Wyślij wiadomość',
                    'success_msg_en'         => "✓ Message sent! We'll be in touch shortly.",
                    'success_msg_pl'         => '✓ Wiadomość wysłana! Odezwiemy się wkrótce.',
                    'error_msg_en'           => 'Please fill in the required fields and accept the privacy policy.',
                    'error_msg_pl'           => 'Uzupełnij wymagane pola i zaznacz zgodę na przetwarzanie danych.',
                    'gdpr_text_en'           => 'I agree to the processing of my personal data for the purpose of responding to this enquiry, in accordance with the',
                    'gdpr_text_pl'           => 'Wyrażam zgodę na przetwarzanie moich danych osobowych w celu odpowiedzi na zapytanie, zgodnie z',
                    'gdpr_link_text_en'      => 'privacy policy',
                    'gdpr_link_text_pl'      => 'polityką prywatności',
                    'label_name_en'          => 'Full name',
                    'label_name_pl'          => 'Imię i nazwisko',
                    'placeholder_name_en'    => 'John Smith',
                    'placeholder_name_pl'    => 'Jan Kowalski',
                    'label_company_en'       => 'Company',
                    'label_company_pl'       => 'Firma',
                    'placeholder_company_en' => 'Company name',
                    'placeholder_company_pl' => 'Nazwa firmy',
                    'label_email_en'         => 'Email',
                    'label_email_pl'         => 'Email',
                    'label_phone_en'         => 'Phone',
                    'label_phone_pl'         => 'Telefon',
                    'label_nip_en'           => 'VAT / NIP',
                    'label_nip_pl'           => 'NIP / VAT',
                    'label_project_type_en'  => 'Project type',
                    'label_project_type_pl'  => 'Rodzaj projektu',
                    'label_contact_pref_en'  => 'Preferred contact',
                    'label_contact_pref_pl'  => 'Preferowany kontakt',
                    'label_message_en'       => 'Message',
                    'label_message_pl'       => 'Wiadomość',
                    'placeholder_message_en' => 'Tell us about your project or ask a question...',
                    'placeholder_message_pl' => 'Opisz swój projekt lub pytanie...',
                    'project_types' => [
                        ['value' => 'wizytowka', 'label_en' => 'Brochure website',     'label_pl' => 'Strona wizytówkowa'],
                        ['value' => 'ecommerce', 'label_en' => 'E-commerce store',     'label_pl' => 'Sklep e-commerce'],
                        ['value' => 'aplikacja', 'label_en' => 'Web application',      'label_pl' => 'Aplikacja webowa'],
                        ['value' => 'seo',       'label_en' => 'SEO / Positioning',    'label_pl' => 'SEO / Pozycjonowanie'],
                        ['value' => 'reklama',   'label_en' => 'Advertising campaign', 'label_pl' => 'Kampania reklamowa'],
                        ['value' => 'inne',      'label_en' => 'Other',                'label_pl' => 'Inne'],
                    ],
                    'contact_prefs' => [
                        ['value' => '',        'label_en' => '– Any method –',  'label_pl' => '– Dowolna forma –'],
                        ['value' => 'email',   'label_en' => 'Email',           'label_pl' => 'Email'],
                        ['value' => 'telefon', 'label_en' => 'Phone',           'label_pl' => 'Telefon'],
                        ['value' => 'teams',   'label_en' => 'Microsoft Teams', 'label_pl' => 'Microsoft Teams'],
                        ['value' => 'meet',    'label_en' => 'Google Meet',     'label_pl' => 'Google Meet'],
                    ],
                ],
                'is_active'   => true,
                'sort_order'  => 11,
            ],
            // -------------------------------------------------------
            // Footer
            // -------------------------------------------------------
            [
                'key'         => 'footer',
                'label'       => 'Footer',
                'title'       => ['en' => 'Footer', 'pl' => 'Stopka'],
                'subtitle'    => null,
                'body'        => null,
                'button_text' => null,
                'button_url'  => null,
                'image_path'  => null,
                'extra'       => [
                    'brand_name'    => 'WebsiteExpert',
                    'tagline_en'    => 'We create websites and web apps that work for your business.',
                    'tagline_pl'    => 'Tworzymy strony i aplikacje internetowe, które pracują na Twój biznes.',
                    'copyright_en'  => 'All rights reserved.',
                    'copyright_pl'  => 'Wszelkie prawa zastrzeżone.',
                    'built_with_en' => 'Designed and built with ❤️ in Poland',
                    'built_with_pl' => 'Zaprojektowane i zbudowane z ❤️ w Polsce',
                    'social'        => [
                        ['key' => 'linkedin',  'url' => '#', 'label' => 'LinkedIn'],
                        ['key' => 'facebook',  'url' => '#', 'label' => 'Facebook'],
                        ['key' => 'instagram', 'url' => '#', 'label' => 'Instagram'],
                    ],
                    'nav_groups' => [
                        [
                            'title_en' => 'Services',
                            'title_pl' => 'Usługi',
                            'links'    => [
                                ['label_en' => 'Brochure Websites', 'label_pl' => 'Strony wizytówkowe', 'href' => '#oferta'],
                                ['label_en' => 'E-Commerce Stores', 'label_pl' => 'Sklepy e-commerce',  'href' => '#oferta'],
                                ['label_en' => 'SEO',               'label_pl' => 'SEO',                'href' => '#oferta'],
                                ['label_en' => 'Google Ads',        'label_pl' => 'Google Ads',         'href' => '#oferta'],
                                ['label_en' => 'Web Hosting',       'label_pl' => 'Hosting WWW',        'href' => '#oferta'],
                            ],
                        ],
                        [
                            'title_en' => 'Company',
                            'title_pl' => 'Firma',
                            'links'    => [
                                ['label_en' => 'About Us',        'label_pl' => 'O nas',              'href' => '#o-nas'],
                                ['label_en' => 'Portfolio',       'label_pl' => 'Portfolio',          'href' => '#portfolio'],
                                ['label_en' => 'Cost Calculator', 'label_pl' => 'Kalkulator kosztów', 'href' => '#kalkulator'],
                                ['label_en' => 'Contact',         'label_pl' => 'Kontakt',            'href' => '#kontakt'],
                            ],
                        ],
                        [
                            'title_en' => 'Legal',
                            'title_pl' => 'Prawne',
                            'links'    => [
                                ['label_en' => 'Privacy Policy', 'label_pl' => 'Polityka prywatności', 'href' => '#'],
                                ['label_en' => 'Terms of Use',   'label_pl' => 'Regulamin',            'href' => '#'],
                                ['label_en' => 'Cookies',        'label_pl' => 'Cookies',              'href' => '#'],
                            ],
                        ],
                    ],
                ],
                'is_active'   => true,
                'sort_order'  => 12,
            ],
        ];

        foreach ($sections as $data) {
            SiteSection::updateOrCreate(['key' => $data['key']], $data);
        }
    }
}
