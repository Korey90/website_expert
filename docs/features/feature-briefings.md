# Feature: Briefings

> Data: 2026-04-17
> Zaktualizowano: 2026-04-17 (decyzje projektowe potwierdzone)
> Status: Approved
> Bounded Context: CRM / Leads
> Priorytet: MUST HAVE (MVP)

**Potwierdzone decyzje projektowe:**

| # | Pytanie | Decyzja |
|---|---------|---------|
| 1 | Zapis briefingu | Autosave (Livewire debounce 1500ms) **+** ręczny przycisk „Zapisz postęp" |
| 2 | Eksport | PDF via **dompdf** (dostępny w projekcie) |
| 3 | Szablony globalne (`business_id=null`) | Widoczne i edytowalne tylko przez **superadmina** |
| 4 | Rola klienta | Admin prowadzi podczas rozmowy **LUB** klient wypełnia samodzielnie gdy portal aktywny |

---

## 1. Definicja

**Cel:** Umożliwić handlowcom przeprowadzenie ustrukturyzowanego briefingu z klientem bezpośrednio z poziomu systemu — na bazie gotowych szablonów z `docs/sales/` — i zapisanie wypełnionego briefingu jako rekordu powiązanego z Leadem.

**Użytkownicy:**
- **Superadmin** — CRUD szablonów globalnych (`business_id=null`), widzi wszystkie briefingi
- **Admin / Manager** — tworzy szablony per-business, prowadzi i przegląda briefingi swojego businessu
- **Developer** — tylko odczyt
- **Klient (portal)** — może wypełnić briefing samodzielnie przez unikalny token, jeśli portal klienta jest aktywny

**Zależności:**
- `Lead` (każdy briefing jest powiązany z leadem)
- `ServiceItem` (opcjonalne dopasowanie do usługi)
- `User` (prowadzący briefing)
- `Business` (multi-tenancy przez `business_id`)
- Szablony MD w `docs/sales/` (discovery / qualification / proposal-input)

**Słownik:**
- **BriefingTemplate** — szablon briefingu (record w bazie, bazujący na plikach MD z `docs/sales/`)
- **Briefing** — wypełniony/aktywny briefing powiązany z konkretnym Leadem

---

## 2. Stan obecny i delta

### Stan obecny
- Istnieją pliki szablonów MD w `docs/sales/`:
  - 9 usług × 3 typy (discovery / qualification / proposal-input) × 2 języki (en / pl) = **54 pliki**
- Brak modelu `Briefing` ani `BriefingTemplate` w systemie
- Brak Filament Resource dla briefingów
- `Lead` posiada `notes`, `activities`, `checklist` — ale brak struktury do prowadzenia briefingu
- W `ViewLead.php` istnieje pattern action (Email, SMS, ProposalBuilder) — do powielenia

### Delta (co budujemy)
1. Migracja + model `BriefingTemplate` — przechowuje szablony (sekcje, pytania) importowane lub wpisywane ręcznie
2. Migracja + model `Briefing` — powiązanie z Leadem, stan (draft/in_progress/completed), wypełnione odpowiedzi
3. Filament Resource `BriefingTemplateResource` — CRUD szablonów
4. Filament Resource `BriefingResource` — CRUD briefingów (lista wszystkich w systemie)
5. Akcja `ConductBriefingAction` w `ListLeads` — bulk/row action "Wykonaj briefing"
6. Akcja `ConductBriefingAction` w `ViewLead` — header action "Wykonaj briefing"
7. Widok/modal briefingu — prowadzenie briefingu sekcja po sekcji, zapis postępu, możliwość eksportu PDF

---

## 3. Model danych

### 3.1 Tabela `briefing_templates`

```
briefing_templates
├── id                     BIGINT UNSIGNED PK
├── business_id            BIGINT UNSIGNED FK → businesses (nullable; NULL = szablon globalny)
├── service_slug           VARCHAR(100) nullable  — np. 'google-ads', 'ecommerce'
├── type                   ENUM('discovery','qualification','proposal_input','sales_offer')
├── language               CHAR(2) DEFAULT 'en'   — 'en' | 'pl'
├── title                  VARCHAR(255)
├── description            TEXT nullable
├── sections               JSON                   — tablica sekcji z pytaniami
│                            [{ "title": "...", "questions": [{ "key": "...", "label": "...", "type": "text|textarea|select|bool", "required": bool }] }]
├── is_active              BOOLEAN DEFAULT true
├── sort_order             SMALLINT DEFAULT 0
├── created_at / updated_at
```

