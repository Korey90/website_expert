# Refactor Plan — Digital Growth OS
## Status: APPROVED — W REALIZACJI
**Data:** 2026-04-03 (aktualizacja)  
**Kontekst:** Pre-SaaS refaktoryzacja — service layer + GlobalScope + Stripe Billing  
**Powiązane dokumenty:** `docs/project-analysis.md`, `docs/architecture-plan.md`

### ZREALIZOWANE (od ostatniej aktualizacji):
- ✅ `app/Services/Business/BusinessProfileService.php` — istnieje
- ✅ `app/Services/Business/BusinessService.php` — istnieje
- ✅ `app/Services/LandingPage/*` (5 serwisów) — zaimplementowane
- ✅ `app/Services/Account/AccountDeletionService.php` — zaimplementowany
- ✅ `LandingPageResource.php` — poprawiony scope dla ról admin/manager/developer
- ✅ Portal LP pages (`PortalLayout`) — zmigrated

### TODO W SPRINCIE (kolejność wdrożenia):
1. `app/Scopes/BusinessScope.php` — aktywacja GlobalScope tenant isolation
2. Fix 4 bugów LP→CRM (UTM, form_data, lp_captured, preview)
3. LP Analytics (views_count / conversions_count increment)
4. `PaymentNotificationService` — usunąć duplikat Mail+SMS z Stripe+PayU
5. Stripe Billing (Laravel Cashier) — plany SaaS

---

## 0. Streszczenie

