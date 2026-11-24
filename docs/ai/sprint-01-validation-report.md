# Sprint 01 — raport uruchomienia testów (2026-11-24)

Status: **BLOCKED — NOT TESTED**. Sprint 02: **NOT STARTED**.

## Próba wykonania

- Workflow: `.github/workflows/ai-sprint-01.yml`
- Run: https://github.com/Korey90/website_expert/actions/runs/45401989427
- Commit workflow: `86fb4de1783635e936385a107b96e3b9f872dda2`
- Stan GitHub Actions: failure przed pierwszym krokiem.
- Adnotacja GitHub: "The job was not started because your account is locked due to a billing issue."
- Nie odnotowano żadnego uruchomionego kroku; nie wykonano PHPUnit, migracji SQLite/MySQL, Pint ani kompilacji widoków.
- Lokalne środowisko asystenta posiada PHP 8.4, ale nie Composer, wymagane rozszerzenia, checkout repo ani połączenie z GitHub w terminalu. `git ls-remote` zwraca `Could not resolve host: github.com`. Bez pełnego checkoutu i zależności testowanie kodu nie jest możliwe.

## Bramka Sprint 02

Nie przechodzić do Sprintu 02 bez:
1. Odblokowania GitHub Actions Billing lub uruchomienia projektu na niezależnym środowisku.
2. Zielonego AI-specific PHPUnit, tenant+Filament regression, sprawdzenia migracji SQLite i MySQL oraz PHP lint.
3. Pełnych testów Laravel, przebudowy widoków i Pint. Ewentualne błędy poprawić na obecnej gałęzi.
4. Sprawdzenia, że narzędzia nie używają produkcyjnych sekretów ani danych.

## Uruchomienie lokalne (w kopii projektu, nie na produkcji)

```bash
composer install --prefer-dist --no-interaction
cp .env.example .env    # tylko lokalny nowy checkout
php artisan key:generate
bash scripts/validate-ai-sprint-01.sh
```

Skrypt wymusza APP_ENV=testing, SQLite in-memory oraz brak klucza OpenAI. Migracje wykonuje wyłącznie test runner używający RefreshDatabase. Produkcyjna gałąź `main` nie została zmieniona.

## Uwaga

Powodzenie GitHub Actions nie jest równoznaczne ze sprawdzeniem MySQL 8.0, wymagana również weryfikacja na dedykowanej bazie testowej zgodnej z docelowym środowiskiem.
