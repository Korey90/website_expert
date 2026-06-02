# Skill: Multi-Language Check

**Opis:** Weryfikacja i synchronizacja tłumaczeń po każdej zmianie UI lub modelu.

**Kiedy używać:** Po każdej zmianie, która dotyka interfejsu użytkownika lub pól modelu z Spatie Translatable.

---

## Kroki walidacji

### 1. Automatyczny check

```bash
./.github/scripts/check-translations.sh
```

### 2. Manualny check — brakujące klucze

```bash
# Znajdź wszystkie użycia trans() i __() w plikach PHP
grep -rn "trans('\|__('" resources/views app/Filament --include="*.php" | \
  grep -oP "(trans|__)\('([^']+)'\)" | sort -u

# Znajdź wszystkie użycia trans() w plikach TSX/JSX
grep -rn "trans(" resources/js --include="*.tsx" --include="*.jsx" | \
  grep -oP "trans\('([^']+)'\)" | sort -u
```

### 3. Sprawdź spójność między językami

```bash
# Klucze w PL które nie ma w EN
php -r "
\$pl = require 'lang/pl/{file}.php';
\$en = require 'lang/en/{file}.php';
print_r(array_diff_key(\$pl, \$en));"
```

---

## Dodawanie nowego klucza tłumaczenia

### Krok 1: Ustal plik tłumaczeń
- Jeśli dotyczy domeny: `lang/{locale}/{domain}.php` (np. `lang/pl/leads.php`)
- Jeśli ogólny: `lang/{locale}/common.php`

### Krok 2: Dodaj klucz we WSZYSTKICH 3 językach jednocześnie

```php
// lang/pl/{domain}.php
'{key}' => 'Tekst polski',

// lang/en/{domain}.php
'{key}' => 'English text',

// lang/pt/{domain}.php
'{key}' => 'Texto em português',
```

### Krok 3: Użyj klucza w kodzie

```php
// PHP/Blade/Filament
trans('{domain}.{key}')
__('leads.create')

// React (Inertia shared translations)
trans('{domain}.{key}')
```

---

## Spatie Translatable — weryfikacja

```php
// Model musi mieć trait
use HasTranslations;
public array $translatable = ['description', 'title'];

// Kolumna w bazie: json type
$table->json('description')->nullable();

// Pobieranie z konkretnym językiem
$model->getTranslation('description', 'pl');
$model->getTranslations('description'); // wszystkie języki
```

---

## Checklist tłumaczeń

- [ ] Nowe klucze dodane w `lang/pl/`, `lang/en/`, `lang/pt/`
- [ ] Brak hardkodowanych stringów PL/EN bezpośrednio w plikach PHP/TSX
- [ ] Spatie Translatable pola mają migrację `json` type
- [ ] `check-translations.sh` przechodzi bez błędów
