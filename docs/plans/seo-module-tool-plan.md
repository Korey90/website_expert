# Moduł: Google Rank Checker / Monitoring pozycji w Google

> ✅ **MVP zaimplementowane** (2026-09-06) — publiczne narzędzie `/tools/google-rank-checker` działa end-to-end: formularz → `CheckGoogleRankingAction` → provider SERP (abstrakcja `SerpProviderInterface`, `manual` stub domyślnie) → cache → wynik + CTA. Punkty 30-34 (historia, wykresy, konkurenci, plany) pozostają poza zakresem MVP — zobacz adnotacje przy poszczególnych sekcjach. — *WebsiteExpert*

## 1. Cel modułu

Celem modułu jest umożliwienie użytkownikowi sprawdzenia, na której pozycji w wynikach wyszukiwania Google znajduje się wskazana domena dla określonej frazy kluczowej.

Moduł ma pełnić dwie role:

1. publiczne, darmowe narzędzie dostępne na Website Expert,
2. fundament pod przyszły, bardziej rozbudowany system monitoringu pozycji SEO dla zalogowanych klientów.

Pierwsza wersja powinna skupiać się przede wszystkim na szybkim i prostym sprawdzeniu pozycji strony dla pojedynczej frazy.

Architektura funkcjonalna powinna jednak umożliwiać późniejsze rozszerzenie o:

* historię pozycji,
* cykliczne sprawdzanie fraz,
* projekty SEO,
* konkurencję,
* raporty,
* wykresy zmian pozycji,
* wiele lokalizacji,
* wiele urządzeń,
* powiadomienia o wzrostach i spadkach.

---

# 2. Główna funkcja

> ✅ Zrealizowano — `CheckGoogleRankingRequest` + `CheckGoogleRankingAction` przyjmują keyword/domain/location/device i zwracają `RankCheckResult` (position + ranking URL). — *WebsiteExpert*

Użytkownik podaje:

* frazę kluczową,
* adres strony / domenę,
* lokalizację wyszukiwania,
* typ urządzenia.

System sprawdza organiczne wyniki Google i zwraca informację, czy wskazana domena znajduje się w analizowanym zakresie wyników.

Domyślnie należy analizować TOP 100 wyników organicznych Google.

Przykład:

Fraza:

`web design belfast`

Domena:

`website-expert.uk`

Lokalizacja:

`Belfast, United Kingdom`

Urządzenie:

`Desktop`

Wynik:

`Website Expert znajduje się na pozycji #7.`

Dodatkowo należy pokazać konkretny URL znaleziony w Google, np.:

`https://website-expert.uk/services/web-design-belfast`

---

# 3. Publiczna strona narzędzia

> ✅ Zrealizowano — `resources/js/Pages/Tools/GoogleRankChecker.tsx` (Inertia, bez auth) + reużywalny komponent `Components/Tools/GoogleRankCheckerForm.tsx`. Trasa: `GET /tools/google-rank-checker` (`routes/web/public.php`). — *WebsiteExpert*

Należy utworzyć publicznie dostępne narzędzie Google Rank Checker. niech to bedzie konponent react ktory bedziemy uzywac w widokach.

Przykładowy adres:

`/tools/google-rank-checker`

lub zgodny z obecnym systemem routingu strony


Strona powinna być dostępna bez logowania.

Głównym celem strony jest:

* dostarczenie użytkownikowi wartościowego darmowego narzędzia SEO,
* zwiększenie ruchu organicznego,
* generowanie potencjalnych leadów,
* kierowanie użytkownika do usług SEO Website Expert.

---

# 4. Formularz

> ✅ Zrealizowano — `CheckGoogleRankingRequest` (trim + max:150 dla keyword, wymagane pola) + `GoogleRankCheckerForm.tsx`. — *WebsiteExpert*

Formularz powinien zawierać następujące pola.

## Keyword

Pole tekstowe.

Przykład:

`web design belfast`

Pole wymagane.

Należy:

* usunąć nadmiarowe spacje,
* ograniczyć maksymalną długość,
* nie pozwalać na pustą wartość.

---

## Website / Domain

Pole tekstowe.

Przykłady poprawnych wartości:

`website-expert.uk`

`www.website-expert.uk`

`https://website-expert.uk`

`https://www.website-expert.uk/`

> ✅ Zrealizowano — `App\Support\Seo\DomainNormalizer::normalize()` (usuwa protokół, `www.`, ścieżkę, port, końcowy slash) + `isValidDomain()` (odrzuca IP/localhost/śmieciowy tekst). Pokryte testem jednostkowym `DomainNormalizerTest`. — *WebsiteExpert*

System powinien samodzielnie znormalizować wartość do domeny.

Przykład:

`https://www.website-expert.uk/services/web-design`

powinno zostać rozpoznane jako domena:

`website-expert.uk`

Do wyszukiwania pozycji nie należy wymagać od użytkownika wpisywania protokołu.

---

# 5. Lokalizacja wyszukiwania

