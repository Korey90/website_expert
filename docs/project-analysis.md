# Analiza projektu — Digital Growth OS (WebsiteExpert)
> Data: 2026-03-31

---

## 1. Feature Inventory

### 1.1 Autentykacja i autoryzacja
| Funkcjonalność | Kluczowe pliki | Status |
|---|---|---|
| Login / Logout | `app/Http/Controllers/Auth/AuthenticatedSessionController.php` | zaimplementowane |
| Rejestracja użytkownika | `app/Http/Controllers/Auth/RegisteredUserController.php` | zaimplementowane |
| Reset hasła (e-mail) | `app/Http/Controllers/Auth/PasswordResetLinkController.php` | zaimplementowane |
| Weryfikacja e-mail | `app/Http/Controllers/Auth/VerifyEmailController.php` | zaimplementowane |
| Role i uprawnienia (Spatie) | `database/seeders/AdminSeeder.php`, `app/Models/User.php` | zaimplementowane |
| Dostęp panelu Filament | `User::canAccessPanel()` — role: admin/manager/developer | zaimplementowane |
| Klient — portal dostęp | `Client::portal_user_id` → `User` | zaimplementowane |

### 1.2 CRM
| Funkcjonalność | Kluczowe pliki | Status |
|---|---|---|
| Zarządzanie klientami | `app/Filament/Resources/ClientResource.php`, `app/Models/Client.php` | zaimplementowane |
| Kontakty klientów | `app/Models/Contact.php` | zaimplementowane |
| Zarządzanie leadami | `app/Filament/Resources/LeadResource.php`, `app/Models/Lead.php` | zaimplementowane |
| Notatki leadów (z pinowaniem) | `app/Models/LeadNote.php` | zaimplementowane |
| Aktywność leadów (historia) | `app/Models/LeadActivity.php` | zaimplementowane |
| Checklista etapów pipeline | `app/Models/LeadChecklistItem.php` | zaimplementowane |
| Pipeline Kanban | `app/Filament/Pages/PipelinePage.php` | zaimplementowane |
| Budget range leadów | `budget_min`, `budget_max` w `leads` table | zaimplementowane |

### 1.3 Projekty
| Funkcjonalność | Kluczowe pliki | Status |
|---|---|---|
| Zarządzanie projektami | `app/Filament/Resources/ProjectResource.php`, `app/Models/Project.php` | zaimplementowane |
| Fazy projektu | `app/Models/ProjectPhase.php` | zaimplementowane |
| Zadania projektu | `app/Models/ProjectTask.php` | zaimplementowane |
| Pliki projektu | `app/Models/ProjectFile.php` | zaimplementowane |
| Wiadomości projektu (messaging) | `app/Models/ProjectMessage.php` | zaimplementowane |
| Szablony projektów | `app/Models/ProjectTemplate.php`, `app/Filament/Resources/ProjectTemplateResource.php` | zaimplementowane |

### 1.4 Finanse
| Funkcjonalność | Kluczowe pliki | Status |
|---|---|---|
| Faktury + pozycje | `app/Models/Invoice.php`, `app/Models/InvoiceItem.php` | zaimplementowane |
| PDF faktur | `app/Http/Controllers/InvoicePdfController.php` | zaimplementowane |
| Oferty (Quotes) | `app/Models/Quote.php`, `app/Models/QuoteItem.php` | zaimplementowane |
| Umowy z podpisem elektronicznym | `app/Models/Contract.php`, `app/Filament/Resources/ContractResource.php` | zaimplementowane |
| Szablony umów z interpolacją | `app/Models/ContractTemplate.php`, `app/Services/ContractInterpolationService.php` | zaimplementowane |
| Płatności Stripe | `app/Http/Controllers/StripeWebhookController.php` | zaimplementowane |
| Płatności PayU | `app/Services/PayuService.php`, `app/Http/Controllers/PayuWebhookController.php` | zaimplementowane |
| Raporty (HTML/PDF/XLSX/CSV) | `app/Http/Controllers/ReportController.php` | zaimplementowane |
| Raport konwersji | `app/Filament/Pages/ConversionReportPage.php` | zaimplementowane |

