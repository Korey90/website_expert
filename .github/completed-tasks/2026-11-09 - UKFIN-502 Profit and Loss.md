# 2026-11-09 - UKFIN-502 Profit & Loss

**Status:** Zakończone sukcesem
**Data realizacji:** 2026-11-09
**Czas trwania:** 1 etap roboczy

## Cel zadania

Udostępnić księgowemu tenantowy rachunek zysków i strat w GBP dla wybranego okresu księgowego, z opcjonalnym porównaniem, wariancją oraz przejrzystym wyliczeniem zysku brutto, operacyjnego, przed opodatkowaniem i netto.

## Zakres wykonanych prac

- Dodano serwis raportowy czytający wyłącznie tenantowy ledger `posted` i `reversed`.
- Agregacja oraz wszystkie działania arytmetyczne są wykonywane w integer pennies.
- Przychody są liczone jako `credit - debit`, a koszty jako `debit - credit`, z obsługą ujemnych korekt.
- Konta są klasyfikowane do sales, cost of sales, operating expenses, other income, other expenses i corporation tax.
- Dodano wyniki gross profit, operating profit, profit before tax oraz net profit/loss.
- Dodano opcjonalny okres porównawczy i wariancję na poziomie kont, sekcji oraz wyników.
- Historycznie nieaktywne konta z aktywnością pozostają widoczne.
- Dodano stronę Filament z filtrami, KPI, sekcjami, subtotalami i calculated totals.
- Dodano prywatny eksport CSV z metadanymi okresów, kontami, subtotalami i wynikami.
- Strona i CSV wymagają `view_finance`; Form Request i serwis niezależnie egzekwują tenant scope.
- Dodano tłumaczenia PL/EN/PT oraz testy obliczeń, korekt, reversal, pustego okresu, RBAC, tenant isolation, UI i CSV.

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

- `app/Services/Finance/ProfitAndLossService.php`
- `app/Http/Requests/Finance/ProfitAndLossReportRequest.php`
- `app/Http/Controllers/Finance/ProfitAndLossReportExportController.php`
- `app/Filament/Pages/ProfitAndLossPage.php`
- `app/Filament/Support/FilamentPermissionRegistry.php`
- `resources/views/filament/pages/profit-and-loss.blade.php`
- `routes/web/admin.php`
- `lang/pl/finance.php`
- `lang/en/finance.php`
- `lang/pt/finance.php`
- `tests/Feature/Finance/ProfitAndLossReportTest.php`
- `docs/plans/uk-finance-tax-module-plan.md`
- `.github/live-docs/current-task.md`
- `.github/live-docs/current-sprint.md`
- `.github/live-docs/status-dashboard.md`

## Walidacja końcowa

- ✅ PHP Coding Standard: Laravel Pint dla plików UKFIN-502.
- ✅ JavaScript / TypeScript: brak zmian w tym etapie; istniejąca modyfikacja landing page użytkownika pozostała nietknięta.
- ✅ Multi-language check: identyczne 631 kluczy liściowych `finance.php` w PL, EN i PT.
- ✅ Multi-tenancy compliance: `finance:audit-tenants --json` zwraca `valid: true` i zero błędów.
- ✅ Dedykowane testy UKFIN-502: 7 passed / 51 assertions.
- ✅ P&L + Trial Balance + General Ledger: 19 passed / 146 assertions.
- ✅ Finance regression: 224 passed / 1 skipped / 1420 assertions.
- ✅ Full validation: 733 passed / 9 znanych baseline failures / 11 skipped / 2864 assertions.
- ✅ Self-review, skan starych nazw firmy i `git diff --check` bez nowych problemów.

## Uwagi i rekomendacje

Raport jest okresowy i nie przenosi sald otwarcia kont wynikowych. Niestandardowe konta `income` inne niż subtype `sales` trafiają do other income; koszty `fx_loss` trafiają do other expenses, `corporation_tax` jest wydzielony, a pozostałe koszty do operating expenses. Dziewięć awarii pełnego pakietu odpowiada wcześniejszemu baseline i nie dotyczy modułu Finance.

## Next steps (opcjonalnie)

- `UKFIN-503` — Balance Sheet oparty na saldach zamknięcia ledgeru.

**Raport wygenerowany automatycznie przez WebsiteExpert**
