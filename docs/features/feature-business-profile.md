# Feature Design: Business Profile
**Data:** 2026-03-31  
**Sprint:** 1 (MVP — Tydzień 1–2)  
**Bazuje na:** `docs/project-analysis.md`, `docs/architecture-plan.md`, `docs/mvp-plan.md`  
**Status:** AWAITING APPROVAL — nie implementuj bez zatwierdzenia

---

## 1. Definicja modułu

**Cel:** Umożliwić właścicielowi konta SaaS stworzenie i zarządzanie profilem swojej firmy — nazwa, logo, branża, kolory brand, ton komunikacji — który staje się fundamentem dla generatora landing pages i AI context.

**Bounded Context:** `IdentityAccess` (Business + BusinessUser) + `BusinessProfile` (dane marki)

**Priorytet MVP:** MUST HAVE — bez `Business` entity żaden inny moduł nie działa

**Zależności:**
- `User` model + Spatie HasRoles — ✅ istnieją
- Rejestracja (`RegisteredUserController`) — ✅ istnieje, wymaga rozszerzenia
- `AdminPanelProvider` (Filament) — ✅ istnieje, wymaga rozszerzenia o topbar + nawigację

**Użytkownik:** Admin agencji (rola `admin`, `manager`), pierwsza osoba rejestrująca firmę

---

## 2. Zakres modułu (co wchodzi, co nie wchodzi)

### Wchodzi w zakres:
- Tabele `businesses` + `business_users` (tenant root, Sprint 1)
- Tabela `business_profiles` (dane marki, Sprint 1)
- Modele `Business`, `BusinessUser`, `BusinessProfile`
- Trait `BelongsToTenant` (szkielet — bez wymuszania GlobalScope w MVP)
- Helper `currentBusiness()` globalny — używany przez kolejne moduły
- Middleware `EnsureHasBusiness` — redirect onboarding
- Event `BusinessCreated` + `BusinessProfileUpdated`
- Onboarding wizard (2 kroki): krok 1 = nazwa firmy, krok 2 = profil (logo, branża, opis, kolory)
- Filament: strona `BusinessProfilePage` w ustawieniach — edycja profilu
- Inertia/React: `Pages/Onboarding/` — wizard po pierwszej rejestracji
- Spatie: nowe uprawnienia `manage_business_profile`

### NIE wchodzi w zakres:
- GlobalScope / `BelongsToTenant` enforcement na istniejących modelach (odkładamy do multi-tenancy fazy)
- Subdomeny (`{slug}.app`) — identyfikacja przez session/auth w MVP
- Plany subskrypcyjne (`stripe_customer_id`, `plan`) — po MVP
- Super-admin panel — po MVP
- AI context cache (`ai_context_cache`) — zaplanowana kolumna, używana przez LP Generator (v1.1)

---

## 3. Model danych

### 3.1 Tabela `businesses`

```
TABELA: businesses
Cel: Korzeń danych firmy (tenant root). Jeden rekord = jedna firma w SaaS.

Kolumny:
- id                    ULID (char 26), PK                         — używamy ULID dla bezpieczeństwa (nie sekwencyjne int)
- name                  varchar(255), NOT NULL                     — "Agencja XYZ"
- slug                  varchar(100), NOT NULL, UNIQUE             — "agencja-xyz" (URL-friendly, auto-generated)
- locale                varchar(10), NOT NULL, DEFAULT 'en'        — domyślny język UI (en|pl|pt)
- timezone              varchar(50), NOT NULL, DEFAULT 'Europe/London'
- logo_path             varchar(500), NULLABLE                     — path w Laravel Storage (public disk)
- primary_color         varchar(7), NULLABLE                       — hex "#ff2b17"; używany w LP branding
- plan                  varchar(50), NOT NULL, DEFAULT 'free'      — 'free'|'starter'|'pro'|'agency' (rozszerzalne)
- is_active             tinyint(1), NOT NULL, DEFAULT 1
- trial_ends_at         timestamp, NULLABLE                        — koniec trialu (null = brak trialu)
- stripe_customer_id    varchar(255), NULLABLE                     — Stripe Customer ID (gdy Cashier wejdzie)
- settings              JSON, NULLABLE                             — per-tenant settings override
                                                                     {"mail_from":"x@y.com","twilio_enabled":true}
Created_at / updated_at / deleted_at (SoftDeletes)

Indeksy:
- UNIQUE(slug)
- INDEX(is_active)
- INDEX(plan)

Relacje:
- has many: business_users (pivot z rolą)
- has one: business_profiles
- has many: landing_pages (nowa tabela — Sprint 2)
- has many: leads (gdy business_id dodane do leads — Sprint 1+)
```

---

### 3.2 Tabela `business_users`

```
TABELA: business_users
Cel: Pivot membership — który User należy do którego Business i z jaką rolą w ramach Business.
     Niezależna od roles/permissions Spatie (które kontrolują dostęp do Filament panelu).

Kolumny:
- id                    bigint unsigned, PK, AUTO INCREMENT
- business_id           char(26), FK → businesses.id, ON DELETE CASCADE, NOT NULL
- user_id               bigint unsigned, FK → users.id, ON DELETE CASCADE, NOT NULL
- role                  varchar(50), NOT NULL, DEFAULT 'member'    — 'owner'|'admin'|'member'
- is_active             tinyint(1), NOT NULL, DEFAULT 1
- invited_by            bigint unsigned, FK → users.id, NULLABLE
- joined_at             timestamp, NULLABLE
- created_at / updated_at

Indeksy:
- UNIQUE(business_id, user_id)      — jeden user może być raz w danym business
- INDEX(user_id)                    — szybkie wyszukiwanie po user -> jego business

Relacje:
- belongs to: businesses
- belongs to: users (member)
- belongs to: users (invited_by)

Uwaga MVP: W MVP role w business_users nie są używane do autoryzacji — Spatie Permission to robi.
  Kolumna role jest zaplanowana pod v1.1 (multi-tenant team management).
```

---

### 3.3 Tabela `business_profiles`

