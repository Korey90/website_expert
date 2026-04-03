# Feature Design: Landing Page Generator
**Data:** 2026-03-31  
**Sprint:** 1 (MVP — Tydzień 2–3) + NICE TO HAVE (Sprint 2 — AI Generator)  
**Bazuje na:** `docs/project-analysis.md`, `docs/architecture-plan.md`, `docs/mvp-plan.md`, `docs/feature-business-profile.md`  
**Status:** AWAITING APPROVAL — nie implementuj bez zatwierdzenia

---

## 1. Definicja modułu

**Cel:** Umożliwić właścicielowi firmy stworzenie landing page w oparciu o predefiniowane sekcje (bloki HTML), opublikowanie jej pod publicznym URL `/lp/{slug}`, oraz przechwytywanie leadów przez formularz kontaktowy — bez wiedzy technicznej, w czasie poniżej 15 minut.

**Bounded Context:** `LandingPages` (+ integracja z `Leads`, `BusinessProfile`)

**Priorytet MVP:**
- **MUST HAVE:** Tworzenie LP, edycja sekcji, formularz lead capture, publikacja, publiczny widok
- **NICE TO HAVE (v1.1):** AI generator treści (OpenAI), statystyki wyświetleń, UTM tracking

**Zależności:**
- `Business` model + `currentBusiness()` helper — z modułu `Business Profile` (Sprint 1)
- `CreateLeadAction` — istnieje (`app/Actions/CreateLeadAction.php`) — wymaga rozszerzenia
- `PipelineStage` model — istnieje, Lead ląduje na `order = 1`
- `Lead` model — istnieje, wymaga 2 nowych kolumn (`landing_page_id`, utm fields)
- OpenAI API key w `.env` — wymagany TYLKO dla v1.1 (AI generator)

**Użytkownik:** Admin agencji (role `admin`, `manager`), właściciel konta SaaS

---

## 2. Zakres modułu

### Wchodzi w MVP:
- Tabele `landing_pages` + `landing_page_sections`
- Modele `LandingPage` + `LandingPageSection`
- Tworzenie LP z wyborem szablonu (3 szablony startowe)
- Edytor sekcji w Filament (Repeater — szybki w implementacji, dobry UX dla v1 MVP)
- Publikacja / archiwizacja LP
- Publiczny widok `/lp/{slug}` (React + Inertia — bez auth)
- Formularz lead capture in-page (sekcja `form`) z honeypot + rate limiting
- Rozszerzenie `CreateLeadAction` o `landing_page_id`, `utm_*`
- Dodanie kolumn `landing_page_id` + `utm_*` do istniejącej tabeli `leads`
- Filament: `LandingPageResource` (zarządzanie LP)
- Filament: rozszerzenie `LeadResource` o kolumny + filtr LP
- Inertia/React strona publiczna LP (`Pages/LandingPage/Show.jsx`)
- Events: `LandingPagePublished`, `LeadCaptured`
- Testy Feature dla publikacji i lead capture

### Wchodzi w v1.1 (NICE TO HAVE — osobny sprint):
- AI generator treści (`GenerateSectionContentJob` + OpenAI)
- Statystyki LP (licznik views + conversions)
- UTM tracking full (kolumny są tworzone w MVP, dane odczytywane w v1.1)
- Filament Widget: LP Stats (konwersja %)

### NIE wchodzi w zakres:
- Drag-and-drop editor (Inertia/React) — v1.1 per mvp-plan 4.6
- A/B testing (`landing_page_variants`) — v2+
- Custom domains (`landing_page_domains`) — v2+
- Wielojęzyczne LP (osobne wersje EN/PL/PT) — v2+
- Integracje reklamowe (Meta Ads pixel, Google Tag) — v2+
- Preview live w panelu — v1.1

---

## 3. Model danych

### 3.1 Modyfikacja istniejącej tabeli `leads`

```
MIGRACJA ADDYTYWNA: 2026_03_31_000004_add_lp_fields_to_leads_table.php
(uruchamiać PO migracjach Business Profile 000001-000003)

Dodawane kolumny:
- landing_page_id    bigint unsigned, FK → landing_pages.id, NULLABLE, ON DELETE SET NULL
                     (nullable: lead może przyjść z innych źródeł)
- utm_source         varchar(255), NULLABLE    — "meta_ads", "google", "newsletter"
- utm_medium         varchar(255), NULLABLE    — "cpc", "email", "social"
- utm_campaign       varchar(255), NULLABLE    — "summer_promo_2026"
- utm_content        varchar(255), NULLABLE    — "banner_v2" (opcjonalne — dla v1.1)
- utm_term           varchar(255), NULLABLE    — "marketing agency" (opcjonalne — dla v1.1)

Indeksy:
- INDEX(landing_page_id)   — filtrowanie leadów per LP

UWAGA: Dodawać za pomocą dwóch migracji atomicznych:
  - Najpierw kolumny bez FK (bo `landing_pages` tabela musi istnieć wcześniej)
  - FK dodawać PO stworzeniu tabeli landing_pages
  - LUB jedna migracja z delayed FK (`->after('existing_col')`)
```

---

### 3.2 Tabela `landing_pages`

```
TABELA: landing_pages
Cel: Korzenne encje landing page — metadane, status, powiązanie z business (tenant).

Kolumny:
- id                    bigint unsigned, PK, AUTO INCREMENT
- business_id           char(26), FK → businesses.id, ON DELETE CASCADE, NOT NULL
                        (ULID — typ char(26) jak w tabeli businesses)
- title                 varchar(255), NOT NULL           — roboczy tytuł (widoczny tylko adminom)
- slug                  varchar(100), NOT NULL           — URL-friendly: "moja-agencja-landing"
                                                           UNIQUE per business_id
- status                varchar(20), NOT NULL, DEFAULT 'draft'
                                                           enum walidowany w modelu: draft|published|archived
- template_key          varchar(50), NULLABLE            — klucz użytego szablonu (np. 'lead_magnet')
                                                           NULL jeśli tworzono ręcznie
- language              varchar(5), NOT NULL, DEFAULT 'en'  — 'en'|'pl'|'pt'
- meta_title            varchar(160), NULLABLE           — SEO title (max 160 znaków)
- meta_description      varchar(320), NULLABLE           — SEO description (max 320 znaków)
- og_image_path         varchar(500), NULLABLE           — Open Graph image (dla social share)
- conversion_goal       varchar(50), NULLABLE            — 'book_call'|'download'|'purchase'|'contact'
- views_count           int unsigned, NOT NULL, DEFAULT 0 — licznik wyświetleń (denormalizacja dla szybkości)
- conversions_count     int unsigned, NOT NULL, DEFAULT 0 — licznik submisji formularza
- ai_generated          tinyint(1), NOT NULL, DEFAULT 0  — czy treść sekcji wygenerowana przez AI
- published_at          timestamp, NULLABLE              — kiedy po raz pierwszy opublikowano
- created_at / updated_at / deleted_at (SoftDeletes)

Indeksy:
- INDEX(business_id)                     — filtrowanie per tenant CRITICAL
- UNIQUE(business_id, slug)              — unikalny slug w ramach business
- INDEX(status)                          — filtrowanie draft/published
- INDEX(published_at)                    — sortowanie po dacie publikacji

Ograniczenia:
- slug: musi pasować do regex /^[a-z0-9-]+$/, min 3, max 100 znaków
```

---

### 3.3 Tabela `landing_page_sections`

```
TABELA: landing_page_sections
Cel: Bloki (sekcje) budujące landing page — każda sekcja ma typ i elastyczną strukturę treści w JSON.

Kolumny:
- id                    bigint unsigned, PK, AUTO INCREMENT
- landing_page_id       bigint unsigned, FK → landing_pages.id, ON DELETE CASCADE, NOT NULL
- type                  varchar(50), NOT NULL
                        Dozwolone wartości w MVP:
                          'hero'         — główna sekcja: nagłówek + podtytuł + CTA + obraz
                          'features'     — lista korzyści/punktów (ikon + tekst)
                          'testimonials' — recenzje klientów
                          'cta'          — prosta sekcja CTA (tytuł + button)
                          'form'         — formularz lead capture (OBOWIĄZKOWY dla LP)
                          'faq'          — lista pytań i odpowiedzi
                          'text'         — prosty blok tekstu/HTML
                          'video'        — embed video (YouTube/Vimeo URL)
- order                 smallint unsigned, NOT NULL, DEFAULT 0  — kolejność sekcji (od 0)
- content               JSON, NOT NULL, DEFAULT '{}'
                        Struktura zależy od type — patrz sekcja 3.4
- settings              JSON, NOT NULL, DEFAULT '{}'
                        Wygląd sekcji:
                        {
                          "background": "white|dark|primary|gradient",
                          "padding":    "sm|md|lg",
                          "visible":    true|false,
                          "customCss":  ""  (tylko v1.1)
                        }
- is_visible            tinyint(1), NOT NULL, DEFAULT 1  — czy sekcja widoczna na publicznym widoku
- created_at / updated_at

Indeksy:
- INDEX(landing_page_id)           — pobieranie sekcji danej LP
- INDEX(landing_page_id, order)    — pobieranie w kolejności

Uwagi:
- Brak SoftDeletes — sekcje są usuwane bezpowrotnie (są częścią LP)
- JSON `content` jest walidowany w FormRequest per `type` zanim trafi do bazy
- Maksymalnie 20 sekcji per LP (walidacja w serwisie)
```

---

### 3.4 Struktura JSON `content` per typ sekcji

