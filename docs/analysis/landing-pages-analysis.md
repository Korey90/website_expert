# Analiza systemu landing pages i widokow stron
> Data: 2026-03-31

## 1. Stan obecny

Projekt ma obecnie trzy rozne mechanizmy obslugi stron publicznych:

1. Strona marketingowa glowna oparta o Inertia + React i zasilana dynamicznie z `SiteSection`.
2. Strony CMS / legal pages oparte o Inertia + React i zasilane z modelu `Page`.
3. Tenantowe landing pages oparte o Inertia + React i zasilane z modeli `LandingPage` oraz `LandingPageSection`.

W repozytorium istnieje tez oddzielny folder `szablon/`, ale jest to prototyp HTML + React CDN, a nie aktywna warstwa runtime aplikacji.

## 2. Folder szablon

### 2.1 Zawartosc

Folder `szablon/` zawiera:

- `layout.html` — statyczny prototyp calej strony marketingowej oparty o Tailwind CDN.
- `components/CostCalculator.jsx` — prototyp kalkulatora kosztow uruchamianego przez React z CDN/Babel.
- `components/Testimonials.jsx` — prototyp karuzeli testimonials uruchamianej przez React z CDN/Babel.
- `css/` — zasoby prototypowe.

### 2.2 Znaczenie architektoniczne

Ten folder nie jest wpiety do routingu Laravel ani do bundla Vite. Nie ma polaczenia z Inertia, modelami, bazą danych ani tenantami.

W praktyce `szablon/` jest zbiorem referencji wizualnych i prototypow UX, ktore mozna wykorzystac jako inspiracje dla generatora landing pages, ale nie jest to aktualny silnik renderowania.

### 2.3 Co mozna wykorzystac

- Uklad i estetyke `layout.html` jako referencje design systemu.
- Logike krokowego kalkulatora z `szablon/components/CostCalculator.jsx` jako material do dalszych promptow / generatora sekcji kalkulatorowej.
- Mechanike testimonials z `szablon/components/Testimonials.jsx` jako wzorzec tresci i interakcji.

## 3. Sposob renderowania widokow

### 3.1 Runtime publiczny

Aktualny runtime stron publicznych dziala przez Inertia + React:

- `/` -> `WelcomeController` -> `resources/js/Pages/Welcome.jsx`
- `/p/{slug}` -> `PageController` -> `resources/js/Pages/CmsPage.jsx`
- `/lp/{slug}` -> `PublicLandingPageController` -> `resources/js/Pages/LandingPage/Show.jsx`

### 3.2 Blade vs Inertia vs React

- Dla landing pages i stron marketingowych nie ma renderowania Blade.
- Blade jest uzywany w innych obszarach projektu, glownie dla PDF/reportow i widokow pomocniczych, ale nie dla publicznych landing pages.
- Warstwa publiczna jest spójna technologicznie: Laravel + Inertia + React.

### 3.3 Layout publiczny

Wspolny layout publiczny to `resources/js/Layouts/MarketingLayout.jsx`.

Zawiera:

- `Navbar`
- `Footer`
- `CookieBanner`
- `ConsentContext`
- inicjalizacje `Meta Pixel` przez `useMetaPixel`

To oznacza, ze klasyczne strony marketingowe i CMS sa osadzone w jednym wspolnym kontenerze UX, ale landing pages pod `/lp/{slug}` korzystaja z osobnej strony `LandingPage/Show.jsx` i nie przechodza przez `MarketingLayout`.

## 4. Istniejace komponenty UI

### 4.1 Globalny frontend marketingowy

Istnieje rozbudowany zestaw komponentow w `resources/js/Components/Marketing/`, m.in.:

- `Hero`
- `About`
- `CtaBanner`
- `TrustStrip`
- `Services`
- `Process`
- `Portfolio`
- `CostCalculatorV2`
- `Faq`
- `Contact`
- `Navbar`
- `Footer`
- `CookieBanner`

To jest dojrzały zestaw komponentow pod strone glowna i dynamiczne sekcje marketingowe sterowane z bazy przez `SiteSection`.

### 4.2 Komponenty publicznych landing pages

Istnieje osobny zestaw komponentow w `resources/js/Components/LandingPage/PublicSection/`:

- `HeroSection`
- `FeaturesSection`
- `TestimonialsSection`
- `CtaSection`
- `FormSection`
- `FaqSection`
- `TextSection`
- `VideoSection`

`resources/js/Pages/LandingPage/Show.jsx` posiada twardo zdefiniowany `SECTION_MAP`, ktory mapuje `type` sekcji na konkretny komponent React.

### 4.3 Komponenty panelu do edycji landing pages

Istnieje panel zarzadzania landing pages oparty o Inertia:

- `resources/js/Pages/LandingPages/Create.jsx`
- `resources/js/Pages/LandingPages/Edit.jsx`
- `resources/js/Components/LandingPage/SectionsList.jsx`
- `resources/js/Components/LandingPage/StatusBadge.jsx`
- `resources/js/Components/LandingPage/TemplateCard.jsx`

Edytor pozwala:

- wybrac szablon startowy,
- ustawic slug i jezyk,
- dodawac sekcje,
- edytowac podstawowe pola sekcji inline,
- przestawiac kolejnosc sekcji,
- publikowac i zdejmowac publikacje.

## 5. Czy istnieje system dynamicznych stron

### 5.1 Tak, ale w trzech niezaleznych warstwach

#### A. Dynamiczna strona glowna

`WelcomeController` sklada strone glowna z rekordow `SiteSection` o znanych kluczach:

- hero
- about
- cta_banner
- trust_strip
- testimonials
- services
- process
- portfolio
- faq
- cost_calculator_v2
- navbar
- contact
- footer

Jest to system sekcji sterowanych z bazy danych, ale tylko dla glownej strony marketingowej.

#### B. Dynamiczne strony CMS

`PageController` renderuje rekordy `Page` pod `/p/{slug}` oraz wybrane clean URLs dla legal pages. To jest system dynamicznych stron oparty o tresc HTML i pola translatable.

#### C. Dynamiczne landing pages

`LandingPage` + `LandingPageSection` tworza osobny system dynamicznych landing pages per business. To jest najblizszy odpowiednik docelowego generatora landing pages.

### 5.2 Ograniczenia obecnego systemu dynamicznego

- Nie ma jednej wspolnej abstrakcji dla wszystkich typow stron.
- `SiteSection` i `LandingPageSection` sa odrebnymi systemami, bez wspolnego buildera.
- Nie ma silnika generacji tresci ani wersjonowania sekcji.
- Nie ma dynamicznego doboru layoutu poza `template_key` i twarda lista sekcji startowych.

## 6. Routing publicznych stron

### 6.1 Strony marketingowe i CMS

W `routes/web.php` istnieja publiczne trasy:

- `/` -> `WelcomeController`
- `/kalkulator` -> `KalkulatorController`
- `/p/{slug}` -> `PageController@show`
- `/{slug}` dla wybranych legal pages (`privacy-policy`, `terms-and-conditions`, `cookies`, `accessibility`)
- `/lang/{locale}` do zmiany jezyka
- `/contact` do obslugi formularza kontaktowego
- `/calculator-lead` do zapisu leada z kalkulatora

### 6.2 Publiczne landing pages

Istnieje wydzielony publiczny routing landing pages:

- `GET /lp/{slug}` -> `PublicLandingPageController@show`
- `POST /lp/{slug}/submit` -> `PublicLandingPageController@submit`

Submit ma rate limit `throttle:3,60`.

### 6.3 Wniosek

Routing jest czytelny i gotowy pod generator landing pages, ale opiera sie wyłącznie na slugach bez publicznego kontekstu business/domain.

## 7. Powiazanie z tenantami

### 7.1 Co istnieje

`LandingPage` ma bezposrednie powiazanie z `Business` przez `business_id`.

Model korzysta z traitu `BelongsToTenant`, ktory:

- automatycznie uzupelnia `business_id` podczas tworzenia,
- ale nie naklada globalnego scope tenantowego.

W panelu prywatnym tenant jest respektowany przez:

- `currentBusiness()`
- `scopeForBusiness()`
- `LandingPagePolicy`
- routing chroniony middleware `has.business`

### 7.2 Istotny problem architektoniczny

Publiczna trasa `/lp/{slug}` nie niesie zadnej informacji o tenantcie, a `LandingPageSlugService` zapewnia unikalnosc sluga tylko w ramach `business_id`, nie globalnie.

To oznacza realne ryzyko kolizji slugow miedzy tenantami:

- dwa biznesy moga miec ten sam slug,
- publiczny kontroler wyszukuje tylko po `slug` i `status = published`,
- przy kolizji nie ma deterministycznej izolacji tenantowej.

To jest najwazniejsze ograniczenie obecnego modelu publicznych landing pages.

### 7.3 Ocena tenantowosci

- panel prywatny: czesciowo poprawny,
- runtime publiczny LP: niepelny,
- globalny tenant scope: przygotowany, ale nieaktywny.

## 8. Formularze i ich obsluga

### 8.1 Istniejace formularze publiczne

Projekt posiada trzy glowne sciezki formularzy:

1. Formularz kontaktowy strony marketingowej przez `ContactController` i komponent `resources/js/Components/Marketing/Contact.jsx`.
2. Formularz kalkulatora przez `CalculatorLeadController`.
3. Formularz landing page przez `FormSection.jsx` i `PublicLandingPageController@submit`.

### 8.2 Landing page form

`FormSection.jsx` wysyla AJAX przez `axios` na `POST /lp/{slug}/submit`.