### 1.5 Portal klienta
| Funkcjonalność | Kluczowe pliki | Status |
|---|---|---|
| Dashboard portalu | `app/Http/Controllers/Portal/DashboardController.php`, `resources/js/Pages/Portal/Dashboard.jsx` | zaimplementowane |
| Projekty klienta | `app/Http/Controllers/Portal/ProjectController.php`, `resources/js/Pages/Portal/Projects.jsx` | zaimplementowane |
| Faktury klienta | `app/Http/Controllers/Portal/InvoiceController.php`, `resources/js/Pages/Portal/Invoices.jsx` | zaimplementowane |
| Oferty klienta (akceptacja/odrzucenie) | `app/Http/Controllers/Portal/QuoteController.php`, `resources/js/Pages/Portal/Quote.jsx` | zaimplementowane |
| Umowy klienta (podpis) | `app/Http/Controllers/Portal/ContractController.php`, `resources/js/Pages/Portal/Contract.jsx` | zaimplementowane |
| Płatność faktury (Stripe + PayU) | `app/Http/Controllers/Portal/PaymentController.php`, `resources/js/Pages/Portal/PayInvoice.jsx` | zaimplementowane |
| Preferencje powiadomień klienta | `app/Http/Controllers/Portal/NotificationController.php`, `resources/js/Pages/Portal/NotificationSettings.jsx` | zaimplementowane |

### 1.6 Powiadomienia
| Funkcjonalność | Kluczowe pliki | Status |
|---|---|---|
| In-app DB notifications (Filament) | `app/Livewire/CustomDatabaseNotifications.php`, `app/Models/DatabaseNotification.php` | zaimplementowane |
| Notification follow-link (mark as read) | `routes/web.php` — `/notification-follow` | zaimplementowane |
| Polling JS (keepalive) | `resources/js/admin/notifications.js` (oddzielny entrypoint Vite) | zaimplementowane |
| Preferencje komunikacji klienta | `ClientNotificationGate`, pola `notify_*` w `Client` | zaimplementowane |

### 1.7 Integracje zewnętrzne
| Integracja | Paczka | Kluczowe pliki | Status |
|---|---|---|---|
| Stripe | `stripe/stripe-php ^19.4` | `StripeWebhookController.php`, `Portal/PaymentController.php` | działa |
| PayU | własna implementacja HTTP | `PayuService.php`, `PayuWebhookController.php` | działa |
| Twilio SMS | `twilio/sdk ^8.11` | `SmsService.php` | działa |
| SMTP / Mail | Laravel Mail | `app/Mail/*`, `IntegrationSettingsPage.php` | działa |
| Google Tag Manager | — | `TrackingSettingsPage.php`, widoki Blade | działa |
| Google Analytics 4 | — | `TrackingSettingsPage.php` | działa |
| Meta Pixel (Facebook) | — | `useMetaPixel.js`, `ConsentContext.js` | działa |
| Google Ads | — | `TrackingSettingsPage.php`, `dataLayer.js` | działa |
| DomPDF | `barryvdh/laravel-dompdf ^3.1` | `InvoicePdfController.php`, `ReportController.php` | działa |
| PhpSpreadsheet | `phpoffice/phpspreadsheet ^5.5` | `ReportController.php` | działa |
| OpenAI | **brak** | brak | **nieużywane** |

