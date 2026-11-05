# 2026-11-05 - UKFIN-406 tworzenie zapisów z transakcji bankowych

**Status:** Zakończone sukcesem  
**Data realizacji:** 2026-11-05  
**Czas trwania:** jeden przyrost roboczy

## Cel zadania

Umożliwić utworzenie i zaksięgowanie płatności klienta albo wydatku dostawcy bezpośrednio z w pełni nieuzgodnionej transakcji bankowej, a następnie atomowo uzgodnić oba zapisy.

## Zakres wykonanych prac

- Dodano opcjonalne `bank_account_id` do customer i supplier payments wraz z tenantowymi relacjami.
- Posting płatności używa teraz cash ledger konkretnego rachunku bankowego; historyczne płatności bez rachunku zachowują fallback `1100`.
- Dodano atomową akcję dla wpływu: istniejąca zaksięgowana faktura → customer payment → posting → reconciliation.
- Dodano atomową akcję dla wypływu: wydatek z jednej pozycji gross-to-net VAT → approve → post → supplier payment → reconciliation.
- Obsłużono przepływy same-currency, rachunek GBP rozliczający dokument walutowy oraz rachunek walutowy z jawnym kursem GBP.
- Dodano kontrolę statusu `unmatched`, kierunku, kwot, walut, kursu, tax code, ledger account, aktywnego membership oraz uprawnień `manage_finance` i `post_finance`.
- Wszystkie dokumenty, journale, płatności i reconciliation są wycofywane razem, jeżeli dowolny etap zawiedzie.
- Dodano w Filament osobne akcje dla wpływu i wypływu z formularzami zależnymi od kierunku transakcji.
- Rozszerzono `finance:audit-tenants` o relacje rachunku na obu typach płatności.
- Dodano komplet tłumaczeń PL/EN/PT i testy domenowe, RBAC, tenant, FX, VAT, rollback oraz widoczność UI.

## Użyte role i skille

- @BackendEngineer
- @DatabaseEngineer
- @FrontendEngineer
- @TestingEngineer
- @DocumentationEngineer
- Skill: delta-analysis
- Skill: database-migration
- Skill: filament-resource
- Skill: test-generation
- Skill: multi-language-check
- Skill: code-review
- Skill: task-completion-report

## Zmodyfikowane / utworzone pliki

- `app/Actions/Finance/CreateRecordFromBankTransactionAction.php`
- `app/Actions/Finance/RecordInvoicePaymentAction.php`
- `app/Actions/Finance/RecordExpensePaymentAction.php`
- `app/Services/Finance/PaymentJournalPayloadFactory.php`
- `app/Filament/Resources/BankTransactionResource/Pages/ViewBankTransaction.php`
- `app/Models/BankAccount.php`
- `app/Models/Payment.php`
- `app/Models/ExpensePayment.php`
- `app/Console/Commands/AuditFinanceTenantData.php`
- `database/migrations/2026_11_05_000022_add_bank_account_to_finance_payments.php`
- `lang/{pl,en,pt}/finance.php`
- `tests/Feature/Finance/BankTransactionRecordCreationTest.php`
- `tests/Feature/Finance/AuditFinanceTenantDataCommandTest.php`

## Walidacja końcowa

- ✅ Migracja uruchomiona lokalnie; schema MySQL przyjęła oba klucze obce.
- ✅ PHP Coding Standard: Laravel Pint dla zmienionych plików.
- ⚠️ ESLint: brak nowych błędów; pozostają 3 istniejące błędy `__ENV` w `docs/python tests/load-test.js`.
- ✅ Multi-language check: 508 identycznych kluczy liściowych PL/EN/PT.
- ✅ Multi-tenancy: `php artisan finance:audit-tenants` przechodzi bez nieprawidłowych rekordów.
- ✅ Dedykowane testy tworzenia zapisów: 6 passed / 37 assertions.
- ✅ Rozszerzony audit i nowy flow: 14 passed / 57 assertions.
- ✅ Regresja payment posting + reconciliation + audit: 26 passed / 135 assertions.
- ✅ Finance regression: 196 passed / 1 skipped / 1205 assertions.
- ✅ Full PHPUnit: 705 passed / 9 znanych baseline failures / 11 skipped / 2649 assertions.
- ✅ `git diff --check` bez błędów po finalizacji dokumentacji.

## Granice bieżącego etapu

- Akcje tworzenia są dostępne wyłącznie dla transakcji bez jakichkolwiek alokacji; partial/split pozostaje w manualnym workflow UKFIN-405.
- Wpływ może utworzyć płatność tylko do istniejącej, zaksięgowanej faktury z dostępnym saldem.
- Wypływ tworzy pojedynczą pozycję wydatku; bardziej złożone koszty wielopozycyjne nadal tworzy się w standardowym formularzu Expense.
- Cofnięcie reconciliation z pełnym audit trail pozostaje zakresem UKFIN-407.

## Wdrożenie

1. Wykonać `php artisan migrate --force`.
2. Wykonać `php artisan finance:audit-tenants`.
3. Sprawdzić smoke test dla jednego wpływu i jednego wypływu na rachunku testowym.

## Next steps

- UKFIN-407 — Undo reconciliation z audit trail.
- UKFIN-408 — Bank balance/reconciliation report.

**Raport wygenerowany automatycznie przez Website Expert NI**
