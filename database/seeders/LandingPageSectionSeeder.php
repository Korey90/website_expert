<?php

namespace Database\Seeders;

use App\Models\SiteSection;
use Illuminate\Database\Seeder;

/**
 * Seeds SiteSection records for the two static landing pages:
 *  - landing_pl  → /pl/strony-internetowe-uk
 *  - landing_en  → /en/web-design-northern-ireland
 *
 * Idempotent: uses updateOrCreate so it is safe to run multiple times.
 * Editable from Filament admin → Marketing → Front-end Sections.
 */
class LandingPageSectionSeeder extends Seeder
{
    public function run(): void
    {
        // ── Polish LP ─────────────────────────────────────────────────────────
        SiteSection::updateOrCreate(['key' => 'landing_pl'], [
            'label'      => 'Landing Page PL — /pl/strony-internetowe-uk',
            'title'      => ['pl' => 'Strony internetowe dla polskich firm w Wielkiej Brytanii', 'en' => 'Websites for Polish businesses in the UK'],
            'subtitle'   => ['pl' => 'Stworzę profesjonalną stronę, która wzbudza zaufanie brytyjskich klientów i pomaga zdobywać nowe zapytania. Cały projekt omawiamy po polsku.', 'en' => ''],
            'body'       => null,
            'button_text' => ['pl' => 'Otrzymaj bezpłatną wycenę', 'en' => 'Get a free quote'],
            'button_url' => '#contact-form',
            'image_path' => null,
            'is_active'  => true,
            'sort_order' => 90,
            'extra'      => [
                // Hero
                'hero_price_label'    => 'Strony od £799',
                'hero_delivery_label' => 'Realizacja 3–5 tygodni',
                'hero_cta_secondary'  => 'Zobacz realizacje',
                'trust_items'         => [
                    ['icon' => '📍', 'text' => 'Baza w Północnej Irlandii'],
                    ['icon' => '🇵🇱', 'text' => 'Obsługa po polsku'],
                    ['icon' => '📱', 'text' => 'Mobile-first'],
                    ['icon' => '💷', 'text' => 'Ustalona cena z góry'],
                ],

                // Package
                'package_from'     => '£799',
                'package_scope'    => [
                    'Do pięciu głównych podstron',
                    'Projekt dopasowany do branży',
                    'Mobile-first (telefony i tablety)',
                    'Formularz kontaktowy',
                    'Podstawowe SEO techniczne',
                    'Google Analytics i Search Console',
                    'SSL i zabezpieczenie formularzy',
                    'Panel do samodzielnej edycji treści',
                    'Dwie rundy poprawek',
                    'Wdrożenie i szkolenie po polsku',
                ],
                'package_excluded' => [
                    'Rozbudowany sklep e-commerce',
                    'Niestandardowe aplikacje',
                    'Profesjonalna sesja zdjęciowa',
                    'Nieograniczone poprawki',
                    'Miesięczne SEO',
                ],
                'no_hidden_fees_note' => 'Brak ukrytych kosztów. Ostateczny zakres i cena są ustalane przed rozpoczęciem prac.',

                // Founder / About
                'founder_name'        => '',
                'founder_bio'         => 'Website Expert to pracownia założona przez developera z siedzibą w Północnej Irlandii. Rozmawiasz bezpośrednio z osobą odpowiedzialną za planowanie, projektowanie, budowanie i uruchamianie Twojego projektu.',
                'founder_photo_url'   => '',
                'founder_linkedin_url' => '',
                'founder_location'    => 'Północna Irlandia',
                'founder_experience'  => '10+ lat doświadczenia',
                'founder_stats'       => [],

                // Testimonials
                'testimonials' => [
                    [
                        'quote'      => 'Nowa strona znacznie ułatwiła zrozumienie naszych usług i zauważalnie zwiększyła liczbę zapytań przez formularz kontaktowy.',
                        'name'       => '',
                        'role'       => '',
                        'photo_url'  => '',
                        'verify_url' => '',
                    ],
                    [
                        'quote'      => 'Bezpośredni kontakt z deweloperem zrobił ogromną różnicę. Każda decyzja była podejmowana szybko, a wszystko zostało dostarczone zgodnie z obietnicą.',
                        'name'       => '',
                        'role'       => '',
                        'photo_url'  => '',
                        'verify_url' => '',
                    ],
                    [
                        'quote'      => 'Stała cena i jasny harmonogram oznaczały brak niespodzianek. Cały proces był przejrzysty od początku do końca.',
                        'name'       => '',
                        'role'       => '',
                        'photo_url'  => '',
                        'verify_url' => '',
                    ],
                ],

                // FAQ
                'faq_items' => [
                    ['q' => 'Ile kosztuje strona internetowa dla firmy?', 'a' => 'Podstawowy pakiet (do 5 podstron, formularz kontaktowy, SEO techniczne, CMS) zaczyna się od £799. Ostateczna cena zależy od zakresu projektu i jest ustalana przed rozpoczęciem prac.'],
                    ['q' => 'Jak długo trwa realizacja projektu?', 'a' => 'Typowy czas realizacji podstawowego pakietu to 3–5 tygodni od potwierdzenia zakresu i pierwszej wpłaty.'],
                    ['q' => 'Co muszę przygotować ze swojej strony?', 'a' => 'Podstawowe informacje o firmie, opis usług, kontakt, kilka zdjęć. Pomagam w zbieraniu treści podczas procesu.'],
                    ['q' => 'Czy będę właścicielem strony i domeny?', 'a' => 'Tak. Po zakończeniu projektu i opłaceniu faktury otrzymujesz pełny dostęp do strony. Domenę rejestruj zawsze na siebie lub swoją firmę.'],
                    ['q' => 'Czy będę mógł samodzielnie aktualizować treści?', 'a' => 'Tak. Strona jest wyposażona w panel CMS umożliwiający edycję treści bez znajomości programowania. Szkolenie wchodzi w cenę.'],
                    ['q' => 'Co się dzieje po uruchomieniu strony?', 'a' => 'Przeprowadzam szkolenie z panelu administracyjnego i pozostaję dostępny w przypadku pytań technicznych.'],
                ],

                // Form / contact
                'form_title'       => 'Omów projekt i otrzymaj bezpłatną wycenę',
                'form_note'        => 'Bez zobowiązań i bez presji sprzedażowej. Odpowiedź w ciągu jednego dnia roboczego.',
                'contact_phone'    => env('CONTACT_PHONE', ''),
                'success_heading'  => 'Dziękujemy — zapytanie zostało wysłane!',
                'success_body'     => 'Zapoznam się z Twoim zapytaniem i odpiszę w ciągu jednego dnia roboczego.',
            ],
        ]);

        // ── English LP ────────────────────────────────────────────────────────
        SiteSection::updateOrCreate(['key' => 'landing_en'], [
            'label'      => 'Landing Page EN — /en/web-design-northern-ireland',
            'title'      => ['en' => 'Professional Websites for Northern Ireland Businesses', 'pl' => ''],
            'subtitle'   => ['en' => 'Get a fast, mobile-first website designed to build trust and generate more enquiries — with fixed pricing and direct contact with the developer.', 'pl' => ''],
            'body'       => null,
            'button_text' => ['en' => 'Get a fixed-price proposal', 'pl' => ''],
            'button_url' => '#contact-form',
            'image_path' => null,
            'is_active'  => true,
            'sort_order' => 91,
            'extra'      => [
                // Hero
                'hero_price_label'    => 'Websites from £799',
                'hero_delivery_label' => 'Delivered in 3–5 weeks',
                'hero_cta_secondary'  => 'View recent projects',
                'trust_items'         => [
                    ['icon' => '📍', 'text' => 'Based in Northern Ireland'],
                    ['icon' => '⭐', 'text' => '10+ years of commercial experience'],
                    ['icon' => '📱', 'text' => 'Mobile-first development'],
                    ['icon' => '💷', 'text' => 'Clear fixed pricing'],
                ],

                // Package
                'package_from'     => '£799',
                'package_scope'    => [
                    'Up to five core pages',
                    'Custom layout tailored to your business',
                    'Mobile-first design',
                    'Contact form',
                    'Basic technical SEO',
                    'Google Analytics and Search Console setup',
                    'SSL and form security',
                    'CMS for editing your own content',
                    'Two rounds of revisions',
                    'Deployment',
                    'Short handover training',
                ],
                'package_excluded' => [
                    'Complex e-commerce functionality',
                    'Custom web applications',
                    'Professional photography',
                    'Unlimited revisions',
                    'Ongoing monthly SEO',
                    'Large numbers of integrations',
                ],
                'no_hidden_fees_note' => 'No hidden fees. The final scope and price are confirmed in writing before work begins.',

                // Founder / About
                'founder_name'        => '',
                'founder_bio'         => 'Website Expert is a founder-led web studio based in Northern Ireland. You speak directly with the person responsible for planning, designing, building and launching your project. No account managers. No outsourced communication. No unclear responsibility.',
                'founder_photo_url'   => '',
                'founder_linkedin_url' => '',
                'founder_location'    => 'Northern Ireland',
                'founder_experience'  => '10+ years experience',
                'founder_career_stat' => '200+ projects contributed to',

                // Testimonials
                'testimonials' => [
                    [
                        'quote'      => 'The new website made our services much easier to understand and significantly increased the number of enquiries received through the contact form.',
                        'name'       => '',
                        'role'       => '',
                        'photo_url'  => '',
                        'verify_url' => '',
                    ],
                    [
                        'quote'      => 'Working directly with the developer made a huge difference. Every decision was made quickly and everything was delivered as promised.',
                        'name'       => '',
                        'role'       => '',
                        'photo_url'  => '',
                        'verify_url' => '',
                    ],
                    [
                        'quote'      => 'The fixed price and clear timeline meant there were no surprises. The whole process was straightforward from start to finish.',
                        'name'       => '',
                        'role'       => '',
                        'photo_url'  => '',
                        'verify_url' => '',
                    ],
                ],

                // FAQ
                'faq_items' => [
                    ['q' => 'How much does a business website cost?', 'a' => 'The basic package (up to 5 pages, contact form, technical SEO, CMS) starts from £799. The final price depends on the project scope and is confirmed in writing before work begins.'],
                    ['q' => 'How long will the project take?', 'a' => 'Typical delivery for the basic package is 3–5 weeks from scope confirmation and initial payment.'],
                    ['q' => 'What do I need to provide?', 'a' => 'Basic information about your business, a description of your services, contact details, and a few photos. I help gather and structure content during the project.'],
                    ['q' => 'Will I own the website and domain?', 'a' => 'Once the project has been fully paid, you receive ownership of the agreed website deliverables. The domain should always be registered in your name or business.'],
                    ['q' => 'Can I update the website myself?', 'a' => 'Yes. The website includes a CMS that allows you to edit content without any technical knowledge. Training is included in the basic package price.'],
                    ['q' => 'What happens after launch?', 'a' => 'I provide handover training and remain available for technical questions. Additional support, updates or expansion can be discussed separately.'],
                ],

                // Form / contact
                'form_title'       => 'Get a fixed-price proposal',
                'form_note'        => 'No obligation and no sales pressure. You will receive a personal response within one business day.',
                'contact_phone'    => env('CONTACT_PHONE', ''),
                'success_heading'  => "Thanks — your enquiry has been received.",
                'success_body'     => 'I will review the information and respond within one business day.',
            ],
        ]);
    }
}