```
TABELA: business_profiles
Cel: Rozszerzone dane marki firmy — używane do personalizacji LP, AI context, kampanii.
     Relacja 1:1 z businesses.

Kolumny:
- id                    bigint unsigned, PK, AUTO INCREMENT
- business_id           char(26), FK → businesses.id, ON DELETE CASCADE, UNIQUE, NOT NULL
- tagline               varchar(255), NULLABLE                     — "Twój sukces w internecie"
- description           text, NULLABLE                            — opis firmy (do 1000 znaków)
- industry              varchar(100), NULLABLE                    — 'marketing_agency'|'web_design'|'ecommerce'|...
- tone_of_voice         varchar(50), NULLABLE, DEFAULT 'professional'
                                                                   — 'professional'|'friendly'|'bold'|'minimalist'
- target_audience       JSON, NULLABLE                            — {"age_range":"25-45","gender":"mixed","interests":["..."]}
- services              JSON, NULLABLE                            — ["Web Design","SEO","Social Media"]
- brand_colors          JSON, NULLABLE                            — {"primary":"#ff2b17","secondary":"#000","accent":"#fff"}
- fonts                 JSON, NULLABLE                            — {"heading":"Syne","body":"Inter"}
- website_url           varchar(500), NULLABLE
- social_links          JSON, NULLABLE                            — {"facebook":"url","instagram":"url","linkedin":"url"}
- seo_keywords          JSON, NULLABLE                            — ["agencja marketingowa","SEO Kraków"]
- ai_context_cache      text, NULLABLE                            — skompilowany prompt AI (TTL 24h) — ZAREZERWOWANE na v1.1
- ai_context_updated_at timestamp, NULLABLE                       — kiedy cache AI był ostatnio odświeżony
- created_at / updated_at

Indeksy:
- UNIQUE(business_id)         — 1:1 z businesses

Relacje:
- belongs to: businesses

Uwagi:
- Pola JSON: target_audience, services, brand_colors, fonts, social_links, seo_keywords
  są flexibile — pozwalają rozszerzać bez migracji. 
- ai_context_cache: nie wypełniamy w MVP — kolumna istnieje dla kompatybilności z LP Generator
- Walidacja brand_colors.primary: musi być poprawnym hex (#rrggbb)
```

---

### 3.4 Relacja z istniejącymi tabelami

```
users (istniejąca)
  └── business_users (nowa pivot)
        └── businesses (nowa)
              └── business_profiles (nowa 1:1)

[ISTNIEJĄCE — NIE MODYFIKUJEMY w Sprint 1:]
leads        — brak business_id jeszcze (dodamy w Sprint 3 przy lead capture)
clients      — brak business_id jeszcze
projects     — brak business_id jeszcze
invoices     — brak business_id jeszcze

[MODYFIKACJA MINIMALNA — Sprint 1:]
users        — dodajemy relację hasMany(BusinessUser) i helper currentBusiness()
```

---

## 4. Backend Laravel

### 4.1 Modele

---

**Model: `app/Models/Business.php`**

```
Traits:
- HasFactory
- SoftDeletes
- HasUlids                  — używamy ULID jako primary key (Laravel 10+)

Fillable:
- name, slug, locale, timezone, logo_path, primary_color, plan,
  is_active, trial_ends_at, stripe_customer_id, settings

Casts:
- is_active:             boolean
- trial_ends_at:         datetime
- settings:              'array'          — JSON → PHP array

Relacje:
- public function users(): BelongsToMany
    przez tabelę business_users, z polami: role, is_active, joined_at
    
- public function owner(): HasOneThrough | first user z role='owner' przez business_users
    (helper scope, nie relacja Eloquent — patrz Scopes)
    
- public function profile(): HasOne
    zwraca BusinessProfile (1:1)

- public function members(): HasMany
    zwraca BusinessUser — pivot records

Scopes:
- scopeActive($query): where('is_active', true)

Akcesory:
- getLogoUrlAttribute(): string|null
    return $this->logo_path ? Storage::disk('public')->url($this->logo_path) : null;

- getIsOnTrialAttribute(): bool
    return $this->trial_ends_at && $this->trial_ends_at->isFuture();

Metody:
- public function isOwnedBy(User $user): bool
    sprawdza czy user ma membership z role='owner'

- public static function forUser(User $user): ?self
    zwraca pierwszy aktywny Business danego usera (MVP: jeden business per user)

Uwagi:
- HasUlids: używa char(26) ULID jako PK — bardziej bezpieczne niż int auto-increment
  (nie ujawnia liczby klientów, odporność na enumeration attacks)
- $primaryKey = 'id', $keyType = 'string', $incrementing = false
```

---

**Model: `app/Models/BusinessUser.php`**

```
Traits:
- HasFactory

Table: 'business_users'

Fillable:
- business_id, user_id, role, is_active, invited_by, joined_at

Casts:
- is_active:  boolean
- joined_at:  datetime
- role:       — string (enum: owner|admin|member — nie PHP enum, żeby łatwo rozszerzać)

Relacje:
- public function business(): BelongsTo → Business
- public function user(): BelongsTo → User
- public function invitedBy(): BelongsTo → User (nullable)

Scopes:
- scopeActive($query): where('is_active', true)
- scopeOwners($query): where('role', 'owner')
```

---

**Model: `app/Models/BusinessProfile.php`**

```
Traits:
- HasFactory

Table: 'business_profiles'

Fillable:
- business_id, tagline, description, industry, tone_of_voice,
  target_audience, services, brand_colors, fonts, website_url,
  social_links, seo_keywords, ai_context_cache, ai_context_updated_at

Casts:
- target_audience:       'array'
- services:              'array'
- brand_colors:          'array'
- fonts:                 'array'
- social_links:          'array'
- seo_keywords:          'array'
- ai_context_updated_at: datetime

Relacje:
- public function business(): BelongsTo → Business

Akcesory:
- getPrimaryColorAttribute(): string
    return $this->brand_colors['primary'] ?? $this->business->primary_color ?? '#3b82f6';

Metody (używane przez AI Generator — v1.1):
- public function isAiCacheStale(): bool
    return !$this->ai_context_updated_at || $this->ai_context_updated_at->diffInHours(now()) > 24;

- public function toAiContext(): array
    Zwraca array gotowy do użycia w OpenAI prompt:
    ['brand_name', 'tagline', 'industry', 'tone_of_voice',
     'target_audience', 'services', 'primary_color', 'language']
```

---

**Rozszerzenie modelu `app/Models/User.php`**

```
Dodać relacje i helper (istniejący plik — minimalne zmiany):

Relacje (dodać):
- public function businessMemberships(): HasMany → BusinessUser
- public function businesses(): BelongsToMany
    przez business_users, z: role, is_active

Helper (dodać):
- public function currentBusiness(): ?Business
    return $this->businesses()->wherePivot('is_active', true)->first();
    
    Uwaga: W MVP jest zwykle jeden business per user. W v1.1 (multi-business)
    będzie wybierane z session lub subdomeny.
```

---

**Globalny helper `currentBusiness()` — `app/Helpers/BusinessHelper.php`**

```php
// Rejestracja w AppServiceProvider lub przez autoload
function currentBusiness(): ?App\Models\Business
{
    if (!auth()->check()) return null;
    return auth()->user()->currentBusiness();
}
```