### 1.8 Panel administracyjny (Filament)
| Obszar | Resources / Pages | Status |
|---|---|---|
| CRM | ClientResource, LeadResource, ContractResource, PipelinePage | zaimplementowane |
| Projekty | ProjectResource, ProjectTemplateResource | zaimplementowane |
| Finanse | InvoiceResource, QuoteResource, PaymentResource | zaimplementowane |
| Szablony | EmailTemplateResource, SmsTemplateResource, ContractTemplateResource | zaimplementowane |
| Automatyzacje | AutomationRuleResource | zaimplementowane |
| CMS Stron | PageResource, SiteSectionResource | zaimplementowane |
| Kalkulator | CalculatorPricingResource, CalculatorStepsResource, CalculatorStringsResource, CalculatorAdminPage | zaimplementowane |
| Użytkownicy/Role | UserResource, RoleResource, PermissionResource | zaimplementowane |
| Ustawienia | IntegrationSettingsPage, LegalSettingsPage, PaymentSettingsPage, TrackingSettingsPage | zaimplementowane |
| Raporty | ConversionReportPage, SessionResource | zaimplementowane |
| Dashboard widgets | StatsOverviewWidget, RecentLeadsWidget, OverdueInvoicesWidget, ActiveProjectsWidget, RevenueChartWidget, LeadsBySourceWidget, ProjectStatusWidget, QuickActionsWidget, ProjectDeadlinesWidget, StaleLeadsWidget | zaimplementowane |
| Pinowane notatki lead | `AdminPanelProvider` — renderHook topbar | zaimplementowane |

### 1.9 Frontend publiczny
| Funkcjonalność | Kluczowe pliki | Status |
|---|---|---|
| Strona główna (sekcje z DB) | `resources/js/Pages/Welcome.jsx`, `app/Http/Controllers/WelcomeController.php` | zaimplementowane |
| Kalkulator kosztów V2 (DB-driven) | `resources/js/Components/Marketing/CostCalculatorV2.jsx`, `KalkulatorController.php` | zaimplementowane |
| Strony CMS (/p/{slug}) | `resources/js/Pages/CmsPage.jsx`, `PageController.php` | zaimplementowane |
| Formularz kontaktowy | `app/Http/Controllers/ContactController.php`, `ContactRequest.php` | zaimplementowane |
| Formularz do leadów z kalkulatora | `app/Http/Controllers/CalculatorLeadController.php` | zaimplementowane |
| Cookie banner + consent | `resources/js/Components/Marketing/CookieBanner.jsx`, `ConsentContext.js` | zaimplementowane |
| Przełącznik języka (/lang/{locale}) | `routes/web.php` | zaimplementowane |

### 1.10 Automatyzacje i kolejki
| Funkcjonalność | Kluczowe pliki | Status |
|---|---|---|
| Engine automatyzacji (trigger → conditions → actions) | `app/Automation/`, `app/Jobs/ProcessAutomationJob.php` | zaimplementowane |
| Triggery: lead, project, invoice, quote, contract | `app/Listeners/AutomationEventListener.php` | zaimplementowane |
| Akcje: email, SMS, internal email, notify_admin, change_status, create_portal_access, add_tag | `app/Automation/Actions/` | zaimplementowane |
| Log aktywności klientów | `app/Listeners/ClientActivityListener.php`, `app/Models/ClientActivity.php` | zaimplementowane |
| Kolejki (database driver) | `bootstrap/app.php`, `ProcessAutomationJob implements ShouldQueue` | zaimplementowane |

### 1.11 Wielojęzyczność (i18n)
| Obszar | Pliki/metoda | Status |
|---|---|---|
| Portal klienta (en/pl/pt) | `lang/en/portal.php`, `lang/pl/portal.php`, `lang/pt/portal.php` | zaimplementowane |
| Modele Page, SiteSection | `spatie/laravel-translatable` — JSON columns | zaimplementowane |
| Kalkulator (en/pl/pt) | DB: kolumny `label_en`, `label_pl`, `label_pt` w `calculator_pricing` | zaimplementowane |
| Przełącznik języka | `/lang/{locale}` z session | zaimplementowane |
| React `usePortalTrans` hook | `resources/js/Hooks/usePortalTrans.js` | zaimplementowane |
| Backend `__()` i `trans()` | brak systematycznego użycia poza portalem | częściowe |

---

## 2. Architektura backendu

### 2.1 Modele i relacje

