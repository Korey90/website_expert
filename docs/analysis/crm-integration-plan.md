# Plan Integracji — Landing Pages + Lead Capture ↔ CRM
> Data: 2026-03-31  
> Bazuje na: `docs/project-analysis.md`, `docs/architecture-plan.md`, `docs/mvp-plan.md`, `docs/crm-integration-analysis.md`  
> **Nie zawiera kodu — wyłącznie decyzje i plan działania**

---

## 0. Cel dokumentu

Zdefiniować precyzyjny plan integracji modułu **Landing Pages** i **Lead Capture** (sesje 2–4) z istniejącym **CRM i pipeline sprzedażowym** — bez łamania działających funkcji agencji.

Dokument odpowiada na pytania:
1. Jak mapować dane z formularza LP na rekordy CRM?
2. Czy lead z LP to nowy byt, czy trafia do istniejącej struktury?
3. Jak unikać duplikatów klientów i leadów?
4. Jak automatycznie przypisywać leady do właścicieli i etapów pipeline?
5. Jakie eventy, joby i notyfikacje uruchomić po przechwyceniu leada?
6. Jak to wszystko zszyć z Filament i Inertia/React?
7. Jak zachować zgodność z multi-tenancy i Spatie RBAC?

---

## 1. Kluczowe decyzje architektoniczne

### Decyzja 1 — Lead jako centralny byt, nie duplikat

> **Lead z Landing Page = rekord w istniejącej tabeli `leads`** — ZAWSZE.

Nie tworzymy osobnej tabeli `lp_leads` ani `capture_leads`. Istniejący model `Lead` jest wystarczający (ma `landing_page_id`, `utm_*`, `business_id`, `source`). Dodajemy tylko brakujące pola (patrz sekcja 3).

**Uzasadnienie:** Pipeline Kanban, LeadResource, automatyzacje, LeadActivity — wszystko już działa na tabeli `leads`. Duplikowanie tej struktury byłoby over-engineering.

### Decyzja 2 — Klient: firstOrCreate z scope per business

> **Klient CRM tworzony automatycznie z adresu email, ale TYLKO w obrębie tego samego `business_id`.**

Obecna logika `Client::firstOrCreate(['primary_contact_email' => $email])` jest globalna — błąd w środowisku multi-tenant. Nowa logika:

```
Client::where('business_id', $businessId)
      ->where('primary_contact_email', $email)
      ->firstOrCreate(...)
```

Jeśli klient z tym emailem istnieje w tym biznesie → użyj istniejącego.  
Jeśli klient istnieje w innym biznesie → stwórz nowego (osobny rekord per tenant).

### Decyzja 3 — Contact tworzony automatycznie przy LP lead

> **Lead z LP powinien tworzyć rekord `Contact` jako osobę** — oprócz (lub zamiast) tylko `Client` (firmy).

Obecny `CreateLeadAction` tworzy Client, ale zostawia `contact_id = null`. To powoduje, że checklist `has_contact` nigdy nie jest spełniony. Nowa logika: jeśli dane z formularza LP zawierają `first_name` + `last_name` → twórz `Contact` i przypisz do leada.

Jeśli dane zawierają tylko `name` (jedno pole) → próba split na first/last name, fallback do `name` jako `first_name`.

### Decyzja 4 — Domyślny etap pipeline dla LP leadów

> **Leady z LP zawsze trafiają do pierwszego etapu (order=1) — "New Lead"** — o ile nie ma konfiguracji per-business.

W MVP: brak per-LP konfiguracji etapu. Używamy `PipelineStage::where('business_id', $id)->orderBy('order')->first()`.

W v1.1 (NICE TO HAVE): pole `default_stage_id` na `landing_pages` tabeli — umożliwi przypisanie innego etapu dla różnych LP (np. webinar → "Warm Lead").

### Decyzja 5 — Przypisanie opiekuna (owner assignment)

> **Kolejność priorytetu owner assignment:**

```
1. Pole `assigned_to` z formularza (API token call z zewnętrznego systemu)
2. Pole `default_assignee_id` na landing_page (admin konfiguruje per LP)
3. Reguła automatyzacji: AutomationRule trigger=lead.created → akcja change_status/assign
4. NULL (lead nieprzypisany — widać w StaleLeadsWidget)
```

W MVP: priorytet (1) + (2) + (4). Priorytet (3) istnieje już — admin może skonfigurować.

### Decyzja 6 — Strategia deduplicaton (anty-spam)

> **Two-layer protection: rate limiting + fingerprint hash**

Warstwa 1 — Rate Limiting (istniejący `config/leads.php`):
- max 3 submissions per IP per 60 minut
- max 10 submissions per email per 24h
- realizowane przez `LeadRateLimitMiddleware` (już istnieje)

