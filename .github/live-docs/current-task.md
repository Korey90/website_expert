# Current Task

**Status:** 🟢 P2.3 wykonane na produkcji — P2.4 (moduł bloga) w realizacji lokalnie
**Last Updated:** 2026-10-05

**Decyzje użytkownika (2026-10-05):**
- P2.3: wykonane bezpośrednio na produkcji przez SSH (za zgodą i pod nadzorem użytkownika, hasło sudo wpisane ręcznie przez użytkownika).
- P2.4: budujemy osobny, lekki moduł bloga (nie dociążamy modelu `Page`).
- Kolejność: najpierw plan do akceptacji, potem implementacja — zaakceptowany, P2.3 wykonane, P2.4 w realizacji.

**Ostatnio zrealizowane (2026-10-05):**
- ✅ **P2.1 — Inertia SSR** wdrożone i zweryfikowane (zob. sekcja P2 niżej) — surowy HTML ze wszystkich stron publicznych zawiera teraz prawdziwy `<h1>`, treść i JSON-LD, nie pusty div.
- ✅ **P2.2 — bezpieczny cache HTTP** (`private, max-age=60`) na stronach marketingowych — zero ryzyka dla waluty/sesji.
- ✅ **P2.5 — rozbudowa H2** na LP PL (zakres usługi, SEO, lokalizacje) z linkiem kontekstowym do `/services/seo`.
- ✅ **P2.3 — ukrycie wersji serwera** — **wykonane na produkcji 2026-10-05 przez SSH** (`korey@website-expert.uk`, Ubuntu 24.04.4, Apache/2.4.58): `/etc/apache2/conf-available/security.conf` → `ServerTokens Prod` + `ServerSignature Off`; `/etc/php/8.3/{fpm,apache2}/php.ini` → `expose_php = Off`. Zweryfikowane: `curl -sI https://website-expert.uk/` → `server: Apache` (bez wersji/OS), brak `X-Powered-By`, strona główna nadal 200.
- 🕐 **P2.4 — klaster treści SEO (moduł bloga)** — plan zaakceptowany, implementacja w toku (patrz sekcja niżej).
- ✅ Plan kampanii Google Search dla `/pl/strony-internetowe-uk` — `docs/sales/plan-kampanii-pl.md`
- ✅ SEO audyt LP — P0 + P1 wdrożone (patrz `.github/completed-tasks/`)

> **Uwaga:** P2.1/P2.2/P2.5 są gotowe tylko lokalnie (w tym repo) — produkcja (`website-expert.uk`) jeszcze ich nie ma wdrożonych (potwierdzone: `curl -sI https://website-expert.uk/` nadal zwraca `Cache-Control: no-cache, private` bez middleware `cache.marketing`). P2.3 zostało wykonane bezpośrednio na produkcji (konfiguracja serwera, nie kod repo). Przed deployem SSR/cache pamiętaj: `npm run build && npm run build:ssr` + uruchomienie `php artisan inertia:start-ssr` pod supervisorem.

## Plan P2.3 — Ukrycie wersji serwera (SSH/root)

Do wykonania ręcznie przez użytkownika na serwerze produkcyjnym (Apache, zgodnie z nagłówkiem widocznym w audycie):

1. Znaleźć główny config: zwykle `/etc/apache2/apache2.conf` (Debian/Ubuntu) lub `/etc/httpd/conf/httpd.conf` (RHEL/CentOS/CloudLinux — częste na cPanel/WHM).
2. Dodać/zmienić dwie dyrektywy (najlepiej w `/etc/apache2/conf-available/security.conf` na Debian/Ubuntu, żeby przetrwało aktualizacje pakietu):
   ```apache
   ServerTokens Prod
   ServerSignature Off
   ```