```
type: 'hero'
{
  "headline":    "Twój sukces zaczyna się od jednej strony",
  "subheadline": "Tworzymy landing pages które konwertują",
  "cta_text":    "Zamów bezpłatną konsultację",
  "cta_url":     "#form",           // anchor do sekcji form lub URL zewnętrzny
  "image_path":  "lp/images/hero.jpg" // opcjonalny, z Storage
}

type: 'features'
{
  "headline": "Dlaczego my?",
  "items": [
    {"icon": "check", "title": "Szybka realizacja", "description": "w 7 dni roboczych"},
    {"icon": "star",  "title": "Gwarancja jakości", "description": "lub zwrot pieniędzy"},
    ...
  ]
}

type: 'testimonials'
{
  "headline": "Co mówią nasi klienci",
  "items": [
    {"author": "Jan Kowalski", "company": "AgroFirma sp. z o.o.",
     "text": "Świetna robota!", "rating": 5, "avatar_path": null},
    ...
  ]
}

type: 'cta'
{
  "headline": "Gotowy na wzrost?",
  "subheadline": "Dołącz do 50+ firm które nam zaufały",
  "cta_text":   "Porozmawiajmy",
  "cta_url":    "#form"
}

type: 'form'
{
  "headline":    "Skontaktuj się z nami",
  "subheadline": "Odpowiadamy w ciągu 24 godzin",
  "fields":      ["name", "email", "phone", "message"],  // widoczne pola
  "required":    ["name", "email"],                       // wymagane pola
  "cta_text":   "Wyślij wiadomość",
  "success_message": "Dziękujemy! Skontaktujemy się wkrótce.",
  "redirect_url": null  // null = zostań na stronie, URL = redirect po submit
}

type: 'faq'
{
  "headline": "Często zadawane pytania",
  "items": [
    {"question": "Ile kosztuje?", "answer": "Wycena indywidualna..."},
    ...
  ]
}

type: 'text'
{
  "headline": "O nas",              // opcjonalny
  "html":     "<p>Treść...</p>"    // sanitizowana przy zapisie
}

type: 'video'
{
  "headline":   "Obejrzyj jak działamy",
  "video_url":  "https://youtube.com/watch?v=xxx",
  "autoplay":   false,
  "thumbnail_path": null
}
```

---

### 3.5 Relacja z istniejącymi tabelami

```
businesses (Sprint 1)
  └── landing_pages (nowa)
        └── landing_page_sections (nowa, cascadeDelete)

leads (istniejąca — modyfikowana)
  └── landing_page_id → landing_pages (nullable FK)

[Istniejące — tylko dodajemy kolumnę landing_page_id + utm_* :]
leads.landing_page_id FK → landing_pages.id (SET NULL on delete)
leads.utm_source, utm_medium, utm_campaign, utm_content, utm_term (nullable strings)

[BRAK ZMIAN w Sprint 1:]
clients, contacts, projects, invoices — bez zmian
AutomationRule — nie dotykamy
PipelineStage — używamy ale nie modyfikujemy
```

---

## 4. Backend Laravel

### 4.1 Modele

---

**Model: `app/Models/LandingPage.php`**

```
Traits:
- HasFactory
- SoftDeletes
- BelongsToTenant (szkielet — auto-fill business_id przy creating, bez GlobalScope w MVP)

Table: 'landing_pages'

Fillable:
- business_id, title, slug, status, template_key, language, meta_title,
  meta_description, og_image_path, conversion_goal, views_count,
  conversions_count, ai_generated, published_at

Casts:
- ai_generated:      boolean
- views_count:       integer
- conversions_count: integer
- published_at:      datetime

Stałe (const):
- STATUS_DRAFT      = 'draft'
- STATUS_PUBLISHED  = 'published'
- STATUS_ARCHIVED   = 'archived'

- TEMPLATE_LEAD_MAGNET = 'lead_magnet'
- TEMPLATE_SERVICES    = 'services'
- TEMPLATE_PORTFOLIO   = 'portfolio'

Relacje:
- public function business(): BelongsTo → Business
- public function sections(): HasMany → LandingPageSection (ordered by 'order')
- public function leads(): HasMany → Lead (przez landing_page_id)
- public function formSection(): HasOne → LandingPageSection (where type='form', first)

Scopes:
- scopePublished($query): where('status', 'published')
- scopeDraft($query): where('status', 'draft')
- scopeForBusiness($query, Business $b): where('business_id', $b->id)

Akcesory:
- getPublicUrlAttribute(): string
    return route('lp.show', ['slug' => $this->slug]);

- getConversionRateAttribute(): float
    if ($this->views_count === 0) return 0;
    return round(($this->conversions_count / $this->views_count) * 100, 2);

- getIsPublishedAttribute(): bool
    return $this->status === self::STATUS_PUBLISHED;

Metody:
- public function hasFormSection(): bool
    return $this->sections()->where('type', 'form')->exists();

- public function canBePublished(): bool
    return $this->sections()->exists() && $this->hasFormSection();

- public function publish(): void
    $this->update(['status' => 'published', 'published_at' => now() ?? $this->published_at]);

- public function unpublish(): void
    $this->update(['status' => 'archived']);
```

---

**Model: `app/Models/LandingPageSection.php`**

```
Traits:
- HasFactory

Table: 'landing_page_sections'

Fillable:
- landing_page_id, type, order, content, settings, is_visible

Casts:
- content:    'array'
- settings:   'array'
- is_visible: boolean
- order:      integer

Stałe:
- TYPES = ['hero', 'features', 'testimonials', 'cta', 'form', 'faq', 'text', 'video']

Relacja:
- public function landingPage(): BelongsTo → LandingPage

Scopes:
- scopeVisible($query): where('is_visible', true)
- scopeOrdered($query): orderBy('order')

Metody:
- public function getDefaultContent(string $type): array
    Zwraca domyślną strukturę JSON dla danego type — używane przy tworzeniu nowej sekcji
    (switch/match per type, patrz 3.4)
```

---

**Rozszerzenie modelu `app/Models/Lead.php`**

```
Dodać do fillable (istniejący plik — minimalne zmiany):
- 'landing_page_id', 'utm_source', 'utm_medium', 'utm_campaign', 'utm_content', 'utm_term'

Dodać relację:
- public function landingPage(): BelongsTo
    return $this->belongsTo(LandingPage::class);

Bez zmian w reszcie modelu.
```

---

### 4.2 Serwisy

---

**`app/Services/LandingPage/LandingPageService.php`**

```
Odpowiedzialność: Zarządzanie cyklem życia landing page (CRUD, publikacja, archiwizacja),
                  inicjalizacja sekcji z szablonu, walidacja przed publikacją.

Metody publiczne:

- create(array $data, Business $business): LandingPage
  Parametry: dane = {title, slug?, template_key?, language, meta_title?, meta_description?}
  Zwraca: LandingPage
  Logika:
    1. Generuj slug z title jeśli brak (Str::slug + unikalność w ramach business)
    2. Utwórz rekord landing_pages z business_id = $business->id
    3. Jeśli $data['template_key'] — zainicjalizuj sekcje przez initSectionsFromTemplate()
    4. Zwróć LandingPage
  Rzuca: ValidationException jeśli slug zajęty w danym business

- update(LandingPage $lp, array $data): LandingPage
  Parametry: dane = {title?, slug?, meta_title?, meta_description?, language?}
  Logika:
    1. Jeśli slug zmieniony → sprawdź unikalność, zaktualizuj
    2. UWAGA: slug LP można zmienić tylko gdy status = 'draft' (bo published LP ma live URL)
    3. Zaktualizuj pola
  Rzuca: DomainException jeśli próba zmiany sluga opublikowanej LP

- publish(LandingPage $lp): LandingPage
  Parametry: LandingPage
  Logika:
    1. Sprawdź canBePublished() — min. 1 sekcja + sekcja 'form'
    2. $lp->publish()
    3. Dispatch event LandingPagePublished
    4. Zwróć aktualizowany LP
  Rzuca: DomainException jeśli LP nie spełnia warunków

- unpublish(LandingPage $lp): LandingPage
  Parametry: LandingPage
  Logika:
    1. $lp->unpublish()  → status = 'archived'
    2. Nie emituje eventu (cisza przy archiwizacji)
    3. Zwróć LP

- delete(LandingPage $lp): void
  Parametry: LandingPage
  Logika:
    1. Sprawdź czy LP ma powiązane leady
    2. Jeśli TAK: SoftDelete tylko (nie hard delete)
    3. Jeśli NIE: SoftDelete
    4. Sekcje kaskadowo usunięte przez FK ON DELETE CASCADE
    Uwaga: leady zachowane, landing_page_id → SET NULL (ON DELETE SET NULL)

- reorderSections(LandingPage $lp, array $orderedIds): void
  Parametry: LandingPage, tablica [id => newOrder]
  Logika:
    1. Iteruj przez $orderedIds
    2. Aktualizuj column 'order' dla każdej sekcji
  Używane przez drag-and-drop API endpoint w Filament

- initSectionsFromTemplate(LandingPage $lp, string $templateKey): void
  Parametry: LandingPage, klucz szablonu
  Logika:
    1. Pobierz definicję szablonu z config('landing_pages.templates')
    2. Iteruj przez sekcje szablonu
    3. Utwórz LandingPageSection dla każdej z domyślnym content
  Rzuca: InvalidArgumentException jeśli nieznany template_key

Zależy od: LandingPageSectionService (pośrednio), config/landing_pages.php
```

---

**`app/Services/LandingPage/LandingPageSectionService.php`**

