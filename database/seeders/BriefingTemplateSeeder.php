<?php

namespace Database\Seeders;

use App\Models\BriefingTemplate;
use Illuminate\Database\Seeder;

class BriefingTemplateSeeder extends Seeder
{
    public function run(): void
    {
        if (BriefingTemplate::whereNull('business_id')->exists()) {
            $this->command->info('BriefingTemplate global templates already seeded — skipping.');
            return;
        }

        foreach ($this->templates() as $tpl) {
            BriefingTemplate::create($tpl);
        }

        $this->command->info('BriefingTemplate: ' . count($this->templates()) . ' global templates seeded.');
    }

    // ── Template definitions ───────────────────────────────────────────────

    private function templates(): array
    {
        $result = [];

        foreach ($this->services() as $slug => $names) {
            foreach (['en', 'pl'] as $lang) {
                $result[] = $this->discovery($slug, $names[$lang], $lang);
                $result[] = $this->qualification($slug, $names[$lang], $lang);
                $result[] = $this->proposalInput($slug, $names[$lang], $lang);
            }
        }

        return $result;
    }

    private function services(): array
    {
        return [
            'brochure-websites' => ['en' => 'Brochure Websites',       'pl' => 'Strony Wizytówkowe'],
            'ecommerce'         => ['en' => 'E-Commerce',               'pl' => 'Sklep Internetowy'],
            'web-applications'  => ['en' => 'Web Applications',         'pl' => 'Aplikacje Webowe'],
            'seo'               => ['en' => 'SEO & Digital Marketing',  'pl' => 'SEO i Marketing Cyfrowy'],
            'google-ads'        => ['en' => 'Google Ads (PPC)',         'pl' => 'Google Ads (PPC)'],
            'meta-ads'          => ['en' => 'Meta / Pixel Ads',         'pl' => 'Meta Ads'],
            'content'           => ['en' => 'Content Creation',         'pl' => 'Tworzenie Treści'],
            'audits'            => ['en' => 'Security & Performance Audits', 'pl' => 'Audyty Bezpieczeństwa i Wydajności'],
            'maintenance'       => ['en' => 'Website Maintenance',      'pl' => 'Opieka nad Stroną'],
        ];
    }

    // ── Discovery ──────────────────────────────────────────────────────────

    private function discovery(string $slug, string $serviceName, string $lang): array
    {
        $isEn = $lang === 'en';

        return [
            'business_id'  => null,
            'service_slug' => $slug,
            'type'         => 'discovery',
            'language'     => $lang,
            'title'        => $isEn
                ? "{$serviceName} — Discovery Brief"
                : "{$serviceName} — Brief Discovery",
            'description'  => $isEn
                ? "Understand the prospect's business goals, current situation and fit for {$serviceName}."
                : "Zrozumienie celów biznesowych klienta i dopasowania do usługi {$serviceName}.",
            'is_active'    => true,
            'sort_order'   => 0,
            'sections'     => $this->discoverySections($slug, $lang),
        ];
    }

