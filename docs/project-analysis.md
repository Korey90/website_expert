# Analiza projektu — Digital Growth OS (web-dev-app)

> Data analizy: 31.03.2026
> Podstawa: kod źródłowy repozytorium (routes/, app/, database/, resources/js/, lang/, tests/)
> Cel: ocena stanu obecnego pod kątem transformacji w skalowalny SaaS

---

## 1. Feature Inventory

### 1.1 Autentykacja i autoryzacja

| Funkcja | Opis | Kluczowe pliki | Status |
|---------|------|----------------|--------|
| Rejestracja / logowanie | Standard Laravel Breeze, sesja + cookie | `app/Http/Controllers/Auth/`, `routes/auth.php` | zaimplementowane |
| Reset hasła | Email token flow | `Auth/PasswordResetLinkController.php`, `Auth/NewPasswordController.php` | zaimplementowane |
| Weryfikacja emaila | Link z podpisem | `Auth/VerifyEmailController.php` | zaimplementowane |
| Role i uprawnienia | Spatie Permission v7, 4 role, 40+ uprawnień | `AdminSeeder.php`, `User.php` (HasRoles) | zaimplementowane |
| Dostęp do panelu | `canAccessPanel()` — tylko admin/manager/developer | `User.php` | zaimplementowane |
| Portal klienta | Dostęp przez `portal_user_id` na kliencie | `Portal/BasePortalController.php` | zaimplementowane |
| Zmiana profilu | Imię, email, hasło | `ProfileController.php`, `Pages/Profile/` | zaimplementowane |

### 1.2 CRM — Klienci i Leady

| Funkcja | Opis | Kluczowe pliki | Status |
|---------|------|----------------|--------|
| Zarządzanie klientami | Firma, VAT, Companies House, adres, notatki, SoftDeletes | `ClientResource.php`, `Client.php` | zaimplementowane |
| Kontakty klientów | Powiązane z klientem, SoftDeletes | `Contact.php`, sekcja w `ClientResource` | zaimplementowane |
| Zarządzanie leadami | Tytuł, wartość, źródło, budżet, etap, przypisany | `LeadResource.php`, `Lead.php` | zaimplementowane |
| Pipeline Kanban | Widok kolumnowy, drag etapów | `PipelinePage.php`, `filament/pages/pipeline.blade.php` | zaimplementowane |
| Etapy pipeline | CRUD etapów z kolejnością, checklistami | `PipelineStageResource.php`, `PipelineStage.php` | zaimplementowane |
| Notatki do leadów | Pinowalne wpisy, historia | `LeadNote.php`, `PipelinePage.php` (modal) | zaimplementowane |
| Aktywności leadów | Log zdarzeń (zmiana etapu, email, SMS) | `LeadActivity.php`, `ClientActivityListener.php` | zaimplementowane |
| Checklista etapu | Elementy do wykonania per etap | `LeadChecklistItem.php`, `PipelineStageChecklistSeeder.php` | zaimplementowane |
| Konwersja leada w projekt | Lead → Project jedną akcją | `LeadResource` (action), relacja `lead.project` | zaimplementowane |
| Raport konwersji | Współczynnik konwersji per źródło leada | `ConversionReportPage.php` | zaimplementowane |

### 1.3 Projekty

| Funkcja | Opis | Kluczowe pliki | Status |
|---------|------|----------------|--------|
| Zarządzanie projektami | Status, deadline, budżet, typ usługi | `ProjectResource.php`, `Project.php` | zaimplementowane |
| Fazy projektu | Kolejność, status, daty | `ProjectPhase.php`, RelationManager | zaimplementowane |
| Zadania (tasks) | Powiązane z fazą | `ProjectTask.php` | częściowe (model + RelationManager, brak Kanban board) |
| Załączniki | Pliki per projekt | `ProjectFile.php` | zaimplementowane |
| Czat projektu | Wiadomości klient ↔ agencja | `ProjectMessage.php`, `Portal/ProjectController.php` | zaimplementowane |
| Szablony projektów | Reużywalne szablony z fazami | `ProjectTemplate.php`, `ProjectTemplateResource.php` | zaimplementowane |
| Portal tok projektu | Klient widzi fazy, taski, pliki, czat | `Portal/ProjectController.php`, `Pages/Portal/Project.jsx` | zaimplementowane |

### 1.4 Finanse