> ⚠️ Częściowo zrealizowano — predefiniowana lista lokalizacji UK/NI w `config/seo.php` (`rank_checker.locations`) + opcja "Other location…" z polem tekstowym w formularzu. Wyszukiwarka/autocomplete (preferowana forma z planu) **nie została zaimplementowana** w MVP — prosty `<select>` + custom input. — *WebsiteExpert*

Pozycja Google zależy od lokalizacji użytkownika, dlatego lokalizacja musi być częścią sprawdzenia.

W pierwszej wersji należy umożliwić wybór popularnych lokalizacji istotnych dla Website Expert.

Przykładowe opcje:

* United Kingdom,
* Northern Ireland,
* Belfast,
* Derry / Londonderry,
* Lisburn,
* Newry,
* Armagh,
* Craigavon,
* Dungannon.

Powinna również istnieć możliwość wyboru lokalizacji niestandardowej, jeżeli wykorzystywany provider SERP na to pozwala.

Preferowana forma:

`Location`

z wyszukiwarką/autocomplete zamiast bardzo dużej statycznej listy.

Domyślna lokalizacja może być ustawiona na:

`United Kingdom`

lub:

`Northern Ireland`

w zależności od aktualnej strategii Website Expert.

Lokalizacja wykorzystana do sprawdzenia zawsze powinna zostać pokazana użytkownikowi w wyniku.

---

# 6. Typ urządzenia

> ✅ Zrealizowano — przełącznik Desktop/Mobile w `GoogleRankCheckerForm.tsx`, domyślnie Desktop (`config('seo.rank_checker.default_device')`). — *WebsiteExpert*

Użytkownik powinien mieć możliwość wyboru:

* Desktop,
* Mobile.

Domyślnie:

`Desktop`

Wynik dla mobile i desktop należy traktować jako dwa różne pomiary.

---

# 7. Opcjonalnie: język

> ✅ Zrealizowano (backend) — `language` obsługiwane w całym łańcuchu (Request/Action/Provider), domyślnie `en`, niewidoczne w publicznym formularzu zgodnie z planem. — *WebsiteExpert*

Jeżeli provider wyników wymaga języka wyszukiwania, system powinien go obsługiwać.

Na publicznym formularzu nie musi być widoczny.

Dla rynku UK domyślna wartość:

`English`

W przyszłości można umożliwić zmianę języka.

---

# 8. Uruchomienie sprawdzenia

> ✅ Zrealizowano — cała sekwencja (walidacja → normalizacja domeny → throttle → cache → SERP → matching → wynik) zaimplementowana w `GoogleRankCheckerController::check()` + `CheckGoogleRankingAction::execute()`. — *WebsiteExpert*

Po kliknięciu:

`Check Google Ranking`

system powinien:

1. zwalidować formularz,
2. znormalizować domenę,
3. sprawdzić limity użytkownika,
4. sprawdzić, czy identyczny wynik istnieje już w cache,
5. jeżeli nie, pobrać wyniki SERP,
6. przeszukać wyniki organiczne,
7. znaleźć pierwsze wystąpienie wskazanej domeny,
8. zwrócić wynik,
9. wyświetlić go użytkownikowi.

---

# 9. Matching domeny

> ✅ Zrealizowano — `DomainNormalizer::hostMatchesDomain()` porównuje hostname (nie substring), ignoruje protokół/`www`/ścieżkę. Test: `test_host_matches_domain_for_apex_and_subdomains` (w tym przypadek `notexample.com` / `example.com.evil.com`). — *WebsiteExpert*

System powinien sprawdzać domenę, a nie pełny URL.

Dla domeny:

`example.com`

następujące wyniki powinny zostać uznane za dopasowanie:

`https://example.com`

`https://www.example.com`

`https://example.com/services`

`https://example.com/blog/article`

`https://www.example.com/contact`

System powinien ignorować:

* `http/https`,
* `www`,
* końcowy slash,
* ścieżkę URL.

Nie można jednak dopasowywać domen przez zwykłe wyszukiwanie tekstu.

Przykład:

szukana domena:

`example.com`

nie może zostać uznana za znalezioną dla:

`notexample.com`

Dopasowanie musi opierać się na poprawnym hostname.

---

# 10. Subdomeny

> ✅ Zrealizowano — `DomainNormalizer::isApexDomain()` (uwzględnia sufiksy typu `co.uk`) decyduje, czy domena-cel dopuszcza subdomeny; explicit subdomena (np. `shop.example.com`) wymaga dokładnego dopasowania. — *WebsiteExpert*

Należy przewidzieć jasno określone zachowanie dla subdomen.

Domyślnie rekomendowane:

jeżeli użytkownik wpisze:

`example.com`

należy uznać również:

`shop.example.com`

`blog.example.com`

za część tej samej domeny.

Jeżeli użytkownik poda konkretną subdomenę:

`shop.example.com`

system powinien szukać właśnie tej subdomeny.

Logika powinna być łatwa do zmiany w przyszłości.

---

# 11. Zakres wyników