```
User ──────────────────── HasRoles (Spatie)
  │
  └─ Client ─────────────── contacts: HasMany(Contact)
       │                 ── leads: HasMany(Lead)
       │                 ── projects: HasMany(Project)
       │                 ── quotes: HasMany(Quote)
       │                 ── invoices: HasMany(Invoice)
       │                 ── portal_user: BelongsTo(User)
       │
  Lead ──────────────────── stage: BelongsTo(PipelineStage)
       |                 ── assignedTo: BelongsTo(User)
       │                 ── project: HasOne(Project)
       │                 ── activities: HasMany(LeadActivity)
       │                 ── notes: HasMany(LeadNote)
       │
  Project ───────────────── phases: HasMany(ProjectPhase) → tasks: HasMany(ProjectTask)
       │                 ── files: HasMany(ProjectFile)
       │                 ── messages: HasMany(ProjectMessage)
       │                 ── invoices: HasMany(Invoice)
       │
  Invoice ───────────────── items: HasMany(InvoiceItem)
       │                 ── payments: HasMany(Payment)
       │
  Contract ──────────────── contractTemplate: BelongsTo(ContractTemplate)
  Quote ─────────────────── items: HasMany(QuoteItem)
  AutomationRule ─────────── trigger_event, conditions (JSON), actions (JSON)
  Setting ────────────────── key/value store z cachem 1-dniowym
  Page ───────────────────── HasTranslations (spatie/laravel-translatable)
  SiteSection ────────────── HasTranslations (spatie/laravel-translatable)
  CalculatorPricing/Steps/Strings ── kolumny *_en, *_pl, *_pt
```

**SoftDeletes** używane na: `Client`, `Lead`, `Project`, `Invoice`, `Quote`, `Contract`, `Page`, `Contact`

**Cascade delete** zaimplementowany ręcznie w `Client::booted()` — force-delete usuwa zagnieżdżone rekordy.

### 2.2 Service layer

`app/Services/` zawiera tylko 4 serwisy:

| Serwis | Odpowiedzialność |
|---|---|
| `SmsService` | Wysyłanie SMS przez Twilio, normalizacja numerów, flaga enabled z DB |
| `PayuService` | OAuth2 token, tworzenie zamówień PayU |
| `ClientNotificationGate` | Sprawdzenie preferencji komunikacji klienta przed wysyłką |
| `ContractInterpolationService` | Podmiana placeholder'ów w treści umów |

**Brakuje** serwisów dla: Stripe, Invoice, Lead, Project, Automation, Mail.

### 2.3 Kontrolery

Kontrolery są częściowo grube:
- `StripeWebhookController` — zawiera logikę biznesową (update Payment, Invoice.recalculate(), wysyłanie maili). **HIGH risk** — brak serwisu.
- `ReportController` — zawiera zapytania Eloquent bezpośrednio. **MEDIUM risk**.
- `DashboardController` — proste zliczenia, akceptowalne.
- Kontrolery portalu (`Portal/`) — umiarkowanie grube, ale logika dobrze odizolowana per moduł.
- `CreateLeadAction` — poprawna warstwa Action dla tworzenia leadów.

### 2.4 Filament — zasoby i strony

**Filament 5.4** (nie 3.x jak w nazwie projektu SaaS — wersja nowsza niż zakładano).

Nawigacja pogrupowana na: CRM, Projects, Finance, Marketing, Settings.

26 Resources + 7 custom Pages + 13 Widgets. Panel `/admin` z kolorystyką brand (`#ff2b17`).

Unikalne mechanizmy:
- `PipelinePage` — własna Kanban z modalami email/notatki/historia (Livewire-style w Filament page)
- `CustomDatabaseNotifications` — nadpisanie Filament DatabaseNotifications (X = mark as read, nie delete)
- Pinowane notatki leadów w topbarze (renderHook)
- Polling JS dla powiadomień (`resources/js/admin/notifications.js`)

### 2.5 Kolejki i zdarzenia

| Komponent | Opis |
|---|---|
| `ProcessAutomationJob` | `ShouldQueue`, 3 retries, obsługuje 7 typów akcji |
| `AutomationEventListener` | Subscribe na Eloquent events (lead, project, invoice, quote, contract) |
| `ClientActivityListener` | Log osi czasu aktywności klienta w portalu |
| `AppServiceProvider` | Rejestruje oba Listeners + override config mail z DB |

