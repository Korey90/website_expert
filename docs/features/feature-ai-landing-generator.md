# Feature Design: AI Landing Page Generator
**Data:** 2026-03-31
**Status:** AWAITING APPROVAL
**Bazuje na:** `docs/project-analysis.md`, `docs/architecture-plan.md`, `docs/mvp-plan.md`, `docs/landing-pages-analysis.md`, `docs/landing-pages-architecture.md`, `docs/feature-landing-page-generator.md`

---

## Definicja modułu: AI Landing Page Generator

**Cel**: Umożliwić użytkownikowi wygenerowanie draftu landing page na podstawie danych z `BusinessProfile` oraz opcjonalnego opisu kampanii, bez ręcznego budowania sekcji od zera. Generator ma zwracać gotową strukturę JSON strony, którą użytkownik może obejrzeć, poprawić i dopiero wtedy zapisać jako `LandingPage`.

**Bounded Context**: `LandingPages`

**Priorytet MVP**: NICE TO HAVE

**Zależności**:
- `Business` i `currentBusiness()`
- `BusinessProfile` i `BusinessProfileService::getAiContext()`
- istniejące `LandingPage`, `LandingPageSection`, `LandingPageService`, `LandingPageSlugService`
- publiczny runtime `LandingPage/Show.jsx`
- integracja z OpenAI przez `config/services.php`

**Użytkownik**: Admin agencji, manager, właściciel konta SaaS

---

## 1. Cel biznesowy modułu

Moduł rozwiązuje problem pustego startu. Obecny builder landing pages wymaga ręcznego wyboru sekcji i wpisywania treści. AI Generator ma skrócić drogę do pierwszej opublikowanej strony przez:

- wykorzystanie danych marki z `BusinessProfile`,
- przyjęcie krótkiego opisu oferty lub kampanii,
- wygenerowanie spójnej struktury strony,
- przygotowanie CTA, hero, sekcji korzyści, social proof, FAQ i formularza,
- umożliwienie regeneracji całości lub pojedynczych sekcji,
- zachowanie pełnej kontroli użytkownika przed zapisem.

Generator nie publikuje strony automatycznie. Jego zadaniem jest przygotowanie draftu wysokiej jakości, zgodnego z brandem i celem konwersji.

---

## 2. Zakres modułu

### Wchodzi w zakres

- formularz generowania LP z danymi wejściowymi z `BusinessProfile` i promptem użytkownika,
- budowa promptu systemowego i użytkownika do OpenAI,
- generowanie jednego draftu landing page jako ustrukturyzowany JSON,
- walidacja i normalizacja odpowiedzi modelu AI,
- podgląd draftu przed zapisem,
- regeneracja całej strony,
- regeneracja wybranej sekcji,
- zapis draftu jako `LandingPage` + `LandingPageSection`,
- obsługa fallbacków przy brakach w profilu firmy lub błędnej odpowiedzi modelu,
- logowanie prób generacji i błędów technicznych.

### Nie wchodzi w zakres

- automatyczna publikacja LP,
- A/B testing,
- generacja obrazów,
- custom domains,
- wielojęzyczne generowanie jednej LP w wielu językach naraz,
- pełny chat-style editor z historią rozmowy,
- autooptymalizacja konwersji na podstawie statystyk.

---

## 3. Wejście i wyjście modułu

## 3.1 Input użytkownika

Generator przyjmuje dwa źródła wejścia:

1. **Business Profile** jako źródło kontekstu marki:
   - `brand_name`
   - `tagline`
   - `industry`
   - `tone_of_voice`
   - `target_audience`
   - `services`
   - `primary_color`
   - `website_url`
   - `seo_keywords`
   - `language`

2. **Opis użytkownika** jako prompt kampanii:
   - co promujemy,
   - dla kogo,
   - jaki jest główny problem klienta,
   - jaki jest CTA,
   - ewentualny kanał kampanii,
   - opcjonalne ograniczenia tonu i układu.

### Minimalny input wymagany