Rejestracja pliku w `composer.json` → `autoload.files`:
```json
"autoload": {
  "files": ["app/Helpers/BusinessHelper.php"]
}
```

---

### 4.2 Trait `BelongsToTenant`

```
PLIK: app/Traits/BelongsToTenant.php
Cel: Zarezerwowany szkielet dla GlobalScope multi-tenancy (v1.1).
     W MVP: nie aktywuje GlobalScope, tylko auto-fill business_id przy tworzeniu.

Zachowanie w MVP:
- boot() → static::creating() → $model->business_id = currentBusiness()?->id

Zachowanie w v1.1 (odkomentować):
- boot() → static::addGlobalScope(new BusinessScope())

Modele które będą używać traitu (Sprint 1 — TYLKO nowe tabele):
- LandingPage (Sprint 2)
- Business nie używa własnego traitu (jest root tenanta)

Modele istniejące (NIE dodajemy traitu w Sprint 1 — ryzyko regresji):
- Lead, Client, Project, Invoice etc. → Sprint 3+ (multi-tenancy enforcement)
```

---

### 4.3 Middleware `EnsureHasBusiness`

```
PLIK: app/Http/Middleware/EnsureHasBusiness.php

Logika:
1. Jeśli user jest niezalogowany → nie rób nic (Auth middleware obsłuży)
2. Jeśli user zalogowany i ma aktywny Business → continue
3. Jeśli user zalogowany i NIE ma Business → redirect('/onboarding') z flash warning

Stosować na trasach wymagających Business (nie na auth/guest):
- /dashboard, /admin (Filament), /business/*, /lp/* (backend)

Nie stosować na:
- /onboarding/* (pętla redirect)
- /auth/* 
- Publiczne trasy /lp/{slug}

Rejestracja w bootstrap/app.php:
->withMiddleware(function (Middleware $middleware) {
    $middleware->alias(['has.business' => EnsureHasBusiness::class]);
})
```

---

### 4.4 Event i Listener

```
EVENT: app/Events/BusinessCreated.php
Konstruktor: public function __construct(public readonly Business $business)
Implements: ShouldBroadcastNow? → NIE w MVP (nie używamy WebSocket dla tego)

Używane przez:
- BusinessService::create() → event(new BusinessCreated($business))
- Potencjalny Listener (v1.1): tworzenie default AutomationRule per business

---

EVENT: app/Events/BusinessProfileUpdated.php
Konstruktor: public function __construct(public readonly BusinessProfile $profile)

Używane przez:
- BusinessProfileService::update() → event(new BusinessProfileUpdated($profile))
- Potencjalny Listener (v1.1): invalidacja ai_context_cache w LP Generator
```

---

### 4.5 Serwisy

---

**`app/Services/Business/BusinessService.php`**

```
Odpowiedzialność: Tworzenie i zarządzanie encją Business (tenant root).

Metody publiczne:

- createForUser(User $user, array $data): Business
  Parametry: User, dane = {name: string, locale?: string}
  Zwraca: Business z relacją BusinessUser (role=owner)
  Logika:
    1. Wygeneruj slug z name (Str::slug + unikalność check)
    2. Utwórz rekord businesses
    3. Utwórz rekord business_users z role='owner', joined_at=now()
    4. Utwórz pusty rekord business_profiles (1:1 — zawsze istnieje)
    5. Dispatch event BusinessCreated
    6. Zwróć Business
  Uwaga: wywoływane tuż po rejestracji użytkownika

- update(Business $business, array $data): Business
  Parametry: Business, dane = {name?, locale?, timezone?, primary_color?}
  Logika:
    1. Walidacja slug uniqueness jeśli name zmienione
    2. Utwórz nowy slug jeśli name zmienione (slug immutable po publish LP — sprawdź)
    3. Update business
    4. Aktualizuj logo_path jeśli data['logo'] — deleguj do uploadLogo()
  
- uploadLogo(Business $business, UploadedFile $file): string
  Parametry: Business, plik obrazu
  Logika:
    1. Walidacja: image, max:2048, mimes:jpg,png,webp
    2. Usuń stary plik jeśli istnieje (Storage::disk('public')->delete($oldPath))
    3. Zapisz do: storage/app/public/businesses/{business_id}/logo.{ext}
    4. Update business.logo_path
    5. Zwróć public URL path
  Bezpieczeństwo: nie ujawniaj ścieżek serwera; zawsze przez Storage::url()

Zależy od: nic bezpośrednio (nie wstrzykuje innych serwisów)
```

---

**`app/Services/Business/BusinessProfileService.php`**

```
Odpowiedzialność: Zarządzanie danymi profilowymi firmy (brand, AI context, dane marketingowe).

Metody publiczne:

- update(BusinessProfile $profile, array $data): BusinessProfile
  Parametry: BusinessProfile, dane formularza
  Logika:
    1. Filtruj dozwolone pola przez array_intersect_key
    2. Sanitize: brand_colors → upewnij się że primary jest hex (#rrggbb lub #rgb)
    3. Update profile
    4. Invaliduj ai_context_cache (wyzeruj ai_context_updated_at)
    5. Dispatch event BusinessProfileUpdated
    6. Zwróć zaktualizowany profil
  
- getOrCreate(Business $business): BusinessProfile
  Logika: firstOrCreate(['business_id' => $business->id])
  Używane przy pierwszym wyświetleniu formularza profilu

- getAiContext(Business $business): array
  Logika:
    1. Pobierz profil przez getOrCreate()
    2. Zwróć tablicę dla prompt OpenAI:
       ['brand_name' => $business->name,
        'tagline' => $profile->tagline,
        'industry' => $profile->industry,
        'tone_of_voice' => $profile->tone_of_voice ?? 'professional',
        'target_audience' => $profile->target_audience ?? [],
        'services' => $profile->services ?? [],
        'primary_color' => $profile->getPrimaryColorAttribute(),
        'language' => $business->locale]
  Używana przez LP Generator (v1.1) — zarezerwowane

- isComplete(BusinessProfile $profile): bool
  Sprawdza czy profil ma minimum wymaganych pól do generowania LP:
  - tagline NOT NULL
  - industry NOT NULL
  - tone_of_voice NOT NULL
  - services NOT NULL i niepuste
  Zwraca boolean + używana w UI do wskaźnika completion %

Zależy od: nic bezpośrednio
```

---

### 4.6 Form Requests

---

**`app/Http/Requests/Business/StoreBusinessRequest.php`**

