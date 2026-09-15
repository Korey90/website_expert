# Current Sprint

**Sprint Name:** Moduł SEO — Google Rank Checker
**Date:** 2026-09-06 (ukończony jednodniowy sprint)
**Goal:** Publiczne narzędzie `/tools/google-rank-checker` + fundament domeny biznesowej "Search Ranking" pod przyszły monitoring pozycji klientów.
**Źródło planu:** `docs/plans/seo-module-tool-plan.md`
**Status:** ✅ DONE — pełny raport: `.github/completed-tasks/2026-09-06 - Modul SEO Google Rank Checker.md`

- [x] Migracja `search_ranking_checks` + model + factory
- [x] `SerpProviderInterface` + `ManualSerpProviderService` (domyślny stub) + `DataForSeoProviderService`
- [x] `DomainNormalizer` (normalizacja + matching subdomen), pokryty testami
- [x] `CheckGoogleRankingAction` (jedna reużywalna funkcja biznesowa: cache → provider → matching → zapis)
- [x] `GoogleRankCheckerController` + trasy (`throttle:10,60`) + sitemap
- [x] Frontend: `Pages/Tools/GoogleRankChecker.tsx`, `Components/Tools/{GoogleRankCheckerForm,GoogleRankResult}.tsx`
- [x] Tłumaczenia `lang/{en,pl,pt}/rank_checker.php` + `rank_checker_translations` w `HandleInertiaRequests`
- [x] Filament `SearchRankingCheckResource` (tylko odczyt, grupa "SEO")
- [x] Testy: `DomainNormalizerTest` (4), `GoogleRankCheckerTest` (8) — 12/12 ✅
- [x] `phpunit.xml`: izolacja reCAPTCHA w testach (`RECAPTCHA_SECRET_KEY=""`)
- [x] Adnotacje ✅/⚠️/⏳ przy wszystkich 50 sekcjach `docs/plans/seo-module-tool-plan.md`

**Poza zakresem MVP (świadomie, sekcja 45 planu):** historia pozycji, wykresy, konkurenci, plany/limity billingowe, harmonogram automatyczny, Cloudflare Turnstile, autocomplete lokalizacji.

---

# Poprzedni Sprint — Front Redesign (2026-07-19)
**Date:** 2026-07-19 → ongoing
**Goal:** Dwie oddzielne ścieżki sprzedażowe: polski LP dla polskich przedsiębiorców w UK (PRIORYTET 1) i angielski LP dla klientów anglojęzycznych (PRIORYTET 2). Integracja CRM z locale/audience/GCLID/UTM, follow-up automation, mobile UX, przebudowa strony głównej.
**Źródło planu:** `docs/plik.md` (sekcje 1–8 + update od lini 810: segment polski w UK)

---

## Priorytety wykonania (zaktualizowane po nowej sekcji plik.md)
```
1. Polski LP /pl/strony-internetowe-uk          ← NOWY PRIORYTET 1
2. Polski formularz + CRM (locale=pl, audience)
3. Polskie e-mail potwierdzające + follow-up
4. Angielski LP /en/web-design-northern-ireland
5. Przebudowa strony głównej /
6. Polskie podstrony usług + treści SEO
7. Kalkulator
```

---

## Sprint 1 — Polski Landing Page (NOWY PRIORYTET 1)
**URL:** `/pl/strony-internetowe-uk`
**Szacunek:** ~2–3 dni
**Agenci:** @BackendEngineer, @FrontendEngineer, @DatabaseEngineer

### Backend
- [x] PL-B1 — Migracja: pola `gclid`, `gbraid`, `wbraid`, `source_type`, `device_type`, `locale`, `audience`, `landing_page_slug` ✅
- [x] PL-B2 — Route `/pl/strony-internetowe-uk` + `PlLandingController` ✅
- [x] PL-B3 — `StorePlLandingRequest` — walidacja 6-polowego formularza PL ✅
- [x] PL-B4 — `PlLandingController::submit()` — zapis locale=pl, audience, source_type, gclid/UTM ✅
- [x] PL-B5 — `LeadEnquiryConfirmationMail` — e-mail potwierdzający PO POLSKU (queued) ✅
- [x] PL-B6 — `NotifyLeadOwnerListener` (istniejący) obsługuje powiadomienie admina ✅

