# Plan rozwoju projektu WebsiteExpert

> Ostatnia aktualizacja: 20.03.2026
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
| 6 | Portfolio — 3 projekty (seeder) | 🟡 dane dummy, bez prawdziwych obrazków |
| 7 | Usługi — wizytówki, e-com, SEO, Hosting, **Tworzenie treści**, **Audyty bezp./wydajności**, Google Ads, **Pixel/Meta Ads** | ✅ wszystkie 8 usług w seederze |
| 8 | Formularz kontaktowy — imię, email, telefon, wiadomość, rodzaj projektu, NIP/firma, preferowany termin | ✅ ContactController + NewLeadMail + zapis Lead do DB |
| 9 | Kalkulator → dane do bazy / lead | ✅ CalculatorLeadController + CostCalculator.jsx handleCalcSubmit |
| 10 | PT translations w seederze SiteSection (`extra` JSON) | ✅ wszystkie 13 sekcji trilingual (EN+PL+PT) |
| 11 | Language switcher — redirect i wyświetlanie w PT | 🟡 dane w seederze gotowe, komponenty React wymagają podłączenia kluczy `_pt` |

---

## B. PANEL ADMINISTRACYJNY

| # | Wymaganie | Stan |
|---|-----------|------|
| 1 | ACL — pełen system ról + interface zarządzania | ✅ spatie/permission + RoleResource + UserResource |
| 2 | CRM — dane klienta (rynek UK: CH number, VAT, adresy, notatki) | ✅ ClientResource |
| 3 | Pipeline sprzedażowy z edytowalnymi etapami | ✅ PipelineStageResource CRUD + Kanban widok |
| 4 | **Client Portal (Must Have)** — klient loguje się i śledzi projekt | ✅ PortalController + 6 tras + PortalLayout.jsx + 5 stron React (Dashboard, Projects, Project, Invoices, Quotes) |
| 5 | Projekty — fazy, zadania, załączniki, wiadomości | ✅ modele: ProjectPhase, ProjectTask, ProjectFile, ProjectMessage |
| 6 | Task board (Kanban) w projekcie | 🟡 model istnieje, brak weryfikacji widoku board w Filament RelationManager |
| 7 | Faktury — wielowalutowe, PDF | ✅ InvoiceResource + dompdf |
| 8 | Śledzenie płatności | ✅ Payment model + Invoice statuses |
| 9 | Kosztorysy/oferty z panelu | ✅ QuoteResource |
| 10 | **Stripe integracja** — link do płatności, webhook | ✅ StripeWebhookController + config/services.php stripe + CSRF exempt |
| 11 | Wiadomości per projekt (klient ↔ agencja) | 🟡 Portal: czat gotowy; Filament admin: RelationManager bez dedykowanego widoku czatu |
| 12 | **Automatyzacje** — definicja reguł + wykonanie | ✅ AutomationEventListener + ProcessAutomationJob (queue, tries=3) |
| 13 | **Generowanie raportów** — PDF, Excel, CSV, HTML | ✅ ReportController + 4 Blade views + 12 tras (leads/invoices/projects × 4 formaty) |
| 14 | **Quick actions na Dashboard** | ✅ QuickActionsWidget — 6 akcji (sort=0) |
| 15 | Edycja cen kalkulatora z panelu | ✅ CalculatorPricingResource |
| 16 | Kalkulator → lead w panelu | ✅ CalculatorLeadController, dane kalkulatora w Lead.calculator_data |
| 17 | CMS stron (regulamin, cookies, itp.) | ✅ PageResource + CmsPage.jsx + trilingual seeder |
| 18 | SiteSection CMS z multilingual | ✅ wszystkie 13 sekcji EN+PL+PT |
| 19 | Powiadomienia email — konfigurowalne zdarzenia | ✅ NewLeadMail, InvoiceSentMail, QuoteSentMail, ProjectStatusMail + AutomationEventListener |
| 20 | Wykresy/dashboardy z danymi | ✅ RevenueChartWidget (bar), LeadsBySourceWidget (doughnut), ProjectStatusWidget (bar) |

---

## C. PRIORYTETY

### 🔴 Krytyczne (blokujące działanie biznesowe)

- [x] 1. **`/contact` — implementacja** — zapis do DB jako `Lead` + wysłanie maila (Laravel Mail)
- [x] 2. **Kalkulator → Lead** — `POST /calculator-lead` z danymi kalkulatora + zapis do bazy
- [x] 3. **Client Portal** — PortalLayout.jsx, /portal/* trasy, Dashboard, Projects, Project (czat), Invoices, Quotes
- [x] 4. **Stripe webhooks** — `POST /stripe/webhook`, StripeWebhookController, aktualizacja statusu płatności

### 🟠 Ważne (funkcjonalność panelu)

- [x] 5. **Generowanie raportów** — ReportController + PDF/Excel/CSV/HTML + 12 tras
- [x] 6. **Automation engine** — AutomationEventListener + ProcessAutomationJob (queue)
- [x] 7. **PipelineStageResource** — CRUD etapów pipeline z poziomu panelu
- [x] 8. **Quick actions na Dashboard** — QuickActionsWidget z 6 akcjami

### 🟡 Uzupełniające

- [x] 9. **SiteSectionSeeder + komponenty — PT translations** — wszystkie 13 sekcji EN+PL+PT ✅
- [x] 10. **Services seeder** — wszystkie 8 usług (+ Treści, Audyty, Meta Ads) ✅
- [x] 11. **PageResource — zakładka PT** — dynamicznie z config('languages') ✅
- [x] 12. **Wykresy na dashboardzie** — Revenue (bar), Leads by source (doughnut), Projects by status (bar) ✅
- [x] 13. **Email sending** — 4 Mailable classes (NewLeadMail, InvoiceSentMail, QuoteSentMail, ProjectStatusMail) ✅

---

## D. POZOSTAŁE DO ZROBIENIA

| # | Wymaganie | Priorytet |
|---|-----------|-----------|
| 1 | Portfolio — prawdziwe obrazki projektów | 🟡 niski |
| 2 | React komponenty — renderowanie treści PT (klucze `_pt` z `extra` JSON) | 🟡 średni |
| 3 | Filament: widok czatu (RelationManager) dla wiadomości w panelu admina | 🟡 niski |
| 4 | Task board (Kanban) w Filament ProjectResource | 🟡 niski |
