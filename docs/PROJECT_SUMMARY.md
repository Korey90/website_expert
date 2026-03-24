# Podsumowanie projektu — `web-dev-app`

> Wygenerowano: 20.03.2026

---

## 1. Cel projektu

Kompletna platforma dla agencji web-dev (**Website Expert**) obsługująca:
- **Stronę marketingową** (landing page, kalkulator wyceny, formularz kontaktowy)
- **Panel CRM / admin** (Filament) do zarządzania klientami, leadami, projektami, fakturami, wycenami
- **Portal klienta** — dedykowany widok dla klientów (projekty, faktury, wyceny, wiadomości)
- **Analitykę / tracking** — GTM + GA4 + Meta Pixel z GDPR Consent Mode v2

---

## 2. Stack technologiczny

| Warstwa | Technologia |
|---|---|
| PHP | 8.3+ |
| Framework | Laravel 13 |
| Frontend | React 18 + Inertia.js 2.0 |
| Build | Vite 8 |
| CSS | Tailwind CSS 3 + @tailwindcss/forms |
| Admin panel | Filament 5.4 |
| Auth scaffolding | Laravel Breeze 2 |
| Uprawnienia | Spatie Laravel Permission 7 |
| Tłumaczenia (DB) | Spatie Laravel Translatable 6 |
| Płatności | Stripe PHP SDK 19 |
| PDF | DomPDF 3 |
| Eksport Excel/CSV | PhpSpreadsheet 5 |
| Named routes w JS | Ziggy 2 |
| WYSIWYG | TinyMCE (własny wrapper `TinyEditor.js`) |
| UI primitives | Headless UI 2 |

---

## 3. Struktura katalogów

```
web-dev-app/
├── app/
│   ├── Console/Commands/
│   ├── Filament/
│   │   ├── Pages/           ← PipelinePage, TrackingSettingsPage
│   │   ├── Resources/       ← 13 zasobów CRUD
│   │   └── Widgets/         ← 8 widgetów dashboard
│   ├── Forms/Components/
│   ├── Http/
│   │   ├── Controllers/     ← 11 kontrolerów + Auth/
│   │   ├── Middleware/
│   │   └── Requests/
│   ├── Jobs/                ← ProcessAutomationJob
│   ├── Listeners/           ← AutomationEventListener
│   ├── Mail/                ← 4 klasy Mailable
│   ├── Models/              ← 21 modeli Eloquent
│   └── Providers/
├── database/
│   ├── migrations/          ← 30 migracji
│   └── seeders/
├── resources/
│   ├── js/
│   │   ├── app.jsx          ← punkt wejścia Inertia
│   │   ├── Pages/           ← 16 stron Inertia
│   │   ├── Components/      ← komponenty UI
│   │   ├── Layouts/         ← 4 layouty
│   │   ├── Hooks/           ← 3 hooki
│   │   ├── Contexts/        ← ConsentContext
│   │   └── utils/dataLayer.js
│   └── views/               ← Blade (Filament + PDF)
├── routes/
│   ├── web.php
│   └── auth.php
└── szablon/                 ← Statyczny szablon HTML
```

---

## 4. Baza danych — Tabele

