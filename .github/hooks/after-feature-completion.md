# Hook: After Feature Completion

**Opis:** Finalny checklist przed oznaczeniem zadania jako Done i archiwizacją.

**Trigger:** Gdy wszystkie etapy implementacji są zakończone i testy przechodzą.

---

## Finalny checklist (każda pozycja musi być ✅)

### Kod
- [ ] `php artisan pint` → 0 errors
- [ ] `npm run lint` → 0 errors
- [ ] `npm run format` → brak zmian (wszystko sformatowane)
- [ ] Brak `dd()`, `dump()`, `console.log` debugowych
- [ ] Brak zakomentowanego kodu (tylko celowo zostawiony z komentarzem)

### Testy
- [ ] `php artisan test` → wszystkie przechodzą
- [ ] `npm run test` → wszystkie przechodzą
- [ ] Nowe testy napisane dla nowej funkcji
- [ ] Coverage nie spadło poniżej poprzedniego poziomu

### Wielojęzyczność
- [ ] `check-translations.sh` → 0 błędów
- [ ] Klucze dodane w `lang/pl/`, `lang/en/`, `lang/pt/`
- [ ] Spatie Translatable pola mają `json` migracje

### Architektura
- [ ] Kontrolery są cienkie
- [ ] Logika w Actions, nie w modelach ani kontrolerach
- [ ] `business_id` na wszystkich nowych tabelach tenant-scoped
- [ ] `php ./.github/scripts/validate-multi-tenancy.php` → OK

### Bezpieczeństwo
- [ ] Policy zarejestrowana dla nowych modeli
- [ ] Endpointy mają właściwe middleware
- [ ] Żadnych hardkodowanych sekretów

### Dokumentacja
- [ ] `current-task.md` odzwierciedla ukończenie
- [ ] `current-sprint.md` zaktualizowany (zadanie w Done)
- [ ] `status-dashboard.md` zaktualizowany (test count)

---

## Po zaliczeniu wszystkich checkboxów

```bash
# Wygeneruj raport końcowy
# Skill: task-completion-report
# → archiwizuje w .github/completed-tasks/YYYY-MM-DD - Tytuł.md
# → czyści current-task.md
```