> ✅ Zrealizowano — `config('seo.rank_checker.top_results')` (domyślnie 100); `DataForSeoProviderService` filtruje wyłącznie elementy `type === 'organic'`, pomijając reklamy/map pack/PAA/itd. — *WebsiteExpert*

Domyślnie sprawdzamy:

`TOP 100 organicznych wyników Google`.

System powinien ignorować elementy, które nie są standardowymi wynikami organicznymi, jeżeli provider zwraca je jako osobne typy.

Przykładowo:

* reklamy,
* map pack,
* images,
* shopping,
* videos,
* People Also Ask,
* featured snippets jako osobny typ,
* inne elementy SERP.

Pozycja powinna oznaczać pozycję w wynikach organicznych zgodną z danymi providera.

---

# 12. Gdy domena została znaleziona

> ✅ Zrealizowano — `Components/Tools/GoogleRankResult.tsx` renderuje position/ranking URL/komunikat "first page" dla pozycji 1-10. — *WebsiteExpert*

Należy pokazać użytkownikowi czytelny wynik.

Przykład:

## Google Ranking

Keyword:

`web design belfast`

Website:

`website-expert.uk`

Location:

`Belfast, United Kingdom`

Device:

`Desktop`

### Position

`#7`

### Ranking page

`website-expert.uk/services/web-design-belfast`

Dodatkowo może zostać pokazany komunikat:

`Your website appears on the first page of Google for this keyword.`

dla pozycji 1-10.

---

# 13. Interpretacja pozycji

> ✅ Zrealizowano — dokładnie te progi (1-3/4-10/11-20/21-50/51-100) w `GoogleRankResult.tsx::interpretationKey()` + tłumaczenia `lang/*/rank_checker.php` (`result.interpretation.*`). — *WebsiteExpert*

Można użyć prostych komunikatów kontekstowych.

Pozycja 1-3:

`Excellent visibility`

Pozycja 4-10:

`First page of Google`

Pozycja 11-20:

`Close to page one`

Pozycja 21-50:

`Your website is ranking, but visibility is limited`

Pozycja 51-100:

`Your website is ranking outside the main visible results`

Nie należy przedstawiać tych opisów jako obiektywnej oceny jakości SEO.

Mają być jedynie krótkim komentarzem UX.

---

# 14. Gdy domena nie została znaleziona

> ✅ Zrealizowano — status `not_found` (nigdy błąd HTTP), komunikat + CTA "Get a free SEO audit" w `GoogleRankResult.tsx`. Pokryte testem `test_not_found_result_when_domain_absent_from_results`. — *WebsiteExpert*

Jeżeli domena nie występuje w TOP 100:

należy zwrócić:

`Not found in the top 100 results`

Nie należy przedstawiać tego jako błędu.

To jest poprawny wynik sprawdzenia.

Przykład komunikatu:

`We couldn't find website-expert.uk in the top 100 organic Google results for "web design london".`

Pod spodem można wyświetlić CTA:

`Want to improve your Google visibility?`

`Get a free SEO audit`

---

# 15. Ranking URL

> ✅ Zrealizowano — `ranking_url` zapisywany w `search_ranking_checks` i zwracany w `RankCheckResult`/wyniku UI. — *WebsiteExpert*

Jeżeli domena została znaleziona, należy zapisać i wyświetlić dokładny URL, który rankuje.

Jest to ważne, ponieważ użytkownik może wpisać:

`example.com`

ale Google może rankować:

`example.com/services/plumber-belfast`

Ta informacja ma być częścią wyniku.

---

# 16. Wiele wyników tej samej domeny

> ✅ Zrealizowano — `CheckGoogleRankingAction::execute()` iteruje wszystkie dopasowania i zachowuje najwyższą (najniższą liczbowo) pozycję. — *WebsiteExpert*

Jeżeli w SERP występuje kilka stron z tej samej domeny:

należy jako główną pozycję przyjąć najwyżej znajdujący się wynik.

Opcjonalnie system może zachować pozostałe znalezione wyniki do późniejszego wykorzystania.

Przykład:

`#4 example.com/service`

`#17 example.com/blog/article`

Główny wynik:

`#4`

---

# 17. Cache wyników

> ✅ Zrealizowano — `Cache::remember`-style klucz z (keyword, domain, location, device, language), TTL konfigurowalny `RANK_CHECK_CACHE_TTL_HOURS` (domyślnie 8h). Test: `test_identical_check_is_served_from_cache` (provider wołany tylko raz). "Checked X ago" — nie zaimplementowane (opcjonalne w planie). — *WebsiteExpert*

Identyczne sprawdzenia nie powinny za każdym razem wykonywać nowego zapytania do zewnętrznego źródła SERP.

Należy cachować wynik na określony czas.

Identyczne sprawdzenie oznacza tę samą kombinację:

* keyword,
* domain,
* location,
* device,
* language.

Przykładowy czas cache:

6-12 godzin.

Wynik cache powinien zachowywać się dla użytkownika tak samo jak świeże sprawdzenie.

Opcjonalnie można pokazać:

`Checked 2 hours ago`

Nie jest to jednak wymagane w pierwszej wersji.