| Tabela | Opis |
|---|---|
| `users` | Użytkownicy (admin, manager, developer, portal) |
| `clients` | Klienci z danymi firmy (Companies House, VAT), statusem, źródłem, adresem |
| `contacts` | Kontakty powiązane z klientem |
| `pipeline_stages` | Etapy lejka sprzedażowego (won/lost flagi) |
| `leads` | Leady CRM — powiązane z klientem, etapem, kalkulatorem |
| `project_templates` | Szablony projektów z fazami (JSON) |
| `projects` | Projekty z tokenem portalu (64-znakowy unikalny) |
| `project_phases` | Fazy w projekcie |
| `project_tasks` | Zadania w fazach |
| `project_files` | Pliki uploadowane do projektów |
| `project_messages` | Wiadomości projektu (polimorficzny sender: User lub Contact) |
| `quotes` | Wyceny z pozycjami, rabatem, VAT |
| `quote_items` | Pozycje wyceny (ilość × cena = kwota, auto) |
| `invoices` | Faktury (statusy: draft/sent/partially_paid/paid/overdue/cancelled) |
| `invoice_items` | Pozycje faktury |
| `payments` | Płatności (Stripe `payment_intent_id`) |
| `email_templates` | Szablony emaili (subject/body jako JSON per locale) |
| `automation_rules` | Reguły automatyzacji (trigger + conditions + actions jako JSON) |
| `calculator_pricing` | Cennik kalkulatora (kategoria/klucz/koszt) |
| `pages` | Strony CMS (tytuł/treść tłumaczalne przez Spatie) |
| `site_sections` | Sekcje strony marketingowej (hero, about, services, itp.) |
| `settings` | Ustawienia k/v (klucz jako PK string, cache 1 dzień) |
| `cache`, `cache_locks` | Cache DB |
| `jobs`, `job_batches`, `failed_jobs` | Kolejki |
| `roles`, `permissions`, + tabele pivot | Spatie Permission |

---

## 5. Modele Eloquent (21)

| Model | Cechy szczególne |
|---|---|
| `User` | `HasRoles`; `canAccessPanel()` → wymaga `is_active` + rola admin/manager/developer |
| `Client` | SoftDeletes; `fullAddress` accessor; `lifetime_value`, `portal_user_id` FK |
| `Contact` | SoftDeletes; `fullName` accessor |
| `Lead` | SoftDeletes; `calculator_data` cast → array |
| `PipelineStage` | `is_won` / `is_lost` flagi |
| `Project` | SoftDeletes; auto-generuje `portal_token` na create; relacje do faz, zadań, plików, wiadomości |
| `Quote` | SoftDeletes; metoda `recalculate()` przelicza totale |
| `QuoteItem` | `amount` auto na saving |
| `Invoice` | SoftDeletes; `isOverdue()`; `recalculate()`; numer formatu `INV-2026-XXX` |
| `InvoiceItem` | `amount` auto na saving |
| `Payment` | `stripe_payment_intent_id` |
| `EmailTemplate` | JSON per locale; metoda `getForLocale($locale)` |
| `AutomationRule` | `conditions`, `actions` JSON; używane przez `ProcessAutomationJob` |
| `CalculatorPricing` | Cennik kalkulatora |
| `Page` | `HasTranslations`; pola title/content/meta jako JSON |
| `SiteSection` | `HasTranslations`; pola title/subtitle/body/button_text jako JSON; `extra` JSON |
| `Setting` | String PK; `Setting::get($key, $default)` (cache 1d); `Setting::set($key, $val)` |

---

## 6. Routes

### Publiczne (`web.php`)

| Metoda | URL | Akcja |
|---|---|---|
| GET | `/` | `WelcomeController` — strona marketingowa |
| GET | `/kalkulator` | `KalkulatorController` — standalone kalkulator |
| GET | `/p/{slug}` | `PageController@show` — strony CMS |
| GET | `/lang/{locale}` | Zmiana języka (session) |
| POST | `/contact` | `ContactController@store` |
| POST | `/calculator-lead` | `CalculatorLeadController@store` |
| POST | `/stripe/webhook` | `StripeWebhookController@handle` (CSRF-exempt) |

### Zalogowani

| Metoda | URL | Akcja |
|---|---|---|
| GET | `/dashboard` | Inertia Dashboard |
| GET/PATCH/DELETE | `/profile` | `ProfileController` |
| GET | `/invoices/{invoice}/pdf` | `InvoicePdfController` — download PDF |
| GET | `/reports/{type}/{format}` | `ReportController` — HTML/PDF/XLSX/CSV |

### Portal klienta (`/portal/*`)

