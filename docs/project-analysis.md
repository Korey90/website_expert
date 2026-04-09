# Analiza projektu — Digital Growth OS (WebsiteExpert)
> Data: 2026-04-03 | Autor: GitHub Copilot (SaaS Architect Agent)
> Stack: Laravel 13 · FilamentPHP 5 · Inertia.js 2 · React 18 · Tailwind 4 · Vite 8

---

## 1. Feature Inventory

### 1.1 Autentykacja i autoryzacja

| Funkcjonalność | Kluczowe pliki | Status |
|---|---|---|
| Login / Logout (email + hasło) | `Auth/AuthenticatedSessionController.php` | ✅ zaimplementowane |
| Rejestracja z auto-tworzeniem Business | `Auth/RegisteredUserController.php`, `BusinessService` | ✅ zaimplementowane |
| Reset hasła (e-mail) | `Auth/PasswordResetLinkController.php` | ✅ zaimplementowane |
| Weryfikacja e-mail | `Auth/VerifyEmailController.php` | ✅ zaimplementowane |
| Google OAuth | `Auth/SocialAuthController.php`, `SocialAccount.php` | ✅ działa |
| Facebook OAuth | `Auth/SocialAuthController.php` | ⚠️ częściowe (kod gotowy, brak kluczy APP) |
| Hasło nullable (social-only users) | migracja `make_password_nullable_in_users_table` | ✅ zaimplementowane |
| Łączenie kont społecznościowych (connect/unlink) | `SocialAccountController.php`, `Profile/Partials/LinkedAccountsForm.jsx` | ✅ zaimplementowane |
| GDPR — usuwanie konta | `AccountDeletionService.php`, `DeleteUserForm.jsx` | ✅ zaimplementowane |
| Role Spatie | `AdminSeeder.php`: admin, manager, developer, client | ✅ zaimplementowane |
| Dostęp panelu Filament | `User::canAccessPanel()` — admin/manager/developer | ✅ zaimplementowane |

### 1.2 SaaS — Business & Multi-tenancy

| Funkcjonalność | Kluczowe pliki | Status |
|---|---|---|
| Model Business (tenant root) | `Business.php`, `BusinessUser.php` (pivot) | ✅ zaimplementowane |
| Auto-create Business przy rejestracji | `BusinessService::createForUser()`, `RegisteredUserController` | ✅ zaimplementowane |
| Auto-create Business przy OAuth | `SocialAuthController::handleCallback()` | ✅ zaimplementowane |
| BusinessProfile (brand, AI context) | `BusinessProfile.php`, `BusinessProfileService.php` | ✅ zaimplementowane |
| `currentBusiness()` helper | `app/Helpers/BusinessHelper.php` | ✅ zaimplementowane |
| `BelongsToTenant` trait | `app/Traits/BelongsToTenant.php` | ⚠️ MVP — GlobalScope zakomentowany, izolacja ręczna |
| Onboarding flow | `OnboardingController.php`, `Pages/Onboarding/` | ✅ zaimplementowane |
| API Tokens (Zapier/Make.com) | `ApiTokenController.php`, `ApiToken.php`, `Business/ApiTokens.jsx` | ✅ zaimplementowane |

### 1.3 SaaS — Landing Pages

| Funkcjonalność | Kluczowe pliki | Status |
|---|---|---|
| CRUD Landing Pages | `LandingPageController.php`, `LandingPage.php` | ✅ zaimplementowane |
| Sekcje LP (CRUD + reorder) | `LandingPageSectionController.php`, `LandingPageSection.php` | ✅ zaimplementowane |
| Publish / Unpublish | `LandingPageController::publish/unpublish()` | ✅ zaimplementowane |
| Publiczna strona LP (`/lp/{slug}`) | `PublicLandingPageController.php` + widok Blade | ✅ zaimplementowane |
| Formularz Capture na LP | `LeadCaptureController.php`, `capture_fields` JSON | ✅ zaimplementowane |
| Lead Sources (per LP) | `LeadSource.php`, `LeadSourceService.php` | ✅ zaimplementowane |
| Lead Consents (RODO) | `LeadConsent.php`, `LeadConsentService.php` | ✅ zaimplementowane |
| AI Generator (OpenAI) | `AiLandingGeneratorController.php`, `GenerateLandingService.php` | ✅ zaimplementowane |
| OpenAI client + prompt builder | `OpenAiLandingClient.php`, `OpenAiLandingPromptBuilder.php` | ✅ zaimplementowane |
| JSON normalizer + validator | `LandingPageJsonNormalizer.php`, `LandingPageJsonSchemaValidator.php` | ✅ zaimplementowane |
| AI Variants (zapis przed zapisem LP) | `LandingPageGenerationVariant.php`, `LandingPageAiGeneration.php` | ✅ zaimplementowane |
| Regeneracja sekcji AI | `AiLandingGeneratorController::regenerateSection()` | ✅ zaimplementowane |
| Slug Service (globally unique) | `LandingPageSlugService.php` | ✅ zaimplementowane |
| Tenant isolation na LP routes | middleware `landing-page.tenant` | ✅ zaimplementowane |
| Filament LP Resource (admin) | `LandingPageResource.php` | ✅ zaimplementowane |
| A/B testing | — | ❌ brak |
| Custom domains | — | ❌ brak |
| Analytics LP (views/conversions count) | kolumny `views_count`, `conversions_count` | ⚠️ kolumny są, brak trackowania |