Warstwa 2 — Fingerprint deduplication (nowa):
- hash MD5 z `(email + landing_page_id + date)` 
- sprawdzenie czy lead z tym fingerprint już istnieje w ciągu ostatnich 24h
- jeśli tak → odpowiedź 200 (bez tworzenia duplikatu) + logowanie w `lead_sources`
- realizowane w `LeadService::checkDuplicate()` (do dodania)

### Decyzja 7 — `leads.source` enum — aktualizacja

> **Dodać wartość `landing_page` do enum `leads.source`.**

Obecny enum: `calculator|contact_form|referral|cold_outreach|social_media|other`.  
Nowy enum (addytywna migracja): dodać `landing_page`.

Leady z formularza LP ustawiają `source='landing_page'` — rozwiązuje dualność opisaną w `crm-integration-analysis.md`.

### Decyzja 8 — `LeadCaptured` event jako główny mechanizm propagacji

> **Cały downstream (notyfikacje, automatyzacje, scoring) wyzwalany przez `LeadCaptured` event.**

Flow:
```
LeadController::store() 
  → LeadService::createFromSource()
    → CreateLeadAction (Lead + Client + Contact)
    → LeadSourceService::record()
    → LeadConsentService::record()
    → event(new LeadCaptured($lead, $landingPage))

LeadCaptured listeners:
  → AutomationEventListener::onLeadCaptured() → ProcessAutomationJob('lead.created', ...)
  → NotifyLeadOwnerListener::handle()          → DB notification + email (nowy listener)
  → LeadScoringListener::handle()              → LeadScoringJob (v1.1 — AI scoring)
```

---

## 2. Mapowanie danych: formularz LP → CRM

### 2.1 Pola formularza landing page

```
Formularz LP może zawierać (JSON schema per LP):
┌──────────────────────────┬────────────────────────┬──────────────────────────────┐
│ Pole formularza LP       │ Cel CRM                │ Notatka                      │
├──────────────────────────┼────────────────────────┼──────────────────────────────┤
│ first_name               │ Contact.first_name     │ wymagane                     │
│ last_name                │ Contact.last_name      │ opcjonalne (split z name)    │
│ name (jedno pole)        │ Contact.first_name     │ fallback jeśli brak podziału │
│ email                    │ Client.primary_contact_email, Contact.email │ wymagane │
│ phone                    │ Client.primary_contact_phone, Contact.phone│ opcjonalne│
│ company                  │ Client.company_name    │ opcjonalne                   │
│ message / notes          │ Lead.notes             │ opcjonalne                   │
│ budget (slider)          │ Lead.budget_min + budget_max │ opcjonalne            │
│ project_type / subject   │ Lead.title (suffix)    │ opcjonalne                   │
│ consent (checkbox)       │ LeadConsent.given=true │ wymagane (GDPR)              │
│ [custom fields JSON]     │ Lead.form_data (nowe pole) │ opcjonalne              │
│ utm_source               │ Lead.utm_source + LeadSource.utm_source │ auto z URL  │
│ utm_medium               │ Lead.utm_medium        │ auto z URL                   │
│ utm_campaign             │ Lead.utm_campaign      │ auto z URL                   │
│ utm_content              │ Lead.utm_content       │ auto z URL                   │
│ utm_term                 │ Lead.utm_term          │ auto z URL                   │
│ referrer_url             │ LeadSource.referrer_url │ auto z header               │
│ page_url                 │ LeadSource.page_url    │ auto z request               │
└──────────────────────────┴────────────────────────┴──────────────────────────────┘
```

### 2.2 Auto-generowany tytuł leada

Format: `{company_or_name} — {LP.title} ({source})`

Przykłady:
- "Kowalski Design — Strona dla Fryzjera (landing_page)"
- "jan@example.com — Oferta SEO (landing_page)"

Jeśli `landing_page.conversion_goal` ustawione → dodane do tytułu:
- "Kowalski Design — Oferta SEO → book_call"

### 2.3 Pola brakujące — wymagana migracja addytywna

| Kolumna | Tabela | Typ | Domyślna | Priorytet |
|---|---|---|---|---|
| `form_data` | `leads` | json nullable | null | HIGH |
| `default_assignee_id` | `landing_pages` | FK → users nullable | null | HIGH |
| `landing_page` | `leads.source` (enum) | dodanie wartości | — | HIGH |
| `ai_score` | `leads` | smallint nullable | null | MEDIUM |
| `ai_score_reason` | `leads` | text nullable | null | MEDIUM |
| `score_calculated_at` | `leads` | timestamp nullable | null | MEDIUM |

### 2.4 Pełny schemat tworzenia rekordu CRM z LP