Obecna obsluga obejmuje:

- stany `idle/sending/success/error`,
- honeypot `website`,
- walidacje backendowa przez `LeadCaptureRequest`,
- zapis leada przez `LeadService::createFromLandingPage()`,
- zapis source attribution,
- opcjonalna zgoda GDPR,
- event `LeadCaptured` i downstream listeners.

### 8.3 Faktyczne ograniczenia formularza LP

- `FormSection.jsx` obsluguje tylko staly zestaw pol (`name`, `email`, `phone`, `message`).
- Model `LandingPage` ma pole `capture_fields`, ale formularz publiczny nie generuje jeszcze pol dynamicznie z tej konfiguracji.
- Model ma tez `thank_you_url`, ale runtime formularza opiera sie glownie na `content.redirect_url` sekcji i stanie lokalnym komponentu.
- Nie ma wizualnego buildera custom fieldow w edytorze strony.

Wniosek: formularz istnieje i dziala, ale nie jest jeszcze prawdziwym systemem dynamicznych formularzy.

## 9. Struktura sekcji (hero, CTA itd.)

### 9.1 Definicja sekcji

System `LandingPageSection` obsluguje osiem typow sekcji:

- `hero`
- `features`
- `testimonials`
- `cta`
- `form`
- `faq`
- `text`
- `video`

Sekcje sa trzymane w tabeli `landing_page_sections` z polami:

- `type`
- `order`
- `content` JSON
- `settings` JSON
- `is_visible`

### 9.2 Szablony startowe

W `config/landing_pages.php` zdefiniowane sa trzy template keys:

- `lead_magnet`
- `services`
- `portfolio`

Template nie definiuje odrebnego layoutu React. Definiuje tylko poczatkowa liste typow sekcji, ktore `LandingPageService::initSectionsFromTemplate()` zaklada przy tworzeniu strony.

### 9.3 Domyslne tresci sekcji

`LandingPageSection::getDefaultContent()` posiada gotowe placeholdery dla kazdego typu sekcji, np. headline, CTA, FAQ item, testimonial item.

To jest dobry fundament pod generator, bo daje stabilny schemat JSON dla kazdego typu sekcji.

### 9.4 Ograniczenia sekcji

- Sekcje nie sa translatable jak `Page` lub `SiteSection`.
- Dla niektorych typow edytor inline obsluguje tylko headline, a nie pelne itemy listowe.
- Brakuje sekcji typu pricing, logos, stats, comparison, countdown, gallery, integrations, brand proof.
- Brakuje sekcji dla custom code/embed.

## 10. Co mozna wykorzystac do landing page generatora

### 10.1 Najbardziej wartosciowe elementy do reuzycia

1. `LandingPage` + `LandingPageSection` jako model danych generatora.
2. `config/landing_pages.php` jako punkt definicji template bundles i dozwolonych typow sekcji.
3. `LandingPageService::initSectionsFromTemplate()` jako miejsce tworzenia draftu strony z zestawu sekcji.
4. `resources/js/Pages/LandingPage/Show.jsx` i `SECTION_MAP` jako runtime renderer gotowych stron.
5. `resources/js/Components/LandingPage/PublicSection/*` jako biblioteka gotowych sekcji.
6. `BusinessProfileService::getAiContext()` oraz `BusinessProfile::toAiContext()` jako gotowy kontekst do promptowania generatora.
7. `resources/js/Pages/LandingPages/Create.jsx` jako naturalny ekran startowy generatora.
8. `resources/js/Pages/LandingPages/Edit.jsx` + `SectionsList.jsx` jako warstwa manualnej korekty wygenerowanej tresci.
9. Globalny design system z komponentow `Marketing/*` i prototypow `szablon/` jako referencja dla stylu i jezyka sekcji.

### 10.2 Co juz sugeruje gotowosc pod generator

- flaga `ai_generated` w `landing_pages`,
- opisy w onboarding/profile o AI-generated landing pages,
- `BusinessProfileService` zwracajacy AI context,
- business profile z polami brand/tone/services/target audience,
- event publikacji `LandingPagePublished`.

Wniosek: projekt ma przygotowany data model i miejsca integracji pod generator, ale nie ma jeszcze samego silnika generacji.

## 11. Czego brakuje

### 11.1 Braki funkcjonalne

1. Brak realnej integracji z OpenAI lub innym silnikiem generowania tresci.
2. Brak serwisu typu `LandingPageGeneratorService` lub joba generujacego sekcje.
3. Brak workflow typu: business profile -> wybór celu -> prompt -> draft LP.
4. Brak dynamicznego generatora formularza na bazie `capture_fields`.
5. Brak wielojezycznych tresci per sekcja landing page.
6. Brak custom domain / publicznego routingu tenant-aware.
7. Brak preview variants / A-B testow.
8. Brak biblioteki assets dla sekcji (hero image, testimonial avatars, thumbnails) w edytorze.
9. Brak mechanizmu generacji SEO / meta / CTA z danych biznesu.

