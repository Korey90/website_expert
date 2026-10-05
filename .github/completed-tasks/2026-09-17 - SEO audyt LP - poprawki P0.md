# 2026-09-17 - SEO audyt LP (/pl/strony-internetowe-uk) - poprawki P0

**Status:** Zakończone sukcesem (P0). P1/P2 zaplanowane w `.github/live-docs/current-task.md` — kolejne sprinty.
**Data realizacji:** 2026-09-17
**Źródło:** `docs/reports/seo-and-performence-raport.md`

## Cel zadania
Naprawić najpilniejsze (P0) problemy SEO/wydajności wskazane w audycie zewnętrznym dla `/pl/strony-internetowe-uk`, bez psucia istniejących, działających mechanizmów (server-side canonical/hreflang, nawigacja sitewide, personalizacja waluty/sesji).

## Zakres wykonanych prac (P0)
- Usunięty sitewide double-branding w `<title>` (np. „...Website Expert - WebsiteExpert”) — globalny sufiks brandingu zostaje wyłącznie w `resources/js/app.jsx`, usunięty ręczny duplikat z 7 stron: `Welcome.jsx`, `Kalkulator.jsx`, `Portfolio/Index.jsx`, `Portfolio/Show.jsx`, `Services/Index.jsx`, `PlStronyInternetowe.tsx`, `EnWebDesign.tsx`.
- Usunięty zduplikowany `canonical` + `hreflang` renderowany po stronie klienta (React `<Head>`) na obu landing page'ach — server-side wersja w `app.blade.php` pozostaje jedynym źródłem.
- Hreflang między PL LP a EN LP usunięty **całkowicie** z `$hreflangMap` w `app.blade.php` (decyzja użytkownika: strony celują w różne rynki — cała UK vs Irlandia Północna — nie są tłumaczeniem tej samej oferty).
- Zaktualizowana treść `<meta name="description">` na PL LP (wersja z audytu).
- Naprawiony pusty `href="tel:"` na obu LP — realny numer `+447882799050` przez nowe pole `contact_phone` (CMS `SiteSection.extra`, edytowalne w Filamencie), z fallbackiem w kodzie. Przy okazji ujawniony i naprawiony ukryty duplikat tego samego błędu na PL LP (`getQueryParam('phone') || ''` dawał ten sam pusty `tel:`, audyt złapał tylko wersję EN).
- Dodany `lastmod` do `sitemap.xml` dla wszystkich statycznych URL-i (home, oba LP, `/calculate`, `/tools/google-rank-checker`, `/portfolio`, `/services`) — generowany przy każdym odświeżeniu cache sitemapy (co godzinę).
- Świadomie pominięte (decyzja użytkownika): link „Kalkulator kosztów” w stopce (`/#calculate`) zostaje bez zmian.

## Użyte agenty i skille
- @WebsiteExpert (orkiestracja, delta-analysis, plan)
- Skill: delta-analysis
- @FrontendEngineer (edycje React/TSX/JSX)
- @BackendEngineer (blade, sitemap route)
- @TestingEngineer (aktualizacja testów regresyjnych)

## Zmodyfikowane pliki
- `resources/js/app.jsx` — bez zmian (potwierdzony jako jedyne źródło brandingu)
- `resources/js/Pages/Welcome.jsx`
- `resources/js/Pages/Kalkulator.jsx`
- `resources/js/Pages/Portfolio/Index.jsx`
- `resources/js/Pages/Portfolio/Show.jsx`
- `resources/js/Pages/Services/Index.jsx`
- `resources/js/Pages/Landing/PlStronyInternetowe.tsx`
- `resources/js/Pages/Landing/EnWebDesign.tsx`
- `resources/views/app.blade.php`
- `routes/web/public.php`
- `resources/views/sitemap.blade.php`
- `tests/Feature/Landing/LandingPreLaunchTest.php`

## Walidacja końcowa
- ✅ `tests/Feature/Landing/LandingPreLaunchTest.php` — 14/14
- ✅ Regresja Landing: `PlLandingControllerTest` + `EnLandingControllerTest` + `LandingLeadFollowUpTest` — 25/25
- ✅ `npm run build` (Vite) — brak błędów
- ✅ `npm run lint` — brak nowych błędów (jedyny błąd to pre-existing, niezwiązany `docs/python tests/load-test.js`)
- ✅ `vendor/bin/pint --dirty` — 1 plik poprawiony (line_ending), testy dalej zielone
- ✅ Brak zmian w multi-tenancy / brak nowych modeli

## Uwagi i rekomendacje
- Pełne Inertia SSR (główna rekomendacja P0 raportu) świadomie **nie** zostało zrobione — to osobny sprint infrastrukturalny (Node SSR proces + docker/supervisor), zaplanowany w P2.
- Cache HTTP (`Cache-Control: public`) dla stron marketingowych świadomie odłożone do P2 — ryzyko: `DetectGeoCurrency` personalizuje strony per-sesja, naiwne dodanie publicznego cache mogłoby pokazać złą walutę/cenę innym użytkownikom z CDN/proxy.
- Pełny plan P1/P2 (JSON-LD, OG/Twitter Cards, FAQ w HTML, kompresja zdjęcia założyciela, ograniczenie payloadu Ziggy, SSR, cache HTTP, ukrycie wersji serwera, klaster treści SEO) pozostaje w `.github/live-docs/current-task.md` do zaplanowania jako kolejne sprinty.

## Next steps
- Zaplanować sprint P1 (JSON-LD + OG/Twitter + FAQ w HTML + kompresja obrazu + Ziggy `except`) — średnie ryzyko, do doprecyzowania (case studies, bio założyciela).
- Zaplanować sprint P2 (SSR/prerendering, cache HTTP, ukrycie wersji serwera, klaster treści) — wymaga osobnej analizy infra i decyzji biznesowych.

**Raport wygenerowany automatycznie przez WebsiteExpert**
