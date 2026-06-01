# Plan: Domain Registration Feature — WebsiteExperts

**Data planu:** 2026-05-29  
**Ostatnia aktualizacja statusu:** 2026-05-29  
**Etap realizacji:** Sprint 7 DONE ✅ — wszystkie sprinty zakończone  
**Stack:** Laravel 13 + Inertia.js/React + Filament 5 + Stripe

---

## Status ogólny

| Sprint | Zakres | Status |
|--------|--------|--------|
| Sprint 1 — Fundament | Migracje, Modele, DTOs, Serwisy, ServiceProvider | ✅ **DONE** |
| Sprint 2 — Panel admina | Filament Resources, Widgets, Sync Openprovider | ✅ **DONE** |
| Sprint 3 — Frontend publiczny | Strona /domains, wyszukiwarka | ✅ **DONE** |
| Sprint 4 — Portal klienta | Portal/Domains (5 stron) | ✅ **DONE** |
| Sprint 5 — Notyfikacje i cron | Notifications (5), Jobs (3) | ✅ **DONE** |
| Sprint 6 — Integracja API | Openprovider zintegrowany | ✅ **DONE** (ahead of plan) |
| Sprint 7 — Bundling | Powiązania z Invoice, Project, Quote | ✅ **DONE** |

---

## Kontekst i podejście

Sprzedaż domen jako **entry product** prowadzący do strony, hostingu, email, SSL i maintenance.  
Nie budujemy pełnego GoDaddy. Budujemy:
1. Najpierw ręczny MVP (formularz + zamówienie + Stripe + ręczna rejestracja u providera)
2. Potem automatyzację przez API (OpenSRS lub Openprovider)
3. Na końcu bundling z innymi produktami platformy

---

## ETAP 1 — MVP (ręczny / półautomatyczny)

### 1.1 Baza danych — nowe migracje ✅ DONE

**Kolejność tworzenia tabel:**

```
domain_orders         ✅ — zamówienia domen
domains               ✅ — zarejestrowane domeny
domain_contacts       ✅ — dane kontaktowe do rejestracji (WHOIS)
domain_price_list     ✅ — cennik (snapshot na potrzeby wyceny)
domain_renewals       ✅ — harmonogram odnowień
domain_events         ✅ — log zdarzeń domeny (rejestracja, transfer, odnowienie)
+ 2026_05_29_add_wholesale_margin ✅ — kolumny hurtowe + marża (dodane przy integracji OP)
```

**Schemat `domain_orders`:**
```
id, business_id, user_id, domain_name, tld, years,
action (register|transfer|renew),
status (pending_payment|paid|registering|completed|failed|cancelled),
provider, wholesale_price, retail_price, currency,
payment_id, stripe_payment_intent_id,
notes, admin_notes,
timestamps
```

**Schemat `domains`:**
```
id, business_id, user_id, domain_order_id,
provider, provider_domain_id,
name, tld, full_domain,
status (pending|active|expired|transferred|cancelled),
registered_at, expires_at, auto_renew (bool),
nameservers (json), dns_records (json),
whois_privacy (bool),
timestamps
```

**Schemat `domain_price_list`:**
```
id, tld, register_price, renew_price, transfer_price,
currency, is_active, notes,
+ wholesale_register, wholesale_renew, wholesale_transfer (decimal, nullable)
+ margin_percent (decimal, default 50.00)
timestamps
```

**Schemat `domain_renewals`:**
```
id, domain_id, due_date, years, status (pending|completed|failed),
notified_30d, notified_14d, notified_7d, notified_1d,
timestamps
```

---

### 1.2 Modele Laravel ✅ DONE

- ✅ `DomainOrder` — relacje: `belongsTo(Business)`, `belongsTo(User)`, `hasOne(Domain)`
- ✅ `Domain` — relacje: `belongsTo(Business)`, `belongsTo(User)`, `belongsTo(DomainOrder)`, `hasMany(DomainRenewal)`, `hasMany(DomainEvent)`
- ✅ `DomainPriceList` — z polami wholesale_* i margin_percent
- ✅ `DomainRenewal`
- ✅ `DomainEvent`
- ✅ `DomainContact`

---

### 1.3 Service Layer (kontrakt + implementacje) ✅ DONE

**Interfejs:**
```php
// app/Services/Domain/DomainRegistrarInterface.php ✅
interface DomainRegistrarInterface
{
    public function search(string $query): array;
    public function checkAvailability(string $domain): DomainAvailabilityResult;
    public function register(DomainRegistrationPayload $payload): DomainRegistrationResult;
    public function renew(string $domain, int $years): DomainRenewalResult;
    public function updateNameservers(string $domain, array $nameservers): bool;
    public function getDomainInfo(string $domain): DomainInfoResult;
    public function transfer(string $domain, string $authCode): DomainTransferResult;
}
```

**Data Transfer Objects (DTOs):** ✅ ALL DONE
```
app/Data/Domain/
  DomainAvailabilityResult.php    ✅
  DomainRegistrationPayload.php   ✅
  DomainRegistrationResult.php    ✅
  DomainRenewalResult.php         ✅
  DomainInfoResult.php            ✅
  DomainTransferResult.php        ✅
  DomainSearchResult.php          ✅
  DomainPriceSnapshot.php         ✅
```

