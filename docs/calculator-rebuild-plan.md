# Plan przebudowy modułu kalkulatora

**Data:** 2026-03-26  
**Cel:** Wszystkie teksty kalkulatora (pytania kroków, etykiety, przyciski, ekran wyników) muszą być edytowalne z panelu admin Filament — zero hardkodowanych stringów w JSX.

---

## Problem (stan wyjściowy)

| Problem | Gdzie |
|---------|-------|
| `DEFAULTS` obiekt w JSX – tytuł/podtytuł hardkodowany | `CostCalculator.jsx` linia ~52 |
| Kroki mają tylko EN/PL – brak `question_pt` / `hint_pt` | `SiteSectionSeeder` `extra.steps` |
| 4 etykiety inline (`baseMultiplierLabel`, `noExtraLabel`, …) hardkodowane | `CostCalculator.jsx` linia ~218 |
| Brak dedykowanego UI admina do edycji pytań kroków | – |
| Brak dedykowanego UI admina do edycji wszystkich stringów | – |

---

## Architektura docelowa

```
DB
├── calculator_pricing      (istniejąca — ceny opcji EN/PL/PT)
├── calculator_strings      (NOWA — wszystkie etykiety UI per locale)
└── calculator_steps        (NOWA — 8 pytań + hinty per locale)

Filament Admin
├── Calculator Pricing      (istniejący zasób)
├── Calculator Strings      (NOWY — zarządzanie etykietami)
└── Calculator Steps        (NOWY — zarządzanie pytaniami/krokami)

Controller (KalkulatorController)
└── Ładuje pricing + strings + steps z DB
    └── Rozwiązuje locale, wysyła gotowe stringi do frontendu

Frontend
├── CostCalculator.jsx      (istniejący — zachowany bez zmian)
└── CostCalculatorV2.jsx    (NOWY — zero hardkodowanych stringów)
    └── Wszystkie teksty z props: strings{} + steps[] + pricing{}
```

---

## Tabela `calculator_strings`

| kolumna | typ | opis |
|---------|-----|------|
| id | bigint PK | |
| key | varchar(100) UNIQUE | identyfikator stringu |
| group | varchar(50) | grupowanie w adminie |
| value_en | text | wartość angielska |
| value_pl | text | wartość polska |
| value_pt | text | wartość portugalska |
| note | varchar(255) NULL | wskazówka dla admina |
| sort_order | smallint | kolejność w adminie |

### Grupy i klucze

**header**
- `section_label` — "Cost Calculator"
- `title` — "How Much Will Your Project Cost?"
- `subtitle` — "Answer a few questions…"

**navigation**
- `step_label` — "Step"
- `step_of` — "of"
- `nav_next` — "Next →"
- `nav_back` — "← Back"
- `nav_skip` — "Skip →"
- `nav_calc` — "Calculate Quote 🚀"

**misc_labels**
- `from_label` — "from"
- `base_multiplier_label` — "of base price"
- `no_extra_label` — "no extra charge"
- `to_quote_label` — "to quote"
- `standard_pricing_label` — "standard pricing"
- `per_year` — "/year"
- `self_managed` — "self-managed"
- `pages_addon` — "Each page above 5: +£80"
- `pages_chip` — "pages"
- `integrations_chip` — "integrations"

**result_page**
- `result_title` — "Your Estimated Quote"
- `result_subtitle` — "Estimate based on the information you provided."
- `result_cost_label` — "Estimated project cost"
- `hosting_addon_label` — "+ hosting"
- `restart` — "Start over"

**contact_form**
- `contact_title` — "Enter your details and we'll send you a detailed quote."
- `name_placeholder` — "Your name / company"
- `email_placeholder` — "Your email"
- `submit_btn` — "Send enquiry 🚀"
- `submitting_btn` — "Sending…"
- `success_msg` — "✓ Done! We'll get back to you within 1 business day."
- `sent_to` — "Sent to:"

---

## Tabela `calculator_steps`

| kolumna | typ | opis |
|---------|-----|------|
| id | bigint PK | |
| step_number | tinyint | 1–8 |
| question_en/pl/pt | text | pytanie |
| hint_en/pl/pt | text NULL | podpowiedź |
| is_active | boolean default true | |
| sort_order | smallint | kolejność |

### 8 kroków

1. Typ projektu
2. Liczba podstron
3. Poziom designu
4. CMS
5. Integracje (multi-select)
6. Pakiet SEO
7. Termin wykonania
8. Hosting

---

## Filament Admin Resources

### CalculatorStringsResource
- Tabela: kolumny Key, Group, EN (truncated), PL, PT, Sort
- Filtr po grupie
- Formularz: `key`, `group`, `note` | `value_en` | `value_pl` | `value_pt` | `sort_order`
- Navigationgroup: "Settings", icon: `heroicon-o-language`