3. Przeładować Apache: `sudo systemctl reload apache2` (Debian/Ubuntu) lub `sudo apachectl graceful` (RHEL/cPanel).
4. Weryfikacja: `curl -I https://website-expert.uk/` — nagłówek `Server:` powinien pokazać samo `Apache` bez numeru wersji i systemu operacyjnego.
5. Jeśli to hosting współdzielony bez dostępu do głównego configu (mimo roota WHM może nie dawać dostępu do httpd.conf bezpośrednio na niektórych panelach) — w WHM: **Service Configuration → Apache Configuration → Global Configuration → ServerTokens → Prod**, potem **Rebuild Configuration** i restart Apache.

PHP może też ujawniać swoją wersję w nagłówku `X-Powered-By` — przy okazji warto dodać do głównego `php.ini`: `expose_php = Off` i zrestartować PHP-FPM/Apache.

## Plan P2.4 — Lekki moduł bloga (content cluster SEO)

### Delta Analysis
**Wzorzec do powielenia:** `Page`/`PortfolioProject`/`ServiceItem` — `HasFactory, SoftDeletes, HasTranslations` (spatie/laravel-translatable), bez `business_id`/`BelongsToTenant` (publiczna treść marketingowa, nie dane tenant-owe — zgodnie z faktycznie single-tenantowym charakterem aplikacji). Brak kolizji nazw (nie ma dziś żadnego `Article`/`Post`/`BlogPost`).

### Do stworzenia
- [ ] `database/migrations/..._create_articles_table.php` — `title`/`excerpt`/`content`/`meta_title`/`meta_description` (json, translatable), `slug` (unique), `category` (string), `featured_image_path`, `status` (draft/published), `published_at`, `created_by` (FK users, nullable), soft deletes, timestamps.
- [ ] `app/Models/Article.php` — translatable, scope `published()`, `scopeCategory()`.
- [ ] `app/Http/Controllers/ArticleController.php` — `index()` (listing + filtr kategorii, paginacja), `show($slug)` (pojedynczy artykuł + 2-3 powiązane + CTA do odpowiedniego LP).
- [ ] Trasy w `routes/web/public.php`: `GET /blog`, `GET /blog/{slug}` (+ `cache.marketing` middleware, jak reszta stron publicznych).
- [ ] `resources/js/Pages/Blog/Index.jsx` — listing kart (tytuł, excerpt, kategoria, data), breadcrumb Home › Blog.
- [ ] `resources/js/Pages/Blog/Show.jsx` — artykuł: breadcrumb Home › Blog › {tytuł}, JSON-LD `Article`, `meta description`, CTA kontekstowe do `/pl/strony-internetowe-uk` lub `/en/web-design-northern-ireland` zależnie od tematu.
- [ ] `app/Filament/Resources/ArticleResource.php` — formularz z polami translatable (RichEditor dla `content`), upload zdjęcia, select kategorii, status.
- [ ] Sitemap: dodać opublikowane artykuły do `routes/web/public.php` (wzorem `Page`/`PortfolioProject`/`ServiceItem`).
- [ ] Testy PHPUnit: listing 200, pojedynczy artykuł 200, draft → 404, sitemap zawiera opublikowany artykuł.

### Faza 2 — treść (po akceptacji modułu)
6 artykułów z audytu, każdy linkujący do odpowiedniego LP:
1. „Ile kosztuje strona internetowa w UK?” → link do `/pl/strony-internetowe-uk` i `/calculate`
2. „Strona internetowa po polsku czy angielsku?” → link do sekcji „W jakim języku” na LP PL
3. „WordPress czy strona dedykowana — co wybrać?” → link do `/services/brochure-websites`
4. „SEO lokalne dla polskiej firmy w UK” → link do `/services/seo`
5. „Jak wybrać wykonawcę strony internetowej w UK” → link do LP PL (sekcja „Jasne zasady”)
6. „Koszty domeny, hostingu i utrzymania strony w UK” → link do `/calculate`

### Ryzyka
- Routing: `/blog/{slug}` nie koliduje z istniejącym `/{slug}` catch-all w `web.php` (ten jest zarejestrowany jako ostatni — trzeba zarejestrować `/blog` wcześniej, analogicznie do `/p/{slug}`).
- Brak dotychczasowej nawigacji do „Blog” w Navbar/Footer — do dodania jako nowy punkt (albo zostawiamy bez linku w menu na start, tylko wewnętrzne linki z LP, żeby nie przeciążać nawigacji).