### 1.4 SaaS — Lead Capture & CRM

| Funkcjonalność | Kluczowe pliki | Status |
|---|---|---|
| Capture lead z LP (`POST /leads`) | `LeadCaptureController.php`, `PublicLeadCaptureService.php` | ✅ zaimplementowane |
| PublicLeadCaptureService | deduplication (24h cache), `form_data`, `source` | ✅ zaimplementowane |
| UTM parameters | `lead_sources` tabela | ⚠️ bug — UTM nie forwarded do axios POST |
| `form_data` null bug | `LeadService::createFromSource()` | ⚠️ bug znany z debug-report |
| Lead detail view w portalu | `Portal/LeadController.php`, `Portal/Leads/Show.jsx` | ✅ zaimplementowane |
| Widok leadów z LP (per LP) | `LandingPages/Show.jsx` — tabela recentLeads | ✅ zaimplementowane |
| Lead → CRM pipeline | `LeadService.php`, `ProcessAutomationJob` | ✅ zaimplementowane |
| Zarządzanie leadami (Filament) | `LeadResource.php`, `PipelinePage.php` | ✅ zaimplementowane |
| LeadResource — tenant scoping | `LeadResource::getEloquentQuery()` | ✅ zaimplementowane |

### 1.5 Portal klienta

| Funkcjonalność | Kluczowe pliki | Status |
|---|---|---|
| Dashboard portalu | `Portal/DashboardController.php`, `Portal/Dashboard.jsx` | ✅ zaimplementowane |
| Projekty klienta | `Portal/ProjectController.php`, `Portal/Projects.jsx` | ✅ zaimplementowane |
| Szczegół projektu + messaging | `Portal/Project.jsx` | ✅ zaimplementowane |
| Faktury klienta | `Portal/InvoiceController.php`, `Portal/Invoices.jsx` | ✅ zaimplementowane |
| Płatność faktur (Stripe + PayU) | `Portal/PaymentController.php`, `Portal/PayInvoice.jsx` | ✅ zaimplementowane |
| Oferty (akceptacja/odrzucenie) | `Portal/QuoteController.php`, `Portal/Quote.jsx` | ✅ zaimplementowane |
| Umowy z podpisem elektronicznym | `Portal/ContractController.php`, `Portal/Contract.jsx` | ✅ zaimplementowane |
| Preferencje powiadomień klienta | `Portal/NotificationController.php`, `Portal/NotificationSettings.jsx` | ✅ zaimplementowane |
| Layout portalu | `Layouts/PortalLayout.jsx` z i18n EN/PL/PT | ✅ zaimplementowane |
| Sekcja "Growth Tools" w portalu | `PortalLayout.jsx` — nawigacja sidebar | ✅ zaimplementowane |

### 1.6 CRM (Filament — tylko dla agencji)

| Funkcjonalność | Kluczowe pliki | Status |
|---|---|---|
| Zarządzanie klientami | `ClientResource.php`, `Client.php` | ✅ zaimplementowane |
| Zarządzanie leadami + pipeline | `LeadResource.php`, `PipelinePage.php` | ✅ zaimplementowane |
| Notatki leadów (pinowanie) | `LeadNote.php` | ✅ zaimplementowane |
| Aktywność leadów (timeline) | `LeadActivity.php` | ✅ zaimplementowane |
| Checklista etapów pipeline | `LeadChecklistItem.php` | ✅ zaimplementowane |
| Budget range | `budget_min`, `budget_max` w `leads` | ✅ zaimplementowane |
| Automatyczne przypisanie leadów | `NotifyLeadOwnerListener.php` | ✅ zaimplementowane |

