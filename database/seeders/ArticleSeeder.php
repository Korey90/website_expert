<?php

namespace Database\Seeders;

use App\Models\Article;
use Illuminate\Database\Seeder;

class ArticleSeeder extends Seeder
{
    public function run(): void
    {
        foreach ($this->articles() as $data) {
            Article::updateOrCreate(['slug' => $data['slug']], $data);
        }
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function articles(): array
    {
        return [
            [
                'slug' => 'ile-kosztuje-strona-internetowa-w-uk',
                'category' => 'pricing',
                'status' => 'published',
                'published_at' => now()->subDays(12),
                'title' => [
                    'en' => 'How Much Does a Website Cost in the UK? (2026 Price Guide)',
                    'pl' => 'Ile kosztuje strona internetowa w UK? (Przewodnik cenowy 2026)',
                    'pt' => 'Quanto Custa um Website no Reino Unido? (Guia de Preços 2026)',
                ],
                'excerpt' => [
                    'en' => 'A realistic breakdown of website costs in the UK — from a simple brochure site to a full online store, plus ongoing costs most agencies forget to mention.',
                    'pl' => 'Rzetelny przegląd kosztów strony internetowej w UK — od prostej wizytówki po sklep online, oraz koszty utrzymania, o których agencje czasem zapominają wspomnieć.',
                    'pt' => 'Um resumo realista dos custos de um website no Reino Unido — desde um site institucional simples até uma loja online completa, incluindo custos contínuos.',
                ],
                'content' => [
                    'en' => <<<'HTML'
<p>If you've been asking web agencies for a quote and getting wildly different numbers, you're not alone. Website pricing in the UK varies enormously depending on scope, so here's a realistic breakdown for 2026.</p>
<h2>A simple brochure website</h2>
<p>A professional, mobile-first brochure website (up to 5 pages, contact form, basic SEO setup) typically starts <strong>from £799</strong>. This is the right choice for most trades, local services and small businesses that need a credible online presence and a steady stream of enquiries.</p>
<h2>An online store (e-commerce)</h2>
<p>E-commerce is a different scale of project: product catalogue, payment integration, order management and stock handling all add complexity. Expect a wider price range here, driven mostly by the number of products, payment providers and any custom checkout logic you need.</p>
<h2>What actually drives the price?</h2>
<ul>
<li>Number of pages/templates and how custom the design is</li>
<li>Integrations: payments, CRM, booking systems, email marketing</li>
<li>Content: do you provide copy and photos, or do we create them?</li>
<li>Multilingual requirements (e.g. Polish + English content)</li>
<li>Ongoing SEO and marketing on top of the build</li>
</ul>
<h2>Costs agencies sometimes forget to mention</h2>
<p>Domain renewal, hosting, SSL, backups and security monitoring are recurring costs that continue long after launch. Always ask what's included in "maintenance" before you sign anything — a cheap build with an expensive, vague maintenance contract can cost more over 3 years than a slightly pricier, transparent package.</p>
<h2>Get a real number in minutes</h2>
<p>Instead of guessing, use our free calculator to get an instant estimate based on your actual requirements, shown in your local currency.</p>
HTML,
                    'pl' => <<<'HTML'
<p>Jeśli pytałeś już kilka agencji o wycenę strony i dostałeś kompletnie różne kwoty — nie jesteś sam. Ceny stron internetowych w UK potrafią się mocno różnić w zależności od zakresu, dlatego poniżej rzetelny przegląd na 2026 rok.</p>
<h2>Prosta strona wizytówkowa</h2>
<p>Profesjonalna, zoptymalizowana pod telefony strona wizytówkowa (do 5 podstron, formularz kontaktowy, podstawowe SEO) zaczyna się <strong>od £799</strong>. To dobry wybór dla większości rzemieślników, usług lokalnych i małych firm, które potrzebują wiarygodnej obecności online i stałego napływu zapytań.</p>
<h2>Sklep internetowy (e-commerce)</h2>
<p>Sklep internetowy to zupełnie inna skala projektu: katalog produktów, integracja płatności, zarządzanie zamówieniami i stanami magazynowymi dodają złożoności. Tutaj widełki cenowe są szersze i zależą głównie od liczby produktów, dostawców płatności oraz niestandardowej logiki koszyka/checkoutu.</p>
<h2>Co realnie wpływa na cenę?</h2>
<ul>
<li>Liczba podstron/szablonów i stopień unikalności designu</li>
<li>Integracje: płatności, CRM, systemy rezerwacji, mailing</li>
<li>Treści: czy dostarczasz teksty i zdjęcia, czy my je tworzymy</li>
<li>Wymagania wielojęzyczne (np. treści po polsku i angielsku)</li>
<li>Dalsze SEO i marketing poza samą budową strony</li>
</ul>
<h2>Koszty, o których agencje czasem zapominają wspomnieć</h2>
<p>Odnowienie domeny, hosting, SSL, kopie zapasowe i monitoring bezpieczeństwa to koszty cykliczne, które pojawiają się długo po uruchomieniu strony. Zawsze dopytaj, co dokładnie wchodzi w "opiekę nad stroną", zanim podpiszesz umowę — tania budowa z drogą i niejasną opieką może w 3 lata kosztować więcej niż nieco droższy, ale przejrzysty pakiet.</p>
<h2>Policz realny koszt w kilka minut</h2>
<p>Zamiast zgadywać, skorzystaj z naszego darmowego kalkulatora i otrzymaj natychmiastową wycenę dopasowaną do Twoich wymagań, pokazaną w Twojej lokalnej walucie.</p>
HTML,
                    'pt' => <<<'HTML'
<p>Se já pediu orçamentos a várias agências e recebeu valores completamente diferentes, não está sozinho. Os preços de websites no Reino Unido variam muito consoante o âmbito do projeto.</p>
<h2>Um website institucional simples</h2>
<p>Um website profissional, otimizado para telemóvel (até 5 páginas, formulário de contacto, SEO básico) começa <strong>a partir de £799</strong>. É a escolha certa para a maioria dos negócios locais que precisam de uma presença online credível.</p>
<h2>Uma loja online (e-commerce)</h2>
<p>Uma loja online é um projeto de outra escala: catálogo de produtos, integração de pagamentos e gestão de encomendas aumentam a complexidade e o preço.</p>
<h2>O que influencia realmente o preço?</h2>
<ul>
<li>Número de páginas e nível de personalização do design</li>
<li>Integrações: pagamentos, CRM, reservas, email marketing</li>
<li>Conteúdo: fornece textos e fotos ou criamos nós?</li>
<li>Requisitos multilingues (ex: conteúdo em polaco e inglês)</li>
</ul>
<h2>Custos que as agências às vezes esquecem de mencionar</h2>
<p>Domínio, alojamento, SSL, backups e monitorização de segurança são custos recorrentes. Pergunte sempre o que está incluído na "manutenção" antes de assinar qualquer contrato.</p>
<h2>Obtenha um valor real em minutos</h2>
<p>Use a nossa calculadora gratuita para obter uma estimativa instantânea baseada nos seus requisitos reais.</p>
HTML,
                ],
                'meta_title' => [
                    'en' => 'How Much Does a Website Cost in the UK? | Website Expert',
                    'pl' => 'Ile kosztuje strona internetowa w UK? | Website Expert',
                    'pt' => 'Quanto Custa um Website no Reino Unido? | Website Expert',
                ],
                'meta_description' => [
                    'en' => 'Realistic 2026 UK website pricing: brochure sites, e-commerce, and the hidden ongoing costs. Get an instant estimate with our free calculator.',
                    'pl' => 'Rzetelne ceny stron internetowych w UK na 2026 rok: wizytówki, e-commerce i ukryte koszty utrzymania. Policz swój koszt za pomocą darmowego kalkulatora.',
                    'pt' => 'Preços realistas de websites no Reino Unido em 2026: sites institucionais, e-commerce e custos ocultos. Obtenha uma estimativa instantânea.',
                ],
            ],
            [
                'slug' => 'strona-po-polsku-czy-po-angielsku',
                'category' => 'pl-business',
                'status' => 'published',
                'published_at' => now()->subDays(10),
                'title' => [
                    'en' => 'Should Your UK Business Website Be in Polish or English?',
                    'pl' => 'Strona internetowa po polsku czy po angielsku? Co wybrać prowadząc firmę w UK',
                    'pt' => 'O Seu Website no Reino Unido Deve Ser em Polaco ou Inglês?',
                ],
                'excerpt' => [
                    'en' => 'Running a Polish business in the UK? Here\'s how to decide between a Polish, English or bilingual website — and why the wrong choice quietly costs you customers.',
                    'pl' => 'Prowadzisz polską firmę w UK? Sprawdź, jak wybrać między stroną po polsku, po angielsku lub dwujęzyczną — i dlaczego zły wybór po cichu kosztuje Cię klientów.',
                    'pt' => 'Dirige um negócio polaco no Reino Unido? Veja como escolher entre um website em polaco, inglês ou bilingue.',
                ],
                'content' => [
                    'en' => <<<'HTML'
<p>This is one of the most common questions we get from Polish entrepreneurs in the UK: "Should my website be in Polish, English, or both?" The honest answer is: it depends on who actually pays you.</p>
<h2>If most of your customers are Polish</h2>
<p>Builders, removals, accountants and shops serving the Polish community in the UK should lead with Polish — it builds instant trust and your customers will find you searching in Polish ("budowlaniec w Londynie", "polski księgowy UK", etc.). This is exactly why we built a dedicated <a href="/pl/strony-internetowe-uk">Polish-language landing page</a> for this audience.</p>
<h2>If you also want British/local customers</h2>
<p>A bilingual website done properly (not a lazy Google-translated page) lets you capture both audiences — but it needs separate, properly written content and separate SEO for each language, with correct <code>hreflang</code> tags so Google shows the right version to the right searcher.</p>
<h2>The mistake we see most often</h2>
<p>Many Polish business owners launch an English-only site "because everyone speaks English" — then wonder why their Polish community customers, who specifically search in Polish and trust Polish-language service providers, never find them. Language isn't just translation; it's a trust signal.</p>
<h2>Our recommendation</h2>
<p>Start with the language of your actual paying customers. If you serve both communities, invest in a real bilingual site rather than a half-translated compromise — it pays for itself in enquiries within months.</p>
HTML,
                    'pl' => <<<'HTML'
<p>To jedno z najczęstszych pytań, jakie słyszymy od polskich przedsiębiorców w UK: "Czy moja strona ma być po polsku, po angielsku, czy dwujęzyczna?". Szczera odpowiedź brzmi: to zależy od tego, kto naprawdę Ci płaci.</p>
<h2>Jeśli większość klientów to Polacy</h2>
<p>Budowlańcy, firmy przeprowadzkowe, księgowi i sklepy obsługujące polską społeczność w UK powinny postawić na język polski — buduje to natychmiastowe zaufanie, a Twoi klienci i tak szukają po polsku ("budowlaniec w Londynie", "polski księgowy UK" itd.). Dokładnie dlatego stworzyliśmy dedykowaną <a href="/pl/strony-internetowe-uk">stronę po polsku</a> dla tej grupy odbiorców.</p>
<h2>Jeśli zależy Ci też na klientach brytyjskich/lokalnych</h2>
<p>Dobrze wykonana strona dwujęzyczna (nie byle jak przetłumaczona przez Google) pozwala dotrzeć do obu grup — wymaga jednak osobnych, porządnie napisanych treści i osobnego SEO dla każdego języka, z poprawnymi tagami <code>hreflang</code>, dzięki którym Google pokaże właściwą wersję właściwej osobie.</p>
<h2>Błąd, który widzimy najczęściej</h2>
<p>Wielu polskich przedsiębiorców uruchamia stronę wyłącznie po angielsku, "bo przecież wszyscy mówią po angielsku" — a potem zastanawia się, dlaczego klienci z polskiej społeczności, którzy szukają konkretnie po polsku i ufają polskojęzycznym usługodawcom, nigdy ich nie znajdują. Język to nie tylko tłumaczenie — to sygnał zaufania.</p>
<h2>Nasza rekomendacja</h2>
<p>Zacznij od języka Twoich realnych, płacących klientów. Jeśli obsługujesz obie społeczności, zainwestuj w prawdziwą stronę dwujęzyczną zamiast półśrodka — zwraca się to w zapytaniach już po kilku miesiącach.</p>
HTML,
                    'pt' => <<<'HTML'
<p>Esta é uma das perguntas mais comuns que recebemos de empresários polacos no Reino Unido: "O meu website deve ser em polaco, inglês, ou ambos?". A resposta honesta é: depende de quem realmente lhe paga.</p>
<h2>Se a maioria dos seus clientes é polaca</h2>
<p>Negócios que servem a comunidade polaca no Reino Unido devem apostar no polaco — cria confiança imediata e os seus clientes procuram mesmo em polaco.</p>
<h2>Se também quer clientes britânicos/locais</h2>
<p>Um website bilingue bem feito (não uma tradução automática) permite alcançar ambos os públicos, mas precisa de conteúdo e SEO próprios para cada idioma, com as tags <code>hreflang</code> corretas.</p>
<h2>O erro mais comum</h2>
<p>Muitos empresários lançam um site apenas em inglês "porque toda a gente fala inglês" — e depois perguntam-se porque os clientes da comunidade polaca nunca os encontram. O idioma não é só tradução, é um sinal de confiança.</p>
<h2>A nossa recomendação</h2>
<p>Comece pelo idioma dos seus clientes reais. Se serve ambas as comunidades, invista num verdadeiro site bilingue.</p>
HTML,
                ],
                'meta_title' => [
                    'en' => 'Polish or English Website for Your UK Business? | Website Expert',
                    'pl' => 'Strona po polsku czy po angielsku dla firmy w UK? | Website Expert',
                    'pt' => 'Website em Polaco ou Inglês para o seu Negócio no UK? | Website Expert',
                ],
                'meta_description' => [
                    'en' => 'Polish business in the UK? Learn how to choose between a Polish, English or bilingual website to stop losing customers to the wrong language.',
                    'pl' => 'Prowadzisz polską firmę w UK? Dowiedz się, jak wybrać język strony internetowej, żeby nie tracić klientów przez zły wybór.',
                    'pt' => 'Negócio polaco no Reino Unido? Saiba como escolher entre um website em polaco, inglês ou bilingue.',
                ],
            ],
            [
                'slug' => 'wordpress-czy-strona-dedykowana',
                'category' => 'comparison',
                'status' => 'published',
                'published_at' => now()->subDays(8),
                'title' => [
                    'en' => 'WordPress or a Custom-Built Website? An Honest Comparison',
                    'pl' => 'WordPress czy strona dedykowana? Szczere porównanie',
                    'pt' => 'WordPress ou um Website Personalizado? Uma Comparação Honesta',
                ],
                'excerpt' => [
                    'en' => 'Both can work. Here\'s an honest, no-sales-pitch comparison of WordPress vs a custom-built website so you can pick the right option for your business.',
                    'pl' => 'Oba rozwiązania mogą się sprawdzić. Oto szczere, bez naciągania, porównanie WordPressa i strony dedykowanej, żebyś mógł wybrać to, co faktycznie pasuje do Twojej firmy.',
                    'pt' => 'Ambos podem funcionar. Uma comparação honesta entre WordPress e um website personalizado.',
                ],
                'content' => [
                    'en' => <<<'HTML'
<p>We get asked this constantly, and the honest answer is: neither option is universally "better" — they solve different problems.</p>
<h2>WordPress: fast, familiar, flexible</h2>
<p>WordPress powers a huge share of the web for good reason. It's quick to launch, there's a plugin for almost anything, and content editors who've used WordPress before will feel at home immediately. It's a solid choice for straightforward brochure sites and blogs with a tight budget and timeline.</p>
<h2>The trade-offs of WordPress</h2>
<ul>
<li>Plugin sprawl can slow pages down and create security vulnerabilities if not maintained</li>
<li>Updates to core, theme and plugins need ongoing attention or things break</li>
<li>Highly custom functionality often means stitching together multiple plugins rather than a clean, purpose-built solution</li>
</ul>
<h2>A custom-built website: built for exactly what you need</h2>
<p>A dedicated build (what we do by default for our <a href="/services/brochure-websites">brochure website service</a>) means no unnecessary plugin bloat, code written specifically for your business logic, and performance/security tuned from day one rather than bolted on.</p>
<h2>The trade-offs of custom builds</h2>
<p>It typically takes a little longer to launch than a template-based WordPress site, and day-to-day content edits may need a simpler, purpose-built admin panel rather than WordPress's familiar editor — which is exactly why we build our own lightweight content tools into every project.</p>
<h2>So which should you choose?</h2>
<p>If you need a simple site fast, on a tight budget, and you're comfortable maintaining plugins yourself — WordPress is fine. If you need strong performance, tight security, and functionality specific to your business (bookings, quoting tools, multi-currency pricing, multilingual content), a custom build pays off within the first year.</p>
HTML,
                    'pl' => <<<'HTML'
<p>To pytanie słyszymy bardzo często, a szczera odpowiedź brzmi: żadne rozwiązanie nie jest "lepsze" uniwersalnie — oba rozwiązują inne problemy.</p>
<h2>WordPress: szybko, znajomo, elastycznie</h2>
<p>WordPress napędza ogromną część internetu i nie bez powodu. Można go szybko uruchomić, istnieje wtyczka niemal do wszystkiego, a osoby, które kiedyś z niego korzystały, od razu czują się jak w domu. To solidny wybór dla prostych stron wizytówkowych i blogów przy ograniczonym budżecie i czasie.</p>
<h2>Minusy WordPressa</h2>
<ul>
<li>Nadmiar wtyczek potrafi spowalniać stronę i tworzyć luki bezpieczeństwa, jeśli nie jest regularnie aktualizowany</li>
<li>Aktualizacje rdzenia, motywu i wtyczek wymagają stałej uwagi, inaczej coś się psuje</li>
<li>Bardzo nietypowa funkcjonalność często oznacza sklejanie kilku wtyczek zamiast czystego, dedykowanego rozwiązania</li>
</ul>
<h2>Strona dedykowana: zbudowana dokładnie pod Twoje potrzeby</h2>
<p>Dedykowana budowa (czyli standard w naszej <a href="/services/brochure-websites">usłudze stron wizytówkowych</a>) oznacza brak zbędnego balastu wtyczek, kod napisany specjalnie pod logikę Twojego biznesu oraz wydajność i bezpieczeństwo dopięte od pierwszego dnia, a nie doklejone później.</p>
<h2>Minusy strony dedykowanej</h2>
<p>Zwykle trwa to trochę dłużej niż uruchomienie gotowego szablonu WordPress, a codzienna edycja treści wymaga prostszego, dedykowanego panelu zamiast znanego edytora WordPressa — dlatego właśnie do każdego projektu budujemy własne, lekkie narzędzia do zarządzania treścią.</p>
<h2>Który wybrać?</h2>
<p>Jeśli potrzebujesz prostej strony szybko, przy ograniczonym budżecie, i jesteś gotów samodzielnie dbać o wtyczki — WordPress się sprawdzi. Jeśli zależy Ci na wysokiej wydajności, bezpieczeństwie i funkcjach specyficznych dla Twojego biznesu (rezerwacje, narzędzia wycenowe, ceny w wielu walutach, treści wielojęzyczne) — strona dedykowana zwróci się już w pierwszym roku.</p>
HTML,
                    'pt' => <<<'HTML'
<p>Esta pergunta surge constantemente, e a resposta honesta é: nenhuma opção é universalmente "melhor" — resolvem problemas diferentes.</p>
<h2>WordPress: rápido, familiar, flexível</h2>
<p>O WordPress move uma grande parte da internet por boas razões. É rápido de lançar e existe um plugin para quase tudo. É uma boa escolha para sites institucionais simples com orçamento e prazo apertados.</p>
<h2>As desvantagens do WordPress</h2>
<p>O excesso de plugins pode tornar o site lento e criar vulnerabilidades de segurança se não for mantido regularmente.</p>
<h2>Um website personalizado: feito exatamente para o que precisa</h2>
<p>Uma construção dedicada (o que fazemos por defeito no nosso <a href="/services/brochure-websites">serviço de websites institucionais</a>) significa código escrito especificamente para a lógica do seu negócio, com desempenho e segurança otimizados desde o primeiro dia.</p>
<h2>Qual escolher?</h2>
<p>Se precisa de um site simples e rápido com orçamento apertado, o WordPress serve. Se precisa de desempenho forte, segurança e funcionalidades específicas do seu negócio, um website personalizado compensa já no primeiro ano.</p>
HTML,
                ],
                'meta_title' => [
                    'en' => 'WordPress vs Custom Website: Which Should You Choose? | Website Expert',
                    'pl' => 'WordPress czy strona dedykowana? Co wybrać | Website Expert',
                    'pt' => 'WordPress vs Website Personalizado: Qual Escolher? | Website Expert',
                ],
                'meta_description' => [
                    'en' => 'An honest, no-sales-pitch comparison of WordPress vs a custom-built website — costs, performance, security and maintenance explained.',
                    'pl' => 'Szczere porównanie WordPressa i strony dedykowanej — koszty, wydajność, bezpieczeństwo i utrzymanie bez marketingowego naciągania.',
                    'pt' => 'Uma comparação honesta entre WordPress e um website personalizado — custos, desempenho, segurança e manutenção.',
                ],
            ],
            [
                'slug' => 'seo-lokalne-dla-polskiej-firmy-w-uk',
                'category' => 'seo',
                'status' => 'published',
                'published_at' => now()->subDays(6),
                'title' => [
                    'en' => 'Local SEO for Polish Businesses in the UK: A Practical Guide',
                    'pl' => 'SEO lokalne dla polskiej firmy w UK: praktyczny przewodnik',
                    'pt' => 'SEO Local para Negócios Polacos no Reino Unido: Um Guia Prático',
                ],
                'excerpt' => [
                    'en' => 'How Polish businesses in the UK can actually show up on Google — Google Business Profile, bilingual keywords, reviews, and local citations explained simply.',
                    'pl' => 'Jak polska firma w UK może realnie pojawić się w Google — profil Google Firma, dwujęzyczne słowa kluczowe, opinie i lokalne wzmianki, wyjaśnione prostym językiem.',
                    'pt' => 'Como os negócios polacos no Reino Unido podem aparecer no Google — perfil, palavras-chave bilingues e avaliações explicadas de forma simples.',
                ],
                'content' => [
                    'en' => <<<'HTML'
<p>Having a website isn't enough if nobody can find it. Here's a practical local SEO checklist specifically for Polish businesses operating in the UK.</p>
<h2>1. Claim and complete your Google Business Profile</h2>
<p>This is the single highest-impact, free thing you can do. Fill in every field, add real photos, choose accurate categories, and keep your opening hours current. For local searches like "polski elektryk w Manchesterze", this listing often matters more than your website itself.</p>
<h2>2. Target bilingual keywords, deliberately</h2>
<p>Your Polish customers search differently in Polish than in English — "polski fryzjer Londyn" is not the same search as "Polish hairdresser London", and both have real volume. A well-built site targets both, with dedicated, properly translated (not auto-translated) content for each.</p>
<h2>3. Collect reviews — in both languages</h2>
<p>Ask happy customers to leave a Google review. Reviews written in Polish signal to other Polish searchers "this business understands people like me" — which builds trust faster than almost anything else.</p>
<h2>4. Get listed consistently across local directories</h2>
<p>Your business name, address and phone number (NAP) should match exactly across your website, Google Business Profile, Facebook, and any Polish community directories you're listed in. Inconsistent details quietly undermine your local rankings.</p>
<h2>5. Make sure your site is actually fast and crawlable</h2>
<p>Technical basics matter: a fast-loading, mobile-friendly site with clean URLs and proper meta tags for every page gives Google what it needs to rank you well. This is foundational work we build into every site from day one.</p>
<h2>Want a professional audit?</h2>
<p>If you're not sure where you currently stand, our <a href="/services/seo">SEO service</a> includes a full audit of your current visibility and a prioritised action plan.</p>
HTML,
                    'pl' => <<<'HTML'
<p>Posiadanie strony internetowej nie wystarczy, jeśli nikt jej nie znajduje. Oto praktyczna checklista SEO lokalnego specjalnie dla polskich firm działających w UK.</p>
<h2>1. Uzupełnij profil Google Firma</h2>
<p>To najprostsza i darmowa rzecz o największym wpływie. Wypełnij każde pole, dodaj prawdziwe zdjęcia, wybierz trafne kategorie i aktualizuj godziny otwarcia. Przy wyszukiwaniach lokalnych typu "polski elektryk w Manchesterze" ten wpis często liczy się bardziej niż sama strona.</p>
<h2>2. Celuj świadomie w dwujęzyczne słowa kluczowe</h2>
<p>Twoi klienci inaczej szukają po polsku niż po angielsku — "polski fryzjer Londyn" to zupełnie inne zapytanie niż "Polish hairdresser London", a oba mają realny ruch wyszukiwań. Dobrze zbudowana strona celuje w oba, z osobnymi, porządnie przetłumaczonymi (nie automatycznie) treściami dla każdego języka.</p>
<h2>3. Zbieraj opinie — w obu językach</h2>
<p>Poproś zadowolonych klientów o opinię w Google. Opinie po polsku sygnalizują innym polskim użytkownikom "ta firma rozumie takich jak ja" — co buduje zaufanie szybciej niż niemal cokolwiek innego.</p>
<h2>4. Zadbaj o spójność danych w katalogach lokalnych</h2>
<p>Nazwa firmy, adres i numer telefonu (NAP) powinny być identyczne na stronie, w profilu Google Firma, na Facebooku i w polskich katalogach firm, w których figurujesz. Niespójne dane po cichu osłabiają Twoje pozycje lokalne.</p>
<h2>5. Zadbaj o szybkość i techniczną poprawność strony</h2>
<p>Techniczne podstawy mają znaczenie: szybko ładująca się, mobilna strona z czystymi adresami URL i poprawnymi meta tagami na każdej podstronie daje Google to, czego potrzebuje, by dobrze Cię zrankować. To fundament, który wbudowujemy w każdą stronę od pierwszego dnia.</p>
<h2>Chcesz profesjonalny audyt?</h2>
<p>Jeśli nie wiesz, jak obecnie wygląda Twoja widoczność, nasza <a href="/services/seo">usługa SEO</a> obejmuje pełny audyt i plan działań z priorytetami.</p>
HTML,
                    'pt' => <<<'HTML'
<p>Ter um website não é suficiente se ninguém o encontrar. Aqui está uma checklist prática de SEO local para negócios polacos a operar no Reino Unido.</p>
<h2>1. Reivindique o seu Perfil de Empresa Google</h2>
<p>É a ação gratuita de maior impacto que pode fazer. Preencha todos os campos, adicione fotos reais e mantenha o horário atualizado.</p>
<h2>2. Direcione palavras-chave bilingues</h2>
<p>Os seus clientes polacos pesquisam de forma diferente em polaco e em inglês. Um site bem construído direciona ambos, com conteúdo próprio e bem traduzido para cada idioma.</p>
<h2>3. Reúna avaliações — em ambos os idiomas</h2>
<p>Peça aos clientes satisfeitos para deixarem uma avaliação no Google. Avaliações em polaco geram confiança imediata junto de outros pesquisadores polacos.</p>
<h2>4. Mantenha os dados consistentes nos diretórios locais</h2>
<p>O nome, morada e telefone da sua empresa devem ser idênticos em todo o lado online.</p>
<h2>5. Garanta que o seu site é rápido e indexável</h2>
<p>Velocidade, adaptação a telemóveis e meta tags corretas são a base para um bom posicionamento no Google.</p>
<h2>Quer uma auditoria profissional?</h2>
<p>O nosso <a href="/services/seo">serviço de SEO</a> inclui uma auditoria completa e um plano de ação com prioridades.</p>
HTML,
                ],
                'meta_title' => [
                    'en' => 'Local SEO for Polish Businesses in the UK | Website Expert',
                    'pl' => 'SEO lokalne dla polskiej firmy w UK | Website Expert',
                    'pt' => 'SEO Local para Negócios Polacos no Reino Unido | Website Expert',
                ],
                'meta_description' => [
                    'en' => 'A practical local SEO checklist for Polish businesses in the UK: Google Business Profile, bilingual keywords, reviews and technical basics.',
                    'pl' => 'Praktyczna checklista SEO lokalnego dla polskich firm w UK: profil Google Firma, dwujęzyczne słowa kluczowe, opinie i podstawy techniczne.',
                    'pt' => 'Uma checklist prática de SEO local para negócios polacos no Reino Unido.',
                ],
            ],
            [
                'slug' => 'jak-wybrac-wykonawce-strony-internetowej-w-uk',
                'category' => 'pl-business',
                'status' => 'published',
                'published_at' => now()->subDays(4),
                'title' => [
                    'en' => 'How to Choose a Website Developer in the UK (Without Getting Burned)',
                    'pl' => 'Jak wybrać wykonawcę strony internetowej w UK (żeby się nie zawieść)',
                    'pt' => 'Como Escolher um Desenvolvedor de Websites no Reino Unido',
                ],
                'excerpt' => [
                    'en' => 'Red flags, the right questions to ask, and what a fair contract should include — a practical guide before you hire anyone to build your website.',
                    'pl' => 'Czerwone flagi, pytania, które warto zadać, i co powinna zawierać uczciwa umowa — praktyczny przewodnik zanim zlecisz budowę strony komukolwiek.',
                    'pt' => 'Sinais de alerta, as perguntas certas a fazer e o que um contrato justo deve incluir.',
                ],
                'content' => [
                    'en' => <<<'HTML'
<p>We hear a lot of horror stories from Polish business owners who were burned by a "friend of a friend" or an overseas freelancer who disappeared mid-project. Here's how to protect yourself before you pay a deposit.</p>
<h2>Red flags to watch for</h2>
<ul>
<li>No written contract, or a contract with no clear scope, timeline or payment milestones</li>
<li>Asking for 100% payment upfront</li>
<li>No clear answer to "who owns the domain and the code once it's finished?"</li>
<li>No examples of previous, live, working websites they've actually built</li>
<li>Vague answers about ongoing support and what happens if something breaks after launch</li>
</ul>
<h2>Questions worth asking before you commit</h2>
<ul>
<li>Will I own the domain, hosting account and source code outright?</li>
<li>What exactly is included in the price — and what costs extra?</li>
<li>What happens after launch — is there a support period, and what does it cover?</li>
<li>Can I see 2-3 live websites you've built for businesses like mine?</li>
</ul>
<h2>What a fair contract looks like</h2>
<p>A proper agreement sets out the scope of work, clear payment milestones (not 100% upfront), a realistic timeline, who owns what at the end, and what ongoing support includes. Our own <a href="/pl/strony-internetowe-uk">clear-rules approach</a> is built around exactly this kind of transparency — fixed scope, fixed price, no surprise invoices.</p>
<h2>Trust, but verify</h2>
<p>A legitimate agency will happily show you real client websites, explain their process clearly, and put everything in writing without being asked twice. If someone is reluctant to do any of that, treat it as a warning sign.</p>
HTML,
                    'pl' => <<<'HTML'
<p>Słyszymy mnóstwo historii od polskich przedsiębiorców, którzy zostali "wystawieni" przez znajomego znajomego lub zagranicznego freelancera, który zniknął w połowie projektu. Oto jak się zabezpieczyć, zanim wpłacisz zaliczkę.</p>
<h2>Czerwone flagi, na które warto uważać</h2>
<ul>
<li>Brak pisemnej umowy albo umowa bez jasnego zakresu, terminów i etapów płatności</li>
<li>Żądanie 100% płatności z góry</li>
<li>Brak jasnej odpowiedzi na pytanie "kto po ukończeniu będzie właścicielem domeny i kodu?"</li>
<li>Brak przykładów rzeczywistych, działających stron, które faktycznie wykonali</li>
<li>Wymijające odpowiedzi na temat wsparcia po uruchomieniu i tego, co się stanie, gdy coś się zepsuje</li>
</ul>
<h2>Pytania warte zadania przed podjęciem decyzji</h2>
<ul>
<li>Czy będę pełnoprawnym właścicielem domeny, konta hostingowego i kodu źródłowego?</li>
<li>Co dokładnie wchodzi w cenę, a co kosztuje dodatkowo?</li>
<li>Co dzieje się po uruchomieniu — czy jest okres wsparcia i co obejmuje?</li>
<li>Czy mogę zobaczyć 2-3 działające strony zbudowane dla firm podobnych do mojej?</li>
</ul>
<h2>Jak wygląda uczciwa umowa</h2>
<p>Właściwa umowa określa zakres prac, jasne etapy płatności (nie 100% z góry), realny harmonogram, kto jest właścicielem czego po zakończeniu oraz co obejmuje dalsze wsparcie. Nasze <a href="/pl/strony-internetowe-uk">jasne zasady współpracy</a> opierają się dokładnie na takiej przejrzystości — stały zakres, stała cena, żadnych niespodziewanych faktur.</p>
<h2>Ufaj, ale sprawdzaj</h2>
<p>Rzetelna agencja chętnie pokaże Ci prawdziwe strony klientów, jasno wyjaśni proces pracy i wszystko spisze bez proszenia dwa razy. Jeśli ktoś niechętnie to robi — potraktuj to jako sygnał ostrzegawczy.</p>
HTML,
                    'pt' => <<<'HTML'
<p>Ouvimos muitas histórias de empresários que foram prejudicados por um freelancer que desapareceu a meio do projeto. Veja como se proteger antes de pagar um sinal.</p>
<h2>Sinais de alerta</h2>
<ul>
<li>Nenhum contrato escrito, ou um contrato sem âmbito, prazos ou fases de pagamento claras</li>
<li>Pedido de pagamento de 100% adiantado</li>
<li>Nenhuma resposta clara sobre quem fica com o domínio e o código no final</li>
<li>Nenhum exemplo de websites reais e funcionais que tenham construído</li>
</ul>
<h2>Perguntas a fazer antes de contratar</h2>
<ul>
<li>Serei o proprietário total do domínio, alojamento e código-fonte?</li>
<li>O que está exatamente incluído no preço?</li>
<li>O que acontece depois do lançamento?</li>
</ul>
<h2>Como é um contrato justo</h2>
<p>Um acordo adequado define o âmbito do trabalho, fases de pagamento claras, um prazo realista e o que inclui o suporte contínuo.</p>
<h2>Confie, mas verifique</h2>
<p>Uma agência legítima mostra com prazer websites reais de clientes e põe tudo por escrito sem ser preciso insistir.</p>
HTML,
                ],
                'meta_title' => [
                    'en' => 'How to Choose a Website Developer in the UK | Website Expert',
                    'pl' => 'Jak wybrać wykonawcę strony internetowej w UK | Website Expert',
                    'pt' => 'Como Escolher um Desenvolvedor de Websites no UK | Website Expert',
                ],
                'meta_description' => [
                    'en' => 'Red flags, key questions, and what a fair contract should include before you hire anyone to build your business website in the UK.',
                    'pl' => 'Czerwone flagi, kluczowe pytania i co powinna zawierać uczciwa umowa, zanim zlecisz budowę strony internetowej w UK.',
                    'pt' => 'Sinais de alerta e perguntas-chave antes de contratar alguém para construir o seu website no Reino Unido.',
                ],
            ],
            [
                'slug' => 'koszty-domeny-hostingu-i-utrzymania-strony-w-uk',
                'category' => 'pricing',
                'status' => 'published',
                'published_at' => now()->subDays(2),
                'title' => [
                    'en' => 'Domain, Hosting and Website Maintenance Costs in the UK Explained',
                    'pl' => 'Koszty domeny, hostingu i utrzymania strony w UK — wyjaśnione',
                    'pt' => 'Custos de Domínio, Alojamento e Manutenção de Websites no Reino Unido',
                ],
                'excerpt' => [
                    'en' => 'The build is just the start. Here\'s what domains, hosting, SSL and ongoing maintenance actually cost in the UK — and what to watch for in a maintenance contract.',
                    'pl' => 'Budowa strony to dopiero początek. Sprawdź, ile realnie kosztuje domena, hosting, SSL i utrzymanie strony w UK — i na co uważać w umowie serwisowej.',
                    'pt' => 'A construção é só o início. Veja quanto custam realmente domínios, alojamento e manutenção no Reino Unido.',
                ],
                'content' => [
                    'en' => <<<'HTML'
<p>A website isn't a one-off purchase — it's a small piece of ongoing infrastructure. Here's what you should budget for after launch.</p>
<h2>Domain registration and renewal</h2>
<p>A standard .co.uk or .com domain typically renews annually at a modest cost. Watch out for registrars who offer an unusually cheap first-year price and then triple it on renewal — always check the renewal price, not just the introductory offer.</p>
<h2>Hosting</h2>
<p>Hosting cost depends heavily on traffic and technical requirements. A simple brochure site needs far less than an e-commerce store processing hundreds of orders a month. Reliable hosting should include regular backups and basic security monitoring as standard, not as a pricey add-on.</p>
<h2>SSL certificates</h2>
<p>An SSL certificate (the padlock in the browser, required for any site handling forms or payments) should be included in any reasonable hosting or maintenance package today — if an agency wants to charge you extra for basic SSL, that's worth questioning.</p>
<h2>What "maintenance" should actually include</h2>
<ul>
<li>Core software, plugin and security updates</li>
<li>Regular backups you can actually restore from</li>
<li>Uptime monitoring and a quick response if something goes down</li>
<li>Small content updates (text/image changes) without a separate invoice every time</li>
</ul>
<h2>The real lesson</h2>
<p>A cheap build with a vague, expensive maintenance contract often costs more over three years than a transparent package from the start. Always ask for a written breakdown of ongoing costs before you sign, and use our <a href="/calculate">free calculator</a> to see a realistic full picture — build plus ongoing costs — before committing.</p>
HTML,
                    'pl' => <<<'HTML'
<p>Strona internetowa to nie jednorazowy zakup — to mały element infrastruktury wymagający ciągłej opieki. Oto, na co realnie trzeba budżetować po uruchomieniu.</p>
<h2>Rejestracja i odnowienie domeny</h2>
<p>Standardowa domena .co.uk lub .com zwykle odnawia się rocznie za umiarkowaną kwotę. Uważaj na rejestratorów, którzy oferują niezwykle tanią cenę pierwszego roku, a potem potrajają ją przy odnowieniu — zawsze sprawdzaj cenę odnowienia, nie tylko ofertę wprowadzającą.</p>
<h2>Hosting</h2>
<p>Koszt hostingu mocno zależy od ruchu i wymagań technicznych. Prosta strona wizytówkowa potrzebuje znacznie mniej niż sklep internetowy obsługujący setki zamówień miesięcznie. Solidny hosting powinien standardowo obejmować regularne kopie zapasowe i podstawowy monitoring bezpieczeństwa, a nie drogi dodatek.</p>
<h2>Certyfikaty SSL</h2>
<p>Certyfikat SSL (kłódka w przeglądarce, wymagana dla każdej strony z formularzami lub płatnościami) powinien dziś być elementem każdego rozsądnego pakietu hostingowego lub serwisowego — jeśli agencja chce dodatkowo liczyć sobie za podstawowy SSL, warto to zakwestionować.</p>
<h2>Co powinno realnie wchodzić w "opiekę nad stroną"</h2>
<ul>
<li>Aktualizacje rdzenia, wtyczek i bezpieczeństwa</li>
<li>Regularne kopie zapasowe, z których faktycznie można odtworzyć stronę</li>
<li>Monitoring dostępności i szybka reakcja, gdy coś przestanie działać</li>
<li>Drobne zmiany treści (teksty/zdjęcia) bez osobnej faktury za każdym razem</li>
</ul>
<h2>Prawdziwa lekcja</h2>
<p>Tania budowa z niejasną i drogą umową serwisową często kosztuje więcej w perspektywie trzech lat niż przejrzysty pakiet od samego początku. Zawsze proś o pisemny rozkład kosztów bieżących przed podpisaniem umowy i skorzystaj z naszego <a href="/calculate">darmowego kalkulatora</a>, żeby zobaczyć realny, pełny obraz — budowę plus koszty utrzymania — zanim się zdecydujesz.</p>
HTML,
                    'pt' => <<<'HTML'
<p>Um website não é uma compra única — é uma pequena infraestrutura contínua. Veja o que deve orçamentar depois do lançamento.</p>
<h2>Registo e renovação de domínio</h2>
<p>Um domínio .co.uk ou .com padrão renova-se anualmente por um valor modesto. Cuidado com registradores que oferecem um preço muito baixo no primeiro ano e depois triplicam na renovação.</p>
<h2>Alojamento</h2>
<p>O custo do alojamento depende muito do tráfego e dos requisitos técnicos. Um alojamento fiável deve incluir backups regulares e monitorização básica de segurança como padrão.</p>
<h2>Certificados SSL</h2>
<p>Um certificado SSL deve estar incluído em qualquer pacote de alojamento ou manutenção razoável atualmente.</p>
<h2>O que a "manutenção" deve realmente incluir</h2>
<ul>
<li>Atualizações de segurança e de plugins</li>
<li>Backups regulares e restauráveis</li>
<li>Monitorização de disponibilidade</li>
<li>Pequenas alterações de conteúdo sem fatura extra de cada vez</li>
</ul>
<h2>A verdadeira lição</h2>
<p>Use a nossa <a href="/calculate">calculadora gratuita</a> para ver o panorama completo e realista antes de se comprometer.</p>
HTML,
                ],
                'meta_title' => [
                    'en' => 'Domain, Hosting & Maintenance Costs in the UK | Website Expert',
                    'pl' => 'Koszty domeny, hostingu i utrzymania strony w UK | Website Expert',
                    'pt' => 'Custos de Domínio, Alojamento e Manutenção no UK | Website Expert',
                ],
                'meta_description' => [
                    'en' => 'What domains, hosting, SSL and website maintenance really cost in the UK, and what to check in a maintenance contract before you sign.',
                    'pl' => 'Ile naprawdę kosztuje domena, hosting, SSL i utrzymanie strony w UK oraz na co zwrócić uwagę w umowie serwisowej przed podpisaniem.',
                    'pt' => 'Quanto custam realmente domínios, alojamento, SSL e manutenção de websites no Reino Unido.',
                ],
            ],
        ];
    }
}
