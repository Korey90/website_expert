# 2026-11-07 - UKFIN-407 cofanie uzgodnień bankowych

**Status:** Zakończone sukcesem  
**Data realizacji:** 2026-11-07  
**Czas trwania:** jeden przyrost roboczy

## Cel zadania

Umożliwić bezpieczne cofnięcie wybranego reconciliation batch bez kasowania historii, ponownie udostępnić jego kwoty do dopasowania i atomowo przeliczyć status transakcji bankowej.

## Zakres wykonanych prac

- Dodano status `reversed` oraz `reversed_by`, `reversed_at` i `reversal_reason` do reconciliation batch.
- Dodano `UndoBankReconciliationAction` z aktywnym membership, `manage_finance`, transakcją DB i retry.
- Akcja blokuje bank transaction, batch, alokacje i wszystkie polymorficzne źródła przed zmianą statusu.
- Przed reversal weryfikowane są tenant, waluta, powiązanie transakcji oraz zgodność sumy batcha z niezmiennymi alokacjami.
- Retry z tym samym powodem jest idempotentne, a próba zmiany powodu zostaje odrzucona jako payload drift.
- Status transakcji jest przeliczany do `unmatched`, `partially_reconciled` albo `reconciled` z sumy wyłącznie aktywnych batchy.
- Odwrócone alokacje pozostają w bazie jako historia, ale nie pomniejszają kwot dostępnych w matching service.
- Modele reconciliation batch i allocation blokują bezpośrednią edycję oraz usuwanie.
- Filament pozwala wybrać aktywny batch, wymaga powodu i pokazuje status, autora, czas oraz uzasadnienie reversal.
- Rozszerzono `finance:audit-tenants` o kontrolę dozwolonych statusów i kompletności reversal metadata.
- Dodano komplet tłumaczeń PL/EN/PT oraz testy exact, partial, split, source reuse, idempotency, rollback, RBAC, tenant i UI.

## Zmodyfikowane / utworzone pliki

- `app/Actions/Finance/UndoBankReconciliationAction.php`
- `app/Models/BankReconciliation.php`
- `app/Models/BankReconciliationAllocation.php`
- `app/Filament/Resources/BankTransactionResource.php`
- `app/Filament/Resources/BankTransactionResource/Pages/ViewBankTransaction.php`
- `app/Console/Commands/AuditFinanceTenantData.php`
- `database/migrations/2026_11_07_000023_add_reversal_audit_to_bank_reconciliations.php`
- `lang/{pl,en,pt}/finance.php`
- `tests/Feature/Finance/BankReconciliationUndoTest.php`
- `tests/Feature/Finance/AuditFinanceTenantDataCommandTest.php`

## Walidacja końcowa

- ✅ Migracja `000023` uruchomiona lokalnie.
- ✅ Laravel Pint dla wszystkich zmienionych plików PHP.
- ⚠️ ESLint: wyłącznie 3 istniejące błędy `__ENV` w `docs/python tests/load-test.js`.
- ✅ Multi-language check: 521 identycznych kluczy liściowych PL/EN/PT.
- ✅ `php artisan finance:audit-tenants`: zero nieprawidłowych rekordów.
- ✅ Dedykowane testy UKFIN-407: 7 passed / 43 assertions.
- ✅ Reconciliation + undo + audit: 24 passed / 112 assertions.
- ✅ Finance regression: 204 passed / 1 skipped / 1251 assertions.
- ✅ Full PHPUnit: 713 passed / 9 znanych baseline failures / 11 skipped / 2695 assertions.
- ✅ `git diff --check` bez błędów.

## Granice bieżącego etapu

- Cofnięcie reconciliation nie usuwa i nie odwraca payment, expense ani journal entry; koryguje wyłącznie powiązanie bankowe.
- Historycznych batchy oraz alokacji nie można edytować ani usuwać.
- Raport salda i uzgodnienia bankowego pozostaje zakresem UKFIN-408.

## Wdrożenie

1. Wykonać `php artisan migrate --force`.
2. Wykonać `php artisan finance:audit-tenants`.
3. Sprawdzić cofnięcie pojedynczego i częściowego batcha na danych testowych.

## Next steps

- UKFIN-408 — Bank balance/reconciliation report.
- UKFIN-409 — Testy duplicates, split, FX, reversal i tenant isolation.

**Raport wygenerowany automatycznie przez Website Expert NI**