| Funkcja | Opis | Kluczowe pliki | Status |
|---------|------|----------------|--------|
| Kosztorysy (Quotes) | Wieloliniowe, VAT, discount, status workflow | `QuoteResource.php`, `Quote.php`, `QuoteItem.php` | zaimplementowane |
| Faktury (Invoices) | Numeracja, VAT, PDF export, statusy | `InvoiceResource.php`, `Invoice.php`, `InvoiceItem.php`, `InvoicePdfController.php` | zaimplementowane |
| Płatności | Śledzenie wpłat, powiązanie z fakturą | `Payment.php`, `PaymentResource.php` | zaimplementowane |
| Stripe Checkout | Płatność przez Checkout Session | `Portal/PaymentController.php`, `StripeWebhookController.php` | zaimplementowane |
| PayU | Bramka płatności (sandbox + produkcja) | `PayuService.php`, `Portal/PaymentController.php`, `PayuWebhookController.php` | zaimplementowane |
| Eksport faktur PDF | DomPDF, szablon Blade | `InvoicePdfController.php` | zaimplementowane |
| Kontrakty | Podpis elektroniczny, interpolacja danych | `ContractResource.php`, `Contract.php`, `ContractInterpolationService.php` | zaimplementowane |
| Szablony kontraktów | Wielorazowe szablony z placeholderami | `ContractTemplate.php`, `ContractTemplateResource.php` | zaimplementowane |

### 1.5 Powiadomienia i komunikacja

| Funkcja | Opis | Kluczowe pliki | Status |
|---------|------|----------------|--------|
| Powiadomienia in-app | Panel Filament, custom DatabaseNotification | `DatabaseNotification.php`, `NotificationResource.php`, `CustomDatabaseNotifications.php` (Livewire) | zaimplementowane |
| Szablony emaili | Edytowalne przez panel, podgląd live | `EmailTemplate.php`, `EmailTemplateResource.php`, `EmailTemplatePreviewController.php` | zaimplementowane |
| Maile transakcyjne | Lead, Invoice, Quote, Project, Payment, Portal invite | `app/Mail/` (7 klas) | zaimplementowane |
| SMS przez Twilio | Szablony SMS, bramka przez SmsService | `SmsTemplate.php`, `SmsService.php`, `SmsTemplateResource.php` | zaimplementowane |
| Preferencje komunikacji | Klient ustawia zgody: email transakcyjny/projektowy/marketing + SMS | `Client.php` (4 flagi), `Portal/NotificationController.php` | zaimplementowane |
| Client notification gate | Walidacja preferencji przed wysyłką | `ClientNotificationGate.php` | zaimplementowane |

### 1.6 Automatyzacje

| Funkcja | Opis | Kluczowe pliki | Status |
|---------|------|----------------|--------|
| Reguły automatyzacji | CRUD z triggerem, warunkami, akcjami, delay | `AutomationRule.php`, `AutomationRuleResource.php` | zaimplementowane |
| Eventy trigger | lead.created, lead.stage_changed, project.created/status_changed, invoice.sent/paid/overdue, quote.accepted, contract.created/signed | `AutomationEventListener.php` | zaimplementowane |
| Ewaluacja warunków | Operator =, !=, >, <, contains | `ConditionEvaluator.php` | zaimplementowane |
| Akcje automatyzacji | send_email, send_internal_email, send_sms, notify_admin, add_tag, change_status, create_portal_access | `Automation/Actions/` (7 klas) | zaimplementowane |
| Opóźnienie (delay) | Re-dispatch job z delay_minutes | `ProcessAutomationJob.php` | zaimplementowane |

### 1.7 Panel administracyjny (Filament)

