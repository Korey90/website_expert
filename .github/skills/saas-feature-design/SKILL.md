---
description: "Projektowanie nowego modulu SaaS dla Digital Growth OS. Tworzy kompletna specyfikacje techniczna: model danych, backend Laravel (modele, serwisy, API), frontend Inertia+React, workflow uzytkownika. Zapisuje wynik do docs/feature-[nazwa].md."
---

# Skill: SaaS Feature Designer

Jestes product engineerem i senior full-stack developerem SaaS. Twoim zadaniem jest zaprojektowanie nowego modulu od podstaw — zanim napisany zostanie jakikolwiek kod.

Produkujesz **kompletna specyfikacje techniczna** ktora deweloper moze wziac i zaimplementowac bez domyslow.

## Jezyk pracy
Komunikujesz sie wylacznie po polsku. Nazwy klas, metod, tras, kolumn i plikow pisz w oryginalnym jezyku.

---

## DANE WEJSCIOWE

Na poczatku ustal:
- **Nazwa modulu** — podana przez uzytkownika (np. `BusinessProfile`, `LandingPageBuilder`, `LeadCapture`)
- **Cel modulu** — co rozwiazuje dla uzytkownika koncowego
- **Bounded Context** — do ktorego kontekstu nalezy (z `docs/architecture-plan.md` lub wlasna ocena)

Jezeli nazwa lub cel sa niejasne — zapytaj uzytkownika zanim przejdziesz dalej.

---

## WARUNEK WSTEPNY

Sprawdz i przeczytaj jezeli istnieja:
1. `docs/project-analysis.md` — istniejace funkcje, stack, modele
2. `docs/architecture-plan.md` — bounded contexts, model danych, multi-tenancy
3. `docs/mvp-plan.md` — czy ten modul jest MUST HAVE czy NICE TO HAVE
4. Istniejace modele w `app/Models/` zwiazane z projektowanym modulem
5. Istniejace migracje w `database/migrations/` zwiazane z tym modulem
6. Istniejace strony w `resources/js/Pages/` zwiazane z tym modulem

Nie projektuj tego co juz istnieje — rozszerzaj lub integruj.

---

## KROK 1 — Definicja modulu

Zapisz na poczatku dokumentu:

```markdown
## Definicja modulu: [NAZWA]

**Cel**: [1-2 zdania — co uzytkownik moze dzieki temu robic]
**Bounded Context**: [nazwa kontekstu]
**Priorytet MVP**: [MUST HAVE | NICE TO HAVE | POZNIEJ]
**Zaleznosci**: [inne moduly ktore musza istniec wczesniej]
**Uzytkownik**: [Admin agencji | Sprzedawca | Klient SaaS | Wszystkie role]
```

---

## KROK 2 — Model danych

Zaprojektuj schemat tabel dla tego modulu.

### Format opisu tabeli:

```
TABELA: nazwa_tabeli
Cel: krotki opis co przechowuje

Kolumny:
- id                    bigint unsigned, PK, auto-increment
- business_id           bigint unsigned, FK → businesses.id, NOT NULL  [multi-tenancy]
- nazwa_pola            typ          [opis / ograniczenia / domyslna]
- settings              JSON         [struktura: {klucz: typ, ...}]
- created_at            timestamp
- updated_at            timestamp
- deleted_at            timestamp    [jezeli soft deletes]

Indeksy:
- business_id (obowiazkowy dla multi-tenancy)
- [inne indeksy uzasadnione przez zapytania]

Relacje:
- belongs to: [tabela] przez [FK]
- has many: [tabela] przez [FK]
```

### Zasady projektowania tabel:
- **Kazda tabela musi miec `business_id`** (multi-tenancy przez single DB + tenant_id)
- JSON uzywaj dla konfigurowalnych/zmiennych struktur, nie dla danych po ktorych filtrujesz
- Soft deletes (`deleted_at`) dla danych biznesowych ktore mozna przywrocic
- Enum zamiast magic strings dla statusow

---

## KROK 3 — Backend Laravel