    private function discoverySections(string $slug, string $lang): array
    {
        $isEn = $lang === 'en';

        $context = [
            'title' => $isEn ? 'Client Context' : 'Kontekst Klienta',
            'key'   => 'client_context',
            'questions' => [
                $this->q('company_name',    $isEn ? 'Company name'    : 'Nazwa firmy',       'text',     true),
                $this->q('industry',        $isEn ? 'Industry / niche': 'Branża / nisza',    'text',     true),
                $this->q('website_url',     $isEn ? 'Current website URL (or "none")' : 'Adres strony (lub „brak")', 'text', false),
                $this->q('decision_deadline', $isEn ? 'Decision deadline' : 'Termin decyzji', 'text',   false),
                $this->q('budget_indication', $isEn ? 'Budget indication' : 'Orientacyjny budżet', 'text', false),
            ],
        ];

        $goals = [
            'title' => $isEn ? 'Business Goals' : 'Cele Biznesowe',
            'key'   => 'business_goals',
            'questions' => [
                $this->q('primary_goal',    $isEn ? 'What is the primary goal for this project?' : 'Jaki jest główny cel projektu?', 'textarea', true),
                $this->q('success_metric',  $isEn ? 'How will you measure success in 6 months?' : 'Jak zmierzysz sukces za 6 miesięcy?', 'textarea', false),
                $this->q('target_audience', $isEn ? 'Who is your ideal customer?' : 'Kim jest Twój idealny klient?', 'textarea', false),
            ],
        ];

        $serviceSpecific = $this->discoveryServiceSection($slug, $lang);

        $budget = [
            'title' => $isEn ? 'Budget & Timeline' : 'Budżet i Harmonogram',
            'key'   => 'budget_timeline',
            'questions' => [
                $this->q('budget_range',      $isEn ? 'What is your budget range?' : 'Jaki jest Twój zakres budżetu?', 'text', false),
                $this->q('start_date',        $isEn ? 'Ideal project start date' : 'Idealny termin startu', 'text', false),
                $this->q('hard_deadline',     $isEn ? 'Is there a hard launch deadline?' : 'Czy jest twardy termin uruchomienia?', 'text', false),
                $this->q('decision_process',  $isEn ? 'Who else is involved in the decision?' : 'Kto jeszcze uczestniczy w decyzji?', 'text', false),
            ],
        ];

        $notes = [
            'title' => $isEn ? 'Additional Notes' : 'Dodatkowe Notatki',
            'key'   => 'additional_notes',
            'questions' => [
                $this->q('handlowiec_notes', $isEn ? 'Handover notes / observations' : 'Notatki i obserwacje handlowca', 'textarea', false),
                $this->q('next_action',      $isEn ? 'Agreed next action' : 'Uzgodniony następny krok', 'text', false),
            ],
        ];

        return [$context, $goals, $serviceSpecific, $budget, $notes];
    }

