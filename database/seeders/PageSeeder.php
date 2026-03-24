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
                'effective_date' => '2025-01-01',
                'version'        => '1.0',

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
                    'en' => 'How {{legal.company_name}} collects, uses, and protects your personal data in compliance with UK GDPR and the Data Protection Act 2018.',
                    'pl' => 'Jak {{legal.company_name}} zbiera, wykorzystuje i chroni Twoje dane osobowe zgodnie z UK RODO i Ustawą o Ochronie Danych 2018.',
                    'pt' => 'Como a {{legal.company_name}} recolhe, usa e protege os seus dados pessoais em conformidade com o RGPD do Reino Unido.',
                ],
                'content' => [
                    'en' => <<<'HTML'
<h2>About This Notice</h2>
<p>{{legal.company_name}} ("we", "us", "our") is committed to protecting your personal data. This Privacy Notice explains how we collect, use, store, and share information about you in accordance with the UK General Data Protection Regulation (UK GDPR) and the Data Protection Act 2018.</p>
<p><strong>Data Controller:</strong> {{legal.company_name}}, registered in England and Wales (Company No. {{legal.company_number}}), {{legal.company_address}}.</p>
<p>For data protection enquiries, contact our designated lead: {{legal.dpo_name}} at <a href="mailto:{{legal.privacy_email}}">{{legal.privacy_email}}</a>.</p>

<h2>Information We Collect</h2>
<p>We may collect the following categories of personal data:</p>
<ul>
  <li><strong>Identity &amp; contact:</strong> name, email address, phone number, postal address</li>
  <li><strong>Business details:</strong> company name, Companies House number, VAT registration number</li>
  <li><strong>Communications:</strong> emails, enquiry form submissions, support requests</li>
  <li><strong>Technical data:</strong> IP address, browser type and version, pages visited, time on site (collected only with your consent via analytics cookies)</li>
  <li><strong>Financial data:</strong> invoices, payment records (we do not store full card details)</li>
  <li><strong>Project data:</strong> files, images, copy, and other materials you provide for project delivery</li>
</ul>

<h2>How We Use Your Information</h2>
<ul>
  <li>To provide, manage, and deliver web development and digital marketing services</li>
  <li>To send quotations and respond to enquiries</li>
  <li>To issue invoices and process payments</li>
  <li>To communicate project progress and notify you of changes</li>
  <li>To improve our website and services through aggregated analytics</li>
  <li>To comply with legal and regulatory obligations</li>
</ul>

<h2>Legal Basis for Processing</h2>
<ul>
  <li><strong>Performance of a contract</strong> (Art. 6(1)(b) UK GDPR): processing necessary to deliver the agreed services</li>
  <li><strong>Legal obligation</strong> (Art. 6(1)(c)): tax records, HMRC requirements</li>
  <li><strong>Legitimate interests</strong> (Art. 6(1)(f)): improving services, fraud prevention, direct marketing to existing clients</li>
  <li><strong>Consent</strong> (Art. 6(1)(a)): analytics cookies, marketing emails to prospects</li>
</ul>

<h2>Sharing Your Data</h2>
<p>We may share personal data with:</p>
<ul>
  <li><strong>Service providers:</strong> hosting providers, payment processors (Stripe), accounting software, project management tools — each bound by data processing agreements</li>
  <li><strong>Professional advisers:</strong> accountants, solicitors — under obligations of confidentiality</li>
  <li><strong>Regulators:</strong> HMRC, ICO, or other authorities where required by law</li>
</ul>
<p>We do not sell, rent, or trade your personal data to third parties for marketing purposes.</p>

<h2>International Transfers</h2>
<p>Some of our third-party service providers are based outside the UK. Where we transfer data internationally, we ensure appropriate safeguards are in place (e.g. UK adequacy decisions, Standard Contractual Clauses, or equivalent protections).</p>

<h2>Data Retention</h2>
<p>We retain personal data only for as long as necessary for the purposes for which it was collected:</p>
<ul>
  <li><strong>Client records &amp; financial data:</strong> {{legal.data_retention_years}} years from project completion (HMRC requirement)</li>
  <li><strong>Enquiry &amp; prospect data:</strong> 12 months from last contact, unless a contract is formed</li>
  <li><strong>Website analytics:</strong> 26 months (Google Analytics default, with IP anonymisation)</li>
</ul>
<p>After the retention period, data is securely deleted or anonymised.</p>

<h2>Your Rights</h2>
<p>Under UK GDPR, you have the right to:</p>
<ul>
  <li><strong>Access:</strong> request a copy of the personal data we hold about you</li>
  <li><strong>Rectification:</strong> ask us to correct inaccurate or incomplete data</li>
  <li><strong>Erasure:</strong> request deletion of your data where there is no compelling reason for its continued processing</li>
  <li><strong>Restriction:</strong> ask us to restrict processing in certain circumstances</li>
  <li><strong>Data portability:</strong> receive your data in a structured, commonly used, machine-readable format</li>
  <li><strong>Objection:</strong> object to processing based on legitimate interests or for direct marketing</li>
  <li><strong>Withdraw consent:</strong> at any time, without affecting the lawfulness of prior processing</li>
</ul>
<p>To exercise any right, contact us at <a href="mailto:{{legal.privacy_email}}">{{legal.privacy_email}}</a>. We will respond within one calendar month.</p>

<h2>Children's Privacy</h2>
<p>Our services are directed at businesses and professionals. We do not knowingly collect data from children under the age of 13. If you believe we have inadvertently collected such data, please contact us immediately.</p>

<h2>Contact &amp; Complaints</h2>
<p>For any data protection queries: <a href="mailto:{{legal.privacy_email}}">{{legal.privacy_email}}</a></p>
<p>If you are not satisfied with how we handle your data, you have the right to lodge a complaint with the <strong>Information Commissioner's Office (ICO)</strong>:</p>
<ul>
  <li>ICO Registration No.: {{legal.ico_number}}</li>
  <li>Website: <a href="{{legal.ico_registration_url}}" rel="noopener noreferrer">{{legal.ico_registration_url}}</a></li>
  <li>ICO helpline: 0303 123 1113</li>
</ul>
HTML,
                    'pl' => <<<'HTML'
<h2>Informacje ogólne</h2>
<p>{{legal.company_name}} ("my", "nas", "nasze") zobowiązuje się do ochrony Twoich danych osobowych. Niniejsza Informacja o ochronie prywatności wyjaśnia, w jaki sposób zbieramy, wykorzystujemy, przechowujemy i udostępniamy informacje o Tobie zgodnie z UK RODO i Ustawą o Ochronie Danych z 2018 r. (Data Protection Act 2018).</p>
<p><strong>Administrator danych:</strong> {{legal.company_name}}, spółka zarejestrowana w Anglii i Walii (numer rejestracyjny: {{legal.company_number}}), {{legal.company_address}}.</p>
<p>W sprawach ochrony danych prosimy o kontakt z osobą odpowiedzialną: {{legal.dpo_name}}, e-mail: <a href="mailto:{{legal.privacy_email}}">{{legal.privacy_email}}</a>.</p>

<h2>Jakie dane zbieramy</h2>
<p>Możemy zbierać następujące kategorie danych osobowych:</p>
<ul>
  <li><strong>Identyfikacja i kontakt:</strong> imię i nazwisko, adres e-mail, numer telefonu, adres pocztowy</li>
  <li><strong>Dane firmowe:</strong> nazwa firmy, numer KRS/Companies House, numer VAT</li>
  <li><strong>Korespondencja:</strong> wiadomości e-mail, zgłoszenia przez formularz, prośby o wsparcie</li>
  <li><strong>Dane techniczne:</strong> adres IP, typ i wersja przeglądarki, odwiedzone strony (wyłącznie za zgodą, przez pliki cookies analitycznych)</li>
  <li><strong>Dane finansowe:</strong> faktury, rekordy płatności (nie przechowujemy pełnych danych karty)</li>
  <li><strong>Dane projektowe:</strong> pliki, obrazy, treści i inne materiały dostarczone przez Ciebie na potrzeby projektu</li>
</ul>

<h2>Jak wykorzystujemy Twoje dane</h2>
<ul>
  <li>Świadczenie, zarządzanie i realizacja usług web developmentu oraz marketingu cyfrowego</li>
  <li>Przesyłanie ofert i odpowiadanie na zapytania</li>
  <li>Wystawianie faktur i obsługa płatności</li>
  <li>Komunikacja o postępach projektu i powiadamianie o zmianach</li>
  <li>Doskonalenie naszej strony i usług na podstawie zagregowanych danych analitycznych</li>
  <li>Wypełnianie obowiązków prawnych i regulacyjnych</li>
</ul>

<h2>Podstawa prawna przetwarzania</h2>
<ul>
  <li><strong>Wykonanie umowy</strong> (art. 6 ust. 1 lit. b UK RODO): przetwarzanie niezbędne do świadczenia uzgodnionych usług</li>
  <li><strong>Obowiązek prawny</strong> (art. 6 ust. 1 lit. c): dokumenty podatkowe, wymogi HMRC</li>
  <li><strong>Uzasadniony interes</strong> (art. 6 ust. 1 lit. f): doskonalenie usług, zapobieganie nadużyciom, marketing bezpośredni do istniejących klientów</li>
  <li><strong>Zgoda</strong> (art. 6 ust. 1 lit. a): cookies analityczne, e-maile marketingowe do potencjalnych klientów</li>
</ul>

<h2>Odbiorcy danych</h2>
<p>Możemy udostępniać dane osobowe:</p>
<ul>
  <li><strong>Dostawcom usług:</strong> hostingu, obsługi płatności (Stripe), oprogramowania księgowego, narzędzi do zarządzania projektami – każdy związany umową powierzenia danych</li>
  <li><strong>Doradcom zawodowym:</strong> księgowym, prawnikom – pod obowiązkiem zachowania poufności</li>
  <li><strong>Regulatorom:</strong> HMRC, ICO lub innym organom, gdy wymaga tego prawo</li>
</ul>
<p>Nie sprzedajemy, nie wynajmujemy ani nie przekazujemy Twoich danych osobowych do celów marketingowych.</p>

<h2>Przekazywanie danych poza UK</h2>
<p>Niektórzy z naszych dostawców usług mają siedzibę poza Wielką Brytanią. Zapewniamy odpowiednie zabezpieczenia (decyzje stwierdzające odpowiedni poziom ochrony, standardowe klauzule umowne lub równoważne mechanizmy).</p>

<h2>Okres przechowywania danych</h2>
<p>Przechowujemy dane osobowe tylko przez czas niezbędny do celów, dla których zostały zebrane:</p>
<ul>
  <li><strong>Dokumentacja klienta i dane finansowe:</strong> {{legal.data_retention_years}} lat od zakończenia projektu (wymóg HMRC)</li>
  <li><strong>Dane zapytań i potencjalnych klientów:</strong> 12 miesięcy od ostatniego kontaktu, chyba że zawarto umowę</li>
  <li><strong>Analityka strony:</strong> 26 miesięcy (domyślne ustawienie Google Analytics z anonimizacją IP)</li>
</ul>
<p>Po upływie okresu przechowywania dane są bezpiecznie usuwane lub anonimizowane.</p>

<h2>Twoje prawa</h2>
<p>Na podstawie UK RODO przysługują Ci następujące prawa:</p>
<ul>
  <li><strong>Dostęp:</strong> żądanie kopii danych osobowych, które o Tobie posiadamy</li>
  <li><strong>Sprostowanie:</strong> żądanie poprawienia niedokładnych lub niekompletnych danych</li>
  <li><strong>Usunięcie:</strong> żądanie usunięcia danych, gdy nie ma ważnych powodów dalszego przetwarzania</li>
  <li><strong>Ograniczenie:</strong> żądanie ograniczenia przetwarzania w określonych okolicznościach</li>
  <li><strong>Przenoszenie danych:</strong> otrzymanie danych w ustrukturyzowanym, powszechnie używanym formacie</li>
  <li><strong>Sprzeciw:</strong> sprzeciw wobec przetwarzania w oparciu o uzasadniony interes lub dla marketingu bezpośredniego</li>
  <li><strong>Wycofanie zgody:</strong> w dowolnym momencie, bez wpływu na zgodność z prawem wcześniejszego przetwarzania</li>
</ul>
<p>Aby skorzystać z przysługujących praw, napisz na adres <a href="mailto:{{legal.privacy_email}}">{{legal.privacy_email}}</a>. Odpowiemy w ciągu miesiąca kalendarzowego.</p>

<h2>Prywatność dzieci</h2>
<p>Nasze usługi skierowane są do firm i specjalistów. Nie zbieramy świadomie danych dzieci poniżej 13 roku życia. Jeśli uważasz, że przypadkowo zebraliśmy takie dane, prosimy o natychmiastowy kontakt.</p>

<h2>Kontakt i skargi</h2>
<p>W sprawach ochrony danych: <a href="mailto:{{legal.privacy_email}}">{{legal.privacy_email}}</a></p>
<p>Jeśli nie jesteś zadowolony z naszego podejścia do danych, masz prawo złożyć skargę do <strong>Urzędu Komisarza ds. Informacji (ICO)</strong>:</p>
<ul>
  <li>Numer rejestracyjny ICO: {{legal.ico_number}}</li>
  <li>Strona: <a href="{{legal.ico_registration_url}}" rel="noopener noreferrer">{{legal.ico_registration_url}}</a></li>
  <li>Infolinia ICO: 0303 123 1113</li>
</ul>
HTML,
                    'pt' => <<<'HTML'
<h2>Sobre Este Aviso</h2>
<p>A {{legal.company_name}} ("nós", "nos", "nosso") compromete-se a proteger os seus dados pessoais. Este Aviso de Privacidade explica como recolhemos, utilizamos, armazenamos e partilhamos informações sobre si, em conformidade com o RGPD do Reino Unido e a Lei de Proteção de Dados de 2018.</p>
<p><strong>Responsável pelo tratamento de dados:</strong> {{legal.company_name}}, registada em Inglaterra e no País de Gales (N.º de empresa: {{legal.company_number}}), {{legal.company_address}}.</p>
<p>Para questões de proteção de dados, contacte o nosso responsável designado: {{legal.dpo_name}}, e-mail: <a href="mailto:{{legal.privacy_email}}">{{legal.privacy_email}}</a>.</p>

<h2>Informações que Recolhemos</h2>
<p>Podemos recolher as seguintes categorias de dados pessoais:</p>
<ul>
  <li><strong>Identidade e contacto:</strong> nome, endereço de e-mail, número de telefone, morada postal</li>
  <li><strong>Dados empresariais:</strong> nome da empresa, número de registo, número de IVA</li>
  <li><strong>Comunicações:</strong> e-mails, submissões de formulários de contacto, pedidos de suporte</li>
  <li><strong>Dados técnicos:</strong> endereço IP, tipo e versão do browser, páginas visitadas (apenas com consentimento, através de cookies analíticos)</li>
  <li><strong>Dados financeiros:</strong> faturas e registos de pagamento (não armazenamos dados completos do cartão)</li>
  <li><strong>Dados de projeto:</strong> ficheiros, imagens, conteúdos e outros materiais fornecidos para a execução do projeto</li>
</ul>

<h2>Como Utilizamos as Suas Informações</h2>
<ul>
  <li>Prestação, gestão e entrega de serviços de desenvolvimento web e marketing digital</li>
  <li>Envio de propostas e resposta a pedidos de informação</li>
  <li>Emissão de faturas e processamento de pagamentos</li>
  <li>Comunicação do progresso do projeto e notificação de alterações</li>
  <li>Melhoria do nosso website e serviços através de análises agregadas</li>
  <li>Cumprimento de obrigações legais e regulamentares</li>
</ul>

<h2>Base Legal para o Tratamento</h2>
<ul>
  <li><strong>Execução de contrato</strong> (Art. 6(1)(b) RGPD UK): tratamento necessário para a prestação dos serviços acordados</li>
  <li><strong>Obrigação legal</strong> (Art. 6(1)(c)): registos fiscais, requisitos do HMRC</li>
  <li><strong>Interesses legítimos</strong> (Art. 6(1)(f)): melhoria dos serviços, prevenção de fraudes, marketing direto a clientes existentes</li>
  <li><strong>Consentimento</strong> (Art. 6(1)(a)): cookies analíticos, e-mails de marketing para potenciais clientes</li>
</ul>

<h2>Partilha dos Seus Dados</h2>
<p>Podemos partilhar dados pessoais com:</p>
<ul>
  <li><strong>Prestadores de serviços:</strong> fornecedores de alojamento, processadores de pagamento (Stripe), software de contabilidade, ferramentas de gestão de projetos — cada um vinculado por acordos de tratamento de dados</li>
  <li><strong>Consultores profissionais:</strong> contabilistas, advogados — sob obrigação de confidencialidade</li>
  <li><strong>Autoridades reguladoras:</strong> HMRC, ICO ou outras entidades quando exigido por lei</li>
</ul>
<p>Não vendemos, alugamos nem cedemos os seus dados pessoais a terceiros para fins de marketing.</p>

<h2>Transferências Internacionais</h2>
<p>Alguns dos nossos fornecedores de serviços estão localizados fora do Reino Unido. Quando transferimos dados internacionalmente, garantimos salvaguardas adequadas (decisões de adequação do Reino Unido, Cláusulas Contratuais Tipo ou proteções equivalentes).</p>

<h2>Conservação dos Dados</h2>
<p>Conservamos os dados pessoais apenas pelo tempo necessário para os fins para os quais foram recolhidos:</p>
<ul>
  <li><strong>Registos de clientes e dados financeiros:</strong> {{legal.data_retention_years}} anos após a conclusão do projeto (requisito do HMRC)</li>
  <li><strong>Dados de consultas e potenciais clientes:</strong> 12 meses a partir do último contacto, salvo se for celebrado um contrato</li>
  <li><strong>Análise do website:</strong> 26 meses (padrão do Google Analytics, com anonimização de IP)</li>
</ul>
<p>Após o período de conservação, os dados são eliminados de forma segura ou anonimizados.</p>

<h2>Os Seus Direitos</h2>
<p>Ao abrigo do RGPD do Reino Unido, tem o direito de:</p>
<ul>
  <li><strong>Acesso:</strong> solicitar uma cópia dos dados pessoais que detemos sobre si</li>
  <li><strong>Retificação:</strong> pedir-nos que corrijamos dados inexatos ou incompletos</li>
  <li><strong>Apagamento:</strong> solicitar a eliminação dos seus dados quando não existam razões imperativas para o tratamento contínuo</li>
  <li><strong>Limitação:</strong> pedir-nos que limitemos o tratamento em determinadas circunstâncias</li>
  <li><strong>Portabilidade:</strong> receber os seus dados num formato estruturado, de uso comum e legível por máquina</li>
  <li><strong>Oposição:</strong> opor-se ao tratamento com base em interesses legítimos ou para marketing direto</li>
  <li><strong>Retirar consentimento:</strong> a qualquer momento, sem afetar a licitude do tratamento anterior</li>
</ul>
<p>Para exercer qualquer direito, contacte-nos em <a href="mailto:{{legal.privacy_email}}">{{legal.privacy_email}}</a>. Responderemos no prazo de um mês civil.</p>

<h2>Privacidade das Crianças</h2>
<p>Os nossos serviços destinam-se a empresas e profissionais. Não recolhemos intencionalmente dados de crianças com menos de 13 anos. Se acreditar que recolhemos inadvertidamente tais dados, contacte-nos imediatamente.</p>

<h2>Contacto e Reclamações</h2>
<p>Para quaisquer questões de proteção de dados: <a href="mailto:{{legal.privacy_email}}">{{legal.privacy_email}}</a></p>
<p>Se não estiver satisfeito com a forma como tratamos os seus dados, tem o direito de apresentar uma reclamação junto do <strong>Information Commissioner's Office (ICO)</strong>:</p>
<ul>
  <li>N.º de registo ICO: {{legal.ico_number}}</li>
  <li>Website: <a href="{{legal.ico_registration_url}}" rel="noopener noreferrer">{{legal.ico_registration_url}}</a></li>
  <li>Linha de apoio do ICO: 0303 123 1113</li>
</ul>
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
                'effective_date' => '2025-01-01',
                'version'        => '1.0',

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
                    'en' => 'Terms and conditions governing the provision of web development and digital marketing services by {{legal.company_name}}.',
                    'pl' => 'Regulamin świadczenia usług web developmentu i marketingu cyfrowego przez {{legal.company_name}}.',
                    'pt' => 'Termos e condições que regem a prestação de serviços de desenvolvimento web e marketing digital pela {{legal.company_name}}.',
                ],
                'content' => [
                    'en' => <<<'HTML'
<h2>About These Terms</h2>
<p>These Terms and Conditions ("Terms") govern all services provided by <strong>{{legal.company_name}}</strong> (Company No. {{legal.company_number}}, VAT No. {{legal.vat_number}}), registered at {{legal.company_address}} ("the Company", "we", "us") to our clients ("Client", "you").</p>
<p>By engaging our services, signing a project agreement, or accepting a quote, you agree to be bound by these Terms, which are governed by the laws of England and Wales and comply with the Consumer Rights Act 2015 and Consumer Contracts (Information, Cancellation and Additional Charges) Regulations 2013 where applicable.</p>

<h2>Our Services</h2>
<p>We provide web design, web development, e-commerce solutions, digital marketing, SEO, website maintenance, hosting management, and related digital services. The specific scope is outlined in each individual project quotation or service agreement.</p>

<h2>Quotations</h2>
<p>All written quotations are valid for 30 days from the date of issue unless otherwise stated. Quotations are not binding until both parties have signed a project agreement or the Client has confirmed acceptance in writing. Any changes to project requirements after acceptance may require a revised quotation.</p>

<h2>Project Start &amp; Deposit</h2>
<p>Projects commence upon receipt of a signed project agreement and the agreed deposit payment. The deposit is <strong>{{legal.deposit_percent}}%</strong> of the total quoted project value and is non-refundable once project work has commenced, reflecting costs already incurred in planning and resource allocation.</p>

<h2>Payment</h2>
<p>Invoices are issued in accordance with the agreed payment schedule and are due within <strong>{{legal.payment_terms_days}} days</strong> of the invoice date.</p>
<ul>
  <li>Interim milestone payments are due before the corresponding project phase commences</li>
  <li>Final payment is due before the website goes live or final files are delivered</li>
  <li>Late payments may incur statutory interest under the Late Payment of Commercial Debts (Interest) Act 1998 at 8% above the Bank of England base rate</li>
  <li>Work may be suspended on accounts overdue by more than 30 days</li>
  <li>Ownership of deliverables remains with the Company until all outstanding invoices are paid in full</li>
</ul>
<p>Accepted payment methods: bank transfer (BACS/CHAPS), debit/credit card via Stripe. All prices are exclusive of VAT unless stated otherwise.</p>

<h2>Scope &amp; Variations</h2>
<p>The agreed project scope is defined in the signed quotation or project brief. Any additions, changes, or scope creep will be communicated in writing as a Change Request. Change Requests may affect the project timeline and cost. The Client must approve all Change Requests in writing before additional work begins.</p>

<h2>Timelines &amp; Delays</h2>
<p>Timely delivery is contingent on the Client providing content, assets, feedback, and approvals promptly. Failure to provide required materials within 5 working days of the agreed deadline may result in project delays or additional charges. We are not liable for delays caused by the Client or circumstances beyond our reasonable control (force majeure).</p>

<h2>Intellectual Property</h2>
<p>Upon receipt of full and final payment, copyright in the custom deliverables created specifically for the Client transfers to the Client. This does not include:</p>
<ul>
  <li>Third-party software, frameworks, or plugins (licensed under their own terms)</li>
  <li>Stock photography or licensed assets (transferred where permitted by the applicable licence)</li>
  <li>Generic components and code libraries developed by the Company as part of its standard toolkit</li>
</ul>
<p>The Company retains the right to display completed work in its portfolio unless the Client requests otherwise in writing.</p>

<h2>Client Obligations</h2>
<ul>
  <li>Provide accurate, complete, and up-to-date content, images, and materials required for the project</li>
  <li>Ensure all materials provided do not infringe third-party intellectual property rights</li>
  <li>Designate a primary contact with authority to approve work on behalf of the organisation</li>
  <li>Respond to requests for feedback, approvals, and information within agreed timeframes</li>
  <li>Ensure the completed website and its content complies with relevant legal requirements (GDPR, Consumer Rights Act, advertising standards)</li>
</ul>

<h2>Cancellation Rights</h2>
<p>Where these Terms constitute a "distance contract" under the Consumer Contracts (Information, Cancellation and Additional Charges) Regulations 2013, you have the right to cancel within 14 days without giving any reason. However, if you request that work begins before the end of the 14-day period, your right to cancel is reduced pro-rata to work already performed. Business clients contracting for business purposes do not benefit from statutory cancellation rights.</p>
<p>After the statutory period (if applicable), cancellations are subject to payment for all work completed to date plus a reasonable cancellation fee to cover sunk costs and lost revenue.</p>

<h2>Limitation of Liability</h2>
<p>To the fullest extent permitted by law, the Company's total aggregate liability shall not exceed the total fees paid for the specific service giving rise to the claim. The Company is not liable for loss of profits, revenue, data, third-party service outages, errors in Client-provided materials, or search engine ranking changes.</p>
<p>Nothing in these Terms excludes liability for death or personal injury caused by negligence, fraud, or any other liability that cannot be excluded by law.</p>

<h2>Governing Law</h2>
<p>These Terms are governed by the laws of England and Wales. Any disputes shall be subject to the exclusive jurisdiction of the courts of England and Wales.</p>
<p>Contact: <a href="mailto:{{legal.company_email}}">{{legal.company_email}}</a> | {{legal.company_phone}}</p>
HTML,
                    'pl' => <<<'HTML'
<h2>Informacje ogólne</h2>
<p>Niniejszy Regulamin ("Regulamin") reguluje wszelkie usługi świadczone przez <strong>{{legal.company_name}}</strong> (numer rejestracyjny: {{legal.company_number}}, numer VAT: {{legal.vat_number}}), zarejestrowaną pod adresem: {{legal.company_address}} ("Firma", "my", "nas"), na rzecz naszych klientów ("Klient", "Ty").</p>
<p>Korzystając z naszych usług, podpisując umowę projektową lub akceptując ofertę, wyrażasz zgodę na niniejszy Regulamin, który podlega prawu Anglii i Walii oraz jest zgodny z Consumer Rights Act 2015 i Rozporządzeniem Consumer Contracts z 2013 r., o ile ma to zastosowanie.</p>

<h2>Zakres usług</h2>
<p>Świadczymy usługi projektowania stron internetowych, web developmentu, rozwiązań e-commerce, marketingu cyfrowego, SEO, utrzymania stron, zarządzania hostingiem i pokrewnych usług cyfrowych. Szczegółowy zakres określony jest w indywidualnych wycenach lub umowach projektowych.</p>

<h2>Oferty</h2>
<p>Wszystkie pisemne oferty są ważne przez 30 dni od daty wystawienia, chyba że zaznaczono inaczej. Oferty nie są wiążące do momentu podpisania umowy projektowej lub pisemnego potwierdzenia przez Klienta. Wszelkie zmiany wymagań po akceptacji mogą wymagać zmiany wyceny.</p>

<h2>Rozpoczęcie projektu i zaliczka</h2>
<p>Projekt rozpoczyna się po otrzymaniu podpisanej umowy projektowej oraz wpłaceniu uzgodnionej zaliczki. Zaliczka wynosi <strong>{{legal.deposit_percent}}%</strong> łącznej wartości projektu i jest bezzwrotna po rozpoczęciu prac, co odzwierciedla koszty poniesione w fazie planowania i alokacji zasobów.</p>

<h2>Płatności</h2>
<p>Faktury są wystawiane zgodnie z uzgodnionym harmonogramem płatności i wymagalne w ciągu <strong>{{legal.payment_terms_days}} dni</strong> od daty wystawienia faktury.</p>
<ul>
  <li>Płatności za kolejne etapy są wymagalne przed rozpoczęciem danej fazy projektu</li>
  <li>Płatność końcowa jest wymagalna przed uruchomieniem strony lub dostarczeniem finalnych plików</li>
  <li>Opóźnienia mogą skutkować naliczeniem odsetek ustawowych</li>
  <li>Prace mogą zostać wstrzymane przy zaległości powyżej 30 dni</li>
  <li>Własność rezultatów prac pozostaje przy Firmie do całkowitego uregulowania należności</li>
</ul>
<p>Akceptowane formy płatności: przelew bankowy, karta debetowa/kredytowa przez Stripe. Wszystkie ceny są cenami netto (bez VAT), chyba że zaznaczono inaczej.</p>

<h2>Zakres i zmiany</h2>
<p>Uzgodniony zakres projektu określony jest w podpisanej ofercie lub briefie projektowym. Wszelkie uzupełnienia lub zmiany przekazywane są na piśmie w formie Zlecenia Zmiany. Zlecenia Zmiany mogą wpłynąć na harmonogram i koszt projektu. Klient musi zatwierdzić wszystkie zlecenia zmiany na piśmie przed rozpoczęciem dodatkowych prac.</p>

<h2>Terminy i opóźnienia</h2>
<p>Terminowość realizacji zależy od sprawnego dostarczania przez Klienta treści, materiałów, informacji zwrotnych i zatwierdzeń. Niedostarczenie wymaganych materiałów w ciągu 5 dni roboczych od umówionego terminu może skutkować opóźnieniami lub dodatkowymi kosztami. Nie ponosimy odpowiedzialności za opóźnienia spowodowane przez Klienta lub siłę wyższą.</p>

<h2>Własność intelektualna</h2>
<p>Po uiszczeniu pełnej i ostatecznej płatności prawa autorskie do niestandardowych rezultatów stworzonych specjalnie dla Klienta przechodzą na Klienta. Nie obejmuje to:</p>
<ul>
  <li>Oprogramowania, frameworków i wtyczek stron trzecich (licencjonowanych na własnych warunkach)</li>
  <li>Zasobów stockowych lub licencjonowanych (przenoszonych, gdy zezwala na to licencja)</li>
  <li>Ogólnych komponentów i bibliotek kodu opracowanych przez Firmę jako część jej standardowego zestawu narzędzi</li>
</ul>
<p>Firma zastrzega sobie prawo do prezentacji ukończonych prac w portfolio, chyba że Klient pisemnie zażąda inaczej.</p>

<h2>Obowiązki Klienta</h2>
<ul>
  <li>Dostarczanie dokładnych, kompletnych i aktualnych treści, zdjęć i materiałów wymaganych do projektu</li>
  <li>Zapewnienie, że dostarczone materiały nie naruszają praw własności intelektualnej osób trzecich</li>
  <li>Wyznaczenie głównego kontaktu z upoważnieniem do zatwierdzania prac w imieniu organizacji</li>
  <li>Odpowiadanie na prośby o informacje zwrotne i zatwierdzenia w ustalonych terminach</li>
  <li>Zapewnienie zgodności ukończonej strony z obowiązującymi przepisami prawa</li>
</ul>

<h2>Prawo do odstąpienia od umowy</h2>
<p>W przypadku gdy niniejszy Regulamin stanowi "umowę zawartą na odległość" w rozumieniu Rozporządzenia Consumer Contracts z 2013 r., masz prawo do odstąpienia od umowy w ciągu 14 dni bez podania przyczyny. Jeśli jednak zażądasz rozpoczęcia prac przed upływem 14-dniowego okresu, Twoje prawo do odstąpienia jest proporcjonalnie ograniczone do wykonanych już prac. Klienci biznesowi zawierający umowy w celach biznesowych nie korzystają z ustawowych praw do odstąpienia.</p>
<p>Po upływie ustawowego okresu rezygnacji (jeśli dotyczy), anulowanie podlega opłacie za wszystkie ukończone do tej pory prace oraz rozsądnej opłacie anulacyjnej.</p>

<h2>Ograniczenie odpowiedzialności</h2>
<p>W najszerszym zakresie dozwolonym przez prawo łączna odpowiedzialność Firmy wobec Klienta nie przekroczy łącznej wartości opłat zapłaconych za konkretną usługę. Firma nie ponosi odpowiedzialności za utratę zysku, danych, awarie usług stron trzecich, błędy w materiałach Klienta ani zmiany w pozycjonowaniu.</p>
<p>Nic w niniejszym Regulaminie nie wyłącza odpowiedzialności za śmierć lub uszkodzenie ciała spowodowane zaniedbaniem ani żadnej innej odpowiedzialności, której nie można wyłączyć przepisami prawa.</p>

<h2>Prawo właściwe</h2>
<p>Niniejszy Regulamin podlega prawu Anglii i Walii. Wszelkie spory podlegają wyłącznej jurysdykcji sądów angielskich i walijskich.</p>
<p>Kontakt: <a href="mailto:{{legal.company_email}}">{{legal.company_email}}</a> | {{legal.company_phone}}</p>
HTML,
                    'pt' => <<<'HTML'
<h2>Sobre Estes Termos</h2>
<p>Estes Termos e Condições ("Termos") regem todos os serviços prestados pela <strong>{{legal.company_name}}</strong> (N.º de empresa: {{legal.company_number}}, N.º de IVA: {{legal.vat_number}}), registada em {{legal.company_address}} ("a Empresa", "nós", "nos"), aos nossos clientes ("Cliente", "você").</p>
<p>Ao contratar os nossos serviços, assinar um acordo de projeto ou aceitar uma proposta, concorda com estes Termos, regidos pelas leis de Inglaterra e do País de Gales e em conformidade com o Consumer Rights Act 2015 e o Regulamento de Contratos com Consumidores de 2013, onde aplicável.</p>

<h2>Os Nossos Serviços</h2>
<p>Prestamos serviços de design web, desenvolvimento web, soluções de e-commerce, marketing digital, SEO, manutenção de websites, gestão de alojamento e serviços digitais relacionados. O âmbito específico é descrito em cada proposta individual ou acordo de projeto.</p>

<h2>Propostas</h2>
<p>Todas as propostas escritas são válidas por 30 dias a partir da data de emissão, salvo indicação em contrário. As propostas não são vinculativas até que ambas as partes assinem um acordo de projeto ou o Cliente confirme a aceitação por escrito. Alterações nos requisitos do projeto após a aceitação podem exigir uma proposta revista.</p>

<h2>Início do Projeto e Adiantamento</h2>
<p>Os projetos começam após a receção de um acordo de projeto assinado e do pagamento do adiantamento acordado. O adiantamento é de <strong>{{legal.deposit_percent}}%</strong> do valor total do projeto cotado e não é reembolsável após o início do trabalho, refletindo os custos já incorridos no planeamento e alocação de recursos.</p>

<h2>Pagamento</h2>
<p>As faturas são emitidas de acordo com o calendário de pagamentos acordado e são devidas no prazo de <strong>{{legal.payment_terms_days}} dias</strong> a partir da data da fatura.</p>
<ul>
  <li>Os pagamentos de marcos intermédios vencem antes do início da fase de projeto correspondente</li>
  <li>O pagamento final vence antes da publicação do website ou da entrega dos ficheiros finais</li>
  <li>Os atrasos podem incorrer em juros legais ao abrigo da lei de juros sobre dívidas comerciais</li>
  <li>Os trabalhos podem ser suspensos em contas com mais de 30 dias de atraso</li>
  <li>A propriedade dos produtos permanece na Empresa até que todas as faturas em aberto sejam pagas na íntegra</li>
</ul>
<p>Métodos de pagamento aceites: transferência bancária, cartão de débito/crédito via Stripe. Todos os preços são líquidos de IVA, salvo indicação em contrário.</p>

<h2>Âmbito e Variações</h2>
<p>O âmbito do projeto acordado é definido na proposta assinada ou no briefing do projeto. Quaisquer adições ou alterações serão comunicadas por escrito como Pedido de Alteração. Os Pedidos de Alteração podem afetar o prazo e o custo do projeto. O Cliente deve aprovar todos os Pedidos de Alteração por escrito antes de os trabalhos adicionais serem iniciados.</p>

<h2>Prazos e Atrasos</h2>
<p>A entrega atempada está condicionada ao fornecimento atempado de conteúdos, ativos, feedback e aprovações por parte do Cliente. O não fornecimento dos materiais necessários no prazo de 5 dias úteis a contar do prazo acordado pode resultar em atrasos ou encargos adicionais. Não nos responsabilizamos por atrasos causados pelo Cliente ou por força maior.</p>

<h2>Propriedade Intelectual</h2>
<p>Após a receção do pagamento integral e final, os direitos de autor sobre os produtos personalizados criados especificamente para o Cliente são transferidos para o Cliente. Isto não inclui:</p>
<ul>
  <li>Software, frameworks ou plugins de terceiros (licenciados sob os seus próprios termos)</li>
  <li>Fotografias de stock ou ativos licenciados (transferidos quando permitido pela licença aplicável)</li>
  <li>Componentes genéricos e bibliotecas de código desenvolvidos pela Empresa como parte do seu kit de ferramentas padrão</li>
</ul>
<p>A Empresa reserva-se o direito de apresentar o trabalho concluído no seu portefólio, a menos que o Cliente solicite o contrário por escrito.</p>

<h2>Obrigações do Cliente</h2>
<ul>
  <li>Fornecer conteúdo, imagens e materiais precisos, completos e atualizados necessários para o projeto</li>
  <li>Garantir que todos os materiais fornecidos não violam direitos de propriedade intelectual de terceiros</li>
  <li>Designar um contacto principal com autoridade para aprovar trabalhos em nome da organização</li>
  <li>Responder a pedidos de feedback, aprovações e informações dentro dos prazos acordados</li>
  <li>Garantir que o website concluído e o seu conteúdo cumpre os requisitos legais aplicáveis</li>
</ul>

<h2>Direito de Cancelamento</h2>
<p>Quando estes Termos constituem um "contrato à distância" nos termos do Regulamento de Contratos com Consumidores de 2013, tem o direito de cancelar no prazo de 14 dias sem necessidade de justificação. No entanto, se solicitar que os trabalhos comecem antes do término do período de 14 dias, o seu direito de cancelamento é reduzido proporcionalmente ao trabalho já realizado. Os clientes empresariais não beneficiam de direitos estatutários de cancelamento.</p>
<p>Após o período de cancelamento legal (se aplicável), os cancelamentos estão sujeitos ao pagamento de todos os trabalhos concluídos até à data, acrescido de uma taxa de cancelamento razoável.</p>

<h2>Limitação de Responsabilidade</h2>
<p>Na máxima medida permitida por lei, a responsabilidade total agregada da Empresa não excederá os honorários totais pagos pelo serviço específico. A Empresa não é responsável por perda de lucros, dados, interrupções de serviços de terceiros, erros em materiais fornecidos pelo Cliente ou alterações no posicionamento em motores de pesquisa.</p>
<p>Nada nestes Termos exclui responsabilidade por morte ou lesões corporais causadas por negligência, fraude ou qualquer outra responsabilidade que não possa ser excluída por lei.</p>

<h2>Lei Aplicável</h2>
<p>Estes Termos são regidos pelas leis de Inglaterra e do País de Gales. Quaisquer litígios estão sujeitos à jurisdição exclusiva dos tribunais de Inglaterra e do País de Gales.</p>
<p>Contacte-nos: <a href="mailto:{{legal.company_email}}">{{legal.company_email}}</a> | {{legal.company_phone}}</p>
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
                'effective_date' => '2025-01-01',
                'version'        => '1.0',

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
<h2>What Are Cookies?</h2>
<p>Cookies are small text files placed on your device (computer, tablet, or smartphone) when you visit a website. They help the website function correctly, remember your preferences, and provide information to website owners about how visitors use the site.</p>
<p>This Cookie Policy explains which cookies {{legal.company_name}} uses on this website, why we use them, and how you can manage them.</p>

<h2>Cookies We Use</h2>
<h3>Strictly Necessary Cookies</h3>
<p>These cookies are essential for the website to operate and cannot be switched off. They include:</p>
<ul>
  <li><strong>Session cookies:</strong> maintain your session while you browse the site</li>
  <li><strong>CSRF token cookies:</strong> protect against cross-site request forgery attacks</li>
  <li><strong>Cookie consent cookie:</strong> stores your cookie preferences</li>
</ul>

<h3>Analytics Cookies</h3>
<p>With your consent, we use <strong>Google Analytics 4 (GA4)</strong> to understand how visitors interact with our website. These cookies collect anonymous information about pages visited, time on site, and referral sources.</p>
<ul>
  <li>IP addresses are anonymised before storage</li>
  <li>Data is processed by Google LLC under a Data Processing Agreement</li>
  <li>Opt out at <a href="https://tools.google.com/dlpage/gaoptout" rel="noopener noreferrer">tools.google.com/dlpage/gaoptout</a></li>
</ul>

<h3>Functional Cookies</h3>
<p>These cookies enable enhanced functionality such as remembering your preferred language. They are only placed with your consent.</p>

<h3>Marketing &amp; Targeting Cookies</h3>
<p>We only place marketing cookies (such as Google Ads conversion tracking or LinkedIn Insight Tag) with your explicit consent. You can withdraw consent at any time via our cookie preferences panel.</p>

<h2>Third-Party Cookies</h2>
<p>Some cookies on our site are set by third-party services. We have no direct control over these. Services we may use include:</p>
<ul>
  <li><strong>Google Analytics</strong> (analytics) — <a href="https://policies.google.com/privacy" rel="noopener noreferrer">Google Privacy Policy</a></li>
  <li><strong>Stripe</strong> (payment processing) — <a href="https://stripe.com/gb/privacy" rel="noopener noreferrer">Stripe Privacy Policy</a></li>
</ul>

<h2>Managing Your Preferences</h2>
<p>You can manage your cookie preferences at any time through:</p>
<ul>
  <li><strong>Our cookie consent banner:</strong> click "Manage Preferences" to update your choices</li>
  <li><strong>Your browser settings:</strong> most browsers allow you to block or delete cookies. Note that blocking all cookies may affect website functionality.</li>
</ul>
<p>Browser guides: <a href="https://support.google.com/chrome/answer/95647" rel="noopener noreferrer">Chrome</a> | <a href="https://support.mozilla.org/en-US/kb/cookies-information-websites-store-on-your-computer" rel="noopener noreferrer">Firefox</a> | <a href="https://support.apple.com/en-gb/guide/safari/manage-cookies-sfri11471" rel="noopener noreferrer">Safari</a> | <a href="https://support.microsoft.com/en-us/windows/delete-and-manage-cookies" rel="noopener noreferrer">Edge</a></p>

<h2>Updates to This Policy</h2>
<p>We may update this Cookie Policy from time to time to reflect changes in technology, legislation, or our data practices. We will notify you of significant changes through our cookie consent banner.</p>

<h2>Contact</h2>
<p>Questions about our use of cookies: <a href="mailto:{{legal.cookie_policy_email}}">{{legal.cookie_policy_email}}</a></p>
HTML,
                    'pl' => <<<'HTML'
<h2>Czym są pliki cookies?</h2>
<p>Pliki cookies to małe pliki tekstowe umieszczane na Twoim urządzeniu (komputerze, tablecie lub smartfonie) podczas odwiedzania strony internetowej. Pomagają one w prawidłowym funkcjonowaniu strony, zapamiętywaniu preferencji oraz dostarczaniu właścicielom informacji o sposobie korzystania z witryny.</p>
<p>Niniejsza Polityka cookies wyjaśnia, jakich plików cookies używa {{legal.company_name}} na tej stronie, dlaczego je stosujemy oraz jak możesz nimi zarządzać.</p>

<h2>Pliki cookies, których używamy</h2>
<h3>Niezbędne pliki cookies</h3>
<p>Te pliki cookies są konieczne do działania strony i nie mogą zostać wyłączone. Obejmują:</p>
<ul>
  <li><strong>Cookies sesji:</strong> utrzymują Twoją sesję podczas przeglądania strony</li>
  <li><strong>Tokeny CSRF:</strong> chronią przed atakami typu cross-site request forgery</li>
  <li><strong>Cookie zgody:</strong> przechowują Twoje preferencje dotyczące cookies</li>
</ul>

<h3>Analityczne pliki cookies</h3>
<p>Za Twoją zgodą używamy <strong>Google Analytics 4 (GA4)</strong> do analizy sposobu korzystania z naszej strony. Te pliki cookies zbierają anonimowe informacje o odwiedzanych stronach, czasie na stronie i źródłach ruchu.</p>
<ul>
  <li>Adresy IP są anonimizowane przed przechowaniem</li>
  <li>Dane są przetwarzane przez Google LLC na podstawie Umowy o Przetwarzaniu Danych</li>
  <li>Możesz zrezygnować ze śledzenia na <a href="https://tools.google.com/dlpage/gaoptout" rel="noopener noreferrer">tools.google.com/dlpage/gaoptout</a></li>
</ul>

<h3>Funkcjonalne pliki cookies</h3>
<p>Te pliki cookies umożliwiają rozszerzoną funkcjonalność, na przykład zapamiętywanie preferowanego języka. Są umieszczane wyłącznie za Twoją zgodą.</p>

<h3>Marketingowe pliki cookies</h3>
<p>Marketingowe pliki cookies (np. śledzenie konwersji Google Ads, LinkedIn Insight Tag) umieszczamy wyłącznie za Twoją wyraźną zgodą. Możesz wycofać zgodę w dowolnym momencie przez panel preferencji cookies.</p>

<h2>Cookies podmiotów trzecich</h2>
<p>Niektóre pliki cookies na naszej stronie są ustawiane przez usługi stron trzecich. Nie mamy nad nimi bezpośredniej kontroli. Usługi, z których możemy korzystać:</p>
<ul>
  <li><strong>Google Analytics</strong> (analityka) — <a href="https://policies.google.com/privacy" rel="noopener noreferrer">Polityka prywatności Google</a></li>
  <li><strong>Stripe</strong> (obsługa płatności) — <a href="https://stripe.com/gb/privacy" rel="noopener noreferrer">Polityka prywatności Stripe</a></li>
</ul>

<h2>Zarządzanie preferencjami</h2>
<p>Możesz zarządzać swoimi preferencjami dotyczącymi plików cookies w dowolnym momencie przez:</p>
<ul>
  <li><strong>Nasz baner zgody:</strong> kliknij „Zarządzaj preferencjami", aby zaktualizować swoje wybory</li>
  <li><strong>Ustawienia przeglądarki:</strong> większość przeglądarek umożliwia blokowanie lub usuwanie cookies. Uwaga: blokowanie wszystkich cookies może wpłynąć na funkcjonalność strony.</li>
</ul>
<p>Przewodniki: <a href="https://support.google.com/chrome/answer/95647" rel="noopener noreferrer">Chrome</a> | <a href="https://support.mozilla.org/pl/kb/ciasteczka" rel="noopener noreferrer">Firefox</a> | <a href="https://support.apple.com/pl-pl/guide/safari" rel="noopener noreferrer">Safari</a> | <a href="https://support.microsoft.com/pl-pl/windows/usuwanie-pliki-cookie-i-zarzadzanie-nimi" rel="noopener noreferrer">Edge</a></p>

<h2>Aktualizacje niniejszej polityki</h2>
<p>Możemy okresowo aktualizować niniejszą Politykę cookies, aby odzwierciedlić zmiany w technologii, przepisach lub naszych praktykach. O istotnych zmianach poinformujemy za pośrednictwem banera zgody na cookies.</p>

<h2>Kontakt</h2>
<p>Pytania dotyczące plików cookies: <a href="mailto:{{legal.cookie_policy_email}}">{{legal.cookie_policy_email}}</a></p>
HTML,
                    'pt' => <<<'HTML'
<h2>O Que São Cookies?</h2>
<p>Os cookies são pequenos ficheiros de texto colocados no seu dispositivo (computador, tablet ou smartphone) quando visita um website. Ajudam o website a funcionar corretamente, a lembrar as suas preferências e fornecem informações aos proprietários do website sobre a forma como os visitantes utilizam o site.</p>
<p>Esta Política de Cookies explica quais os cookies que a {{legal.company_name}} utiliza neste website, por que os utilizamos e como pode geri-los.</p>

<h2>Cookies que Utilizamos</h2>
<h3>Cookies Estritamente Necessários</h3>
<p>Estes cookies são essenciais para o funcionamento do website e não podem ser desativados. Incluem:</p>
<ul>
  <li><strong>Cookies de sessão:</strong> mantêm a sua sessão enquanto navega no site</li>
  <li><strong>Tokens CSRF:</strong> protegem contra ataques de falsificação de pedidos entre sites</li>
  <li><strong>Cookie de consentimento:</strong> armazena as suas preferências de cookies</li>
</ul>

<h3>Cookies Analíticos</h3>
<p>Com o seu consentimento, utilizamos o <strong>Google Analytics 4 (GA4)</strong> para compreender como os visitantes interagem com o nosso website. Estes cookies recolhem informações anónimas sobre páginas visitadas, tempo no site e fontes de referência.</p>
<ul>
  <li>Os endereços IP são anonimizados antes do armazenamento</li>
  <li>Os dados são processados pela Google LLC ao abrigo de um Acordo de Tratamento de Dados</li>
  <li>Pode optar por não participar em <a href="https://tools.google.com/dlpage/gaoptout" rel="noopener noreferrer">tools.google.com/dlpage/gaoptout</a></li>
</ul>

<h3>Cookies Funcionais</h3>
<p>Estes cookies permitem funcionalidades melhoradas, como lembrar a sua preferência de idioma. Só são colocados com o seu consentimento.</p>

<h3>Cookies de Marketing e Segmentação</h3>
<p>Só colocamos cookies de marketing (como o rastreamento de conversões do Google Ads ou o LinkedIn Insight Tag) com o seu consentimento explícito. Pode retirar o consentimento a qualquer momento através do nosso painel de preferências de cookies.</p>

<h2>Cookies de Terceiros</h2>
<p>Alguns cookies no nosso site são definidos por serviços de terceiros. Não temos controlo direto sobre estes. Os serviços que podemos utilizar incluem:</p>
<ul>
  <li><strong>Google Analytics</strong> (análise) — <a href="https://policies.google.com/privacy" rel="noopener noreferrer">Política de Privacidade da Google</a></li>
  <li><strong>Stripe</strong> (processamento de pagamentos) — <a href="https://stripe.com/gb/privacy" rel="noopener noreferrer">Política de Privacidade do Stripe</a></li>
</ul>

<h2>Gerir as Suas Preferências</h2>
<p>Pode gerir as suas preferências de cookies a qualquer momento através de:</p>
<ul>
  <li><strong>O nosso banner de consentimento de cookies:</strong> clique em "Gerir Preferências" para atualizar as suas escolhas</li>
  <li><strong>As definições do seu browser:</strong> a maioria dos browsers permite bloquear ou eliminar cookies. Nota: bloquear todos os cookies pode afetar a funcionalidade do website.</li>
</ul>
<p>Guias: <a href="https://support.google.com/chrome/answer/95647" rel="noopener noreferrer">Chrome</a> | <a href="https://support.mozilla.org/pt-PT/kb/cookies-informacao-que-os-websites-guardam-no-seu-computador" rel="noopener noreferrer">Firefox</a> | <a href="https://support.apple.com/pt-pt/guide/safari" rel="noopener noreferrer">Safari</a> | <a href="https://support.microsoft.com/pt-pt/windows/eliminar-e-gerir-cookies" rel="noopener noreferrer">Edge</a></p>

<h2>Atualizações a Esta Política</h2>
<p>Podemos atualizar esta Política de Cookies periodicamente para refletir alterações na tecnologia, legislação ou nas nossas práticas de dados. Notificá-lo-emos de alterações significativas através do nosso banner de consentimento de cookies.</p>

<h2>Contacto</h2>
<p>Questões sobre a nossa utilização de cookies: <a href="mailto:{{legal.cookie_policy_email}}">{{legal.cookie_policy_email}}</a></p>
HTML,
                ],
            ],

            // ─── Accessibility ────────────────────────────────────────────────
            [
                'slug'           => 'accessibility',
                'status'         => 'published',
                'type'           => 'accessibility',
                'show_in_footer' => true,
                'sort_order'     => 4,
                'created_by'     => $admin?->id,
                'published_at'   => now()->subMonths(6),
                'effective_date' => '2025-01-01',
                'version'        => '1.0',

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
                    'en' => '{{legal.company_name}}\'s commitment to digital accessibility for all users, in line with WCAG 2.1 Level AA.',
                    'pl' => 'Zobowiązanie {{legal.company_name}} do zapewnienia cyfrowej dostępności dla wszystkich użytkowników zgodnie ze standardem WCAG 2.1 AA.',
                    'pt' => 'Compromisso da {{legal.company_name}} com a acessibilidade digital para todos os utilizadores, em conformidade com o WCAG 2.1 Nível AA.',
                ],
                'content' => [
                    'en' => <<<'HTML'
<h2>Our Commitment</h2>
<p>{{legal.company_name}} is committed to making this website accessible to all users, regardless of disability or the technology they use. We aim to comply with <strong>Web Content Accessibility Guidelines (WCAG) 2.1 Level AA</strong>, as required by the Equality Act 2010.</p>
<p>When we build and maintain websites for our clients, accessibility is a core part of our process — and we apply those same standards to our own website.</p>

<h2>Technical Approach &amp; Standards</h2>
<p>This website has been designed and developed to meet the following accessibility criteria:</p>
<ul>
  <li><strong>Semantic HTML:</strong> meaningful heading hierarchy (H1–H6), landmark regions (main, nav, footer, aside)</li>
  <li><strong>Keyboard navigation:</strong> all interactive elements are reachable and operable by keyboard alone</li>
  <li><strong>Screen reader compatibility:</strong> appropriate ARIA labels, roles, and properties used throughout</li>
  <li><strong>Colour contrast:</strong> all text meets WCAG 2.1 AA minimum contrast ratios (4.5:1 for normal text, 3:1 for large text)</li>
  <li><strong>Images:</strong> all informative images include descriptive alt text; decorative images use empty alt attributes</li>
  <li><strong>Forms:</strong> all form inputs have visible, associated labels and clear error messages</li>
  <li><strong>Text resize:</strong> the website remains fully functional when text is resized up to 200%</li>
  <li><strong>Motion:</strong> animations respect the <code>prefers-reduced-motion</code> media query</li>
  <li><strong>Focus indicators:</strong> visible focus rings are present on all focusable elements</li>
  <li><strong>Language attributes:</strong> the <code>lang</code> attribute is set correctly on the document</li>
</ul>

<h2>Known Limitations</h2>
<p>While we strive for full WCAG 2.1 AA compliance, you may encounter the following known issues:</p>
<ul>
  <li>Some embedded third-party content (such as maps or video players) may not fully meet accessibility standards — we are working with providers to address these</li>
  <li>PDF documents linked from this site may not all be fully accessible — we are working to remediate these</li>
</ul>
<p>We are continually improving. If you find an issue not listed here, please let us know.</p>

<h2>Feedback &amp; Contact</h2>
<p>We welcome feedback on the accessibility of this website. If you experience any accessibility barriers, please contact us:</p>
<ul>
  <li>Email: <a href="mailto:{{legal.complaints_email}}">{{legal.complaints_email}}</a></li>
  <li>Phone: {{legal.complaints_phone}}</li>
</ul>
<p>We aim to acknowledge accessibility complaints within <strong>{{legal.response_days}} working days</strong> and provide a full response within 20 working days.</p>

<h2>Enforcement Procedure</h2>
<p>If you have contacted us and are not satisfied with our response, you may contact the <strong>Equality Advisory and Support Service (EASS)</strong>:</p>
<ul>
  <li>Website: <a href="https://www.equalityadvisoryservice.com" rel="noopener noreferrer">www.equalityadvisoryservice.com</a></li>
  <li>Telephone: 0808 800 0082</li>
</ul>
HTML,
                    'pl' => <<<'HTML'
<h2>Nasze zobowiązanie</h2>
<p>{{legal.company_name}} zobowiązuje się do zapewnienia dostępności tej strony internetowej dla wszystkich użytkowników, niezależnie od niepełnosprawności lub stosowanej technologii. Dążymy do zgodności z <strong>Wytycznymi dla dostępności treści internetowych (WCAG) 2.1 poziom AA</strong>, zgodnie z wymogami Ustawy o równości z 2010 r. (Equality Act 2010).</p>
<p>Kiedy budujemy i utrzymujemy strony internetowe dla naszych klientów, dostępność jest podstawowym elementem naszego procesu — i stosujemy te same standardy na własnej stronie.</p>

<h2>Podejście techniczne i standardy</h2>
<p>Ta strona została zaprojektowana i opracowana z myślą o spełnieniu następujących kryteriów dostępności:</p>
<ul>
  <li><strong>Semantyczny HTML:</strong> sensowna hierarchia nagłówków (H1–H6), regiony orientacyjne (main, nav, footer, aside)</li>
  <li><strong>Nawigacja klawiaturą:</strong> wszystkie elementy interaktywne są dostępne i obsługiwalne wyłącznie za pomocą klawiatury</li>
  <li><strong>Zgodność z czytnikami ekranu:</strong> odpowiednie etykiety ARIA, role i właściwości używane w całej witrynie</li>
  <li><strong>Kontrast kolorów:</strong> cały tekst spełnia minimalne wskaźniki kontrastu WCAG 2.1 AA (4,5:1 dla zwykłego tekstu, 3:1 dla dużego tekstu)</li>
  <li><strong>Obrazy:</strong> wszystkie obrazy informacyjne zawierają opisowy tekst alternatywny; obrazy dekoracyjne mają puste atrybuty alt</li>
  <li><strong>Formularze:</strong> wszystkie pola formularzy mają widoczne, powiązane etykiety i jasne komunikaty o błędach</li>
  <li><strong>Zmiana rozmiaru tekstu:</strong> strona zachowuje pełną funkcjonalność po powiększeniu tekstu do 200%</li>
  <li><strong>Animacje:</strong> animacje respektują zapytanie medialne <code>prefers-reduced-motion</code></li>
  <li><strong>Wskaźniki fokusu:</strong> widoczne obramowania fokusu są obecne na wszystkich elementach, które można skupić</li>
  <li><strong>Atrybuty języka:</strong> atrybut <code>lang</code> jest poprawnie ustawiony na dokumencie</li>
</ul>

<h2>Znane ograniczenia</h2>
<p>Choć dążymy do pełnej zgodności z WCAG 2.1 AA, możesz napotkać następujące znane problemy:</p>
<ul>
  <li>Niektóre osadzone treści stron trzecich (np. mapy lub odtwarzacze wideo) mogą nie spełniać w pełni standardów dostępności — pracujemy z dostawcami nad ich rozwiązaniem</li>
  <li>Dokumenty PDF dostępne z tej strony mogą nie być w pełni dostępne — pracujemy nad ich poprawą</li>
</ul>
<p>Stale pracujemy nad poprawą dostępności naszej strony. Jeśli znajdziesz problem nieuwzględniony na tej liście, daj nam znać.</p>

<h2>Informacje zwrotne i kontakt</h2>
<p>Chętnie przyjmujemy opinie na temat dostępności tej strony. Jeśli napotykasz bariery dostępności, prosimy o kontakt:</p>
<ul>
  <li>E-mail: <a href="mailto:{{legal.complaints_email}}">{{legal.complaints_email}}</a></li>
  <li>Telefon: {{legal.complaints_phone}}</li>
</ul>
<p>Staramy się potwierdzać skargi dotyczące dostępności w ciągu <strong>{{legal.response_days}} dni roboczych</strong> i udzielać pełnej odpowiedzi w ciągu 20 dni roboczych.</p>

<h2>Procedura egzekwowania</h2>
<p>Jeśli skontaktowałeś się z nami w sprawie dostępności i nie jesteś zadowolony z naszej odpowiedzi, możesz skontaktować się z <strong>Equality Advisory and Support Service (EASS)</strong>:</p>
<ul>
  <li>Strona: <a href="https://www.equalityadvisoryservice.com" rel="noopener noreferrer">www.equalityadvisoryservice.com</a></li>
  <li>Telefon: 0808 800 0082</li>
</ul>
HTML,
                    'pt' => <<<'HTML'
<h2>O Nosso Compromisso</h2>
<p>A {{legal.company_name}} compromete-se a tornar este website acessível a todos os utilizadores, independentemente da deficiência ou da tecnologia que utilizam. Procuramos cumprir as <strong>Diretrizes de Acessibilidade para Conteúdo Web (WCAG) 2.1 Nível AA</strong>, conforme exigido pelo Equality Act 2010.</p>
<p>Quando construímos e mantemos websites para os nossos clientes, a acessibilidade é uma parte fundamental do nosso processo — e aplicamos esses mesmos padrões ao nosso próprio website.</p>

<h2>Abordagem Técnica e Normas</h2>
<p>Este website foi concebido e desenvolvido para cumprir os seguintes critérios de acessibilidade:</p>
<ul>
  <li><strong>HTML semântico:</strong> hierarquia de cabeçalhos significativa (H1–H6), regiões de referência (main, nav, footer, aside)</li>
  <li><strong>Navegação por teclado:</strong> todos os elementos interativos são alcançáveis e operáveis apenas com o teclado</li>
  <li><strong>Compatibilidade com leitores de ecrã:</strong> etiquetas ARIA, funções e propriedades adequadas utilizadas em todo o site</li>
  <li><strong>Contraste de cores:</strong> todo o texto cumpre os rácios mínimos de contraste das WCAG 2.1 AA (4,5:1 para texto normal, 3:1 para texto grande)</li>
  <li><strong>Imagens:</strong> todas as imagens informativas incluem texto alternativo descritivo; as imagens decorativas utilizam atributos alt vazios</li>
  <li><strong>Formulários:</strong> todos os campos dos formulários têm etiquetas visíveis e associadas e mensagens de erro claras</li>
  <li><strong>Redimensionamento de texto:</strong> o website mantém-se totalmente funcional quando o texto é redimensionado até 200%</li>
  <li><strong>Movimento:</strong> as animações respeitam a media query <code>prefers-reduced-motion</code></li>
  <li><strong>Indicadores de foco:</strong> indicadores de foco visíveis estão presentes em todos os elementos focáveis</li>
  <li><strong>Atributos de idioma:</strong> o atributo <code>lang</code> está corretamente definido no documento</li>
</ul>

<h2>Limitações Conhecidas</h2>
<p>Embora nos esforcemos pela plena conformidade com as WCAG 2.1 AA, pode encontrar os seguintes problemas conhecidos:</p>
<ul>
  <li>Alguns conteúdos de terceiros incorporados (como mapas ou leitores de vídeo) podem não cumprir totalmente os padrões de acessibilidade — estamos a trabalhar com os fornecedores para resolver estes problemas</li>
  <li>Alguns documentos PDF disponíveis neste site podem não ser totalmente acessíveis — estamos a trabalhar para os corrigir</li>
</ul>
<p>Estamos a trabalhar continuamente para melhorar a acessibilidade do nosso website. Se encontrar um problema não listado aqui, por favor informe-nos.</p>

<h2>Feedback e Contacto</h2>
<p>Damos as boas-vindas ao feedback sobre a acessibilidade deste website. Se encontrar barreiras de acessibilidade, contacte-nos:</p>
<ul>
  <li>E-mail: <a href="mailto:{{legal.complaints_email}}">{{legal.complaints_email}}</a></li>
  <li>Telefone: {{legal.complaints_phone}}</li>
</ul>
<p>Procuramos confirmar as reclamações de acessibilidade no prazo de <strong>{{legal.response_days}} dias úteis</strong> e fornecer uma resposta completa no prazo de 20 dias úteis.</p>

<h2>Procedimento de Execução</h2>
<p>Se nos contactou sobre uma questão de acessibilidade e não está satisfeito com a nossa resposta, pode contactar o <strong>Equality Advisory and Support Service (EASS)</strong>:</p>
<ul>
  <li>Website: <a href="https://www.equalityadvisoryservice.com" rel="noopener noreferrer">www.equalityadvisoryservice.com</a></li>
  <li>Telefone: 0808 800 0082</li>
</ul>
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