### 1.7 Projekty

| Funkcjonalność | Kluczowe pliki | Status |
|---|---|---|
| Projekty CRUD | `ProjectResource.php`, `Project.php` | ✅ zaimplementowane |
| Fazy + zadania | `ProjectPhase.php`, `ProjectTask.php` | ✅ zaimplementowane |
| Pliki + messaging | `ProjectFile.php`, `ProjectMessage.php` | ✅ zaimplementowane |
| Szablony projektów | `ProjectTemplate.php` | ✅ zaimplementowane |
| Task board Kanban (Filament) | — | ⚠️ brak dedykowanego widoku w Filament |

### 1.8 Finanse

| Funkcjonalność | Kluczowe pliki | Status |
|---|---|---|
| Faktury + pozycje + PDF | `Invoice.php`, `InvoicePdfController.php`, DomPDF | ✅ zaimplementowane |
| Oferty | `Quote.php`, `QuoteItem.php` | ✅ zaimplementowane |
| Umowy z interpolacją | `Contract.php`, `ContractInterpolationService.php` | ✅ zaimplementowane |
| Płatności Stripe + PayU | `StripeWebhookController.php`, `PayuService.php` | ✅ zaimplementowane |
| Raporty (HTML/PDF/XLSX/CSV) | `ReportController.php` — 12 tras | ✅ zaimplementowane |

### 1.9 Panel administracyjny (Filament 5)

| Obszar | Resources / Pages | Status |
|---|---|---|
| CRM | ClientResource, LeadResource, ContractResource, PipelinePage | ✅ zaimplementowane |
| Projekty | ProjectResource, ProjectTemplateResource | ✅ zaimplementowane |
| Finanse | InvoiceResource, QuoteResource, PaymentResource | ✅ zaimplementowane |
| SaaS LP | LandingPageResource | ✅ zaimplementowane |
| Powiadomienia | NotificationResource | ✅ zaimplementowane |
| Szablony | EmailTemplateResource, SmsTemplateResource, ContractTemplateResource | ✅ zaimplementowane |
| Automatyzacje | AutomationRuleResource | ✅ zaimplementowane |
| CMS | PageResource, SiteSectionResource | ✅ zaimplementowane |
| Kalkulator | CalculatorPricingResource, CalculatorStepsResource, CalculatorStringsResource, CalculatorAdminPage | ✅ zaimplementowane |
| Użytkownicy/Role/Uprawnienia | UserResource, RoleResource, PermissionResource | ✅ zaimplementowane |
| Ustawienia | IntegrationSettingsPage, LegalSettingsPage, PaymentSettingsPage, TrackingSettingsPage | ✅ zaimplementowane |
| Raporty | ConversionReportPage, SessionResource | ✅ zaimplementowane |
| Dashboard Widgets (13) | StatsOverviewWidget, RecentLeadsWidget, OverdueInvoicesWidget, ActiveProjectsWidget, RevenueChartWidget, LeadsBySourceWidget, ProjectStatusWidget, QuickActionsWidget, ProjectDeadlinesWidget, StaleLeadsWidget, CalculatorPricingTableWidget, CalculatorStepsTableWidget, CalculatorStringsTableWidget | ✅ zaimplementowane |

### 1.10 Frontend publiczny (Marketing)

| Funkcjonalność | Kluczowe pliki | Status |
|---|---|---|
| Strona główna z sekcjami z DB | `WelcomeController.php`, `Welcome.jsx` | ✅ zaimplementowane |
| SaaS sekcja marketingowa | `Components/Marketing/SaasLandingSection.jsx` | ✅ zaimplementowane |
| Kalkulator kosztów V2 (DB-driven) | `CostCalculatorV2.jsx`, `KalkulatorController.php` | ✅ zaimplementowane |
| CMS strony (`/p/{slug}`) | `PageController.php`, `CmsPage.jsx` | ✅ zaimplementowane |
| Formularz kontaktowy | `ContactController.php` | ✅ zaimplementowane |
| Cookie banner + consent | `CookieBanner.jsx`, `ConsentContext.js` | ✅ zaimplementowane |
| Language switcher (EN/PL/PT) | `/lang/{locale}`, `session` | ✅ zaimplementowane |
| Dark / Light mode | `useThemeMode.js`, Tailwind `dark:` | ✅ zaimplementowane |

### 1.11 Powiadomienia

