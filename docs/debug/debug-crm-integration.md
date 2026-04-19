# Debug Report — LP → CRM Lead Integration

**Data:** 2026-03-31  
**Zakres:** Pełna ścieżka: formularz landing page → rekord leada w CRM  
**Środowisko:** local (branch: main)  
**Skill:** laravel-react-debugger

---

## Podsumowanie wykonawcze

> Implementacja logiki biznesowej (`LeadService`, `AutomationEventListener`, powiadomienia, deduplication) jest **poprawna i kompletna**, ale **nigdy nie jest wywoływana** z produkcyjnego kontrolera HTTP.  
> `PublicLandingPageController` używa starego `LeadCaptureService`, który pomija cały nowy stos orkiestracji.

---

## Scenariusze weryfikacyjne

### ✅ SCENARIO-01 — Nowy lead tworzy poprawny rekord

**Wynik: CZĘŚCIOWY PASS**

`CreateLeadAction::execute()` jest poprawnie zaimplementowany — tworzy `Client`, `Contact`, `Lead` w jednym atomicznym bloku. Pola `form_data`, `assigned_to`, `business_id`, `utm_*` są w `$fillable`. Jednak:
- **Przez ścieżkę HTTP (formularz publiczny)** — `business_id` nie jest przekazywany do `CreateLeadAction` (stary `LeadCaptureService` nie ustawia tej wartości), więc `lead.business_id = NULL`.
- `lp_default_assignee_id` nie jest przekazywany → lead nie jest auto-przypisany.

---

### ❌ SCENARIO-02 — Istniejący email nie tworzy duplikatu

**Wynik: FAIL (KRYTYCZNY)**

Deduplikacja oparta na cache (fingerprint 24h) istnieje w `LeadService::createFromLandingPage()`, ale **ta metoda nie jest nigdy wywoływana** z kontrolera. `PublicLandingPageController` wywołuje `LeadCaptureService::capture()`, który nie ma żadnej ochrony przed duplikatami.

Przy 3 próbach z tego samego emaila na tę samą LP w ciągu 60 min — throttle (`throttle:3,60`) blokuje. Po upływie okna, ta sama osoba może stworzyć kolejny lead.

---

### ⚠️ SCENARIO-03 — Pipeline dostaje poprawny etap startowy

**Wynik: PASS z ryzykiem regresji**

`CreateLeadAction` poprawnie pobiera `PipelineStage::orderBy('order')->first()` i tworzy domyślny etap jeśli żaden nie istnieje. Jednak `PipelineStage` **nie ma kolumny `business_id`** — wszystkie tenanci dzielą te same etapy. We wdrożeniu multi-tenant:
- Zapytanie zwróci pierwszy etap globalny, niekoniecznie należący do właściwego biznesu.
- Auto-guard EC-07 tworzy globalny `New Lead`, nie per-tenant.

---

### ❌ SCENARIO-04 — Source jest poprawnie zapisany

**Wynik: FAIL**

`LeadSourceService::record()` jest wywoływany tylko przez `LeadService::createFromLandingPage()` — metody, która nie jest wołana z HTTP kontrolera. Tabela `lead_sources` pozostaje pusta dla wszystkich leadów z formularza LP.

Dodatkowo: schemat `lead_sources.business_id` jest `NOT NULL`, ale `LeadSourceService::record()` używa `$business?->id ?? $lead->business_id`. Przy `business_id = null` na obu — zapis zakończy się wyjątkiem DB constraint violation.

---

### ❌ SCENARIO-05 — Tenant isolation działa poprawnie

**Wynik: FAIL (WYSOKI PRIORYTET)**

W `CreateLeadAction::findOrCreateClient()`:

```php
$query = Client::where('primary_contact_email', $data['email']);
if ($businessId) {
    $query->where('business_id', $businessId);
}
```

Logika warunkowa jest poprawna — ale jest wywołana bez `business_id` (przez stary `LeadCaptureService`). Skutek: zapytanie nie scope'uje po tenant. Jeśli `test@example.com` istnieje w Business A, lead z Business B użyje **tego samego klienta z Business A** — cross-tenant data leak.

Dodatkowo: trait `BelongsToTenant` na `LandingPage` odpala `currentBusiness()` przy tworzeniu, ale **`Lead` modelu nie ma tego traita** — `business_id` nie jest auto-fill'owany przez Eloquent.

---

### ✅ SCENARIO-06 — Permissions nie blokują poprawnych akcji

**Wynik: PASS**