- `goal` lub `description` od użytkownika,
- dostępne `business_id`,
- przynajmniej częściowy `BusinessProfile`.

Jeżeli profil jest niepełny, generator nie blokuje działania, ale przechodzi na tryb fallback z bardziej generycznymi treściami.

## 3.2 Output generatora

Wyjściem jest **JSON draft landing page**, a nie HTML.

Docelowa struktura:

```json
{
  "title": "Landing page title",
  "slug_suggestion": "landing-page-title",
  "language": "pl",
  "template_key": "services",
  "meta": {
    "meta_title": "...",
    "meta_description": "...",
    "conversion_goal": "book_call"
  },
  "sections": [
    {
      "type": "hero",
      "content": {
        "headline": "...",
        "subheadline": "...",
        "cta_text": "...",
        "cta_url": "#form"
      },
      "settings": {
        "background": "gradient",
        "padding": "lg",
        "visible": true
      }
    },
    {
      "type": "features",
      "content": {
        "headline": "...",
        "items": []
      },
      "settings": {
        "background": "white",
        "padding": "md",
        "visible": true
      }
    },
    {
      "type": "testimonials",
      "content": {
        "headline": "...",
        "items": []
      },
      "settings": {
        "background": "dark",
        "padding": "md",
        "visible": true
      }
    },
    {
      "type": "cta",
      "content": {
        "headline": "...",
        "subheadline": "...",
        "cta_text": "...",
        "cta_url": "#form"
      },
      "settings": {
        "background": "primary",
        "padding": "md",
        "visible": true
      }
    },
    {
      "type": "form",
      "content": {
        "headline": "...",
        "subheadline": "...",
        "fields": ["name", "email", "phone", "message"],
        "required": ["name", "email"],
        "cta_text": "Wyślij",
        "success_message": "Dziękujemy, odezwiemy się wkrótce.",
        "redirect_url": null
      },
      "settings": {
        "background": "white",
        "padding": "md",
        "visible": true
      }
    }
  ]
}
```

### Wymagania dla outputu

- musi zawierać minimum sekcje `hero` i `form`,
- sekcje muszą być zgodne z istniejącym `SECTION_MAP` po stronie React,
- nie może zawierać nieobsługiwanych typów sekcji,
- ma zwracać treść biznesową, a nie markup HTML,
- musi być możliwy do zapisania do istniejących tabel bez dodatkowej translacji domenowej.

---

## 4. Model danych

Moduł powinien rozszerzać istniejący model Landing Pages, a nie go zastępować.

### TABELA: `landing_page_ai_generations`
Cel: log prób generowania i diagnostyka jakości generatora.

Kolumny:
- `id` bigint unsigned, PK, auto-increment
- `business_id` char(26), FK -> `businesses.id`, NOT NULL
- `landing_page_id` bigint unsigned, FK -> `landing_pages.id`, NULLABLE
- `user_id` bigint unsigned, FK -> `users.id`, NULLABLE
- `status` enum/string: `pending|succeeded|failed|partial`
- `source` string, default `business_profile_prompt`
- `model` string, np. `gpt-5.4-mini` albo inny skonfigurowany model
- `input_payload` JSON
- `normalized_payload` JSON, NULLABLE
- `error_code` string, NULLABLE
- `error_message` text, NULLABLE
- `tokens_input` integer unsigned, NULLABLE
- `tokens_output` integer unsigned, NULLABLE
- `duration_ms` integer unsigned, NULLABLE
- `created_at` timestamp
- `updated_at` timestamp

Indeksy:
- `business_id`
- `(business_id, status)`
- `(landing_page_id, created_at)`
- `(user_id, created_at)`

Relacje:
- belongs to: `Business`
- belongs to: `LandingPage`
- belongs to: `User`

Uwagi:
- tabela nie jest krytyczna dla runtime LP, ale jest ważna dla debugowania i limitów planu,
- przechowuje wyłącznie payloady techniczne; nie służy do wersjonowania finalnej strony.