### Frontend — 10 sekcji (wersja polska)
- [x] PL-F1 — `PlStronyInternetowe.tsx` — layout, SEO PL, canonical, hreflang ✅
- [x] PL-F2 — Sekcja 1: Hero ✅
- [x] PL-F3 — Sekcja 2: "Polska obsługa. Brytyjski standard strony." ✅
- [x] PL-F4 — Sekcja 3: Dla kogo (10 branż) ✅
- [x] PL-F5 — Sekcja 4: Pakiet od £799 ✅
- [x] PL-F6 — Sekcja 5: Portfolio PL ✅
- [x] PL-F7 — Sekcja 6: Strona PL czy EN? ✅
- [x] PL-F8 — Sekcja 7: O założycielu + Jasne zasady (8 punktów) ✅
- [x] PL-F9 — Sekcja 8: Proces 4-etapowy ✅
- [x] PL-F10 — Sekcja 9: FAQ PL (6 pytań) ✅
- [x] PL-F11 — Sekcja 10: Formularz 6-pól + pytanie kwalifikujące + success state ✅
- [x] PL-F12 — GTM: `lead_created`, `landing_page_view`, `primary_cta_click`, `form_start` ✅
- [x] PL-F14 — Sticky mobile CTA bar ✅

### Testy
- [ ] PL-T1 — PHPUnit: `StorePlLandingLeadActionTest` — happy path, locale=pl, audience, gclid
- [ ] PL-T2 — PHPUnit: pola audience/source_type/landing_page zapisywane poprawnie

- [x] PL-T1 — 11/11 testów PHPUnit ✅ (happy path, locale=pl, audience, gclid, walidacja, email)

---

## Sprint 2 — Angielski Landing Page (Priorytet 4 z plik.md)
**URL:** `/en/web-design-northern-ireland`
**Szacunek:** ~1–2 dni
**Agenci:** @FrontendEngineer, @BackendEngineer

> Infrastruktura backendowa (migracja, Action) powstaje już w Sprint 1 — tutaj tylko nowe widoki.

### Backend (delta od Sprint 1)
- [x] EN-B1 — Route `/en/web-design-northern-ireland` + `EnLandingController` ✅
- [x] EN-B2 — `StoreEnLandingRequest` — walidacja 5-polowego formularza EN ✅
- [x] EN-B3 — `EnLandingController::submit()` reuse LeadService z locale=en, audience=ni_business ✅
- [x] EN-B4 — `LeadEnquiryConfirmationMail` z mailLocale='en' ✅

### Frontend — 9 sekcji (wersja angielska)
- [x] EN-F1 — `EnWebDesign.tsx` — layout, SEO EN, canonical, hreflang ✅
- [x] EN-F2 — Sekcja 1: Hero EN ✅
- [x] EN-F3 — Sekcja 2: Problem (3 karty) ✅
- [x] EN-F4 — Sekcja 3: Pakiet od £799 ✅
- [x] EN-F5 — Sekcja 4: Portfolio EN ✅
- [x] EN-F6 — Sekcja 5: O założycielu EN + Clear terms ✅
- [x] EN-F7 — Sekcja 6: Proces 4-etapowy ✅
- [x] EN-F8 — Sekcja 7: Opinie (3 placeholdery) ✅
- [x] EN-F9 — Sekcja 8: FAQ EN (6 pytań) ✅
- [x] EN-F10 — Sekcja 9: Formularz 5-pól + success state ✅
- [x] EN-F11 — GTM events + link do PL LP pod formularzem ✅
- [x] EN-F12 — Sticky mobile CTA bar EN ✅

### Testy
- [x] EN-T1 — 10/10 testów PHPUnit ✅

---

## Sprint 3 — Follow-up Automation + Mobile Review
**Status:** ✅ DONE

- [x] AUT-1 — `ScheduleLandingLeadFollowUpJob` — 24h follow-up task ✅
- [x] AUT-2 — 72h reminder task (w tym samym jobbie) ✅
- [x] AUT-3 — 8 testów PHPUnit: `LandingLeadFollowUpTest` ✅
- [x] LANG-1 — Oba LP w sitemap.xml (`/en/web-design-northern-ireland`, `/pl/strony-internetowe-uk`) ✅
- [x] Migracja `2026_07_19_000002` — widen `lead_activities.type` dla SQLite ✅

---

## Sprint 4 — Przebudowa strony głównej (Priorytet 5)
**Szacunek:** ~1–2 dni
**Agenci:** @FrontendEngineer

- [ ] HOME-1 — Nowa kolejność sekcji: Hero → Trust strip → 3 rozwiązania → Portfolio → O założycielu → Proces → Opinie → CTA → FAQ → Footer
- [ ] HOME-2 — 3 kategorie usług (Business websites £799 / E-commerce £2,999 / Custom £5,999)
- [ ] HOME-3 — SEO/Ads/hosting → sekcja "Ongoing growth and support"
- [ ] HOME-4 — Jedno CTA "Get a fixed-price proposal" + drugorzędne "Book a free 20-minute call"
- [ ] HOME-5 — Baner dla polskich klientów w nawigacji: "Prowadzisz firmę w UK? → /pl/strony-internetowe-uk"
- [ ] HOME-6 — Na polskiej wersji: link do angielskiego LP dla klientów anglojęzycznych

