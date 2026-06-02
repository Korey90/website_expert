# Hook: Post Generation

**Opis:** Uruchamiany automatycznie po każdej generacji kodu przez agenta.

**Trigger:** Po zakończeniu implementacji przez @BackendEngineer lub @FrontendEngineer.

---

## Kroki (w kolejności — nie pomijaj)

```bash
# 1. Tłumaczenia
./.github/scripts/check-translations.sh

# 2. PHP formatting
php artisan pint

# 3. JS/TS linting + formatting
npm run lint && npm run format

# 4. Testy — uruchom dotkniętą domenę
php artisan test --filter={ChangedDomain}
# lub wszystkie
php artisan test

# 5. Walidacja multi-tenancy
php ./.github/scripts/validate-multi-tenancy.php

# 6. Aktualizuj current-task.md
# Oznacz etap jako Done w .github/live-docs/current-task.md

# 7. Wygeneruj podsumowanie dla użytkownika
```

---

## Agent musi potwierdzić wszystkie kroki

Przed finalnym raportem agent musi zgłosić:

```
✅ Tłumaczenia: OK (lub lista brakujących)
✅ PHP Pint: OK
✅ ESLint/Prettier: OK
✅ Testy: X/X przeszło
✅ Multi-tenancy: OK
✅ current-task.md: zaktualizowany
```

---

## Jeśli cokolwiek failuje

- **Tłumaczenia fail** → @DocumentationEngineer dodaje brakujące klucze
- **Pint fail** → `php artisan pint` automatycznie naprawia
- **ESLint fail** → `npm run lint -- --fix` + manualny review
- **Test fail** → @TestingEngineer naprawia test lub kod
- **Multi-tenancy fail** → @BackendEngineer + @DatabaseEngineer naprawia `business_id` scoping

Nie finalizuj zadania dopóki wszystkie kroki nie przejdą.