### 3.2 Tabela `briefings`

```
briefings
├── id                     BIGINT UNSIGNED PK
├── business_id            BIGINT UNSIGNED FK → businesses
├── lead_id                BIGINT UNSIGNED FK → leads
├── briefing_template_id   BIGINT UNSIGNED FK → briefing_templates (nullable)
├── conducted_by           BIGINT UNSIGNED FK → users (nullable gdy wypełnia klient samodzielnie)
├── client_token           VARCHAR(64) nullable, unique
│                            — jednorazowy token do dostępu z portalu klienta
│                            — generowany gdy admin klika „Udostępnij klientowi"
│                            — NULL gdy admin prowadzi briefing samodzielnie
├── client_submitted_at    TIMESTAMP nullable — kiedy klient zatwierdził odpowiedzi
├── title                  VARCHAR(255)       — domyślnie "{LeadTitle} — {TemplateTitle} — {date}"
├── type                   ENUM('discovery','qualification','proposal_input','sales_offer','custom')
├── language               CHAR(2) DEFAULT 'en'
├── status                 ENUM('draft','in_progress','completed','cancelled') DEFAULT 'draft'
├── answers                JSON               — odpowiedzi per sekcja per pytanie
│                            { "section_key": { "question_key": "answer_value" } }
├── autosave_at            TIMESTAMP nullable — timestamp ostatniego autosave
├── notes                  TEXT nullable      — dodatkowe notatki handlowca
├── completed_at           TIMESTAMP nullable
├── created_at / updated_at
├── deleted_at             TIMESTAMP nullable (SoftDeletes)
```

### 3.3 Relacje

```
Lead         hasMany  Briefing
Briefing     belongsTo Lead
Briefing     belongsTo BriefingTemplate (nullable)
Briefing     belongsTo User (conducted_by)
Briefing     belongsTo Business
BriefingTemplate belongsTo Business (nullable)
```

### 3.4 Seeder szablonów

`BriefingTemplateSeeder` — importuje strukturę z plików MD w `docs/sales/` do tabeli `briefing_templates`. 
Format sekcji JSON pokrywa pytania z szablonów MD. Seeder uruchamiany raz; pliki MD pozostają jako źródło prawdy do ewentualnego re-importu.

**Mapowanie pliku → rekord:**
```
template-{service_slug}-discovery-en.md
→ BriefingTemplate { service_slug, type:'discovery', language:'en', title, sections: [...] }
```

---

## 4. Backend

### 4.1 Modele

**`App\Models\BriefingTemplate`**
```php
// fillable: business_id, service_slug, type, language, title, description, sections, is_active, sort_order
// casts: sections => 'array', is_active => 'boolean'
// scopes: scopeActive(), scopeForService(string $slug), scopeForLanguage(string $lang)
// belongs to Business (nullable)
```

**`App\Models\Briefing`**
```php
// fillable: business_id, lead_id, briefing_template_id, conducted_by, title, type, language, status, answers, notes, completed_at
// casts: answers => 'array', completed_at => 'datetime'
// SoftDeletes
// relations: lead(), template(), conductedBy(), business()
// method: isComplete(): bool — sprawdza czy wszystkie required pytania mają odpowiedź
// method: getProgressPercentage(): int — % wypełnionych required pytań
```

### 4.2 Migracje

**`create_briefing_templates_table`** — tabela jak w 3.1
**`create_briefings_table`** — tabela jak w 3.2

### 4.3 Seeder

**`BriefingTemplateSeeder`**
- Parsuje pliki MD z `docs/sales/` (discovery + qualification + proposal-input)
- Wyodrębnia sekcje i pytania (nagłówki H2/H3 → sekcje, pytania z bloków → pola)
- Zapisuje jako JSON `sections` w `briefing_templates`
- Fallback: dla brakujących plików tworzy szablon z jedną sekcją "Notatki ogólne"
- `is_active = true`, `business_id = null` (szablony globalne)