## Delta Analysis: Poprawki SEO/wydajność dla LP `/pl/strony-internetowe-uk` (i pokrewnych)

### Anchor files (reużywam / analizowałem)
- `resources/views/app.blade.php` — jedyne, poprawne, server-side źródło `<title>`, canonical, hreflang, robots (przez `$hreflangMap` po `Route::currentRouteName()`), `@routes` (Ziggy), `@vite`.
- `resources/js/app.jsx` — globalny szablon tytułu Inertia: `` `${title} - ${VITE_APP_NAME}` ``.
- `resources/js/Pages/Landing/PlStronyInternetowe.tsx` i `EnWebDesign.tsx` — `<Head>` per-strona (title, description, OG, hreflang-duplikat).
- `resources/js/Components/LandingPage/LandingPageSeo.jsx` — istniejący, gotowy wzorzec pełnego zestawu OG/Twitter/JSON-LD (WebPage) używany przez inny, CMS-owy moduł `/lp/{slug}` — dobry punkt odniesienia pod rozszerzenie LP PL/EN.
- `resources/js/Components/Marketing/{Footer,Navbar}.jsx` — współdzielony mechanizm `isHome` + `resolveHref('#anchor')`, celowo kierujący do sekcji strony głównej (`About.jsx`, `Services.jsx`, `Process.jsx`, `Faq.jsx`, `Contact.jsx`, `CostCalculator.jsx` — wszystkie mają pasujące `id`).
- `database/seeders/SiteSectionSeeder.php` (`footer.extra.nav_groups`) — źródło danych CMS dla stopki, już wgrane do DB.
- `routes/web/public.php` + `resources/views/sitemap.blade.php` — generator sitemapy (brak `lastmod` dla stron statycznych).
- `app/Http/Middleware/HandleInertiaRequests.php` + `bootstrap/app.php` (`DetectGeoCurrency` w grupie `web`) — personalizacja per-request (locale/currency), współdzieli sesję na wszystkich trasach publicznych.
- `tests/Feature/Landing/LandingPreLaunchTest.php` — istniejące testy hreflang/canonical/sitemap/lang (asercje `assertStringContainsString`, bez liczenia wystąpień).

### Potwierdzone istniejące mechanizmy — NIE RUSZAMY
- Server-side canonical (`url()->current()`, bez query string — UTM już nie psuje canonical, wbrew sugestii raportu nic tu nie trzeba zmieniać).
- Server-side hreflang w `app.blade.php` (już działa poprawnie dla `home`, `pl.strony-internetowe-uk`, `en.web-design-northern-ireland`).
- `Navbar.jsx`/`Footer.jsx` `isHome`-owy mechanizm linkowania do sekcji strony głównej — to świadomy, działający wzorzec nawigacji sitewide, a nie błąd (poza jednym wyjątkiem — patrz P1 niżej).
- Middleware `DetectGeoCurrency` + sesja/CSRF na trasach `web` — odpowiada za poprawne ceny/walutę; wszelkie zmiany cache HTTP muszą to uwzględnić.

### Ryzyka / rzeczy wymagające decyzji
- Pełne Inertia SSR (rekomendacja P0 raportu) to zmiana infrastrukturalna (Node SSR proces, `php artisan inertia:start-ssr`, supervisor/docker) — nie da się zrobić "przy okazji" bez ryzyka dla działającego builda. Wymaga osobnego sprintu infra.
- Dodanie publicznego `Cache-Control: public, s-maxage=...` na strony marketingowe może ujawnić spersonalizowaną (currency/session) treść innym użytkownikom przez CDN/proxy, jeśli nie zrobimy tego ostrożnie (np. `Vary`, cache tylko szkieletu, przeniesienie personalizacji do klienta).
- Zmiana zachowania linku „Kalkulator kosztów” w stopce (`#calculate` → `/calculate`) zmieniłaby dziś działające UX na stronie głównej (scroll zamiast przejścia na pełną stronę) — proponuję zmienić **tylko** gdy stopka renderuje się na stronie, która nie ma sekcji `#calculate` (LP, portfolio, itd.), zostawiając stronę główną bez zmian.
- Wpis w DB (`site_sections.footer.extra.nav_groups`) już zawiera zły href — sama zmiana kodu/domyślnych wartości nie naprawi istniejącej instalacji; potrzebny dodatkowy krok danych.
- Ukrycie wersji serwera (Apache/Ubuntu) to zmiana konfiguracji hostingu/infry, nie kodu aplikacji — do zweryfikowania, gdzie faktycznie jest deployowana produkcja (repo ma configi dla nginx+docker, audyt widział nagłówek Apache).