---

## Sprint 5 — URL-e językowe + hreflang + sitemap
**Szacunek:** ~1 dzień
**Agenci:** @BackendEngineer

- [ ] LANG-1 — Oba LP w sitemap.xml (en + pl)
- [ ] LANG-2 — `hreflang` tagi: `x-default`, `en`, `pl` w Head obu LP i strony głównej
- [ ] LANG-3 — `canonical` do własnego URL-a per locale
- [ ] LANG-4 — Weryfikacja: brak auto-redirect według IP/geolokalizacji

---

## Sprint 6 — Testy pre-launch + accessibility
**Szacunek:** ~1 dzień
**Agenci:** @TestingEngineer, @FrontendEngineer

- [ ] TEST-1 — Testy formularza PL + EN: happy path, błędne pola, podwójny submit
- [ ] TEST-2 — Jeden lead, jedna konwersja, jeden e-mail (brak duplikatów)
- [ ] TEST-3 — Mobile: iPhone Safari, Android Chrome, tablet, desktop
- [ ] TEST-4 — Dostępność: klawiatura, focus, etykiety, kontrast, H1-H6
- [ ] TEST-5 — Wydajność: AVIF/WebP, lazy loading, brak slidera/video w hero

---

## Status
**Build:** ✅ (przed startem)
**Tests:** 321/321 ✅ (stan wyjściowy)
**Nowe pola Lead:** `gclid`, `gbraid`, `wbraid`, `source_type`, `device_type`, `audience` — do dodania w Sprint 1

---


**Date:** 2026-06-01 → bieżący
**Goal:** Umożliwienie klientowi zarządzania domeną z portalu — edycja nameserverów i pełny CRUD rekordów DNS przez OpenProvider API

## Backlog

### To Do
- (brak)

### In Progress
- (brak)

### Done
- [x] DOM-1 — Naprawa sandbox URL OpenProvider (`api.cte` → `api.sandbox.openprovider.nl:8480`) — 2026-06-01
- [x] DOM-2 — Izolacja credentiali sandbox (`op_sandbox_username` / `op_sandbox_password`) — 2026-06-01
- [x] DOM-3 — Fix formatu telefonu OP (`country_code` bez `+`, `area_code` = `'0'`) — 2026-06-01
- [x] DOM-4 — Fix pola adresu (`zip_code` → `zipcode`) — 2026-06-01
- [x] DOM-5 — Obsługa `code 10` (Registry not reachable — kolejkowanie) — 2026-06-01
- [x] DOM-6 — `DnsRecord` DTO + 4 metody DNS w `DomainRegistrarInterface` — 2026-06-01
- [x] DOM-7 — Implementacja DNS w `OpenProviderRegistrarService` (GET/POST/PUT/DELETE `/dns/zones/`) — 2026-06-01
- [x] DOM-8 — `FetchDnsRecordsAction`, `SaveDnsRecordAction`, `DeleteDnsRecordAction` — 2026-06-01
- [x] DOM-9 — `DomainDnsController` (index, store, update, destroy) — 2026-06-01
- [x] DOM-10 — Route `updateNameservers` + 4 trasy DNS w `routes/web.php` — 2026-06-01
- [x] DOM-11 — `DomainController::updateNameservers()` — 2026-06-01
- [x] DOM-12 — `Show.jsx` — modal edycji nameserverów + link "Manage DNS →" — 2026-06-01
- [x] DOM-13 — `Dns.jsx` — nowa strona: tabela DNS + modal add/edit + inline delete — 2026-06-01
- [x] DOM-14 — Weryfikacja: 321/321 testów ✅, build OK, route:list OK — 2026-06-01

## Status
**Translation Status:** ✅ Inline w JSX (EN/PL/PT)
**Test Coverage:** 321/321 ✅
**Multi-Tenancy Compliance:** 100% ✅
**Build:** ✅ brak błędów TS/JS

---

# Poprzedni Sprint — Google Calendar Hardening (2026-05-18)

- [x] GC-1 — Smart type detection (holiday/birthday → `reminder`)
- [x] GC-2 — Konfigurowalny zakres dat importu (modal z DatePicker)
- [x] GC-3 — Paginacja listy kalendarzy (`nextPageToken` loop)
- [x] GC-4 — Detekcja braku `refresh_token` + persistent warning z reconnect
- [x] GC-5 — Surface błędów kalendarzy do UI (warning notification)
- [x] GC-0 — Activity History, Sync All, Import multi-calendar, wire:ignore fix
- [x] T1–T5, multi-tenancy, ClientPortalAccess, ToggleColumn — 2026-05-17