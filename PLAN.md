# Plan rozwoju projektu WebsiteExpert

> Ostatnia aktualizacja: 19.03.2026
> Legenda: ✅ Gotowe | 🟡 Częściowo | ❌ Brakuje

---

## A. FRONTEND / STRONA MARKETINGOWA

| # | Wymaganie | Stan |
|---|-----------|------|
| 1 | Brand colours (czerwień, aggressive startup) | ✅ |
| 2 | Dark/light mode | ✅ |
| 3 | Fonty Inter + Syne (PL/EN/PT) | ✅ |
| 4 | Sekcje: Hero, About, Services, Portfolio, CostCalculator, TrustStrip, Contact, Footer, Navbar, CtaBanner | ✅ |
| 5 | Testimonials — combo (cytat + zdjęcie) z karuzelą, 5-6 pozycji | ✅ komponent gotowy |
| 6 | Portfolio — 3 projekty (seeder) | 🟡 dane dummy, bez obrazków |
| 7 | Usługi — wizytówki, e-com, SEO, Hosting, **Tworzenie treści**, **Audyty bezp./wydajności**, Google Ads, **Pixel/Meta Ads** | 🟡 6/8 w seederze (brak: treści, audyty, pixel ads) |
| 8 | Formularz kontaktowy — imię, email, telefon, wiadomość, rodzaj projektu, NIP/firma, preferowany termin | 🟡 komponent istnieje, `/contact` POST = TODO stub (nie zapisuje, nie wysyła maila) |
| 9 | Kalkulator → dane do bazy / lead | ❌ wynik tylko w UI, brak zapisu do DB |
| 10 | PT translations w seederze SiteSection (`extra` JSON) | ❌ wszystkie pola `_en`/`_pl`, brak `_pt` |
| 11 | Language switcher — redirect i wyświetlanie w PT | 🟡 przełącznik działa, ale treści tylko EN+PL |

---

## B. PANEL ADMINISTRACYJNY

| # | Wymaganie | Stan |
|---|-----------|------|
| 1 | ACL — pełen system ról + interface zarządzania | ✅ spatie/permission + RoleResource + UserResource |
| 2 | CRM — dane klienta (rynek UK: CH number, VAT, adresy, notatki) | ✅ ClientResource |
| 3 | Pipeline sprzedażowy z edytowalnymi etapami | 🟡 PipelinePage (widok Kanban), PipelineStage model — brak `PipelineStageResource` do zarządzania etapami |
| 4 | **Client Portal (Must Have)** — klient loguje się i śledzi projekt | ❌ `portal_user_id` i `portal_token` w modelach, ale brak: tras, stron React, layoutu portalu |
| 5 | Projekty — fazy, zadania, załączniki, wiadomości | ✅ modele: ProjectPhase, ProjectTask, ProjectFile, ProjectMessage |
| 6 | Task board (Kanban) w projekcie | 🟡 model istnieje, brak weryfikacji czy Filament RelationManager ma widok board |
| 7 | Faktury — wielowalutowe, PDF | ✅ InvoiceResource + dompdf |
| 8 | Śledzenie płatności | ✅ Payment model + Invoice statuses |
| 9 | Kosztorysy/oferty z panelu | ✅ QuoteResource |
| 10 | **Stripe integracja** — link do płatności, webhook | 🟡 `stripe-php` zainstalowany, `stripe_payment_link` w Invoice — brak webhook handlera, brak faktycznego flow płatności |
| 11 | Wiadomości per projekt (klient ↔ agencja) | 🟡 ProjectMessage model — brak UI (RelationManager z widokiem czatu?) |
| 12 | **Automatyzacje** — definicja reguł + wykonanie | 🟡 AutomationRuleResource (CRUD reguł) — brak silnika: Listenery/Jobs które faktycznie je uruchamiają |
| 13 | **Generowanie raportów** — PDF, Excel, CSV, HTML | ❌ pakiety zainstalowane (dompdf, phpspreadsheet) — brak: ReportController, tras, UI w Filament |
| 14 | **Quick actions na Dashboard** | ❌ 4 widgety stat, brak szybkich akcji |
| 15 | Edycja cen kalkulatora z panelu | ✅ CalculatorPricingResource |
| 16 | Kalkulator → lead w panelu | ❌ `calculator_data` w Lead, ale frontend nie wysyła danych do API |
| 17 | CMS stron (regulamin, cookies, itp.) | ✅ PageResource + CmsPage.jsx + trilingual seeder |
| 18 | SiteSection CMS z multilingual | ✅ ale bez `pt` w treściach |
| 19 | Powiadomienia email — konfigurowalne zdarzenia | 🟡 EmailTemplate + AutomationRule w DB, brak faktycznego Mailable/Queue |
| 20 | Wykresy/dashboardy z danymi | 🟡 4 widgety, brak wykresów (chart library) |

---

## C. PRIORYTETY

### 🔴 Krytyczne (blokujące działanie biznesowe)

- [ ] 1. **`/contact` — implementacja** — zapis do DB jako `Lead` + wysłanie maila (Laravel Mail)
- [ ] 2. **Kalkulator → Lead** — `POST /api/leads` z danymi kalkulatora + zapis do bazy
- [ ] 3. **Client Portal** — osobny layout React (`/portal/*`), logowanie przez e-mail / token, widok projektów, faz, wiadomości
- [ ] 4. **Stripe webhooks** — route `POST /stripe/webhook`, handler `StripeWebhookController`, aktualizacja statusu płatności

### 🟠 Ważne (funkcjonalność panelu)

- [ ] 5. **Generowanie raportów** — `ReportController` + eksport do PDF/Excel/CSV/HTML + przycisk w Filament
- [ ] 6. **Automation engine** — `AutomationEventListener` + `ProcessAutomationJob` obsługujące zdefiniowane reguły
- [ ] 7. **PipelineStageResource** — CRUD etapów pipeline z poziomu panelu
- [ ] 8. **Quick actions na Dashboard** — widżet z przyciskami: "Nowy lead", "Nowa faktura", "Nowy projekt"

### 🟡 Uzupełniające

- [ ] 9. **SiteSectionSeeder + komponenty — PT translations** — dodanie `_pt` kluczy we wszystkich `extra` JSON
- [ ] 10. **Services seeder** — dodanie: Tworzenie treści, Audyty bezpieczeństwa/wydajności, Pixel/Meta Ads
- [ ] 11. **PageResource — zakładka PT** — trzecia zakładka `pt` w Filament (aktualnie tylko EN+PL)
- [ ] 12. **Wykresy na dashboardzie** — np. `recharts` po stronie React lub natywny Filament chart widget
- [ ] 13. **Email sending** — Mailable classes dla kluczowych zdarzeń (nowy lead, faktura, zmiana statusu)