```
Dane wejściowe z formularza LP + request (UTM, IP, user agent)
        │
        ▼
LeadRateLimitMiddleware (sprawdź rate limit per IP + email)
        │
        ▼ (przeszło)
LeadService::checkDuplicate($email, $landingPageId)
        │
        ├─ DUPLIKAT (< 24h) → zwróć istniejący lead ID, odpowiedź 200
        │
        └─ OK → LeadService::createFromSource($leadData, $sourceData, $consentData, $business)
                    │
                    ├─ CreateLeadAction::execute()
                    │       ├─ Client::businessScoped()->firstOrCreate(email)
                    │       ├─ Contact::createForLead($data, $client)  [NOWE]
                    │       ├─ PipelineStage::firstForBusiness($business)
                    │       └─ Lead::create([
                    │               source = 'landing_page',
                    │               business_id, landing_page_id,
                    │               client_id, contact_id,  [NOWE]
                    │               pipeline_stage_id (first),
                    │               assigned_to (LP.default_assignee_id),  [NOWE]
                    │               form_data,  [NOWE]
                    │               utm_*, title, notes, budget_min/max
                    │           ])
                    ├─ LeadActivity::log('created', ...)
                    ├─ NewLeadMail (admin email — queued)
                    │
                    ├─ LeadSourceService::record($lead, $business, $sourceData)
                    │       → lead_sources: type='landing_page', landing_page_id, utm_*, ip_hash, ...
                    │
                    ├─ LeadConsentService::record($lead, $consentData) [jeśli consent=true]
                    │       → lead_consents: given, consent_text, locale, ...
                    │
                    └─ event(new LeadCaptured($lead, $landingPage))
```

---

## 3. Automatyczne tworzenie wpisu w pipeline

### 3.1 Flow przypisania do etapu pipeline

```
1. Lead::create() → pipeline_stage_id = PipelineStage::firstForBusiness()

2. Gdzie `firstForBusiness` = PipelineStage
     ::where('business_id', $businessId)
     ::orderBy('order')
     ::first()
   
   → Domyślnie: "New Lead" (order=1, seed AdminSeeder)

3. Jeśli LandingPage.default_stage_id ustawione (v1.1) → użyj tego stage_id
```

### 3.2 Checklist pipeline po LP lead

Automatyczne wypełnienie checklist items przy tworzeniu leada z LP:

| Warunek | Spełniony przy tworzeniu? | Wymaganie |
|---|---|---|
| `has_assignee` | Tak (jeśli LP.default_assignee_id) | LP z konfiguracją assignee |
| `has_client` | **Zawsze** — firstOrCreate | Zawsze |
| `has_contact` | **Tak** — Contact tworzony (Decyzja 3) | Po implementacji Contact creation |
| `has_phone` | Tak, jeśli przekazano w formularzu | — |
| `has_email` | **Zawsze** — wymagane pole | Zawsze |
| `has_value` | Nie (chyba że budget w formularzu) | Ręczne |
| `has_expected_close` | Nie | Ręczne |
| `email_sent` | Tak — NewLeadMail wysłany | Zawsze |
| `has_project` | Nie | Ręczne (konwersja) |
| `has_notes` | Tak, jeśli przekazano `message` | — |
| `has_calculator_data` | Nie (dla LP — nie kalkulator) | — |

### 3.3 LeadChecklistItem — automatyczne generowanie

Po Lead::create() → `LeadChecklistItem::generateForLead($lead)` (nowa metoda):
- sprawdź checklist aktualnego `pipeline_stage_id`
- dla każdego warunku który jest `true` → stwórz `LeadChecklistItem` z `completed_at = now()`
- dla warunków `false` → stwórz item bez `completed_at`

Skutek: stage checklist będzie aktualny od razu po przechwyceniu leada.

---

## 4. Eventy, Joby i Notyfikacje

### 4.1 Drzewo eventów po zatrzechwyceniu leada z LP

```
event(LeadCaptured)
      │
      ├──► AutomationEventListener::onLeadCaptured()
      │         └──► ProcessAutomationJob::dispatch('lead.created', context)
      │                   ├── AutomationRule.trigger_event = 'lead.created' → execute actions
      │                   │   ├── send_email (welcome email do lead'a)
      │                   │   ├── send_internal_email (alert do zespołu)
      │                   │   ├── send_sms (jeśli phone podany)
      │                   │   ├── notify_admin (DB notification)
      │                   │   └── change_status (np. client.status → 'active' po wygraniu)
      │
      ├──► NotifyLeadOwnerListener::handle() [NOWY LISTENER]
      │         ├── jeśli lead.assigned_to NOT NULL
      │         │       → DatabaseNotification do przypisanego usera
      │         │       → opcjonalnie email (jeśli user.notify_email=true)
      │         └── jeśli lead.assigned_to NULL
      │                 → DatabaseNotification do wszystkich z rolą admin/manager
      │
      └──► LeadScoringListener::handle() [NOWY — v1.1 only]
                └──► LeadScoringJob::dispatch($lead)
                          └── OpenAI → score 0–100 → Lead::update(['ai_score', 'ai_score_reason'])
```

