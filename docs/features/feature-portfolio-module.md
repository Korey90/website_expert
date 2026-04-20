# Feature: Portfolio Module

## Definicja modułu

**Cel**: Wydzielenie danych projektów portfolio z `site_sections.extra` (JSON) do dedykowanej tabeli `portfolio_projects`, z pełnym zarządzaniem przez panel admina i wyświetlaniem na froncie z nowego źródła danych.

**Bounded Context**: Marketing / Content  
**Priorytet MVP**: MUST HAVE (widoczne na stronie głównej, zastępuje hardcoded dane)  
**Zależności**: `SiteSection` (sekcja `portfolio` pozostaje — przechowuje tytuł, opis, ustawienia sekcji), `WelcomeController`  
**Użytkownik**: Admin agencji

---

## Stan aktualny (co istnieje)

- `site_sections` tabela z rekordem `key = 'portfolio'` (ID 6 w seederze)
- `site_sections.extra` przechowuje JSON z tablicą `items[]` — każdy item to: `title_en/pl/pt`, `tag_en/pl/pt`, `desc_en/pl/pt`, `result_en/pl/pt`, `image`, `link`
- `WelcomeController` przekazuje `$portfolio` do frontu z `extra` jako array
- `Portfolio.jsx` czyta `extra.items` i renderuje karty
- `SiteSectionResource` w Filament — edycja przez surowy JSON textarea
- Istnieje `ProjectResource` (CRM) — nie dotykamy, to osobny model

---

## KROK 1 — Model danych

### TABELA: `portfolio_projects`

**Cel**: Przechowuje pojedynczy projekt w portfolio agencji — pracę którą chcemy pokazać potencjalnym klientom.

```
Kolumny:
- id                    bigint unsigned, PK, auto-increment
- title                 JSON (translatable)    [EN/PL/PT — tytuł projektu]
- tag                   JSON (translatable)    [EN/PL/PT — kategoria, np. "E-Commerce"]
- description           JSON (translatable)    [EN/PL/PT — opis projektu]
- result                JSON (translatable)    [EN/PL/PT — osiągnięty wynik, np. "+40% konwersji"]
- client_name           string, nullable       [nazwa klienta — nie FK, tylko string]
- image_path            string, nullable       [ścieżka do miniatury, np. /images/portfolio/x.webp]
- link                  string, nullable       [URL do case study lub zewnętrznej strony]
- tags                  JSON, nullable         [dodatkowe tagi technologiczne, np. ["Laravel","React"]]
- is_featured           boolean, default true  [czy pokazywać na stronie głównej]
- sort_order            integer, default 0     [kolejność na stronie]
- is_active             boolean, default true  [czy widoczny publicznie]
- created_at            timestamp
- updated_at            timestamp
- deleted_at            timestamp              [soft deletes]

Indeksy:
- is_featured + is_active + sort_order  (zapytanie na stronie głównej)
- is_active + sort_order               (pełna lista)
```

**Uwaga**: Brak `business_id` — to MVP agencji jako single-tenant. Jeśli projekt ewoluuje w SaaS multi-tenant, dodamy `business_id` przez oddzielną migrację.

---

## KROK 2 — Backend Laravel

### 2a. Model

```
PLIK: app/Models/PortfolioProject.php

Traits:
- HasFactory
- SoftDeletes
- HasTranslations (Spatie Translatable)

Translatable: ['title', 'tag', 'description', 'result']

Fillable:
- title, tag, description, result
- client_name, image_path, link
- tags, is_featured, sort_order, is_active

Casts:
- tags:       'array'
- is_featured: 'boolean'
- is_active:   'boolean'

Scopes:
- scopeActive($query)   → where('is_active', true)
- scopeFeatured($query) → where('is_featured', true)->orderBy('sort_order')

Accessory:
- getLocalizedTitle(string $locale): string   → $this->getTranslation('title', $locale)
```

### 2b. Migracja

```
PLIK: database/migrations/[timestamp]_create_portfolio_projects_table.php

Schema::create('portfolio_projects', function (Blueprint $table) {
    $table->id();
    $table->json('title');
    $table->json('tag')->nullable();
    $table->json('description')->nullable();
    $table->json('result')->nullable();
    $table->string('client_name')->nullable();
    $table->string('image_path')->nullable();
    $table->string('link')->nullable();
    $table->json('tags')->nullable();
    $table->boolean('is_featured')->default(true);
    $table->unsignedInteger('sort_order')->default(0);
    $table->boolean('is_active')->default(true);
    $table->timestamps();
    $table->softDeletes();
    $table->index(['is_active', 'sort_order']);
    $table->index(['is_featured', 'is_active', 'sort_order']);
});
```