| URL | Akcja |
|---|---|
| `/portal/` | `PortalController@dashboard` |
| `/portal/projects` | Lista projektów klienta |
| `/portal/projects/{id}` | Szczegóły projektu + wiadomości |
| `/portal/invoices` | Faktury klienta |
| `/portal/quotes` | Wyceny klienta |

---

## 7. Kontrolery (11)

| Kontroler | Opis |
|---|---|
| `WelcomeController` | Ładuje wszystkie SiteSection, przekazuje do `Welcome.jsx` |
| `KalkulatorController` | Renderuje standalone kalkulator |
| `PageController` | Wyświetla CMS Page po slug |
| `ContactController` | Tworzy klienta + lead, wysyła `NewLeadMail` w kolejce |
| `CalculatorLeadController` | Jak ContactController, z danymi kalkulatora |
| `PortalController` | Pełny portal klienta (5 akcji) |
| `ProfileController` | Edycja profilu (Breeze) |
| `InvoicePdfController` | Generuje PDF faktury przez DomPDF |
| `ReportController` | Raporty leads/invoices/projects × HTML/PDF/XLSX/CSV |
| `StripeWebhookController` | Obsługa webhooków Stripe (5 eventów) |

---

## 8. Strony Inertia (`resources/js/Pages/`)

| Strona | Trasa |
|---|---|
| `Welcome.jsx` | `/` |
| `Kalkulator.jsx` | `/kalkulator` |
| `CmsPage.jsx` | `/p/{slug}` |
| `Dashboard.jsx` | `/dashboard` |
| `Auth/Login.jsx` | `/login` |
| `Auth/Register.jsx` | `/register` |
| `Auth/ForgotPassword.jsx` | `/forgot-password` |
| `Auth/ResetPassword.jsx` | `/reset-password/{token}` |
| `Profile/Edit.jsx` | `/profile` |
| `Portal/Dashboard.jsx` | `/portal/` |
| `Portal/Projects.jsx` | `/portal/projects` |
| `Portal/Project.jsx` | `/portal/projects/{id}` |
| `Portal/Invoices.jsx` | `/portal/invoices` |
| `Portal/Quotes.jsx` | `/portal/quotes` |

---

## 9. Komponenty React (`resources/js/Components/`)

### Generyczne (Breeze/UI)

`ApplicationLogo`, `Checkbox`, `DangerButton`, `Dropdown`, `InputError`, `InputLabel`, `Modal`, `NavLink`, `PrimaryButton`, `ResponsiveNavLink`, `SecondaryButton`, `TextInput`, `TinyEditor`

### Marketing

| Komponent | Opis |
|---|---|
| `Navbar.jsx` | Nawigacja (dane z `navbar` SiteSection) |
| `Footer.jsx` | Stopka + przycisk "Zarządzaj cookies" |
| `CookieBanner.jsx` | Baner GDPR — granular consent (analytics/marketing/preferences) |
| `Hero.jsx` | Sekcja hero |
| `About.jsx` | Sekcja "o nas" |
| `Services.jsx` | Sekcja usług |
| `Portfolio.jsx` | Portfolio/realizacje |
| `Contact.jsx` | Formularz kontaktowy → `/contact` |
| `CostCalculator.jsx` | Kalkulator wyceny → `/calculator-lead` |
| `CtaBanner.jsx` | Baner CTA |
| `TrustStrip.jsx` | Pasek zaufania (loga, certyfikaty) |

---

## 10. Layouty

| Layout | Używany przez |
|---|---|
| `MarketingLayout.jsx` | Welcome, Kalkulator, CmsPage — zawiera Navbar + Footer + CookieBanner; opakowuje przez `ConsentContext.Provider`; inicjuje `useMetaPixel` |
| `GuestLayout.jsx` | Strony auth (Login, Register, itd.) |
| `AuthenticatedLayout.jsx` | Dashboard, Profile |
| `PortalLayout.jsx` | Portal klienta — sidebar z nawigacją |

---

## 11. Hooki, Konteksty, Narzędzia

### Hooki (`resources/js/Hooks/`)