---

## Plan poprawek (priorytety jak w raporcie: P0 / P1 / P2)

### P0 — WYKONANE ✅ (2026-09-17)

1. ✅ **Naprawa podwójnego brandingu w `<title>`** — usunięty ręczny sufiks „— Website Expert” / „– WebsiteExpert” z: `PlStronyInternetowe.tsx`, `EnWebDesign.tsx`, `Welcome.jsx`, `Kalkulator.jsx`, `Portfolio/Index.jsx`, `Portfolio/Show.jsx`, `Services/Index.jsx`. Jedyne źródło brandingu: `resources/js/app.jsx`.
2. ✅ **Usunięcie zduplikowanego hreflang + canonical po stronie klienta** — usunięte z `<Head>` w obu LP (blade renderuje canonical dla wszystkich stron; hreflang PL↔EN usunięty **całkowicie** z `app.blade.php` per decyzję użytkownika — różne rynki, nie tłumaczenia tej samej oferty).
3. ✅ **Meta description PL LP** — podmieniona na wersję z raportu.
4. ✅ **`tel:` na obu LP** — realny numer `+447882799050` przez `contact_phone` (CMS `SiteSection.extra`, edytowalne w Filamencie), fallback w kodzie. Naprawiono też PL LP, gdzie `getQueryParam('phone')` dawał ten sam pusty `tel:` co na EN (ukryty duplikat błędu, niewykryty przez audyt).
5. ✅ **`lastmod` w sitemap** — dodany dla wszystkich statycznych URL-i (`routes/web/public.php` + `resources/views/sitemap.blade.php`), generowany przy każdym odświeżeniu cache (co godzinę).
6. ⛔ **Pominięte per decyzję użytkownika** — link „Kalkulator kosztów” w stopce zostaje `/#calculate` bez zmian.

**Walidacja:** `tests/Feature/Landing/LandingPreLaunchTest.php` zaktualizowany (usunięte asercje hreflang PL↔EN, dodane asercje "brak hreflang" + poprawione asercje canonical niezależne od env) — 14/14 ✅. Regresja: `PlLandingControllerTest` 11/11, `EnLandingControllerTest` + `LandingLeadFollowUpTest` — 25/25 ✅ razem. `npm run build` ✅ bez błędów. `npm run lint` — bez nowych błędów (jedyny błąd to pre-existing, niezwiązany `docs/python tests/load-test.js`). `vendor/bin/pint --dirty` ✅.

---

### P1 — WYKONANE ✅ (2026-09-17)

