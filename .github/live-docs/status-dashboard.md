# WebsiteExpert - Project Status Dashboard

**Last Full Validation:** 2026-06-14
**Last Targeted Validation:** 2026-07-01 — Admin Account Profile module (migration, Actions, Filament Page, 2FA)

## Health Checks
- **Code Quality (Pint + ESLint):** ✅ `npx eslint .` i `npm run lint` przechodzą; vendor/generated wyłączone jawnie
- **Translations (pl/en/pt):** ✅ Homepage SEO w `lang/{locale}/seo.php`; pozostały frontend inline EN/PL/PT
- **Tests:** SEO: PHPUnit 3/3 (51 asercji), Vitest 3/3 ✅; pełny Vitest: 12 passed, 4 `CostCalculatorV2` failed ⚠️
- **Multi-Tenancy Compliance:** 100% ✅
- **Security Review:** ✅ OK
- **Build (Vite):** ✅ Brak błędów TS/JS
- **Hardcoded £/GBP scan:** ✅ Brak trafień w app/, resources/, seeders/

## Recent Activity
- 2026-07-01 — Moduł konta admina `/admin/account-profile-page`: UpdateAdminProfileAction, ChangePasswordAction, EnableTwoFactorAction (TOTP QR), DisableTwoFactorAction; migracja `two_factor_enabled`/`google_2fa_secret` (encrypted); 3-sekcyjna strona Filament; tłumaczenia PL/EN/PT; 10 testów PHPUnit ✅
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