| Funkcjonalność | Kluczowe pliki | Status |
|---|---|---|
| In-app DB notifications (Filament) | `CustomDatabaseNotifications.php`, `DatabaseNotification.php` | ✅ zaimplementowane |
| `LeadCapturedNotification`, `LeadAssignedNotification` | `app/Notifications/` | ✅ zaimplementowane |
| Polling JS (keepalive) | `resources/js/admin/notifications.js` | ✅ zaimplementowane |
| Preferencje komunikacji klienta | `ClientNotificationGate.php` | ✅ zaimplementowane |
| WebSocket (Laravel Reverb) | `config/reverb.php`, `routes/channels.php` | ⚠️ skonfigurowany, nieaktywny runtime |

### 1.12 Automatyzacje i kolejki

| Funkcjonalność | Kluczowe pliki | Status |
|---|---|---|
| Engine: trigger → conditions → actions | `app/Automation/`, `ProcessAutomationJob.php` | ✅ zaimplementowane |
| Triggery: lead, project, invoice, quote, contract | `AutomationEventListener.php` | ✅ zaimplementowane |
| Akcje: email, SMS, notify_admin, change_status, create_portal_access, add_tag | `app/Automation/Actions/` | ✅ zaimplementowane |
| Usuwanie PII z LeadSource | `CleanLeadSourcePiiJob.php` | ✅ zaimplementowane |
| Events dla LP | `LandingPagePublished.php`, `LeadCaptured.php`, `LeadAssigned.php` | ✅ zaimplementowane |

### 1.13 Integracje zewnętrzne

| Integracja | Paczka | Kluczowe pliki | Status |
|---|---|---|---|
| Stripe | `stripe/stripe-php ^19.4` | `StripeWebhookController.php`, `Portal/PaymentController.php` | ✅ działa |
| PayU | własna HTTP | `PayuService.php`, `PayuWebhookController.php` | ✅ działa |
| Twilio SMS | `twilio/sdk ^8.11` | `SmsService.php` | ✅ działa |
| OpenAI (`gpt-4o-mini`) | cURL wewnętrzny | `OpenAiLandingClient.php`, `config/services.php` | ✅ działa (klucz w `.env`) |
| Google OAuth | `laravel/socialite *` | `SocialAuthController.php` | ✅ działa |
| Facebook OAuth | `laravel/socialite *` | `SocialAuthController.php` | ⚠️ kod gotowy, brak kluczy |
| DomPDF | `barryvdh/laravel-dompdf ^3.1` | `InvoicePdfController.php` | ✅ działa |
| PhpSpreadsheet | `phpoffice/phpspreadsheet ^5.5` | `ReportController.php` | ✅ działa |
| GTM / GA4 / Meta Pixel | — | `TrackingSettingsPage.php`, `useMetaPixel.js` | ✅ działa |
| Laravel Reverb (WebSocket) | wbudowany | `config/reverb.php` | ⚠️ skonfigurowany, nieaktywny runtime |
| Spatie Translatable | `^6.13` | `Page.php`, `SiteSection.php` | ✅ działa |
| Spatie Permission | `^7.2` | `AdminSeeder.php`, `User.php` | ✅ działa |
| Ziggy (JS routes) | `tightenco/ziggy ^2.0` | `vite.config.js`, `app.jsx` | ✅ działa |

### 1.14 Wielojęzyczność

| Obszar | Metoda | Status |
|---|---|---|
| Portal klienta (EN/PL/PT) | `lang/en/portal.php`, `lang/pl/portal.php`, `lang/pt/portal.php` | ✅ |
| Auth widoki (EN/PL/PT) | `lang/{en,pl,pt}/auth.php`, `const T` obiekt w JSX | ✅ |
| LP moduł (EN/PL/PT) | `lang/{en,pl,pt}/landing_pages.php` | ✅ |
| Modele Page, SiteSection | `spatie/laravel-translatable` | ✅ |
| Kalkulator | kolumny `label_en`, `label_pl`, `label_pt` w DB | ✅ |
| `usePortalTrans` hook | `Hooks/usePortalTrans.js` | ✅ |
| Backend `__()` | systematyczne użycie w LP/portal | ✅ |
| Filament panel | domyślnie EN (Filament default) | ⚠️ tylko EN |

---

## 2. Architektura backendu

### 2.1 Modele i relacje

