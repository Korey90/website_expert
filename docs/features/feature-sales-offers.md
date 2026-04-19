# Feature: Sales Offers (Oferty Sprzedażowe)
> Data: 2026-04-19  
> Status: Approved

## Zatwierdzone decyzje

| # | Decyzja |
|---|---------|
| 1 | Akcja **"Wyślij"** tworzy ofertę I wysyła od razu. Akcja **"Zapisz draft"** tworzy tylko draft bez wysyłania. |
| 2 | ClientView ma CTA jako **zwykły przycisk** — zachowanie zostanie zdefiniowane później. |
| 3 | Wysłanie oferty NIE przesuwa etapu leada automatycznie. |
| 4 | SalesOffer NIE jest widoczna w portalu klienta — tylko publiczny link. |
| 5 | Seeder importuje body z plików `docs/sales/` automatycznie. |
| 6 | Edytor treści: **MarkdownEditor** (Filament built-in). |

---

## 0. Decyzja architektoniczna — nowy moduł vs rozbudowa Quotes

### Czym jest Quote (istniejący moduł)
| Atrybut | Quote |
|---------|-------|
| Cel | Dokument finansowy — wycena |
| Treść | Line items: opis, ilość, cena, rabat, VAT, total |
| Workflow portalu | Klient akceptuje / odrzuca → zmiana statusu leada |
| Generacja | Ręcznie z poziomu ViewLead (modal) lub QuoteResource |
| Format | Tabelaryczny PDF, numer dokumentu |
| Model | `Quote` + `QuoteItem`, `QuoteSentMail`, `portal.quotes.*` |

### Czym jest Sales Offer (nowy moduł)
| Atrybut | SalesOffer |
|---------|-----------|
| Cel | Narracyjny dokument sprzedażowy — persuasion-first |
| Treść | Sekcje: idealny klient, problem, rezultaty, zakres, proces, harmonogram, anchor cenowy |
| Workflow portalu | Klient odczytuje ofertę (read-only), opcjonalnie: CTA "Chcę to omówić" |
| Generacja | Z predefiniowanego szablonu per usługa + język, edytowalna kopia |
| Format | Rich-text/markdown → HTML e-mail + publiczny link |
| Model | `SalesOffer` + `SalesOfferTemplate` — **nowe tabele** |

### Dlaczego NOWY moduł (nie rozbudowa Quote)
1. **Różny model danych** — Quote ma `QuoteItem`, obliczenia VAT, numerację; SalesOffer ma `body` (longtext) z narracyjną treścią.
2. **Różny UX portalu** — Quote: accept/reject workflow; SalesOffer: view-only z opcjonalnym CTA.
3. **Różna email template** — QuoteSentMail wysyła numer i sumę; SalesOfferMail wysyła formatowaną prezentację usługi.
4. **Różna nawigacja** — Quote jest w grupie "Finance"; SalesOffer idzie do grupy "Sales" (już tam jest BriefingTemplateResource).
5. **Brak wspólnej logiki biznesowej** — nie ma sensu dzielić serwisów ani Policy.

### Potencjalne konflikty

| Konflikt | Ocena | Rozwiązanie |
|----------|-------|-------------|
| `BriefingTemplate.type = 'sales_offer'` | Niski — różne byty | Dodać komentarz w kodzie. Briefing type `sales_offer` = kwestionariusz do zebrania danych; `SalesOffer` = gotowy dokument wysyłany klientowi. Nie zmieniać nazw. |
| `dispatchProposalEmail()` w ViewLead — obie akcje mogą przesunąć etap na "Proposal Sent" | Średni | SalesOffer send NIE przesuwa etapu automatycznie. Admin decyduje. |
| Trasa `/offers/{token}` | Brak — trasa nieużywana | Nowa trasa nie koliduje z niczym. |
| `EmailTemplate` model | Brak | SalesOfferMail nie korzysta z EmailTemplate; wysyła body bezpośrednio. |
| Seeder — 18 plików z docs/sales/ | Brak | Dedykowany `SalesOfferTemplateSeeder`, guard sprawdza czy globalne już istnieją. |

---

## 1. Definicja

**Cel:** Umożliwić adminowi tworzenie, personalizowanie i wysyłanie bogatych, narracyjnych ofert sprzedażowych (per usługa, per język) bezpośrednio z poziomu konkretnego leada. Oferta ma wygląd profesjonalnej prezentacji — nie tabelki z kwotami.