| Hook | Opis |
|---|---|
| `useConsent.js` | Zarządzanie zgodami GDPR — `localStorage` (`cookie_consent` v1.0), push do GTM `dataLayer`, `window.gtag('consent','update',…)`, lazy-init Meta Pixel przy pierwszym marketing=granted |
| `useMetaPixel.js` | Wstrzykuje skrypt Pixel gdy `consent.marketing === true` i pixel jeszcze nie załadowany |
| `useScrollReveal.js` | IntersectionObserver — dodaje klasę `visible` do `.reveal` elementów przy 15% widoczności |

### Konteksty

| Kontekst | Opis |
|---|---|
| `ConsentContext.js` | Eksportuje `ConsentContext` i `useConsentContext()` — udostępnia stan zgód bez prop-drillingu |

### Narzędzia

| Plik | Opis |
|---|---|
| `utils/dataLayer.js` | `pushEvent(event, params)` — thin wrapper do `window.dataLayer.push()` |

---

## 12. Panel Filament

### Zasoby (13)

| Zasób | Model |
|---|---|
| `AutomationRuleResource` | AutomationRule |
| `CalculatorPricingResource` | CalculatorPricing |
| `ClientResource` | Client |
| `EmailTemplateResource` | EmailTemplate |
| `InvoiceResource` | Invoice |
| `LeadResource` | Lead |
| `PageResource` | Page |
| `PipelineStageResource` | PipelineStage |
| `ProjectResource` | Project |
| `QuoteResource` | Quote |
| `RoleResource` | Spatie Role |
| `SiteSectionResource` | SiteSection |
| `UserResource` | User |

### Strony własne (2)

| Strona | Opis |
|---|---|
| `PipelinePage` | Kanban lejka sprzedażowego — leady pogrupowane po etapach, licznik + wartość |
| `TrackingSettingsPage` | Konfiguracja GTM / GA4 / Meta Pixel / Google Ads + toggle Cookie Consent |

### Widgety dashboard (8)

`StatsOverviewWidget`, `RecentLeadsWidget`, `LeadsBySourceWidget`, `ActiveProjectsWidget`, `ProjectStatusWidget`, `RevenueChartWidget`, `OverdueInvoicesWidget`, `QuickActionsWidget`

---

## 13. Tracking & Analytics

### Aktualna konfiguracja (DB)

| Klucz | Wartość |
|---|---|
| `gtm_enabled` | `1` |
| `gtm_id` | `GTM-K749NJ7Q` |
| `pixel_enabled` | `1` |
| `pixel_id` | `1455716816214418` |
| `gads_enabled` | `0` |
| `gads_id` | *(pusty — do uzupełnienia)* |
| `cookie_consent_enabled` | `1` |

### Przepływ

```
app.blade.php
  │
  ├── window.dataLayer = [] + gtag('consent','default', {all: denied, wait_for_update: 10000})
  ├── GTM snippet (GTM-K749NJ7Q) — warunkowy z DB
  └── window._metaPixelId = '...' — warunkowy z DB

useConsent.js (mount)
  │
  ├── Czyta localStorage → jeśli consent istnieje → gtag('consent','update',…)
  ├── Pushuje 'consent_update' do dataLayer
  └── Jeśli marketing=granted → wstrzykuje fbevents.js + fbq('init') + fbq('track','PageView')

CostCalculator.jsx / Contact.jsx (po submit)
  ├── pushEvent('generate_lead', { lead_source, project_type, ... })
  └── fbq('track', 'Lead')
```

### GA4 Events

| Zdarzenie | Źródło | Parametry |
|---|---|---|
| `generate_lead` | CostCalculator | `lead_source: 'calculator'`, `project_type`, `estimate_low`, `estimate_high` |
| `generate_lead` | Contact | `lead_source: 'contact'`, `project_type` |

---

## 14. System ustawień (Settings)