Driver kolejek: domyślnie `database` (tabela `jobs` z migracji).

### 2.6 Wzorce

| Wzorzec | Użycie |
|---|---|
| Service Layer | Częściowe (4 serwisy) — brakuje dla Stripe, Mail, Lead, Invoice |
| Action Pattern | `app/Actions/CreateLeadAction.php` — jeden Action |
| Repository Pattern | **Brak** |
| DTO | **Brak** |
| Observer | **Brak** — używa Eloquent event listeners przez Dispatcher |

---

## 3. Architektura frontendu

### 3.1 Stack frontendu — **Inertia.js 2.0 + React 18 (JSX) + Tailwind CSS 4.x**

**Brak TypeScript** — projekt używa czystego JSX (`.jsx`). Livewire jest obecne w vendor (zależność FilamentPhp), ale używane tylko do `CustomDatabaseNotifications`.

### 3.2 Struktura komponentów

```
resources/js/
├── app.jsx                    # Inertia bootstrap (createInertiaApp)
├── Pages/
│   ├── Welcome.jsx            # Strona główna (sekcje z DB)
│   ├── Dashboard.jsx          # Dashboard agencji (Inertia)
│   ├── Kalkulator.jsx         # Standalone kalkulator
│   ├── CmsPage.jsx            # Renderowanie stron CMS
│   ├── Auth/                  # Login, register, reset password (Breeze style)
│   ├── Profile/               # Edycja profilu użytkownika
│   └── Portal/                # 12 stron portalu klienta
│       ├── Dashboard.jsx
│       ├── Projects.jsx / Project.jsx
│       ├── Invoices.jsx / Invoice.jsx / PayInvoice.jsx / PaymentResult.jsx
│       ├── Quotes.jsx / Quote.jsx
│       ├── Contracts.jsx / Contract.jsx
│       └── NotificationSettings.jsx
├── Components/
│   ├── Marketing/             # 13 komponentów strony publicznej
│   │   ├── Hero.jsx, About.jsx, Services.jsx, Process.jsx, Portfolio.jsx
│   │   ├── CostCalculatorV2.jsx  # Kalkulator DB-driven (EN/PL/PT)
│   │   ├── Contact.jsx, Faq.jsx, Footer.jsx, Navbar.jsx
│   │   └── CookieBanner.jsx, TrustStrip.jsx, CtaBanner.jsx
│   └── Shared/                # wspólne komponenty UI
├── Layouts/
│   ├── AuthenticatedLayout.jsx
│   ├── GuestLayout.jsx
│   ├── MarketingLayout.jsx
│   └── PortalLayout.jsx       # Sidebar, mobile-responsive
├── Hooks/
│   ├── useConsent.js          # Cookie consent
│   ├── useMetaPixel.js        # Meta Pixel z consent-gate
│   ├── usePortalTrans.js      # i18n hook dla portalu
│   └── useScrollReveal.js     # Scroll animations
├── Contexts/
│   └── ConsentContext.js      # React context dla cookie consent
└── utils/
    └── dataLayer.js           # GTM dataLayer helper
```

### 3.3 Zarządzanie stanem

- **React `useState`** — lokalny stan komponentów
- **`ConsentContext`** — React Context dla preferencji cookies
- **`usePage().props`** — Inertia shared props (auth, tracking, portal_translations)
- **Brak Zustand / Redux** — stan globalny przez Inertia props sharing

### 3.4 TypeScript

**Projektu nie używa TypeScript.** Wszystkie pliki to `.jsx` (JSX bez typów). Brak `tsconfig.json`. Brak typów dla API responses.

### 3.5 Tailwind CSS 4.x

- `darkMode: 'class'` skonfigurowany, ale **dark mode niezaimplementowany** w komponentach
- Niestandardowa paleta `brand` (czerwień `#ff2b17`)
- Fonty: Inter (sans) + Syne (display)
- Mobile-first z `sm:`, `lg:` breakpoints
- Plugin `@tailwindcss/forms` + `@tailwindcss/typography`