```
Odpowiedzialność: Zarządzanie sekcjami LP — tworzenie, aktualizacja, usuwanie, walidacja typów.

Metody publiczne:

- create(LandingPage $lp, array $data): LandingPageSection
  Parametry: LandingPage, dane = {type, order?, content, settings?}
  Logika:
    1. Sprawdź typ jest w LandingPageSection::TYPES
    2. Sprawdź limit 20 sekcji
    3. Jeśli brak content → pobierz domyślny przez getDefaultContent($type)
    4. Sanitize content.html jeśli type='text' przez Purifier / strip_tags
    5. Ustaw order = max(order) + 1 jeśli nie podano
    6. Utwórz sekcję

- update(LandingPageSection $section, array $data): LandingPageSection
  Parametry: LandingPageSection, dane = {content?, settings?, order?, is_visible?}
  Logika:
    1. Sanitize content.html jeśli type='text'
    2. Walidacja content JSON vs type (sprawdź wymagane pola dla danego type)
    3. Zaktualizuj sekcję

- delete(LandingPageSection $section): void
  Parametry: LandingPageSection
  Logika:
    1. Sprawdź czy to jedyna sekcja 'form' — jeśli LP jest 'published': rzuć DomainException
    2. Usuń sekcję (hard delete — sekcje nie mają SoftDeletes)
    3. Przeindeksuj order po usunięciu (opcjonalnie: lazy reindex przy następnym read)

- validateContentForType(string $type, array $content): array
  Parametry: type, content JSON
  Zwraca: zwalidowany content lub rzuca ValidationException
  Logika: switch/match per type → sprawdź wymagane pola

Bezpieczeństwo:
- type='text' → content.html sanitized przez HTMLPurifier / strip_tags z allowed list
- type='video' → content.video_url musi przejść przez whitelist: youtube.com, vimeo.com
- Inne typy → escape HTML przy renderze (React robi to defaultowo)
```

---

**`app/Services/LandingPage/LeadCaptureService.php`**

```
Odpowiedzialność: Przechwytywanie i walidacja submisji formularza z landing page.
                  Łączy LandingPage z istniejącym CreateLeadAction.

Metody publiczne:

- capture(LandingPage $lp, array $data, Request $request): Lead
  Parametry: LandingPage, dane formularza, oryginalny Request (dla IP + UA)
  Zwraca: Lead
  Logika:
    1. Sprawdź czy LP jest opublikowana — jeśli nie: rzuć DomainException
    2. Sprawdź sekcja 'form' na LP — pobierz jej config (required fields, redirect_url)
    3. Odczytaj UTM z query string requestu: utm_source, utm_medium, utm_campaign, utm_content, utm_term
    4. Przygotuj dane dla CreateLeadAction:
       {
         email:          $data['email'],
         name:           $data['name'],
         phone:          $data['phone'] ?? null,
         source:         'landing_page',
         notes:          $data['message'] ?? null,
         landing_page_id: $lp->id,
         utm_source:     $utm['utm_source'] ?? null,
         utm_medium:     $utm['utm_medium'] ?? null,
         utm_campaign:   $utm['utm_campaign'] ?? null,
       }
    5. Wywołaj app(CreateLeadAction::class)->execute($preparedData)
    6. Inkrementuj $lp->conversions_count (atomic: LandingPage::increment('conversions_count'))
    7. Dispatch event LeadCaptured
    8. Zwróć Lead

- trackView(LandingPage $lp): void
  Logika:
    1. LandingPage::where('id', $lp->id)->increment('views_count')
  Uwagi:
    - O ile w MVP zostawiamy trackowanie widoków (views_count już jest w tabeli)
    - NIE blokuje odpowiedzi — można wykonać w tle (dispatchAfterResponse)
    - W v1.1 zastąpić przez osobny job (LandingPageViewJob) dla batch inserts

Zależy od: CreateLeadAction (istniejąca), LandingPage model
```

---

**`app/Services/LandingPage/LandingPageSlugService.php`**

```
Odpowiedzialność: Generowanie i walidacja unikalnych slugów LP w ramach business.

Metody publiczne:

- generate(string $title, Business $business): string
  Parametry: tytuł LP, business
  Logika:
    1. $slug = Str::slug($title)
    2. Sprawdź unikalność: LandingPage::where('business_id', $b->id)->where('slug', $slug)->exists()
    3. Jeśli zajęty → append '-2', '-3', ... aż do wolnego
    4. Zwróć unikalny slug

- validate(string $slug, Business $business, ?LandingPage $excluding = null): bool
  Parametry: proponowany slug, business, opcjonalne LP do wykluczenia (edycja)
  Logika:
    1. Sprawdź format: /^[a-z0-9-]{3,100}$/
    2. Sprawdź unikalność w ramach business (excludując $excluding->id jeśli podane)
    3. Zwróć bool

Bezpieczeństwo:
- Slug NIGDY nie pochodzi bezpośrednio z URL parametru bez walidacji
- Blacklist zarezerwowanych slugów: ['admin', 'api', 'lp', 'dashboard', 'login', 'register']
```

---

### 4.3 Rozszerzenie `CreateLeadAction`

```
PLIK: app/Actions/CreateLeadAction.php (istniejący)

Do dodania w @param docblock:
'landing_page_id' => int|null,
'utm_source'      => string|null,
'utm_medium'      => string|null,
'utm_campaign'    => string|null,

Do dodania w Lead::create([...]):
'landing_page_id'  => $data['landing_page_id'] ?? null,
'utm_source'       => $data['utm_source'] ?? null,
'utm_medium'       => $data['utm_medium'] ?? null,
'utm_campaign'     => $data['utm_campaign'] ?? null,

Bez innych zmian — istniejąca logika client/stage/email notification niezmieniona.
```

---

### 4.4 Form Requests

---

**`app/Http/Requests/LandingPage/StoreLandingPageRequest.php`**

```
Reguły walidacji:
- title:             required|string|min:3|max:255
- slug:              nullable|string|min:3|max:100|regex:/^[a-z0-9-]+$/
                     (jeśli podany — zostanie użyty; jeśli null — auto-generate)
- language:          required|string|in:en,pl,pt
- template_key:      nullable|string|in:lead_magnet,services,portfolio
- meta_title:        nullable|string|max:160
- meta_description:  nullable|string|max:320
- conversion_goal:   nullable|string|in:book_call,download,purchase,contact

Autoryzacja:
- $this->user()->can('manage_landing_pages') || $this->user()->hasRole(['admin', 'manager'])
```

---

**`app/Http/Requests/LandingPage/UpdateLandingPageRequest.php`**

```
Reguły walidacji:
- title:             nullable|string|min:3|max:255
- slug:              nullable|string|min:3|max:100|regex:/^[a-z0-9-]+$/
                     Walidacja unikalności: Rule::unique('landing_pages')->where('business_id', currentBusiness()->id)
                                                                         ->ignore($this->route('landingPage')->id)
- language:          nullable|string|in:en,pl,pt
- meta_title:        nullable|string|max:160
- meta_description:  nullable|string|max:320
- conversion_goal:   nullable|string|in:book_call,download,purchase,contact
- og_image:          nullable|image|mimes:jpg,jpeg,png,webp|max:2048

Autoryzacja:
- Sprawdź czy LP należy do currentBusiness()
```

---

**`app/Http/Requests/LandingPage/StoreSectionRequest.php`**

```
Reguły walidacji:
- type:              required|string|in:hero,features,testimonials,cta,form,faq,text,video
- order:             nullable|integer|min:0
- content:           required|array
- settings:          nullable|array
- settings.background: nullable|string|in:white,dark,primary,gradient
- settings.padding:    nullable|string|in:sm,md,lg
- settings.visible:    nullable|boolean
- is_visible:        nullable|boolean

Dynamiczna walidacja content per type:
Implementacja: override after() lub custom Rule per type
Przykład dla type='form':
  - content.fields: required|array
  - content.fields.*: string|in:name,email,phone,message
  - content.required: required|array
  - content.cta_text: required|string|max:100
Dla type='video':
  - content.video_url: required|url — z custom Rule sprawdzającą domain whitelist
```

---

**`app/Http/Requests/LandingPage/LeadCaptureRequest.php`**

```
WAŻNE: Plik bez autoryzacji auth — formularz jest publiczny

Reguły walidacji:
- name:       required|string|min:2|max:255
- email:      required|email:rfc,dns|max:255
- phone:      nullable|string|max:30|regex:/^[\+\d\s\(\)\-]+$/
- message:    nullable|string|max:2000
- honeypot:   required|string|size:0|nullable
              (pole honeypot musi być puste — boty wypełniają wszystkie pola)
- utm_source:   nullable|string|max:100
- utm_medium:   nullable|string|max:100
- utm_campaign: nullable|string|max:100

Middleware (w route, nie w Request):
- throttle:3,60  — max 3 submisje per IP na 60 minut (anty-spam)

UWAGA bezpieczeństwo:
- email:rfc,dns — walidacja DNS MX sprawdza czy domena email istnieje (blokuje fake emails)
- Brak auth — nie ujawniaj żadnych danych systemu w odpowiedzi błędu (ogólne komunikaty)
- CSRF token wymagany przy renderze formularza (meta csrf token w JS)
```

---

### 4.5 Kontrolery

---

**`app/Http/Controllers/LandingPage/LandingPageController.php`**

