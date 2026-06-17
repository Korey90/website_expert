<?php

namespace Database\Seeders;

use App\Models\Page;
use App\Models\User;
use Illuminate\Database\Seeder;

class DomainLegalPagesSeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::where('email', 'admin@websiteexpert.co.uk')->first();

        $pages = [

            // ─── Domain Registration Terms ────────────────────────────────────
            [
                'slug' => 'domain-registration-terms',
                'status' => 'published',
                'type' => 'policy',
                'show_in_footer' => false,
                'sort_order' => 10,
                'created_by' => $admin?->id,
                'published_at' => now(),
                'effective_date' => '2026-05-29',
                'version' => '1.0',

                'title' => [
                    'en' => 'Domain Registration Terms',
                    'pl' => 'Regulamin rejestracji domen',
                    'pt' => 'Termos de Registo de Domínio',
                ],
                'meta_title' => [
                    'en' => 'Domain Registration Terms | WebsiteExpert',
                    'pl' => 'Regulamin rejestracji domen | WebsiteExpert',
                    'pt' => 'Termos de Registo de Domínio | WebsiteExpert',
                ],
                'meta_description' => [
                    'en' => 'Terms and conditions governing domain registration, transfer, and renewal services provided by {{legal.company_name}} via Openprovider.',
                    'pl' => 'Regulamin dotyczący rejestracji, transferu i odnowienia domen świadczonych przez {{legal.company_name}} za pośrednictwem Openprovider.',
                    'pt' => 'Termos e condições que regem os serviços de registo, transferência e renovação de domínios prestados pela {{legal.company_name}} através da Openprovider.',
                ],
                'content' => [
                    'en' => <<<'HTML'
<h2>1. About These Terms</h2>
<p>These Domain Registration Terms ("Terms") govern the registration, renewal, and transfer of domain names arranged by <strong>{{legal.company_name}}</strong> (Company No. {{legal.company_number}}), registered at {{legal.company_address}} ("we", "us") on behalf of the customer ("you", "Registrant").</p>
<p>Domain names are registered through our accredited registrar partner <strong>Openprovider</strong>, and all registrations are subject to the applicable registry and registrar policies (including ICANN policies, Nominet terms for .uk domains, and the policies of the relevant country-code registry).</p>
<p>By placing a domain order you confirm that you have read, understood, and agree to these Terms.</p>

<h2>2. Eligibility &amp; Registrant Obligations</h2>
<ul>
  <li>You must be at least 18 years old to register a domain name.</li>
  <li>You warrant that the registration of the domain name does not infringe any third-party intellectual property rights, is not being registered for abusive purposes, and that all contact information provided is accurate and up to date.</li>
  <li>You are responsible for maintaining accurate WHOIS/registrant contact data at all times. Providing false or misleading contact information may result in suspension or cancellation of the domain.</li>
  <li>For .uk domains, you must comply with <a href="https://www.nominet.uk/go/terms" rel="noopener noreferrer">Nominet's Terms and Conditions</a> and Domain Registration Policy.</li>
  <li>For generic TLDs (.com, .net, .org, etc.), you must comply with ICANN's Registrant Rights and Responsibilities.</li>
</ul>

<h2>3. Registration Process &amp; Availability</h2>
<p>Domain availability is checked in real time against the relevant registry. A domain appearing available at the time of search is <strong>not guaranteed to remain available</strong> until registration is complete and payment has been received in full.</p>
<p>Registration is deemed complete only when:</p>
<ol>
  <li>Payment has been confirmed by our payment processor (Stripe); and</li>
  <li>The domain has been successfully registered with the registry and a confirmation email has been sent.</li>
</ol>
<p>Minimum registration period: <strong>1 year</strong>. Maximum: 10 years (where permitted by the registry).</p>

<h2>4. Pricing &amp; Payment</h2>
<p>All prices displayed are inclusive of our service fee and exclusive of VAT (where applicable). The price at the time of order confirmation is binding. Registration fees are charged in the currency displayed at checkout.</p>
<p>Payment is processed via <strong>Stripe</strong> (debit/credit card). Orders are processed only after successful payment authorisation. If payment fails, the domain will not be registered and no reservation is made.</p>

<h2>5. No Refunds on Registered Domains</h2>
<p>Domain registration fees are <strong>non-refundable</strong> once a domain has been successfully registered with the registry. This is because domain registration involves an immediate, irrecoverable cost to the registrar and registry.</p>
<p>If a technical error results in a domain being registered incorrectly due to our fault, we will work with the registrar to resolve the issue at no additional charge.</p>
<p>Consumer statutory cancellation rights under the Consumer Contracts (Information, Cancellation and Additional Charges) Regulations 2013 do not apply to domain registrations, as the service is performed immediately upon order and the cancellation right is explicitly waived at the time of purchase.</p>

<h2>6. WHOIS Privacy</h2>
<p>Where included, WHOIS privacy masks your personal contact details in public WHOIS lookups by substituting our registrar's proxy details. WHOIS privacy is provided subject to the policies of the applicable registry. Some registries (e.g. Nominet for .uk domains) may publish registrant details regardless of privacy settings.</p>
<p>WHOIS privacy does not affect your ownership of the domain or your legal obligations as the registrant.</p>

<h2>7. DNS Management</h2>
<p>Upon registration, your domain will be pointed to our default nameservers unless you provide custom nameservers at the time of order. You may update your nameservers at any time through your client portal. DNS changes may take up to 48 hours to propagate globally.</p>

<h2>8. Domain Ownership &amp; Control</h2>
<p>You are the registered owner (Registrant) of the domain. We act as your registrar and do not claim ownership of your domain. You retain full control and may transfer your domain to another registrar at any time, subject to the restrictions in section 9.</p>

<h2>9. Prohibited Uses</h2>
<p>You must not use any domain registered through us for:</p>
<ul>
  <li>Distributing malware, spyware, or ransomware</li>
  <li>Phishing, spoofing, or impersonating other organisations</li>
  <li>Sending unsolicited bulk email (spam)</li>
  <li>Operating botnets or command-and-control infrastructure</li>
  <li>Pharming or DNS hijacking</li>
  <li>Any activity that violates applicable laws or regulations</li>
  <li>Any activity that infringes the intellectual property rights of third parties</li>
</ul>
<p>We reserve the right to suspend or cancel any domain found to be in violation of these restrictions without prior notice, in accordance with our DNS Abuse Policy and applicable registry rules.</p>

<h2>10. Dispute Resolution</h2>
<p>For .uk domain disputes, the <strong>Nominet Dispute Resolution Service (DRS)</strong> applies. For gTLD disputes (.com, .net, .org, etc.), disputes are handled under the <strong>ICANN Uniform Domain-Name Dispute-Resolution Policy (UDRP)</strong>.</p>
<p>We do not arbitrate domain disputes between registrants and third-party claimants. Any such disputes must be submitted to the relevant dispute resolution body.</p>

<h2>11. Limitation of Liability</h2>
<p>We shall not be liable for any loss arising from the inability to register a domain that was available at the time of checking but taken by the time of registration, third-party registry outages, domain suspension imposed by a registry or ICANN, or your failure to renew a domain before expiry.</p>
<p>Our total liability shall not exceed the registration fee paid for the specific domain giving rise to the claim.</p>

<h2>12. Governing Law</h2>
<p>These Terms are governed by the laws of England and Wales. Any disputes shall be subject to the non-exclusive jurisdiction of the courts of England and Wales.</p>
<p>Contact: <a href="mailto:{{legal.company_email}}">{{legal.company_email}}</a> | {{legal.company_address}}</p>
HTML,

                    'pl' => <<<'HTML'
<h2>1. Informacje ogólne</h2>
<p>Niniejszy Regulamin rejestracji domen ("Regulamin") reguluje rejestrację, odnowienie i transfer nazw domen realizowany przez <strong>{{legal.company_name}}</strong> (numer rejestracyjny: {{legal.company_number}}), zarejestrowaną pod adresem: {{legal.company_address}} ("my", "nas") w imieniu klienta ("Ty", "Rejestrujący").</p>
<p>Domeny rejestrowane są za pośrednictwem akredytowanego partnera rejestratorskiego <strong>Openprovider</strong>. Wszystkie rejestracje podlegają zasadom stosownych rejestrów i rejestratorów (w tym zasadom ICANN, warunkom Nominet dla domen .uk i zasadom odpowiednich krajowych rejestrów).</p>
<p>Składając zamówienie na domenę, potwierdzasz, że zapoznałeś się z niniejszym Regulaminem, rozumiesz go i akceptujesz.</p>

<h2>2. Wymagania i obowiązki Rejestrującego</h2>
<ul>
  <li>Musisz mieć ukończone 18 lat, aby zarejestrować nazwę domeny.</li>
  <li>Zapewniasz, że rejestracja nazwy domeny nie narusza praw własności intelektualnej osób trzecich, nie jest dokonywana w celach nadużycia, a wszystkie podane dane kontaktowe są dokładne i aktualne.</li>
  <li>Jesteś odpowiedzialny za utrzymanie aktualnych danych kontaktowych WHOIS/Rejestrującego. Podawanie fałszywych lub wprowadzających w błąd informacji kontaktowych może skutkować zawieszeniem lub anulowaniem domeny.</li>
  <li>W przypadku domen .uk musisz przestrzegać <a href="https://www.nominet.uk/go/terms" rel="noopener noreferrer">Regulaminu Nominet</a> i Polityki Rejestracji Domen.</li>
  <li>W przypadku ogólnych TLD (.com, .net, .org itp.) musisz przestrzegać praw i obowiązków Rejestrującego określonych przez ICANN.</li>
</ul>

<h2>3. Proces rejestracji i dostępność</h2>
<p>Dostępność domeny jest sprawdzana w czasie rzeczywistym w odpowiednim rejestrze. Domena wyglądająca na dostępną w chwili wyszukiwania <strong>nie jest gwarantowana jako wolna</strong> aż do momentu zakończenia rejestracji i otrzymania pełnej płatności.</p>
<p>Rejestracja jest uznana za zakończoną wyłącznie gdy:</p>
<ol>
  <li>Płatność została potwierdzona przez nasz procesor płatności (Stripe); oraz</li>
  <li>Domena została pomyślnie zarejestrowana w rejestrze i wysłano potwierdzenie e-mail.</li>
</ol>
<p>Minimalny okres rejestracji: <strong>1 rok</strong>. Maksymalny: 10 lat (jeśli rejestr na to zezwala).</p>

<h2>4. Ceny i płatności</h2>
<p>Wszystkie wyświetlane ceny obejmują naszą opłatę za usługę i są cenami netto (bez VAT, tam gdzie ma to zastosowanie). Cena w momencie potwierdzenia zamówienia jest wiążąca. Opłaty rejestracyjne są pobierane w walucie wyświetlonej przy zamówieniu.</p>
<p>Płatność jest przetwarzana za pośrednictwem <strong>Stripe</strong> (karta debetowa/kredytowa). Zamówienia są realizowane wyłącznie po pomyślnej autoryzacji płatności. W przypadku niepowodzenia płatności domena nie zostanie zarejestrowana i nie dokonano żadnej rezerwacji.</p>

<h2>5. Brak zwrotów za zarejestrowane domeny</h2>
<p>Opłaty rejestracyjne domeny są <strong>bezzwrotne</strong> po pomyślnej rejestracji domeny w rejestrze. Wynika to z faktu, że rejestracja domeny wiąże się z natychmiastowym, nieodwracalnym kosztem po stronie rejestratora i rejestru.</p>
<p>Jeśli błąd techniczny po naszej stronie spowoduje nieprawidłową rejestrację domeny, podejmiemy działania u rejestratora w celu rozwiązania problemu bez dodatkowych opłat.</p>
<p>Ustawowe prawa odstąpienia konsumenta wynikające z Rozporządzenia Consumer Contracts z 2013 r. nie mają zastosowania do rejestracji domen, ponieważ usługa jest wykonywana natychmiastowo po złożeniu zamówienia, a prawo do odstąpienia jest wyraźnie zrzeczone w momencie zakupu.</p>

<h2>6. Prywatność WHOIS</h2>
<p>Tam gdzie jest uwzględniona, prywatność WHOIS maskuje Twoje osobiste dane kontaktowe w publicznych wyszukiwaniach WHOIS poprzez zastąpienie ich danymi pośrednika rejestratora. Prywatność WHOIS jest zapewniana zgodnie z zasadami stosownego rejestru. Niektóre rejestry (np. Nominet dla domen .uk) mogą publikować dane rejestrującego niezależnie od ustawień prywatności.</p>
<p>Prywatność WHOIS nie wpływa na Twoją własność domeny ani Twoje obowiązki prawne jako Rejestrującego.</p>

<h2>7. Zarządzanie DNS</h2>
<p>Po rejestracji domena będzie wskazywać na nasze domyślne serwery nazw, chyba że podasz własne serwery nazw w momencie składania zamówienia. Możesz zaktualizować serwery nazw w dowolnym momencie za pośrednictwem portalu klienta. Zmiany DNS mogą potrwać do 48 godzin, zanim zostaną odzwierciedlone globalnie.</p>

<h2>8. Własność i kontrola domeny</h2>
<p>Jesteś zarejestrowanym właścicielem (Rejestrującym) domeny. Działamy jako Twój rejestrator i nie rościmy sobie prawa własności do Twojej domeny. Zachowujesz pełną kontrolę i możesz przenieść domenę do innego rejestratora w dowolnym momencie, z zastrzeżeniem ograniczeń określonych w sekcji 9.</p>

<h2>9. Zabronione użytkowanie</h2>
<p>Nie możesz używać żadnej domeny zarejestrowanej za naszym pośrednictwem do:</p>
<ul>
  <li>Dystrybucji złośliwego oprogramowania, oprogramowania szpiegującego lub ransomware</li>
  <li>Phishingu, spoofingu lub podszywania się pod inne organizacje</li>
  <li>Wysyłania niechcianych masowych wiadomości e-mail (spam)</li>
  <li>Obsługi botnetów lub infrastruktury dowodzenia i kontroli</li>
  <li>Pharmingu lub przejmowania DNS</li>
  <li>Wszelkich działań naruszających obowiązujące przepisy prawa</li>
  <li>Wszelkich działań naruszających prawa własności intelektualnej osób trzecich</li>
</ul>
<p>Zastrzegamy sobie prawo do zawieszenia lub anulowania domeny, o której stwierdzono naruszenie niniejszych ograniczeń, bez uprzedniego powiadomienia, zgodnie z naszą Polityką nadużyć DNS i obowiązującymi zasadami rejestru.</p>

<h2>10. Rozstrzyganie sporów</h2>
<p>W przypadku sporów dotyczących domen .uk zastosowanie ma <strong>Usługa Rozstrzygania Sporów Nominet (DRS)</strong>. W przypadku sporów dotyczących gTLD (.com, .net, .org itp.) spory są rozstrzygane zgodnie z <strong>Jednolitą Polityką Rozstrzygania Sporów Dotyczących Nazw Domen ICANN (UDRP)</strong>.</p>
<p>Nie arbitrujemy sporów dotyczących domen między rejestrującymi a roszczeniami stron trzecich. Takie spory muszą być kierowane do odpowiedniego organu rozstrzygania sporów.</p>

<h2>11. Ograniczenie odpowiedzialności</h2>
<p>Nie ponosimy odpowiedzialności za straty wynikające z niemożności zarejestrowania domeny, która była dostępna w momencie sprawdzania, ale została zajęta przed momentem rejestracji, awarii rejestru stron trzecich, zawieszenia domeny nałożonego przez rejestr lub ICANN, ani z nieodnowienia domeny przed jej wygaśnięciem.</p>
<p>Nasza łączna odpowiedzialność nie przekroczy opłaty rejestracyjnej zapłaconej za konkretną domenę będącą przedmiotem roszczenia.</p>

<h2>12. Prawo właściwe</h2>
<p>Niniejszy Regulamin podlega prawu Anglii i Walii. Wszelkie spory podlegają niewyłącznej jurysdykcji sądów angielskich i walijskich.</p>
<p>Kontakt: <a href="mailto:{{legal.company_email}}">{{legal.company_email}}</a> | {{legal.company_address}}</p>
HTML,

                    'pt' => <<<'HTML'
<h2>1. Sobre Estes Termos</h2>
<p>Estes Termos de Registo de Domínio ("Termos") regem o registo, renovação e transferência de nomes de domínio organizados pela <strong>{{legal.company_name}}</strong> (N.º de empresa: {{legal.company_number}}), registada em {{legal.company_address}} ("nós", "nos") em nome do cliente ("você", "Titular").</p>
<p>Os domínios são registados através do nosso parceiro registador acreditado <strong>Openprovider</strong>. Todos os registos estão sujeitos às políticas dos registos e registadores aplicáveis (incluindo as políticas da ICANN, os termos da Nominet para domínios .uk e as políticas do registo de código de país relevante).</p>
<p>Ao efetuar um pedido de domínio, confirma que leu, compreendeu e concorda com estes Termos.</p>

<h2>2. Elegibilidade e Obrigações do Titular</h2>
<ul>
  <li>Deve ter pelo menos 18 anos para registar um nome de domínio.</li>
  <li>Garante que o registo do nome de domínio não infringe quaisquer direitos de propriedade intelectual de terceiros, não está a ser registado para fins abusivos e que todas as informações de contacto fornecidas são exatas e atuais.</li>
  <li>É responsável pela manutenção de dados de contacto WHOIS/Titular precisos em todos os momentos. O fornecimento de informações de contacto falsas ou enganosas pode resultar na suspensão ou cancelamento do domínio.</li>
  <li>Para domínios .uk, deve cumprir os <a href="https://www.nominet.uk/go/terms" rel="noopener noreferrer">Termos e Condições da Nominet</a> e a Política de Registo de Domínios.</li>
  <li>Para TLDs genéricos (.com, .net, .org, etc.), deve cumprir os Direitos e Responsabilidades dos Titulares da ICANN.</li>
</ul>

<h2>3. Processo de Registo e Disponibilidade</h2>
<p>A disponibilidade do domínio é verificada em tempo real no registo relevante. Um domínio que apareça disponível no momento da pesquisa <strong>não é garantido como estando disponível</strong> até que o registo seja concluído e o pagamento tenha sido recebido na íntegra.</p>
<p>O registo é considerado completo apenas quando:</p>
<ol>
  <li>O pagamento foi confirmado pelo nosso processador de pagamentos (Stripe); e</li>
  <li>O domínio foi registado com sucesso no registo e foi enviado um e-mail de confirmação.</li>
</ol>
<p>Período mínimo de registo: <strong>1 ano</strong>. Máximo: 10 anos (onde permitido pelo registo).</p>

<h2>4. Preços e Pagamento</h2>
<p>Todos os preços apresentados incluem a nossa taxa de serviço e são líquidos de IVA (onde aplicável). O preço no momento da confirmação do pedido é vinculativo. As taxas de registo são cobradas na moeda apresentada no checkout.</p>
<p>O pagamento é processado via <strong>Stripe</strong> (cartão de débito/crédito). Os pedidos só são processados após autorização de pagamento bem-sucedida. Se o pagamento falhar, o domínio não será registado e não é feita qualquer reserva.</p>

<h2>5. Sem Reembolsos em Domínios Registados</h2>
<p>As taxas de registo de domínio são <strong>não reembolsáveis</strong> assim que um domínio seja registado com sucesso no registo. Isto deve-se ao facto de o registo de domínio envolver um custo imediato e irrecuperável para o registador e o registo.</p>
<p>Se um erro técnico resultar num domínio sendo registado incorretamente devido a culpa nossa, trabalharemos com o registador para resolver o problema sem custo adicional.</p>
<p>Os direitos estatutários de cancelamento do consumidor ao abrigo do Regulamento de Contratos com Consumidores de 2013 não se aplicam aos registos de domínio, uma vez que o serviço é executado imediatamente após o pedido e o direito de cancelamento é explicitamente renunciado no momento da compra.</p>

<h2>6. Privacidade WHOIS</h2>
<p>Onde incluída, a privacidade WHOIS mascara os seus dados de contacto pessoais nas pesquisas públicas de WHOIS, substituindo-os pelos dados de proxy do nosso registador. A privacidade WHOIS é fornecida de acordo com as políticas do registo aplicável. Alguns registos (por ex., Nominet para domínios .uk) podem publicar os dados do titular independentemente das definições de privacidade.</p>
<p>A privacidade WHOIS não afeta a sua titularidade do domínio nem as suas obrigações legais enquanto Titular.</p>

<h2>7. Gestão de DNS</h2>
<p>Após o registo, o seu domínio será apontado para os nossos servidores de nomes predefinidos, a menos que forneça servidores de nomes personalizados no momento do pedido. Pode atualizar os seus servidores de nomes a qualquer momento através do portal do cliente. As alterações de DNS podem demorar até 48 horas a propagar-se globalmente.</p>

<h2>8. Propriedade e Controlo do Domínio</h2>
<p>É o proprietário registado (Titular) do domínio. Atuamos como seu registador e não reivindicamos a propriedade do seu domínio. Mantém o controlo total e pode transferir o seu domínio para outro registador a qualquer momento, sujeito às restrições da secção 9.</p>

<h2>9. Usos Proibidos</h2>
<p>Não pode utilizar nenhum domínio registado através de nós para:</p>
<ul>
  <li>Distribuição de malware, spyware ou ransomware</li>
  <li>Phishing, spoofing ou personificação de outras organizações</li>
  <li>Envio de e-mail em massa não solicitado (spam)</li>
  <li>Operação de botnets ou infraestrutura de comando e controlo</li>
  <li>Pharming ou sequestro de DNS</li>
  <li>Qualquer atividade que viole as leis ou regulamentos aplicáveis</li>
  <li>Qualquer atividade que infrinja os direitos de propriedade intelectual de terceiros</li>
</ul>
<p>Reservamo-nos o direito de suspender ou cancelar qualquer domínio que se verifique estar em violação destas restrições sem aviso prévio, em conformidade com a nossa Política de Abuso de DNS e as regras do registo aplicável.</p>

<h2>10. Resolução de Litígios</h2>
<p>Para litígios de domínios .uk, aplica-se o <strong>Serviço de Resolução de Litígios da Nominet (DRS)</strong>. Para litígios de gTLD (.com, .net, .org, etc.), os litígios são tratados ao abrigo da <strong>Política de Resolução de Litígios de Nomes de Domínio da ICANN (UDRP)</strong>.</p>
<p>Não arbitramos litígios de domínios entre titulares e reclamantes terceiros. Esses litígios devem ser submetidos ao organismo de resolução de litígios relevante.</p>

<h2>11. Limitação de Responsabilidade</h2>
<p>Não nos responsabilizamos por quaisquer perdas resultantes da impossibilidade de registar um domínio que estava disponível no momento da verificação mas foi ocupado antes do registo, interrupções de registos de terceiros, suspensão de domínio imposta por um registo ou pela ICANN, ou pela sua falha em renovar um domínio antes do prazo de expiração.</p>
<p>A nossa responsabilidade total não excederá a taxa de registo paga pelo domínio específico que deu origem ao pedido de indemnização.</p>

<h2>12. Lei Aplicável</h2>
<p>Estes Termos são regidos pelas leis de Inglaterra e do País de Gales. Quaisquer litígios estão sujeitos à jurisdição não exclusiva dos tribunais de Inglaterra e do País de Gales.</p>
<p>Contacte-nos: <a href="mailto:{{legal.company_email}}">{{legal.company_email}}</a> | {{legal.company_address}}</p>
HTML,
                ],
            ],

            // ─── Domain Renewal Policy ────────────────────────────────────────
            [
                'slug' => 'domain-renewal-policy',
                'status' => 'published',
                'type' => 'policy',
                'show_in_footer' => false,
                'sort_order' => 11,
                'created_by' => $admin?->id,
                'published_at' => now(),
                'effective_date' => '2026-05-29',
                'version' => '1.0',

                'title' => [
                    'en' => 'Domain Renewal Policy',
                    'pl' => 'Polityka odnowień domen',
                    'pt' => 'Política de Renovação de Domínio',
                ],
                'meta_title' => [
                    'en' => 'Domain Renewal Policy | WebsiteExpert',
                    'pl' => 'Polityka odnowień domen | WebsiteExpert',
                    'pt' => 'Política de Renovação de Domínio | WebsiteExpert',
                ],
                'meta_description' => [
                    'en' => 'How {{legal.company_name}} handles domain renewals, expiry reminders, grace periods, and domain deletion.',
                    'pl' => 'Jak {{legal.company_name}} zarządza odnowieniami domen, przypomnieniami o wygaśnięciu, okresami karencji i usuwaniem domen.',
                    'pt' => 'Como a {{legal.company_name}} gere as renovações de domínio, lembretes de expiração, períodos de carência e eliminação de domínios.',
                ],
                'content' => [
                    'en' => <<<'HTML'
<h2>1. Auto-Renewal</h2>
<p>Domain auto-renewal is <strong>disabled by default</strong>. You are solely responsible for renewing your domain before it expires. We strongly recommend enabling renewal reminders in your client portal to avoid unintentional expiry.</p>
<p>If you wish to enable auto-renewal for a domain, you may do so at any time from your portal. Auto-renewal will be attempted 30 days before the expiry date using the payment method on file. If the auto-renewal payment fails, we will notify you immediately and the domain will return to manual renewal status.</p>

<h2>2. Renewal Reminders</h2>
<p>We will send renewal reminder notifications to your registered email address at the following intervals before your domain expires:</p>
<ul>
  <li><strong>30 days</strong> before expiry</li>
  <li><strong>14 days</strong> before expiry</li>
  <li><strong>7 days</strong> before expiry</li>
  <li><strong>1 day</strong> before expiry</li>
</ul>
<p>It is your responsibility to ensure your registered email address is current and that reminder emails are not filtered as spam. Failure to receive a reminder email does not relieve you of the obligation to renew your domain on time.</p>

<h2>3. Renewal Window</h2>
<p>You may renew your domain at any time during the final 90 days before the expiry date and for up to 30 days after expiry (the "Renewal Grace Period"), where the registry permits. Renewal prices are as displayed in your client portal at the time of renewal and are charged in the currency shown there.</p>

<h2>4. Domain Expiry</h2>
<p>If a domain is not renewed before its expiry date:</p>
<ul>
  <li>The domain will <strong>stop resolving</strong> — your website and associated email addresses will become unavailable</li>
  <li>The domain enters a <strong>Renewal Grace Period</strong> (typically 0–30 days, registry-dependent) during which you may still renew, usually at the standard renewal price</li>
  <li>After the grace period, the domain may enter a <strong>Redemption Period</strong> (typically 30 days) during which recovery is possible but may incur a significantly higher redemption fee charged by the registry</li>
  <li>After the redemption period, the domain is <strong>permanently deleted</strong> and released for public registration</li>
</ul>
<p>Timeline varies by registry. We strongly recommend renewing well in advance of the expiry date.</p>

<h2>5. Redemption &amp; Recovery Fees</h2>
<p>Recovering a domain during the Redemption Period involves additional fees imposed by the upstream registry. These fees are passed on to you in full and are separate from the standard renewal fee. Redemption fees are non-negotiable and non-refundable.</p>

<h2>6. No Liability for Expired Domains</h2>
<p>We are not liable for any loss, damage, or consequential harm arising from the expiry, suspension, or deletion of a domain, including loss of website traffic, email service disruption, or loss of business resulting from domain downtime.</p>
<p>It is your responsibility to maintain accurate renewal dates and act promptly on renewal reminders.</p>

<h2>7. Renewal Pricing</h2>
<p>Renewal prices may differ from registration prices. Current renewal prices are displayed in your client portal and on our domain pricing page. Prices are subject to change; we will notify you of any price changes at least 30 days before your next scheduled renewal.</p>

<h2>8. Contact</h2>
<p>For questions about domain renewals: <a href="mailto:{{legal.company_email}}">{{legal.company_email}}</a></p>
HTML,

                    'pl' => <<<'HTML'
<h2>1. Automatyczne odnawianie</h2>
<p>Automatyczne odnawianie domen jest domyślnie <strong>wyłączone</strong>. Jesteś wyłącznie odpowiedzialny za odnowienie swojej domeny przed jej wygaśnięciem. Zdecydowanie zalecamy włączenie przypomnień o odnowieniu w portalu klienta, aby uniknąć niezamierzonego wygaśnięcia.</p>
<p>Jeśli chcesz włączyć automatyczne odnawianie domeny, możesz to zrobić w dowolnym momencie z poziomu portalu. Automatyczne odnowienie zostanie wykonane 30 dni przed datą wygaśnięcia przy użyciu metody płatności zapisanej na koncie. Jeśli płatność za automatyczne odnowienie nie powiedzie się, natychmiast Cię powiadomimy, a domena wróci do statusu ręcznego odnowienia.</p>

<h2>2. Przypomnienia o odnowieniu</h2>
<p>Będziemy wysyłać powiadomienia z przypomnieniem o odnowieniu na Twój zarejestrowany adres e-mail w następujących terminach przed wygaśnięciem domeny:</p>
<ul>
  <li><strong>30 dni</strong> przed wygaśnięciem</li>
  <li><strong>14 dni</strong> przed wygaśnięciem</li>
  <li><strong>7 dni</strong> przed wygaśnięciem</li>
  <li><strong>1 dzień</strong> przed wygaśnięciem</li>
</ul>
<p>Twoją odpowiedzialnością jest zapewnienie aktualności zarejestrowanego adresu e-mail i tego, że wiadomości e-mail z przypomnieniami nie są filtrowane jako spam. Nieotrzymanie wiadomości z przypomnieniem nie zwalnia Cię z obowiązku terminowego odnowienia domeny.</p>

<h2>3. Okno odnowienia</h2>
<p>Możesz odnowić swoją domenę w dowolnym momencie w ciągu ostatnich 90 dni przed datą wygaśnięcia i do 30 dni po wygaśnięciu ("Okres karencji odnowienia"), tam gdzie rejestr na to pozwala. Ceny odnowień są takie, jak wyświetlane w Twoim portalu klienta w momencie odnowienia i są pobierane w pokazanej tam walucie.</p>

<h2>4. Wygaśnięcie domeny</h2>
<p>Jeśli domena nie zostanie odnowiona przed datą wygaśnięcia:</p>
<ul>
  <li>Domena <strong>przestanie rozwiązywać</strong> — Twoja strona internetowa i powiązane adresy e-mail staną się niedostępne</li>
  <li>Domena wchodzi w <strong>Okres karencji odnowienia</strong> (zazwyczaj 0–30 dni, zależnie od rejestru), podczas którego nadal możesz ją odnowić, zazwyczaj po standardowej cenie odnowienia</li>
  <li>Po upływie okresu karencji domena może wejść w <strong>Okres wykupu</strong> (zazwyczaj 30 dni), podczas którego odzyskanie jest możliwe, ale może wiązać się ze znacznie wyższą opłatą za wykup naliczoną przez rejestr</li>
  <li>Po upływie okresu wykupu domena jest <strong>trwale usuwana</strong> i udostępniana do publicznej rejestracji</li>
</ul>
<p>Harmonogram różni się w zależności od rejestru. Zdecydowanie zalecamy odnowienie na długo przed datą wygaśnięcia.</p>

<h2>5. Opłaty za wykup i odzyskanie</h2>
<p>Odzyskanie domeny podczas Okresu wykupu wiąże się z dodatkowymi opłatami nałożonymi przez nadrzędny rejestr. Opłaty te są w całości przenoszone na Ciebie i są oddzielne od standardowej opłaty za odnowienie. Opłaty za wykup są nienegocjowalne i bezzwrotne.</p>

<h2>6. Brak odpowiedzialności za wygasłe domeny</h2>
<p>Nie ponosimy odpowiedzialności za jakiekolwiek straty, szkody ani następcze szkody wynikające z wygaśnięcia, zawieszenia lub usunięcia domeny, w tym utratę ruchu na stronie internetowej, zakłócenie usługi e-mail lub utratę biznesu wynikającą z przestoju domeny.</p>
<p>Twoją odpowiedzialnością jest utrzymywanie aktualnych dat odnowienia i szybkie reagowanie na przypomnienia o odnowieniu.</p>

<h2>7. Ceny odnowień</h2>
<p>Ceny odnowień mogą różnić się od cen rejestracji. Aktualne ceny odnowień są wyświetlane w Twoim portalu klienta i na naszej stronie z cennikiem domen. Ceny mogą ulec zmianie; powiadomimy Cię o wszelkich zmianach cen co najmniej 30 dni przed kolejnym planowanym odnowieniem.</p>

<h2>8. Kontakt</h2>
<p>W sprawach dotyczących odnowień domen: <a href="mailto:{{legal.company_email}}">{{legal.company_email}}</a></p>
HTML,

                    'pt' => <<<'HTML'
<h2>1. Renovação Automática</h2>
<p>A renovação automática de domínio está <strong>desativada por predefinição</strong>. É da sua exclusiva responsabilidade renovar o seu domínio antes de expirar. Recomendamos vivamente que ative os lembretes de renovação no seu portal de cliente para evitar a expiração não intencional.</p>
<p>Se pretender ativar a renovação automática para um domínio, pode fazê-lo a qualquer momento a partir do seu portal. A renovação automática será tentada 30 dias antes da data de expiração utilizando o método de pagamento registado. Se o pagamento da renovação automática falhar, notificá-lo-emos imediatamente e o domínio regressará ao estado de renovação manual.</p>

<h2>2. Lembretes de Renovação</h2>
<p>Enviaremos notificações de lembrete de renovação para o seu endereço de e-mail registado nos seguintes intervalos antes da expiração do seu domínio:</p>
<ul>
  <li><strong>30 dias</strong> antes da expiração</li>
  <li><strong>14 dias</strong> antes da expiração</li>
  <li><strong>7 dias</strong> antes da expiração</li>
  <li><strong>1 dia</strong> antes da expiração</li>
</ul>
<p>É da sua responsabilidade garantir que o seu endereço de e-mail registado está atualizado e que os e-mails de lembrete não são filtrados como spam. O facto de não receber um e-mail de lembrete não o isenta da obrigação de renovar o seu domínio atempadamente.</p>

<h2>3. Janela de Renovação</h2>
<p>Pode renovar o seu domínio a qualquer momento durante os últimos 90 dias antes da data de expiração e até 30 dias após a expiração (o "Período de Carência de Renovação"), onde o registo o permitir. Os preços de renovação são os apresentados no seu portal de cliente no momento da renovação e são cobrados na moeda aí indicada.</p>

<h2>4. Expiração do Domínio</h2>
<p>Se um domínio não for renovado antes da sua data de expiração:</p>
<ul>
  <li>O domínio <strong>deixará de resolver</strong> — o seu website e os endereços de e-mail associados ficarão indisponíveis</li>
  <li>O domínio entra num <strong>Período de Carência de Renovação</strong> (normalmente 0–30 dias, dependente do registo) durante o qual ainda pode renovar, geralmente ao preço de renovação padrão</li>
  <li>Após o período de carência, o domínio pode entrar num <strong>Período de Resgate</strong> (normalmente 30 dias) durante o qual a recuperação é possível, mas pode incorrer numa taxa de resgate significativamente mais elevada cobrada pelo registo</li>
  <li>Após o período de resgate, o domínio é <strong>eliminado permanentemente</strong> e disponibilizado para registo público</li>
</ul>
<p>O calendário varia consoante o registo. Recomendamos vivamente a renovação com bastante antecedência em relação à data de expiração.</p>

<h2>5. Taxas de Resgate e Recuperação</h2>
<p>A recuperação de um domínio durante o Período de Resgate envolve taxas adicionais impostas pelo registo a montante. Estas taxas são-lhe integralmente repercutidas e são separadas da taxa de renovação padrão. As taxas de resgate não são negociáveis nem reembolsáveis.</p>

<h2>6. Sem Responsabilidade por Domínios Expirados</h2>
<p>Não somos responsáveis por quaisquer perdas, danos ou danos consequenciais resultantes da expiração, suspensão ou eliminação de um domínio, incluindo perda de tráfego no website, interrupção do serviço de e-mail ou perda de negócio resultante de inatividade do domínio.</p>
<p>É da sua responsabilidade manter datas de renovação precisas e agir prontamente com base nos lembretes de renovação.</p>

<h2>7. Preços de Renovação</h2>
<p>Os preços de renovação podem diferir dos preços de registo. Os preços de renovação atuais são apresentados no seu portal de cliente e na nossa página de preços de domínio. Os preços estão sujeitos a alterações; notificá-lo-emos de quaisquer alterações de preços pelo menos 30 dias antes da sua próxima renovação programada.</p>

<h2>8. Contacto</h2>
<p>Para questões sobre renovações de domínio: <a href="mailto:{{legal.company_email}}">{{legal.company_email}}</a></p>
HTML,
                ],
            ],

            // ─── Domain Transfer Policy ───────────────────────────────────────
            [
                'slug' => 'domain-transfer-policy',
                'status' => 'published',
                'type' => 'policy',
                'show_in_footer' => false,
                'sort_order' => 12,
                'created_by' => $admin?->id,
                'published_at' => now(),
                'effective_date' => '2026-05-29',
                'version' => '1.0',

                'title' => [
                    'en' => 'Domain Transfer Policy',
                    'pl' => 'Zasady transferu domen',
                    'pt' => 'Política de Transferência de Domínio',
                ],
                'meta_title' => [
                    'en' => 'Domain Transfer Policy | WebsiteExpert',
                    'pl' => 'Zasady transferu domen | WebsiteExpert',
                    'pt' => 'Política de Transferência de Domínio | WebsiteExpert',
                ],
                'meta_description' => [
                    'en' => 'Terms governing inbound domain transfers to {{legal.company_name}} and outbound transfers away from us.',
                    'pl' => 'Zasady dotyczące transferu domen do {{legal.company_name}} i transferu domen do innego rejestratora.',
                    'pt' => 'Termos que regem as transferências de domínio de entrada para a {{legal.company_name}} e as transferências de saída.',
                ],
                'content' => [
                    'en' => <<<'HTML'
<h2>1. Transfer-In (Moving Your Domain to Us)</h2>
<p>You may transfer an existing domain from another registrar to {{legal.company_name}} / Openprovider. By initiating a transfer, you authorise us to act as the registrar of record for the domain.</p>

<h3>Requirements for Transfer-In</h3>
<ul>
  <li>The domain must be <strong>at least 60 days old</strong> since initial registration or last transfer (ICANN policy)</li>
  <li>The domain must <strong>not be expired, suspended, or locked</strong></li>
  <li>You must obtain a valid <strong>EPP/Auth code</strong> (also known as an authorisation code or transfer key) from your current registrar</li>
  <li>The domain must have <strong>more than 14 days remaining</strong> before expiry at the time of transfer initiation</li>
  <li>WHOIS registrant contact details must match what is on file — transfers can fail if details are incorrect or outdated</li>
</ul>

<h3>Transfer Process</h3>
<ol>
  <li>Submit a transfer order through your client portal, providing the EPP/Auth code</li>
  <li>Payment of the transfer fee is required in advance</li>
  <li>The transfer request is submitted to the registry; your current registrar has <strong>5 calendar days</strong> to approve or reject the request</li>
  <li>Once approved, the transfer is completed and the domain is added to your account — typically within <strong>5–7 days</strong> from initiation</li>
  <li>Most domain transfers extend the registration period by <strong>1 year</strong> (added to the current expiry date)</li>
</ol>

<h3>Transfer Fees</h3>
<p>Transfer fees are non-refundable once the transfer has been submitted to the registry, regardless of whether the transfer succeeds or fails due to issues outside our control (e.g. rejection by the current registrar, incorrect EPP code, domain lock).</p>
<p>If a transfer fails due to our error, we will initiate a re-transfer at no additional charge or issue a full refund.</p>

<h2>2. Transfer-Out (Moving Your Domain Away from Us)</h2>
<p>You have the right to transfer your domain to another registrar at any time, subject to the following conditions:</p>
<ul>
  <li>The domain must have been registered or last transferred <strong>at least 60 days ago</strong></li>
  <li>There must be <strong>no outstanding balance</strong> on your account</li>
  <li>The domain must not be expired or subject to a registry hold</li>
</ul>
<p>To initiate a transfer-out, log in to your client portal and request your EPP/Auth code. We will provide the code within <strong>1 business day</strong>. You must then initiate the transfer with your new registrar within the validity period of the code (typically 7–14 days).</p>
<p>We do not charge a fee for transferring your domain away. No refund is given for any unused portion of the registration period.</p>

<h2>3. .uk Domain Transfers (Nominet)</h2>
<p>For .uk domains, the transfer process differs and is governed by Nominet's IPS Tag system. To transfer a .uk domain away from us, you must ask your new registrar to request a tag change from us. We will process tag change requests within <strong>1 business day</strong> unless there is a valid reason to withhold (e.g. domain dispute, fraud investigation).</p>

<h2>4. Transfer Disputes</h2>
<p>If you believe an unauthorised transfer has been initiated for your domain, contact us immediately at <a href="mailto:{{legal.company_email}}">{{legal.company_email}}</a>. We will cooperate fully with the applicable registry to investigate and, where possible, reverse any unauthorised transfer.</p>

<h2>5. Limitation of Liability</h2>
<p>We are not liable for delays in transfer completion caused by the current or gaining registrar, incorrect EPP codes provided by the registrant, or registry policy restrictions. Our total liability in relation to any transfer shall not exceed the transfer fee paid.</p>

<h2>6. Contact</h2>
<p>For domain transfer enquiries: <a href="mailto:{{legal.company_email}}">{{legal.company_email}}</a></p>
HTML,

                    'pl' => <<<'HTML'
<h2>1. Transfer do nas (przeniesienie domeny do {{legal.company_name}})</h2>
<p>Możesz przenieść istniejącą domenę od innego rejestratora do {{legal.company_name}} / Openprovider. Inicjując transfer, upoważniasz nas do działania jako rejestrator domeny.</p>

<h3>Wymagania dotyczące transferu do nas</h3>
<ul>
  <li>Domena musi mieć <strong>co najmniej 60 dni</strong> od pierwszej rejestracji lub ostatniego transferu (zasada ICANN)</li>
  <li>Domena <strong>nie może być wygasła, zawieszona ani zablokowana</strong></li>
  <li>Musisz uzyskać ważny <strong>kod EPP/Auth</strong> (zwany też kodem autoryzacyjnym lub kluczem transferu) od swojego obecnego rejestratora</li>
  <li>W chwili inicjowania transferu domena musi mieć <strong>ponad 14 dni do wygaśnięcia</strong></li>
  <li>Dane kontaktowe WHOIS rejestrującego muszą być zgodne z aktualnymi — transfery mogą się nie powieść, jeśli dane są nieprawidłowe lub nieaktualne</li>
</ul>

<h3>Proces transferu</h3>
<ol>
  <li>Złóż zlecenie transferu przez portal klienta, podając kod EPP/Auth</li>
  <li>Opłata za transfer jest wymagana z góry</li>
  <li>Żądanie transferu jest przesyłane do rejestru; Twój obecny rejestrator ma <strong>5 dni kalendarzowych</strong> na zatwierdzenie lub odrzucenie żądania</li>
  <li>Po zatwierdzeniu transfer jest zakończony i domena zostaje dodana do Twojego konta — zazwyczaj w ciągu <strong>5–7 dni</strong> od inicjacji</li>
  <li>Większość transferów domen przedłuża okres rejestracji o <strong>1 rok</strong> (doliczany do aktualnej daty wygaśnięcia)</li>
</ol>

<h3>Opłaty transferowe</h3>
<p>Opłaty za transfer są bezzwrotne po przesłaniu transferu do rejestru, niezależnie od tego, czy transfer się powiedzie, czy nie z przyczyn poza naszą kontrolą (np. odrzucenie przez obecnego rejestratora, nieprawidłowy kod EPP, blokada domeny).</p>
<p>Jeśli transfer nie powiedzie się z naszego powodu, zainicjujemy ponowny transfer bez dodatkowych opłat lub wydamy pełny zwrot.</p>

<h2>2. Transfer od nas (przeniesienie domeny do innego rejestratora)</h2>
<p>Masz prawo przenieść domenę do innego rejestratora w dowolnym momencie, pod warunkiem spełnienia następujących warunków:</p>
<ul>
  <li>Domena musi być zarejestrowana lub ostatnio przeniesiona <strong>co najmniej 60 dni temu</strong></li>
  <li>Na Twoim koncie <strong>nie może być zaległych płatności</strong></li>
  <li>Domena nie może być wygasła ani objęta blokadą rejestru</li>
</ul>
<p>Aby zainicjować transfer, zaloguj się do portalu klienta i zażądaj kodu EPP/Auth. Udostępnimy kod w ciągu <strong>1 dnia roboczego</strong>. Następnie musisz zainicjować transfer u nowego rejestratora w okresie ważności kodu (zazwyczaj 7–14 dni).</p>
<p>Nie pobieramy opłaty za przeniesienie domeny. Nie zwracamy opłaty za niewykorzystaną część okresu rejestracji.</p>

<h2>3. Transfer domen .uk (Nominet)</h2>
<p>W przypadku domen .uk proces transferu jest inny i regulowany przez system tagów IPS Nominet. Aby przenieść domenę .uk od nas, musisz poprosić nowego rejestratora o żądanie zmiany tagu od nas. Realizujemy żądania zmiany tagu w ciągu <strong>1 dnia roboczego</strong>, chyba że istnieje uzasadniony powód odmowy (np. spór o domenę, dochodzenie w sprawie oszustwa).</p>

<h2>4. Spory dotyczące transferu</h2>
<p>Jeśli uważasz, że zainicjowano nieautoryzowany transfer Twojej domeny, skontaktuj się z nami natychmiast pod adresem <a href="mailto:{{legal.company_email}}">{{legal.company_email}}</a>. W pełni będziemy współpracować z odpowiednim rejestrem w celu zbadania sprawy i, jeśli to możliwe, odwrócenia nieautoryzowanego transferu.</p>

<h2>5. Ograniczenie odpowiedzialności</h2>
<p>Nie ponosimy odpowiedzialności za opóźnienia w realizacji transferu spowodowane przez obecnego lub nowego rejestratora, nieprawidłowe kody EPP dostarczone przez rejestrującego ani ograniczenia polityki rejestru. Nasza łączna odpowiedzialność w związku z jakimkolwiek transferem nie przekroczy zapłaconej opłaty transferowej.</p>

<h2>6. Kontakt</h2>
<p>W sprawach dotyczących transferu domen: <a href="mailto:{{legal.company_email}}">{{legal.company_email}}</a></p>
HTML,

                    'pt' => <<<'HTML'
<h2>1. Transferência de Entrada (Mover o Seu Domínio para Nós)</h2>
<p>Pode transferir um domínio existente de outro registador para a {{legal.company_name}} / Openprovider. Ao iniciar uma transferência, autoriza-nos a atuar como registador de registo para o domínio.</p>

<h3>Requisitos para Transferência de Entrada</h3>
<ul>
  <li>O domínio deve ter <strong>pelo menos 60 dias</strong> desde o registo inicial ou última transferência (política da ICANN)</li>
  <li>O domínio <strong>não pode estar expirado, suspenso ou bloqueado</strong></li>
  <li>Deve obter um <strong>código EPP/Auth</strong> válido (também conhecido como código de autorização ou chave de transferência) do seu registador atual</li>
  <li>O domínio deve ter <strong>mais de 14 dias restantes</strong> antes da expiração no momento do início da transferência</li>
  <li>Os dados de contacto do titular no WHOIS devem corresponder ao que está registado — as transferências podem falhar se os dados estiverem incorretos ou desatualizados</li>
</ul>

<h3>Processo de Transferência</h3>
<ol>
  <li>Submeta um pedido de transferência através do seu portal de cliente, fornecendo o código EPP/Auth</li>
  <li>O pagamento da taxa de transferência é exigido antecipadamente</li>
  <li>O pedido de transferência é submetido ao registo; o seu registador atual tem <strong>5 dias de calendário</strong> para aprovar ou rejeitar o pedido</li>
  <li>Uma vez aprovada, a transferência é concluída e o domínio é adicionado à sua conta — normalmente no prazo de <strong>5–7 dias</strong> após o início</li>
  <li>A maioria das transferências de domínio alargam o período de registo em <strong>1 ano</strong> (adicionado à data de expiração atual)</li>
</ol>

<h3>Taxas de Transferência</h3>
<p>As taxas de transferência não são reembolsáveis assim que a transferência tenha sido submetida ao registo, independentemente de a transferência ter sucesso ou falhar por razões fora do nosso controlo (por ex., rejeição pelo registador atual, código EPP incorreto, bloqueio do domínio).</p>
<p>Se uma transferência falhar devido a um erro nosso, iniciaremos uma nova transferência sem custo adicional ou emitiremos um reembolso total.</p>

<h2>2. Transferência de Saída (Mover o Seu Domínio para Outro Registador)</h2>
<p>Tem o direito de transferir o seu domínio para outro registador a qualquer momento, sujeito às seguintes condições:</p>
<ul>
  <li>O domínio deve ter sido registado ou transferido pela última vez <strong>há pelo menos 60 dias</strong></li>
  <li>Não deve existir <strong>nenhum saldo em dívida</strong> na sua conta</li>
  <li>O domínio não pode estar expirado ou sujeito a uma retenção do registo</li>
</ul>
<p>Para iniciar uma transferência de saída, inicie sessão no seu portal de cliente e solicite o seu código EPP/Auth. Forneceremos o código no prazo de <strong>1 dia útil</strong>. Em seguida, deve iniciar a transferência com o seu novo registador dentro do período de validade do código (normalmente 7–14 dias).</p>
<p>Não cobramos uma taxa pela transferência do seu domínio. Não é dado reembolso por qualquer parte não utilizada do período de registo.</p>

<h2>3. Transferências de Domínios .uk (Nominet)</h2>
<p>Para domínios .uk, o processo de transferência é diferente e é regido pelo sistema de etiquetas IPS da Nominet. Para transferir um domínio .uk de nós, deve pedir ao seu novo registador que solicite uma mudança de etiqueta. Processaremos os pedidos de mudança de etiqueta no prazo de <strong>1 dia útil</strong>, a menos que exista uma razão válida para recusar (por ex., litígio de domínio, investigação de fraude).</p>

<h2>4. Litígios de Transferência</h2>
<p>Se acreditar que foi iniciada uma transferência não autorizada para o seu domínio, contacte-nos imediatamente em <a href="mailto:{{legal.company_email}}">{{legal.company_email}}</a>. Cooperaremos plenamente com o registo aplicável para investigar e, sempre que possível, reverter qualquer transferência não autorizada.</p>

<h2>5. Limitação de Responsabilidade</h2>
<p>Não somos responsáveis por atrasos na conclusão da transferência causados pelo registador atual ou novo, códigos EPP incorretos fornecidos pelo titular, ou restrições de política do registo. A nossa responsabilidade total em relação a qualquer transferência não excederá a taxa de transferência paga.</p>

<h2>6. Contacto</h2>
<p>Para questões sobre transferência de domínios: <a href="mailto:{{legal.company_email}}">{{legal.company_email}}</a></p>
HTML,
                ],
            ],

            // ─── DNS Abuse Policy ─────────────────────────────────────────────
            [
                'slug' => 'dns-abuse-policy',
                'status' => 'published',
                'type' => 'policy',
                'show_in_footer' => false,
                'sort_order' => 13,
                'created_by' => $admin?->id,
                'published_at' => now(),
                'effective_date' => '2026-05-29',
                'version' => '1.0',

                'title' => [
                    'en' => 'DNS Abuse Policy',
                    'pl' => 'Polityka nadużyć DNS',
                    'pt' => 'Política de Abuso de DNS',
                ],
                'meta_title' => [
                    'en' => 'DNS Abuse Policy | WebsiteExpert',
                    'pl' => 'Polityka nadużyć DNS | WebsiteExpert',
                    'pt' => 'Política de Abuso de DNS | WebsiteExpert',
                ],
                'meta_description' => [
                    'en' => 'How {{legal.company_name}} defines, detects, and responds to DNS abuse including malware, phishing, spam, and botnets.',
                    'pl' => 'Jak {{legal.company_name}} definiuje, wykrywa i reaguje na nadużycia DNS, w tym złośliwe oprogramowanie, phishing, spam i botnety.',
                    'pt' => 'Como a {{legal.company_name}} define, detecta e responde a abusos de DNS, incluindo malware, phishing, spam e botnets.',
                ],
                'content' => [
                    'en' => <<<'HTML'
<h2>1. Our Commitment</h2>
<p>{{legal.company_name}} is committed to maintaining the security and integrity of the Domain Name System (DNS). We take a zero-tolerance approach to DNS abuse and comply with all applicable ICANN contractual commitments, Nominet policies for .uk domains, and the DNS Abuse Framework published by the Global Anti-Scam Alliance (GASA) and the DNS Abuse Institute.</p>

<h2>2. What Is DNS Abuse?</h2>
<p>DNS abuse is the use of domain names or DNS infrastructure to facilitate or enable malicious activity. We recognise the following as forms of DNS abuse:</p>
<ul>
  <li><strong>Malware:</strong> using a domain to distribute or host malicious software, ransomware, trojans, keyloggers, or exploit kits</li>
  <li><strong>Phishing:</strong> using a domain to deceive users into disclosing credentials, financial information, or personal data by impersonating a legitimate brand or institution</li>
  <li><strong>Pharming:</strong> redirecting DNS queries to fraudulent websites to harvest sensitive data or inject malware</li>
  <li><strong>Spam:</strong> using a domain as the sender identity in unsolicited bulk email campaigns — particularly where the domain is registered solely for this purpose (spam domain registration)</li>
  <li><strong>Botnets:</strong> using a domain as command-and-control (C2) infrastructure for a botnet</li>
  <li><strong>Fast-flux hosting:</strong> rapidly changing DNS records to evade detection while hosting abusive content</li>
</ul>
<p><strong>Content-related disputes</strong> (e.g. trademark infringement, defamation, illegal but non-technical-abuse content) are not within scope of this DNS Abuse Policy and are addressed separately under our Terms and Conditions and applicable law.</p>

<h2>3. How We Respond to Abuse Reports</h2>
<p>Upon receiving a credible abuse report, we will:</p>
<ol>
  <li><strong>Acknowledge</strong> the report within <strong>1 business day</strong></li>
  <li><strong>Investigate</strong> the report by reviewing available evidence, checking abuse intelligence feeds, and consulting with our registrar partner (Openprovider)</li>
  <li><strong>Take action</strong> within <strong>2 business days</strong> of confirming the abuse, which may include:
    <ul>
      <li>Contacting the domain registrant to demand immediate remediation</li>
      <li>Suspending the domain's DNS resolution (removing it from the zone) pending investigation</li>
      <li>Cancelling and deleting the domain registration in cases of severe or repeat abuse</li>
      <li>Reporting the matter to law enforcement or relevant cyber-security bodies (e.g. NCSC, Action Fraud, CERT)</li>
    </ul>
  </li>
  <li><strong>Notify the reporter</strong> of the outcome where permitted by law</li>
</ol>
<p>We may take immediate action (including DNS suspension) without prior notice to the registrant where there is an imminent risk of harm to users or the integrity of the DNS.</p>

<h2>4. How to Report Abuse</h2>
<p>To report DNS abuse involving a domain registered through {{legal.company_name}}:</p>
<ul>
  <li><strong>Email:</strong> <a href="mailto:{{legal.company_email}}">{{legal.company_email}}</a> — please use the subject line "DNS Abuse Report"</li>
</ul>
<p>Please include in your report:</p>
<ul>
  <li>The domain name(s) involved</li>
  <li>A description of the abuse (type, evidence, URLs, email headers as applicable)</li>
  <li>Your contact details for follow-up</li>
  <li>Any supporting evidence (screenshots, malware hashes, WHOIS data)</li>
</ul>
<p>Reports that lack sufficient evidence may not result in action. Reports made in bad faith (e.g. to disrupt a competitor) may be referred to law enforcement.</p>

<h2>5. Third-Party Abuse Resources</h2>
<p>You may also report DNS abuse directly to:</p>
<ul>
  <li><strong>Nominet</strong> (for .uk domains): <a href="https://www.nominet.uk/report-abuse/" rel="noopener noreferrer">nominet.uk/report-abuse</a></li>
  <li><strong>ICANN</strong> (for gTLDs): <a href="https://www.icann.org/compliance/complaint" rel="noopener noreferrer">icann.org/compliance/complaint</a></li>
  <li><strong>Action Fraud</strong> (UK): <a href="https://www.actionfraud.police.uk" rel="noopener noreferrer">actionfraud.police.uk</a></li>
  <li><strong>NCSC</strong> (UK): <a href="https://www.ncsc.gov.uk/section/about-ncsc/report-incident" rel="noopener noreferrer">ncsc.gov.uk</a></li>
  <li><strong>PhishTank</strong>: <a href="https://www.phishtank.com" rel="noopener noreferrer">phishtank.com</a></li>
</ul>

<h2>6. Policy Review</h2>
<p>This policy is reviewed annually or following material changes to ICANN or Nominet requirements. Last reviewed: <strong>May 2026</strong>.</p>
HTML,

                    'pl' => <<<'HTML'
<h2>1. Nasze zobowiązanie</h2>
<p>{{legal.company_name}} zobowiązuje się do utrzymania bezpieczeństwa i integralności Systemu Nazw Domen (DNS). Stosujemy politykę zerowej tolerancji wobec nadużyć DNS i przestrzegamy wszystkich obowiązujących zobowiązań umownych ICANN, zasad Nominet dla domen .uk oraz Ram DNS Abuse opublikowanych przez Global Anti-Scam Alliance (GASA) i DNS Abuse Institute.</p>

<h2>2. Co to jest nadużycie DNS?</h2>
<p>Nadużycie DNS polega na używaniu nazw domen lub infrastruktury DNS do ułatwiania lub umożliwiania złośliwych działań. Uznajemy następujące formy nadużyć DNS:</p>
<ul>
  <li><strong>Złośliwe oprogramowanie (malware):</strong> używanie domeny do dystrybucji lub hostowania złośliwego oprogramowania, ransomware, trojanów, keyloggerów lub zestawów exploitów</li>
  <li><strong>Phishing:</strong> używanie domeny w celu nakłonienia użytkowników do ujawnienia danych uwierzytelniających, informacji finansowych lub danych osobowych poprzez podszywanie się pod legalną markę lub instytucję</li>
  <li><strong>Pharming:</strong> przekierowywanie zapytań DNS do fałszywych stron w celu zbierania poufnych danych lub wstrzykiwania złośliwego oprogramowania</li>
  <li><strong>Spam:</strong> używanie domeny jako tożsamości nadawcy w niezamawianych kampaniach masowej poczty — szczególnie gdy domena jest zarejestrowana wyłącznie w tym celu</li>
  <li><strong>Botnety:</strong> używanie domeny jako infrastruktury dowodzenia i kontroli (C2) dla botnetu</li>
  <li><strong>Fast-flux hosting:</strong> szybkie zmienianie rekordów DNS w celu uniknięcia wykrycia przy jednoczesnym hostowaniu złośliwych treści</li>
</ul>
<p><strong>Spory dotyczące treści</strong> (np. naruszenie praw do znaków towarowych, zniesławienie, treści nielegalne, ale niebędące technicznym nadużyciem) nie wchodzą w zakres niniejszej Polityki nadużyć DNS i są regulowane oddzielnie na mocy naszego Regulaminu i obowiązujących przepisów.</p>

<h2>3. Jak reagujemy na zgłoszenia nadużyć</h2>
<p>Po otrzymaniu wiarygodnego zgłoszenia nadużycia podejmiemy następujące działania:</p>
<ol>
  <li><strong>Potwierdzimy</strong> odbiór zgłoszenia w ciągu <strong>1 dnia roboczego</strong></li>
  <li><strong>Zbadamy</strong> zgłoszenie, przeglądając dostępne dowody, sprawdzając strumienie informacji o nadużyciach i konsultując się z naszym partnerem rejestratorem (Openprovider)</li>
  <li><strong>Podejmiemy działania</strong> w ciągu <strong>2 dni roboczych</strong> od potwierdzenia nadużycia, które mogą obejmować:
    <ul>
      <li>Skontaktowanie się z rejestrującym domenę z żądaniem natychmiastowego naprawienia sytuacji</li>
      <li>Zawieszenie rozwiązywania DNS domeny (usunięcie ze strefy) do czasu zakończenia dochodzenia</li>
      <li>Anulowanie i usunięcie rejestracji domeny w przypadkach poważnych lub powtarzających się nadużyć</li>
      <li>Zgłoszenie sprawy organom ścigania lub właściwym organom cyberbezpieczeństwa (np. NCSC, Action Fraud, CERT)</li>
    </ul>
  </li>
  <li><strong>Poinformujemy zgłaszającego</strong> o wynikach, jeśli pozwalają na to przepisy prawa</li>
</ol>
<p>Możemy podjąć natychmiastowe działania (w tym zawieszenie DNS) bez wcześniejszego powiadomienia rejestrującego w przypadku bezpośredniego zagrożenia dla użytkowników lub integralności DNS.</p>

<h2>4. Jak zgłosić nadużycie</h2>
<p>Aby zgłosić nadużycie DNS dotyczące domeny zarejestrowanej przez {{legal.company_name}}:</p>
<ul>
  <li><strong>E-mail:</strong> <a href="mailto:{{legal.company_email}}">{{legal.company_email}}</a> — prosimy użyć tematu "Zgłoszenie nadużycia DNS"</li>
</ul>
<p>Prosimy dołączyć do zgłoszenia:</p>
<ul>
  <li>Nazwę(y) domeny</li>
  <li>Opis nadużycia (rodzaj, dowody, adresy URL, nagłówki wiadomości e-mail, jeśli dotyczy)</li>
  <li>Swoje dane kontaktowe do dalszych wyjaśnień</li>
  <li>Wszelkie dowody pomocnicze (zrzuty ekranu, sumy kontrolne złośliwego oprogramowania, dane WHOIS)</li>
</ul>
<p>Zgłoszenia pozbawione wystarczających dowodów mogą nie skutkować podjęciem działań. Zgłoszenia złożone w złej wierze (np. w celu zakłócenia działalności konkurencji) mogą być zgłoszone organom ścigania.</p>

<h2>5. Zewnętrzne zasoby dotyczące nadużyć</h2>
<p>Możesz również zgłosić nadużycia DNS bezpośrednio do:</p>
<ul>
  <li><strong>Nominet</strong> (domeny .uk): <a href="https://www.nominet.uk/report-abuse/" rel="noopener noreferrer">nominet.uk/report-abuse</a></li>
  <li><strong>ICANN</strong> (gTLD): <a href="https://www.icann.org/compliance/complaint" rel="noopener noreferrer">icann.org/compliance/complaint</a></li>
  <li><strong>Action Fraud</strong> (UK): <a href="https://www.actionfraud.police.uk" rel="noopener noreferrer">actionfraud.police.uk</a></li>
  <li><strong>NCSC</strong> (UK): <a href="https://www.ncsc.gov.uk/section/about-ncsc/report-incident" rel="noopener noreferrer">ncsc.gov.uk</a></li>
  <li><strong>PhishTank</strong>: <a href="https://www.phishtank.com" rel="noopener noreferrer">phishtank.com</a></li>
</ul>

<h2>6. Przegląd polityki</h2>
<p>Niniejsza polityka jest przeglądana corocznie lub po istotnych zmianach w wymogach ICANN lub Nominet. Ostatnia aktualizacja: <strong>maj 2026</strong>.</p>
HTML,

                    'pt' => <<<'HTML'
<h2>1. O Nosso Compromisso</h2>
<p>A {{legal.company_name}} compromete-se a manter a segurança e integridade do Sistema de Nomes de Domínio (DNS). Adotamos uma política de tolerância zero face ao abuso de DNS e cumprimos todos os compromissos contratuais da ICANN aplicáveis, as políticas da Nominet para domínios .uk e o Quadro de Abuso de DNS publicado pela Global Anti-Scam Alliance (GASA) e pelo DNS Abuse Institute.</p>

<h2>2. O Que É Abuso de DNS?</h2>
<p>O abuso de DNS é a utilização de nomes de domínio ou infraestrutura DNS para facilitar ou possibilitar atividade maliciosa. Reconhecemos as seguintes formas de abuso de DNS:</p>
<ul>
  <li><strong>Malware:</strong> utilização de um domínio para distribuir ou alojar software malicioso, ransomware, trojans, keyloggers ou kits de exploração</li>
  <li><strong>Phishing:</strong> utilização de um domínio para enganar utilizadores a divulgar credenciais, informações financeiras ou dados pessoais, fazendo-se passar por uma marca ou instituição legítima</li>
  <li><strong>Pharming:</strong> redirecionamento de consultas DNS para websites fraudulentos para recolher dados sensíveis ou injetar malware</li>
  <li><strong>Spam:</strong> utilização de um domínio como identidade de remetente em campanhas de e-mail em massa não solicitadas — especialmente quando o domínio é registado apenas para este fim</li>
  <li><strong>Botnets:</strong> utilização de um domínio como infraestrutura de comando e controlo (C2) para uma botnet</li>
  <li><strong>Fast-flux hosting:</strong> alteração rápida de registos DNS para evitar a deteção enquanto aloja conteúdo abusivo</li>
</ul>
<p><strong>Litígios relacionados com conteúdo</strong> (por ex., violação de marca registada, difamação, conteúdo ilegal mas não abusivo tecnicamente) não estão no âmbito desta Política de Abuso de DNS e são tratados separadamente ao abrigo dos nossos Termos e Condições e da legislação aplicável.</p>

<h2>3. Como Respondemos a Relatórios de Abuso</h2>
<p>Ao receber um relatório de abuso credível, iremos:</p>
<ol>
  <li><strong>Confirmar</strong> a receção do relatório no prazo de <strong>1 dia útil</strong></li>
  <li><strong>Investigar</strong> o relatório, revendo as evidências disponíveis, verificando os feeds de inteligência de abuso e consultando o nosso parceiro registador (Openprovider)</li>
  <li><strong>Tomar medidas</strong> no prazo de <strong>2 dias úteis</strong> após confirmar o abuso, que podem incluir:
    <ul>
      <li>Contactar o titular do domínio para exigir remediação imediata</li>
      <li>Suspender a resolução DNS do domínio (removendo-o da zona) durante a investigação</li>
      <li>Cancelar e eliminar o registo do domínio em casos de abuso grave ou repetido</li>
      <li>Reportar o assunto às autoridades competentes ou organismos de cibersegurança relevantes (por ex., NCSC, Action Fraud, CERT)</li>
    </ul>
  </li>
  <li><strong>Notificar o denunciante</strong> sobre o resultado quando permitido por lei</li>
</ol>
<p>Podemos tomar medidas imediatas (incluindo suspensão de DNS) sem aviso prévio ao titular nos casos em que exista um risco iminente para os utilizadores ou para a integridade do DNS.</p>

<h2>4. Como Reportar Abuso</h2>
<p>Para reportar abuso de DNS envolvendo um domínio registado através da {{legal.company_name}}:</p>
<ul>
  <li><strong>E-mail:</strong> <a href="mailto:{{legal.company_email}}">{{legal.company_email}}</a> — por favor utilize o assunto "Relatório de Abuso de DNS"</li>
</ul>
<p>Por favor inclua no seu relatório:</p>
<ul>
  <li>O(s) nome(s) de domínio envolvidos</li>
  <li>Uma descrição do abuso (tipo, evidências, URLs, cabeçalhos de e-mail conforme aplicável)</li>
  <li>Os seus dados de contacto para seguimento</li>
  <li>Quaisquer evidências de suporte (capturas de ecrã, hashes de malware, dados WHOIS)</li>
</ul>
<p>Relatórios sem evidências suficientes podem não resultar em ação. Relatórios feitos de má-fé (por ex., para perturbar um concorrente) podem ser encaminhados para as autoridades competentes.</p>

<h2>5. Recursos de Abuso de Terceiros</h2>
<p>Pode também reportar abuso de DNS diretamente a:</p>
<ul>
  <li><strong>Nominet</strong> (domínios .uk): <a href="https://www.nominet.uk/report-abuse/" rel="noopener noreferrer">nominet.uk/report-abuse</a></li>
  <li><strong>ICANN</strong> (gTLDs): <a href="https://www.icann.org/compliance/complaint" rel="noopener noreferrer">icann.org/compliance/complaint</a></li>
  <li><strong>Action Fraud</strong> (UK): <a href="https://www.actionfraud.police.uk" rel="noopener noreferrer">actionfraud.police.uk</a></li>
  <li><strong>NCSC</strong> (UK): <a href="https://www.ncsc.gov.uk/section/about-ncsc/report-incident" rel="noopener noreferrer">ncsc.gov.uk</a></li>
  <li><strong>PhishTank</strong>: <a href="https://www.phishtank.com" rel="noopener noreferrer">phishtank.com</a></li>
</ul>

<h2>6. Revisão da Política</h2>
<p>Esta política é revista anualmente ou na sequência de alterações materiais aos requisitos da ICANN ou da Nominet. Última revisão: <strong>maio de 2026</strong>.</p>
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