### 3.6 Testy frontendowe

- Vitest + testing-library skonfigurowane w `package.json` i `vite.config.js`
- Katalog `resources/js/tests/` (setup.js)
- **Brak faktycznych testów komponentów** (tylko setup)

---

## 4. Integracje zewnętrzne

| Integracja | Pakiet | Status | Uwagi |
|---|---|---|---|
| **Stripe** | `stripe/stripe-php ^19.4` | działa | Webhook, checkout, payment intents; brak Laravel Cashier |
| **PayU** | własna implementacja (HTTP) | działa | OAuth2, sandbox/prod przełącznik w DB |
| **Twilio** | `twilio/sdk ^8.11` | działa | SMS, config z DB; normalizer E.164 UK |
| **SMTP / Mail** | Laravel Mail | działa | W pełni konfigurowalny z panelu admin (DB override) |
| **Google Tag Manager** | — | działa | DB toggle + tracking props przez Inertia |
| **Google Analytics 4** | — | działa | Direct snippet lub przez GTM |
| **Meta Pixel** | — | działa | `useMetaPixel` hook z consent-gate |
| **Google Ads** | — | działa | Tracking ID w DB, przez GTM |
| **DomPDF** | `barryvdh/laravel-dompdf ^3.1` | działa | PDF faktury, raporty |
| **PhpSpreadsheet** | `phpoffice/phpspreadsheet ^5.5` | działa | XLSX + CSV eksport raportów |
| **Spatie Translatable** | `spatie/laravel-translatable ^6.13` | działa | Page, SiteSection — JSON columns |
| **Spatie Permission** | `spatie/laravel-permission ^7.2` | działa | Role + 50 uprawnień granularnych |
| **OpenAI** | **brak** | nieużywane | Brak pakietu, brak implementacji |
| **Reverb / Pusher** | `laravel/reverb` w config | częściowe | Config `reverb.php` istnieje, brak aktywnego real-time |
| **Mailgun / Postmark / SES** | — | częściowe | Obsługiwane przez IntegrationSettingsPage, niezweryfikowane |

---

## 5. Role i uprawnienia (Spatie)

### 5.1 Struktura ról

| Rola | Opis | Dostęp do panelu |
|---|---|---|
| `admin` | Pełna kontrola | TAK |
| `manager` | CRM + Finance + Projects (bez zarządzania rolami/pipeline) | TAK |
| `developer` | Read-only CRM/Finance + edit projects/contracts | TAK |
| `client` | Portal klienta (bez Filament) | NIE |

### 5.2 Uprawnienia (50 granularnych)

Grupowane według obszarów:

| Obszar | Uprawnienia |
|---|---|
| CRM — Clients | `view_clients`, `create_clients`, `edit_clients`, `delete_clients` |
| CRM — Leads | `view_leads`, `create_leads`, `edit_leads`, `delete_leads` |
| CRM — Contracts | `view_contracts`, `create_contracts`, `edit_contracts`, `delete_contracts` |
| Finance — Quotes | `view_quotes`, `create_quotes`, `edit_quotes`, `delete_quotes` |
| Finance — Invoices | `view_invoices`, `create_invoices`, `edit_invoices`, `delete_invoices` |
| Projects | `view_projects`, `create_projects`, `edit_projects`, `delete_projects` |
| Templates (Contract/Email/SMS) | `view_*`, `create_*`, `edit_*`, `delete_*` (12 uprawnień) |
| Automations | `view_automations`, `create_automations`, `edit_automations`, `delete_automations` |
| Website CMS | `view_pages/site_sections`, `create_*`, `edit_*`, `delete_*` (8 uprawnień) |
| Users | `view_users`, `create_users`, `edit_users`, `delete_users` |
| Reports | `view_reports`, `export_reports` |
| System Settings | `manage_settings`, `manage_roles`, `manage_pipeline`, `manage_calculator`, `manage_project_templates` |

### 5.3 Macierz ról

