# Feature Design: CRM Lead Integration (Landing Pages → CRM)

> Data: 2026-03-31  
> Bazuje na: `docs/crm-integration-plan.md`, `docs/crm-integration-analysis.md`, `docs/architecture-plan.md`  
> **Specyfikacja techniczna — bez implementacji kodu**

---

## Definicja modułu

**Cel:** Umożliwić automatyczne przechwycenie danych osoby z formularza landing page i natychmiastowe stworzenie spójnego rekordu CRM (Lead + Client + Contact) w odpowiednim etapie pipeline sprzedażowego — z pełną atrybucją źródła, deduplikacją i powiadomieniami zespołu.

**Bounded Context:** Leads ↔ CRM (bridge między kontekstami `LandingPages` i `CRM`)

**Priorytet MVP:** MUST HAVE

**Zależności:**
- Moduł `Business` (business_id musi istnieć)
- Moduł `LandingPages` (landing_page musi istnieć i być opublikowana)
- Istniejący CRM: `Lead`, `Client`, `Contact`, `PipelineStage`, `LeadActivity`
- Istniejąca warstwa: `CreateLeadAction`, `LeadService`, `LeadSourceService`, `LeadConsentService`
- Istniejący engine: `AutomationEventListener`, `ProcessAutomationJob`

**Użytkownicy:**
- **Anonimowy visitor** — wypełnia formularz na LP (nie zalogowany)
- **Admin / Manager** — przegląda i zarządza leadami w Filament
- **Developer (rola)** — podgląd leadów, brak edycji

---

## 1. Model danych

### 1.1 Tabele modyfikowane (addytywne migracje)

---

#### TABELA: `leads` — nowe kolumny

Istniejąca tabela. Dodajemy pola potrzebne dla LP integration:

```
Nowe kolumny (addytywne migracje — bez zmiany istniejących):

- form_data             JSON, nullable
                        Przechowuje custom pola z formularza LP.
                        Struktura: {"interest": "ecommerce", "timeline": "3months", ...}
                        Nie filtrujemy po tych polach — JSON dla elastyczności.

- source (enum)         Dodać wartość 'landing_page' do istniejącego ENUM:
                        'calculator' | 'contact_form' | 'referral' |
                        'cold_outreach' | 'social_media' | 'landing_page' | 'other'
                        
- ai_score              SMALLINT UNSIGNED, nullable
                        Wynik scoringu AI (0–100). NULL = nie oceniony.

- ai_score_reason       TEXT, nullable
                        Uzasadnienie wyniku w 1–2 zdaniach (OpenAI output).

- score_calculated_at   TIMESTAMP, nullable
                        Kiedy obliczono score — do invalidation.

Indeksy do dodania:
- INDEX(source)          — filtrowanie po source='landing_page'
- INDEX(ai_score)        — sortowanie po score
```

---

#### TABELA: `landing_pages` — nowe kolumny

Istniejąca tabela. Dodajemy pola konfiguracji capture:

```
Nowe kolumny:

- default_assignee_id   BIGINT UNSIGNED, nullable, FK → users.id (nullOnDelete)
                        Domyślny opiekun leadów z tej LP.
                        NULL = lead nieprzypisany (trafia do Lead Inbox).

- default_stage_id      BIGINT UNSIGNED, nullable, FK → pipeline_stages.id (nullOnDelete)
                        Opcjonalny etap pipeline dla leadów z tej LP.
                        NULL = używaj pierwszego etapu (order=1) dla business.
                        v1.1 feature — w MVP zawsze NULL.

- thank_you_url         VARCHAR(2048), nullable
                        URL do którego redirect po sukcesie formularza.
                        NULL = in-page success state (MVP default).

- capture_fields        JSON, nullable
                        Konfiguracja pól formularza per LP.
                        Struktura: 
                        [
                          {"name": "first_name", "label": "Imię", "type": "text", "required": true},
                          {"name": "company",    "label": "Firma", "type": "text", "required": false},
                          {"name": "budget",     "label": "Budżet", "type": "range", "min": 1000, "max": 50000}
                        ]
                        NULL = używaj domyślnych pól (first_name, email, phone, message, consent).

Indeksy:
- INDEX(default_assignee_id)
```

---

### 1.2 Nowe tabele

Tabele `lead_sources` i `lead_consents` zostały już stworzone w poprzedniej sesji. Poniżej dokumentacja do referencji w tym module:

---

#### TABELA: `lead_sources` (istniejąca — bez zmian)

```
Cel: Pełna atrybucja źródła pozyskania leada (marketing analytics).

Kolumny:
- id                    BIGINT PK
- lead_id               FK → leads.id (cascadeOnDelete)
- business_id           CHAR(26), nullable, FK → businesses.id
- type                  VARCHAR — 'landing_page' | 'contact_form' | 'calculator' |
                                  'api' | 'manual' | 'import' | 'referral'
- landing_page_id       FK → landing_pages.id, nullable
- utm_source            VARCHAR(255), nullable
- utm_medium            VARCHAR(255), nullable
- utm_campaign          VARCHAR(255), nullable
- utm_content           VARCHAR(255), nullable
- utm_term              VARCHAR(255), nullable
- referrer_url          VARCHAR(2048), nullable
- page_url              VARCHAR(2048), nullable
- ip_hash               VARCHAR(64), nullable    — SHA-256 hashed IP (GDPR compliant)
- user_agent            TEXT, nullable
- device_type           VARCHAR(20), nullable    — 'mobile' | 'tablet' | 'desktop'
- country_code          CHAR(2), nullable
- created_at            TIMESTAMP

UWAGA: Kolumna `ip_address` (raw IP) MUSI zostać usunięta lub zamieniona.
Migracja S1-14 z crm-integration-plan.md: DROP COLUMN ip_address lub
zmienić na `ip_masked` VARCHAR(20) przechowujący tylko pierwsze 3 oktety (np. '192.168.1.*').

Indeksy:
- INDEX(lead_id)
- INDEX(business_id)
- INDEX(landing_page_id)
- INDEX(type)
```

---

#### TABELA: `lead_consents` (istniejąca — bez zmian)

```
Cel: Audit trail zgody GDPR przy przechwyceniu leada.

Kolumny:
- id                    BIGINT PK
- lead_id               FK → leads.id (cascadeOnDelete)
- given                 BOOLEAN
- consent_text          TEXT            — pełna treść klauzuli wyświetlona userowi
- consent_version       VARCHAR(20)     — np. 'v1.2', 'gdpr-2026-01'
- collected_at          TIMESTAMP
- source_url            VARCHAR(2048)   — URL strony z której zebrana zgoda
- ip_hash               VARCHAR(64)     — SHA-256 hashed IP
- locale                VARCHAR(5)      — 'pl', 'en', 'pt'
- created_at / updated_at

Indeksy:
- UNIQUE(lead_id)       — jeden consent per lead
```

---

### 1.3 Nowa tabela: `lead_duplicates`

```
TABELA: lead_duplicates
Cel: Log prób submit które zostały zidentyfikowane jako duplikaty — do debugowania
     i późniejszej analizy (ile razy ta sama osoba próbowała się zapisać).

Kolumny:
- id                    BIGINT PK
- fingerprint           VARCHAR(64)     — MD5(email + landing_page_id + date)
- email_hash            VARCHAR(64)     — SHA-256(email) — nie raw email dla GDPR
- landing_page_id       FK → landing_pages.id (nullOnDelete)
- original_lead_id      FK → leads.id, nullable (nullOnDelete)
- ip_hash               VARCHAR(64), nullable
- attempted_at          TIMESTAMP

Indeksy:
- INDEX(fingerprint)                        — główny lookup
- INDEX(landing_page_id, attempted_at)
- Brak updated_at (tylko INSERT, nigdy UPDATE)

Uwaga: Tabela jest append-only. Nie przechowuje danych osobowych.
```

---

