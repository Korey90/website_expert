<?php

namespace Database\Seeders;

use App\Models\Page;
use App\Models\User;
use Illuminate\Database\Seeder;

class PageSeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::where('email', 'admin@websiteexpert.co.uk')->first();

        $pages = [

            // ─── Privacy Policy ───────────────────────────────────────────────
            [
                'slug'           => 'privacy-policy',
                'status'         => 'published',
                'type'           => 'policy',
                'show_in_footer' => true,
                'sort_order'     => 1,
                'created_by'     => $admin?->id,
                'published_at'   => now()->subMonths(6),

                'title' => [
                    'en' => 'Privacy Policy',
                    'pl' => 'Polityka prywatności',
                    'pt' => 'Política de Privacidade',
                ],
                'meta_title' => [
                    'en' => 'Privacy Policy | WebsiteExpert',
                    'pl' => 'Polityka prywatności | WebsiteExpert',
                    'pt' => 'Política de Privacidade | WebsiteExpert',
                ],
                'meta_description' => [
                    'en' => 'How WebsiteExpert collects, uses, and protects your personal data in compliance with UK GDPR.',
                    'pl' => 'Jak WebsiteExpert zbiera, wykorzystuje i chroni Twoje dane osobowe zgodnie z RODO.',
                    'pt' => 'Como a WebsiteExpert recolhe, usa e protege os seus dados pessoais em conformidade com o RGPD.',
                ],
                'content' => [
                    'en' => <<<'HTML'
<h2>Privacy Policy</h2>
<p><strong>Last updated: January 2025</strong></p>
<p>WebsiteExpert Ltd ("we", "us", or "our") is committed to protecting your personal data and respecting your privacy in accordance with the UK General Data Protection Regulation (UK GDPR) and the Data Protection Act 2018.</p>
<h3>1. Who We Are</h3>
<p>WebsiteExpert Ltd is a company registered in England and Wales. We are the data controller for the personal data we collect and process about you.</p>
<h3>2. What Personal Data We Collect</h3>
<ul>
  <li><strong>Contact information:</strong> name, email address, phone number, business address</li>
  <li><strong>Business information:</strong> company name, Companies House number, VAT number</li>
  <li><strong>Communications:</strong> emails, enquiry form submissions</li>
  <li><strong>Technical data:</strong> IP address, browser type, pages visited</li>
  <li><strong>Financial data:</strong> invoice and payment records</li>
</ul>
<h3>3. How We Use Your Personal Data</h3>
<ul>
  <li>To provide and manage our web development and digital marketing services</li>
  <li>To respond to enquiries and provide quotes</li>
  <li>To send invoices and manage payments</li>
  <li>To improve our website and services</li>
  <li>To comply with our legal and regulatory obligations</li>
</ul>
<h3>4. Legal Basis for Processing</h3>
<ul>
  <li><strong>Contract:</strong> processing necessary for the performance of a contract with you</li>
  <li><strong>Legitimate interests:</strong> improving our services, fraud prevention</li>
  <li><strong>Legal obligation:</strong> tax and accounting records</li>
  <li><strong>Consent:</strong> marketing communications</li>
</ul>
<h3>5. Data Retention</h3>
<p>We retain personal data for as long as necessary. Financial records are retained for 7 years in compliance with HMRC requirements.</p>
<h3>6. Your Rights</h3>
<ul>
  <li>Access, rectify, or erase your data</li>
  <li>Restrict or object to processing</li>
  <li>Data portability</li>
  <li>Withdraw consent at any time</li>
</ul>
<p>Contact: <a href="mailto:privacy@websiteexpert.co.uk">privacy@websiteexpert.co.uk</a></p>
<h3>7. Complaints</h3>
<p>You may lodge a complaint with the ICO: <a href="https://www.ico.org.uk" rel="noopener">www.ico.org.uk</a></p>
HTML,
                    'pl' => <<<'HTML'
<h2>Polityka prywatności</h2>
<p><strong>Ostatnia aktualizacja: styczeń 2025</strong></p>
<p>WebsiteExpert Ltd zobowiązuje się do ochrony Twoich danych osobowych i poszanowania Twojej prywatności zgodnie z UK RODO i Ustawą o Ochronie Danych z 2018 r.</p>
<h3>1. Kim jesteśmy</h3>
<p>WebsiteExpert Ltd jest spółką zarejestrowaną w Anglii i Walii. Jesteśmy administratorem danych osobowych, które zbieramy i przetwarzamy.</p>
<h3>2. Jakie dane osobowe zbieramy</h3>
<ul>
  <li><strong>Dane kontaktowe:</strong> imię i nazwisko, adres e-mail, numer telefonu, adres firmowy</li>
  <li><strong>Dane firmowe:</strong> nazwa firmy, numer rejestracyjny, NIP/VAT</li>
  <li><strong>Korespondencja:</strong> wiadomości e-mail, zgłoszenia przez formularz kontaktowy</li>
  <li><strong>Dane techniczne:</strong> adres IP, typ przeglądarki, odwiedzone strony</li>
  <li><strong>Dane finansowe:</strong> faktury i płatności</li>
</ul>
<h3>3. Jak wykorzystujemy Twoje dane</h3>
<ul>
  <li>Świadczenie i zarządzanie usługami web developmentu i marketingu cyfrowego</li>
  <li>Odpowiedź na zapytania i przygotowanie ofert</li>
  <li>Wystawianie faktur i obsługa płatności</li>
  <li>Doskonalenie naszej strony i usług</li>
  <li>Wypełnianie zobowiązań prawnych i regulacyjnych</li>
</ul>
<h3>4. Podstawa prawna przetwarzania</h3>
<ul>
  <li><strong>Umowa:</strong> przetwarzanie niezbędne do wykonania umowy</li>
  <li><strong>Uzasadniony interes:</strong> doskonalenie usług, zapobieganie nadużyciom</li>
  <li><strong>Obowiązek prawny:</strong> ewidencja podatkowa i księgowa</li>
  <li><strong>Zgoda:</strong> komunikacja marketingowa</li>
</ul>
<h3>5. Okres przechowywania danych</h3>
<p>Przechowujemy dane osobowe tak długo, jak jest to konieczne. Dokumenty finansowe są przechowywane przez 7 lat zgodnie z wymogami HMRC.</p>
<h3>6. Twoje prawa</h3>
<ul>
  <li>Dostęp, sprostowanie lub usunięcie danych</li>
  <li>Ograniczenie lub sprzeciw wobec przetwarzania</li>
  <li>Przenoszenie danych</li>
  <li>Wycofanie zgody w dowolnym momencie</li>
</ul>
<p>Kontakt: <a href="mailto:privacy@websiteexpert.co.uk">privacy@websiteexpert.co.uk</a></p>
<h3>7. Skargi</h3>
<p>Masz prawo złożyć skargę do Urzędu Komisarza ds. Informacji (ICO): <a href="https://www.ico.org.uk" rel="noopener">www.ico.org.uk</a></p>
HTML,
                    'pt' => <<<'HTML'
<h2>Política de Privacidade</h2>
<p><strong>Última atualização: janeiro de 2025</strong></p>
<p>A WebsiteExpert Ltd compromete-se a proteger os seus dados pessoais e a respeitar a sua privacidade em conformidade com o RGPD do Reino Unido e a Lei de Proteção de Dados de 2018.</p>
<h3>1. Quem Somos</h3>
<p>A WebsiteExpert Ltd é uma empresa registada em Inglaterra e no País de Gales. Somos o responsável pelo tratamento dos dados pessoais que recolhemos e processamos.</p>
<h3>2. Que Dados Recolhemos</h3>
<ul>
  <li><strong>Informações de contacto:</strong> nome, endereço de e-mail, número de telefone, morada comercial</li>
  <li><strong>Informações empresariais:</strong> nome da empresa, número de registo, NIF</li>
  <li><strong>Comunicações:</strong> e-mails, submissões de formulários de contacto</li>
  <li><strong>Dados técnicos:</strong> endereço IP, tipo de browser, páginas visitadas</li>
  <li><strong>Dados financeiros:</strong> faturas e registos de pagamento</li>
</ul>
<h3>3. Como Utilizamos os Seus Dados</h3>
<ul>
  <li>Prestação e gestão dos nossos serviços de desenvolvimento web e marketing digital</li>
  <li>Resposta a pedidos de informação e elaboração de propostas</li>
  <li>Emissão de faturas e gestão de pagamentos</li>
  <li>Melhoria do nosso website e serviços</li>
  <li>Cumprimento de obrigações legais e regulamentares</li>
</ul>
<h3>4. Base Legal para o Tratamento</h3>
<ul>
  <li><strong>Contrato:</strong> tratamento necessário para execução de um contrato</li>
  <li><strong>Interesses legítimos:</strong> melhoria dos serviços, prevenção de fraudes</li>
  <li><strong>Obrigação legal:</strong> registos fiscais e contabilísticos</li>
  <li><strong>Consentimento:</strong> comunicações de marketing</li>
</ul>
<h3>5. Retenção de Dados</h3>
<p>Conservamos os dados pessoais durante o tempo necessário. Os registos financeiros são conservados durante 7 anos.</p>
<h3>6. Os Seus Direitos</h3>
<ul>
  <li>Aceder, retificar ou apagar os seus dados</li>
  <li>Restringir ou opor-se ao tratamento</li>
  <li>Portabilidade dos dados</li>
  <li>Retirar o consentimento a qualquer momento</li>
</ul>
<p>Contacto: <a href="mailto:privacy@websiteexpert.co.uk">privacy@websiteexpert.co.uk</a></p>
<h3>7. Reclamações</h3>
<p>Pode apresentar uma reclamação ao ICO (Comissário de Informação do Reino Unido): <a href="https://www.ico.org.uk" rel="noopener">www.ico.org.uk</a></p>
HTML,
                ],
            ],

            // ─── Terms & Conditions ───────────────────────────────────────────
            [
                'slug'           => 'terms-and-conditions',
                'status'         => 'published',
                'type'           => 'terms',
                'show_in_footer' => true,
                'sort_order'     => 2,
                'created_by'     => $admin?->id,
                'published_at'   => now()->subMonths(6),

                'title' => [
                    'en' => 'Terms & Conditions',
                    'pl' => 'Regulamin',
                    'pt' => 'Termos e Condições',
                ],
                'meta_title' => [
                    'en' => 'Terms & Conditions | WebsiteExpert',
                    'pl' => 'Regulamin | WebsiteExpert',
                    'pt' => 'Termos e Condições | WebsiteExpert',
                ],
                'meta_description' => [
                    'en' => 'Terms and conditions governing the provision of web development and digital marketing services by WebsiteExpert Ltd.',
                    'pl' => 'Regulamin świadczenia usług web developmentu i marketingu cyfrowego przez WebsiteExpert Ltd.',
                    'pt' => 'Termos e condições que regem a prestação de serviços de desenvolvimento web e marketing digital pela WebsiteExpert Ltd.',
                ],
                'content' => [
                    'en' => <<<'HTML'
<h2>Terms &amp; Conditions</h2>
<p><strong>Last updated: January 2025</strong></p>
<p>These Terms and Conditions ("Terms") govern all services provided by WebsiteExpert Ltd ("the Company", "we", "us") to our clients ("Client", "you"). By engaging our services or accepting a quote, you agree to be bound by these Terms.</p>
<h3>1. Services</h3>
<p>WebsiteExpert Ltd provides web design, web development, digital marketing, hosting, and related services as outlined in individual project quotes or service agreements.</p>
<h3>2. Quotes &amp; Proposals</h3>
<p>All quotes are valid for 30 days from the date of issue. Scope changes may result in revised pricing.</p>
<h3>3. Project Kickoff &amp; Deposits</h3>
<p>Projects commence upon receipt of a signed agreement and the agreed deposit (typically 40–50% of the project total).</p>
<h3>4. Payment Terms</h3>
<ul>
  <li>Invoices are due within 14 days of issue</li>
  <li>Late payments may incur interest at 8% above the Bank of England base rate per annum</li>
  <li>Work may be suspended on accounts more than 30 days overdue</li>
</ul>
<h3>5. Intellectual Property</h3>
<p>Upon receipt of full payment, copyright in the final deliverables transfers to the Client. Third-party assets are subject to their own licences.</p>
<h3>6. Client Responsibilities</h3>
<ul>
  <li>Providing accurate content, materials, and timely feedback</li>
  <li>Ensuring content does not infringe third-party rights</li>
  <li>Compliance with relevant laws (GDPR, Consumer Rights Act, etc.)</li>
</ul>
<h3>7. Revisions</h3>
<p>Additional revisions beyond the agreed scope will be charged at our current hourly rate (£65/hour).</p>
<h3>8. Limitation of Liability</h3>
<p>The Company's total liability shall not exceed the total fees paid for the specific service giving rise to the claim.</p>
<h3>9. Governing Law</h3>
<p>These Terms are governed by the laws of England and Wales.</p>
<h3>10. Contact</h3>
<p><a href="mailto:hello@websiteexpert.co.uk">hello@websiteexpert.co.uk</a></p>
HTML,
                    'pl' => <<<'HTML'
<h2>Regulamin</h2>
<p><strong>Ostatnia aktualizacja: styczeń 2025</strong></p>
<p>Niniejszy Regulamin reguluje wszystkie usługi świadczone przez WebsiteExpert Ltd ("Firma", "my") na rzecz Klientów. Korzystając z naszych usług lub akceptując ofertę, wyrażasz zgodę na niniejszy Regulamin.</p>
<h3>1. Usługi</h3>
<p>WebsiteExpert Ltd świadczy usługi projektowania i tworzenia stron www, marketingu cyfrowego, hostingu i pokrewnych usług określonych w indywidualnych wycenach lub umowach.</p>
<h3>2. Oferty i propozycje</h3>
<p>Wszystkie oferty są ważne przez 30 dni od daty wystawienia. Zmiany zakresu mogą skutkować zmianami cenowymi.</p>
<h3>3. Rozpoczęcie projektu i zaliczki</h3>
<p>Projekt rozpoczyna się po otrzymaniu podpisanej umowy i ustalonej zaliczki (zazwyczaj 40–50% wartości projektu).</p>
<h3>4. Warunki płatności</h3>
<ul>
  <li>Faktury są płatne w ciągu 14 dni od wystawienia</li>
  <li>Opóźnienia mogą skutkować naliczeniem odsetek</li>
  <li>Prace mogą zostać wstrzymane przy opóźnieniu powyżej 30 dni</li>
</ul>
<h3>5. Własność intelektualna</h3>
<p>Po otrzymaniu pełnej zapłaty prawa autorskie do ostatecznych rezultatów przechodzą na Klienta. Zasoby stron trzecich podlegają własnym licencjom.</p>
<h3>6. Obowiązki Klienta</h3>
<ul>
  <li>Dostarczanie dokładnych treści i terminowej informacji zwrotnej</li>
  <li>Zapewnienie, że treści nie naruszają praw osób trzecich</li>
  <li>Zgodność z obowiązującymi przepisami prawa</li>
</ul>
<h3>7. Poprawki</h3>
<p>Dodatkowe poprawki wykraczające poza uzgodniony zakres będą naliczane według bieżącej stawki godzinowej.</p>
<h3>8. Ograniczenie odpowiedzialności</h3>
<p>Całkowita odpowiedzialność Firmy nie przekroczy łącznych opłat zapłaconych za konkretną usługę.</p>
<h3>9. Prawo właściwe</h3>
<p>Niniejszy Regulamin podlega prawu Anglii i Walii.</p>
<h3>10. Kontakt</h3>
<p><a href="mailto:hello@websiteexpert.co.uk">hello@websiteexpert.co.uk</a></p>
HTML,
                    'pt' => <<<'HTML'
<h2>Termos e Condições</h2>
<p><strong>Última atualização: janeiro de 2025</strong></p>
<p>Estes Termos e Condições ("Termos") regem todos os serviços prestados pela WebsiteExpert Ltd ("a Empresa", "nós") aos nossos clientes ("Cliente", "você"). Ao contratar os nossos serviços ou aceitar uma proposta, concorda com estes Termos.</p>
<h3>1. Serviços</h3>
<p>A WebsiteExpert Ltd presta serviços de design web, desenvolvimento web, marketing digital, alojamento e serviços relacionados, conforme especificado em propostas ou acordos individuais.</p>
<h3>2. Propostas</h3>
<p>Todas as propostas são válidas por 30 dias a partir da data de emissão. Alterações no âmbito podem resultar em revisão de preços.</p>
<h3>3. Início do Projeto e Adiantamentos</h3>
<p>Os projetos começam após a receção de um acordo assinado e do adiantamento acordado (tipicamente 40–50% do total do projeto).</p>
<h3>4. Condições de Pagamento</h3>
<ul>
  <li>As faturas vencem no prazo de 14 dias após a emissão</li>
  <li>Os atrasos podem incorrer em juros</li>
  <li>Os trabalhos podem ser suspensos em contas com mais de 30 dias de atraso</li>
</ul>
<h3>5. Propriedade Intelectual</h3>
<p>Após o pagamento integral, os direitos de autor sobre os entregáveis finais são transferidos para o Cliente. Os recursos de terceiros estão sujeitos às suas próprias licenças.</p>
<h3>6. Responsabilidades do Cliente</h3>
<ul>
  <li>Fornecer conteúdo preciso e feedback atempado</li>
  <li>Garantir que o conteúdo não viola direitos de terceiros</li>
  <li>Conformidade com as leis aplicáveis</li>
</ul>
<h3>7. Revisões</h3>
<p>Revisões adicionais além do âmbito acordado serão cobradas à taxa horária atual.</p>
<h3>8. Limitação de Responsabilidade</h3>
<p>A responsabilidade total da Empresa não excederá os honorários totais pagos pelo serviço específico.</p>
<h3>9. Lei Aplicável</h3>
<p>Estes Termos são regidos pelas leis de Inglaterra e do País de Gales.</p>
<h3>10. Contacto</h3>
<p><a href="mailto:hello@websiteexpert.co.uk">hello@websiteexpert.co.uk</a></p>
HTML,
                ],
            ],

            // ─── Cookie Policy ────────────────────────────────────────────────
            [
                'slug'           => 'cookies',
                'status'         => 'published',
                'type'           => 'cookie_policy',
                'show_in_footer' => true,
                'sort_order'     => 3,
                'created_by'     => $admin?->id,
                'published_at'   => now()->subMonths(6),

                'title' => [
                    'en' => 'Cookie Policy',
                    'pl' => 'Polityka cookies',
                    'pt' => 'Política de Cookies',
                ],
                'meta_title' => [
                    'en' => 'Cookie Policy | WebsiteExpert',
                    'pl' => 'Polityka cookies | WebsiteExpert',
                    'pt' => 'Política de Cookies | WebsiteExpert',
                ],
                'meta_description' => [
                    'en' => 'Information about the cookies used on the WebsiteExpert website and how to manage your preferences.',
                    'pl' => 'Informacje o plikach cookies stosowanych na stronie WebsiteExpert i zarządzaniu preferencjami.',
                    'pt' => 'Informações sobre os cookies utilizados no website da WebsiteExpert e como gerir as suas preferências.',
                ],
                'content' => [
                    'en' => <<<'HTML'
<h2>Cookie Policy</h2>
<p><strong>Last updated: January 2025</strong></p>
<p>This Cookie Policy explains how WebsiteExpert Ltd uses cookies and similar tracking technologies on our website.</p>
<h3>What Are Cookies?</h3>
<p>Cookies are small text files placed on your device when you visit a website. They help websites work more efficiently and provide information about how the site is used.</p>
<h3>Types of Cookies We Use</h3>
<h4>Strictly Necessary Cookies</h4>
<p>Essential for the website to function: session management, security tokens, CSRF protection.</p>
<h4>Analytics Cookies</h4>
<p>We use Google Analytics (GA4) to understand visitor behaviour. IP addresses are anonymised. Opt out at <a href="https://tools.google.com/dlpage/gaoptout" rel="noopener">tools.google.com/dlpage/gaoptout</a>.</p>
<h4>Functional Cookies</h4>
<p>These remember your preferences (language settings, form data) to improve your experience.</p>
<h4>Marketing Cookies</h4>
<p>Only placed with your explicit consent (e.g. Google Ads, LinkedIn Insight Tag).</p>
<h3>Managing Cookies</h3>
<p>You can control and/or delete cookies at any time via our cookie consent banner or your browser settings.</p>
HTML,
                    'pl' => <<<'HTML'
<h2>Polityka cookies</h2>
<p><strong>Ostatnia aktualizacja: styczeń 2025</strong></p>
<p>Niniejsza Polityka cookies wyjaśnia, w jaki sposób WebsiteExpert Ltd używa plików cookies i podobnych technologii śledzenia na naszej stronie.</p>
<h3>Czym są pliki cookies?</h3>
<p>Pliki cookies to małe pliki tekstowe umieszczane na urządzeniu podczas odwiedzania strony internetowej. Pomagają w efektywnym działaniu stron i dostarczają informacje o sposobie ich użytkowania.</p>
<h3>Rodzaje plików cookies, których używamy</h3>
<h4>Niezbędne pliki cookies</h4>
<p>Konieczne do działania strony: zarządzanie sesją, tokeny bezpieczeństwa, ochrona CSRF.</p>
<h4>Analityczne pliki cookies</h4>
<p>Używamy Google Analytics (GA4) do analizy zachowań odwiedzających. Adresy IP są anonimizowane. Możesz zrezygnować na <a href="https://tools.google.com/dlpage/gaoptout" rel="noopener">tools.google.com/dlpage/gaoptout</a>.</p>
<h4>Funkcjonalne pliki cookies</h4>
<p>Zapamiętują Twoje preferencje (język, dane formularzy) w celu poprawy doświadczeń.</p>
<h4>Marketingowe pliki cookies</h4>
<p>Stosowane wyłącznie za Twoją wyraźną zgodą (np. Google Ads, LinkedIn Insight Tag).</p>
<h3>Zarządzanie plikami cookies</h3>
<p>Możesz kontrolować i/lub usuwać pliki cookies w dowolnym momencie poprzez baner zgody lub ustawienia przeglądarki.</p>
HTML,
                    'pt' => <<<'HTML'
<h2>Política de Cookies</h2>
<p><strong>Última atualização: janeiro de 2025</strong></p>
<p>Esta Política de Cookies explica como a WebsiteExpert Ltd utiliza cookies e tecnologias de rastreamento semelhantes no nosso website.</p>
<h3>O que são Cookies?</h3>
<p>Os cookies são pequenos ficheiros de texto colocados no seu dispositivo quando visita um website. Ajudam os websites a funcionar de forma mais eficiente e fornecem informações sobre como o site é utilizado.</p>
<h3>Tipos de Cookies que Utilizamos</h3>
<h4>Cookies Estritamente Necessários</h4>
<p>Essenciais para o funcionamento do website: gestão de sessões, tokens de segurança, proteção CSRF.</p>
<h4>Cookies Analíticos</h4>
<p>Utilizamos o Google Analytics (GA4) para compreender o comportamento dos visitantes. Os endereços IP são anonimizados. Pode optar por não participar em <a href="https://tools.google.com/dlpage/gaoptout" rel="noopener">tools.google.com/dlpage/gaoptout</a>.</p>
<h4>Cookies Funcionais</h4>
<p>Recordam as suas preferências (idioma, dados de formulários) para melhorar a sua experiência.</p>
<h4>Cookies de Marketing</h4>
<p>Apenas colocados com o seu consentimento explícito (ex.: Google Ads, LinkedIn Insight Tag).</p>
<h3>Gerir Cookies</h3>
<p>Pode controlar e/ou eliminar cookies a qualquer momento através do nosso banner de consentimento ou das definições do browser.</p>
HTML,
                ],
            ],

            // ─── Accessibility ────────────────────────────────────────────────
            [
                'slug'           => 'accessibility',
                'status'         => 'published',
                'type'           => 'page',
                'show_in_footer' => true,
                'sort_order'     => 4,
                'created_by'     => $admin?->id,
                'published_at'   => now()->subMonths(6),

                'title' => [
                    'en' => 'Accessibility Statement',
                    'pl' => 'Oświadczenie o dostępności',
                    'pt' => 'Declaração de Acessibilidade',
                ],
                'meta_title' => [
                    'en' => 'Accessibility Statement | WebsiteExpert',
                    'pl' => 'Oświadczenie o dostępności | WebsiteExpert',
                    'pt' => 'Declaração de Acessibilidade | WebsiteExpert',
                ],
                'meta_description' => [
                    'en' => 'WebsiteExpert\'s commitment to digital accessibility for all users.',
                    'pl' => 'Zobowiązanie WebsiteExpert do zapewnienia cyfrowej dostępności dla wszystkich użytkowników.',
                    'pt' => 'Compromisso da WebsiteExpert com a acessibilidade digital para todos os utilizadores.',
                ],
                'content' => [
                    'en' => <<<'HTML'
<h2>Accessibility Statement</h2>
<p><strong>Last updated: January 2025</strong></p>
<p>WebsiteExpert Ltd is committed to ensuring digital accessibility for people with disabilities. We continually work to improve the user experience for everyone according to WCAG 2.1 Level AA.</p>
<h3>Our Commitment</h3>
<ul>
  <li>All images include descriptive alt text</li>
  <li>Colour contrast meets WCAG 2.1 AA minimum ratios</li>
  <li>The website is navigable by keyboard alone</li>
  <li>Screen reader compatibility has been tested</li>
  <li>Forms include clear labels and error messages</li>
  <li>Text can be resized up to 200% without loss of functionality</li>
</ul>
<h3>Reporting Accessibility Issues</h3>
<p>If you experience any accessibility barriers, please contact us:</p>
<p>Email: <a href="mailto:hello@websiteexpert.co.uk">hello@websiteexpert.co.uk</a></p>
<p>We aim to respond within 5 working days.</p>
HTML,
                    'pl' => <<<'HTML'
<h2>Oświadczenie o dostępności</h2>
<p><strong>Ostatnia aktualizacja: styczeń 2025</strong></p>
<p>WebsiteExpert Ltd zobowiązuje się do zapewnienia cyfrowej dostępności dla osób z niepełnosprawnościami zgodnie ze standardem WCAG 2.1 poziom AA.</p>
<h3>Nasze zobowiązania</h3>
<ul>
  <li>Wszystkie obrazy posiadają opisowe teksty alternatywne</li>
  <li>Kontrast kolorów spełnia minimalne wymagania WCAG 2.1 AA</li>
  <li>Strona jest w pełni nawigowalna za pomocą klawiatury</li>
  <li>Zgodność z czytnikami ekranu została przetestowana</li>
  <li>Formularze zawierają czytelne etykiety i komunikaty błędów</li>
  <li>Tekst można powiększyć do 200% bez utraty funkcjonalności</li>
</ul>
<h3>Zgłaszanie problemów z dostępnością</h3>
<p>Jeśli napotkasz jakiekolwiek bariery dostępności, skontaktuj się z nami:</p>
<p>E-mail: <a href="mailto:hello@websiteexpert.co.uk">hello@websiteexpert.co.uk</a></p>
<p>Odpowiadamy w ciągu 5 dni roboczych.</p>
HTML,
                    'pt' => <<<'HTML'
<h2>Declaração de Acessibilidade</h2>
<p><strong>Última atualização: janeiro de 2025</strong></p>
<p>A WebsiteExpert Ltd compromete-se a garantir a acessibilidade digital para pessoas com deficiência, de acordo com as WCAG 2.1 Nível AA.</p>
<h3>O Nosso Compromisso</h3>
<ul>
  <li>Todas as imagens incluem texto alternativo descritivo</li>
  <li>O contraste de cores cumpre os rácios mínimos das WCAG 2.1 AA</li>
  <li>O website é navegável apenas com teclado</li>
  <li>A compatibilidade com leitores de ecrã foi testada</li>
  <li>Os formulários incluem etiquetas claras e mensagens de erro</li>
  <li>O texto pode ser redimensionado até 200% sem perda de funcionalidade</li>
</ul>
<h3>Reportar Problemas de Acessibilidade</h3>
<p>Se encontrar barreiras de acessibilidade, por favor contacte-nos:</p>
<p>E-mail: <a href="mailto:hello@websiteexpert.co.uk">hello@websiteexpert.co.uk</a></p>
<p>Respondemos no prazo de 5 dias úteis.</p>
HTML,
                ],
            ],
        ];

        foreach ($pages as $data) {
            Page::updateOrCreate(
                ['slug' => $data['slug']],
                $data
            );
        }
    }
}
