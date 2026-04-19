# Feature Design: Stripe Billing + Plan Gates
> Data: 2026-04-03 | Moduł: `Billing`
> Bounded Context: Subscriptions
> Priorytet MVP: MUST HAVE (blokuje monetyzację SaaS)

---

## Definicja modułu: Billing

**Cel**: Umożliwić właścicielowi Business wybór planu SaaS (free/pro/agency), zarządzanie subskrypcją przez Stripe, egzekwowanie limitów funkcji per plan.

**Bounded Context**: Subscriptions  
**Priorytet MVP**: MUST HAVE  
**Zależności**: `Business` model (istnieje, ma `plan`, `stripe_customer_id`, `trial_ends_at`), Google OAuth, rejestracja  
**Użytkownik**: Klient SaaS (rola `client`), Admin agencji (wgląd)

---

## Plany SaaS

| Plan | Cena | LP limit | AI Generacji/mies | Storage |
|------|------|----------|-------------------|---------|
| `free` | £0 | 3 | 5 | 100 MB |
| `pro` | £29/mies | Unlimited | 50 | 5 GB |
| `agency` | £99/mies | Unlimited | Unlimited | 20 GB |

---

## Model danych

### Tabele (Laravel Cashier standardowe):

Cashier tworzy `subscriptions` i `subscription_items` tabele automatycznie.
Dodatkowa tabela do śledzenia AI rate-limit:

```
TABELA: lp_generation_rate_limits
- id                bigint unsigned, PK
- business_id       char(26) FK → businesses.id, NOT NULL
- year              smallint unsigned, NOT NULL
- month             tinyint unsigned, NOT NULL
- count             int unsigned, default 0
- created_at        timestamp
- updated_at        timestamp

Indeksy:
- UNIQUE(business_id, year, month)
```

### Istniejące kolumny w `businesses` (już są):
- `plan` ENUM('free', 'pro', 'agency') default 'free'
- `trial_ends_at` timestamp nullable
- `stripe_customer_id` varchar(255) nullable

---

## Backend — PlanService

```php
// app/Services/Billing/PlanService.php

class PlanService
{
    const LIMITS = [
        'free'   => ['landing_pages' => 3,         'ai_per_month' => 5       ],
        'pro'    => ['landing_pages' => PHP_INT_MAX, 'ai_per_month' => 50     ],
        'agency' => ['landing_pages' => PHP_INT_MAX, 'ai_per_month' => PHP_INT_MAX],
    ];

    public function getEffectivePlan(Business $business): string;
    public function isOnTrial(Business $business): bool;
    public function canCreateLandingPage(Business $business): bool;
    public function canUseAiGenerator(Business $business): bool;
    public function getRemainingAiGenerations(Business $business): int;
    public function getLandingPageLimit(Business $business): int;
    public function getCurrentAiCount(Business $business): int;
    public function incrementAiCount(Business $business): void;
    public function getUpgradeUrl(Business $business): string;
}
```

### Cashier na modelu Business

```php
// Business.php — dodać
use Laravel\Cashier\Billable;

class Business extends Model
{
    use Billable; // dodać ten trait
    // ...
}
```

### Migracja rate-limit

```php
// database/migrations/xxxx_create_lp_generation_rate_limits_table.php
Schema::create('lp_generation_rate_limits', function (Blueprint $table) {
    $table->id();
    $table->char('business_id', 26)->index();
    $table->foreign('business_id')->references('id')->on('businesses')->cascadeOnDelete();
    $table->smallInteger('year')->unsigned();
    $table->tinyInteger('month')->unsigned();
    $table->unsignedInteger('count')->default(0);
    $table->timestamps();
    $table->unique(['business_id', 'year', 'month']);
});
```

---

## Backend — Cashier Webhook

Nowy kontroler `SubscriptionWebhookController` rozszerzający Cashier WebhookController:

```php
// app/Http/Controllers/Billing/SubscriptionWebhookController.php
// Obsługuje: customer.subscription.updated, deleted, checkout.session.completed

// Po subscription update/create → Business::where('stripe_customer_id', ...)
//   → aktualizuj kolumnę plan (na podstawie price_id → plan mapping)
```