### 2c. Seeder

```
PLIK: database/seeders/PortfolioProjectSeeder.php

Przepisuje 3 istniejące projekty z SiteSectionSeeder (hargreaves, nts-direct, oakfield-dental)
do nowej tabeli. Każdy projekt: title[en/pl/pt], tag[en/pl/pt], description, result, image_path, link.

Wywołanie w DatabaseSeeder: $this->call(PortfolioProjectSeeder::class);
```

### 2d. Serwis

```
SERWIS: app/Services/Portfolio/PortfolioProjectService.php

Metody publiczne:

- getFeatured(int $limit = 3): Collection
  Zwraca: aktywne, featured projekty posortowane wg sort_order, limit $limit
  Używane przez: WelcomeController

- getAll(): Collection
  Zwraca: wszystkie aktywne posortowane wg sort_order
  Używane przez: przyszła strona /portfolio

- create(array $data): PortfolioProject
  Parametry: zwalidowane dane z StorePortfolioProjectRequest
  Zwraca: nowy model

- update(PortfolioProject $project, array $data): PortfolioProject

- delete(PortfolioProject $project): void
  Soft delete

- reorder(array $orderedIds): void
  Parametry: [['id' => 1, 'sort_order' => 0], ...]
  Aktualizuje sort_order masowo
```

### 2e. Form Requests

```
REQUEST: app/Http/Requests/Portfolio/StorePortfolioProjectRequest.php
REQUEST: app/Http/Requests/Portfolio/UpdatePortfolioProjectRequest.php

Reguły (obie klasy, Update ma sometimes na wymaganych):
- title.en:       required|string|max:255
- title.pl:       nullable|string|max:255
- title.pt:       nullable|string|max:255
- tag.en:         nullable|string|max:100
- description.en: nullable|string|max:1000
- result.en:      nullable|string|max:255
- client_name:    nullable|string|max:255
- image_path:     nullable|string|max:500
- link:           nullable|url|max:500
- tags:           nullable|array
- tags.*:         string|max:50
- is_featured:    boolean
- is_active:      boolean
- sort_order:     integer|min:0

Autoryzacja: permission('portfolio-project.create' / 'portfolio-project.update')
```

### 2f. Kontroler

```
KONTROLER: app/Http/Controllers/Admin/PortfolioProjectController.php
(Dostęp przez panel admina — Inertia, middleware auth + role:admin)

Trasy (routes/web.php lub routes/admin.php):
Route::prefix('admin/portfolio')->middleware(['auth', 'role:admin'])->group(function () {
    Route::get('/',                [PortfolioProjectController::class, 'index'])  ->name('admin.portfolio.index');
    Route::get('/create',          [PortfolioProjectController::class, 'create']) ->name('admin.portfolio.create');
    Route::post('/',               [PortfolioProjectController::class, 'store'])  ->name('admin.portfolio.store');
    Route::get('/{project}/edit',  [PortfolioProjectController::class, 'edit'])   ->name('admin.portfolio.edit');
    Route::put('/{project}',       [PortfolioProjectController::class, 'update']) ->name('admin.portfolio.update');
    Route::delete('/{project}',    [PortfolioProjectController::class, 'destroy'])->name('admin.portfolio.destroy');
    Route::post('/reorder',        [PortfolioProjectController::class, 'reorder'])->name('admin.portfolio.reorder');
});

Metody:
- index():   Inertia::render('Admin/Portfolio/Index', ['projects' => PortfolioProject::active()->ordered()->get()])
- create():  Inertia::render('Admin/Portfolio/Create')
- store():   serwis->create(), redirect z flash
- edit():    Inertia::render('Admin/Portfolio/Edit', ['project' => $project])
- update():  serwis->update(), redirect z flash
- destroy(): serwis->delete(), redirect z flash
- reorder(): serwis->reorder(request('items')), response()->json(['ok' => true])
```

### 2g. Filament Resource (panel /admin)

