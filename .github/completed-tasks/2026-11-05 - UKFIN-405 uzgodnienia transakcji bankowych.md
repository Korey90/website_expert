# 2026-11-05 - UKFIN-405 uzgodnienia transakcji bankowych

**Status:** Zakończone sukcesem  
**Data realizacji:** 2026-11-05  
**Czas trwania:** jeden przyrost roboczy

## Cel zadania

Umożliwić bezpieczne przypisanie jednej lub wielu transakcji bankowych do jednej lub wielu istniejących wpłat i wypłat, z kontrolą pozostałych kwot, tenantów, kierunku i waluty.

## Zakres wykonanych prac

- Dodano tenantowe `BankReconciliation` i `BankReconciliationAllocation`.
- Dodano audytowalne batche i polymorphic allocations do customer/supplier payment oraz refund.
- Zaimplementowano exact, partial, split i many-to-many.
- Dodano blokady `lockForUpdate`, retry deadlocków i ochronę obu stron przed over-allocation.
- Rozszerzono matching o pozostałą kwotę transakcji i pozostałą kwotę źródła.
- Zachowano osobne kwoty bankowe i źródłowe dla źródeł walutowych ze snapshotem GBP.
- Dodano statusy `unmatched`, `partially_reconciled`, `reconciled`.
- Dodano Filament resource `/admin/bank-transactions` z filtrami, szczegółami i modalem wielu alokacji.
- Dodano policy i RBAC `view_finance` / `manage_finance`.
- Rozszerzono `finance:audit-tenants` o tabele, batch transaction i źródła polymorficzne.
- Dodano komplet tłumaczeń PL/EN/PT.
- Ujednolicono nazwę firmy w bazie, seederach, UI i dokumentacji do `Website Expert NI`.

## Użyte agenty i skille

- @BackendEngineer
- @DatabaseEngineer
- @FrontendEngineer
- @TestingEngineer
- @SecurityEngineer
- @DocumentationEngineer
- Skill: delta-analysis
- Skill: database-migration
- Skill: filament-resource
- Skill: test-generation
- Skill: multi-language-check
- Skill: code-review
- Skill: task-completion-report

## Zmodyfikowane / utworzone pliki

- `app/Actions/Finance/ReconcileBankTransactionAction.php`
- `app/Services/Finance/BankTransactionMatchingService.php`
- `app/Models/BankReconciliation.php`
- `app/Models/BankReconciliationAllocation.php`
- `app/Models/BankTransaction.php`
- `app/Filament/Resources/BankTransactionResource.php`
- `app/Filament/Resources/BankTransactionResource/Pages/*`
- `app/Policies/BankTransactionPolicy.php`
- `database/migrations/2026_11_05_000021_create_bank_reconciliations_tables.php`
- `lang/{pl,en,pt}/finance.php`
- `tests/Feature/Finance/BankReconciliationTest.php`
- `app/Console/Commands/AuditFinanceTenantData.php`

## Walidacja końcowa

- ✅ PHP Coding Standard: Laravel Pint.
- ⚠️ ESLint: brak nowych błędów; 3 istniejące błędy `__ENV` w pliku k6 `docs/python tests/load-test.js`.
- ✅ Multi-language check: 494 identyczne klucze liściowe PL/EN/PT.
- ✅ Multi-tenancy: audit lokalnej bazy przechodzi; nowe relacje są kontrolowane.
- ✅ Testy UKFIN-404/405: 15 passed / 92 assertions.
- ✅ Finance regression: 188 passed / 1 skipped / 1162 assertions.
- ✅ Full PHPUnit: 698 passed / 9 znanych baseline failures / 11 skipped / 2609 assertions.
- ✅ `git diff --check` bez błędów po finalizacji dokumentacji.

## Uwagi i rekomendacje

- Undo/reversal reconciliation jest świadomie pozostawione do UKFIN-407; bieżące batche mają status przygotowany pod ten workflow.
- Utworzenie nowego expense/payment z nieuzgodnionej transakcji należy do UKFIN-406.
- Produkcyjny deploy powinien wykonać `php artisan migrate --force`, następnie `php artisan finance:audit-tenants`.
- Poprzednia błędna nazwa prawna nie występuje już w repozytorium ani aktywnych rekordach firmy.

## Next steps (opcjonalnie)

- UKFIN-406 — Create expense/payment from unmatched transaction.
- UKFIN-407 — Undo reconciliation z audit trail.

**Raport wygenerowany automatycznie przez WebsiteExpert**
