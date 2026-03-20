<?php

namespace Database\Seeders;

use App\Models\EmailTemplate;
use Illuminate\Database\Seeder;

class EmailTemplateSeeder extends Seeder
{
    public function run(): void
    {
        $templates = [
            [
                'name'      => 'Welcome Email',
                'slug'      => 'welcome_email',
                'subject'   => [
                    'en' => 'Welcome to WebsiteExpert – Your Project Journey Starts Here',
                    'pl' => 'Witaj w WebsiteExpert – Twój projekt się zaczyna!',
                    'pt' => 'Bem-vindo à WebsiteExpert – A sua jornada começa agora',
                ],
                'variables' => ['client_name', 'project_title', 'manager_name', 'portal_url'],
                'is_active' => true,
                'body_html' => [
                    'en' => <<<'HTML'
<p>Dear {{client_name}},</p>

<p>Thank you for choosing <strong>WebsiteExpert</strong>. We're thrilled to be working with you on <strong>{{project_title}}</strong>.</p>

<p>Your dedicated project manager is <strong>{{manager_name}}</strong>, who will be your primary point of contact throughout the project.</p>

<p>You can track your project progress, review milestones, and send us messages via your secure client portal:</p>

<p><a href="{{portal_url}}" style="background:#4F46E5;color:#fff;padding:10px 20px;border-radius:6px;text-decoration:none;display:inline-block;">Access Your Portal</a></p>

<p>If you have any questions, please don't hesitate to reach out to us at <a href="mailto:hello@websiteexpert.co.uk">hello@websiteexpert.co.uk</a>.</p>

<p>Warm regards,<br>
<strong>The WebsiteExpert Team</strong></p>
HTML,
                    'pl' => <<<'HTML'
<p>Szanowny/a {{client_name}},</p>

<p>Dziękujemy za wybór <strong>WebsiteExpert</strong>. Cieszymy się, że będziemy współpracować przy projekcie <strong>{{project_title}}</strong>.</p>

<p>Twoim dedykowanym menedżerem projektu jest <strong>{{manager_name}}</strong>, który będzie Twoim głównym punktem kontaktu przez cały czas trwania projektu.</p>

<p>Możesz śledzić postęp projektu, przeglądać kamienie milowe i wysyłać do nas wiadomości za pośrednictwem swojego bezpiecznego portalu klienta:</p>

<p><a href="{{portal_url}}" style="background:#4F46E5;color:#fff;padding:10px 20px;border-radius:6px;text-decoration:none;display:inline-block;">Przejdź do portalu</a></p>

<p>Jeśli masz jakiekolwiek pytania, skontaktuj się z nami pod adresem <a href="mailto:hello@websiteexpert.co.uk">hello@websiteexpert.co.uk</a>.</p>

<p>Z poważaniem,<br>
<strong>Zespół WebsiteExpert</strong></p>
HTML,
                    'pt' => <<<'HTML'
<p>Caro/a {{client_name}},</p>

<p>Obrigado por escolher a <strong>WebsiteExpert</strong>. Estamos entusiasmados por trabalhar consigo no projeto <strong>{{project_title}}</strong>.</p>

<p>O seu gestor de projeto dedicado é <strong>{{manager_name}}</strong>, que será o seu principal ponto de contacto ao longo do projeto.</p>

<p>Pode acompanhar o progresso do projeto, rever marcos e enviar-nos mensagens através do seu portal de cliente seguro:</p>

<p><a href="{{portal_url}}" style="background:#4F46E5;color:#fff;padding:10px 20px;border-radius:6px;text-decoration:none;display:inline-block;">Aceder ao Portal</a></p>

<p>Se tiver alguma dúvida, não hesite em contactar-nos em <a href="mailto:hello@websiteexpert.co.uk">hello@websiteexpert.co.uk</a>.</p>

<p>Com os melhores cumprimentos,<br>
<strong>A Equipa WebsiteExpert</strong></p>
HTML,
                ],
                'body_text' => [
                    'en' => "Dear {{client_name}},\n\nThank you for choosing WebsiteExpert. We're thrilled to be working with you on {{project_title}}.\n\nYour dedicated project manager is {{manager_name}}.\n\nTrack your project: {{portal_url}}\n\nWarm regards,\nThe WebsiteExpert Team",
                    'pl' => "Szanowny/a {{client_name}},\n\nDziękujemy za wybór WebsiteExpert. Cieszymy się ze współpracy przy projekcie {{project_title}}.\n\nTwój menedżer projektu: {{manager_name}}.\n\nPortal klienta: {{portal_url}}\n\nZ poważaniem,\nZespół WebsiteExpert",
                    'pt' => "Caro/a {{client_name}},\n\nObrigado por escolher a WebsiteExpert. Estamos entusiasmados por trabalhar consigo no projeto {{project_title}}.\n\nO seu gestor de projeto: {{manager_name}}.\n\nPortal do cliente: {{portal_url}}\n\nCom os melhores cumprimentos,\nA Equipa WebsiteExpert",
                ],
            ],
            [
                'name'      => 'Invoice Sent',
                'slug'      => 'invoice_sent',
                'subject'   => [
                    'en' => 'Invoice {{invoice_number}} from WebsiteExpert – Payment Due {{due_date}}',
                    'pl' => 'Faktura {{invoice_number}} od WebsiteExpert – Termin płatności: {{due_date}}',
                    'pt' => 'Fatura {{invoice_number}} da WebsiteExpert – Pagamento até {{due_date}}',
                ],
                'variables' => ['client_name', 'invoice_number', 'invoice_total', 'due_date', 'invoice_url', 'payment_link'],
                'is_active' => true,
                'body_html' => [
                    'en' => <<<'HTML'
<p>Dear {{client_name}},</p>

<p>Please find attached invoice <strong>{{invoice_number}}</strong> for the amount of <strong>{{invoice_total}}</strong> (inc. VAT).</p>

<p><strong>Payment is due by: {{due_date}}</strong></p>

<p>You can pay securely online using the button below:</p>

<p><a href="{{payment_link}}" style="background:#10B981;color:#fff;padding:10px 20px;border-radius:6px;text-decoration:none;display:inline-block;">Pay Now</a></p>

<p>Alternatively, you can make a BACS payment using the details below:</p>
<ul>
  <li><strong>Account Name:</strong> WebsiteExpert Ltd</li>
  <li><strong>Sort Code:</strong> 20-00-00</li>
  <li><strong>Account Number:</strong> 12345678</li>
  <li><strong>Reference:</strong> {{invoice_number}}</li>
</ul>

<p>If you have any queries regarding this invoice, please contact us at <a href="mailto:accounts@websiteexpert.co.uk">accounts@websiteexpert.co.uk</a>.</p>

<p>Kind regards,<br>
<strong>WebsiteExpert Accounts Team</strong></p>
HTML,
                    'pl' => <<<'HTML'
<p>Szanowny/a {{client_name}},</p>

<p>W załączeniu przesyłamy fakturę <strong>{{invoice_number}}</strong> na kwotę <strong>{{invoice_total}}</strong> (brutto).</p>

<p><strong>Termin płatności: {{due_date}}</strong></p>

<p>Możesz dokonać płatności online:</p>

<p><a href="{{payment_link}}" style="background:#10B981;color:#fff;padding:10px 20px;border-radius:6px;text-decoration:none;display:inline-block;">Zapłać teraz</a></p>

<p>W razie pytań dotyczących faktury prosimy o kontakt: <a href="mailto:accounts@websiteexpert.co.uk">accounts@websiteexpert.co.uk</a>.</p>

<p>Z poważaniem,<br>
<strong>Dział Księgowości WebsiteExpert</strong></p>
HTML,
                    'pt' => <<<'HTML'
<p>Caro/a {{client_name}},</p>

<p>Segue em anexo a fatura <strong>{{invoice_number}}</strong> no valor de <strong>{{invoice_total}}</strong> (IVA incluído).</p>

<p><strong>Data limite de pagamento: {{due_date}}</strong></p>

<p>Pode pagar de forma segura online:</p>

<p><a href="{{payment_link}}" style="background:#10B981;color:#fff;padding:10px 20px;border-radius:6px;text-decoration:none;display:inline-block;">Pagar Agora</a></p>

<p>Em alternativa, pode efetuar uma transferência bancária com os seguintes dados:</p>
<ul>
  <li><strong>Nome da Conta:</strong> WebsiteExpert Ltd</li>
  <li><strong>Código Swift/IBAN:</strong> (consultar fatura)</li>
  <li><strong>Referência:</strong> {{invoice_number}}</li>
</ul>

<p>Para qualquer esclarecimento, contacte-nos em <a href="mailto:accounts@websiteexpert.co.uk">accounts@websiteexpert.co.uk</a>.</p>

<p>Com os melhores cumprimentos,<br>
<strong>Departamento Financeiro WebsiteExpert</strong></p>
HTML,
                ],
                'body_text' => [
                    'en' => "Dear {{client_name}},\n\nPlease find attached invoice {{invoice_number}} for {{invoice_total}} (inc. VAT).\n\nPayment due: {{due_date}}\n\nPay online: {{payment_link}}\n\nBACS: WebsiteExpert Ltd | Sort: 20-00-00 | Acc: 12345678 | Ref: {{invoice_number}}\n\nKind regards,\nWebsiteExpert Accounts Team",
                    'pl' => "Szanowny/a {{client_name}},\n\nW załączeniu faktura {{invoice_number}} na kwotę {{invoice_total}} (brutto).\n\nTermin płatności: {{due_date}}\n\nPłatność online: {{payment_link}}\n\nZ poważaniem,\nDział Księgowości WebsiteExpert",
                    'pt' => "Caro/a {{client_name}},\n\nSegue em anexo a fatura {{invoice_number}} no valor de {{invoice_total}} (IVA inclu\u00eddo).\n\nData limite: {{due_date}}\n\nPagar online: {{payment_link}}\n\nCom os melhores cumprimentos,\nDepartamento Financeiro WebsiteExpert",
                ],
            ],
            [
                'name'      => 'Invoice Overdue Reminder',
                'slug'      => 'invoice_overdue',
                'subject'   => [
                    'en' => 'REMINDER: Invoice {{invoice_number}} is Overdue – Action Required',
                    'pl' => 'PRZYPOMNIENIE: Faktura {{invoice_number}} jest przeterminowana – wymagane działanie',
                    'pt' => 'LEMBRETE: Fatura {{invoice_number}} em atraso – ação necessária',
                ],
                'variables' => ['client_name', 'invoice_number', 'invoice_total', 'due_date', 'days_overdue', 'payment_link'],
                'is_active' => true,
                'body_html' => [
                    'en' => <<<'HTML'
<p>Dear {{client_name}},</p>

<p>This is a friendly reminder that invoice <strong>{{invoice_number}}</strong> for <strong>{{invoice_total}}</strong> was due on <strong>{{due_date}}</strong> and is now <strong>{{days_overdue}} days overdue</strong>.</p>

<p>Please arrange payment at your earliest convenience:</p>

<p><a href="{{payment_link}}" style="background:#EF4444;color:#fff;padding:10px 20px;border-radius:6px;text-decoration:none;display:inline-block;">Pay Now</a></p>

<p>If you have already made this payment, please disregard this email and accept our apologies for the inconvenience.</p>

<p>If you are experiencing difficulties making payment, please contact us immediately at <a href="mailto:accounts@websiteexpert.co.uk">accounts@websiteexpert.co.uk</a> so we can discuss payment arrangements.</p>

<p>Kind regards,<br>
<strong>WebsiteExpert Accounts Team</strong></p>
HTML,
                    'pl' => <<<'HTML'
<p>Szanowny/a {{client_name}},</p>

<p>Przypominamy, że faktura <strong>{{invoice_number}}</strong> na kwotę <strong>{{invoice_total}}</strong> była płatna do <strong>{{due_date}}</strong> i jest teraz przeterminowana o <strong>{{days_overdue}} dni</strong>.</p>

<p>Prosimy o jak najszybsze uregulowanie płatności:</p>

<p><a href="{{payment_link}}" style="background:#EF4444;color:#fff;padding:10px 20px;border-radius:6px;text-decoration:none;display:inline-block;">Zapłać teraz</a></p>

<p>Jeśli masz trudności z płatnością, skontaktuj się z nami natychmiast pod adresem <a href="mailto:accounts@websiteexpert.co.uk">accounts@websiteexpert.co.uk</a>.</p>

<p>Z poważaniem,<br>
<strong>Dział Księgowości WebsiteExpert</strong></p>
HTML,
                    'pt' => <<<'HTML'
<p>Caro/a {{client_name}},</p>

<p>Este é um aviso amigável de que a fatura <strong>{{invoice_number}}</strong> no valor de <strong>{{invoice_total}}</strong> tinha data limite em <strong>{{due_date}}</strong> e encontra-se atualmente com <strong>{{days_overdue}} dias de atraso</strong>.</p>

<p>Por favor regularize o pagamento o mais brevemente possível:</p>

<p><a href="{{payment_link}}" style="background:#EF4444;color:#fff;padding:10px 20px;border-radius:6px;text-decoration:none;display:inline-block;">Pagar Agora</a></p>

<p>Se já efetuou este pagamento, por favor ignore este email e pedimos desculpa pelo incidente.</p>

<p>Se tiver dificuldades em efetuar o pagamento, contacte-nos imediatamente em <a href="mailto:accounts@websiteexpert.co.uk">accounts@websiteexpert.co.uk</a>.</p>

<p>Com os melhores cumprimentos,<br>
<strong>Departamento Financeiro WebsiteExpert</strong></p>
HTML,
                ],
                'body_text' => [
                    'en' => "Dear {{client_name}},\n\nInvoice {{invoice_number}} for {{invoice_total}} was due on {{due_date}} and is now {{days_overdue}} days overdue.\n\nPay now: {{payment_link}}\n\nIf you have any questions, contact accounts@websiteexpert.co.uk\n\nKind regards,\nWebsiteExpert Accounts Team",
                    'pl' => "Szanowny/a {{client_name}},\n\nFaktura {{invoice_number}} na kwotę {{invoice_total}} była płatna do {{due_date}} i jest przeterminowana o {{days_overdue}} dni.\n\nZapłać teraz: {{payment_link}}\n\nZ poważaniem,\nDział Księgowości WebsiteExpert",
                    'pt' => "Caro/a {{client_name}},\n\nA fatura {{invoice_number}} no valor de {{invoice_total}} tinha data limite em {{due_date}} e encontra-se com {{days_overdue}} dias de atraso.\n\nPagar agora: {{payment_link}}\n\nCom os melhores cumprimentos,\nDepartamento Financeiro WebsiteExpert",
                ],
            ],
            [
                'name'      => 'Quote Sent',
                'slug'      => 'quote_sent',
                'subject'   => [
                    'en' => 'Your Quote from WebsiteExpert – Ref: {{quote_number}}',
                    'pl' => 'Twoja wycena od WebsiteExpert – Nr: {{quote_number}}',
                    'pt' => 'O seu orçamento da WebsiteExpert – Ref: {{quote_number}}',
                ],
                'variables' => ['client_name', 'quote_number', 'quote_total', 'valid_until', 'quote_url', 'manager_name'],
                'is_active' => true,
                'body_html' => [
                    'en' => <<<'HTML'
<p>Dear {{client_name}},</p>

<p>Thank you for your enquiry. Please find attached your personalised quote <strong>{{quote_number}}</strong> from WebsiteExpert.</p>

<p><strong>Quote Summary:</strong><br>
Total: <strong>{{quote_total}}</strong> (inc. VAT)<br>
Valid Until: <strong>{{valid_until}}</strong></p>

<p>You can view and accept your quote online:</p>

<p><a href="{{quote_url}}" style="background:#4F46E5;color:#fff;padding:10px 20px;border-radius:6px;text-decoration:none;display:inline-block;">View Quote</a></p>

<p>I'd love to jump on a quick call to walk you through everything — feel free to book a time that suits you at your convenience.</p>

<p>I look forward to hearing from you.</p>

<p>Best regards,<br>
<strong>{{manager_name}}</strong><br>
WebsiteExpert</p>
HTML,
                    'pl' => <<<'HTML'
<p>Szanowny/a {{client_name}},</p>

<p>Dziękujemy za Twoje zapytanie. W załączeniu przesyłamy spersonalizowaną wycenę <strong>{{quote_number}}</strong>.</p>

<p><strong>Podsumowanie wyceny:</strong><br>
Kwota: <strong>{{quote_total}}</strong> (brutto)<br>
Ważna do: <strong>{{valid_until}}</strong></p>

<p>Możesz przejrzeć i zaakceptować wycenę online:</p>

<p><a href="{{quote_url}}" style="background:#4F46E5;color:#fff;padding:10px 20px;border-radius:6px;text-decoration:none;display:inline-block;">Zobacz wycenę</a></p>

<p>Chętnie porozmawiamy przez telefon, by omówić szczegóły. Daj nam znać, kiedy Ci odpowiada.</p>

<p>Z poważaniem,<br>
<strong>{{manager_name}}</strong><br>
WebsiteExpert</p>
HTML,
                    'pt' => <<<'HTML'
<p>Caro/a {{client_name}},</p>

<p>Obrigado pelo seu contacto. Em anexo encontra o seu orçamento personalizado <strong>{{quote_number}}</strong> da WebsiteExpert.</p>

<p><strong>Resumo do Orçamento:</strong><br>
Total: <strong>{{quote_total}}</strong> (IVA incluído)<br>
Válido até: <strong>{{valid_until}}</strong></p>

<p>Pode consultar e aceitar o seu orçamento online:</p>

<p><a href="{{quote_url}}" style="background:#4F46E5;color:#fff;padding:10px 20px;border-radius:6px;text-decoration:none;display:inline-block;">Ver Orçamento</a></p>

<p>Adoraría marcar uma chamada rápida para lhe explicar tudo — avise-nos quando lhe for conveniente.</p>

<p>Aguardo o seu retorno.</p>

<p>Com os melhores cumprimentos,<br>
<strong>{{manager_name}}</strong><br>
WebsiteExpert</p>
HTML,
                ],
                'body_text' => [
                    'en' => "Dear {{client_name}},\n\nPlease find attached your quote {{quote_number}}.\n\nTotal: {{quote_total}} (inc. VAT)\nValid Until: {{valid_until}}\n\nView quote: {{quote_url}}\n\nBest regards,\n{{manager_name}}\nWebsiteExpert",
                    'pl' => "Szanowny/a {{client_name}},\n\nW załączeniu wycena {{quote_number}}.\n\nKwota: {{quote_total}} (brutto)\nWażna do: {{valid_until}}\n\nZobacz wycenę: {{quote_url}}\n\nZ poważaniem,\n{{manager_name}}\nWebsiteExpert",
                    'pt' => "Caro/a {{client_name}},\n\nEm anexo o seu orçamento {{quote_number}}.\n\nTotal: {{quote_total}} (IVA incluído)\nVálido até: {{valid_until}}\n\nVer orçamento: {{quote_url}}\n\nCom os melhores cumprimentos,\n{{manager_name}}\nWebsiteExpert",
                ],
            ],
            [
                'name'      => 'Quote Accepted – Project Kickoff',
                'slug'      => 'quote_accepted',
                'subject'   => [
                    'en' => 'Brilliant News! Your Quote is Confirmed – Next Steps Inside',
                    'pl' => 'Świetna wiadomość! Twoja wycena jest zatwierdzona – sprawdź kolejne kroki',
                    'pt' => 'Ótimas notícias! O seu orçamento foi confirmado – próximos passos',
                ],
                'variables' => ['client_name', 'quote_number', 'project_title', 'manager_name', 'deposit_amount', 'invoice_url'],
                'is_active' => true,
                'body_html' => [
                    'en' => <<<'HTML'
<p>Dear {{client_name}},</p>

<p>Fantastic news — your quote <strong>{{quote_number}}</strong> has been confirmed and we're ready to get started on <strong>{{project_title}}</strong>!</p>

<p><strong>Next Steps:</strong></p>
<ol>
  <li>Pay your project deposit of <strong>{{deposit_amount}}</strong> — <a href="{{invoice_url}}">view deposit invoice</a></li>
  <li>We'll schedule a kick-off call within 48 hours of receiving your deposit</li>
  <li>We'll send you access to your client portal to track progress</li>
</ol>

<p>We're genuinely excited to work with you. If you have any questions before the kick-off call, please feel free to reach out.</p>

<p>Warm regards,<br>
<strong>{{manager_name}}</strong><br>
WebsiteExpert</p>
HTML,
                    'pl' => <<<'HTML'
<p>Szanowny/a {{client_name}},</p>

<p>Fantastyczna wiadomość — Twoja wycena <strong>{{quote_number}}</strong> została zatwierdzona i jesteśmy gotowi do rozpoczęcia prac nad <strong>{{project_title}}</strong>!</p>

<p><strong>Kolejne kroki:</strong></p>
<ol>
  <li>Opłać zaliczkę w wysokości <strong>{{deposit_amount}}</strong> — <a href="{{invoice_url}}">przejdź do faktury zaliczkowej</a></li>
  <li>Zaplanujemy spotkanie inauguracyjne w ciągu 48 godzin od otrzymania zaliczki</li>
  <li>Wyślemy Ci dostęp do portalu klienta, aby śledzić postęp</li>
</ol>

<p>Bardzo cieszymy się na współpracę. W razie pytań przed spotkaniem — napisz do nas.</p>

<p>Z poważaniem,<br>
<strong>{{manager_name}}</strong><br>
WebsiteExpert</p>
HTML,
                    'pt' => <<<'HTML'
<p>Caro/a {{client_name}},</p>

<p>Notícias fanstásticas — o seu orçamento <strong>{{quote_number}}</strong> foi confirmado e estamos prontos para começar o projeto <strong>{{project_title}}</strong>!</p>

<p><strong>Próximos Passos:</strong></p>
<ol>
  <li>Pague o depósito do projeto de <strong>{{deposit_amount}}</strong> — <a href="{{invoice_url}}">ver fatura de depósito</a></li>
  <li>Iremos agendar uma reunião de arranque nas 48 horas após recebermos o depósito</li>
  <li>Enviaremos o acesso ao portal do cliente para acompanhar o progresso</li>
</ol>

<p>Estamos muito entusiasmados por trabalhar consigo. Se tiver alguma dúvida antes da reunião — contacte-nos.</p>

<p>Com os melhores cumprimentos,<br>
<strong>{{manager_name}}</strong><br>
WebsiteExpert</p>
HTML,
                ],
                'body_text' => [
                    'en' => "Dear {{client_name}},\n\nYour quote {{quote_number}} has been confirmed for {{project_title}}.\n\nNext steps:\n1. Pay deposit of {{deposit_amount}}: {{invoice_url}}\n2. Kick-off call within 48 hours\n3. Client portal access\n\nWarm regards,\n{{manager_name}}\nWebsiteExpert",
                    'pl' => "Szanowny/a {{client_name}},\n\nWycena {{quote_number}} dla projektu {{project_title}} została zatwierdzona.\n\nKolejne kroki:\n1. Wpłać zaliczkę {{deposit_amount}}: {{invoice_url}}\n2. Spotkanie inauguracyjne w ciągu 48 godzin\n3. Dostęp do portalu klienta\n\nZ poważaniem,\n{{manager_name}}\nWebsiteExpert",
                    'pt' => "Caro/a {{client_name}},\n\nO or\u00e7amento {{quote_number}} para o projeto {{project_title}} foi confirmado.\n\nPr\u00f3ximos passos:\n1. Pague o dep\u00f3sito de {{deposit_amount}}: {{invoice_url}}\n2. Reuni\u00e3o de arranque nas 48 horas seguintes\n3. Acesso ao portal do cliente\n\nCom os melhores cumprimentos,\n{{manager_name}}\nWebsiteExpert",
                ],
            ],
            [
                'name'      => 'Project Phase Complete',
                'slug'      => 'project_phase_complete',
                'subject'   => [
                    'en' => 'Update on {{project_title}}: {{phase_name}} Complete ✓',
                    'pl' => 'Aktualizacja projektu {{project_title}}: etap {{phase_name}} ukończony ✓',
                    'pt' => 'Atualização de {{project_title}}: {{phase_name}} concluída ✓',
                ],
                'variables' => ['client_name', 'project_title', 'phase_name', 'next_phase', 'portal_url', 'manager_name'],
                'is_active' => true,
                'body_html' => [
                    'en' => <<<'HTML'
<p>Dear {{client_name}},</p>

<p>Great progress update for <strong>{{project_title}}</strong> — we've completed the <strong>{{phase_name}}</strong> phase!</p>

<p>We're now moving on to <strong>{{next_phase}}</strong>.</p>

<p>You can review the latest progress and provide any feedback in your client portal:</p>

<p><a href="{{portal_url}}" style="background:#4F46E5;color:#fff;padding:10px 20px;border-radius:6px;text-decoration:none;display:inline-block;">View Project Progress</a></p>

<p>If you have any feedback or questions, don't hesitate to drop us a message.</p>

<p>Best regards,<br>
<strong>{{manager_name}}</strong><br>
WebsiteExpert</p>
HTML,
                    'pl' => <<<'HTML'
<p>Szanowny/a {{client_name}},</p>

<p>Mamy dla Ciebie dobrą wiadomość dotyczącą projektu <strong>{{project_title}}</strong> — ukończyliśmy etap <strong>{{phase_name}}</strong>!</p>

<p>Przechodzimy teraz do etapu <strong>{{next_phase}}</strong>.</p>

<p>Możesz przejrzeć najnowszy postęp i podzielić się opinią w portalu klienta:</p>

<p><a href="{{portal_url}}" style="background:#4F46E5;color:#fff;padding:10px 20px;border-radius:6px;text-decoration:none;display:inline-block;">Zobacz postęp projektu</a></p>

<p>Jeśli masz uwagi lub pytania — napisz do nas.</p>

<p>Z poważaniem,<br>
<strong>{{manager_name}}</strong><br>
WebsiteExpert</p>
HTML,
                    'pt' => <<<'HTML'
<p>Caro/a {{client_name}},</p>

<p>Temos uma atualização de progresso para o projeto <strong>{{project_title}}</strong> — concluímos a fase <strong>{{phase_name}}</strong>!</p>

<p>Avançamos agora para a fase <strong>{{next_phase}}</strong>.</p>

<p>Pode rever o progresso mais recente e partilhar o seu feedback no portal do cliente:</p>

<p><a href="{{portal_url}}" style="background:#4F46E5;color:#fff;padding:10px 20px;border-radius:6px;text-decoration:none;display:inline-block;">Ver Progresso do Projeto</a></p>

<p>Se tiver algum comentário ou pergunta, não hesite em enviar-nos uma mensagem.</p>

<p>Com os melhores cumprimentos,<br>
<strong>{{manager_name}}</strong><br>
WebsiteExpert</p>
HTML,
                ],
                'body_text' => [
                    'en' => "Dear {{client_name}},\n\nWe've completed the {{phase_name}} phase of {{project_title}}.\n\nNext up: {{next_phase}}\n\nView progress: {{portal_url}}\n\nBest regards,\n{{manager_name}}\nWebsiteExpert",
                    'pl' => "Szanowny/a {{client_name}},\n\nUkończyliśmy etap {{phase_name}} projektu {{project_title}}.\n\nNastępny etap: {{next_phase}}\n\nPortal: {{portal_url}}\n\nZ poważaniem,\n{{manager_name}}\nWebsiteExpert",
                    'pt' => "Caro/a {{client_name}},\n\nConclu\u00edmos a fase {{phase_name}} do projeto {{project_title}}.\n\nPr\u00f3xima fase: {{next_phase}}\n\nPortal: {{portal_url}}\n\nCom os melhores cumprimentos,\n{{manager_name}}\nWebsiteExpert",
                ],
            ],
            [
                'name'      => 'Project Launched',
                'slug'      => 'project_launched',
                'subject'   => [
                    'en' => '🚀 {{project_title}} is LIVE! Congratulations!',
                    'pl' => '🚀 {{project_title}} jest już ONLINE! Gratulacje!',
                    'pt' => '🚀 {{project_title}} está NO AR! Parabéns!',
                ],
                'variables' => ['client_name', 'project_title', 'website_url', 'manager_name', 'support_email'],
                'is_active' => true,
                'body_html' => [
                    'en' => <<<'HTML'
<p>Dear {{client_name}},</p>

<p>We are absolutely thrilled to announce that <strong>{{project_title}}</strong> is now LIVE! 🎉</p>

<p><a href="{{website_url}}">{{website_url}}</a></p>

<p>It's been a pleasure working with you on this project. Here's what's included in your handover pack:</p>
<ul>
  <li>Login credentials for your CMS / admin panel</li>
  <li>Hosting control panel access</li>
  <li>Google Analytics access</li>
  <li>Training video recording</li>
  <li>User guide PDF</li>
</ul>

<p>For ongoing support, please contact us at <a href="mailto:{{support_email}}">{{support_email}}</a>.</p>

<p>Don't forget to share the website on your social channels — we'd love a tag!</p>

<p>Warmest congratulations,<br>
<strong>{{manager_name}} & The WebsiteExpert Team</strong></p>
HTML,
                    'pl' => <<<'HTML'
<p>Szanowny/a {{client_name}},</p>

<p>Z ogromną radością informujemy, że <strong>{{project_title}}</strong> jest już ONLINE! 🎉</p>

<p><a href="{{website_url}}">{{website_url}}</a></p>

<p>Była to dla nas prawdziwa przyjemność. Oto co znajdziesz w paczce powitalnej:</p>
<ul>
  <li>Dane logowania do CMS / panelu administracyjnego</li>
  <li>Dostęp do panelu hostingowego</li>
  <li>Dostęp do Google Analytics</li>
  <li>Nagranie wideo ze szkolenia</li>
  <li>Przewodnik użytkownika w PDF</li>
</ul>

<p>Wsparcie techniczne: <a href="mailto:{{support_email}}">{{support_email}}</a>.</p>

<p>Nie zapomnij podzielić się stroną w mediach społecznościowych — chętnie będziemy oznaczeni!</p>

<p>Serdeczne gratulacje,<br>
<strong>{{manager_name}} i Zespół WebsiteExpert</strong></p>
HTML,
                    'pt' => <<<'HTML'
<p>Caro/a {{client_name}},</p>

<p>Estamos absolutamente entusiasmados em anunciar que o projeto <strong>{{project_title}}</strong> está agora NO AR! 🎉</p>

<p><a href="{{website_url}}">{{website_url}}</a></p>

<p>Foi um prazer trabalhar consigo neste projeto. Aqui está o que está incluído no seu pack de entrega:</p>
<ul>
  <li>Credenciais de acesso ao CMS / painel de administração</li>
  <li>Acesso ao painel de controlo de alojamento</li>
  <li>Acesso ao Google Analytics</li>
  <li>Gravação de vídeo de formação</li>
  <li>Guia do utilizador em PDF</li>
</ul>

<p>Para suporte contínuo, contacte-nos em <a href="mailto:{{support_email}}">{{support_email}}</a>.</p>

<p>Não se esqueça de partilhar o website nas suas redes sociais — adoraríamos ser mencionados!</p>

<p>Os mais calorosos parabéns,<br>
<strong>{{manager_name}} &amp; A Equipa WebsiteExpert</strong></p>
HTML,
                ],
                'body_text' => [
                    'en' => "Dear {{client_name}},\n\n{{project_title}} is now LIVE!\n\n{{website_url}}\n\nHandover pack sent separately.\n\nFor support: {{support_email}}\n\nCongratulations!\n{{manager_name}} & The WebsiteExpert Team",
                    'pl' => "Szanowny/a {{client_name}},\n\n{{project_title}} jest już ONLINE!\n\n{{website_url}}\n\nPaczka powitalna wysłana osobno.\n\nWsparcie: {{support_email}}\n\nGratulacje!\n{{manager_name}} i Zespół WebsiteExpert",
                    'pt' => "Caro/a {{client_name}},\n\n{{project_title}} est\u00e1 agora NO AR!\n\n{{website_url}}\n\nPack de entrega enviado separadamente.\n\nSuporte: {{support_email}}\n\nParab\u00e9ns!\n{{manager_name}} & A Equipa WebsiteExpert",
                ],
            ],
        ];

        foreach ($templates as $data) {
            EmailTemplate::updateOrCreate(['slug' => $data['slug']], $data);
        }
    }
}