### TABELA: `landing_page_generation_variants`
Cel: tymczasowe drafty wygenerowane przez AI przed zapisem do `landing_pages`.

Kolumny:
- `id` bigint unsigned, PK, auto-increment
- `business_id` char(26), FK -> `businesses.id`, NOT NULL
- `generation_id` bigint unsigned, FK -> `landing_page_ai_generations.id`, NOT NULL
- `user_id` bigint unsigned, FK -> `users.id`, NOT NULL
- `title` varchar(255), NOT NULL
- `slug_suggestion` varchar(100), NULLABLE
- `language` varchar(5), NOT NULL
- `template_key` varchar(50), NULLABLE
- `meta` JSON
- `sections` JSON
- `is_saved` boolean default false
- `expires_at` timestamp, NULLABLE
- `created_at` timestamp
- `updated_at` timestamp

Indeksy:
- `business_id`
- `(generation_id, user_id)`
- `(business_id, is_saved)`
- `expires_at`

Relacje:
- belongs to: `Business`
- belongs to: `LandingPageAiGeneration`
- belongs to: `User`

Uwagi:
- ta tabela oddziela etap generacji od trwałego zapisu `LandingPage`,
- pozwala użytkownikowi obejrzeć wynik, poprawić go w UI i dopiero wtedy zapisać,
- można czyścić rekordy niezapisane przez job housekeeping, np. po 7 dniach.

### Rozszerzenie istniejącej tabeli `landing_pages`

Nowe pola opcjonalne:
- `business_profile_snapshot_id` bigint unsigned, NULLABLE
- `ai_generation_source` varchar(50), NULLABLE
- `current_generation_id` bigint unsigned, NULLABLE

Cel:
- zachowanie powiązania z wygenerowanym draftem,
- śledzenie źródła strony: `manual|business_profile|prompt|clone|regenerate_section`.

---

## 5. Backend Laravel

## 5.1 Modele Eloquent

### `app/Models/LandingPageAiGeneration.php`

Traits:
- `BelongsToTenant`
- `HasFactory`

Fillable:
- `business_id`
- `landing_page_id`
- `user_id`
- `status`
- `source`
- `model`
- `input_payload`
- `normalized_payload`
- `error_code`
- `error_message`
- `tokens_input`
- `tokens_output`
- `duration_ms`

Casts:
- `input_payload` => `array`
- `normalized_payload` => `array`

Relacje:
- `business(): BelongsTo`
- `landingPage(): BelongsTo`
- `user(): BelongsTo`
- `variants(): HasMany`

Scopes:
- `scopeSucceeded($query)`
- `scopeFailed($query)`
- `scopeRecent($query)`

### `app/Models/LandingPageGenerationVariant.php`

Traits:
- `BelongsToTenant`
- `HasFactory`

Fillable:
- `business_id`
- `generation_id`
- `user_id`
- `title`
- `slug_suggestion`
- `language`
- `template_key`
- `meta`
- `sections`
- `is_saved`
- `expires_at`

Casts:
- `meta` => `array`
- `sections` => `array`
- `is_saved` => `boolean`
- `expires_at` => `datetime`

Relacje:
- `business(): BelongsTo`
- `generation(): BelongsTo`
- `user(): BelongsTo`

Scopes:
- `scopeUnsaved($query)`
- `scopeNotExpired($query)`

---

## 5.2 Serwisy

### SERWIS: `app/Services/LandingPage/GenerateLandingService.php`

Odpowiedzialność: orkiestracja pełnego procesu generacji LP od inputu użytkownika do znormalizowanego draftu.

Metody publiczne:

- `generate(Business $business, User $user, array $data): LandingPageGenerationVariant`
  Parametry:
  - dane formularza generatora,
  - `Business`,
  - `User` uruchamiający generację.
  Zwraca:
  - zapisany wariant draftu.
  Logika:
  - pobiera AI context z `BusinessProfileService`,
  - buduje payload wejściowy,
  - tworzy rekord `LandingPageAiGeneration` ze statusem `pending`,
  - wywołuje klienta OpenAI,
  - normalizuje odpowiedź do kontraktu JSON,
  - uruchamia walidację struktury,
  - zapisuje wariant draftu,
  - oznacza generację jako `succeeded` albo `partial`.