    private function discoveryServiceSection(string $slug, string $lang): array
    {
        $isEn = $lang === 'en';

        return match ($slug) {
            'brochure-websites' => [
                'title' => $isEn ? 'Website Requirements' : 'Wymagania Strony',
                'key'   => 'website_requirements',
                'questions' => [
                    $this->q('page_count',       $isEn ? 'How many pages are needed?'           : 'Ile podstron jest potrzebnych?',           'text',     false),
                    $this->q('cms_needed',        $isEn ? 'Do they need a CMS to manage content?': 'Czy potrzebują CMS do zarządzania treścią?','boolean',  false),
                    $this->q('content_ready',     $isEn ? 'Is copy/photography ready?'           : 'Czy treści/zdjęcia są gotowe?',            'boolean',  false),
                    $this->q('current_pain',      $isEn ? 'What is wrong with the current site?' : 'Co jest nie tak z obecną stroną?',         'textarea', false),
                    $this->q('competitor_sites',  $isEn ? 'Any competitor/inspiration sites?'    : 'Strony konkurencji/inspiracje?',            'textarea', false),
                ],
            ],
            'ecommerce' => [
                'title' => $isEn ? 'E-Commerce Requirements' : 'Wymagania Sklepu',
                'key'   => 'ecommerce_requirements',
                'questions' => [
                    $this->q('product_count',     $isEn ? 'Approximate number of products'       : 'Przybliżona liczba produktów',             'text',     true),
                    $this->q('payment_gateways',  $isEn ? 'Required payment gateways (Stripe, PayPal, etc.)' : 'Wymagane bramki płatności', 'text', false),
                    $this->q('existing_platform', $isEn ? 'Current platform / migration needed?'  : 'Obecna platforma / migracja?',            'text',     false),
                    $this->q('inventory_mgmt',    $isEn ? 'Does stock need to sync with another system?' : 'Czy stan magazynowy synchronizuje się z innym systemem?', 'textarea', false),
                    $this->q('shipping_needs',    $isEn ? 'Shipping requirements (zones, rates, carriers)' : 'Wymagania dostawy (strefy, stawki, kurierzy)', 'textarea', false),
                ],
            ],
            'web-applications' => [
                'title' => $isEn ? 'Application Requirements' : 'Wymagania Aplikacji',
                'key'   => 'app_requirements',
                'questions' => [
                    $this->q('core_feature',      $isEn ? 'What is the single most important feature?' : 'Jaka jest najważniejsza funkcjonalność?', 'textarea', true),
                    $this->q('user_roles',         $isEn ? 'What user roles / permissions are needed?' : 'Jakie role i uprawnienia użytkowników?', 'textarea', false),
                    $this->q('integrations',       $isEn ? 'Third-party integrations required?' : 'Wymagane integracje zewnętrzne?', 'textarea', false),
                    $this->q('existing_systems',   $isEn ? 'Existing systems to integrate with?' : 'Istniejące systemy do integracji?', 'textarea', false),
                    $this->q('discovery_budget',   $isEn ? 'Is client aware of paid discovery from £599?' : 'Czy klient jest świadomy płatnego discovery od £599?', 'boolean', true),
                ],
            ],
            'seo' => [
                'title' => $isEn ? 'SEO Requirements' : 'Wymagania SEO',
                'key'   => 'seo_requirements',
                'questions' => [
                    $this->q('current_rankings',  $isEn ? 'Current keyword rankings / traffic (GA4 / GSC access?)' : 'Obecne pozycje / ruch (dostęp GA4/GSC?)', 'textarea', false),
                    $this->q('target_keywords',   $isEn ? 'Top 3–5 target keywords or topics' : 'Top 3–5 docelowych słów kluczowych lub tematów', 'textarea', true),
                    $this->q('local_seo',         $isEn ? 'Is local SEO needed (Google Business Profile)?' : 'Czy potrzebne jest lokalne SEO (Google Business Profile)?', 'boolean', false),
                    $this->q('competitor_seo',    $isEn ? 'Who are the main organic competitors?' : 'Kim są główni organiczni konkurenci?', 'textarea', false),
                    $this->q('past_seo_work',     $isEn ? 'Any prior SEO work / penalties?' : 'Czy były poprzednie działania SEO / kary?', 'textarea', false),
                ],
            ],
            'google-ads' => [
                'title' => $isEn ? 'Google Ads Requirements' : 'Wymagania Google Ads',
                'key'   => 'google_ads_requirements',
                'questions' => [
                    $this->q('existing_account',  $isEn ? 'Existing Google Ads account? (MCC access needed)' : 'Istniejące konto Google Ads? (dostęp MCC)', 'boolean', false),
                    $this->q('ad_budget',         $isEn ? 'Monthly ad spend budget (min £500/mo)' : 'Miesięczny budżet reklamowy (min £500/mc)', 'text', true),
                    $this->q('conversion_goal',   $isEn ? 'Primary conversion goal (leads, calls, sales)' : 'Główny cel konwersji (leady, telefony, sprzedaż)', 'text', true),
                    $this->q('landing_page_url',  $isEn ? 'Landing page URL for ads' : 'URL landing page dla reklam', 'text', false),
                    $this->q('target_geography',  $isEn ? 'Target geography (city, region, national)' : 'Docelowa geografia (miasto, region, kraj)', 'text', false),
                ],
            ],
            'meta-ads' => [
                'title' => $isEn ? 'Meta Ads Requirements' : 'Wymagania Meta Ads',
                'key'   => 'meta_ads_requirements',
                'questions' => [
                    $this->q('meta_pixel',        $isEn ? 'Meta Pixel installed?' : 'Zainstalowany Meta Pixel?', 'boolean', false),
                    $this->q('ad_budget',         $isEn ? 'Monthly ad spend budget (min £300/mo)' : 'Miesięczny budżet reklamowy (min £300/mc)', 'text', true),
                    $this->q('campaign_goal',     $isEn ? 'Campaign goal (awareness, traffic, leads, sales)' : 'Cel kampanii (zasięg, ruch, leady, sprzedaż)', 'text', true),
                    $this->q('audience_details',  $isEn ? 'Target audience — age, interests, location' : 'Grupa docelowa — wiek, zainteresowania, lokalizacja', 'textarea', false),
                    $this->q('creative_assets',   $isEn ? 'Creative assets available (images, video)?' : 'Dostępne materiały kreatywne (grafiki, wideo)?', 'boolean', false),
                ],
            ],
            'content' => [
                'title' => $isEn ? 'Content Requirements' : 'Wymagania Contentowe',
                'key'   => 'content_requirements',
                'questions' => [
                    $this->q('content_type',      $isEn ? 'Blog posts, social captions, or both?' : 'Posty blogowe, opisy social media, czy oba?', 'text', true),
                    $this->q('volume',            $isEn ? 'How many pieces per month?' : 'Ile treści miesięcznie?', 'text', true),
                    $this->q('tone_of_voice',     $isEn ? 'Describe your tone of voice / brand personality' : 'Opisz ton komunikacji / osobowość marki', 'textarea', false),
                    $this->q('target_language',   $isEn ? 'Target language(s) — EN, PL, PT?' : 'Język docelowy — EN, PL, PT?', 'text', false),
                    $this->q('existing_content',  $isEn ? 'Existing content / brand guidelines to share?' : 'Istniejące treści / brand guidelines do udostępnienia?', 'textarea', false),
                ],
            ],
            'audits' => [
                'title' => $isEn ? 'Audit Scope' : 'Zakres Audytu',
                'key'   => 'audit_scope',
                'questions' => [
                    $this->q('site_url',          $isEn ? 'Website URL to audit' : 'URL strony do audytu', 'text', true),
                    $this->q('platform',          $isEn ? 'Platform / CMS / framework' : 'Platforma / CMS / framework', 'text', false),
                    $this->q('hosting_provider',  $isEn ? 'Hosting provider' : 'Dostawca hostingu', 'text', false),
                    $this->q('known_issues',      $isEn ? 'Known issues or recent incidents?' : 'Znane problemy lub ostatnie incydenty?', 'textarea', false),
                    $this->q('audit_priority',    $isEn ? 'Audit priority — security, performance, or both?' : 'Priorytet audytu — bezpieczeństwo, wydajność, czy oba?', 'text', true),
                ],
            ],
            'maintenance' => [
                'title' => $isEn ? 'Maintenance Scope' : 'Zakres Opieki',
                'key'   => 'maintenance_scope',
                'questions' => [
                    $this->q('site_url',          $isEn ? 'Website URL' : 'URL strony', 'text', true),
                    $this->q('platform',          $isEn ? 'Platform / CMS' : 'Platforma / CMS', 'text', true),
                    $this->q('hosting_provider',  $isEn ? 'Current hosting provider' : 'Obecny dostawca hostingu', 'text', false),
                    $this->q('last_update',       $isEn ? 'When were plugins/themes last updated?' : 'Kiedy ostatnio aktualizowano wtyczki/motywy?', 'text', false),
                    $this->q('backup_solution',   $isEn ? 'Existing backup solution?' : 'Istniejące rozwiązanie do backupów?', 'text', false),
                    $this->q('migrate_hosting',   $isEn ? 'Interested in migrating to our managed hosting (+£29/mo)?' : 'Zainteresowanie migracją na nasz managed hosting (+£29/mc)?', 'boolean', false),
                ],
            ],
            default => [
                'title' => $isEn ? 'Service Requirements' : 'Wymagania Usługi',
                'key'   => 'service_requirements',
                'questions' => [
                    $this->q('requirements', $isEn ? 'Describe your requirements' : 'Opisz swoje wymagania', 'textarea', true),
                ],
            ],
        };
    }