| Funkcja | Opis | Kluczowe pliki | Status |
|---------|------|----------------|--------|
| Dashboard z widgetami | Stats, Revenue Chart, Leads by Source, Active Projects, Deadlines, Overdue Invoices, Stale Leads, Quick Actions | `app/Filament/Widgets/` (13 widgetów) | zaimplementowane |
| Zarządzanie użytkownikami | CRUD, role assignment | `UserResource.php` | zaimplementowane |
| Zarządzanie rolami i uprawnieniami | CRUD Filament | `RoleResource.php`, `PermissionResource.php` | zaimplementowane |
| Ustawienia integracji | SMTP, Twilio | `IntegrationSettingsPage.php` | zaimplementowane |
| Ustawienia płatności | Stripe + PayU, waluta | `PaymentSettingsPage.php` | zaimplementowane |
| Ustawienia trackingu | GTM, GA4, Meta Pixel, Google Ads, Cookie Consent | `TrackingSettingsPage.php` | zaimplementowane |
| Ustawienia prawne | Dane firmy, RODO, polityki | `LegalSettingsPage.php` | zaimplementowane |
| Kalkulator admin | Edycja kroków, cen, stringów | `CalculatorAdminPage.php`, 3 zasoby | zaimplementowane |
| Sesje użytkowników | Podgląd aktywnych sesji | `SessionResource.php` | zaimplementowane |

### 1.8 Strona marketingowa i CMS

| Funkcja | Opis | Kluczowe pliki | Status |
|---------|------|----------------|--------|
| Strona główna DB-driven | Sekcje z bazy (SiteSection), trilingual | `WelcomeController.php`, `Welcome.jsx` | zaimplementowane |
| Sekcje CMS (13) | Hero, About, Services, Process, Portfolio, FAQ, Contact, Footer, Navbar, CTA, TrustStrip, Testimonials, CostCalculator | `SiteSectionResource.php`, `Components/Marketing/` | zaimplementowane |
| Kalkulator wyceny V2 | Multi-step, DB-driven pricing/strings/steps | `KalkulatorController.php`, `CostCalculatorV2.jsx` | zaimplementowane |
| CMS stron statycznych | Privacy policy, Terms, Cookies, Accessibility — translatable | `PageResource.php`, `Page.php`, `CmsPage.jsx` | zaimplementowane |
| Przełącznik języka | Session-based, EN/PL/PT | `routes/web.php` (lang switch route) | zaimplementowane |
| Śledzenie (tracking) | GTM/GA4/Pixel/Google Ads z cookie consent | `HandleInertiaRequests.php`, `useMetaPixel.js`, `useConsent.js` | zaimplementowane |
| Formularz kontaktowy | Zapis nowego leada do CRM | `ContactController.php` | zaimplementowane |
| Lead z kalkulatora | Zapis danych wyceny jako lead | `CalculatorLeadController.php`, `CreateLeadAction.php` | zaimplementowane |

### 1.9 Raportowanie

| Funkcja | Opis | Kluczowe pliki | Status |
|---------|------|----------------|--------|
| Raporty leadów | HTML, PDF (DomPDF), XLSX, CSV | `ReportController.php` | zaimplementowane |
| Raporty faktur | HTML, PDF, XLSX, CSV | `ReportController.php` | zaimplementowane |
| Raporty projektów | HTML, PDF, XLSX, CSV | `ReportController.php` | zaimplementowane |
| Raport konwersji | Konwersja per źródło, wskaźnik, wartość | `ConversionReportPage.php` | zaimplementowane |

### 1.10 Wielojęzyczność (i18n)

| Funkcja | Status |
|---------|--------|
| Języki: EN, PL, PT | zaimplementowane |
| Portal translations przez Inertia share | zaimplementowane |
| SiteSection — Spatie Translatable (title, subtitle, body, button_text) | zaimplementowane |
| Page (CMS) — Spatie Translatable (title, content, meta_title, meta_description) | zaimplementowane |
| Kalkulator — multilingual strings/steps z bazy | częściowe |
| Komponenty React Marketing/ — hardcoded strings | BRAK (do poprawy) |

---

## 2. Architektura backendu

### 2.1 Modele i relacje

