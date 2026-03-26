<?php

namespace Database\Seeders;

use App\Models\CalculatorString;
use Illuminate\Database\Seeder;

class CalculatorStringsSeeder extends Seeder
{
    public function run(): void
    {
        CalculatorString::truncate();

        $items = [
            // ----------------------------------------------------------------
            // Group: header
            // ----------------------------------------------------------------
            ['key' => 'section_label', 'group' => 'header', 'sort_order' => 1,
             'value_en' => 'Cost Calculator',
             'value_pl' => 'Kalkulator kosztów',
             'value_pt' => 'Calculadora de Custos',
             'note'     => 'Small label above the section title'],

            ['key' => 'title', 'group' => 'header', 'sort_order' => 2,
             'value_en' => 'How Much Will Your Project Cost?',
             'value_pl' => 'Ile będzie kosztował Twój projekt?',
             'value_pt' => 'Quanto Vai Custar o Seu Projeto?',
             'note'     => 'Main section heading'],

            ['key' => 'subtitle', 'group' => 'header', 'sort_order' => 3,
             'value_en' => 'Answer a few questions and get an instant quote estimate. No registration required.',
             'value_pl' => 'Odpowiedz na kilka pytań i otrzymaj wstępną wycenę. Szybko, bez rejestracji.',
             'value_pt' => 'Responda a algumas perguntas e obtenha uma estimativa instantânea. Sem registo necessário.',
             'note'     => 'Section subheading below title'],

            // ----------------------------------------------------------------
            // Group: navigation
            // ----------------------------------------------------------------
            ['key' => 'step_label', 'group' => 'navigation', 'sort_order' => 10,
             'value_en' => 'Step',
             'value_pl' => 'Krok',
             'value_pt' => 'Passo',
             'note'     => 'Progress bar prefix, e.g. "Step 1 of 8"'],

            ['key' => 'step_of', 'group' => 'navigation', 'sort_order' => 11,
             'value_en' => 'of',
             'value_pl' => 'z',
             'value_pt' => 'de',
             'note'     => 'Progress bar connector, e.g. "Step 1 of 8"'],

            ['key' => 'nav_next', 'group' => 'navigation', 'sort_order' => 12,
             'value_en' => 'Next →',
             'value_pl' => 'Dalej →',
             'value_pt' => 'Seguinte →',
             'note'     => 'Primary next-step button'],

            ['key' => 'nav_back', 'group' => 'navigation', 'sort_order' => 13,
             'value_en' => '← Back',
             'value_pl' => '← Wstecz',
             'value_pt' => '← Voltar',
             'note'     => 'Back button'],

            ['key' => 'nav_skip', 'group' => 'navigation', 'sort_order' => 14,
             'value_en' => 'Skip →',
             'value_pl' => 'Pomiń →',
             'value_pt' => 'Saltar →',
             'note'     => 'Skip button (optional steps, e.g. integrations)'],

            ['key' => 'nav_calc', 'group' => 'navigation', 'sort_order' => 15,
             'value_en' => 'Calculate Quote 🚀',
             'value_pl' => 'Oblicz wycenę 🚀',
             'value_pt' => 'Calcular Orçamento 🚀',
             'note'     => 'Button on the last step (step 8)'],

            // ----------------------------------------------------------------
            // Group: misc_labels
            // ----------------------------------------------------------------
            ['key' => 'from_label', 'group' => 'misc_labels', 'sort_order' => 20,
             'value_en' => 'from',
             'value_pl' => 'od',
             'value_pt' => 'a partir de',
             'note'     => 'Prefix before price, e.g. "from £800"'],

            ['key' => 'base_multiplier_label', 'group' => 'misc_labels', 'sort_order' => 21,
             'value_en' => 'of base price',
             'value_pl' => 'ceny bazowej',
             'value_pt' => 'do preço base',
             'note'     => 'Suffix after multiplier, e.g. "×1.5 of base price"'],

            ['key' => 'no_extra_label', 'group' => 'misc_labels', 'sort_order' => 22,
             'value_en' => 'no extra charge',
             'value_pl' => 'bez dopłaty',
             'value_pt' => 'sem custo extra',
             'note'     => 'Shown for zero-cost add-ons'],

            ['key' => 'to_quote_label', 'group' => 'misc_labels', 'sort_order' => 23,
             'value_en' => 'to quote',
             'value_pl' => 'do wyceny',
             'value_pt' => 'do orçamento',
             'note'     => 'Suffix after deadline surcharge, e.g. "+30% to quote"'],

            ['key' => 'standard_pricing_label', 'group' => 'misc_labels', 'sort_order' => 24,
             'value_en' => 'standard pricing',
             'value_pl' => 'standardowa wycena',
             'value_pt' => 'preço padrão',
             'note'     => 'Shown for standard deadline option (no surcharge)'],

            ['key' => 'per_year', 'group' => 'misc_labels', 'sort_order' => 25,
             'value_en' => '/year',
             'value_pl' => '/rok',
             'value_pt' => '/ano',
             'note'     => 'Hosting cost suffix, e.g. "£48/year"'],

            ['key' => 'self_managed', 'group' => 'misc_labels', 'sort_order' => 26,
             'value_en' => 'self-managed',
             'value_pl' => 'własne zarządzanie',
             'value_pt' => 'autogerenciado',
             'note'     => 'Hosting option with no cost — own server'],

            ['key' => 'pages_addon', 'group' => 'misc_labels', 'sort_order' => 27,
             'value_en' => 'Each page above 5: +£80',
             'value_pl' => 'Każda strona powyżej 5: +£80',
             'value_pt' => 'Cada página acima de 5: +£80',
             'note'    => 'Hint shown on the pages slider step'],

            ['key' => 'pages_chip', 'group' => 'misc_labels', 'sort_order' => 28,
             'value_en' => 'pages',
             'value_pl' => 'podstron',
             'value_pt' => 'páginas',
             'note'     => 'Badge on result screen, e.g. "5 pages"'],

            ['key' => 'integrations_chip', 'group' => 'misc_labels', 'sort_order' => 29,
             'value_en' => 'integrations',
             'value_pl' => 'integracje',
             'value_pt' => 'integrações',
             'note'     => 'Badge on result screen, e.g. "3 integrations"'],

            // ----------------------------------------------------------------
            // Group: result_page
            // ----------------------------------------------------------------
            ['key' => 'result_title', 'group' => 'result_page', 'sort_order' => 30,
             'value_en' => 'Your Estimated Quote',
             'value_pl' => 'Twoja szacowana wycena',
             'value_pt' => 'O Seu Orçamento Estimado',
             'note'     => 'Heading on the results screen'],

            ['key' => 'result_subtitle', 'group' => 'result_page', 'sort_order' => 31,
             'value_en' => 'Estimate based on the information you provided.',
             'value_pl' => 'Wycena orientacyjna na podstawie podanych informacji.',
             'value_pt' => 'Estimativa com base nas informações fornecidas.',
             'note'     => 'Subheading on the results screen'],

            ['key' => 'result_cost_label', 'group' => 'result_page', 'sort_order' => 32,
             'value_en' => 'Estimated project cost',
             'value_pl' => 'Szacowany koszt projektu',
             'value_pt' => 'Custo estimado do projeto',
             'note'     => 'Label above the price range'],

            ['key' => 'hosting_addon_label', 'group' => 'result_page', 'sort_order' => 33,
             'value_en' => '+ hosting',
             'value_pl' => '+ hosting',
             'value_pt' => '+ alojamento',
             'note'     => 'Prefix before hosting cost note on result screen'],

            ['key' => 'restart', 'group' => 'result_page', 'sort_order' => 34,
             'value_en' => 'Start over',
             'value_pl' => 'Zacznij od nowa',
             'value_pt' => 'Recomeçar',
             'note'     => 'Link to restart the calculator'],

            // ----------------------------------------------------------------
            // Group: contact_form
            // ----------------------------------------------------------------
            ['key' => 'contact_title', 'group' => 'contact_form', 'sort_order' => 40,
             'value_en' => "Enter your details and we'll send you a detailed quote.",
             'value_pl' => 'Podaj swoje dane, żebyśmy mogli wysłać Ci szczegółową ofertę.',
             'value_pt' => 'Introduza os seus dados e enviaremos um orçamento detalhado.',
             'note'     => 'Instruction above the lead capture form'],

            ['key' => 'name_placeholder', 'group' => 'contact_form', 'sort_order' => 41,
             'value_en' => 'Your name / company',
             'value_pl' => 'Twoje imię / firma',
             'value_pt' => 'O seu nome / empresa',
             'note'     => 'Placeholder in the name input'],

            ['key' => 'email_placeholder', 'group' => 'contact_form', 'sort_order' => 42,
             'value_en' => 'Your email',
             'value_pl' => 'Twój email',
             'value_pt' => 'O seu email',
             'note'     => 'Placeholder in the email input'],

            ['key' => 'submit_btn', 'group' => 'contact_form', 'sort_order' => 43,
             'value_en' => 'Send enquiry 🚀',
             'value_pl' => 'Wyślij zapytanie 🚀',
             'value_pt' => 'Enviar pedido 🚀',
             'note'     => 'Submit button label'],

            ['key' => 'submitting_btn', 'group' => 'contact_form', 'sort_order' => 44,
             'value_en' => 'Sending…',
             'value_pl' => 'Wysyłanie…',
             'value_pt' => 'A enviar…',
             'note'     => 'Submit button label while sending'],

            ['key' => 'success_msg', 'group' => 'contact_form', 'sort_order' => 45,
             'value_en' => "✓ Done! We'll get back to you within 1 business day.",
             'value_pl' => '✓ Gotowe! Odezwiemy się w ciągu 24h roboczych.',
             'value_pt' => '✓ Enviado! Responderemos em 1 dia útil.',
             'note'     => 'Success message after form submission'],

            ['key' => 'sent_to', 'group' => 'contact_form', 'sort_order' => 46,
             'value_en' => 'Sent to:',
             'value_pl' => 'Na adres:',
             'value_pt' => 'Enviado para:',
             'note'     => 'Prefix before email address in success message'],
        ];

        foreach ($items as $data) {
            CalculatorString::create($data);
        }
    }
}