| Uprawnienie | admin | manager | developer | client |
|---|---|---|---|---|
| Wszystkie CRUD | ✅ | większość | tylko widok / edit_projects | ❌ |
| manage_roles | ✅ | ❌ | ❌ | ❌ |
| manage_settings | ✅ | ✅ | ❌ | ❌ |
| manage_pipeline | ✅ | ❌ | ❌ | ❌ |
| Portal klienta | ❌ | ❌ | ❌ | ✅ |

### 5.4 Obowiązujące polityki

`app/Policies/` — **brak pliku**. Autoryzacja oparta wyłącznie na `hasPermissionTo()` w Filament Resources. Brak PolicyClass dla modeli.

---

## 6. Ocena jakości kodu

| Aspekt | Ocena | Uzasadnienie |
|---|---|---|
| Separacja warstw (MVC + service layer) | **ŚREDNI** | 4 serwisy, 1 Action — StripeWebhookController i ReportController zawierają logikę biznesową |
| Pokrycie testami (Feature/Unit) | **ŚREDNI** | 15 testów Feature (Auth, Portal ×5, Raporty ×3, Automation ×2, LeadWorkflow) — brak testów Unit, brak testów dla Filament Resources |
| TypeScript | **WYMAGA POPRAWY** | Projekt używa czystego JSX — brak typowania API responses, propsów komponentów, hooków |
| Konwencja nazewnicza | **DOBRY** | Spójna konwencja camelCase/PascalCase; Route names prefix-owane (`portal.`, `reports.*`) |
| Wielojęzyczność (i18n) | **ŚREDNI** | Portal przetłumaczony (EN/PL/PT), kalkulator przetłumaczony; backend nie używa konsekwentnie `__()` |
| Dokumentacja kodu | **ŚREDNI** | PHPDoc w kluczowych serwisach (ContractInterpolationService, CreateLeadAction, SmsService); brak dokumentacji komponentów React |
| Obsługa błędów i logowanie | **DOBRY** | Log::error/warning/info w krytycznych miejscach (SmsService, PayuService, StripeWebhook); fallback dla brakujących konfiguracji |
| Bezpieczeństwo | **DOBRY** | CSRF na webhookach wyłączony świadomie; open-redirect protection w notification-follow; UUID validation w mark-as-read |

---

## 7. Ryzyka skalowania

### RYZYKO HIGH

| Ryzyko | Lokalizacja | Propozycja rozwiązania |
|---|---|---|
| **Brak multi-tenancy** — wszystkie dane dzielą jeden tenant | Cała baza danych, brak `tenant_id` w żadnej tabeli | Dodać `tenant_id` do wszystkich tabel biznesowych; middleware izolacji; model `Tenant` / `Organization` |
| **Brak Service dla Stripe** — logika biznesowa w kontrolerze | `app/Http/Controllers/StripeWebhookController.php` | Stworzyć `StripePaymentService` + `InvoicePaymentService` |
| **Settings w DB z cache 1-dziennym** — ryzyko stale cache przy skalowaniu | `app/Models/Setting.php` | Centralny cache-tag invalidation lub Redis z krótszym TTL |
| **Brak TypeScript** — brak bezpieczeństwa typów przy rozbudowie frontandu | Cały `resources/js/` | Migracja do `.tsx` z typami dla Inertia props i API responses |

### RYZYKO MEDIUM

| Ryzyko | Lokalizacja | Propozycja rozwiązania |
|---|---|---|
| **Brak Policies** — autoryzacja tylko przez Filament | Brak `app/Policies/` | Stworzyć Policy dla: Lead, Client, Invoice, Contract, Project |
| **Fat controllery** — ReportController z zapytaniami | `app/Http/Controllers/ReportController.php` | Przenieść do `ReportService` / dedykowanych Query classes |
| **Brak Form Requests dla Filament** — walidacja w Resource | Filament Resources | Dodać dedykowane Form Requests / walidację per action |
| **PipelinePage** — logika Eloquent bezpośrednio w Page | `app/Filament/Pages/PipelinePage.php` | Wydzielić LeadPipelineService |
| **Brak OpenAI** — core featura SaaS niezaimplementowany | Brak | Dodać `openai-php/client` lub `openai/openai-php` |
| **N+1 queries potencjalne** — brak eager loading w kilku miejscach | `Portal/DashboardController`, `PipelinePage` | Audyt with() loading, Laravel Debugbar |
| **Kalkulator** — V1 i V2 jednocześnie w komponentach | `CostCalculator.jsx`, `CostCalculatorV2.jsx` | Usunąć V1, zostawić tylko V2 DB-driven |