### 11.2 Braki techniczne lub niespojnosci

1. `LandingPageService` i requesty operuja polami `description`, `custom_css`, `settings`, ale aktualny schemat `landing_pages` nie zawiera tych kolumn.
2. Publiczny routing LP nie rozwiazuje kolizji slugow miedzy tenantami.
3. `LandingPageSection` nie ma translacji, więc pojedyncza strona ma tylko jeden jezyk tresci runtime.
4. `SectionsList.jsx` nie daje pelnej edycji zlozonych elementow `features`, `testimonials`, `faq` i `video`.

## 12. Gdzie wpiac generowanie dynamicznych tresci

### 12.1 Najlepszy punkt wejscia: tworzenie strony

Najbardziej naturalne miejsce to flow `LandingPages/Create.jsx` + `LandingPageController@store` + `LandingPageService::create()`.

Mozliwy przebieg:

1. Uzytkownik wybiera template i cel konwersji.
2. Backend pobiera `BusinessProfileService::getAiContext($business)`.
3. Generator tworzy draft `LandingPage` i komplet `LandingPageSection`.
4. Uzytkownik trafia do `Edit.jsx` i koryguje wynik.

### 12.2 Najlepszy punkt dla generatora tresci sekcji

Generator powinien wypelniac `content` JSON zgodnie z kontraktem istniejacych komponentow publicznych:

- `hero`: `headline`, `subheadline`, `cta_text`, `cta_url`
- `features`: `headline`, `items[]`
- `testimonials`: `headline`, `items[]`
- `cta`: `headline`, `subheadline`, `cta_text`, `cta_url`
- `form`: `headline`, `subheadline`, `fields`, `required`, `cta_text`, `success_message`
- `faq`: `headline`, `items[]`
- `text`: `headline`, `html/body`
- `video`: `headline`, `video_url/url`

To oznacza, ze generator nie musi ingerowac w runtime renderowania. Wystarczy, ze wygeneruje poprawne payloady JSON dla istniejacych sekcji.

### 12.3 Najlepszy punkt dla tresci brandowych

Business Profile to obecnie najlepsze zrodlo danych do promptowania:

- `tagline`
- `description`
- `industry`
- `tone_of_voice`
- `target_audience`
- `services`
- `brand_colors`
- `seo_keywords`

Ten model jest juz przygotowany jako upstream dla generatora.

### 12.4 Punkt rozszerzenia dla szablonow

`config/landing_pages.php` jest najlepszym miejscem do rozszerzania template library o nowe archetypy, np.:

- `webinar`
- `audit_offer`
- `lead_capture_quiz`
- `case_study`
- `service_city_page`

## 13. Ocena przydatnosci obecnego systemu

### Co juz jest gotowe i wartosciowe

- solidny runtime Inertia + React,
- osobny model danych dla landing pages,
- system sekcji z JSON content,
- edytor sekcji,
- publiczny routing,
- obsluga lead capture,
- tenantowe powiazanie danych prywatnych,
- przygotowany context biznesowy pod AI.

### Co obecnie blokuje pelny generator

- brak generacji tresci,
- brak publicznej izolacji tenantowej po slugach,
- brak dynamicznego form buildera,
- brak wielojezycznosci sekcji LP,
- niepelna edycja sekcji zlozonych,
- niespójnosc miedzy serwisem a schematem danych `landing_pages`.

## 14. Rekomendacja architektoniczna

Najlepsza sciezka rozwoju to nie budowanie generatora od zera, tylko rozszerzenie juz istniejacego systemu `LandingPage`.

Rekomendowany kierunek:

1. Traktowac `LandingPage` jako docelowy aggregate generatora.
2. Traktowac `LandingPageSection` jako jedyny kontrakt renderowania sekcji.
3. Wpiac generator na etapie tworzenia draftu strony.
4. Wykorzystywac `BusinessProfileService::getAiContext()` jako wejscie promptowe.
5. Zachowac `Edit.jsx` jako etap post-generation editing.

Nie warto budowac nowego rownoleglego systemu stron. W repo jest juz gotowa baza, ale wymaga domkniecia warstwy generatora, tenant routing i dynamicznych formularzy.

## 15. Krotkie podsumowanie

Aktualny system landing pages jest czesciowo gotowy pod generator: ma modele, routing, sekcje, renderer React i obsluge leadow. Brakuje natomiast samej generacji tresci, dynamicznych formularzy, wielojezycznosci sekcji i bezpiecznego publicznego modelu tenantowego. Najbardziej sensowny punkt integracji generatora to `LandingPageService::create()` zasilany kontekstem z `BusinessProfileService`.