```
Reguły walidacji:
- name:     required|string|min:2|max:255
- locale:   nullable|string|in:en,pl,pt
- timezone: nullable|string|timezone     — używana walidacja 'timezone' z Laravel

Autoryzacja: $this->user()->cannot('create_business') → false; else true
  (w MVP bez Spatie check — każdy zalogowany user może stworzyć business)
  W v1.1: dodać limit "max 1 business per user on free plan"
```

---

**`app/Http/Requests/Business/UpdateBusinessRequest.php`**

```
Reguły walidacji:
- name:          nullable|string|min:2|max:255
- locale:        nullable|string|in:en,pl,pt
- timezone:      nullable|string|timezone
- primary_color: nullable|string|regex:/^#([0-9a-fA-F]{3}|[0-9a-fA-F]{6})$/
- logo:          nullable|image|mimes:jpg,jpeg,png,webp|max:2048    — max 2MB

Autoryzacja: sprawdź czy user jest właścicielem/adminem business
  $this->user()->businessMemberships()
       ->where('business_id', $this->route('business')->id)
       ->whereIn('role', ['owner', 'admin'])
       ->exists()
```

---

**`app/Http/Requests/Business/UpdateProfileRequest.php`**

```
Reguły walidacji:
- tagline:           nullable|string|max:255
- description:       nullable|string|max:2000
- industry:          nullable|string|max:100
- tone_of_voice:     nullable|string|in:professional,friendly,bold,minimalist
- website_url:       nullable|url|max:500
- services:          nullable|array|max:20
- services.*:        string|max:100
- target_audience:   nullable|array
- target_audience.age_range:   nullable|string|max:50
- target_audience.gender:      nullable|string|in:male,female,mixed
- target_audience.interests:   nullable|array
- target_audience.interests.*: string|max:100
- brand_colors:                nullable|array
- brand_colors.primary:        nullable|string|regex:/^#([0-9a-fA-F]{3}|[0-9a-fA-F]{6})$/
- brand_colors.secondary:      nullable|string|regex:/^#([0-9a-fA-F]{3}|[0-9a-fA-F]{6})$/
- social_links:                nullable|array
- social_links.facebook:       nullable|url
- social_links.instagram:      nullable|url
- social_links.linkedin:       nullable|url
- seo_keywords:                nullable|array|max:20
- seo_keywords.*:              string|max:100
- fonts.heading:               nullable|string|max:100
- fonts.body:                  nullable|string|max:100

Autoryzacja: ta sama co UpdateBusinessRequest — member business lub admin
```

---

### 4.7 Kontrolery

---

**`app/Http/Controllers/Business/BusinessController.php`**

```
Trasy (dodać do routes/web.php):

Route::middleware(['auth', 'verified', 'has.business'])->group(function () {
    Route::get('/business/settings', [BusinessController::class, 'edit'])
        ->name('business.edit');
    Route::patch('/business/settings', [BusinessController::class, 'update'])
        ->name('business.update');
    Route::post('/business/logo', [BusinessController::class, 'uploadLogo'])
        ->name('business.logo.upload');
    Route::delete('/business/logo', [BusinessController::class, 'deleteLogo'])
        ->name('business.logo.delete');
});

Metody:
- edit(): 
    $business = currentBusiness()->load('profile');
    return Inertia::render('Business/Settings', [
        'business' => $business->only(['id','name','locale','timezone','logo_url','primary_color','plan']),
        'profile'  => $business->profile,
    ]);

- update(UpdateBusinessRequest $request):
    $this->businessService->update(currentBusiness(), $request->validated());
    return redirect()->back()->with('success', __('business.settings_saved'));

- uploadLogo(Request $request):
    $path = $this->businessService->uploadLogo(currentBusiness(), $request->file('logo'));
    return response()->json(['logo_url' => Storage::disk('public')->url($path)]);

- deleteLogo():
    $this->businessService->deleteLogo(currentBusiness());
    return redirect()->back()->with('success', __('business.logo_deleted'));
```

---

**`app/Http/Controllers/Business/BusinessProfileController.php`**

```
Trasy (dodać do routes/web.php):

Route::middleware(['auth', 'verified', 'has.business'])->group(function () {
    Route::get('/business/profile', [BusinessProfileController::class, 'edit'])
        ->name('business.profile.edit');
    Route::patch('/business/profile', [BusinessProfileController::class, 'update'])
        ->name('business.profile.update');
    Route::get('/business/profile/completion', [BusinessProfileController::class, 'completion'])
        ->name('business.profile.completion');
});

Metody:
- edit():
    $profile = $this->profileService->getOrCreate(currentBusiness());
    return Inertia::render('Business/Profile', [
        'profile'       => $profile,
        'business'      => currentBusiness()->only(['id','name','logo_url']),
        'isComplete'    => $this->profileService->isComplete($profile),
        'industries'    => config('business.industries'),     — lista z config
        'tonesOfVoice'  => config('business.tones_of_voice'), — lista z config
    ]);

- update(UpdateProfileRequest $request):
    $profile = $this->profileService->getOrCreate(currentBusiness());
    $this->profileService->update($profile, $request->validated());
    return redirect()->back()->with('success', __('business.profile_saved'));

- completion():
    Zwraca JSON: {complete: bool, percentage: int, missing: string[]}
    Używane przez Inertia / AJAX do wskaźnika onboarding completion
```

---

**Rozszerzenie `RegisteredUserController.php`**

```
Zmiana w metodzie store():
Po: Auth::login($user);
Przed: return redirect(...)

Dodać:
    // Tworzy Business i BusinessUser(owner) — tylko jeśli nie wskazano company_name
    // (request ma opcjonalne 'company_name' — dodamy pole do formularza rejestracji)
    $companyName = $request->input('company_name', $user->name . "'s Business");
    app(BusinessService::class)->createForUser($user, [
        'name'   => $companyName,
        'locale' => $request->input('locale', app()->getLocale()),
    ]);

Zmiana walidacji store() — dodać:
    'company_name' => 'nullable|string|max:255',
```

---

**`app/Http/Controllers/Onboarding/OnboardingController.php`**