```
PLIK: app/Filament/Resources/PortfolioProjectResource.php

Navigation:
- Group: Marketing
- Label: Portfolio Projects
- Icon: heroicon-o-photo
- Sort: 3 (po SiteSectionResource)

Tabela (columns):
- ImageColumn::make('image_path')       ->label('Thumb') ->disk('public') ->circular(false)
- TextColumn::make('title.en')          ->label('Title') ->searchable() ->sortable()
- TextColumn::make('client_name')       ->label('Client') ->placeholder('—')
- TextColumn::make('tag.en')            ->label('Category') ->badge() ->color('gray')
- IconColumn::make('is_featured')       ->boolean() ->label('Featured')
- IconColumn::make('is_active')         ->boolean() ->label('Active')
- TextColumn::make('sort_order')        ->label('Order') ->sortable()
- TextColumn::make('updated_at')        ->dateTime('d M Y') ->since()

Formularz (form):
Tabs:
  Tab "Content":
    - TextInput::make('title.en') ->required() ->label('Title (EN)')
    - TextInput::make('title.pl') ->label('Title (PL)')
    - TextInput::make('title.pt') ->label('Title (PT)')
    - TextInput::make('tag.en') ->label('Category (EN)')
    - TextInput::make('tag.pl') / tag.pt
    - Textarea::make('description.en') ->rows(3)
    - Textarea::make('description.pl') / description.pt
    - TextInput::make('result.en') ->label('Result snippet (EN)')
    - TextInput::make('result.pl') / result.pt

  Tab "Media & Links":
    - FileUpload::make('image_path') ->image() ->directory('portfolio') ->disk('public')
    - TextInput::make('link') ->url() ->label('Case Study URL')
    - TextInput::make('client_name')
    - TagsInput::make('tags') ->label('Tech Tags')

  Tab "Settings":
    - Toggle::make('is_featured') ->label('Show on homepage')
    - Toggle::make('is_active')
    - TextInput::make('sort_order') ->integer()

Akcje: EditAction, DeleteAction (soft delete)
Filtry: SelectFilter('is_featured'), SelectFilter('is_active')
```

### 2h. Aktualizacja SiteSectionResource (panel /admin route: /admin/site-sections/6)

Sekcja portfolio w `SiteSectionResource` zarządza **ustawieniami sekcji** (tytuł, opis, etykieta, CTA) — nie projektami.

Zmiany w `SiteSectionResource.php`:
- W formularzu edycji sekcji `portfolio` dodać informację (Placeholder/Hint): *"Projekty portfolio zarządzane są w osobnym module: Portfolio Projects"*
- Usunąć lub oznaczyć jako deprecated pole `extra.items` w JSON textarea dla klucza `portfolio`
- Dodać link/button (`Action::make('manageProjects')->url(route('filament.admin.resources.portfolio-projects.index'))`) w sekcji `portfolio`

---

## KROK 3 — Aktualizacja WelcomeController

```php
// WelcomeController.php — zmiana w bloku $portfolio

// PRZED:
$portfolio = ($s = $sections->get('portfolio')) ? [
    'title'  => $s->title,
    ...
    'extra'  => $s->extra,   // ← tu były items[]
] : null;

// PO:
use App\Services\Portfolio\PortfolioProjectService;

$portfolioProjects = app(PortfolioProjectService::class)
    ->getFeatured(limit: 3)
    ->map(fn ($p) => [
        'title_en'  => $p->getTranslation('title', 'en'),
        'title_pl'  => $p->getTranslation('title', 'pl'),
        'title_pt'  => $p->getTranslation('title', 'pt'),
        'tag_en'    => $p->getTranslation('tag', 'en'),
        'tag_pl'    => $p->getTranslation('tag', 'pl'),
        'tag_pt'    => $p->getTranslation('tag', 'pt'),
        'desc_en'   => $p->getTranslation('description', 'en'),
        'desc_pl'   => $p->getTranslation('description', 'pl'),
        'desc_pt'   => $p->getTranslation('description', 'pt'),
        'result_en' => $p->getTranslation('result', 'en'),
        'result_pl' => $p->getTranslation('result', 'pl'),
        'result_pt' => $p->getTranslation('result', 'pt'),
        'client'    => $p->client_name,
        'image'     => $p->image_path,
        'link'      => $p->link,
        'tags'      => $p->tags ?? [],
    ]);

$portfolio = ($s = $sections->get('portfolio')) ? [
    'title'       => $s->title,
    'subtitle'    => $s->subtitle,
    'button_text' => $s->button_text,
    'button_url'  => $s->button_url,
    'extra'       => array_merge($s->extra ?? [], [
        'items' => $portfolioProjects->toArray(),  // ← nowe źródło
    ]),
] : null;
```

