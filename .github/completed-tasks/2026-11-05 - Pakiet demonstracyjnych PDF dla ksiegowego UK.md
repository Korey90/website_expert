# Pakiet demonstracyjnych PDF dla księgowego UK

**Status:** Zakończone  
**Data:** 2026-11-05  
**Czas realizacji:** jeden przyrost roboczy

## Cel

Przygotować spójny pakiet przykładowych dokumentów, który można przekazać rzeczywistemu księgowemu UK przed implementacją kolejnych etapów modułu finansowo-podatkowego.

## Zakres

- 18 anglojęzycznych PDF-ów: faktury, credit notes, receipt, bank reconciliation, VAT Return, VAT detail, P&L, Balance Sheet, Trial Balance, General Ledger, AR/AP ageing, expenses i audit trail.
- Dane firmy pobierane z `Legal Settings`, z `AccountingProfile` jako fallback.
- Fikcyjne transakcje na wspólnym okresie 1 lipca–30 września 2026.
- Watermark i klasyfikacja `DEMO / NOT FOR SUBMISSION`.
- README, checklista dla księgowego, dataset JSON, manifest SHA-256 oraz ZIP.
- Brak zapisu transakcji demonstracyjnych do bazy danych.

## Użyte role i umiejętności

- BackendEngineer — generator, komenda Artisan i integracja z istniejącymi szablonami PDF.
- TestingEngineer — test spójności matematycznej, integralności i regresja finansowa.
- DocumentationEngineer — instrukcja przekazania, checklista i aktualizacja live docs.
- `delta-analysis`, `test-generation`, `task-completion-report`, `multi-language-check`.

## Najważniejsze pliki

- `app/Actions/Finance/GenerateUkAccountantDemoPackAction.php`
- `app/Console/Commands/GenerateUkAccountantDemoPack.php`
- `resources/demo/uk-accountant-pack/fixture.php`
- `resources/views/pdf/demo/accountant-report.blade.php`
- `tests/Feature/Finance/UkAccountantDemoPackTest.php`
- `docs/demo/uk-accountant-pack/uk-accountant-demo-pack.zip`

## Walidacja

- Generator: 18 plików z nagłówkiem `%PDF-`, minimalnym rozmiarem i poprawnym SHA-256.
- Sumy kontrolne: VAT Return, P&L, Balance Sheet, Trial Balance i bank reconciliation są zgodne do pensa.
- Targeted PDF tests: 6 passed / 156 assertions.
- Finance regression: 180 passed / 1 skipped / 1116 assertions.
- Full regression: 689 passed / 9 znanych baseline failures / 11 skipped / 2556 assertions.
- Laravel Pint i `git diff --check`: bez nowych błędów.

## Uwagi i rekomendacje

- Wygenerowany pakiet używa danych `Website Expert NI` z `Legal Settings`, nie fikcyjnego wystawcy.
- Nazwa w `AccountingProfile` została zsynchronizowana do `Website Expert NI`; profil nadal nie ma numerów ani adresu. Pozostałe pola obu źródeł trzeba zsynchronizować przed wystawianiem dokumentów produkcyjnych.
- Numer firmy i VAT zostały przeniesione dokładnie z systemu; ich formalna poprawność nie była potwierdzana zewnętrznie.
- Dokumenty `06`–`17` są docelowymi podglądami do akceptacji, a nie deklaracją dostępności produkcyjnego eksportu.

## Następne kroki

1. Przekazać ZIP i checklistę księgowemu UK.
2. Zapisać jego uwagi jako kryteria akceptacji dla mapowania VAT i raportów.
3. Zsynchronizować `Legal Settings` i `AccountingProfile`.
4. Po review przejść do `UKFIN-405`.