### 4.2 Zmiany w `AutomationEventListener`

Do dodania w klasie `AutomationEventListener`:

```
subscribe() → dodać:
  $events->listen(LeadCaptured::class, [self::class, 'onLeadCaptured']);
  $events->listen(LeadAssigned::class, [self::class, 'onLeadAssigned']);

onLeadCaptured(LeadCaptured $event):
  dispatch('lead.created', [
    'lead_id'         → $event->lead->id,
    'client_id'       → $event->lead->client_id,
    'business_id'     → $event->lead->business_id,
    'source'          → 'landing_page',
    'landing_page_id' → $event->landingPage->id,
  ])

onLeadAssigned(LeadAssigned $event):
  dispatch('lead.assigned', [
    'lead_id'    → $event->lead->id,
    'assignee_id'→ $event->assignee->id,
  ])
```

### 4.3 Nowe triggery automatyzacji

| Trigger event string | Kiedy | Kontekst |
|---|---|---|
| `lead.created` | Każdy nowy lead (LP + inne) | lead_id, client_id, source, landing_page_id |
| `lead.stage_changed` | Zmiana etapu pipeline | lead_id, old_stage_id, stage_id |
| `lead.assigned` | Przypisanie do opiekuna | lead_id, assignee_id |
| `lead.won` | `markWon()` wywołane | lead_id, client_id |
| `lead.lost` | `markLost()` wywołane | lead_id, lost_reason |

Triggery `lead.won` i `lead.lost` należy dodać do `LeadService::markWon()` i `markLost()` — dispatchem do ProcessAutomationJob lub przez Eloquent event (aktualizacja `AutomationEventListener`).

### 4.4 Nowe typy notyfikacji Filament

| Notyfikacja | Typ | Do kogo | Kanał |
|---|---|---|---|
| `LeadCapturedNotification` | Nowy lead z LP | Przypisany user / wszyscy admin+manager | database |
| `LeadAssignedNotification` | Lead przypisany do mnie | Przypisany user | database + email |
| `LeadStaleNotification` | Lead > 7 dni bez aktywności | Przypisany user / manager | database |
| `LeadWonNotification` | Lead wygrany | Wszyscy w business | database |

> Klasy Notification oddzielne od automatyzacji — automatyzacje to konfiguracja użytkownika, notyfikacje systemowe to hardcoded business logic.

### 4.5 Harmonogram zadań (Scheduled Jobs)

```
app/Console/Kernel (lub schedule() w bootstrap/app.php):
  
→ StaleLeadsJob (daily)
    Lead::whereNull('won_at')
        ->whereNull('lost_at')
        ->where('updated_at', '<', now()->subDays(7))
        ->each(fn ($lead) => LeadStaleNotification::send($lead))

→ LeadScoringQueueJob (hourly — v1.1)
    Lead::whereNull('ai_score')
        ->where('created_at', '>', now()->subHours(24))
        ->each(fn ($lead) => LeadScoringJob::dispatch($lead))
```

---

## 5. Integracja z Filament Resources

### 5.1 `LeadResource` — wymagane poprawki

| Zmiana | Priorytet | Opis |
|---|---|---|
| `getEloquentQuery()` → dodać `business_id` scope | **HIGH** | `->where('leads.business_id', currentBusiness()->id)` |
| Kolumna `form_data` (KeyValue) | MEDIUM | Dodać do ViewLead infolist |
| Kolumna `ai_score` (badge) | LOW (v1.1) | Pokazać score z kolorem |
| Filtr `landing_page_id` | HIGH | Lista LP jako select filter |
| Sekcja "Attribution" w ViewLead | MEDIUM | LeadSource data (type, UTM, page_url) |
| Akcja "Assign to me" | MEDIUM | One-click przypisanie do zalogowanego usera |
| Naprawić `markWon()` auto-stage | HIGH | Po `markWon()` → przenieś lead na stage z `is_won=true` |
| Naprawić `markLost()` auto-stage | HIGH | J.w. dla `is_lost=true` |
| Dodać `budget_min`/`budget_max` do formularza | LOW | Pola zakres budżetu |

### 5.2 `ClientResource` — wymagane poprawki

| Zmiana | Priorytet | Opis |
|---|---|---|
| `getEloquentQuery()` → `business_id` scope | **HIGH** | Multi-tenant isolation |
| `ClientPolicy` — stworzyć brakującą | **HIGH** | Row-level security |
| Kolumna "Leads z LP" | LOW | Licznik leadów z LP dla klienta |