```
User (HasRoles — spatie)
  └── Role: admin | manager | developer | client

Client (SoftDeletes)
  ├── portal_user_id → User
  ├── assigned_to → User
  ├── hasMany Contacts
  ├── hasMany Leads
  ├── hasMany Projects
  ├── hasMany Quotes
  ├── hasMany Invoices
  └── hasMany Contracts

Lead (SoftDeletes)
  ├── client_id → Client
  ├── contact_id → Contact
  ├── pipeline_stage_id → PipelineStage
  ├── assigned_to → User
  ├── hasOne Project
  ├── hasMany LeadActivities
  ├── hasMany LeadNotes
  └── hasMany LeadChecklistItems

Project (SoftDeletes)
  ├── client_id → Client
  ├── lead_id → Lead
  ├── template_id → ProjectTemplate
  ├── assigned_to → User
  ├── portal_token (auto-generated)
  ├── hasMany ProjectPhases → hasMany ProjectTasks
  ├── hasMany ProjectFiles
  ├── hasMany ProjectMessages
  └── hasMany Contracts

Quote (SoftDeletes) → hasMany QuoteItems
Invoice (SoftDeletes) → hasMany InvoiceItems, hasMany Payments
Contract (SoftDeletes) → belongsTo ContractTemplate

AutomationRule (trigger_event, conditions[], actions[], delay_minutes)
Setting (key-value store, TTL cache 1 dzień)
SiteSection (HasTranslations — spatie)
Page (HasTranslations — spatie, SoftDeletes)
CalculatorPricing | CalculatorStep | CalculatorString
EmailTemplate | SmsTemplate | ContractTemplate
DatabaseNotification (custom, soft delete zamiast hard delete)
```

**Kluczowe wzorce:**
- `SoftDeletes` powszechnie stosowane; `forceDeleting` cascade w `Client`
- `Model::booted()` — logika inicjalizacji (Project: generowanie portal_token)
- `Setting` jako cache-backed key-value store z 1-dniowym TTL
- Brak globalnych scope'ów na modelach (konieczne do multi-tenancy)

### 2.2 Service Layer

`app/Services/` — tylko **4 serwisy**:

| Serwis | Odpowiedzialność |
|--------|-----------------|
| `ClientNotificationGate` | Sprawdzenie preferencji komunikacyjnych klienta (email typy + SMS) |
| `ContractInterpolationService` | Podmiana placeholderów w treści kontraktu (legal, client, project, contract) |
| `PayuService` | Integracja PayU: OAuth2 token, tworzenie zamówień, weryfikacja IPN |
| `SmsService` | Twilio SMS: konfiguracja z DB, normalizacja E.164, wysyłka |

**Luka krytyczna:** Brak serwisów dla Lead, Invoice, Quote, Project, Automation. Logika biznesowa tych modułów jest rozproszona po kontrolerach i zasobach Filament.

### 2.3 Kontrolery

| Typ | Ocena grubości |
|-----|----------------|
| Publiczne (Welcome, Kalkulator, Contact, CalculatorLead) | CIENKIE |
| Webhooks (Stripe, PayU) | UMIARKOWANE — logika płatności inline |
| Portal (8 kontrolerów) | UMIARKOWANE — `BasePortalController` jako helper |
| ReportController | GRUBY — cała logika eksportu wieloformatowego |
| DashboardController | CIENKI |
| Brak Form Requests poza Auth i ContactRequest | |

### 2.4 Kolejki i eventy

| Komponent | Opis |
|-----------|------|
| `AutomationEventListener` | Subscriber Eloquent (created/updated) dla Lead, Project, Invoice, Quote, Contract |
| `ClientActivityListener` | Logi aktywności klienta w `client_activity_log` |
| `ProcessAutomationJob` | ShouldQueue, tries=3 — pobiera reguły, ewaluuje warunki, wykonuje akcje lub re-dispatches z delay |
| `Automation/Actions/` | 7 klas akcji realizujących konkretne typy działań |
| Scheduler | **NIE używany** — `routes/console.php` tylko `inspire` |

### 2.5 Wzorce architektoniczne

| Wzorzec | Obecność |
|---------|---------|
| MVC | TAK |
| Service Layer | CZĘŚCIOWE (4 serwisy) |
| Repository Pattern | NIE |
| Actions Pattern | CZĘŚCIOWE (`CreateLeadAction`, `Automation/Actions/`) |
| DTO / Value Objects | NIE |
| Form Requests | OGRANICZONE |
| Policies | NIE (brak `app/Policies/`) |
| Events/Listeners | TAK |
| Jobs/Queue | TAK |
| Observer | NIE (używane `booted()` hooks) |

---

## 3. Architektura frontendu

### 3.1 Stack — diagnoza jednoznaczna

**Inertia.js + React (JSX) + Tailwind CSS v4**

- `@inertiajs/react ^2.0.0` — potwierdzony w `package.json`
- `react ^18.2.0`, `react-dom ^18.2.0`
- `tailwindcss ^4.2.2` (Tailwind v4 — CSS-first config)
- **BRAK TypeScript** — wszystkie pliki to `.jsx`; brak `typescript` w devDependencies
- **Livewire** zainstalowane, ale używane wyłącznie do nadpisania komponentu Filament (`CustomDatabaseNotifications.php`)