```
Cel: Zarządzanie LP (CRUD + publish/unpublish). Dostęp tylko dla zalogowanych users.

Trasy (dodać do routes/web.php):

Route::middleware(['auth', 'verified', 'has.business'])
    ->prefix('landing-pages')
    ->name('landing-pages.')
    ->group(function () {
        Route::get('/', [LandingPageController::class, 'index'])->name('index');
        Route::get('/create', [LandingPageController::class, 'create'])->name('create');
        Route::post('/', [LandingPageController::class, 'store'])->name('store');
        Route::get('/{landingPage}', [LandingPageController::class, 'show'])->name('show');
        Route::get('/{landingPage}/edit', [LandingPageController::class, 'edit'])->name('edit');
        Route::patch('/{landingPage}', [LandingPageController::class, 'update'])->name('update');
        Route::delete('/{landingPage}', [LandingPageController::class, 'destroy'])->name('destroy');
        Route::post('/{landingPage}/publish', [LandingPageController::class, 'publish'])->name('publish');
        Route::post('/{landingPage}/unpublish', [LandingPageController::class, 'unpublish'])->name('unpublish');
    });

Metody:
- index():
    $pages = LandingPage::forBusiness(currentBusiness())
             ->orderByDesc('updated_at')->paginate(10);
    return Inertia::render('LandingPages/Index', [
        'landingPages' => $pages,
        'stats' => [published, draft, archived counts],
    ]);

- create():
    return Inertia::render('LandingPages/Create', [
        'templates' => config('landing_pages.templates'),
        'languages' => config('languages'),
    ]);

- store(StoreLandingPageRequest $request):
    $lp = $this->service->create($request->validated(), currentBusiness());
    return redirect()->route('landing-pages.edit', $lp)
        ->with('success', __('lp.created'));

- show(LandingPage $landingPage):
    $this->authorize('view', $landingPage);
    return Inertia::render('LandingPages/Show', [
        'landingPage' => $landingPage->load('sections'),
        'stats' => [views: $lp->views_count, conversions: $lp->conversions_count, rate: $lp->conversion_rate],
        'recentLeads' => $landingPage->leads()->latest()->limit(5)->get(),
    ]);

- edit(LandingPage $landingPage):
    $this->authorize('update', $landingPage);
    return Inertia::render('LandingPages/Edit', [
        'landingPage' => $landingPage->load('sections'),
        'sectionTypes' => LandingPageSection::TYPES,
        'templates' => config('landing_pages.templates'),
    ]);

- update(UpdateLandingPageRequest $request, LandingPage $landingPage):
    $this->authorize('update', $landingPage);
    $this->service->update($landingPage, $request->validated());
    return redirect()->back()->with('success', __('lp.updated'));

- destroy(LandingPage $landingPage):
    $this->authorize('delete', $landingPage);
    $this->service->delete($landingPage);
    return redirect()->route('landing-pages.index')->with('success', __('lp.deleted'));

- publish(LandingPage $landingPage):
    $this->authorize('update', $landingPage);
    $this->service->publish($landingPage);
    return redirect()->back()->with('success', __('lp.published'));

- unpublish(LandingPage $landingPage):
    $this->authorize('update', $landingPage);
    $this->service->unpublish($landingPage);
    return redirect()->back()->with('success', __('lp.unpublished'));
```

---

**`app/Http/Controllers/LandingPage/LandingPageSectionController.php`**

```
Cel: CRUD sekcji LP — wywoływana z edytora (Inertia) lub Filament Repeater.

Trasy (pod landing-pages.{landingPage}.sections):

Route::middleware(['auth', 'verified', 'has.business'])
    ->prefix('landing-pages/{landingPage}/sections')
    ->name('lp-sections.')
    ->group(function () {
        Route::post('/', [LandingPageSectionController::class, 'store'])->name('store');
        Route::patch('/{section}', [LandingPageSectionController::class, 'update'])->name('update');
        Route::delete('/{section}', [LandingPageSectionController::class, 'destroy'])->name('destroy');
        Route::post('/reorder', [LandingPageSectionController::class, 'reorder'])->name('reorder');
    });

Metody:
- store(StoreSectionRequest $request, LandingPage $lp):
    Autoryzacja LP ownership → serwis + redirect back z flash

- update(UpdateSectionRequest $request, LandingPage $lp, LandingPageSection $section):
    Autoryzacja LP ownership → serwis + redirect back

- destroy(LandingPage $lp, LandingPageSection $section):
    Autoryzacja → serwis + redirect back

- reorder(Request $request, LandingPage $lp):
    Walidacja: sections → array of {id, order}
    Autoryzacja → $this->lpService->reorderSections($lp, $request->sections)
    Zwróć: response()->json(['success' => true])
```

---

**`app/Http/Controllers/LandingPage/PublicLandingPageController.php`**

```
Cel: Publiczny widok LP — bez autentykacji. Dostęp dla każdego (potencjalne leady).

WAŻNE: Ten kontroler NIE używa middleware 'auth' ani 'has.business'

Trasy (dodać do routes/web.php — POZA middleware group auth):

Route::prefix('lp')->name('lp.')->group(function () {
    Route::get('/{slug}', [PublicLandingPageController::class, 'show'])->name('show');
    Route::post('/{slug}/submit', [PublicLandingPageController::class, 'submit'])
        ->name('submit')
        ->middleware('throttle:3,60');  // max 3 submisje/IP/godzina
});

Metody:
- show(string $slug):
    $lp = LandingPage::where('slug', $slug)
                     ->where('status', 'published')
                     ->with('sections')
                     ->firstOrFail();
    
    // Track view asynchronicznie (nie blokkuje response)
    app(LeadCaptureService::class)->trackView($lp);
    
    return Inertia::render('LandingPage/Show', [
        'landingPage' => [
            'id'           => $lp->id,
            'slug'         => $lp->slug,
            'meta_title'   => $lp->meta_title ?? $lp->title,
            'meta_description' => $lp->meta_description,
            'language'     => $lp->language,
        ],
        'sections' => $lp->sections()
                         ->visible()
                         ->ordered()
                         ->get()
                         ->map(fn($s) => ['type' => $s->type, 'content' => $s->content, 'settings' => $s->settings]),
        'csrfToken' => csrf_token(),  // dla formularza
    ]);
    
UWAGA: NIE przekazuj business_id, landing_page_id ani żadnych wewnętrznych ID do frontendu
       Tylko slug jest publiczny — ID wewnętrzne sprawdzamy server-side przy submit

- submit(LeadCaptureRequest $request, string $slug):
    $lp = LandingPage::where('slug', $slug)->where('status', 'published')->firstOrFail();
    
    try {
        $lead = app(LeadCaptureService::class)->capture($lp, $request->validated(), $request);
        
        $formSection = $lp->formSection;
        $redirectUrl = $formSection?->content['redirect_url'] ?? null;
        $successMsg  = $formSection?->content['success_message'] ?? 'Thank you!';
        
        return response()->json([
            'success'      => true,
            'message'      => $successMsg,
            'redirect_url' => $redirectUrl,
        ]);
    } catch (TooManyRequestsException $e) {
        return response()->json(['error' => 'Too many submissions. Please try again later.'], 429);
    } catch (\Exception $e) {
        Log::error('LP Lead capture failed', ['slug' => $slug, 'error' => $e->getMessage()]);
        return response()->json(['error' => 'Something went wrong. Please try again.'], 500);
    }
```

---

### 4.6 Policy

```
PLIK: app/Policies/LandingPagePolicy.php

Metody:
- viewAny(User $user): bool
    return $user->can('view_landing_pages') || $user->hasAnyRole(['admin', 'manager']);

- view(User $user, LandingPage $lp): bool
    return $this->isMemberOfBusiness($user, $lp->business_id);

- create(User $user): bool
    return $user->can('manage_landing_pages') || $user->hasAnyRole(['admin', 'manager']);

- update(User $user, LandingPage $lp): bool
    return $this->isMemberOfBusiness($user, $lp->business_id)
        && $user->can('manage_landing_pages');

- delete(User $user, LandingPage $lp): bool
    return $this->isMemberOfBusiness($user, $lp->business_id)
        && $user->hasRole('admin');

Metoda pomocnicza:
- private isMemberOfBusiness(User $user, $businessId): bool
    return $user->businesses()->where('businesses.id', $businessId)->exists();

Rejestracja:
- w AuthServiceProvider lub Boot (Laravel 11 Gate::policy)
```

---

### 4.7 Uprawnienia Spatie (rozszerzenie AdminSeeder)

```
Nowe uprawnienia do dodania w AdminSeeder:

'view_landing_pages',         — przeglądanie listy LP
'manage_landing_pages',       — tworzenie, edycja, usuwanie LP
'publish_landing_pages',      — publikacja i archiwizacja LP (subset manage)

Przypisanie do ról:
- admin:     view_landing_pages, manage_landing_pages, publish_landing_pages
- manager:   view_landing_pages, manage_landing_pages, publish_landing_pages
- developer: view_landing_pages (read-only)
- client:    brak
```

---

### 4.8 Events

```
EVENT: app/Events/LandingPagePublished.php
Konstruktor: public function __construct(
    public readonly LandingPage $landingPage,
    public readonly User $publishedBy
)
Używany przez: LandingPageService::publish() → event(new LandingPagePublished(...))
Potencjalne listenery (v1.1):
  - IndexLandingPageForSearch (SEO indexing)
  - NotifyTeamOnPublish (dashboard notification)

---

EVENT: app/Events/LeadCaptured.php
Konstruktor: public function __construct(
    public readonly Lead $lead,
    public readonly LandingPage $landingPage
)
Używany przez: LeadCaptureService::capture() → event(new LeadCaptured(...))
Potencjalne listenery:
  - ✅ Istniejący AutomationEventListener (obsłuży 'lead_created' trigger)
  - UpdateLandingPageStats (inkrementuje conversions_count — MVP używa atomic increment)
```

---

### 4.9 Jobs (v1.1 — zarezerwowane)

```
JOB: app/Jobs/GenerateLandingPageContentJob.php (v1.1)
Cel: Generowanie treści sekcji LP przez OpenAI na podstawie Business Profile
Kolejka: 'ai' (osobna kolejka dla AI jobs — długo trwające)
Timeout: 120 sekund
Tries: 2 (retry przy błędzie OpenAI API)

Dane wejściowe:
- LandingPage $lp
- Business $business
- string[] $sectionTypes — które sekcje generować

Logika (v1.1):
1. Pobierz AI context z BusinessProfileService::getAiContext($business)
2. Dla każdego sectionType: zbuduj prompt → wywołaj OpenAI Chat Completions API
3. Parsuj odpowiedź → zaktualizuj LandingPageSection::content
4. Oznacz $lp->ai_generated = true
5. Dispatch LandingPageAiGenerated event

Uwagi:
- Używa modelu gpt-4o (konfigurowalny z config/services.php)
- Limity tokenów per sekcja: max 500 tokenów output
- Koszt: ~$0.01-0.05 per LP generation (monitorować w v1.1)
- Błędy API: zapisować do tabeli 'ai_generation_logs' (v1.1 tabela)
```