```
Cel: Wizard onboardingu — 2 kroki po rejestracji.
     Wyświetlany tylko gdy profil jest niekompletny.

Trasy (dodać do routes/web.php):

Route::middleware(['auth', 'verified'])->prefix('onboarding')->name('onboarding.')->group(function () {
    Route::get('/', [OnboardingController::class, 'index'])->name('index');
    Route::get('/profile', [OnboardingController::class, 'profile'])->name('profile');
    Route::post('/profile', [OnboardingController::class, 'saveProfile'])->name('profile.save');
    Route::get('/complete', [OnboardingController::class, 'complete'])->name('complete');
});

Metody:
- index():
    Sprawdź status onboardingu → redirect do właściwego kroku lub /admin (Filament)

- profile():
    return Inertia::render('Onboarding/Profile', [
        'business' => currentBusiness(),
        'profile'  => currentBusiness()->profile,
        'step'     => 1,
        'totalSteps' => 2,
        'industries'    => config('business.industries'),
        'tonesOfVoice'  => config('business.tones_of_voice'),
    ]);

- saveProfile(UpdateProfileRequest $request):
    Zapisz profil → redirect('/onboarding/complete')

- complete():
    return Inertia::render('Onboarding/Complete', [
        'business' => currentBusiness(),
    ]);
    Po 3 sekundach redirect do /admin (JavaScript lub meta-refresh)
```

---

### 4.8 Seeder — rozszerzenie `AdminSeeder.php`

```
Dodać nowe uprawnienia Spatie do tablicy $permissions:

// Business Profile
'manage_business_profile',      — edycja profilu firmy
'view_business_settings',       — widok ustawień firmy
'manage_business_settings',     — edycja ustawień firmy (name, locale, logo)

Przypisanie do ról:
- admin:     'manage_business_profile', 'view_business_settings', 'manage_business_settings'
- manager:   'manage_business_profile', 'view_business_settings'
- developer: 'view_business_settings' (read-only)
- client:    brak

Seedowanie testowego Business przy dev/seeding:
W AdminSeeder::run() po tworzeniu admin user:
    $business = Business::firstOrCreate(
        ['slug' => 'website-expert'],
        ['name' => 'WebsiteExpert Ltd', 'locale' => 'en', 'plan' => 'pro', 'is_active' => true]
    );
    BusinessUser::firstOrCreate(
        ['business_id' => $business->id, 'user_id' => $adminUser->id],
        ['role' => 'owner', 'joined_at' => now()]
    );
    $business->profile()->firstOrCreate(['business_id' => $business->id]);
```

---

### 4.9 Konfiguracja `config/business.php`

```php
// config/business.php — nowy plik konfiguracyjny

return [
    'industries' => [
        'marketing_agency'    => 'Marketing Agency',
        'web_design'          => 'Web Design & Development',
        'ecommerce'           => 'E-commerce',
        'consulting'          => 'Business Consulting',
        'real_estate'         => 'Real Estate',
        'healthcare'          => 'Healthcare',
        'education'           => 'Education',
        'legal'               => 'Legal Services',
        'finance'             => 'Finance & Accounting',
        'hospitality'         => 'Hospitality & Travel',
        'fitness'             => 'Fitness & Wellness',
        'other'               => 'Other',
    ],

    'tones_of_voice' => [
        'professional' => 'Professional & Formal',
        'friendly'     => 'Friendly & Approachable',
        'bold'         => 'Bold & Confident',
        'minimalist'   => 'Minimalist & Clean',
    ],

    'plan_limits' => [
        'free'     => ['landing_pages' => 1,  'leads_per_month' => 50],
        'starter'  => ['landing_pages' => 5,  'leads_per_month' => 200],
        'pro'      => ['landing_pages' => 20, 'leads_per_month' => 1000],
        'agency'   => ['landing_pages' => -1, 'leads_per_month' => -1],  // -1 = unlimited
    ],
];
```

---

### 4.10 Migracje — kolejność i nazwy

```
Migracje do stworzenia (w tej kolejności):

1. 2026_03_31_000001_create_businesses_table.php
   Tworzy: businesses (id ULID, name, slug, locale, timezone, logo_path,
                       primary_color, plan, is_active, trial_ends_at,
                       stripe_customer_id NULLABLE, settings JSON NULLABLE,
                       created_at, updated_at, deleted_at)

2. 2026_03_31_000002_create_business_users_table.php
   Tworzy: business_users (id, business_id FK, user_id FK, role,
                           is_active, invited_by FK NULLABLE, joined_at,
                           created_at, updated_at)
   Indeksy: UNIQUE(business_id, user_id), INDEX(user_id)

3. 2026_03_31_000003_create_business_profiles_table.php
   Tworzy: business_profiles (id, business_id FK UNIQUE, tagline, description,
                              industry, tone_of_voice, target_audience JSON,
                              services JSON, brand_colors JSON, fonts JSON,
                              website_url, social_links JSON, seo_keywords JSON,
                              ai_context_cache MEDIUMTEXT NULLABLE,
                              ai_context_updated_at NULLABLE,
                              created_at, updated_at)
```

---

## 5. Frontend Inertia + React

### 5.1 Nowe pliki do stworzenia

```
resources/js/
├── Pages/
│   ├── Onboarding/
│   │   ├── Profile.jsx          — krok 1: uzupełnij profil firmy (onboarding wizard)
│   │   └── Complete.jsx         — krok 2: sukces + redirect do /admin
│   └── Business/
│       ├── Settings.jsx         — ustawienia firmy (name, locale, logo, primary_color)
│       └── Profile.jsx          — profil marki (tagline, industry, tone, colors, services)
├── Components/
│   └── Business/
│       ├── LogoUploader.jsx     — upload + preview logo z drag-and-drop
│       ├── ColorPicker.jsx      — input hex color z preview swatcha
│       ├── ServicesList.jsx     — dynamiczna lista usług (add/remove tags)
│       ├── ToneSelector.jsx     — radio cards dla tone of voice
│       └── ProfileCompletionBar.jsx — pasek postępu wypełnienia profilu (%)
```

---

### 5.2 `Pages/Onboarding/Profile.jsx`

```
Cel: Pierwsza strona po rejestracji — uzupełnij dane firmy aby zacząć

Props (z kontrolera):
- business: { id, name, locale, logo_url }
- profile:  { tagline, industry, tone_of_voice, services[], brand_colors }
- step: number (1)
- totalSteps: number (2)
- industries: Record<string, string>
- tonesOfVoice: Record<string, string>

State/Form (useForm):
- tagline: string
- industry: string (select)
- tone_of_voice: string (ToneSelector)
- services: string[] (ServicesList)
- brand_colors.primary: string (ColorPicker)
- website_url: string

Layout: GuestLayout lub dedykowany OnboardingLayout (bez sidebar Filament)
  - Header: logo + "Step 1 of 2" 
  - Progres bar: 50% na step 1
  - Formularz
  - CTA: "Save & Continue" → POST /onboarding/profile → redirect /onboarding/complete

UX:
- Każde pole ma placeholder z przykładem ("Twój sukces w internecie")
- Pola opcjonalne oznaczone "(optional)"
- Podgląd na żywo primary color w ColorPicker (live preview swatcha)
- Można pominąć krok ("Skip for now" → /admin) — profil można uzupełnić później
```