```
User ─────────────────────── HasRoles (Spatie)
  │                       ── socialAccounts: HasMany(SocialAccount) [OAuth]
  │                       ── businesses: BelongsToMany(Business) via business_users
  │                       ── currentBusiness(): Business [helper]
  │
Business ─────────────────── users: BelongsToMany(User)
  │    [tenant root]      ── profile: HasOne(BusinessProfile)
  │                       ── landingPages: HasMany(LandingPage)
  │                       ── HasUlids, SoftDeletes
  │
BusinessProfile ──────────── brand_colors, tone_of_voice, target_audience, ai_context_cache (JSON)
  │
LandingPage ──────────────── business: BelongsTo(Business) [BelongsToTenant]
  │                       ── sections: HasMany(LandingPageSection)
  │                       ── leads: HasMany(Lead) via landing_page_id
  │                       ── leadSource: HasOne(LeadSource)
  │                       ── currentGeneration: BelongsTo(LandingPageAiGeneration)
  │                       ── SoftDeletes
  │
LandingPageAiGeneration ──── variants: HasMany(LandingPageGenerationVariant)
LandingPageGenerationVariant ── generation: BelongsTo
  │
Lead ─────────────────────── business: BelongsTo(Business)
  │                       ── landingPage: BelongsTo(LandingPage)
  │                       ── source: BelongsTo(LeadSource)
  │                       ── client: BelongsTo(Client) [CRM]
  │                       ── stage: BelongsTo(PipelineStage)
  │                       ── activities: HasMany(LeadActivity)
  │                       ── notes: HasMany(LeadNote)
  │                       ── consents: HasMany(LeadConsent)
  │
Client ───────────────────── leads, projects, invoices, quotes, contracts, contacts
  │                       ── portalUser: BelongsTo(User) via portal_user_id
  │                       ── SoftDeletes, CascadeDelete w booted()
  │
Project ──────────────────── phases → tasks, files, messages, invoices
Contract ─────────────────── contractTemplate: BelongsTo
AutomationRule ───────────── trigger_event, conditions (JSON), actions (JSON)
SocialAccount ────────────── user: BelongsTo(User), provider, provider_id
```

**Modele używające `BelongsToTenant`**: `LandingPage`, `Lead`, `LeadSource`, `ApiToken`, `BusinessProfile`.

**GlobalScope tenant-isolation**: zakomentowany celowo — MVP działa przez ręczne scopy i `currentBusiness()`. Gotowe do aktywacji w v1.1.

### 2.2 Service layer

`app/Services/` — dojrzała warstwa serwisów (22 klasy):

| Serwis | Odpowiedzialność |
|---|---|
| `BusinessService` | Tworzenie Business, auto-assign ról, onboarding |
| `BusinessProfileService` | getOrCreate, buildAiContext, update |
| `LandingPageService` | CRUD LP, statusy, soft-delete |
| `LandingPageSectionService` | CRUD sekcji, reorder |
| `LandingPageSlugService` | Globally-unique slug generation |
| `GenerateLandingService` | Orchestracja AI: prompt → OpenAI → normalize → validate → DB |
| `OpenAiLandingClient` | HTTP calls do OpenAI API, error handling |
| `OpenAiLandingPromptBuilder` | Budowanie system/user promptów z business context |
| `LandingPageJsonNormalizer` | Normalizacja odpowiedzi OpenAI do expected schema |
| `LandingPageJsonSchemaValidator` | Walidacja znormalizowanego JSON |
| `LeadService` | Create/update lead, CRM pipeline move |
| `LeadSourceService` | Tworzenie LeadSource per landing page |
| `LeadConsentService` | Zapis zgód RODO |
| `PublicLeadCaptureService` | Lead z public LP: deduplication, UTM, form_data |
| `LeadCaptureService` | Lead z formularza kontaktowego / kalkulatora |
| `ApiTokenService` | CRUD tokenów Sanctum dla business |
| `AccountDeletionService` | GDPR-compliant usuwanie konta + email |
| `SmsService` | Twilio send/normalize, flaga enabled |
| `PayuService` | OAuth2, zamówienia PayU |
| `ClientNotificationGate` | Preferencje komunikacji przed wysyłką |
| `ContractInterpolationService` | Interpolacja placeholderów w umowach |

### 2.3 Kontrolery

Większość kontrolerów jest cienka — delegują do serwisów. Wyjątki:
- `StripeWebhookController` — zawiera logikę aktualizacji płatności. **[MEDIUM]** — do ekstrakcji do `StripePaymentService`
- `ReportController` — bezpośrednie zapytania Eloquent. **[LOW]** — akceptowalne dla raportów

`BasePortalController` zapewnia `currentBusiness()` i `$client` dla wszystkich kontrolerów portalu.

### 2.4 Actions, Data Objects, Traits