- `regenerateSection(LandingPageGenerationVariant $variant, string $sectionType, array $data): LandingPageGenerationVariant`
  Logika:
  - buduje prompt tylko dla jednej sekcji,
  - zachowuje pozostałe sekcje bez zmian,
  - podmienia wskazaną sekcję po walidacji.

- `saveAsLandingPage(LandingPageGenerationVariant $variant, Business $business, User $user): LandingPage`
  Logika:
  - generuje finalny slug przez `LandingPageSlugService`,
  - zapisuje rekord `LandingPage`,
  - zapisuje sekcje do `landing_page_sections`,
  - ustawia `ai_generated = true`,
  - linkuje `current_generation_id`,
  - oznacza wariant jako `is_saved = true`.

Zależy od:
- `BusinessProfileService`
- `LandingPageSlugService`
- `LandingPageService`
- `OpenAiLandingPromptBuilder`
- `LandingPageJsonNormalizer`
- `LandingPageJsonSchemaValidator`

### SERWIS: `app/Services/LandingPage/OpenAiLandingPromptBuilder.php`

Odpowiedzialność: budowanie bezpiecznego, deterministycznego promptu systemowego i użytkownika.

Metody publiczne:
- `buildSystemPrompt(array $context): string`
- `buildUserPrompt(array $input): string`
- `buildSectionRegenerationPrompt(array $context, string $sectionType, array $existingSections, array $input): string`

Wymagania:
- model ma dostać precyzyjny kontrakt JSON,
- prompt musi zakazać zwracania markdown i HTML spoza dozwolonych pól,
- prompt musi wymuszać sekcję `form` oraz zgodność z istniejącymi typami komponentów.

### SERWIS: `app/Services/LandingPage/OpenAiLandingClient.php`

Odpowiedzialność: cienka warstwa integracji z zewnętrznym API OpenAI.

Metody publiczne:
- `generateStructuredLanding(array $payload): array`
- `regenerateSection(array $payload): array`

Wymagania:
- timeout i retry,
- obsługa limitów API,
- zwracanie metadanych zużycia tokenów,
- centralny mapping wyjątków technicznych.

### SERWIS: `app/Services/LandingPage/LandingPageJsonNormalizer.php`

Odpowiedzialność: normalizacja odpowiedzi modelu do kontraktu akceptowanego przez system.

Metody publiczne:
- `normalize(array $raw): array`

Logika:
- usuwa nieobsługiwane pola,
- dokłada brakujące `settings`,
- nadaje domyślne `padding`, `background`, `visible`,
- porządkuje sekcje w sensownej kolejności,
- zamienia zbyt długie pola na bezpieczne skróty,
- wstawia fallback `form` jeśli model jej nie zwróci.

### SERWIS: `app/Services/LandingPage/LandingPageJsonSchemaValidator.php`

Odpowiedzialność: walidacja semantyczna draftu przed pokazaniem użytkownikowi albo zapisem.

Metody publiczne:
- `validateDraft(array $payload): void`
- `validateSection(string $type, array $content, array $settings = []): void`

Walidacja obejmuje:
- dozwolone typy sekcji,
- minimalny zestaw sekcji,
- maksymalną liczbę sekcji,
- poprawność list `fields` i `required` dla formularza,
- brak pustych headline w krytycznych sekcjach,
- limit długości tekstów.

---

## 5.3 Form Requests

### REQUEST: `app/Http/Requests/LandingPage/GenerateLandingRequest.php`