1. ✅ **Dane strukturalne JSON-LD** — `Service` + `FAQPage` (wygenerowany z tej samej tablicy `faqItems`, które renderuje UI — jedno źródło prawdy) + `BreadcrumbList` dla obu LP, jeden `<script type="application/ld+json">` z `@graph`.
2. ✅ **Open Graph + Twitter Cards** — dodane `og:type`, `og:url`, warunkowe `og:image`/`twitter:image` (przez nowe pole CMS `og_image_url`, na wzór `LandingPageSeo.jsx`), `twitter:card` (`summary_large_image` gdy jest obrazek, inaczej `summary`). **Aktualizacja 2026-09-20:** obrazek OG (1200×630, `public/images/og/strony-internetowe-uk-pl.png`) wygenerowany i podłączony jako domyślny fallback — `og:image`/`twitter:image` aktywne, zweryfikowane na żywo w przeglądarce.
3. ✅ **FAQ w pełnym HTML** — potwierdzone: `{open && (...)}` faktycznie usuwało odpowiedź z DOM. Zmienione na zawsze-renderowane `<p className={... hidden}>` — odpowiedzi są teraz w DOM/HTML (indeksowalne), a widocznie chowane/pokazywane przez CSS `hidden`/`block` — bez zmiany UX/animacji.
4. ✅ **Zdjęcie założyciela** — dodane `width`/`height`/`loading="lazy"`/`decoding="async"` + bardziej opisowy `alt` (imię + rola) na obu LP. **Nie skompresowano samego pliku** — plik `storage/app/public/2342452345234.png` (1.79 MB, zgadza się z audytem) jest lokalnym, niewersjonowanym uploadem CMS (poza gitem), więc realna kompresja/konwersja do WebP musi się odbyć przez ponowny upload skompresowanego pliku w Filamencie (operacyjne, nie kodowe zadanie).
5. ✅ **Ograniczenie payloadu Ziggy** — dodany `config/ziggy.php` (`except: ['filament.*', 'admin.*']`). Zweryfikowane grepem (zero użyć `route('admin.`/`route('filament.` w `resources/js/**`) i empirycznie w przeglądarce: payload LP nie zawiera już tras `filament.*`/`admin.*`, publiczne trasy (`kalkulator`, `sitemap`, itd.) działają bez zmian.
6. ⏸️ **Realne case studies / referencje / bio założyciela** — odłożone, wymaga danych od Ciebie (zakres treściowy, nie techniczny).

**Walidacja P1:** build ✅, lint bez nowych błędów ✅, testy Landing 27/27 ✅. Dodatkowo zweryfikowane **na żywo w przeglądarce** (bo te zmiany żyją w client-side `<Head>`, niewidoczne w surowym HTTP response bez SSR): tytuł bez duplikatu brandingu, 0 tagów hreflang, 1 canonical, JSON-LD z `Service`/`FAQPage`/`BreadcrumbList`, odpowiedź FAQ obecna w DOM z klasą `hidden`, `tel:+447882799050` — wszystko potwierdzone na obu LP (PL i EN).

### P2 — WYKONANE W WIĘKSZOŚCI ✅ (2026-10-05)