**Ważne**: `Portfolio.jsx` czyta `extra.items` — format odpowiedzi musi być identyczny. Dzięki temu frontend NIE wymaga zmian strukturalnych, tylko ewentualne uzupełnienie pola `client`.

---

## KROK 4 — Frontend (Inertia + React + TypeScript)

### 4a. Typy TypeScript

```typescript
// resources/js/types/portfolio.ts

export interface PortfolioProject {
  id: number;
  title_en: string; title_pl?: string; title_pt?: string;
  tag_en?: string;  tag_pl?: string;  tag_pt?: string;
  desc_en?: string; desc_pl?: string; desc_pt?: string;
  result_en?: string; result_pl?: string; result_pt?: string;
  client?: string;
  image?: string;
  link?: string;
  tags?: string[];
}

export interface PortfolioPageProps {
  projects: PortfolioProject[];
}
```

### 4b. Aktualizacja Portfolio.jsx

Zmiany minimalne — komponent już działa poprawnie z `extra.items`. Jedyna zmiana: obsługa pola `client` (już jest w istniejącym komponencie). **Brak wymaganych zmian jeśli format items jest zachowany.**

### 4c. Strony Inertia — Panel admina portfolio

```
PLIK: resources/js/Pages/Admin/Portfolio/Index.tsx

Props: { projects: PortfolioProject[] }
Widok: tabela z projektami, przyciski Dodaj/Edytuj/Usuń, drag-and-drop reorder (opcjonalnie)
```

```
PLIK: resources/js/Pages/Admin/Portfolio/Create.tsx

Props: {}
Widok: formularz z tabami (Content / Media & Links / Settings)
Hook: useForm<PortfolioProjectForm>(defaultValues)
Submit: router.post(route('admin.portfolio.store'), form.data)
```

```
PLIK: resources/js/Pages/Admin/Portfolio/Edit.tsx

Props: { project: PortfolioProject }
Widok: jak Create, pre-filled danymi projektu
Submit: router.put(route('admin.portfolio.update', project.id), form.data)
```

---

## KROK 5 — Uprawnienia Spatie

```
Nowe permisje (dodać do seeder PermissionSeeder lub RoleSeeder):
- portfolio-project.view-any
- portfolio-project.create
- portfolio-project.update
- portfolio-project.delete

Rola admin: wszystkie
Rola manager: view-any, create, update
```

---

## Kolejność implementacji