### 4.4 Service

**`App\Services\BriefingService`**
```php
// createFromTemplate(Lead $lead, BriefingTemplate $template, User $conductor): Briefing
//   - Tworzy Briefing z status='draft', answers={}, autosave_at=null
//   - Loguje LeadActivity type='briefing_started'

// saveAnswers(Briefing $briefing, array $answers): void
//   - Merge answers (patch per klucz — nie nadpisuje całości)
//   - Aktualizuje status na 'in_progress' jeśli był 'draft'
//   - Ustawia autosave_at = now()
//   - Wywoływane przez autosave (debounce 1500ms) I przez ręczny przycisk

// complete(Briefing $briefing, array $answers, ?string $notes): Briefing
//   - Wywołuje saveAnswers() przed walidacją
//   - Waliduje required pytania — rzuca ValidationException przy brakach
//   - Ustawia status='completed', completed_at=now()
//   - Loguje LeadActivity type='briefing_completed'

// cancel(Briefing $briefing): void
//   - status='cancelled'
//   - Loguje LeadActivity type='briefing_cancelled'

// shareWithClient(Briefing $briefing): string
//   - Wymaga: portal klienta aktywny (Setting::get('client_portal_enabled'))
//   - Generuje unikalny client_token (64-znakowy hex)
//   - Zapisuje token w briefingu
//   - Loguje LeadActivity type='briefing_shared_with_client'
//   - Zwraca URL: /client/briefings/{token}

// submitByClient(Briefing $briefing, array $answers): Briefing
//   - Merge answers, ustawia client_submitted_at=now()
//   - Status → 'in_progress' (admin musi jeszcze zatwierdzić → 'completed')
//   - Loguje LeadActivity type='briefing_submitted_by_client'
```

### 4.5 Actions (Filament)

**`App\Filament\Actions\ConductBriefingAction`** — reusable Filament Action
```php
// Może być użyta zarówno w tabeli (ListLeads) jak i w nagłówku (ViewLead)
// Krok 1: Wybór szablonu (service_slug + type + language) — Select z opcjami z BriefingTemplate::active()
// Krok 2: Otwiera modal/slide-over z formularzem briefingu
// submit: BriefingService::createFromTemplate() + redirect do widoku briefingu
// Wymaga: $lead jako record
```

### 4.6 Policies

**`App\Policies\BriefingPolicy`**
```php
// viewAny: admin, manager
// view: właściciel (conducted_by) lub admin lub superadmin
// create: admin, manager
// update: admin, manager (tylko status != completed/cancelled)
// delete: admin, superadmin
// shareWithClient: admin, manager (tylko gdy portal klienta aktywny)
// Brak dostępu developer do tworzenia/edycji
// Klient — dostęp wyłącznie przez client_token (guard osobny, bez standardowej Policy)
```

**`App\Policies\BriefingTemplatePolicy`**
```php
// viewAny: admin, manager, superadmin
// view: admin/manager (tylko własny business), superadmin (globalne + wszystkie)
// create: admin/manager (per-business); superadmin (może tworzyć globalne business_id=null)
// update: właściciel lub superadmin
// delete: właściciel lub superadmin
// WAŻNE: szablony globalne (business_id=null) może edytować wyłącznie superadmin
```

### 4.7 LeadActivity types (addytywne)

Dodać do istniejącego systemu aktywności:
- `briefing_started`
- `briefing_completed`
- `briefing_cancelled`
- `briefing_shared_with_client`
- `briefing_submitted_by_client`

### 4.8 Routes (portal klienta)

```php
// routes/web.php — bez auth guard; weryfikacja przez middleware client_token
Route::middleware(['client.portal.active'])->group(function () {
    Route::get('/client/briefings/{token}',   [ClientBriefingController::class, 'show']);
    Route::post('/client/briefings/{token}',  [ClientBriefingController::class, 'submit']);
    Route::patch('/client/briefings/{token}/autosave', [ClientBriefingController::class, 'autosave']);
});
```