- `app/Actions/CreateLeadAction.php` — poprawny wzorzec Action
- `app/Data/LandingPage/` — DTO: `GenerateLandingData`, `RegenerateLandingSectionData`, `SaveGeneratedLandingData`
- `app/Traits/BelongsToTenant` — auto-fill `business_id` przy tworzeniu modelu
- `app/Automation/ConditionEvaluator.php` + `Actions/` — oddzielna warstwa automatyzacji

### 2.5 Kolejki, eventy, listenery

| Komponent | Typ | Cel |
|---|---|---|
| `ProcessAutomationJob` | ShouldQueue | Wykonanie reguł automatyzacji |
| `CleanLeadSourcePiiJob` | ShouldQueue | Usuwanie PII z lead_sources |
| `BusinessCreated`, `BusinessProfileUpdated` | Event | Webhook/cache invalidation |
| `LandingPagePublished` | Event | Notyfikacje, tracking |
| `LeadCaptured`, `LeadAssigned` | Event | Notifications + automation trigger |
| `AutomationEventListener` | Listener | Wyzwalanie ProcessAutomationJob |
| `NotifyLeadOwnerListener` | Listener | Email/notifications przy przypisaniu leada |
| `ClientActivityListener` | Listener | Log aktywności klientów |

---

## 3. Architektura frontendu

**Stack**: Inertia.js 2 + React 18 + Tailwind CSS 4 + Vite 8. Brak Livewire w UI.

### 3.1 Layouty (4 pliki, 2 aktywne)

| Layout | Użycie | Stan |
|---|---|---|
| `MarketingLayout.jsx` | Strona główna, auth widoki | ✅ aktywny |
| `PortalLayout.jsx` | Cały portal `/portal/*` + landing pages | ✅ aktywny |
| `AuthenticatedLayout.jsx` | Legacy — nieużywany po refaktorze | ⚠️ legacy |
| `GuestLayout.jsx` | Legacy po Breeze — nieużywany | ⚠️ legacy |

### 3.2 Strony (Pages/)

| Obszar | Pliki | Layout |
|---|---|---|
| Auth | `Auth/{Login,Register,ForgotPassword,...}.jsx` | MarketingLayout |
| Business | `Business/{Profile,Settings,ApiTokens}.jsx` | PortalLayout |
| Landing Pages | `LandingPages/{Index,Create,Edit,Show,AiGenerator}.jsx` | PortalLayout |
| Portal | `Portal/{Dashboard,Projects,Invoices,Quotes,Contracts,...}.jsx` | PortalLayout |
| Portal Leads | `Portal/Leads/Show.jsx` | PortalLayout |
| Onboarding | `Onboarding/` | — |
| Profile | `Profile/Edit.jsx` + 4 partials | MarketingLayout |
| Public | `Welcome.jsx`, `CmsPage.jsx`, `Kalkulator.jsx` | MarketingLayout |

### 3.3 Hooki custom

| Plik | Cel |
|---|---|
| `useAiLandingGenerator.js` | Stan i logika AI generatora |
| `useApiTokens.js` | CRUD API tokenów |
| `useLandingPageTrans.js` | i18n LP |
| `usePortalTrans.js` | i18n portalu |
| `useMetaPixel.js` | Meta Pixel tracking |
| `useConsent.js` | Cookie consent |
| `useThemeMode.js` | Dark/light mode |
| `useScrollReveal.js` | Animacje scroll |

### 3.4 TypeScript

**Nieużywany** — projekt jest w czystym JavaScript (.jsx). Brak plików `.tsx` ani `tsconfig.json`. Typy propsów nieokreślone.

### 3.5 Stan globalny

Brak Zustand/Redux. Stan zarządzany lokalnie przez `useState`/`useReducer` i custom hooki. `Inertia.js props` jako główny transport danych. Jeden kontekst: `ConsentContext.js`.

---

## 4. Role i uprawnienia (Spatie Permission 7.2)

| Rola | Kluczowe uprawnienia | Dostęp |
|---|---|---|
| `admin` | wszystkie (~70 uprawnień) | Filament panel + cały portal |
| `manager` | CRM, Finanse, Projekty, LP, Leads — bez manage_roles, delete_users, export_leads | Filament panel |
| `developer` | view_* CRM/Finance, edit_projects, view_landing_pages, view_lead_sources | Filament panel |
| `client` | `view/manage/publish_landing_pages`, `generate_landing_pages_ai`, `view/create_leads` | tylko portal klienta |

**Polityki**:
- `LandingPagePolicy.php` — viewAny, create, update, delete, publish, generateAi
- `LeadPolicy.php` — view, create, update, delete