**Bounded context:** Sales — obok BriefingTemplate.  
**Priorytet:** Wysoki — blokuje konwersję leadów.

**Główne role:**
- `admin` / `manager` — tworzy, edytuje, wysyła oferty
- `super_admin` — zarządza szablonami globalnymi (business_id = null)
- Klient — czyta ofertę przez publiczny link (bez logowania)

**Zależności:**
- `Lead`, `Client` — oferta jest zawsze powiązana z leadem
- `LeadActivity::log()` — logowanie wysłania, odczytu
- `BriefingTemplate.type = 'sales_offer'` — poprzedni krok w pipeline (briefing zbiera dane → oferta jest wysyłana)
- `Quote` — następny krok (po akceptacji oferty, admin tworzy Quote)
- `ServiceItem` — slug usługi do filtrowania szablonów

---

## 2. Stan obecny i delta

### Istniejące elementy (BEZ ZMIAN)
- `Quote`, `QuoteItem`, `QuoteResource`, `QuoteSentMail`, `portal.quotes.*`
- `BriefingTemplate`, `Briefing`, `BriefingService`
- `LeadResource`, `ViewLead` — tylko dodajemy akcje
- `EmailTemplate` model — nie rozszerzamy

### Nowe elementy (delta)

| Element | Typ | Status |
|---------|-----|--------|
| `sales_offer_templates` | Migracja | nowe |
| `sales_offers` | Migracja | nowe |
| `SalesOfferTemplate` | Model | nowe |
| `SalesOffer` | Model | nowe |
| `Lead::salesOffers()` | Relacja | nowe |
| `SalesOfferService` | Serwis | nowe |
| `SalesOfferPolicy` | Policy | nowe |
| `SalesOfferTemplatePolicy` | Policy | nowe |
| `SalesOfferMail` | Mail | nowe |
| `SalesOfferTemplateResource` | Filament Resource | nowe |
| `SalesOfferResource` | Filament Resource | nowe |
| `ViewSalesOffer` (Page) | Filament Page + Blade | nowe |
| Akcja "Wyślij ofertę" w `LeadResource::table()` | Action | nowe |
| Akcja "Wyślij ofertę" w `ViewLead::getHeaderActions()` | Action | nowe |
| `SalesOfferTemplateSeeder` | Seeder | nowe — 18 szablonów |
| `GET /offers/{token}` | Route | nowe |
| `PATCH /offers/{token}/view` | Route | nowe |
| `ClientSalesOfferController` | Controller | nowe |
| `resources/js/Pages/SalesOffer/ClientView.jsx` | React Page | nowe |
| `resources/views/pdf/sales-offer.blade.php` | Blade (dompdf) | nowe |
| `resources/views/emails/sales-offer.blade.php` | Email Blade | nowe |

---

## 3. Model danych

### `sales_offer_templates`

```sql
id           bigint PK autoincrement
business_id  char(26) nullable, FK businesses (nullOnDelete) -- null = globalny
service_slug varchar(100) nullable, index
language     char(2) default 'en'
title        varchar(255)
description  text nullable          -- wewnętrzny opis szablonu
body         longtext               -- markdown/HTML treść oferty
is_active    boolean default true
sort_order   smallint unsigned default 0
created_at, updated_at
```

Indeksy: `(service_slug, language, is_active)`, `(business_id)`

### `sales_offers`

```sql
id           bigint PK autoincrement
business_id  char(26), FK businesses (cascadeOnDelete), index
lead_id      bigint FK leads (cascadeOnDelete)
template_id  bigint nullable FK sales_offer_templates (nullOnDelete)
created_by   bigint nullable FK users (nullOnDelete)
client_token varchar(64) unique nullable    -- publiczny link bez auth
title        varchar(255)
language     char(2) default 'en'
body         longtext                       -- edytowalna kopia z szablonu
status       enum('draft','sent','viewed','converted') default 'draft'
sent_at      timestamp nullable
viewed_at    timestamp nullable
notes        text nullable                  -- wewnętrzne notatki admina
created_at, updated_at
deleted_at   timestamp nullable (SoftDeletes)
```

Indeksy: `(lead_id, status)`, `(business_id, status)`

### Relacje

