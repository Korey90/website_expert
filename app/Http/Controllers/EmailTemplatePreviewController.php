<?php

namespace App\Http\Controllers;

use App\Models\EmailTemplate;
use App\Services\Currency\CurrencyResolver;
use App\Services\Currency\MoneyFormatter;
use Illuminate\View\View;

class EmailTemplatePreviewController extends Controller
{
    public function show(EmailTemplate $template, string $locale = 'en'): View
    {
        $locale = in_array($locale, ['en', 'pl', 'pt']) ? $locale : 'en';
        $currencyResolver = app(CurrencyResolver::class);
        $currency = $currencyResolver->resolveForLocale($locale) ?? $currencyResolver->defaultCurrency();
        $money = app(MoneyFormatter::class);

        $sample = [
            '{{client_name}}' => 'John Smith',
            '{{company_name}}' => 'NoName Agency',
            '{{project_title}}' => 'Corporate Website Redesign',
            '{{project_name}}' => 'Corporate Website Redesign',
            '{{manager_name}}' => 'Alex Johnson',
            '{{portal_url}}' => 'https://example.com/portal',
            '{{invoice_number}}' => 'INV-2026-0042',
            '{{invoice_total}}' => $money->format(2400, $currency),
            '{{due_date}}' => '31 March 2026',
            '{{payment_link}}' => '#payment',
            '{{invoice_url}}' => '#invoice',
            '{{days_overdue}}' => '7',
            '{{quote_number}}' => 'QUO-2026-0018',
            '{{quote_total}}' => $money->format(3600, $currency),
            '{{valid_until}}' => '15 April 2026',
            '{{quote_url}}' => '#quote',
            '{{deposit_amount}}' => $money->format(720, $currency),
            '{{phase_name}}' => 'Design & Prototyping',
            '{{next_phase}}' => 'Frontend Development',
            '{{website_url}}' => 'https://example.com',
            '{{support_email}}' => 'support@noname.agency',
            '{{assigned_name}}' => 'Alex Johnson',
            '{{stage_name}}' => 'Proposal Sent',
            '{{lead_title}}' => 'Website Redesign Project',
            '{{today}}' => now()->format('d M Y'),
        ];

        $bodyHtml = $template->body_html[$locale]
            ?? $template->body_html['en']
            ?? '<p>No content for this locale.</p>';

        $subject = $template->subject[$locale]
            ?? $template->subject['en']
            ?? $template->name;

        $bodyHtml = str_replace(array_keys($sample), array_values($sample), $bodyHtml);
        $subject = str_replace(array_keys($sample), array_values($sample), $subject);

        return view('emails.client-email', [
            'emailBody' => $bodyHtml,
            'emailSubject' => $subject,
        ]);
    }
}
