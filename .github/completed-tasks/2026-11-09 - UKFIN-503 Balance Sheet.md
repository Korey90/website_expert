# 2026-11-09 - UKFIN-503 Balance Sheet

**Status:** Zakończone sukcesem
**Data realizacji:** 2026-11-09
**Czas trwania:** 1 etap roboczy

## Cel zadania

Udostępnić księgowemu tenantowy bilans w GBP na koniec wybranego okresu księgowego, z opcjonalnym porównaniem, wariancją i jawną kontrolą równania `aktywa = zobowiązania + kapitał`, również przed formalnym zamknięciem kont wynikowych.

## Zakres wykonanych prac

- Dodano serwis raportowy czytający wyłącznie tenantowy ledger `posted` i `reversed` do daty końcowej okresu.
- Aktywa są prezentowane jako `debit - credit`, a zobowiązania i kapitał jako `credit - debit`.
- Agregacja i wszystkie działania arytmetyczne są wykonywane w integer pennies.
- Dodano sekcje aktywów, zobowiązań i kapitału własnego wraz z subtotalami.
- Dodano jawne wiersze syntetyczne: wynik sprzed okresu oraz zysk/strata wybranego okresu.
- Niezamknięty wynik jest doliczany do kapitału, dzięki czemu bilans pozostaje sprawdzalny bez wygenerowanego closing journal.
- Dodano sumy: aktywa, zobowiązania, zaksięgowany kapitał, skumulowany wynik, kapitał razem, zobowiązania i kapitał razem oraz różnica kontrolna.
- Dodano opcjonalny okres porównawczy i wariancję per konto, sekcja oraz suma raportu.
- Konta korygujące aktywa zachowują ujemny znak; historycznie nieaktywne konta z zapisem pozostają widoczne.
- Dodano stronę Filament z filtrami, KPI, statusem zbilansowania i responsywną tabelą.
- Dodano prywatny eksport CSV z `Cache-Control: private, no-store`.
- Strona i CSV wymagają `view_finance`; Form Request i serwis niezależnie egzekwują tenant scope okresów i zapisów.
- Dodano tłumaczenia PL/EN/PT oraz testy obliczeń, porównania, reversal, pustego okresu, RBAC, tenant isolation, UI i CSV.
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

- `app/Services/Finance/BalanceSheetService.php`
- `app/Http/Requests/Finance/BalanceSheetReportRequest.php`
- `app/Http/Controllers/Finance/BalanceSheetReportExportController.php`
- `app/Filament/Pages/BalanceSheetPage.php`
- `app/Filament/Support/FilamentPermissionRegistry.php`
- `resources/views/filament/pages/balance-sheet.blade.php`
- `routes/web/admin.php`
- `lang/pl/finance.php`
- `lang/en/finance.php`
- `lang/pt/finance.php`
- `tests/Feature/Finance/BalanceSheetReportTest.php`
- `docs/plans/uk-finance-tax-module-plan.md`
- `.github/live-docs/current-task.md`
- `.github/live-docs/current-sprint.md`
- `.github/live-docs/status-dashboard.md`

## Walidacja końcowa

- ✅ PHP Coding Standard: Laravel Pint dla plików UKFIN-503.
- ✅ JavaScript / TypeScript: brak zmian w tym etapie; istniejący status ESLint bez zmian.
- ✅ Multi-language check: identyczne 673 klucze liściowe `finance.php` w PL, EN i PT; zero brakujących i dodatkowych kluczy.
- ✅ Multi-tenancy compliance: `finance:audit-tenants --json` zwraca `valid: true` i zero błędów.
- ✅ Dedykowane testy UKFIN-503: 6 passed / 61 assertions.
- ✅ Balance Sheet + P&L + Trial Balance + General Ledger: 25 passed / 207 assertions.
- ✅ Finance regression: 230 passed / 1 skipped / 1481 assertions.
- ✅ Full validation: 739 passed / 9 znanych baseline failures / 11 skipped / 2925 assertions.
- ✅ Trasa `admin.finance.balance-sheet.csv`, self-review, skan starych nazw firmy i `git diff --check` bez nowych problemów.

## Uwagi i rekomendacje

Bilans jest raportem „as at” i kumuluje zapisy od początku ledgeru. Jeśli wynik został formalnie zamknięty do konta retained earnings, closing entries wyzerują odpowiednią część niezamkniętego wyniku i przeniosą ją do zaksięgowanego kapitału. Do czasu zamknięcia raport pokazuje wynik w dwóch jawnych pozycjach syntetycznych. Dziewięć awarii pełnego pakietu odpowiada wcześniejszemu baseline i nie dotyczy modułu Finance.

## Next steps (opcjonalnie)

- `UKFIN-504` — Cash Flow w wersji zarządczej.

**Raport wygenerowany automatycznie przez Website Expert NI**