```
Lead         hasMany SalesOffer
SalesOffer   belongsTo Lead
SalesOffer   belongsTo SalesOfferTemplate (nullable)
SalesOffer   belongsTo User (created_by)
SalesOffer   belongsTo Business
SalesOfferTemplate belongsTo Business (nullable)
```

### Scope `SalesOfferTemplate::forBusiness()`
Zwraca szablony danego biznesu PLUS globalne (business_id = null) — analogicznie do `BriefingTemplate`.

---

## 4. Backend

### Migracje

**`2026_04_19_000001_create_sales_offer_templates_table.php`**  
**`2026_04_19_000002_create_sales_offers_table.php`**

Wzorzec: `char('business_id', 26)->nullable()` + manualne FK (nie `foreignId()`).

### SalesOfferTemplate (Model)
- `fillable`: business_id, service_slug, language, title, description, body, is_active, sort_order
- `casts`: is_active → boolean
- Scopes: `scopeActive()`, `scopeForBusiness()`, `scopeForService(string $slug)`, `scopeForLanguage(string $lang)`
- Helper: `isGlobal(): bool`

### SalesOffer (Model)
- `fillable`: business_id, lead_id, template_id, created_by, client_token, title, language, body, status, sent_at, viewed_at, notes
- `casts`: sent_at/viewed_at → datetime
- SoftDeletes
- Helper: `isSent(): bool`, `isEditable(): bool` (tylko draft)

### SalesOfferService

```php
createFromTemplate(Lead $lead, SalesOfferTemplate $tpl, User $creator): SalesOffer
// Kopiuje body z szablonu, interpoluje {{client_name}}, {{company_name}}, {{lead_title}}
// Status: draft, loguje 'offer_created'

send(SalesOffer $offer): void
// Generuje client_token (Str::random(64))
// Wysyła SalesOfferMail do client->primary_contact_email
// Ustawia status='sent', sent_at=now()
// Loguje 'offer_sent' w LeadActivity

markViewed(SalesOffer $offer): void
// Ustawia viewed_at=now(), status='viewed' (jeśli sent)
// Loguje 'offer_viewed'

convertToQuote(SalesOffer $offer): void
// Ustawia status='converted'
// Loguje 'offer_converted'
```

### SalesOfferMail

```php
// envelope: "Oferta: {offer->title} from {business->name}"
// content: view('emails.sales-offer') — ładna prezentacja HTML z CTA
// attach: opcjonalnie PDF (dompdf)
```

### ClientSalesOfferController

```php
show(string $token)  // GET /offers/{token}
// Pobiera SalesOffer po client_token
// Wywołuje SalesOfferService::markViewed()
// Inertia::render('SalesOffer/ClientView', [...])

// Brak submit — oferta jest read-only
// Opcjonalnie: formularz "Chcę to omówić" → email do admina
```

### Policy

**SalesOfferPolicy:**
- `viewAny`: admin, manager, super_admin
- `create`: admin, manager, super_admin
- `update`: admin, manager (tylko gdy `isEditable()`)
- `delete`: admin, super_admin
- `send`: admin, manager

**SalesOfferTemplatePolicy:**
- `create`: admin/manager (business-scoped); super_admin (może global)
- `update`: owner business OR super_admin; global = tylko super_admin
- `delete`: owner business OR super_admin

### Rejestracja w AppServiceProvider
```php
Gate::policy(SalesOffer::class, SalesOfferPolicy::class);
Gate::policy(SalesOfferTemplate::class, SalesOfferTemplatePolicy::class);
```

### LeadActivity — nowe typy
| Type | Ikona | Kolor |
|------|-------|-------|
| `offer_created` | heroicon-m-document-plus | blue |
| `offer_sent` | heroicon-m-paper-airplane | sky |
| `offer_viewed` | heroicon-m-eye | indigo |
| `offer_converted` | heroicon-m-arrow-path | green |

### Trasy
```php
// routes/web.php — publiczne, bez auth
Route::prefix('offers')->name('offers.')->group(function () {
    Route::get('/{token}',      [ClientSalesOfferController::class, 'show'])->name('show');
    Route::patch('/{token}/view',[ClientSalesOfferController::class, 'markViewed'])->name('view');
});
```

### Seeder — SalesOfferTemplateSeeder
- 18 globalnych szablonów (9 usług × pl + en)
- Pobiera treść z plików `docs/sales/template-{service}-sales-offer-{lang}.md`
- Guard: `whereNull('business_id')->exists()` → skip jeśli już istnieją