**Implementacje serwisów:**
```
app/Services/Domain/
  ManualDomainRegistrarService.php     ✅ MVP: ręczna obsługa
  OpenSrsRegistrarService.php          ✅ Zaimplementowany
  OpenProviderRegistrarService.php     ✅ Zaimplementowany (aktywny)
  OpenProviderClient.php               ✅ Klient API (auth, token cache)
  DomainPricingService.php             ✅ zarządzanie cennikiem
  DomainOrderService.php               ✅
  DomainRenewalService.php             ✅ (sendReminders, markOverdue, createRenewal) logika zamówień
```

**Service Provider binding (DomainServiceProvider):** ✅ DONE
```php
// W zależności od config binduje odpowiednią implementację
// Aktualnie: OpenProviderRegistrarService (aktywne konto OP)
```

---

### 1.4 Actions ✅ DONE

```
app/Actions/Domain/
  CreateDomainOrderAction.php          ✅
  CheckDomainAvailabilityAction.php    ✅
  FetchOpenproviderPricesAction.php    ✅ (dodane przy integracji OP — poza planem)
  ProcessDomainPaymentAction.php       ✅
  GenerateDomainInvoiceAction.php      ✅ (Sprint 7 — generuje fakturę z pozycją domeny)
  GenerateDomainQuoteAction.php        ✅ (Sprint 7 — generuje wycenę z pozycją domeny)
  RegisterDomainAction.php             ✅ (dispatches RegisterDomainJob — validates 'paid' status)
  RenewDomainAction.php                ✅ (calls registrar->renew(), updates expiry, creates DomainRenewal)
  CancelDomainOrderAction.php          ✅ (wraps DomainOrderService::cancelOrder with status guard)
  UpdateNameserversAction.php          ✅ (calls registrar->updateNameservers(), persists to Domain)
  SendRenewalReminderAction.php        ✅ (dispatches SendDomainRenewalReminderJob for admin reminders)
```

---

### 1.5 Jobs ✅ DONE

```
app/Jobs/
  RegisterDomainJob.php               ✅ odpala się po udanej płatności, retry 3x
  SendDomainRenewalReminderJob.php     ✅
  CheckDomainExpiryJob.php            ✅ scheduled cron
```

---

### 1.6 Controllers ⚠️ PARTIAL

```
app/Http/Controllers/
  Domain/
    PublicDomainController.php         ✅ index() + check()
    DomainProviderWebhookController.php ✅
  Portal/
    DomainController.php               ✅
    DomainOrderController.php          ✅
    DomainCheckoutController.php       ✅
```

> **Uwaga:** W planie były `DomainOrderController` i `DomainCheckoutController` w folderze `Domain/`, ale zostały zaimplementowane w `Portal/` — to właściwa lokalizacja.

---

### 1.7 Routes ✅ DONE

Publiczne i portalowe route są skonfigurowane zgodnie z planem.

---

### 1.8 Powiadomienia (Notifications) ✅ DONE

```
app/Notifications/
  DomainOrderPlacedNotification.php        ✅
  DomainRegisteredNotification.php         ✅
  DomainExpiryReminderNotification.php     ✅
  DomainOrderAdminNotification.php         ✅
  DomainRegistrationFailedNotification.php ✅
```

---

## ETAP 2 — Panel administracyjny (Filament) ✅ DONE

### 2.1 Filament Resources ✅ DONE

```
app/Filament/Resources/
  DomainOrderResource.php          ✅
    Pages/ListDomainOrders.php     ✅
    Pages/ViewDomainOrder.php      ✅ akcje: zatwierdź, anuluj, oznacz jako zarejestrowaną
  DomainResource.php               ✅
    Pages/ListDomains.php          ✅
    Pages/ViewDomain.php           ✅
  DomainPriceListResource.php      ✅ + kolumny wholesale + margin_percent
    Pages/ListDomainPriceLists.php ✅ + przycisk "Sync from Openprovider"
    Pages/EditDomainPriceList.php  ✅
    Pages/CreateDomainPriceList.php ✅
```

### 2.2 Filament Pages / Widgets ✅ DONE

```
app/Filament/Widgets/
  DomainOrderStatsWidget.php       ✅
  DomainExpiryWidget.php           ✅
```

### 2.3 Kluczowe akcje w panelu admina ✅ DONE + EXTRA

- ✅ "Mark as Registered" — zmień status zamówienia i utwórz rekord `Domain`
- ✅ "Cancel Order" — anuluj + zwrot
- ✅ **"Generate Invoice"** — tworzy szkic faktury z domeną jako pozycją (Sprint 7)
- ✅ **"Generate Quote"** — tworzy szkic wyceny z domeną jako pozycją (Sprint 7)
- ✅ **"Sync from Openprovider"** — pobiera aktualne ceny hurtowe z OP API, modal diff z checkboxami per TLD, aktualizuje wholesale_* i przelicza ceny retail (poza pierwotnym planem)
- ✅ Ustawienie globalnej marży w IntegrationSettingsPage (`domain_default_margin`)
- ✅ Marża per-TLD w formularzu DomainPriceListResource

---

## ETAP 3 — Frontend publiczny (Inertia.js/React) ⚠️ PARTIAL