Reguły:
- `goal`: `required|string|in:book_call,contact,download,quote,signup`
- `description`: `nullable|string|max:5000`
- `campaign_name`: `nullable|string|max:255`
- `target_audience_override`: `nullable|string|max:1000`
- `offer_summary`: `nullable|string|max:2000`
- `preferred_language`: `nullable|string|in:pl,en,pt`
- `template_key`: `nullable|string|in:services,lead_magnet,portfolio`
- `include_sections`: `nullable|array|max:8`
- `include_sections.*`: `string|in:hero,features,testimonials,cta,faq,form,text,video`

Autoryzacja:
- użytkownik musi mieć aktywny `currentBusiness()`,
- uprawnienie `landing-pages.create` albo `landing-pages.generate-ai`.

### REQUEST: `app/Http/Requests/LandingPage/RegenerateLandingSectionRequest.php`

Reguły:
- `section_type`: `required|string|in:hero,features,testimonials,cta,faq,form,text,video`
- `instruction`: `nullable|string|max:2000`

Autoryzacja:
- jak wyżej.

### REQUEST: `app/Http/Requests/LandingPage/SaveGeneratedLandingRequest.php`

Reguły:
- `variant_id`: `required|integer|exists:landing_page_generation_variants,id`
- `title`: `required|string|max:255`
- `slug`: `nullable|string|max:100|regex:/^[a-z0-9-]+$/`
- `meta_title`: `nullable|string|max:160`
- `meta_description`: `nullable|string|max:320`
- `sections`: `required|array|min:2|max:20`

Autoryzacja:
- wariant musi należeć do `currentBusiness()` i bieżącego użytkownika albo business.

---

## 5.4 Kontroler

### KONTROLER: `app/Http/Controllers/LandingPage/AiLandingGeneratorController.php`

Trasy:

```php
Route::prefix('landing-pages/ai')
    ->middleware(['auth', 'verified', 'has.business'])
    ->group(function () {
        Route::get('/create', [AiLandingGeneratorController::class, 'create'])
            ->name('landing-pages.ai.create');
        Route::post('/generate', [AiLandingGeneratorController::class, 'generate'])
            ->name('landing-pages.ai.generate');
        Route::post('/variants/{variant}/regenerate-section', [AiLandingGeneratorController::class, 'regenerateSection'])
            ->name('landing-pages.ai.regenerate-section');
        Route::post('/variants/{variant}/save', [AiLandingGeneratorController::class, 'save'])
            ->name('landing-pages.ai.save');
    });
```

Metody:
- `create()` -> `Inertia::render('LandingPages/AiGenerator/Create')`
- `generate()` -> deleguje do `GenerateLandingService`, zwraca props draftu do preview
- `regenerateSection()` -> deleguje do serwisu, zwraca odświeżony wariant
- `save()` -> zapisuje draft jako `LandingPage`, redirect do edycji LP

Kontroler ma pozostać cienki. Bez prompt engineeringu i bez logiki walidacji schematu.

---

## 5.5 Uprawnienia i polityki

### UPRAWNIENIA (Spatie)

- `landing-pages.view-any`
- `landing-pages.view`
- `landing-pages.create`
- `landing-pages.update`
- `landing-pages.delete`
- `landing-pages.generate-ai`

### ROLE z dostępem

- `admin`: wszystkie
- `manager`: `view-any`, `view`, `create`, `update`, `generate-ai`
- `viewer`: `view-any`, `view`

### POLITYKI

- istniejąca `LandingPagePolicy` powinna zostać rozszerzona o zdolność `generateAi(User $user)`
- dostęp tylko w obrębie `currentBusiness()`

---

## 5.6 Zdarzenia i joby

### EVENT: `LandingPageAiGenerated`

Emitowany po poprawnym wygenerowaniu draftu.

Payload:
- `Business`
- `User`
- `LandingPageAiGeneration`
- `LandingPageGenerationVariant`

Zastosowanie:
- telemetry,
- przyszłe limity planów,
- analityka jakości generatora.

### JOB: `CleanupExpiredLandingGenerationVariantsJob`

Odpowiedzialność:
- usuwa lub archiwizuje niesaved drafty po `expires_at`,
- czyści stare payloady pomocnicze.

