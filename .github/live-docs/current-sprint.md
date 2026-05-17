# Current Sprint

**Sprint Name:** Technical Debt Cleanup
**Date:** 2026-05-17 → 2026-05-31
**Goal:** Spłata długu technicznego — martwy kod, testy, tłumaczenia, bezpieczeństwo szablonów globalnych

## Backlog

### To Do
- (brak)

### In Progress
- (brak)

### Done
- [x] T1 — Usunięto `app/Services/LandingPage/LeadCaptureService.php` (martwy kod) — 2026-05-17
- [x] T2 — Komentarze ochronne w `BriefingTemplate` + `SalesOfferTemplate` — 2026-05-17
- [x] T3 — `GlobalTemplateVisibilityTest` (9 testów: BriefingTemplate + SalesOfferTemplate) — 2026-05-17
  - Bonus: naprawiono bug `BriefingTemplate::scopeForBusiness()` (type hint `?int` → `string|null`)
- [x] T4 — Pokryte przez istniejący `PublicLeadCaptureTest.php` (20+ testów HTTP) — 2026-05-17
- [x] T5 — 54 brakujące klucze tłumaczeń dodane w EN/PL/PT + nowe pliki `sales_offers.php` — 2026-05-17
- [x] Multi-tenancy unification (BelongsToTenant na 5 modelach + TenantIsolationTest) — 2026-05-17
- [x] ClientPortalAccess refactor (nowy model, migracja, 9 plików produkcyjnych, 11 testów) — 2026-05-17
- [x] ClientResource: ToggleColumn portal_access w tabeli /admin/clients — 2026-05-17
- [x] MySQL migration uruchomiona — 2026-05-17

## Status
**Translation Status:** ✅ Wszystkie klucze uzupełnione (EN/PL/PT)
**Test Coverage:** 260/260 ✅
**Multi-Tenancy Compliance:** 100% ✅