### 3.1 Strony React — publiczne ⚠️ PARTIAL

```
resources/js/Pages/Domains/
  Index.jsx    ✅ landing page z cennikiem TLD
  Check.jsx    ✅ wyniki sprawdzania dostępności domeny
  Order.jsx    ✅ formularz zamówienia (MarketingLayout)
  Checkout.jsx ✅ płatność Stripe (MarketingLayout)
  Result.jsx   ✅ wynik płatności (MarketingLayout)
```

### 3.2 Komponenty React ❌ TODO

> Komponenty mogą być aktualnie zaimplementowane inline w stronach. Wydzielenie do `Components/Domain/` do rozważenia przy refaktorze.

```
resources/js/Components/Domain/  ← katalog nie istnieje
  DomainSearchForm       ❌ (inline w Index.jsx)
  DomainAvailabilityResult ❌ (inline w Check.jsx)
  DomainPriceCard        ❌
  DomainOrderSummary     ❌
  DomainTldSelector      ❌
  DomainFeatureList      ❌
  DomainFaq              ❌
  DomainBundleCards      ❌
  DomainStatusBadge      ❌
  DomainExpiryAlert      ❌
```

### 3.3 Struktura strony `/domains` (Index.jsx) ✅ DONE

Strona zaimplementowana z cennikiem TLD. Sekcje bundles/FAQ do rozbudowy.

---

## ETAP 4 — Portal klienta ✅ DONE

```
resources/js/Pages/Portal/Domains/
  Index.jsx    ✅ lista domen klienta
  Show.jsx     ✅ szczegóły domeny
  Order.jsx    ✅ zamów nową domenę
  Checkout.jsx ✅ płatność za domenę
  Result.jsx   ✅ wynik płatności (bonus)
```

---

## ETAP 5 — Notyfikacje i cron ✅ DONE

Wszystkie notyfikacje i joby zaimplementowane (patrz 1.5 i 1.8).

---

## ETAP 6 — Integracja API ✅ DONE (zrealizowany wcześniej niż planowano)

### Zrealizowane

- ✅ **Openprovider** wybrany jako provider (konto aktywne)
- ✅ `OpenProviderClient.php` — auth, bearer token z cache 23h
- ✅ `OpenProviderRegistrarService.php` — search, check, register
- ✅ `FetchOpenproviderPricesAction.php` — parallel HTTP::pool, 48 requestów naraz
- ✅ Sync cen hurtowych z modal diff + checkboxy per TLD w panelu admina
- ✅ Kolumny `wholesale_register`, `wholesale_renew`, `wholesale_transfer`, `margin_percent` w `domain_price_list`
- ✅ Przeliczanie retail = wholesale × (1 + margin%)

### OpenSRS

- ✅ `OpenSrsRegistrarService.php` — zaimplementowany jako alternatywny adapter

---

## ETAP 7 — Bundling z innymi produktami ✅ DONE

### 7.1 Pakiety sprzedażowe ✅ DONE

| Pakiet | Zawartość | Cena |
|--------|-----------|------|
| Domain Only | Domena + DNS + WHOIS privacy | od £12/rok |
| Domain + Email | Domena + business email | od £25/rok |
| Domain + Website | Domena + konfiguracja DNS + podpięcie | od £X |
| Website Launch | Domena + strona + hosting + SSL + email + SEO | od £X |

> ✅ Sekcja Bundle Cards zaimplementowana w `resources/js/Pages/Domains/Index.jsx` (3 karty: Domain Only, Domain + Email, Website Launch).

### 7.2 Integracja z istniejącymi modułami ✅ DONE

- ✅ **Invoice** → `GenerateDomainInvoiceAction` + przycisk „Generate Invoice" w ViewDomainOrder; auto-faktura w `RegisterDomainJob`
- ✅ **Client** → domeny powiązane z klientem przez `client_id` w DomainOrder
- ✅ **Project** → `domain_order_id` FK na tabeli `projects`; `DomainOrder::projects()` HasMany
- ✅ **Sales Offer** → `domain_order_id` FK na tabeli `sales_offers`; pole w formularzu SalesOfferResource
- ✅ **Quote** → `GenerateDomainQuoteAction` + przycisk „Generate Quote" w ViewDomainOrder; QuoteItem z domeną pre-filled
- ✅ **Automation** → trigger `domain.expiry_reminder` dispatches `ProcessAutomationJob` from `SendDomainRenewalReminderJob`; trigger zarejestrowany w `AutomationTriggerSeeder`

### 7.3 Integracja z kalkulatorem cen ✅ DONE

- ✅ Krok „Domain" w `CostCalculatorV2` (krok 9, po wszystkich krokach DB-driven)
- ✅ Weryfikacja dostępności domeny: debounce 1.5 s → GET `/domains/availability?q=` → status (⏳/✅/❌/⚠️)
- ✅ Endpoint JSON `domains.availability` w `PublicDomainController`

---

## ETAP 8 — Aspekty prawne i formalne ✅ DONE

### 8.1 Wymagane dokumenty (przed sprzedażą)