## 2. Walidacja

### 2.1 `StoreLandingPageLeadRequest`

```
KLASA: app/Http/Requests/Leads/StoreLandingPageLeadRequest.php
GUARD: brak (request publiczny — anonimowy)

Pola i reguły walidacji:

Wymagane:
- email             required | email | max:254
- consent           required | accepted   (musi być true dla GDPR)

Opcjonalne — dane kontaktowe:
- first_name        nullable | string | max:100
- last_name         nullable | string | max:100
- name              nullable | string | max:200     (fallback jeśli brak f/l name)
- phone             nullable | string | max:30 | regex:/^[+\d\s\-\(\)]+$/
- company           nullable | string | max:200

Opcjonalne — dane leada:
- message           nullable | string | max:5000
- budget_min        nullable | integer | min:0 | max:10000000
- budget_max        nullable | integer | min:0 | max:10000000 | gte:budget_min
- project_type      nullable | string | max:200
- form_data         nullable | array | max:50 kluczy  (custom fields)
- form_data.*       nullable | string | max:1000

Tracking (opcjonalne — auto z URL/JS):
- utm_source        nullable | string | max:255
- utm_medium        nullable | string | max:255
- utm_campaign      nullable | string | max:255
- utm_content       nullable | string | max:255
- utm_term          nullable | string | max:255
- referrer_url      nullable | url | max:2048
- locale            nullable | string | in:pl,en,pt | max:5

Niestandardowe reguły:
1. Jeśli `first_name` puste i `name` puste → fail('Proszę podać imię')
2. Jeśli `budget_max` podany i `budget_min` podany → budget_max >= budget_min
3. `email` — dodatkowy check przez `filter_var(FILTER_VALIDATE_EMAIL)`

Wiadomości błędów:
- Wielojęzyczne przez lang/pl/validation.php, lang/en/validation.php, lang/pt/validation.php
- Klucz: 'leads.email_required', 'leads.consent_required', etc.

Odpowiedź przy błędzie walidacji:
- HTTP 422 JSON: { "message": "...", "errors": { "field": ["..."] } }
```

---

## 3. Backend Flow — pełny opis

### 3.1 Endpoint: `POST /api/lp/{landingPage:slug}/capture`

```
KLASA:   app/Http/Controllers/Leads/LeadCaptureController.php
METODA:  store(StoreLandingPageLeadRequest $request, LandingPage $landingPage)

TRASA:
Route::post('/lp/{landingPage:slug}/capture', [LeadCaptureController::class, 'store'])
    ->name('lp.capture')
    ->middleware(['throttle:leads', 'lead.rate-limit']);
    // Bez 'auth' — publiczny endpoint

MIDDLEWARE stack (kolejność wykonania):
1. throttle:leads          — Laravel rate limiter (60/min per IP, konfiguracja routes)
2. LeadRateLimitMiddleware — dodatkowe sprawdzenie per email + IP z config/leads.php
3. StoreLandingPageLeadRequest::authorize() — zawsze true (publiczny)
4. StoreLandingPageLeadRequest::rules() — walidacja pól

LOGIKA KONTROLERA (cienka):
1. Sprawdź czy $landingPage->status === 'published' → jeśli nie: abort(404)
2. Sprawdź czy $landingPage->business→is_active === true → jeśli nie: abort(404)
3. Pobierz $business = $landingPage->business
4. Zbierz $sourceData z request (ip, user_agent, referer, utm_*) + device detection
5. Zbierz $consentData (consent, locale, source_url = current URL)
6. Deleguj: $result = $this->leadService->createFromLandingPage($validated, $sourceData, $consentData, $landingPage)
7. Zwróć odpowiedź JSON

ODPOWIEDZI:
- 201 Created:  { "status": "created", "lead_id": 42, "message": "Dziękujemy! Skontaktujemy się wkrótce." }
- 200 OK:       { "status": "duplicate", "message": "Twoje zgłoszenie zostało już zarejestrowane." }
- 422:          { "message": "...", "errors": { ... } }
- 429:          { "message": "Zbyt wiele zgłoszeń. Spróbuj ponownie za chwilę." }
- 404:          { "message": "Landing page nie istnieje lub nie jest opublikowana." }
```

---

### 3.2 Serwis: `LeadService` — metoda `createFromLandingPage`

```
SERWIS:  app/Services/Leads/LeadService.php
METODA:  createFromLandingPage(array $data, array $sourceData, array $consentData, LandingPage $lp): array

Odpowiedzialność:
Orkiestruje cały proces tworzenia leada z landing page: 
deduplikacja → tworzenie CRM → source + consent → eventy.

Kroki wykonania (w kolejności):

KROK 1 — Sprawdzenie duplikatu
  → $fingerprint = md5($data['email'] . $lp->id . date('Y-m-d'))
  → LeadDuplicateService::check($fingerprint, $lp->id)
  → Jeśli duplikat istnieje (< 24h):
        → LogDuplicateAttempt ($fingerprint, $lp->id, $ip_hash)
        → Zwróć ['status' => 'duplicate', 'lead_id' => $existingLeadId]

KROK 2 — Tworzenie CRM w transakcji DB::transaction()
  → CreateLeadAction::execute($leadData)
       (patrz sekcja 3.3)
  → Jeśli wyjątek: rollback + log + throw LeadCreationException

KROK 3 — Rejestracja źródła
  → LeadSourceService::record($lead, $lp->business, [
        'type'            => 'landing_page',
        'landing_page_id' => $lp->id,
        'ip_hash'         => sha256($sourceData['ip']),
        'ip_masked'       => maskIp($sourceData['ip']),  // pierwsze 3 oktety
        ...utm_*, referrer_url, page_url, user_agent, device_type, country_code
    ])

KROK 4 — Rejestracja zgody GDPR (tylko jeśli consent = true)
  → LeadConsentService::record($lead, [
        'given'           => true,
        'consent_text'    => config('leads.gdpr_text.' . $data['locale']),
        'consent_version' => config('leads.gdpr_version'),
        'collected_at'    => now(),
        'source_url'      => $sourceData['page_url'],
        'ip_hash'         => sha256($sourceData['ip']),
        'locale'          => $data['locale'] ?? 'pl',
    ])

KROK 5 — Wygenerowanie checklist items
  → LeadChecklistItemService::generateForLead($lead)
       (patrz sekcja 3.5)

KROK 6 — Emit event
  → event(new LeadCaptured($lead, $lp))

KROK 7 — Zwróć wynik
  → ['status' => 'created', 'lead_id' => $lead->id]

Zależy od:
  LeadDuplicateService, CreateLeadAction, LeadSourceService, 
  LeadConsentService, LeadChecklistItemService
```

---

### 3.3 Action: `CreateLeadAction` — zaktualizowana logika