### 3.2 Struktura katalogu `resources/js/`

```
resources/js/
├── app.jsx                        # Entry point Inertia
├── bootstrap.js                   # Axios setup
├── admin/                         # Custom assety panelu Filament
├── Components/
│   ├── Marketing/  (14 komponentów)  # Hero, About, Services, CostCalculatorV2, Contact, ...
│   ├── Shared/     (1 komponent)     # EmptyState.jsx
│   └── [UI prymitywy]                # Button, Input, Modal, Dropdown, Checkbox, ...
├── Contexts/
│   └── ConsentContext.js              # Cookie consent global state
├── Hooks/
│   ├── useConsent.js                  # Odczyt zgód GDPR
│   ├── useMetaPixel.js                # Meta Pixel tracking
│   ├── usePortalTrans.js              # Portal i18n translations
│   └── useScrollReveal.js             # Scroll animation
├── Layouts/
│   ├── AuthenticatedLayout.jsx
│   ├── GuestLayout.jsx
│   ├── MarketingLayout.jsx
│   └── PortalLayout.jsx
├── Pages/
│   ├── Auth/         (6 stron)        # Login, Register, ForgotPassword, ...
│   ├── Portal/       (12 stron)       # Dashboard, Project[s], Invoice[s], Quote[s], Contract[s], PayInvoice, ...
│   ├── Profile/      (1 strona)       # Edit
│   ├── CmsPage.jsx
│   ├── Dashboard.jsx                  # PLACEHOLDER Breeze — wymaga rozbudowy
│   ├── Kalkulator.jsx
│   └── Welcome.jsx
└── utils/
```

### 3.3 State management

| Aspekt | Stan |
|--------|------|
| Globalny state | BRAK Zustand/Redux; tylko `ConsentContext` |
| Formularze | `useForm` z `@inertiajs/react` |
| Translations | `usePortalTrans.js` — odczytuje `portal_translations` z Inertia props |
| Lokalne state | `useState`/`useReducer` per komponent |

### 3.4 TypeScript — brak (krytyczna luka)

Żaden plik `.tsx` ani `.ts` nie istnieje. Brak `typescript`, `@types/react` w `package.json`. Istnieje tylko `jsconfig.json` dla aliasów ścieżek. To jest **najważniejsza luka techniczna** blokująca skalowanie zespołowe.

---

## 4. Filament — Resources, Pages, Widgets

### 4.1 Resources (22)

| Resource | Moduł | RelationManagers |
|----------|-------|-----------------|
| `ClientResource` | CRM | Contacts, Leads, Projects, Quotes, Invoices, Contracts |
| `LeadResource` | CRM | Activities, Notes, Checklist |
| `PipelineStageResource` | CRM | Checklist items |
| `ProjectResource` | Projects | Phases, Tasks, Files, Messages |
| `ProjectTemplateResource` | Projects | Phases |
| `QuoteResource` | Finance | Items |
| `InvoiceResource` | Finance | Items, Payments |
| `ContractResource` | Finance | — |
| `ContractTemplateResource` | Finance | — |
| `EmailTemplateResource` | Comm. | — |
| `SmsTemplateResource` | Comm. | — |
| `AutomationRuleResource` | Automation | — |
| `PageResource` | CMS | — |
| `SiteSectionResource` | CMS | — |
| `UserResource` | System | — |
| `RoleResource` | System | Permissions |
| `PermissionResource` | System | — |
| `NotificationResource` | System | — |
| `SessionResource` | System | — |
| `PaymentResource` | Finance | — |
| `CalculatorPricingResource`, `CalculatorStepsResource`, `CalculatorStringsResource` | Calculator | — |

### 4.2 Custom Pages (7)

`PipelinePage` (Kanban), `ConversionReportPage`, `IntegrationSettingsPage`, `PaymentSettingsPage`, `TrackingSettingsPage`, `LegalSettingsPage`, `CalculatorAdminPage`

### 4.3 Widgets (13)

`StatsOverviewWidget`, `RevenueChartWidget`, `LeadsBySourceWidget`, `RecentLeadsWidget`, `ActiveProjectsWidget`, `ProjectStatusWidget`, `ProjectDeadlinesWidget`, `OverdueInvoicesWidget`, `StaleLeadsWidget`, `QuickActionsWidget`, `CalculatorPricingTableWidget`, `CalculatorStepsTableWidget`, `CalculatorStringsTableWidget`