- [x] Regulamin rejestracji domen
- [x] Polityka odnowień (terminy, automatyczne odnowienie)
- [x] Zasady transferu domeny
- [x] Procedura zgłaszania nadużyć DNS
- [x] Zasady anulowania i zwrotów — `/p/domain-cancellation-policy` ✅
- [x] Aktualizacja Polityki Prywatności (GDPR) — `/p/domain-privacy-gdpr` ✅
- [x] Informacja o rejestratorze nadrzędnym (Openprovider) — `/p/domain-registrar-info` ✅
- [x] Cennik z jasnym rozróżnieniem cena rejestracji vs. odnowienia — `/p/domain-pricing` ✅

### 8.2 Nowe strony CMS (`/p/slug`) ✅ DONE

- ✅ `/p/domain-registration-terms` — Regulamin rejestracji domen (EN/PL/PT)
- ✅ `/p/domain-renewal-policy` — Polityka odnowień (EN/PL/PT)
- ✅ `/p/domain-transfer-policy` — Zasady transferu (EN/PL/PT)
- ✅ `/p/dns-abuse-policy` — Polityka nadużyć DNS (EN/PL/PT)

> Zaimplementowane przez `DomainLegalPagesSeeder` (4 strony × 3 języki).

---

## Co pozostało do zrobienia (backlog)

### Pilne (blokują pełne uruchomienie sprzedaży)

1. ✅ ~~`Domains/Order.jsx`~~
2. ✅ ~~`Domains/Checkout.jsx`~~
3. ✅ ~~`ProcessDomainPaymentAction.php`~~
4. ✅ ~~**`DomainRenewalService.php`**~~ — `sendReminders()`, `markOverdue()`, `createRenewal()`; `CheckDomainExpiryJob` deleguje do serwisu

### Ważne (po uruchomieniu) — ZAKOŃCZONE ✅

5. ✅ ~~Pozostałe Actions~~: `RegisterDomainAction`, `RenewDomainAction`, `CancelDomainOrderAction`, `UpdateNameserversAction`, `SendRenewalReminderAction`
6. ✅ ~~Dokumenty prawne (CMS pages)~~ — `DomainLegalPagesSeeder` + `DomainLegalPagesExtraSeeder`
7. ✅ ~~Tłumaczenia~~: `lang/en/domain.php`, `lang/pl/domain.php`, `lang/pt/domain.php`
8. ✅ ~~Pozostałe dokumenty prawne z 8.1~~ — `domain-cancellation-policy`, `domain-privacy-gdpr`, `domain-registrar-info`, `domain-pricing`

### Sprint 7 — ZAKOŃCZONY ✅

- ✅ ~~Bundling z Invoice, Project, Quote, SalesOffer~~
- ✅ ~~Krok „Domain" w kalkulatorze z weryfikacją dostępności (debounce 1.5 s)~~
- ✅ ~~Pakiety sprzedażowe na stronie `/domains`~~
- Wydzielenie komponentów React (`Components/Domain/`) — opcjonalny refactor

---

## Kluczowe decyzje architektoniczne

| Decyzja | Wybór | Uzasadnienie |
|---------|-------|--------------|
| Płatność | Stripe (istniejący) | Już zintegrowany w platformie |
| Provider API | **Openprovider** ✅ aktywny | Szerokie TLD, REST API, white-label |
| Flow rejestracji | Pay → Job → Register | Unikamy utraty domeny między wyszukaniem a płatnością |
| Architektura serwisów | Interface + adaptery | Swap providera bez zmian w kontrolerach |
| MVP | ManualDomainRegistrarService | Zero integracji API na start |
| Ceny premium domen | Non-premium test label w FetchOpenproviderPricesAction | `zxq9k2w7m4ptest` jako label |
| Marża | Per-TLD + globalny default (50%) | Elastyczność cenowa |

---

## Notatki / Ustalenia

- ✅ Które TLD: .co.uk, .uk, .com, .net, .org, .io, .co, .me, .info, .biz, .dev, .app, .online, .store, .tech, .ai (16 TLD aktywnych)
- ✅ Ceny hurtowe — pobierane z Openprovider API, sync przez panel admina
- [x] Czy WHOIS privacy ma być w cenie, czy jako addon? — **W CENIE** — Openprovider daje WPP (WHOIS Privacy Protection) za darmo przy każdej domenie; kod domyślnie ustawia `whoisPrivacy: true`
- ✅ Domeny samodzielnie i z projektem (hybrydowo)
- ✅ Minimalny okres rejestracji: 1 rok
- ✅ Auto-renewal: domyślnie wyłączone
- ✅ Provider: Openprovider (konto aktywne, API działa)


Sprzedaż domen jako **entry product** prowadzący do strony, hostingu, email, SSL i maintenance.  
Nie budujemy pełnego GoDaddy. Budujemy:
1. Najpierw ręczny MVP (formularz + zamówienie + Stripe + ręczna rejestracja u providera)
2. Potem automatyzację przez API (OpenSRS lub Openprovider)
3. Na końcu bundling z innymi produktami platformy

---

## ETAP 1 — MVP (ręczny / półautomatyczny)

### 1.1 Baza danych — nowe migracje

**Kolejność tworzenia tabel:**

```
domain_orders         — zamówienia domen
domains               — zarejestrowane domeny
domain_contacts       — dane kontaktowe do rejestracji (WHOIS)
domain_price_list     — cennik (snapshot na potrzeby wyceny)
domain_renewals       — harmonogram odnowień
domain_events         — log zdarzeń domeny (rejestracja, transfer, odnowienie)
```