```
KLASA:   app/Actions/CreateLeadAction.php
METODA:  execute(array $data): Lead

Istniejąca klasa — poniżej TYLKO ZMIANY względem obecnej implementacji.

ZMIANA 1 — Client::firstOrCreate z business scope (naprawia bug multi-tenancy)

  PRZED: Client::firstOrCreate(['primary_contact_email' => $data['email']], [...])
  
  PO:    Client::where('business_id', $data['business_id'])
               ->where('primary_contact_email', $data['email'])
               ->first()
               ?? Client::create([
                    'business_id'           => $data['business_id'],
                    'primary_contact_email' => $data['email'],
                    'company_name'          => $data['company'] ?? $data['name'] ?? $data['email'],
                    ...
                  ])

ZMIANA 2 — Tworzenie Contact (nowe)

  Po stworzeniu lub znalezieniu Client:
  
  $contact = Contact::where('client_id', $client->id)
                    ->where('email', $data['email'])
                    ->first();
  
  Jeśli $contact nie istnieje:
    [$firstName, $lastName] = splitName($data['first_name'] ?? $data['name'], $data['last_name']);
    
    $contact = Contact::create([
        'client_id'  => $client->id,
        'first_name' => $firstName,
        'last_name'  => $lastName ?? '',
        'email'      => $data['email'],
        'phone'      => $data['phone'] ?? null,
        'is_primary' => true, // LP lead = primary contact
    ]);
  
  Helper splitName(string $name, ?string $lastName): [$firstName, $lastName]
    Logika: Jeśli $lastName niepusty → użyj jako is. Jeśli $name zawiera spację → explode.
    Fallback: $firstName = $name, $lastName = null.

ZMIANA 3 — Lead::create z contact_id + assigned_to + form_data + source='landing_page'

  Lead::create([
    ...,                             // istniejące pola
    'contact_id'        => $contact?->id,          // NOWE
    'source'            => 'landing_page',          // ZMIENIONE (zamiast calc)
    'assigned_to'       => $data['assigned_to'] ?? $data['lp_default_assignee_id'] ?? null, // NOWE
    'form_data'         => $data['form_data'] ?? null,  // NOWE
    'budget_min'        => $data['budget_min'] ?? null,
    'budget_max'        => $data['budget_max'] ?? null,
  ])

ZMIANA 4 — LeadActivity z bogatszym kontekstem

  LeadActivity::log($lead->id, 'created', 'Lead captured from landing page', [
    'landing_page_id'    => $data['landing_page_id'],
    'landing_page_title' => $data['landing_page_title'] ?? null,
    'source'             => 'landing_page',
    'contact_created'    => $contact !== null,
    'client_was_new'     => $clientWasNew,  // bool
  ], null);

BEZ ZMIAN: NewLeadMail (queued) — wysyłany do admin email
```

---

### 3.4 Serwis: `LeadDuplicateService`

```
SERWIS:  app/Services/Leads/LeadDuplicateService.php

Odpowiedzialność: Wykrywanie i logowanie zduplikowanych zgłoszeń z LP formularzy.

Metody:

check(string $fingerprint, int $landingPageId): ?int
  Sprawdza czy istnieje duplikat dla tego fingerprint w ciągu ostatnich 24h.
  Zwraca lead_id jeśli duplikat, null jeśli brak duplikatu.
  Logika:
    → Lead::where('business_id', ...)
           ->where(DB::raw('MD5(CONCAT(primary_contact_email, landing_page_id, DATE(created_at)))'), $fingerprint)
           ->where('created_at', '>=', now()->subHours(24))
           ->value('id')
  
  ALBO prościej — cache-based:
    → Cache::get("lp_duplicate_{$fingerprint}") → zwróć lead_id lub null

logAttempt(string $fingerprint, int $landingPageId, ?int $originalLeadId, string $ipHash): void
  Tworzy rekord w lead_duplicates.
  Loguje do logów aplikacji (kanał 'leads').

buildFingerprint(string $email, int $landingPageId): string
  Zwraca: md5(strtolower(trim($email)) . '|' . $landingPageId . '|' . date('Y-m-d'))
  Uwaga: lowercase + trim email przed hash dla case-insensitive deduplication.

Zależy od: Cache (Redis lub database), LeadDuplicate model
```

---

### 3.5 Serwis: `LeadChecklistItemService`

```
SERWIS:  app/Services/Leads/LeadChecklistItemService.php

Odpowiedzialność: Automatyczne generowanie i aktualizacja checklisty pipeline dla leada.

Metody:

generateForLead(Lead $lead): void
  Po stworzeniu leada — generuje LeadChecklistItem rekordy na podstawie
  checklist dla aktualnego pipeline_stage.
  
  Logika:
    $stage = $lead->stage;
    $checklist = $stage->checklist ?? [];  // JSON array warunków
    
    foreach ($checklist as $index => $item) {
        $isMet = $this->evaluateCondition($item['condition'], $lead);
        
        LeadChecklistItem::create([
            'lead_id'           => $lead->id,
            'pipeline_stage_id' => $stage->id,
            'item_index'        => $index,
            'completed_by'      => $isMet ? null : null,  // system, nie user
            'completed_at'      => $isMet ? now() : null,
        ]);
    }

evaluateCondition(string $condition, Lead $lead): bool
  Mapowanie warunków na walidację pól leada:
  
  'has_client'         → $lead->client_id !== null
  'has_contact'        → $lead->contact_id !== null
  'has_email'          → $lead->client?->primary_contact_email !== null
  'has_phone'          → $lead->client?->primary_contact_phone !== null
  'has_assignee'       → $lead->assigned_to !== null
  'has_value'          → $lead->value !== null
  'has_notes'          → !empty($lead->notes)
  'has_expected_close' → $lead->expected_close_date !== null
  'has_calculator_data'→ !empty($lead->calculator_data)
  'email_sent'         → sprawdź LeadActivity gdzie type='email_sent'
  'has_project'        → $lead->project !== null
  default              → false

regenerateForStage(Lead $lead, PipelineStage $newStage): void
  Po zmianie etapu — usuwa stare checklisty i generuje nowe dla nowego etapu.
  Logika: DELETE istniejące dla (lead_id + stage_id), potem generateForLead() z nowym stage.

Zależy od: LeadChecklistItem model, PipelineStage model
```

---

### 3.6 Listener: `NotifyLeadOwnerListener`

```
KLASA:  app/Listeners/NotifyLeadOwnerListener.php
EVENT:  App\Events\LeadCaptured

Odpowiedzialność: 
Natychmiastowe powiadomienie opiekuna (lub wszystkich adminów/managerów) o nowym leadzie z LP.

handle(LeadCaptured $event): void

Logika:
  $lead  = $event->lead;
  $lp    = $event->landingPage;
  
  Jeśli $lead->assigned_to !== null:
    $recipient = User::find($lead->assigned_to)
    → Wyślij DatabaseNotification do $recipient
    → Jeśli $recipient->notify_email (preferencja) → wyślij email w tle (Job)
  
  Jeśli $lead->assigned_to === null:
    $admins = User::role(['admin', 'manager'])
                  ->whereHas('businesses', fn($q) => $q->where('business_id', $lead->business_id))
                  ->get()
    → foreach $admins → DatabaseNotification

Typ notyfikacji: 
  LeadCapturedNotification (Notification klasa — patrz sekcja 5.4)

Rejestracja listenera:
  W AppServiceProvider::boot():
    Event::listen(LeadCaptured::class, NotifyLeadOwnerListener::class);
  
  LUB w EventServiceProvider::$listen (jeśli istnieje):
    LeadCaptured::class => [
        AutomationEventListenerAsSubscriber::class,
        NotifyLeadOwnerListener::class,
    ]
```

---

### 3.7 Zaktualizowany `AutomationEventListener`

```
KLASA:  app/Listeners/AutomationEventListener.php

ZMIANY — nowe metody subscribe:

W metodzie subscribe() dodać:
  $events->listen(LeadCaptured::class, [self::class, 'onLeadCaptured']);
  $events->listen(LeadAssigned::class, [self::class, 'onLeadAssigned']);

Nowe metody:

onLeadCaptured(LeadCaptured $event): void
  $this->dispatch('lead.created', [
    'lead_id'         => $event->lead->id,
    'client_id'       => $event->lead->client_id,
    'business_id'     => $event->lead->business_id,
    'source'          => 'landing_page',
    'landing_page_id' => $event->landingPage->id,
    'assigned_to'     => $event->lead->assigned_to,
  ]);

onLeadAssigned(LeadAssigned $event): void
  $this->dispatch('lead.assigned', [
    'lead_id'     => $event->lead->id,
    'client_id'   => $event->lead->client_id,
    'business_id' => $event->lead->business_id,
    'assignee_id' => $event->assignee->id,
  ]);

ZMIANA w onLeadUpdated():
  Dodać check na won_at / lost_at:
  
  Jeśli $lead->wasChanged('won_at') && $lead->won_at !== null:
    $this->dispatch('lead.won', ['lead_id' => $lead->id, 'client_id' => $lead->client_id])
    
    // auto-move do stage z is_won=true
    $wonStage = PipelineStage::where('business_id', $lead->business_id)
                             ->where('is_won', true)->first();
    Jeśli $wonStage && $lead->pipeline_stage_id !== $wonStage->id:
        $lead->updateQuietly(['pipeline_stage_id' => $wonStage->id]);
        // updateQuietly żeby nie triggerować recursive event

  Jeśli $lead->wasChanged('lost_at') && $lead->lost_at !== null:
    $this->dispatch('lead.lost', [...])
    // analogicznie do won — auto-move do is_lost stage
```