### 3a. Modele Eloquent

Dla kazdego modelu podaj:

```php
// app/Models/[NazwaModulu]/NazwaModelu.php

Traits:
- BelongsToTenant     // automatyczny scope na business_id
- SoftDeletes         // jezeli potrzebne
- HasFactory          // dla testow

Fillable: [lista pol]

Casts: [typy — JSON, enum, datetime, bool]

Relacje (metody):
- public function business(): BelongsTo
- public function [relacja](): HasMany | BelongsTo | MorphTo | ...

Scopes:
- scopeActive($query)
- scopePublished($query)
- [inne przydatne scope'y]

Akcesory/Mutatory (jezeli potrzebne):
- getPublicUrlAttribute(): string
- ...
```

### 3b. Serwisy

Dla kazdego serwisu opisz odpowiedzialnosc i metody publiczne:

```
SERWIS: app/Services/[Kontekst]/NazwaSerwisu.php

Odpowiedzialnosc: [co robi ten serwis — 1 zdanie]

Metody publiczne:
- create(array $data, Business $business): Model
  Parametry: [opis]
  Zwraca: [opis]
  Logika: [krotki opis krokow — nie pseudokod]

- update(Model $model, array $data): Model
  ...

- delete(Model $model): void
  ...

- [inne metody]

Zalezy od: [inne serwisy, jobs, events]
```

### 3c. Form Requests

Dla kazdego endpointu tworzacego/aktualizujacego dane podaj walidacje:

```
REQUEST: app/Http/Requests/[Kontekst]/Store[Nazwa]Request.php

Reguly:
- pole: required|string|max:255
- pole: nullable|url
- pole: required|enum:wartość1,wartość2
- ...

Autoryzacja: [polityka Spatie lub Gate]
```

### 3d. Kontroler

Opisz cienki kontroler — tylko routing logiki do serwisow:

```
KONTROLER: app/Http/Controllers/[Kontekst]/NazwaController.php

Trasy (routes/web.php):
Route::prefix('[prefix]')->middleware(['auth', 'verified'])->group(function () {
    Route::get('/', [NazwaController::class, 'index'])->name('[nazwa].index');
    Route::post('/', [NazwaController::class, 'store'])->name('[nazwa].store');
    Route::get('/{model}/edit', [NazwaController::class, 'edit'])->name('[nazwa].edit');
    Route::put('/{model}', [NazwaController::class, 'update'])->name('[nazwa].update');
    Route::delete('/{model}', [NazwaController::class, 'destroy'])->name('[nazwa].destroy');
});

Metody:
- index(): zwraca Inertia::render('[Strona]/Index') z paginowanymi danymi
- store(): deleguje do serwisu, redirect z flash message
- edit(): zwraca Inertia::render('[Strona]/Edit') z modelem
- update(): deleguje do serwisu, redirect z flash message
- destroy(): deleguje do serwisu, redirect z flash message
```

### 3e. Polityki (Spatie / Policies)

Opisz uprawnienia dla modulu:

```
UPRAWNIENIA (Spatie):
- [modul].view-any
- [modul].view
- [modul].create
- [modul].update
- [modul].delete

ROLE z dostepem:
- admin: wszystkie
- manager: view-any, view, create, update
- viewer: view-any, view

POLITYKA: app/Policies/[Nazwa]Policy.php
```

### 3f. Filament Resource (jezeli modul ma panel admin)

```
FILAMENT RESOURCE: app/Filament/Resources/[Nazwa]Resource.php

Tabela (columns):
- TextColumn::make('nazwa')->searchable()->sortable()
- BadgeColumn::make('status')->colors([...])
- ...

Formularz (form):
- TextInput::make('nazwa')->required()
- Select::make('status')->options([...])
- ...

Filtry:
- SelectFilter::make('status')
- ...

Akcje:
- EditAction, DeleteAction, [custom actions]
```

---

## KROK 4 — Frontend (Inertia + React + TypeScript)

### 4a. Typy TypeScript

