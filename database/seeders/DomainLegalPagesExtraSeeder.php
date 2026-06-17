<?php

namespace Database\Seeders;

use App\Models\Page;
use App\Models\User;
use Illuminate\Database\Seeder;

/**
 * Seeds the 4 remaining domain legal / informational CMS pages:
 *   - domain-cancellation-policy  (zasady anulowania i zwrotów)
 *   - domain-privacy-gdpr         (GDPR / privacy for domain registrations)
 *   - domain-registrar-info       (information about our registrar — Openprovider)
 *   - domain-pricing              (domain pricing overview)
 */
class DomainLegalPagesExtraSeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::where('email', 'admin@websiteexpert.co.uk')->first();

        $pages = [

            // ─── Cancellation & Refund Policy ────────────────────────────────
            [
                'slug' => 'domain-cancellation-policy',
                'status' => 'published',
                'type' => 'policy',
                'show_in_footer' => false,
                'sort_order' => 14,
                'created_by' => $admin?->id,
                'published_at' => now(),
                'effective_date' => '2026-05-29',
                'version' => '1.0',

                'title' => [
                    'en' => 'Domain Cancellation & Refund Policy',
                    'pl' => 'Zasady anulowania i zwrotów za domeny',
                    'pt' => 'Política de Cancelamento e Reembolso de Domínios',
                ],
                'meta_title' => [
                    'en' => 'Domain Cancellation & Refund Policy | WebsiteExpert',
                    'pl' => 'Zasady anulowania i zwrotów za domeny | WebsiteExpert',
                    'pt' => 'Política de Cancelamento e Reembolso de Domínios | WebsiteExpert',
                ],
                'meta_description' => [
                    'en' => 'Understand our cancellation and refund policy for domain registration, renewal, and transfer orders.',
                    'pl' => 'Dowiedz się o zasadach anulowania i zwrotów za rejestrację, odnowienie i transfer domen.',
                    'pt' => 'Conheça a nossa política de cancelamento e reembolso para pedidos de registo, renovação e transferência de domínios.',
                ],
                'content' => [
                    'en' => <<<'HTML'
<h2>1. Overview</h2>
<p>This policy explains the circumstances under which you may cancel a domain order and what refunds, if any, apply. Please read this policy carefully before placing a domain order with <strong>{{legal.company_name}}</strong>.</p>

<h2>2. Pre-Registration Cancellations</h2>
<p>If your payment has been authorised but the domain has <strong>not yet been registered</strong> with the registry (i.e. the order is in "Awaiting Registration" status), you may request a cancellation by contacting us at <a href="mailto:{{legal.company_email}}">{{legal.company_email}}</a>. If we are able to halt the registration process, a full refund will be issued within 5–10 business days.</p>
<p>However, due to the automated and near-instant nature of domain registration processing, we cannot guarantee that a cancellation request will be received in time to prevent the registration from completing.</p>

<h2>3. Post-Registration — No Refunds</h2>
<p>Once a domain has been <strong>successfully registered</strong> with the relevant registry, the registration fee is <strong>non-refundable</strong>. This is because:</p>
<ul>
  <li>The registry charges an irrecoverable fee upon registration.</li>
  <li>Domain names cannot be "unregistered" — they must either be allowed to expire or transferred.</li>
  <li>The service is fully performed at the point of successful registration.</li>
</ul>
<p>Consumer statutory cancellation rights under the Consumer Contracts (Information, Cancellation and Additional Charges) Regulations 2013 do not apply to domain registrations, as explicitly permitted by Regulation 28(1)(b) — digital content supplied immediately upon the consumer's express request, waiving their right of withdrawal.</p>

<h2>4. Renewal Cancellations</h2>
<p>Renewal orders that have been paid but not yet processed may be cancelled subject to the same conditions as section 2 above. Once the renewal has been transmitted to the registry, it is non-refundable.</p>
<p>If you do not wish to renew your domain, you must ensure that auto-renewal is disabled in your client portal before the renewal due date. We are not responsible for renewals that occur because auto-renewal was not disabled in time.</p>

<h2>5. Transfer Cancellations</h2>
<p>Domain transfer requests may be cancelled before the transfer is initiated by contacting us immediately. If the transfer has already been submitted to the registry, cancellation may not be possible.</p>

<h2>6. Registration Failures</h2>
<p>In the event that we are unable to register a domain due to:</p>
<ul>
  <li>The domain being taken between your search and payment completion;</li>
  <li>Registry technical issues; or</li>
  <li>Eligibility restrictions imposed by the registry;</li>
</ul>
<p>a <strong>full refund</strong> will be issued automatically within 5–10 business days.</p>

<h2>7. Errors Caused by Us</h2>
<p>If a domain is registered incorrectly as a result of an error on our part, we will take all reasonable steps to rectify the issue at no additional charge to you.</p>

<h2>8. How to Request a Cancellation</h2>
<p>Email <a href="mailto:{{legal.company_email}}">{{legal.company_email}}</a> with your order reference and reason for cancellation. We will respond within 1 business day.</p>

<h2>9. Governing Law</h2>
<p>This policy is subject to the laws of England and Wales and forms part of our Domain Registration Terms.</p>
HTML,

                    'pl' => <<<'HTML'
<h2>1. Informacje ogólne</h2>
<p>Niniejsza polityka wyjaśnia okoliczności, w których możesz anulować zamówienie na domenę, oraz jakie zwroty, o ile jakiekolwiek, mają zastosowanie. Prosimy o uważne zapoznanie się z niniejszą polityką przed złożeniem zamówienia na domenę w <strong>{{legal.company_name}}</strong>.</p>

<h2>2. Anulowania przed rejestracją</h2>
<p>Jeśli płatność została autoryzowana, ale domena <strong>nie została jeszcze zarejestrowana</strong> w rejestrze (tj. zamówienie ma status "Oczekuje na rejestrację"), możesz złożyć wniosek o anulowanie, kontaktując się z nami pod adresem <a href="mailto:{{legal.company_email}}">{{legal.company_email}}</a>. Jeśli będziemy w stanie wstrzymać proces rejestracji, pełny zwrot zostanie dokonany w ciągu 5–10 dni roboczych.</p>
<p>Ze względu na zautomatyzowany i niemal natychmiastowy charakter przetwarzania rejestracji domen, nie możemy zagwarantować, że wniosek o anulowanie zostanie otrzymany na czas, zanim rejestracja zostanie zakończona.</p>

<h2>3. Po rejestracji — brak zwrotów</h2>
<p>Po <strong>pomyślnej rejestracji</strong> domeny w stosownym rejestrze, opłata rejestracyjna jest <strong>bezzwrotna</strong>. Wynika to z następujących powodów:</p>
<ul>
  <li>Rejestr pobiera nieodwracalną opłatę w momencie rejestracji.</li>
  <li>Nazwy domen nie mogą być "odrejestrowane" — muszą albo wygasnąć, albo zostać przeniesione.</li>
  <li>Usługa jest w pełni wykonana w momencie pomyślnej rejestracji.</li>
</ul>
<p>Ustawowe prawa odstąpienia konsumenta wynikające z Rozporządzenia Consumer Contracts z 2013 r. nie mają zastosowania do rejestracji domen, zgodnie z Rozporządzeniem 28(1)(b) — treści cyfrowe dostarczone natychmiast na wyraźne żądanie konsumenta, który zrzekł się prawa do odstąpienia.</p>

<h2>4. Anulowania odnowień</h2>
<p>Zamówienia na odnowienie, które zostały opłacone, ale nie zostały jeszcze przetworzone, mogą być anulowane na tych samych warunkach co sekcja 2 powyżej. Po przesłaniu odnowienia do rejestru, nie podlega ono zwrotowi.</p>
<p>Jeśli nie chcesz odnawiać domeny, musisz upewnić się, że automatyczne odnowienie jest wyłączone w portalu klienta przed terminem odnowienia. Nie ponosimy odpowiedzialności za odnowienia, które nastąpiły, ponieważ automatyczne odnowienie nie zostało wyłączone na czas.</p>

<h2>5. Anulowania transferów</h2>
<p>Wnioski o transfer domeny mogą być anulowane przed zainicjowaniem transferu poprzez natychmiastowy kontakt z nami. Jeśli transfer został już przesłany do rejestru, anulowanie może nie być możliwe.</p>

<h2>6. Niepowodzenia rejestracji</h2>
<p>W przypadku gdy nie jesteśmy w stanie zarejestrować domeny z powodu:</p>
<ul>
  <li>Zajęcia domeny między wyszukiwaniem a zakończeniem płatności;</li>
  <li>Problemów technicznych rejestru; lub</li>
  <li>Ograniczeń kwalifikowalności nałożonych przez rejestr;</li>
</ul>
<p><strong>pełny zwrot</strong> zostanie dokonany automatycznie w ciągu 5–10 dni roboczych.</p>

<h2>7. Błędy po naszej stronie</h2>
<p>Jeśli domena zostanie zarejestrowana nieprawidłowo w wyniku błędu po naszej stronie, podejmiemy wszelkie uzasadnione kroki w celu naprawienia problemu bez dodatkowych opłat dla Ciebie.</p>

<h2>8. Jak złożyć wniosek o anulowanie</h2>
<p>Napisz na adres <a href="mailto:{{legal.company_email}}">{{legal.company_email}}</a>, podając numer referencyjny zamówienia i powód anulowania. Odpowiemy w ciągu 1 dnia roboczego.</p>

<h2>9. Prawo właściwe</h2>
<p>Niniejsza polityka podlega prawu Anglii i Walii i stanowi część naszego Regulaminu rejestracji domen.</p>
HTML,

                    'pt' => <<<'HTML'
<h2>1. Visão Geral</h2>
<p>Esta política explica as circunstâncias em que pode cancelar um pedido de domínio e que reembolsos, se existirem, se aplicam. Por favor leia esta política cuidadosamente antes de efetuar um pedido de domínio com a <strong>{{legal.company_name}}</strong>.</p>

<h2>2. Cancelamentos Antes do Registo</h2>
<p>Se o seu pagamento foi autorizado mas o domínio <strong>ainda não foi registado</strong> no registo (ou seja, o pedido está no estado "Aguardando Registo"), pode solicitar o cancelamento contactando-nos em <a href="mailto:{{legal.company_email}}">{{legal.company_email}}</a>. Se conseguirmos parar o processo de registo, será emitido um reembolso total dentro de 5 a 10 dias úteis.</p>
<p>No entanto, devido à natureza automatizada e quase instantânea do processamento do registo de domínios, não podemos garantir que um pedido de cancelamento seja recebido a tempo de impedir a conclusão do registo.</p>

<h2>3. Após o Registo — Sem Reembolsos</h2>
<p>Uma vez que um domínio tenha sido <strong>registado com sucesso</strong> no registo relevante, a taxa de registo é <strong>não reembolsável</strong>. Isto porque:</p>
<ul>
  <li>O registo cobra uma taxa irrecuperável no momento do registo.</li>
  <li>Os nomes de domínio não podem ser "cancelados" — devem ser deixados a expirar ou transferidos.</li>
  <li>O serviço é totalmente prestado no momento do registo bem-sucedido.</li>
</ul>

<h2>4. Cancelamentos de Renovações</h2>
<p>Os pedidos de renovação que foram pagos mas ainda não processados podem ser cancelados sujeitos às mesmas condições da secção 2 acima. Uma vez que a renovação tenha sido transmitida ao registo, não é reembolsável.</p>

<h2>5. Falhas de Registo</h2>
<p>Se não conseguirmos registar um domínio devido a problemas técnicos do registo ou ao domínio ser tomado entre a pesquisa e a conclusão do pagamento, será emitido automaticamente um <strong>reembolso total</strong> dentro de 5 a 10 dias úteis.</p>

<h2>6. Como Solicitar um Cancelamento</h2>
<p>Envie um email para <a href="mailto:{{legal.company_email}}">{{legal.company_email}}</a> com a referência do seu pedido e o motivo do cancelamento. Responderemos dentro de 1 dia útil.</p>
HTML,
                ],
            ],

            // ─── GDPR / Privacy (Domains) ─────────────────────────────────────
            [
                'slug' => 'domain-privacy-gdpr',
                'status' => 'published',
                'type' => 'policy',
                'show_in_footer' => false,
                'sort_order' => 15,
                'created_by' => $admin?->id,
                'published_at' => now(),
                'effective_date' => '2026-05-29',
                'version' => '1.0',

                'title' => [
                    'en' => 'Domain Services — Privacy & GDPR Notice',
                    'pl' => 'Usługi domenowe — Prywatność i RODO',
                    'pt' => 'Serviços de Domínio — Aviso de Privacidade e RGPD',
                ],
                'meta_title' => [
                    'en' => 'Domain Services Privacy & GDPR Notice | WebsiteExpert',
                    'pl' => 'Prywatność i RODO dla usług domenowych | WebsiteExpert',
                    'pt' => 'Aviso de Privacidade RGPD — Serviços de Domínio | WebsiteExpert',
                ],
                'meta_description' => [
                    'en' => 'How we collect, use, and protect your personal data when providing domain registration services, in compliance with UK GDPR.',
                    'pl' => 'Jak gromadzimy, używamy i chronimy Twoje dane osobowe podczas świadczenia usług rejestracji domen, zgodnie z UK RODO.',
                    'pt' => 'Como recolhemos, usamos e protegemos os seus dados pessoais ao prestar serviços de registo de domínios, em conformidade com o RGPD do Reino Unido.',
                ],
                'content' => [
                    'en' => <<<'HTML'
<h2>1. Who We Are</h2>
<p><strong>{{legal.company_name}}</strong> (Company No. {{legal.company_number}}, {{legal.company_address}}) is the Data Controller for personal data processed in connection with domain registration services. Contact: <a href="mailto:{{legal.company_email}}">{{legal.company_email}}</a>.</p>

<h2>2. What Personal Data We Collect</h2>
<p>To register a domain name on your behalf, we collect:</p>
<ul>
  <li><strong>Registrant contact details</strong>: full name, email address, phone number, postal address, and country of residence.</li>
  <li><strong>Payment information</strong>: handled entirely by Stripe — we do not store card numbers. We retain only the Stripe transaction reference.</li>
  <li><strong>Technical data</strong>: IP address, browser/device information collected via server logs.</li>
</ul>

<h2>3. Why We Collect This Data (Legal Basis)</h2>
<table>
  <thead><tr><th>Purpose</th><th>Legal Basis</th></tr></thead>
  <tbody>
    <tr><td>Domain registration with the registry</td><td>Performance of contract (Art. 6(1)(b) UK GDPR)</td></tr>
    <tr><td>Sending renewal reminders and order notifications</td><td>Performance of contract (Art. 6(1)(b) UK GDPR)</td></tr>
    <tr><td>Compliance with ICANN / registry data retention rules</td><td>Legal obligation (Art. 6(1)(c) UK GDPR)</td></tr>
    <tr><td>Fraud prevention and security</td><td>Legitimate interests (Art. 6(1)(f) UK GDPR)</td></tr>
  </tbody>
</table>

<h2>4. Sharing Your Data</h2>
<p>Your registrant contact details are shared with the following parties as required for the provision of the service:</p>
<ul>
  <li><strong>Openprovider</strong> (our domain registrar partner) — to register the domain with the relevant registry. Openprovider acts as a data processor on our behalf.</li>
  <li><strong>The domain registry</strong> (e.g. Nominet for .uk, Verisign for .com/.net) — registrant data is passed to the registry as required by their terms. For most gTLDs this data may appear in WHOIS unless WHOIS privacy is enabled.</li>
  <li><strong>Stripe</strong> — for payment processing. Stripe's privacy policy applies: <a href="https://stripe.com/gb/privacy" rel="noopener noreferrer">stripe.com/gb/privacy</a>.</li>
</ul>
<p>We do not sell your personal data to third parties.</p>

<h2>5. WHOIS Privacy</h2>
<p>Where WHOIS privacy is enabled, your personal contact details are masked in public WHOIS lookups. However, your data remains on file with us and with the registrar as required by registry rules. Law enforcement and competent authorities may access this information pursuant to a lawful request.</p>

<h2>6. Retention</h2>
<p>We retain domain registrant data for as long as the domain is registered with us, plus a further 6 years for tax and contractual record-keeping purposes. Log data is retained for 90 days.</p>

<h2>7. Your Rights</h2>
<p>Under UK GDPR you have the right to: access, rectify, erase (subject to registry retention obligations), restrict processing, data portability, and to object to processing based on legitimate interests. To exercise these rights, email <a href="mailto:{{legal.company_email}}">{{legal.company_email}}</a>.</p>
<p>You have the right to lodge a complaint with the <strong>Information Commissioner's Office (ICO)</strong>: <a href="https://ico.org.uk" rel="noopener noreferrer">ico.org.uk</a>.</p>

<h2>8. International Transfers</h2>
<p>Openprovider is based in the Netherlands (EU/EEA) and processes data under Standard Contractual Clauses where applicable. Stripe Inc. is a US company that transfers data under UK GDPR adequacy decisions and SCCs.</p>
HTML,

                    'pl' => <<<'HTML'
<h2>1. Kim jesteśmy</h2>
<p><strong>{{legal.company_name}}</strong> (numer rejestracyjny: {{legal.company_number}}, {{legal.company_address}}) jest Administratorem Danych Osobowych przetwarzanych w związku z usługami rejestracji domen. Kontakt: <a href="mailto:{{legal.company_email}}">{{legal.company_email}}</a>.</p>

<h2>2. Jakie dane osobowe gromadzimy</h2>
<p>Aby zarejestrować nazwę domeny w Twoim imieniu, gromadzimy:</p>
<ul>
  <li><strong>Dane kontaktowe Rejestrującego</strong>: imię i nazwisko, adres e-mail, numer telefonu, adres pocztowy i kraj zamieszkania.</li>
  <li><strong>Dane płatności</strong>: obsługiwane wyłącznie przez Stripe — nie przechowujemy numerów kart. Przechowujemy wyłącznie referencję transakcji Stripe.</li>
  <li><strong>Dane techniczne</strong>: adres IP, informacje o przeglądarce/urządzeniu zbierane przez logi serwera.</li>
</ul>

<h2>3. Dlaczego gromadzimy te dane (podstawa prawna)</h2>
<table>
  <thead><tr><th>Cel</th><th>Podstawa prawna</th></tr></thead>
  <tbody>
    <tr><td>Rejestracja domeny w rejestrze</td><td>Wykonanie umowy (art. 6 ust. 1 lit. b UK RODO)</td></tr>
    <tr><td>Wysyłanie przypomnień o odnowieniu i powiadomień o zamówieniu</td><td>Wykonanie umowy (art. 6 ust. 1 lit. b UK RODO)</td></tr>
    <tr><td>Zgodność z zasadami przechowywania danych ICANN/rejestru</td><td>Obowiązek prawny (art. 6 ust. 1 lit. c UK RODO)</td></tr>
    <tr><td>Zapobieganie oszustwom i bezpieczeństwo</td><td>Uzasadniony interes (art. 6 ust. 1 lit. f UK RODO)</td></tr>
  </tbody>
</table>

<h2>4. Udostępnianie danych</h2>
<p>Twoje dane kontaktowe Rejestrującego są udostępniane następującym podmiotom w zakresie niezbędnym do świadczenia usługi:</p>
<ul>
  <li><strong>Openprovider</strong> (nasz partner rejestratorski) — w celu rejestracji domeny w stosownym rejestrze.</li>
  <li><strong>Rejestr domen</strong> (np. Nominet dla .uk, Verisign dla .com/.net).</li>
  <li><strong>Stripe</strong> — do przetwarzania płatności.</li>
</ul>
<p>Nie sprzedajemy Twoich danych osobowych stronom trzecim.</p>

<h2>5. Prywatność WHOIS</h2>
<p>Tam gdzie prywatność WHOIS jest włączona, Twoje osobiste dane kontaktowe są maskowane w publicznych wyszukiwaniach WHOIS. Jednak Twoje dane pozostają u nas i u rejestratora zgodnie z wymogami regulaminów rejestrów.</p>

<h2>6. Przechowywanie danych</h2>
<p>Przechowujemy dane rejestrujących domenę przez cały czas, gdy domena jest zarejestrowana u nas, oraz przez kolejne 6 lat ze względów podatkowych i umownych. Dane z logów są przechowywane przez 90 dni.</p>

<h2>7. Twoje prawa</h2>
<p>Na mocy UK RODO masz prawo do: dostępu, sprostowania, usunięcia (z zastrzeżeniem obowiązków przechowywania rejestru), ograniczenia przetwarzania, przenoszenia danych oraz sprzeciwu wobec przetwarzania opartego na uzasadnionym interesie. Aby skorzystać z tych praw, napisz na <a href="mailto:{{legal.company_email}}">{{legal.company_email}}</a>.</p>
<p>Masz prawo złożyć skargę do <strong>Biura Komisarza ds. Informacji (ICO)</strong>: <a href="https://ico.org.uk" rel="noopener noreferrer">ico.org.uk</a>.</p>
HTML,

                    'pt' => <<<'HTML'
<h2>1. Quem Somos</h2>
<p><strong>{{legal.company_name}}</strong> (N.º de empresa: {{legal.company_number}}, {{legal.company_address}}) é o Responsável pelo Tratamento de dados pessoais processados em conexão com serviços de registo de domínios. Contacto: <a href="mailto:{{legal.company_email}}">{{legal.company_email}}</a>.</p>

<h2>2. Que Dados Pessoais Recolhemos</h2>
<p>Para registar um domínio em seu nome, recolhemos:</p>
<ul>
  <li><strong>Dados de contacto do Titular</strong>: nome completo, endereço de email, número de telefone, endereço postal e país de residência.</li>
  <li><strong>Dados de pagamento</strong>: geridos inteiramente pelo Stripe — não armazenamos números de cartão. Retemos apenas a referência da transação Stripe.</li>
  <li><strong>Dados técnicos</strong>: endereço IP, informações de browser/dispositivo recolhidas através de logs do servidor.</li>
</ul>

<h2>3. Partilha de Dados</h2>
<p>Os seus dados de contacto do Titular são partilhados com as seguintes entidades conforme necessário para a prestação do serviço:</p>
<ul>
  <li><strong>Openprovider</strong> — para registar o domínio no registo relevante.</li>
  <li><strong>O registo do domínio</strong> (ex.: Nominet para .uk, Verisign para .com/.net).</li>
  <li><strong>Stripe</strong> — para processamento de pagamentos.</li>
</ul>
<p>Não vendemos os seus dados pessoais a terceiros.</p>

<h2>4. Os Seus Direitos</h2>
<p>Ao abrigo do RGPD do Reino Unido tem direito a: acesso, retificação, apagamento, restrição de tratamento, portabilidade de dados e oposição. Para exercer estes direitos, envie email para <a href="mailto:{{legal.company_email}}">{{legal.company_email}}</a>.</p>
<p>Tem o direito de apresentar uma reclamação junto do <strong>Information Commissioner's Office (ICO)</strong>: <a href="https://ico.org.uk" rel="noopener noreferrer">ico.org.uk</a>.</p>
HTML,
                ],
            ],

            // ─── Registrar Information (Openprovider) ────────────────────────
            [
                'slug' => 'domain-registrar-info',
                'status' => 'published',
                'type' => 'page',
                'show_in_footer' => false,
                'sort_order' => 16,
                'created_by' => $admin?->id,
                'published_at' => now(),
                'effective_date' => '2026-05-29',
                'version' => '1.0',

                'title' => [
                    'en' => 'About Our Domain Registrar',
                    'pl' => 'O naszym rejestratorze domen',
                    'pt' => 'Sobre o Nosso Registador de Domínios',
                ],
                'meta_title' => [
                    'en' => 'About Our Domain Registrar — Openprovider | WebsiteExpert',
                    'pl' => 'O naszym rejestratorze domen — Openprovider | WebsiteExpert',
                    'pt' => 'Sobre o Nosso Registador — Openprovider | WebsiteExpert',
                ],
                'meta_description' => [
                    'en' => 'We register domain names through Openprovider, an ICANN-accredited and Nominet-registered domain registrar based in the Netherlands.',
                    'pl' => 'Rejestrujemy domeny poprzez Openprovider — akredytowanego przez ICANN i zarejestrowanego w Nominet rejestratora domen z siedzibą w Holandii.',
                    'pt' => 'Registamos domínios através da Openprovider, um registador de domínios credenciado pela ICANN com sede nos Países Baixos.',
                ],
                'content' => [
                    'en' => <<<'HTML'
<h2>Who Registers Your Domain?</h2>
<p>Domain names ordered through <strong>{{legal.company_name}}</strong> are registered via <strong>Openprovider</strong>, our accredited registrar partner. We act as a reseller on your behalf.</p>

<h2>About Openprovider</h2>
<p><strong>Openprovider B.V.</strong> is an ICANN-accredited domain registrar and hosting wholesaler headquartered in Rotterdam, the Netherlands (EU). They are:</p>
<ul>
  <li>Accredited by <strong>ICANN</strong> for generic top-level domains (gTLDs) such as .com, .net, .org, .info, and hundreds of new gTLDs.</li>
  <li>Registered with <strong>Nominet UK</strong> as a registrar for .uk, .co.uk, .org.uk, .me.uk, and related domains.</li>
  <li>Compliant with <strong>GDPR / EU data protection</strong> regulations.</li>
</ul>
<p>Learn more: <a href="https://www.openprovider.com" rel="noopener noreferrer">www.openprovider.com</a></p>

<h2>What This Means for You</h2>
<p>As the <strong>Registrant</strong> (owner) of the domain, you retain full legal ownership and control. We do not claim ownership of any domain registered on your behalf.</p>
<ul>
  <li>Your domain is registered in <strong>your name</strong> in the official registry records.</li>
  <li>You may transfer your domain to any other accredited registrar at any time, subject to standard transfer policies.</li>
  <li>You will receive all registry communications (where applicable) directly.</li>
  <li>In the event that {{legal.company_name}} ceases trading, your domain remains registered in your name and is not affected — you can initiate a transfer to another registrar at any time.</li>
</ul>

<h2>Registry Contacts</h2>
<ul>
  <li><strong>Nominet UK</strong> (for .uk domains): <a href="https://www.nominet.uk" rel="noopener noreferrer">nominet.uk</a></li>
  <li><strong>Verisign</strong> (for .com / .net): <a href="https://www.verisign.com" rel="noopener noreferrer">verisign.com</a></li>
  <li><strong>ICANN</strong> (oversight body): <a href="https://www.icann.org" rel="noopener noreferrer">icann.org</a></li>
</ul>

<h2>Questions?</h2>
<p>If you have any questions about your domain registration or your registrant rights, contact us at <a href="mailto:{{legal.company_email}}">{{legal.company_email}}</a>.</p>
HTML,

                    'pl' => <<<'HTML'
<h2>Kto rejestruje Twoją domenę?</h2>
<p>Domeny zamówione przez <strong>{{legal.company_name}}</strong> są rejestrowane za pośrednictwem <strong>Openprovider</strong> — naszego akredytowanego partnera rejestratorskiego. Działamy jako odsprzedawca w Twoim imieniu.</p>

<h2>O Openprovider</h2>
<p><strong>Openprovider B.V.</strong> to akredytowany przez ICANN rejestrator domen i hurtowy dostawca usług hostingowych z siedzibą w Rotterdamie, w Holandii (UE). Firma:</p>
<ul>
  <li>Jest akredytowana przez <strong>ICANN</strong> dla ogólnych domen najwyższego poziomu (gTLD), takich jak .com, .net, .org, .info i setki nowych gTLD.</li>
  <li>Jest zarejestrowana w <strong>Nominet UK</strong> jako rejestrator dla domen .uk, .co.uk, .org.uk, .me.uk i pokrewnych.</li>
  <li>Spełnia wymogi <strong>RODO / unijnych przepisów o ochronie danych</strong>.</li>
</ul>
<p>Więcej informacji: <a href="https://www.openprovider.com" rel="noopener noreferrer">www.openprovider.com</a></p>

<h2>Co to oznacza dla Ciebie</h2>
<p>Jako <strong>Rejestrujący</strong> (właściciel) domeny, zachowujesz pełne prawo własności i kontrolę. Nie rościmy sobie prawa własności do żadnej domeny zarejestrowanej w Twoim imieniu.</p>
<ul>
  <li>Twoja domena jest zarejestrowana <strong>na Twoje imię i nazwisko</strong> w oficjalnych rejestrach.</li>
  <li>Możesz przenieść domenę do dowolnego innego akredytowanego rejestratora w dowolnym momencie.</li>
  <li>W przypadku zaprzestania działalności przez {{legal.company_name}}, Twoja domena pozostaje zarejestrowana na Twoje dane i nie jest zagrożona.</li>
</ul>

<h2>Pytania?</h2>
<p>Napisz do nas na <a href="mailto:{{legal.company_email}}">{{legal.company_email}}</a>.</p>
HTML,

                    'pt' => <<<'HTML'
<h2>Quem Regista o Seu Domínio?</h2>
<p>Os domínios encomendados através da <strong>{{legal.company_name}}</strong> são registados via <strong>Openprovider</strong>, o nosso parceiro registador credenciado. Atuamos como revendedor em seu nome.</p>

<h2>Sobre a Openprovider</h2>
<p><strong>Openprovider B.V.</strong> é um registador de domínios credenciado pela ICANN com sede em Roterdão, nos Países Baixos (UE). É:</p>
<ul>
  <li>Credenciada pela <strong>ICANN</strong> para domínios genéricos de topo (gTLDs) como .com, .net, .org e centenas de novos gTLDs.</li>
  <li>Registada na <strong>Nominet UK</strong> como registador para .uk, .co.uk e domínios relacionados.</li>
  <li>Conforme com o <strong>RGPD / legislação europeia de proteção de dados</strong>.</li>
</ul>
<p>Saiba mais: <a href="https://www.openprovider.com" rel="noopener noreferrer">www.openprovider.com</a></p>

<h2>O Que Isto Significa Para Si</h2>
<p>Como <strong>Titular</strong> (proprietário) do domínio, mantém plena propriedade legal e controlo. Não reivindicamos a propriedade de nenhum domínio registado em seu nome.</p>
<ul>
  <li>O seu domínio está registado em <strong>seu nome</strong> nos registos oficiais do registo.</li>
  <li>Pode transferir o seu domínio para qualquer outro registador credenciado a qualquer momento.</li>
</ul>

<h2>Questões?</h2>
<p>Contacte-nos em <a href="mailto:{{legal.company_email}}">{{legal.company_email}}</a>.</p>
HTML,
                ],
            ],

            // ─── Domain Pricing Overview ──────────────────────────────────────
            [
                'slug' => 'domain-pricing',
                'status' => 'published',
                'type' => 'page',
                'show_in_footer' => false,
                'sort_order' => 17,
                'created_by' => $admin?->id,
                'published_at' => now(),
                'effective_date' => '2026-05-29',
                'version' => '1.0',

                'title' => [
                    'en' => 'Domain Pricing',
                    'pl' => 'Cennik domen',
                    'pt' => 'Preços de Domínios',
                ],
                'meta_title' => [
                    'en' => 'Domain Registration & Renewal Prices | WebsiteExpert',
                    'pl' => 'Cennik rejestracji i odnowień domen | WebsiteExpert',
                    'pt' => 'Preços de Registo e Renovação de Domínios | WebsiteExpert',
                ],
                'meta_description' => [
                    'en' => 'Transparent domain registration and renewal pricing. See registration vs. renewal costs for all supported TLDs.',
                    'pl' => 'Przejrzyste ceny rejestracji i odnowień domen. Sprawdź koszty rejestracji i odnowienia dla wszystkich obsługiwanych TLD.',
                    'pt' => 'Preços transparentes de registo e renovação de domínios. Veja os custos de registo vs. renovação para todos os TLDs suportados.',
                ],
                'content' => [
                    'en' => <<<'HTML'
<h2>Pricing Principles</h2>
<p>We believe in transparent, honest pricing with <strong>no hidden fees</strong>. Our domain prices cover the cost of the registry fee, the registrar fee charged by Openprovider, and our service fee. VAT is added where applicable.</p>

<h2>Registration vs. Renewal Pricing</h2>
<p>It is important to understand the difference between registration and renewal prices:</p>
<ul>
  <li><strong>Registration price</strong> — the price you pay to register a new domain name for the first time (or transfer it to us).</li>
  <li><strong>Renewal price</strong> — the price you pay each year to keep the domain active after the initial registration period ends.</li>
</ul>
<p>Renewal prices may differ from registration prices. The renewal price applicable at the time of renewal is shown in your client portal under "My Domains" at least 30 days before the renewal date.</p>

<h2>Current Price List</h2>
<p>The full live price list — including registration, renewal, and transfer prices for all supported TLDs — is available on our <a href="/domains">Domain Registration page</a>.</p>
<p>Prices displayed on the website are inclusive of our service fee and exclusive of VAT (20% UK VAT is added at checkout where applicable).</p>

<h2>Price Changes</h2>
<p>Registry fees can change. When a price change affects your domain's renewal price, we will notify you by email at least <strong>30 days in advance</strong>. The price in force at the time of your renewal order will apply.</p>

<h2>Payment Currency</h2>
<p>All prices are displayed and charged in the currency shown at checkout. Payment is processed securely via Stripe.</p>

<h2>Promotional Prices</h2>
<p>From time to time we may offer promotional registration prices for specific TLDs. Promotional prices apply only to the initial registration period. Renewals are billed at the standard renewal rate.</p>

<h2>Questions</h2>
<p>For a custom quote or volume pricing enquiry, contact <a href="mailto:{{legal.company_email}}">{{legal.company_email}}</a>.</p>
HTML,

                    'pl' => <<<'HTML'
<h2>Zasady cenowe</h2>
<p>Wierzymy w przejrzyste i uczciwe ceny bez <strong>ukrytych opłat</strong>. Nasze ceny domen obejmują opłatę rejestrową, opłatę rejestratora pobieraną przez Openprovider oraz naszą opłatę serwisową. VAT jest doliczany tam, gdzie ma to zastosowanie.</p>

<h2>Cena rejestracji a cena odnowienia</h2>
<p>Ważne jest rozróżnienie między ceną rejestracji a ceną odnowienia:</p>
<ul>
  <li><strong>Cena rejestracji</strong> — cena, którą płacisz za rejestrację nowej nazwy domeny po raz pierwszy (lub jej transfer do nas).</li>
  <li><strong>Cena odnowienia</strong> — cena, którą płacisz każdego roku, aby utrzymać domenę aktywną po zakończeniu początkowego okresu rejestracji.</li>
</ul>
<p>Ceny odnowień mogą różnić się od cen rejestracji. Aktualna cena odnowienia jest widoczna w portalu klienta w sekcji "Moje domeny" co najmniej 30 dni przed terminem odnowienia.</p>

<h2>Aktualny cennik</h2>
<p>Pełny aktualny cennik — obejmujący ceny rejestracji, odnowienia i transferu dla wszystkich obsługiwanych TLD — jest dostępny na naszej <a href="/domains">stronie rejestracji domen</a>.</p>
<p>Ceny na stronie zawierają naszą opłatę serwisową i są cenami netto (20% VAT jest doliczane przy kasie tam, gdzie ma zastosowanie).</p>

<h2>Zmiany cen</h2>
<p>Opłaty rejestrowe mogą ulec zmianie. Gdy zmiana ceny dotyczy ceny odnowienia Twojej domeny, powiadomimy Cię e-mailem co najmniej <strong>30 dni wcześniej</strong>.</p>

<h2>Pytania</h2>
<p>W sprawie indywidualnej wyceny lub zapytania o ceny hurtowe, napisz na <a href="mailto:{{legal.company_email}}">{{legal.company_email}}</a>.</p>
HTML,

                    'pt' => <<<'HTML'
<h2>Princípios de Preços</h2>
<p>Acreditamos em preços transparentes e honestos <strong>sem taxas ocultas</strong>. Os nossos preços de domínio cobrem a taxa do registo, a taxa do registador cobrada pela Openprovider e a nossa taxa de serviço. O IVA é adicionado quando aplicável.</p>

<h2>Preço de Registo vs. Renovação</h2>
<p>É importante compreender a diferença entre os preços de registo e renovação:</p>
<ul>
  <li><strong>Preço de registo</strong> — o preço que paga para registar um novo nome de domínio pela primeira vez.</li>
  <li><strong>Preço de renovação</strong> — o preço que paga cada ano para manter o domínio ativo após o período de registo inicial.</li>
</ul>
<p>Os preços de renovação podem diferir dos preços de registo. O preço de renovação aplicável é mostrado no seu portal de cliente em "Os Meus Domínios" pelo menos 30 dias antes da data de renovação.</p>

<h2>Lista de Preços Atual</h2>
<p>A lista de preços completa — incluindo preços de registo, renovação e transferência para todos os TLDs suportados — está disponível na nossa <a href="/domains">página de Registo de Domínios</a>.</p>

<h2>Alterações de Preços</h2>
<p>As taxas de registo podem mudar. Quando uma alteração de preço afeta o preço de renovação do seu domínio, iremos notificá-lo por email com pelo menos <strong>30 dias de antecedência</strong>.</p>

<h2>Questões</h2>
<p>Para um orçamento personalizado, contacte <a href="mailto:{{legal.company_email}}">{{legal.company_email}}</a>.</p>
HTML,
                ],
            ],

        ]; // end $pages

        foreach ($pages as $data) {
            Page::updateOrCreate(
                ['slug' => $data['slug']],
                $data
            );
        }
    }
}