---

### 3.8 Job: `ProcessLeadNotificationJob`

```
KLASA:  app/Jobs/ProcessLeadNotificationJob.php
QUEUE:  'notifications' (osobna kolejka — nie blokuje automations)
TRIES:  3
BACKOFF: [30, 60, 120] sekund

Odpowiedzialność: 
Asynchroniczne wysłanie email powiadomienia o nowym leadzie do opiekuna.
Oddzielony od synchronicznego DatabaseNotification w `NotifyLeadOwnerListener`.

constructor(int $leadId, int $recipientUserId, string $type): void
  type: 'lead_captured' | 'lead_assigned' | 'lead_stale' | 'lead_won'

handle(): void
  $lead = Lead::with(['client', 'landingPage', 'stage'])->find($this->leadId);
  $user = User::find($this->recipientUserId);
  
  Sprawdź preferencje usera (user->notify_email — jeśli takie pole istnieje)
  
  switch ($this->type):
    'lead_captured': Mail::to($user->email)->send(new NewLeadAssignedMail($lead, $user))
    'lead_assigned': Mail::to($user->email)->send(new LeadAssignedMail($lead, $user))
    'lead_stale':    Mail::to($user->email)->send(new StaleLeadReminderMail($lead, $user))
    'lead_won':      // opcjonalnie — logi, stats

Uwaga: `NewLeadMail` (istniejący) — wysyłany do admin@websiteexpert.co.uk (hardcoded).
`NewLeadAssignedMail` (nowy) — do przypisanego użytkownika (dynamiczny email).
```

---

### 3.9 Naprawka: `LeadService::markWon()` i `markLost()`

```
SERWIS:  app/Services/Leads/LeadService.php

ZMIANA w markWon(Lead $lead, User $actor): Lead
  Dodać po $lead->update(['won_at' => now()]):
  
  $wonStage = PipelineStage::where('business_id', $lead->business_id)
                            ->where('is_won', true)->first();
  Jeśli $wonStage:
    $lead->updateQuietly(['pipeline_stage_id' => $wonStage->id]);
    LeadActivity::log($lead->id, 'stage_moved', 
        "Stage moved to {$wonStage->name} (won)", 
        ['stage' => $wonStage->name, 'auto' => true], 
        $actor->id);
  
  ProcessAutomationJob::dispatch('lead.won', [
    'lead_id'   => $lead->id,
    'client_id' => $lead->client_id,
  ]);
  
  // Update client status → 'active' jeśli był 'prospect'
  Jeśli $lead->client && $lead->client->status === 'prospect':
    $lead->client->updateQuietly(['status' => 'active']);

ZMIANA w markLost(Lead $lead, string $reason, User $actor): Lead
  Analogicznie: auto-move do stage z is_lost=true + dispatch 'lead.lost'.
```

---

## 4. API — wymagane endpointy

### 4.1 Publiczne endpoint (bez auth)

| Metoda | URL | Kontroler | Cel |
|---|---|---|---|
| `POST` | `/api/lp/{landingPage:slug}/capture` | `LeadCaptureController@store` | Zapis leada z formularza LP |
| `GET` | `/lp/{landingPage:slug}` | `LandingPageController@show` | Publiczny widok LP (Inertia) |
| `GET` | `/lp/{landingPage:slug}/thanks` | `LandingPageController@thanks` | Strona podziękowania (opcjonalna) |

### 4.2 Chronione endpointy (auth + business scope)

| Metoda | URL | Kontroler | Cel |
|---|---|---|---|
| `GET` | `/admin/leads/inbox` | Filament strona | Lead Inbox nieprzypisane leady |
| `PATCH` | `/admin/leads/{lead}/assign` | `LeadWebController@assign` | Przypisz lead do zalogowanego usera |
| `PATCH` | `/admin/leads/{lead}/stage` | `LeadWebController@updateStage` | Zmień etap pipeline |
| `POST` | `/admin/leads/{lead}/won` | `LeadWebController@markWon` | Oznacz jako wygrany |
| `POST` | `/admin/leads/{lead}/lost` | `LeadWebController@markLost` | Oznacz jako przegrany |

### 4.3 Format request/response dla głównego endpointu

**Request:** `POST /api/lp/{slug}/capture`

```json
{
  "first_name": "Jan",
  "last_name": "Kowalski",
  "email": "jan@example.com",
  "phone": "+48 123 456 789",
  "company": "JKD Studio",
  "message": "Szukam strony dla fryzjera, chciałbym działać ASAP",
  "budget_min": 3000,
  "budget_max": 8000,
  "consent": true,
  "form_data": {
    "interest": "ecommerce",
    "timeline": "1month"
  },
  "utm_source": "google",
  "utm_medium": "cpc",
  "utm_campaign": "spring2026-seo",
  "referrer_url": "https://google.com/search?q=strona+www",
  "locale": "pl"
}
```

**Response 201:**
```json
{
  "status": "created",
  "lead_id": 142,
  "message": "Dziękujemy! Skontaktujemy się z Tobą wkrótce."
}
```

**Response 200 (duplikat):**
```json
{
  "status": "duplicate",
  "message": "Twoje zgłoszenie zostało już zarejestrowane. Skontaktujemy się wkrótce."
}
```

**Response 422:**
```json
{
  "message": "Dane formularza są nieprawidłowe.",
  "errors": {
    "email": ["Podaj prawidłowy adres e-mail."],
    "consent": ["Zgoda jest wymagana."]
  }
}
```

**Response 429:**
```json
{
  "message": "Zbyt wiele zgłoszeń z tego adresu. Spróbuj ponownie za 60 minut.",
  "retry_after": 3600
}
```

---

## 5. Zmiany w Filament

### 5.1 `LeadResource` — zmiany istniejącego Resource

```
PLIK: app/Filament/Resources/LeadResource.php

ZMIANA 1 — getEloquentQuery(): multi-tenant scope
  Dodać: ->where('leads.business_id', currentBusiness()->id)
  
ZMIANA 2 — Tabela: nowe kolumny
  Dodać kolumny:
  - TextColumn::make('contact.full_name')
      →label('Kontakt'), toggleable, searchable
  - TextColumn::make('leadSource.landing_page.title')
      → label('Landing Page'), toggleable, placeholder('—')
  - TextColumn::make('ai_score')
      → label('Score'), badge, nullable
      → colory: 0-30 = 'danger', 31-60 = 'warning', 61-100 = 'success'
  - TextColumn::make('form_data')
      → ukryta domyślnie, toggleable (debug)

ZMIANA 3 — Filtry: dodać filtr LP
  Dodać:
  - SelectFilter::make('landing_page_id')
      → relationship('landingPage', 'title')
      → label('Landing Page')
      → searchable()
  
  - SelectFilter::make('source')
      → label('Źródło')
      → options([...istniejące..., 'landing_page' => 'Landing Page'])
  
  - TernaryFilter::make('assigned')
      → label('Przypisany')
      → nullable() / truthy: assigned_to IS NOT NULL / falsy: assigned_to IS NULL

ZMIANA 4 — Formularz: nowe pola
  Dodać do formularza EditLead:
  - TextInput::make('budget_min') → numeric, prefix('min')
  - TextInput::make('budget_max') → numeric, prefix('max')
  - KeyValue::make('form_data')   → label('Dane formularza'), disableAddingRows

ZMIANA 5 — Akcje: Assign to me
  Dodać TableAction::make('assignToMe'):
    → label('Przypisz do mnie')
    → icon('heroicon-m-user-plus')
    → action: lead->update(['assigned_to' => auth()->id()])
    → visible: fn($record) => $record->assigned_to === null
    → requiresConfirmation: false

ZMIANA 6 — ViewLead: sekcja Attribution
  W ViewLead infolist dodać sekcję "Źródło i atrybucja":
  - LeadSource.type (badge)
  - LeadSource.landing_page.title (link do LP)
  - UTM: source, medium, campaign, content, term
  - LeadSource.device_type (badge)
  - LeadSource.country_code
  - LeadConsent.given (boolean icon)
  - LeadConsent.consent_version
  - LeadConsent.collected_at
```