### JOB: `WarmGeneratedLandingPreviewJob` opcjonalnie

Odpowiedzialność:
- przygotowanie lekkiego cache preview po generacji, jeśli preview okaże się kosztowny.

---

## 6. Frontend

Projekt używa JSX, nie TypeScript. Mimo tego warto zachować kontrakty danych w stylu typów domenowych.

## 6.1 Kontrakty danych frontendowych

Plik docelowy:
- `resources/js/types/landing-page-ai.js` albo lokalny kontrakt w komponentach.

Struktury:
- `AiLandingGeneratorForm`
- `AiLandingDraftVariant`
- `AiLandingSection`
- `AiLandingMeta`

Pola krytyczne:
- `goal`
- `description`
- `campaign_name`
- `preferred_language`
- `template_key`
- `sections[]`
- `meta`

## 6.2 Strony Inertia

### `resources/js/Pages/LandingPages/AiGenerator/Create.jsx`

Odpowiedzialność:
- formularz wejściowy generatora,
- podgląd kompletności `BusinessProfile`,
- wybór celu konwersji,
- wpisanie promptu kampanii,
- uruchomienie generacji,
- obsługa stanów `idle/loading/error/success`.

Sekcje UI:
- panel lewy: formularz wejściowy,
- panel prawy: karta `Brand context` z podglądem danych firmy,
- ostrzeżenia o brakujących danych w profilu,
- CTA `Generate draft`.

### `resources/js/Pages/LandingPages/AiGenerator/Preview.jsx`

Odpowiedzialność:
- renderowanie draftu LP jeszcze przed zapisem,
- edycja podstawowych pól inline,
- akcje `Regenerate all`, `Regenerate section`, `Save as draft`, `Discard`.

Renderowanie:
- wykorzystać istniejące komponenty publiczne z `resources/js/Components/LandingPage/PublicSection/`,
- preview powinien używać tego samego `SECTION_MAP`, żeby ograniczyć rozjazd między draftem i runtime.

## 6.3 Komponenty React

### `resources/js/Components/LandingPage/AiGeneratorForm.jsx`

Pola:
- cel LP,
- prompt użytkownika,
- opcjonalny opis oferty,
- wybór szablonu,
- wybór języka,
- przełącznik sekcji opcjonalnych.

### `resources/js/Components/LandingPage/AiDraftPreview.jsx`

Pokazuje:
- tytuł,
- SEO meta,
- listę sekcji,
- status zgodności z walidacją,
- przyciski akcji.

### `resources/js/Components/LandingPage/AiRegenerateSectionDialog.jsx`

Pozwala podać krótką instrukcję typu:
- „napisz bardziej konkretnie”,
- „skróć hero”,
- „zmień CTA na konsultację”,
- „użyj tonu bardziej premium”.

### `resources/js/Components/LandingPage/BusinessProfileCompletenessCard.jsx`

Pokazuje:
- procent kompletności,
- brakujące pola,
- link do uzupełnienia profilu firmy.

## 6.4 Stan i integracja

Rekomendacja:
- użyć `useForm` z Inertia dla głównego formularza,
- lokalny stan preview trzymać w komponencie strony,
- bez dodatkowego global store na starcie,
- regenerać sekcje asynchronicznie przez `axios` lub `router.post` z częściowym odświeżeniem propsów.

---

## 7. Workflow użytkownika

1. Użytkownik wchodzi do modułu AI Generator.
2. System pokazuje dane z `BusinessProfile` i sygnalizuje braki.
3. Użytkownik wpisuje opis kampanii lub oferty.
4. Użytkownik wybiera cel konwersji i opcjonalny template.
5. System wysyła żądanie do generatora.
6. Backend pobiera AI context i buduje prompt.
7. OpenAI zwraca JSON draftu.
8. Backend normalizuje i waliduje draft.
9. UI pokazuje preview strony.
10. Użytkownik:
    - zapisuje draft bez zmian,
    - poprawia treść ręcznie,
    - regeneruje wybraną sekcję,
    - odrzuca wynik i generuje ponownie.
