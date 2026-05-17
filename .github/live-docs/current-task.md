# Current Task

**Status:** Done

**Task Title:** Technical Debt Cleanup Sprint (T1–T5)
**Requested by:** User (2026-05-17)
**Completed:** 2026-05-17

**Results:**
- T1 ✅ — Usunięto `app/Services/LandingPage/LeadCaptureService.php` (martwy kod, 0 referencji)
- T2 ✅ — Komentarze ochronne w `BriefingTemplate.php` + `SalesOfferTemplate.php`
- T3 ✅ — `GlobalTemplateVisibilityTest.php` — 9/9 testów zielonych
  - Bonus: naprawiono bug produkcyjny `BriefingTemplate::scopeForBusiness()` (type hint `?int` → `string|null`)
- T4 ✅ — Już pokryte — `PublicLeadCaptureTest.php` ma 20+ testów HTTP (pełny stack)
- T5 ✅ — 54 brakujące klucze znalezione i dodane do 3 języków:
  - `landing_pages.messages.*` (8 kluczy) + `landing_pages.errors.{invalid_section_type,invalid_video_domain,plan_limit_reached}`
  - `landing_pages.ai.errors.plan_limit_reached` + `landing_pages.validation.*` (3 klucze)
  - `business.onboarding_required` + `notifications.lead_source_body`
  - Nowe pliki: `lang/{en,pl,pt}/sales_offers.php`

**Test Suite:** 260/260 ✅ (było 251, +9 z T3)

**Last Updated:** 2026-05-17