1. ✅ **Inertia SSR** — wdrożone i zweryfikowane. `resources/js/ssr.jsx` (nowy entry point, `@inertiajs/react/server` + `ReactDOMServer.renderToString`, identyczny `GoogleReCaptchaProvider` wrapper co `app.jsx`), `vite.config.js` (`ssr: 'resources/js/ssr.jsx'`), `package.json` (`build:ssr`), build do `bootstrap/ssr/` (gitignored jak `public/build`). Żadnej zmiany `config/inertia.php` nie było potrzeba — pakiet ma bezpieczne domyślne wartości (`ssr.enabled=true`), wystarczyło że bundle zaczął istnieć. **Naprawiony jedyny realny blocker:** `Navbar.jsx` miał `useState(() => localStorage.getItem(...))` w lazy initializerze — crashowało SSR na każdej stronie publicznej (bo `localStorage` nie istnieje w Node); naprawione guardem `typeof window === 'undefined'`. Uruchom lokalnie: `npm run build && npm run build:ssr && php artisan inertia:start-ssr`. **Produkcja:** proces Node musi działać stale (supervisor/systemd) i być restartowany po każdym deployu wraz z `npm run build:ssr` — repo (docker-compose) nie ma do tego serwisu, bo produkcja to Apache, nie ten docker setup; trzeba to skonfigurować na realnym hostingu.
2. ✅ **Bezpieczny cache HTTP** — nowy `app/Http/Middleware/CacheMarketingResponse.php` (alias `cache.marketing`) ustawia `Cache-Control: private, max-age=60, must-revalidate` na GET-owych trasach marketingowych (`/`, `/calculate`, `/portfolio(/{slug})`, `/services(/{slug})`, oba LP, `/tools/google-rank-checker`, strony CMS/`legal`). Celowo **`private`, nie `public`** — strony mają w payloadzie Inertia spersonalizowaną walutę (`DetectGeoCurrency`)/locale, więc cache współdzielony (CDN) mógłby pokazać czyjąś walutę innemu użytkownikowi; `private` daje tylko przeglądarce nadawcy prawo do reużycia odpowiedzi (np. back/forward) — zero ryzyka między użytkownikami. `/portal` (nietknięte) nadal zwraca oryginalny `no-cache, private`.
3. ⏸️ **Ukrycie wersji serwera** (Apache/Ubuntu w nagłówkach) — **nie da się zrobić z repo.** Audyt widział nagłówek na produkcji, ale ten docker-compose uruchamia nginx+php-fpm, nie Apache — produkcja działa więc na innym, realnym hostingu (cPanel/VPS?), do którego nie mam dostępu. **Instrukcja do wykonania ręcznie na hostingu:** w głównym `httpd.conf`/`apache2.conf` ustaw `ServerTokens Prod` i `ServerSignature Off`; jeśli to hosting współdzielony bez dostępu do głównego configu, zapytaj dostawcę lub dodaj do `.htaccess` (działa tylko częściowo, nie usuwa nagłówka `Server` samego serwera, tylko stopki błędów).
4. ⏸️ **Klaster treści SEO** (6 artykułów z audytu: ile kosztuje strona w UK, PL vs EN, WordPress vs dedykowane, SEO lokalne, jak wybrać wykonawcę, koszty domeny/hostingu) — **wymaga decyzji, nie tylko czasu.** W repo nie ma modułu blog/artykuły — jest tylko `Page`/`PageController` (`/p/{slug}`), zaprojektowany pod strony prawne/CMS (pola `type`, `effective_date`, `version`, podmiana zmiennych prawnych), bez listingu/kategorii/nawigacji artykułów. Zanim napiszę 6 pełnych artykułów, potrzebuję Twojej decyzji: (a) dociążyć istniejący model `Page` i ręcznie podlinkować artykuły z LP, czy (b) zbudować lekki moduł bloga (listing + kategorie) najpierw. To osobny sprint treściowy.
5. ✅ **Rozbudowa H2 na LP PL** — dodana nowa sekcja między pakietem a portfolio: „Projektowanie stron internetowych w UK — co obejmuje usługa?”, „SEO dla firm w UK” (z linkiem kontekstowym do `/services/seo`), „Obsługiwane lokalizacje w Wielkiej Brytanii” — bez duplikowania istniejących sekcji (cena, PL/EN już były). Zweryfikowane w surowym SSR HTML.
6. ✅ **PageSpeed / Core Web Vitals — fundament położony** (SSR + cache). Pełny pomiar PageSpeed Insights wymaga wdrożenia na produkcję (nie da się wiarygodnie zmierzyć lokalnie) — do zrobienia po deployu SSR.

**Walidacja P2:** `php artisan test` (pełny pakiet) — 502 passed / 6 failed / 10 skipped. Wszystkie 6 failów to pre-existing, niezwiązane z dzisiejszą pracą (`SocialAuthControllerTest`, `PortalBillingAccessTest` PLN/GBP, 3× `ProfileTest` po przeniesieniu trasy do `/portal/settings/profile` w innym zadaniu 2026-09-20, `ServicePageControllerTest` — inny kontroler niż `/services`). `npm run build` + `npm run build:ssr` ✅, `npm run lint` bez nowych błędów ✅, `vendor/bin/pint --dirty` ✅. Zweryfikowane empirycznie (surowy HTTP, bez JS): `<h1>` i pełna treść obecne na `/`, `/pl/strony-internetowe-uk`, `/en/web-design-northern-ireland`, `/services`, `/services/{slug}`, `/portfolio`, `/tools/google-rank-checker`; nagłówek `Cache-Control: private, max-age=60, must-revalidate` na wszystkich wymienionych.