---

## 5. Integracje zewnętrzne

| Integracja | Cel | Pakiet | Kluczowe pliki | Status |
|-----------|-----|--------|----------------|--------|
| **Stripe** | Płatności Checkout Session, webhook | `stripe/stripe-php ^19.4` | `StripeWebhookController.php`, `Portal/PaymentController.php` | działa |
| **PayU** | Bramka PL (sandbox + prod) | HTTP (brak SDK) | `PayuService.php`, `PayuWebhookController.php` | działa |
| **Twilio** | SMS | `twilio/sdk ^8.11` | `SmsService.php` | działa |
| **SMTP / Mail** | Email transakcyjny | Laravel Mail | `app/Mail/` (7 klas) | działa |
| **DomPDF** | PDF (faktury, raporty) | `barryvdh/laravel-dompdf ^3.1` | `InvoicePdfController.php`, `ReportController.php` | działa |
| **PhpSpreadsheet** | XLSX/CSV export | `phpoffice/phpspreadsheet ^5.5` | `ReportController.php` | działa |
| **Spatie Permission** | Role + Uprawnienia | `spatie/laravel-permission ^7.2` | `AdminSeeder.php`, `User.php` | działa |
| **Spatie Translatable** | Wielojęzyczne kolumny | `spatie/laravel-translatable ^6.13` | `SiteSection.php`, `Page.php` | działa |
| **Filament** | Panel administracyjny | `filament/filament ^5.4` | `app/Filament/` | działa |
| **GTM / GA4 / Meta Pixel / Google Ads** | Tracking i reklama | JS snippety | `TrackingSettingsPage.php`, `HandleInertiaRequests.php` | działa |
| **OpenAI** | AI generation | **BRAK** | — | nie zaimplementowane |
| **Reverb / WebSockets** | Real-time | config istnieje | — | nieużywane |

---

## 6. Role i uprawnienia (Spatie)

### 6.1 Role

| Rola | Dostęp do panelu | Zakres uprawnień |
|------|-----------------|-----------------|
| **admin** | TAK | Wszystkie 40+ uprawnień |
| **manager** | TAK | Wszystkie minus: `manage_roles`, `delete_users`, `manage_pipeline`, `manage_project_templates` |
| **developer** | TAK | `view_clients`, `view_leads`, `view_quotes`, `view_invoices`, `view_contracts`, `view_projects`, `edit_projects` |
| **client** | NIE (portal only) | Brak uprawnień panelowych |

### 6.2 Grupy uprawnień (40+ definicji)

CRM (klienci, leady, kontrakty), Finance (quotes, invoices), Projects, Templates (contract/email/sms), Automations, CMS (pages, site_sections), Users, Reports, Settings, Roles, Pipeline, Calculator, Project Templates.

### 6.3 Polityki (Policies)

**Katalog `app/Policies/` nie istnieje.** Autoryzacja opiera się wyłącznie na uprawnieniach Spatie w widokach Filament. Brak Policy na poziomie modeli — krytyczna luka dla multi-tenancy.

### 6.4 Seeder

`database/seeders/AdminSeeder.php` — tworzy role, uprawnienia i domyślnego użytkownika admin.

---

## 7. Ocena jakości kodu

| Aspekt | Ocena | Uzasadnienie |
|--------|-------|--------------|
| Separacja warstw | **ŚREDNI** | MVC dobrze zorganizowany; service layer zbyt mały — logika Invoice/Quote/Lead rozproszona |
| Pokrycie testami | **ŚREDNI** | Feature testy: Auth, Portal (5), Automation, Reports, FullLeadWorkflow; brak Unit testów serwisów; środowisko testowe problematyczne (SQLite + ENUM migration) |
| Typizacja JavaScript | **WYMAGA POPRAWY** | Brak TypeScript — wyłącznie `.jsx`; brak `@types/*` |
| Konwencje nazewnicze | **DOBRY** | Spójne w warstwach; modele/kontrolery/zasoby Filament konsekwentne |
| Wielojęzyczność | **ŚREDNI** | EN/PL/PT gotowe dla portalu i CMS; hardcoded strings w komponentach React Marketing/ |
| Dokumentacja kodu | **ŚREDNI** | Komentarze w kluczowych klasach (SmsService, PayuService, ProcessAutomationJob); brak PHPDoc na modelach |
| Obsługa błędów | **ŚREDNI** | `Log::error/warning` w integracjach; global try-catch w AppServiceProvider; bug: `CostCalculatorV2.jsx` ustawia sukces w `finally` |
| Bezpieczeństwo | **DOBRY** | CSRF exempt przez bootstrap (nie skip), webhook signature validation, PayU MD5 hmac, walidacja ID notyfikacji regex, brak Open Redirect |
| Form Requests | **WYMAGA POPRAWY** | Tylko `ContactRequest.php` + Auth requests; brak Form Requests dla Portal endpoints |