1. **Migracja** `create_portfolio_projects_table` — `php artisan make:migration`
2. **Model** `PortfolioProject` z trait HasTranslations
3. **Seeder** `PortfolioProjectSeeder` — przepisz 3 projekty z SiteSectionSeeder
4. **Serwis** `PortfolioProjectService` — metody getFeatured, create, update, delete
5. **Aktualizacja WelcomeController** — podmień źródło danych
6. **Filament Resource** `PortfolioProjectResource` — panel admina
7. **Aktualizacja SiteSectionResource** — hint + link do nowego zasobu przy kluczu `portfolio`
8. **Form Requests** + **Kontroler** (jeśli potrzebny poza Filament)
9. **Strony Inertia** (Admin/Portfolio/*) — opcjonalnie jeśli Filament nie wystarcza
10. **Testy** Feature: CRUD + getFeatured zwraca właściwe projekty

---

## Ryzyka i uwagi

| Ryzyko | Mitygacja |
|---|---|
| Istniejące dane w `site_sections.extra.items` znikną | Seeder przepisuje dane do nowej tabeli PRZED usunięciem z extra |
| Format items musi być identyczny z tym co czyta Portfolio.jsx | WelcomeController mapuje nowe pola na stary format |
| `HasTranslations` wymaga JSON w kolumnach | Wszystkie pola translatable jako `json` w migracji |
| Obrazy — aktualnie ścieżki SVG w public/ | FileUpload w Filament zapisuje do storage/app/public/portfolio — trzeba `php artisan storage:link` |

---

## KROK 6 — AI Translation (Filament Header Action)

> Status: Planned
> Data: 2026-04-20

### Cel

Przycisk **"Translate with AI"** w widoku Edit Portfolio Project w Filament. Odczytuje pola EN (title, tag, description, result), wysyła do OpenAI i wypełnia pola PL + PT bez przeładowania strony.

### Stan obecny

- `OpenAiLandingClient` (`app/Services/LandingPage/OpenAiLandingClient.php`) — gotowy klient HTTP z retry i obsługą błędów, używa `config('services.openai')`
- Konfiguracja: `config/services.php` → `openai.api_key`, `openai.model`, `openai.base_url`, `openai.timeout`
- `PortfolioProjectResource` używa Filament 5 z `Schema $form`
- Formularz ma Tabs: `English` / `Polski` / `Português` — pola: `title.{locale}`, `tag.{locale}`, `description.{locale}`, `result.{locale}`

### Delta — co trzeba zbudować

| Element | Plik | Opis |
|---|---|---|
| **Service** | `app/Services/Portfolio/PortfolioTranslationService.php` | Nowa klasa, wrapper na `OpenAiLandingClient`, buduje prompt, parsuje odpowiedź |
| **Controller** | `app/Http/Controllers/Filament/PortfolioTranslateController.php` | Endpoint `POST /admin/portfolio-projects/{record}/translate` |
| **Route** | `routes/web.php` | Pod middleware `auth` + `filament.admin` |
| **Filament Action** | `PortfolioProjectResource::getHeaderActions()` | `Action::make('translateWithAI')` na stronie Edit |

### Backend

#### PortfolioTranslationService

```php
// app/Services/Portfolio/PortfolioTranslationService.php

namespace App\Services\Portfolio;

use App\Services\LandingPage\OpenAiLandingClient;

class PortfolioTranslationService
{
    public function __construct(
        private readonly OpenAiLandingClient $client,
    ) {}

    /**
     * @param array{title: string, tag: string, description: string, result: string} $source  EN content
     * @return array{pl: array, pt: array}
     */
    public function translate(array $source): array
    {
        $systemPrompt = <<<PROMPT
You are a professional translator for a web agency portfolio. 
Translate the provided fields from English into Polish (pl) and Portuguese (pt).
Return ONLY a JSON object in this exact structure:
{
  "pl": { "title": "...", "tag": "...", "description": "...", "result": "..." },
  "pt": { "title": "...", "tag": "...", "description": "...", "result": "..." }
}
Keep translations natural and professional. Preserve line breaks in "result".
PROMPT;

        $userPrompt = json_encode([
            'title'       => $source['title']       ?? '',
            'tag'         => $source['tag']         ?? '',
            'description' => $source['description'] ?? '',
            'result'      => $source['result']      ?? '',
        ], JSON_UNESCAPED_UNICODE);

        $response = $this->client->generateStructuredLanding([
            'system_prompt' => $systemPrompt,
            'user_prompt'   => $userPrompt,
        ]);

        $content = $response['content'];

        return [
            'pl' => [
                'title'       => (string) ($content['pl']['title']       ?? ''),
                'tag'         => (string) ($content['pl']['tag']         ?? ''),
                'description' => (string) ($content['pl']['description'] ?? ''),
                'result'      => (string) ($content['pl']['result']      ?? ''),
            ],
            'pt' => [
                'title'       => (string) ($content['pt']['title']       ?? ''),
                'tag'         => (string) ($content['pt']['tag']         ?? ''),
                'description' => (string) ($content['pt']['description'] ?? ''),
                'result'      => (string) ($content['pt']['result']      ?? ''),
            ],
        ];
    }
}
```

#### Controller endpoint

```php
// app/Http/Controllers/Filament/PortfolioTranslateController.php

namespace App\Http\Controllers\Filament;

use App\Http\Controllers\Controller;
use App\Models\PortfolioProject;
use App\Services\Portfolio\PortfolioTranslationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PortfolioTranslateController extends Controller
{
    public function __invoke(
        Request $request,
        PortfolioProject $record,
        PortfolioTranslationService $service,
    ): JsonResponse {
        $data = $request->validate([
            'title'       => ['nullable', 'string', 'max:255'],
            'tag'         => ['nullable', 'string', 'max:100'],
            'description' => ['nullable', 'string', 'max:2000'],
            'result'      => ['nullable', 'string', 'max:500'],
        ]);

        // Walidacja: co najmniej jedno pole musi być niepuste
        if (empty(array_filter($data))) {
            return response()->json(['error' => 'No source content provided.'], 422);
        }

        $translations = $service->translate($data);

        return response()->json($translations);
    }
}
```

#### Route

```php
// routes/web.php — w grupie middleware auth (panel Filament)
Route::post(
    '/admin/portfolio-projects/{record}/translate',
    \App\Http\Controllers\Filament\PortfolioTranslateController::class,
)->middleware(['web', 'auth'])->name('filament.portfolio.translate');
```

### Filament Action (Frontend Filament)

```php
// PortfolioProjectResource.php — Pages/EditPortfolioProject.php lub bezpośrednio w Resource

