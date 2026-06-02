# Hook: Before New Sprint

**Opis:** Przygotowanie środowiska i kontekstu przed rozpoczęciem nowego sprintu.

**Trigger:** Na początku każdego nowego bloku pracy (nowy tydzień, nowy moduł).

---

## Kroki przygotowania (w kolejności)

### 1. Stan projektu — weryfikacja

```bash
# Czy wszystkie testy przechodzą (clean baseline)
php artisan test
npm run test

# Czy kod jest czysty
php artisan pint --test
npm run lint

# Czy tłumaczenia są kompletne
./.github/scripts/check-translations.sh

# Pełna walidacja
./.github/scripts/run-full-validation.sh
```

### 2. Przeczytaj live-docs

- `.github/live-docs/status-dashboard.md` — zdrowie projektu
- `.github/live-docs/project-analysis.md` — stan modułów
- `.github/completed-tasks/` — ostatnie zadania (kontekst)

### 3. Przejrzyj tech debt

Sprawdź w `project-analysis.md` sekcję "Tech Debt" — czy coś wymaga uwagi przed nowym modułem.

### 4. Zaktualizuj current-sprint.md

Używając skill: `sprint-planning`:
- Wyczyść poprzedni sprint (przenieś Done do archiwum lub wyczyść)
- Zdefiniuj nowy sprint z celem i zadaniami

### 5. Zaktualizuj current-task.md

```markdown
# Bieżące zadanie: [Nazwa pierwszego zadania sprintu]
**Status:** Planowanie
**Sprint:** [Nazwa sprintu]
```

---

## Checklist przed-sprintowy

- [ ] Wszystkie testy przechodzą
- [ ] Kod czysty (Pint + ESLint)
- [ ] Live-docs przeczytane i aktualne
- [ ] Cel sprintu jasno zdefiniowany
- [ ] Zadania podzielone na < 8h każde
- [ ] Zależności zidentyfikowane
- [ ] `current-sprint.md` zaktualizowany

---

## Nie zaczynaj sprintu jeśli

- Jakiekolwiek testy failują — najpierw napraw
- Są pending zmiany niecommitowane — najpierw commit lub stash
- `status-dashboard.md` pokazuje krityczne problemy — najpierw rozwiąż
