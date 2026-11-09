# 2026-11-09 - UKFIN-505 Aged Receivables and Payables

**Status:** Zakończone sukcesem
**Data realizacji:** 2026-11-09
**Czas trwania:** 1 etap roboczy

## Cel zadania

Udostępnić księgowemu tenantowy raport wieku należności i zobowiązań w GBP na wybrany dzień, z podziałem według przeterminowania oraz jawnym uzgodnieniem sumy szczegółów z kontami kontrolnymi AR i AP.

## Zakres wykonanych prac

- Dodano serwis raportowy rekonstruujący historyczne saldo wyłącznie z journal lines `posted` i `reversed` do daty „as at”.
- AR jest obliczane jako debit minus credit na kontach `asset/accounts_receivable`, a AP jako credit minus debit na kontach `liability/accounts_payable`.
- Rozpoznawane są wszystkie konta kontrolne według podtypu, w tym konta niestandardowe i historycznie nieaktywne.
- Journal entries dla Invoice, Payment, CreditNote, Expense, ExpensePayment, PurchaseCreditNote i PurchaseCreditRefund są przypisywane do właściwego dokumentu źródłowego.
- Reversals dziedziczą źródło od odwracanego journalu i wpływają na saldo dopiero w swojej dacie.
- Dokumenty są dzielone na current, 1–30, 31–60, 61–90 oraz 91+ dni według terminu płatności.
- Płatności i korekty po dacie raportu nie zmieniają wcześniejszego obrazu ledgeru.
- Kwoty ujemne, nadpłaty i salda kredytowe pozostają widoczne.
- Ręczne i nierozpoznane zapisy na kontach kontrolnych są prezentowane jako ledger adjustments, z datą journalu jako zastępczą datą wymagalności.
- Każdy wiersz, bucket i suma są liczone w integer pennies; suma raportu jest porównywana z saldem odpowiedniego konta kontrolnego.
- Metadane dokumentów i kontrahentów są dostępne również dla soft-deleted records.
- Dodano stronę Filament z filtrem daty, KPI, metodologią, statusem uzgodnienia i oddzielnymi tabelami AR/AP.
- Dodano prywatny eksport CSV z `Cache-Control: private, no-store`.
- Strona i CSV wymagają `view_finance`; Form Request i serwis egzekwują aktywny tenant i ścisły format daty.
- Dodano tłumaczenia PL/EN/PT oraz testy obliczeń, wszystkich bucketów, płatności, korekt, refundów, reversals, adjustmentów, custom accounts, soft deletes, RBAC, UI, CSV i tenant isolation.
- Nie dodano migracji ani alternatywnego źródła sald.

## Użyte agenty i skille

- @BackendEngineer
- @FrontendEngineer
- @TestingEngineer
- @DocumentationEngineer
- Skill: delta-analysis
- Skill: filament-resource
- Skill: test-generation
- Skill: code-review
- Skill: multi-language-check
- Skill: task-completion-report

## Zmodyfikowane / utworzone pliki

- `app/Services/Finance/AgedReceivablesPayablesService.php`
- `app/Http/Requests/Finance/AgedReceivablesPayablesReportRequest.php`
- `app/Http/Controllers/Finance/AgedReceivablesPayablesReportExportController.php`
- `app/Filament/Pages/AgedReceivablesPayablesPage.php`
- `app/Filament/Support/FilamentPermissionRegistry.php`
- `resources/views/filament/pages/aged-receivables-payables.blade.php`
- `routes/web/admin.php`
- `lang/pl/finance.php`
- `lang/en/finance.php`
- `lang/pt/finance.php`
- `tests/Feature/Finance/AgedReceivablesPayablesReportTest.php`
- `docs/plans/uk-finance-tax-module-plan.md`
- `.github/live-docs/current-task.md`
- `.github/live-docs/current-sprint.md`
- `.github/live-docs/status-dashboard.md`

## Walidacja końcowa

- ✅ PHP Coding Standard: Laravel Pint dla plików UKFIN-505.
- ✅ JavaScript / TypeScript: brak zmian w tym etapie; istniejący status ESLint bez zmian.
- ✅ Multi-language check: identyczne 749 kluczy liściowych `finance.php` w PL, EN i PT; zero brakujących i dodatkowych kluczy.
- ✅ Multi-tenancy compliance: `finance:audit-tenants --json` zwraca `valid: true` i zero błędów.
- ✅ Dedykowane testy UKFIN-505: 5 passed / 63 assertions.
- ✅ Trial Balance + P&L + Balance Sheet + Management Cash Flow + Aged AR/AP + General Ledger: 35 passed / 337 assertions.
- ✅ Finance regression: 240 passed / 1 skipped / 1611 assertions.
- ✅ Full validation: 749 passed / 9 znanych baseline failures / 11 skipped / 3055 assertions.
- ✅ Trasa `admin.finance.aged-receivables-payables.csv`, self-review, skan starych nazw firmy i `git diff --check` bez nowych problemów.

## Uwagi i rekomendacje

Źródłem kwot historycznych jest niezmienny ledger, jednak termin płatności i nazwa kontrahenta są odczytywane z bieżących metadanych dokumentu. Zmiana `due_date` po zaksięgowaniu może więc przenieść historyczne saldo między bucketami bez zmiany łącznej kwoty i uzgodnienia. Ręczne lub nierozpoznane zapisy nie mają dokumentowego terminu płatności, dlatego są starzone od daty journalu i jawnie oznaczone jako adjustment. Dziewięć awarii pełnego pakietu odpowiada wcześniejszemu baseline i nie dotyczy modułu Finance.

## Next steps (opcjonalnie)

- `UKFIN-506` — VAT Detail: output/input VAT, tax codes i dokumenty źródłowe.

**Raport wygenerowany automatycznie przez Website Expert NI**