// W klasie EditPortfolioProject (Pages):
protected function getHeaderActions(): array
{
    return [
        Actions\SaveAction::make(),

        Actions\Action::make('translateWithAI')
            ->label('Translate with AI')
            ->icon('heroicon-o-language')
            ->color('info')
            ->requiresConfirmation()
            ->modalHeading('Translate from English with AI')
            ->modalDescription('AI will generate Polish and Portuguese translations from the English content currently saved for this record. Existing PL/PT translations will be overwritten.')
            ->modalSubmitActionLabel('Generate translations')
            ->action(function (PortfolioProject $record): void {
                $service = app(\App\Services\Portfolio\PortfolioTranslationService::class);

                $translations = $service->translate([
                    'title'       => $record->getTranslation('title', 'en'),
                    'tag'         => $record->getTranslation('tag', 'en'),
                    'description' => $record->getTranslation('description', 'en'),
                    'result'      => $record->getTranslation('result', 'en'),
                ]);

                // Aktualizuj tylko PL i PT — EN nie ruszamy
                $record->setTranslation('title',       'pl', $translations['pl']['title']);
                $record->setTranslation('title',       'pt', $translations['pt']['title']);
                $record->setTranslation('tag',         'pl', $translations['pl']['tag']);
                $record->setTranslation('tag',         'pt', $translations['pt']['tag']);
                $record->setTranslation('description', 'pl', $translations['pl']['description']);
                $record->setTranslation('description', 'pt', $translations['pt']['description']);
                $record->setTranslation('result',      'pl', $translations['pl']['result']);
                $record->setTranslation('result',      'pt', $translations['pt']['result']);
                $record->save();

                Notification::make()
                    ->title('Translations generated')
                    ->body('Polish and Portuguese fields have been updated. Review and save.')
                    ->success()
                    ->send();

                // Odśwież formularz z nową zawartością
                $this->fillForm();
            })
            ->visible(fn () => filled(config('services.openai.api_key'))),
    ];
}
```

### Workflow (happy path)

1. Admin otwiera Edit Portfolio Project
2. Wypełnia pola w zakładce **English** (title, tag, description, result)
3. Klika **Save** — rekord zapisany z EN content
4. Klika **Translate with AI** → modal z potwierdzeniem
5. Klika **Generate translations** → Filament wysyła action
6. `PortfolioTranslationService` buduje prompt z pól EN z bazy, wywołuje `OpenAiLandingClient`
7. OpenAI zwraca JSON z `{pl: {...}, pt: {...}}`
8. `setTranslation()` zapisuje PL i PT na rekordzie → `save()`
9. `fillForm()` odświeża formularz — admin widzi wypełnione zakładki Polski i Português
10. Notification: "Translations generated"

### Edge cases

| Case | Obsługa |
|---|---|
| Pola EN puste | Walidacja w serwisie — `array_filter($data)` pusty → błąd 422 / Notification error |
| OpenAI nie skonfigurowane | Action niewidoczny (`->visible(fn() => filled(config(...)))`) |
| OpenAI timeout / błąd | `LandingPageGenerationException` → catch w action → Notification danger |
| Odpowiedź brakuje klucza pl/pt | `?? ''` w `PortfolioTranslationService::translate()` |
| Istniejące tłumaczenia PL/PT | Nadpisywane — użytkownik jest ostrzeżony w modal description |

### Checklist implementacji

- [ ] `PortfolioTranslationService` — nowa klasa w `app/Services/Portfolio/`
- [ ] Route `POST /admin/portfolio-projects/{record}/translate` (opcjonalny — jeśli chcemy przez HTTP; gdy Action działa inline, route nie jest potrzebny)
- [ ] `EditPortfolioProject` page class (jeśli nie istnieje) z `getHeaderActions()`
- [ ] Import `Filament\Notifications\Notification` w klasie page
- [ ] Obsługa wyjątku `LandingPageGenerationException` w action (try/catch → Notification::danger())
- [ ] Test: mock `OpenAiLandingClient`, sprawdź że PL/PT są zapisane, EN niezmienione