### 5.3 `LandingPageResource` (Filament) — nowy zasób

Nowy resource w Filament dla zarządzania LP:

| Element | Opis |
|---|---|
| Nawigacja | Group: "Marketing", sort=1, icon: heroicon-o-globe-alt |
| Lista | Kolumny: title, status (badge), leads_count, conversion_rate%, published_at |
| Formularz | title, slug, status, template_type, language, meta fields, **default_assignee_id** |
| Akcje | Publish/Unpublish, Preview, Copy URL |
| RelationManager | `LandingPageSectionsRelationManager`, LeadsRelationManager |
| Filtr | status, template_type, language |

### 5.4 Nowy widget: `LeadInboxWidget` w dashboardzie

Zastąpił/uzupełnia `RecentLeadsWidget` — pokazuje tylko leady z LP, z `business_id` scope, z możliwością szybkiego przypisania:

```
LeadInboxWidget:
  - query: Lead->where('source','landing_page')->where('assigned_to', null)->latest()->limit(10)
  - kolumny: contact (name+email), LP title, created_at, actions: Assign to me / View
  - sort: 3 (po StatsOverviewWidget i RecentLeadsWidget)
  - scope: tylko dla aktualnego business_id
```

### 5.5 `StaleLeadsWidget` — poprawka business scope

```
Zmiana: dodać ->where('business_id', currentBusiness()->id) do query
```

### 5.6 `LeadsBySourceWidget` — przełącz na `lead_sources.type`

```
Zmiana: 
  PRZED: Lead::selectRaw('source, COUNT(*) as total')
  PO:    LeadSource::selectRaw('type, COUNT(*) as total')
             ->where('business_id', currentBusiness()->id)
             ->groupBy('type')
```

---

## 6. Integracja z frontendem Inertia/React

### 6.1 Publiczny endpoint `/lp/{slug}` (już istnieje)

Trasa i kontroler publicznego widoku LP **istnieje** (`LandingPageController::show()`). Wymaga weryfikacji czy:
- [x] Nie wymaga auth
- [x] Przekazuje dane LP + sekcje do React komponentu
- [ ] Przekazuje `csrfToken`, `recaptcha_key` (dla formularza)
- [ ] Ustawia UTM cookies przy pierwszym wejściu

### 6.2 Endpoint API submit formularza LP

```
POST /api/lp/{landingPage:slug}/capture
```

Obsługiwany przez `LeadCaptureController` (istniejący z sesji 3/4).  
Oczekiwane dane request (JSON):

```json
{
  "first_name": "Jan",
  "last_name": "Kowalski",
  "email": "jan@example.com",
  "phone": "+48 123 456 789",
  "company": "Kowalski Design",
  "message": "Szukam strony...",
  "budget_min": 2000,
  "budget_max": 5000,
  "consent": true,
  "form_data": { "interest": "ecommerce", "timeline": "3months" },
  "utm_source": "google",
  "utm_medium": "cpc",
  "utm_campaign": "spring2026",
  "referrer_url": "https://google.com/...",
  "locale": "pl"
}
```

Odpowiedzi:
- `200 OK` — duplikat wykryty, nie stworzono
- `201 Created` — lead stworzony → `{ lead_id, message }`
- `422 Unprocessable` — błędy walidacji
- `429 Too Many Requests` — rate limit przekroczony

### 6.3 React hook `useLeadCapture`

Istniejący hook z sesji 4. Wymaga weryfikacji czy obsługuje:
- [ ] `budget_min` / `budget_max` (range slider)
- [x] UTM auto-fill z URL params
- [x] Consent checkbox
- [x] Obsługę 429 (rate limit UI feedback)
- [ ] Custom fields (`form_data` — dynamiczne z LP konfiguracji)

### 6.4 Strona sukcesu po zapisaniu leada

Po submit formularza → redirect lub in-page success state:

```
Options:
  A. In-page: ukryj formularz, pokaż "Dziękujemy! Odezwiemy się wkrótce."
  B. Redirect: GET /lp/{slug}/thanks
  C. Redirect na custom URL z landing_pages.thank_you_url (nowe pole — v1.1)
```

MVP: opcja A (in-page), konfiguracja przez sekcję `confirmation` w `landing_page_sections`.

### 6.5 React komponent `LeadInboxTable` (Filament override)

Nowa strona lub widget w Filament dla "Lead Inbox" — lista nieprzypisanych leadów z LP:

```
resources/js/Pages/Admin/LeadInbox.jsx (Inertia page)
  lub
Filament Widget z custom Livewire component (jeśli admin panel Filament)
```

Decyzja: **Filament widget** (konsistentny z resztą panelu admina). Widok listy w tabeli z quick-action "Assign to me".

---

## 7. Zgodność z Multi-Tenancy