    // ── Qualification ──────────────────────────────────────────────────────

    private function qualification(string $slug, string $serviceName, string $lang): array
    {
        $isEn = $lang === 'en';

        return [
            'business_id'  => null,
            'service_slug' => $slug,
            'type'         => 'qualification',
            'language'     => $lang,
            'title'        => $isEn
                ? "{$serviceName} — Qualification Brief"
                : "{$serviceName} — Brief Kwalifikacyjny",
            'description'  => $isEn
                ? "Qualify the lead's budget, authority, need and timeline before preparing a proposal."
                : "Kwalifikacja budżetu, decydenta, potrzeby i harmonogramu przed przygotowaniem oferty.",
            'is_active'    => true,
            'sort_order'   => 1,
            'sections'     => $this->qualificationSections($lang),
        ];
    }

    private function qualificationSections(string $lang): array
    {
        $isEn = $lang === 'en';

        return [
            [
                'title' => $isEn ? 'Budget Confirmed' : 'Budżet Potwierdzony',
                'key'   => 'budget_confirmed',
                'questions' => [
                    $this->q('budget_confirmed',   $isEn ? 'Has the client confirmed budget range?' : 'Czy klient potwierdził zakres budżetu?', 'boolean', true),
                    $this->q('budget_amount',      $isEn ? 'Confirmed budget amount / range' : 'Potwierdzony budżet / zakres', 'text', true),
                    $this->q('payment_preference', $isEn ? 'Payment preference — upfront, milestone, monthly?' : 'Preferowana forma płatności — z góry, etapy, miesięcznie?', 'text', false),
                ],
            ],
            [
                'title' => $isEn ? 'Decision Authority' : 'Decydent',
                'key'   => 'decision_authority',
                'questions' => [
                    $this->q('decision_maker',   $isEn ? 'Who is the final decision maker?' : 'Kto podejmuje ostateczną decyzję?', 'text', true),
                    $this->q('other_suppliers',  $isEn ? 'Are they comparing other suppliers?' : 'Czy porównują innych dostawców?', 'boolean', false),
                    $this->q('decision_timeline',$isEn ? 'When will they make the decision?' : 'Kiedy podejmą decyzję?', 'text', false),
                ],
            ],
            [
                'title' => $isEn ? 'Need & Fit' : 'Potrzeba i Dopasowanie',
                'key'   => 'need_fit',
                'questions' => [
                    $this->q('problem_confirmed', $isEn ? 'Is the core problem clearly defined?' : 'Czy kluczowy problem jest jasno zdefiniowany?', 'boolean', true),
                    $this->q('urgency',           $isEn ? 'What is driving urgency?' : 'Co napędza pilność?', 'textarea', false),
                    $this->q('risk_of_no_action', $isEn ? 'What happens if they do nothing?' : 'Co się stanie jeśli nie podejmą działania?', 'textarea', false),
                ],
            ],
            [
                'title' => $isEn ? 'Timeline & Readiness' : 'Harmonogram i Gotowość',
                'key'   => 'timeline_readiness',
                'questions' => [
                    $this->q('start_date',         $isEn ? 'Confirmed project start date' : 'Potwierdzony termin startu projektu', 'text', false),
                    $this->q('content_ready',      $isEn ? 'Are content / assets ready?' : 'Czy treści i materiały są gotowe?', 'boolean', false),
                    $this->q('stakeholder_bought_in', $isEn ? 'Have all key stakeholders bought in?' : 'Czy wszyscy kluczowi interesariusze są za?', 'boolean', false),
                ],
            ],
            [
                'title' => $isEn ? 'Qualification Outcome' : 'Wynik Kwalifikacji',
                'key'   => 'qualification_outcome',
                'questions' => [
                    $this->q('qualified',         $isEn ? 'Is this lead qualified?' : 'Czy lead jest zakwalifikowany?', 'boolean', true),
                    $this->q('disqualify_reason', $isEn ? 'If not qualified — reason?' : 'Jeśli nie zakwalifikowany — dlaczego?', 'textarea', false),
                    $this->q('next_step',         $isEn ? 'Agreed next step' : 'Uzgodniony następny krok', 'text', true),
                ],
            ],
        ];
    }

