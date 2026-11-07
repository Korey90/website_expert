# 2026-11-07 - UKFIN-501 Trial Balance per period i comparison

**Status:** Zakończone sukcesem
**Data realizacji:** 2026-11-07
**Czas trwania:** 1 etap roboczy

## Cel zadania

Udostępnić księgowemu tenantowy Trial Balance w GBP dla wybranego okresu księgowego: saldo otwarcia, obroty Winien/Ma, saldo końcowe oraz opcjonalne porównanie z zamknięciem innego okresu, z kontrolą bilansowania i eksportem CSV.

## Zakres wykonanych prac

- Dodano tenantowy serwis raportowy oparty na niezmiennych zapisach `journal_lines.debit_gbp` i `journal_lines.credit_gbp`.
- Obliczenia sald, obrotów, comparison i variance są prowadzone w integer pennies.
- Raport uwzględnia zapisy `posted` i `reversed`, dzięki czemu zachowuje pełny audit trail i prawidłowe saldo netto.
- Dodano stronę Filament z wyborem okresu bieżącego i opcjonalnego okresu porównawczego.
- Dodano sumy kontrolne Winien/Ma, różnicę zamknięcia, status bilansowania i liczniki kont oraz zapisów.
- Dodano prywatny eksport CSV z metadanymi okresów, wierszami kont i sumami.
- Strona i eksport wymagają `view_finance`; oba okresy są niezależnie walidowane względem aktywnego tenanta.
- Raport zachowuje widoczność historycznie nieaktywnych kont mających saldo lub obrót.
- Dodano komplet tłumaczeń PL/EN/PT.
- Dodano testy obliczeń, porównania, reversal, pustego okresu, RBAC, tenant isolation, UI i CSV.

## Użyte agenty i skille

- @BackendEngineer
- @FrontendEngineer
- @TestingEngineer
- @DocumentationEngineer
- Skill: delta-analysis
- Skill: sprint-planning
- Skill: before-new-sprint
- Skill: filament-resource
- Skill: test-generation
- Skill: multi-language-check
- Skill: task-completion-report

## Zmodyfikowane / utworzone pliki

- `app/Services/Finance/TrialBalanceService.php`
- `app/Http/Requests/Finance/TrialBalanceReportRequest.php`
- `app/Http/Controllers/Finance/TrialBalanceReportExportController.php`
- `app/Filament/Pages/TrialBalancePage.php`
- `app/Filament/Support/FilamentPermissionRegistry.php`
- `resources/views/filament/pages/trial-balance.blade.php`
- `routes/web/admin.php`
- `lang/pl/finance.php`
- `lang/en/finance.php`
- `lang/pt/finance.php`
- `tests/Feature/Finance/TrialBalanceReportTest.php`
- `docs/plans/uk-finance-tax-module-plan.md`
- `.github/live-docs/current-task.md`
- `.github/live-docs/current-sprint.md`
- `.github/live-docs/status-dashboard.md`

## Walidacja końcowa

- ✅ PHP Coding Standard: Laravel Pint dla plików UKFIN-501.
- ✅ JavaScript / TypeScript: brak zmian wymagających lintowania lub Prettier.
- ✅ Multi-language check: identyczne 596 kluczy liściowych `finance.php` w PL, EN i PT.
- ✅ Multi-tenancy compliance: `finance:audit-tenants --json` zwraca `valid: true` i zero błędów.
- ✅ Dedykowane testy UKFIN-501 + General Ledger: 12 passed / 95 assertions.
- ✅ Finance regression: 217 passed / 1 skipped / 1369 assertions.
- ✅ Full validation: 726 passed / 9 znanych baseline failures / 11 skipped / 2813 assertions.
- ✅ `git diff --check` oraz skan starych nazw firmy po usunięciu końcowych odstępów.

## Uwagi i rekomendacje

Trial Balance prezentuje ledger w GBP i nie przelicza ponownie kursów ani dokumentów źródłowych. Okres porównawczy oznacza saldo końcowe na jego dzień końcowy, a wariancja jest różnicą bieżącego salda signed debit-minus-credit i salda porównawczego. Dziewięć awarii pełnego pakietu jest identycznych z wcześniejszym baseline i nie dotyczy modułu Finance.

## Next steps (opcjonalnie)

- `UKFIN-502` — Profit & Loss z wyborem okresu i porównaniem.

**Raport wygenerowany automatycznie przez WebsiteExpert**
