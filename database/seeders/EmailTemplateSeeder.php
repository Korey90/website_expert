<?php

namespace Database\Seeders;

use App\Models\EmailTemplate;
use Illuminate\Database\Seeder;

class EmailTemplateSeeder extends Seeder
{
    // ── Shared inline-style helpers ──────────────────────────────────────────

    private static function box(string $content, string $color = '#4F46E5', string $bg = '#f8fafc', string $border = '#e2e8f0'): string
    {
        return '<div style="background:' . $bg . ';border:1px solid ' . $border . ';border-left:4px solid ' . $color . ';border-radius:6px;padding:20px 24px;margin:24px 0;">' . $content . '</div>';
    }

    private static function row(string $label, string $value): string
    {
        return '<tr><td style="padding:7px 0;color:#64748b;font-size:13px;font-weight:500;width:45%;vertical-align:top;">' . $label . '</td><td style="padding:7px 0;color:#1e293b;font-weight:700;font-size:14px;">' . $value . '</td></tr>';
    }

    private static function table(string ...$rows): string
    {
        return '<table style="width:100%;border-collapse:collapse;">' . implode('', $rows) . '</table>';
    }

    private static function btn(string $label, string $url, string $color = '#4F46E5'): string
    {
        return '<div style="text-align:center;margin:28px 0;"><a href="' . $url . '" style="display:inline-block;background:' . $color . ';color:#ffffff;text-decoration:none;padding:14px 34px;border-radius:8px;font-weight:700;font-size:15px;letter-spacing:0.3px;">' . $label . '</a></div>';
    }

    private static function sig(string $name, bool $pl = false): string
    {
        $regards = $pl ? 'Z poważaniem' : 'Kind regards';
        return '<hr style="border:none;border-top:1px solid #e5e7eb;margin:32px 0 20px;">'
            . '<p style="margin:0;font-size:14px;color:#374151;">' . $regards . ',<br><strong style="color:#1e293b;">' . $name . '</strong></p>';
    }