Formularz publiczny (`lp/{slug}/submit`) używa `LeadCaptureRequest` z `authorize(): true` — brak auth. Throttle `3,60` jest ustawiony na poziomie route. `LeadPolicy` nie jest stosowana do publicznego submitu. Poprawne.

`LandingPagePolicy` poprawnie chroni operacje CRUD (admin/manager tylko). Defensywny `perm()` helper łapie `PermissionDoesNotExist` zamiast wyrzucać 500.

---

### ⚠️ SCENARIO-07 — Eventy i queue jobs nie wykonują się podwójnie

**Wynik: CZĘŚCIOWY PASS / RYZYKO**

Mechanizm anti-double-dispatch jest poprawny:
- `AutomationEventListener::onLeadCreated()` returnuje early dla `source === 'landing_page'`
- `onLeadCaptured()` obsługuje LP leads z pełnym kontekstem
- `NotifyLeadOwnerListener::uniqueId()` chroni przed równoległymi retry

Jednak `CreateLeadAction::execute()` wewnętrznie kolejkuje `NewLeadMail` do `admin_address`. **Gdy następnie `LeadCaptured` event odpali `NotifyLeadOwnerListener`**, który wysyła `NewLeadAssignedMail` do assigned_to — jeśli `admin_address == assigned_to.email`, admin dostaje **2 emaile** dla jednego leada.

Nie ma mechanizmu blokującego to zduplikowanie.

---

### ✅ SCENARIO-08 — Formularz frontendowy poprawnie obsługuje błędy API

**Wynik: PASS z zastrzeżeniem**

`FormSection.jsx` poprawnie:
- Obsługuje 422 (mapuje `errors` z Laravel na pola)
- Obsługuje 5xx (pokazuje generyczny komunikat)
- Ma honeypot (`website` field)
- Wysyła CSRF przez axios cookie (`XSRF-TOKEN`)
- Stan `sending` blokuje double-submit

Zastrzeżenie: stan `error` (5xx) nie resetuje przycisku do retry bez przeładowania strony. UX issue, nie bloker danych.

---

## Zidentyfikowane bugi

### 🔴 BUG-01 — KRYTYCZNY: Kontroler używa starego LeadCaptureService

**Plik:** `app/Http/Controllers/LandingPage/PublicLandingPageController.php:17`  
**Metoda:** `__construct(private readonly LeadCaptureService $leadCaptureService)`

```php
// AKTUALNY KOD (błędny):
public function __construct(
    private readonly LeadCaptureService $leadCaptureService,
) {}
// submit() wywołuje $this->leadCaptureService->capture(...)
```

**Problem:**  
Nowy stack orkiestracji (`LeadService::createFromLandingPage()`) jest **dead code** z perspektywy HTTP requestu. Kontroler wstrzykuje i używa starego `LeadCaptureService`, który:
- Nie deduplikuje
- Nie ustawia `business_id` na leadzie
- Nie wywołuje `LeadSourceService` (brak attribution)
- Nie wywołuje `LeadConsentService` (brak GDPR consent)
- Nie przekazuje `lp_default_assignee_id`

**Skutek:** Każde zgłoszenie przez formularz LP omija wszystkie nowe zabezpieczenia.

**Zalecana poprawka:**

```php
// PLIK: app/Http/Controllers/LandingPage/PublicLandingPageController.php

// Zastąp LeadCaptureService → LeadService
public function __construct(
    private readonly \App\Services\Leads\LeadService $leadService,
) {}

public function submit(LeadCaptureRequest $request, string $slug): JsonResponse
{
    $page = LandingPage::published()->where('slug', $slug)->firstOrFail();

    $sourceData = [
        'ip_address'   => $request->ip(),
        'user_agent'   => $request->userAgent(),
        'page_url'     => $request->header('Referer'),
        'utm_source'   => $request->query('utm_source'),
        'utm_medium'   => $request->query('utm_medium'),
        'utm_campaign' => $request->query('utm_campaign'),
    ];

    $consentData = [
        'given'       => (bool) $request->input('consent', false),
        'consent_text'=> __('gdpr.consent_text'),
        'source_url'  => $request->url(),
        'ip_address'  => $request->ip(),
        'locale'      => app()->getLocale(),
    ];

    $result = $this->leadService->createFromLandingPage(
        $request->validated(),
        $sourceData,
        $consentData,
        $page,
    );

    if ($result['status'] === 'duplicate') {
        return response()->json([
            'success' => true,
            'message' => __('landing_pages.messages.lead_captured'),
        ]);
    }

    $page->increment('conversions_count');
    return response()->json(['success' => true, 'message' => __('landing_pages.messages.lead_captured')]);
}
```

---