---

## Backend — Portal Billing Controller

```php
// app/Http/Controllers/Portal/BillingController.php

class BillingController extends BasePortalController
{
    // GET /portal/billing  → widok BillingPage z danymi subskrypcji
    public function index(): Response;
    
    // POST /portal/billing/checkout/{plan}  → Stripe Checkout redirect
    public function checkout(string $plan): RedirectResponse;
    
    // GET /portal/billing/success  → success po płatności
    public function success(): Response;
    
    // POST /portal/billing/portal  → Stripe Customer Portal redirect
    public function portal(): RedirectResponse;
}
```

---

## Frontend — Portal/Billing/Index.jsx

Strona `/portal/billing` w PortalLayout:

**Sekcje:**
1. Aktualny plan (badge: Free/Pro/Agency) + trial countdown
2. Karty planów z ceną, limitami i CTA
3. Invoke history / invoices (z Cashier)
4. Zarządzanie metodą płatności (Stripe Portal button)

**Props z kontrolera:**
```
{
  client,
  business: { plan, trial_ends_at, stripe_customer_id },
  subscription: { status, current_period_end } | null,
  plans: [{ key, name, price, limits }],
  ai_generations_used: int,
  ai_generations_limit: int,
  landing_pages_count: int,
  landing_pages_limit: int,
}
```

---

## Plan gates — integracja z LP i AI

### `canCreateLandingPage` — guard w LandingPageController::store()

```php
public function store(StoreLandingPageRequest $request): RedirectResponse
{
    $this->authorize('create', LandingPage::class);
    
    if (! $this->planService->canCreateLandingPage($business)) {
        return back()->withErrors(['plan' => __('landing_pages.plan_limit_reached')]);
    }
    // ...
}
```

### `canUseAiGenerator` — guard w AiLandingGeneratorController::generate()

```php
public function generate(GenerateLandingRequest $request): JsonResponse
{
    if (! $this->planService->canUseAiGenerator($business)) {
        return response()->json(['error' => __('landing_pages.ai.plan_limit_reached')], 429);
    }
    // ...do AI call...
    $this->planService->incrementAiCount($business);
}
```

### Frontend — banery limitów

W `LandingPages/Index.jsx` gdy `stats.published >= plan_limit`:
```jsx
<PlanLimitBanner 
  message="You've reached your 3 landing page limit on the Free plan"
  upgradeUrl="/portal/billing"
/>
```

---

## Środowisko — zmienne .env

```
STRIPE_KEY=pk_live_...
STRIPE_SECRET=sk_live_...
STRIPE_WEBHOOK_SECRET=whsec_...
CASHIER_CURRENCY=gbp
CASHIER_CURRENCY_LOCALE=en_GB

# Stripe Price IDs (z Stripe Dashboard)
STRIPE_PRICE_PRO_MONTHLY=price_...
STRIPE_PRICE_AGENCY_MONTHLY=price_...
```

---

## Kolejność wdrożenia

1. [x] `Business` posiada `plan`, `stripe_customer_id`, `trial_ends_at` (DONE)
2. [ ] `composer require laravel/cashier`
3. [ ] Migracja `lp_generation_rate_limits` 
4. [ ] `Business` model — dodać `use Billable`
5. [ ] `PlanService.php` + `AiGenerationRateLimiter.php`
6. [ ] `BillingController.php` + trasy `/portal/billing/*`
7. [ ] `SubscriptionWebhookController.php` + webhook route
8. [ ] Frontend `Portal/Billing/Index.jsx`
9. [ ] `PlanLimitBanner.jsx` komponent
10. [ ] Guards w `LandingPageController` + `AiLandingGeneratorController`
11. [ ] i18n — klucze billing w `lang/en/billing.php`, pl, pt
12. [ ] Testy Feature: `BillingControllerTest`, `PlanServiceTest`, `AiRateLimitTest`

---

*Feature design: docs/feature-billing.md*