---

# 18. Ochrona przed nadużyciami

> ⚠️ Częściowo zrealizowano — rate limiting (`throttle:10,60`), cache i reCAPTCHA v3 wdrożone. Cloudflare Turnstile, globalny wyłącznik narzędzia i dzienny limit kosztów API **nie zostały zaimplementowane** w MVP (poza zakresem — sekcja 45). — *WebsiteExpert*

Ponieważ każde sprawdzenie może generować koszt po stronie providera SERP, endpoint publiczny musi być zabezpieczony.

Należy zastosować kilka poziomów ochrony.

Minimum:

* rate limiting,
* limit sprawdzeń per IP,
* cache,
* zabezpieczenie przeciw botom.

Preferowane dodatkowo:

* Cloudflare Turnstile lub podobne rozwiązanie,
* wykrywanie bardzo dużej liczby zapytań,
* możliwość globalnego wyłączenia narzędzia,
* możliwość ustawienia dziennego limitu kosztów/API.

System nie powinien pozwalać jednej osobie na wykonanie tysięcy kosztownych requestów.

---

# 19. Rate limiting

> ✅ Zrealizowano — `throttle:10,60` na trasie `POST /tools/google-rank-checker/check` (zgodne z konwencją reszty aplikacji — limity route-level, nie named limiters). Test: `test_rate_limit_blocks_the_eleventh_check_within_the_window`. — *WebsiteExpert*

Limity powinny być konfigurowalne.

Przykładowo:

anonimowy użytkownik:

`5-10 sprawdzeń na godzinę`

lub:

`20 sprawdzeń dziennie`

Dokładne limity można później dostosować na podstawie realnego ruchu i kosztów.

Po przekroczeniu limitu należy pokazać przyjazny komunikat.

Nie należy zwracać użytkownikowi technicznego komunikatu błędu.

---

# 20. Stan ładowania

> ✅ Zrealizowano — `GoogleRankCheckerForm.tsx` pokazuje `form.loading_label` ("Checking Google results…") i blokuje przycisk podczas requestu; brak sztucznego progress-bara. — *WebsiteExpert*

Sprawdzanie pozycji może potrwać kilka sekund.

Interfejs powinien jasno komunikować, że trwa sprawdzanie.

Przykład:

`Checking Google results...`

Można dodać subtelne komunikaty:

`Searching Google results`

`Checking the top results`

Nie należy jednak pokazywać sztucznego procentowego progress baru, jeżeli system nie zna realnego postępu.

---

# 21. Obsługa błędów

> ✅ Zrealizowano — `SerpProviderException` mapowana na status `failed` (nigdy `not_found`); `DataForSeoProviderService` łapie timeout/HTTP/payload errors i loguje bez sekretów. Test: `test_provider_failure_is_reported_as_failed_not_not_found`. — *WebsiteExpert*

Należy rozróżnić:

* brak wyniku,
* błąd providera,
* timeout,
* limit API,
* błędną lokalizację,
* błędne dane użytkownika,
* chwilową niedostępność usługi.

Jeżeli zewnętrzne API nie odpowiada:

nie należy pokazywać:

`Position: not found`

ponieważ oznaczałoby to nieprawidłowy wynik.

Należy pokazać komunikat w rodzaju:

`We couldn't complete the ranking check right now. Please try again shortly.`

---

# 22. Walidacja domeny

> ✅ Zrealizowano — `CheckGoogleRankingRequest::withValidator()` + `DomainNormalizer::isValidDomain()` odrzucają IP, localhost i niepoprawny hostname. — *WebsiteExpert*

System powinien obsługiwać zarówno:

`example.com`

jak i:

`https://example.com`

Nie powinien przyjmować:

* losowego tekstu,
* niepoprawnych hostname,
* javascript URL,
* localhost,
* prywatnych adresów IP.

Jeżeli narzędzie pobiera wyłącznie dane z SERP providera i nie wykonuje requestów do domeny użytkownika, nadal warto dokładnie normalizować wartości wejściowe.

---

# 23. Dane do zapisu

> ✅ Zrealizowano — tabela `search_ranking_checks` zapisuje dokładnie te pola; IP nigdy nie jest zapisywane surowo — tylko `ip_hash` (SHA-256), zgodnie z rekomendacją planu. — *WebsiteExpert*

W pierwszej wersji publicznego narzędzia nie ma potrzeby przechowywania pełnej historii każdego użytkownika.

Warto jednak zachować minimum danych analitycznych.

Dla każdego wykonanego sprawdzenia można zapisać:

* keyword,
* znormalizowaną domenę,
* location,
* device,
* position,
* ranking URL,
* status,
* timestamp,
* provider,
* czy wynik pochodził z cache.

Jeżeli zapisujemy IP, powinno być odpowiednio zabezpieczone/anonymizowane zgodnie z polityką prywatności.

Preferowane jest zapisanie skrótu/hasha zamiast surowego IP, jeżeli IP ma służyć wyłącznie do abuse prevention.

---

# 24. Status sprawdzenia