### CalculatorStepsResource
- Tabela: Step #, Question EN, active, sort_order
- Formularz: step_number, is_active | EN question+hint | PL question+hint | PT question+hint
- Reorderable (drag & drop)
- Navigationgroup: "Settings", icon: `heroicon-o-queue-list`

---

## Controller — KalkulatorController

```php
// Resolve locale (istniejąca logika)
$locale = ...;

// 1. Strings: mapuj key → wartość w bieżącym locale (fallback EN)
$strings = \App\Models\CalculatorString::orderBy('sort_order')
    ->get()
    ->mapWithKeys(fn($s) => [
        $s->key => $s->{"value_$locale"} ?: $s->value_en
    ])
    ->all();

// 2. Steps: pytania + hinty w bieżącym locale
$steps = \App\Models\CalculatorStep::where('is_active', true)
    ->orderBy('sort_order')
    ->get()
    ->map(fn($s) => [
        'question' => $s->{"question_$locale"} ?: $s->question_en,
        'hint'     => $s->{"hint_$locale"}     ?: $s->hint_en,
    ])
    ->values()
    ->all();

// 3. Pricing (istniejąca logika — bez zmian)
$pricing = ...;

return Inertia::render('Kalkulator', compact(
    'cost_calculator', 'navbar', 'footer',
    'pricing', 'strings', 'steps'
));
```

---

## Frontend — CostCalculatorV2.jsx

### Props
```js
export default function CostCalculatorV2({ strings = {}, steps = [], pricing = null })
```

### Zasada
- `strings` to obiekt `{ key: 'wartość w locale' }` — pre-resolved przez controller
- `steps` to tablica `[{ question: '...', hint: '...' }, ...]`
- `pricing` — ten sam format co w v1 (z DB przez controller)
- `s(key, fallback)` helper: `strings[key] ?? fallback` — jedyna funkcja lokalizacji
- Zero `locale` importów, zero `usePage()` dla tekstów, zero hardkodowanych stringów

### Sekcja na stronie
ID: `#kalkulator-v2`  
Umieszczona w `Kalkulator.jsx` bezpośrednio pod istniejącym `<CostCalculator>`

---

## Kolejność implementacji

1. ✅ Plan (ten plik)
2. [ ] Migracje: `calculator_strings` + `calculator_steps`
3. [ ] Modele: `CalculatorString`, `CalculatorStep`
4. [ ] Seeder: `CalculatorStringsSeeder` (28 stringów × 3 locale)
5. [ ] Seeder: `CalculatorStepsSeeder` (8 kroków × 3 locale)
6. [ ] Rejestracja w `DatabaseSeeder`
7. [ ] Filament: `CalculatorStringsResource`
8. [ ] Filament: `CalculatorStepsResource`
9. [ ] Controller: `KalkulatorController` — dodaj `$strings`, `$steps`
10. [ ] JSX: `CostCalculatorV2.jsx` — nowy komponent bez hardkodów
11. [ ] JSX: `Kalkulator.jsx` — dodaj `<CostCalculatorV2>` poniżej `<CostCalculator>`
12. [ ] `php artisan migrate`
13. [ ] `php artisan db:seed --class=CalculatorStringsSeeder`
14. [ ] `php artisan db:seed --class=CalculatorStepsSeeder`
15. [ ] `npm run build`

---

## Pliki do utworzenia

| Plik | Typ |
|------|-----|
| `database/migrations/2026_03_26_140000_create_calculator_strings_table.php` | NEW |
| `database/migrations/2026_03_26_140001_create_calculator_steps_table.php` | NEW |
| `app/Models/CalculatorString.php` | NEW |
| `app/Models/CalculatorStep.php` | NEW |
| `database/seeders/CalculatorStringsSeeder.php` | NEW |
| `database/seeders/CalculatorStepsSeeder.php` | NEW |
| `app/Filament/Resources/CalculatorStringsResource.php` | NEW |
| `app/Filament/Resources/CalculatorStringsResource/Pages/*.php` | NEW (3 pliki) |
| `app/Filament/Resources/CalculatorStepsResource.php` | NEW |
| `app/Filament/Resources/CalculatorStepsResource/Pages/*.php` | NEW (3 pliki) |
| `resources/js/Components/Marketing/CostCalculatorV2.jsx` | NEW |
| `app/Http/Controllers/KalkulatorController.php` | MODIFY |
| `resources/js/Pages/Kalkulator.jsx` | MODIFY |
| `database/seeders/DatabaseSeeder.php` | MODIFY |