**Seedery**: `AdminSeeder.php` — role, uprawnienia, admin user, domyślny business, pipeline stages, szablony projektów.

---

## 5. Multi-tenancy — stan obecny

### Architektura: Single-DB z `business_id`

```
businesses
    │
    ├── business_users (pivot: role, is_active)
    │       └── users
    ├── business_profiles
    ├── landing_pages ──── landing_page_sections
    │                  ──── leads ──── lead_activities, lead_notes, lead_consents
    ├── lead_sources
    └── api_tokens
```

### Mechanizmy izolacji

| Mechanizm | Status | Ryzyko |
|---|---|---|
| `BelongsToTenant` trait — auto-fill `business_id` przy create | ✅ aktywny | brak izolacji READ |
| GlobalScope (`BusinessScope`) | ❌ zakomentowany | **HIGH** — możliwy wyciek danych |
| `currentBusiness()` helper | ✅ używany w kontrolerach | ręczna dyscyplina |
| `middleware('landing-page.tenant')` | ✅ | chroni LP routes |
| Kontrolery portalu — ręczny check `business_id` | ✅ np. `Portal/LeadController` | manualne, podatne na pominięcie |

---

## 6. Testy

| Obszar | Pliki | Przybliżona liczba |
|---|---|---|
| Auth | 6 plików w `Feature/Auth/` | ~30 |
| Landing Pages | 8 plików w `Feature/LandingPage/` | ~80 |
| Portal | 5 plików w `Feature/Portal/` | ~25 |
| CRM / Leads | `Feature/Leads/`, `FullLeadWorkflowTest.php` | ~30 |
| Automatyzacje | `AutomationActionTest.php`, `AutomationTriggerTest.php` | ~20 |
| Raporty | 3 pliki report tests | ~15 |
| Kalkulator | `CalculatorLeadTest.php` | ~5 |
| **SUMA** | **~21 plików Feature** | **~221 potwierdzonych** |

Vitest (frontend unit tests) + PHPUnit 12.5 (backend Feature/Unit tests).

---

## 7. Ocena jakości kodu

| Aspekt | Ocena | Uzasadnienie |
|---|---|---|
| Separacja warstw (MVC + service layer) | **DOBRY** | 22 serwisy, cienkie kontrolery. Wyjątek: StripeWebhookController |
| Pokrycie testami | **DOBRY** | ~221 testów Feature, LP flow z mockami OpenAI |
| TypeScript | **WYMAGA POPRAWY** | Brak `.tsx`, brak typizacji propsów — czysty JSX |
| Konwencje nazewnicze | **DOBRY** | PSR-4, snake_case DB, camelCase JS |
| Wielojęzyczność (i18n) | **DOBRY** | 3 języki w `lang/`, `usePortalTrans`, `const T` w JSX |
| Dokumentacja kodu | **ŚRODKOWY** | Docbloki w kluczowych serwisach, brak w modelach i widokach |
| Obsługa błędów | **DOBRY** | `LandingPageGenerationException`, error boundaries w AI generatorze |
| Multi-tenancy izolacja | **ŚRODKOWY** | GlobalScope zakomentowany — izolacja ręczna, ryzyko wycieków |
| Bezpieczeństwo | **DOBRY** | CSRF exempt tylko dla webhooków, throttle na lead capture, GDPR delete, open-redirect guard |

---

## 8. Porównanie: stan obecny vs. planowany SaaS

| Moduł SaaS | Planowany | Stan | Braki |
|---|---|---|---|
| **Business Profile** | brand colors, logo, tone of voice, target audience, AI context | ✅ **w pełni** | — |
| **AI Landing Page Generator** | generacja z profilu firmy + OpenAI + variants | ✅ **w pełni** | preview form submit bug |
| **Landing Pages Management** | CRUD, publish, sections | ✅ **w pełni** | A/B testing, custom domains, LP analytics tracking |
| **Lead Capture** | formularz na LP, form_data, UTM, deduplication | ⚠️ **~80%** | UTM forwarding bug, form_data null bug |
| **Lead Management w portalu** | widok leadów, szczegóły | ✅ **w pełni** | — |
| **CRM + Sales Pipeline** | pipeline Kanban, lead → CRM | ✅ **w pełni** | — |
| **Multi-tenancy** | izolacja danych business_id | ⚠️ **MVP** | GlobalScope nieaktywny |
| **Billing / Subskrypcje SaaS** | plan, trial, Stripe Billing | ❌ **brak** | `Business.plan` istnieje, brak Laravel Cashier |
| **Onboarding** | guided setup nowego tenanta | ✅ **w pełni** | — |
| **API Tokens (Zapier/Make)** | external integrations | ✅ **w pełni** | — |
| **Wielojęzyczność SaaS** | EN/PL/PT | ✅ **w pełni** | brak PT w Filament |
| **Portal klienta** | projekty, faktury, oferty, umowy | ✅ **w pełni** | — |
| **OAuth (Google/Facebook)** | social login | ⚠️ **~50%** | Google działa, Facebook bez kluczy |