---

### 5.2 `ClientResource` — zmiany istniejącego Resource

```
PLIK: app/Filament/Resources/ClientResource.php

ZMIANA 1 — getEloquentQuery(): multi-tenant scope
  Dodać: ->where('clients.business_id', currentBusiness()->id)

ZMIANA 2 — Tabela: leads count z LP
  Dodać kolumnę:
  - TextColumn::make('lp_leads_count')
      → counts('leads', fn($q) => $q->where('source', 'landing_page'))
      → label('Leady z LP')
      → badge
      → color('info')
      → sortable
```

---

### 5.3 Nowy Filament Resource: `LandingPageResource`

```
PLIK:       app/Filament/Resources/LandingPageResource.php
NAWIGACJA:  Group: 'Marketing', Sort: 1, Icon: heroicon-o-globe-alt
MODEL:      App\Models\LandingPage

getEloquentQuery(): zwraca LP filtrowane przez business_id

Tabela:
- TextColumn::make('title')           → searchable, sortable, weight('bold')
- TextColumn::make('slug')            → monospace, copyable (kopia URL)
- BadgeColumn::make('status')         → colors: draft=gray, published=success, archived=warning
- TextColumn::make('template_type')   → badge, gray
- TextColumn::make('leads_count')     → counts('leads'), badge, color('info')
- TextColumn::make('conversion_rate') → accessor, format '%', color(fn)
- TextColumn::make('published_at')    → dateTime, sortable, since()

Formularz (CreateLandingPage / EditLandingPage):
Section 'Podstawowe':
  - TextInput::make('title')           → required, maxLength(200)
  - TextInput::make('slug')            → required, unique per business_id, regex
  - Select::make('status')             → options: draft/published/archived
  - Select::make('template_type')      → options: lead_capture/sales/webinar/coming_soon
  - Select::make('language')           → options: pl/en/pt

Section 'Ustawienia przechwytywania leadów':
  - Select::make('default_assignee_id')
      → relationship('users' filtered by business)
      → label('Domyślny opiekun leadów')
      → nullable, searchable
  - TextInput::make('thank_you_url')
      → nullable, url, label('URL strony dziękujemy')
      → helperText('Pozostaw puste dla in-page communicatu')
  - Toggle::make('capture_fields_custom')
      → label('Własna konfiguracja pól formularza')
      → reactive
  - Repeater::make('capture_fields')
      → visible gdy capture_fields_custom=true
      → schema: [TextInput name, TextInput label, Select type, Toggle required]

Section 'SEO':
  - TextInput::make('meta_title')
  - Textarea::make('meta_description')

Akcje w tabeli:
  - EditAction
  - Action::make('publish')     → toggle status, visible dla draft/archived
  - Action::make('unpublish')   → visible dla published
  - Action::make('copyUrl')     → clipboard copy /lp/{slug}
  - Action::make('preview')     → openUrlInNewTab('/lp/{slug}')
  - DeleteAction                → soft delete

RelationManagers:
  - LeadsRelationManager       → tabela leadów dla tej LP (z filtrami)
  - LandingPageSectionsRelationManager → edycja sekcji (v1.1)

Filtry:
  - SelectFilter::make('status')
  - SelectFilter::make('template_type')
  - SelectFilter::make('language')
  - Filter::make('has_assignee') → has konfigurację default_assignee_id
```

---

### 5.4 Nowy Filament Widget: `LeadInboxWidget`

```
PLIK:   app/Filament/Widgets/LeadInboxWidget.php
SORT:   3 (po StatsOverview i RecentLeads)
SPAN:   'full'
HEADING: 'Lead Inbox — Nowe nieprzypisane leady'

query():
  Lead::with(['client', 'landingPage', 'leadSource'])
      ->where('business_id', currentBusiness()->id)
      ->where('source', 'landing_page')
      ->whereNull('assigned_to')
      ->whereNull('deleted_at')
      ->latest()
      ->limit(15)

Kolumny tabeli:
  - TextColumn::make('created_at')     → since(), label('Kiedy')
  - TextColumn::make('client.primary_contact_name') → label('Kontakt'), weight('bold')
  - TextColumn::make('client.primary_contact_email') → label('Email'), copyable
  - TextColumn::make('landingPage.title') → label('Landing Page'), badge, gray
  - TextColumn::make('leadSource.utm_campaign') → label('Kampania'), placeholder('—')
  - TextColumn::make('stage.name')     → label('Etap'), badge

Akcje wiersza:
  - Action::make('assignToMe')   → 'Przypisz do mnie', heroicon-m-user-plus
  - Action::make('view')         → link do LeadResource::ViewLead

Headings:
  Jeśli 0 rekordów → "Brak nowych leadów. Świetna robota! 🎉"

Widoczność:
  static::canView(): can('view_lead_inbox') || hasAnyRole(['admin', 'manager'])
```

---

### 5.5 Notyfikacje Filament

```
KLASA 1: app/Notifications/LeadCapturedNotification.php
  Channels: database
  Via:      ['database']
  
  toDatabase():
    title:   'Nowy lead z landing page'
    body:    "{$lead->client->primary_contact_name} — {$lead->landingPage->title}"
    icon:    'heroicon-o-funnel'
    color:   'info'
    actions: [
      NotificationAction::make('view')
          ->button()
          ->url(LeadResource::getUrl('view', ['record' => $lead]))
    ]

KLASA 2: app/Notifications/LeadAssignedNotification.php
  Channels: database (+ queued email — opcjonalnie)
  
  toDatabase():
    title:   'Lead przypisany do Ciebie'
    body:    "Lead '{$lead->title}' został Ci przypisany"
    color:   'warning'
    actions: [view button]

KLASA 3: app/Notifications/LeadStaleNotification.php
  Channels: database
  
  toDatabase():
    title:   'Lead bez aktywności od 7+ dni'
    body:    "'{$lead->title}' — ostatnia aktywność: {$lead->updated_at->diffForHumans()}"
    color:   'danger'
    actions: [view button]
```

---

### 5.6 Naprawka widgetów — multi-tenant scope

```
PLIK: app/Filament/Widgets/StatsOverviewWidget.php
  Dodać $businessId = currentBusiness()?->id
  Lead::whereNull('deleted_at')->where('business_id', $businessId)->whereMonth(...)
  Invoice::where('status', 'paid')->where('business_id', $businessId)->whereMonth(...)

PLIK: app/Filament/Widgets/RecentLeadsWidget.php
  Lead::withoutTrashed()->where('business_id', currentBusiness()?->id)->latest()->limit(8)

PLIK: app/Filament/Widgets/StaleLeadsWidget.php
  Dodać ->where('business_id', currentBusiness()?->id)

PLIK: app/Filament/Widgets/LeadsBySourceWidget.php
  ZMIANA: z leads.source → lead_sources.type
  
  PRZED: Lead::selectRaw('source, COUNT(*) as total')->groupBy('source')
  
  PO:    LeadSource::selectRaw('type, COUNT(*) as total')
               ->where('business_id', currentBusiness()?->id)
               ->groupBy('type')
               ->orderByDesc('total')
               ->pluck('total', 'type')
  
  Labels update: 'landing_page' → 'Landing Page', etc.
```