---

## 5. Frontend (Filament)

### SalesOfferTemplateResource
- Grupa: "Sales", ikona: `heroicon-o-document-text`, sort: 4
- Form: TextInput(title), Select(service_slug), Select(language), Toggle(is_active), Textarea(description), **MarkdownEditor(body)** — pełen edytor markdown
- Table: title, service_slug badge, language badge, is_active, business (Global/nazwa)
- Pages: List, Create, Edit, View
- `getEloquentQuery()`: non-superadmin widzi tylko `forBusiness()`

### SalesOfferResource
- Grupa: "CRM", ikona: `heroicon-o-paper-airplane`, sort: 4
- Table: title, lead.title (link), status badge, sent_at, viewed_at
- Pages: List, View (bez Create — tworzone z Lead)
- `ViewSalesOffer` Page:
  - Header actions: **Wyślij** (jeśli draft), **Export PDF**, **Oznacz jako skonwertowaną** (jeśli sent/viewed)
  - Widok body: MarkdownEditor z autosave (debounce 1500ms) gdy status=draft
  - Read-only preview gdy status≠draft

### Akcja "Wyślij ofertę" w LeadResource::table()
```php
Action::make('send_sales_offer')
    ->label('Wyślij ofertę')
    ->icon('heroicon-o-paper-airplane')
    ->color('info')
    ->modalHeading('Wyślij ofertę sprzedażową')
    ->form([
        Select::make('template_id') // filtrowane per service_slug + language
        Select::make('language')
    ])
    ->action(fn(Lead $record, array $data) => /* SalesOfferService::createFromTemplate() + send() */)
```

### Akcja "Wyślij ofertę" w ViewLead::getHeaderActions()
- Identyczna logika, modal z wyborem szablonu
- Po wysłaniu: Notification + redirect na ViewSalesOffer

### React Page: SalesOffer/ClientView.jsx
- Publiczna strona bez layoutu portalu
- Header: logo business + tytuł oferty
- Body: sformatowany HTML/markdown (DOMPurify sanitize)
- Sekcje wyróżnione wizualnie
- CTA: "Chcę to omówić — skontaktuj się z nami" → mailto lub link

### Email blade: emails/sales-offer.blade.php
- Responsywny HTML email
- Nagłówek: logo + "Oferta dla: {company_name}"
- Body: sformatowana treść oferty
- CTA button: "Otwórz pełną ofertę" → link `/offers/{token}`

---

## 6. Workflow

### Happy path — wysłanie oferty z leada
```
Admin → /admin/leads → klik "Wyślij ofertę" (row action)
  → Modal: wybierz szablon (filtrowany po service_slug leada + language)
  → Klik "Wyślij"
  → SalesOfferService::createFromTemplate() → body interpolated
  → SalesOfferService::send() → client_token, SalesOfferMail queued
  → LeadActivity: offer_created + offer_sent
  → Notification: "Oferta wysłana na email@client.com"
  → Redirect → ViewSalesOffer (aby admin widział co zostało wysłane)

Klient → odbiera email → klik CTA
  → GET /offers/{abc123...}
  → ClientSalesOfferController::show() → markViewed()
  → LeadActivity: offer_viewed
  → Inertia: SalesOffer/ClientView.jsx
  → Klient czyta ofertę
  → Opcjonalny CTA: mailto do admina

Admin → widzi offer_viewed w LeadActivity
  → Tworzy Quote (finansową wycenę) — osobny krok
  → SalesOfferService::convertToQuote() → status='converted'
```

### Edge cases

| Przypadek | Zachowanie |
|-----------|-----------|
| Klient nie ma emaila | Notification::danger, oferta zapisana jako draft |
| Szablon nie istnieje dla danej usługi | Dropdown pusty, admin może wybrać dowolny szablon |
| Token nieprawidłowy lub oferta deleted | abort(404) |
| Oferta już wysłana — ponowny send | Blocked (isEditable() = false); admin może "Reset to draft" |
| Brak szablonu — blank offer | Dozwolone — admin wpisuje treść ręcznie |
| Lead bez client_id | Ostrzeżenie, można wysłać na dowolny email (custom input) |

---

## 7. Test plan