**Schemat `domain_orders`:**
```
id, business_id, user_id, domain_name, tld, years,
action (register|transfer|renew),
status (pending_payment|paid|registering|completed|failed|cancelled),
provider, wholesale_price, retail_price, currency,
payment_id, stripe_payment_intent_id,
notes, admin_notes,
timestamps
```

**Schemat `domains`:**
```
id, business_id, user_id, domain_order_id,
provider, provider_domain_id,
name, tld, full_domain,
status (pending|active|expired|transferred|cancelled),
registered_at, expires_at, auto_renew (bool),
nameservers (json), dns_records (json),
whois_privacy (bool),
timestamps
```

**Schemat `domain_price_list`:**
```
id, tld, register_price, renew_price, transfer_price,
currency, is_active, notes,
timestamps
```

**Schemat `domain_renewals`:**
```
id, domain_id, due_date, years, status (pending|completed|failed),
notified_30d, notified_14d, notified_7d, notified_1d,
timestamps
```

---

### 1.2 Modele Laravel

- `DomainOrder` — relacje: `belongsTo(Business)`, `belongsTo(User)`, `hasOne(Domain)`
- `Domain` — relacje: `belongsTo(Business)`, `belongsTo(User)`, `belongsTo(DomainOrder)`, `hasMany(DomainRenewal)`, `hasMany(DomainEvent)`
- `DomainPriceList`
- `DomainRenewal`
- `DomainEvent`
- `DomainContact`

---

### 1.3 Service Layer (kontrakt + implementacje)

**Interfejs:**
```php
// app/Services/Domain/DomainRegistrarInterface.php
interface DomainRegistrarInterface
{
    public function search(string $query): array;
    public function checkAvailability(string $domain): DomainAvailabilityResult;
    public function register(DomainRegistrationPayload $payload): DomainRegistrationResult;
    public function renew(string $domain, int $years): DomainRenewalResult;
    public function updateNameservers(string $domain, array $nameservers): bool;
    public function getDomainInfo(string $domain): DomainInfoResult;
    public function transfer(string $domain, string $authCode): DomainTransferResult;
}
```

**Data Transfer Objects (DTOs):**
```
app/Data/Domain/
  DomainAvailabilityResult.php
  DomainRegistrationPayload.php
  DomainRegistrationResult.php
  DomainRenewalResult.php
  DomainInfoResult.php
  DomainTransferResult.php
  DomainSearchResult.php
  DomainPriceSnapshot.php
```

**Implementacje serwisów:**
```
app/Services/Domain/
  ManualDomainRegistrarService.php     ← MVP: ręczna obsługa (stub bez API)
  OpenSrsRegistrarService.php          ← Etap 3
  OpenProviderRegistrarService.php     ← Etap 3
  DomainPricingService.php             ← zarządzanie cennikiem
  DomainRenewalService.php             ← wysyłanie powiadomień o odnowieniu
  DomainOrderService.php               ← logika zamówień
```

**Service Provider binding (DomainServiceProvider):**
```php
// W etapie 1 binduje ManualDomainRegistrarService
// W etapie 3 zmiana na OpenSrsRegistrarService
$this->app->bind(DomainRegistrarInterface::class, ManualDomainRegistrarService::class);
```

---

### 1.4 Actions

```
app/Actions/Domain/
  CreateDomainOrderAction.php
  ProcessDomainPaymentAction.php
  RegisterDomainAction.php
  RenewDomainAction.php
  CancelDomainOrderAction.php
  UpdateNameserversAction.php
  CheckDomainAvailabilityAction.php
  SendRenewalReminderAction.php
```

---

### 1.5 Jobs

```
app/Jobs/
  RegisterDomainJob.php          ← odpala się po udanej płatności
  SendDomainRenewalReminderJob.php
  CheckDomainExpiryJob.php       ← scheduled cron
```

---

### 1.6 Controllers

```
app/Http/Controllers/
  Domain/
    PublicDomainController.php         ← publiczna strona + wyszukiwarka
    DomainOrderController.php          ← flow zamówienia (checkout)
  Portal/
    DomainController.php               ← panel klienta: lista domen, odnowienie
    DomainCheckoutController.php       ← płatność Stripe za domenę
```

---

### 1.7 Routes

**Publiczne (routes/web.php):**
```php
// Public domain registration
Route::get('/domains', [PublicDomainController::class, 'index'])->name('domains.index');
Route::get('/domains/check', [PublicDomainController::class, 'check'])->name('domains.check');
Route::post('/domains/check', [PublicDomainController::class, 'checkPost'])->name('domains.check.post');
```

**Portal klienta (/portal/domains):**
```php
Route::prefix('domains')->name('portal.domains.')->group(function () {
    Route::get('/', [DomainController::class, 'index'])->name('index');
    Route::get('/order', [DomainController::class, 'order'])->name('order');
    Route::post('/order', [DomainOrderController::class, 'store'])->name('order.store');
    Route::get('/order/{order}/checkout', [DomainCheckoutController::class, 'show'])->name('checkout');
    Route::post('/order/{order}/pay', [DomainCheckoutController::class, 'pay'])->name('pay');
    Route::get('/{domain}', [DomainController::class, 'show'])->name('show');
    Route::post('/{domain}/renew', [DomainController::class, 'renew'])->name('renew');
    Route::patch('/{domain}/nameservers', [DomainController::class, 'updateNameservers'])->name('nameservers.update');
});
```