    public function run(): void
    {
        $T = fn (string $pl, string $en, string $pt = '') => ['pl' => $pl, 'en' => $en, 'pt' => $pt ?: $en];

        $templates = [

            // ── 1. Welcome / Project Kickoff ────────────────────────────────
            [
                'name'      => 'Powitanie – Start projektu',
                'slug'      => 'welcome_email',
                'subject'   => $T('Witamy! Twój projekt {{project_title}} ruszył – co dalej?', 'Welcome! Your project {{project_title}} is starting – next steps inside'),
                'variables' => ['client_name', 'project_title', 'manager_name', 'portal_url'],
                'is_active' => true,
                'body_html' => $T(
                    // PL
                    '<p>Szanowny/a <strong>{{client_name}}</strong>,</p>'
                    . '<p>Cieszymy się, że możemy razem zrealizować projekt <strong>{{project_title}}</strong>. To oficjalny start naszej współpracy!</p>'
                    . self::box(
                        '<p style="margin:0 0 12px;font-weight:700;color:#1e293b;font-size:14px;">SZCZEGÓŁY PROJEKTU</p>'
                        . self::table(
                            self::row('Projekt:', '{{project_title}}'),
                            self::row('Opiekun projektu:', '{{manager_name}}'),
                            self::row('Portal klienta:', '<a href="{{portal_url}}" style="color:#4F46E5;">{{portal_url}}</a>'),
                        )
                    )
                    . '<p>Przez <strong>portal klienta</strong> możesz na bieżąco śledzić postępy, przeglądać etapy i komunikować się z zespołem — 24/7, z dowolnego urządzenia.</p>'
                    . self::btn('Przejdź do portalu klienta', '{{portal_url}}')
                    . '<p>W razie pytań jesteśmy do dyspozycji pod adresem <a href="mailto:hello@noname.agency">hello@noname.agency</a>.</p>'
                    . self::sig('{{manager_name}}', true),

                    // EN
                    '<p>Dear <strong>{{client_name}}</strong>,</p>'
                    . '<p>We\'re thrilled to be working with you on <strong>{{project_title}}</strong>. This is the official start of our collaboration!</p>'
                    . self::box(
                        '<p style="margin:0 0 12px;font-weight:700;color:#1e293b;font-size:14px;">PROJECT DETAILS</p>'
                        . self::table(
                            self::row('Project:', '{{project_title}}'),
                            self::row('Project Manager:', '{{manager_name}}'),
                            self::row('Client Portal:', '<a href="{{portal_url}}" style="color:#4F46E5;">{{portal_url}}</a>'),
                        )
                    )
                    . '<p>Via your <strong>client portal</strong> you can track progress, review milestones and communicate with the team — 24/7, from any device.</p>'
                    . self::btn('Access Your Client Portal', '{{portal_url}}')
                    . '<p>If you have any questions, reach us at <a href="mailto:hello@noname.agency">hello@noname.agency</a>.</p>'
                    . self::sig('{{manager_name}}')
                ),
                'body_text' => $T(
                    "Szanowny/a {{client_name}},\n\nProjekt {{project_title}} ruszył!\n\nOpiekun: {{manager_name}}\nPortal: {{portal_url}}\n\nZ poważaniem,\n{{manager_name}}",
                    "Dear {{client_name}},\n\nProject {{project_title}} is starting!\n\nManager: {{manager_name}}\nPortal: {{portal_url}}\n\nKind regards,\n{{manager_name}}"
                ),
            ],

            // ── 2. Invoice Sent ──────────────────────────────────────────────
            [
                'name'      => 'Faktura – do opłacenia',
                'slug'      => 'invoice_sent',
                'subject'   => $T(
                    'Faktura {{invoice_number}} – termin płatności: {{due_date}}',
                    'Invoice {{invoice_number}} – payment due {{due_date}}'
                ),
                'variables' => ['client_name', 'invoice_number', 'invoice_total', 'due_date', 'invoice_url', 'payment_link'],
                'is_active' => true,
                'body_html' => $T(
                    // PL
                    '<p>Szanowny/a <strong>{{client_name}}</strong>,</p>'
                    . '<p>Przesyłamy fakturę do opłacenia. Prosimy o uregulowanie płatności w podanym terminie.</p>'
                    . self::box(
                        '<p style="margin:0 0 12px;font-weight:700;color:#1e293b;font-size:14px;">SZCZEGÓŁY FAKTURY</p>'
                        . self::table(
                            self::row('Numer faktury:', '<strong>{{invoice_number}}</strong>'),
                            self::row('Kwota do zapłaty:', '<strong style="font-size:16px;color:#1e293b;">{{invoice_total}}</strong>'),
                            self::row('Termin płatności:', '<strong style="color:#ef4444;">{{due_date}}</strong>'),
                        ),
                        '#10B981', '#f0fdf4', '#bbf7d0'
                    )
                    . self::btn('Zapłać online teraz', '{{payment_link}}', '#10B981')
                    . '<p style="font-size:13px;color:#64748b;">Lub dokonaj przelewu bankowego:<br>'
                    . '<strong>Nazwa konta:</strong> NoName Agency Ltd &nbsp;|&nbsp; <strong>Sort Code:</strong> 04-00-03 &nbsp;|&nbsp; <strong>Numer konta:</strong> 12345678 &nbsp;|&nbsp; <strong>Tytułem:</strong> {{invoice_number}}</p>'
                    . '<p>W razie pytań dotyczących faktury skontaktuj się z nami: <a href="mailto:hello@noname.agency">hello@noname.agency</a></p>'
                    . self::sig('Dział Finansów', true),

                    // EN
                    '<p>Dear <strong>{{client_name}}</strong>,</p>'
                    . '<p>Please find your invoice below. Kindly arrange payment by the due date.</p>'
                    . self::box(
                        '<p style="margin:0 0 12px;font-weight:700;color:#1e293b;font-size:14px;">INVOICE DETAILS</p>'
                        . self::table(
                            self::row('Invoice Number:', '<strong>{{invoice_number}}</strong>'),
                            self::row('Amount Due:', '<strong style="font-size:16px;color:#1e293b;">{{invoice_total}}</strong>'),
                            self::row('Payment Due:', '<strong style="color:#ef4444;">{{due_date}}</strong>'),
                        ),
                        '#10B981', '#f0fdf4', '#bbf7d0'
                    )
                    . self::btn('Pay Online Now', '{{payment_link}}', '#10B981')
                    . '<p style="font-size:13px;color:#64748b;">Or pay by bank transfer:<br>'
                    . '<strong>Account:</strong> NoName Agency Ltd &nbsp;|&nbsp; <strong>Sort:</strong> 04-00-03 &nbsp;|&nbsp; <strong>Acc:</strong> 12345678 &nbsp;|&nbsp; <strong>Ref:</strong> {{invoice_number}}</p>'
                    . '<p>Questions? Contact us at <a href="mailto:hello@noname.agency">hello@noname.agency</a></p>'
                    . self::sig('Accounts Team')
                ),
                'body_text' => $T(
                    "Szanowny/a {{client_name}},\n\nFaktura {{invoice_number}} | Kwota: {{invoice_total}} | Termin: {{due_date}}\n\nPłatność online: {{payment_link}}\n\nZ poważaniem,\nDział Finansów",
                    "Dear {{client_name}},\n\nInvoice {{invoice_number}} | Amount: {{invoice_total}} | Due: {{due_date}}\n\nPay online: {{payment_link}}\n\nKind regards,\nAccounts Team"
                ),
            ],

            // ── 3. Invoice Overdue ───────────────────────────────────────────
            [
                'name'      => 'Faktura przeterminowana – przypomnienie',
                'slug'      => 'invoice_overdue',
                'subject'   => $T(
                    'PRZYPOMNIENIE: Faktura {{invoice_number}} jest przeterminowana o {{days_overdue}} dni',
                    'REMINDER: Invoice {{invoice_number}} is {{days_overdue}} days overdue – action required'
                ),
                'variables' => ['client_name', 'invoice_number', 'invoice_total', 'due_date', 'days_overdue', 'payment_link'],
                'is_active' => true,
                'body_html' => $T(
                    // PL
                    '<p>Szanowny/a <strong>{{client_name}}</strong>,</p>'
                    . '<p>Prosimy o pilne uregulowanie zaległej płatności. Poniżej szczegóły przeterminowanej faktury.</p>'
                    . self::box(
                        '<p style="margin:0 0 12px;font-weight:700;color:#b91c1c;font-size:14px;">⚠ FAKTURA PRZETERMINOWANA</p>'
                        . self::table(
                            self::row('Numer faktury:', '<strong>{{invoice_number}}</strong>'),
                            self::row('Kwota do zapłaty:', '<strong style="font-size:16px;color:#b91c1c;">{{invoice_total}}</strong>'),
                            self::row('Termin płatności był:', '{{due_date}}'),
                            self::row('Dni po terminie:', '<strong style="color:#b91c1c;">{{days_overdue}} dni</strong>'),
                        ),
                        '#ef4444', '#fff1f2', '#fecdd3'
                    )
                    . self::btn('Ureguluj płatność teraz', '{{payment_link}}', '#ef4444')
                    . '<p>Jeśli płatność została już wykonana, prosimy o zignorowanie tej wiadomości i przepraszamy za niedogodności.</p>'
                    . '<p>W przypadku trudności z płatnością prosimy o <strong>pilny kontakt</strong>: <a href="mailto:hello@noname.agency">hello@noname.agency</a></p>'
                    . self::sig('Dział Finansów', true),

                    // EN
                    '<p>Dear <strong>{{client_name}}</strong>,</p>'
                    . '<p>This is an urgent reminder regarding an outstanding payment. Please see the overdue invoice details below.</p>'
                    . self::box(
                        '<p style="margin:0 0 12px;font-weight:700;color:#b91c1c;font-size:14px;">⚠ OVERDUE INVOICE</p>'
                        . self::table(
                            self::row('Invoice Number:', '<strong>{{invoice_number}}</strong>'),
                            self::row('Amount Due:', '<strong style="font-size:16px;color:#b91c1c;">{{invoice_total}}</strong>'),
                            self::row('Original Due Date:', '{{due_date}}'),
                            self::row('Days Overdue:', '<strong style="color:#b91c1c;">{{days_overdue}} days</strong>'),
                        ),
                        '#ef4444', '#fff1f2', '#fecdd3'
                    )
                    . self::btn('Pay Now', '{{payment_link}}', '#ef4444')
                    . '<p>If you have already made this payment, please disregard this message — we apologise for the inconvenience.</p>'
                    . '<p>If you are experiencing difficulties, please contact us <strong>urgently</strong>: <a href="mailto:hello@noname.agency">hello@noname.agency</a></p>'
                    . self::sig('Accounts Team')
                ),
                'body_text' => $T(
                    "Szanowny/a {{client_name}},\n\nFaktura {{invoice_number}} ({{invoice_total}}) jest przeterminowana o {{days_overdue}} dni.\nTermin był: {{due_date}}\n\nZapłać teraz: {{payment_link}}\n\nZ poważaniem,\nDział Finansów",
                    "Dear {{client_name}},\n\nInvoice {{invoice_number}} ({{invoice_total}}) is {{days_overdue}} days overdue.\nDue date was: {{due_date}}\n\nPay now: {{payment_link}}\n\nKind regards,\nAccounts Team"
                ),
            ],

            // ── 4. Quote / Proposal Sent ─────────────────────────────────────
            [
                'name'      => 'Oferta / Wycena',
                'slug'      => 'quote_sent',
                'subject'   => $T(
                    'Twoja wycena od NoName Agency – Nr {{quote_number}}',
                    'Your quote from NoName Agency – Ref {{quote_number}}'
                ),
                'variables' => ['client_name', 'quote_number', 'quote_total', 'valid_until', 'quote_url', 'manager_name'],
                'is_active' => true,
                'body_html' => $T(
                    // PL
                    '<p>Szanowny/a <strong>{{client_name}}</strong>,</p>'
                    . '<p>Dziękujemy za zapytanie. W załączeniu przesyłamy przygotowaną dla Ciebie spersonalizowaną wycenę.</p>'
                    . self::box(
                        '<p style="margin:0 0 12px;font-weight:700;color:#1e293b;font-size:14px;">PODSUMOWANIE WYCENY</p>'
                        . self::table(
                            self::row('Numer wyceny:', '<strong>{{quote_number}}</strong>'),
                            self::row('Łączna kwota brutto:', '<strong style="font-size:16px;color:#1e293b;">{{quote_total}}</strong>'),
                            self::row('Ważna do:', '<strong>{{valid_until}}</strong>'),
                            self::row('Opiekun:', '{{manager_name}}'),
                        )
                    )
                    . self::btn('Przejrzyj wycenę online', '{{quote_url}}')
                    . '<p>Chętnie umówimy się na krótką rozmowę telefoniczną, aby omówić szczegóły — daj nam znać kiedy Ci odpowiada.</p>'
                    . '<p>Czekamy na Twoją odpowiedź!</p>'
                    . self::sig('{{manager_name}}', true),

                    // EN
                    '<p>Dear <strong>{{client_name}}</strong>,</p>'
                    . '<p>Thank you for your enquiry. Please find your personalised quote attached below.</p>'
                    . self::box(
                        '<p style="margin:0 0 12px;font-weight:700;color:#1e293b;font-size:14px;">QUOTE SUMMARY</p>'
                        . self::table(
                            self::row('Quote Reference:', '<strong>{{quote_number}}</strong>'),
                            self::row('Total (inc. VAT):', '<strong style="font-size:16px;color:#1e293b;">{{quote_total}}</strong>'),
                            self::row('Valid Until:', '<strong>{{valid_until}}</strong>'),
                            self::row('Your Manager:', '{{manager_name}}'),
                        )
                    )
                    . self::btn('View Your Quote', '{{quote_url}}')
                    . '<p>I\'d love to arrange a quick call to walk you through everything — just let me know when suits you.</p>'
                    . '<p>I look forward to hearing from you!</p>'
                    . self::sig('{{manager_name}}')
                ),
                'body_text' => $T(
                    "Szanowny/a {{client_name}},\n\nWycena {{quote_number}} | Kwota: {{quote_total}} | Ważna do: {{valid_until}}\n\nZobacz wycenę: {{quote_url}}\n\nZ poważaniem,\n{{manager_name}}",
                    "Dear {{client_name}},\n\nQuote {{quote_number}} | Total: {{quote_total}} | Valid until: {{valid_until}}\n\nView quote: {{quote_url}}\n\nKind regards,\n{{manager_name}}"
                ),
            ],

            // ── 5. Quote Accepted – Kickoff ──────────────────────────────────
            [
                'name'      => 'Umowa podpisana – Kickoff',
                'slug'      => 'quote_accepted',
                'subject'   => $T(
                    'Zaczynamy! 🎉 Projekt {{project_title}} – kolejne kroki',
                    'We\'re go! 🎉 Project {{project_title}} – what happens next'
                ),
                'variables' => ['client_name', 'quote_number', 'project_title', 'manager_name', 'deposit_amount', 'invoice_url'],
                'is_active' => true,
                'body_html' => $T(
                    // PL
                    '<p>Szanowny/a <strong>{{client_name}}</strong>,</p>'
                    . '<p>Świetna wiadomość — wycena <strong>{{quote_number}}</strong> została zatwierdzona i jesteśmy gotowi do startu projektu <strong>{{project_title}}</strong>! 🎉</p>'
                    . self::box(
                        '<p style="margin:0 0 12px;font-weight:700;color:#065f46;font-size:14px;">✅ PROJEKT ZATWIERDZONY</p>'
                        . '<p style="margin:0;color:#374151;">Projekt: <strong>{{project_title}}</strong><br>Opiekun: <strong>{{manager_name}}</strong><br>Zaliczka: <strong>{{deposit_amount}}</strong></p>',
                        '#10B981', '#f0fdf4', '#bbf7d0'
                    )
                    . '<p><strong>Kolejne kroki:</strong></p>'
                    . '<ol style="padding-left:20px;margin:0 0 20px;">'
                    . '<li style="margin-bottom:10px;">Opłać <strong>fakturę zaliczkową ({{deposit_amount}})</strong> — <a href="{{invoice_url}}" style="color:#4F46E5;">kliknij tutaj</a></li>'
                    . '<li style="margin-bottom:10px;">W ciągu 48h od płatności skontaktujemy się z propozycją terminu spotkania kickoff</li>'
                    . '<li style="margin-bottom:10px;">Otrzymasz dostęp do panelu klienta do śledzenia postępów</li>'
                    . '</ol>'
                    . self::btn('Opłać fakturę zaliczkową', '{{invoice_url}}')
                    . '<p>Bardzo cieszymy się na tę współpracę!</p>'
                    . self::sig('{{manager_name}}', true),

                    // EN
                    '<p>Dear <strong>{{client_name}}</strong>,</p>'
                    . '<p>Fantastic news — quote <strong>{{quote_number}}</strong> has been confirmed and we\'re ready to kick off <strong>{{project_title}}</strong>! 🎉</p>'
                    . self::box(
                        '<p style="margin:0 0 12px;font-weight:700;color:#065f46;font-size:14px;">✅ PROJECT CONFIRMED</p>'
                        . '<p style="margin:0;color:#374151;">Project: <strong>{{project_title}}</strong><br>Manager: <strong>{{manager_name}}</strong><br>Deposit: <strong>{{deposit_amount}}</strong></p>',
                        '#10B981', '#f0fdf4', '#bbf7d0'
                    )
                    . '<p><strong>Next Steps:</strong></p>'
                    . '<ol style="padding-left:20px;margin:0 0 20px;">'
                    . '<li style="margin-bottom:10px;">Pay your <strong>deposit invoice ({{deposit_amount}})</strong> — <a href="{{invoice_url}}" style="color:#4F46E5;">click here</a></li>'
                    . '<li style="margin-bottom:10px;">Within 48 hours of payment we\'ll schedule your kick-off call</li>'
                    . '<li style="margin-bottom:10px;">You\'ll receive access to your client portal to track progress</li>'
                    . '</ol>'
                    . self::btn('Pay Deposit Invoice', '{{invoice_url}}')
                    . '<p>We\'re genuinely excited to work with you!</p>'
                    . self::sig('{{manager_name}}')
                ),
                'body_text' => $T(
                    "Szanowny/a {{client_name}},\n\nProjekt {{project_title}} zatwierdzony!\n\n1. Opłać zaliczkę {{deposit_amount}}: {{invoice_url}}\n2. Kickoff w ciągu 48h od płatności\n3. Dostęp do portalu klienta\n\nZ poważaniem,\n{{manager_name}}",
                    "Dear {{client_name}},\n\nProject {{project_title}} confirmed!\n\n1. Pay deposit {{deposit_amount}}: {{invoice_url}}\n2. Kick-off call within 48h of payment\n3. Client portal access\n\nKind regards,\n{{manager_name}}"
                ),
            ],

            // ── 6. Project Phase Complete ────────────────────────────────────
            [
                'name'      => 'Etap projektu ukończony',
                'slug'      => 'project_phase_complete',
                'subject'   => $T(
                    'Aktualizacja: {{phase_name}} ukończony ✓ – {{project_title}}',
                    'Update: {{phase_name}} complete ✓ – {{project_title}}'
                ),
                'variables' => ['client_name', 'project_title', 'phase_name', 'next_phase', 'portal_url', 'manager_name'],
                'is_active' => true,
                'body_html' => $T(
                    // PL
                    '<p>Szanowny/a <strong>{{client_name}}</strong>,</p>'
                    . '<p>Mamy dla Ciebie aktualności na temat projektu <strong>{{project_title}}</strong>!</p>'
                    . self::box(
                        '<p style="margin:0 0 12px;font-weight:700;color:#065f46;font-size:14px;">✅ ETAP UKOŃCZONY</p>'
                        . self::table(
                            self::row('Projekt:', '{{project_title}}'),
                            self::row('Ukończony etap:', '<strong style="color:#10B981;">✓ {{phase_name}}</strong>'),
                            self::row('Następny etap:', '<strong>▶ {{next_phase}}</strong>'),
                        ),
                        '#10B981', '#f0fdf4', '#bbf7d0'
                    )
                    . '<p>Zapraszamy do portalu klienta, żeby przejrzeć wyniki i przekazać ewentualne uwagi — Twój feedback jest dla nas bardzo ważny.</p>'
                    . self::btn('Zobacz postęp w portalu', '{{portal_url}}')
                    . self::sig('{{manager_name}}', true),

                    // EN
                    '<p>Dear <strong>{{client_name}}</strong>,</p>'
                    . '<p>We have a progress update on your project <strong>{{project_title}}</strong>!</p>'
                    . self::box(
                        '<p style="margin:0 0 12px;font-weight:700;color:#065f46;font-size:14px;">✅ PHASE COMPLETE</p>'
                        . self::table(
                            self::row('Project:', '{{project_title}}'),
                            self::row('Completed Phase:', '<strong style="color:#10B981;">✓ {{phase_name}}</strong>'),
                            self::row('Next Phase:', '<strong>▶ {{next_phase}}</strong>'),
                        ),
                        '#10B981', '#f0fdf4', '#bbf7d0'
                    )
                    . '<p>Please visit your client portal to review the deliverables and share any feedback — your input is very important to us.</p>'
                    . self::btn('View Progress in Portal', '{{portal_url}}')
                    . self::sig('{{manager_name}}')
                ),
                'body_text' => $T(
                    "Szanowny/a {{client_name}},\n\nEtap {{phase_name}} ukończony!\nNastępny: {{next_phase}}\n\nPortal: {{portal_url}}\n\nZ poważaniem,\n{{manager_name}}",
                    "Dear {{client_name}},\n\n{{phase_name}} is complete!\nNext: {{next_phase}}\n\nPortal: {{portal_url}}\n\nKind regards,\n{{manager_name}}"
                ),
            ],

            // ── 7. Project Launched / Delivered ─────────────────────────────
            [
                'name'      => 'Projekt ukończony – przekazanie',
                'slug'      => 'project_launched',
                'subject'   => $T(
                    '🚀 {{project_title}} jest LIVE! Gratulacje i co dalej',
                    '🚀 {{project_title}} is LIVE! Congratulations & what\'s next'
                ),
                'variables' => ['client_name', 'project_title', 'website_url', 'manager_name', 'support_email'],
                'is_active' => true,
                'body_html' => $T(
                    // PL
                    '<p>Szanowny/a <strong>{{client_name}}</strong>,</p>'
                    . '<p>Z ogromną radością informujemy, że projekt <strong>{{project_title}}</strong> jest oficjalnie ukończony i dostępny online! 🎉</p>'
                    . self::box(
                        '<p style="margin:0 0 12px;font-weight:700;color:#1e40af;font-size:14px;">🚀 PROJEKT LIVE</p>'
                        . '<p style="margin:0;"><strong>Adres strony:</strong><br><a href="{{website_url}}" style="color:#4F46E5;font-size:15px;">{{website_url}}</a></p>',
                        '#4F46E5', '#eff6ff', '#bfdbfe'
                    )
                    . '<p><strong>Paczka przekazania zawiera:</strong></p>'
                    . '<ul style="padding-left:20px;margin:0 0 20px;">'
                    . '<li style="margin-bottom:8px;">Dane dostępowe do CMS / panelu administracyjnego</li>'
                    . '<li style="margin-bottom:8px;">Dostęp do panelu hostingowego</li>'
                    . '<li style="margin-bottom:8px;">Dostęp do Google Analytics / Search Console</li>'
                    . '<li style="margin-bottom:8px;">Nagranie wideo ze szkolenia</li>'
                    . '<li style="margin-bottom:8px;">PDF z dokumentacją użytkownika</li>'
                    . '</ul>'
                    . self::box(
                        '<p style="margin:0;font-size:13px;color:#374151;"><strong>Gwarancja i wsparcie:</strong> 3 miesiące bezpłatnych poprawek. W przypadku pytań: <a href="mailto:{{support_email}}" style="color:#4F46E5;">{{support_email}}</a></p>',
                        '#6366f1', '#f5f3ff', '#ddd6fe'
                    )
                    . '<p>Jeśli projekt spełnił Twoje oczekiwania, bylibyśmy bardzo wdzięczni za opinię w Google — to ogromna pomoc dla naszego zespołu.</p>'
                    . self::sig('{{manager_name}}', true),

                    // EN
                    '<p>Dear <strong>{{client_name}}</strong>,</p>'
                    . '<p>We are thrilled to announce that <strong>{{project_title}}</strong> is officially live and online! 🎉</p>'
                    . self::box(
                        '<p style="margin:0 0 12px;font-weight:700;color:#1e40af;font-size:14px;">🚀 PROJECT IS LIVE</p>'
                        . '<p style="margin:0;"><strong>Website address:</strong><br><a href="{{website_url}}" style="color:#4F46E5;font-size:15px;">{{website_url}}</a></p>',
                        '#4F46E5', '#eff6ff', '#bfdbfe'
                    )
                    . '<p><strong>Your handover pack includes:</strong></p>'
                    . '<ul style="padding-left:20px;margin:0 0 20px;">'
                    . '<li style="margin-bottom:8px;">CMS / admin panel login credentials</li>'
                    . '<li style="margin-bottom:8px;">Hosting control panel access</li>'
                    . '<li style="margin-bottom:8px;">Google Analytics / Search Console access</li>'
                    . '<li style="margin-bottom:8px;">Training video recording</li>'
                    . '<li style="margin-bottom:8px;">User guide PDF</li>'
                    . '</ul>'
                    . self::box(
                        '<p style="margin:0;font-size:13px;color:#374151;"><strong>Warranty & support:</strong> 3 months of free revisions. For support: <a href="mailto:{{support_email}}" style="color:#4F46E5;">{{support_email}}</a></p>',
                        '#6366f1', '#f5f3ff', '#ddd6fe'
                    )
                    . '<p>If you are happy with the project, we\'d be so grateful for a Google review — it means the world to our team.</p>'
                    . self::sig('{{manager_name}}')
                ),
                'body_text' => $T(
                    "Szanowny/a {{client_name}},\n\nProjekt {{project_title}} jest LIVE!\n\n{{website_url}}\n\nDokumentacja i dostępy wysłane w osobnej wiadomości.\nWsparcie: {{support_email}}\n\nZ poważaniem,\n{{manager_name}}",
                    "Dear {{client_name}},\n\n{{project_title}} is LIVE!\n\n{{website_url}}\n\nHandover pack sent separately.\nSupport: {{support_email}}\n\nKind regards,\n{{manager_name}}"
                ),
            ],

            // ── 8. Portal Invite ─────────────────────────────────────────────
            [
                'name'      => 'Portal klienta – zaproszenie',
                'slug'      => 'portal_invite',
                'subject'   => $T(
                    'Twój dostęp do portalu klienta – {{company_name}}',
                    'Your client portal access – {{company_name}}'
                ),
                'variables' => ['client_name', 'login_email', 'plain_password', 'portal_url', 'company_name'],
                'is_active' => true,
                'body_html' => $T(
                    // PL
                    '<p>Szanowny/a <strong>{{client_name}}</strong>,</p>'
                    . '<p>Przygotowaliśmy dla Ciebie dostęp do <strong>portalu klienta {{company_name}}</strong>. Za jego pośrednictwem możesz na bieżąco śledzić postępy swoich projektów, sprawdzać faktury i oferty — 24&nbsp;h na dobę, z każdego urządzenia.</p>'
                    . self::box(
                        '<p style="margin:0 0 14px;font-weight:700;font-size:14px;color:#1e293b;text-transform:uppercase;letter-spacing:.5px;">Dane logowania</p>'
                        . self::table(
                            self::row('Adres e-mail:', '{{login_email}}'),
                            self::row('Hasło (tymczasowe):', '<code style="background:#f1f5f9;border:1px solid #e2e8f0;border-radius:5px;padding:4px 10px;font-size:14px;color:#1e293b;letter-spacing:1px;">{{plain_password}}</code>'),
                        )
                    )
                    . self::btn('Zaloguj się do portalu', '{{portal_url}}')
                    . self::box(
                        '<p style="margin:0;font-size:13px;color:#92400e;"><strong>⚠ Ważne:</strong> Ze względów bezpieczeństwa zalecamy zmianę hasła po pierwszym logowaniu. Hasło tymczasowe pozostanie aktywne do czasu jego zmiany.</p>',
                        '#f59e0b', '#fffbeb', '#fde68a'
                    )
                    . self::sig('Zespół {{company_name}}', true),

                    // EN
                    '<p>Dear <strong>{{client_name}}</strong>,</p>'
                    . '<p>We have set up your <strong>{{company_name}} client portal</strong> access. Through it, you can track your project progress, review invoices and proposals — 24/7, from any device.</p>'
                    . self::box(
                        '<p style="margin:0 0 14px;font-weight:700;font-size:14px;color:#1e293b;text-transform:uppercase;letter-spacing:.5px;">Login Details</p>'
                        . self::table(
                            self::row('Email address:', '{{login_email}}'),
                            self::row('Password (temporary):', '<code style="background:#f1f5f9;border:1px solid #e2e8f0;border-radius:5px;padding:4px 10px;font-size:14px;color:#1e293b;letter-spacing:1px;">{{plain_password}}</code>'),
                        )
                    )
                    . self::btn('Log In to Your Portal', '{{portal_url}}')
                    . self::box(
                        '<p style="margin:0;font-size:13px;color:#92400e;"><strong>⚠ Important:</strong> For security, we recommend changing your password after your first login. The temporary password will remain active until changed.</p>',
                        '#f59e0b', '#fffbeb', '#fde68a'
                    )
                    . self::sig('{{company_name}} Team')
                ),
                'body_text' => $T(
                    "Szanowny/a {{client_name}},\n\nDostęp do portalu klienta {{company_name}} jest gotowy.\n\nE-mail: {{login_email}}\nHasło: {{plain_password}}\nPortal: {{portal_url}}\n\nProsimy o zmianę hasła po pierwszym logowaniu.\n\nZ poważaniem,\nZespół {{company_name}}",
                    "Dear {{client_name}},\n\nYour {{company_name}} client portal access is ready.\n\nEmail: {{login_email}}\nPassword: {{plain_password}}\nPortal: {{portal_url}}\n\nPlease change your password after first login.\n\nKind regards,\n{{company_name}} Team"
                ),
            ],

            // ── Service CTA – admin notification ─────────────────────────────
            [
                'name'      => 'Service CTA Recived - Email Notification to Admin',
                'slug'      => 'service_cta_admin_mail_notice',
                'subject'   => [
                    'en' => 'dowy lead wpadl en',
                    'pl' => 'dowy lead wpadl pl',
                    'pt' => 'dowy lead wpadl pt',
                ],
                'body_html' => [
                    'en' => '<p>siema nowy lead wpadł en</p>',
                    'pl' => '<p>siema nowy lead wpadł pl</p>',
                    'pt' => '<p>siema nowy lead wpadł pt</p>',
                ],
                'body_text' => [
                    'en' => 'nowy lead wpadł en',
                    'pl' => null,
                    'pt' => 'siema nowy lead wpadł pt',
                ],
                'variables'  => ['client_name', 'lead_name'],
                'is_active'  => true,
            ],

        ];

        foreach ($templates as $data) {
            EmailTemplate::updateOrCreate(['slug' => $data['slug']], $data);
        }
    }
}