**`App\Http\Controllers\ClientBriefingController`**
```php
// show:     weryfikuje token, sprawdza status != completed/cancelled
//           Renderuje Inertia page 'Briefing/ClientFill'
// submit:   waliduje token, wywołuje BriefingService::submitByClient() → strona sukcesu
// autosave: waliduje token, wywołuje BriefingService::saveAnswers() → JSON 200
// Middleware client.portal.active: sprawdza Setting::get('client_portal_enabled') → 503 gdy off
```

---

## 5. Frontend (Filament)

### 5.1 `BriefingTemplateResource`

**Plik:** `app/Filament/Resources/BriefingTemplateResource.php`
**Strony:** ListBriefingTemplates, CreateBriefingTemplate, EditBriefingTemplate, ViewBriefingTemplate
**Navigation:** Group: "Sales", Icon: `heroicon-o-document-text`, Sort: 3

**Formularz:**
```
TextInput: title (required)
Select: service_slug (opcje z ServiceItem::pluck('slug','slug'))
Select: type (discovery | qualification | proposal_input | sales_offer | custom)
Select: language (en | pl)
Textarea: description
Toggle: is_active
Repeater: sections
  └── TextInput: title (sekcja)
  └── Repeater: questions
        ├── TextInput: key (snake_case, required)
        ├── TextInput: label (required)
        ├── Select: type (text | textarea | select | boolean | rating)
        ├── TextInput: placeholder (opcjonalne)
        └── Toggle: required
```

**Tabela (lista):**
```
TextColumn: title | service_slug | type (badge) | language (badge) | is_active (toggle)
Actions: View, Edit, Delete
Filters: type, service_slug, language, is_active
```

### 5.2 `BriefingResource`

**Plik:** `app/Filament/Resources/BriefingResource.php`
**Strony:** ListBriefings, ViewBriefing
**Navigation:** Group: "CRM" (obok LeadResource), Icon: `heroicon-o-clipboard-document-list`, Sort: 3

**Tabela (lista):**
```
TextColumn: title | lead.title (link) | conductedBy.name | type (badge) | status (badge) | completed_at
Badge colors: draft=gray, in_progress=warning, completed=success, cancelled=danger
Actions: View, Delete (soft)
Filters: status, type, language, conducted_by
DefaultSort: created_at DESC
```

**ViewBriefing — strona szczegółów:**
```
Sekcja górna: metadane (lead, szablon, prowadzący, status, created_at)
Progress bar: getProgressPercentage()
Sekcje briefingu: każda sekcja jako akordeon
  └── Pytanie → odpowiedź (lub placeholder "Brak odpowiedzi")
Notatki handlowca
Header actions:
  - "Kontynuuj briefing" (jeśli status != completed/cancelled) → modal briefingu
  - "Eksport PDF" → BriefingExportAction
  - "Oznacz jako ukończony" (jeśli in_progress)
  - DeleteAction
```

### 5.3 Integracja w `ListLeads`

**Plik:** `app/Filament/Resources/LeadResource.php` — metoda `table()`

Dodać do sekcji `->actions([...])`:
```php
Action::make('conduct_briefing')
    ->label('Wykonaj briefing')
    ->icon('heroicon-o-clipboard-document-check')
    ->color('primary')
    ->modalHeading('Wybierz szablon briefingu')
    ->form([
        Select::make('briefing_template_id')
            ->label('Szablon')
            ->options(fn () => BriefingTemplate::active()->get()->mapWithKeys(...))
            ->searchable()
            ->required(),
    ])
    ->action(fn (Lead $record, array $data, ConductBriefingAction $action) => $action->execute($record, $data))
    ->successRedirectUrl(fn (Briefing $briefing) => BriefingResource::getUrl('view', ['record' => $briefing]))
```

### 5.4 Integracja w `ViewLead`

**Plik:** `app/Filament/Resources/LeadResource/Pages/ViewLead.php` — metoda `getHeaderActions()`