### 7.1 Obowiązkowe zmiany dla MVP (przed wdrożeniem multi-tenant)

| Zmiana | Gdzie | Priorytet |
|---|---|---|
| `CreateLeadAction` → `Client::firstOrCreate` z business scope | `app/Actions/CreateLeadAction.php` | **KRYTYCZNE** |
| `LeadResource::getEloquentQuery()` → `business_id` filter | `LeadResource.php` | **KRYTYCZNE** |
| `ClientResource::getEloquentQuery()` → `business_id` filter | `ClientResource.php` | **KRYTYCZNE** |
| `RecentLeadsWidget` → `business_id` filter | widget | **KRYTYCZNE** |
| `StaleLeadsWidget` → `business_id` filter | widget | **KRYTYCZNE** |
| `LeadsBySourceWidget` → `business_id` filter + przełącz na `lead_sources` | widget | **KRYTYCZNE** |
| `StatsOverviewWidget` → `business_id` filter dla Lead/Invoice stats | widget | HIGH |
| `ProcessAutomationJob` → filtr reguł per `business_id` | `AutomationRule::where('business_id', ...)` | HIGH |

### 7.2 Plan wdrożenia trait `BelongsToTenant` (Sprint 2)

W Sprint 2 (po MVP) dodać `GlobalScope` na modelach:

Kolejność dodawania (aby nie zepsuć istniejących danych):
1. `Lead` — HIGH (core dla LP integracji)
2. `LandingPage`, `LeadSource`, `LeadConsent` — HIGH (nowe modele, łatwe)
3. `Client`, `Contact` — HIGH (CRM)
4. `AutomationRule` — MEDIUM (po business_id migracji)
5. `PipelineStage` — MEDIUM (domyślne etapy per business)
6. `Project`, `Invoice`, `Quote`, `Contract` — MEDIUM
7. `EmailTemplate`, `SmsTemplate`, `ContractTemplate` — LOW
8. `Setting`, `Page`, `SiteSection` — LOW

### 7.3 Helper `currentBusiness()` — spójność

Helper `currentBusiness()` (w `app/Helpers/BusinessHelper.php`) musi być wywoływany spójnie:
- W `CreateLeadAction` — pobierany z `$data['business_id']` (przekazany jawnie, nie z session)
- W Filament Resources — przez `auth()->user()->currentBusiness()` lub helper
- W middleware `IdentifyBusiness` (v1.1) — z subdomeny

---

## 8. Zgodność z rolami i uprawnieniami Spatie

### 8.1 Istniejące uprawnienia CRM (gotowe)

| Uprawnienie | Kto ma | Wystarczy dla LP integration? |
|---|---|---|
| `view_leads` | admin, manager, developer | ✅ TAK — Lead Inbox |
| `manage_leads` | admin, manager | ✅ TAK — przypisywanie, edycja |
| `delete_leads` | admin, manager | ✅ TAK |
| `export_leads` | admin only | ✅ TAK |
| `view_landing_pages` | admin, manager, developer | ✅ TAK |
| `manage_landing_pages` | admin, manager | ✅ TAK — edycja LP |
| `publish_landing_pages` | admin, manager | ✅ TAK |
| `view_lead_sources` | admin, manager, developer | ✅ TAK |
| `manage_api_tokens` | admin, manager | ✅ TAK |

### 8.2 Nowe uprawnienia do dodania w AdminSeeder

| Uprawnienie | Kto otrzymuje | Cel |
|---|---|---|
| `assign_leads` | admin, manager | Przypisywanie leadów do użytkowników |
| `view_lead_inbox` | admin, manager, developer | Dostęp do Lead Inbox widget |
| `configure_lp_capture` | admin only | Konfiguracja formularzy LP (pola, walidacja) |

> Istniejące `manage_leads` obejmuje assign w sensie logicznym, ale wydzielenie `assign_leads` umożliwi np. sprzedawcy (nowa rola v1.1) przypisywanie bez pełnego zarządzania.

### 8.3 Policy dla `LandingPage` model (brak — do dodania)

```php
// Wymagana klasa: app/Policies/LandingPagePolicy.php
viewAny: can('view_landing_pages') || hasAnyRole(['admin','manager','developer'])
view:    = viewAny
create:  can('manage_landing_pages') || hasAnyRole(['admin','manager'])
update:  can('manage_landing_pages') || hasAnyRole(['admin','manager'])
delete:  can('manage_landing_pages') && hasRole('admin')
publish: can('publish_landing_pages') || hasRole('admin')
```

### 8.4 Publiczny formularz LP — bez auth

Endpoint `POST /api/lp/{slug}/capture` musi działać **bez autentykacji** (anonimowy lead). Chroniony przez:
- Rate limiting (LeadRateLimitMiddleware)
- CSRF token
- reCAPTCHA (opcjonalne, v1.1)
- Walidacja `ApiToken` dla programmatic submissions