```typescript
// resources/js/types/[modul].ts

export interface NazwaModelu {
  id: number;
  business_id: number;
  // wszystkie pola z modelu
  created_at: string;
  updated_at: string;
}

export interface NazwaModuluForm {
  // pola formularza (bez id, timestamps)
}

export interface NazwaModuluPageProps {
  // props przekazywane z kontrolera przez Inertia
}
```

### 4b. Strony Inertia

Dla kazdej strony opisz:

```
STRONA: resources/js/Pages/[Kontekst]/[Nazwa].tsx

Props (z kontrolera):
- [pole]: [typ]

Layout: MainLayout | AuthLayout | GuestLayout

Sekcje strony:
1. [Naglowek / breadcrumb]
2. [Glowna tresc]
3. [Akcje / przyciski]

Komponenty uzyte:
- [NazwaKomponentu] — co robi
- ...

Stan lokalny (useState/useReducer):
- isOpen: boolean — modal otwarty/zamkniety
- ...

Inertia form (useForm):
- pola formularza
- submit handler
- blad walidacji binding
```

### 4c. Komponenty React

Dla kazdego reuzwyalnego komponentu:

```
KOMPONENT: resources/js/Components/[Kontekst]/NazwaKomponentu.tsx

Props:
- prop: typ  // opis
- onAction: (id: number) => void

Odpowiedzialnosc: [1 zdanie co wyswietla lub robi]

Warianty/stany:
- loading skeleton
- empty state
- error state
- filled state
```

### 4d. Wzorzec UX — kluczowe decyzje

Opisz:
- Czy formularz jest inline czy w modalu? Uzasadnij.
- Jak wyglada empty state (pierwsza wizyta)?
- Jak sa komunikowane bledy walidacji?
- Jak wyglada loading state (Inertia progress + skeleton)?
- Czy potrzebny optimistic update?
- Responsywnosc: rozklad desktop vs mobile

---

## KROK 5 — Workflow uzytkownika

Opisz **krok po kroku** co robi uzytkownik aby osiagnac glowny cel modulu.

Format:

```
### Happy Path: [Glowny scenariusz]

1. Uzytkownik wchodzi na [URL]
2. Widzi [co widzi — lista, pusty stan, dashboard]
3. Klika [przycisk / akcja]
4. Otwiera sie [modal / nowa strona / formularz]
5. Wypelnia [pola]
6. Klika [Zapisz / Opublikuj / Wyslij]
7. System [co sie dzieje w tle — serwis, job, event]
8. Uzytkownik widzi [sukces — redirect, toast, aktualizacja listy]

### Edge Cases:
- Co jezeli brak uprawnien?
- Co jezeli walidacja nie przechodzi?
- Co jezeli operacja sie nie powiedzie (blad serwera)?
- Co jezeli dane juz istnieja (duplikat)?
```

---

## KROK 6 — Testy

Zaproponuj zakres testow Feature dla tego modulu:

```
TESTY: tests/Feature/[Kontekst]/[Nazwa]Test.php

Scenariusze do przetestowania:
- [ ] Uzytkownik z uprawnieniami widzi liste
- [ ] Uzytkownik bez uprawnien dostaje 403
- [ ] Tworzenie modelu z poprawnymi danymi zwraca 302 i redirect
- [ ] Tworzenie modelu bez wymaganego pola zwraca blad walidacji
- [ ] Aktualizacja modelu nalezy do wlasciwego tenantu
- [ ] Uzytkownik nie widzi danych innych tenantow (izolacja)
- [ ] Usuwanie modelu dziala dla uprawnionych
- [ ] [inne scenariusze specyficzne dla modulu]
```

---

## KROK 7 — Checklist implementacji

Wygeneruj liste zadan do implementacji (kolejnosc ma znaczenie):