---

### 4.10 Filament Resources

---

**`app/Filament/Resources/LandingPageResource.php`**

```
Model: LandingPage
NavigationGroup: 'Marketing'
NavigationIcon: heroicon-o-document-text
NavigationLabel: 'Landing Pages'
NavigationSort: 1

Tabela (table columns):
- TextColumn::make('title') → searchable, sortable
- TextColumn::make('slug') → copyable (klinkalna URL do LP)
- BadgeColumn::make('status') → colors: [draft→gray, published→success, archived→warning]
- TextColumn::make('language') → badge
- TextColumn::make('views_count') → label 'Views', sortable
- TextColumn::make('conversions_count') → label 'Leads'
- TextColumn::make('conversion_rate') → label 'CVR %', suffix '%'
- TextColumn::make('published_at') → date
- TextColumn::make('updated_at') → since (human-friendly)

Akcje w tabeli:
- ViewAction → show stats + recent leads
- EditAction → edytor sekcji
- Action::make('publish') → wywołuje $record->publish() via service; disabled gdy status = 'published'
- Action::make('unpublish') → disabled gdy status != 'published'
- Action::make('copyUrl') → clipboard copy public URL
- DeleteAction (soft) → widoczna tylko dla admin role

Filtry:
- SelectFilter::make('status') → options: draft|published|archived
- SelectFilter::make('language') → options: en|pl|pt

Formularz (form — dla Create + Edit ustawień LP, BEZ edycji sekcji):
Section 'Basic Information':
  - TextInput::make('title') → required
  - TextInput::make('slug') → hint: 'Auto-generated if empty'
  - Select::make('language') → options config
  - Select::make('conversion_goal') → options config
  - Select::make('template_key') → tylko przy Create; options z config

Section 'SEO':
  - TextInput::make('meta_title') → maxLength 160, character counter
  - Textarea::make('meta_description') → maxLength 320, character counter

Sekcje LP (Edytor w Filament — Sprint 1):
Używamy dedykowanej strony EditSections:
  - app/Filament/Resources/LandingPageResource/Pages/EditSections.php
  - Repeater z polami per sekcja:
    - Select('type') → po zmianie: reset content do default
    - Toggle('is_visible')
    - KeyValue / TextInput / Textarea dla content fields
    - Uwaga: nie idealny UX, ale działa szybko → Inertia editor w v1.1

Stats Page / View:
  - app/Filament/Resources/LandingPageResource/Pages/ViewLandingPage.php
  - Wyświetla: statystyki (views, conversions, CVR), ostatnie 10 leadów z tej LP
  - Przycisk: "Open Public URL" → nowa karta z /lp/{slug}
```

---

**Rozszerzenie `app/Filament/Resources/LeadResource.php`**

```
Zmiany minimalne (istniejący plik):

W table columns — dodać:
- TextColumn::make('landingPage.title') → label 'Landing Page', searchable, nullable-safe

W table filters — dodać:
- SelectFilter::make('landing_page_id')
    → options(LandingPage::forBusiness(currentBusiness())->pluck('title', 'id'))
    → label 'From Landing Page'

W form — dodać w Section 'Lead Details':
- Select::make('landing_page_id')
    → nullable, label 'Landing Page'
    → options(LandingPage::forBusiness(currentBusiness())->pluck('title', 'id'))

W source options — dodać wartość: 'landing_page' => 'Landing Page'

Bez zmian w reszcie Resource.
```

---

**Nowy Widget Dashboard: `app/Filament/Widgets/LandingPageStatsWidget.php`**

```
Typ: StatsOverviewWidget
Pozycja: po istniejącym OverviewStatsWidget

Stats:
- Stat::make('Published LP', count) → icon: heroicon-o-globe-alt → color: success
- Stat::make('Draft LP', count) → icon: heroicon-o-document → color: gray
- Stat::make('LP Views (7 dni)', sum) → icon: heroicon-o-eye
- Stat::make('LP Leads (7 dni)', count) → icon: heroicon-o-user-plus → color: primary

Query:
$lp = LandingPage::where('business_id', currentBusiness()?->id);
- published: $lp->published()->count()
- draft:     $lp->draft()->count()
- views:     $lp->published()->sum('views_count')
- leads:     Lead::where('landing_page_id', '<>', null)
                 ->whereDate('created_at', '>=', now()->subDays(7))
                 ->count()
```

---

### 4.11 Konfiguracja `config/landing_pages.php`

```php
// config/landing_pages.php — nowy plik

return [

    // Dostępne typy sekcji
    'section_types' => [
        'hero', 'features', 'testimonials', 'cta', 'form', 'faq', 'text', 'video'
    ],

    // Max sekcje per LP
    'max_sections_per_page' => 20,

    // Gotowe szablony startowe (Sprint 1 — 3 szablony)
    'templates' => [

        'lead_magnet' => [
            'name'        => 'Lead Magnet',
            'description' => 'Capture leads with a valuable offer',
            'sections'    => ['hero', 'features', 'cta', 'form'],
        ],

        'services' => [
            'name'        => 'Services',
            'description' => 'Showcase your services and get inquiries',
            'sections'    => ['hero', 'features', 'testimonials', 'cta', 'form', 'faq'],
        ],

        'portfolio' => [
            'name'        => 'Portfolio',
            'description' => 'Show your work and invite collaboration',
            'sections'    => ['hero', 'text', 'features', 'cta', 'form'],
        ],
    ],

    // Białe listy domen video (bezpieczeństwo)
    'video_allowed_domains' => [
        'youtube.com',
        'youtu.be',
        'vimeo.com',
    ],

    // Publiczny URL prefix
    'public_url_prefix' => '/lp/',

    // Zarezerwowane slugi (nie można ich użyć)
    'reserved_slugs' => [
        'admin', 'api', 'lp', 'dashboard', 'login', 'register', 'profile',
        'onboarding', 'business', 'portal', 'stripe', 'payu',
    ],
];
```

---

### 4.12 Migracje — kolejność i nazwy

```
Migracje do stworzenia (w tej kolejności, po migracjach Business Profile 000001-000003):

4. 2026_03_31_000004_create_landing_pages_table.php
   Tworzy: landing_pages (business_id char(26) FK, title, slug, status, template_key,
                          language, meta_title, meta_description, og_image_path,
                          conversion_goal, views_count DEFAULT 0, conversions_count DEFAULT 0,
                          ai_generated DEFAULT 0, published_at nullable,
                          created_at, updated_at, deleted_at)
   Indeksy: INDEX(business_id), UNIQUE(business_id, slug), INDEX(status), INDEX(published_at)
   FK: business_id → businesses.id ON DELETE CASCADE

5. 2026_03_31_000005_create_landing_page_sections_table.php
   Tworzy: landing_page_sections (landing_page_id FK, type, order DEFAULT 0,
                                   content JSON NOT NULL DEFAULT '{}',
                                   settings JSON NOT NULL DEFAULT '{}',
                                   is_visible TINYINT DEFAULT 1,
                                   created_at, updated_at)
   Indeksy: INDEX(landing_page_id), INDEX(landing_page_id, order)
   FK: landing_page_id → landing_pages.id ON DELETE CASCADE

6. 2026_03_31_000006_add_lp_fields_to_leads_table.php
   Addytywna do istniejącej tabeli leads:
   - Dodaje: landing_page_id bigint unsigned nullable
   - Dodaje: utm_source, utm_medium, utm_campaign, utm_content, utm_term varchar(255) nullable
   - FK: landing_page_id → landing_pages.id ON DELETE SET NULL
   - INDEX: landing_page_id
   UWAGA: Dodawać nullable bez default — istniejące rekordy dostaną NULL (bezpieczne)
```

---

## 5. Frontend Inertia + React

### 5.1 Nowe pliki do stworzenia

```
resources/js/
├── Pages/
│   ├── LandingPages/
│   │   ├── Index.jsx        — lista LP z filtrowaniem i statusami
│   │   ├── Create.jsx       — wybór szablonu + podstawowe dane LP
│   │   ├── Edit.jsx         — edytor sekcji LP (główna strona edycji)
│   │   └── Show.jsx         — widok statystyk + ostatnie leady z tej LP
│   └── LandingPage/
│       └── Show.jsx         — publiczny widok LP (bez auth) — renderuje sekcje
├── Components/
│   └── LandingPage/
│       ├── SectionEditor.jsx       — edytor contentu pojedynczej sekcji
│       ├── SectionList.jsx         — lista sekcji z drag-and-drop
│       ├── SectionTypeIcon.jsx     — ikona + label dla type sekcji
│       ├── StatusBadge.jsx         — badge draft|published|archived
│       ├── TemplateCard.jsx        — karta do wyboru szablonu
│       ├── ConversionStats.jsx     — views/conversions/CVR widget
│       └── PublicSections/
│           ├── HeroSection.jsx         — render sekcji hero
│           ├── FeaturesSection.jsx     — render sekcji features
│           ├── TestimonialsSection.jsx — render sekcji testimonials
│           ├── CtaSection.jsx          — render sekcji CTA
│           ├── FormSection.jsx         — render + submit formularza lead capture
│           ├── FaqSection.jsx          — render FAQ z accordion
│           ├── TextSection.jsx         — render tekstu
│           └── VideoSection.jsx        — embed YouTube/Vimeo
└── hooks/
    ├── useLandingPage.js       — pobieranie i aktualizacja LP
    └── useLeadCapture.js       — logika submit formularza publicznego
```

---

### 5.2 `Pages/LandingPages/Index.jsx`

