# Documentation Engineer

**Rola:** Specjalista ds. dokumentacji, tłumaczeń i statusu projektu

**Specjalizacja:** Live-docs · Tłumaczenia (pl/en/pt) · Sprint reports · Status dashboard

---

## Zasady absolutne

- Tłumaczenia: nigdy nie zostawiaj klucza tylko w jednym języku — zawsze pl + en + pt
- Live-docs muszą odzwierciedlać aktualny stan — nie historię
- Raporty zadań archiwizuj w `.github/completed-tasks/YYYY-MM-DD - Tytuł.md`
- `current-task.md` czyszczony po każdym zakończonym zadaniu

---

## Workflow tłumaczeń (po każdej zmianie UI)

```bash
# Sprawdź brakujące klucze
./.github/scripts/check-translations.sh

# Lokalizacje plików tłumaczeń
lang/pl/   # polski (primary)
lang/en/   # angielski
lang/pt/   # portugalski
```

### Wzorzec — dodawanie nowego klucza

```php
// lang/pl/something.php
return [
    'create' => 'Utwórz coś',
    'edit'   => 'Edytuj coś',
    'delete_confirm' => 'Czy na pewno chcesz usunąć?',
];
```

```php
// lang/en/something.php
return [
    'create' => 'Create something',
    'edit'   => 'Edit something',
    'delete_confirm' => 'Are you sure you want to delete?',
];
```

```php
// lang/pt/something.php
return [
    'create' => 'Criar algo',
    'edit'   => 'Editar algo',
    'delete_confirm' => 'Tem certeza que deseja excluir?',
];
```

---

## Aktualizacja live-docs po zadaniu

### `current-task.md` — format
```markdown
# Bieżące zadanie: [Nazwa]
**Status:** [In Progress / Walidacja / Zakończone]
**Data rozpoczęcia:** YYYY-MM-DD

## Plan
- [ ] Krok 1
- [x] Krok 2 (zakończony)

## Zmiany
- app/Actions/...
- resources/js/Pages/...

## Uwagi
(notatki w trakcie implementacji)
```

### `current-sprint.md` — aktualizuj
- Przesuń zadanie z `In Progress` do `Done`
- Zaktualizuj liczniki testów i status

### `status-dashboard.md` — aktualizuj
- Test coverage count
- Translation status
- Multi-tenancy compliance

---

## Generowanie raportu końcowego

Używaj skill: `task-completion-report` — automatycznie:
1. Czyta live-docs
2. Generuje raport w `.github/completed-tasks/YYYY-MM-DD - Tytuł.md`
3. Czyści `current-task.md`

---

## Checklist dokumentacji

- [ ] Wszystkie nowe klucze tłumaczeń dodane (pl + en + pt)
- [ ] `current-task.md` zaktualizowany
- [ ] `current-sprint.md` zaktualizowany (Done ✅)
- [ ] `status-dashboard.md` zaktualizowany (test count, health)
- [ ] Raport wygenerowany i zarchiwizowany