---

### 5.3 `Pages/Onboarding/Complete.jsx`

```
Cel: Sukces — potwierdzenie zakończenia onboardingu

Props:
- business: { name, logo_url }

UI:
- Ikona sukcesu (check circle)
- "You're all set, {business.name}!"
- "Redirecting to your dashboard in 3 seconds..."
- Ręczny link "Go to Dashboard →" → /admin
- Auto-redirect po 3 sekundach (useEffect + setTimeout → window.location.href = '/admin')

Layout: minimalistyczny, centered, bez nawigacji
```

---

### 5.4 `Pages/Business/Settings.jsx`

```
Cel: Ustawienia firmowe — dostępne z panelu (Settings → Business Settings)

Props:
- business: { id, name, locale, timezone, logo_url, primary_color, plan }
- profile:  pełny BusinessProfile object

Sekcje formularza:
1. **General** — name, locale (EN/PL/PT select), timezone
2. **Appearance** — Logo upload (LogoUploader), primary_color (ColorPicker)
3. **Plan** — wyświetl aktualny plan (read-only w MVP, "Upgrade" button → placeholder)

UX:
- useForm z oddzielnymi sekcjami lub jeden duży patchForm
- Submit: PATCH /business/settings
- Logo: osobny endpoint POST /business/logo (async, nie w głównym formularzu)
- Flash message: "Settings saved successfully"
- Walidacja inline: color picker tylko hex, logo max 2MB

Dostępność z Filament:
- Nie jest stroną Filament — jest stroną Inertia, linkowaną z menu
- Link "Business Settings" w user menu Filament (topbar) lub w Navigation Group "Settings"
- Alternatywnie: Filament Page wrapper przekierowujący do Inertia URL
```

---

### 5.5 `Pages/Business/Profile.jsx`

```
Cel: Edycja profilu marki — dostępna z panelu (Settings → Brand Profile)

Props:
- profile: pełny BusinessProfile object
- business: { id, name, logo_url }
- isComplete: boolean
- industries: Record<string, string>
- tonesOfVoice: Record<string, string>

Sekcje formularza:
1. **Brand Identity** — tagline, description (textarea), industry (select)
2. **Tone of Voice** — ToneSelector (4 radio cards z opisem: Professional / Friendly / Bold / Minimalist)
3. **Brand Colors** — ColorPicker dla primary + secondary (opcjonalne)
4. **Target Audience** — age_range (select), gender (radio), interests (tags input)
5. **Services** — ServicesList (dynamic add/remove)
6. **Online Presence** — website_url, social_links (facebook, instagram, linkedin)
7. **SEO** — seo_keywords (tags input, max 20)

UX:
- ProfileCompletionBar na górze strony: "Profile 40% complete — add more details for better AI results"
- Pola opcjonalne z tooltipem "Used by AI Generator to create personalized content"
- Zapis per-sekcja (Save section) lub jeden globalny Save button
- Rekomendacja: globalny Save (prostsze w MVP)
- Submit: PATCH /business/profile
```

---

### 5.6 Komponenty

**`Components/Business/LogoUploader.jsx`**
```
Props:
- currentUrl: string|null      — URL aktualnego logo
- onUpload: (file: File) => void
- onDelete: () => void
- disabled?: boolean

Funkcje:
- Drag & drop + click to upload
- Podgląd uploaded image (FileReader API)
- Walidacja client-side: type=image/*, max 2MB
- Usunięcie logo (DELETE /business/logo)
- Wyświetla initials placeholder gdy brak logo (np. "WE" dla "WebsiteExpert")
```

**`Components/Business/ColorPicker.jsx`**
```
Props:
- value: string              — hex value "#ff2b17"
- onChange: (hex: string) => void
- label?: string

Funkcje:
- Input type="color" (native browser color picker)
- Obok: input text dla hex wpisanego ręcznie
- Walidacja hex: /^#([0-9a-fA-F]{3}|[0-9a-fA-F]{6})$/
- Swatch preview (div z background-color = value)
- Predefiniowane kolory (8 sugestii brandowych)
```

**`Components/Business/ServicesList.jsx`**
```
Props:
- value: string[]
- onChange: (services: string[]) => void
- max?: number (default: 20)

Funkcje:
- Lista tagów z możliwością usunięcia (×)
- Input do dodania nowej usługi + Enter/przycisk Add
- Max limit z komunikatem
- Sortable (opcjonalnie drag-and-drop w v1.1)
```

**`Components/Business/ToneSelector.jsx`**
```
Props:
- value: string             — 'professional'|'friendly'|'bold'|'minimalist'
- onChange: (tone: string) => void
- options: Record<string, string>  — z config/business.php przez props

Funkcje:
- Siatka 2×2 kart (radio cards)
- Każda karta: ikona + nazwa + opis tonu ("Formal language, expert positioning")
- Aktywna karta: highlighted border w primary_color
```

**`Components/Business/ProfileCompletionBar.jsx`**
```
Props:
- percentage: number (0-100)
- missingFields: string[]

Funkcje:
- Progress bar z kolorowym gradientem (czerwony → żółty → zielony)
- Tekst: "Profile X% complete"
- Tooltip/lista brakujących pól po hover
- Schować gdy percentage === 100
```

---

### 5.7 Layout dla onboardingu

**`Layouts/OnboardingLayout.jsx`**
```
Cel: Minimalistyczny layout dla wizard onboardingu (bez pełnej nawigacji)

Struktura:
- Header: logo (text "Digital Growth OS") + "Step X of Y"
- Content: centrowany formularz (max-w-2xl mx-auto)
- Footer: brak lub "Need help? Contact us"

Tailwind: bg-gray-50 min-h-screen flex flex-col items-center justify-center
```

---

### 5.8 Filament — integracja

**Nowa Filament Page: `app/Filament/Pages/BusinessSettingsPage.php`**
```
Cel: Dostęp do Business Profile z panelu Filament w grupie "Settings"

Podejście: Filament Custom Page renderująca Blade view z linkiem do Inertia
  → Prostsza opcja dla MVP: strona przekierowuje do /business/profile (Inertia URL)
  → Oznacza że kliknięcie "Brand Profile" w Filament otwiera Inertia page

Nawigacja:
- navigationGroup: 'Settings'
- navigationIcon: 'heroicon-o-building-office'
- navigationLabel: 'Brand Profile'
- navigationSort: 1

Implementacja MVP:
Filament Page z jedną metodą mount() → return redirect()->to('/business/profile');
(Inertia page ładuje się poza Filament shell — akceptowalne w MVP)

Alternatywa (v1.1): Pełny Filament Resource z Form Schema dla business_profiles
```

