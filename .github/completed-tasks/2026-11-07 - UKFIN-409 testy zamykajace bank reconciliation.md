# 2026-11-07 - UKFIN-409 testy zamykające bank reconciliation

**Status:** Zakończone sukcesem  
**Data realizacji:** 2026-11-07  
**Czas trwania:** jeden przyrost roboczy

## Cel zadania

Zamknąć fazę bank reconciliation przekrojowymi testami deduplikacji, splitów, częściowego FX, reversal, ponownego uzgodnienia, raportowania i izolacji tenantów.

## Zakres wykonanych prac

- Rozszerzono importer CSV o test tożsamości duplikatu na wielu rachunkach i u wielu tenantów.
- Potwierdzono, że provider ID i kanoniczny SHA-256 blokują duplikat wyłącznie w obrębie konkretnego rachunku bankowego.
- Dodano scenariusz dwóch częściowych batchy dla źródła `100.00 EUR` rozliczanego jako `83.33 GBP`.
- Scenariusz FX cofa pierwszą część, sprawdza odzyskaną capacity i ponownie ją alokuje bez utraty ani dodania jednego pensa.
- Rozszerzono scenariusz split reversal o pełne ponowne uzgodnienie obu źródeł.
- Potwierdzono niezmienność historycznych alokacji oraz oddzielenie historii `reversed` od aktywnego pokrycia.
- Zweryfikowano stan końcowy transakcji i raportu: pełne uzgodnienie, zero reszty oraz dokładne sumy bank/source allocations.
- Nie zmieniano kodu produkcyjnego — istniejące mechanizmy spełniły wszystkie przekrojowe niezmienniki.

## Główne pliki

- `tests/Feature/Finance/BankStatementCsvImportTest.php`
- `tests/Feature/Finance/BankReconciliationTest.php`
- `tests/Feature/Finance/BankReconciliationUndoTest.php`
- `.github/live-docs/current-task.md`
- `.github/live-docs/current-sprint.md`
- `.github/live-docs/status-dashboard.md`
- `docs/plans/uk-finance-tax-module-plan.md`

## Walidacja końcowa

- ✅ Nowe scenariusze: 3 pliki testowe, 24 passed / 159 assertions.
- ✅ Pełny bank suite: 48 passed / 326 assertions.
- ✅ `php artisan finance:audit-tenants --json`: `valid: true`, zero nieprawidłowych rekordów i relacji.
- ✅ Finance regression: 211 passed / 1 skipped / 1313 assertions.
- ✅ Full PHPUnit: 720 passed / 9 znanych baseline failures / 11 skipped / 2757 assertions.
- ✅ Laravel Pint dla wszystkich zmienionych plików PHP.
- ✅ Tłumaczenia: 555/555/555 kluczy liściowych PL/EN/PT.
- ✅ `git diff --check` bez błędów.

## Potwierdzone niezmienniki

- Suma aktywnych alokacji bankowych nie przekracza kwoty transakcji.
- Suma aktywnych `source_amount` dokładnie odtwarza kwotę dokumentu także po FX reversal i retry.
- Cofnięte alokacje pozostają w bazie jako historia, ale nie konsumują capacity ani nie wpływają na raport.
- Identyczne dane źródłowe nie łączą oddzielnych rachunków ani tenantów.
- Status `reconciled` i zero reszty wynikają wyłącznie z aktywnych batchy.

## Wdrożenie

1. UKFIN-409 nie dodaje migracji ani zmian kodu produkcyjnego.
2. Testy mogą zostać uruchomione na istniejącym schemacie po wdrożeniu UKFIN-401–408.
3. Deploy gate nadal powinien wykonywać `php artisan finance:audit-tenants --json`.

## Next steps

- UKFIN-501 — Trial Balance per period i comparison.

**Raport wygenerowany automatycznie przez Website Expert NI**
