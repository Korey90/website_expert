# Skill: Code Review (Self-Review dla Solo Developera)

**Opis:** Systematyczny self-review kodu przed commitem. Zastępuje review od drugiej osoby.

**Kiedy używać:** Przed każdym commitem lub przed oznaczeniem zadania jako Done.

---

## Checklist — Backend (PHP/Laravel)

### Architektura
- [ ] Kontroler jest cienki (max 10 linii w metodzie)
- [ ] Logika biznesowa w Action, nie w kontrolerze
- [ ] Brak `if ($request->has(...))` w kontrolerze — to należy do Form Request
- [ ] Nowe metody w istniejących modelach to tylko relacje lub scope — nie logika

### Bezpieczeństwo
- [ ] Każdy endpoint ma `auth` middleware lub jest świadomie publiczny
- [ ] Policy sprawdzona dla operacji CRUD
- [ ] Brak hardkodowanych wartości wrażliwych (klucze, hasła, tokeny)
- [ ] Form Request ma `authorize()` z prawdziwą logiką (nie `return true`)

### Multi-tenancy
- [ ] Nowe modele mają `business_id` (jeśli tenant-scoped)
- [ ] Queries nie pomijają `business_id` w WHERE
- [ ] Test izolacji (użytkownik z innego business nie widzi danych)

### Jakość kodu
- [ ] Brak `dd()`, `dump()`, `var_dump()`, `print_r()`
- [ ] Brak `TODO` bez ticketu
- [ ] Nazwy metod są czasownikami (createLead, updateStatus)
- [ ] Brak martwego kodu (zakomentowane bloki)

---

## Checklist — Frontend (TypeScript/React)

### TypeScript
- [ ] Zero `any` — każde miejsce sprawdzone
- [ ] Props typowane przez interface
- [ ] Return type funkcji zdefiniowany jeśli nie-trivialny

### React
- [ ] Brak `useEffect` z brakującymi dependencjami
- [ ] Formularze używają `useForm` z Inertia (nie `useState` + `axios`)
- [ ] Komponenty nie są zbyt duże (> 150 linii → podziel)
- [ ] Brak inline styles (tylko Tailwind)

### Tłumaczenia
- [ ] Żadnych hardkodowanych polskich/angielskich stringów
- [ ] Wszystkie nowe teksty mają klucz trans()

---

## Checklist — Ogólne

### Testy
- [ ] Nowe testy dodane dla nowej funkcjonalności
- [ ] Istniejące testy nadal przechodzą: `php artisan test`
- [ ] Vitest: `npm run test`

### Git
- [ ] Commit message opisuje CO i DLACZEGO (nie tylko CO)
- [ ] Jeden commit = jedna logiczna zmiana
- [ ] Brak przypadkowo dodanych plików (sprawdź `git diff --staged`)

---

## Szybki self-review (< 5 min)

```bash
# Sprawdź co zmieniłeś
git diff --staged

# Uruchom walidację
php artisan pint --test && npm run lint

# Uruchom testy zmienionego obszaru
php artisan test --filter={ChangedFeature}
```

---

## Czerwone flagi — zawsze zatrzymaj i popraw

- `any` w TypeScript
- `dd()` lub `dump()` w PHP
- Hardkodowany tekst PL/EN bez klucza trans()
- `business_id` pominięte w nowym modelu
- Test który sprawdza tylko happy path bez auth check