**Rozszerzenie `AdminPanelProvider.php`**
```
Zmiana brandName:
  Zamiast hardcoded 'WebsiteExpert':
  ->brandName(fn() => currentBusiness()?->name ?? 'Digital Growth OS')

Zmiana topbar renderHook — dodać business info:
  W topbar: wyświetlać logo business (jeśli istnieje) + name
  Obok istniejących pinowanych notatek

Navigation Group — dodać:
  NavigationGroup::make('Business') — nowa grupa nad 'Settings'
  zawiera: BusinessSettingsPage, (w v1.1) TeamMembersPage
```

---

## 6. API — endpointy

> MVP nie wymaga pełnego REST API. Wszystkie endpointy są Inertia-driven (server-side render z redirect).
> Jedynym "API-like" endpointem jest upload logo (async JSON response).

```
GET    /business/settings                → BusinessController::edit()         [auth, has.business]
PATCH  /business/settings                → BusinessController::update()        [auth, has.business]
POST   /business/logo                    → BusinessController::uploadLogo()    [auth, has.business]
DELETE /business/logo                    → BusinessController::deleteLogo()    [auth, has.business]

GET    /business/profile                 → BusinessProfileController::edit()   [auth, has.business]
PATCH  /business/profile                 → BusinessProfileController::update() [auth, has.business]
GET    /business/profile/completion      → BusinessProfileController::completion() [auth, has.business]

GET    /onboarding                       → OnboardingController::index()       [auth, verified]
GET    /onboarding/profile               → OnboardingController::profile()     [auth, verified]
POST   /onboarding/profile               → OnboardingController::saveProfile() [auth, verified]
GET    /onboarding/complete              → OnboardingController::complete()    [auth, verified]
```

**Uwagi bezpieczeństwa:**
- Brak publicznych (unauthenticated) API endpoints w tym module
- Logo upload: walidacja MIME type server-side (nie ufamy `Content-Type` z requestu), skanowanie przez pillow/imagick opcjonalnie
- CSRF protection: wszystkie POST/PATCH/DELETE przez standardowe CSRF Laravel (nie wyłączane)
- Rate limiting: logo upload max 10/minutę (`throttle:10,1`)
- Nie zwracamy pełnych ścieżek serwera — tylko Storage::url() public paths

---

## 7. Workflow użytkownika

### 7.1 Scenariusz: Pierwsza rejestracja

```
1. Użytkownik wchodzi na /register
2. Wypełnia: name, email, password, (opcjonalnie) company_name
3. POST /register:
   a. Tworzy User (Laravel Breeze)
   b. ⟶ BusinessService::createForUser() → tworzy Business + BusinessUser(owner) + pusty BusinessProfile
   c. Dispatch: BusinessCreated event
   d. Auth::login($user)
4. Redirect → /onboarding (nie /dashboard — bo profil niekompletny)

5. /onboarding → OnboardingController::index():
   a. Sprawdź: czy profile.isComplete()? → TAK: redirect /admin, NIE: redirect /onboarding/profile

6. /onboarding/profile:
   a. Użytkownik widzi formularz: tagline, industry, tone_of_voice, services, brand_colors.primary
   b. Opcja "Skip for now" → redirect /admin
   c. Submit → POST /onboarding/profile → zapis → redirect /onboarding/complete

7. /onboarding/complete:
   a. Wiadomość sukcesu
   b. Auto-redirect po 3 sek do /admin

8. /admin (Filament Panel):
   a. brandName = business.name
   b. W grupie "Settings": "Brand Profile" link do /business/profile
```

---

### 7.2 Scenariusz: Edycja profilu z panelu

```
1. Użytkownik w /admin → kliknie "Brand Profile" w grupie "Settings"
2. Redirect do /business/profile (Inertia page, poza Filament shell)
3. Widzi formularz — 7 sekcji profilu
4. Zmienia usługi: ServicesList — usuwa stare, dodaje nowe
5. Zmienia kolor brand: ColorPicker — wybiera na kole barw lub wpisuje hex
6. Submit → PATCH /business/profile → BusinessProfileService::update()
7. Dispatch: BusinessProfileUpdated
8. Flash: "Profile saved" → back to form (redirect()->back())
9. ProfileCompletionBar aktualizuje się: np. "Profile 80% complete"
```

---

### 7.3 Scenariusz: Upload logo

```
1. Użytkownik na /business/settings, sekcja Appearance
2. Klika na LogoUploader lub drag-and-drop plik PNG
3. Client-side preview: pokazuje miniaturkę
4. Auto-upload (useEffect lub onChange → Axios POST /business/logo):
   - multipart/form-data
   - Response: {logo_url: "https://..."}
5. Zaktualizuj UI: podmień placeholder initials na <img> z logo_url
6. Brak osobnego "Save" dla logo — zapis asynchroniczny

Alternatywnie: logo w głównym formularzu PATCH /business/settings
(prostsze, mniej kodu, ale gorsze UX przy dużych plikach)
Rekomendacja MVP: async upload (oddzielny endpoint)
```

---

## 8. Integracja z istniejącymi modułami

### 8.1 CRM (istniejące modele — brak zmian w Sprint 1)

```
Lead, Client, Project, Invoice — NIE dodajemy business_id w Sprint 1
Powód: ryzyko regresji istniejących testów, portal klienta, automatyzacje

Plan integracji (Sprint 3):
- Migracja addytywna: dodać business_id NULLABLE do leads (przed Lead Capture)
- Migracja addytywna: dodać business_id NULLABLE do clients
- Aktualizować CreateLeadAction: przypisać currentBusiness()->id
- Istniejące rekordy (admin agencji): business_id = seeded Business ID
  (AdminSeeder tworzy testowe Business — patrz 4.8)
```

### 8.2 Filament Panel

```
Ostrożne zmiany w AdminPanelProvider:
- brandName: fn() => currentBusiness()?->name ?? 'Digital Growth OS'
  RYZYKO: currentBusiness() może zwrócić null przy logowaniu — null-safe operator wymagany
  
- Nowa grupa nawigacji "Business" (przed "Settings")
- Dodać BusinessSettingsPage do auto-discover (lub ręczne rejestrowanie)

Bez zmian (zachować 100%):
- Wszystkie istniejące Resources, Pages, Widgets
- clientNotifications, renderHooks
- pinowane notatki leadów w topbarze
```

### 8.3 Auth Flow