11. Po zapisie tworzony jest standardowy `LandingPage` w statusie `draft`.
12. Dalsza edycja i publikacja odbywa się już w istniejącym builderze LP.

Kluczowa decyzja architektoniczna: AI Generator nie zastępuje edytora LP. Jest warstwą szybkiego startu przed istniejącym flow edycyjnym.

---

## 8. UX i wymagania produktowe

### Szybkość

- użytkownik ma dostać pierwszy wynik w czasie docelowym 10-20 sekund,
- jeśli czas przekroczy 20 sekund, UI powinno pokazać krok postępu i komunikat, że trwa generowanie,
- po timeout nie wolno tracić formularza wejściowego.

### Edycja przed zapisem

- wynik musi być edytowalny przed trwałym zapisem,
- użytkownik musi móc zmienić tytuł, meta dane i treść sekcji,
- zapis bez preview jest zabroniony.

### Jakość treści

- treść ma być konkretna, sprzedażowa i zgodna z branżą,
- generator nie może tworzyć abstrakcyjnych sloganów bez odniesienia do oferty,
- social proof bez realnych danych powinien używać neutralnych placeholderów albo zostać zastąpiony sekcją `features`.

### Przewidywalność

- wynik ma być oparty o stały kontrakt JSON,
- układ sekcji ma być ograniczony do wspieranego zestawu,
- nie wolno wypychać do runtime sekcji, których frontend nie umie renderować.

---

## 9. Edge cases i fallbacki

### Brak pełnego `BusinessProfile`

Zachowanie:
- generator działa,
- używa dostępnych pól,
- brakujące fragmenty uzupełnia generycznym, ale poprawnym copy,
- UI wyświetla ostrzeżenie i sugeruje uzupełnienie profilu.

### Brak `services` lub `target_audience`

Zachowanie:
- prompt uwzględnia opis ręczny użytkownika jako główne źródło treści,
- sekcja `features` bazuje bardziej na problemach/korzyściach niż na literalnej liście usług.

### Model zwróci błędny JSON

Zachowanie:
- backend próbuje normalizacji,
- jeśli nadal brak zgodności, oznacza generację jako `failed`,
- UI dostaje czytelny komunikat i możliwość ponowienia próby.

### Model pominie sekcję `form`

Zachowanie:
- normalizer automatycznie dokłada standardową sekcję `form`,
- `cta_url` w hero/cta jest mapowane do `#form`.

### Model zwróci zmyślone testimoniale

Zachowanie:
- w trybie bez danych referencyjnych generator powinien:
  - albo pominąć `testimonials`,
  - albo użyć sekcji `features` lub `faq`,
  - albo zastosować neutralny blok social proof bez przypisywania wypowiedzi konkretnym osobom.

### OpenAI API niedostępne lub limit przekroczony

Zachowanie:
- zapis błędu do `landing_page_ai_generations`,
- komunikat w UI bez trace technicznego,
- możliwość przejścia do klasycznego buildera LP.

### Zbyt długi input użytkownika

Zachowanie:
- walidacja requestu ogranicza długość,
- prompt builder dodatkowo skraca pola do bezpiecznych limitów.

---

## 10. Integracja z istniejącym modułem Landing Pages

Generator ma integrować się z już istniejącymi elementami repozytorium:

- `LandingPageService` pozostaje głównym serwisem zapisu i publikacji LP,
- `LandingPageSlugService` generuje finalny slug,
- `LandingPage/Show.jsx` i publiczne sekcje są źródłem prawdy dla preview,
- `capture_fields` i sekcja `form` muszą być budowane zgodnie z aktualnym flow lead capture,
- `ai_generated` w `landing_pages` staje się realnym wskaźnikiem pochodzenia treści.