---

### 1.8 Powiadomienia (Notifications)

```
app/Notifications/
  DomainOrderPlacedNotification.php       ← do klienta po zamówieniu
  DomainRegisteredNotification.php        ← do klienta po rejestracji
  DomainExpiryReminderNotification.php    ← 30/14/7/1 dni przed wygaśnięciem
  DomainOrderAdminNotification.php        ← do admina o nowym zamówieniu
  DomainRegistrationFailedNotification.php
```

---

## ETAP 2 — Panel administracyjny (Filament)

### 2.1 Filament Resources

```
app/Filament/Resources/
  DomainOrderResource.php          ← zarządzanie zamówieniami
    Pages/
      ListDomainOrders.php
      ViewDomainOrder.php           ← szczegóły + akcje (zatwierdź, anuluj, oznacz jako zarejestrowaną)
  DomainResource.php               ← lista zarejestrowanych domen
    Pages/
      ListDomains.php
      ViewDomain.php
  DomainPriceListResource.php      ← edycja cennika TLD
    Pages/
      ListDomainPriceLists.php
      EditDomainPriceList.php
```

### 2.2 Filament Pages / Widgets

```
app/Filament/Widgets/
  DomainOrderStatsWidget.php       ← liczba zamówień, status, przychód z domen
  DomainExpiryWidget.php           ← domeny wygasające w ciągu 30 dni
```

### 2.3 Kluczowe akcje w panelu admina

W `DomainOrderResource` → `ViewDomainOrder`:
- **"Mark as Registered"** — zmień status zamówienia i utwórz rekord `Domain`
- **"Cancel Order"** — anuluj + zwrot (Stripe refund action)
- **"Add notes"** — notatki dla admina
- **Tabela powiązanych domen** — lista domen klienta

W `DomainResource` → `ViewDomain`:
- Edycja nameserverów
- Przycisk "Send renewal reminder"
- Historia zdarzeń (DomainEvent)
- Link do zamówienia, do klienta

---

## ETAP 3 — Frontend publiczny (Inertia.js/React)

### 3.1 Nowe strony React

```
resources/js/Pages/
  Domains/
    Index.tsx              ← strona landing "Domain Registration UK"
    Check.tsx              ← wyniki wyszukiwania domeny
    Order.tsx              ← podsumowanie zamówienia
    Checkout.tsx           ← płatność Stripe
```

### 3.2 Komponenty React

```
resources/js/Components/
  Domain/
    DomainSearchForm.tsx          ← input + przycisk szukaj
    DomainAvailabilityResult.tsx  ← wynik sprawdzenia dostępności
    DomainPriceCard.tsx           ← karta z ceną i TLD
    DomainOrderSummary.tsx        ← podsumowanie zamówienia
    DomainTldSelector.tsx         ← wybór TLD (.co.uk, .com, .uk, .net, .org)
    DomainFeatureList.tsx         ← "Co dostajesz z domeną"
    DomainFaq.tsx                 ← FAQ sekcja
    DomainBundleCards.tsx         ← pakiety bundlingowe
```

### 3.3 Struktura strony `/domains` (Index.tsx)

```
[Hero Section]
  "Register your UK business domain"
  Wyszukiwarka DomainSearchForm
  "from £X/year"

[Popular TLDs Section]
  Karty: .co.uk | .uk | .com | .net | .org
  Cena rejestracji + odnowienia

[Bundles Section]
  1. Domain Registration — od £X/rok
  2. Domain + Website Setup — od £X
  3. Domain + Business Email — od £X
  4. Website Launch Package — od £X (domena + hosting + SSL + email + SEO)

[Features Section]
  ✓ Free DNS management
  ✓ WHOIS privacy
  ✓ Auto-renewal reminders
  ✓ Managed for you

[FAQ Section]

[CTA Section]
  "Let us handle everything"
  → Formularz kontaktowy / zamówienie
```

### 3.4 Portal klienta — nowe strony

```
resources/js/Pages/Portal/
  Domains/
    Index.tsx         ← lista domen klienta (status, data wygaśnięcia, nameservery)
    Show.tsx          ← szczegóły domeny
    Order.tsx         ← zamów nową domenę
    Checkout.tsx      ← płatność za domenę
```

### 3.5 Portal — widgety / nawigacja ✅ DONE

- ✅ "Domains" dodane do nav portalu klienta (`PortalLayout.jsx`)
- ✅ Widget "Your domains" z alertem o wygasaniu na dashboardzie portalu (`Portal/Dashboard.jsx`)
- ✅ Badge z liczbą domen wygasających w ciągu 30 dni; `DashboardController` przekazuje `domains` + `domainsExpiringCount`

---

## ETAP 4 — Integracja API (Etap 3 wg roadmapy)

### 4.1 Wybór providera (rekomendacja: OpenSRS lub Openprovider)

**OpenSRS:**
- Sprawdzona platforma reseller
- API XML + REST
- White-label
- Moduły WHMCS (nieobowiązkowe)
- Pakiety: domeny + email + SSL