---

## 8. Ryzyka skalowania

### 8.1 BRAK MULTI-TENANCY — priorytet HIGH

Projekt jest single-tenant. Nie ma `tenant_id`, `organization_id` ani izolacji danych między klientami SaaS. Wszystkie modele (`Client`, `Lead`, `Project`, `Invoice`, etc.) są globalnie dostępne dla wszystkich użytkowników panelu.

**Lokalizacja:** każdy model i migracja.
**Propozycja:** Model `Workspace`/`Tenant`, `tenant_id` na modelach, middleware izolacji (Scoped Queries lub package `stancl/tenancy`).

---

### 8.2 Minimalny service layer — priorytet HIGH

Tylko 4 serwisy przy 30+ modelach biznesowych. Logika tworzenia leadów, faktur, projektów, konwersji quote → invoice jest wbudowana bezpośrednio w kontrolery portalu i zasoby Filament.

**Lokalizacja:** `app/Http/Controllers/Portal/`, `app/Filament/Resources/`, `app/Filament/Pages/PipelinePage.php`
**Propozycja:** `LeadService`, `InvoiceService`, `ProjectService`, `QuoteService`.

---

### 8.3 Brak TypeScript — priorytet HIGH

Cały frontend w `.jsx` bez typizacji. Przy rozbudowie SaaS (landing pages, AI generator) brak typów prowadzi do trudnych do wykrycia błędów.

**Lokalizacja:** `resources/js/**/*.jsx`
**Propozycja:** Migracja do TypeScript (`.tsx`) — stopniowo moduł po module.

---

### 8.4 Hardcoded single-instance config — priorytet HIGH

Ustawienia firmy, Stripe keys, Twilio, SMTP są globalne (tabela `settings`). W SaaS każdy tenant musi mieć osobną konfigurację.

**Lokalizacja:** `app/Models/Setting.php`, `app/Filament/Pages/*SettingsPage.php`

---

### 8.5 ProcessAutomationJob jako God Object — priorytet MEDIUM

Jeden job odpowiada za: pobieranie reguł, ewaluację warunków, re-dispatch z delay'em, wykonanie 7 typów akcji.

**Lokalizacja:** `app/Jobs/ProcessAutomationJob.php`
**Propozycja:** Wydzielenie `AutomationDispatcher` (orkiestrator) od dedykowanych job'ów per akcja.

---

### 8.6 Bug: eksport faktur — pole `tax_amount` vs `vat_amount` — priorytet HIGH (bugfix)

`ReportController` eksportuje faktury używając pola `tax_amount`, a model `Invoice` operuje na `vat_amount`. To potencjalny błąd danych finansowych w eksportach.

**Lokalizacja:** `app/Http/Controllers/ReportController.php`

---

### 8.7 Bug: CostCalculatorV2 — fałszywy sukces — priorytet HIGH (bugfix)

`CostCalculatorV2.jsx` ustawia stan sukcesu w bloku `finally`, więc użytkownik zobaczy potwierdzenie wysłania nawet gdy request skończył się błędem.

**Lokalizacja:** `resources/js/Components/Marketing/CostCalculatorV2.jsx`

---

### 8.8 Brak Policies — priorytet MEDIUM

Brak `app/Policies/`. Bez Policies niemożliwa jest prawidłowa izolacja danych między tenantami w przyszłości.
**Propozycja:** Policies dla Lead, Client, Project, Invoice, Quote, Contract.

---

### 8.9 Środowisko testowe niestabilne — priorytet MEDIUM

