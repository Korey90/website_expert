# 2026-10-05 - SEO audyt LP - P2 (SSR, cache, H2)

**Status:** Zakończone w większości sukcesem. 2 punkty (ukrycie wersji serwera, klaster treści) wymagają dostępu/decyzji spoza zakresu repo — udokumentowane jako kolejny krok.
**Data realizacji:** 2026-10-05
**Źródło:** `docs/reports/seo-and-performence-raport.md` (kontynuacja P0/P1 — patrz raporty z 2026-09-17)

## Cel zadania
Dokończyć plan SEO/wydajności: P2 — Inertia SSR, bezpieczny cache HTTP, ukrycie wersji serwera, klaster treści SEO, rozbudowa H2, fundament pod PageSpeed/CWV.

## Zakres wykonanych prac

### P2.1 — Inertia SSR ✅
- Nowy `resources/js/ssr.jsx` (`@inertiajs/react/server` + `ReactDOMServer.renderToString`), identyczny wrapper `GoogleReCaptchaProvider` co `app.jsx`.
- `vite.config.js`: `laravel({ ssr: 'resources/js/ssr.jsx' })`.
- `package.json`: nowy skrypt `build:ssr` (`vite build --ssr`) → `bootstrap/ssr/` (dodane do `.gitignore`, jak `public/build`).
- `.env`/`.env.example`: `INERTIA_SSR_ENABLED`/`INERTIA_SSR_URL` (dla przejrzystości — pakiet ma bezpieczne domyślne wartości, `config/inertia.php` nie było potrzebne).
- **Jedyny napotkany blocker:** `Navbar.jsx` miał `useState(() => localStorage.getItem(...))` w lazy initializerze renderowanym także w Node (gdzie `localStorage` nie istnieje) — naprawione guardem `typeof window === 'undefined'`. Zweryfikowany grepem brak innych podobnych wzorców w `resources/js/**`.
- Potwierdzone bezpieczeństwo mechanizmu: Inertia (`vendor/inertiajs/inertia-laravel/src/Directive.php`) **gracefully fallbackuje** do zwykłego client-side renderu, jeśli SSR padnie na konkretnej stronie — nie psuje innych stron ani nie zwraca błędu użytkownikowi.

### P2.2 — Bezpieczny cache HTTP ✅
- Nowy `app/Http/Middleware/CacheMarketingResponse.php` (alias `cache.marketing`): `Cache-Control: private, max-age=60, must-revalidate` na GET-owych trasach marketingowych.
- Celowo `private`, nie `public`/shared — uniknięcie ryzyka z audytu (CDN mógłby pokazać czyjąś spersonalizowaną walutę/locale innemu użytkownikowi).
- Zastosowane na: `/`, `/calculate`, `/portfolio(/{slug})`, `/services(/{slug})`, `/pl/strony-internetowe-uk`, `/en/web-design-northern-ireland`, `/tools/google-rank-checker` (GET), `/p/{slug}` + strony prawne. Trasy POST/portal/admin nietknięte.

### P2.5 — Rozbudowa H2 na LP PL ✅
- Nowa sekcja między pakietem a portfolio: „Projektowanie stron internetowych w UK — co obejmuje usługa?”, „SEO dla firm w UK” (link kontekstowy do `/services/seo`), „Obsługiwane lokalizacje w Wielkiej Brytanii”.
- Treść ugruntowana w faktycznej ofercie (bez nadmiernych obietnic — np. nie twierdzi, że miesięczne SEO jest w pakiecie, bo nie jest).

### P2.3 — Ukrycie wersji serwera ⏸️ (poza zakresem repo)
Ten docker-compose uruchamia nginx + php-fpm, nie Apache — audyt widział nagłówek Apache na realnej produkcji, która działa na innym hostingu, do którego nie mam dostępu. Instrukcja do wykonania ręcznie: `ServerTokens Prod` + `ServerSignature Off` w głównym configu Apache (lub kontakt z dostawcą hostingu współdzielonego).

### P2.4 — Klaster treści SEO ⏸️ (wymaga decyzji)
W repo nie ma modułu blog/artykuły — jest tylko `Page`/`PageController` zaprojektowany pod strony prawne/CMS (bez listingu, kategorii). Przed napisaniem 6 artykułów z audytu potrzebna decyzja: wykorzystać istniejący model `Page`, czy zbudować osobny moduł bloga.

## Walidacja końcowa
- ✅ `php artisan test` (pełny pakiet): 502 passed / 6 failed (wszystkie pre-existing, niezwiązane — udokumentowane w `current-task.md` i pamięci repo) / 10 skipped
- ✅ `npm run build` + `npm run build:ssr` — brak błędów
- ✅ `npm run lint` — bez nowych błędów (dodano `bootstrap/ssr/**` do ignorowanych katalogów ESLint)
- ✅ `vendor/bin/pint --dirty` — czysto
- ✅ Zweryfikowane empirycznie (surowy HTTP, bez wykonania JS): `<h1>` + pełna treść + JSON-LD obecne na `/`, obu LP, `/services(/{slug})`, `/portfolio`, `/tools/google-rank-checker`; nagłówek `Cache-Control: private, max-age=60, must-revalidate` wszędzie tam gdzie oczekiwano, `/portal` bez zmian

## Zmodyfikowane / utworzone pliki
- `resources/js/ssr.jsx` (nowy)
- `resources/js/Components/Marketing/Navbar.jsx`
- `resources/js/Pages/Landing/PlStronyInternetowe.tsx`
- `vite.config.js`, `package.json`, `.env`, `.env.example`, `.gitignore`, `eslint.config.js`
- `app/Http/Middleware/CacheMarketingResponse.php` (nowy)
- `bootstrap/app.php`, `routes/web/public.php`

## Uwagi i rekomendacje
- **Produkcja:** po wdrożeniu repo na prawdziwy serwer, proces `php artisan inertia:start-ssr` musi działać stale (supervisor/systemd) i być restartowany po każdym deployu razem z `npm run build:ssr` — inaczej SSR po prostu nie zadziała (ale dzięki fallbackowi, strona nadal będzie działać, tylko bez benefitu SSR).
- Po wdrożeniu na produkcję warto uruchomić realny PageSpeed Insights (P2.6) — nie da się tego wiarygodnie zmierzyć lokalnie.
- P2.3 i P2.4 czekają na Twoją decyzję/dostęp — nie są „zapomniane”, tylko świadomie poza zakresem tego, co można zrobić z samego repozytorium.

## Next steps
- Zdecydować o platformie dla klastra treści SEO (Page vs nowy moduł blog) i zaplanować pisanie 6 artykułów.
- Skonfigurować `ServerTokens Prod` na realnym hostingu produkcyjnym.
- Po deployu: uruchomić `php artisan inertia:start-ssr` pod supervisorem, zweryfikować PageSpeed Insights na produkcji.

**Raport wygenerowany automatycznie przez WebsiteExpert**