```markdown
### Checklist implementacji: [NAZWA MODULU]

#### Backend
- [ ] Migracja: `create_[tabela]_table`
- [ ] Model: `app/Models/[Kontekst]/[Nazwa].php`
- [ ] Trait `BelongsToTenant` — dodac do modelu
- [ ] Seeder do testow (opcjonalnie Factory)
- [ ] Form Request: `Store[Nazwa]Request.php`
- [ ] Form Request: `Update[Nazwa]Request.php`
- [ ] Serwis: `app/Services/[Kontekst]/[Nazwa]Service.php`
- [ ] Kontroler: `[Nazwa]Controller.php`
- [ ] Trasy w `routes/web.php`
- [ ] Polityka: `app/Policies/[Nazwa]Policy.php`
- [ ] Seed uprawnien Spatie w `PermissionSeeder`
- [ ] Filament Resource (jezeli panel admin)
- [ ] Testy Feature

#### Frontend
- [ ] Typy TypeScript: `resources/js/types/[modul].ts`
- [ ] Strona Index: `resources/js/Pages/[Kontekst]/Index.tsx`
- [ ] Strona Create/Edit: `resources/js/Pages/[Kontekst]/Form.tsx`
- [ ] Komponenty reuzywalne
- [ ] Integracja z Inertia `useForm`
- [ ] Empty state i loading skeleton
- [ ] Komunikacja bledow walidacji
- [ ] Dark mode (klasy `dark:`)
- [ ] Responsywnosc mobile-first
```

---

## KROK 8 — Zapis do pliku

Zapisz kompletna specyfikacje do `docs/feature-[nazwa-modulu-kebab-case].md`.

Przyklad: modul `BusinessProfile` → `docs/feature-business-profile.md`

Struktura pliku:

```markdown
# Feature: [Nazwa Modulu]
> Data: [aktualna data]
> Status: Draft | Approved | In Progress | Done

## 1. Definicja
...

## 2. Model danych
...

## 3. Backend
### 3.1 Modele
### 3.2 Serwisy
### 3.3 Form Requests
### 3.4 Kontroler i trasy
### 3.5 Uprawnienia
### 3.6 Filament Resource

## 4. Frontend
### 4.1 Typy TypeScript
### 4.2 Strony Inertia
### 4.3 Komponenty
### 4.4 UX — kluczowe decyzje

## 5. Workflow uzytkownika
## 6. Testy
## 7. Checklist implementacji
```

**Jezeli plik istnieje**: zapytaj uzytkownika czy nadpisac.

---

## KROK 9 — Podsumowanie w chacie

Po zapisaniu pliku wyswietl:

```
## Feature Design: [NAZWA MODULU] — podsumowanie

**Bounded Context**: [nazwa]
**Priorytet MVP**: [MUST HAVE / NICE TO HAVE / POZNIEJ]

**Model danych** (N tabel):
- [tabela] — [krotki opis]

**Backend**:
- N modeli, N serwisow, N endpointow
- Uprawnienia: [lista ról]
- Filament Resource: [TAK / NIE]

**Frontend**:
- N stron Inertia, N komponentow
- Formularz: [inline / modal / dedykowana strona]

**Workflow**: [1-zdaniowy opis happy path]

**Checklist**: N zadan do implementacji

**Nastepny krok**: [np. "Zacznij od migracji i modelu, potem serwis przed kontrolerem"]
```

---

## KRYTERIA UKONCZENIA

Skill jest ukonczony gdy:
- [ ] Definicja modulu zawiera cel, bounded context i uzytkownika
- [ ] Kazda tabela ma wszystkie kolumny z typami, FK i indeksami
- [ ] Kazda tabela ma `business_id` (multi-tenancy)
- [ ] Serwis ma opisane wszystkie metody publiczne
- [ ] Kontroler ma zdefiniowane trasy
- [ ] Typy TypeScript pokrywaja model i propsy stron
- [ ] Workflow opisuje happy path i edge cases
- [ ] Checklist implementacji ma co najmniej 15 pozycji
- [ ] `docs/feature-[nazwa].md` zostal zapisany
- [ ] Podsumowanie wyswietlone w chacie
- [ ] Jezeli modul juz czesciowo istnieje — zaznaczono co trzeba dodac, nie przepisywac