---

## 6. Frontend (Inertia + React)

### 6.1 Typy (JSDoc + PropTypes — projekt używa .jsx, nie TypeScript)

```
// resources/js/types/leads.js  (JSDoc dla IDE hint)

/**
 * @typedef {Object} LandingPageLead
 * @property {string} first_name
 * @property {string} [last_name]
 * @property {string} email
 * @property {string} [phone]
 * @property {string} [company]
 * @property {string} [message]
 * @property {number} [budget_min]
 * @property {number} [budget_max]
 * @property {boolean} consent
 * @property {Object} [form_data]
 * @property {string} [utm_source]
 * @property {string} [utm_medium]
 * @property {string} [utm_campaign]
 * @property {string} [utm_content]
 * @property {string} [utm_term]
 * @property {string} [locale]
 */

/**
 * @typedef {'idle'|'submitting'|'success'|'duplicate'|'error'|'rate_limited'} LeadCaptureStatus
 */

/**
 * @typedef {Object} CaptureField
 * @property {string} name
 * @property {string} label
 * @property {'text'|'email'|'tel'|'textarea'|'range'|'select'|'checkbox'} type
 * @property {boolean} required
 * @property {string} [placeholder]
 * @property {number} [min]
 * @property {number} [max]
 * @property {Array<{value:string,label:string}>} [options]
 */
```

---

### 6.2 Hook: `useLeadCapture` — zaktualizowany

```
// resources/js/Hooks/useLeadCapture.js

Parametry props:
  - landingPageSlug: string  (slug LP)
  - fields: CaptureField[]   (konfiguracja pól z LP)
  - locale: string           (domyślnie 'pl')

Stan zarządzany przez hook:
  - formData: Object         (wartości pól)
  - errors: Object           (błędy walidacji z API)
  - status: LeadCaptureStatus
  - isDirty: boolean
  - retryAfter: number|null  (sekundy do retry po 429)

Metody zwracane przez hook:
  - handleChange(field, value) → aktualizacja formData
  - handleSubmit(e)            → submisja formularza
  - reset()                   → reset stanu
  - register(fieldName)        → helper dla input props {name, value, onChange, error}

Logika handleSubmit:
  1. preventDefault()
  2. Ustaw status = 'submitting'
  3. Zbierz UTM z URL params (window.location.search) 
     → parseUtmFromUrl() helper
  4. Zbierz locale z <html lang> lub props
  5. POST do `/api/lp/{slug}/capture` przez axios lub fetch (nie useForm Inertia!)
  6. Na 201: ustaw status = 'success'
  7. Na 200: ustaw status = 'duplicate'
  8. Na 422: ustaw errors = response.data.errors, status = 'error'
  9. Na 429: ustaw status = 'rate_limited', retryAfter = response.data.retry_after
 10. Na inne: status = 'error', errors = {_global: 'Wystąpił błąd...'}

Użycie axios (nie Inertia useForm):
  Powód: Formularz LP jest publiczny, renderowany poza kontekstem Inertia.
  Inertia useForm wysyła x-inertia header — może konfliktować z publicznym endpointem.
  Używamy czystego axios.post() lub fetch().

Pobieranie CSRF token:
  axios.defaults.headers.common['X-CSRF-TOKEN'] = 
    document.head.querySelector('meta[name="csrf-token"]')?.content;

Nowe funkcjonalności względem obecnej wersji:
  - Obsługa pola 'range' (budget_min/budget_max z range slidera)
  - Obsługa pola 'select' (custom choices)
  - Obsługa custom `form_data` — dynamiczne pola z capture_fields JSON
  - Countdown timer dla stanu rate_limited (retryAfter countdown)
  - localStorage persist formData — jeśli user odświeży stronę mid-fill
    Key: `lp_form_${slug}`, TTL: 30 minut
```

---

### 6.3 Komponent: `LeadCaptureForm`

```
// resources/js/Components/LandingPage/LeadCaptureForm.jsx

Props:
  - landingPage: { slug, title, capture_fields, language }
  - gdprText: string    (treść klauzuli GDPR z backenda)
  - csrfToken: string   (z <head> meta)
  - className: string   (opcjonalnie)

Rendery per status:
  - 'idle' / 'error':     Formularz z polami
  - 'submitting':         Formularz z loading state (spinner, disabled)
  - 'success':            SuccessMessage komponent
  - 'duplicate':          DuplicateMessage komponent
  - 'rate_limited':       RateLimitedMessage z odliczaniem

Renderowanie dynamicznych pól:
  Iteruje po `capture_fields` (lub domyślna lista pól):
  
  Domyślne pola (gdy capture_fields = null/puste):
  [
    {name: 'first_name', label: 'Imię', type: 'text', required: true},
    {name: 'email',      label: 'E-mail', type: 'email', required: true},
    {name: 'phone',      label: 'Telefon', type: 'tel', required: false},
    {name: 'message',    label: 'Wiadomość', type: 'textarea', required: false},
    {name: 'consent',    label: gdprText, type: 'checkbox', required: true},
  ]

Obsługa pola 'range' (budget):
  Dwa pola: budget_min + budget_max
  Wyświetl jako: dwa TextInput lub RangeSlider
  Walidacja inline: max >= min

Dostępność (a11y):
  - aria-label na każdym input
  - aria-describedby dla błędów
  - aria-live="polite" dla komunikatów sukcesu/błędu
  - tabIndex management przy focus trap w success state

Wielojęzyczność:
  - Labele z props (z LP konfiguracji)
  - Komunikaty błędów z lang/[locale]/leads.php przez backend
  - Lokalne formatowanie: useLocale() hook
```

---

### 6.4 Komponenty pomocnicze

```
// resources/js/Components/LandingPage/SuccessMessage.jsx
Props: { leadId, message, thankYouUrl }
Logika: Jeśli thankYouUrl → window.location.href = thankYouUrl
        Jeśli nie → pokaż in-page success state
Content: Ikonka check, tytuł 'Dziękujemy!', podtytuł, opcjonalny timer powrotu

// resources/js/Components/LandingPage/DuplicateMessage.jsx
Props: { message }
Content: Ikonka info, tekst 'Zawsze elegancko!, wiadomość, link do kontaktu

// resources/js/Components/LandingPage/RateLimitedMessage.jsx
Props: { retryAfter }
Content: Odliczanie w sekundach, auto-reset formularza po countdown = 0

// resources/js/Components/LandingPage/FormField.jsx  
Props: { field: CaptureField, value, onChange, error }
Renderuje odpowiedni input type (text/email/tel/textarea/range/select/checkbox)
Obsługuje aria-* attributes + error display
```

---

### 6.5 Strona publiczna: `PublicLandingPage.jsx`

```
// resources/js/Pages/LandingPage/PublicLandingPage.jsx

Props z Inertia (z LandingPageController::show()):
  - landingPage: { id, slug, title, status, template_type, language, 
                   sections: [], capture_fields, thank_you_url }
  - gdprText: string    (z config/leads.php → gdpr_texts[locale])
  - csrfToken: string
  - utmParams: object   (już wyparsowane przez kontroler z request)

Layout: PublicLayout (bez sidebar, bez auth header)

Renderowanie sekcji:
  Iteruje po landingPage.sections (posortowane po order):
  Switch na section.type:
    'hero'         → HeroSection
    'features'     → FeaturesSection
    'cta'          → CtaSection
    'form'         → <LeadCaptureForm ...> ← tu osadzony formularz
    'faq'          → FaqSection
    'testimonials' → TestimonialsSection

UTM tracking:
  Przy mount — zapisz UTM params do sessionStorage
  (hook useUtmTracker — istniejący lub do stworzenia)

Meta tags:
  Ustaw <title> = landingPage.meta_title || landingPage.title
  <meta name="description"> = landingPage.meta_description
  Użyj React Helmet lub <Head> z Inertia (@inertiajs/react Head component)
```