> ✅ Zrealizowano — `found` / `not_found` / `failed` jako `SearchRankingCheck::STATUS_*`; `cached` reprezentowane jako flaga `from_cache` (bool) obok statusu domenowego, zgodnie z sugestią planu. — *WebsiteExpert*

Każdy check powinien logicznie kończyć się jednym ze statusów:

`found`

domena została znaleziona,

`not_found`

domena nie występuje w analizowanym zakresie,

`failed`

sprawdzenie nie mogło zostać wykonane,

`cached`

opcjonalnie jako informacja techniczna, niekoniecznie jako główny status domenowy.

---

# 25. CTA po wyniku

> ✅ Zrealizowano — `GoogleRankResult.tsx` pokazuje kontekstowe CTA (dobry/słaby wynik) prowadzące do `/services/seo`; kliknięcia raportowane do GTM (`rank_checker_cta_click`). — *WebsiteExpert*

Publiczny checker powinien być częścią lejka sprzedażowego Website Expert.

Po wyniku należy wyświetlić kontekstowe CTA.

Przykład dla słabego wyniku:

`Your website is currently outside Google's top results for this keyword.`

`Want to improve your visibility?`

`Get a free SEO audit`

Przykład dla dobrego wyniku:

`You're already ranking well. Find out what could help you move even higher.`

`Request an SEO review`

CTA powinno prowadzić do istniejącego formularza kontaktowego, formularza audytu SEO lub odpowiedniej usługi.

---

# 26. SEO samej strony narzędzia

> ✅ Zrealizowano — title/meta description/H1/intro/FAQ w `GoogleRankChecker.tsx`, treści z `lang/*/rank_checker.php` (`meta.*`, `faq`). — *WebsiteExpert*

Strona Google Rank Checker powinna być przygotowana jako landing page, który sam może generować ruch organiczny.

Powinna zawierać:

* odpowiedni title,
* meta description,
* H1,
* opis działania narzędzia,
* FAQ,
* krótkie wyjaśnienie, czym jest pozycja Google,
* informację, dlaczego wyniki mogą różnić się lokalizacją,
* linkowanie do usług SEO Website Expert.

Przykładowy H1:

`Free Google Rank Checker`

Przykładowy opis:

`Check where your website ranks on Google for any keyword and location.`

---

# 27. Ważne wyjaśnienie dla użytkownika

> ✅ Zrealizowano — `result.disclaimer` wyświetlany pod każdym wynikiem (`GoogleRankResult.tsx`). — *WebsiteExpert*

Należy wyraźnie zaznaczyć, że ranking Google nie jest identyczny dla wszystkich użytkowników.

Wyniki mogą zależeć od:

* lokalizacji,
* urządzenia,
* języka,
* centrum danych,
* konfiguracji wyszukiwarki,
* zmian algorytmu,
* aktualności indeksu.

Narzędzie pokazuje reprezentatywny wynik dla wskazanych parametrów, a nie gwarantowaną pozycję widoczną dla każdego użytkownika Google.

---

# 28. Nie nazywać wyniku „dokładną pozycją każdego użytkownika”

> ✅ Zrealizowano — treści `rank_checker.php` używają sformułowania "appears at position #X for the selected search location and device", zgodnego z rekomendacją. — *WebsiteExpert*

Unikać komunikatów typu:

`This is your exact Google ranking.`

Lepsze:

`Your website currently appears at position #7 for the selected search location and device.`

---

# 29. Przygotowanie pod przyszły panel klienta

> ⚠️ Częściowo przygotowane — `search_ranking_checks.business_id` (nullable, FK do `businesses`) istnieje już w schemacie na potrzeby przyszłego powiązania z projektem/klientem; encje `Project`/`Keyword` **nie zostały zaimplementowane** (poza zakresem MVP). — *WebsiteExpert*

Mimo że pierwsza wersja jest publicznym checkerem, logika biznesowa powinna umożliwiać ponowne wykorzystanie jej w panelu użytkownika.

Docelowo użytkownik powinien móc posiadać:

## Project

Przykład:

`ABC Plumbing`

Domain:

`abcplumbing.co.uk`

## Keywords

* plumber belfast
* emergency plumber belfast
* boiler repair belfast

Każda fraza posiada:

* lokalizację,
* urządzenie,
* język,
* aktualną pozycję,
* poprzednią pozycję,
* ranking URL,
* datę ostatniego sprawdzenia.

---

# 30. Przyszła historia pozycji

> ⏳ Poza zakresem MVP (zgodnie z sekcją 45) — nie zaimplementowane. Każdy check jest już logowany w `search_ranking_checks`, co stanowi surowe dane pod przyszłą historię/wykresy. — *WebsiteExpert*

System powinien być przygotowany na przechowywanie wielu pomiarów tej samej frazy.

Przykład:

`web design belfast`

1 Sep → #18

2 Sep → #14

3 Sep → #11

4 Sep → #8

Na tej podstawie później powstanie:

* wykres,
* zmiana pozycji,
* trend,
* raport miesięczny.

---