---

## 9. Znane bugi do naprawy

| Bug | Priorytet | Lokalizacja |
|---|---|---|
| UTM parameters tracone przy submicie LP formularza | **HIGH** | `Components/Lead/LeadCapture/FormSection.jsx` — axios POST nie przekazuje `window.location.search` |
| `form_data` zawsze null w CRM | **HIGH** | `LeadService::createFromSource()` — nie mapuje `form_data` z `$validated` |
| `lp_captured` activity — brak UTM metadata | **MEDIUM** | `PublicLeadCaptureService` — czyta UTM z `$validated` zamiast `$sourceData` |
| AI preview formularza można wysłać na slug `ai-draft` | **MEDIUM** | guard w `PublicLandingPageController::submit()` |
| Facebook OAuth — brak kluczy APP | **LOW** | `.env` — `FACEBOOK_APP_ID`, `FACEBOOK_APP_SECRET` |
| LP `views_count`/`conversions_count` nieinkrementowane | **MEDIUM** | `PublicLandingPageController::show()` i `submit()` |

---

## 10. Ryzyka skalowania

| Ryzyko | Lokalizacja | Priorytet | Rozwiązanie |
|---|---|---|---|
| **GlobalScope tenant wyłączony** — możliwy wyciek danych | `BelongsToTenant.php` | **HIGH** | Aktywować `BusinessScope` GlobalScope, dodać testy izolacji |
| **Brak planu SaaS** — `Business.plan` bez egzekucji limitów | `Business.php` | **HIGH** | `PlanService` + gate'y przy tworzeniu LP i AI generacji |
| **Brak Stripe Billing** — monetyzacja niemożliwa | — | **HIGH** | Laravel Cashier + subskrypcje planów (`free`, `pro`, `agency`) |
| **StripeWebhookController** — logika w kontrolerze | `StripeWebhookController.php` | **MEDIUM** | Wydzielić `StripePaymentService` |
| **Brak TypeScript** — błędy typów w runtime | `resources/js/` | **MEDIUM** | Migracja do `.tsx` stopniowo od nowych modułów |
| **AI generation bez rate-limit per tenant** | `AiLandingGeneratorController.php` | **MEDIUM** | Limit generacji per business per miesiąc |
| **`currentBusiness()` przy API/token auth** — zwraca null | `BusinessHelper.php` | **MEDIUM** | Obsługa kontekstu tenant dla tokenów API |
| **N+1 queries w portalu** | `PortalDashboardController.php` | **LOW** | Dodać `with()` dla kluczowych relacji |
| **LP sekcje bez wersjonowania** | `LandingPageSection.php` | **LOW** | Activity log lub historia sekcji |

---

## 11. Rekomendacje i priorytety

### 🔴 KRYTYCZNE (blokują go-live SaaS)

1. **Naprawić 4 bugi LP→CRM** — UTM, form_data, lp_captured, AI preview submit
2. **Aktywować GlobalScope tenant isolation** — `BusinessScope` GlobalScope
3. **Zaimplementować Stripe Billing** — Laravel Cashier, plany, trial, limity per plan
4. **LP Analytics** — inkrementacja `views_count`/`conversions_count`

### 🟠 WAŻNE (sprint 2)

5. **Plan gates** — limit LP per plan (`free`: 3, `pro`: unlimited)
6. **AI generation rate-limit** per business per miesiąc
7. **Facebook OAuth** — dodać klucze do `.env`
8. **TypeScript migration** — zacząć od `LandingPages/*`, `Portal/Leads/`

### 🟡 UZUPEŁNIAJĄCE (v1.5+)

9. Custom domains dla LP
10. A/B testing LP
11. Stripe Webhook → StripeService (refactor)
12. Task board Kanban w Filament
13. Filament panel wielojęzyczny (PL/PT oprócz EN)

---

*Raport wygenerowany: 2026-04-03 przez GitHub Copilot (SaaS Architect Agent)*
*Testy: 221 passing | Stack: Laravel 13 + FilamentPHP 5 + Inertia.js 2 + React 18 + Tailwind 4*