**Openprovider:**
- 1900+ TLD
- REST API
- Moduły WHMCS
- Dobry dla .uk + Nominet

### 4.2 Implementacja `OpenSrsRegistrarService`

```php
// app/Services/Domain/OpenSrsRegistrarService.php
class OpenSrsRegistrarService implements DomainRegistrarInterface
{
    public function __construct(
        private readonly OpenSrsClient $client,
        private readonly DomainEventRepository $events,
    ) {}

    public function checkAvailability(string $domain): DomainAvailabilityResult { ... }
    public function register(DomainRegistrationPayload $payload): DomainRegistrationResult { ... }
    public function renew(string $domain, int $years): DomainRenewalResult { ... }
    // itd.
}
```

### 4.3 Zmiana bindingu w DomainServiceProvider

```php
// config/services.php — dodać sekcję domain_registrar
// .env — DOMAIN_REGISTRAR_PROVIDER=opensrs

$this->app->bind(DomainRegistrarInterface::class, function ($app) {
    return match(config('services.domain_registrar.provider')) {
        'opensrs'       => $app->make(OpenSrsRegistrarService::class),
        'openprovider'  => $app->make(OpenProviderRegistrarService::class),
        default         => $app->make(ManualDomainRegistrarService::class),
    };
});
```

### 4.4 Obsługa webhooków od providera

```
app/Http/Controllers/Domain/
  DomainProviderWebhookController.php   ← eventy z OpenSRS/Openprovider
```

```php
// routes/web.php (CSRF exempt)
Route::post('/webhooks/domain/{provider}', DomainProviderWebhookController::class);
```

---

## ETAP 5 — Bundling z innymi produktami

### 5.1 Pakiety sprzedażowe

| Pakiet | Zawartość | Cena |
|--------|-----------|------|
| Domain Only | Domena + DNS + WHOIS privacy | od £12/rok |
| Domain + Email | Domena + business email | od £25/rok |
| Domain + Website | Domena + konfiguracja DNS + podpięcie | od £X |
| Website Launch | Domena + strona + hosting + SSL + email + SEO | od £X |

### 5.2 Integracja z istniejącymi modułami

- **Invoice** → po rejestracji domeny generuj fakturę automatycznie
- **Client** → domeny przypisane do klienta w CRM
- **Project** → możliwość powiązania domeny z projektem
- **Sales Offer / Quote** → dodaj domenę jako pozycję w ofercie/wycenie
- **Automation** → reguła: "30 dni przed wygaśnięciem → wyślij powiadomienie"

### 5.3 Integracja z kalkulatorem cen

- Dodać "Domain Registration" jako krok w `CalculatorStep`
- Możliwość dodania domeny do wyceny projektu

---

## ETAP 6 — Aspekty prawne i formalne

### 6.1 Wymagane dokumenty (przed sprzedażą)

- [ ] Regulamin rejestracji domen
- [ ] Polityka odnowień (terminy, automatyczne odnowienie)
- [ ] Zasady anulowania i zwrotów
- [ ] Zasady transferu domeny
- [ ] Aktualizacja Polityki Prywatności (GDPR)
- [ ] Informacja o rejestratorze nadrzędnym (OpenSRS/Openprovider)
- [ ] Procedura zgłaszania nadużyć DNS
- [ ] Cennik z jasnym rozróżnieniem cena rejestracji vs. odnowienia

### 6.2 Nowe strony CMS (`/p/slug`)

- `/p/domain-registration-terms` — Regulamin rejestracji domen
- `/p/domain-renewal-policy` — Polityka odnowień
- `/p/domain-transfer-policy` — Zasady transferu
- `/p/dns-abuse-policy` — Polityka nadużyć DNS

---

## Kolejność implementacji (sprint plan)

### Sprint 1 — Fundament
1. Migracje: `domain_price_list`, `domain_orders`, `domains`, `domain_renewals`, `domain_events`
2. Modele z relacjami
3. `DomainRegistrarInterface` + DTOs
4. `ManualDomainRegistrarService` (stub)
5. `DomainPricingService`

### Sprint 2 — Panel admina (Filament)
1. `DomainPriceListResource` + edycja cennika
2. `DomainOrderResource` z widokiem szczegółów + akcjami (zatwierdź, anuluj)
3. `DomainResource`
4. Widgety dashboardu: stats + expiry

### Sprint 3 — Frontend publiczny
1. Strona `/domains` (React + Inertia)
2. Wyszukiwarka domeny (formularz → check → wyniki)
3. Flow zamówienia: Order → Checkout → Stripe payment
4. Potwierdzenie zamówienia + email

### Sprint 4 — Portal klienta
1. Strona `/portal/domains` — lista domen klienta
2. Szczegóły domeny (`/portal/domains/{domain}`)
3. Odnowienie domeny + płatność
4. Widget na dashboardzie portalu
5. Nawigacja portalu — dodanie "Domains"

### Sprint 5 — Notyfikacje i cron
1. Notyfikacje email (zamówienie, rejestracja, wygaśnięcie)
2. `CheckDomainExpiryJob` (scheduled)
3. `SendDomainRenewalReminderJob`
4. `RegisterDomainJob`