### RYZYKO LOW

| Ryzyko | Lokalizacja | Propozycja rozwiązania |
|---|---|---|
| **Brak repository pattern** — Eloquent bezpośrednio wszędzie | Ogólnie | Opcjonalny — nie priorytet, ale warto przy skalowaniu |
| **Notifikacje przez polling** zamiast WebSocket | `resources/js/admin/notifications.js` | Reverb skonfigurowany — uruchomić real-time broadcasting |
| **Brak API (JSON)** — tylko Inertia + Filament | Brak `routes/api.php` | Przy SaaS konieczne API v1 dla zewnętrznych integracji |
| **Blade + Inertia mixed** — raporty w Blade, portal w Inertia | `resources/views/reports/` | Spójność — raporty mogą zostać w Blade (server-side) |
| **Brak dark mode** mimo konfiguracji | `tailwind.config.js` — `darkMode: 'class'` | Implementacja klas `dark:` w komponentach |

---

## 8. Rekomendacje i priorytety

### Faza 1 — Fundament SaaS (przed każdą nową funkcją)

1. **[HIGH] Multi-tenancy** — model `Organization`/`Tenant`, `tenant_id` na tabelach, middleware izolacji
2. **[HIGH] API layer** — `routes/api.php` + Laravel Sanctum (już zainstalowany)
3. **[HIGH] TypeScript migracja** — rename `.jsx` → `.tsx`, typy Inertia, typy API
4. **[HIGH] StripeService** — wydzielenie logiki z StripeWebhookController

### Faza 2 — Kluczowe moduły SaaS

5. **[HIGH] OpenAI integracja** — landing page generator (core value prop SaaS)
6. **[HIGH] Landing Pages moduł** — nowe tabele: `landing_pages`, `landing_page_blocks`
7. **[MEDIUM] Business Profile** — kontekst firmy dla AI generacji content
8. **[MEDIUM] Billing / Subscriptions** — Laravel Cashier (Stripe) dla planów SaaS

### Faza 3 — Jakość kodu

9. **[MEDIUM] Policy classes** — dla Lead, Client, Invoice, Contract, Project
10. **[MEDIUM] Więcej Form Requests** — dla kontrolerów portalu
11. **[MEDIUM] LeadPipelineService** — wydzielenie z PipelinePage
12. **[LOW] Repository pattern** — opcjonalne, przy dużej skali
13. **[LOW] Dark mode** — implementacja klas `dark:`

### Gotowość projektu pod SaaS: **2/10 (fundament istnieje, brak izolacji danych)**

Projekt jest solidnym, dobrze zbudowanym narzędziem dla **jednej agencji** (WebsiteExpert Ltd). Podstawy do budowania SaaS są mocne:
- Silnik automatyzacji rozbudowany i rozszerzalny
- Portal klienta kompletny i funkcjonalny
- Integracje płatności (Stripe + PayU) działające
- Kalkulator DB-driven wielojęzyczny
- 50 granularnych uprawnień Spatie

**Czego brakuje** do SaaS:
- Multi-tenancy (izolacja danych między organizacjami)
- Billing/subscriptions (plany cenowe, limity funkcji)
- OpenAI / AI generacja contentu
- API publiczne
- Landing pages jako moduł
- TypeScript (bezpieczeństwo typów przy rozbudowie)

---

*Raport wygenerowany automatycznie przez skill `laravel-react-analyst` — Digital Growth OS v1, 2026-03-31*