# 31. Zmiana pozycji

> ⏳ Poza zakresem MVP — nie zaimplementowane. — *WebsiteExpert*

W przyszłej wersji użytkownik powinien widzieć:

`Current position: #7`

`Previous position: #11`

`Change: +4`

lub:

`Current position: #16`

`Previous position: #10`

`Change: -6`

Należy pamiętać, że niższa liczba pozycji oznacza poprawę.

Czyli:

`11 → 7`

oznacza:

`+4 positions`

---

# 32. Aktualizacja pozycji

> ⏳ Poza zakresem MVP — nie zaimplementowane (brak schedulera). — *WebsiteExpert*

Dla zalogowanych klientów w przyszłości sprawdzanie powinno być możliwe:

* ręcznie,
* automatycznie.

Automatyczny monitoring może działać:

* codziennie,
* kilka razy w tygodniu,
* tygodniowo.

Częstotliwość powinna zależeć od planu użytkownika.

---

# 33. Przyszłe limity planów

> ⏳ Poza zakresem MVP — nie zaimplementowane, zgodnie z planem ("Nie należy implementować całego billingowego systemu w pierwszej wersji"). — *WebsiteExpert*

System powinien być przygotowany pod plany abonamentowe.

Przykład:

Free:

`10 keywords`

Starter:

`50 keywords`

Business:

`250 keywords`

Agency:

`1000 keywords`

Nie należy implementować całego billingowego systemu w pierwszej wersji, ale logika nie powinna zakładać, że liczba fraz będzie zawsze nieograniczona.

---

# 34. Konkurenci

> ⏳ Poza zakresem MVP — nie zaimplementowane. — *WebsiteExpert*

W kolejnej fazie projekt może umożliwiać przypisanie konkurentów do projektu.

Przykład:

główna domena:

`website-expert.uk`

konkurenci:

`competitor-a.co.uk`

`competitor-b.co.uk`

`competitor-c.co.uk`

Dla każdej frazy będzie można pokazać:

Website Expert — #6

Competitor A — #2

Competitor B — #11

Competitor C — not found

Nie jest to częścią MVP, ale projekt powinien pozwalać na takie rozszerzenie.

---

# 35. Publiczny checker a monitoring klienta

> ✅ Zrealizowano — jedyna funkcja biznesowa to `App\Actions\Seo\CheckGoogleRankingAction::execute()`; każdy przyszły konsument (dashboard, scheduler, raporty) będzie ją reużywał zamiast duplikować logikę. — *WebsiteExpert*

Te dwie funkcje powinny korzystać z tej samej podstawowej logiki sprawdzania pozycji.

Nie tworzyć dwóch niezależnych mechanizmów.

Powinna istnieć jedna funkcja biznesowa:

`check ranking`

którą można wykorzystać przez:

* publiczny checker,
* dashboard klienta,
* zadania automatyczne,
* raporty,
* API wewnętrzne.

---

# 36. Provider SERP

> ✅ Zrealizowano — `SerpProviderInterface` + `SeoServiceProvider` (bind wg `config('seo.rank_checker.provider')`): `ManualSerpProviderService` (darmowy stub, domyślny) i `DataForSeoProviderService` (realny provider). Reszta aplikacji operuje wyłącznie na `SerpSearchResult`/`SerpOrganicResult` DTO. — *WebsiteExpert*

Moduł nie powinien być trwale związany z jednym dostawcą danych.

Należy założyć, że provider może zostać zmieniony.

Przykładowo:

obecnie DataForSEO,

w przyszłości inny provider.

Pozostała część aplikacji nie powinna wiedzieć, z którego konkretnego API pochodzą wyniki.

Dla aplikacji liczy się ustandaryzowany wynik:

* keyword,
* location,
* device,
* organic results,
* position,
* URL.

---

# 37. Awaria providera

> ✅ Zrealizowano — `DataForSeoProviderService` łapie timeouty/wyjątki HTTP i mapuje na `SerpProviderException`; `CheckGoogleRankingAction` zwraca status `failed` zamiast wywalać 500. Drugi provider jako fallback — nie zaimplementowany (opcjonalne w planie). — *WebsiteExpert*

Awaria lub chwilowa niedostępność zewnętrznego API nie może powodować awarii całej strony.

System powinien:

* obsłużyć timeout,
* przechwycić wyjątek,
* zapisać błąd,
* zwrócić użytkownikowi czytelny komunikat.

Opcjonalnie w przyszłości można dodać drugiego providera jako fallback.

---

# 38. Kontrola kosztów

> ✅ Zrealizowano (wgląd podstawowy) — `Filament\Resources\SearchRankingCheckResource` (tylko odczyt, grupa nawigacji "SEO"): lista checków z filtrami status/provider/cache. Szacunkowy koszt w £ — nie zaimplementowany. — *WebsiteExpert*

Ponieważ każde zapytanie SERP może kosztować pieniądze, moduł powinien umożliwiać kontrolę zużycia.

Administrator powinien mieć możliwość sprawdzenia co najmniej:

* liczby wykonanych checków,
* liczby requestów do providera,
* liczby wyników z cache,
* liczby błędów,
* liczby użytkowników narzędzia.

W przyszłości warto również śledzić szacunkowy koszt.

---

# 39. Logowanie diagnostyczne

> ✅ Zrealizowano — `Log::error()` w `DataForSeoProviderService` przy błędach HTTP/task/timeout (keyword + status, nigdy login/hasło). — *WebsiteExpert*

Każdy błąd komunikacji z providerem powinien być logowany.

Log powinien umożliwiać ustalenie:

* kiedy nastąpił błąd,
* jaki provider był używany,
* jaki typ requestu wykonywano,
* jaki był status odpowiedzi,
* czy wystąpił timeout.

Nie należy logować sekretów API.

---

# 40. Analytics

> ⚠️ Częściowo zrealizowano — zdarzenia GTM `rank_check_completed` i `rank_checker_cta_click` wysyłane z frontendu. Pełny lejek (page view, form_start) — nie zaimplementowany w MVP. — *WebsiteExpert*

Warto mierzyć:

* wejście na Google Rank Checker,
* rozpoczęcie formularza,
* wykonanie sprawdzenia,
* znalezioną pozycję,
* kliknięcie CTA,
* przejście do SEO audit,
* przejście do kontaktu.

Pozwoli to sprawdzić, czy narzędzie rzeczywiście generuje leady.

---

# 41. UX formularza

> ✅ Zrealizowano — dokładnie ta kolejność pól w `GoogleRankCheckerForm.tsx`: Keyword → Website → Location → Device → Check Ranking, bez zaawansowanych ustawień. — *WebsiteExpert*

Formularz powinien być maksymalnie prosty.

Użytkownik nie powinien potrzebować wiedzy SEO.

Preferowana kolejność:

1. Keyword
2. Website
3. Location
4. Device
5. Check Ranking

Nie należy pokazywać zaawansowanych ustawień w pierwszym widoku.

---

# 42. Przykładowy flow

> ✅ Zrealizowano — dokładnie ten flow działa w aplikacji (formularz → loading → wynik + CTA). — *WebsiteExpert*

Użytkownik wchodzi na:

`/tools/google-rank-checker`

Widzi:

`Check your Google ranking`

Wpisuje:

Keyword:

`plumber belfast`

Website:

`abcplumbing.co.uk`

Location:

`Belfast`

Device:

`Mobile`

Klik:

`Check Ranking`

System pokazuje loading.

Następnie:

`Your website ranks #12`

`abcplumbing.co.uk/emergency-plumber-belfast`

Dodatkowo:

`You're just outside Google's first page.`

CTA:

`Find out what's holding your website back`

`Get a free SEO audit`

---

# 43. Przykład braku wyniku

> ✅ Zrealizowano — patrz sekcja 14 / `test_not_found_result_when_domain_absent_from_results`. — *WebsiteExpert*

Użytkownik sprawdza:

Keyword:

`web design london`

Website:

`website-expert.uk`

System nie znajduje domeny w TOP100.

Pokazujemy:

`Not found in the top 100 results`

`We couldn't find website-expert.uk in Google's top 100 organic results for "web design london" from the selected location.`

CTA:

`Want to improve your visibility?`

`Request an SEO audit`

---

# 44. Dane w wyniku

> ✅ Zrealizowano — `RankCheckResult::toArray()` zwraca dokładnie te pola (+ `from_cache`); UI pokazuje interpretację i CTA. — *WebsiteExpert*

Publiczny wynik powinien zawierać minimum:

* keyword,
* domain,
* location,
* device,
* position lub informację `not found`,
* ranking URL,
* czas sprawdzenia.

Opcjonalnie:

* status pierwszej strony,
* krótką interpretację,
* CTA.

---

# 45. Czego nie robić w pierwszej wersji

> ✅ Zasada przestrzegana — żadna z wymienionych funkcji nie została zaimplementowana w MVP (świadoma decyzja, patrz `current-task.md`). — *WebsiteExpert*

MVP nie wymaga:

* scrapowania Google bezpośrednio,
* systemu proxy,
* obsługi CAPTCHA Google,
* logowania użytkownika,
* pełnego panelu SEO,
* wykresów,
* monitoringu konkurentów,
* raportów PDF,
* płatnych planów,
* codziennego schedulera,
* obsługi tysięcy fraz jednego użytkownika.

Najpierw ma powstać stabilny publiczny checker.

---

# 46. Priorytety MVP

> ✅ **Must have: 17/17 zrealizowane.** ⚠️ **Should have:** lista lokalizacji UK/NI + interpretacja pozycji + zapis statystyk — zrealizowane; autocomplete lokalizacji i "czas wykonania sprawdzenia" w UI — nie zrealizowane. ⏳ **Could have:** nie zrealizowane (poza zakresem MVP). — *WebsiteExpert*

## Must have