### Backend Unit
- `SalesOfferService::createFromTemplate()` interpoluje zmienne w body
- `SalesOfferService::send()` generuje unikalny token, ustawia status
- `SalesOfferService::markViewed()` ustawia viewed_at, nie nadpisuje jeśli już ustawione
- `SalesOffer::isEditable()` zwraca true tylko dla draft
- `SalesOfferTemplate::forBusiness()` zwraca globalne + własne

### Backend Feature
- POST tworzy offer → email w kolejce
- GET `/offers/{token}` z prawidłowym tokenem → 200, viewed_at ustawione
- GET `/offers/invalid-token}` → 404
- GET `/offers/{token}` ponownie → viewed_at nie zmienia się
- Policy: manager może send, developer nie może
- Policy: global template edytowalny tylko przez superadmin

### Frontend Smoke
- SalesOfferTemplateResource CRUD działa (List/Create/Edit z MarkdownEditor)
- SalesOfferResource List wyświetla rekordy
- ViewSalesOffer autosave nie wysyła requestu gdy status != draft
- ClientView.jsx renderuje się bez logowania
- PDF export generuje plik

---

## 8. Checklist implementacji

### Faza 1 — Migracje i modele
- [ ] `2026_04_19_000001_create_sales_offer_templates_table.php`
- [ ] `2026_04_19_000002_create_sales_offers_table.php`
- [ ] Model `SalesOfferTemplate` (scope, fillable, casts, relations)
- [ ] Model `SalesOffer` (scope, fillable, casts, relations, helpers)
- [ ] `Lead::salesOffers()` relacja

### Faza 2 — Seeder
- [ ] `SalesOfferTemplateSeeder` — 18 szablonów z docs/sales/
- [ ] Rejestracja w `DatabaseSeeder`

### Faza 3 — Service, Policy, Mail, Activity
- [ ] `SalesOfferService` (createFromTemplate, send, markViewed, convertToQuote)
- [ ] `SalesOfferMail` + `resources/views/emails/sales-offer.blade.php`
- [ ] `SalesOfferPolicy`
- [ ] `SalesOfferTemplatePolicy`
- [ ] Rejestracja policy w `AppServiceProvider`
- [ ] `LeadActivity` — 4 nowe typy (icon + color + bg)

### Faza 4 — Filament Resources
- [ ] `SalesOfferTemplateResource` (CRUD z MarkdownEditor)
- [ ] Pages: ListSalesOfferTemplates, CreateSalesOfferTemplate, EditSalesOfferTemplate, ViewSalesOfferTemplate
- [ ] `SalesOfferResource` (List + View)
- [ ] `ViewSalesOffer` Page + Blade (`view-sales-offer.blade.php`)
- [ ] Autosave (wire:model.live.debounce.1500ms) dla draft

### Faza 5 — Lead integration
- [ ] Akcja "Wyślij ofertę" w `LeadResource::table()`
- [ ] Akcja "Wyślij ofertę" w `ViewLead::getHeaderActions()`
- [ ] Sekcja z listą ofert w widoku ViewLead (opcjonalnie — w blade)

### Faza 6 — PDF Export
- [ ] `resources/views/pdf/sales-offer.blade.php`
- [ ] Akcja "Export PDF" w `ViewSalesOffer`

### Faza 7 — Public link
- [ ] Trasy `/offers/{token}` w `routes/web.php`
- [ ] `ClientSalesOfferController` (show, markViewed jako AJAX)
- [ ] `resources/js/Pages/SalesOffer/ClientView.jsx`
- [ ] Sanitizacja HTML (DOMPurify) w ClientView

---

## 9. Pytania otwarte / decyzje do podjęcia

| # | Pytanie | Rekomendacja |
|---|---------|-------------|
| 1 | Czy ClientView ma CTA "Chcę to omówić"? | Tak — mailto lub formularz kontaktowy |
| 2 | Czy wysłanie oferty auto-przesuwa etap leada do "Proposal Sent"? | NIE (inaczej niż Quote) — admin decyduje ręcznie |
| 3 | Czy admin może ponownie wysłać ofertę (resend)? | Tak — nowy token, nowy email |
| 4 | Czy SalesOffer jest widoczna w portalu klienta (portal.*)? | NIE — wystarczy publiczny link |
| 5 | Czy seeder importuje body z plików docs/sales/ automatycznie? | TAK — priorytet wysoki |
| 6 | Jaki edytor treści? MarkdownEditor (Filament built-in) vs TipTap? | MarkdownEditor — prostszy, bez dodatkowych zależności |