### Sprint 6 — Integracja API (po pierwszych klientach)
1. Rejestracja konta reseller (OpenSRS lub Openprovider)
2. `OpenSrsRegistrarService` lub `OpenProviderRegistrarService`
3. Testy sandbox
4. Zmiana bindingu w DomainServiceProvider
5. Webhook handler od providera

### Sprint 7 — Bundling
1. Powiązanie domen z Invoice, Project, Quote, SalesOffer
2. Krok "Domain" w kalkulatorze (musi weryfikowac dostepnosc dynamicznie, opuznienie zapytania 1.5s )
3. Pakiety sprzedażowe na stronie `/domains`
4. Dokumenty prawne (CMS pages)

---

## Kluczowe decyzje architektoniczne

| Decyzja | Wybór | Uzasadnienie |
|---------|-------|--------------|
| Płatność | Stripe (istniejący) | Już zintegrowany w platformie |
| Provider API (Etap 3) | OpenSRS lub Openprovider | Szerokie TLD, REST API, white-label |
| Flow rejestracji | Pay → Job → Register | Unikamy utraty domeny między wyszukaniem a płatnością |
| Architektura serwisów | Interface + adaptery | Swap providera bez zmian w kontrolerach |
| MVP | ManualDomainRegistrarService | Zero integracji API na start |
| Ceny premium domen | Explicit acknowledgement | Zgodnie z ICANN i dobrymi praktykami |

---

## Pliki do utworzenia (pełna lista)

### Baza danych
```
database/migrations/
  xxxx_create_domain_price_list_table.php
  xxxx_create_domain_orders_table.php
  xxxx_create_domains_table.php
  xxxx_create_domain_contacts_table.php
  xxxx_create_domain_renewals_table.php
  xxxx_create_domain_events_table.php
```

### Backend (PHP)
```
app/
  Data/Domain/
    DomainAvailabilityResult.php
    DomainRegistrationPayload.php
    DomainRegistrationResult.php
    DomainRenewalResult.php
    DomainInfoResult.php
    DomainTransferResult.php
    DomainSearchResult.php
    DomainPriceSnapshot.php
  Models/
    Domain.php
    DomainOrder.php
    DomainContact.php
    DomainPriceList.php
    DomainRenewal.php
    DomainEvent.php
  Services/Domain/
    DomainRegistrarInterface.php
    ManualDomainRegistrarService.php
    OpenSrsRegistrarService.php        (Etap 3)
    OpenProviderRegistrarService.php   (Etap 3)
    DomainPricingService.php
    DomainRenewalService.php
    DomainOrderService.php
  Actions/Domain/
    CreateDomainOrderAction.php
    ProcessDomainPaymentAction.php
    RegisterDomainAction.php
    RenewDomainAction.php
    CancelDomainOrderAction.php
    UpdateNameserversAction.php
    CheckDomainAvailabilityAction.php
    SendRenewalReminderAction.php
  Http/Controllers/
    Domain/
      PublicDomainController.php
      DomainOrderController.php
      DomainProviderWebhookController.php
    Portal/
      DomainController.php
      DomainCheckoutController.php
  Jobs/
    RegisterDomainJob.php
    SendDomainRenewalReminderJob.php
    CheckDomainExpiryJob.php
  Notifications/
    DomainOrderPlacedNotification.php
    DomainRegisteredNotification.php
    DomainExpiryReminderNotification.php
    DomainOrderAdminNotification.php
    DomainRegistrationFailedNotification.php
  Filament/Resources/
    DomainOrderResource.php
    DomainResource.php
    DomainPriceListResource.php
  Filament/Widgets/
    DomainOrderStatsWidget.php
    DomainExpiryWidget.php
  Providers/
    DomainServiceProvider.php
```

### Frontend (React/TypeScript)
```
resources/js/
  Pages/
    Domains/
      Index.tsx
      Check.tsx
      Order.tsx
      Checkout.tsx
    Portal/Domains/
      Index.tsx
      Show.tsx
      Order.tsx
      Checkout.tsx
  Components/Domain/
    DomainSearchForm.tsx
    DomainAvailabilityResult.tsx
    DomainPriceCard.tsx
    DomainOrderSummary.tsx
    DomainTldSelector.tsx
    DomainFeatureList.tsx
    DomainFaq.tsx
    DomainBundleCards.tsx
    DomainStatusBadge.tsx
    DomainExpiryAlert.tsx
```

### Tłumaczenia
```
lang/
  en/domain.php
  pl/domain.php
  pt/domain.php
```

---

## Notatki / Do ustalenia

- [ ] Które TLD obsługujemy na start? Rekomendacja: .co.uk, .uk, .com, .net, .org // mamy wiecej
- [ ] Ceny startowe — do ustalenia po weryfikacji cen hurtowych u providera
- [x] Czy WHOIS privacy ma być w cenie, czy jako addon? — **W CENIE** — Openprovider daje WPP za darmo; `whoisPrivacy: true` domyślnie w kodzie
- [ ] Czy domeny mogą być zamawiane tylko w ramach projektu/strony, czy też samodzielnie?// hybrydowo czyli samodzielnie i z projektem
- [ ] Minimalny okres rejestracji: 1 rok
- [ ] Auto-renewal: domyślnie wyłączone (klient decyduje)
- [ ] Provider do integracji w Etapie 3: Openprovider