### 🔴 BUG-02 — KRYTYCZNY: `Show.jsx` — prop `landingPage` undefined

**Plik:** `resources/js/Pages/LandingPage/Show.jsx:21`

```jsx
// AKTUALNY KOD (błędny):
export default function Show({ landingPage, sections = [] }) {
    const metaTitle = landingPage.meta_title || landingPage.title; // ← TypeError
```

```php
// KONTROLER — klucz to 'page', nie 'landingPage'
return Inertia::render('LandingPage/Show', [
    'page'     => $page->append(['conversion_rate']),
    'sections' => $page->sections,
]);
```

**Problem:** Inertia przesyła `page`, komponent oczekuje `landingPage` → `undefined` → biały ekran na każdej publicznej LP.  

**Zalecana poprawka:**  
Zmienić destrukturyzację w `Show.jsx` na `{ page, sections }`, lub zmienić klucz w kontrolerze na `landingPage`.

---

### 🔴 BUG-03 — WYSOKI: `resolveRecipients()` — TypeError przy assigned_to

**Plik:** `app/Listeners/NotifyLeadOwnerListener.php:89`

```php
// AKTUALNY KOD (błędny):
return collect([$assignee])->toBase()->mapInto(User::class);
```

**Problem:**  
`mapInto(User::class)` wywołuje `new User($assignee)` — przekazuje obiekt `User` do konstruktora Eloquent Model, który oczekuje `array $attributes`. PHP wyrzuci `TypeError: Argument 1 must be of type array, App\Models\User given`.

Listener trzykrotnie się wysypie (retries 30/60/120s), powiadomienia nigdy nie dotrą do assigned user.

**Zalecana poprawka:**

```php
// PRZED:
return collect([$assignee])->toBase()->mapInto(User::class);
// PO:
return User::whereKey($lead->assigned_to)->get();
```

---

### 🟡 BUG-04 — ŚREDNI: `lead_sources.business_id` NOT NULL bez gwarancji wartości

**Plik:** `app/Services/LandingPage/LeadCaptureService.php` i `database/migrations/2026_03_31_000008_create_lead_sources_table.php`

`lead_sources.business_id` jest `NOT NULL` w schemacie. `LeadSourceService::record()` używa `$business?->id ?? $lead->business_id`. Gdy lead ma `business_id = null` (stara ścieżka), wywołanie zakończy się `SQLSTATE[23000]: Integrity constraint violation`.

W aktualnym stanie nie wykonuje się (stara ścieżka nie woła `LeadSourceService`), ale po naprawie BUG-01 stanie się aktywny.

**Zalecana poprawka:** Zmienić `business_id` na `nullable()` w migracji lub zapewnić że lead zawsze ma `business_id` przed zapisem do `lead_sources`.

---

### 🟡 BUG-05 — ŚREDNI: Double email przy LP lead + auto-assigned

**Pliki:** `app/Actions/CreateLeadAction.php:128`, `app/Listeners/NotifyLeadOwnerListener.php:65`

`CreateLeadAction::execute()` zawsze wysyła `NewLeadMail` (do `mail.admin_address`).  
`NotifyLeadOwnerListener::handle()` wysyła `NewLeadAssignedMail` do `$lead->assigned_to`.

Jeśli `mail.admin_address == assignedTo.email` — ten sam odbiorca dostaje 2 różne emaile za jeden lead.

Brak flagi/sprawdzenia powodującego pominięcie `NewLeadMail` gdy lead ma przypisanego użytkownika.

---

### 🟢 BUG-06 — NISKI: Brak consent checkbox w formularzu

**Plik:** `resources/js/Components/LandingPage/PublicSection/FormSection.jsx`

Frontend nie renderuje pola zgody RODO. `LeadCaptureRequest` też nie waliduje pola `consent`. `LeadConsentService` może zapisać `given = false` (default) ale nie ma możliwości ustawienia `true` przez formularz.

Ryzyko compliance GDPR dla użytkowników z EU.

---

### 🟢 BUG-07 — NISKI: PipelineStage bez business_id

**Plik:** `app/Models/PipelineStage.php`, `app/Actions/CreateLeadAction.php:55`

```php
$stage = PipelineStage::orderBy('order')->first();
```

Zapytanie nie scope'uje po business — w środowisku multi-tenant zwróci pierwszy etap globalny. Auto-create guard tworzy jeden globalny `New Lead` stage.

Przy `markWon()` / `markLost()`:
```php
$wonStage = PipelineStage::where('is_won', true)->first();
```
Również bez scope — może wziąć etap z innego biznesu.

---

