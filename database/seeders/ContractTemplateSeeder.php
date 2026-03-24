<?php

namespace Database\Seeders;

use App\Models\ContractTemplate;
use Illuminate\Database\Seeder;

class ContractTemplateSeeder extends Seeder
{
    public function run(): void
    {
        $templates = $this->templates();

        foreach ($templates as $t) {
            ContractTemplate::updateOrCreate(
                ['type' => $t['type'], 'language' => $t['language']],
                ['name' => $t['name'], 'content' => $t['content'], 'is_active' => true]
            );
        }
    }

    private function templates(): array
    {
        return [

            // ──────────────────────────────────────────────────────────────────
            //  WEB DEVELOPMENT AGREEMENT — ENGLISH
            // ──────────────────────────────────────────────────────────────────
            [
                'type'     => 'web_development',
                'language' => 'en',
                'name'     => 'Web Development Agreement (EN)',
                'content'  => <<<'HTML'
<h2>1. Parties to This Agreement</h2>
<p>This Web Development &amp; Design Agreement ("Agreement") is made between:</p>
<ul>
  <li><strong>Service Provider:</strong> {{legal.company_name}}, registered in England and Wales (Company No. {{legal.company_number}}, VAT No. {{legal.vat_number}}), registered address: {{legal.company_address}} ("the Company"); and</li>
  <li><strong>Client:</strong> [CLIENT NAME / COMPANY NAME], [CLIENT REGISTERED ADDRESS] [COMPANY REGISTRATION NUMBER IF APPLICABLE] ("the Client").</li>
</ul>
<p>Together referred to as "the Parties". This Agreement takes effect on the date the Client accepts the Company's quotation in writing or makes the deposit payment, whichever is earlier.</p>

<h2>2. Definitions</h2>
<ul>
  <li><strong>"Project"</strong> — the web design and development work described in the accepted Quotation.</li>
  <li><strong>"Deliverables"</strong> — the website, designs, code, graphics, and all digital assets produced specifically for the Client under this Agreement.</li>
  <li><strong>"Content"</strong> — all text, images, videos, data, and other materials provided by the Client for inclusion in the Project.</li>
  <li><strong>"Change Request"</strong> — any request to amend the scope of work beyond that described in the Quotation.</li>
  <li><strong>"Intellectual Property Rights"</strong> — all copyright, design rights, database rights, trademarks, and other proprietary rights.</li>
</ul>

<h2>3. Scope of Services</h2>
<p>The Company agrees to provide the services described in the accepted Quotation ("the Services"). The Quotation forms part of this Agreement. If there is a conflict between this Agreement and the Quotation, the Quotation prevails in respect of project-specific details.</p>
<p>Services may include (as specified in the Quotation): website design, front-end development, back-end development, CMS integration, e-commerce functionality, third-party integrations, SEO configuration, mobile responsiveness, cross-browser testing, and launch support.</p>

<h2>4. Project Schedule</h2>
<p>Estimated project timelines are stated in the Quotation or agreed in writing at project kick-off. Timelines are conditional on:</p>
<ul>
  <li>The Client providing all required Content, materials, credentials, and approvals within <strong>5 working days</strong> of each request;</li>
  <li>No significant changes to the agreed Scope of Work;</li>
  <li>Payment of all amounts due in accordance with the payment schedule.</li>
</ul>
<p>The Company is not liable for delays caused by the Client's failure to provide required materials, third-party service issues, or events beyond the Company's reasonable control.</p>

<h2>5. Fees, Deposit &amp; Payment</h2>
<p><strong>Deposit:</strong> A non-refundable deposit of <strong>{{legal.deposit_percent}}%</strong> of the total quoted project value is required before project work commences. The deposit confirms the Client's commitment and covers initial planning, research, and resource allocation.</p>
<p><strong>Payment Schedule:</strong> The balance is payable in accordance with the milestone schedule set out in the Quotation. All invoices are due within <strong>{{legal.payment_terms_days}} calendar days</strong> of the date of issue.</p>
<p><strong>Late Payment:</strong> Invoices unpaid after the due date will accrue statutory interest under the Late Payment of Commercial Debts (Interest) Act 1998 at 8% per annum above the Bank of England base rate. The Company reserves the right to suspend work on overdue accounts without liability.</p>
<p><strong>Retention of Title:</strong> All Deliverables remain the property of the Company until all invoices are paid in full.</p>
<p><strong>VAT:</strong> All fees are exclusive of VAT, which will be charged at the applicable rate where the Company is required to do so.</p>
<p><strong>Expenses:</strong> Third-party costs (domain registration, stock photography, premium plugins, hosting, licensed fonts, etc.) will be advised in advance and invoiced separately unless already included in the Quotation.</p>

<h2>6. Intellectual Property Rights</h2>
<p>Upon receipt of full and final payment of all amounts due under this Agreement:</p>
<ul>
  <li>Copyright in all custom Deliverables created specifically for the Client transfers to the Client under the Copyright, Designs and Patents Act 1988;</li>
  <li>The Client grants the Company a perpetual, non-exclusive, royalty-free licence to display the completed work in its portfolio, case studies, and promotional materials, unless the Client requests otherwise in writing within 30 days of project completion.</li>
</ul>
<p><strong>Excluded from transfer:</strong></p>
<ul>
  <li>Open-source software, frameworks, libraries, and plugins used in the Project (governed by their respective licences, e.g. MIT, GPL);</li>
  <li>Stock photography, fonts, or other licensed third-party assets (the Client receives the benefit of the applicable licence);</li>
  <li>The Company's proprietary code components, tools, and methodologies developed independently of this Project.</li>
</ul>
<p>The Client warrants that all Content provided is either owned by the Client or the Client has full rights to use it, and that its use will not infringe any third party's Intellectual Property Rights. The Client indemnifies the Company against any claims arising from a breach of this warranty.</p>

<h2>7. Client Responsibilities</h2>
<p>The Client agrees to:</p>
<ul>
  <li>Designate a single point of contact with authority to make decisions and approve work on behalf of the organisation;</li>
  <li>Provide all required Content, images, brand assets, login credentials, and information in a timely manner;</li>
  <li>Review and provide consolidated feedback on design proofs and development stages within <strong>5 working days</strong>;</li>
  <li>Ensure all Client-supplied materials are accurate, lawful, and do not infringe third-party rights;</li>
  <li>Comply with all applicable laws in connection with the Deliverables, including UK GDPR, the Consumer Rights Act 2015, the Consumer Contracts (Information, Cancellation and Additional Charges) Regulations 2013, and applicable advertising standards;</li>
  <li>Not resell, redistribute, or licence the Deliverables as a template or off-the-shelf product without prior written consent from the Company.</li>
</ul>

<h2>8. Revisions &amp; Change Requests</h2>
<p>The number of included design revision rounds is specified in the Quotation. Additional revisions or changes beyond the agreed scope will be charged at the Company's then-current hourly rate, communicated to the Client in advance.</p>
<p>Any Change Request that materially alters the scope, timeline, or cost must be agreed in writing (email accepted) before the Company proceeds. The Company reserves the right to adjust pricing and timelines accordingly. Verbal instructions to proceed do not constitute agreement to additional charges.</p>

<h2>9. Third-Party Services</h2>
<p>The Company may integrate third-party services (e.g. hosting providers, payment gateways, CMS platforms, API services, analytics). The Client acknowledges that:</p>
<ul>
  <li>Third-party services are subject to their providers' terms and conditions, which may change independently;</li>
  <li>The Company is not responsible for the availability, security, data handling, or performance of third-party services;</li>
  <li>Ongoing subscription costs for third-party services (hosting, domain renewals, software licences) are the Client's responsibility unless included in a separate maintenance agreement.</li>
</ul>

<h2>10. Confidentiality</h2>
<p>Each Party agrees to keep confidential all information received from the other that is designated as confidential or that a reasonable person would treat as confidential ("Confidential Information"). Neither Party shall disclose Confidential Information to third parties without prior written consent, except as required by law or to employees and sub-contractors who need it to fulfil obligations under this Agreement and are bound by equivalent confidentiality obligations.</p>
<p>This obligation survives termination of this Agreement for a period of <strong>three (3) years</strong>.</p>

<h2>11. Data Protection</h2>
<p>Each Party shall comply with its obligations under the UK General Data Protection Regulation (UK GDPR) and the Data Protection Act 2018. Where the Company processes personal data on behalf of the Client in the course of providing the Services, the Parties shall enter into a Data Processing Agreement as required under Article 28 UK GDPR. For the Company's general privacy practices, please refer to our Privacy Notice or contact <a href="mailto:{{legal.company_email}}">{{legal.company_email}}</a>.</p>

<h2>12. Warranties</h2>
<p>The Company warrants that:</p>
<ul>
  <li>It will perform the Services with reasonable skill and care in accordance with good industry practice;</li>
  <li>The Deliverables will function materially in accordance with the agreed specification at the time of delivery;</li>
  <li>It has the full right and authority to enter into this Agreement and perform the Services.</li>
</ul>
<p>The Company does not warrant that the Deliverables will be entirely error-free under all circumstances, that search engine rankings will improve, or that the website will remain free from security vulnerabilities introduced by third-party software updates after delivery.</p>
<p>Post-delivery defects attributable to the original specification will be corrected at no charge within <strong>30 days</strong> of the launch date. Issues caused by Client modifications, third-party plugin updates, server configuration changes, or actions outside the Company's control are excluded from this warranty.</p>

<h2>13. Limitation of Liability</h2>
<p>To the maximum extent permitted by applicable law:</p>
<ul>
  <li>The Company's total aggregate liability under or in connection with this Agreement shall not exceed the total fees paid by the Client for the specific Services giving rise to the claim;</li>
  <li>The Company shall not be liable for any indirect, consequential, special, or punitive damages, loss of profits, loss of revenue, loss of data, loss of business opportunity, or reputational harm;</li>
  <li>The Company shall not be liable for damage arising from errors in Client-supplied materials, failures of third-party services, force majeure events, or Client modifications to the Deliverables.</li>
</ul>
<p>Nothing in this Agreement excludes or limits liability for death or personal injury caused by negligence, fraud or fraudulent misrepresentation, or any other liability that cannot be excluded or limited under applicable law (including the Consumer Rights Act 2015 where applicable).</p>

<h2>14. Cancellation Rights</h2>
<p>Where this Agreement constitutes a "distance contract" under the Consumer Contracts (Information, Cancellation and Additional Charges) Regulations 2013, the Client has the right to cancel within <strong>14 calendar days</strong> ("cooling-off period") without giving any reason. If the Client requests that work begins before the end of the cooling-off period, the Client's right to a full refund is reduced pro-rata to the value of work already performed; if the Services are fully performed within the cooling-off period, the right to cancel is lost.</p>
<p>Business clients contracting for business purposes do not benefit from statutory cancellation rights under the 2013 Regulations.</p>
<p>After the statutory period (where applicable), cancellations require payment for all work completed to date plus a reasonable cancellation fee to cover committed resources and loss of revenue, which will be calculated and communicated at the time.</p>

<h2>15. Termination</h2>
<p><strong>By the Client:</strong> The Client may terminate this Agreement on <strong>14 days'</strong> written notice. Upon termination, the Client shall pay for all work completed to the date of termination plus the applicable cancellation charge. The deposit is non-refundable.</p>
<p><strong>By the Company:</strong> The Company may terminate immediately on written notice if the Client: (a) fails to pay any invoice within 30 days of the due date after a reminder has been sent; (b) breaches a material term and fails to remedy the breach within 14 days of written notice; (c) becomes insolvent, enters administration, or ceases to trade.</p>
<p><strong>Effect of Termination:</strong> On termination, the Company will deliver all work-in-progress materials paid for to the date of termination. Intellectual Property Rights in unpaid work remain with the Company.</p>

<h2>16. Force Majeure</h2>
<p>Neither Party is liable for delay or failure to perform its obligations to the extent caused by circumstances beyond its reasonable control ("Force Majeure Events"), including but not limited to: natural disasters, war, civil unrest, pandemic, government action, power failure, or major internet outage. The affected Party must notify the other promptly. If the Force Majeure Event continues for more than <strong>30 consecutive days</strong>, either Party may terminate this Agreement on written notice without further liability, other than payment for work completed to the date of termination.</p>

<h2>17. Entire Agreement &amp; Amendments</h2>
<p>This Agreement (together with the accepted Quotation) constitutes the entire agreement between the Parties in relation to the Project and supersedes all prior agreements, representations, and understandings. No amendment is valid unless made in writing and confirmed by both Parties (email is acceptable).</p>

<h2>18. Severability</h2>
<p>If any provision of this Agreement is found to be invalid or unenforceable, it shall be modified to the minimum extent necessary to make it valid. All remaining provisions shall continue in full force and effect.</p>

<h2>19. Governing Law &amp; Jurisdiction</h2>
<p>This Agreement is governed by the laws of <strong>England and Wales</strong>. The Parties irrevocably submit to the exclusive jurisdiction of the courts of England and Wales, save that the Company may seek emergency injunctive relief in any competent jurisdiction.</p>
<p>Contact: <a href="mailto:{{legal.company_email}}">{{legal.company_email}}</a> | {{legal.company_phone}}</p>

<h2>20. Signatures</h2>
<p>By signing below (or confirming acceptance in writing / by email), the Parties agree to be bound by the terms of this Agreement.</p>
<table style="width:100%;border-collapse:collapse;margin-top:24px;">
  <tr>
    <td style="width:50%;padding-right:20px;vertical-align:top;">
      <p><strong>For and on behalf of the Company:</strong></p>
      <p>{{legal.company_name}}</p>
      <p style="margin-top:48px;border-top:1px solid #555;padding-top:8px;">Signature &amp; Date</p>
      <p>Printed Name: _______________________</p>
      <p>Position: ___________________________</p>
    </td>
    <td style="width:50%;padding-left:20px;vertical-align:top;">
      <p><strong>For and on behalf of the Client:</strong></p>
      <p>[CLIENT NAME]</p>
      <p style="margin-top:48px;border-top:1px solid #555;padding-top:8px;">Signature &amp; Date</p>
      <p>Printed Name: _______________________</p>
      <p>Position: ___________________________</p>
    </td>
  </tr>
</table>
HTML,
            ],

            // ──────────────────────────────────────────────────────────────────
            //  WEB DEVELOPMENT AGREEMENT — POLISH
            // ──────────────────────────────────────────────────────────────────
            [
                'type'     => 'web_development',
                'language' => 'pl',
                'name'     => 'Umowa o Tworzenie Strony Internetowej (PL)',
                'content'  => <<<'HTML'
<h2>1. Strony Umowy</h2>
<p>Niniejsza Umowa o Projektowanie i Tworzenie Strony Internetowej ("Umowa") jest zawarta pomiędzy:</p>
<ul>
  <li><strong>Wykonawcą:</strong> {{legal.company_name}}, spółką zarejestrowaną w Anglii i Walii (numer rejestracyjny: {{legal.company_number}}, numer VAT: {{legal.vat_number}}), adres rejestrowy: {{legal.company_address}} ("Wykonawca"); oraz</li>
  <li><strong>Zamawiającym:</strong> [NAZWA KLIENTA / FIRMY KLIENTA], [ADRES REJESTROWY KLIENTA] [NUMER REJESTRACYJNY FIRMY, JEŚLI DOTYCZY] ("Zamawiający").</li>
</ul>
<p>Łącznie zwane "Stronami". Niniejsza Umowa wchodzi w życie z dniem akceptacji przez Zamawiającego oferty Wykonawcy w formie pisemnej lub dokonania wpłaty zaliczki — w zależności od tego, które z tych zdarzeń nastąpi wcześniej.</p>

<h2>2. Definicje</h2>
<ul>
  <li><strong>„Projekt"</strong> — prace projektowo-programistyczne opisane w zaakceptowanej Ofercie Wykonawcy.</li>
  <li><strong>„Rezultaty"</strong> — strona internetowa, projekty graficzne, kod, grafiki i wszelkie zasoby cyfrowe wytworzone specjalnie dla Zamawiającego w ramach niniejszej Umowy.</li>
  <li><strong>„Treści"</strong> — wszelkie teksty, obrazy, filmy, dane i inne materiały dostarczone przez Zamawiającego do umieszczenia w Projekcie.</li>
  <li><strong>„Zlecenie Zmiany"</strong> — wniosek o modyfikację zakresu prac wykraczający poza opis w Ofercie.</li>
  <li><strong>„Prawa Własności Intelektualnej"</strong> — wszelkie prawa autorskie, prawa do wzorów, prawa do baz danych, znaki towarowe i inne prawa własności intelektualnej.</li>
</ul>

<h2>3. Zakres Usług</h2>
<p>Wykonawca zobowiązuje się do świadczenia usług opisanych w zaakceptowanej Ofercie ("Usługi"). Oferta stanowi integralną część niniejszej Umowy. W przypadku sprzeczności między postanowieniami Umowy a Oferty w zakresie szczegółów projektowych, pierwszeństwo ma Oferta.</p>
<p>Usługi mogą obejmować (zgodnie ze specyfikacją w Ofercie): projektowanie stron internetowych, front-end development, back-end development, integrację CMS, funkcjonalność e-commerce, integracje z systemami zewnętrznymi, konfigurację SEO, responsywność mobilną, testy wieloprzeglądarkowe i wsparcie przy uruchomieniu.</p>

<h2>4. Harmonogram Projektu</h2>
<p>Szacowane terminy realizacji projektu określone są w Ofercie lub uzgodnione pisemnie na etapie uruchomienia projektu. Dotrzymanie harmonogramu jest uzależnione od:</p>
<ul>
  <li>dostarczenia przez Zamawiającego wszelkich wymaganych Treści, materiałów, danych dostępowych i zatwierdzeń w ciągu <strong>5 dni roboczych</strong> od każdego zapytania;</li>
  <li>braku istotnych zmian w uzgodnionym zakresie prac;</li>
  <li>terminowego regulowania płatności zgodnie z harmonogramem.</li>
</ul>
<p>Wykonawca nie ponosi odpowiedzialności za opóźnienia spowodowane niedostarczeniem materiałów przez Zamawiającego w terminie, problemami z usługami zewnętrznymi lub zdarzeniami pozostającymi poza uzasadnioną kontrolą Wykonawcy.</p>

<h2>5. Wynagrodzenie, Zaliczka i Płatności</h2>
<p><strong>Zaliczka:</strong> Przed rozpoczęciem prac projektowych wymagana jest bezzwrotna zaliczka w wysokości <strong>{{legal.deposit_percent}}%</strong> łącznej wartości projektu. Zaliczka potwierdza zobowiązanie Zamawiającego i pokrywa koszty planowania, prac koncepcyjnych i alokacji zasobów.</p>
<p><strong>Harmonogram płatności:</strong> Pozostała kwota płatna jest zgodnie z harmonogramem kamieni milowych opisanym w Ofercie. Wszystkie faktury są wymagalne w ciągu <strong>{{legal.payment_terms_days}} dni kalendarzowych</strong> od daty ich wystawienia.</p>
<p><strong>Opóźnienia w płatnościach:</strong> Faktury nieopłacone po upływie terminu płatności będą obciążone ustawowymi odsetkami za opóźnienie. Wykonawca zastrzega sobie prawo do zawieszenia prac w przypadku zaległości płatniczych bez ponoszenia z tego tytułu odpowiedzialności wobec Zamawiającego.</p>
<p><strong>Zastrzeżenie własności:</strong> Wszelkie Rezultaty pozostają własnością Wykonawcy do czasu uregulowania wszystkich faktur w całości.</p>
<p><strong>Podatek VAT:</strong> Wszystkie ceny podane są bez podatku VAT, który zostanie naliczony według obowiązującej stawki, jeżeli Wykonawca jest do tego zobowiązany.</p>
<p><strong>Wydatki dodatkowe:</strong> Koszty zewnętrzne (rejestracja domeny, fotografia stockowa, płatne wtyczki, hosting, licencjonowane czcionki itp.) będą wcześniej uzgodnione i fakturowane odrębnie, o ile nie zostały ujęte w Ofercie.</p>

<h2>6. Prawa Własności Intelektualnej</h2>
<p>Po otrzymaniu pełnej i ostatecznej płatności wszystkich kwot należnych na podstawie niniejszej Umowy:</p>
<ul>
  <li>Majątkowe prawa autorskie do wszystkich Rezultatów stworzonych specjalnie dla Zamawiającego przechodzą na Zamawiającego na podstawie art. 41 Ustawy o prawie autorskim i prawach pokrewnych z dnia 4 lutego 1994 r. (t.j. Dz.U. 2022 poz. 2509).</li>
  <li>Zamawiający udziela Wykonawcy bezterminowej, niewyłącznej, bezpłatnej licencji na prezentowanie ukończonych prac w portfolio, studiach przypadków i materiałach promocyjnych Wykonawcy, o ile Zamawiający nie zażąda inaczej na piśmie w ciągu 30 dni od zakończenia projektu.</li>
</ul>
<p><strong>Z przelewu wyłączone są:</strong></p>
<ul>
  <li>oprogramowanie open-source, frameworki, biblioteki i wtyczki wykorzystane w Projekcie (podlegające ich własnym licencjom, np. MIT, GPL);</li>
  <li>fotografia stockowa, czcionki lub inne licencjonowane zasoby zewnętrzne (Zamawiający korzysta z praw wynikających z danej licencji);</li>
  <li>autorskie komponenty kodu, narzędzia i metodyki Wykonawcy opracowane niezależnie od niniejszego Projektu.</li>
</ul>
<p>Zamawiający zapewnia, że wszystkie dostarczone Treści stanowią jego własność lub że posiada pełne prawa do ich wykorzystania, oraz że ich użycie nie narusza praw własności intelektualnej osób trzecich. Zamawiający zobowiązuje się naprawić wszelkie szkody Wykonawcy wynikłe z naruszenia tego zapewnienia.</p>

<h2>7. Obowiązki Zamawiającego</h2>
<p>Zamawiający zobowiązuje się do:</p>
<ul>
  <li>wyznaczenia jednego punktu kontaktowego z uprawnieniami do podejmowania decyzji i zatwierdzania prac w imieniu organizacji;</li>
  <li>terminowego dostarczania wszelkich wymaganych Treści, obrazów, zasobów marki, danych dostępowych i informacji;</li>
  <li>przeglądania projektów i etapów prac programistycznych oraz dostarczania skonsolidowanych uwag w ciągu <strong>5 dni roboczych</strong>;</li>
  <li>zapewnienia, że dostarczone materiały są dokładne, zgodne z prawem i nie naruszają praw osób trzecich;</li>
  <li>przestrzegania wszystkich obowiązujących przepisów prawa w związku z korzystaniem z Rezultatów, w tym RODO, Ustawy z dnia 30 maja 2014 r. o prawach konsumenta oraz stosownych przepisów o reklamie;</li>
  <li>nieodsprzedawania ani nieprzekazywania Rezultatów jako szablonów lub gotowych produktów bez uprzedniej pisemnej zgody Wykonawcy.</li>
</ul>

<h2>8. Poprawki i Zlecenia Zmian</h2>
<p>Liczba rund poprawek graficznych uwzględnionych w Ofercie jest w niej określona. Dodatkowe poprawki lub zmiany wykraczające poza uzgodniony zakres będą wyceniane według aktualnej stawki godzinowej Wykonawcy, o której Zamawiający zostanie poinformowany przed przystąpieniem do prac.</p>
<p>Każde Zlecenie Zmiany istotnie wpływające na zakres, termin lub koszt projektu musi zostać pisemnie zaakceptowane przez obie Strony (akceptuje się formę wiadomości e-mail). Wykonawca zastrzega sobie prawo odpowiedniej korekty harmonogramu i wynagrodzenia. Ustne polecenia wykonania dodatkowych prac nie stanowią zgody na dodatkowe koszty.</p>

<h2>9. Usługi Zewnętrzne</h2>
<p>Wykonawca może integrować usługi zewnętrzne (np. dostawców hostingu, bramki płatnicze, platformy CMS, usługi API, analitykę). Zamawiający przyjmuje do wiadomości, że:</p>
<ul>
  <li>usługi zewnętrzne podlegają warunkom określonym przez ich dostawców, które mogą się zmieniać niezależnie;</li>
  <li>Wykonawca nie ponosi odpowiedzialności za dostępność, bezpieczeństwo, przetwarzanie danych ani wydajność usług zewnętrznych;</li>
  <li>bieżące koszty usług zewnętrznych (hosting, odnawianie domeny, licencje oprogramowania) leżą po stronie Zamawiającego, chyba że objęte są odrębną umową serwisową.</li>
</ul>

<h2>10. Poufność</h2>
<p>Każda ze Stron zobowiązuje się traktować jako poufne wszelkie informacje otrzymane od drugiej Strony, które zostały oznaczone jako poufne lub które rozsądna osoba uznałaby za poufne ("Informacje Poufne"). Żadna ze Stron nie ujawni Informacji Poufnych osobom trzecim bez uprzedniej pisemnej zgody, z wyjątkiem przypadków wymaganych przez przepisy prawa lub udostępniania pracownikom i podwykonawcom, którym jest to niezbędne do realizacji Umowy i którzy są związani równoważnymi obowiązkami zachowania poufności.</p>
<p>Obowiązek ten obowiązuje przez okres <strong>trzech (3) lat</strong> od daty rozwiązania lub wygaśnięcia niniejszej Umowy.</p>

<h2>11. Ochrona Danych Osobowych</h2>
<p>Każda ze Stron wypełnia swoje obowiązki wynikające z Rozporządzenia Parlamentu Europejskiego i Rady (UE) 2016/679 z dnia 27 kwietnia 2016 r. (RODO) oraz polskich przepisów o ochronie danych osobowych. W zakresie, w jakim Wykonawca przetwarza dane osobowe w imieniu Zamawiającego podczas świadczenia Usług, Strony zawrą odrębną umowę powierzenia przetwarzania danych osobowych, o której mowa w art. 28 RODO. Szczegółowe informacje dotyczące praktyk Wykonawcy w zakresie prywatności dostępne są na żądanie pod adresem <a href="mailto:{{legal.company_email}}">{{legal.company_email}}</a>.</p>

<h2>12. Gwarancje i Oświadczenia</h2>
<p>Wykonawca zapewnia, że:</p>
<ul>
  <li>będzie świadczył Usługi z należytą starannością i zgodnie z dobrymi praktykami branżowymi;</li>
  <li>Rezultaty będą funkcjonowały zgodnie z uzgodnioną specyfikacją w chwili ich dostarczenia;</li>
  <li>jest uprawniony do zawarcia niniejszej Umowy i świadczenia Usług.</li>
</ul>
<p>Wykonawca nie gwarantuje, że Rezultaty będą całkowicie wolne od błędów we wszystkich okolicznościach, że poprawi się pozycjonowanie w wyszukiwarkach, ani że strona będzie wolna od luk bezpieczeństwa wynikłych z aktualizacji zewnętrznego oprogramowania po dostarczeniu.</p>
<p>Wady wynikające z pierwotnej specyfikacji będą usuwane nieodpłatnie w ciągu <strong>30 dni</strong> od daty uruchomienia. Problemy spowodowane modyfikacjami Zamawiającego, aktualizacjami zewnętrznych wtyczek, zmianami konfiguracji serwera lub działaniami poza kontrolą Wykonawcy są wyłączone z gwarancji.</p>

<h2>13. Ograniczenie Odpowiedzialności</h2>
<p>W maksymalnym zakresie dopuszczonym przez obowiązujące prawo:</p>
<ul>
  <li>łączna odpowiedzialność Wykonawcy wynikająca z niniejszej Umowy nie przekroczy całkowitego wynagrodzenia zapłaconego przez Zamawiającego za Usługi, które dały podstawę do roszczenia;</li>
  <li>Wykonawca nie ponosi odpowiedzialności za szkody pośrednie, wynikowe, szczególne lub karne, utratę zysku, przychodów, danych, szans biznesowych ani uszczerbek na reputacji;</li>
  <li>Wykonawca nie ponosi odpowiedzialności za szkody wynikające z błędów w materiałach dostarczonych przez Zamawiającego, awarii usług zewnętrznych, siły wyższej ani modyfikacji Rezultatów przez Zamawiającego.</li>
</ul>
<p>Żadne postanowienie niniejszej Umowy nie wyłącza ani nie ogranicza odpowiedzialności za szkodę wyrządzoną z winy umyślnej ani za inną odpowiedzialność, której wyłączenie jest niedopuszczalne na gruncie obowiązujących przepisów prawa.</p>

<h2>14. Prawo Odstąpienia od Umowy</h2>
<p>Zamawiający będący konsumentem w rozumieniu art. 22¹ Kodeksu cywilnego i zawierający Umowę na odległość ma prawo odstąpienia od niej w terminie <strong>14 dni kalendarzowych</strong> ("okres na namysł") bez podawania przyczyny, zgodnie z Ustawą o prawach konsumenta z dnia 30 maja 2014 r. Jeżeli Zamawiający wyraźnie zażąda rozpoczęcia prac przed upływem tego terminu, jego prawo do pełnego zwrotu wynagrodzenia ulega proporcjonalnemu zmniejszeniu o wartość wykonanych prac; gdy Usługi zostaną w pełni wykonane w tym okresie, prawo odstąpienia wygasa.</p>
<p>Zamawiający działający w charakterze przedsiębiorcy i zawierający Umowę w ramach prowadzonej działalności gospodarczej nie korzysta z ustawowego prawa odstąpienia na podstawie ww. Ustawy.</p>
<p>Po upływie ustawowego okresu wszelkie anulowanie zamówienia wiąże się z obowiązkiem zapłaty za wszystkie prace wykonane do daty anulowania oraz uzasadnionej opłaty za zerwanie umowy, pokrywającej zaangażowane zasoby i utracone przychody.</p>

<h2>15. Rozwiązanie Umowy</h2>
<p><strong>Przez Zamawiającego:</strong> Zamawiający może rozwiązać niniejszą Umowę za <strong>14-dniowym</strong> pisemnym wypowiedzeniem. Zamawiający zobowiązany jest uiścić wynagrodzenie za wszystkie prace wykonane do daty rozwiązania oraz uzasadnioną opłatę za anulowanie. Zaliczka jest bezzwrotna.</p>
<p><strong>Przez Wykonawcę:</strong> Wykonawca może rozwiązać Umowę ze skutkiem natychmiastowym w formie pisemnego zawiadomienia, jeżeli Zamawiający: (a) nie opłaci żadnej faktury w ciągu 30 dni od terminu płatności po wcześniejszym przypomnieniu; (b) naruszy istotne postanowienie Umowy i nie usunie naruszenia w ciągu 14 dni od pisemnego wezwania; (c) stanie się niewypłacalny lub zaprzestanie prowadzenia działalności.</p>
<p><strong>Skutki rozwiązania:</strong> Po rozwiązaniu Wykonawca przekaże wszelkie opłacone materiały w toku prac. Prawa własności intelektualnej do prac nieopłaconych pozostają przy Wykonawcy.</p>

<h2>16. Siła Wyższa</h2>
<p>Żadna ze Stron nie ponosi odpowiedzialności za opóźnienia ani niewykonanie swoich zobowiązań w zakresie, w jakim wynikają one z okoliczności pozostających poza jej uzasadnioną kontrolą ("Siła Wyższa"), w tym m.in.: klęsk żywiołowych, działań wojennych, niepokojów społecznych, pandemii, działań administracji publicznej, awarii zasilania lub rozległych awarii infrastruktury internetowej. Dotknięta Strona niezwłocznie powiadomi drugą Stronę. Jeżeli zdarzenie Siły Wyższej trwa dłużej niż <strong>30 kolejnych dni</strong>, każda ze Stron może rozwiązać Umowę w drodze pisemnego zawiadomienia bez dalszej odpowiedzialności, z wyjątkiem obowiązku zapłaty za prace wykonane do daty rozwiązania.</p>

<h2>17. Całość Umowy i Zmiany</h2>
<p>Niniejsza Umowa (wraz z zaakceptowaną Ofertą) stanowi całość porozumienia między Stronami i zastępuje wszelkie wcześniejsze umowy, oświadczenia i uzgodnienia dotyczące przedmiotu Umowy. Żadna zmiana nie jest ważna, o ile nie zostanie dokonana w formie pisemnej i potwierdzona przez obie Strony (akceptuje się formę wiadomości e-mail).</p>

<h2>18. Rozdzielność Postanowień</h2>
<p>Jeżeli którekolwiek postanowienie niniejszej Umowy zostanie uznane za nieważne lub niewykonalne, zostanie ono zmienione w minimalnym zakresie niezbędnym do zapewnienia jego ważności i wykonalności. Wszystkie pozostałe postanowienia zachowują swoją pełną moc wiążącą.</p>

<h2>19. Prawo Właściwe i Jurysdykcja</h2>
<p>Niniejsza Umowa podlega prawu <strong>Anglii i Walii</strong> i będzie interpretowana zgodnie z tym prawem. Strony nieodwołalnie poddają się wyłącznej jurysdykcji sądów Anglii i Walii, z zastrzeżeniem, że Wykonawca może dochodzić środków zabezpieczających w dowolnej właściwej jurysdykcji.</p>
<p>Kontakt: <a href="mailto:{{legal.company_email}}">{{legal.company_email}}</a> | {{legal.company_phone}}</p>

<h2>20. Podpisy</h2>
<p>Składając podpisy poniżej (lub potwierdzając akceptację w formie pisemnej / pocztą elektroniczną), Strony akceptują warunki niniejszej Umowy i zobowiązują się do jej przestrzegania.</p>
<table style="width:100%;border-collapse:collapse;margin-top:24px;">
  <tr>
    <td style="width:50%;padding-right:20px;vertical-align:top;">
      <p><strong>W imieniu Wykonawcy:</strong></p>
      <p>{{legal.company_name}}</p>
      <p style="margin-top:48px;border-top:1px solid #555;padding-top:8px;">Podpis i data</p>
      <p>Imię i nazwisko: ____________________</p>
      <p>Stanowisko: _________________________</p>
    </td>
    <td style="width:50%;padding-left:20px;vertical-align:top;">
      <p><strong>W imieniu Zamawiającego:</strong></p>
      <p>[NAZWA KLIENTA]</p>
      <p style="margin-top:48px;border-top:1px solid #555;padding-top:8px;">Podpis i data</p>
      <p>Imię i nazwisko: ____________________</p>
      <p>Stanowisko: _________________________</p>
    </td>
  </tr>
</table>
HTML,
            ],

            // ──────────────────────────────────────────────────────────────────
            //  WEB DEVELOPMENT AGREEMENT — PORTUGUESE
            // ──────────────────────────────────────────────────────────────────
            [
                'type'     => 'web_development',
                'language' => 'pt',
                'name'     => 'Acordo de Desenvolvimento Web (PT)',
                'content'  => <<<'HTML'
<h2>1. Partes do Acordo</h2>
<p>Este Acordo de Desenvolvimento e Design Web ("Acordo") é celebrado entre:</p>
<ul>
  <li><strong>Prestador de Serviços:</strong> {{legal.company_name}}, empresa registada em Inglaterra e no País de Gales (N.º de empresa: {{legal.company_number}}, N.º de IVA: {{legal.vat_number}}), morada registada: {{legal.company_address}} ("a Empresa"); e</li>
  <li><strong>Cliente:</strong> [NOME DO CLIENTE / EMPRESA DO CLIENTE], [MORADA REGISTADA DO CLIENTE] [NÚMERO DE REGISTO SE APLICÁVEL] ("o Cliente").</li>
</ul>
<p>Conjuntamente designados como "as Partes". Este Acordo entra em vigor na data em que o Cliente aceita o Orçamento da Empresa por escrito ou efetua o pagamento do depósito, consoante o que ocorrer primeiro.</p>

<h2>2. Definições</h2>
<ul>
  <li><strong>"Projeto"</strong> — os trabalhos de design e desenvolvimento web descritos no Orçamento aceite.</li>
  <li><strong>"Entregáveis"</strong> — o website, designs, código, gráficos e todos os ativos digitais produzidos especificamente para o Cliente ao abrigo deste Acordo.</li>
  <li><strong>"Conteúdo"</strong> — todos os textos, imagens, vídeos, dados e outros materiais fornecidos pelo Cliente para inclusão no Projeto.</li>
  <li><strong>"Pedido de Alteração"</strong> — qualquer pedido de modificação do âmbito do trabalho para além do descrito no Orçamento.</li>
  <li><strong>"Direitos de Propriedade Intelectual"</strong> — todos os direitos de autor, direitos sobre desenhos e modelos, direitos sobre bases de dados, marcas registadas e outros direitos de propriedade.</li>
</ul>

<h2>3. Âmbito dos Serviços</h2>
<p>A Empresa compromete-se a prestar os serviços descritos no Orçamento aceite ("os Serviços"). O Orçamento faz parte integrante deste Acordo. Em caso de conflito, o Orçamento prevalece nos detalhes específicos do projeto.</p>
<p>Os Serviços podem incluir (conforme especificado no Orçamento): design de websites, desenvolvimento front-end, desenvolvimento back-end, integração de CMS, funcionalidades de e-commerce, integrações com serviços de terceiros, configuração de SEO, responsividade móvel, testes entre navegadores e suporte ao lançamento.</p>

<h2>4. Cronograma do Projeto</h2>
<p>Os prazos estimados são indicados no Orçamento ou acordados por escrito no arranque do projeto. O cumprimento dos prazos está condicionado a:</p>
<ul>
  <li>O fornecimento pelo Cliente de todo o Conteúdo, materiais, credenciais e aprovações necessárias no prazo de <strong>5 dias úteis</strong> após cada solicitação;</li>
  <li>Ausência de alterações significativas ao âmbito de trabalho acordado;</li>
  <li>Pagamento de todos os montantes devidos em conformidade com o calendário de pagamentos.</li>
</ul>
<p>A Empresa não é responsável por atrasos causados pela falha do Cliente em fornecer os materiais necessários, problemas com serviços de terceiros ou eventos fora do controlo razoável da Empresa.</p>

<h2>5. Honorários, Depósito e Pagamento</h2>
<p><strong>Depósito:</strong> É exigido um depósito não reembolsável de <strong>{{legal.deposit_percent}}%</strong> do valor total do projeto antes do início dos trabalhos. O depósito confirma o compromisso do Cliente e cobre os custos de planeamento inicial, conceção e alocação de recursos.</p>
<p><strong>Calendário de Pagamentos:</strong> O saldo é pago de acordo com o calendário de marcos definido no Orçamento. Todas as faturas vencem no prazo de <strong>{{legal.payment_terms_days}} dias de calendário</strong> a contar da data de emissão.</p>
<p><strong>Pagamentos em Atraso:</strong> As faturas não liquidadas após a data de vencimento incorrerão em juros de mora à taxa legal aplicável. A Empresa reserva-se o direito de suspender os trabalhos em contas com pagamentos em atraso, sem responsabilidade perante o Cliente.</p>
<p><strong>Reserva de Propriedade:</strong> Todos os Entregáveis permanecem propriedade da Empresa até que todas as faturas sejam liquidadas na íntegra.</p>
<p><strong>IVA:</strong> Todos os honorários são indicados sem IVA, o qual será cobrado à taxa aplicável onde a Empresa for obrigada a fazê-lo.</p>
<p><strong>Despesas:</strong> Quaisquer custos de terceiros (registo de domínio, fotografia de stock, plugins pagos, alojamento, fontes licenciadas, etc.) serão comunicados previamente e faturados separadamente, salvo se incluídos no Orçamento.</p>

<h2>6. Direitos de Propriedade Intelectual</h2>
<p>Após a receção do pagamento total e definitivo de todos os montantes devidos ao abrigo deste Acordo:</p>
<ul>
  <li>Os direitos de autor sobre todos os Entregáveis criados especificamente para o Cliente são transferidos para o Cliente, nos termos do Código do Direito de Autor e dos Direitos Conexos (aprovado pelo Decreto-Lei n.º 63/85, na sua redação atual);</li>
  <li>O Cliente concede à Empresa uma licença perpétua, não exclusiva e gratuita para apresentar os trabalhos concluídos no seu portefólio, estudos de caso e materiais promocionais, salvo pedido em contrário por escrito no prazo de 30 dias após a conclusão do projeto.</li>
</ul>
<p><strong>Excluídos da transferência:</strong></p>
<ul>
  <li>Software de código aberto, frameworks, bibliotecas e plugins utilizados no Projeto (regidos pelas respetivas licenças, como MIT ou GPL);</li>
  <li>Fotografia de stock, tipos de letra ou outros ativos licenciados de terceiros (o Cliente beneficia da licença aplicável);</li>
  <li>Componentes de código, ferramentas e metodologias proprietárias da Empresa desenvolvidos independentemente deste Projeto.</li>
</ul>
<p>O Cliente garante que todo o Conteúdo fornecido é da sua propriedade ou que possui todos os direitos para o utilizar, e que a sua utilização não infringe os Direitos de Propriedade Intelectual de terceiros. O Cliente compromete-se a indemnizar a Empresa por quaisquer reclamações decorrentes da violação desta garantia.</p>

<h2>7. Responsabilidades do Cliente</h2>
<p>O Cliente compromete-se a:</p>
<ul>
  <li>Designar um único ponto de contacto com autoridade para tomar decisões e aprovar trabalhos em nome da organização;</li>
  <li>Fornecer tempestivamente todo o Conteúdo, imagens, ativos de marca, credenciais de acesso e informações necessárias;</li>
  <li>Rever e fornecer feedback consolidado sobre provas de design e fases de desenvolvimento no prazo de <strong>5 dias úteis</strong>;</li>
  <li>Garantir que todos os materiais fornecidos são precisos, legais e não infringem direitos de terceiros;</li>
  <li>Cumprir todas as leis aplicáveis em relação ao uso dos Entregáveis, incluindo o RGPD, a legislação de direitos dos consumidores e os padrões publicitários relevantes;</li>
  <li>Não revender, redistribuir ou licenciar os Entregáveis como modelo ou produto pronto a usar sem consentimento prévio por escrito da Empresa.</li>
</ul>

<h2>8. Revisões e Pedidos de Alteração</h2>
<p>O número de rondas de revisão de design incluídas está especificado no Orçamento. Revisões adicionais ou alterações além do âmbito acordado serão cobradas com base na taxa horária vigente da Empresa, comunicada ao Cliente antecipadamente.</p>
<p>Qualquer Pedido de Alteração que altere materialmente o âmbito, o prazo ou o custo deve ser acordado por escrito (o e-mail é suficiente) antes de a Empresa proceder. A Empresa reserva-se o direito de ajustar os preços e os prazos em conformidade. Instruções verbais para prosseguir não constituem acordo relativamente a custos adicionais.</p>

<h2>9. Serviços de Terceiros</h2>
<p>A Empresa pode integrar serviços de terceiros (ex.: fornecedores de alojamento, gateways de pagamento, plataformas CMS, serviços de API, ferramentas de análise). O Cliente reconhece que:</p>
<ul>
  <li>Os serviços de terceiros estão sujeitos aos termos e condições dos respetivos fornecedores, que podem ser alterados de forma independente;</li>
  <li>A Empresa não é responsável pela disponibilidade, segurança, tratamento de dados ou desempenho dos serviços de terceiros;</li>
  <li>Os custos contínuos dos serviços de terceiros (alojamento, renovação de domínio, subscrições de software) são da responsabilidade do Cliente, salvo se incluídos num contrato de manutenção separado.</li>
</ul>

<h2>10. Confidencialidade</h2>
<p>Cada Parte compromete-se a tratar como confidencial toda a informação recebida da outra que seja designada como tal ou que uma pessoa razoável consideraria confidencial ("Informação Confidencial"). Nenhuma das Partes divulgará Informação Confidencial a terceiros sem consentimento prévio por escrito, exceto quando exigido por lei ou a colaboradores e subcontratados que dela necessitem para cumprir obrigações ao abrigo deste Acordo e que se encontrem vinculados por obrigações de confidencialidade equivalentes.</p>
<p>Esta obrigação subsiste durante <strong>três (3) anos</strong> após a cessação do Acordo.</p>

<h2>11. Proteção de Dados</h2>
<p>Cada Parte cumprirá as suas obrigações ao abrigo do Regulamento Geral sobre a Proteção de Dados (RGPD — Regulamento (UE) 2016/679) e da legislação nacional de proteção de dados aplicável. Sempre que a Empresa processe dados pessoais em nome do Cliente no âmbito da prestação dos Serviços, as Partes celebrarão um Acordo de Tratamento de Dados separado, conforme exigido pelo Artigo 28.º do RGPD. Para informações sobre as práticas de privacidade da Empresa, contacte <a href="mailto:{{legal.company_email}}">{{legal.company_email}}</a>.</p>

<h2>12. Garantias e Declarações</h2>
<p>A Empresa garante que:</p>
<ul>
  <li>Prestará os Serviços com competência e diligência razoáveis, em conformidade com as boas práticas do setor;</li>
  <li>Os Entregáveis funcionarão materialmente de acordo com as especificações acordadas no momento da entrega;</li>
  <li>Tem o pleno direito e autoridade para celebrar este Acordo e prestar os Serviços.</li>
</ul>
<p>A Empresa não garante que os Entregáveis estarão totalmente isentos de erros em todas as circunstâncias, que as classificações nos motores de pesquisa melhorarão, ou que o website permanecerá isento de vulnerabilidades de segurança introduzidas por atualizações de software de terceiros após a entrega.</p>
<p>Defeitos relacionados com a especificação original serão corrigidos gratuitamente no prazo de <strong>30 dias</strong> após o lançamento. Problemas causados por modificações do Cliente, atualizações de plugins de terceiros, alterações à configuração do servidor ou ações fora do controlo da Empresa estão excluídos desta garantia.</p>

<h2>13. Limitação de Responsabilidade</h2>
<p>Na máxima extensão permitida pela lei aplicável:</p>
<ul>
  <li>A responsabilidade total agregada da Empresa ao abrigo ou em conexão com este Acordo não excederá os honorários totais pagos pelo Cliente pelos Serviços específicos que deram origem à reclamação;</li>
  <li>A Empresa não será responsável por quaisquer danos indiretos, consequenciais, especiais ou punitivos, perda de lucros, receitas, dados, oportunidades de negócio ou danos à reputação;</li>
  <li>A Empresa não será responsável por danos decorrentes de erros nos materiais fornecidos pelo Cliente, falhas nos serviços de terceiros, eventos de força maior ou modificações dos Entregáveis pelo Cliente.</li>
</ul>
<p>Nada neste Acordo exclui ou limita a responsabilidade por morte ou lesões pessoais causadas por negligência, fraude ou qualquer outra responsabilidade que não possa ser excluída ou limitada pela lei aplicável.</p>

<h2>14. Direito de Rescisão do Consumidor</h2>
<p>Quando o Cliente seja um consumidor nos termos do Decreto-Lei n.º 24/2014 (na sua redação atual) e este Acordo constitua um contrato à distância, o Cliente tem o direito de resolução no prazo de <strong>14 dias de calendário</strong> sem indicação do motivo. Se o Cliente solicitar expressamente o início dos trabalhos antes do termo desse prazo, o direito ao reembolso integral é reduzido proporcionalmente ao valor dos serviços já prestados; se os Serviços forem integralmente prestados nesse período, o direito de resolução extingue-se.</p>
<p>O Cliente que celebre o Acordo no exercício da sua atividade profissional não beneficia do direito legal de rescisão ao abrigo da legislação de defesa do consumidor.</p>
<p>Após o período legal (quando aplicável), a rescisão implica o pagamento de todos os trabalhos realizados até à data, acrescido de uma taxa de cancelamento razoável para cobrir os recursos comprometidos e as receitas perdidas.</p>

<h2>15. Rescisão</h2>
<p><strong>Pelo Cliente:</strong> O Cliente pode rescindir este Acordo mediante aviso prévio escrito de <strong>14 dias</strong>. Após a rescisão, o Cliente pagará por todos os trabalhos concluídos até à data, acrescido da taxa de cancelamento aplicável. O depósito não é reembolsável.</p>
<p><strong>Pela Empresa:</strong> A Empresa pode rescindir imediatamente mediante aviso escrito se o Cliente: (a) não pagar qualquer fatura no prazo de 30 dias após a data de vencimento, após envio de lembrete; (b) violar um termo material e não remediar a violação no prazo de 14 dias após aviso escrito; (c) se tornar insolvente, entrar em processo de recuperação ou cessar a sua atividade.</p>
<p><strong>Efeitos da rescisão:</strong> Após a rescisão, a Empresa entregará todos os materiais em curso pagos até à data. A Propriedade Intelectual nos trabalhos não pagos permanece na Empresa.</p>

<h2>16. Força Maior</h2>
<p>Nenhuma das Partes é responsável por atrasos ou incumprimento na medida em que sejam causados por circunstâncias fora do seu controlo razoável ("Eventos de Força Maior"), incluindo, entre outros: catástrofes naturais, guerra, perturbações civis, pandemia, ação governamental, falha de energia ou interrupção grave da infraestrutura de internet. A Parte afetada notificará a outra prontamente. Se o Evento de Força Maior se mantiver por mais de <strong>30 dias consecutivos</strong>, qualquer das Partes pode rescindir este Acordo mediante aviso escrito, sem responsabilidade adicional, à exceção do pagamento pelos trabalhos realizados até à data.</p>

<h2>17. Acordo Integral e Alterações</h2>
<p>Este Acordo (juntamente com o Orçamento aceite) constitui o acordo integral entre as Partes e substitui todos os acordos, representações e entendimentos anteriores relativos ao objeto do mesmo. Nenhuma alteração é válida a menos que seja feita por escrito e confirmada por ambas as Partes (o e-mail é aceitável).</p>

<h2>18. Divisibilidade</h2>
<p>Se qualquer disposição deste Acordo for considerada inválida ou inexequível, será modificada na medida mínima necessária para a tornar válida. Todas as restantes disposições permanecem em pleno vigor e efeito.</p>

<h2>19. Lei Aplicável e Jurisdição</h2>
<p>Este Acordo é regido e interpretado de acordo com as leis de <strong>Inglaterra e País de Gales</strong>. As Partes submetem-se irrevogavelmente à jurisdição exclusiva dos tribunais de Inglaterra e País de Gales, sem prejuízo de a Empresa poder solicitar medidas cautelares em qualquer jurisdição competente.</p>
<p>Contacto: <a href="mailto:{{legal.company_email}}">{{legal.company_email}}</a> | {{legal.company_phone}}</p>

<h2>20. Assinaturas</h2>
<p>Ao assinar abaixo (ou confirmar a aceitação por escrito ou por e-mail), as Partes declaram-se vinculadas pelos termos deste Acordo.</p>
<table style="width:100%;border-collapse:collapse;margin-top:24px;">
  <tr>
    <td style="width:50%;padding-right:20px;vertical-align:top;">
      <p><strong>Em nome da Empresa:</strong></p>
      <p>{{legal.company_name}}</p>
      <p style="margin-top:48px;border-top:1px solid #555;padding-top:8px;">Assinatura e Data</p>
      <p>Nome: _______________________________</p>
      <p>Cargo: ______________________________</p>
    </td>
    <td style="width:50%;padding-left:20px;vertical-align:top;">
      <p><strong>Em nome do Cliente:</strong></p>
      <p>[NOME DO CLIENTE]</p>
      <p style="margin-top:48px;border-top:1px solid #555;padding-top:8px;">Assinatura e Data</p>
      <p>Nome: _______________________________</p>
      <p>Cargo: ______________________________</p>
    </td>
  </tr>
</table>
HTML,
            ],

            // ──────────────────────────────────────────────────────────────────
            //  WEBSITE MAINTENANCE AGREEMENT — ENGLISH
            // ──────────────────────────────────────────────────────────────────
            [
                'type'     => 'maintenance',
                'language' => 'en',
                'name'     => 'Website Maintenance Agreement (EN)',
                'content'  => <<<'HTML'
<h2>1. Parties</h2>
<p>This Website Maintenance &amp; Support Agreement ("Agreement") is made between:</p>
<ul>
  <li><strong>Service Provider:</strong> {{legal.company_name}}, registered in England and Wales (Company No. {{legal.company_number}}, VAT No. {{legal.vat_number}}), {{legal.company_address}} ("the Company"); and</li>
  <li><strong>Client:</strong> [CLIENT NAME / COMPANY NAME], [CLIENT ADDRESS] ("the Client").</li>
</ul>
<p>This Agreement commences on [START DATE] and continues on a rolling monthly basis unless terminated in accordance with clause 8.</p>

<h2>2. Services Included</h2>
<p>The monthly maintenance retainer covers the following services as specified in the agreed plan:</p>
<ul>
  <li>CMS, plugin, theme, and framework updates (tested in staging before deployment);</li>
  <li>Security monitoring, malware scanning, and vulnerability patching;</li>
  <li>Automated daily backups with [X]-day retention and monthly backup verification;</li>
  <li>Uptime monitoring with automatic alerts (target: 99.9% uptime);</li>
  <li>Up to [X] hours per month of minor content updates (text, images, basic layout changes);</li>
  <li>Bug fixes for issues arising from normal use and covered platform updates;</li>
  <li>Monthly performance and uptime report delivered by email.</li>
</ul>
<p>Services not listed above (new feature development, major redesigns, SEO campaigns, third-party integrations) are not included and will be quoted separately.</p>

<h2>3. Response Times &amp; Service Level</h2>
<ul>
  <li><strong>Critical issues</strong> (site down, security breach, data loss): initial response within <strong>4 business hours</strong>;</li>
  <li><strong>High priority</strong> (major functionality broken, checkout failing): response within <strong>1 business day</strong>;</li>
  <li><strong>Standard requests</strong> (content updates, minor bugs): completed within <strong>3 business days</strong>.</li>
</ul>
<p>Response times apply during standard business hours (Mon–Fri, 09:00–17:30 UK time), excluding UK public holidays.</p>

<h2>4. Fees &amp; Payment</h2>
<p>The monthly retainer fee is as stated in the agreed quotation and is invoiced in advance at the start of each calendar month. Payment is due within <strong>{{legal.payment_terms_days}} days</strong> of invoice date.</p>
<p>Unused hours in a given month do not carry over to subsequent months. Hours used beyond the included allowance are charged at the Company's standard hourly rate, notified to the Client before work commences.</p>
<p>The Company reserves the right to review and adjust retainer fees on <strong>30 days' written notice</strong>, not more than once per 12-month period.</p>
<p>Late payments accrue statutory interest under the Late Payment of Commercial Debts (Interest) Act 1998 at 8% above the Bank of England base rate. The Company may suspend services on accounts overdue by more than 14 days.</p>

<h2>5. Client Responsibilities</h2>
<p>The Client agrees to:</p>
<ul>
  <li>Provide valid login credentials and server/hosting access necessary to perform maintenance;</li>
  <li>Ensure test/staging environment access is available where required;</li>
  <li>Notify the Company promptly of any issues, changes, or security incidents affecting the website;</li>
  <li>Not make direct modifications to core files, plugins, or themes without prior notice to the Company;</li>
  <li>Maintain valid hosting, domain, and third-party software subscriptions required for website operation;</li>
  <li>Ensure the website content remains compliant with applicable laws (GDPR, Consumer Rights Act, advertising standards).</li>
</ul>

<h2>6. Limitations &amp; Exclusions</h2>
<p>The following are excluded from this maintenance agreement:</p>
<ul>
  <li>Issues caused by modifications made by the Client or third parties without the Company's knowledge;</li>
  <li>Failures of third-party hosting providers, CDN services, or external APIs beyond the Company's control;</li>
  <li>Damage caused by the Client's hardware, local network, or browser configuration;</li>
  <li>Restoration from backups caused by Client error (may incur additional charges);</li>
  <li>Development work beyond the included hours.</li>
</ul>

<h2>7. Intellectual Property &amp; Data</h2>
<p>All pre-existing intellectual property rights remain with their respective owners. No new intellectual property is created by routine maintenance activities. The Company treats all Client data and website credentials as Confidential Information and will not access, copy, or share such data beyond what is strictly necessary to perform the Services.</p>

<h2>8. Termination</h2>
<p>Either Party may terminate this Agreement by providing <strong>30 days' written notice</strong>. The Client remains liable for retainer fees up to and including the last day of the notice period. The Company will deliver all access credentials, backups, and documentation held upon termination.</p>
<p>The Company may terminate immediately with written notice if the Client fails to pay within 30 days of the invoice due date after a payment reminder has been sent, or if the Client materially breaches any term of this Agreement.</p>

<h2>9. Limitation of Liability</h2>
<p>The Company's total aggregate liability under this Agreement shall not exceed the total retainer fees paid in the preceding three (3) months. The Company is not liable for data loss, loss of revenue, or business disruption caused by third-party service failures, hosting outages, the Client's own modifications, or events beyond the Company's reasonable control.</p>
<p>Nothing in this Agreement excludes liability for death or personal injury caused by negligence, fraud, or any other liability that cannot be excluded by applicable law.</p>

<h2>10. Governing Law</h2>
<p>This Agreement is governed by the laws of <strong>England and Wales</strong>. Any disputes shall be subject to the exclusive jurisdiction of the courts of England and Wales.</p>
<p>Contact: <a href="mailto:{{legal.company_email}}">{{legal.company_email}}</a> | {{legal.company_phone}}</p>

<h2>11. Signatures</h2>
<p>By signing below (or confirming by email), both Parties agree to the terms of this Agreement.</p>
<table style="width:100%;border-collapse:collapse;margin-top:24px;">
  <tr>
    <td style="width:50%;padding-right:20px;vertical-align:top;">
      <p><strong>For and on behalf of the Company:</strong></p>
      <p>{{legal.company_name}}</p>
      <p style="margin-top:48px;border-top:1px solid #555;padding-top:8px;">Signature &amp; Date</p>
      <p>Printed Name: _______________________</p>
      <p>Position: ___________________________</p>
    </td>
    <td style="width:50%;padding-left:20px;vertical-align:top;">
      <p><strong>For and on behalf of the Client:</strong></p>
      <p>[CLIENT NAME]</p>
      <p style="margin-top:48px;border-top:1px solid #555;padding-top:8px;">Signature &amp; Date</p>
      <p>Printed Name: _______________________</p>
      <p>Position: ___________________________</p>
    </td>
  </tr>
</table>
HTML,
            ],

            // ──────────────────────────────────────────────────────────────────
            //  WEBSITE MAINTENANCE AGREEMENT — POLISH
            // ──────────────────────────────────────────────────────────────────
            [
                'type'     => 'maintenance',
                'language' => 'pl',
                'name'     => 'Umowa o Utrzymanie Strony Internetowej (PL)',
                'content'  => <<<'HTML'
<h2>1. Strony Umowy</h2>
<p>Niniejsza Umowa o Utrzymanie i Wsparcie Strony Internetowej ("Umowa") jest zawarta pomiędzy:</p>
<ul>
  <li><strong>Wykonawcą:</strong> {{legal.company_name}}, spółką zarejestrowaną w Anglii i Walii (numer rejestracyjny: {{legal.company_number}}, numer VAT: {{legal.vat_number}}), adres: {{legal.company_address}} ("Wykonawca"); oraz</li>
  <li><strong>Zamawiającym:</strong> [NAZWA KLIENTA / FIRMY KLIENTA], [ADRES KLIENTA] ("Zamawiający").</li>
</ul>
<p>Niniejsza Umowa obowiązuje od [DATA ROZPOCZĘCIA] i ulega automatycznemu odnowieniu na kolejne miesiące kalendarzowe, o ile nie zostanie wypowiedziana zgodnie z pkt 8.</p>

<h2>2. Zakres Usług</h2>
<p>Miesięczny abonament serwisowy obejmuje następujące usługi zgodne z uzgodnionym planem:</p>
<ul>
  <li>aktualizacje CMS, wtyczek, motywów i frameworków (testowane w środowisku staging przed wdrożeniem);</li>
  <li>monitoring bezpieczeństwa, skanowanie w poszukiwaniu złośliwego oprogramowania i instalacja poprawek;</li>
  <li>zautomatyzowane codzienne kopie zapasowe z retencją [X] dni oraz miesięczna weryfikacja kopii;</li>
  <li>monitoring dostępności strony z automatycznymi alertami (cel: 99,9% uptime);</li>
  <li>do [X] godzin miesięcznie na drobne aktualizacje treści (teksty, obrazy, zmiany układu);</li>
  <li>usuwanie błędów wynikających z normalnego użytkowania i obsługiwanych aktualizacji platformy;</li>
  <li>miesięczny raport wydajności i dostępności przesyłany pocztą elektroniczną.</li>
</ul>
<p>Usługi nieujęte powyżej (tworzenie nowych funkcji, przebudowy, kampanie SEO, integracje z systemami zewnętrznymi) nie są objęte abonamentem i będą wyceniane odrębnie.</p>

<h2>3. Czasy Reakcji i Poziom Usług</h2>
<ul>
  <li><strong>Problemy krytyczne</strong> (strona niedostępna, naruszenie bezpieczeństwa, utrata danych): wstępna reakcja w ciągu <strong>4 godzin roboczych</strong>;</li>
  <li><strong>Wysoki priorytet</strong> (poważna awaria funkcjonalności, błąd procesu zakupowego): reakcja w ciągu <strong>1 dnia roboczego</strong>;</li>
  <li><strong>Standardowe zlecenia</strong> (aktualizacje treści, drobne błędy): realizacja w ciągu <strong>3 dni roboczych</strong>.</li>
</ul>
<p>Czasy reakcji obowiązują w standardowych godzinach pracy (pon.–pt., 09:00–17:30 czasu UK), z wyłączeniem brytyjskich dni ustawowo wolnych od pracy.</p>

<h2>4. Opłaty i Płatności</h2>
<p>Miesięczna opłata abonamentowa jest określona w uzgodnionej Ofercie i fakturowana z góry na początku każdego miesiąca kalendarzowego. Płatność jest wymagalna w ciągu <strong>{{legal.payment_terms_days}} dni</strong> od daty wystawienia faktury.</p>
<p>Niewykorzystane godziny w danym miesiącu nie przechodzą na następne okresy. Godziny przekraczające uwzględniony limit są rozliczane według standardowej stawki godzinowej Wykonawcy, o której Zamawiający zostanie poinformowany przed przystąpieniem do prac.</p>
<p>Wykonawca zastrzega sobie prawo weryfikacji i korekty opłat abonamentowych za <strong>30-dniowym pisemnym wypowiedzeniem</strong>, nie częściej niż raz na 12 miesięcy.</p>
<p>Opóźnione płatności podlegają ustawowym odsetkom za opóźnienie w transakcjach handlowych. Wykonawca może zawiesić świadczenie usług w przypadku zaległości przekraczającej 14 dni od terminu płatności.</p>

<h2>5. Obowiązki Zamawiającego</h2>
<p>Zamawiający zobowiązuje się do:</p>
<ul>
  <li>dostarczenia ważnych danych dostępowych oraz dostępu do serwera i hostingu niezbędnych do realizacji prac serwisowych;</li>
  <li>zapewnienia dostępu do środowiska testowego/staging, gdy jest to wymagane;</li>
  <li>niezwłocznego informowania Wykonawcy o wszelkich problemach, zmianach lub incydentach bezpieczeństwa dotyczących strony;</li>
  <li>niemodyfikowania bezpośrednio plików rdzenia, wtyczek ani motywów bez uprzedniego zawiadomienia Wykonawcy;</li>
  <li>utrzymywania ważnych subskrypcji hostingu, domeny i zewnętrznego oprogramowania niezbędnych do działania strony;</li>
  <li>dbania o zgodność treści strony z obowiązującymi przepisami (RODO, ustawa o prawach konsumenta, przepisy reklamowe).</li>
</ul>

<h2>6. Ograniczenia i Wyłączenia</h2>
<p>Poza zakresem niniejszej umowy pozostają:</p>
<ul>
  <li>problemy spowodowane modyfikacjami dokonanymi przez Zamawiającego lub osoby trzecie bez wiedzy Wykonawcy;</li>
  <li>awarie zewnętrznych dostawców hostingu, usług CDN lub interfejsów API poza kontrolą Wykonawcy;</li>
  <li>uszkodzenia spowodowane sprzętem Zamawiającego, siecią lokalną lub konfiguracją przeglądarki;</li>
  <li>przywrócenie danych z kopii zapasowej spowodowane błędem Zamawiającego (może wiązać się z dodatkowymi opłatami);</li>
  <li>prace programistyczne przekraczające uwzględniony limit godzin.</li>
</ul>

<h2>7. Własność Intelektualna i Dane</h2>
<p>Wszelkie istniejące prawa własności intelektualnej pozostają przy ich właścicielach. Rutynowe czynności serwisowe nie tworzą nowej własności intelektualnej. Wykonawca traktuje wszelkie dane Zamawiającego i dane dostępowe do strony jako Informacje Poufne i nie uzyska do nich dostępu, nie skopiuje ani nie udostępni ich poza zakresem niezbędnym do świadczenia Usług.</p>

<h2>8. Wypowiedzenie Umowy</h2>
<p>Każda ze Stron może wypowiedzieć niniejszą Umowę z <strong>30-dniowym pisemnym wypowiedzeniem</strong>. Zamawiający pozostaje zobowiązany do uiszczenia opłat abonamentowych do ostatniego dnia okresu wypowiedzenia włącznie. Wykonawca przekaże wszelkie dane dostępowe, kopie zapasowe i dokumentację po rozwiązaniu Umowy.</p>
<p>Wykonawca może rozwiązać Umowę ze skutkiem natychmiastowym w formie pisemnego zawiadomienia, jeżeli Zamawiający nie uiści płatności w ciągu 30 dni od terminu wymagalności faktury po uprzednim wezwaniu do zapłaty, lub jeżeli Zamawiający w sposób istotny naruszy jakiekolwiek postanowienie niniejszej Umowy.</p>

<h2>9. Ograniczenie Odpowiedzialności</h2>
<p>Łączna odpowiedzialność Wykonawcy wynikająca z niniejszej Umowy nie przekroczy łącznych opłat abonamentowych uiszczonych w ciągu poprzednich trzech (3) miesięcy. Wykonawca nie ponosi odpowiedzialności za utratę danych, utratę przychodów ani zakłócenia działalności spowodowane awariami usług zewnętrznych, problemami z hostingiem, własnymi modyfikacjami Zamawiającego ani zdarzeniami pozostającymi poza uzasadnioną kontrolą Wykonawcy.</p>
<p>Żadne postanowienie niniejszej Umowy nie wyłącza odpowiedzialności za szkodę wyrządzoną z winy umyślnej ani za inną odpowiedzialność, której wyłączenie jest niedopuszczalne na gruncie obowiązujących przepisów prawa.</p>

<h2>10. Prawo Właściwe</h2>
<p>Niniejsza Umowa podlega prawu <strong>Anglii i Walii</strong>. Wszelkie spory podlegają wyłącznej jurysdykcji sądów Anglii i Walii.</p>
<p>Kontakt: <a href="mailto:{{legal.company_email}}">{{legal.company_email}}</a> | {{legal.company_phone}}</p>

<h2>11. Podpisy</h2>
<p>Składając podpisy poniżej (lub potwierdzając akceptację pocztą elektroniczną), obie Strony zobowiązują się do przestrzegania warunków niniejszej Umowy.</p>
<table style="width:100%;border-collapse:collapse;margin-top:24px;">
  <tr>
    <td style="width:50%;padding-right:20px;vertical-align:top;">
      <p><strong>W imieniu Wykonawcy:</strong></p>
      <p>{{legal.company_name}}</p>
      <p style="margin-top:48px;border-top:1px solid #555;padding-top:8px;">Podpis i data</p>
      <p>Imię i nazwisko: ____________________</p>
      <p>Stanowisko: _________________________</p>
    </td>
    <td style="width:50%;padding-left:20px;vertical-align:top;">
      <p><strong>W imieniu Zamawiającego:</strong></p>
      <p>[NAZWA KLIENTA]</p>
      <p style="margin-top:48px;border-top:1px solid #555;padding-top:8px;">Podpis i data</p>
      <p>Imię i nazwisko: ____________________</p>
      <p>Stanowisko: _________________________</p>
    </td>
  </tr>
</table>
HTML,
            ],

            // ──────────────────────────────────────────────────────────────────
            //  WEBSITE MAINTENANCE AGREEMENT — PORTUGUESE
            // ──────────────────────────────────────────────────────────────────
            [
                'type'     => 'maintenance',
                'language' => 'pt',
                'name'     => 'Acordo de Manutenção de Website (PT)',
                'content'  => <<<'HTML'
<h2>1. Partes</h2>
<p>Este Acordo de Manutenção e Suporte de Website ("Acordo") é celebrado entre:</p>
<ul>
  <li><strong>Prestador de Serviços:</strong> {{legal.company_name}}, registada em Inglaterra e no País de Gales (N.º de empresa: {{legal.company_number}}, N.º de IVA: {{legal.vat_number}}), {{legal.company_address}} ("a Empresa"); e</li>
  <li><strong>Cliente:</strong> [NOME DO CLIENTE / EMPRESA DO CLIENTE], [MORADA DO CLIENTE] ("o Cliente").</li>
</ul>
<p>Este Acordo entra em vigor em [DATA DE INÍCIO] e renova-se automaticamente mensalmente, salvo rescisão nos termos da cláusula 8.</p>

<h2>2. Serviços Incluídos</h2>
<p>A mensalidade de manutenção cobre os seguintes serviços, de acordo com o plano acordado:</p>
<ul>
  <li>Atualizações do CMS, plugins, temas e frameworks (testadas em ambiente de staging antes da implementação);</li>
  <li>Monitorização de segurança, análise de malware e aplicação de correções de vulnerabilidades;</li>
  <li>Cópias de segurança diárias automatizadas com retenção de [X] dias e verificação mensal;</li>
  <li>Monitorização de disponibilidade com alertas automáticos (objetivo: 99,9% de uptime);</li>
  <li>Até [X] horas mensais para atualizações de conteúdo menores (textos, imagens, pequenas alterações de layout);</li>
  <li>Correção de erros decorrentes da utilização normal e de atualizações abrangidas pela plataforma;</li>
  <li>Relatório mensal de desempenho e disponibilidade enviado por e-mail.</li>
</ul>
<p>Serviços não listados acima (desenvolvimento de novas funcionalidades, redesigns de grande escala, campanhas SEO, integrações com terceiros) não estão incluídos e serão orçamentados separadamente.</p>

<h2>3. Tempos de Resposta e Nível de Serviço</h2>
<ul>
  <li><strong>Problemas críticos</strong> (site inativo, violação de segurança, perda de dados): resposta inicial em <strong>4 horas úteis</strong>;</li>
  <li><strong>Prioridade alta</strong> (falha principal de funcionalidade, erro no processo de checkout): resposta em <strong>1 dia útil</strong>;</li>
  <li><strong>Pedidos padrão</strong> (atualizações de conteúdo, erros menores): concluídos em <strong>3 dias úteis</strong>.</li>
</ul>
<p>Os tempos de resposta aplicam-se durante o horário normal de trabalho (seg.–sex., 09:00–17:30, hora do Reino Unido), excluindo feriados nacionais do Reino Unido.</p>

<h2>4. Honorários e Pagamento</h2>
<p>A mensalidade de manutenção é a indicada no orçamento acordado e é faturada antecipadamente no início de cada mês civil. O pagamento é exigível no prazo de <strong>{{legal.payment_terms_days}} dias</strong> a contar da data de emissão da fatura.</p>
<p>As horas não utilizadas num determinado mês não transitam para períodos subsequentes. As horas utilizadas além do limite incluído são cobradas à taxa horária normal da Empresa, comunicada ao Cliente antes do início dos trabalhos.</p>
<p>A Empresa reserva-se o direito de rever e ajustar as mensalidades mediante <strong>aviso prévio escrito de 30 dias</strong>, não mais do que uma vez por período de 12 meses.</p>
<p>Os pagamentos em atraso incorrem em juros de mora à taxa legal aplicável. A Empresa pode suspender os serviços em contas com pagamentos em atraso há mais de 14 dias.</p>

<h2>5. Responsabilidades do Cliente</h2>
<p>O Cliente compromete-se a:</p>
<ul>
  <li>Fornecer credenciais de acesso válidas e acesso ao servidor/alojamento necessários para a realização da manutenção;</li>
  <li>Disponibilizar acesso ao ambiente de teste/staging quando necessário;</li>
  <li>Notificar prontamente a Empresa de quaisquer problemas, alterações ou incidentes de segurança que afetem o website;</li>
  <li>Não efetuar modificações diretas em ficheiros de núcleo, plugins ou temas sem aviso prévio à Empresa;</li>
  <li>Manter válidas as subscrições de alojamento, domínio e software de terceiros necessárias para o funcionamento do website;</li>
  <li>Garantir que o conteúdo do website está em conformidade com as leis aplicáveis (RGPD, direitos dos consumidores, normas de publicidade).</li>
</ul>

<h2>6. Limitações e Exclusões</h2>
<p>Estão excluídos deste acordo de manutenção:</p>
<ul>
  <li>Problemas causados por modificações efetuadas pelo Cliente ou por terceiros sem o conhecimento da Empresa;</li>
  <li>Falhas de fornecedores externos de alojamento, serviços de CDN ou APIs fora do controlo da Empresa;</li>
  <li>Danos causados pelo hardware, rede local ou configuração do navegador do Cliente;</li>
  <li>Restauros a partir de cópias de segurança motivados por erro do Cliente (podem implicar encargos adicionais);</li>
  <li>Trabalho de desenvolvimento além das horas incluídas.</li>
</ul>

<h2>7. Propriedade Intelectual e Dados</h2>
<p>Todos os direitos de propriedade intelectual pré-existentes permanecem com os respetivos titulares. As atividades de manutenção de rotina não criam nova propriedade intelectual. A Empresa trata todos os dados do Cliente e as credenciais de acesso ao website como Informação Confidencial, não acedendo, copiando ou partilhando esses dados para além do estritamente necessário para a prestação dos Serviços.</p>

<h2>8. Rescisão</h2>
<p>Qualquer das Partes pode rescindir este Acordo mediante <strong>aviso prévio escrito de 30 dias</strong>. O Cliente mantém a responsabilidade pelo pagamento das mensalidades até ao último dia do período de aviso, inclusive. Após a rescisão, a Empresa entregará todas as credenciais de acesso, cópias de segurança e documentação em sua posse.</p>
<p>A Empresa pode rescindir imediatamente mediante aviso escrito se o Cliente não pagar no prazo de 30 dias após a data de vencimento da fatura, depois de enviado um lembrete, ou se o Cliente violar materialmente qualquer cláusula deste Acordo.</p>

<h2>9. Limitação de Responsabilidade</h2>
<p>A responsabilidade total agregada da Empresa ao abrigo deste Acordo não excederá o total das mensalidades pagas nos três (3) meses anteriores. A Empresa não é responsável por perda de dados, perda de receitas ou perturbação da atividade causada por falhas de serviços de terceiros, interrupções de alojamento, modificações próprias do Cliente ou eventos fora do controlo razoável da Empresa.</p>
<p>Nada neste Acordo exclui a responsabilidade por morte ou lesões pessoais causadas por negligência, fraude ou qualquer outra responsabilidade que não possa ser excluída pela lei aplicável.</p>

<h2>10. Lei Aplicável</h2>
<p>Este Acordo é regido pelas leis de <strong>Inglaterra e País de Gales</strong>. Quaisquer litígios ficam sujeitos à jurisdição exclusiva dos tribunais de Inglaterra e País de Gales.</p>
<p>Contacto: <a href="mailto:{{legal.company_email}}">{{legal.company_email}}</a> | {{legal.company_phone}}</p>

<h2>11. Assinaturas</h2>
<p>Ao assinar abaixo (ou confirmar por e-mail), ambas as Partes declaram-se vinculadas pelos termos deste Acordo.</p>
<table style="width:100%;border-collapse:collapse;margin-top:24px;">
  <tr>
    <td style="width:50%;padding-right:20px;vertical-align:top;">
      <p><strong>Em nome da Empresa:</strong></p>
      <p>{{legal.company_name}}</p>
      <p style="margin-top:48px;border-top:1px solid #555;padding-top:8px;">Assinatura e Data</p>
      <p>Nome: _______________________________</p>
      <p>Cargo: ______________________________</p>
    </td>
    <td style="width:50%;padding-left:20px;vertical-align:top;">
      <p><strong>Em nome do Cliente:</strong></p>
      <p>[NOME DO CLIENTE]</p>
      <p style="margin-top:48px;border-top:1px solid #555;padding-top:8px;">Assinatura e Data</p>
      <p>Nome: _______________________________</p>
      <p>Cargo: ______________________________</p>
    </td>
  </tr>
</table>
HTML,
            ],
        ];
    }
}