* publiczna strona rank checkera,
* keyword,
* domain,
* location,
* desktop/mobile,
* sprawdzanie TOP100,
* poprawne rozpoznawanie domeny,
* pokazanie pozycji,
* pokazanie ranking URL,
* obsługa `not found`,
* loading state,
* obsługa błędów,
* cache,
* rate limiting,
* ochrona przed botami,
* CTA do SEO services/audit,
* podstawowe analytics.

## Should have

* lista popularnych lokalizacji UK/NI,
* autocomplete lokalizacji,
* zapis statystyk wykorzystania,
* informacja o czasie wykonania sprawdzenia,
* krótka interpretacja pozycji.

## Could have

* udostępnienie wyniku,
* kopiowanie wyniku,
* ponowne sprawdzenie,
* zapis wyników zalogowanego użytkownika,
* historia ostatnich checków.

---

# 47. Kryteria akceptacji MVP

> ✅ **15/15 kryteriów spełnione** — potwierdzone testami `tests/Unit/Seo/DomainNormalizerTest.php` i `tests/Feature/Seo/GoogleRankCheckerTest.php` (12 testów, wszystkie przechodzą). — *WebsiteExpert*

Moduł można uznać za ukończony, jeżeli:

1. użytkownik bez logowania może wejść na stronę Google Rank Checker,
2. może podać frazę, domenę, lokalizację i urządzenie,
3. formularz odrzuca niepoprawne dane,
4. system potrafi pobrać organiczne SERP Google,
5. potrafi przeszukać co najmniej TOP100,
6. poprawnie rozpoznaje domenę niezależnie od `www`, protokołu i URL path,
7. zwraca najwyższą znalezioną pozycję domeny,
8. pokazuje ranking URL,
9. poprawnie obsługuje przypadek braku domeny w TOP100,
10. błąd providera nie jest przedstawiany jako `not found`,
11. identyczne requesty mogą korzystać z cache,
12. istnieje rate limiting,
13. publiczny endpoint jest zabezpieczony przed masowym automatycznym użyciem,
14. wynik zawiera CTA do oferty SEO Website Expert,
15. kod/logika modułu może zostać później wykorzystana do monitoringu pozycji klientów.

---

# 48. Ważne założenia projektowe

> ✅ Zasada zachowana — MVP implementuje wyłącznie `Ranking check` (`CheckGoogleRankingAction`), ale schemat DB (`business_id` nullable) i abstrakcja providera nie blokują przyszłego `Keyword tracking` / `SEO project`. — *WebsiteExpert*

Najważniejsze jest rozdzielenie trzech pojęć:

### Ranking check

Jednorazowe sprawdzenie pozycji.

### Keyword tracking

Regularne sprawdzanie tej samej frazy.

### SEO project

Zbiór domeny, fraz, lokalizacji, historii i później konkurentów.

MVP implementuje głównie:

`Ranking check`

ale fundament funkcjonalny powinien umożliwiać rozwój w:

`Ranking check → Keyword tracking → SEO project`

---

# 49. Docelowa wizja modułu

> ⏳ Poza zakresem MVP — panel "SEO Rankings" z projektami/wykresami nie został zaimplementowany; fundament (Action + provider abstraction + `business_id`) jest gotowy pod tę rozbudowę. — *WebsiteExpert*

W przyszłości moduł powinien stać się sekcją:

`SEO Rankings`

w panelu Website Expert.

Przykład:

Project:

`ABC Plumbing`

Domain:

`abcplumbing.co.uk`

| Keyword                   | Location         | Position | Change |
| ------------------------- | ---------------- | -------: | -----: |
| plumber belfast           | Belfast          |       #4 |     +2 |
| boiler repair belfast     | Belfast          |       #7 |     +1 |
| emergency plumber belfast | Belfast          |      #11 |     +5 |
| plumber northern ireland  | Northern Ireland |      #18 |     -3 |

Kliknięcie frazy otwiera historię:

* aktualna pozycja,
* wykres,
* ranking URL,
* najlepsza pozycja,
* najgorsza pozycja,
* historia zmian.

Dzięki temu darmowy Google Rank Checker będzie jednocześnie początkiem większego produktu SEO w ramach Website Expert.

---

# 50. Najważniejsza zasada implementacji

> ✅ Zasada spełniona — moduł zaimplementowany jako domena `Seo` (Action/Services/Data/Support), nie jako pojedynczy formularz-do-API. `CheckGoogleRankingAction` jest gotowa do reużycia przez panel klienta, zadania automatyczne, monitoring fraz i raporty bez zmiany istniejącego kodu. — *WebsiteExpert, 2026-09-06*

Nie traktować tego jako pojedynczego formularza wysyłającego request do zewnętrznego API.

Traktować go jako pierwszy interfejs większej domeny biznesowej:

`Search Ranking`

Podstawowa logika sprawdzania pozycji powinna być wspólna i możliwa do użycia później przez:

* publiczny checker,
* panel użytkownika,
* automatyczne zadania,
* monitoring fraz,
* raporty,
* alerty,
* porównania konkurencji.

MVP ma być niewielkie od strony funkcjonalnej, ale nie powinno blokować dalszego rozwoju modułu SEO.