    // ── Proposal Input ─────────────────────────────────────────────────────

    private function proposalInput(string $slug, string $serviceName, string $lang): array
    {
        $isEn = $lang === 'en';

        return [
            'business_id'  => null,
            'service_slug' => $slug,
            'type'         => 'proposal_input',
            'language'     => $lang,
            'title'        => $isEn
                ? "{$serviceName} — Proposal Input Brief"
                : "{$serviceName} — Brief do Propozycji",
            'description'  => $isEn
                ? "Capture all inputs needed to write a winning proposal for {$serviceName}."
                : "Zebranie wszystkich informacji potrzebnych do przygotowania propozycji dla {$serviceName}.",
            'is_active'    => true,
            'sort_order'   => 2,
            'sections'     => $this->proposalInputSections($slug, $lang),
        ];
    }

    private function proposalInputSections(string $slug, string $lang): array
    {
        $isEn = $lang === 'en';

        return [
            [
                'title' => $isEn ? 'Project Overview' : 'Przegląd Projektu',
                'key'   => 'project_overview',
                'questions' => [
                    $this->q('project_summary',   $isEn ? 'Summary of project / what we are building' : 'Podsumowanie projektu / co budujemy', 'textarea', true),
                    $this->q('client_name',       $isEn ? 'Client name and company' : 'Nazwa klienta i firmy', 'text', true),
                    $this->q('contact_email',     $isEn ? 'Primary contact email' : 'Email głównego kontaktu', 'text', true),
                ],
            ],
            [
                'title' => $isEn ? 'Scope Confirmed' : 'Zakres Potwierdzony',
                'key'   => 'scope_confirmed',
                'questions' => [
                    $this->q('scope_items',       $isEn ? 'Confirmed scope items (list deliverables)' : 'Potwierdzone elementy zakresu (lista rezultatów)', 'textarea', true),
                    $this->q('out_of_scope',      $isEn ? 'Explicitly out of scope' : 'Wyraźnie poza zakresem', 'textarea', false),
                    $this->q('optional_items',    $isEn ? 'Optional add-ons to include in proposal' : 'Opcjonalne dodatki do oferty', 'textarea', false),
                ],
            ],
            [
                'title' => $isEn ? 'Pricing Approach' : 'Podejście do Wyceny',
                'key'   => 'pricing_approach',
                'questions' => [
                    $this->q('price_tier',        $isEn ? 'Which pricing tier / package applies?' : 'Który poziom / pakiet cenowy?', 'text', true),
                    $this->q('custom_price',      $isEn ? 'Custom price (if different from standard)' : 'Cena niestandardowa (jeśli inna od standardowej)', 'text', false),
                    $this->q('discount',          $isEn ? 'Any discount agreed? Reason?' : 'Uzgodniony rabat? Powód?', 'text', false),
                    $this->q('payment_terms',     $isEn ? 'Payment terms agreed' : 'Uzgodnione warunki płatności', 'text', false),
                ],
            ],
            [
                'title' => $isEn ? 'Timeline & Milestones' : 'Harmonogram i Kamienie Milowe',
                'key'   => 'timeline_milestones',
                'questions' => [
                    $this->q('start_date',        $isEn ? 'Proposed start date' : 'Proponowana data startu', 'text', false),
                    $this->q('delivery_date',     $isEn ? 'Proposed delivery / go-live date' : 'Proponowana data dostawy / uruchomienia', 'text', false),
                    $this->q('key_milestones',    $isEn ? 'Key milestones (if phased delivery)' : 'Kluczowe kamienie milowe (jeśli dostarczanie etapami)', 'textarea', false),
                ],
            ],
            [
                'title' => $isEn ? 'Proposal Notes' : 'Notatki do Propozycji',
                'key'   => 'proposal_notes',
                'questions' => [
                    $this->q('win_themes',        $isEn ? 'Key win themes to emphasise in proposal' : 'Kluczowe motywy do podkreślenia w propozycji', 'textarea', false),
                    $this->q('objections',        $isEn ? 'Likely objections to address' : 'Prawdopodobne zastrzeżenia do adresowania', 'textarea', false),
                    $this->q('internal_notes',    $isEn ? 'Internal notes (not shown in proposal)' : 'Notatki wewnętrzne (niewidoczne w propozycji)', 'textarea', false),
                ],
            ],
        ];
    }

    // ── Question builder ───────────────────────────────────────────────────

    private function q(
        string $key,
        string $label,
        string $type = 'text',
        bool   $required = false,
        string $placeholder = ''
    ): array {
        $definition = [
            'key'      => $key,
            'label'    => $label,
            'type'     => $type,
            'required' => $required,
        ];

        if ($placeholder !== '') {
            $definition['placeholder'] = $placeholder;
        }

        return $definition;
    }
}