```
Cel: Lista wszystkich LP w panelu — przegląd i zarządzanie

Props (z kontrolera):
- landingPages: {data: LandingPage[], meta: PaginationMeta}
- stats: {published: number, draft: number, archived: number}

Layout: AuthenticatedLayout (istniejący lub Filament shell)

Sekcje:
1. Header: "Landing Pages" + stats (published/draft badges) + CTA "Create New"
2. Filter tabs: All | Published | Draft | Archived
3. Lista kart LP:
   Każda karta: title, slug (klinkalna), status badge, views, conversions, CVR %, data,
   akcje: Edit (→ /landing-pages/{id}/edit), Preview (→ /lp/{slug} nowa karta),
          Publish/Unpublish (POST), Delete

UX:
- Empty state: "Start with your first landing page" + button Create
- Filtry przez Inertia preserveState (bez przeładowania strony)
- Koli statusu: draft=szary, published=zielony, archived=żółty
```

---

### 5.3 `Pages/LandingPages/Create.jsx`

```
Cel: Krok 1 — wybór szablonu i podstawowe dane LP

Props:
- templates: Template[]  — z config/landing_pages.php
- languages: Record<string, string>

Sekcje formularza:
1. Template picker: siatka kart TemplateCard (3 opcje: Lead Magnet, Services, Portfolio)
   Każda karta: ikona, nazwa, opis, lista sekcji które zawiera → "Blank page" jako 4. opcja
2. Podstawowe dane:
   - title: wymagane
   - language: select (EN/PL/PT) — default z Business.locale
   - slug: opcjonalne, z podpowiedzią "auto-generated from title"
   - conversion_goal: select opcjonalne

Submit: POST /landing-pages → redirect do /landing-pages/{id}/edit
CTA: "Create & Start Editing"

UX:
- Selekcja szablonu przez kliknięcie karty (nie radio button)
- Podgląd sekcji szablonu po hoveru (lista)
- Walidacja inline
```

---

### 5.4 `Pages/LandingPages/Edit.jsx`

```
Cel: Główna strona edycji LP — edytor sekcji i ustawień

Props:
- landingPage: LandingPage z sections[]
- sectionTypes: string[]
- templates: Template[]

Layout: pełnoekranowy edytor (2 panele)

Struktura UI:
┌─────────────────────────────────────────────────────────┐
│ Topbar: "← Back" | title LP | Status badge | [Publish]  │
├─────────────────┬───────────────────────────────────────┤
│  PANEL L (30%)  │  PANEL R (70%)                        │
│  Lista sekcji   │  Edytor aktywnej sekcji               │
│  + Add Section  │  (SectionEditor)                      │
│  (drag-to-sort) │                                       │
├─────────────────┴───────────────────────────────────────┤
│ Footer: SEO settings (collapsed) | Preview | Save       │
└─────────────────────────────────────────────────────────┘

Panel lewy (SectionList):
- Drag-and-drop listy sekcji (react-beautiful-dnd lub @dnd-kit/core)
- Ikona + label type, toggle visible, usuń
- "+ Add section" → modal wyboru type + inicjalizacja default content
- Kliknięcie sekcji → otwiera edytor w panelu prawym

Panel prawy (SectionEditor):
- Dynamiczny formularz zależny od type aktywnej sekcji
- Dla 'hero': pola headline, subheadline, cta_text, cta_url, image upload
- Dla 'features': lista items (add/remove), każdy item: icon(select), title, description
- Dla 'form': checkboxy fields (name/email/phone/message), required toggle, cta text, success message
- Dla 'video': input URL (z walidacją domeny)
- Itp.

UX:
- Auto-save przy każdej zmianie (debounce 800ms) LUB ręczny Save
- Rekomendacja MVP: ręczny Save (prostsze) + debouncowany preview refresh
- "Preview LP" button → otwiera /lp/{slug} w nowej karcie
- "Publish" button → POST /landing-pages/{id}/publish z potwierdzeniem
- Walidacja: "Cannot publish — missing form section" jeśli brak sekcji form

Stan lokalny:
- activeSectionId: number | null
- sections: LandingPageSection[]  — lokalny state, sync z backendem przy Save
- isDirty: boolean — niezapisane zmiany
- isPublishing: boolean
```

---

### 5.5 `Pages/LandingPage/Show.jsx` (publiczny widok)

```
Cel: Publiczne renderowanie LP — bez Auth, dla potencjalnych leadów

Props (z PublicLandingPageController):
- landingPage: {slug, meta_title, meta_description, language}
- sections: {type: string, content: object, settings: object}[]
- csrfToken: string

Layout: MinimalPublicLayout (bez nawigacji, bez footer agencji)
  — tylko LP content + ewentualnie powered-by badge (v1.1)

SEO (przez Inertia Head):
<title>{landingPage.meta_title}</title>
<meta name="description" content={landingPage.meta_description} />
<meta property="og:title" content={landingPage.meta_title} />
<meta property="og:image" content={landingPage.og_image} />

Renderowanie sekcji:
- Map przez sections[]
- Renderuj odpowiedni komponent z PublicSections/:
  hero → HeroSection, features → FeaturesSection, form → FormSection, itd.
- Każda sekcja dostaje props: content (dane), settings (kolory, padding)

UX:
- Smooth scroll do sekcji #form przy kliknięciu CTA (anchory)
- Sekcje z settings.visible = false NIE renderowane (backend filtruje, frontend też)
- Loading state: skeleton loader przy pierwszym load (Inertia SSR lub CSR)
- Mobile-first: sekcje responsywne (sm: md: lg:)
- Dark/light : sekcje z settings.background = 'dark' używają ciemnego motywu
```

---

### 5.6 `Components/LandingPage/PublicSections/FormSection.jsx`

```
Cel: Formularz lead capture widoczny na publicznej LP — najważniejszy komponent

Props:
- content: {headline, subheadline, fields[], required[], cta_text, success_message, redirect_url}
- settings: {background, padding}
- slug: string  — do submit endpoint POST /lp/{slug}/submit
- csrfToken: string

State:
- formData: {name, email, phone, message, honeypot: ''}
- isSubmitting: boolean
- isSubmitted: boolean
- errors: Record<string, string>

Pola formularza (renderowane per content.fields[]):
- name:    TextInput type="text" + GDPR label opcjonalnie
- email:   TextInput type="email"
- phone:   TextInput type="tel"
- message: Textarea

Dodatkowe:
- Pole honeypot: <input name="honeypot" style="display:none" tabIndex="-1" autoComplete="off" />
  Boty wypełniają ukryte pola → backend odrzuca jeśli honeypot != ""

Submit flow:
1. Client-side walidacja required fields
2. Axios.post(`/lp/${slug}/submit`, formData)
3. On success: isSubmitted = true → pokazuj success message
   Jeśli response.redirect_url → window.location.href = redirect_url
4. On error 422: wyświetl errors inline
5. On error 429: "Too many submissions. Try again in an hour."
6. On error 500: "Something went wrong."

GDPR:
- Checkbox "I agree to contact" (required, walidacja client-side)
- Link do polityki prywatności (konfigurowalne w v1.1 lub hardcoded URL w MVP)

Security:
- CSRF token w header: axios.defaults.headers['X-CSRF-TOKEN'] = csrfToken
- Honeypot pole (sprawdzane server-side)
- Rate limiting 3/60min (server-side throttle middleware)
```

---

### 5.7 Typy TypeScript

```typescript
// resources/js/types/landingPage.ts

export interface LandingPage {
  id: number;
  business_id: string;      // ULID
  title: string;
  slug: string;
  status: 'draft' | 'published' | 'archived';
  template_key: string | null;
  language: 'en' | 'pl' | 'pt';
  meta_title: string | null;
  meta_description: string | null;
  og_image_path: string | null;
  conversion_goal: string | null;
  views_count: number;
  conversions_count: number;
  ai_generated: boolean;
  published_at: string | null;
  public_url: string;
  conversion_rate: number;
  created_at: string;
  updated_at: string;
  sections?: LandingPageSection[];
}

export interface LandingPageSection {
  id: number;
  landing_page_id: number;
  type: SectionType;
  order: number;
  content: SectionContent;
  settings: SectionSettings;
  is_visible: boolean;
}

export type SectionType = 'hero' | 'features' | 'testimonials' | 'cta' | 'form' | 'faq' | 'text' | 'video';

export interface SectionSettings {
  background?: 'white' | 'dark' | 'primary' | 'gradient';
  padding?: 'sm' | 'md' | 'lg';
  visible?: boolean;
}

// Content per sekcja type
export interface HeroContent {
  headline: string;
  subheadline: string;
  cta_text: string;
  cta_url: string;
  image_path?: string;
}

export interface FormContent {
  headline: string;
  subheadline?: string;
  fields: Array<'name' | 'email' | 'phone' | 'message'>;
  required: Array<'name' | 'email' | 'phone' | 'message'>;
  cta_text: string;
  success_message: string;
  redirect_url?: string | null;
}

export interface LandingPageTemplate {
  name: string;
  description: string;
  sections: SectionType[];
}

export interface LandingPageFormData {
  title: string;
  slug?: string;
  language: string;
  template_key?: string;
  conversion_goal?: string;
  meta_title?: string;
  meta_description?: string;
}

export interface LeadCaptureFormData {
  name: string;
  email: string;
  phone?: string;
  message?: string;
  honeypot: '';         // zawsze puste
  utm_source?: string;
  utm_medium?: string;
  utm_campaign?: string;
}
```

---

### 5.8 Hook `useLeadCapture.js`