---

## 9. Sekwencja implementacji

### Sprint 1 — Krytyczne poprawki (przed oddaniem funkcji)

> Cel: leady z LP nie przeciekają między tenantami i trafiają w dobre miejsce pipeline.

| # | Zadanie | Zależności | Estymata |
|---|---|---|---|
| S1-01 | Migracja addytywna: dodaj `landing_page` do enum `leads.source` | brak | 30 min |
| S1-02 | Migracja addytywna: `leads.form_data` json nullable | brak | 30 min |
| S1-03 | Migracja addytywna: `landing_pages.default_assignee_id` FK nullable | brak | 30 min |
| S1-04 | `CreateLeadAction` — dodać `business_id` scope do `Client::firstOrCreate` | S1-01 | 1h |
| S1-05 | `CreateLeadAction` — dodać tworzenie `Contact` (Decyzja 3) | S1-04 | 1h |
| S1-06 | `CreateLeadAction` — wypełnić `assigned_to` z LP `default_assignee_id` | S1-03, S1-04 | 30 min |
| S1-07 | `LeadService::checkDuplicate()` — fingerprint hash deduplication | S1-04 | 1h |
| S1-08 | `LeadResource` + `ClientResource` — dodać `business_id` scope do query | brak | 1h |
| S1-09 | Widgety: `RecentLeadsWidget`, `StaleLeadsWidget`, `StatsOverviewWidget` — business scope | S1-08 | 1h |
| S1-10 | `LeadsBySourceWidget` — przełącz na `lead_sources.type` + business scope | S1-09 | 30 min |
| S1-11 | `AutomationEventListener` — dodać handler dla `LeadCaptured`, `LeadAssigned` eventów | brak | 1h |
| S1-12 | Nowy `NotifyLeadOwnerListener` — DatabaseNotification przy przechwyceniu leada | S1-11 | 1.5h |
| S1-13 | `LeadService::markWon()` / `markLost()` — auto-move stage + dispatch triggera | brak | 1h |
| S1-14 | Usuń `ip_address` raw z `lead_sources` lub zamień na masked/hashed | brak | 30 min |
| S1-15 | `LandingPagePolicy` — stworzyć brakującą Policy | brak | 30 min |
| S1-16 | `ClientPolicy` — stworzyć brakującą Policy | brak | 30 min |

**Łączna estymata Sprint 1:** ~12h (1.5 dnia pracy)

---

### Sprint 2 — Filament + Frontend (Lead Inbox UX)

> Cel: admin widzi i zarządza leadami z LP w Filament w intuicyjny sposób.

| # | Zadanie | Zależności | Estymata |
|---|---|---|---|
| S2-01 | `LeadResource` — filtr po `landing_page_id`, kolumna `form_data`, sekcja Attribution | S1-08 | 2h |
| S2-02 | `LeadResource` — akcja "Assign to me" (inline QuickAction) | S1-12 | 1h |
| S2-03 | Nowy Filament widget `LeadInboxWidget` — nieprzypisane leady z LP | S1-08, S1-12 | 2h |
| S2-04 | `LandingPageResource` — nowy Filament Resource dla LP | S1-15 | 3h |
| S2-05 | `LandingPageResource` — `LeadRelationManager` (leady per LP) | S2-04 | 1.5h |
| S2-06 | `AdminSeeder` — dodać nowe uprawnienia `assign_leads`, `view_lead_inbox` | brak | 30 min |
| S2-07 | React: `useLeadCapture` hook — dodać `budget_min/max`, custom `form_data` | S1-02 | 2h |
| S2-08 | React: in-page success state po submit (Decyzja opcja A) | S2-07 | 1h |
| S2-09 | `LeadChecklistItem::generateForLead()` static method | S1-05 | 1h |

**Łączna estymata Sprint 2:** ~14h (1.5–2 dni pracy)

---

### Sprint 3 — Scoring, Notyfikacje zaawansowane (v1.1)

> Cel: inteligentne priorytety leadów i notyfikacje kontekstualne.

| # | Zadanie | Zależności | Estymata |
|---|---|---|---|
| S3-01 | `LeadScoringJob` — OpenAI scoring 0-100 | klucz OpenAI | 3h |
| S3-02 | `LeadScoringListener` — dispatch po `LeadCaptured` | S3-01 | 30 min |
| S3-03 | `ai_score` badge w `LeadResource` tabeli | S3-01 | 30 min |
| S3-04 | `StaleLeadsJob` — scheduled job codziennie | brak | 1h |
| S3-05 | `LeadStaleNotification` klasa | S3-04 | 1h |
| S3-06 | LP `default_stage_id` pole + UI konfiguracji | S1-03 | 2h |
| S3-07 | GlobalScope `BelongsToTenant` trait — Lead, LandingPage, LeadSource | Sprint 1+2 done | 3h |
| S3-08 | GlobalScope `BelongsToTenant` — Client, Contact, PipelineStage | S3-07 | 2h |
| S3-09 | `LeadWonNotification`, `LeadAssignedNotification` klasy | S1-12 | 1h |
| S3-10 | `thank_you_url` na `landing_pages` + redirect po submit | brak | 1h |