```
Zmiana w RegisteredUserController::store():
- Dodać pole 'company_name' (nullable) do walidacji
- Po Auth::login() → wywołać BusinessService::createForUser()
- Zmienić redirect: z route('dashboard') na route('onboarding.index')

Brak zmian w:
- LoginController (Filament + Breeze) — pozostają bez zmian  
- PasswordResetLinkController — bez zmian
- VerifyEmailController — bez zmian

EnsureHasBusiness middleware:
- Stosować NA: /dashboard, /business/*, /onboarding/* (sprawdz onboarding loop)
- NIE stosować na: /admin (Filament ma własne auth), publiczne trasy
- Uwaga: /admin (Filament) nie przechodzi przez web middleware w standardowy sposób
  — Filament Panel ma swoje middleware stack w AdminPanelProvider
  — Dodać middleware w AdminPanelProvider::panel() → ->middleware([EnsureHasBusiness::class])
    LUB sprawdzić w canAccessPanel() → user must have business
```

### 8.4 Tłumaczenia (i18n)

```
Dodać do plików językowych:

lang/en/business.php:
- 'settings_saved'  => 'Business settings saved successfully.',
- 'profile_saved'   => 'Brand profile saved successfully.',
- 'logo_deleted'    => 'Logo removed successfully.',
- 'onboarding_welcome' => 'Welcome! Let\'s set up your business profile.',
- 'profile_completion' => ':percent% complete',
- 'profile_incomplete_hint' => 'Complete your profile to get better AI-generated content.',

lang/pl/business.php: (odpowiedniki PL)
lang/pt/business.php: (odpowiedniki PT)
```

---

## 9. Checklist implementacji

### Backend

- [ ] Migracja `businesses` (1/3)
- [ ] Migracja `business_users` (2/3)
- [ ] Migracja `business_profiles` (3/3)
- [ ] Model `Business` (HasUlids, SoftDeletes, relacje, akcesory)
- [ ] Model `BusinessUser` (relacje, scopes)
- [ ] Model `BusinessProfile` (relacje, casts, toAiContext())
- [ ] Rozszerzenie `User` model (relacje, currentBusiness() helper)
- [ ] Plik `app/Helpers/BusinessHelper.php` + autoload w composer.json
- [ ] Trait `BelongsToTenant` (szkielet bez GlobalScope)
- [ ] Middleware `EnsureHasBusiness`
- [ ] Events: `BusinessCreated`, `BusinessProfileUpdated`
- [ ] `BusinessService` (createForUser, update, uploadLogo, deleteLogo)
- [ ] `BusinessProfileService` (update, getOrCreate, getAiContext, isComplete)
- [ ] Form Requests: `StoreBusinessRequest`, `UpdateBusinessRequest`, `UpdateProfileRequest`
- [ ] `BusinessController` (edit, update, uploadLogo, deleteLogo)
- [ ] `BusinessProfileController` (edit, update, completion)
- [ ] `OnboardingController` (index, profile, saveProfile, complete)
- [ ] Rozszerzenie `RegisteredUserController` + pola company_name
- [ ] Rejestracja tras w `routes/web.php`
- [ ] Rejestracja middleware w `bootstrap/app.php`
- [ ] `config/business.php`
- [ ] Aktualizacja `AdminSeeder` (nowe permissions + Business seed)
- [ ] Pliki tłumaczeń `lang/*/business.php`

### Filament

- [ ] `BusinessSettingsPage` (przekierowanie do Inertia)
- [ ] Rozszerzenie `AdminPanelProvider` (brandName dynamic, nowa nawigacja)
- [ ] `EnsureHasBusiness` w middleware panelu Filament (opcjonalne w MVP)

### Frontend

- [ ] Layout `OnboardingLayout.jsx`
- [ ] `Pages/Onboarding/Profile.jsx`
- [ ] `Pages/Onboarding/Complete.jsx`
- [ ] `Pages/Business/Settings.jsx`
- [ ] `Pages/Business/Profile.jsx`
- [ ] `Components/Business/LogoUploader.jsx`
- [ ] `Components/Business/ColorPicker.jsx`
- [ ] `Components/Business/ServicesList.jsx`
- [ ] `Components/Business/ToneSelector.jsx`
- [ ] `Components/Business/ProfileCompletionBar.jsx`

### Testy

- [ ] Feature Test: `tests/Feature/Business/BusinessRegistrationTest.php`
  - Rejestracja tworzy Business + BusinessUser(owner) + BusinessProfile
  - Redirect po rejestracji: /onboarding (nie /dashboard)
  - Brak Business → redirect /onboarding z EnsureHasBusiness
- [ ] Feature Test: `tests/Feature/Business/BusinessProfileTest.php`
  - Aktualizacja profilu zapisuje dane
  - Aktualizacja profilu dispatcha BusinessProfileUpdated event
  - Logo upload zapisuje plik
  - Nieautoryzowany dostęp zwraca 403
- [ ] Feature Test: `tests/Feature/Business/OnboardingTest.php`
  - Kompletny flow: rejestracja → onboarding → /admin
  - Skip onboarding działa

---

## 10. Ryzyka i decyzje techniczne

| Ryzyko | Poziom | Mitygacja |
|---|---|---|
| `currentBusiness()` zwraca null w middleware Filament (admin nie ma Business) | HIGH | Seeder tworzy Business dla admin usera; EnsureHasBusiness nie stosować na Filament bez `null-safe check`; `canAccessPanel()` może sprawdzić business w v1.1 |
| ULID jako FK w business_users i business_profiles — czy działa z istniejącą bazą | MEDIUM | ULID to char(26) — poprawny FK w MySQL/PostgreSQL. Upewnić się że migracje tworzą ULID nie UUID (`$table->ulid('business_id')`) |
| Upload logo — path traversal, MIME spoofing | HIGH | Walidacja server-side: mimes:jpg,jpeg,png,webp; użyj `$file->hashName()` nie oryginalnej nazwy; zapisuj poza webroot (`storage/app/public/`) |
| Istniejące Feature Tests mogą failować po zmianie redirect w RegisteredUserController | MEDIUM | `tests/Feature/Auth/RegistrationTest.php` musi być zaktualizowany: expected redirect = `/onboarding` zamiast `/dashboard` |
| Filament brandName jako closure może spowalniać każdy request | LOW | Cachować `currentBusiness()` w request scope (singleton per request w AppServiceProvider) |
| `EnsureHasBusiness` + onboarding pętla redirect | LOW | Nie stosować middleware na trasach `/onboarding/*` — sprawdzić konfigurację grup routerów |

---

*Specyfikacja gotowa do implementacji po zatwierdzeniu przez tech lead / product owner.*  
*Następny krok po zatwierdzeniu: `laravel-backend-impl` dla Sprintu 1 — backend Business Profile.*