## Ryzyka regresji

| Ryzyko | Prawdopodobieństwo | Wpływ | Warunek wyzwalający |
|---|---|---|---|
| Duplikaty leadów po rate-limit window | WYSOKI | WYSOKI | Wiele subdomains / leadów z tego samego IP |
| Cross-tenant Client assignment | WYSOKI | KRYTYCZNY | Wiele firm z wspólnymi emailami |
| Broken public LP page (BUG-02) | PEWNY | KRYTYCZNY | Każde wejście na `/lp/{slug}` |
| Silent notification failure (BUG-03) | PEWNY | WYSOKI | Każdy LP lead po wdrożeniu |
| `lead_sources` brak danych | PEWNY | ŚREDNI | Każda submisja formularza |
| Double email dla admina | NISKI | NISKI | admin_email == assigned_to.email |

---

## Brakujące testy

```
tests/Feature/
├── LandingPage/
│   ├── PublicLeadCaptureTest.php        ← BRAK: submit form → lead created z business_id
│   ├── LeadDeduplicationTest.php        ← BRAK: same email + LP w 24h → status=duplicate
│   └── LeadCaptureThrottleTest.php      ← BRAK: 4 submity → 429
├── Leads/
│   ├── MultiTenantClientIsolationTest.php ← BRAK: 2 businessy, ten sam email → 2 klienci
│   ├── LeadServiceFromLpTest.php        ← BRAK: createFromLandingPage() end-to-end
│   ├── MarkWonPipelineMoveTest.php      ← BRAK: markWon() → stage zmienia się na is_won=true
│   └── MarkLostPipelineMoveTest.php     ← BRAK: markLost() → stage zmienia się na is_lost=true
└── Notifications/
    ├── NotifyLeadOwnerListenerTest.php  ← BRAK: listener wysyła notif do assigned_to
    └── LeadCapturedNotificationTest.php ← BRAK: database notification ma poprawne lead_id
```

---

## Plan naprawy (priorytety)

### Priorytety HIGH — blokerzy produkcji

| # | Plik | Zmiana |
|---|---|---|
| 1 | `PublicLandingPageController.php` | Zamień `LeadCaptureService` → `LeadService::createFromLandingPage()` |
| 2 | `LandingPage/Show.jsx` | Fix prop name: `landingPage` → `page` (lub zmień klucz w kontrolerze) |
| 3 | `NotifyLeadOwnerListener.php:89` | Fix `mapInto(User::class)` → `User::whereKey()->get()` |

### Priorytety MEDIUM — naprawić przed release

| # | Plik | Zmiana |
|---|---|---|
| 4 | `LeadCaptureRequest.php` | Dodaj pole `consent` (boolean, optional) |
| 5 | `FormSection.jsx` | Dodaj opcjonalny checkbox GDPR |
| 6 | `lead_sources` migration | `business_id` → `nullable()` lub enforce business na leadzie |

### Priorytety LOW — przed skalowaniem multi-tenant

| # | Plik | Zmiana |
|---|---|---|
| 7 | `PipelineStage` | Dodaj `business_id` + scope per tenant |
| 8 | `CreateLeadAction` | Scope `PipelineStage::orderBy('order')->where('business_id', ...)` |
| 9 | `LeadCaptureService.php` | Po wdrożeniu BUG-01 — usunąć lub zdeprecjonować starą metodę `capture()` |

---

## Wpływ istniejącego CRM

| Komponent CRM | Status | Uwagi |
|---|---|---|
| Lista leadów (Filament) | ✅ Działający | Wyświetla leady niezależnie od `business_id` |
| Pipeline Kanban | ✅ Działający | Nie zależy od nowych pól |
| Client record | ⚠️ Ryzyko | Cross-tenant po BUG-01 naprawa — wymaga migracji istniejących klientów |
| Automations (ProcessAutomationJob) | ✅ Działający | Anti-double-dispatch poprawny |
| Powiadomienia bell | ❌ Nie działa | BUG-03 blokuje dostarczenie |
| Email do admina | ✅ Działa | Via `NewLeadMail` w `CreateLeadAction` |
| Email do assigned_to | ❌ Nie działa | BUG-03 blokuje `NotifyLeadOwnerListener` |
| Activity log | ⚠️ Niekompletny | Brak `lp_captured` log — tylko `created` przez stary serwis |
| Source attribution | ❌ Nie działa | `lead_sources` pusty dla LP leadów |
| GDPR Consent | ❌ Nie działa | `lead_consents` pusty, brak pola UI |

---

*Raport wygenerowany przez laravel-react-debugger skill.*
