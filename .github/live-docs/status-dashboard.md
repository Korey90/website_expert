# WebsiteExpert - Project Status Dashboard

**Last Full Validation:** 2026-11-05
**Last Targeted Validation:** 2026-11-05 — UKFIN-406: utworzenie expense/payment z transakcji bankowej

## Health Checks
- **Code Quality (Pint + ESLint):** ✅ Pint przechodzi dla plików UKFIN-406; pełny skan repozytorium wskazuje wcześniejszy backlog formatowania. ESLint bez nowych błędów, pozostają 3 pre-existing `__ENV` w `docs/python tests/load-test.js`
- **Translations (pl/en/pt):** ✅ `finance.php` ma identyczne 508 kluczy liściowych w PL/EN/PT
- **Tests:** PHPUnit pełny pakiet (2026-11-05): 705 passed / 9 failed (baseline, niezwiązane: SocialAuth, 3×homepage meta, Billing PLN/GBP, 3×ProfileTest po przeniesieniu trasy, ServicePageController) / 11 skipped / 2649 assertions
- **Multi-Tenancy Compliance:** 100% ✅ (wyjątek celowy: `search_ranking_checks.business_id` nullable — narzędzie publiczne/anonimowe, przygotowane pod przyszłe powiązanie z klientem)
- **Security Review:** ✅ OK — throttle + reCAPTCHA v3 na endpointach publicznych, `ip_hash` zamiast surowego IP, brak sekretów w logach
- **Build (Vite):** ✅ Brak błędów TS/JS
- **Hardcoded £/GBP scan:** ✅ Brak trafień w app/, resources/, seeders/