Dodać jako pierwszy element listy:
```php
Action::make('conduct_briefing')
    ->label('Wykonaj briefing')
    ->icon('heroicon-o-clipboard-document-check')
    ->color('primary')
    ->modalHeading('Nowy briefing')
    ->form([
        Select::make('briefing_template_id')
            ->label('Szablon briefingu')
            ->options(fn () => BriefingTemplate::active()->forBusiness()->get()->mapWithKeys(...))
            ->searchable()
            ->required(),
    ])
    ->action(fn (array $data) => redirect(BriefingResource::getUrl('view', [
        'record' => app(BriefingService::class)->createFromTemplate(
            $this->record,
            BriefingTemplate::findOrFail($data['briefing_template_id']),
            auth()->user()
        )
    ])))
```

### 5.5 Prowadzenie briefingu — ViewBriefing (tryb edycji)

Prowadzenie briefingu na dedykowanej stronie `ViewBriefing` (nie inline modal):

**Autosave:**
```
- Livewire wire:model.live.debounce.1500ms na każdym polu formularza
- Po każdej zmianie wywołuje saveAnswers() w tle (AJAX)
- Wizualny wskaźnik stanu: „Zapisuję..." → „Zapisano [HH:MM]" (na podstawie autosave_at)
- Przycisk „Zapisz postęp" — jawny zapis na żądanie (bez oczekiwania na debounce)
- Oba mechanizmy wywołują tę samą metodę BriefingService::saveAnswers()
```

**Formularz (każda sekcja jako Filament Section, akordeon):**
```
Każde pytanie renderowane dynamicznie na podstawie type:
  - type=text     → TextInput (wire:model.live.debounce.1500ms)
  - type=textarea → Textarea rows=4 (wire:model.live.debounce.1500ms)
  - type=select   → Select z opcjami z definicji pytania
  - type=boolean  → Toggle
  - type=rating   → Select (1–5)
```

**Header actions w trybie edycji:**
```
- „Zapisz postęp" (primary) → saveAnswers() jawny
- „Udostępnij klientowi" (secondary) → shareWithClient() → kopiuj URL
  - visible: tylko gdy Setting::get('client_portal_enabled') = true
  - Pokazuje URL do skopiowania lub opcję „Wyślij emailem"
- „Ukończ briefing" (success) → complete() — waliduje required, status=completed
- „Anuluj" (danger, z potwierdzeniem) → cancel()
```

### 5.6 Frontend portalu klienta — `Briefing/ClientFill`

**Plik:** `resources/js/Pages/Briefing/ClientFill.tsx`

```
Layout: publiczny (bez sidebaru Filament, bez auth)
Komponenty:
  - Nagłówek z logo Website Expert i tytułem briefingu
  - Sekcje z pytaniami — ta sama logika renderowania (type → komponent React)
  - Autosave: PATCH /client/briefings/{token}/autosave po debounce 1500ms
  - Wskaźnik zapisu (identyczny z widokiem admina)
  - Przycisk „Zapisz i wróć później" → autosave
  - Przycisk „Zatwierdź i wyślij" → POST → strona potwierdzenia
Język: wynika z briefing.language (en/pl)
Po zatwierdzeniu: strona sukcesu „Dziękujemy — Twoje odpowiedzi zostały zapisane"
```

---

## 6. Workflow

### 6.1 Happy path A — admin prowadzi briefing podczas rozmowy z klientem

```
1. Admin otwiera /admin/leads/ lub /admin/leads/{id}
2. Klika „Wykonaj briefing" → modal wyboru szablonu
3. System tworzy Briefing { status:'draft', answers:{} } → redirect do /admin/briefings/{id}
4. Admin widzi formularz z sekcjami — Livewire autosave aktywny (debounce 1500ms)
5. Wpisuje odpowiedzi podczas rozmowy — każda zmiana autosave'owana w tle
6. Może kliknąć „Zapisz postęp" dla pewności
7. Po zakończeniu klika „Ukończ briefing"
8. System waliduje required pytania → status='completed', completed_at=now()
9. LeadActivity 'briefing_completed' zalogowana
10. Admin eksportuje briefing do PDF (dompdf)
```

### 6.2 Happy path B — klient wypełnia briefing samodzielnie przez portal

