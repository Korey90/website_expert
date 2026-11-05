# 2026-11-05 - Importer wyciągów bankowych CSV i deduplikacja

**Status:** Zakończone sukcesem  
**Data realizacji:** 2026-11-05  
**Czas trwania:** jeden etap implementacyjny

## Cel zadania

Dostarczyć `UKFIN-402` i niezbędną ochronę `UKFIN-403`: tenantowy importer wyciągów bankowych CSV z mapowaniem kolumn, brytyjskimi formatami dat i kwot, bezpiecznym dry-run oraz deduplikacją docelowych transakcji po provider ID i kanonicznym hashu.

## Zakres wykonanych prac

- Dodano parser CSV z wykrywaniem BOM, UTF-8/Windows-1252 oraz delimitera przecinek/średnik/tabulator.
- Dodano automatyczne i ręczne mapowanie booking/value date, amount albo debit/credit, currency, reference, counterparty i provider ID.
- Dodano normalizację dat UK i kwot z kropką lub przecinkiem dziesiętnym.
- Wprowadzono limity 5 MB, 5000 wierszy i 100 kolumn oraz błędy walidacji przypisane do numerów wierszy.
- Dry-run nie zapisuje partii, transakcji ani `last_synced_at`.
- Import właściwy zapisuje zminimalizowane dane transakcji i audyt partii; raw CSV nie trafia do bazy, a plik tymczasowy jest usuwany po operacji.
- Dodano tenantowe modele `BankStatementImport` i `BankTransaction`.
- Dodano deduplikację po provider ID i stabilnym SHA-256 danych kanonicznych, także wewnątrz jednego pliku.
- Dodano unique constraints i `insertOrIgnore`, aby baza chroniła również wyścigi równoległych importów.
- Rozszerzono audit tenantów o rachunek i partię importu.
- Dodano akcję importu na widoku rachunku bankowego oraz pełne tłumaczenia PL/EN/PT.
- Zaktualizowano główny plan: `UKFIN-402` i `UKFIN-403` ukończone.

## Użyte agenty i skille

- @BackendEngineer
- @DatabaseEngineer
- @FrontendEngineer
- @TestingEngineer
- Skill: delta-analysis
- Skill: database-migration
- Skill: laravel-action
- Skill: filament-resource
- Skill: test-generation
- Skill: multi-language-check
- Skill: task-completion-report

## Zmodyfikowane / utworzone pliki

- `app/Actions/Finance/ImportBankStatementCsvAction.php`
- `app/Data/Finance/BankStatementCsvError.php`
- `app/Data/Finance/BankStatementCsvParseResult.php`
- `app/Data/Finance/BankStatementCsvRow.php`
- `app/Data/Finance/BankStatementImportResult.php`
- `app/Services/Finance/BankStatementCsvParser.php`
- `app/Models/BankStatementImport.php`
- `app/Models/BankTransaction.php`
- `app/Models/BankAccount.php`
- `app/Models/Business.php`
- `app/Filament/Resources/BankAccountResource/Pages/ViewBankAccount.php`
- `app/Console/Commands/AuditFinanceTenantData.php`
- `database/migrations/2026_11_05_000020_create_bank_statement_imports_and_transactions_tables.php`
- `config/finance.php`
- `lang/{pl,en,pt}/finance.php`
- `tests/Feature/Finance/BankStatementCsvImportTest.php`
- `tests/Feature/Finance/AuditFinanceTenantDataCommandTest.php`
- `docs/plans/uk-finance-tax-module-plan.md`
- `docs/plans/uk-finance-tax-module-plan.html`

## Walidacja końcowa

- ✅ PHP Coding Standard: `vendor/bin/pint --dirty`.
- ✅ `git diff --check`.
- ✅ Tłumaczenia PL/EN/PT: identyczny zestaw kluczy, brakujące 0 / nadmiarowe 0.
- ✅ Migracja `2026_11_05_000020` zastosowana na lokalnym MySQL.
- ✅ `php artisan finance:audit-tenants`: 0 błędnych rekordów.
- ✅ Testy ukierunkowane: 19 passed / 106 assertions.
- ✅ Finance regression: 172 passed / 1 skipped / 938 assertions.
- ✅ Full regression: 681 passed / 11 skipped / 9 znanych baseline failures / 2382 assertions.
- ✅ Multi-tenancy: akcja, scope, FK, audit command i testy cross-tenant.

## Uwagi i rekomendacje

- Import częściowy zapisuje poprawne wiersze, a numery maksymalnie 10 pierwszych błędnych wierszy pokazuje w powiadomieniu.
- Hash fallback celowo bazuje na dacie, dacie waluty, kwocie, walucie, referencji i kontrahencie; provider ID ma osobny unique constraint.
- Błędy pełnej regresji są identyczne z baseline i nie dotyczą modułu bankowego.
- Pliki bankowe nie są zachowywane jako raw payload; w razie przyszłego wymagania audytowego należy użyć prywatnego `FinancialAttachment`, nie kolumny publicznej ani logów.

## Next steps

- `UKFIN-404`: scoring i reguły dopasowania transakcji do płatności/dokumentów po referencji, kwocie, dacie i kontrahencie.
- Następnie `UKFIN-405`: UI reconcile exact/partial/split/many-to-many.

**Raport wygenerowany automatycznie przez WebsiteExpert**