---

## 7. Uprawnienia Spatie — zmiany

### 7.1 Nowe uprawnienia do dodania w `AdminSeeder`

```
Nowe uprawnienia:
- 'assign_leads'          → przypisywanie leadów do użytkowników
- 'view_lead_inbox'       → dostęp do Lead Inbox widget
- 'configure_lp_capture'  → konfiguracja formularzy LP (pola, assignee, thank_you)

Przypisanie do ról:

admin: wszystkie (już ma via syncPermissions)

manager: 
  + 'assign_leads'
  + 'view_lead_inbox'
  + 'configure_lp_capture'   ← nowe

developer:
  + 'view_lead_inbox'        ← nowe (podgląd tylko)

client: brak zmian
```

### 7.2 Nowa Policy: `LandingPagePolicy`

```
PLIK:  app/Policies/LandingPagePolicy.php
MODEL: App\Models\LandingPage

Metody:
  viewAny(User $user): 
    can('view_landing_pages') || hasAnyRole(['admin','manager','developer'])
  
  view(User $user, LandingPage $lp):
    viewAny() && $lp->business_id === currentBusiness()?->id
  
  create(User $user):
    can('manage_landing_pages') || hasAnyRole(['admin','manager'])
  
  update(User $user, LandingPage $lp):
    create() && $lp->business_id === currentBusiness()?->id
  
  delete(User $user, LandingPage $lp):
    can('manage_landing_pages') && hasRole('admin')
    && $lp->business_id === currentBusiness()?->id
  
  publish(User $user, LandingPage $lp):
    can('publish_landing_pages') || hasRole('admin')
  
  configureCapture(User $user, LandingPage $lp):
    can('configure_lp_capture') || hasRole('admin')

Rejestracja:
  W AppServiceProvider::boot():
    Gate::policy(LandingPage::class, LandingPagePolicy::class);
```

### 7.3 Nowa Policy: `ClientPolicy`

```
PLIK:  app/Policies/ClientPolicy.php
MODEL: App\Models\Client

Metody:
  viewAny(User $user):
    can('view_clients') || hasAnyRole(['admin','manager','developer'])
  
  view(User $user, Client $client):
    viewAny() && $client->business_id === currentBusiness()?->id
  
  create(User $user):
    can('create_clients') || hasAnyRole(['admin','manager'])
  
  update(User $user, Client $client):
    can('edit_clients') && $client->business_id === currentBusiness()?->id
  
  delete(User $user, Client $client):
    can('delete_clients') && hasRole('admin')
    && $client->business_id === currentBusiness()?->id

Rejestracja:
  Gate::policy(Client::class, ClientPolicy::class);
```

---

## 8. Scenariusze Edge Case

### EC-01 — Email wysłany z LP A, potem z LP B (ta sama osoba)

```
Sytuacja: jan@example.com wypełnia formularz LP "Strona dla fryzjera" (id=1), 
          potem (po >24h) wypełnia formularz LP "Pozycjonowanie SEO" (id=2).

Oczekiwane zachowanie:
1. LP-1: Client firstOrCreate → nowy Client (jan@example.com, business_id=5)
         Lead #100 (landing_page_id=1)
         Contact #50 (client_id=X, email=jan@example.com)

2. LP-2 (po 24h): fingerprint MD5(email + 2 + date) ≠ poprzedni fingerprint
         Client::where('business_id', 5)->where('email', ...) → znaleziony Client (reuse)
         Lead #118 (landing_page_id=2) — nowy lead, ten sam klient
         Contact — już istnieje (no duplicate Contact)

Rezultat: 1 Client, 2 Lead, 1 Contact — poprawne.
```

### EC-02 — Ta sama osoba, inna firma, inna LP (multi-tenant)

```
Sytuacja: jan@example.com wypełnia LP business_id=5 i LP business_id=8.

Oczekiwane zachowanie:
- business_id=5: Client #50, Lead #100  
- business_id=8: Client #51 (nowy! bo different business_id), Lead #101

Powód: Client::firstOrCreate scope'owany przez business_id.
Dane klientów są izolowane per tenant.

Rezultat: 2 Client (1 per tenant), 2 Lead — poprawne dla multi-tenant.
```

### EC-03 — Submit formularz bez zgody GDPR (błąd walidacji)

```
Sytuacja: Bot lub user wysyła request bez pola `consent: true`.

Oczekiwane zachowanie:
→ StoreLandingPageLeadRequest validation fail
→ HTTP 422 z errors.consent = "Zgoda jest wymagana."
→ Brak rekordu w leads, clients, lead_sources, lead_consents
→ Logi: brak (walidacja przed logiką)

Uwaga: Nie rejestrujemy prób bez zgody w lead_duplicates — to normalne zachowanie.
```

### EC-04 — Rate limit przekroczony (anty-spam)

```
Sytuacja: Bot wysyła 4 requesty w < 60 minut z tego samego IP.

Flow:
1. Request 1-3: przechodzą przez LeadRateLimitMiddleware (max 3/60min per IP)
2. Request 4: LeadRateLimitMiddleware → HTTP 429
              headers: Retry-After: 3600
              body: { message: '...', retry_after: 3600 }

Brak zapisu do leads. Brak zapisu do lead_duplicates (middleware przed serwisem).
Log aplikacji: channels.leads → Warning 'Rate limit hit' z hashed IP.
```

### EC-05 — Duplikat w ciągu 24h (ta sama LP, ten sam email)

```
Sytuacja: jan@example.com wypełnia formularz LP "fryzjer" o 10:00 i znowu o 14:00.

Flow @ 10:00:
  Fingerprint = MD5("jan@example.com|1|2026-03-31") = "abc123"
  Lead::checkDuplicate("abc123") → null
  Lead #100 stworzony.
  Cache::put("lp_dup_abc123", 100, ttl=24h).

Flow @ 14:00:
  Fingerprint = MD5("jan@example.com|1|2026-03-31") = "abc123" (ten sam dzień!)
  Lead::checkDuplicate("abc123") → Cache::get → lead_id = 100 (hit!)
  LeadDuplicateService::logAttempt(fingerprint, lp_id=1, lead_id=100, ip_hash)
  → INSERT lead_duplicates
  Odpowiedź: HTTP 200 { status: 'duplicate', message: '...' }

Brak nowego Lead, Client, LeadSource, LeadConsent.
```

### EC-06 — LP niepublikowana przy subimsji (race condition)

```
Sytuacja: Admin unpublishuje LP w trakcie gdy user ma otwarty formularz w przeglądarce.
          User klika "Wyślij" po unpublish.

Flow:
1. GET /lp/{slug} → 200 (cache przeglądarki lub CDN)
2. POST /api/lp/{slug}/capture:
   → LandingPageController pobiera LP
   → $landingPage->status !== 'published' → abort(404)
   → HTTP 404 { message: 'Landing page nie istnieje lub nie jest opublikowana.' }

Frontend obsługa: status 404 → pokaż ErrorMessage: "Ta strona nie jest już dostępna."
```

### EC-07 — Brak etapu pipeline (business bez pipeline)

```
Sytuacja: Nowy business, admin nie dodał żadnych etapów pipeline.
          Ktoś wypełnia formularz LP.

Flow:
  PipelineStage::where('business_id', ...).orderBy('order').first() → null

Oczekiwane zachowanie:
  Lead::create(['pipeline_stage_id' => null]) → BŁĄD! (pipeline_stage_id has NOT NULL constraint)
  
ROZWIĄZANIE:
  W CreateLeadAction: Jeśli $stage === null:
    → Stwórz domyślny etap automatycznie:
      PipelineStage::create([
          'business_id' => $businessId,
          'name'  => 'New Lead',
          'slug'  => 'new-lead',
          'color' => '#6B7280',
          'order' => 1,
          'is_won'  => false,
          'is_lost' => false,
      ])
    → Log: info("Auto-created default pipeline stage for business {$businessId}")
```