- **Tabela:** `settings` (`key` STRING PK, `value` TEXT, `group` INDEX)
- **Odczyt:** `Setting::get($key, $default)` — cached `1 dzień` w DB cache
- **Zapis:** `Setting::set($key, $value)` → `updateOrCreate` + `cache()->forget("settings.{$key}")`
- **Admin UI:** Filament `TrackingSettingsPage` — `ToggleButton` + `TextInput` per usługa
- **JS:** Przez Inertia shared props (`tracking`) — `usePage().props.tracking`

---

## 15. Middleware

| Middleware | Opis |
|---|---|
| `HandleInertiaRequests` | Udostępnia `auth.user`, `locale`, `available_locales` i pełny obiekt `tracking` każdej stronie Inertia |

---

## 16. Internacjonalizacja (i18n)

- **Języki:** EN + PL (konfiguracja w `config/languages.php`)
- **Detekcja:** `Accept-Language` → session override → `/lang/{locale}` route
- **Tłumaczenia DB:** Spatie Translatable na `Page` i `SiteSection` — pola jako `{"en":"...","pl":"..."}` JSON
- **Lokalizacja Filament:** automatyczna przez Filament's built-in i18n

---

## 17. Kluczowe decyzje architektoniczne

| Decyzja | Uzasadnienie |
|---|---|
| DB-driven settings z cache | Zmiana GTM/Pixel bez deploymentu |
| Consent Mode v2 (default denied) | Zgodność z GDPR / Google EU |
| Meta Pixel lazy-loaded | Ładowany tylko po marketing=granted |
| Queue dla maili | Bezpieczeństwo — żaden mail nie blokuje response |
| Polimorficzny `sender` w wiadomościach | Wiadomości mogą wysyłać i pracownicy i kontakty klientów |
| `portal_token` (64 znaków) | Bezpieczny dostęp do portalu bez pełnego auth |
| Single `ReportController` | Leads/Invoices/Projects × 4 formaty jednym kodem |

---

## 18. Co zostało zrobione (tracking plan 22/22 ✅)

- [x] Migracja + model + seeder + Filament page dla ustawień
- [x] Consent Mode v2 (default denied) przed GTM
- [x] GTM ładowany warunkowo z DB
- [x] GA4 skonfigurowane w GTM (zweryfikowane)
- [x] `generate_lead` z obu formularzy (zweryfikowane w GA4)
- [x] Meta Pixel lazy-loaded po marketing consent (zweryfikowane przez Pixel Helper)
- [x] `fbq('track','Lead')` w obu formularzach
- [x] Cookie banner z granular consent
- [x] ConsentContext + link "Zarządzaj cookies" w Footer
- [x] `pushEvent` utility
- [x] HandleInertiaRequests sharing tracking props

## Co pozostało

- [ ] **Google Ads** — infrastruktura gotowa (`gads_enabled=0`), wymaga założenia konta Google Ads → ID w formacie `AW-XXXXXXXXX` → wpisanie w Filament → tagi w GTM
- [ ] **Opublikowanie GTM** — kontener na wersji Preview/5, wymaga publikacji na produkcję

---

## 19. Zmienne środowiskowe (`.env`)

```
APP_NAME, APP_ENV, APP_KEY, APP_DEBUG, APP_URL
APP_LOCALE, APP_FALLBACK_LOCALE
DB_CONNECTION, DB_HOST, DB_PORT, DB_DATABASE, DB_USERNAME, DB_PASSWORD
SESSION_DRIVER=database, SESSION_LIFETIME
QUEUE_CONNECTION=database
CACHE_STORE=database
MAIL_MAILER, MAIL_HOST, MAIL_PORT, MAIL_USERNAME, MAIL_PASSWORD
MAIL_FROM_ADDRESS, MAIL_FROM_NAME
MAIL_ADMIN_ADDRESS=admin@websiteexpert.co.uk
STRIPE_KEY, STRIPE_SECRET, STRIPE_WEBHOOK_SECRET
TINYMCE_API_KEY
VITE_APP_NAME
```