```javascript
// resources/js/hooks/useLeadCapture.js

Eksportuje:
{
  formData,              — state formularza
  setField(name, value), — setter
  errors,                — błędy walidacji {name: msg}
  isSubmitting,          — boolean
  isSubmitted,           — boolean (sukces)
  successMessage,        — tekst po submit
  submitForm(slug, csrfToken) — główna funkcja submit
}

Logika submitForm:
1. setIsSubmitting(true)
2. Odczytaj UTM z window.location.search (URLSearchParams)
3. POST /lp/{slug}/submit z formData + utm params + X-CSRF-TOKEN header
4. Success → setIsSubmitted(true), setSuccessMessage(response.data.message)
   Jeśli redirect_url → setTimeout(() => window.location.href = redirectUrl, 2000)
5. Error 422 → setErrors(response.data.errors)
6. Error 429 → setErrors({_general: 'Too many submissions...'})
7. Error 5xx → setErrors({_general: 'Something went wrong...'})
8. finally → setIsSubmitting(false)
```

---

## 6. API — endpointy

```
Prywatne (auth + has.business):
GET    /landing-pages                       → LandingPageController::index()
GET    /landing-pages/create                → LandingPageController::create()
POST   /landing-pages                       → LandingPageController::store()
GET    /landing-pages/{lp}                  → LandingPageController::show()
GET    /landing-pages/{lp}/edit             → LandingPageController::edit()
PATCH  /landing-pages/{lp}                  → LandingPageController::update()
DELETE /landing-pages/{lp}                  → LandingPageController::destroy()
POST   /landing-pages/{lp}/publish          → LandingPageController::publish()
POST   /landing-pages/{lp}/unpublish        → LandingPageController::unpublish()

POST   /landing-pages/{lp}/sections         → LandingPageSectionController::store()
PATCH  /landing-pages/{lp}/sections/{sec}   → LandingPageSectionController::update()
DELETE /landing-pages/{lp}/sections/{sec}   → LandingPageSectionController::destroy()
POST   /landing-pages/{lp}/sections/reorder → LandingPageSectionController::reorder()

Publiczne (bez auth):
GET    /lp/{slug}                           → PublicLandingPageController::show()
POST   /lp/{slug}/submit                    → PublicLandingPageController::submit()
       [middleware: throttle:3,60]
```

**Bezpieczeństwo API:**
- Endpoint `POST /lp/{slug}/submit`: nie wymaga auth (publiczny), ale ma rate limiting 3/60min per IP
- Nie zwraca wewnętrznych ID (business_id, landing_page_id) w odpowiedzi publicznej
- Email validation `:rfc,dns` blokuje nieistniejące domeny email
- CSRF token wymagany przez Laravel dla POST requests (meta tag w HTML)
- Honeypot field: server-side check `$request->honeypot === ''`
- Odpowiedzi błędów: ogólne komunikaty, bez stack trace, bez ujawniania struktury DB

---

## 7. Workflow użytkownika

### 7.1 Tworzenie i publikacja LP (pełny flow MVP)

```
1. Admin loguje się do /admin (Filament)
2. Kliknie "Landing Pages" w grupie nawigacji "Marketing"
3. "Create New Landing Page" → redirect Inertia /landing-pages/create
4. Wybiera szablon "Services" (3 kliknięcia: szablon → tytuł → Create)
5. Redirect do /landing-pages/{id}/edit
6. Panel lewy: lista sekcji z szablonu (hero, features, testimonials, cta, form, faq)
7. Klika na "Hero" → Panel prawy: edytor hero section
   - Wpisuje headline: "Tworzymy strony które sprzedają"
   - Wpisuje subheadline + CTA text + CTA URL "#form"
   - Save → PATCH /landing-pages/{id}/sections/{hero_id}
8. Edytuje pozostałe sekcje analogicznie (5-10 min)
9. Klika "Preview" → otwiera /lp/{slug} w nowej karcie (status draft, dostępna do preview)
   UWAGA: publiczny widok draftu → tylko dla zalogowanego usera (sprawdzić auth)
   LUB: draft dostępny zawsze ale nieindeksowany → prostsze w MVP
10. Wraca do edytora → klika "Publish" → potwierdzenie modal
11. POST /landing-pages/{id}/publish → event(LandingPagePublished)
12. Flash: "Landing Page published! URL: /lp/moja-agencja-uslugi"
13. LP widoczna pod /lp/{slug} dla wszystkich
```

---

### 7.2 Lead capture (z perspektywy odwiedzającego)

```
1. Odwiedzający wchodzi na /lp/moja-agencja-uslugi
2. Widzi LP: sekcje hero, features, testimonials, cta, form
3. Wypełnia formularz: name + email + phone
4. Checkbox GDPR ✓
5. Klika "Wyślij wiadomość" → Axios POST /lp/moja-agencja-uslugi/submit
6. Server:
   a. Walidacja LeadCaptureRequest
   b. Rate limiting: max 3/IP/godz
   c. Honeypot check
   d. LeadCaptureService::capture() → CreateLeadAction::execute()
     → tworzy Client (firstOrCreate by email)
     → tworzy Lead (stage = PipelineStage order=1, source='landing_page', landing_page_id=X)
     → tworzy LeadActivity
     → wysyła NewLeadMail do admina
     → event(LeadCaptured) → AutomationEventListener (trigger: lead_created)
   e. LandingPage::increment('conversions_count')
   f. Response: {success: true, message: "Dziękujemy! Skontaktyjemy się wkrótce."}
7. UI: formularz znika → pojawia się success message
8. Admin w Filament widzi nowy lead w Lead Inbox (real-time notification)
9. Lead pojawia się w Pipeline Kanban (kolumna "New Enquiries")
```

---

### 7.3 Edycja opublikowanej LP

```
1. Admin wraca do /landing-pages/{id}/edit
2. Widzi badge "Published" + ostrzeżenie "Changes will be live immediately"
3. Edytuje sekcję → Save (PATCH sekcję) → zmiany widoczne natychmiast na /lp/{slug}
4. NIE ma wersjonowania w MVP — każda zmiana idzie live
5. Jeśli chce zmienić slug → system odmawia (slug immutable po publish)
   → "Cannot change URL of a published page. Unpublish first."
6. Jeśli chce zmienić slug → Unpublish → Edit → Re-publish
```

---

## 8. Integracja z istniejącymi modułami

### 8.1 Integracja z CreateLeadAction (istniejąca)

```
Zmiana minimalna — dodajemy 5 opcjonalnych parametrów:
landing_page_id, utm_source, utm_medium, utm_campaign, utm_content, utm_term

Bez zmian w:
- Client::firstOrCreate() logic
- PipelineStage::orderBy('order')->first() — lead ląduje na pierwszym etapie
- LeadActivity::log() — bez zmian
- NewLeadMail — bez zmian (można rozszerzyć o LP info w v1.1)

Gwarancja backwards compatibility:
Wszystkie nowe pola są NULLABLE z ?? null defaultem → istniejące wywołania (ContactController,
CalculatorLeadController) działają bez żadnych zmian.
```

---

### 8.2 Integracja z Pipeline Kanban (istniejąca)

```
Zero zmian w PipelinePage.php (Filament LiveWire/Inertia page)

Leady z LP pojawiają się automatycznie na PipelinePage bo:
1. Lead::create() w CreateLeadAction przypisuje pipeline_stage_id = first stage
2. PipelinePage pobiera Lead::orderBy...->get() → widzi wszystkie leady
3. Nowy badge w v1.1: wyróżnienie "LP Lead" (landing_page_id IS NOT NULL)

MVP: leady z LP NIE są wizualnie wyróżnione na Kanban → zaplanowane na v1.1
```

---

### 8.3 Integracja z Automation Engine (istniejący)

```
Existing AutomationEventListener nasłuchuje na LandingPage/LeadCaptured?
Status: AutomationEventListener nasłuchuje przez AppServiceProvider→boot()
        Event::subscribe(AutomationEventListener::class)

LeadCaptured event nie jest bezpośrednio nasłuchiwany przez Automation.
JEDNAK: CreateLeadAction tworzy Lead → Eloquent event 'created' na modelu Lead
        → AutomationEventListener reaguje na Eloquent events: lead.created → trigger automations

Konkluzja: Automation Engine automatycznie reaguje na leady z LP BEZ żadnych zmian.
Warunek: musi istnieć AutomationRule z trigger 'lead_created' w bazie (seeder lub UI).
```

---

### 8.4 Integracja z Business / Multi-tenancy

```
LandingPage używa business_id jako hard foreign key.
W MVP (bez GlobalScope):
- LandingPageController → zawsze filtruje przez currentBusiness()->id
- PublicLandingPageController → slug jest globalnie unikalny? NIE! UNIQUE(business_id, slug)
  
PROBLEM: /lp/{slug} jest publiczne. Ten sam slug może istnieć dla 2 różnych business!
ROZWIĄZANIE w MVP: 
  - Ogłosić że slug musi być unikalny globalnie w MVP (dodać UNIQUE(slug) zamiast UNIQUE(business_id, slug))
  - LUB: Endpoint resolvuje slug biorąc PUBLISHED LP z dowolnego business
  
DECYZJA ARCHITEKTONICZNA (MVP):
  - Używamy UNIQUE(slug) — globalnie unikalny slug per platforma
  - Prostsze, bezpieczniejsze, bez potrzeby identyfikacji tenanta na publicznym URL
  - W v1.1 z custom domains problem znika (lp.moja-agencja.pl/{slug} = scoped)
  - Zmiana w storageowym migracji 000004: UNIQUE(slug) zamiast UNIQUE(business_id, slug)
  
>>> ODNOTOWAĆ: to jest kompromis MVP — w production z wieloma tenantami slugi muszą być unikalne
```

---

### 8.5 Integracja z Filament Panel

```
Nowy NavigationGroup 'Marketing' (już zaplanowany w arch-plan):
  - LandingPageResource
  - (w v1.1) CampaignResource
  - (w v1.1) LeadSourceAnalyticsPage

Dashboard widget LandingPageStatsWidget — dodać po istniejących widgetach.

Spójność z istniejącymi Resources:
- LeadResource: dodać kolumnę + filtr 'Landing Page' — patrz 4.10
- Bez zmian w ClientResource, ProjectResource, InvoiceResource etc.
```