```
1. Admin tworzy Briefing (status='draft')
2. Klika „Udostępnij klientowi" → system generuje client_token
   → LeadActivity 'briefing_shared_with_client' zalogowana
3. Admin kopiuje URL /client/briefings/{token} i wysyła klientowi
4. Klient otwiera URL → widzi formularz w publicznym layoucie (język z briefing.language)
5. Klient wypełnia odpowiedzi — autosave PATCH /client/briefings/{token}/autosave
6. Klient klika „Zatwierdź i wyślij"
   → client_submitted_at=now(), status='in_progress'
   → LeadActivity 'briefing_submitted_by_client' zalogowana
7. Admin widzi powiadomienie / aktywność na Leadzie
8. Admin przegląda odpowiedzi w /admin/briefings/{id}, uzupełnia notatki
9. Admin klika „Ukończ briefing" → status='completed'
10. Admin eksportuje PDF
```

### 6.3 Edge cases

| Sytuacja | Zachowanie |
|----------|------------|
| Brak szablonów w systemie | Modal informuje o braku szablonów; link do BriefingTemplateResource |
| Lead bez klienta | Briefing można stworzyć; client_id=null na lead jest możliwe |
| Wielokrotne briefingi tego samego leada | Dozwolone — każdy ma osobny rekord; lista widoczna w ViewLead |
| Admin wychodzi podczas wypełniania | Status 'draft'/'in_progress', autosave zapisał stan; kontynuacja w dowolnym momencie |
| Autosave i ręczny zapis jednocześnie | Debounce resetowany przy jawnym zapisie; nie ma duplikacji requestów |
| Próba edycji completed briefingu | Formularz readonly; akcje „Udostępnij", „Kontynuuj" ukryte; tylko eksport PDF |
| Szablon usunięty po stworzeniu briefingu | briefing_template_id nullable FK; briefing wyświetla answers bez struktury szablonu |
| Szablony globalne (business_id=null) | Widoczne dla wszystkich jako read-only; edytowalne tylko przez superadmina |
| Klient otwiera token po ukończeniu briefingu | 403 z komunikatem „Ten briefing został już zakończony" |
| Portal klienta wyłączony | Przycisk „Udostępnij klientowi" ukryty; GET /client/briefings/{token} → 503 |
| Klient odświeża stronę w trakcie | Autosave zapisał stan; formularz odtwarza answers z props Inertia przy reloadzie |
| business_id mismatch | BriefingTemplate scopeForBusiness() filtruje po currentBusiness() — wzorzec z LeadResource |

---

## 7. Test plan

### 7.1 Unit
- `BriefingService::createFromTemplate()` — tworzy Briefing z poprawnym statusem i answers={}
- `BriefingService::saveAnswers()` — merge nie nadpisuje istniejących odpowiedzi przy partial update
- `BriefingService::complete()` — rzuca wyjątek gdy required pytania bez odpowiedzi
- `Briefing::getProgressPercentage()` — poprawnie liczy % dla 0/3 → 5/3 (>100% niemożliwe) pytań wymaganych
- `BriefingPolicy` — manager może tworzyć, developer nie może

### 7.2 Feature — admin
- POST `/admin/briefings` → tworzy Briefing, redirect do ViewBriefing — ✓
- Akcja „Wykonaj briefing" w ListLeads → modal, submit → redirect do briefingu — ✓
- Akcja „Wykonaj briefing" w ViewLead → modal, submit → redirect do briefingu — ✓
- Autosave wysyła PATCH po debounce 1500ms, aktualizuje autosave_at — ✓
- Ręczny przycisk „Zapisz postęp" wywołuje tę samą metodę co autosave — ✓
- „Ukończ briefing" waliduje required, ustawia status=completed — ✓
- Eksport PDF zwraca poprawny `.pdf` z sekcjami i odpowiedziami — ✓
- Szablony globalne (business_id=null) widoczne tylko superadminowi — ✓
- Filtrowanie po `business_id` — briefingi innego business niewidoczne — ✓
- `BriefingTemplateSeeder` — tworzy 54 rekordy globalne — ✓

