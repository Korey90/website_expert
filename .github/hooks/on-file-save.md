# Hook: On File Save

**Opis:** Szybkie sprawdzenia przy zapisie ważnych plików.

**Trigger:** Zapis pliku w obserwowanych lokalizacjach.

---

## Obserwowane lokalizacje i akcje

### `app/Models/*.php`
- Sprawdź: czy model ma `BelongsToTenant` jeśli dotyczy tenanta
- Sprawdź: czy `$fillable` nie zawiera `business_id` (powinien być obsługiwany przez trait)
- Sprawdź: czy relacje mają poprawne typy zwracane
- Trigger: @DatabaseEngineer jeśli zmiana dotyczy kolumn

### `app/Actions/**/*.php`
- Sprawdź: czy metoda `execute()` ma typowane parametry
- Sprawdź: brak logiki HTTP (request, response) w Action
- Trigger: @TestingEngineer — sprawdź czy test istnieje

### `app/Http/Controllers/**/*.php`
- Sprawdź: kontroler wywołuje Action (nie logikę inline)
- Sprawdź: Form Request w sygnaturze metody
- Sprawdź: Policy check (`$this->authorize(...)`)

### `resources/js/Components/**/*.tsx`, `resources/js/Pages/**/*.tsx`
- Sprawdź: brak `any` TypeScript
- Sprawdź: props mają interface
- Sprawdź: teksty UI przez `trans()` nie hardkodowane
- Trigger: @DocumentationEngineer — sprawdź tłumaczenia

### `database/migrations/*.php`
- Sprawdź: `down()` metoda kompletna
- Sprawdź: `business_id` w nowych tabelach tenant-scoped
- Trigger: @DatabaseEngineer — sprawdź indeksy

### `lang/pl/*.php` (bez `lang/en/` i `lang/pt/`)
- **Zawsze** synchronizuj z en/ i pt/
- Trigger: @DocumentationEngineer

---

## Szybka walidacja składniowa

```bash
# PHP syntax check
php -l {saved_file}

# TypeScript check (przez Vite)
npm run lint -- {saved_file}
```