Rekomendacja implementacyjna:
- nie rozszerzać nadmiernie `LandingPageService` o prompt engineering,
- logikę AI wydzielić do dedykowanego `GenerateLandingService` i pomocniczych serwisów,
- zapisywanie finalnego draftu wykonywać przez spójną metodę aplikacyjną, nie przez bezpośrednie tworzenie modeli w kontrolerze.

---

## 11. Testy

### Feature tests

- generacja draftu z poprawnym `BusinessProfile`
- generacja draftu przy niepełnym profilu
- błąd API OpenAI i czytelna odpowiedź dla UI
- zapis wariantu jako `LandingPage`
- regeneracja pojedynczej sekcji
- blokada dostępu bez `currentBusiness()`
- blokada dostępu bez uprawnienia `landing-pages.generate-ai`

### Unit tests

- `OpenAiLandingPromptBuilder`
- `LandingPageJsonNormalizer`
- `LandingPageJsonSchemaValidator`
- mapowanie fallbacków dla sekcji `form`

### Contract tests

- odpowiedź modelu AI musi przejść przez schema validator,
- preview payload musi być zgodny z publicznym rendererem sekcji.

---

## 12. Checklist implementacji

1. Dodać migracje `landing_page_ai_generations` i `landing_page_generation_variants`.
2. Dodać opcjonalne pola śledzące generację do `landing_pages`.
3. Dodać modele Eloquent i relacje.
4. Dodać `GenerateLandingService`, prompt builder, client API, normalizer i schema validator.
5. Dodać wpisy do `config/services.php` i `.env` dla OpenAI.
6. Dodać Form Requests i kontroler Inertia.
7. Rozszerzyć uprawnienia Spatie i politykę LP.
8. Dodać strony Inertia `Create` i `Preview` dla generatora.
9. Oprzeć preview o istniejące komponenty sekcji publicznych.
10. Dodać możliwość zapisania draftu do istniejącego modułu LP.
11. Dodać testy Feature i Unit.
12. Dodać job sprzątający stare warianty.

---

## 13. Decyzje architektoniczne

### Decyzja 1: output jako JSON, nie HTML

Powód:
- zgodność z obecnym builderem sekcji,
- łatwiejsza walidacja,
- możliwość regeneracji pojedynczych bloków,
- brak zależności od swobodnego HTML od modelu AI.

### Decyzja 2: preview przed zapisem jest obowiązkowe

Powód:
- redukcja ryzyka słabej jakości copy,
- zachowanie kontroli użytkownika,
- lepsza zgodność z aktualnym flow draft -> edit -> publish.

### Decyzja 3: generator jako osobny moduł nad istniejącym LandingPageService

Powód:
- mniejszy coupling,
- czystsza architektura,
- łatwiejsze testowanie integracji z OpenAI,
- brak rozlewania logiki AI po kontrolerach i modelach LP.

### Decyzja 4: fallbacki zamiast twardych blokad przy niepełnym profilu

Powód:
- szybsze time-to-value,
- mniejszy drop-off w onboardingu,
- zgodność z celem produktu: szybkie wygenerowanie pierwszej LP.

---

## 14. Rekomendacja wdrożenia etapami

### Etap 1

- generacja całego draftu,
- preview,
- zapis do `LandingPage`.

### Etap 2

- regeneracja pojedynczych sekcji,
- log generacji,
- cleanup draftów.

### Etap 3

- lepsze fallbacki branżowe,
- telemetry jakości promptów,
- limity planów i billing usage.

---

## 15. Podsumowanie

AI Landing Page Generator powinien być warstwą przyspieszającą tworzenie draftu, a nie alternatywnym systemem publikacji stron. Najbezpieczniejszy kierunek dla tego repozytorium to generacja ustrukturyzowanego JSON, walidacja po backendzie, preview na tych samych komponentach co runtime i dopiero potem zapis do istniejącego modelu `LandingPage`.

To podejście minimalizuje ryzyko architektoniczne, nie rozbija aktualnego modułu Landing Pages i pozwala wdrożyć AI iteracyjnie bez przepisywania publicznego renderera ani panelu edycji.