### 7.3 Feature — portal klienta
- GET `/client/briefings/{token}` → 200 gdy token aktywny, portal włączony — ✓
- GET `/client/briefings/{token}` → 403 gdy briefing completed — ✓
- GET `/client/briefings/{token}` → 503 gdy portal klienta wyłączony — ✓
- PATCH `/client/briefings/{token}/autosave` → 200 JSON, nie zmienia statusu — ✓
- POST `/client/briefings/{token}` → client_submitted_at=now(), status=in_progress, activity logged — ✓
- Po submit klient widzi stronę potwierdzenia — ✓

---

## 8. Checklist implementacji

### Faza 1 — Model i migracje
- [ ] Migracja `create_briefing_templates_table`
- [ ] Migracja `create_briefings_table`
- [ ] Model `BriefingTemplate` (fillable, casts, scopes, relations)
- [ ] Model `Briefing` (fillable, casts, SoftDeletes, relations, helper methods)
- [ ] Dodać relację `Lead::briefings()` → hasMany(Briefing::class)

### Faza 2 — Seeder szablonów
- [ ] `BriefingTemplateSeeder` — parsowanie plików MD lub ręczne sekcje dla 9 usług × 3 typy × 2 języki
- [ ] Zarejestrować w `DatabaseSeeder` (warunkowo, jeśli tabela pusta)

### Faza 3 — Service i Policy
- [ ] `BriefingService` (createFromTemplate, saveAnswers, complete, cancel)
- [ ] `BriefingPolicy` + rejestracja w `AuthServiceProvider`
- [ ] Dodać typy aktywności do systemu `LeadActivity`

### Faza 4 — Filament Resources
- [ ] `BriefingTemplateResource` (CRUD — formularz z Repeater dla sections/questions)
- [ ] `BriefingResource` (lista + widok + tryb edycji/prowadzenia briefingu)
- [ ] Zarejestrować oba resources w panelu

### Faza 5 — Integracja z Leadami
- [ ] Akcja "Wykonaj briefing" w `LeadResource::table()` (table row action)
- [ ] Akcja "Wykonaj briefing" w `ViewLead::getHeaderActions()`
- [ ] Relacja `briefings` widoczna w `ViewLead` (sekcja / zakładka z listą briefingów leada)

### Faza 6 — Eksport PDF
- [ ] `BriefingExportAction` — generuje PDF via **dompdf**
- [ ] Blade template `resources/views/pdf/briefing.blade.php` — sekcje + pytania + odpowiedzi + metadata
- [ ] Dostępna z `ViewBriefing` header action (disabled gdy status=draft)

### Faza 7 — Portal klienta (wymaga aktywnego portalu klienta)
- [ ] Middleware `client.portal.active` — sprawdza `Setting::get('client_portal_enabled')`
- [ ] Route `/client/briefings/{token}` (GET, POST, PATCH) w `routes/web.php`
- [ ] `ClientBriefingController` (show, submit, autosave)
- [ ] Inertia page `resources/js/Pages/Briefing/ClientFill.tsx` (publiczny layout)
- [ ] Przycisk „Udostępnij klientowi" w `ViewBriefing` (visible tylko gdy portal aktywny)
- [ ] `BriefingService::shareWithClient()` — generuje token, loguje aktywność
- [ ] `BriefingService::submitByClient()` — odpowiedzi klienta + client_submitted_at

### Faza 8 — Testy
- [ ] Unit: BriefingService (createFromTemplate, saveAnswers, complete, shareWithClient, submitByClient)
- [ ] Unit: BriefingPolicy, BriefingTemplatePolicy, Briefing helper methods
- [ ] Feature: CRUD briefingów + akcje w Leads
- [ ] Feature: portal klienta (token valid/invalid, autosave, submit, portal disabled)

---

## 9. Decyzje projektowe (zamknięte)

| # | Pytanie | Decyzja | Data |
|---|---------|----------|------|
| 1 | Zapis briefingu | **Autosave** (Livewire debounce 1500ms) + **ręczny przycisk** „Zapisz postęp" | 2026-04-17 |
| 2 | Eksport | **dompdf** — Blade template dla briefingu | 2026-04-17 |
| 3 | Szablony globalne | Widoczne i edytowalne tylko przez **superadmina** | 2026-04-17 |
| 4 | Rola klienta | Admin prowadzi podczas rozmowy **LUB** klient wypełnia samodzielnie gdy portal aktywny | 2026-04-17 |