**Łączna estymata Sprint 3:** ~16h (2 dni pracy)

---

## 10. Diagram przepływu (konsolidowany)

```
[Użytkownik odwiedza LP]
         │
         ▼
    GET /lp/{slug}
    LandingPageController::show()
         │
         ├── Landing page: status=published? ────► NIE → 404
         │
         ▼  TAK
    React: PublicLandingPage.jsx
         ├── Renderuje sekcje (hero, features, cta, form)
         ├── Czyta UTM z URL → useLeadCapture hook
         └── Formularz: "Skontaktuj się"
                    │
                    ▼ (submit)
    POST /api/lp/{slug}/capture
         │
         ├── LeadRateLimitMiddleware (429 jeśli limit)
         ├── Walidacja FormRequest
         ├── LeadService::checkDuplicate() ──► duplikat → 200
         │
         ▼  OK
    LeadService::createFromSource()
         ├── CreateLeadAction::execute()
         │       ├── Client (business-scoped firstOrCreate)
         │       ├── Contact (nowy z first/last name + email)
         │       ├── Lead (source=landing_page, stage=first, assigned_to=LP.default_assignee)
         │       └── NewLeadMail → admin (queued)
         ├── LeadSourceService::record() → lead_sources (type=landing_page, utm_*)
         ├── LeadConsentService::record() → lead_consents (jeśli consent=true)
         └── event(LeadCaptured)
                    │
        ┌───────────┼───────────────────────────────┐
        ▼           ▼                               ▼
AutomationListener  NotifyLeadOwnerListener        LeadScoringListener (v1.1)
        │           │                               │
        ▼           ▼                               ▼
ProcessAutomation  DatabaseNotification            LeadScoringJob → OpenAI
Job('lead.created') (+ email jeśli assigned_to)         │
        │                                          └── Lead.ai_score = 72
        ▼
AutomationRules
(per tenant business_id)
        ├── send_email (welcome do lead'a)
        ├── send_internal_email (alerty)
        └── ... (skonfigurowane przez admina)

[Filament Panel — admin loguje się]
         │
         ▼
    /admin dashboard
         ├── StatsOverviewWidget (business scoped)
         ├── LeadInboxWidget (nieprzypisane LP leady)
         └── PipelinePage (Kanban — "New Lead" kolumna)
                    │
                    ▼ (admin klika lead)
              LeadResource::ViewLead
                    ├── Attribution: LP title, utm_source, device_type
                    ├── Checklist: has_client ✓, has_contact ✓, has_email ✓
                    ├── Notes, Activities
                    └── Actions: Assign to me, Move stage, Mark Won/Lost
```

---

## 11. Podsumowanie decyzji (tabela zbiorcza)

| # | Pytanie | Decyzja |
|---|---|---|
| 1 | Nowy byt czy istniejący rekord? | Istniejąca tabela `leads` — bez osobnej tabeli |
| 2 | Client: globalny czy per-tenant? | `firstOrCreate` z `business_id` scope — per-tenant |
| 3 | Czy tworzyć `Contact` automatycznie? | TAK — `Contact` tworzony z danych LP formularza |
| 4 | Domyślny etap pipeline? | Pierwszy etap (`order=1`) dla business, konfigurowalne per LP w v1.1 |
| 5 | Przypisanie opiekuna? | LP.default_assignee_id, fallback NULL (widoczny w LeadInbox) |
| 6 | Anti-spam / deduplication? | Rate limiting (istniejący) + fingerprint hash per (email+LP+dzień) |
| 7 | Enum `leads.source`? | Dodać wartość `landing_page` (addytywna migracja) |
| 8 | Główny mechanizm propagacji? | `LeadCaptured` event → Listeners (Automations + Notify + Scoring) |
| 9 | Multi-tenancy isolated kiedy? | Sprint 1: ręczny scope w Queries; Sprint 3: GlobalScope trait |
| 10 | Notyfikacje do admina? | `LeadCaptureNotification` (DB) + `NewLeadMail` (email) — dwa kanały |
| 11 | LeadsBySourceWidget źródło? | Zmiana z `leads.source` na `lead_sources.type` |
| 12 | Success page po form submit? | In-page success state (sekcja `confirmation`) — MVP |