Migracja ENUM (`ALTER TABLE ... MODIFY COLUMN`) nie wspierana przez SQLite in-memory. `HandleInertiaRequests` odpytuje tabelę `settings` przed seedowaniem.

**Lokalizacja:** `database/migrations/2026_03_22_194527_update_project_phases_status_enum.php`, `HandleInertiaRequests.php`

---

### 8.10 Brak OpenAI / AI Layer — priorytet MEDIUM (kluczowe dla SaaS)

Brak integracji OpenAI — kluczowej funkcji Digital Growth OS (AI Landing Page Generator). Brak pakietu `openai-php/client`.

---

### 8.11 Brak Schedulera — priorytet LOW

`routes/console.php` zawiera tylko `inspire`. Brak automatycznych przypomnień (np. overdue invoices, stale leads).

---

## 9. Gotowość projektu pod SaaS

### 9.1 Co jest gotowe (przenosi się do SaaS)

| Element | Poziom gotowości |
|---------|----------------|
| CRM (Lead, Client, Contact, Pipeline) | ✅ wysoka |
| Portal klienta (pełny workflow) | ✅ wysoka |
| Finanse (Quote, Invoice, Contract, Payment) | ✅ wysoka |
| Automatyzacje event-driven | ✅ wysoka |
| Integracje płatności (Stripe, PayU) | ✅ wysoka |
| Twilio SMS | ✅ wysoka |
| Panel Filament | ✅ wysoka |
| Marketing frontend DB-driven | ✅ średnia |
| Wielojęzyczność (EN/PL/PT) | ✅ średnia |
| Tracking (GTM, GA4, Pixel, Google Ads) | ✅ wysoka |
| Raportowanie (4 formaty) | ✅ średnia |

### 9.2 Co wymaga budowy przed SaaS MVP

| Element | Priorytet | Nakład szacunkowy |
|---------|-----------|-----------------|
| Multi-tenancy (Workspace model + izolacja danych) | 🔴 HIGH | Duży |
| TypeScript migracja frontendu | 🔴 HIGH | Średni–Duży |
| Service Layer (Lead, Invoice, Project, Quote) | 🔴 HIGH | Średni |
| Bugfix: vat_amount w eksporcie faktur | 🔴 HIGH | Mały |
| Bugfix: fałszywy sukces kalkulatora | 🔴 HIGH | Mały |
| Policies (autoryzacja na poziomie modeli) | 🟠 MEDIUM | Średni |
| OpenAI / AI Landing Page Generator | 🟠 MEDIUM | Duży |
| Business Profile (per tenant) | 🟠 MEDIUM | Średni |
| Landing Pages Builder | 🟠 MEDIUM | Duży |
| Subskrypcje / plany SaaS (Stripe Billing) | 🟠 MEDIUM | Średni |
| Onboarding flow (rejestracja tenanta) | 🟠 MEDIUM | Średni |
| Scheduler / przypomnienia automatyczne | 🟡 LOW | Mały |
| Feature flags / per-tenant config | 🟡 LOW | Mały |

---

## 10. Rekomendacje i priorytety

### Faza 1 — Stabilizacja (przed rozwojem)

1. Naprawić bug: `vat_amount` zamiast `tax_amount` w `ReportController`
2. Naprawić bug: przenieść `setSuccess(true)` z `finally` do `then` w `CostCalculatorV2.jsx`
3. Naprawić środowisko testowe — zmiana migracji ENUM na kompatybilną z SQLite, dodać guard Settings w `HandleInertiaRequests`
4. Dodać Form Requests dla Portal endpoints

### Faza 2 — Fundament SaaS

1. Model `Workspace`/`Tenant` + middleware izolacji + global scope na modelach
2. Service Layer: `LeadService`, `InvoiceService`, `ProjectService`, `QuoteService`
3. Policies dla kluczowych modeli
4. Migracja do TypeScript — stopniowo zacznij od typów Inertia props

### Faza 3 — Nowe moduły SaaS

1. Business Profile (dane firmy per tenant, logo, kolory, tone of voice)
2. AI Landing Page Generator (OpenAI integration)
3. Landing Pages Management (edycja, publikacja, custom domains)
4. Stripe Billing / subskrypcje
5. Onboarding flow dla nowych tenantów

---

*Analiza oparta na pełnym przeglądzie kodu źródłowego: routes/, app/, database/, resources/js/, lang/, tests/. Data: 31.03.2026.*