Projekt jest w dobrym stanie jako agencyjny CRM, ale wymaga strukturalnego przygotowania przed transformacją w SaaS. Główne problemy to: **duplikacja logiki w kontrolerach** (PortalController vs Portal/*, StripeWebhook vs PayuWebhook), **brak service layer** dla płatności i raportów, **logika biznesowa w Filament Pages** (PipelinePage 388 linii), **dwa jednoczesne kalkulatory** bez jasnego właściciela, oraz **brak TypeScript** utrudniający skalowanie frontendu.

Poniższy plan NIE wymaga przepisania systemu od zera — to seria precyzyjnych wyciągnięć do warstwy serwisowej.

---

## 1. Audyt backendu

### 1.1 Fat Controllers

| Kontroler | Linie | Problem |
|---|---|---|
| `PortalController.php` | 447 | God controller — powtarza logikę z `Portal/DashboardController.php`; zawiera dashboard, projekty, faktury, oferty, kontrakty, wiadomości w jednym pliku |
| `ReportController.php` | 210 | Zapytania Eloquent bezpośrednio w akcjach; brak query builderów/service layer |
| `StripeWebhookController.php` | 184 | Pełna logika płatności: update Payment, `invoice->recalculate()`, wysyłka Mail + SMS w jednym kontrolerze |
| `WelcomeController.php` | 176 | Data transformation — ręczne mapowanie 13 sekcji CMS na tablice PHP; nadaje się do CMS Data Service |
| `PayuWebhookController.php` | 115 | **Duplikat** logiki z `StripeWebhookController` — identyczny kod wysyłki Mail/SMS kopiowany dosłownie |
| `KalkulatorController.php` | 105 | Złożona transformacja danych DB → JSON; logika mapowania `categoryMap` w kontrolerze |

**Szczegółowy opis krytycznych przypadków:**

**`PortalController.php` — duplikacja z `Portal/*`:**
```
// /app/Http/Controllers/PortalController.php — STARY, LEGACY
public function dashboard(): Response { ... }  // identyczny jak Portal/DashboardController::index()
public function projects(): Response { ... }
// itd. — ten plik jest pozostałością sprzed refaktoru na Portal/* 
// routes/web.php prawdopodobnie wskazuje na stary lub nowy kontroler
```
→ **Akcja**: usunąć `PortalController.php`, trasy przepiąć na `Portal/*` (już istnieje odpowiednik).

**`StripeWebhookController` + `PayuWebhookController` — duplikat notyfikacji:**
```php
// StripeWebhookController::sendPaymentNotifications() — linia 161
// PayuWebhookController::notify() — linia 87
// IDENTYCZNA logika: Mail::to()->send(new PaymentReceivedMail($payment)) + SmsService->send()
```
→ **Akcja**: wyciągnąć do `PaymentNotificationService`.

### 1.2 Brak Service Layer

Aktualny stan `app/Services/`:
```
SmsService.php                  (90 linii) — OK, dobrze napisany
PayuService.php                 (139 linii) — OK, dobra abstrakcja
ClientNotificationGate.php      (45 linii) — OK, util class
ContractInterpolationService.php (112 linii) — OK, dobry wzorzec
```
Brakuje serwisów dla:
- Obsługi płatności (Stripe + PayU notification handling)
- Raportów (query building + formatowanie)
- Kalkulatora (data assembly per locale)
- Pipeline CRM (moveStage, markWon, markLost, email)
- CMS sections (pobieranie i transformacja sekcji)

### 1.3 Tight Coupling

| Miejsce | Problem |
|---|---|
| `StripeWebhookController` | `new SmsService()` — bezpośrednia instancjacja zamiast DI |
| `PayuWebhookController` | `new PayuService()` i `new SmsService()` — bez DI |
| `PipelinePage::moveStage()` | `Mail::to()->send()` bezpośrednio w Filament Page — wymaga dostępu do maila |
| `StripeWebhookController` | Pobiera `Setting::get('stripe_webhook_secret')` z DB w konstrukcji eventu |
| `Portal/PaymentController` | `\Stripe\Stripe::setApiKey()` bezpośrednio w akcji kontrolera |

### 1.4 Duplikacja kodu

| Para | Problem |
|---|---|
| `StripeWebhookController::sendPaymentNotifications()` + `PayuWebhookController::notify()` (linie 87-111) | Identyczna logika Mail + SMS — skopiowana dosłownie |
| `PortalController::dashboard()` + `Portal/DashboardController::index()` | Identyczna logika query — dwa kontrolery dla tej samej trasy? |
| `WelcomeController` + `KalkulatorController` | Oba mają kopię logiki wykrywania języka i locale session |

### 1.5 Logika w modelach i Filament Pages

**`Invoice::recalculate()`** — metoda obliczająca subtotal, VAT, total bezpośrednio w modelu + `update()`. Przy multi-tenancy trzeba uwważać na wywołanie bez scope tenanta. Można zostawić jako model accessor, ale `update()` wewnątrz modelu jest problematyczne przy testowaniu.

**`Client::booted()` — cascade delete:**
```php
static::forceDeleting(function (Client $client): void {
    $client->contacts()->withTrashed()->forceDelete();
    $client->leads()->withTrashed()->forceDelete();
    // ...
});
```
Logika poprawna, ale przy dodaniu `business_id` trzeba zadbać, żeby cascade był izolowany per tenant.

**`PipelinePage.php` (388 linii):**
- `getViewData()` — Eloquent queries, groupBy, totals query
- `moveStage()` — logika biznesowa + `LeadActivity::log()` + `Notification`
- `markWon()`, `markLost()` — logika statusów
- `sendEmail()` — `Mail::to()->send()` bezpośrednio
- `saveNote()`, `updateNote()`, `deleteNote()` — CRUD notatek
→ PipelinePage powinna tylko przekazywać do `LeadPipelineService` i `LeadNotesService`.

**`ConversionReportPage.php` — raw SQL w Filament Page:**
```php
DB::raw("COALESCE(NULLIF(source, ''), 'unknown') as source"),
DB::raw('COUNT(*) as total_leads'),
// ...
```
→ Należy do `ReportQueryService`.

### 1.6 Brak Policies

Aktualnie autoryzacja wyłącznie przez Filament permissions (`can('view_lead')` itp.). Brak `app/Policies/` oznacza, że:
- API i portal używają jawnych `abort(403)` w kontrolerach
- Multi-tenancy scope nie jest wymuszany przez Policy
- Nie ma standardowego miejsca na `Gate::define`

### 1.7 Problem z `Setting::get()` — global singleton

`Setting::get()` jest wywoływany z bazy danych w wielu miejscach (SmsService, StripeWebhookController, PayuWebhookController, Portal/PaymentController). Przy multi-tenancy każdy tenant ma własne ustawienia — globalne `Setting` przestaje działać.

**Ryzyko dla SaaS**: HIGH — wymaga `BusinessSetting` per-tenant zamiast globalnego `Setting`.

---

## 2. Audyt frontendu

### 2.1 Fat Components

| Komponent | Linie | Problem |
|---|---|---|
| `CostCalculatorV2.jsx` | 531 | God component — `calcEstimate()`, `ProgressBar`, `StepCard`, `SummaryCard`, `ContactForm`, wszystkie stany UI; brak ekstrakcji hooków |
| `CostCalculator.jsx` | 467 | **Stara wersja** — nadal w projekcie, brak usunięcia legacy kodu |
| `Portal/Contract.jsx` | 356 | Renderowanie HTML umowy inline + logika statusu + modalne okna |
| `Navbar.jsx` | 263 | Logika języka/locale, dropdown menu, mobile menu, sticky — wszystko w jednym |
| `CmsPage.jsx` | 251 | Renderuje wszystkie sekcje; logika warunkowa dla każdego type sekcji inline |
| `Portal/Invoice.jsx` | 246 | Tabela pozycji + status badge + download + pay button — brak subkomponentów |
| `Marketing/Contact.jsx` | 236 | Formularz kontaktowy z walidacją i `useForm` — OK ale duży |

### 2.2 Duplikacja V1 i V2 kalkulatora

```
resources/js/Components/Marketing/CostCalculator.jsx   (467 linii) — LEGACY
resources/js/Components/Marketing/CostCalculatorV2.jsx (531 linii) — AKTYWNY
```
Oba pliki istnieją w projekcie. `CostCalculator.jsx` (V1) ma hardcoded strings i stare podejście. `CostCalculatorV2.jsx` jest DB-driven i właściwy. **V1 powinien zostać usunięty.**

### 2.3 Brak TypeScript

Cały frontend w `.jsx` bez typów. Przy dodawaniu nowych modułów SaaS (landing pages, leads scoring, campaigns) brak typów zwiększy liczbę błędów prop-drilling i API mismatch.

**Priorytet migracji**: nie blokuje MVP, ale blokuje skalowalność po MVP.

### 2.4 Brak separacji logiki od widoku

W większości komponentów nie ma `useXxx` hooków — logika stanu i efekty inline. Jedynym wyjątkiem są `resources/js/Hooks/`:
- `useConsent.js`
- `useMetaPixel.js`
- `usePortalTrans.js`
- `useScrollReveal.js`

Brak: `useCalculator`, `usePortalDashboard`, `usePipelineStage`, `useCampaign`.

### 2.5 Dark mode skonfigurowany, ale nie zaimplementowany

`tailwind.config.js` ma `darkMode: 'class'`, ale żaden komponent nie używa `dark:` klas. Nie blokuje MVP, ale trzeba implementować raz dla wszystkich komponentów — nie etapami.

---

## 3. Proponowana struktura `app/Services/`

Pogrupowana według bounded contexts z `docs/architecture-plan.md`:

```
app/Services/
│
├── Payment/
│   ├── StripePaymentService.php        # logika webhook Stripe (handlePaymentIntent*, handleCheckout*)
│   ├── PayuPaymentService.php          # logika webhook PayU (processCompletedOrder)
│   └── PaymentNotificationService.php  # Mail + SMS po płatności (usunąć duplikat)
│
├── Report/
│   ├── ReportQueryService.php          # Eloquent queries dla leads/invoices/projects/conversion
│   └── ReportExportService.php         # PDF/XLSX/CSV generation (wyciągnąć z ReportController)
│
├── CRM/
│   ├── LeadPipelineService.php         # moveStage, markWon, markLost, konwersja na projekt
│   └── LeadNotesService.php            # CRUD notatek i historii leada
│
├── CMS/
│   ├── SiteSectionService.php          # pobieranie i transformacja sekcji per locale
│   └── CalculatorDataService.php       # pricing + strings + steps assembly per locale
│
├── Business/  [NOWE — SaaS]
│   ├── BusinessProfileService.php      # zarządzanie profilem firmy (brand, AI context)
│   └── BusinessSettingService.php      # per-tenant settings (zastępuje globalny Setting::get)
│
├── AI/  [NOWE — SaaS]
│   ├── OpenAIService.php               # wrapper klienta OpenAI z retry + error handling
│   ├── LandingPageGenerationService.php # orchestracja GenerateLandingPageJob
│   └── LeadScoringService.php          # orchestracja ScoreLeadJob
│
├── LandingPage/  [NOWE — SaaS]
│   └── LandingPagePublishService.php   # publikacja, custom domains, A/B testing
│
├── Campaign/  [NOWE — SaaS]
│   └── CampaignDispatchService.php     # wysyłka emaili/SMS per kampania
│
├── Subscription/  [NOWE — SaaS]
│   └── SubscriptionService.php         # Cashier — plany, trials, upgrade/downgrade
│
│   # Istniejące — ZACHOWAĆ BEZ ZMIAN:
├── SmsService.php                      ✅ dobrze napisany, tylko dodać DI 
├── PayuService.php                     ✅ dobra abstrakcja HTTP
├── ClientNotificationGate.php          ✅ util class — OK
└── ContractInterpolationService.php    ✅ dobry wzorzec — zachować
```

---

## 4. Ocena Repository Pattern

**Werdykt: NIE wprowadzać w MVP.**

Uzasadnienie:
- Projekt używa Eloquent + Query Builder bezpośrednio i jest to spójne
- Serwisy wystarczą jako warstwa abstrakcji nad modelem
- Repository pattern przy Eloquent dodaje boilerplate bez realnej korzyści dla tego rozmiaru projektu
- Można rozważyć TYLKO dla `LeadRepository` i `BusinessRepository` po dodaniu multi-tenancy scope, jeśli globalne scope okazałyby się niewystarczające

**Ewentualne wyjątki** (v2+):
- `LeadRepository` — gdy raportowanie i scoring będą wymagały skomplikowanych optimized queries
- `BusinessRepository` — gdy multi-tenancy scope będzie zbyt złożony

---

## 5. Docelowa struktura folderów

### Backend `app/`

```
app/
├── Actions/                    # ✅ już istnieje (CreateLeadAction itp.)
├── Automation/                 # ✅ zachować — Actions/, ConditionEvaluator
├── Console/                    # ✅ zachować
├── Events/                     # [NOWE] dla LeadCreated, PaymentReceived, PagePublished
├── Filament/
│   ├── Pages/                  # ✅ zachować — uprościć PipelinePage (deleguj do Service)
│   ├── Resources/              # ✅ zachować — 26 resources
│   └── Widgets/                # ✅ zachować — 13 widgets
├── Forms/                      # ✅ zachować
├── Http/
│   ├── Controllers/
│   │   ├── Auth/               # ✅ zachować
│   │   ├── Portal/             # ✅ zachować (dobra struktura)
│   │   ├── Api/                # [NOWE] dla SaaS API endpoints (BusinessController, LandingPageController, LeadController)
│   │   ├── Webhooks/           # [NOWE] przenieść StripeWebhookController + PayuWebhookController tutaj
│   │   ├── ReportController.php    # ✅ uprościć do delegation control
│   │   ├── WelcomeController.php   # ✅ uprościć przez SiteSectionService
│   │   ├── KalkulatorController.php # ✅ uprościć przez CalculatorDataService
│   │   └── PortalController.php    # ❌ USUNĄĆ — duplikat Portal/*
│   ├── Middleware/
│   │   └── IdentifyBusiness.php    # [NOWE] subdomain tenant detection
│   └── Requests/               # [NOWE] Form Requests dla API endpoints
├── Jobs/
│   ├── ProcessAutomationJob.php    # ✅ zachować
│   ├── GenerateLandingPageJob.php  # [NOWE]
│   └── ScoreLeadJob.php            # [NOWE]
├── Listeners/                  # ✅ zachować AutomationEventListener + ClientActivityListener
├── Mail/                       # ✅ zachować
├── Models/
│   ├── [istniejące 33]*        # ✅ zachować — dodać BelongsToTenant trait
│   ├── Business.php            # [NOWE] tenant root
│   ├── BusinessProfile.php     # [NOWE]
│   ├── LandingPage.php         # [NOWE]
│   ├── Lead.php               # ✅ zachować — dodać business_id + scoring fields
│   └── Plan.php                # [NOWE]
├── Policies/                   # [NOWE] LeadPolicy, InvoicePolicy, ProjectPolicy, LandingPagePolicy
├── Providers/                  # ✅ zachować + dodać AuthServiceProvider dla Policy rejestracji
├── Services/                   # patrz sekcja 3 powyżej
└── Traits/
    ├── BelongsToTenant.php     # [NOWE] + GlobalScope BusinessScope
    └── HasBusinessSettings.php # [NOWE]
```

### Frontend `resources/js/`

```
resources/js/
├── Components/
│   ├── Marketing/              # ✅ zachować — wszystkie 13 komponentów
│   ├── UI/                     # [NOWE] wspólne: Button, Badge, Modal, Table, Card
│   ├── Portal/                 # [NOWE] Portal-specific: InvoiceRow, ProjectCard, StatusBadge
│   └── LandingPage/            # [NOWE] builder, sekcje, preview
├── Contexts/                   # ✅ zachować + dodać ThemeContext (dark/light)
├── Hooks/
│   ├── useConsent.js           # ✅ zachować
│   ├── useMetaPixel.js         # ✅ zachować
│   ├── usePortalTrans.js       # ✅ zachować
│   ├── useScrollReveal.js      # ✅ zachować
│   ├── useCalculator.js        # [NOWE] logika kalkulatora z CostCalculatorV2
│   ├── usePortalDashboard.js   # [NOWE] logika dashboard portalu klienta
│   └── usePipelineStage.js     # [NOWE] dla Kanban (useForm + drag)
├── Layouts/                    # ✅ zachować
├── Pages/
│   ├── Auth/                   # ✅ zachować
│   ├── Portal/                 # ✅ zachować — refaktoryzacja subkomponentów
│   ├── Dashboard/              # [NOWE] SaaS dashboard: BusinessDashboard, Analytics
│   ├── LandingPages/           # [NOWE] AI generator, editor, publish
│   ├── Leads/                  # [NOWE] leads board, scoring, details
│   └── Campaigns/              # [NOWE]
├── types/                      # [NOWE po TypeScript]
│   ├── models.ts               # Lead, Client, Invoice, Business...
│   ├── api.ts                  # response shapes
│   └── props.ts                # component prop types
└── utils/
    ├── dataLayer.js            # ✅ zachować
    └── currency.js             # [NOWE] wyciągnąć fmt() z kalkulatora
```

---

## 6. Tabela priorytetów

| # | Problem | Plik / Obszar | Priorytet | Uzasadnienie | Estymata złożoności |
|---|---|---|---|---|---|
| 1 | **Duplikat `PortalController.php`** vs `Portal/*` | `app/Http/Controllers/PortalController.php` | **HIGH** | 447 linii martwego/duplikowanego kodu; trasy mogą być zduplikowane — ryzyko konserwacji | XS (30 min) |
| 2 | **Duplikat logiki płatności Stripe/PayU webhook** — Mail + SMS w obu | `StripeWebhookController` + `PayuWebhookController` | **HIGH** | Identyczna logika w dwóch miejscach; przy każdej zmianie trzeba pamiętać o obu plikach | S (2h) |
| 3 | **Brak PaymentNotificationService** | `StripeWebhookController::sendPaymentNotifications()` | **HIGH** | Logika biznesowa (mail + SMS) w kontrolerze; blokuje testy jednostkowe | S (2h) |
| 4 | **StripePaymentService** — wyciągnąć logikę z StripeWebhookController | `app/Http/Controllers/StripeWebhookController.php` | **HIGH** | Payment handling logic w kontrolerze; blokuje SaaS billing separation | M (4h) |
| 5 | **PayuPaymentService** — wyciągnąć logikę z PayuWebhookController | `app/Http/Controllers/PayuWebhookController.php` | **HIGH** | Jak wyżej | M (3h) |
| 6 | **PipelinePage — deleguj do LeadPipelineService** | `app/Filament/Pages/PipelinePage.php` | **HIGH** | 388 linii w Filament Page; Mail::send() w Page; blokuje testowanie CRM | L (6h) |
| 7 | **Brak multi-tenancy scope** — dodać `business_id` + `BelongsToTenant` | wszystkie modele biznesowe | **HIGH** | Krytyczne dla SaaS — bez tego business-isolation niemożliwa | XL (2 dni) |
| 8 | **GlobalSetting → per-tenant BusinessSettingService** | `Setting::get()` wywoływane w ~8 miejscach | **HIGH** | Globalny `Setting` przestaje działać przy multi-tenancy | L (6h) |
| 9 | **Usunąć CostCalculator.jsx (V1)** — legacy | `resources/js/Components/Marketing/CostCalculator.jsx` | **HIGH** | 467 linii martwego kodu; może powodować confusion; czyszczenie długu technicznego | XS (15 min) |
| 10 | **ReportQueryService** — wyciągnąć Eloquent z ReportController | `app/Http/Controllers/ReportController.php` | **MEDIUM** | Eloquent w kontrolerze; przy multi-tenancy musi być skoped do tenant | M (3h) |
| 11 | **CalculatorDataService** — wyciągnąć locale logic z KalkulatorController | `app/Http/Controllers/KalkulatorController.php` | **MEDIUM** | Locale detection kopiowana w WelcomeController i KalkulatorController | S (2h) |
| 12 | **SiteSectionService** — CMS data assembly per locale | `app/Http/Controllers/WelcomeController.php` | **MEDIUM** | 176-liniowy `__invoke()` z ręcznym mapowaniem sekcji; trudne do rozszerzenia | M (3h) |
| 13 | **LeadPipelineService** — moveStage, markWon, markLost | `app/Filament/Pages/PipelinePage.php` | **MEDIUM** | Zależy od #6; wymagany dla API Pipeline do frontend Kanban | M (3h) |
| 14 | **ConversionReportPage — raw SQL do ReportQueryService** | `app/Filament/Pages/ConversionReportPage.php` | **MEDIUM** | Raw `DB::raw()` w Filament Page; trudne w testowaniu | S (2h) |
| 15 | **Brak DI dla SmsService / PayuService** | `PayuWebhookController`, `StripeWebhookController`, `Portal/PaymentController` | **MEDIUM** | `new SmsService()`, `new PayuService()` bezpośrednio — utrudnia testowanie i DI container | S (1h) |
| 16 | **Brak `app/Policies/`** — Policy classes | brak pliku | **MEDIUM** | Autoryzacja tylko w Filament; portal używa `abort(403)` inline; bez Policies nie można użyć `Gate::authorize()` | L (5h) |
| 17 | **Portal/PaymentController — Stripe API key inline** | `app/Http/Controllers/Portal/PaymentController.php` | **MEDIUM** | `\Stripe\Stripe::setApiKey()` w akcji kontrolera; będzie musiał stać się per-tenant | S (1h) |
| 18 | **`useCalculator` hook** — wyciągnąć logikę z CostCalculatorV2 | `resources/js/Components/Marketing/CostCalculatorV2.jsx` | **MEDIUM** | 531-liniowy komponent; `calcEstimate()` i stany powinny być w custom hook | M (3h) |
| 19 | **Ekstrakcja subkomponentów Portal** — InvoiceRow, ProjectCard, StatusBadge | `resources/js/Pages/Portal/Invoice.jsx` (246 linii), `Contract.jsx` (356 linii) | **MEDIUM** | Duże pliki bez subkomponentów; trudne w utrzymaniu i edycji | M (4h) |
| 20 | **Locale detection duplication** w WelcomeController + KalkulatorController | oba pliki | **MEDIUM** | Ten sam 10-liniowy blok wykrywania locale kopiowany — wyciągnąć do trait/helper | XS (30 min) |
| 21 | **Invoice::recalculate()** — `update()` w metodzie modelu | `app/Models/Invoice.php` | **LOW** | Business logic w modelu; przy testowaniu wymaga pełnej persystencji; rozważyć przeniesienie do InvoiceService | S (2h) |
| 22 | **TypeScript migration** — pliki `.jsx` → `.tsx` + typy | `resources/js/` cały katalog | **LOW** | Brak typów utrudni skalowanie, ale nie blokuje MVP; migracja inkrementalna | XL (3 dni, etapami) |
| 23 | **Dark mode** — implementacja `dark:` klas | wszystkie komponenty | **LOW** | `darkMode: 'class'` skonfigurowany ale nieużywany; dopiero po ustaleniu design systemu | L (2 dni) |
| 24 | **Brak Unit testów** | `tests/Unit/` — brak | **LOW** | 15 Feature tests OK; Unit testy dla Services (gdy zostaną wyciągnięte) | M (per serwis ~2h) |
| 25 | **`Client::booted()` cascade delete** — wyizolować per tenant | `app/Models/Client.php` | **LOW** | Działający kod; przy multi-tenancy trzeba upewnić się że cascade działa tylko w obrębie tenanta | S (1h) |

---

## 7. Moduły: zachować vs przebudować

### ✅ Zachować bez zmian (lub minimalne zmiany)

| Moduł | Plik | Uwaga |
|---|---|---|
| Automation Engine | `app/Automation/`, `ProcessAutomationJob.php` | Dobra architektura; dodać tylko `business_id` scope |
| Event Listeners | `app/Listeners/` | Dobrze odizolowane; zachować |
| SmsService | `app/Services/SmsService.php` | Dobry wzorzec; dodać tylko DI |
| PayuService | `app/Services/PayuService.php` | Dobra abstrakcja |
| ClientNotificationGate | `app/Services/ClientNotificationGate.php` | Util class — OK |
| ContractInterpolationService | `app/Services/ContractInterpolationService.php` | Dobry wzorzec |
| Portal Controllers | `app/Http/Controllers/Portal/` | Dobrze wyizolowane; zachować strukturę |
| Filament Resources | `app/Filament/Resources/` 26 plików | Zachować; dodać tenant filtering |
| Filament Widgets | `app/Filament/Widgets/` 13 plików | Zachować; dodać tenant scope |
| Auth | `app/Http/Controllers/Auth/`, `routes/auth.php` | Standard Breeze — zachować |
| Marketing Components | `resources/js/Components/Marketing/` | Zachować (poza V1 kalkulatora) |
| Portal Pages (struktura) | `resources/js/Pages/Portal/` | Zachować strukturę; refaktor subkomponentów |
| Seeders | `database/seeders/` | Zachować; `AdminSeeder` poprawnie definiuje role/permissions |
| Feature Tests | `tests/Feature/` 15 testów | Zachować wszystkie; dostosować scope |

### 🔄 Wymaga przebudowy / wyciągnięcia do service

| Moduł | Akcja |
|---|---|
| `StripeWebhookController` | Wyciągnąć do `StripePaymentService` + `PaymentNotificationService` |
| `PayuWebhookController` | Wyciągnąć do `PayuPaymentService` + reużyć `PaymentNotificationService` |
| `ReportController` | Wyciągnąć queries do `ReportQueryService`, eksport do `ReportExportService` |
| `WelcomeController` | Wyciągnąć do `SiteSectionService` |
| `KalkulatorController` | Wyciągnąć do `CalculatorDataService` |
| `PipelinePage` | Wyciągnąć `moveStage/markWon/markLost/email` do `LeadPipelineService` |
| `ConversionReportPage` | Wyciągnąć queries do `ReportQueryService` |
| `CostCalculatorV2.jsx` | Wyciągnąć logikę do `useCalculator` hook |
| `PortalController.php` | Usunąć — duplikat `Portal/*` |
| `CostCalculator.jsx` (V1) | Usunąć — legacy |

### ❌ Usunąć

| Plik | Powód |
|---|---|
| `app/Http/Controllers/PortalController.php` | Duplikat `Portal/DashboardController` + `Portal/ProjectController` etc.; verify trasy przed usunięciem |
| `resources/js/Components/Marketing/CostCalculator.jsx` | V1 legacy; V2 jest aktywna |

---

## 8. Kolejność implementacji (priorytety)

### Faza 0 — Szybkie wygrane (przed SaaS) — 1 dzień

1. **Usuń `PortalController.php`** + weryfikacja tras (#1)
2. **Usuń `CostCalculator.jsx` V1** (#9)
3. **Wyciągnij locale detection** do helper/trait (#20)

### Faza 1 — Service Layer płatności — 2-3 dni

4. **`PaymentNotificationService`** (#3) — najpierw, bo jest prereq
5. **`StripePaymentService`** (#4) — wyciągnąć z StripeWebhookController
6. **`PayuPaymentService`** (#5) — wyciągnąć z PayuWebhookController; reużyć PaymentNotificationService
7. **DI dla SmsService + PayuService** (#15)

### Faza 2 — Service Layer CRM + Reports — 2-3 dni

8. **`LeadPipelineService`** (#6, #13) — prereq dla multi-tenancy
9. **`ReportQueryService`** + `ReportExportService` (#10)
10. **`SiteSectionService`** + `CalculatorDataService` (#11, #12)
11. **`ConversionReportPage`** → ReportQueryService (#14)

### Faza 3 — Multi-tenancy foundation — 3-4 dni

12. **`BelongsToTenant` trait** + `BusinessScope` GlobalScope (#7)
13. **`BusinessSettingService`** — zastąpić globalny `Setting::get()` (#8)
14. **Middleware `IdentifyBusiness`** (#7 prereq)
15. **`app/Policies/`** (#16) — LeadPolicy, InvoicePolicy, ProjectPolicy

### Faza 4 — Frontend (równolegle z Fazą 2-3)

16. **`useCalculator` hook** (#18)
17. **Subkomponenty Portal** (#19)
18. **TypeScript migration** — inkrementalnie nowe pliki w .tsx (#22)

### Faza 5+ — SaaS features (po Architecture Plan)

19. `BusinessProfileService`, `OpenAIService`, `LandingPageGenerationService`, `SubscriptionService`

---

## 9. Ryzyka

| Ryzyko | Poziom | Mitygacja |
|---|---|---|
| Usunięcie `PortalController.php` — mogą istnieć stare trasy wskazujące na niego | MEDIUM | Sprawdzić `routes/web.php` przed usunięciem; uruchomić testy Feature |
| `Setting::get()` w wielu miejscach — migracja do per-tenant settings może zepsuć istniejące integracje | HIGH | Wdrożyć `BusinessSettingService` z fallbackiem na globalne Setting w trybie single-tenant |
| `Invoice::recalculate()` wywołuje `update()` — może ominąć BusinessScope | MEDIUM | Po dodaniu GlobalScope, upewnić się że recalculate() wywołuje się po wykonaniu Auth check (scope już aktywny) |
| Testy Feature mogą nie pokrywać refaktoryzowanych serwisów | MEDIUM | Dodać testy Unit dla nowych serwisów zaraz po wyciągnięciu |

---

*Plan wymaga zatwierdzenia przed implementacją. Każda faza to osobny PR.*
