# 2026-09-17 - SEO audyt LP (/pl/strony-internetowe-uk) - poprawki P1

**Status:** Zakończone sukcesem (P1). P2 zaplanowane w `.github/live-docs/current-task.md` — osobny sprint infra/treściowy.
**Data realizacji:** 2026-09-17
**Źródło:** `docs/reports/seo-and-performence-raport.md` (kontynuacja P0 — patrz `2026-09-17 - SEO audyt LP - poprawki P0.md`)

## Cel zadania
Wdrożyć poprawki P1 z planu SEO/wydajności: dane strukturalne, pełne Open Graph/Twitter Cards, FAQ indeksowalne w HTML, atrybuty obrazu founder oraz ograniczenie publicznego payloadu Ziggy — bez psucia istniejących mechanizmów.

## Zakres wykonanych prac (P1)
- **JSON-LD** — dodany jeden `<script type="application/ld+json">` z `@graph` zawierającym `Service`, `FAQPage` (generowany z tej samej tablicy `faqItems`, którą renderuje UI — jedno źródło prawdy, bez duplikacji treści) i `BreadcrumbList`, na obu LP (PL i EN).
- **Open Graph + Twitter Cards** — dodane `og:type`, `og:url`; nowe pole CMS `og_image_url` (`CmsExtra`) sterujące warunkowym `og:image`/`twitter:image` i `twitter:card` (`summary_large_image` vs `summary`) — mechanizm gotowy, aktywuje się automatycznie po wgraniu obrazka przez Filament (żaden URL nie został na sztywno wymyślony, żeby uniknąć 404).
- **FAQ w pełnym HTML** — potwierdzone, że `{open && (<p>...</p>)}` faktycznie usuwało odpowiedź z DOM (niewidoczne dla crawlera bez interakcji JS, nawet po pełnym renderze React). Zmienione na zawsze-renderowane `<p>` z klasą `hidden`/`block` sterowaną stanem `open` — treść jest teraz w DOM/HTML, wizualnie chowana przez CSS, bez zmiany animacji/UX.
- **Zdjęcie założyciela** — dodane `width={192} height={192}`, `loading="lazy"`, `decoding="async"` i opisowy `alt` (imię + rola, z fallbackiem gdy CMS nie ma imienia). Sama kompresja pliku 1.79 MB **nie została wykonana** — plik źródłowy (`storage/app/public/2342452345234.png`) to lokalny, niewersjonowany upload CMS (poza gitem); realna kompresja wymaga ponownego wgrania zoptymalizowanego pliku przez Filament, nie zmiany kodu.
- **Ograniczenie payloadu Ziggy** — nowy `config/ziggy.php` z `except: ['filament.*', 'admin.*']`. Zweryfikowane grepem (zero wywołań `route('admin.`/`route('filament.` w `resources/js/**`) oraz empirycznie w przeglądarce — publiczny payload LP nie zawiera już tras panelu admina, a publiczne trasy (`kalkulator`, `sitemap`, itd.) działają bez zmian.

## Użyte agenty i skille
- @WebsiteExpert (orkiestracja)
- @FrontendEngineer (JSON-LD, OG/Twitter, FAQ, atrybuty obrazu)
- @BackendEngineer (`config/ziggy.php`, weryfikacja route names)
- @TestingEngineer (walidacja regresji + weryfikacja w przeglądarce)

## Zmodyfikowane / utworzone pliki
- `resources/js/Pages/Landing/PlStronyInternetowe.tsx`
- `resources/js/Pages/Landing/EnWebDesign.tsx`
- `config/ziggy.php` (nowy)

## Walidacja końcowa
- ✅ Regresja Landing: `LandingPreLaunchTest` + `PlLandingControllerTest` + `EnLandingControllerTest` — 27/27
- ✅ `npm run build` (Vite) — brak błędów
- ✅ `npm run lint` — brak nowych błędów (jedyny błąd to pre-existing, niezwiązany `docs/python tests/load-test.js`)
- ✅ `vendor/bin/pint --dirty` — 1 plik poprawiony (line_ending)
- ✅ **Weryfikacja na żywo w przeglądarce** (Playwright, `php artisan serve` + istniejący Vite dev): na obu LP potwierdzone — tytuł bez duplikatu brandingu, 0 tagów hreflang, 1 canonical, obecność JSON-LD (`Service`/`FAQPage`/`BreadcrumbList`), odpowiedź FAQ w DOM z klasą `hidden`, poprawny `tel:+447882799050`
- ✅ Empirycznie potwierdzone (tinker + request): payload Ziggy na LP nie zawiera już `filament.`/`admin.email-preview`, publiczne trasy (`kalkulator`, `sitemap`) nadal obecne

## Uwagi i rekomendacje
- Powyższe zmiany (title, meta description, OG, JSON-LD, widoczność FAQ) żyją wyłącznie w kliencie (React `<Head>`/DOM) — bez SSR (P2) nie da się ich zweryfikować samym `curl`/PHPUnit `getContent()`. Do walidacji użyto realnej przeglądarki (Playwright) — warto pamiętać o tym podejściu przy kolejnych zadaniach SEO na tym froncie.
- `og:image`/`twitter:image` pozostają puste do czasu wgrania realnej grafiki 1200×630 przez Filament (`og_image_url`) — mechanizm gotowy, nie wymaga dalszych zmian w kodzie.
- Kompresja zdjęcia założyciela to zadanie operacyjne (re-upload przez CMS), nie kodowe — poza zakresem tego, co mogłem bezpiecznie zmienić z repozytorium.

## Next steps
- Zaplanować sprint P2: pełne Inertia SSR/prerendering, bezpieczny cache HTTP (z uwzględnieniem `DetectGeoCurrency`), ukrycie wersji serwera, klaster treści SEO + rozbudowa H2.
- Operacyjnie: wgrać skompresowaną grafikę founder (WebP/AVIF) i obrazek OG 1200×630 przez Filament.

**Raport wygenerowany automatycznie przez WebsiteExpert**