## Recent Activity
- 2026-11-05 — **UKFIN-406:** atomowe utworzenie customer payment do istniejącej faktury albo kompletnego posted/paid expense z nieuzgodnionej transakcji bankowej; płatności zachowują rachunek i księgują przepływ na jego cash ledger, a workflow obsługuje VAT gross-to-net, FX, tenant/RBAC, pełny rollback i automatyczne reconciliation. Finance regression: 196 passed / 1 skipped / 1205 assertions; full regression: 705 passed / 9 baseline failures / 11 skipped / 2649 assertions ✅
- 2026-11-05 — **UKFIN-405:** tenantowe reconciliation batches i polymorphic allocations dla exact/partial/split/many-to-many; blokady DB i ponowne liczenie reszt zabezpieczają transakcję oraz payment/refund przed over-allocation, obsługiwany jest snapshot GBP dla źródeł walutowych. Nowy Filament `/admin/bank-transactions` ma listę, filtry, szczegóły i modal wielu alokacji; audit command sprawdza nowe relacje. Finance regression: 188 passed / 1 skipped / 1162 assertions; full regression: 698 passed / 9 baseline failures / 11 skipped / 2609 assertions ✅
- 2026-11-05 — **Pakiet demonstracyjnych PDF dla księgowego UK:** 18 anglojęzycznych PDF-ów, checklista, dataset, manifest SHA-256 i ZIP w `docs/demo/uk-accountant-pack/`. Dane wystawcy są pobierane z rzeczywistych `Legal Settings` (`Website Expert NI`), transakcje pozostają fikcyjne, a raporty docelowe są jawnie oznaczone jako preview. Finance regression: 180 passed / 1 skipped / 1116 assertions; full regression: 689 passed / 9 baseline failures / 11 skipped / 2556 assertions ✅
- 2026-11-05 — **UKFIN-404:** tenantowy scoring 0–100 kandydatów bank reconciliation dla wpłat klientów, zwrotów klientom, płatności i zwrotów dostawców; punkty za kwotę/datę/referencję/kontrahenta, kierunek przepływu, snapshot GBP, confidence exact/high/medium/low, deterministyczne sortowanie i próg partial 40. Finance regression: 179 passed / 1 skipped / 984 assertions; full regression: 688 passed / 9 baseline failures / 11 skipped / 2428 assertions ✅
- 2026-11-05 — **UKFIN-402/403:** importer wyciągów CSV na widoku rachunku (mapowanie kolumn, formaty UK, UTF-8/Windows-1252, delimiter, limity 5 MB/5000, dry-run bez zapisów), tenantowe partie importu i `BankTransaction`, deduplikacja provider ID + kanoniczny SHA-256 z unique constraints, prywatny plik tymczasowy usuwany po operacji, tłumaczenia PL/EN/PT i rozszerzony audit tenantów. Finance regression: 172 passed / 1 skipped / 938 assertions ✅
- 2026-10-05 — **SEO P2 (główna część zakończona):** włączone Inertia SSR (`resources/js/ssr.jsx`, `vite.config.js` `ssr:`, `npm run build:ssr` → `bootstrap/ssr/`, uruchamiane przez `php artisan inertia:start-ssr`) — surowy HTTP (bez JS) zawiera teraz prawdziwy `<h1>`/treść/JSON-LD na wszystkich stronach publicznych (wcześniej pusty div). Jedyny napotkany SSR-blocker: `Navbar.jsx` czytał `localStorage` w lazy state initializerze (crash w Node) — naprawione guardem `typeof window`. Dodany bezpieczny `Cache-Control: private, max-age=60` (`app/Http/Middleware/CacheMarketingResponse.php`, alias `cache.marketing`) na stronach marketingowych — `private` nie `public`, zero ryzyka przecieku waluty/sesji między użytkownikami. Rozbudowane H2 na PL LP (zakres usługi, SEO, lokalizacje) z linkiem do `/services/seo`. Pełny `php artisan test`: 502/6/10 (failę pre-existing, niezwiązane). Ukrycie wersji serwera i klaster treści SEO odrębnie udokumentowane w `current-task.md` — wymagają dostępu do hostingu / decyzji o platformie blogowej ✅
- 2026-09-20 — Dodane widoczne breadcrumbs w hero na `/pl/strony-internetowe-uk` (`Strona główna › Strony internetowe UK`), zgodne z istniejącym JSON-LD `BreadcrumbList` (audyt SEO wskazywał ich brak jako czysto techniczne — dotąd był tylko schema, bez widocznego elementu UI). Wzorowane na istniejącym wzorcu `resources/js/Pages/CmsPage.jsx`. Build ✅, lint bez nowych błędów ✅, testy Landing 14/14 ✅, zweryfikowane na żywo w przeglądarce ✅
- 2026-09-20 — Wygenerowana grafika `og:image`/`twitter:image` dla LP PL (`public/images/og/strony-internetowe-uk-pl.png`, 1200×630) — Glif zablokowany brakiem kredytów, więc grafika złożona z HTML/CSS (marka: gradient neutral-950→800, akcent `#ff2b17`) i wyrenderowana przez headless-browser screenshot + doprowadzona do dokładnych 1200×630 przez GDI+; podłączona jako domyślny fallback `og_image_url` w `PlStronyInternetowe.tsx` (CMS może nadpisać). Zweryfikowane na żywo w przeglądarce: `og:image`, `twitter:card=summary_large_image`, `twitter:image` aktywne. `docs/sales/plan-kampanii-pl.md` zaktualizowany (status ✅, usunięty punkt z „do zrobienia zewnętrznie”). Build ✅ ✅
- 2026-09-20 — Przygotowany plan kampanii Google Search dla `/pl/strony-internetowe-uk` (`docs/sales/plan-kampanii-pl.md`) — jedna kampania, 4 grupy reklam (ogólna/branding językowe/branżowe long-tail/cena), słowa kluczowe + wykluczenia, RSA (15 nagłówków/4 opisy z treści realnej LP), rozszerzenia (sitelinks do `#portfolio`/`#contact-form`/`/calculate`, callouts, structured snippets, call extension z realnym numerem `+447882799050`; location extension świadomie pominięty — kampania ogólnokrajowa, nie lokalna), targeting UK/polski język, budżet startowy £600/mies., tracking oparty o istniejące zdarzenia `dataLayer` (`lead_created`, `phone_click` — zero zmian w kodzie). Wzorowane na konwencji `docs/sales/plan-kampanii-ni.md` ✅
- 2026-09-17 — SEO/wydajność audyt `/pl/strony-internetowe-uk`, ciąg dalszy (P1): dodane JSON-LD (`Service`+`FAQPage`+`BreadcrumbList`, `@graph`, z tej samej tablicy `faqItems` co UI) na obu LP, uzupełnione OG (`og:type`/`og:url`) + Twitter Cards (warunkowy `og:image`/`twitter:image` przez nowe pole CMS `og_image_url` — czeka na realny plik graficzny), FAQ przestało być usuwane z DOM przy zamknięciu (`{open && ...}` → zawsze renderowane, ukrywane klasą `hidden`), zdjęcie założyciela dostało `width`/`height`/`loading="lazy"`/lepszy `alt` (sama kompresja pliku 1.79 MB odłożona — to lokalny, niewersjonowany upload CMS, wymaga re-uploadu przez Filament), dodany `config/ziggy.php` (`except: filament.*, admin.*`) ograniczający publiczny payload Ziggy — zweryfikowane grepem i empirycznie w przeglądarce (Playwright). Testy Landing 27/27 ✅, build ✅, lint bez nowych błędów ✅. P2 (SSR, cache HTTP, ukrycie wersji serwera, klaster treści) pozostaje zaplanowane w `.github/live-docs/current-task.md` ✅
- 2026-09-17 — SEO/wydajność audyt `/pl/strony-internetowe-uk` (`docs/reports/seo-and-performence-raport.md`) — wdrożone P0: usunięty sitewide double-branding `<title>` (Welcome/Kalkulator/Portfolio/Services/oba LP), usunięty zduplikowany hreflang+canonical po stronie klienta na obu LP, hreflang PL↔EN usunięty całkowicie z `app.blade.php` (różne rynki, decyzja użytkownika), naprawiony pusty `tel:` na obu LP (`contact_phone` CMS, `+447882799050` fallback — ujawniony też ukryty duplikat błędu na PL LP), dodany `lastmod` do sitemap dla stron statycznych. `tests/Feature/Landing/LandingPreLaunchTest.php` zaktualizowany pod nowe zachowanie — 14/14 ✅, regresja Landing (PL/EN controller + follow-up) 25/25 ✅, `npm run build` ✅, pint ✅. P1/P2 zaplanowane w `.github/live-docs/current-task.md` (JSON-LD, OG/Twitter, FAQ w HTML, kompresja zdjęcia, ograniczenie payloadu Ziggy, SSR, cache HTTP, ukrycie wersji serwera, klaster treści) ✅
- 2026-09-15 — Naprawiono krytyczną lukę: 2FA nie było egzekwowane przy logowaniu (login przechodził mimo aktywnego 2FA). Migracja z custom TOTP (`google_2fa_secret`/`two_factor_enabled`) na natywny `Filament\Auth\MultiFactor\App\AppAuthentication` (kolumny `app_authentication_secret`/`app_authentication_recovery_codes`), zarejestrowany w `AdminPanelProvider::multiFactorAuthentication()` — Filament teraz wymusza TOTP challenge w `Login` page. `AccountProfilePage` deleguje zarządzanie 2FA do natywnego providera (usunięto `EnableTwoFactorAction`/`DisableTwoFactorAction`); 10 testów PHPUnit (w tym regresja logowania z/bez 2FA) ✅
- 2026-09-06 — Moduł SEO: Google Rank Checker (`/tools/google-rank-checker`) — provider abstraction (`SerpProviderInterface`, manual stub + DataForSEO), `CheckGoogleRankingAction`, `DomainNormalizer`, Filament read-only wgląd, tłumaczenia pl/en/pt, 12 testów PHPUnit; pełny raport: `.github/completed-tasks/2026-09-06 - Modul SEO Google Rank Checker.md` ✅
- 2026-07-18 — Utworzono raport faktycznego panelu administratora Filament w `docs/admin-panel-report.md`; zrodla: `app/Filament`, `AdminPanelProvider`, trasy admin/calendar, User access i permission registry ✅
- 2026-07-01 — Moduł konta admina `/admin/account-profile-page`: UpdateAdminProfileAction, ChangePasswordAction, 3-sekcyjna strona Filament; tłumaczenia PL/EN/PT (2FA sekcja przebudowana 2026-09-15, patrz wyżej) ✅
- 2026-06-29 — Naprawiono zakres flat config ESLint: 11 809 fałszywych błędów z `vendor` i assetów Filament usunięto przez precyzyjne ignore; reguły React ograniczono do frontendu, skrypty lint ujednolicono, kod aplikacji bez autofixu ✅
- 2026-06-29 — Przywrócono wielojęzyczny `meta description` strony głównej (PL/EN/PT); jedno źródło w `lang/{locale}/seo.php`, fallback tylko `home`, deduplikacja Inertia i testy locale ✅
- 2026-06-13 — Multi-currency end-to-end (Fazy 1–8): GBP/EUR/PLN ✅
  - `config/currencies.php`, `CurrencyResolver`, `MoneyFormatter`, `CurrencyPriceCalculator`
  - `useCurrency()` hook, `formatCurrency()` util, `servicePrice.js`
  - Inertia shared props: `currency`, `available_currencies`, `currency_settings`
  - `DefaultsCurrency` trait na Lead/Client/Project/Quote/Invoice/Contract/Payment
  - Multi-currency domain price list (`tld + currency` unique)
  - `plan_prices` table + `PlanService` per waluta + Stripe Price ID per waluta
  - Raporty, PDF, e-maile, dashboardy per waluta
  - Publiczny price book usług i kalkulatora (`GBP/EUR/PLN`)
  - Seedery i frontend oczyszczone z hardcoded `£`
- 2026-06-01 — `EnsureOpHandleAction` — cache OP customer handle w `clients.op_handle` ✅
- 2026-06-01 — DNS API fix + `Dns.jsx` CRUD + `DomainPortalTest` ✅
- 2026-05-18 — Google Calendar Hardening GC-1–GC-5 ✅
- 2026-05-17 — Multi-tenancy unification, ClientPortalAccess refactor, T1–T5 ✅

## Dostępne funkcje domenowe (portal klienta)
| Funkcja | Status |
|---------|--------|
| Zakup domeny (search → order → Stripe → rejestracja OP) | ✅ |
| Edycja nameserverów (modal, 1–5 NS) | ✅ |
| Zarządzanie DNS (A/AAAA/CNAME/MX/TXT/NS/SRV) | ✅ |
| Sandbox integration tests (OpenProvider) | ✅ |

## Open Technical Debt

- Test harness `CostCalculatorV2`: 4 istniejące testy Vitest wymagają kontekstu Inertia po dodaniu `useCurrency` (`usePage must be used within the Inertia component`).
- Lint TypeScript: `resources/js/Pages/Services/ServicePage.tsx` nie jest jeszcze objęty parserem ani skryptem ESLint.

**Agent Instructions:** Update this file after every major task completion.