---

## 9. Tłumaczenia (i18n)

```
Dodać do plików językowych:

lang/en/lp.php:
- 'created'               => 'Landing page created successfully.',
- 'updated'               => 'Landing page updated.',
- 'deleted'               => 'Landing page deleted.',
- 'published'             => 'Landing page is now live!',
- 'unpublished'           => 'Landing page archived.',
- 'publish_failed'        => 'Cannot publish: your page needs at least one form section.',
- 'slug_immutable'        => 'Cannot change URL of a published page. Unpublish first.',
- 'section_limit'         => 'Maximum 20 sections per page.',
- 'section_type_invalid'  => 'Invalid section type.',

lang/en/lead_capture.php:
- 'success'               => 'Thank you! We\'ll get back to you soon.',
- 'too_many_requests'     => 'Too many submissions. Please try again in an hour.',
- 'error_generic'         => 'Something went wrong. Please try again.',

lang/pl/lp.php: (odpowiedniki PL)
lang/pt/lp.php: (odpowiedniki PT)
lang/pl/lead_capture.php: (odpowiedniki PL)
lang/pt/lead_capture.php: (odpowiedniki PT)
```

---

## 10. Checklist implementacji

### Backend (Sprint 1 — MVP)

- [ ] Migracja `landing_pages` (4/6) — UNIQUE(slug) globalnie
- [ ] Migracja `landing_page_sections` (5/6)
- [ ] Migracja `add_lp_fields_to_leads_table` (6/6)
- [ ] Model `LandingPage` (HasFactory, SoftDeletes, relacje, scopy, akcesory)
- [ ] Model `LandingPageSection` (relacje, stałe TYPES, getDefaultContent)
- [ ] Rozszerzenie `Lead` model (fillable + relacja)
- [ ] Rozszerzenie `CreateLeadAction` (5 nowych nullable params)
- [ ] `LandingPageSlugService` (generate, validate, reserved list)
- [ ] `LandingPageService` (create, update, publish, unpublish, delete, reorder, initFromTemplate)
- [ ] `LandingPageSectionService` (create, update, delete, validateContent, sanitizeHtml)
- [ ] `LeadCaptureService` (capture, trackView)
- [ ] Form Requests: `StoreLandingPageRequest`, `UpdateLandingPageRequest`
- [ ] Form Requests: `StoreSectionRequest`, `UpdateSectionRequest`
- [ ] Form Request: `LeadCaptureRequest` (z honeypot + rate limit throttle)
- [ ] `LandingPageController` (9 metod)
- [ ] `LandingPageSectionController` (4 metody)
- [ ] `PublicLandingPageController` (show + submit — bez auth)
- [ ] `LandingPagePolicy` (viewAny, view, create, update, delete)
- [ ] Rejestracja tras w `routes/web.php`
- [ ] Events: `LandingPagePublished`, `LeadCaptured`
- [ ] `config/landing_pages.php` (szablony, reserved slugs, video whitelist)
- [ ] Aktualizacja `AdminSeeder` (nowe permissions: view/manage/publish_landing_pages)
- [ ] Tłumaczenia: `lang/*/lp.php`, `lang/*/lead_capture.php`

### Filament

- [ ] `LandingPageResource.php` (tabela, form, akcje, filtry)
- [ ] `LandingPageResource/Pages/EditSections.php` (edytor sekcji z Repeater)
- [ ] `LandingPageResource/Pages/ViewLandingPage.php` (statystyki + leady)
- [ ] Rozszerzenie `LeadResource.php` (kolumna + filtr LP)
- [ ] `LandingPageStatsWidget.php` (nowy widget Dashboard)
- [ ] Dodanie grupy 'Marketing' w AdminPanelProvider

### Frontend

- [ ] Typy TypeScript `resources/js/types/landingPage.ts`
- [ ] Hook `useLeadCapture.js`
- [ ] `Pages/LandingPages/Index.jsx`
- [ ] `Pages/LandingPages/Create.jsx` z TemplateCard
- [ ] `Pages/LandingPages/Edit.jsx` z SectionList + SectionEditor
- [ ] `Pages/LandingPages/Show.jsx` (statystyki panel)
- [ ] `Pages/LandingPage/Show.jsx` (publiczny widok LP)
- [ ] `Components/LandingPage/PublicSections/HeroSection.jsx`
- [ ] `Components/LandingPage/PublicSections/FeaturesSection.jsx`
- [ ] `Components/LandingPage/PublicSections/TestimonialsSection.jsx`
- [ ] `Components/LandingPage/PublicSections/CtaSection.jsx`
- [ ] `Components/LandingPage/PublicSections/FormSection.jsx` (KRYTYCZNY)
- [ ] `Components/LandingPage/PublicSections/FaqSection.jsx`
- [ ] `Components/LandingPage/PublicSections/TextSection.jsx`
- [ ] `Components/LandingPage/PublicSections/VideoSection.jsx`
- [ ] `Components/LandingPage/StatusBadge.jsx`
- [ ] `Components/LandingPage/ConversionStats.jsx`

### Testy

- [ ] `tests/Feature/LandingPage/LandingPageCrudTest.php`
  - Tworzenie LP (z szablonem i bez)
  - Autoryzacja (manager może, client nie)
  - Slug unikalność
- [ ] `tests/Feature/LandingPage/LandingPagePublishTest.php`
  - Publikacja z sekcją form → sukces
  - Publikacja bez sekcji form → DomainException
  - Zmiana sluga opublikowanej LP → DomainException
- [ ] `tests/Feature/LandingPage/LeadCaptureTest.php`
  - Submit na opublikowanej LP → Lead tworzy się w DB
  - Submit honeypot wypełniony → odrzucone 422
  - Submit ratelimit przekroczony → 429
  - Submit na nieopublikowanej LP → 404 lub 422
  - Lead ma landing_page_id i utm_source

---

## 11. Ryzyka i decyzje techniczne

| Ryzyko | Poziom | Mitygacja |
|---|---|---|
| Slug LP globalnie unikalny → możliwy konflikt między tenantami przy skalowaniu | HIGH | Dokumentujemy jako dług techniczny; przy multi-tenancy (v1.1) migrujemy do UNIQUE(business_id, slug) + identyfikacja tenanta przez subdomenę |
| content JSON sekcji bez silnej typizacji → brak walidacji przy direct DB edit | MEDIUM | validateContentForType() w serwisie; fronted TypeScript types; Filament form validation per type |
| HTML injection w sekcji type='text' | HIGH | HTMLPurifier lub strip_tags($html, '<b><strong><i><em><p><br><ul><ol><li><a><h2><h3>') —  **KRYTYCZNE** — nigdy renderuj raw user HTML bez sanitizacji |
| Video embed XSS przez zewnętrzny URL | HIGH | Whitelist domen (youtube.com, youtu.be, vimeo.com); video_url musi przejść custom Rule przed zapisem i przy renderze używać embed URL transform (nie raw URL) |
| Rate limiting 3/60min może blokować prawdziwych użytkowników za NAT | LOW | Rozważyć honeypot-only bez rate limit lub podnieść do 10/60min; monitoring spamu przed decyzją |
| SoftDelete LP + ON DELETE SET NULL na leads.landing_page_id → "orphan" lead bez LP | ACCEPTABLE | To zamierzone — lead pozostaje w systemie, tylko traci powiązanie z LP. Historyczne UTM dane zachowane. |
| Filament Repeater jako edytor sekcji — ograniczony UX, trudne reorderowanie | MEDIUM | Dokumentujemy jako tech debt → Inertia/React editor w v1.1 per mvp-plan 4.6; Repeater wystarczy dla MVP |
| PublicLandingPageController bez auth — podatny na scraping | LOW | Publiczne LP mają być publicznie widoczne. Brak PII w odpowiedzi. Rate limit na submit endpoint wystarczy. |
| `PipelineStage::orderBy('order')->first()` bez business_id scope → cross-tenant bugs | HIGH | W MVP (jeden tenant) nie ma problemu. MUST FIX przed zaonboardowaniem 2. tenanta — dodać `->where('business_id', currentBusiness()->id)` |

---

## 12. Decyzje architektoniczne (ADR)

### ADR-001: Slugi LP globalnie unikalne w MVP
**Kontekst:** Publiczny URL `/lp/{slug}` wymaga identyfikacji LP bez dodatkowego kontekstu tenanta.  
**Decyzja:** UNIQUE(slug) globalnie w tabeli `landing_pages` w MVP.  
**Konsekwencje:** Ogranicza równolegle działające tenantów, wymaga migracji przy multi-tenancy.  
**Rewizja:** W v1.1 z custom domains lub subdomenami → UNIQUE(business_id, slug) + routing przez subdomenę.

### ADR-002: Edytor sekcji w Filament (nie dedykowana Inertia/React app)
**Kontekst:** Dedykowany edytor LP to 7-10 dni pracy vs Filament Repeater to 2-3 dni.  
**Decyzja:** Filament Repeater w MVP. Inertia/React editor w v1.1.  
**Konsekwencje:** Gorszy UX w fazie MVP. Szybsze dostarczenie wartości.  
**Rewizja:** v1.1 per mvp-plan.md 4.6.

### ADR-003: views_count i conversions_count denormalizowane na LP
**Kontekst:** Liczenie przez COUNT() queries byłoby wolne przy dużej liczbie leadów.  
**Decyzja:** Denormalizacja przez atomic increment (nie event system).  
**Konsekwencje:** Drobna niespójność przy SoftDelete. Acceptable w MVP.  
**Rewizja:** v1.1 — dedykowana tabela `landing_page_stats` z time series.

---

*Specyfikacja gotowa do implementacji po zatwierdzeniu przez tech lead / product owner.*  
*Następny krok po zatwierdzeniu: `laravel-backend-impl` dla modułu Landing Page Generator (Sprint 1).*