### EC-08 — Transakcja DB nieudana (np. deadlock)

```
Sytuacja: DB::transaction() w LeadService::createFromLandingPage() rzuca wyjątek
          po Client::create ale przed Lead::create.

Flow:
  DB::transaction() → rollback()
  → Client cofnięty
  → Lead nie stworzony
  → LeadCreationException przechwycony w kontrolerze
  → HTTP 500 z ogólnym komunikatem (nie ujawniaj szczegółów)
  → Log::error('Lead creation failed', ['exception' => $e, 'email' => ...])
  
Frontend: HTTP 500 → ErrorMessage: "Wystąpił błąd systemowy. Spróbuj ponownie."
Monitoring: Alert przez Sentry/Bugsnag (jeśli skonfigurowany).
```

### EC-09 — LP z `capture_fields = null` (domyślne pola)

```
Sytuacja: Admin nie skonfigurował custom pól formularza — capture_fields = null.

Oczekiwane zachowanie:
Frontend: Użyj domyślnej listy pól:
  [first_name (required), email (required), phone (optional), 
   message (optional), consent (required)]

Backend: StoreLandingPageLeadRequest używa tej samej domyślnej walidacji
         niezależnie od capture_fields.
         Custom capture_fields zmienia tylko renderowanie UI, nie walidację backendu.
```

### EC-10 — Lead z telefonu i tylko `name` (bez first/last name)

```
Sytuacja: Formularz LP ma jedno pole "Imię i nazwisko" (type: text, name: name).
          User wpisuje "Jan Kowalski".

Flow w splitName("Jan Kowalski", null):
  explode(' ', 'Jan Kowalski', 2) → ['Jan', 'Kowalski']
  first_name = 'Jan', last_name = 'Kowalski' ✓

Flow w splitName("Madonna", null):
  Pojedyncze słowo — brak spacji
  first_name = 'Madonna', last_name = '' (pusty string)
  Contact::create(['first_name' => 'Madonna', 'last_name' => '']) ✓

Flow w splitName("Jan van den Berg", null):
  explode(' ', ..., 2) → ['Jan', 'van den Berg']
  first_name = 'Jan', last_name = 'van den Berg' ✓
```

---

## 9. Migracje (lista plików do stworzenia)

```
Kolejność wykonania migracji:

1. xxxx_xx_xx_add_landing_page_to_leads_source_enum.php
   → ALTER TABLE leads MODIFY COLUMN source ENUM(... , 'landing_page', ...)

2. xxxx_xx_xx_add_form_data_to_leads_table.php
   → ALTER TABLE leads ADD COLUMN form_data JSON NULL AFTER calculator_data

3. xxxx_xx_xx_add_ai_score_to_leads_table.php
   → ALTER TABLE leads 
     ADD COLUMN ai_score SMALLINT UNSIGNED NULL,
     ADD COLUMN ai_score_reason TEXT NULL,
     ADD COLUMN score_calculated_at TIMESTAMP NULL

4. xxxx_xx_xx_add_capture_fields_to_landing_pages_table.php
   → ALTER TABLE landing_pages
     ADD COLUMN default_assignee_id BIGINT UNSIGNED NULL REFERENCES users(id) ON DELETE SET NULL,
     ADD COLUMN default_stage_id BIGINT UNSIGNED NULL REFERENCES pipeline_stages(id) ON DELETE SET NULL,
     ADD COLUMN thank_you_url VARCHAR(2048) NULL,
     ADD COLUMN capture_fields JSON NULL

5. xxxx_xx_xx_create_lead_duplicates_table.php
   → CREATE TABLE lead_duplicates (...)

6. xxxx_xx_xx_remove_ip_address_from_lead_sources_table.php
   → ALTER TABLE lead_sources
     DROP COLUMN ip_address,
     ADD COLUMN ip_masked VARCHAR(20) NULL

7. xxxx_xx_xx_add_business_id_to_clients_table.php  (jeśli brak)
   → ALTER TABLE clients ADD COLUMN business_id CHAR(26) NULL REFERENCES businesses(id) ON DELETE SET NULL
   → UPDATE clients SET business_id = (SELECT id FROM businesses ORDER BY created_at LIMIT 1) WHERE business_id IS NULL
   → ALTER TABLE clients ADD INDEX idx_clients_business_id (business_id)
```

---

## 10. Checklist implementacji

### Sprint 1 — Krytyczne (przed oddaniem użytkownikom)

- [ ] Migracja 1: enum leads.source + 'landing_page'
- [ ] Migracja 2: leads.form_data
- [ ] Migracja 4: landing_pages.default_assignee_id + capture_fields
- [ ] Migracja 5: lead_duplicates tabela
- [ ] Migracja 6: usuń ip_address raw z lead_sources
- [ ] `CreateLeadAction` — business-scoped Client::firstOrCreate
- [ ] `CreateLeadAction` — tworzenie Contact
- [ ] `CreateLeadAction` — wypełnianie assigned_to z LP.default_assignee_id
- [ ] `CreateLeadAction` — source='landing_page', form_data
- [ ] `LeadDuplicateService` — buildFingerprint + check + logAttempt
- [ ] `LeadService::createFromLandingPage()` — orchestration method
- [ ] `AutomationEventListener` — handler LeadCaptured + LeadAssigned
- [ ] `NotifyLeadOwnerListener` — nowy listener
- [ ] `LeadCapturedNotification` — Filament Database Notification
- [ ] `LeadService::markWon()` — auto-stage + automation trigger
- [ ] `LeadService::markLost()` — auto-stage + automation trigger
- [ ] `LeadResource` — business_id scope w getEloquentQuery()
- [ ] `ClientResource` — business_id scope w getEloquentQuery()
- [ ] Wszystkie widgety — business_id scope
- [ ] `LandingPagePolicy` — stworzyć i zarejestrować
- [ ] `ClientPolicy` — stworzyć i zarejestrować

### Sprint 2 — Filament UX

- [ ] `LandingPageResource` — nowy Filament Resource
- [ ] `LeadInboxWidget` — nowy widget
- [ ] `LeadResource` — filtr LP, kolumny form_data+attribution, Assign to me action
- [ ] `LeadsBySourceWidget` — przełącz na lead_sources.type
- [ ] `AdminSeeder` — nowe uprawnienia assign_leads, view_lead_inbox
- [ ] `LeadChecklistItemService::generateForLead()` — auto-checklist
- [ ] `LeadAssignedNotification`, `LeadStaleNotification` — klasy notyfikacji
- [ ] `ProcessLeadNotificationJob` — async email notifications

### Sprint 2 — Frontend

- [ ] `useLeadCapture` hook — dodać budget range, form_data, rate limit countdown
- [ ] `LeadCaptureForm` — renderowanie dynamicznych capture_fields
- [ ] `SuccessMessage`, `DuplicateMessage`, `RateLimitedMessage` — komponenty
- [ ] `PublicLandingPage.jsx` — obsługa UTM tracking, Meta tags z Inertia Head
- [ ] lang/pl/leads.php, lang/en/leads.php, lang/pt/leads.php — komunikaty

### Sprint 3 — AI + Advanced (v1.1)

- [ ] Migracja 3: leads.ai_score, ai_score_reason, score_calculated_at
- [ ] `LeadScoringJob` — OpenAI integration
- [ ] `LeadScoringListener` — dispatch po LeadCaptured
- [ ] `ai_score` badge w LeadResource
- [ ] `landing_pages.default_stage_id` — UI konfiguracji w LandingPageResource
- [ ] `StaleLeadsJob` — scheduled + LeadStaleNotification
- [ ] `LeadWonNotification` — klasa notyfikacji
